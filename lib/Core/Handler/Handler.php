<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Handler;

/**
 * Language Server Handler
 *
 * See documentation for the `methods` method for more details.
 */
interface Handler
{
	/**
	 * Return a map of RPC method names to instance method names.
	 *
	 * For example:
	 *
	 * ```
	 *     public function methods(): array
	 *     {
	 *         return [
	 *             'textDocument/didOpen' => 'didOpen'
	 *             'textDocument/completion' => 'complete'
	 *         ];
	 *     }
	 *
	 *     public function didOpen(DidOpenTextDocumentParams $params): void
	 *     {
	 *     }
	 *
	 *     public function complete(CompletionParams $params, ?Cancellation $cancellation = null): CompletionList
	 *     {
	 *         return new CompletionList(// ...);
	 *     }
	 * ```
	 *
	 * Methods may do anything a normal PHP method can do: the server runs them
	 * in a fiber, so blocking on I/O suspends only this request.
	 *
	 * The arguments passed by the RPC call depend on the `ArgumentResolver`
	 * implementation used by the `HandlerMethodRunner`.
	 *
	 * @return array<string, string>
	 */
	public function methods(): array;
}
