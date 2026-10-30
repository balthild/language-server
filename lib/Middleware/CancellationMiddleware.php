<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Middleware;

use Phpactor\LanguageServer\Core\Handler\MethodRunner;
use Phpactor\LanguageServer\Core\Middleware\Middleware;
use Phpactor\LanguageServer\Core\Middleware\RequestHandler;
use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;

class CancellationMiddleware implements Middleware
{
	const METHOD_CANCEL_REQUEST = '$/cancelRequest';

	public function __construct(private MethodRunner $runner) {}

	/**
	 * {@inheritDoc}
	 */
	public function process(Message $message, RequestHandler $handler): ?ResponseMessage
	{
		if (
			$message instanceof NotificationMessage
			&& $message->method === self::METHOD_CANCEL_REQUEST
		) {
			$id = $message->params['id'] ?? null;

			if (!\is_int($id) && !\is_string($id)) {
				throw new \RuntimeException('ID parameter not present in cancel notification');
			}

			$this->runner->cancelRequest($id);
			return null;
		}

		return $handler->handle($message);
	}
}
