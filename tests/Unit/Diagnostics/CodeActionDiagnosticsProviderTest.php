<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Diagnostics;

use Amp\Cancellation;
use Phpactor\LanguageServer\Core\CodeAction\CodeActionProvider;
use Phpactor\LanguageServer\Diagnostics\CodeActionDiagnosticsProvider;
use Phpactor\LanguageServer\LanguageServerTesterBuilder;
use Phpactor\LanguageServer\Test\ProtocolFactory;
use Phpactor\LanguageServerProtocol\CodeAction;
use Phpactor\LanguageServerProtocol\Range;
use Phpactor\LanguageServerProtocol\TextDocumentItem;
use PHPUnit\Framework\TestCase;

class CodeActionDiagnosticsProviderTest extends TestCase
{
	public function testProvidesDiagnostics(): void
	{
		$tester = LanguageServerTesterBuilder::create();
		$tester->addDiagnosticsProvider(new CodeActionDiagnosticsProvider(
			new TestCodeActionProvider(),
		));

		$tester = $tester->build();
		$tester->initialize();

		$tester->textDocument()->open('file:///foobar', 'barfoo');

		\Amp\delay(0.01);

		self::assertEquals(2, $tester->transmitter()->count());

		$tester->textDocument()->update('file:///foobar', 'bar');
		\Amp\delay(0.01);

		self::assertEquals(5, $tester->transmitter()->count());
		self::assertEquals(
			'textDocument/publishDiagnostics',
			$tester->transmitter()->shiftNotification()->method,
		);
	}
}

class TestCodeActionProvider implements CodeActionProvider
{
	public function provideActionsFor(
		TextDocumentItem $textDocument,
		Range $range,
		?Cancellation $cancel = null,
	): array {
		return [
			CodeAction::fromArray([
				'title' => 'Bar',
				'diagnostics' => [
					ProtocolFactory::diagnostic(
						ProtocolFactory::range(0, 0, 0, 0),
						'Foobar',
					),
				],
			]),
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function kinds(): array
	{
		return ['foo'];
	}

	public function describe(): string
	{
		return 'test';
	}
}
