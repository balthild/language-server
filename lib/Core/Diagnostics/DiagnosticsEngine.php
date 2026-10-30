<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Diagnostics;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\DeferredFuture;
use Phpactor\LanguageServer\Core\Server\ClientApi;
use Phpactor\LanguageServerProtocol\Diagnostic;
use Phpactor\LanguageServerProtocol\TextDocumentItem;
use Psr\Log\LoggerInterface;

class DiagnosticsEngine
{
	/**
	 * @var DeferredFuture<null>
	 */
	private DeferredFuture $deferred;

	private ?TextDocumentItem $waiting = null;

	/**
	 * @var array<int|string, list<Diagnostic>>
	 */
	private array $diagnostics = [];

	/**
	 * @var array<string, DeferredFuture<bool>>
	 */
	private array $locks = [];

	private float $lastUpdatedAt;

	/**
	 * @param DiagnosticsProvider[] $providers
	 */
	public function __construct(
		private ClientApi $clientApi,
		private LoggerInterface $logger,
		private array $providers,
		private int $sleepTime = 1000,
	) {
		$this->deferred = new DeferredFuture();
		$this->providers = $providers;
		$this->clientApi = $clientApi;
		$this->sleepTime = $sleepTime;
		$this->lastUpdatedAt = 0.0;
	}

	public function clear(TextDocumentItem $textDocument): void
	{
		$this->clientApi->diagnostics()->publishDiagnostics(
			$textDocument->uri,
			$textDocument->version,
			[],
		);
	}

	public function run(?Cancellation $token = null): void
	{
		while (true) {
			try {
				$token?->throwIfRequested();
			} catch (CancelledException $cancelled) {
				return;
			}

			$this->awaitNextDocument();

			$beforeDocument = $this->waiting;
			$gracePeriod = \abs($this->sleepTime - ((\microtime(true) - $this->lastUpdatedAt) * 1000));
			\Amp\delay(\intval($gracePeriod) / 1000);

			if ($beforeDocument !== $this->waiting) {
				continue;
			}

			$textDocument = $this->waiting;
			$this->waiting = null;
			// allow the next document update to resolve
			$this->deferred = new DeferredFuture();

			// should never happen
			if ($textDocument === null) {
				continue;
			}

			// reset diagnostics for this document
			$this->clientApi->diagnostics()->publishDiagnostics(
				$textDocument->uri,
				$textDocument->version,
				[],
			);
			$this->diagnostics[$textDocument->uri] = [];

			$crashedProviders = [];

			foreach ($this->providers as $providerId => $provider) {
				if (\array_key_exists($providerId, $crashedProviders)) {
					continue;
				}

				\Amp\async(function () use (
					$providerId,
					$provider,
					$token,
					$textDocument,
					&$crashedProviders,
				): void {
					$start = \microtime(true);

					$this->awaitProviderLock($providerId);

					if (!$this->isDocumentCurrent($textDocument)) {
						return;
					}

					$this->locks[$providerId] = new DeferredFuture();

					try {
						/** @var Diagnostic[] $diagnostics */
						$diagnostics = $provider->provideDiagnostics($textDocument, $token);
					} catch (\Throwable $e) {
						$message = \sprintf(
							'Diagnostic provider "%s" errored with "%s", removing from pool',
							$providerId,
							$e->getMessage(),
						);
						$this->clientApi->window()->showMessage()->warning($message);
						$this->logger->error($message, [
							'stack' => (new \Exception())->getTraceAsString(),
						]);
						$crashedProviders[$providerId] = true;
						return;
					}

					if (isset($this->locks[$providerId])) {
						$lock = $this->locks[$providerId];
						unset($this->locks[$providerId]);
						$lock->complete(true);
					}

					if (!$diagnostics) {
						return;
					}

					$elapsed = (int) \round((\microtime(true) - $start) / 1000);

					if (!$this->isDocumentCurrent($textDocument)) {
						return;
					}

					$this->diagnostics[$textDocument->uri] = \array_merge(
						$this->diagnostics[$textDocument->uri] ?? [],
						$diagnostics,
					);

					$this->clientApi->diagnostics()->publishDiagnostics(
						$textDocument->uri,
						$textDocument->version,
						$this->diagnostics[$textDocument->uri],
					);
				});
			}
		}
	}

	public function enqueue(TextDocumentItem $textDocument): void
	{
		// set the last updated at timestamp - this will be used as the basis of
		// the grace period before linting
		$this->lastUpdatedAt = \microtime(true);

		$waiting = $this->waiting;

		// set the next document
		$this->waiting = $textDocument;

		// if we already had a waiting document then do nothing
		// it will get resolved next time
		if ($waiting !== null) {
			return;
		}

		// otherwise trigger the lint process
		$this->deferred->complete();
	}

	private function isDocumentCurrent(TextDocumentItem $textDocument): bool
	{
		return $this->waiting === null;
	}

	private function awaitProviderLock(int|string $providerId): void
	{
		if (!\array_key_exists($providerId, $this->locks)) {
			return;
		}

		$this->locks[$providerId]->getFuture()->await();
	}

	private function awaitNextDocument(): void
	{
		if ($this->waiting) {
			return;
		}

		$this->deferred->getFuture()->await();
	}
}
