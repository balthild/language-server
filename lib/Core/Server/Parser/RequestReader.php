<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server\Parser;

use Phpactor\LanguageServer\Core\Rpc\RawMessage;

interface RequestReader
{
	public function readMessage(): ?RawMessage;
}
