<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server;

interface ServerStatsReader
{
	public function uptime(): \DateInterval;

	public function connectionCount(): int;

	public function requestCount(): int;
}
