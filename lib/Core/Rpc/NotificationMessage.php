<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Rpc;

final class NotificationMessage extends Message
{
	/**
	 * @param ?array<string, mixed> $params
	 */
	public function __construct(
		public string $method,
		public ?array $params = null,
	) {}
}
