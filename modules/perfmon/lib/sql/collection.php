<?php
namespace Bitrix\Perfmon\Sql;

/**
 * Class Collection
 * This class represents collection of database objects such as table columns or indexes, schema procedures or sequences.
 * @package Bitrix\Perfmon\Sql
 */
class Collection
{
	/** @var array[BaseObject]  */
	private $list = array();

	/**
	 * Add object into the tail of the collection.
	 *
	 * @param BaseObject $object Object to add.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод добавляет объект в конец коллекции.</p>
	*
	*
	* @param mixed $Bitrix  Добавляемый объект.
	*
	* @param Bitri $Perfmon  
	*
	* @param Perfmo $Sql  
	*
	* @param BaseObject $object  
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/collection/add.php
	* @author Bitrix
	*/
	public function add(BaseObject $object)
	{
		$this->list[] = $object;
	}

	/**
	 * Searches collection for an object by it's name.
	 *
	 * @param string $name Object name to look up.
	 *
	 * @return BaseObject|null
	 */
	
	/**
	* <p>Нестатический метод ищет объект в коллекции. Поиск производится по имени объекта.</p>
	*
	*
	* @param string $name  Имя объекта.
	*
	* @return \Bitrix\Perfmon\Sql\BaseObject|null 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/collection/search.php
	* @author Bitrix
	*/
	public function search($name)
	{
		/** @var BaseObject $object */
		foreach ($this->list as $object)
		{
			if ($object->compareName($name) == 0)
				return $object;
		}
		return null;
	}

	/**
	 * Returns all collection objects.
	 *
	 * @return array[BaseObject]
	 */
	
	/**
	* <p>Нестатический метод возвращает все объекты коллекции.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Perfmon\Sql\array[BaseObject] 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/collection/getlist.php
	* @author Bitrix
	*/
	public function getList()
	{
		return $this->list;
	}

	/**
	 * Compares two collections of objects and returns array of pairs.
	 * <p>
	 * Pair is the two element array:
	 * - First element with index "0" is the object from the source collection.
	 * - Second element with index "1" is the object from $targetList.
	 * - if pair element is null when no such element found (by name) in the collection.
	 *
	 * @param Collection $targetList Collection to compare.
	 * @param bool $compareBody Whenever to compare objects bodies or not.
	 *
	 * @return array
	 */
	
	/**
	* <p>Нестатический метод сравнивает две коллекции объектов и возвращает массив пар:</p> <p></p> <p> Каждая пара является массивом из двух элементов:</p> <ol> <li>Первый объект, с индексом <code>"0"</code> это объект из исходного набора.</li> <li>Второй объект с индексом <code>"1"</code> это объект  из <code>$targetList</code>. В случае если элемент отсутствует, значит имя такого элемента не было найдено в коллекции.</li> </ol>
	*
	*
	* @param mixed $Bitrix  Сравниваемая коллекция.
	*
	* @param Bitri $Perfmon  Необходимо ли сравнивать исходный код (<code>body</code>).
	*
	* @param Perfmo $Sql  
	*
	* @param Collection $targetList  
	*
	* @param boolean $compareBody = true 
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/collection/compare.php
	* @author Bitrix
	*/
	public function compare(Collection $targetList, $compareBody = true)
	{
		$difference = array();
		/** @var BaseObject $source */
		foreach ($this->list as $source)
		{
			if (!$targetList->search($source->name))
			{
				$difference[] = array(
					$source,
					null,
				);
			}
		}
		/** @var BaseObject $target */
		foreach ($targetList->list as $target)
		{
			$source = $this->search($target->name);
			if (!$source)
			{
				$difference[] = array(
					null,
					$target,
				);
			}
			elseif (
				!$compareBody
				|| $source->body !== $target->body
			)
			{
				$difference[] = array(
					$source,
					$target,
				);
			}
		}
		return $difference;
	}
}
