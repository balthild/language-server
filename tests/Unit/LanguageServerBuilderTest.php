<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit;

use Phpactor\LanguageServer\Core\Dispatcher\Factory\ClosureDispatcherFactory;
use Phpactor\LanguageServer\Core\Server\LanguageServer;
use Phpactor\LanguageServer\LanguageServerBuilder;
use Phpactor\TestUtils\PHPUnit\TestCase;

class LanguageServerBuilderTest extends TestCase
{
	public function testBuild(): void
	{
		$factory = new ClosureDispatcherFactory(static function (): void {});
		$server = LanguageServerBuilder::create($factory)
			->tcpServer('127.0.0.1:8888')
			->build();

		$this->assertInstanceOf(LanguageServer::class, $server);
	}
}
