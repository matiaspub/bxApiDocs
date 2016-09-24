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
	
	/**
	* <p>Нестатический метод устанавливает новый текст для токена.</p> <br>
	*
	*
	* @param string $text  Новый текст.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/token/settext.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод добавляет новый текст к токену.</p> <br>
	*
	*
	* @param string $text  Добавляемая часть.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/token/appendtext.php
	* @author Bitrix
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
	
	/**
	* <p>Статический метод разбивает текст на токены, создавая новый объект потока токенов. Возвращает созданный объект.</p>
	*
	*
	* @param string $sql  Sql-текст.
	*
	* @return \Bitrix\Perfmon\Sql\Tokenizer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/tokenizer/createfromstring.php
	* @author Bitrix
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
	
	/**
	* <p>Статический метод создает новые объекты потока токенов и устанавливает их токены в данное.</p>
	*
	*
	* @param mixed $Bitrix  Новые токены.
	*
	* @param Bitri $Perfmon  
	*
	* @param Perfmo $Sql  
	*
	* @param Sq $array  
	*
	* @param arra $Token  
	*
	* @return \Bitrix\Perfmon\Sql\Tokenizer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/tokenizer/createfromtokens.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает все токены.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Perfmon\Sql\array[Token] 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/tokenizer/gettokens.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод сбрасывает внутреннее состояние.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/tokenizer/resetstate.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод фиксирует и запоминает текущую позицию.</p> <p>Без параметров</p>
	*
	*
	* @return void 
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/tokenizer/restorebookmark.php">restoreBookmark</a>
	* (<code>\Bitrix\Perfmon\Sql\Tokenizer::restoreBookmark</code>)</li> </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/tokenizer/setbookmark.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод восстанавливает ранее запомненную позицию.</p> <p>Без параметров</p>
	*
	*
	* @return void 
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/tokenizer/setbookmark.php">setBookmark</a>
	* (<code>\Bitrix\Perfmon\Sql\Tokenizer::setBookmark</code>)</li> </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/tokenizer/restorebookmark.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод передвигает текущую позицию на шаг назад.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/tokenizer/putback.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод проверяет, достигнут ли конец списка токенов.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/tokenizer/endofinput.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает текущий токен, не меняя текущую позицию.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Perfmon\Sql\Token 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/tokenizer/getcurrenttoken.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает следующий токен в списке, продвигая текущую позицию на шаг вперед.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Perfmon\Sql\Token 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/tokenizer/nexttoken.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод пропускает все пробелы и комментарии.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/tokenizer/skipwhitespace.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод проверяет соответствие текста в параметре <code>$text</code> с текстом, содержащемуся в токене. В случае соответствия текущая позиция продвигается на шаг вперед.</p> <br>
	*
	*
	* @param string $text  Сравниваемый текст.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/tokenizer/testuppertext.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод проверяет соответствие текста в параметре <code>$text</code> с текстом, содержащемуся в токене. В случае соответствия текущая позиция продвигается на шаг вперед.</p> <br>
	*
	*
	* @param string $text  Сравниваемый текст.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/tokenizer/testtext.php
	* @author Bitrix
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