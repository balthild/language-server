<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Handler;

use Phpactor\LanguageServerProtocol\ServerCapabilities;

interface CanRegisterCapabilities
{
	public function registerCapabiltiies(ServerCapabilities $capabilities): void;
}
