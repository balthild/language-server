<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Test;

use Amp\Cancellation;
use Amp\CancelledException;
use Phpactor\LanguageServer\Core\Handler\ClosureHandler;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use Phpactor\LanguageServer\Example\Service\PingProvider;
use Phpactor\LanguageServer\LanguageServerTesterBuilder;
use Phpactor\LanguageServer\Test\LanguageServerTester;
use Phpactor\LanguageServer\Test\ProtocolFactory;
use Phpactor\LanguageServerProtocol\InitializeParams;
use Phpactor\LanguageServerProtocol\ServerCapabilities;
use PHPUnit\Framework\TestCase;

class LanguageServerTesterTest extends TestCase
{
	const SUCCESS = 'success';

	public function testDispatch(): void
	{
		$tester = $this->createTester();
		$response = $tester->dispatch(
			ProtocolFactory::requestMessage(
				'foobar',
				['foo' => 'Barfoo'],
			),
		)->await();
		$this->assertSuccessResponse($response);
	}

	public function testDispatchAndWait(): void
	{
		$tester = $this->createTester();
		$response = $tester->dispatchAndWait(
			ProtocolFactory::requestMessage(
				'foobar',
				['foo' => 'Barfoo'],
			),
		);
		$this->assertSuccessResponse($response);
	}

	public function testRequest(): void
	{
		$tester = $this->createTester();
		$response = $tester->request('foobar', ['foobar' => 'Barfoo'])->await();
		$this->assertSuccessResponse($response);
	}

	public function testRequestAndWait(): void
	{
		$tester = $this->createTester();
		$response = $tester->requestAndWait('foobar', ['foobar' => 'Barfoo']);
		$this->assertSuccessResponse($response);
	}

	public function testCancel(): void
	{
		$this->expectException(CancelledException::class);
		$tester = $this->createTester();
		$responsePromise = $tester->request('delay_and_check_cancellation', [], 1);
		$tester->cancel(1);
		$responsePromise->await();
	}

	public function testNotify(): void
	{
		$tester = $this->createTester();
		$response = $tester->notify('foobar', ['foobar' => 'Barfoo'])->await();
		$this->assertNotifysTransmission($tester);
	}

	public function testNotifyAndWait(): void
	{
		$tester = $this->createTester();
		$tester->notifyAndWait('foobar', ['foobar' => 'Barfoo']);
		$this->assertNotifysTransmission($tester);
	}

	public function testOpenTextDocument(): void
	{
		$tester = $this->createTester();
		$tester->textDocument()->open('file://foobar', 'content');
		$this->addToAssertionCount(1);
	}

	public function testServiceListRunning(): void
	{
		$tester = $this->createTester();
		$tester->initialize();
		$services = $tester->services()->listRunning();
		self::assertContains('ping', $services);

		$tester->services()->stop('ping');
		$services = $tester->services()->listRunning();
		self::assertNotContains('ping', $services);

		$tester->services()->start('ping');
		$services = $tester->services()->listRunning();
		self::assertContains('ping', $services);
	}

	public function testInitialize(): void
	{
		$tester = $this->createTester();
		$initializeResult = $tester->initialize();
		self::assertInstanceOf(ServerCapabilities::class, $initializeResult->capabilities);
	}

	private function createTester(?InitializeParams $params = null): LanguageServerTester
	{
		$params ??= ProtocolFactory::initializeParams();
		$builder = LanguageServerTesterBuilder::create();
		$builder->setInitializeParams($params);
		$builder->enableTextDocuments();
		$builder->addServiceProvider(new PingProvider($builder->clientApi()));
		$builder->addHandler(
			new ClosureHandler('foobar', static function (Cancellation $token) use ($builder) {
				$builder->transmitter()->transmit(new NotificationMessage(self::SUCCESS, [
					'message' => self::SUCCESS,
				]));

				return self::SUCCESS;
			}),
		);
		$builder->addHandler(
			new ClosureHandler('delay_and_check_cancellation', static function (Cancellation $token) {
				\Amp\delay(0.01);
				$token->throwIfRequested();

				return self::SUCCESS;
			}),
		);

		return $builder->build();
	}

	private function assertSuccessResponse(ResponseMessage $response): void
	{
		self::assertEquals(self::SUCCESS, $response->result);
	}

	private function assertNotifysTransmission(LanguageServerTester $tester): void
	{
		$notification = $tester->transmitter()->shift();
		self::assertNotNull($notification, 'Notication was sent');
		self::assertInstanceOf(NotificationMessage::class, $notification);
		\assert($notification instanceof NotificationMessage);
		self::assertEquals(self::SUCCESS, $notification->params['message']);
	}
}
