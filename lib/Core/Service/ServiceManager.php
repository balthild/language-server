<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Service;

use Amp\DeferredCancellation;
use Psr\Log\LoggerInterface;

class ServiceManager
{
	/**
	 * @var array<string, DeferredCancellation>
	 */
	private array $cancellations = [];

	public function __construct(
		private ServiceProviders $serviceProviders,
		private LoggerInterface $logger,
	) {}

	/**
	 * @return array<string>
	 */
	public function runningServices(): array
	{
		return \array_keys($this->cancellations);
	}

	public function startAll(): void
	{
		foreach ($this->serviceProviders->names() as $serviceName) {
			$this->start($serviceName);
		}
	}

	public function start(string $serviceName): void
	{
		$provider = $this->serviceProviders->get($serviceName);

		if (isset($this->cancellations[$serviceName])) {
			throw new \RuntimeException(\sprintf(
				'Service "%s" is already running',
				$serviceName,
			));
		}

		$this->logger->info(\sprintf('Starting service: %s (%s)', $serviceName, $provider::class));

		if (!\method_exists($provider, $serviceName)) {
			throw new \RuntimeException(\sprintf(
				'Service provider "%s" has no service method "%s"',
				$provider::class,
				$serviceName,
			));
		}

		$cancel = new DeferredCancellation();
		$this->cancellations[$serviceName] = $cancel;
		$token = $cancel->getCancellation();

		\Amp\async(function () use ($provider, $serviceName, $token): void {
			try {
				$provider->$serviceName($token);
			} catch (\Throwable $error) {
				$this->logger->error(\sprintf(
					'Error in service "%s" "%s:%s": %s',
					$serviceName,
					$provider::class,
					__FUNCTION__,
					$error->getMessage(),
				));
			}
		});
	}

	public function stop(string $serviceName): void
	{
		$this->serviceProviders->assertExists($serviceName);

		if (!isset($this->cancellations[$serviceName])) {
			throw new \RuntimeException(\sprintf(
				'Cannot stop service "%s" it has not been started, running services: "%s"',
				$serviceName,
				\implode('", "', \array_keys($this->cancellations)),
			));
		}

		$tokenSource = $this->cancellations[$serviceName];
		\assert($tokenSource instanceof DeferredCancellation);
		$tokenSource->cancel();
		unset($this->cancellations[$serviceName]);
	}

	public function isRunning(string $serviceName): bool
	{
		$this->serviceProviders->assertExists($serviceName);

		return isset($this->cancellations[$serviceName]);
	}

	public function stopAll(): void
	{
		foreach ($this->serviceProviders->names() as $serviceName) {
			$this->stop($serviceName);
		}
	}
}
