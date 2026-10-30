<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server\Stream;

use Amp\ByteStream\ReadableResourceStream;
use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;

final class ResourceDuplexStream implements DuplexStream
{
	public function __construct(private ReadableStream $input, private WritableStream $output) {}

	public function read(): ?string
	{
		return $this->input->read();
	}

	public function write(string $data): void
	{
		$this->output->write($data);
	}

	public function end(): void
	{
		$this->output->end();
	}

	public function close(): void
	{
		if ($this->input instanceof ReadableResourceStream) {
			$this->input->close();
		}
	}
}
