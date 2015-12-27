<?php
namespace Bitrix\Perfmon\Sql;

use Bitrix\Main\NotSupportedException;
use Bitrix\Perfmon\Php;

class Updater
{
	protected $dbType = '';
	protected $delimiter = '';
	/** @var \Bitrix\Perfmon\Sql\Table  */
	protected $tableCheck = null;
	protected $conditions = array();

	/** @var \Bitrix\Perfmon\Php\Statement[]*/
	protected  $statements = array();

	/**
	 * Sets database type. Currently supported:
	 * - MYSQL
	 * - ORACLE
	 * - MSSQL
	 *
	 * @param string $dbType Database type.
	 *
	 * @return Updater
	 */
	public function setDbType($dbType = '')
	{
		$this->dbType = (string)$dbType;
		return $this;
	}

	/**
	 * Sets DDL delimiter for parsing.
	 *
	 * @param string $delimiter DDL statements delimiter.
	 *
	 * @return Updater
	 */
	public function setDelimiter($delimiter = '')
	{
		$this->delimiter = (string)$delimiter;
		return $this;
	}

	/**
	 * Returns array of generated statements.
	 *
	 * @return \Bitrix\Perfmon\Php\Statement[]
	 */
	public function getStatements()
	{
		return $this->statements;
	}

	/**
	 * Produces updater code.
	 *
	 * @param string $sourceSql Source DDL statements.
	 * @param string $targetSql Target DDL statements.
	 *
	 * @return string
	 * @throws NotSupportedException
	 */
	public function generate($sourceSql, $targetSql)
	{
		$source = new Schema;
		$source->createFromString($sourceSql, $this->delimiter);

		$target = new Schema;
		$target->createFromString($targetSql, $this->delimiter);

		$diff = Compare::diff($source ,$target);
		if ($diff)
		{
			$sourceTables = $source->tables->getList();
			if ($sourceTables)
			{
				$this->tableCheck = array_shift($sourceTables);
			}
			else
			{
				$targetTables = $target->tables->getList();
				if ($targetTables)
				{
					$this->tableCheck = array_shift($targetTables);
				}
				else
				{
					$this->tableCheck = null;
				}
			}

			if (!$this->tableCheck)
				throw new NotSupportedException("no CHECK TABLE found.");

			$php = $this->handle($diff);

			return
				"if (\$updater->CanUpdateDatabase() && \$updater->TableExists('".EscapePHPString($this->tableCheck->name)."'))\n".
				"{\n".
				"\tif (\$DB->type == \"".EscapePHPString($this->dbType)."\")\n".
				"\t{\n".
				$php.
				"\t}\n".
				"}\n";
		}
		else
		{
			return "";
		}
	}

	/**
	 * @param array $diff Difference pairs.
	 *
	 * @return string
	 */
	protected function handle(array $diff)
	{
		$this->conditions = array();
		foreach ($diff as $pair)
		{
			if (!isset($pair[0]))
			{
				$this->handleCreate($pair[1]);
			}
			elseif (!isset($pair[1]))
			{
				$this->handleDrop($pair[0]);
			}
			else
			{
				$this->handleChange($pair[0], $pair[1]);
			}
		}

		$result = "";
		foreach ($this->conditions as $condition => $statements)
		{
			$result .= $condition;
			if ($condition)
				$result .= "\t\t{\n";
			$result .= implode("", $statements);
			if ($condition)
				$result .= "\t\t}\n";
		}

		return $result;
	}

