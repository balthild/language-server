<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\WorkDoneProgress;

use Phpactor\LanguageServer\Core\Server\Client\WorkDoneProgressClient;
use Phpactor\LanguageServer\Core\Server\ClientApi;

final class WorkDoneProgressNotifier implements ProgressNotifier
{
	private WorkDoneProgressClient $api;

	public function __construct(ClientApi $api)
	{
		$this->api = $api->workDoneProgress();
	}

	/**
	 * {@inheritDoc}
	 */
	public function create(WorkDoneToken $token): void
	{
		$this->api->create($token);
	}

	/**
	 * {@inheritDoc}
	 */
	public function begin(
		WorkDoneToken $token,
		string $title,
		?string $message = null,
		?int $percentage = null,
		?bool $cancellable = null,
	): void {
		$this->api->begin($token, $title, $message, $percentage, $cancellable);
	}

	/**
	 * {@inheritDoc}
	 */
	public function report(
		WorkDoneToken $token,
		?string $message = null,
		?int $percentage = null,
		?bool $cancellable = null,
	): void {
		$this->api->report($token, $message, $percentage, $cancellable);
	}

	public function end(WorkDoneToken $token, ?string $message = null): void
	{
		$this->api->end($token, $message);
	}
}
