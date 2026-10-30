<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Handler\System;

use Phpactor\LanguageServer\Core\Handler\Handler;
use Phpactor\LanguageServer\Core\Server\ClientApi;
use Phpactor\LanguageServer\Core\Server\ServerStatsReader;

class StatsHandler implements Handler
{
	const METHOD_STATUS = 'phpactor/stats';

	public function __construct(private ClientApi $client, private ServerStatsReader $statProvider) {}

	public function methods(): array
	{
		return [
			self::METHOD_STATUS => 'status',
		];
	}

	public function status(): void
	{
		$this->client
			->window()
			->showMessage()
			->info(
				\implode(', ', [
					'pid: ' . \getmypid(),
					'up: ' . $this->statProvider->uptime()->format('%ad %hh %im %ss'),
					'connections: ' . $this->statProvider->connectionCount(),
					'requests: ' . $this->statProvider->requestCount(),
					'mem: ' . \number_format(\memory_get_peak_usage()) . 'b',
				]),
			);
	}
}
