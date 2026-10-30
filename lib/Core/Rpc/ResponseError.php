<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Rpc;

final class ResponseError
{
	/**
	 * @param mixed $data
	 */
	public function __construct(public int $code, public string $message, public $data = null) {}
}
