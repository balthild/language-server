<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Dispatcher;

use Phpactor\LanguageServer\Core\Server\Transmitter\MessageTransmitter;
use Phpactor\LanguageServerProtocol\InitializeParams;

interface DispatcherFactory
{
	public function create(
		MessageTransmitter $transmitter,
		InitializeParams $initializeParams,
	): Dispatcher;
}
