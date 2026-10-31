<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server\Client;

use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use Phpactor\LanguageServer\Core\Server\RpcClient;
use Phpactor\LanguageServerProtocol\Registration;
use Phpactor\LanguageServerProtocol\Unregistration;

final class ClientClient
{
	public function __construct(private RpcClient $client) {}

	public function registerCapability(Registration ...$registrations): ResponseMessage
	{
		return $this->client->request('client/registerCapability', [
			'registrations' => $registrations,
		]);
	}

	public function unregisterCapability(Unregistration ...$unregistrations): ResponseMessage
	{
		return $this->client->request('client/unregisterCapability', [
			'unregisterations' => $unregistrations,
		]);
	}
}
