<?
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);


/**
 * <b>CCatalogVat</b> - класс для работы со ставками НДС. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/index.php
 * @author Bitrix
 */
class CAllCatalogVat
{
/*
* @deprecated deprecated since catalog 12.5.6
*/
	public static function err_mess()
	{
		return "<br>Module: catalog<br>Class: CCatalogVat<br>File: ".__FILE__;
	}

	
	/**
	* <p>Метод служит для проверки параметров, переданных в методы <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/add.php">CCatalogVat::Add</a> и <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/update.php">CCatalogVat::Update</a>. Метод динамичный.</p>
	*
	*
	* @param string $ACTION  Указывает, для какого метода идет проверка. Возможные значения:
	* <br><ul> <li> <b>ADD</b> - для метода <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/add.php">CCatalogVat::Add</a>;</li> <li>
	* <b>UPDATE</b> - для метода <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/update.php">CCatalogVat::Update</a>.</li> </ul>
	*
	* @param array &$arFields  Ассоциативный массив параметров ставки НДС. Допустимые ключи: <ul>
	* <li>ACTIVE - активность ставки НДС ('Y' - активна, 'N' - неактивна);</li> <li>SORT -
	* индекс сортировки (до версии 12.5.6 использовалось поле C_SORT);</li> <li>NAME
	* - название ставки НДС;</li> <li>RATE - величина ставки НДС.</li> </ul>
	*
	* @param int $ID = 0 Код ставки НДС. Параметр является необязательным и имеет смысл
	* только для $ACTION = 'UPDATE'.
	*
	* @return bool <p>В случае корректности переданных параметров возвращает <i>true</i>,
	* иначе - <i>false</i>. Если метод вернула <i>false</i>, с помощью
	* <i>$APPLICATION-&gt;GetException()</i> можно получить текст ошибок.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/add.php">CCatalogVat::Add</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/update.php">CCatalogVat::Update</a> </li>
	* </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/checkfields.php
	* @author Bitrix
	*/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $APPLICATION;
		$arMsg = array();
		$boolResult = true;

		$ACTION = strtoupper($ACTION);
		if ('INSERT' == $ACTION)
			$ACTION = 'ADD';

		if (isset($arFields['SORT']))
		{
			$arFields['C_SORT'] = $arFields['SORT'];
			unset($arFields['SORT']);
		}

		if (array_key_exists('ID', $arFields))
		{
			unset($arFields['ID']);
		}

		if ('ADD' == $ACTION)
		{
			if (!isset($arFields['NAME']))
			{
				$boolResult = false;
				$arMsg[] = array('id' => 'NAME', "text" => Loc::getMessage('CVAT_ERROR_BAD_NAME'));
			}
			if (!isset($arFields['RATE']))
			{
				$boolResult = false;
				$arMsg[] = array('id' => 'RATE', "text" => Loc::getMessage('CVAT_ERROR_BAD_RATE'));
			}
			if (!isset($arFields['C_SORT']))
			{
				$arFields['C_SORT'] = 100;
			}
			if (!isset($arFields['ACTIVE']))
			{
				$arFields['ACTIVE'] = 'Y';
			}
		}

		if ($boolResult)
		{
			if (array_key_exists('NAME', $arFields))
			{
				$arFields['NAME'] = trim($arFields['NAME']);
				if ('' == $arFields['NAME'])
				{
					$boolResult = false;
					$arMsg[] = array('id' => 'NAME', "text" => Loc::getMessage('CVAT_ERROR_BAD_NAME'));
				}
			}
			if (array_key_exists('RATE', $arFields))
			{
				$arFields['RATE'] = doubleval($arFields['RATE']);
				if (0 > $arFields['RATE'] || 100 < $arFields['RATE'])
				{
					$boolResult = false;
					$arMsg[] = array('id' => 'RATE', "text" => Loc::getMessage('CVAT_ERROR_BAD_RATE'));
				}
			}
			if (array_key_exists('C_SORT', $arFields))
			{
				$arFields['C_SORT'] = intval($arFields['C_SORT']);
				if (0 >= $arFields['C_SORT'])
				{
					$arFields['C_SORT'] = 100;
				}
			}
			if (array_key_exists('ACTIVE', $arFields))
			{
				$arFields['ACTIVE'] = ($arFields['ACTIVE'] == 'Y' ? 'Y' : 'N');
			}
		}

