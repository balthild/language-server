<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Integration\Core\Server;

use Amp\ByteStream\ReadableBuffer;
use Amp\ByteStream\ReadableIterableStream;
use Amp\ByteStream\WritableBuffer;
use Amp\PHPUnit\AsyncTestCase;
use Phpactor\LanguageServer\Core\Dispatcher\Dispatcher\ClosureDispatcher;
use Phpactor\LanguageServer\Core\Dispatcher\Factory\ClosureDispatcherFactory;
use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\RawMessage;
use Phpactor\LanguageServer\Core\Rpc\RequestMessage;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use Phpactor\LanguageServer\Core\Server\Initializer\PredefinedInitializer;
use Phpactor\LanguageServer\Core\Server\LanguageServer;
use Phpactor\LanguageServer\Core\Server\Parser\LspMessageReader;
use Phpactor\LanguageServer\Core\Server\Stream\ResourceDuplexStream;
use Phpactor\LanguageServer\Core\Server\StreamProvider\ResourceStreamProvider;
use Phpactor\LanguageServer\Core\Server\Transmitter\LspMessageFormatter;
use Phpactor\LanguageServer\Core\Server\Transmitter\LspMessageSerializer;
use Phpactor\LanguageServer\Core\Server\Transmitter\MessageSerializer;
use Phpactor\LanguageServer\Core\Server\Transmitter\TestMessageSerializer;
use PHPUnit\Framework\Assert;
use Psr\Log\NullLogger;
use Revolt\EventLoop;

class LanguageServerTest extends AsyncTestCase
{
	/**
	 * @return void
	 */
	public function testDispatchesRequest(): void
	{
		$response = $this->dispatchRequest(
			new RequestMessage(1, 'foobar', []),
			static function (Message $message) {
				if (!$message instanceof RequestMessage) {
					throw new \Exception('not a request');
				}
				Assert::assertEquals('foobar', $message->method);
				return new ResponseMessage(1, [
					'foo' => 'bar',
				]);
			},
		);

		Assert::assertInstanceOf(RawMessage::class, $response);
		Assert::assertEquals(['foo' => 'bar'], $response->body()['result']);
	}

	/**
	 * @return void
	 */
	public function testHandlesMalformedRequest(): void
	{
		$serializer = new TestMessageSerializer('{"id":3,"foo":"bar"}');
		$response = $this->dispatchRequest(
			new RequestMessage(1, 'foobar', []),
			static fn (Message $message) => new ResponseMessage(1, ['foo' => 'bar']),
			$serializer,
		);

		Assert::assertInstanceOf(RawMessage::class, $response);
		self::assertEquals(255, $response->body()['error']['code']);
	}

	/**
	 * @param \Closure(Message): ?ResponseMessage $handler
	 */
	private function dispatchRequest(
		RequestMessage $request,
		\Closure $handler,
		?MessageSerializer $serializer = new LspMessageSerializer(),
	): ?RawMessage {
		$this->setTimeout(1);

		$formatter = new LspMessageFormatter($serializer);
		$message = $formatter->format($request);

		$output = new WritableBuffer();
		$stream = new ResourceDuplexStream(
			new ReadableIterableStream(
				(static function () use ($message) {
					yield $message;
				})(),
			),
			$output,
		);

		$server = new LanguageServer(
			new ClosureDispatcherFactory(static fn () => new ClosureDispatcher($handler)),
			new NullLogger(),
			new ResourceStreamProvider($stream, new NullLogger()),
			new PredefinedInitializer(),
		);

		EventLoop::delay(0.01, static function () use ($server): void {
			\Amp\async($server->shutdown(...));
		});

		$server->start();

		$parser = new LspMessageReader(new ResourceDuplexStream(
			new ReadableBuffer($output->buffer()),
			new WritableBuffer(),
		));

		return $parser->readMessage();
	}
}
