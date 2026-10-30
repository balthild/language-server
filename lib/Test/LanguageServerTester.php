<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Test;

use Amp\Future;
use Phpactor\LanguageServer\Core\Dispatcher\Dispatcher;
use Phpactor\LanguageServer\Core\Dispatcher\DispatcherFactory;
use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Rpc\RequestMessage;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use Phpactor\LanguageServer\Core\Server\Transmitter\LspMessageSerializer;
use Phpactor\LanguageServer\Core\Server\Transmitter\MessageSerializer;
use Phpactor\LanguageServer\Core\Server\Transmitter\TestMessageTransmitter;
use Phpactor\LanguageServer\Test\LanguageServerTester\ServicesTester;
use Phpactor\LanguageServer\Test\LanguageServerTester\TextDocumentTester;
use Phpactor\LanguageServer\Test\LanguageServerTester\WorkspaceTester;
use Phpactor\LanguageServerProtocol\InitializeParams;
use Phpactor\LanguageServerProtocol\InitializeResult;

final class LanguageServerTester
{
	private Dispatcher $dispatcher;

	public function __construct(
		DispatcherFactory $factory,
		private InitializeParams $initializeParams,
		private TestMessageTransmitter $transmitter = new TestMessageTransmitter(),
		private MessageSerializer $messageSerializer = new LspMessageSerializer(),
	) {
		$this->dispatcher = $factory->create($this->transmitter, $initializeParams);
	}

	/**
	 * @return Future<ResponseMessage|null>
	 */
	public function dispatch(Message $message): Future
	{
		/** @var Future<ResponseMessage|null> $future */
		// @mago-expect lint:inline-variable-return
		$future = \Amp\async(fn (): ?ResponseMessage => $this->dispatcher->dispatch($message));

		return $future;
	}

	public function dispatchAndWait(Message $message): ?ResponseMessage
	{
		return $this->dispatch($message)->await();
	}

	/**
	 * @param array|object $params
	 * @param int|string $id
	 * @return Future<ResponseMessage|null>
	 */
	public function request(string $method, $params, $id = null): Future
	{
		$requestMessage = new RequestMessage(
			$id ?: \uniqid(),
			$method,
			$this->normalizeParams($params),
		);

		return $this->dispatch($requestMessage);
	}

	/**
	 * @param array|object $params
	 * @param int|string $id
	 */
	public function requestAndWait(string $method, $params, $id = null): ?ResponseMessage
	{
		return $this->request($method, $this->normalizeParams($params), $id)->await();
	}

	/**
	 * @param array|object $params
	 */
	public function mustRequestAndWait(
		string $method,
		mixed $params,
		int|string|null $id = null,
	): ResponseMessage {
		$response = $this->requestAndWait($method, $params);

		if (null === $response) {
			throw new \RuntimeException(\sprintf(
				'Expected request to method "%s" to return a response, but it did not!',
				$method,
			));
		}

		return $response;
	}

	/**
	 * @param mixed $value
	 * @param string|int $id
	 * @return Future<mixed>
	 */
	public function respond($id, $value): Future
	{
		$requestMessage = new ResponseMessage($id, $value);

		return $this->dispatch($requestMessage);
	}

	/**
	 * @param mixed $value
	 * @param string|int $id
	 * @return mixed
	 */
	public function respondAndWait($id, $value)
	{
		return $this->respond($id, $value)->await();
	}

	/**
	 * @param array|object $params
	 * @return Future<ResponseMessage|null>
	 */
	public function notify(string $method, $params): Future
	{
		$notifyMessage = new NotificationMessage($method, $this->normalizeParams($params));

		return $this->dispatch($notifyMessage);
	}

	/**
	 * @param array|object $params
	 */
	public function notifyAndWait(string $method, $params): void
	{
		$this->notify($method, $this->normalizeParams($params))->await();
	}

	public function transmitter(): TestMessageTransmitter
	{
		return $this->transmitter;
	}

	/**
	 * Initialize the server using the initialization parameters provided when
	 * this class was instantiated and return the processed ServerCapabilties.
	 */
	public function initialize(): InitializeResult
	{
		$response = $this->mustRequestAndWait('initialize', $this->initializeParams);
		$this->assertSuccess($response);
		$this->notifyAndWait('initialized', []);

		$result = $response->result;
		if (!$result instanceof InitializeResult) {
			throw new \RuntimeException(\sprintf(
				'Initialize did not return an InitializeResult, got "%s"',
				\get_debug_type($result),
			));
		}

		return $result;
	}

	public function services(): ServicesTester
	{
		return new ServicesTester($this);
	}

	public function textDocument(): TextDocumentTester
	{
		return new TextDocumentTester($this);
	}

	/**
	 * Assert the the response is successful.
	 *
	 * @throws \RuntimeException if not successful.
	 */
	public function assertSuccess(ResponseMessage $response): void
	{
		if ($response->error) {
			throw new \RuntimeException(\sprintf(
				'Response has error: %s' . "\n" . '%s',
				$response->error->message,
				\is_string($response->error->data)
					? $response->error->data
					: \json_encode($response->error->data, \JSON_PRETTY_PRINT),
			));
		}
	}

	public function cancel(int $requestId): void
	{
		$this->dispatchAndWait(new NotificationMessage('$/cancelRequest', ['id' => $requestId]));
	}

	public function workspace(): WorkspaceTester
	{
		return new WorkspaceTester($this);
	}

	/**
	 * @param array|object $params
	 * @return array<string,mixed>
	 */
	private function normalizeParams($params): array
	{
		if (\is_array($params)) {
			return $params;
		}

		$params = $this->messageSerializer->normalize($params);
		if (!\is_array($params)) {
			throw new \RuntimeException(\sprintf(
				'Could not normalize params, returned as type %s',
				\gettype($params),
			));
		}

		return $params;
	}
}
