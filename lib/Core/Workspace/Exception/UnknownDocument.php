<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Workspace\Exception;

class UnknownDocument extends \Exception
{
	public function __construct(string $documentUri)
	{
		parent::__construct(\sprintf(
			'Unknown text document "%s"',
			$documentUri,
		));
	}
}
