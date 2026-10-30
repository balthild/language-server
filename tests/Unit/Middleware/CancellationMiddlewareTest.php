<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Middleware;

use Amp\PHPUnit\AsyncTestCase;
use Phpactor\LanguageServer\Core\Handler\MethodRunner;
use Phpactor\LanguageServer\Core\Middleware\RequestHandler;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Rpc\RequestMessage;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use Phpactor\LanguageServer\Middleware\CancellationMiddleware;
use Phpactor\LanguageServer\Middleware\ClosureMiddleware;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class CancellationMiddlewareTest extends AsyncTestCase
{
	use ProphecyTrait;

	/**
	 * @var ObjectProphecy<MethodRunner>
	 */
	private ObjectProphecy $runner;

	protected function setUp(): void
	{
		parent::setUp();
		$this->runner = $this->prophesize(MethodRunner::class);
	}

	public function testDelegatesToNextHandlerIfMessageIsNotNotification(): void
	{
		$response = $this->createMiddleware()->process(
			new RequestMessage(1, 'foobar', []),
			new RequestHandler([
				new ClosureMiddleware(static fn () => new ResponseMessage(1, null)),
			]),
		);

		self::assertInstanceOf(ResponseMessage::class, $response);
	}

	public function testDelegatesToNextHandlerIfMethodNotCancel(): void
	{
		$response = $this->createMiddleware()->process(
			new NotificationMessage('foobar', []),
			new RequestHandler([
				new ClosureMiddleware(static fn () => new ResponseMessage(1, null)),
			]),
		);

		self::assertInstanceOf(ResponseMessage::class, $response);
	}

	public function testCancelsRunningRequest(): void
	{
		$this->runner->cancelRequest(1)->shouldBeCalled();

		$response = $this->createMiddleware()->process(
			new NotificationMessage('$/cancelRequest', [
				'id' => 1,
			]),
			new RequestHandler([]),
		);

		self::assertNull($response);
	}

	private function createMiddleware(): CancellationMiddleware
	{
		return new CancellationMiddleware($this->runner->reveal());
	}
}
