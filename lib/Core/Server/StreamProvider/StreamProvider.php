<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server\StreamProvider;

interface StreamProvider
{
	public function accept(): ?Connection;

	public function close(): void;
}
