<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Command;

/**
 * Commands can be registered using this class.
 */
class CommandDispatcher
{
	/**
	 * @param array<string,Command> $commandMap Map of command names to invokable objects
	 */
	public function __construct(private array $commandMap = [])
	{
		foreach ($commandMap as $id => $command) {
			$this->addCommand($id, $command);
		}
	}

	/**
	 * @return array<string>
	 */
	public function registeredCommands(): array
	{
		return \array_keys($this->commandMap);
	}

	/**
	 * @param array<int,mixed> $args
	 */
	public function dispatch(string $command, array $args = []): mixed
	{
		if (!\array_key_exists($command, $this->commandMap)) {
			throw new \RuntimeException(\sprintf(
				'Command "%s" not found, known commands: "%s"',
				$command,
				\implode('", "', \array_keys($this->commandMap)),
			));
		}

		if (!\is_callable($this->commandMap[$command])) {
			throw new \RuntimeException(\sprintf(
				'Command "%s" is not invokable',
				$command,
			));
		}

		return $this->commandMap[$command](...$args);
	}

	private function addCommand(string $id, Command $invokable): void
	{
		if (!\is_callable($invokable)) {
			throw new \RuntimeException(\sprintf(
				'Object "%s" is not invokable',
				$invokable::class,
			));
		}

		$this->commandMap[$id] = $invokable;
	}
}
