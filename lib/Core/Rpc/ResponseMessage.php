<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Rpc;

final class ResponseMessage extends Message implements \JsonSerializable
{
	/**
	 * @param mixed $result
	 * @param string|int $id
	 */
	public function __construct(public $id, public $result, public ?ResponseError $error = null) {}

	public function jsonSerialize(): array
	{
		$response = [
			'jsonrpc' => $this->jsonrpc,
			'id' => $this->id,
		];

		if (null !== $this->error) {
			$response['error'] = $this->error;
		} else {
			$response['result'] = $this->result;
		}

		return $response;
	}
}
