<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Middleware;

use Phpactor\LanguageServer\Core\Middleware\Exception\MiddlewareTerminated;
use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;

final class RequestHandler
{
	public function __construct(
		/** @var array<Middleware> */
		private array $queue = [],
	) {}

	public function handle(Message $request): ?ResponseMessage
	{
		$middleware = \array_shift($this->queue);

		if (!$middleware) {
			throw new MiddlewareTerminated('Middleware terminated (no middleware handled the request)');
		}

		\assert($middleware instanceof Middleware);

		return $middleware->process($request, $this);
	}
}
