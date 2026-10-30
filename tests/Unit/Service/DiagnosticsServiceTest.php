<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Service;

use Phpactor\LanguageServer\Core\Diagnostics\ClosureDiagnosticsProvider;
use Phpactor\LanguageServer\Core\Diagnostics\DiagnosticsEngine;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\LanguageServerTesterBuilder;
use Phpactor\LanguageServer\Service\DiagnosticsService;
use Phpactor\LanguageServer\Test\ProtocolFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class DiagnosticsServiceTest extends TestCase
{
	public function testService(): void
	{
		$tester = LanguageServerTesterBuilder::createBare()
			->enableServices()
			->enableTextDocuments();

		$service = new DiagnosticsService(
			new DiagnosticsEngine(
				$tester->clientApi(),
				new NullLogger(),
				[
					new ClosureDiagnosticsProvider(static fn () => [
						ProtocolFactory::diagnostic(
							ProtocolFactory::range(0, 0, 0, 0),
							'Foobar is bust',
						),
					]),
				],
				0,
			),
			true,
			true,
			$tester->workspace(),
		);

		$tester->addServiceProvider($service);
		$tester->addListenerProvider($service);

		$tester = $tester->build();
		$tester->services()->start('diagnostics');
		$tester->textDocument()->open('file:///foobar', 'barfoo');
		\Amp\delay(0.1);
		$tester->textDocument()->update('file:///foobar', 'barfoo');
		\Amp\delay(0.1);
		$notification = $tester->transmitter()->shift();
		\assert($notification instanceof NotificationMessage);
		self::assertEquals('textDocument/publishDiagnostics', $notification->method);

		$tester->textDocument()->save('file:///foobar');
		\Amp\delay(0.1);
		$notification = $tester->transmitter()->shift();
		\assert($notification instanceof NotificationMessage);
		self::assertEquals('textDocument/publishDiagnostics', $notification->method);
	}
}
