<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Server\ResponseWatcher;

use Amp\Future;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use Phpactor\LanguageServer\Core\Server\ResponseWatcher;

final class TestResponseWatcher implements ResponseWatcher
{
	/**
	 * @var list<int|string>
	 */
	private array $requestIds = [];

	public function __construct(private ResponseWatcher $innerWatcher = new DeferredResponseWatcher()) {}

	public function handle(ResponseMessage $response): void
	{
		$this->innerWatcher->handle($response);
	}

	/**
	 * @param mixed $result
	 */
	public function resolveLastResponse($result): void
	{
		$id = \array_shift($this->requestIds);
		if (null === $id) {
			throw new \RuntimeException('No responses left to handle');
		}
		$this->handle(new ResponseMessage($id, $result));
	}

	/**
	 * {@inheritDoc}
	 */
	public function waitForResponse(int|string $requestId): Future
	{
		$this->requestIds[] = $requestId;
		return $this->innerWatcher->waitForResponse($requestId);
	}
}
