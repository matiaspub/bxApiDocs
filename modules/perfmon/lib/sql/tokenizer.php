<?php
namespace Bitrix\Perfmon\Sql;

class Token
{
	const T_WHITESPACE = 0;
	const T_STRING = 1;
	const T_CHAR = 2;

	const T_SINGLE_QUOTE = 3;
	const T_DOUBLE_QUOTE = 4;
	const T_BACK_QUOTE = 5;
	const T_SQUARE_QUOTE = 6;

	const T_COMMENT = 7;

	public $type;
	public $text;
	public $upper;
	public $line;
	public $level;

	/**
	 * @param integer $type Type of the token.
	 * @param string $text Text of the token.
	 */
	public function __construct($type, $text)
	{
		$this->type = $type;
		$this->text = $text;
		$this->upper = strtoupper($this->text);
	}

	/**
	 * Sets new text for the token.
	 * <p>
	 * And updates member $upper properly.
	 *
	 * @param string $text New text.
	 *
	 * @return void
	 */
	public function setText($text)
	{
		$this->text = $text;
		$this->upper = strtoupper($this->text);
	}

	/**
	 * Adds new text for the token.
	 * <p>
	 * And updates member $upper properly.
	 *
	 * @param string $text A chunk to be added.
	 *
	 * @return void
	 */
	public function appendText($text)
	{
		$this->text .= $text;
		$this->upper = strtoupper($this->text);
	}
}

class Tokenizer
{
	protected $index = 0;
	protected $bookmark = 0;
	/** @var array[Token] */
	protected $tokens = array();

	/**
	 * Splits a text into tokens, creates new Tokenizer object, and returns it.
	 *
	 * @param string $sql Sql text.
	 *
	 * @return Tokenizer
	 */
	public static function createFromString($sql)
	{
		$tokenizer = new self;
		$tokenizer->_tokenize($sql);
		$tokenizer->makeLines();
		$tokenizer->makeParenthesis();
		return $tokenizer;
	}

	/**
	 * Creates new Tokenizer objects and sets its tokens into given.
	 *
	 * @param array[Token] $tokens New tokens.
	 *
	 * @return Tokenizer
	 */
	public static function createFromTokens(array $tokens)
	{
		$tokenizer = new self;
		$tokenizer->tokens = $tokens;
		return $tokenizer;
	}

	/**
	 * Returns all the tokens.
	 *
	 * @return array[Token]
	 */
	public function getTokens()
	{
		return $this->tokens;
	}

	/**
	 * Resets internal state.
	 *
	 * @return void
	 */
	public function resetState()
	{
		$this->index = 0;
	}

	/**
	 * Remembers current position.
	 *
	 * @return void
	 * @see Tokenizer::restoreBookmark
	 */
	public function setBookmark()
	{
		$this->bookmark = $this->index;
	}

	/**
	 * Restores previously remembered position.
	 *
	 * @return void
	 * @see Tokenizer::setBookmark
	 */
	public function restoreBookmark()
	{
		$this->index = $this->bookmark;
	}

	/**
	 * Moves current position one step back.
	 *
	 * @return void
	 */
	public function putBack()
	{
		$this->index--;
	}

	/**
	 * Checks if end of tokens reached.
	 *
	 * @return boolean
	 */
	public function endOfInput()
	{
		return !isset($this->tokens[$this->index]);
	}

	/**
	 * Returns current token.
	 * <p>
	 * Leaves position intact.
	 *
	 * @return Token
	 */
	public function getCurrentToken()
	{
		/** @var Token $token */
		$token = $this->tokens[$this->index];
		return $token;
	}

	/**
	 * Returns next token.
	 * <p>
	 * Advances position one step forward.
	 *
	 * @return Token
	 */
	public function nextToken()
	{
		$this->index++;
		/** @var Token $token */
		$token = $this->tokens[$this->index];
		return $token;
	}

	/**
	 * Skips all whitespace and commentaries.
	 *
	 * @return void
	 */
	public function skipWhiteSpace()
	{
		while (isset($this->tokens[$this->index]))
		{
			/** @var Token $token */
			$token = $this->tokens[$this->index];
			if ($token->type == Token::T_WHITESPACE || $token->type == Token::T_COMMENT)
				$this->index++;
			else
				break;
		}
	}

