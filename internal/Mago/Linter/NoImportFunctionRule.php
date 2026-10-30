<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Internal\Mago\Linter;

use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

final class NoImportFunctionRule implements Rule
{
	public function __construct(
		public readonly bool $namespaced = false,
	) {}

	public function getDefinition(): RuleDefinition
	{
		return new RuleDefinition(
			code: 'project/no-import-function',
			name: 'No import function',
			description: 'Do not import functions in use statements.',
			defaultLevel: Level::Help,
			defaultEnabled: true,
			targets: [NodeKind::TypedUseItemSequence],
		);
	}

	public function lint(LintContext $context): void
	{
		[$type, $item] = \array_map(
			$context->file->getText(...),
			$context->getChildren(),
		);

		if ($type !== 'function') {
			return;
		}

		if ($this->namespaced && \str_contains($item, '\\')) {
			return;
		}

		$context->cancellation->throwIfCancelled();
		$context->report(Issue::new(
			'Do not import functions. Prefer fully-qualified names.',
			$context->node->span,
		));
	}
}
