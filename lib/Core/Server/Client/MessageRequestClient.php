<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server\Client;

use Phpactor\LanguageServer\Core\Server\RpcClient;
use Phpactor\LanguageServerProtocol\MessageActionItem;
use Phpactor\LanguageServerProtocol\MessageType;

final class MessageRequestClient
{
	public function __construct(private RpcClient $client) {}

	public function error(string $message, MessageActionItem ...$actions): ?MessageActionItem
	{
		return $this->requestMessage(MessageType::ERROR, $message, ...$actions);
	}

	public function warning(string $message, MessageActionItem ...$actions): ?MessageActionItem
	{
		return $this->requestMessage(MessageType::WARNING, $message, ...$actions);
	}

	public function info(string $message, MessageActionItem ...$actions): ?MessageActionItem
	{
		return $this->requestMessage(MessageType::INFO, $message, ...$actions);
	}

	public function log(string $message, MessageActionItem ...$actions): ?MessageActionItem
	{
		return $this->requestMessage(MessageType::LOG, $message, ...$actions);
	}

	private function requestMessage(
		int $messageType,
		string $message,
		MessageActionItem ...$actions,
	): ?MessageActionItem {
		$response = $this->client->request('window/showMessageRequest', [
			'type' => $messageType,
			'message' => $message,
			'actions' => $actions,
		]);

		$result = $response->result;

		if (null === $result) {
			return null;
		}

		if (!\is_array($result)) {
			throw new \RuntimeException(\sprintf(
				'Expected "window/showMessageRequest" to return an object, got "%s"',
				\get_debug_type($result),
			));
		}

		return MessageActionItem::fromArray($result, true);
	}
}
