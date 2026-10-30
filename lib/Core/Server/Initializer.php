<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server;

use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServerProtocol\InitializeParams;

interface Initializer
{
	/**
	 * Provide initialization parameters.
	 *
	 * Typically implementations will be passed the _first request_ to the
	 * language server session, which should be the initialization request from
	 * which the initialize parameters can be extracted (including the
	 * ClientCapabilities).
	 */
	public function provideInitializeParams(Message $message): InitializeParams;
}
