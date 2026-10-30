<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Dispatcher\ArgumentResolver;

use Phpactor\LanguageServer\Core\Dispatcher\ArgumentResolver;
use Phpactor\LanguageServer\Core\Dispatcher\Exception\CouldNotResolveArguments;
use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Rpc\RequestMessage;

final class LanguageSeverProtocolParamsResolver implements ArgumentResolver
{
	/**
	 * {@inheritDoc}
	 */
	public function resolveArguments(object $object, string $method, Message $message): array
	{
		if (!$message instanceof RequestMessage && !$message instanceof NotificationMessage) {
			return [];
		}

		$reflection = new \ReflectionClass($object);
		$arguments = $message->params;

		if (!$reflection->hasMethod($method)) {
			throw new CouldNotResolveArguments(\sprintf(
				'Class "%s" has no method "%s"',
				$object::class,
				$method,
			));
		}

		foreach ($reflection->getMethod($method)->getParameters() as $parameter) {
			if (!$parameter->getType()) {
				continue;
			}

			$type = $parameter->getType();

			if (!$type instanceof \ReflectionNamedType) {
				continue;
			}

			/** @var class-string */
			$classFqn = $type->getName();

			if ($classFqn === RequestMessage::class && $message instanceof RequestMessage) {
				return [$message];
			}

			if ($classFqn === NotificationMessage::class && $message instanceof NotificationMessage) {
				return [$message];
			}

			if (\preg_match('{^Phpactor\\\LanguageServerProtocol\\\.*Params$}', $classFqn)) {
				$reflection = new \ReflectionClass($classFqn);
				return [$reflection->getMethod('fromArray')->invoke(null, $arguments, true)];
			}

			throw new CouldNotResolveArguments(\sprintf(
				'First argument of LSP class "%s" method "%s" must be the LSP param object, it is "%s"',
				$object::class,
				$method,
				$classFqn,
			));
		}

		throw new CouldNotResolveArguments(\sprintf(
			'Class "%s" method "%s" is not a language server protocol-accepting method',
			$object::class,
			$method,
		));
	}
}
