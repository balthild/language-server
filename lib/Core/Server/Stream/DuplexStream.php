<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server\Stream;

interface DuplexStream
{
	public function read(): ?string;

	public function write(string $data): void;

	public function end(): void;

	public function close(): void;
}
