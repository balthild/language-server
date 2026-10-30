<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Core\CodeAction;

use Amp\DeferredCancellation;
use Phpactor\LanguageServer\Core\CodeAction\AggregateCodeActionProvider;
use Phpactor\LanguageServer\Core\CodeAction\CodeActionProvider;
use Phpactor\LanguageServer\Test\ProtocolFactory;
use Phpactor\LanguageServerProtocol\CodeAction;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class AggregateCodeActionProviderTest extends TestCase
{
	use ProphecyTrait;

	public function testProvidesKindsFromAggregatedProviders(): void
	{
		$provider1 = $this->prophesize(CodeActionProvider::class);
		$provider2 = $this->prophesize(CodeActionProvider::class);

		$aggregate = new AggregateCodeActionProvider($provider1->reveal(), $provider2->reveal());

		$provider1->kinds()->willReturn(['one', 'two']);
		$provider2->kinds()->willReturn(['one', 'two', 'three', 'four']);

		self::assertEquals(['one', 'two', 'three', 'four'], $aggregate->kinds());
	}

	public function testProvidesCodeActionsFromProviders(): void
	{
		$range = ProtocolFactory::range(0, 0, 0, 0);
		$item = ProtocolFactory::textDocumentItem('file://foo', 'content');

		$action1 = new CodeAction('foobar');
		$action2 = new CodeAction('barfoo');

		$provider1 = $this->prophesize(CodeActionProvider::class);
		$provider2 = $this->prophesize(CodeActionProvider::class);

		$source = new DeferredCancellation();
		$token = $source->getCancellation();
		$provider1->provideActionsFor($item, $range, $token)->willReturn([$action1]);
		$provider2->provideActionsFor($item, $range, $token)->willReturn([$action2]);

		$aggregate = new AggregateCodeActionProvider($provider1->reveal(), $provider2->reveal());

		$actions = [];
		foreach ($aggregate->provideActionsFor($item, $range, $token) as $action) {
			$actions[] = $action;
		}

		self::assertEquals([$action1, $action2], $actions);
	}
}
