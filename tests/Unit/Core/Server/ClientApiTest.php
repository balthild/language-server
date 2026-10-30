<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Tests\Unit\Core\Server;

use Amp\PHPUnit\AsyncTestCase;
use Phpactor\LanguageServer\Core\Server\ClientApi;
use Phpactor\LanguageServer\Core\Server\RpcClient\TestRpcClient;
use Phpactor\LanguageServer\WorkDoneProgress\WorkDoneToken;
use Phpactor\LanguageServerProtocol\ApplyWorkspaceEditResult;
use Phpactor\LanguageServerProtocol\DidChangeWatchedFilesRegistrationOptions;
use Phpactor\LanguageServerProtocol\FileSystemWatcher;
use Phpactor\LanguageServerProtocol\MessageActionItem;
use Phpactor\LanguageServerProtocol\MessageType;
use Phpactor\LanguageServerProtocol\Registration;
use Phpactor\LanguageServerProtocol\Unregistration;
use Phpactor\LanguageServerProtocol\WorkspaceEdit;

class ClientApiTest extends AsyncTestCase
{
	/**
	 * @dataProvider provideWindowShowMessage
	 * @dataProvider provideWindowLogMessage
	 * @dataProvider provideWindowShowMessageRequest
	 * @dataProvider provideWorkspaceEdit
	 * @dataProvider provideWorkspaceExecuteCommand
	 * @dataProvider provideDiagnostics
	 * @dataProvider provideRegisterCapability
	 * @dataProvider provideWorkDoneProgress
	 */
	public function testSend(\Closure $executor, \Closure $assertions): void
	{
		$client = TestRpcClient::create();
		$api = new ClientApi($client);
		$result = $executor($api);

		$assertions($client, $result);
	}

	/**
	 * @return \Generator<mixed>
	 */
	public function provideWindowShowMessage(): \Generator
	{
		yield [
			static function (ClientApi $api): void {
				$api->window()->showMessage()->error('foobar');
				$api->window()->showMessage()->log('foobar');
				$api->window()->showMessage()->info('foobar');
				$api->window()->showMessage()->warning('foobar');
			},
			static function (TestRpcClient $client): void {
				$message = $client->transmitter()->shiftNotification();
				self::assertEquals('window/showMessage', $message->method);
				self::assertEquals(MessageType::ERROR, $message->params['type']);
				self::assertEquals('foobar', $message->params['message']);

				$message = $client->transmitter()->shiftNotification();
				self::assertEquals(MessageType::LOG, $message->params['type']);
				self::assertEquals('foobar', $message->params['message']);

				$message = $client->transmitter()->shiftNotification();
				self::assertEquals(MessageType::INFO, $message->params['type']);
				self::assertEquals('foobar', $message->params['message']);

				$message = $client->transmitter()->shiftNotification();
				self::assertEquals(MessageType::WARNING, $message->params['type']);
				self::assertEquals('foobar', $message->params['message']);
			},
		];
	}

	/**
	 * @return \Generator<mixed>
	 */
	public function provideWindowLogMessage(): \Generator
	{
		yield [
			static function (ClientApi $api): void {
				$api->window()->logMessage()->error('foobar');
				$api->window()->logMessage()->log('foobar');
				$api->window()->logMessage()->info('foobar');
				$api->window()->logMessage()->warning('foobar');
			},
			static function (TestRpcClient $client): void {
				$message = $client->transmitter()->shiftNotification();
				self::assertEquals('window/logMessage', $message->method);
				self::assertEquals(MessageType::ERROR, $message->params['type']);
				self::assertEquals('foobar', $message->params['message']);

				$message = $client->transmitter()->shiftNotification();
				self::assertEquals(MessageType::LOG, $message->params['type']);
				self::assertEquals('foobar', $message->params['message']);

				$message = $client->transmitter()->shiftNotification();
				self::assertEquals(MessageType::INFO, $message->params['type']);
				self::assertEquals('foobar', $message->params['message']);

				$message = $client->transmitter()->shiftNotification();
				self::assertEquals(MessageType::WARNING, $message->params['type']);
				self::assertEquals('foobar', $message->params['message']);
			},
		];
	}

	/**
	 * @return \Generator<mixed>
	 */
	public function provideWindowShowMessageRequest(): \Generator
	{
		yield [
			static function (ClientApi $api) {
				$future = \Amp\async(static fn () => $api
					->window()
					->showMessageRequest()
					->info('foobar', new MessageActionItem('foobar')));
				\Amp\delay(0);

				return $future;
			},
			static function (TestRpcClient $client, $result): void {
				$client->responseWatcher()->resolveLastResponse(['title' => 'foobar']);
				$message = $client->transmitter()->shiftRequest();
				self::assertEquals('window/showMessageRequest', $message->method);
				self::assertEquals(MessageType::INFO, $message->params['type']);
				self::assertEquals('foobar', $message->params['message']);

				$result = $result->await();
				self::assertInstanceOf(MessageActionItem::class, $result);
				self::assertEquals('foobar', $result->title);
			},
		];
	}

	/**
	 * @return \Generator<mixed>
	 */
	public function provideWorkspaceEdit(): \Generator
	{
		yield [
			static function (ClientApi $api) {
				$future = \Amp\async(static fn () => $api->workspace()->applyEdit(new WorkspaceEdit([])));
				\Amp\delay(0);

				return $future;
			},
			static function (TestRpcClient $client, $result): void {
				$client
					->responseWatcher()
					->resolveLastResponse([
						'applied' => false,
						'failureReason' => 'sorry',
					]);
				$message = $client->transmitter()->shiftRequest();
				self::assertEquals('workspace/applyEdit', $message->method);

				$result = $result->await();
				self::assertInstanceOf(ApplyWorkspaceEditResult::class, $result);
				self::assertFalse($result->applied);
				self::assertEquals('sorry', $result->failureReason);
			},
		];
	}

