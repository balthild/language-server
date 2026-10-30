<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Handler;

use Phpactor\LanguageServer\Core\Handler\Handler;
use Phpactor\LanguageServer\LanguageServerTesterBuilder;
use Phpactor\TestUtils\PHPUnit\TestCase;

abstract class HandlerTestCase extends TestCase
{
	abstract public function handler(): Handler;

	public function dispatch(string $method, array $params)
	{
		$tester = LanguageServerTesterBuilder::createBare()->addHandler($this->handler())->build();

		return $tester->requestAndWait($method, $params);
	}

	public function notify(string $method, array $params)
	{
		$tester = LanguageServerTesterBuilder::createBare()->addHandler($this->handler())->build();

		return $tester->notifyAndWait($method, $params);
	}
}
