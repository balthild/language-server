<?php

declare(strict_types=1);

/**
 * Scan PHP files for mago#2646-style cases: a *single* parameter that the
 * formatter refuses to break, even when the signature exceeds print-width or
 * the default value already spans multiple lines.
 *
 * Read-only: this script never writes to the scanned files.
 *
 * Usage:
 *   php scan-single-param.php [--print-width=N] [--tab-width=N] [paths...]
 *
 * With no paths, every PHP file tracked by git (plus untracked, non-ignored
 * ones) is scanned.
 */

const T_AMP = 38;

const T_PAREN_OPEN = 40;

const T_PAREN_CLOSE = 41;

const T_COMMA = 44;

const T_COLON = 58;

const T_BRACE_OPEN = 123;

const T_BRACE_CLOSE = 125;

const T_BRACKET_OPEN = 91;

const T_BRACKET_CLOSE = 93;

const T_SEMICOLON = 59;

const T_EQUAL_ARROW_SIGN = '=>';

$printWidth = 100;

// Verified against the real formatter: a leading tab counts as a single column,
// not as `indentWidth`, so a tab-indented line may hold 99 columns of content.
$tabWidth = 1;

$paths = [];

foreach (array_slice($argv, 1) as $arg) {
	if (str_starts_with($arg, '--print-width=')) {
		$printWidth = (int) substr($arg, 14);
	} elseif (str_starts_with($arg, '--tab-width=')) {
		$tabWidth = (int) substr($arg, 12);
	} else {
		$paths[] = $arg;
	}
}

$files = $paths === [] ? discoverFiles() : $paths;

$hits = [];
$borderline = [];
$multiParamHits = [];
$scanned = 0;

foreach ($files as $file) {
	$source = file_get_contents($file);

	if ($source === false) {
		fwrite(STDERR, "cannot read: {$file}\n");

		continue;
	}

	$scanned++;

	foreach (scanSource($source, $printWidth, $tabWidth) as $finding) {
		$finding['file'] = $file;

		if ($finding['paramCount'] === 1) {
			if ($finding['reason'] !== null) {
				$hits[] = $finding;
			} elseif ($finding['width'] >= ($printWidth - 10)) {
				$borderline[] = $finding;
			}
		} elseif ($finding['reason'] === 'WIDTH') {
			// mago always breaks multi-parameter lists, so an inline one that is
			// too wide means the file is either unformatted or the formatter failed.
			$multiParamHits[] = $finding;
		}
	}
}

report($scanned, $printWidth, $tabWidth, $hits, $borderline, $multiParamHits);

/**
 * @return list<string>
 */
function discoverFiles(): array
{
	$out = [];
	$status = 0;

	exec("git ls-files --cached --others --exclude-standard '*.php'", $out, $status);

	if ($status !== 0) {
		fwrite(STDERR, "git ls-files failed; pass explicit paths instead\n");

		exit(1);
	}

	return $out;
}

/**
 * @return list<array{line: int, name: string, kind: string, paramCount: int, width: int, reason: ?string, detail: string}>
 */
function scanSource(string $source, int $printWidth, int $tabWidth): array
{
	$tokens = token_get_all($source);
	$entries = [];
	$offset = 0;
	$line = 1;

	foreach ($tokens as $token) {
		if (is_array($token)) {
			[$id, $text] = $token;
		} else {
			$id = null;
			$text = $token;
		}

		$entries[] = [
			'id' => $id,
			'text' => $text,
			'start' => $offset,
			'end' => $offset + strlen($text),
			'line' => $line,
		];

		$offset += strlen($text);
		$line += substr_count($text, "\n");
	}

	$findings = [];
	$count = count($entries);

	for ($i = 0; $i < $count; $i++) {
		$id = $entries[$i]['id'];

		if ($id !== T_FUNCTION && $id !== T_FN) {
			continue;
		}

		$finding = scanFunctionLike($source, $entries, $i, $printWidth, $tabWidth);

		if ($finding !== null) {
			$findings[] = $finding;
		}
	}

	return $findings;
}

/**
 * @param list<array{id: ?int, text: string, start: int, end: int, line: int}> $entries
 *
 * @return null|array{line: int, name: string, kind: string, paramCount: int, width: int, reason: ?string, detail: string}
 */
