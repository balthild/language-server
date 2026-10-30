<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Handler;

final class ClosureHandler implements Handler
{
	public function __construct(private string $methodName, private \Closure $closure) {}

	/**
	 * {@inheritDoc}
	 */
	public function methods(): array
	{
		return [
			$this->methodName => 'handle',
		];
	}

	public function handle(): mixed
	{
		$args = \func_get_args();
		return ($this->closure)(...$args);
	}
}