	/**
	 * @param BaseObject $object Database schema object.
	 *
	 * @return void
	 */
	protected function handleCreate(BaseObject $object)
	{
		if ($object instanceof Sequence || $object instanceof Procedure)
		{
			$ddl = $object->getCreateDdl($this->dbType);

			$this->conditions[""][] = $this->multiLinePhp("\t\t\$DB->Query(\"", $ddl, "\", true);\n");

			$stmt = $this->createStatement("\$DB->Query(\"", $ddl, "\", true);");
			$stmt->addCondition("\$updater->CanUpdateDatabase()");
			$stmt->addCondition("\$DB->type == \"".EscapePHPString($this->dbType)."\"");
			$stmt->addCondition("\$updater->TableExists(\"".EscapePHPString($this->tableCheck->getLowercasedName())."\")");
		}
		elseif ($object instanceof Table)
		{
			$ddl = $object->getCreateDdl($this->dbType);
			$predicate = "!\$updater->TableExists(\"".EscapePHPString($object->name)."\")";
			$cond = "\t\tif ($predicate)\n";

			$this->conditions[$cond][] = $this->multiLinePhp("\t\t\t\$DB->Query(\"\n\t\t\t\t", str_replace("\n", "\n\t\t\t\t", $ddl), "\n\t\t\t\");\n");

			$stmt = $this->createStatement("\$DB->Query(\"", $ddl, "\", true);");
			$stmt->addCondition("\$updater->CanUpdateDatabase()");
			$stmt->addCondition("\$DB->type == \"".EscapePHPString($this->dbType)."\"");
			$stmt->addCondition("\$updater->TableExists(\"".EscapePHPString($this->tableCheck->getLowercasedName())."\")");
			$stmt->addCondition("!\$updater->TableExists(\"".EscapePHPString($object->getLowercasedName())."\")");
		}
		elseif ($object instanceof Column)
		{
			$ddl = $object->getCreateDdl($this->dbType);
			$predicate = "\$updater->TableExists(\"".EscapePHPString($object->parent->name)."\")";
			$cond = "\t\tif ($predicate)\n";
			$predicate2 = "!\$DB->Query(\"SELECT ".EscapePHPString($object->name)." FROM ".EscapePHPString(strtolower($object->parent->name))." WHERE 1=0\", true)";

			$this->conditions[$cond][] =
				"\t\t\tif ($predicate2)\n".
				"\t\t\t{\n".
				$this->multiLinePhp("\t\t\t\t\$DB->Query(\"", $ddl, "\");\n").
				"\t\t\t}\n";

			$stmt = $this->createStatement("\$DB->Query(\"", $ddl, "\");");
			$stmt->addCondition("\$updater->CanUpdateDatabase()");
			$stmt->addCondition("\$DB->type == \"".EscapePHPString($this->dbType)."\"");
			$stmt->addCondition("\$updater->TableExists(\"".EscapePHPString($object->parent->getLowercasedName())."\")");
			$stmt->addCondition("!\$DB->Query(\"SELECT ".EscapePHPString($object->name)." FROM ".EscapePHPString($object->parent->getLowercasedName())." WHERE 1=0\", true)");
		}
		elseif ($object instanceof Index)
		{
			$ddl = $object->getCreateDdl($this->dbType);
			$predicate = "\$updater->TableExists(\"".EscapePHPString($object->parent->name)."\")";
			$cond = "\t\tif ($predicate)\n";
			$predicate2 = "!\$DB->IndexExists(\"".EscapePHPString($object->parent->name)."\", array(".$this->multiLinePhp("\"", $object->columns, "\", ")."))";

			$this->conditions[$cond][] =
				"\t\t\tif ($predicate2)\n".
				"\t\t\t{\n".
				$this->multiLinePhp("\t\t\t\t\$DB->Query(\"", $ddl, "\");\n").
				"\t\t\t}\n";

			$stmt = $this->createStatement("\$DB->Query(\"", $ddl, "\");");
			$stmt->addCondition("\$updater->CanUpdateDatabase()");
			$stmt->addCondition("\$DB->type == \"".EscapePHPString($this->dbType)."\"");
			$stmt->addCondition("\$updater->TableExists(\"".EscapePHPString($object->parent->getLowercasedName())."\")");
			$stmt->addCondition("!\$DB->IndexExists(\"".EscapePHPString($object->parent->getLowercasedName())."\", array(".$this->multiLinePhp("\"", $object->columns, "\", ")."))");
		}
		elseif ($object instanceof Trigger || $object instanceof Constraint)
		{
			$ddl = $object->getCreateDdl($this->dbType);
			$predicate = "\$updater->TableExists(\"".EscapePHPString($object->parent->name)."\")";
			$cond = "\t\tif ($predicate)\n";

			$this->conditions[$cond][] = $this->multiLinePhp("\t\t\t\$DB->Query(\"", $ddl, "\", true);\n");

			$stmt = $this->createStatement("\$DB->Query(\"", $ddl, "\", true);");
			$stmt->addCondition("\$updater->CanUpdateDatabase()");
			$stmt->addCondition("\$DB->type == \"".EscapePHPString($this->dbType)."\"");
			$stmt->addCondition("\$updater->TableExists(\"".EscapePHPString($object->parent->getLowercasedName())."\")");
		}
		else
		{
			$this->conditions[""][] = "\t\t//create for ".get_class($object)." not supported yet\n";
			$stmt = $this->createStatement("", "//create for ".get_class($object)." not supported yet", "");
		}
		
		if ($stmt)
		{
			$this->statements[] = $stmt;
		}
	}

