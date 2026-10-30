<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\WorkDoneProgress;

use Phpactor\LanguageServer\Core\Server\Client\MessageClient;
use Phpactor\LanguageServer\Core\Server\ClientApi;

final class MessageProgressNotifier implements ProgressNotifier
{
	private MessageClient $api;

	public function __construct(ClientApi $api)
	{
		$this->api = $api->window()->showMessage();
	}

	/**
	 * {@inheritDoc}
	 */
	public function create(WorkDoneToken $token): void {}

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
		$this->api->info($message ?? '');
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
		$this->api->info(\sprintf('%s - %d%%', $message, $percentage));
	}

	public function end(WorkDoneToken $token, ?string $message = null): void
	{
		$this->api->info($message ?? '');
	}
}
