<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Event;

use Phpactor\LanguageServerProtocol\FileEvent;

final class FilesChanged
{
	/**
	 * @var FileEvent[]
	 */
	private array $events = [];

	public function __construct(FileEvent ...$events)
	{
		$this->events = $events;
	}

	/**
	 * @return FileEvent[]
	 */
	public function events(): array
	{
		return $this->events;
	}

	public function byType(int $type): self
	{
		return new self(...\array_filter(
			$this->events,
			static fn (FileEvent $event) => $event->type === $type,
		));
	}

	public function first(): FileEvent
	{
		$first = \reset($this->events);

		if (false === $first) {
			throw new \RuntimeException('No file events, cannot get the first one');
		}

		return $first;
	}
}
