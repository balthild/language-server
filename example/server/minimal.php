#!/usr/bin/env php
<?php

declare(strict_types=1);

use Phpactor\LanguageServer\Core\Dispatcher\Dispatcher\MiddlewareDispatcher;
use Phpactor\LanguageServer\Core\Dispatcher\Factory\ClosureDispatcherFactory;
use Phpactor\LanguageServer\Core\Middleware\RequestHandler;
use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\RequestMessage;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use Phpactor\LanguageServer\Core\Server\Transmitter\MessageTransmitter;
use Phpactor\LanguageServer\LanguageServerBuilder;
use Phpactor\LanguageServer\Middleware\ClosureMiddleware;
use Phpactor\LanguageServerProtocol\InitializeParams;

require __DIR__ . '/../../vendor/autoload.php';

// @mago-expect lint:prefer-arrow-function
$builder = LanguageServerBuilder::create(new ClosureDispatcherFactory(
	function (MessageTransmitter $transmitter, InitializeParams $params) {
		return new MiddlewareDispatcher(
			new ClosureMiddleware(function (Message $message, RequestHandler $handler) {
				if (!$message instanceof RequestMessage) {
					return $handler->handle($message);
				}

				return new ResponseMessage($message->id, 'Hello World!');
			}),
		);
	},
));

$builder
	->build()
	->run();
