<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Service;

interface ServiceProvider
{
	/**
	 * Return a list of methods which should be registered as services.
	 *
	 * Services are started when the language server is initialized and run
	 * until the given cancellation is requested. Each service method blocks
	 * for as long as it wants to run.
	 *
	 * For example:
	 *
	 * ```
	 *     public function services(): array
	 *     {
	 *         return [
	 *             'pingService'
	 *         ];
	 *     }
	 *
	 *     public function pingService(?Cancellation $cancellation = null): void
	 *     {
	 *         while (true) {
	 *             $cancellation->throwIfRequested();
	 *             \Amp\delay(1);
	 *         }
	 *     }
	 * ```
	 *
	 * @return list<string>
	 */
	public function services(): array;
}
