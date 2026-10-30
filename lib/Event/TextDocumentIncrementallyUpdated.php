<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Event;

use Phpactor\LanguageServerProtocol\TextDocumentContentChangeIncrementalEvent;
use Phpactor\LanguageServerProtocol\VersionedTextDocumentIdentifier;

class TextDocumentIncrementallyUpdated
{
	/**
	 * @param TextDocumentContentChangeIncrementalEvent[] $events
	 */
	public function __construct(
		private VersionedTextDocumentIdentifier $identifier,
		private array $events,
	) {}

	public function identifier(): VersionedTextDocumentIdentifier
	{
		return $this->identifier;
	}

	/**
	 * @return TextDocumentContentChangeIncrementalEvent[]
	 */
	public function events(): array
	{
		return $this->events;
	}
}
