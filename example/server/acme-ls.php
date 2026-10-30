#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use AcmeLs\AcmeLsDispatcherFactory;
use Phpactor\LanguageServer\LanguageServerBuilder;
use Psr\Log\NullLogger;

$logger = new NullLogger();
LanguageServerBuilder::create(new AcmeLsDispatcherFactory($logger))
	->build()
	->run();
