<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Handler;

use Amp\DeferredCancellation;
use Phpactor\LanguageServer\Core\Dispatcher\ArgumentResolver;
use Phpactor\LanguageServer\Core\Dispatcher\ArgumentResolver\PassThroughArgumentResolver;
use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Rpc\RequestMessage;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class HandlerMethodRunner implements MethodRunner
{
	/**
	 * @var array<string|int, DeferredCancellation>
	 */
	private array $cancellations = [];

	public function __construct(
		private Handlers $handlers,
		private ArgumentResolver $argumentResolver = new PassThroughArgumentResolver(),
		private LoggerInterface $logger = new NullLogger(),
		private HandlerMethodResolver $resolver = new HandlerMethodResolver(),
	) {}

	public function dispatch(Message $request): ?ResponseMessage
	{
		if (!$request instanceof NotificationMessage && !$request instanceof RequestMessage) {
			throw new \RuntimeException(\sprintf(
				'Message must either be a Notification or a Request, got "%s"',
				$request::class,
			));
		}

		$handler = $this->handlers->get($request->method);
		$method = $this->resolver->resolveHandlerMethod($handler, $request->method);

		$cancellationTokenSource = new DeferredCancellation();

		// we only cancel requests (that have IDs) and not notifications
		if ($request instanceof RequestMessage) {
			$this->cancellations[$request->id] = $cancellationTokenSource;
		}

		$args = \array_values($this->argumentResolver->resolveArguments($handler, $method, $request));
		$args[] = $cancellationTokenSource->getCancellation();

		$result = $handler->$method(...$args);

		if (!$request instanceof RequestMessage) {
			return null;
		}

		return new ResponseMessage($request->id, $result);
	}

	/**
	 * @param string|int $id
	 */
	public function cancelRequest($id): void
	{
		if (!isset($this->cancellations[$id])) {
			$this->logger->warning(\sprintf(
				'Trying to cancel non-running request "%s", running requests: "%s"',
				$id,
				\implode('", "', \array_keys($this->cancellations)),
			));
			return;
		}

		$tokenSource = $this->cancellations[$id];
		\assert($tokenSource instanceof DeferredCancellation);
		$tokenSource->cancel();
	}
}
