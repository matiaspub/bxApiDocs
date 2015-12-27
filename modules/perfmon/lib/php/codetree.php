<?php
namespace Bitrix\Perfmon\Php;

class CodeTree
{
	protected $statements = array();
	protected $tree = array();

	/**
	 * @param array $statements Sequence of updater statements.
	 */
	public function __construct(array $statements)
	{
		$this->statements = $statements;
		$this->tree = array();
	}

	/**
	 * Returns php code.
	 *
	 * @param integer $level Alignment level.
	 *
	 * @return string
	 */
	public function getCode($level)
	{
		$tree = $this->getCodeTree();
		return $this->formatCodeTree($tree, $level);
	}

	/**
	 * @param array $result Nested arrays of structured code.
	 * @param integer $level Alignment level.
	 *
	 * @return string
	 */
	protected function formatCodeTree($result, $level=0)
	{
		$code = '';
		foreach ($result as $stmt)
		{
			if (is_array($stmt) && isset($stmt["if"]))
			{
				$code .= str_repeat("\t", $level)."if(".implode(" && ", $stmt["if"]).")\n";
				$code .= str_repeat("\t", $level)."{\n";
				$code .= $this->formatCodeTree($stmt["body"], $level+1);
				$code .= str_repeat("\t", $level)."}\n";
			}
			else
			{
				$stmt = trim($stmt, "\n\t");
				$stmt = preg_replace("/\\n[\\t]+/", "\n", $stmt);
				$code .= str_repeat("\t", $level).str_replace("\n\$", "\n".str_repeat("\t", $level)."\$", $stmt)."\n";
			}
		}
		return $code;
	}

	/**
	 * @return array
	 */
	public function getCodeTree()
	{
		if (!$this->tree)
		{
			$this->makeCodeTree($this->statements, $this->tree);
		}
		return $this->tree;
	}
	
	/**
	 * Adds one more line to the body.
	 *
	 * @param Statement[] $updaterSteps Plain array of updater steps.
	 * @param array &$result Nested arrays of structured code.
	 *
	 * @return void
	 */
	protected function makeCodeTree(array $updaterSteps, &$result)
	{
		foreach ($updaterSteps as $i => $statement)
		{
			if (empty($statement->conditions))
			{
				$result[] = $statement->formatBodyLines(0);
				unset($updaterSteps[$i]);
			}
		}

		while ($updaterSteps)
		{
			$byPredicates = array();
			foreach ($updaterSteps as $i => $statement)
			{
				/**
				 * @var Condition $condition
				 */
				foreach ($statement->conditions as $condition)
				{
					$predicate = $condition->getPredicate();
					if (!isset($byPredicates[$predicate]))
					{
						$byPredicates[$predicate] = array(
							"predicate" => $predicate,
							"sort" => $this->getPredicateSort($predicate),
							"count" => 1,
						);
					}
					else
					{
						$byPredicates[$predicate]["count"]++;
					}
				}
			}

			if ($byPredicates)
			{
				sortByColumn($byPredicates, array(
					"count" => SORT_DESC,
					"sort" => SORT_ASC,
				));
				$mostPopular = key($byPredicates);
				$subSteps = array();
				$ifStatement = array(
					"if" => array($mostPopular),
					"body" => array(),
				);
				foreach ($updaterSteps as $i => $statement)
				{
					foreach ($statement->conditions as $j => $condition)
					{
						if ($condition->getPredicate() == $mostPopular)
						{
							unset($statement->conditions[$j]);
							$subSteps[] = $statement;
							unset($updaterSteps[$i]);
						}
					}
				}
				$this->makeCodeTree($subSteps, $ifStatement["body"]);
				if (
					is_array($ifStatement["body"])
					&& count($ifStatement["body"]) == 1
					&& is_array($ifStatement["body"][0])
					&& isset($ifStatement["body"][0]["if"])
					&& isset($ifStatement["body"][0]["body"])
					&& strlen(implode(' && ', array_merge($ifStatement["if"], $ifStatement["body"][0]["if"]))) < 100
				)
				{
					$ifStatement["if"] = array_merge($ifStatement["if"], $ifStatement["body"][0]["if"]);
					$ifStatement["body"] = $ifStatement["body"][0]["body"];
				}
				$result[] = $ifStatement;
			}
		}
	}

	/**
	 * @param array $predicate Array describing predicate.
	 *
	 * @return integer
	 */
	protected function getPredicateSort($predicate)
	{
		if (strpos($predicate, "CanUpdateDatabase"))
			return 10;
		elseif (strpos($predicate, "->type"))
			return 20;
		elseif (strpos($predicate, "TableExists"))
			return 30;
		else
			return 50;
	}
}