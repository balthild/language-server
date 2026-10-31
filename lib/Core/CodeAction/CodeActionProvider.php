<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\CodeAction;

use Amp\Cancellation;
use Phpactor\LanguageServerProtocol\CodeAction;
use Phpactor\LanguageServerProtocol\Range;
use Phpactor\LanguageServerProtocol\TextDocumentItem;

interface CodeActionProvider
{
	/**
	 * @return array<CodeAction>
	 */
	public function provideActionsFor(
		TextDocumentItem $textDocument,
		Range $range,
		?Cancellation $cancel = null,
	): array;

	/**
	 * Return the kinds of actions that this provider can return, for example
	 * "refactor.extract", "quickfix", etc.
	 *
	 * @see Phpactor\LanguageServerProtocol\CodeAction
	 *
	 * @return list<string>
	 */
	public function kinds(): array;

	public function describe(): string;
}
