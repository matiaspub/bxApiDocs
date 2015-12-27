<?php
namespace Bitrix\Perfmon\Sql;

class Constraint extends BaseObject
{
	/**
	 * Creates constraint object from tokens.
	 * <p>
	 * If parameter $constraintName is not passed then current position should point to the name of the constraint.
	 *
	 * @param Tokenizer $tokenizer Tokens collection.
	 * @param string $constraintName Optional name of the constraint.
	 *
	 * @return Constraint
	 */
	public static function create(Tokenizer $tokenizer, $constraintName = '')
	{
		if ($constraintName === false)
		{
			$constraintName = '';
		}
		elseif (!$constraintName)
		{
			$constraintName = $tokenizer->getCurrentToken()->text;
			$tokenizer->nextToken();
			$tokenizer->skipWhiteSpace();
		}

		$constraint = new self($constraintName);

		$token = $tokenizer->getCurrentToken();
		$level = $token->level;
		$constraintDefinition = '';
		do
		{
			if ($token->level == $level && $token->text == ',')
				break;
			if ($token->level < $level && $token->text == ')')
				break;

			$constraintDefinition .= $token->text;

			$token = $tokenizer->nextToken();
		}
		while (!$tokenizer->endOfInput());

		$constraint->setBody($constraintDefinition);

		return $constraint;
	}

	/**
	 * Return DDL for constraint creation.
	 *
	 * @param string $dbType Database type (MYSQL, ORACLE or MSSQL).
	 *
	 * @return array|string
	 */
	public function getCreateDdl($dbType = '')
	{
		switch ($dbType)
		{
		case "MYSQL":
			return "// ".get_class($this).":getCreateDdl for database type [".$dbType."] not implemented";
		case "MSSQL":
			return "ALTER TABLE ".$this->parent->name." ADD CONSTRAINT ".$this->name." ".$this->body;
		case "ORACLE":
			return "ALTER TABLE ".$this->parent->name." ADD CONSTRAINT ".$this->name." ".$this->body;
		default:
			return "// ".get_class($this).":getCreateDdl for database type [".$dbType."] not implemented";
		}
	}

	/**
	 * Return DDL for constraint destruction.
	 *
	 * @param string $dbType Database type (MYSQL, ORACLE or MSSQL).
	 *
	 * @return array|string
	 */
	public function getDropDdl($dbType = '')
	{
		switch ($dbType)
		{
		case "MYSQL":
			return "// ".get_class($this).":getDropDdl for database type [".$dbType."] not implemented";
		case "MSSQL":
			return "ALTER TABLE ".$this->parent->name." DROP CONSTRAINT ".$this->name;
		case "ORACLE":
			return "ALTER TABLE ".$this->parent->name." DROP CONSTRAINT ".$this->name;
		default:
			return "// ".get_class($this).":getDropDdl for database type [".$dbType."] not implemented";
		}
	}

	/**
	 * Return DDL for constraint modification.
	 *
	 * @param BaseObject $target Target object.
	 * @param string $dbType Database type (MYSQL, ORACLE or MSSQL).
	 *
	 * @return array|string
	 */
	public function getModifyDdl(BaseObject $target, $dbType = '')
	{
		return array(
			$this->getDropDdl($dbType),
			$target->getCreateDdl($dbType),
		);
	}
}