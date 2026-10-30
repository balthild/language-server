<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server\Client;

use DTL\Invoke\Invoke;
use Phpactor\LanguageServer\Core\Server\RpcClient;
use Phpactor\LanguageServerProtocol\ApplyWorkspaceEditResult;
use Phpactor\LanguageServerProtocol\WorkspaceEdit;

final class WorkspaceClient
{
	public function __construct(private RpcClient $client) {}

	public function applyEdit(WorkspaceEdit $edit, ?string $label = null): ApplyWorkspaceEditResult
	{
		$response = $this->client->request(
			'workspace/applyEdit',
			[
				'edit' => $edit,
				'label' => $label,
			],
		);

		return Invoke::new(ApplyWorkspaceEditResult::class, (array) $response->result);
	}

	public function executeCommand(string $command, array $arguments): mixed
	{
		$response = $this->client->request(
			'workspace/executeCommand',
			[
				'command' => $command,
				'arguments' => $arguments,
			],
		);

		return $response->result;
	}
}
