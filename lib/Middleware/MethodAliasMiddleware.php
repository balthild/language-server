<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Middleware;

use Phpactor\LanguageServer\Core\Middleware\Middleware;
use Phpactor\LanguageServer\Core\Middleware\RequestHandler;
use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Rpc\RequestMessage;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;

class MethodAliasMiddleware implements Middleware
{
	/**
	 * @param array<string, string> $aliasMap
	 */
	public function __construct(private array $aliasMap) {}

	/**
	 * {@inheritDoc}
	 */
	public function process(Message $request, RequestHandler $handler): ?ResponseMessage
	{
		if (!$request instanceof RequestMessage && !$request instanceof NotificationMessage) {
			return $handler->handle($request);
		}

		if (isset($this->aliasMap[$request->method])) {
			$request->method = $this->aliasMap[$request->method];
		}

		return $handler->handle($request);
	}
}
