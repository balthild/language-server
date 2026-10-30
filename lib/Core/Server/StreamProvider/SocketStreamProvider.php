<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server\StreamProvider;

use Amp\Socket\ServerSocket;
use Amp\Socket\Socket;
use Phpactor\LanguageServer\Core\Server\Stream\SocketDuplexStream;
use Psr\Log\LoggerInterface;

final class SocketStreamProvider implements StreamProvider
{
	public function __construct(private ServerSocket $server, private LoggerInterface $logger) {}

	public function accept(): ?Connection
	{
		$socket = $this->server->accept();

		if (!$socket instanceof Socket) {
			return null;
		}

		$address = (string) $socket->getRemoteAddress();

		$this->logger->info(\sprintf('Accepted connection from "%s"', $address));

		return new Connection($address, new SocketDuplexStream($socket));
	}

	public function address(): ?string
	{
		return (string) $this->server->getAddress();
	}

	public function close(): void
	{
		$this->server->close();
	}
}
