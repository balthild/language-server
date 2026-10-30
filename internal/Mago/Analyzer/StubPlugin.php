<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Internal\Mago\Analyzer;

use Mago\Sdk\Analyzer\InitializationContext;
use Mago\Sdk\Analyzer\InitializationHook;
use Mago\Sdk\Analyzer\Plugin;
use Mago\Sdk\Analyzer\PluginDefinition;
use Mago\Sdk\Analyzer\PluginRegistry;

class StubPlugin implements Plugin
{
	public function getDefinition(): PluginDefinition
	{
		return new PluginDefinition(
			identifier: 'project/stub',
			name: 'Stub',
			description: 'Adds stubs to fix broken types in dependencies.',
		);
	}

	public function register(PluginRegistry $registry): void
	{
		$registry->registerInitializationHook(
			new class implements InitializationHook {
				public function initialize(InitializationContext $context): void
				{
					$paths = \glob(__DIR__ . '/../../stub/*.php') ?: [];
					foreach ($paths as $path) {
						$name = \basename($path);
						$source = \file_get_contents($path) ?: '';
						$context->addStub("stub/{$name}", $source);
					}
				}
			},
		);
	}
}
