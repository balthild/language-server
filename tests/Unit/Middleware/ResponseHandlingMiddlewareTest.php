<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Middleware;

use Amp\PHPUnit\AsyncTestCase;
use Phpactor\LanguageServer\Core\Middleware\RequestHandler;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use Phpactor\LanguageServer\Core\Server\ResponseWatcher;
use Phpactor\LanguageServer\Core\Server\ResponseWatcher\DeferredResponseWatcher;
use Phpactor\LanguageServer\Middleware\ClosureMiddleware;
use Phpactor\LanguageServer\Middleware\ResponseHandlingMiddleware;

class ResponseHandlingMiddlewareTest extends AsyncTestCase
{
	private ResponseWatcher $watcher;

	protected function setUp(): void
	{
		parent::setUp();
		$this->watcher = new DeferredResponseWatcher();
	}

	public function testDelegatesToNextHandlerIfMessageIsNotResponse(): void
	{
		$response = $this->createMiddleware()->process(
			new NotificationMessage('foobar'),
			new RequestHandler([
				new ClosureMiddleware(static fn () => new ResponseMessage(1, null)),
			]),
		);

		self::assertInstanceOf(ResponseMessage::class, $response);
	}

	public function testResolvesResponsePromise(): void
	{
		$future = $this->watcher->waitForResponse(1);
		$response = new ResponseMessage(1, 'foobar');

		$responseResponse = $this->createMiddleware()->process(
			$response,
			new RequestHandler([
				new ClosureMiddleware(static fn () => new ResponseMessage(1, null)),
			]),
		);

		$resolvedResponse = $future->await();

		self::assertNull($responseResponse);
		self::assertSame($response, $resolvedResponse);
	}

	public function testResolves(): void
	{
		$response = $this->createMiddleware()->process(
			new NotificationMessage('foobar'),
			new RequestHandler([
				new ClosureMiddleware(static fn () => new ResponseMessage(1, null)),
			]),
		);

		self::assertInstanceOf(ResponseMessage::class, $response);
	}

	private function createMiddleware(): ResponseHandlingMiddleware
	{
		return new ResponseHandlingMiddleware($this->watcher);
	}
}
