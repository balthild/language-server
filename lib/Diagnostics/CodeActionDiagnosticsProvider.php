<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Diagnostics;

use Amp\Cancellation;
use Phpactor\LanguageServer\Core\CodeAction\CodeActionProvider;
use Phpactor\LanguageServer\Core\Diagnostics\DiagnosticsProvider;
use Phpactor\LanguageServerProtocol\Position;
use Phpactor\LanguageServerProtocol\Range;
use Phpactor\LanguageServerProtocol\TextDocumentItem;

class CodeActionDiagnosticsProvider implements DiagnosticsProvider
{
	/**
	 * @var array<CodeActionProvider>
	 */
	private array $providers;

	public function __construct(CodeActionProvider ...$providers)
	{
		$this->providers = $providers;
	}

	/**
	 * {@inheritDoc}
	 */
	public function provideDiagnostics(
		TextDocumentItem $textDocument,
		?Cancellation $cancel = null,
	): array {
		$diagnostics = [];
		foreach ($this->providers as $provider) {
			$codeActions = $provider->provideActionsFor(
				$textDocument,
				new Range(
					new Position(0, 0),
					new Position(\PHP_INT_MAX, \PHP_INT_MAX),
				),
				$cancel,
			);

			foreach ($codeActions as $codeAction) {
				foreach ($codeAction->diagnostics ?? [] as $diagnostic) {
					$diagnostics[] = $diagnostic;
				}
			}

			$cancel?->throwIfRequested();
		}

		return $diagnostics;
	}

	public function name(): string
	{
		return 'code-action';
	}
}
