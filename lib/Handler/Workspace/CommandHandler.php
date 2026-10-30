<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Handler\Workspace;

use Phpactor\LanguageServer\Core\Command\CommandDispatcher;
use Phpactor\LanguageServer\Core\Handler\CanRegisterCapabilities;
use Phpactor\LanguageServer\Core\Handler\Handler;
use Phpactor\LanguageServerProtocol\ExecuteCommandOptions;
use Phpactor\LanguageServerProtocol\ExecuteCommandParams;
use Phpactor\LanguageServerProtocol\ServerCapabilities;

class CommandHandler implements Handler, CanRegisterCapabilities
{
	public function __construct(private CommandDispatcher $dispatcher) {}

	/**
	 * {@inheritDoc}
	 */
	public function methods(): array
	{
		return [
			'workspace/executeCommand' => 'executeCommand',
		];
	}

	public function executeCommand(ExecuteCommandParams $params): mixed
	{
		return $this->dispatcher->dispatch($params->command, $params->arguments ?? []);
	}

	public function registerCapabiltiies(ServerCapabilities $capabilities): void
	{
		$capabilities->executeCommandProvider = new ExecuteCommandOptions(
			$this->dispatcher->registeredCommands(),
		);
	}
}