	/**
	 * @param BaseObject $object Database schema object.
	 *
	 * @return void
	 */
	protected function handleDrop(BaseObject $object)
	{
		if ($object instanceof Sequence || $object instanceof Procedure)
		{
			$ddl = $object->getDropDdl($this->dbType);

			$this->conditions[""][] = "\t\t\$DB->Query(\"".EscapePHPString($ddl)."\", true);\n";

			$stmt = $this->createStatement("\$DB->Query(\"", $ddl, "\", true);");
			$stmt->addCondition("\$updater->CanUpdateDatabase()");
			$stmt->addCondition("\$DB->type == \"".EscapePHPString($this->dbType)."\"");
			$stmt->addCondition("\$updater->TableExists(\"".EscapePHPString($this->tableCheck->getLowercasedName())."\")");
		}
		elseif ($object instanceof Table)
		{
			$ddl = $object->getDropDdl($this->dbType);
			$predicate = "\$updater->TableExists(\"".EscapePHPString($object->name)."\")";
			$cond = "\t\tif ($predicate)\n";

			$this->conditions[$cond][] = $this->multiLinePhp("\t\t\t\$DB->Query(\"", $ddl, "\");\n");

			$stmt = $this->createStatement("\$DB->Query(\"", $ddl, "\");");
			$stmt->addCondition("\$updater->CanUpdateDatabase()");
			$stmt->addCondition("\$DB->type == \"".EscapePHPString($this->dbType)."\"");
			$stmt->addCondition("\$updater->TableExists(\"".EscapePHPString($object->getLowercasedName())."\")");
		}
		elseif ($object instanceof Column)
		{
			$ddl = $object->getDropDdl($this->dbType);
			$predicate = "\$updater->TableExists(\"".EscapePHPString($object->parent->name)."\")";
			$cond = "\t\tif ($predicate)\n";
			$predicate2 = "\$DB->Query(\"SELECT ".EscapePHPString($object->name)." FROM ".EscapePHPString($object->parent->name)." WHERE 1=0\", true)";

			$this->conditions[$cond][] =
				"\t\t\tif ($predicate2)\n".
				"\t\t\t{\n".
				$this->multiLinePhp("\t\t\t\t\$DB->Query(\"", $ddl, "\");\n").
				"\t\t\t}\n";

			$stmt = $this->createStatement("\$DB->Query(\"", $ddl, "\");");
			$stmt->addCondition("\$updater->CanUpdateDatabase()");
			$stmt->addCondition("\$DB->type == \"".EscapePHPString($this->dbType)."\"");
			$stmt->addCondition("\$updater->TableExists(\"".EscapePHPString($object->parent->getLowercasedName())."\")");
			$stmt->addCondition("\$DB->Query(\"SELECT ".EscapePHPString($object->name)." FROM ".EscapePHPString($object->parent->getLowercasedName())." WHERE 1=0\", true)");
		}
		elseif ($object instanceof Index)
		{
			$ddl = $object->getDropDdl($this->dbType);
			$predicate = "\$updater->TableExists(\"".EscapePHPString($object->parent->name)."\")";
			$cond = "\t\tif ($predicate)\n";
			$predicate2 = "\$DB->IndexExists(\"".EscapePHPString($object->parent->name)."\", array(".$this->multiLinePhp("\"", $object->columns, "\", ")."))";

			$this->conditions[$cond][] =
				"\t\t\tif ($predicate2)\n".
				"\t\t\t{\n".
				$this->multiLinePhp("\t\t\t\t\$DB->Query(\"", $ddl, "\");\n").
				"\t\t\t}\n";

			$stmt = $this->createStatement("\$DB->Query(\"", $ddl, "\");");
			$stmt->addCondition("\$updater->CanUpdateDatabase()");
			$stmt->addCondition("\$DB->type == \"".EscapePHPString($this->dbType)."\"");
			$stmt->addCondition("\$updater->TableExists(\"".EscapePHPString($object->parent->getLowercasedName())."\")");
			$stmt->addCondition("\$DB->IndexExists(\"".EscapePHPString($object->parent->getLowercasedName())."\", array(".$this->multiLinePhp("\"", $object->columns, "\", ")."))");
		}
		elseif ($object instanceof Trigger || $object instanceof Constraint)
		{
			$ddl = $object->getDropDdl($this->dbType);
			$predicate = "\$updater->TableExists(\"".EscapePHPString($object->parent->name)."\")";
			$cond = "\t\tif ($predicate)\n";

			$this->conditions[$cond][] = $this->multiLinePhp("\t\t\t\$DB->Query(\"", $ddl, "\", true);\n");

			$stmt = $this->createStatement("\$DB->Query(\"", $ddl, "\", true);");
			$stmt->addCondition("\$updater->CanUpdateDatabase()");
			$stmt->addCondition("\$DB->type == \"".EscapePHPString($this->dbType)."\"");
			$stmt->addCondition("\$updater->TableExists(\"".EscapePHPString($object->parent->getLowercasedName())."\")");
		}
		else
		{
			$this->conditions[""][] = "\t\t//drop for ".get_class($object)." not supported yet\n";
			$stmt = $this->createStatement("", "//drop for ".get_class($object)." not supported yet", "");
		}

		if ($stmt)
		{
			$this->statements[] = $stmt;
		}
	}

