<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\CodeAction;

use Amp\Cancellation;
use Phpactor\LanguageServerProtocol\Range;
use Phpactor\LanguageServerProtocol\TextDocumentItem;

class AggregateCodeActionProvider implements CodeActionProvider
{
	/**
	 * @var CodeActionProvider[]
	 */
	private array $providers;

	public function __construct(CodeActionProvider ...$providers)
	{
		$this->providers = $providers;
	}

	/**
	 * {@inheritDoc}
	 */
	public function provideActionsFor(
		TextDocumentItem $textDocument,
		Range $range,
		?Cancellation $cancel = null,
	): array {
		$actions = [];
		foreach ($this->providers as $provider) {
			$actions = \array_merge($actions, $provider->provideActionsFor(
				$textDocument,
				$range,
				$cancel,
			));

			\Amp\delay(0);

			$cancel?->throwIfRequested();
		}

		return $actions;
	}

	/**
	 * {@inheritDoc}
	 */
	public function kinds(): array
	{
		$kinds = [];
		foreach ($this->providers as $provider) {
			foreach ($provider->kinds() as $kind) {
				$kinds[$kind] = true;
			}
		}
		return \array_keys($kinds);
	}

	public function describe(): string
	{
		return \sprintf('aggregate code action proivder with %s providers', \count($this->providers));
	}
}
