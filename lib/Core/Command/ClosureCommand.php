<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Command;

class ClosureCommand implements Command
{
	public function __construct(private \Closure $closure) {}

	/**
	 * @param mixed[] $args
	 * @return mixed
	 */
	public function __invoke(...$args)
	{
		$closure = $this->closure;
		return $closure(...$args);
	}
}
