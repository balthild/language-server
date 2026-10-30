<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server\ResponseWatcher;

use Amp\DeferredFuture;
use Amp\Future;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use Phpactor\LanguageServer\Core\Server\ResponseWatcher;

final class DeferredResponseWatcher implements ResponseWatcher
{
	/**
	 * @var array<string|int, DeferredFuture<ResponseMessage>>
	 */
	private array $watchers = [];

	public function handle(ResponseMessage $response): void
	{
		if (isset($this->watchers[$response->id])) {
			$this->watchers[$response->id]->complete($response);
			return;
		}

		throw new \RuntimeException(\sprintf(
			'Response to unknown request "%s"',
			$response->id,
		));
	}

	public function waitForResponse(int|string $requestId): Future
	{
		$deferred = new DeferredFuture();
		$this->watchers[$requestId] = $deferred;

		return $deferred->getFuture();
	}
}
