<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Internal\Mago\Linter;

use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

final class NoImportConstantRule implements Rule
{
	public function __construct(
		public readonly bool $namespaced = true,
	) {}

	public function getDefinition(): RuleDefinition
	{
		return new RuleDefinition(
			code: 'project/no-import-constant',
			name: 'No import constant',
			description: 'Do not import constants in use statements.',
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

		if ($type !== 'const') {
			return;
		}

		if ($this->namespaced && \str_contains($item, '\\')) {
			return;
		}

		$context->cancellation->throwIfCancelled();
		$context->report(Issue::new(
			'Do not import constants. Prefer fully-qualified names.',
			$context->node->span,
		));
	}
}
