<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Middleware;

use Amp\PHPUnit\AsyncTestCase;
use Phpactor\LanguageServer\Core\Middleware\Exception\MiddlewareTerminated;
use Phpactor\LanguageServer\Core\Middleware\Middleware;
use Phpactor\LanguageServer\Core\Middleware\RequestHandler;
use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Middleware\ClosureMiddleware;
use Phpactor\LanguageServer\Middleware\MethodAliasMiddleware;

class MethodAliasMiddlewareTest extends AsyncTestCase
{
	public function testDelegatesToNextHandlerIfMessageIsNotRequest(): void
	{
		$this->expectException(MiddlewareTerminated::class);
		self::assertNull(
			$this->createMiddleware()->process(
				new NotificationMessage('foobar'),
				new RequestHandler(),
			),
		);
	}

	public function testChangesMethodName(): void
	{
		$this->createMiddleware([
			'foobar' => 'barfoo',
		])->process(
			new NotificationMessage('foobar'),
			new RequestHandler([
				new ClosureMiddleware(static function (Message $message, RequestHandler $handler) {
					self::assertEquals('barfoo', $message->method);
					return null;
				}),
			]),
		);
	}

	public function testIgnoresUnmappedMethod(): void
	{
		$this->createMiddleware([
			'boobar' => 'barfoo',
		])->process(
			new NotificationMessage('foobar'),
			new RequestHandler([
				new ClosureMiddleware(static function (Message $message, RequestHandler $handler) {
					self::assertEquals('foobar', $message->method);
					return null;
				}),
			]),
		);
	}

	private function createMiddleware($aliasMap = []): Middleware
	{
		return new MethodAliasMiddleware($aliasMap);
	}
}
