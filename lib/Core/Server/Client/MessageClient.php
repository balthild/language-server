<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server\Client;

use Phpactor\LanguageServer\Core\Server\RpcClient;
use Phpactor\LanguageServerProtocol\MessageType;

final class MessageClient
{
	public function __construct(private RpcClient $client, private string $method) {}

	public function error(string $message): void
	{
		$this->sendMessage(MessageType::ERROR, $message);
	}

	public function warning(string $message): void
	{
		$this->sendMessage(MessageType::WARNING, $message);
	}

	public function info(string $message): void
	{
		$this->sendMessage(MessageType::INFO, $message);
	}

	public function log(string $message): void
	{
		$this->sendMessage(MessageType::LOG, $message);
	}

	private function sendMessage(int $messageType, string $message): void
	{
		$this->client->notification($this->method, [
			'type' => $messageType,
			'message' => $message,
		]);
	}
}
