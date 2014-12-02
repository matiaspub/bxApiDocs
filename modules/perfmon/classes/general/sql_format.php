<?php
if (!defined("T_KEYWORD"))
	// define("T_KEYWORD", 400);

class CSqlFormatFormatter
{
	public function format($tokens)
	{
		$result = "";
		$skipWS = false;
		foreach ($tokens as $i => $token)
		{
			if ($token[1] === ",")
			{
				$this->removeTrailingSpaces($result);
				$result .= ",\x3".str_repeat("\x2", $token[2]);
				$skipWS = true;
			}
			elseif (
				$token[1] === "="
				|| $token[1] === "-"
				|| $token[1] === "+"
				|| $token[1] === "*"
				|| $token[1] === "/"
				|| $token[1] === "<="
				|| $token[1] === ">="
			)
			{
				$this->removeTrailingSpaces($result);
				$result .= "\x1".$token[1]."\x1";
				$skipWS = true;
			}
			elseif (
				$token[1] === "INNER"
				|| $token[1] === "LEFT"
				|| $token[1] === "SET"
				|| $token[1] === "AND"
				|| $token[1] === "OR"
			)
			{
				$result .= "\x3".str_repeat("\x2", $token[2]).$token[1];
			}
			else
			{
				if ($skipWS)
				{
					$skipWS = false;
					if ($token[0] === T_WHITESPACE)
						continue;
				}
				elseif ($tokens[$i - 1][2] <> $token[2])
				{
					$result .= "\x3".str_repeat("\x2", $token[2]);
					if ($token[0] === T_WHITESPACE)
						continue;
				}

				if ($token[0] === T_WHITESPACE)
					$result .= "\x1";
				else
					$result .= $token[1];
			}
		}

		$result = preg_replace_callback("/(
			\\([\\x1\\x2\\x3_0-9A-Za-z.0-9_,]+\\)
			|\\([\\x1\\x2\\x3'x%0-9,]+\\)
			|\\([\\x1\\x2\\x3]*[a-zA-Z0-9_.]+[\\x1\\x2\\x3]*=[\\x1\\x2\\x3]*[a-zA-Z0-9_.']+[\\x1\\x2\\x3]*\\)
		)/x", array($this, "removeSpaces"), $result);

		$result = str_replace(array(
			"\x1",
			"\x2",
			"\x3",
		), array(
			$this->getSpace(),
			$this->getTab(),
			$this->getEol(),
		), $result);

		return $result;
	}

	static public function removeSpaces($match)
	{
		$result = preg_replace("/^\\([\\x1\\x2\\x3]+/", "(", $match[0]);
		$result = preg_replace("/[\\x1\\x2\\x3]+\\)\$/", ")", $result);
		$result = preg_replace("/,[\\x1\\x2\\x3]+/", ", ", $result);
		return $result;
	}

	static public function removeTrailingSpaces(&$str)
	{
		$str = rtrim($str, "\x1\x2\x3");
	}

	static public function getEol()
	{
		return " ";
	}

	static public function getSpace()
	{
		return " ";
	}

	static public function getTab()
	{
		return " ";
	}
}

class CSqlFormatText extends CSqlFormatFormatter
{
	static public function getEol()
	{
		return "\n";
	}

	static public function getSpace()
	{
		return " ";
	}

	static public function getTab()
	{
		return "\t";
	}
}

class CSqlTokenizer
{
	private $tokens = null;
	private $current = 0;

	public function parse($sql)
	{
		$this->tokens = token_get_all("<?".$sql);
		array_shift($this->tokens);
		$this->current = 0;

		while (isset($this->tokens[$this->current]))
		{
			//Remove excessive brackets
			if (
				$this->tokens[$this->current] === "("
				&& $this->lookForwardFor("(")
			)
			{
				if ($this->removeBalancedBrackets())
					continue;
			}

			//Remove following spaces
			if ($this->tokens[$this->current][0] === T_WHITESPACE && $this->tokens[$this->current - 1][0] === T_WHITESPACE)
			{
				array_splice($this->tokens, $this->current, 1);
				continue;
			}

			$this->tokens[$this->current] = $this->transform($this->tokens[$this->current]);
			$this->current++;
		}

		//Remove leading spaces
		while (
			isset($this->tokens[0])
			&& $this->tokens[0][0] === T_WHITESPACE
		)
		{
			array_splice($this->tokens, 0, 1);
		}

		//Remove trailing spaces
		while (
			!empty($this->tokens)
			&& $this->tokens[count($this->tokens) - 1][0] === T_WHITESPACE
		)
		{
			array_splice($this->tokens, -1, 1);
		}

		return $this->tokens;
	}

	protected function transform($token)
	{
		static $keywords = "UPDATE|SET|DELETE|SELECT|DISTINCT|INNER|LEFT|OUTER|JOIN|ON|FROM|WHERE|GROUP|BY|IN|EXISTS|HAVING|ORDER|ASC|DESC|LIMIT|AND|OR";
		static $functions = "DATE_FORMAT|UNIX_TIMESTAMP|CONCAT|DATE_ADD|UPPER|LENGTH|IFNULL";

		if (isset($token[1]))
			$token = array($token[0], $token[1]);
		else
			$token = array(T_CHARACTER, $token[0]);

		switch ($token[0])
		{
		case T_STRING:
			if (preg_match("/^($keywords)\$/i", $token[1]))
				$token = array(T_KEYWORD, strtoupper($token[1]));
			elseif (preg_match("/^($functions)\$/i", $token[1]))
				$token = array(T_FUNCTION, $token[1]);
			break;
		case T_LOGICAL_AND:
		case T_LOGICAL_OR:
			$token = array(T_KEYWORD, strtoupper($token[1]));
			break;
		case T_AS:
			$token = array(T_KEYWORD, $token[1]);
			break;
		case T_COMMENT:
		case T_BAD_CHARACTER:
			$token = array(T_WHITESPACE, " ");
			break;
		}

		return $token;
	}

	protected function removeBalancedBrackets()
	{
		$pos = $this->current;
		$balance = 0;
		$hasOp = array(false);
		while (isset($this->tokens[$pos]))
		{
			if ($this->tokens[$pos][0] === "(")
			{
				$balance++;
				$hasOp[$balance] = false;
			}
			elseif ($this->tokens[$pos][0] === ")")
			{
				$balance--;
			}
			elseif (
				$this->tokens[$pos][0] === T_LOGICAL_AND
				|| $this->tokens[$pos][0] === T_LOGICAL_OR
				|| $this->tokens[$pos][0] === ","
			)
			{
				$hasOp[$balance] = true;
			}

			if ($balance === 0)
			{
				if (!$hasOp[$balance + 1])
				{
					array_splice($this->tokens, $pos, 1);
					array_splice($this->tokens, $this->current, 1);
					return true;
				}
				else
				{
					return false;
				}
			}
			$pos++;
		}
		return false;
	}

	protected function lookForwardFor($token)
	{
		$pos = $this->current + 1;
		while (isset($this->tokens[$pos]))
		{
			if ($this->tokens[$pos] == $token)
				return true;
			elseif ($this->tokens[$pos][0] !== T_WHITESPACE)
				return false;
			$pos++;
		}
		return false;
	}
}

class CSqlLevel
{
	private $tokens = array();
	private $balance = 0;
	private $level = 0;
	private $current = 0;

	public function addLevel(array $tokens)
	{
		$this->level = array();
		$this->balance = 0;
		$this->tokens = $tokens;
		$this->current = 0;
		while (isset($this->tokens[$this->current]))
		{
			if ($this->tokens[$this->current][1] === "(")
				$this->balance++;
			elseif ($this->tokens[$this->current][1] === ")")
				$this->balance--;

			if ($this->tokens[$this->current][0] !== T_WHITESPACE)
				$this->changeLevelBefore();

			$this->tokens[$this->current][] = array_sum($this->level);

			if ($this->tokens[$this->current][0] !== T_WHITESPACE)
				$this->changeLevelAfter();

			$this->current++;
		}

		return $this->tokens;
	}

	public function changeLevelBefore()
	{
		if ($this->tokens[$this->current][1] === ")")
		{
			$this->level["("]--;
			$this->level["SELECT_".($this->balance + 1)] = 0;
			$this->level["JOIN_".($this->balance + 1)] = 0;
		}
		elseif (
			$this->tokens[$this->current][1] === "FROM"
			|| $this->tokens[$this->current][1] === "LIMIT"
		)
		{
			$this->level["SELECT_".$this->balance]--;
		}
		elseif (
			$this->tokens[$this->current][1] === "WHERE"
			|| $this->tokens[$this->current][1] === "GROUP"
			|| $this->tokens[$this->current][1] === "HAVING"
			|| $this->tokens[$this->current][1] === "ORDER"
		)
		{
			$this->level["SELECT_".$this->balance]--;
			if ($this->level["JOIN_".$this->balance] > 0)
				$this->level["JOIN_".$this->balance]--;
		}
		elseif (
			$this->tokens[$this->current][1] === "INNER"
			|| $this->tokens[$this->current][1] === "LEFT"
		)
		{
			if ($this->level["JOIN_".$this->balance] > 0)
				$this->level["JOIN_".$this->balance]--;
		}
	}

	public function changeLevelAfter()
	{
		if ($this->tokens[$this->current][1] === "(")
		{
			$this->level["("]++;
		}
		elseif (
			(
				$this->tokens[$this->current][1] === "SELECT"
				&& !$this->lookForwardFor("DISTINCT")
			) || (
				$this->tokens[$this->current][1] === "DISTINCT"
			)
		)
		{
			$this->level["SELECT_".$this->balance]++;
		}
		elseif (
			$this->tokens[$this->current][1] === "FROM"
			|| $this->tokens[$this->current][1] === "WHERE"
			|| $this->tokens[$this->current][1] === "BY"
			|| $this->tokens[$this->current][1] === "HAVING"
			|| $this->tokens[$this->current][1] === "SET"
		)
		{
			$this->level["SELECT_".$this->balance]++;
		}
		elseif ($this->tokens[$this->current][1] === "ON")
		{
			$this->level["JOIN_".$this->balance]++;
		}
	}

	protected function lookForwardFor($token)
	{
		$pos = $this->current + 1;
		while (isset($this->tokens[$pos]))
		{
			if ($this->tokens[$pos][1] == $token)
				return true;
			elseif ($this->tokens[$pos][0] !== T_WHITESPACE)
				return false;
			$pos++;
		}
		return false;
	}

	protected function lookBackwardFor($token)
	{
		$pos = $this->current - 1;
		while (isset($this->tokens[$pos]))
		{
			if ($this->tokens[$pos][1] == $token)
				return true;
			elseif ($this->tokens[$pos][0] !== T_WHITESPACE)
				return false;
			$pos--;
		}
		return false;
	}
}

class CSqlFormat
{
	/** @var CSqlTokenizer */
	private $tokenizer = null;
	/** @var CSqlLevel */
	private $levelizer = null;

	private $current = null;
	private $level = 0;
	private $add = 0;
	private $result = "";
	/** @var CSqlFormatFormatter */
	private $formatter = null;

	public function __construct()
	{
		$this->tokenizer = new CSqlTokenizer;
		$this->levelizer = new CSqlLevel;

		$this->level = 0;
		$this->add = 0;
		$this->current = 0;
		$this->result = "";
	}

	public static function reformatSql($sql, CSqlFormatFormatter $formatter = null)
	{
		if (function_exists('token_get_all'))
		{
			$format = new CSqlFormat;
			$format->setFormatter($formatter? $formatter: new CSqlFormatText);
			return $format->format($sql);
		}
		else
		{
			return $sql;
		}
	}

	public function setFormatter(CSqlFormatFormatter $formatter)
	{
		$this->formatter = $formatter;
	}

	public function format($sql)
	{
		$tokens = $this->tokenizer->parse($sql);
		$tokens = $this->levelizer->addLevel($tokens);
		return $this->formatter->format($tokens);
	}
}
