<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Diagnostics;

use Amp\Cancellation;
use Phpactor\LanguageServerProtocol\TextDocumentItem;
use Psr\Log\LoggerInterface;

class AggregateDiagnosticsProvider implements DiagnosticsProvider
{
	/**
	 * @var list<DiagnosticsProvider>
	 */
	private array $providers;

	public function __construct(
		private LoggerInterface $logger,
		DiagnosticsProvider ...$providers,
	) {
		/** @var list<DiagnosticsProvider> $providers */
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
			try {
				$start = \microtime(true);
				$diagnostics = \array_merge(
					$diagnostics,
					$provider->provideDiagnostics($textDocument, $cancel),
				);
				if ($cancel?->isRequested()) {
					$this->logger->info('Diagnostics cancelled');
					return $diagnostics;
				}
				$this->logger->debug(\sprintf(
					'Diagnostic finished in "%s" (%s)',
					\number_format(\microtime(true) - $start, 2),
					$provider::class,
				));
			} catch (\Throwable $throwable) {
				$this->logger->error(\sprintf(
					'Diagnostic error from provider "%s": %s',
					$provider::class,
					$throwable->getMessage(),
				), [
					'trace' => $throwable->getTraceAsString(),
				]);
			}
		}

		return $diagnostics;
	}

	/**
	 * @return list<string>
	 */
	public function names(): array
	{
		return \array_map(
			static fn (DiagnosticsProvider $provider): string => $provider->name(),
			$this->providers,
		);
	}

	public function name(): string
	{
		return \implode(', ', $this->names());
	}
}