	/**
	 * Checks if current token text is equal to $text.
	 *<p>
	 *In case of success advances position one step forward.
	 *
	 * @param string $text Text to compare.
	 *
	 * @return boolean
	 */
	public function testUpperText($text)
	{
		if (isset($this->tokens[$this->index]))
		{
			/** @var Token $token */
			$token = $this->tokens[$this->index];
			if ($token->upper === $text)
			{
				$this->index++;
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if current token text is equal to $text.
	 *<p>
	 *In case of success advances position one step forward.
	 *
	 * @param string $text Text to compare.
	 *
	 * @return boolean
	 */
	public function testText($text)
	{
		if (isset($this->tokens[$this->index]))
		{
			/** @var Token $token */
			$token = $this->tokens[$this->index];
			if ($token->upper === $text)
			{
				$this->index++;
				return true;
			}
		}
		return false;
	}

	/**
	 * Internal method to split text into tokens and store them.
	 *
	 * @param string $sql Sql text.
	 *
	 * @return void
	 */
	private function _tokenize($sql)
	{
		$this->tokens = array();
		$tokenCount = 0;
		$chars = '(),.:=;/';
		$rawTokens = preg_split("/(
			[ \\t\\n\\r]+                   # WHITESPACE
			|\\\\+                          # BACKSLASHES
			|\"                             # DOUBLE QUOTE
			|'                              # SINGLE QUOTE
			|`[^`]`                         # BACK QUOTE
			|\\[[^\\]]+\\]                  # SQUARE QUOTE
			|\\/\\*.*?\\*\\/                # COMMENTARY
			|--.*?\\n                       # COMMENTARY
			|[".preg_quote($chars, "/")."]  # CHARACTER
		)/xs", $sql, -1, PREG_SPLIT_DELIM_CAPTURE);
		$isInSingleQuote = false;
		$isInDoubleQuote = false;
		foreach ($rawTokens as $i => $rawToken)
		{
			if ($rawToken === "")
				continue;

			/** @var Token $prevToken */
			$prevToken = $this->tokens[$tokenCount-1];

			if ($isInSingleQuote)
			{
				$prevToken->appendText($rawToken);
				if (
					$rawToken === "'"
					&& preg_match("/(\\\\)*'\$/", $prevToken->text, $match)
					&& (strlen($match[0]) % 2) === 1
				)
				{
					$isInSingleQuote = false;
				}
			}
			elseif ($isInDoubleQuote)
			{
				$prevToken->appendText($rawToken);
				if (
					$rawToken === "\""
					&& preg_match("/(\\\\)*\"\$/", $prevToken->text, $match)
					&& (strlen($match[0]) % 2) === 1
				)
				{
					$isInDoubleQuote = false;
				}
			}
			elseif ($rawToken[0] === "`")
			{
				$this->tokens[$tokenCount++] = new Token(Token::T_BACK_QUOTE, $rawToken);
			}
			elseif ($rawToken[0] === "[")
			{
				$this->tokens[$tokenCount++] = new Token(Token::T_SQUARE_QUOTE, $rawToken);
			}
			elseif (
				($rawToken[0] === "/" && $rawToken[1] === '*')
				|| ($rawToken[0] === "-" && $rawToken[1] === '-')
			)
			{
				$this->tokens[$tokenCount++] = new Token(Token::T_COMMENT, $rawToken);
			}
			elseif (strlen($rawToken) == 1 && strpos($chars, $rawToken) !== false)
			{
				$this->tokens[$tokenCount++] = new Token(Token::T_CHAR, $rawToken);
			}
			elseif ($rawToken === "\"")
			{
				$this->tokens[$tokenCount++] = new Token(Token::T_DOUBLE_QUOTE, $rawToken);
				$isInDoubleQuote = true;
			}
			elseif ($rawToken === "'")
			{
				$this->tokens[$tokenCount++] = new Token(Token::T_SINGLE_QUOTE, $rawToken);
				$isInSingleQuote = true;
			}
			elseif (preg_match("/^[ \\t\\n\\r]+\$/", $rawToken))
			{
				$this->tokens[$tokenCount++] = new Token(Token::T_WHITESPACE, $rawToken);
			}
			else
			{
				if ($tokenCount > 0 && $prevToken->type === Token::T_STRING)
				{
					$prevToken->appendText($rawToken);
				}
				else
				{
					$this->tokens[$tokenCount++] = new Token(Token::T_STRING, $rawToken);
				}
			}
		}
	}

	/**
	 * Internal method to assign each token corresponded source code line number.
	 *
	 * @return void
	 */
	private function makeLines()
	{
		$line = 1;
		/** @var Token $token */
		foreach ($this->tokens as $token)
		{
			$token->line = $line;
			if (preg_match_all("/\\n/", $token->text, $m))
			{
				$line += count($m[0]);
			}
		}
	}

	/**
	 * Internal method to mark braces level on the tokens.
	 *
	 * @return void
	 */
	private function makeParenthesis()
	{
		$level = 0;
		/** @var Token $token */
		foreach ($this->tokens as $token)
		{
			if ($token->text === ')')
				$level--;
			$token->level = $level;
			if ($token->text === '(')
				$level++;
		}
	}
}