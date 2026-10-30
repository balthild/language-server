<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Internal\Mago\Linter;

use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

final class NoImportGlobalClassRule implements Rule
{
	public function getDefinition(): RuleDefinition
	{
		return new RuleDefinition(
			code: 'project/no-import-global-class',
			name: 'No import global class',
			description: 'Do not import global classes in use statements.',
			defaultLevel: Level::Help,
			defaultEnabled: true,
			targets: [NodeKind::UseItemSequence],
		);
	}

	public function lint(LintContext $context): void
	{
		[$item] = \array_map(
			$context->file->getText(...),
			$context->getChildren(),
		);

		if (\str_contains($item, '\\')) {
			return;
		}

		$context->cancellation->throwIfCancelled();
		$context->report(Issue::new(
			'Do not import global classes. Prefer fully-qualified names.',
			$context->node->span,
		));
	}
}
