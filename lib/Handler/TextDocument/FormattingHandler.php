<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Handler\TextDocument;

use Phpactor\LanguageServer\Core\Formatting\Formatter;
use Phpactor\LanguageServer\Core\Handler\CanRegisterCapabilities;
use Phpactor\LanguageServer\Core\Handler\Handler;
use Phpactor\LanguageServer\Core\Workspace\Workspace;
use Phpactor\LanguageServer\WorkDoneProgress\ProgressNotifier;
use Phpactor\LanguageServer\WorkDoneProgress\SilentWorkDoneProgressNotifier;
use Phpactor\LanguageServer\WorkDoneProgress\WorkDoneToken;
use Phpactor\LanguageServerProtocol\FormattingOptions;
use Phpactor\LanguageServerProtocol\ServerCapabilities;
use Phpactor\LanguageServerProtocol\TextDocumentIdentifier;
use Phpactor\LanguageServerProtocol\TextEdit;

class FormattingHandler implements Handler, CanRegisterCapabilities
{
	public function __construct(
		private Workspace $workspace,
		private Formatter $formatter,
		private ProgressNotifier $notifier = new SilentWorkDoneProgressNotifier(),
	) {}

	public function methods(): array
	{
		return ['textDocument/formatting' => 'formatting'];
	}

	/**
	 * @return list<TextEdit>|null
	 */
	public function formatting(
		TextDocumentIdentifier $textDocument,
		FormattingOptions $options,
	): ?array {
		$token = WorkDoneToken::generate();
		$this->notifier->create($token);
		$document = $this->workspace->get($textDocument->uri);
		$this->notifier->begin($token, 'Formatting document');
		try {
			$formatted = $this->formatter->format($document);
		} finally {
			$this->notifier->end($token);
		}

		return $formatted;
	}

	public function registerCapabiltiies(ServerCapabilities $capabilities): void
	{
		$capabilities->documentFormattingProvider = true;
	}
}
