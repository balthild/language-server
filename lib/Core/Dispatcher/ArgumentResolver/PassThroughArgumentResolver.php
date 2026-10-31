<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Dispatcher\ArgumentResolver;

use Phpactor\LanguageServer\Core\Dispatcher\ArgumentResolver;
use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Rpc\RequestMessage;

final class PassThroughArgumentResolver implements ArgumentResolver
{
	/**
	 * {@inheritDoc}
	 */
	public function resolveArguments(object $object, string $method, Message $message): array
	{
		if ($message instanceof RequestMessage || $message instanceof NotificationMessage) {
			return $message->params ?? [];
		}

		return [];
	}
}
