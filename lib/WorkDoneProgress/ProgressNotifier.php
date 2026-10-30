<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\WorkDoneProgress;

interface ProgressNotifier
{
	public function create(WorkDoneToken $token): void;

	/**
	 * @param int|null $percentage Percentage comprised between 0 and 100
	 */
	public function begin(
		WorkDoneToken $token,
		string $title,
		?string $message = null,
		?int $percentage = null,
		?bool $cancellable = null,
	): void;

	/**
	 * @param int|null $percentage Percentage comprised between 0 and 100
	 */
	public function report(
		WorkDoneToken $token,
		?string $message = null,
		?int $percentage = null,
		?bool $cancellable = null,
	): void;

	public function end(WorkDoneToken $token, ?string $message = null): void;
}
