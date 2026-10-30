<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Example\CodeAction;

use Amp\Cancellation;
use Phpactor\LanguageServer\Core\CodeAction\CodeActionProvider;
use Phpactor\LanguageServerProtocol\CodeAction;
use Phpactor\LanguageServerProtocol\CodeActionKind;
use Phpactor\LanguageServerProtocol\Command;
use Phpactor\LanguageServerProtocol\Range;
use Phpactor\LanguageServerProtocol\TextDocumentItem;

class SayHelloCodeActionProvider implements CodeActionProvider
{
	public function provideActionsFor(
		TextDocumentItem $textDocument,
		Range $range,
		?Cancellation $cancel = null,
	): array {
		return [
			CodeAction::fromArray([
				'title' => 'Alice',
				'command' => new Command(
					'Hello Alice',
					'phpactor.say_hello',
					[
						'Alice',
					],
				),
			]),
			CodeAction::fromArray([
				'title' => 'Bob',
				'command' => new Command(
					'Hello Bob',
					'phpactor.say_hello',
					[
						'Bob',
					],
				),
			]),
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function kinds(): array
	{
		return [CodeActionKind::QUICK_FIX];
	}

	public function describe(): string
	{
		return 'says hello!';
	}
}
