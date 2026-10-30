<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Core\Diagnostics;

use Amp\DeferredCancellation;
use Amp\PHPUnit\AsyncTestCase;
use Phpactor\LanguageServer\Core\Diagnostics\ClosureDiagnosticsProvider;
use Phpactor\LanguageServer\Core\Diagnostics\DiagnosticsEngine;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\LanguageServerTesterBuilder;
use Phpactor\LanguageServer\Test\ProtocolFactory;
use Phpactor\LanguageServerProtocol\TextDocumentItem;
use Psr\Log\NullLogger;

class DiagnosticsEngineTest extends AsyncTestCase
{
	public function testPublishesDiagnostics(): void
	{
		$tester = LanguageServerTesterBuilder::create();
		$engine = $this->createEngine($tester, 0, 0);

		$token = new DeferredCancellation();
		\Amp\async(static fn () => $engine->run($token->getCancellation()));

		$engine->enqueue(
			ProtocolFactory::textDocumentItem('file:///foobar', 'foobar'),
		);

		\Amp\delay(0.01);

		$token->cancel();

		$notification = $tester->transmitter()->shiftNotification();
		self::assertNotNull($notification, 'Notification sent');
		self::assertEquals('textDocument/publishDiagnostics', $notification->method);
		self::assertEquals([], $notification->params['diagnostics'] ?? null);

		$notification = $tester->transmitter()->shiftNotification();
		self::assertNotNull($notification, 'Notification sent');
		self::assertEquals('textDocument/publishDiagnostics', $notification->method);
		/** @phpstan-ignore-next-line */
		self::assertEquals(
			'Foobar is broken',
			$notification->params['diagnostics'][0]->message ?? null,
		);
	}

	public function testOnlyPublishesForMostRecentFile(): void
	{
		$tester = LanguageServerTesterBuilder::create();
		$engine = $this->createEngine($tester, 0, 0);

		$token = new DeferredCancellation();
		\Amp\async(static fn () => $engine->run($token->getCancellation()));

		$engine->enqueue(ProtocolFactory::textDocumentItem('file:///foobar', 'foobar'));
		$engine->enqueue(ProtocolFactory::textDocumentItem('file:///barfoo', 'foobar'));
		$engine->enqueue(ProtocolFactory::textDocumentItem('file:///bazbar', 'foobar'));

		\Amp\delay(0.001);

		$token->cancel();

		// includes reset diagnostic
		self::assertEquals(2, $tester->transmitter()->count());
	}

	public function testDoesNotProcessMoreThanOneDocument(): void
	{
		$tester = LanguageServerTesterBuilder::create();
		$engine = $this->createEngine($tester, 1, 0);

		$token = new DeferredCancellation();
		\Amp\async(static fn () => $engine->run($token->getCancellation()));

		$engine->enqueue(ProtocolFactory::textDocumentItem('file:///foobar', 'foobar'));
		$engine->enqueue(ProtocolFactory::textDocumentItem('file:///foobar', 'foobar'));
		$engine->enqueue(ProtocolFactory::textDocumentItem('file:///foobar', 'foobar'));

		\Amp\delay(0.01);

		$token->cancel();

		// clear + publish
		self::assertEquals(2, $tester->transmitter()->count());
	}

	public function testSleepPreventsSeige(): void
	{
		$tester = LanguageServerTesterBuilder::create();
		$engine = $this->createEngine($tester, 5, 10);

		$token = new DeferredCancellation();
		\Amp\async(static fn () => $engine->run($token->getCancellation()));

		$engine->enqueue(ProtocolFactory::textDocumentItem('file:///foobar', 'bazboo'));
		$engine->enqueue(ProtocolFactory::textDocumentItem('file:///barfoo', 'foobar'));
		$engine->enqueue(ProtocolFactory::textDocumentItem('file:///bazbar', 'foobar'));

		\Amp\delay(0.1);

		$token->cancel();

		self::assertEquals(2, $tester->transmitter()->count());
	}