		if (!$boolResult)
		{
			$obError = new CAdminException($arMsg);
			$APPLICATION->ResetException();
			$APPLICATION->ThrowException($obError);
		}
		return $boolResult;
	}

	
	/**
	* <p>Метод возвращает ставку НДС по ее коду <i>ID</i>. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код ставки НДС.
	*
	* @return CDBResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> </h
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li></ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		return CCatalogVat::GetListEx(array(), array('ID' => $ID));
	}

	
	/**
	* <p>Метод возвращает результат выборки записей из таблицы ставок НДС в соответствии со своими параметрами. Метод динамичный.</p> <p></p> <div class="note"> <b>Примечание:</b> начиная с версии модуля <b>12.5.6</b>, метод считается устаревшим. Вместо него рекомендуется использовать <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/getlistex.php">CCatalogVat::GetListEx</a>.</div>
	*
	*
	* @param array $arrayarOrder = array('CSORT' => 'ASC') Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле, кроме <i>TIMESTAMP_X</i>. В качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию). <br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и т.д.).
	*
	* @param array $arrayarFilter = array() Массив, в соответствии с которым фильтруются записи. Массив имеет
	* вид: <pre class="syntax">array( "[оператор1]название_поля1" =&gt; "значение1",
	* "[оператор2]название_поля2" =&gt; "значение2", . . . )</pre> Удовлетворяющие
	* фильтру записи возвращаются в результате, а записи, которые не
	* удовлетворяют условиям фильтра, отбрасываются. <br><br> Допустимыми
	* являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	* или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	* поля строго больше передаваемой в фильтр величины;</li> <li> <b>&lt;=</b> -
	* значение поля меньше или равно передаваемой в фильтр величины;</li>
	* <li> <b>&lt;</b> - значение поля строго меньше передаваемой в фильтр
	* величины;</li> <li> <b>@</b> - оператор может использоваться для
	* целочисленных и вещественных данных при передаче набора
	* значений (массива). В этом случае при генерации sql-запроса будет
	* использован sql-оператор <b>IN</b>, дающий компактную форму записи;</li>
	* <li> <b>~</b> - значение поля проверяется на соответствие
	* передаваемому в фильтр шаблону;</li> <li> <b>%</b> - значение поля
	* проверяется на соответствие передаваемой в фильтр строке в
	* соответствии с языком запросов.</li> </ul> В качестве "название_поляX"
	* может стоять любое из следующих полей: <i>ID</i>, <i>ACTIVE</i>, <i>NAME</i> или
	* <i>RATE</i>. <br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отфильтрован не будет.
	*
	* @param array $arrayarFields = array() Массив полей записей, которые будут возвращены методом.<br><br>
	* Возможные поля выборки: <i>ID</i>, <i>TIMESTAMP_X</i>, <i>ACTIVE</i>, <i>C_SORT</i>, <i>NAME</i> и
	* <i>RATE</i>.
	*
	* @return CDBResult <p>Возвращает объект класса <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>, содержащий
	* коллекцию ассоциативных массивов с ключами.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array('SORT' => 'ASC'), $arFilter = array(), $arFields = array())
	{
		if (is_array($arFilter))
		{
			if (array_key_exists('NAME', $arFilter) && array_key_exists('NAME_EXACT_MATCH', $arFilter))
			{
				if ('Y' == $arFilter['NAME_EXACT_MATCH'])
				{
					$arFilter['=NAME'] = $arFilter['NAME'];
					unset($arFilter['NAME']);
				}
				unset($arFilter['NAME_EXACT_MATCH']);
			}
		}
		return CCatalogVat::GetListEx($arOrder, $arFilter, false, false, $arFields);
	}

/*
* @deprecated deprecated since catalog 12.5.6
* @see CCatalogVat::Add()
* @see CCatalogVat::Update()
*/
	
	/**
	* <p>Метод добавляет новую ставку НДС или обновляет существующую в зависимости от передаваемых данных в массиве <i>arFields</i>. Метод динамичный.</p> <p></p> <div class="note"> <b>Примечание:</b> метод устарел, вместо него рекомендуется использоваться <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/add.php">CCatalogVat::Add</a> и <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/update.php">CCatalogVat::Update</a> соответственно.</div>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров ставки НДС. Допустимые ключи: <ul>
	* <li>ID - код ставки НДС (задается только для существующей ставки);</li>
	* <li>ACTIVE - активность ставки НДС ('Y' - активна, 'N' - неактивна);</li> <li>C_SORT
	* - индекс сортировки;</li> <li>NAME - название ставки НДС;</li> <li>RATE -
	* величина ставки НДС.</li> </ul>
	*
	* @return mixed <p>Метод возвращает <i>ID</i> созданной или измененной ставки НДС,
	* либо <i>false</i> в случае ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/set.php
	* @author Bitrix
	* @deprecated deprecated since catalog 12.5.6  ->  CCatalogVat::Add()
	*/
	public static function Set($arFields)
	{
		if (isset($arFields['ID']) && intval($arFields['ID']) > 0)
		{
			return CCatalogVat::Update($arFields['ID'], $arFields);
		}
		else
		{
			return CCatalogVat::Add($arFields);
		}
	}

	public static function GetByProductID($PRODUCT_ID)
	{

	}
}
?>