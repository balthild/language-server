<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Middleware;

use Phpactor\LanguageServer\Core\Handler\MethodRunner;
use Phpactor\LanguageServer\Core\Middleware\Middleware;
use Phpactor\LanguageServer\Core\Middleware\RequestHandler;
use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Rpc\RequestMessage;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;

class HandlerMiddleware implements Middleware
{
	public function __construct(private MethodRunner $runner) {}

	public function process(Message $request, RequestHandler $handler): ?ResponseMessage
	{
		if (!$request instanceof RequestMessage && !$request instanceof NotificationMessage) {
			return $handler->handle($request);
		}

		return $this->runner->dispatch($request);
	}
}
