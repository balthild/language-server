<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Rpc;

final class RawMessage
{
	/**
	 * @param array<string, string> $headers
	 */
	public function __construct(
		private array $headers,
		private array $body,
	) {}

	public function body(): array
	{
		return $this->body;
	}

	/**
	 * @return array<string, string>
	 */
	public function headers(): array
	{
		return $this->headers;
	}
}
