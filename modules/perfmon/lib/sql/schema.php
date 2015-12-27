<?php
namespace Bitrix\Perfmon\Sql;

use Bitrix\Main\NotSupportedException;

/* Sample usage:
CModule::IncludeModule('perfmon');
$dir = new \Bitrix\Main\IO\Directory("/opt/php03.cp1251.www/mercurial/bitrix/modules");
foreach ($dir->getChildren()  as $child)
{
	if ($child->isDirectory() && $child->getName()!=='xxx')
	{
		echo $child->getName(),": ";

		foreach (array("mysql"=>";", "mssql"=>"GO", "oracle"=>"/") as $db=>$delimiter)
		{
			$path = $child->getPath()."/install/db/$db/install.sql";
			if (!file_exists($path))
				$path = $child->getPath()."/install/$db/install.sql";
			if (!file_exists($path))
				continue;
			//echo "<br>$path<br>";
			$sql = file_get_contents($path);
			$s = new \Bitrix\Perfmon\Sql\Schema;
			$s->createFromString($sql, $delimiter);
			//print_r($s->tables);
			echo count($s->tables->getList())," ";
		}
		echo "\n";
	}
}
*/
class Schema
{
	/** @var Collection */
	public $tables = null;
	/** @var Collection */
	public $procedures = null;
	/** @var Collection */
	public $sequences = null;

	public function __construct()
	{
		$this->tables = new Collection;
		$this->procedures = new Collection;
		$this->sequences = new Collection;
	}

	/**
	 * Fills database schema from DDL text.
	 *
	 * @param string $str DDL text.
	 * @param string $delimiter How to split DDL into statements.
	 *
	 * @return void
	 * @throws NotSupportedException
	 */
	public function createFromString($str, $delimiter)
	{
		$tokenizer = Tokenizer::createFromString($str);
		foreach ($this->splitStatements($tokenizer, $delimiter) as $statement)
		{
			$this->executeStatement($statement);
		}
	}

	/**
	 * Splits tokens array into bunch of individual DDL statements.
	 *
	 * @param Tokenizer $tokenizer Tokens container.
	 * @param string $delimiter How to split DDL into statements.
	 *
	 * @return array[Tokenizer]
	 */
	protected function splitStatements(Tokenizer $tokenizer, $delimiter = ';')
	{
		$result = array();
		$index = 0;
		$result[$index] = array();

		/** @var Token $prevToken */
		$prevToken = null;
		/** @var Token $token */
		foreach ($tokenizer->getTokens() as $token)
		{
			if (
				$token->text === $delimiter
				&& $prevToken
				&& substr($prevToken->text, -1) === "\n"
			)
			{
				$index++;
				$result[$index] = array();
			}
			elseif (
				substr($token->text , -1) === "\n"
				&& $prevToken
				&& $prevToken->text === $delimiter
			)
			{
				array_pop($result[$index]);
				$index++;
				$result[$index] = array();
			}
			else
			{
				$result[$index][] = $token;
			}
			$prevToken = $token;
		}

		foreach ($result as $i => $tokens)
		{
			$result[$i] = Tokenizer::createFromTokens($tokens);
		}

		return $result;
	}

