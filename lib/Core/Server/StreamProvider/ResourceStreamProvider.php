<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server\StreamProvider;

use Phpactor\LanguageServer\Core\Server\Stream\ResourceDuplexStream;
use Psr\Log\LoggerInterface;

final class ResourceStreamProvider implements StreamProvider
{
	private bool $provided = false;

	public function __construct(
		private ResourceDuplexStream $duplexStream,
		private LoggerInterface $logger,
	) {}

	public function accept(): ?Connection
	{
		// resource connections are valid only for
		// the length of the client connnection
		if ($this->provided) {
			return null;
		}

		$this->provided = true;

		$this->logger->info('Listening on STDIO');

		return new Connection('stdio', $this->duplexStream);
	}

	public function close(): void
	{
		$this->duplexStream->close();
	}
}