	public function testAggregatesResultsFromMultipleProviders(): void
	{
		$tester = LanguageServerTesterBuilder::create();
		$engine = new DiagnosticsEngine(
			$tester->clientApi(),
			new NullLogger(),
			[
				new ClosureDiagnosticsProvider(static fn (TextDocumentItem $item) => [
					ProtocolFactory::diagnostic(ProtocolFactory::range(0, 0, 0, 0), 'Foobar is broken'),
				]),
				new ClosureDiagnosticsProvider(static fn (TextDocumentItem $item) => [
					ProtocolFactory::diagnostic(ProtocolFactory::range(0, 0, 0, 0), 'Barfoo is broken'),
				]),
			],
			10,
		);

		$token = new DeferredCancellation();
		\Amp\async(static fn () => $engine->run($token->getCancellation()));

		$engine->enqueue(ProtocolFactory::textDocumentItem('file:///foobar', 'bazboo'));

		\Amp\delay(0.1);

		self::assertEquals(3, $tester->transmitter()->count());
	}

	public function testHandlesLinterExceptions(): void
	{
		$tester = LanguageServerTesterBuilder::create();
		$engine = new DiagnosticsEngine(
			$tester->clientApi(),
			new NullLogger(),
			[
				new ClosureDiagnosticsProvider(static function (TextDocumentItem $item): void {
					throw new \Exception('oh dear');
				}),
			],
			10,
		);

		$token = new DeferredCancellation();
		\Amp\async(static fn () => $engine->run($token->getCancellation()));

		$engine->enqueue(ProtocolFactory::textDocumentItem('file:///foobar', 'bazboo'));

		\Amp\delay(0.1);

		self::assertEquals(2, $tester->transmitter()->count());
		$notification = $tester->transmitter()->shiftNotification();
		$notification = $tester->transmitter()->shiftNotification();
		\assert($notification instanceof NotificationMessage);
		self::assertEquals('window/showMessage', $notification->method);
		self::assertStringContainsString('oh dear', (string) ($notification->params['message'] ?? ''));
	}

	/**
	 * Note that this test was added in relation to the race condition in
	 * https://github.com/phpactor/phpactor/issues/1974
	 *
	 * It DOES NOT reproduce the race condition sadly.
	 *
	 * See the commit this change was introduced to see what it SHOULD have covered.
	 */
	public function testAlwaysAnalyzesTheLastChangeLast(): void
	{
		$tester = LanguageServerTesterBuilder::create();
		$lastDocument = '';
		$engine = $this->createEngine($tester, 5, 0, $lastDocument);

		$token = new DeferredCancellation();
		\Amp\async(static fn () => $engine->run($token->getCancellation()));
		\Amp\delay(0.001);

		$engine->enqueue(ProtocolFactory::textDocumentItem('file:///foobar', '1', version: 1));
		\Amp\delay(0.001);
		$engine->enqueue(ProtocolFactory::textDocumentItem('file:///foobar', '2', version: 2));
		\Amp\delay(0.01);
		$engine->enqueue(ProtocolFactory::textDocumentItem('file:///foobar', '3', version: 3));
		\Amp\delay(0.001);
		$engine->enqueue(ProtocolFactory::textDocumentItem('file:///foobar', '4', version: 4));
		\Amp\delay(0.001);
		$engine->enqueue(ProtocolFactory::textDocumentItem('file:///foobar', '5', version: 5));

		\Amp\delay(0.1);

		$token->cancel();

		self::assertEquals('5', $lastDocument);
	}

	private function createEngine(
		LanguageServerTesterBuilder $tester,
		int $delayMs = 0,
		int $sleepTime = 0,
		?string &$lastDocument = null,
	): DiagnosticsEngine {
		return new DiagnosticsEngine(
			$tester->clientApi(),
			new NullLogger(),
			[
				new ClosureDiagnosticsProvider(static function (TextDocumentItem $item) use (
					$delayMs,
					&$lastDocument,
				) {
					if ($delayMs) {
						\Amp\delay($delayMs / 1000);
					}
					$lastDocument = $item->text;

					return [
						ProtocolFactory::diagnostic(ProtocolFactory::range(0, 0, 0, 0), 'Foobar is broken'),
					];
				}),
			],
			$sleepTime,
		);
	}
}
