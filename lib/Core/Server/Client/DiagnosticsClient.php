<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server\Client;

use Phpactor\LanguageServer\Core\Server\RpcClient;
use Phpactor\LanguageServerProtocol\Diagnostic;

final class DiagnosticsClient
{
	public function __construct(private RpcClient $client) {}

	/**
	 * @param list<Diagnostic> $diagnostics
	 */
	public function publishDiagnostics(string $uri, ?int $version, array $diagnostics): void
	{
		$this->client->notification('textDocument/publishDiagnostics', [
			'uri' => $uri,
			'version' => $version,
			'diagnostics' => $diagnostics,
		]);
	}
}