	/**
	 * Fills some schema part with information from one DDL statement.
	 *
	 * @param Tokenizer $tokenizer Statement tokens.
	 *
	 * @return void
	 * @throws NotSupportedException
	 */
	protected function executeStatement(Tokenizer $tokenizer)
	{
		/** @var Table $table */
		$tokenizer->resetState();
		$tokenizer->skipWhiteSpace();
		if ($tokenizer->testUpperText('CREATE'))
		{
			$this->executeCreate($tokenizer);
		}
		elseif ($tokenizer->testUpperText('INSERT'))
		{
			//skip insert into
		}
		elseif ($tokenizer->testUpperText('SET'))
		{
			//skip set identity_insert
		}
		elseif ($tokenizer->testUpperText('ALTER'))
		{
			$this->executeAlter($tokenizer);
		}
		elseif ($tokenizer->testUpperText('IF'))
		{
			$tokenizer->skipWhiteSpace();
			if ($tokenizer->testUpperText('OBJECT_ID'))
			{
				while (!$tokenizer->endOfInput())
				{
					if ($tokenizer->nextToken()->upper === 'CREATE')
						break;
				}
				$tokenizer->nextToken();
				$tokenizer->skipWhiteSpace();
				if ($tokenizer->testUpperText('TABLE'))
				{
					$this->executeCreateTable($tokenizer);
				}
				else
				{
					throw new NotSupportedException("'CREATE TABLE' expected. line:".$tokenizer->getCurrentToken()->line);
				}
			}
			elseif ($tokenizer->testUpperText('NOT'))
			{
				$tokenizer->skipWhiteSpace();
				if ($tokenizer->testUpperText('EXISTS'))
				{
					while (!$tokenizer->endOfInput())
					{
						if ($tokenizer->nextToken()->upper === 'CREATE')
							break;
					}
					$tokenizer->nextToken();
					$tokenizer->skipWhiteSpace();

					if ($tokenizer->testUpperText('UNIQUE'))
					{
						$unique = true;
						$tokenizer->skipWhiteSpace();
					}
					else
					{
						$unique = false;
					}

					if ($tokenizer->testUpperText('INDEX'))
					{
						$this->executeCreateIndex($tokenizer, $unique);
					}
					else
					{
						throw new NotSupportedException("'CREATE INDEX' expected. line:".$tokenizer->getCurrentToken()->line);
					}
				}
				else
				{
					throw new NotSupportedException("'NOT EXISTS' expected. line:".$tokenizer->getCurrentToken()->line);
				}
			}
			else
			{
				throw new NotSupportedException("'OBJECT_ID' expected. line:".$tokenizer->getCurrentToken()->line);
			}
		}
		elseif (!$tokenizer->endOfInput())
		{
			throw new NotSupportedException("'CREATE' expected. line:".$tokenizer->getCurrentToken()->line);
		}
	}

	/**
	 * @param Tokenizer $tokenizer Statement tokens.
	 *
	 * @return void
	 * @throws NotSupportedException
	 */
	protected function executeCreate(Tokenizer $tokenizer)
	{
		$tokenizer->skipWhiteSpace();
		if ($tokenizer->testUpperText("OR"))
		{
			$tokenizer->skipWhiteSpace();
			if ($tokenizer->testUpperText("REPLACE"))
				$tokenizer->skipWhiteSpace();
			else
				throw new NotSupportedException("'OR REPLACE' expected. line:".$tokenizer->getCurrentToken()->line);
		}

		if ($tokenizer->testUpperText('TABLE'))
		{
			$this->executeCreateTable($tokenizer);
		}
		elseif ($tokenizer->testUpperText('INDEX'))
		{
			$this->executeCreateIndex($tokenizer, false);
		}
		elseif ($tokenizer->testUpperText('UNIQUE'))
		{
			$tokenizer->skipWhiteSpace();
			if ($tokenizer->testUpperText('INDEX'))
				$tokenizer->skipWhiteSpace();

			$this->executeCreateIndex($tokenizer, true);
		}
		elseif ($tokenizer->testUpperText('TRIGGER'))
		{
			$this->executeCreateTrigger($tokenizer);
		}
		elseif (
			$tokenizer->testUpperText('PROCEDURE')
			|| $tokenizer->testUpperText('FUNCTION')
			|| $tokenizer->testUpperText('TYPE')
		)
		{
			$this->executeCreateProcedure($tokenizer);
		}
		elseif ($tokenizer->testUpperText('SEQUENCE'))
		{
			$this->executeCreateSequence($tokenizer);
		}
		else
		{
			throw new NotSupportedException("TABLE|INDEX|UNIQUE|TRIGGER|PROCEDURE|FUNCTION|TYPE|SEQUENCE expected. line:".$tokenizer->getCurrentToken()->line);
		}
	}

