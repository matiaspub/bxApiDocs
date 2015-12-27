<?php
namespace Bitrix\Perfmon\Sql;

class Compare
{
	private $difference = array();

	/**
	 * Compares two database schemas and returns array of pairs.
	 * <p>
	 * Pair is the two element array:
	 * - First element with index "0" is the object from the source collection.
	 * - Second element with index "1" is the object from $targetList.
	 * - if pair element is null when no such element found (by name) in the collection.
	 *
	 * @param Schema $source Source schema.
	 * @param Schema $target Target schema.
	 *
	 * @return array
	 */
	public static function diff(Schema $source, Schema $target)
	{
		$compare = new Compare;
		$compare->compareSequences($source, $target);
		$compare->compareProcedures($source, $target);
		$compare->compareTables($source, $target);
		return $compare->difference;
	}

	/**
	 * Compares sequences.
	 *
	 * @param Schema $source Source schema.
	 * @param Schema $target Target schema.
	 *
	 * @return void
	 */
	private function compareSequences(Schema $source, Schema $target)
	{
		foreach ($source->sequences->compare($target->sequences) as $pair)
		{
			$this->difference[] = $pair;
		}
	}

	/**
	 * Compares procedures.
	 *
	 * @param Schema $source Source schema.
	 * @param Schema $target Target schema.
	 *
	 * @return void
	 */
	private function compareProcedures(Schema $source, Schema $target)
	{
		foreach ($source->procedures->compare($target->procedures) as $pair)
		{
			$this->difference[] = $pair;
		}
	}

	/**
	 * Compares tables.
	 *
	 * @param Schema $source Source schema.
	 * @param Schema $target Target schema.
	 *
	 * @return void
	 */
	private function compareTables(Schema $source, Schema $target)
	{
		foreach ($source->tables->compare($target->tables, false) as $pair)
		{
			if (isset($pair[0]) && isset($pair[1]))
			{
				$this->compareTable($pair[0], $pair[1]);
			}
			elseif (!isset($pair[0]) && isset($pair[1])) //Table created
			{
				$this->difference[] = $pair;
				$emptyCollection = new Collection;
				foreach ($emptyCollection->compare($pair[1]->indexes) as $pair2)
				{
					$this->difference[] = $pair2;
				}
				$emptyCollection = new Collection;
				foreach ($emptyCollection->compare($pair[1]->triggers) as $pair2)
				{
					$this->difference[] = $pair2;
				}
			}
			else
			{
				$this->difference[] = $pair;
			}
		}
	}

	/**
	 * Compares tables columns, indexes, constraints, and triggers..
	 *
	 * @param Table $source Source table.
	 * @param Table $target Target table.
	 *
	 * @return void
	 */
	private function compareTable(Table $source, Table $target)
	{
		foreach ($source->columns->compare($target->columns) as $pair)
		{
			if (isset($pair[0]) && isset($pair[1]))
			{
				$this->compareColumn($pair[0], $pair[1]);
			}
			else
			{
				$this->difference[] = $pair;
			}
		}
		foreach ($source->indexes->compare($target->indexes) as $pair)
		{
			$this->difference[] = $pair;
		}
		foreach ($source->constraints->compare($target->constraints) as $pair)
		{
			$this->difference[] = $pair;
		}
		foreach ($source->triggers->compare($target->triggers) as $pair)
		{
			$this->difference[] = $pair;
		}
	}

	/**
	 * Compares columns
	 *
	 * @param Column $source Source column.
	 * @param Column $target Target column.
	 *
	 * @return void
	 */
	private function compareColumn(Column $source, Column $target)
	{
		if ($source->type !== $target->type)
		{
			$this->difference[] = array($source, $target);
		}
		elseif ($source->length !== $target->length)
		{
			$this->difference[] = array($source, $target);
		}
		elseif ($source->nullable !== $target->nullable)
		{
			$this->difference[] = array($source, $target);
		}
		elseif ($source->default !== $target->default)
		{
			$this->difference[] = array($source, $target);
		}
	}
}