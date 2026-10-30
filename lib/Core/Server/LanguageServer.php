<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server;

use Amp\Future;
use Phpactor\LanguageServer\Core\Dispatcher\Dispatcher;
use Phpactor\LanguageServer\Core\Dispatcher\DispatcherFactory;
use Phpactor\LanguageServer\Core\Rpc\Exception\CouldNotCreateMessage;
use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\RequestMessageFactory;
use Phpactor\LanguageServer\Core\Rpc\ResponseError;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use Phpactor\LanguageServer\Core\Server\Exception\ExitSession;
use Phpactor\LanguageServer\Core\Server\Exception\ShutdownServer;
use Phpactor\LanguageServer\Core\Server\Parser\LspMessageReader;
use Phpactor\LanguageServer\Core\Server\StreamProvider\Connection;
use Phpactor\LanguageServer\Core\Server\StreamProvider\ResourceStreamProvider;
use Phpactor\LanguageServer\Core\Server\StreamProvider\SocketStreamProvider;
use Phpactor\LanguageServer\Core\Server\StreamProvider\StreamProvider;
use Phpactor\LanguageServer\Core\Server\Transmitter\ConnectionMessageTransmitter;
use Phpactor\LanguageServer\Core\Server\Transmitter\MessageTransmitter;
use Psr\Log\LoggerInterface;
use Revolt\EventLoop;

final class LanguageServer
{
	/**
	 * @var Connection[]
	 */
	private array $connections = [];

	public function __construct(
		private DispatcherFactory $dispatcherFactory,
		private LoggerInterface $logger,
		private StreamProvider $streamProvider,
		private Initializer $initializer,
		private ServerStats $stats = new ServerStats(),
	) {}

	/**
	 * Start the language server only. Event loop is not started.
	 *
	 * Blocks until the language server stops.
	 */
	public function start(): void
	{
		$this->listenForConnections();
	}

	/**
	 * Register signal handlers and run the language server in the event loop.
	 */
	public function run(): void
	{
		// Signals are not supported on Windows
		if (\defined('SIGINT')) {
			EventLoop::onSignal(\SIGINT, function (string $watcherId): void {
				EventLoop::cancel($watcherId);
				\Amp\async($this->shutdown(...));
			});
		}

		EventLoop::setErrorHandler(function (\Throwable $error): void {
			if ($error instanceof ShutdownServer) {
				EventLoop::getDriver()->stop();
				return;
			}

			$this->logger->critical($error->getMessage());
			throw $error;
		});

		$this->start();

		// With a STDIO connection start() returns as soon as the single
		// connection has been accepted, so the loop is needed to serve it.
		EventLoop::run();
	}

	/**
	 * Returns the address of the server if the server is running
	 * as a socket server, otherwise throws an exception.
	 *
	 * @throws \RuntimeException
	 */
	public function address(): ?string
	{
		if (!$this->streamProvider instanceof SocketStreamProvider) {
			throw new \RuntimeException(\sprintf(
				'Cannot get address on non-socket stream provider, using "%s"',
				$this->streamProvider::class,
			));
		}

		return $this->streamProvider->address();
	}

	public function shutdown(): void
	{
		$this->logger->info('Shutting down');

		$futures = [];
		foreach ($this->connections as $connection) {
			$futures[] = \Amp\async(static fn () => $connection->stream()->end());
		}

		if ($futures) {
			Future\awaitAny($futures);
		}

		$this->streamProvider->close();
	}

	private function listenForConnections(): void
	{
		if ($this->streamProvider instanceof SocketStreamProvider) {
			$this->logger->info(\sprintf(
				'Listening on %s',
				$this->streamProvider->address(),
			));
		}

		// accept incoming connections (in the case of a TCP server this is
		// a connection, with a STDIO stream this just returns the stream
		// immediately)
		while (($connection = $this->streamProvider->accept()) !== null) {
			$this->stats->incConnectionCount();

			// create a reference to the connection so that we can later
			// terminate it if necessary
			$this->connections[$connection->id()] = $connection;

			\Amp\async(function () use ($connection): void {
				$this->handle($connection);
				$this->stats->decConnectionCount();
			});
		}
	}

	private function handle(Connection $connection): void
	{
		$transmitter = new ConnectionMessageTransmitter($connection);
		$reader = new LspMessageReader($connection->stream(), $this->logger);
		$dispatcher = null;

		// wait for the next request
		while (null !== ($request = $reader->readMessage())) {
			$this->stats->incRequestCount();

			try {
				$request = RequestMessageFactory::fromRequest($request);
			} catch (CouldNotCreateMessage $e) {
				$transmitter->transmit(new ResponseMessage(
					$request->body()['id'] ?? 0,
					[],
					new ResponseError(255, $e->getMessage()),
				));
				continue;
			}

			// initialize the dispatcher with the initialize parameters
			// (for example to allow a container to boot with the client
			// capabilities)
			if (null === $dispatcher) {
				$dispatcher = $this->dispatcherFactory->create(
					$transmitter,
					$this->initializer->provideInitializeParams($request),
				);
			}

			$this->dispatchRequest($transmitter, $dispatcher, $connection, $request);
		}
	}

	private function dispatchRequest(
		MessageTransmitter $transmitter,
		Dispatcher $dispatcher,
		Connection $connection,
		Message $request,
	): void {
		\Amp\async(function () use ($transmitter, $request, $dispatcher, $connection): void {
			try {
				try {
					$response = $dispatcher->dispatch($request);
				} catch (ExitSession $e) {
					$connection->stream()->end();

					if ($this->streamProvider instanceof ResourceStreamProvider) {
						throw new ShutdownServer('Exit called on STDIO connection, exiting the server');
					}
					return;
				}

				if (null === $response) {
					return;
				}

				$transmitter->transmit($response);
			} catch (ShutdownServer $error) {
				$this->logger->info($error->getMessage());
				EventLoop::getDriver()->stop();
			}
		});
	}
}
