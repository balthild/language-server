<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server\Stream;

use Amp\Socket\Socket;

final class SocketDuplexStream implements DuplexStream
{
	public function __construct(private Socket $socket) {}

	public function read(): ?string
	{
		return $this->socket->read();
	}

	public function write(string $data): void
	{
		$this->socket->write($data);
	}

	public function end(): void
	{
		$this->socket->end();
	}

	public function close(): void
	{
		$this->socket->close();
	}
}
