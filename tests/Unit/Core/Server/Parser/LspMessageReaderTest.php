<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Core\Server\Parser;

use Amp\ByteStream\ReadableBuffer;
use Amp\ByteStream\WritableBuffer;
use Phpactor\LanguageServer\Core\Rpc\RawMessage;
use Phpactor\LanguageServer\Core\Server\Parser\Exception\CouldNotDecodeBody;
use Phpactor\LanguageServer\Core\Server\Parser\LspMessageReader;
use Phpactor\LanguageServer\Core\Server\Stream\ResourceDuplexStream;
use Phpactor\TestUtils\PHPUnit\TestCase;

class LspMessageReaderTest extends TestCase
{
	protected function setUp(): void {}

	public function testYieldsRequest(): void
	{
		$stream = new ResourceDuplexStream(new ReadableBuffer(
			<<<EOT
				Content-Length: 74\r\n
				Content-Type: foo\r\n\r\n
				{
				   "jsonrpc": "2.0",
				   "id": 1,
				   "method": "test",
				   "params": {}
				}
				EOT,
		), new WritableBuffer());

		$reader = new LspMessageReader($stream);
		$result = $reader->readMessage();
		$this->assertInstanceOf(RawMessage::class, $result);
	}

	public function testSkipsInvalidHeader(): void
	{
		$stream = new ResourceDuplexStream(new ReadableBuffer(
			<<<EOT
				Content-Length:74\r\n
				Content-Typefoo\r\n\r\n
				{
				   "jsonrpc": "2.0",
				   "id": 1,
				   "method": "test",
				   "params": {}
				}
				EOT,
		), new WritableBuffer());

		$reader = new LspMessageReader($stream);
		$result = $reader->readMessage();
		$this->assertInstanceOf(RawMessage::class, $result);
	}

	public function testReadsMultipleRequests(): void
	{
		$stream = new ResourceDuplexStream(new ReadableBuffer(
			<<<EOT
				Content-Length: 74\r\n
				Content-Type: foo\r\n\r\n
				{
				   "jsonrpc": "2.0",
				   "id": 1,
				   "method": "test",
				   "params": {}
				}Content-Length: 74\r\n
				Content-Type: foo\r\n\r\n
				{
				   "jsonrpc": "2.0",
				   "id": 1,
				   "method": "tset",
				   "params": {}
				}
				EOT,
		), new WritableBuffer());
		$reader = new LspMessageReader($stream);
		$result = $reader->readMessage();
		$this->assertInstanceOf(RawMessage::class, $result, 'first');
		$result = $reader->readMessage();
		$this->assertInstanceOf(RawMessage::class, $result, 'second');
	}

	public function testReadingAnInvalidStream(): void
	{
		$stream = new ResourceDuplexStream(new ReadableBuffer(
			<<<EOT
				Content-Length: 74\r\n
				Content-Type: foo\r\n\r\n
				{
				    "jsonrpc":"2.0",
				    "method":"textDocument/didOpen",
				    "params": {
				        "textDocument": {
				        }
				EOT,
		), new WritableBuffer());
		$this->expectException(CouldNotDecodeBody::class);

		$reader = new LspMessageReader($stream);
		$result = $reader->readMessage();
	}
}
