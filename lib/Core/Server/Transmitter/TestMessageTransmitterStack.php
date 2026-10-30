<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server\Transmitter;

use Phpactor\LanguageServer\Core\Rpc\Message;

interface TestMessageTransmitterStack
{
	public function shift(): ?Message;

	public function clear(): void;
}
