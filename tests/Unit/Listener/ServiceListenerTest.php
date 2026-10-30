<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Listener;

use Phpactor\LanguageServer\Core\Service\ServiceManager;
use Phpactor\LanguageServer\Event\Initialized;
use Phpactor\LanguageServer\Event\WillShutdown;
use Phpactor\LanguageServer\Listener\ServiceListener;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class ServiceListenerTest extends TestCase
{
	use ProphecyTrait;

	private ServiceListener $listener;

	/**
	 * @var ObjectProphecy<ServiceManager>
	 */
	private ObjectProphecy $serviceManager;

	protected function setUp(): void
	{
		$this->serviceManager = $this->prophesize(ServiceManager::class);
		$this->listener = new ServiceListener($this->serviceManager->reveal());
	}

	public function testStartsServicesonInitialized(): void
	{
		$event = new Initialized();
		$this->dispatch($event);
		$this->serviceManager->startAll()->shouldHaveBeenCalled();
	}

	public function testStopsAllServicesOnShutdown(): void
	{
		$event = new WillShutdown();
		$this->dispatch($event);
		$this->serviceManager->stopAll()->shouldHaveBeenCalled();
	}

	public function testDoesNotStartServicesOnOtherEvent(): void
	{
		$event = new \stdClass();
		$this->dispatch($event);
		$this->serviceManager->startAll()->shouldNotHaveBeenCalled();
	}

	private function dispatch(object $event): void
	{
		foreach ($this->listener->getListenersForEvent($event) as $listener) {
			$listener($event);
		}
	}
}
