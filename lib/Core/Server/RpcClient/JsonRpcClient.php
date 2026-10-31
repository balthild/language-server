<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server\RpcClient;

use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Rpc\RequestMessage;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use Phpactor\LanguageServer\Core\Server\ResponseWatcher;
use Phpactor\LanguageServer\Core\Server\RpcClient;
use Phpactor\LanguageServer\Core\Server\Transmitter\MessageTransmitter;
use Ramsey\Uuid\Uuid;

final class JsonRpcClient implements RpcClient
{
	public function __construct(
		private MessageTransmitter $transmitter,
		private ResponseWatcher $responseWatcher,
	) {}

	/**
	 * @inheritDoc
	 */
	public function notification(string $method, array $params): void
	{
		$this->transmitter->transmit(new NotificationMessage($method, $params));
	}

	/**
	 * @inheritDoc
	 */
	public function request(string $method, array $params): ResponseMessage
	{
		$requestId = (string) Uuid::uuid4();
		$response = $this->responseWatcher->waitForResponse($requestId);
		$this->transmitter->transmit(new RequestMessage($requestId, $method, $params));

		return $response->await();
	}
}
