<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server\Initializer;

use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\RequestMessage;
use Phpactor\LanguageServer\Core\Server\Initializer;
use Phpactor\LanguageServerProtocol\InitializeParams;

final class RequestInitializer implements Initializer
{
	public function provideInitializeParams(Message $request): InitializeParams
	{
		if (!$request instanceof RequestMessage) {
			throw new \RuntimeException(\sprintf(
				'First request must be a RequestMessage (to initialize), got "%s"',
				$request::class,
			));
		}

		if ($request->method !== 'initialize') {
			throw new \RuntimeException(\sprintf(
				'Method of first request must be "initialize", got "%s"',
				$request->method,
			));
		}

		return InitializeParams::fromArray($request->params ?? [], true);
	}
}