	/**
	 * @return \Generator<mixed>
	 */
	public function provideWorkspaceExecuteCommand(): \Generator
	{
		yield [
			static function (ClientApi $api) {
				$future = \Amp\async(static fn () => $api->workspace()->executeCommand('one', [
					'one',
					'two',
				]));
				\Amp\delay(0);

				return $future;
			},
			static function (TestRpcClient $client, $result): void {
				$client->responseWatcher()->resolveLastResponse('result');
				$message = $client->transmitter()->shiftRequest();
				self::assertEquals('workspace/executeCommand', $message->method);

				$result = $result->await();
				self::assertEquals('result', $result);
			},
		];
	}

	/**
	 * @return \Generator<mixed>
	 */
	public function provideDiagnostics(): \Generator
	{
		yield [
			static function (ClientApi $api): void {
				$api->diagnostics()->publishDiagnostics(
					'file://file.php',
					1,
					[],
				);
			},
			static function (TestRpcClient $client, $result): void {
				$message = $client->transmitter()->shiftNotification();
				self::assertEquals('textDocument/publishDiagnostics', $message->method);
			},
		];
	}

	/**
	 * @return \Generator<mixed>
	 */
	public function provideRegisterCapability(): \Generator
	{
		yield [
			static function (ClientApi $api): void {
				\Amp\async(static fn () => $api->client()->registerCapability(
					new Registration(
						'foobar',
						'workspace/didChangeWatchedFiles',
						new DidChangeWatchedFilesRegistrationOptions([
							new FileSystemWatcher('**/*.php'),
						]),
					),
				));
				\Amp\delay(0);
			},
			static function (TestRpcClient $client, $result): void {
				$message = $client->transmitter()->shiftRequest();
				self::assertEquals('client/registerCapability', $message->method);
			},
		];

		yield [
			static function (ClientApi $api): void {
				\Amp\async(static fn () => $api->client()->unregisterCapability(
					new Unregistration('10', 'foo'),
				));
				\Amp\delay(0);
			},
			static function (TestRpcClient $client, $result): void {
				$message = $client->transmitter()->shiftRequest();
				self::assertEquals('client/unregisterCapability', $message->method);
			},
		];
	}

	/**
	 * @return \Generator<mixed>
	 */
	public function provideWorkDoneProgress(): \Generator
	{
		yield 'Creating Work Done Progress' => [
			static function (ClientApi $api): void {
				$token = new WorkDoneToken('4ef439b3-3c6a-4c98-ae0a-af2b4503cb15');
				\Amp\async(static fn () => $api->workDoneProgress()->create($token));
				\Amp\delay(0);
			},
			static function (TestRpcClient $client): void {
				$message = $client->transmitter()->shiftRequest();
				self::assertEquals('window/workDoneProgress/create', $message->method);
				self::assertEquals('4ef439b3-3c6a-4c98-ae0a-af2b4503cb15', $message->params['token']);
			},
		];

		yield 'Work Done Progress Begin' => [
			static function (ClientApi $api): void {
				$token = new WorkDoneToken('4ef439b3-3c6a-4c98-ae0a-af2b4503cb15');
				$api->workDoneProgress()->begin($token, 'title', 'message');
			},
			static function (TestRpcClient $client, $result): void {
				$message = $client->transmitter()->shiftNotification();
				self::assertEquals('$/progress', $message->method);
				self::assertEquals('4ef439b3-3c6a-4c98-ae0a-af2b4503cb15', $message->params['token']);
				self::assertEquals('begin', $message->params['value']['kind']);
				self::assertEquals('title', $message->params['value']['title']);
				self::assertEquals('message', $message->params['value']['message']);
				self::assertNull($message->params['value']['percentage']);
				self::assertNull($message->params['value']['cancellable']);
			},
		];

		yield 'Work Done Progress Report' => [
			static function (ClientApi $api): void {
				$token = new WorkDoneToken('4ef439b3-3c6a-4c98-ae0a-af2b4503cb15');
				$api->workDoneProgress()->report($token, 'message', 20);
			},
			static function (TestRpcClient $client, $result): void {
				$message = $client->transmitter()->shiftNotification();
				self::assertEquals('$/progress', $message->method);
				self::assertEquals('4ef439b3-3c6a-4c98-ae0a-af2b4503cb15', $message->params['token']);
				self::assertEquals('report', $message->params['value']['kind']);
				self::assertEquals('message', $message->params['value']['message']);
				self::assertEquals(20, $message->params['value']['percentage']);
				self::assertNull($message->params['value']['cancellable']);
			},
		];

		yield 'Work Done Progress End' => [
			static function (ClientApi $api): void {
				$token = new WorkDoneToken('4ef439b3-3c6a-4c98-ae0a-af2b4503cb15');
				$api->workDoneProgress()->end($token, 'message');
			},
			static function (TestRpcClient $client, $result): void {
				$message = $client->transmitter()->shiftNotification();
				self::assertEquals('$/progress', $message->method);
				self::assertEquals('4ef439b3-3c6a-4c98-ae0a-af2b4503cb15', $message->params['token']);
				self::assertEquals('end', $message->params['value']['kind']);
				self::assertEquals('message', $message->params['value']['message']);
			},
		];
	}
}
