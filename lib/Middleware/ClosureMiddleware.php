<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Middleware;

use Phpactor\LanguageServer\Core\Middleware\Middleware;
use Phpactor\LanguageServer\Core\Middleware\RequestHandler;
use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;

class ClosureMiddleware implements Middleware
{
	public function __construct(private \Closure $closure) {}

	/**
	 * {@inheritDoc}
	 */
	public function process(Message $request, RequestHandler $handler): ?ResponseMessage
	{
		return ($this->closure)($request, $handler);
	}
}
