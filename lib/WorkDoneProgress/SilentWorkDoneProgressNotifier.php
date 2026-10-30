<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\WorkDoneProgress;

class SilentWorkDoneProgressNotifier implements ProgressNotifier
{
	public function create(WorkDoneToken $token): void {}

	public function begin(
		WorkDoneToken $token,
		string $title,
		?string $message = null,
		?int $percentage = null,
		?bool $cancellable = null,
	): void {}

	public function report(
		WorkDoneToken $token,
		?string $message = null,
		?int $percentage = null,
		?bool $cancellable = null,
	): void {}

	public function end(WorkDoneToken $token, ?string $message = null): void {}
}
