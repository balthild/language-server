<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Internal\Mago;

use Mago\Sdk\Syntax\Node;
use Mago\Sdk\Syntax\SourceFile;

class SyntaxHelper
{
	/**
	 * @param non-negative-int $level
	 */
	public static function dump(SourceFile $file, Node $node, int $level = 0): void
	{
		$indent = \str_repeat('  ', $level);
		$kind = $node->kind->value;
		$text = \json_encode($file->getText($node));
		\fprintf(STDERR, "%s%s %s\n", $indent, $kind, $text);

		foreach ($file->getChildren($node) as $child) {
			self::dump($file, $child, $level + 1);
		}

		if ($level === 0) {
			\fflush(STDERR);
		}
	}
}
