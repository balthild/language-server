<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Diagnostics;

use Amp\Cancellation;
use Phpactor\LanguageServerProtocol\Diagnostic;
use Phpactor\LanguageServerProtocol\TextDocumentItem;

interface DiagnosticsProvider
{
	/**
	 * @return array<Diagnostic>
	 */
	public function provideDiagnostics(
		TextDocumentItem $textDocument,
		?Cancellation $cancel = null,
	): array;

	public function name(): string;
}