function scanFunctionLike(
	string $source,
	array $entries,
	int $index,
	int $printWidth,
	int $tabWidth,
): ?array {
	$count = count($entries);
	$kind = $entries[$index]['id'] === T_FN ? 'fn' : 'function';
	$name = '{closure}';

	$i = nextSignificant($entries, $index + 1);

	if ($i >= $count) {
		return null;
	}

	if ($entries[$i]['text'] === '&') {
		$i = nextSignificant($entries, $i + 1);
	}

	// `use function Foo\bar;` and friends: the name is not followed by `(`.
	if ($kind === 'function' && $entries[$i]['id'] === T_STRING) {
		$name = $entries[$i]['text'];
		$i = nextSignificant($entries, $i + 1);
	}

	if ($i >= $count || $entries[$i]['text'] !== '(') {
		return null;
	}

	$openIndex = $i;
	$closeIndex = matchBracket($entries, $openIndex, '(', ')');

	if ($closeIndex === null) {
		return null;
	}

	[$paramCount, $firstParamIndex] = countParams($entries, $openIndex, $closeIndex);

	if ($paramCount === 0) {
		return null;
	}

	// A properly broken list puts the first parameter on its own line.
	$leading = substr(
		$source,
		$entries[$openIndex]['end'],
		$entries[$firstParamIndex]['start'] - $entries[$openIndex]['end'],
	);

	if (str_contains($leading, "\n")) {
		return null;
	}

	$signatureEnd = extendSignature($entries, $openIndex, $closeIndex);
	$lineStart = strrpos(substr($source, 0, $entries[$openIndex]['start']), "\n");
	$lineStart = $lineStart === false ? 0 : $lineStart + 1;
	$measured = substr($source, $lineStart, $signatureEnd - $lineStart);
	$width = displayWidth(rtrim($measured), $tabWidth);

	if (getenv('SPAN_DEBUG') !== false) {
		fwrite(STDERR, sprintf("[span %d] %s\n", $width, str_replace("\t", '→', $measured)));
	}

	$list = substr(
		$source,
		$entries[$openIndex]['start'],
		$entries[$closeIndex]['end'] - $entries[$openIndex]['start'],
	);

	$reason = null;
	$detail = '';
	$newlineLine = null;

	if (str_contains($list, "\n")) {
		$reason = 'MULTILINE-DEFAULT';
		$detail = 'default value already spans multiple lines';

		foreach (explode("\n", $list) as $offset => $lineText) {
			if ($offset > 0) {
				$newlineLine = $entries[$openIndex]['line'] + $offset;
				$detail .= sprintf(' (break at line %d)', $newlineLine);

				break;
			}
		}
	} elseif ($width > $printWidth) {
		$reason = 'WIDTH';
		$detail = sprintf('%d > %d columns', $width, $printWidth);
	}

	return [
		'line' => $entries[$index]['line'],
		'name' => $name,
		'kind' => $kind,
		'paramCount' => $paramCount,
		'width' => $width,
		'reason' => $reason,
		'detail' => $detail,
	];
}

/**
 * @param list<array{id: ?int, text: string, start: int, end: int, line: int}> $entries
 */
function nextSignificant(array $entries, int $i): int
{
	$count = count($entries);

	while ($i < $count) {
		$id = $entries[$i]['id'];

		if ($id !== T_WHITESPACE && $id !== T_COMMENT && $id !== T_DOC_COMMENT) {
			return $i;
		}

		$i++;
	}

	return $count;
}

/**
 * Find the matching bracket, counting only single-character tokens so that
 * parentheses inside strings and interpolation are ignored.
 *
 * @param list<array{id: ?int, text: string, start: int, end: int, line: int}> $entries
 */
function matchBracket(array $entries, int $openIndex, string $open, string $close): ?int
{
	$depth = 0;
	$count = count($entries);

	for ($i = $openIndex; $i < $count; $i++) {
		if ($entries[$i]['id'] !== null) {
			continue;
		}

		$text = $entries[$i]['text'];

		if ($text === $open) {
			$depth++;
		} elseif ($text === $close) {
			$depth--;

			if ($depth === 0) {
				return $i;
			}
		}
	}

	return null;
}

/**
 * @param list<array{id: ?int, text: string, start: int, end: int, line: int}> $entries
 *
 * @return array{0: int, 1: int} parameter count and index of the first parameter token
 */
function countParams(array $entries, int $openIndex, int $closeIndex): array
{
	// Segments separated by top-level commas that contain at least one
	// significant token. A trailing comma therefore does not add a parameter.
	$depth = 0;
	$count = 0;
	$first = null;
	$hasContent = false;

	for ($i = $openIndex + 1; $i < $closeIndex; $i++) {
		$id = $entries[$i]['id'];
		$text = $entries[$i]['text'];

		if ($id === null) {
			if ($text === '(' || $text === '[' || $text === '{') {
				$depth++;
			} elseif ($text === ')' || $text === ']' || $text === '}') {
				$depth--;
			} elseif ($text === ',' && $depth === 0) {
				if ($hasContent) {
					$count++;
				}

				$hasContent = false;

				continue;
			}
		}

		if ($id !== T_WHITESPACE && $id !== T_COMMENT && $id !== T_DOC_COMMENT) {
			$hasContent = true;
			$first ??= $i;
		}
	}

	if ($hasContent) {
		$count++;
	}

	return [$count, $first ?? ($openIndex + 1)];
}

