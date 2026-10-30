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
	 * @return Future<?ResponseMessage>
	 */
	public function dispatch(Message $message): Future
	{
		return \Amp\async(fn () => $this->dispatcher->dispatch($message));
	}

	public function dispatchAndWait(Message $message): ?ResponseMessage
	{
		return $this->dispatch($message)->await();
	}

	/**
	 * @param array<string, mixed>|object $params
	 * @param int|string|null $id
	 * @return Future<?ResponseMessage>
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
	 * @param array<string, mixed>|object $params
	 * @param int|string|null $id
	 */
	public function requestAndWait(
		string $method,
		array|object $params,
		int|string|null $id = null,
	): ?ResponseMessage {
		return $this->request($method, $this->normalizeParams($params), $id)->await();
	}

	/**
	 * @param array<string, mixed>|object $params
	 */
	public function mustRequestAndWait(
		string $method,
		array|object $params,
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
	 * @param string|int $id
	 * @param mixed $value
	 * @return Future<mixed>
	 */
	public function respond(string|int $id, $value): Future
	{
		$requestMessage = new ResponseMessage($id, $value);

		return $this->dispatch($requestMessage);
	}

	/**
	 * @param string|int $id
	 * @param mixed $value
	 * @return mixed
	 */
	public function respondAndWait(string|int $id, $value)
	{
		return $this->respond($id, $value)->await();
	}

	/**
	 * @param array<string, mixed>|object $params
	 * @return Future<?ResponseMessage>
	 */
	public function notify(string $method, array|object $params): Future
	{
		$notifyMessage = new NotificationMessage($method, $this->normalizeParams($params));

		return $this->dispatch($notifyMessage);
	}

	/**
	 * @param array<string, mixed>|object $params
	 */
	public function notifyAndWait(string $method, array|object $params): void
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
	 * @param array<string, mixed>|object $params
	 * @return array<string, mixed>
	 */
	private function normalizeParams(array|object $params): array
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
