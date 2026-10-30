<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Test\LanguageServerTester;

use Amp\Future;
use Phpactor\LanguageServer\Test\LanguageServerTester;
use Phpactor\LanguageServerProtocol\ExecuteCommandParams;

class WorkspaceTester
{
	public function __construct(private LanguageServerTester $tester) {}

	/**
	 * @param ?array $args
	 * @return Future<mixed>
	 */
	public function executeCommand(string $command, ?array $args = []): Future
	{
		return $this->tester->request(
			'workspace/executeCommand',
			new ExecuteCommandParams(
				$command,
				$args,
			),
		);
	}
}
