<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Diagnostics;

use Amp\Cancellation;
use Phpactor\LanguageServerProtocol\TextDocumentItem;

class ClosureDiagnosticsProvider implements DiagnosticsProvider
{
	public function __construct(private \Closure $closure, private string $name = 'closure') {}

	public function provideDiagnostics(
		TextDocumentItem $textDocument,
		?Cancellation $cancel = null,
	): array {
		$closure = $this->closure;

		return $closure($textDocument);
	}

	public function name(): string
	{
		return $this->name;
	}
}
