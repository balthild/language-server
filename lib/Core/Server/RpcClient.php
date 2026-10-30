<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server;

use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;

interface RpcClient
{
	/**
	 * @param array<string, mixed> $params
	 */
	public function notification(string $method, array $params): void;

	/**
	 * @param array<string, mixed> $params
	 */
	public function request(string $method, array $params): ResponseMessage;
}
