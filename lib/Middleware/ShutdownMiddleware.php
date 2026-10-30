<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Middleware;

use Phpactor\LanguageServer\Adapter\Psr\NullEventDispatcher;
use Phpactor\LanguageServer\Core\Middleware\Middleware;
use Phpactor\LanguageServer\Core\Middleware\RequestHandler;
use Phpactor\LanguageServer\Core\Rpc\ErrorCodes;
use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Rpc\RequestMessage;
use Phpactor\LanguageServer\Core\Rpc\ResponseError;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use Phpactor\LanguageServer\Core\Server\Exception\ExitSession;
use Phpactor\LanguageServer\Event\WillShutdown;
use Psr\EventDispatcher\EventDispatcherInterface;

final class ShutdownMiddleware implements Middleware
{
	const METHOD_SHUTDOWN = 'shutdown';
	const METHOD_EXIT = 'exit';

	private bool $shuttingDown = false;

	public function __construct(
		private EventDispatcherInterface $eventDispatcher = new NullEventDispatcher(),
		private int $gracePeriod = 500,
	) {}

	public function process(Message $request, RequestHandler $handler): ?ResponseMessage
	{
		if ($request instanceof NotificationMessage) {
			if ($request->method === self::METHOD_EXIT) {
				throw new ExitSession('Exit method invoked by client');
			}

			if ($this->shuttingDown) {
				return null;
			}
		}

		if ($request instanceof RequestMessage) {
			if ($this->shuttingDown) {
				return new ResponseMessage(
					$request->id,
					null,
					new ResponseError(
						ErrorCodes::INVALID_REQUEST,
						'Server is currently shutting down, cannot serve requests',
					),
				);
			}

			if ($request->method === self::METHOD_SHUTDOWN) {
				$this->shuttingDown = true;
				return $this->shutdown($request);
			}
		}

		return $handler->handle($request);
	}

	public function shutdown(RequestMessage $request): ResponseMessage
	{
		$this->eventDispatcher->dispatch(new WillShutdown());
		\Amp\delay($this->gracePeriod / 1000);

		return new ResponseMessage(
			$request->id,
			null,
		);
	}
}
