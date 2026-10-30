<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Core\Service;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\PHPUnit\AsyncTestCase;
use Phpactor\LanguageServer\Core\Service\ServiceManager;
use Phpactor\LanguageServer\Core\Service\ServiceProvider;
use Phpactor\LanguageServer\Core\Service\ServiceProviders;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\Test\TestLogger;

class ServiceManagerTest extends AsyncTestCase
{
	use ProphecyTrait;

	private TestLogger $logger;

	protected function setUp(): void
	{
		parent::setUp();
		$this->logger = new TestLogger();
	}

	public function testStartAllServices(): void
	{
		$service = new PingService();
		$serviceManager = $this->createServiceManager($service);
		$serviceManager->startAll();

		\Amp\delay(0);

		self::assertTrue($service->called);
	}

	public function testStopAll(): void
	{
		$service = new PingService();
		$serviceManager = $this->createServiceManager($service);
		$serviceManager->startAll();

		\Amp\delay(0);

		self::assertFalse($service->cancellationToken->isRequested());

		$serviceManager->stopAll();

		self::assertTrue($service->cancellationToken->isRequested());
	}

	public function testStartService(): void
	{
		$service = new PingService();
		$serviceManager = $this->createServiceManager($service);
		self::assertFalse($service->called);
		$serviceManager->start('ping');
		self::assertTrue($serviceManager->isRunning('ping'));

		\Amp\delay(0);

		self::assertTrue($service->called);
	}

	public function testListRunningServices(): void
	{
		$service = new PingService();
		$serviceManager = $this->createServiceManager($service);
		self::assertEmpty($serviceManager->runningServices());
		$serviceManager->start('ping');
		self::assertEquals(['ping'], $serviceManager->runningServices());
		$serviceManager->stop('ping');
		self::assertEquals([], $serviceManager->runningServices());
	}

	public function testExceptionWhenTryingToStartRunningService(): void
	{
		$this->expectExceptionMessage('Service "ping" is already running');
		$service = new PingService();
		$serviceManager = $this->createServiceManager($service);
		$serviceManager->start('ping');
		$serviceManager->start('ping');
	}

	public function testHandlesExceptionsFromServices(): void
	{
		$service = new ExceptionThrowingService();
		$serviceManager = $this->createServiceManager($service);
		$serviceManager->start('exception');

		\Amp\delay(0);

		self::assertTrue($this->logger->hasErrorThatContains('No'));
	}

	public function testStopService(): void
	{
		$service = new DaemonService();
		$serviceManager = $this->createServiceManager($service);

		self::assertFalse($service->called);

		$serviceManager->start('daemon');

		\Amp\delay(0.01);
		$serviceManager->stop('daemon');
		\Amp\delay(0.01);

		self::assertTrue($service->called);
	}

	public function testExceptionOnNonExistingService(): void
	{
		$this->expectExceptionMessage('Service "daemon" not known, known services: "ping"');
		$service = new PingService();
		$serviceManager = $this->createServiceManager($service);
		self::assertFalse($service->called);
		$serviceManager->start('ping');
		$serviceManager->stop('daemon');
	}

	public function testThrowExceptionIfServiceNotReturnPromise(): void
	{
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('has no service method');
		$service = new PingServiceMissingMethod();
		$serviceManager = $this->createServiceManager($service);
		$serviceManager->startAll();
	}

	private function createServiceManager(ServiceProvider ...$services): ServiceManager
	{
		return new ServiceManager(new ServiceProviders(...$services), $this->logger);
	}
}

class PingService implements ServiceProvider
{
	public bool $called = false;
	public Cancellation $cancellationToken;

	/**
	 * {@inheritDoc}
	 */
	public function methods(): array
	{
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function services(): array
	{
		return [
			'ping',
		];
	}

	public function ping(?Cancellation $token = null): void
	{
		$this->called = true;
		\assert($token !== null);
		$this->cancellationToken = $token;
	}
}

class PingServiceMissingMethod implements ServiceProvider
{
	public $called = false;

	/**
	 * {@inheritDoc}
	 */
	public function methods(): array
	{
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function services(): array
	{
		return [
			'ping',
		];
	}
}

class DaemonService implements ServiceProvider
{
	public $called = false;

	/**
	 * {@inheritDoc}
	 */
	public function methods(): array
	{
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function services(): array
	{
		return [
			'daemon',
		];
	}

	public function daemon(?Cancellation $cancel = null): void
	{
		while (true) {
			try {
				$cancel?->throwIfRequested();
			} catch (CancelledException $cancelled) {
				break;
			}
			\Amp\delay(0.001);
		}
		$this->called = true;
	}
}

class ExceptionThrowingService implements ServiceProvider
{
	public function methods(): array
	{
		return [];
	}

	public function services(): array
	{
		return [
			'exception',
		];
	}

	public function exception(?Cancellation $cancel = null): void
	{
		throw new \Exception('No');
	}
}
