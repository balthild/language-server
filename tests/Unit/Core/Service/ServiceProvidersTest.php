<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Core\Service;

use Phpactor\LanguageServer\Core\Service\Exception\UnknownService;
use Phpactor\LanguageServer\Core\Service\ServiceProvider;
use Phpactor\LanguageServer\Core\Service\ServiceProviders;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ServiceProvidersTest extends TestCase
{
	use ProphecyTrait;

	public function testExceptionGettingUnknownService(): void
	{
		$this->expectException(UnknownService::class);
		(new ServiceProviders())->get('foobar');
	}

	public function testGetService(): void
	{
		$provider = new class implements ServiceProvider {
			public function services(): array
			{
				return ['service'];
			}
		};

		$found = (new ServiceProviders($provider))->get('service');
		self::assertSame($found, $provider);
	}
}