/**
 * Extend the measured region past the closing parenthesis to cover the return
 * type, closure `use` list and an inline empty body — everything the formatter
 * keeps on the same line.
 *
 * @param list<array{id: ?int, text: string, start: int, end: int, line: int}> $entries
 */
function extendSignature(array $entries, int $openIndex, int $closeIndex): int
{
	$count = count($entries);
	$end = $entries[$closeIndex]['end'];
	$i = $closeIndex + 1;

	while (true) {
		$i = nextSignificant($entries, $i);

		if ($i >= $count || !isSameLine($entries, $closeIndex, $i)) {
			break;
		}

		$id = $entries[$i]['id'];
		$text = $entries[$i]['text'];

		if ($id === T_USE) {
			$useOpen = nextSignificant($entries, $i + 1);
			$useClose = $entries[$useOpen]['text'] === '('
				? matchBracket($entries, $useOpen, '(', ')')
				: null;

			// A broken `use` list is a line of its own; it must not stretch the
			// measured region across lines.
			if ($useClose === null || !isSameLine($entries, $closeIndex, $useClose)) {
				break;
			}

			$end = $entries[$useClose]['end'];
			$i = $useClose + 1;

			continue;
		}

		if ($text === ':') {
			$i++;
			$depth = 0;

			while ($i < $count) {
				$innerId = $entries[$i]['id'];
				$innerText = $entries[$i]['text'];

				if (!isSameLine($entries, $closeIndex, $i)) {
					break;
				}

				if ($innerId === null) {
					if ($innerText === '(' || $innerText === '[') {
						$depth++;
					} elseif ($innerText === ')' || $innerText === ']') {
						$depth--;
					} elseif ($depth === 0 && ($innerText === '{' || $innerText === ';')) {
						break;
					}
				}

				if ($innerId === T_DOUBLE_ARROW && $depth === 0) {
					break;
				}

				$end = $entries[$i]['end'];
				$i++;
			}

			continue;
		}

		if ($text === '{') {
			$end = $entries[$i]['end'];
			$next = nextSignificant($entries, $i + 1);

			if (
				$next < $count
				&& $entries[$next]['text'] === '}'
				&& isSameLine($entries, $closeIndex, $next)
			) {
				$end = $entries[$next]['end'];
			}

			break;
		}

		break;
	}

	return $end;
}

/**
 * @param list<array{id: ?int, text: string, start: int, end: int, line: int}> $entries
 */
function isSameLine(array $entries, int $reference, int $other): bool
{
	return $entries[$reference]['line'] === $entries[$other]['line'];
}

function displayWidth(string $text, int $tabWidth): int
{
	$text = str_replace("\t", str_repeat(' ', $tabWidth), $text);

	return function_exists('mb_strwidth') ? mb_strwidth($text, 'UTF-8') : strlen($text);
}

/**
 * @param list<array{file: string, line: int, name: string, kind: string, paramCount: int, width: int, reason: ?string, detail: string}> $hits
 * @param list<array{file: string, line: int, name: string, kind: string, paramCount: int, width: int, reason: ?string, detail: string}> $borderline
 * @param list<array{file: string, line: int, name: string, kind: string, paramCount: int, width: int, reason: ?string, detail: string}> $multiParamHits
 */
function report(
	int $scanned,
	int $printWidth,
	int $tabWidth,
	array $hits,
	array $borderline,
	array $multiParamHits,
): void {
	printf("scanned %d files (print-width=%d, tab-width=%d)\n\n", $scanned, $printWidth, $tabWidth);

	if ($hits === []) {
		echo "no single-parameter findings\n";
	} else {
		printf("== single parameter not broken (%d) ==\n", count($hits));

		foreach ($hits as $hit) {
			printf(
				"  %-6s %s:%d  %s%s()  %s\n",
				$hit['reason'],
				$hit['file'],
				$hit['line'],
				$hit['kind'] === 'fn' ? '' : 'function ',
				$hit['name'],
				$hit['detail'],
			);
		}

		echo "\n";
	}

	if ($borderline !== []) {
		printf("== near the limit, single parameter (%d) ==\n", count($borderline));

		foreach ($borderline as $hit) {
			printf(
				"  %d cols  %s:%d  %s\n",
				$hit['width'],
				$hit['file'],
				$hit['line'],
				$hit['name'],
			);
		}

		echo "\n";
	}

	if ($multiParamHits !== []) {
		printf("== inline multi-parameter list over print-width (%d) ==\n", count($multiParamHits));

		foreach ($multiParamHits as $hit) {
			printf(
				"  %s:%d  %s  params=%d  %s\n",
				$hit['file'],
				$hit['line'],
				$hit['name'],
				$hit['paramCount'],
				$hit['detail'],
			);
		}

		echo "\n";
	}
}