	/**
	 * @param BaseObject $source Source object.
	 * @param BaseObject $target Target object.
	 *
	 * @return void
	 */
	protected function handleChange(BaseObject $source, BaseObject $target)
	{
		if ($source instanceof Sequence || $source instanceof Procedure)
		{
			$this->conditions[""][] =
				$this->multiLinePhp("\t\t\$DB->Query(\"", $source->getDropDdl($this->dbType), "\", true);\n").
				$this->multiLinePhp("\t\t\$DB->Query(\"", $target->getCreateDdl($this->dbType), "\", true);\n");

			$dropStmt = $this->createStatement("\$DB->Query(\"", $source->getDropDdl($this->dbType), "\", true);");
			$createStmt = $this->createStatement("\$DB->Query(\"", $target->getCreateDdl($this->dbType), "\", true);");
			$stmt = new Php\Statement;
			$stmt->merge($dropStmt);
			$stmt->merge($createStmt);
			$stmt->addCondition("\$updater->CanUpdateDatabase()");
			$stmt->addCondition("\$DB->type == \"".EscapePHPString($this->dbType)."\"");
			$stmt->addCondition("\$updater->TableExists(\"".EscapePHPString($this->tableCheck->getLowercasedName())."\")");
		}
		elseif ($target instanceof Column)
		{
			$ddl = $source->getModifyDdl($target, $this->dbType);
			$predicate = "\$updater->TableExists(\"".EscapePHPString($source->parent->name)."\")";
			$cond = "\t\tif ($predicate)\n";
			$predicate2 = "\$DB->Query(\"SELECT ".EscapePHPString($source->name)." FROM ".EscapePHPString($source->parent->name)." WHERE 1=0\", true)";

			$this->conditions[$cond][] =
				"\t\t\tif ($predicate2)\n".
				"\t\t\t{\n".
				$this->multiLinePhp("\t\t\t\t\$DB->Query(\"", $ddl, "\");\n").
				"\t\t\t}\n";

			$stmt = $this->createStatement("\$DB->Query(\"", $ddl, "\");");
			$stmt->addCondition("\$updater->CanUpdateDatabase()");
			$stmt->addCondition("\$DB->type == \"".EscapePHPString($this->dbType)."\"");
			$stmt->addCondition("\$updater->TableExists(\"".EscapePHPString($source->parent->getLowercasedName())."\")");
			$stmt->addCondition("\$DB->Query(\"SELECT ".EscapePHPString($source->name)." FROM ".EscapePHPString($source->parent->getLowercasedName())." WHERE 1=0\", true)");
		}
		elseif ($source instanceof Index)
		{
			$this->conditions["\t\tif (\$updater->TableExists(\"".EscapePHPString($source->parent->name)."\"))\n"][] =
				"\t\t\tif (\$DB->IndexExists(\"".EscapePHPString($source->parent->name)."\", array(".$this->multiLinePhp("\"", $source->columns, "\", ").")))\n".
				"\t\t\t{\n".
				$this->multiLinePhp("\t\t\t\t\$DB->Query(\"", $source->getDropDdl($this->dbType), "\");\n").
				$this->multiLinePhp("\t\t\t\t\$DB->Query(\"", $target->getCreateDdl($this->dbType), "\");\n").
				"\t\t\t}\n";

			$dropStmt = $this->createStatement("\$DB->Query(\"", $source->getDropDdl($this->dbType), "\", true);");
			$createStmt = $this->createStatement("\$DB->Query(\"", $target->getCreateDdl($this->dbType), "\", true);");
			$stmt = new Php\Statement;
			$stmt->merge($dropStmt);
			$stmt->merge($createStmt);
			$stmt->addCondition("\$updater->CanUpdateDatabase()");
			$stmt->addCondition("\$DB->type == \"".EscapePHPString($this->dbType)."\"");
			$stmt->addCondition("\$updater->TableExists(\"".EscapePHPString($source->parent->getLowercasedName())."\")");
			$stmt->addCondition("\$DB->IndexExists(\"".EscapePHPString($source->parent->getLowercasedName())."\", array(".$this->multiLinePhp("\"", $source->columns, "\", ")."))");
			$stmt->addCondition("!\$DB->IndexExists(\"".EscapePHPString($target->parent->getLowercasedName())."\", array(".$this->multiLinePhp("\"", $target->columns, "\", ")."))");
		}
		elseif ($source instanceof Trigger || $source instanceof Constraint)
		{
			$ddl = $source->getModifyDdl($target, $this->dbType);
			$predicate = "\$updater->TableExists(\"".EscapePHPString($source->parent->name)."\")";
			$cond = "\t\tif ($predicate)\n";

			$this->conditions[$cond][] = $this->multiLinePhp("\t\t\t\$DB->Query(\"", $ddl, "\", true);\n");
			$stmt = $this->createStatement("\$DB->Query(\"", $ddl, "\", true);");
			$stmt->addCondition("\$updater->CanUpdateDatabase()");
			$stmt->addCondition("\$DB->type == \"".EscapePHPString($this->dbType)."\"");
			$stmt->addCondition("\$updater->TableExists(\"".EscapePHPString($source->parent->getLowercasedName())."\")");
		}
		else
		{
			$this->conditions[""][] = "\t\t//change for ".get_class($source)." not supported yet\n";
			$stmt = $this->createStatement("", "//change for ".get_class($source)." not supported yet", "");
		}

		if ($stmt)
		{
			$this->statements[] = $stmt;
		}
	}

