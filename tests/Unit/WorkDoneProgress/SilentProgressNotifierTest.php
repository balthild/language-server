<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\WorkDoneProgress;

use Phpactor\LanguageServer\WorkDoneProgress\SilentWorkDoneProgressNotifier;
use Phpactor\LanguageServer\WorkDoneProgress\WorkDoneToken;
use Phpactor\TestUtils\PHPUnit\TestCase;

class SilentProgressNotifierTest extends TestCase
{
	public function testNotifier(): void
	{
		$notifier = new SilentWorkDoneProgressNotifier();
		$token = WorkDoneToken::generate();
		$notifier->create($token);
		$notifier->begin($token, 'Foobar');
		$notifier->report($token);
		$notifier->end($token);
		$this->addToAssertionCount(1);
	}
}
