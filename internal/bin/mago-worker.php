<?php

declare(strict_types=1);

use Mago\Sdk\Worker;
use Phpactor\LanguageServer\Internal\Mago\ProjectExtension;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\VarDumper;

require __DIR__ . '/../../vendor/autoload.php';

$dumper = new CliDumper('php://stderr');
$cloner = new VarCloner();

VarDumper::setHandler(function ($var) use ($cloner, $dumper) {
	$dumper->dump($cloner->cloneVar($var));
});

(new Worker(ProjectExtension::create()))->run();
