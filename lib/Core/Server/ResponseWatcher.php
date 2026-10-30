<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server;

use Amp\Future;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;

interface ResponseWatcher
{
	public function handle(ResponseMessage $response): void;

	/**
	 * Register interest in a response. Returns a future which completes when
	 * the response is handled.
	 *
	 * @return Future<ResponseMessage>
	 */
	public function waitForResponse(int|string $requestId): Future;
}
