<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Example\Diagnostics;

use Amp\Cancellation;
use Phpactor\LanguageServer\Core\Diagnostics\DiagnosticsProvider;
use Phpactor\LanguageServerProtocol\Diagnostic;
use Phpactor\LanguageServerProtocol\DiagnosticSeverity;
use Phpactor\LanguageServerProtocol\Position;
use Phpactor\LanguageServerProtocol\Range;
use Phpactor\LanguageServerProtocol\TextDocumentItem;

class SayHelloDiagnosticsProvider implements DiagnosticsProvider
{
	/**
	 * {@inheritDoc}
	 */
	public function provideDiagnostics(
		TextDocumentItem $textDocument,
		?Cancellation $cancel = null,
	): array {
		return [
			new Diagnostic(
				new Range(
					new Position(0, 0),
					new Position(1, 0),
				),
				'This is the first line, hello!',
				DiagnosticSeverity::INFORMATION,
			),
		];
	}

	public function name(): string
	{
		return 'say-hello';
	}
}
