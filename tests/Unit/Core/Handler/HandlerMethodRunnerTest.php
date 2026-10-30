<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Core\Handler;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\PHPUnit\AsyncTestCase;
use Phpactor\LanguageServer\Core\Handler\ClosureHandler;
use Phpactor\LanguageServer\Core\Handler\HandlerMethodRunner;
use Phpactor\LanguageServer\Core\Handler\Handlers;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Rpc\RequestMessage;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;

class HandlerMethodRunnerTest extends AsyncTestCase
{
	public function testExceptionIfNotRequestOrNotification(): void
	{
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Message must either be a ');

		$runner = $this->createRunner([
			new ClosureHandler('foobar', static fn () => 'foobar'),
		]);
		$runner->dispatch(
			new ResponseMessage(1, 'foobar'),
		);
	}

	public function testReturnsNullIfMessageIsANotification(): void
	{
		$dispatcher = $this->createRunner([
			new ClosureHandler('foobar', static function (): void {}),
		]);

		$response = $dispatcher->dispatch(
			new NotificationMessage('foobar', []),
		);

		self::assertNull($response);
	}

	public function testReturnsValueFromHandler(): void
	{
		$dispatcher = $this->createRunner([
			new ClosureHandler('foobar', static fn (string $bar) => 'foobar: ' . $bar),
		]);

		$response = $dispatcher->dispatch(
			new RequestMessage(2, 'foobar', ['bar' => 'foo']),
		);

		self::assertInstanceOf(ResponseMessage::class, $response);
		self::assertEquals('foobar: foo', $response->result);
		self::assertEquals(2, $response->id);
	}

	public function testToleratesTryingToCancelNonRunningRequest(): void
	{
		$dispatcher = $this->createRunner([
			new ClosureHandler('foobar', static function (): void {
				\Amp\delay(0.01);
			}),
		]);

		\Amp\async(static fn () => $dispatcher->dispatch(
			new RequestMessage(1, 'foobar', ['bar' => 'foo']),
		));

		\Amp\delay(0.005);

		$dispatcher->cancelRequest(2);
		$this->addToAssertionCount(1);
	}

	public function testCancelsRequest(): void
	{
		$this->expectException(CancelledException::class);

		$dispatcher = $this->createRunner([
			new ClosureHandler('foobar', static function (string $bar, Cancellation $token): void {
				\Amp\delay(0.1);
				$token->throwIfRequested();
			}),
		]);

		$response = \Amp\async(static fn () => $dispatcher->dispatch(
			new RequestMessage(1, 'foobar', ['bar' => 'foo']),
		));

		\Amp\delay(0);
		$dispatcher->cancelRequest(1);

		$response->await();
	}

	private function createRunner(array $handlers): HandlerMethodRunner
	{
		return new HandlerMethodRunner(
			new Handlers(...$handlers),
		);
	}
}
