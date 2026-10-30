<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Core\Server\Initializer;

use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Rpc\RequestMessage;
use Phpactor\LanguageServer\Core\Server\Initializer\RequestInitializer;
use Phpactor\LanguageServerProtocol\InitializeParams;
use PHPUnit\Framework\TestCase;

class RequestInitializerTest extends TestCase
{
	public function testThrowsExceptionIfMessageIsNotARequestMessage(): void
	{
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('First request must be a RequestMessage');
		(new RequestInitializer())->provideInitializeParams(new NotificationMessage('foobar'));
	}

	public function testIfMessageNotInitialize(): void
	{
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Method of first request must be "initialize');
		(new RequestInitializer())->provideInitializeParams(new RequestMessage(1, 'foobar', []));
	}

	public function testReturnsCapabilitiesFromRequest(): void
	{
		$capabiltiies = (new RequestInitializer())->provideInitializeParams(new RequestMessage(
			1,
			'initialize',
			[
				'capabilities' => [
					'experimental' => [],
				],
			],
		));
		self::assertInstanceOf(InitializeParams::class, $capabiltiies);
		self::assertEquals([], $capabiltiies->capabilities->experimental);
	}
}
