<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Rpc;

final class RequestMessage extends Message
{
	/**
	 * @param string|int $id
	 * @param ?array<string, mixed> $params
	 */
	public function __construct(
		public string|int $id,
		public string $method,
		public ?array $params,
	) {}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'method' => $this->method,
			'params' => $this->params,
		];
	}
}
