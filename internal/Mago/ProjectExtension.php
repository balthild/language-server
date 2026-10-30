<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Internal\Mago;

use Mago\Sdk\Extension;
use Phpactor\LanguageServer\Internal\Mago\Linter\NoImportConstantRule;
use Phpactor\LanguageServer\Internal\Mago\Linter\NoImportFunctionRule;
use Phpactor\LanguageServer\Internal\Mago\Linter\NoImportGlobalClassRule;

final class ProjectExtension
{
	private function __construct() {}

	public static function create(): Extension
	{
		return new Extension(
			identifier: 'balthild/language-server',
			name: 'LanguageServer project extension',
			version: '1.0.0',
			linterRules: [
				new NoImportConstantRule(),
				new NoImportFunctionRule(),
				new NoImportGlobalClassRule(),
			],
		);
	}
}
