<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Listener;

use Phpactor\LanguageServer\Core\Server\ClientApi;
use Phpactor\LanguageServer\Event\Initialized;
use Phpactor\LanguageServerProtocol\ClientCapabilities;
use Phpactor\LanguageServerProtocol\DidChangeWatchedFilesRegistrationOptions;
use Phpactor\LanguageServerProtocol\FileSystemWatcher;
use Phpactor\LanguageServerProtocol\Registration;
use Psr\EventDispatcher\ListenerProviderInterface;
use Ramsey\Uuid\Uuid;

class DidChangeWatchedFilesListener implements ListenerProviderInterface
{
	public function __construct(
		private ClientApi $client,
		private array $globPatterns,
		private ClientCapabilities $clientCapabilities,
	) {}

	/**
	 * {@inheritDoc}
	 */
	public function getListenersForEvent(object $event): iterable
	{
		if ($event instanceof Initialized) {
			return [[$this, 'registerCapability']];
		}

		return [];
	}

	public function registerCapability(Initialized $initialized): void
	{
		if (
			!($this->clientCapabilities->workspace->didChangeWatchedFiles->dynamicRegistration ?? false)
		) {
			return;
		}

		\Amp\async(function (): void {
			$this->client
				->client()
				->registerCapability(
					new Registration(
						Uuid::uuid4()->__toString(),
						'workspace/didChangeWatchedFiles',
						new DidChangeWatchedFilesRegistrationOptions(\array_map(
							static fn (string $glob) => new FileSystemWatcher($glob),
							$this->globPatterns,
						)),
					),
				);
		});
	}
}
