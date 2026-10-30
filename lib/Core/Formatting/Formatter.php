<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Formatting;

use Phpactor\LanguageServerProtocol\TextDocumentItem;
use Phpactor\LanguageServerProtocol\TextEdit;

interface Formatter
{
	/**
	 * @return list<TextEdit>|null
	 */
	public function format(TextDocumentItem $textDocument): ?array;
}