	/**
	 * Returns escaped php code repeated for body? prefixed with $prefix and suffixed with $suffix.
	 *
	 * @param string $prefix Prefix string for each from body.
	 * @param array|string $body Strings to be escaped.
	 * @param string $suffix Suffix string for each from body.
	 *
	 * @return string
	 */
	protected function multiLinePhp($prefix, $body, $suffix)
	{
		$result  = array();
		if (is_array($body))
		{
			foreach ($body as $line)
			{
				$result[] = $prefix.EscapePHPString($line).$suffix;
			}
		}
		else
		{
			$result[] = $prefix.EscapePHPString($body).$suffix;
		}
		return implode("", $result);
	}

	/**
	 * Returns Php\Statement object with escaped php code repeated for body? prefixed with $prefix and suffixed with $suffix.
	 *
	 * @param string $prefix Prefix string for each from body.
	 * @param array|string $body Strings to be escaped.
	 * @param string $suffix Suffix string for each from body.
	 *
	 * @return \Bitrix\Perfmon\Php\Statement
	 */
	protected function createStatement($prefix, $body, $suffix)
	{
		$result  = new Php\Statement;
		if (is_array($body))
		{
			foreach ($body as $line)
			{
				$result->addLine($prefix.EscapePHPString($line).$suffix);
			}
		}
		else
		{
			$result->addLine($prefix.EscapePHPString($body).$suffix);
		}
		return $result;
	}
}
