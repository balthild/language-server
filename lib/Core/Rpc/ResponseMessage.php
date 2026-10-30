<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Rpc;

final class ResponseMessage extends Message implements \JsonSerializable
{
	public function __construct(
		public string|int $id,
		public mixed $result,
		public ?ResponseError $error = null,
	) {}

	/**
	 * @return array<string, mixed>
	 */
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