	/**
	 * @param Tokenizer $tokenizer Statement tokens.
	 *
	 * @return void
	 * @throws NotSupportedException
	 */
	protected function executeAlter(Tokenizer $tokenizer)
	{
		$tokenizer->skipWhiteSpace();
		if ($tokenizer->testUpperText('TABLE'))
		{
			$tokenizer->skipWhiteSpace();
			$tableName = $tokenizer->getCurrentToken()->text;
			/** @var Table $table */
			$table = $this->tables->search($tableName);
			if (!$table)
			{
				throw new NotSupportedException("Table [$tableName] not found. line: ".$tokenizer->getCurrentToken()->line);
			}
			$tokenizer->nextToken();
			$tokenizer->skipWhiteSpace();
			if ($tokenizer->testUpperText('ADD'))
			{
				$tokenizer->skipWhiteSpace();
				if ($tokenizer->testUpperText('CONSTRAINT'))
				{
					$tokenizer->skipWhiteSpace();
					$table->createConstraint($tokenizer);
				}
			}
			elseif ($tokenizer->testUpperText('NOCHECK') || $tokenizer->testUpperText('CHECK'))
			{
				//(NOCHECK|CHECK) CONSTRAINT ALL
			}
			elseif ($tokenizer->testUpperText('DISABLE') || $tokenizer->testUpperText('ENABLE'))
			{
				//(DISABLE|ENABLE) TRIGGER ALL
			}
			else
			{
				throw new NotSupportedException("'ADD' expected. line:".$tokenizer->getCurrentToken()->line);
			}
		}
		else
		{
			throw new NotSupportedException("'TABLE' expected. line:".$tokenizer->getCurrentToken()->line);
		}
	}

	/**
	 * @param Tokenizer $tokenizer Statement tokens.
	 *
	 * @return void
	 * @throws NotSupportedException
	 */
	protected function executeCreateTable(Tokenizer $tokenizer)
	{
		$tokenizer->skipWhiteSpace();
		$this->tables->add(Table::create($tokenizer));
	}

	/**
	 * @param Tokenizer $tokenizer Statement tokens.
	 * @param boolean $unique Index uniqueness flag.
	 *
	 * @return void
	 * @throws NotSupportedException
	 */
	protected function executeCreateIndex(Tokenizer $tokenizer, $unique)
	{
		$tokenizer->skipWhiteSpace();

		$tokenizer->setBookmark();

		Index::searchTableName($tokenizer);
		$tableName = $tokenizer->getCurrentToken()->text;

		/** @var Table $table */
		$table = $this->tables->search($tableName);
		if (!$table)
		{
			throw new NotSupportedException("Table [$tableName] not found. line: ".$tokenizer->getCurrentToken()->line);
		}

		$tokenizer->restoreBookmark();

		$table->createIndex($tokenizer, $unique);
	}

	/**
	 * @param Tokenizer $tokenizer Statement tokens.
	 *
	 * @return void
	 * @throws NotSupportedException
	 */
	protected function executeCreateTrigger(Tokenizer $tokenizer)
	{
		$tokenizer->skipWhiteSpace();

		$tokenizer->setBookmark();

		Trigger::searchTableName($tokenizer);
		$tableName = $tokenizer->getCurrentToken()->text;

		/** @var Table $table */
		$table = $this->tables->search($tableName);
		if (!$table)
		{
			throw new NotSupportedException("Table [$tableName] not found. line: ".$tokenizer->getCurrentToken()->line);
		}

		$tokenizer->restoreBookmark();

		$table->createTrigger($tokenizer);
	}

	/**
	 * @param Tokenizer $tokenizer Statement tokens.
	 *
	 * @return void
	 * @throws NotSupportedException
	 */
	protected function executeCreateProcedure(Tokenizer $tokenizer)
	{
		$tokenizer->putBack();
		$this->procedures->add(Procedure::create($tokenizer));
	}

	/**
	 * @param Tokenizer $tokenizer Statement tokens.
	 *
	 * @return void
	 * @throws NotSupportedException
	 */
	protected function executeCreateSequence(Tokenizer $tokenizer)
	{
		$tokenizer->skipWhiteSpace();
		$this->sequences->add(Sequence::create($tokenizer));
	}
}