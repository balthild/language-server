<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Middleware;

use Amp\PHPUnit\AsyncTestCase;
use Phpactor\LanguageServer\Core\Handler\HandlerNotFound;
use Phpactor\LanguageServer\Core\Middleware\RequestHandler;
use Phpactor\LanguageServer\Core\Rpc\ErrorCodes;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Rpc\RequestMessage;
use Phpactor\LanguageServer\Core\Rpc\ResponseError;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use Phpactor\LanguageServer\Core\Server\Exception\ExitSession;
use Phpactor\LanguageServer\Middleware\ClosureMiddleware;
use Phpactor\LanguageServer\Middleware\ErrorHandlingMiddleware;
use Psr\Log\Test\TestLogger;

class ErrorHandlingMiddlewareTest extends AsyncTestCase
{
	private TestLogger $logger;

	protected function setUp(): void
	{
		parent::setUp();
		$this->logger = new TestLogger();
	}

	public function testDelegatesToNextHandlerIfMessageIsNotRequest(): void
	{
		$response = $this->createMiddleware()->process(
			new NotificationMessage('foobar'),
			new RequestHandler([
				new ClosureMiddleware(static fn () => new ResponseMessage(1, null)),
			]),
		);

		self::assertInstanceOf(ResponseMessage::class, $response);
		self::assertEquals([], $this->logger->records);
	}

	public function testRethrowsServerControlException(): void
	{
		$this->expectException(ExitSession::class);

		$this->createMiddleware()->process(
			new NotificationMessage('foobar'),
			new RequestHandler([
				new ClosureMiddleware(static function (): void {
					throw new ExitSession('please');
				}),
			]),
		);
	}

	public function testLogsAndReturnsNullForNoticationException(): void
	{
		$response = $this->createMiddleware()->process(
			new NotificationMessage('foobar'),
			new RequestHandler([
				new ClosureMiddleware(static function (): void {
					throw new \RuntimeException('please');
				}),
			]),
		);

		self::assertNull($response);
		self::assertTrue($this->logger->hasErrorThatContains('please'));
	}

	public function testDoesNotLogButReturnsErrorResponseForRequest(): void
	{
		$response = $this->createMiddleware()->process(
			new RequestMessage(1, 'foobar', []),
			new RequestHandler([
				new ClosureMiddleware(static function (): void {
					throw new \RuntimeException('please');
				}),
			]),
		);

		self::assertInstanceOf(ResponseMessage::class, $response);
		\assert($response instanceof ResponseMessage);
		self::assertInstanceOf(ResponseError::class, $response->error);
		self::assertStringContainsString(
			'Exception [RuntimeException] please',
			$response->error->message,
		);
		self::assertEquals(ErrorCodes::INTERNAL_ERROR, $response->error->code);
	}

	public function testMETHOD_NOT_FOUNDCodeForHandlerNotFound(): void
	{
		$response = $this->createMiddleware()->process(
			new RequestMessage(1, 'foobar', []),
			new RequestHandler([
				new ClosureMiddleware(static function (): void {
					throw new HandlerNotFound('please');
				}),
			]),
		);

		self::assertInstanceOf(ResponseMessage::class, $response);
		\assert($response instanceof ResponseMessage);
		self::assertInstanceOf(ResponseError::class, $response->error);
		self::assertStringContainsString(
			'Exception [Phpactor\LanguageServer\Core\Handler\HandlerNotFound] please',
			$response->error->message,
		);
		self::assertEquals(ErrorCodes::METHOD_NOT_FOUND, $response->error->code);
	}

	private function createMiddleware(): ErrorHandlingMiddleware
	{
		return new ErrorHandlingMiddleware($this->logger);
	}
}
