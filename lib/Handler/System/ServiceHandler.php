<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Handler\System;

use Phpactor\LanguageServer\Core\Handler\Handler;
use Phpactor\LanguageServer\Core\Server\ClientApi;
use Phpactor\LanguageServer\Core\Service\ServiceManager;

class ServiceHandler implements Handler
{
	public function __construct(private ServiceManager $manager, private ClientApi $client) {}

	/**
	 * {@inheritDoc}
	 */
	public function methods(): array
	{
		return [
			'phpactor/service/start' => 'startService',
			'phpactor/service/stop' => 'stopService',
			'phpactor/service/running' => 'runningServices',
		];
	}

	public function startService(string $name): void
	{
		$this->manager->start($name);
	}

	public function stopService(string $name): void
	{
		$this->manager->stop($name);
	}

	/**
	 * @return array<string>
	 */
	public function runningServices(): array
	{
		$this->client
			->window()
			->showMessage()
			->info(\sprintf(
				'Running services: "%s"',
				\implode('", "', $this->manager->runningServices()),
			));

		return $this->manager->runningServices();
	}
}
