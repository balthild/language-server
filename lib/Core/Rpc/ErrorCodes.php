<?php

declare(strict_types=1);

namespace Phpactor\LanguageServer\Core\Rpc;

final class ErrorCodes
{
	// defined by JSON RPC
	const PARSE_ERROR = -32700;
	const INVALID_REQUEST = -32600;
	const METHOD_NOT_FOUND = -32601;
	const INVALID_PARAMS = -32602;
	const INTERNAL_ERROR = -32603;
	const SERVER_ERROR_START = -32099;
	const SERVER_ERROR_END = -32000;
	const SERVER_NOT_INITIALIZED = -32002;
	const UNKNOWN_ERROR_CODE = -32001;

	// defined by LSP
	const REQUEST_CANCELLED = -32800;
}
