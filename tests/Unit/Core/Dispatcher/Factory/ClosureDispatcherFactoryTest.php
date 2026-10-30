<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Core\Dispatcher\Factory;

use Phpactor\LanguageServer\Core\Dispatcher\Dispatcher;
use Phpactor\LanguageServer\Core\Dispatcher\Dispatcher\ClosureDispatcher;
use Phpactor\LanguageServer\Core\Dispatcher\Factory\ClosureDispatcherFactory;
use Phpactor\LanguageServer\Core\Server\Transmitter\NullMessageTransmitter;
use Phpactor\LanguageServer\Test\ProtocolFactory;
use PHPUnit\Framework\TestCase;

class ClosureDispatcherFactoryTest extends TestCase
{
	public function testReturnsDispatcher(): void
	{
		$dispatcher = $this
			->createDispatcherFactory(static fn () => new ClosureDispatcher(static fn () => null))
			->create(new NullMessageTransmitter(), ProtocolFactory::initializeParams());

		$this->assertInstanceOf(Dispatcher::class, $dispatcher);
	}

	public function testExceptionIfNotReturningDispatcher(): void
	{
		$this->expectException(\RuntimeException::class);
		$this
			->createDispatcherFactory(static fn () => new \stdClass())
			->create(new NullMessageTransmitter(), ProtocolFactory::initializeParams());
	}

	private function createDispatcherFactory(\Closure $closure): ClosureDispatcherFactory
	{
		return new ClosureDispatcherFactory($closure);
	}
}
