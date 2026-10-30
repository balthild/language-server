<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Core\Dispatcher\ArgumentResolver;

use Amp\Cancellation;
use Amp\DeferredCancellation;
use Phpactor\LanguageServer\Core\Dispatcher\ArgumentResolver\LanguageSeverProtocolParamsResolver;
use Phpactor\LanguageServer\Core\Dispatcher\Exception\CouldNotResolveArguments;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Rpc\RequestMessage;
use Phpactor\LanguageServer\Test\ProtocolFactory;
use Phpactor\LanguageServerProtocol\InitializeParams;
use PHPUnit\Framework\TestCase;

class LanguageSeverProtocolParamsResolverTest extends TestCase
{
	public function testResolvesLspParams(): void
	{
		$handler = new LspHandler();
		$resolver = new LanguageSeverProtocolParamsResolver();
		$args = [
			'capabilities' => [],
			'rootUri' => 'file://tmp/foo',
		];
		$resolvedArgs = $resolver->resolveArguments(
			$handler,
			'initialize',
			ProtocolFactory::requestMessage('foo', $args),
		);

		self::assertEquals(
			[
				InitializeParams::fromArray($args),
			],
			$resolvedArgs,
		);
	}

	public function testResolvesRawRequestMessage(): void
	{
		$handler = new LspHandler();
		$resolver = new LanguageSeverProtocolParamsResolver();
		$args = [
			'foo' => 'bar',
		];
		$message = ProtocolFactory::requestMessage('foo', $args);
		$resolvedArgs = $resolver->resolveArguments($handler, 'rawRequest', $message);

		self::assertEquals([$message], $resolvedArgs);
	}

	public function testResolvesRawNotification(): void
	{
		$handler = new LspHandler();
		$resolver = new LanguageSeverProtocolParamsResolver();
		$args = [
			'foo' => 'bar',
		];
		$message = ProtocolFactory::notificationMessage('foo', $args);
		$resolvedArgs = $resolver->resolveArguments($handler, 'rawNotification', $message);

		self::assertEquals([$message], $resolvedArgs);
	}

	public function testNotResolvableWhenFirstParamNotProtocolParams(): void
	{
		$this->expectException(CouldNotResolveArguments::class);
		$this->expectExceptionMessage('First argument');
		$handler = new LspHandler();
		$resolver = new LanguageSeverProtocolParamsResolver();

		$args = [
			'capabilities' => [],
			'rootUri' => 'file://tmp/foo',
		];
		$cancellationToken = new DeferredCancellation();
		$token = $cancellationToken->getCancellation();
		$extraArgs = [
			'cancel' => $token,
		];

		$resolvedArgs = $resolver->resolveArguments(
			$handler,
			'initializeWrongOrder',
			ProtocolFactory::requestMessage('foo', $args),
		);

		self::assertEquals(
			[
				InitializeParams::fromArray($args),
				$token,
			],
			$resolvedArgs,
		);
	}
}

class LspHandler
{
	public function initialize(InitializeParams $params, Cancellation $c): void {}

	public function initializeWrongOrder(Cancellation $c, InitializeParams $params): void {}

	public function rawRequest(RequestMessage $request): void {}

	public function rawNotification(NotificationMessage $notification): void {}
}
