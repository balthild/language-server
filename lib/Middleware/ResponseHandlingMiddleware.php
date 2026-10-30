<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Middleware;

use Phpactor\LanguageServer\Core\Middleware\Middleware;
use Phpactor\LanguageServer\Core\Middleware\RequestHandler;
use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use Phpactor\LanguageServer\Core\Server\ResponseWatcher;

class ResponseHandlingMiddleware implements Middleware
{
	public function __construct(private ResponseWatcher $watcher) {}

	/**
	 * {@inheritDoc}
	 */
	public function process(Message $request, RequestHandler $handler): ?ResponseMessage
	{
		if ($request instanceof ResponseMessage) {
			$this->watcher->handle($request);
			return null;
		}

		return $handler->handle($request);
	}
}
