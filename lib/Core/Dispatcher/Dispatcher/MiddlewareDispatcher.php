<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Dispatcher\Dispatcher;

use Phpactor\LanguageServer\Core\Dispatcher\Dispatcher;
use Phpactor\LanguageServer\Core\Middleware\Middleware;
use Phpactor\LanguageServer\Core\Middleware\RequestHandler;
use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;

final class MiddlewareDispatcher implements Dispatcher
{
	/**
	 * @var Middleware[]
	 */
	private array $middleware;

	public function __construct(Middleware ...$middleware)
	{
		$this->middleware = $middleware;
	}

	/**
	 * {@inheritDoc}
	 */
	public function dispatch(Message $request): ?ResponseMessage
	{
		$handler = new RequestHandler($this->middleware);
		return $handler->handle($request);
	}
}
