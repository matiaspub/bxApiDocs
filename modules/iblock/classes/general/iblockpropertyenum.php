<?
class CIBlockPropertyEnumResult extends CDBResult
{
	function Fetch()
	{
		$a = parent::Fetch();
		if($a && defined("BX_COMP_MANAGED_CACHE"))
		{
			$GLOBALS["CACHE_MANAGER"]->RegisterTag("iblock_property_enum_".$a["PROPERTY_ID"]);
		}
		return $a;
	}
}

/**
 * <b>CIBlockPropertyEnum</b> - класс для работы со вариантами значений свойств типа "список".
 *
 *
 *
 *
 * @return mixed
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpropertyenum/index.php
 * @author Bitrix
 */
class CIBlockPropertyEnum
{

	/**
	 * <p>Возвращает список вариантов значений свойств типа "список" по фильтру <i>arFilter</i> отсортированные в порядке <i>arOrder</i>.</p>
	 *
	 *
	 *
	 *
	 * @param array $arrayarOrder = Array("SORT"=>"ASC", "VALUE"=>"ASC") Массив для сортировки, имеющий вид <i>by1</i>=&gt;<i>order1</i>[,
	 * <i>by2</i>=&gt;<i>order2</i> [, ..]], где <i> <br> by</i> - поле сортировки, может
	 * принимать значения: <br><ul> <li> <i>id</i> - код варианта значения; </li> <li>
	 * <i>value</i> - значение варианта; </li> <li> <i>sort</i> - индекс сортировки
	 * варианта; </li> <li> <i>xml_id </i>или <i>external_id</i> - внешний код варианта
	 * значения; </li> <li> <i> def </i>- по признаку "значение по умолчанию"; </li> <li>
	 * <i>property_id</i> - код свойства; </li> <li> <i>property_sort</i> - индекс сортировки
	 * свойства; </li> <li> <i>property_code</i> - мнемонический код свойства;</li> </ul> <i>
	 * order</i> - порядок сортировки, может принимать значения: <br><ul> <li>
	 * <i>asc</i> - по возрастанию; </li> <li> <i>desc</i> - по убыванию; </li> </ul>
	 *
	 *
	 *
	 * @param array $arrayarFilter = Array() Массив вида array("фильтруемое поле"=&gt;"значение" [, ...]) <br>
	 * "фильтруемое поле" может принимать значения: <br><ul> <li> <i>VALUE</i> - по
	 * значению (по шаблону [%_]); </li> <li> <i>ID</i> - по коду значения варианта
	 * свойства; </li> <li> <i>SORT</i> - по индексу сортировки варианта свойства;
	 * </li> <li> <i>DEF</i> - по параметру "значение по умолчанию" (Y|N); </li> <li>
	 * <i>XML_ID</i> - по внешнему коду(по шаблону [%_]); </li> <li> <i>EXTERNAL_ID</i> - по
	 * внешнему коду; </li> <li> <i>CODE</i> - по мнемоническому коду свойства (по
	 * шаблону [%_]); </li> <li> <i>PROPERTY_ID</i> - по числовому или мнемоническому
	 * коду свойства; </li> <li> <i>IBLOCK_ID</i> - фильтр по коду информационного
	 * блока, которому принадлежит свойство; </li> </ul> Необязательное. По
	 * умолчанию записи не фильтруются.
	 *
	 *
	 *
	 * @return CDBResult <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>
	 *
	 *
	 * <h4>Example</h4>
	 * <pre>
	 * &lt;?<br>$property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=&gt;"DESC", "SORT"=&gt;"ASC"), Array("IBLOCK_ID"=&gt;$IBLOCK_ID, "CODE"=&gt;"COLORS"));<br>while($enum_fields = $property_enums-&gt;GetNext())<br>{<br>  echo $enum_fields["ID"]." - ".$enum_fields["VALUE"]."&lt;br&gt;";<br>}<br>?&gt;<br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4>
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fproperty">Поля варианта значений
	 * свойства типа "список"</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpropertyenum/getlist.php
	 * @author Bitrix
	 */
	public static function GetList($arOrder = array("SORT"=>"ASC", "VALUE"=>"ASC"), $arFilter = array())
	{
		global $DB;

		$arSqlSearch = array();
		foreach ($arFilter as $key => $val)
		{
			if ($key[0] == "!")
			{
				$key = substr($key, 1);
				$bInvert = true;
			}
			else
			{
				$bInvert = false;
			}

			$key = strtoupper($key);
			switch ($key)
			{
			case "CODE":
				$arSqlSearch[] = CIBlock::FilterCreate("P.CODE", $val, "string", $bInvert);
				break;
			case "IBLOCK_ID":
				$arSqlSearch[] = CIBlock::FilterCreate("P.IBLOCK_ID", $val, "number", $bInvert);
				break;
			case "DEF":
				$arSqlSearch[] = CIBlock::FilterCreate("BEN.DEF", $val, "string_equal", $bInvert);
				break;
			case "EXTERNAL_ID":
				$arSqlSearch[] = CIBlock::FilterCreate("BEN.XML_ID", $val, "string_equal", $bInvert);
				break;
			case "VALUE":
			case "XML_ID":
			case "TMP_ID":
				$arSqlSearch[] = CIBlock::FilterCreate("BEN.".$key, $val, "string", $bInvert);
				break;
			case "PROPERTY_ID":
				if(is_numeric(substr($val, 0, 1)))
					$arSqlSearch[] = CIBlock::FilterCreate("P.ID", $val, "number", $bInvert);
				else
					$arSqlSearch[] = CIBlock::FilterCreate("P.CODE", $val, "string", $bInvert);
				break;
			case "PROPERTY_ACTIVE":
				$arSqlSearch[] = CIBlock::FilterCreate("P.ACTIVE", $val, "string_equal", $bInvert);
				break;
			case "ID":
			case "SORT":
				$arSqlSearch[] = CIBlock::FilterCreate("BEN.".$key, $val, "number", $bInvert);
				break;
			}
		}

		$strSqlSearch = "";
		foreach(array_filter($arSqlSearch) as $sqlCondition)
			$strSqlSearch .= " AND  (".$sqlCondition.") ";

		$arSqlOrder = array();
		foreach ($arOrder as $by => $order)
		{
			$order = strtolower($order) != "asc"? "desc": "asc";
			$by = strtoupper($by);
			switch ($by)
			{
			case "ID":
			case "PROPERTY_ID":
			case "VALUE":
			case "XML_ID":
			case "EXTERNAL_ID":
			case "DEF":
				$arSqlOrder[$by] = "BEN.".$by." ".$order;
				break;
			case "PROPERTY_SORT":
				$arSqlOrder[$by] = "P.SORT ".$order;
				break;
			case "PROPERTY_CODE":
				$arSqlOrder[$by] = "P.CODE ".$order;
				break;
			default:
				$arSqlOrder["SORT"] = " BEN.SORT ".$order;
				break;
			}
		}

		if (!empty($arSqlOrder))
			$strSqlOrder = "ORDER BY ".implode(", ", $arSqlOrder);
		else
			$strSqlOrder = "";

		$strSql = "
			SELECT
				BEN.*,
				BEN.XML_ID as EXTERNAL_ID,
				P.NAME as PROPERTY_NAME,
				P.CODE as PROPERTY_CODE,
				P.SORT as PROPERTY_SORT
			FROM
				b_iblock_property_enum BEN,
				b_iblock_property P
			WHERE
				BEN.PROPERTY_ID=P.ID
			$strSqlSearch
			$strSqlOrder
		";

		$rs = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		return new CIBlockPropertyEnumResult($rs);
	}


	/**
	 * <p>Функция добавляет новый вариант значения свойства типа "список". </p>
	 *
	 *
	 *
	 *
	 * @param array $fields  Массив Array("поле"=&gt;"значение", ...). Содержит значения <a
	 * href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fpropertyenum">всех полей</a> варианта
	 * значения свойства.
	 *
	 *
	 *
	 * @return int <p>Функция возвращает код добавленного свойства, если добавление
	 * прошло успешно, при возникновении ошибки функция вернет false.</p>
	 *
	 *
	 * <h4>Example</h4>
	 * <pre>
	 * &lt;?
	 * $ibpenum = new CIBlockPropertyEnum;
	 * if($PropID = $ibpenum-&gt;Add(Array('PROPERTY_ID'=&gt;$PROPERTY_ID, 'VALUE'=&gt;'New Enum 1')))
	 *  echo 'New ID:'.$PropID;
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4>
	 * <ul> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpropertyenum/update.php">CIBlockPropertyEnum::Update</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fpropertyenum">Поля вариантов
	 * значения свойств типа "список" </a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpropertyenum/add.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB, $CACHE_MANAGER;

		if(strlen($arFields["VALUE"])<=0)
			return false;

		if(CACHED_b_iblock_property_enum !== false)
			$GLOBALS["CACHE_MANAGER"]->CleanDir("b_iblock_property_enum");

		if(is_set($arFields, "DEF") && $arFields["DEF"]!="Y")
			$arFields["DEF"]="N";

		if(is_set($arFields, "EXTERNAL_ID"))
			$arFields["XML_ID"] = $arFields["EXTERNAL_ID"];

		if(!is_set($arFields, "XML_ID"))
			$arFields["XML_ID"] = md5(uniqid(""));


		unset($arFields["ID"]);

		$ID = $DB->Add("b_iblock_property_enum", $arFields);

		if (defined("BX_COMP_MANAGED_CACHE"))
			$CACHE_MANAGER->ClearByTag("iblock_property_enum_".$arFields["PROPERTY_ID"]);

		return $ID;
	}


	/**
	 * <p>Функция изменяет параметры варианта свойства с кодом <i>ID</i>.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID изменяемой записи.
	 *
	 *
	 *
	 * @param array $arFields  Массив Array("поле"=&gt;"значение", ...). Содержит значения <a
	 * href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fpropertyenum">всех полей</a> варианта
	 * значения свойства.
	 *
	 *
	 *
	 * @return bool <p>Функция возвращает true если изменение прошло успешно, при
	 * возникновении ошибки метод вернет false.</p>
	 *
	 *
	 * <h4>Example</h4>
	 * <pre>
	 * &lt;?
	 * $ibpenum = new CIBlockPropertyEnum;
	 * $ibpenum-&gt;Update($PROPERTY_ENUM_ID, Array('VALUE'=&gt;'Enum 1'));
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4>
	 * <ul> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpropertyenum/add.php">CIBlockPropertyEnum::Add</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fpropertyenum">Поля свойства</a> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpropertyenum/update.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields)
	{
		global $DB, $CACHE_MANAGER;
		$ID = IntVal($ID);

		if(is_set($arFields, "VALUE") && strlen($arFields["VALUE"])<=0)
			return false;

		if(CACHED_b_iblock_property_enum !== false)
			$CACHE_MANAGER->CleanDir("b_iblock_property_enum");

		if(is_set($arFields, "EXTERNAL_ID"))
			$arFields["XML_ID"] = $arFields["EXTERNAL_ID"];

		if(is_set($arFields, "DEF") && $arFields["DEF"]!="Y")
			$arFields["DEF"]="N";

		$strUpdate = $DB->PrepareUpdate("b_iblock_property_enum", $arFields);
		if(strlen($strUpdate) > 0)
			$DB->Query("UPDATE b_iblock_property_enum SET ".$strUpdate." WHERE ID=".$ID);

		if (defined("BX_COMP_MANAGED_CACHE") && IntVal($arFields["PROPERTY_ID"]) > 0)
			$CACHE_MANAGER->ClearByTag("iblock_property_enum_".$arFields["PROPERTY_ID"]);

		return true;
	}

	public static function DeleteByPropertyID($PROPERTY_ID, $bIgnoreError=false)
	{
		global $DB, $CACHE_MANAGER;

		if(CACHED_b_iblock_property_enum !== false)
			$CACHE_MANAGER->CleanDir("b_iblock_property_enum");

		if (defined("BX_COMP_MANAGED_CACHE"))
			$CACHE_MANAGER->ClearByTag("iblock_property_enum_".$PROPERTY_ID);

		return $DB->Query("
			DELETE FROM b_iblock_property_enum
			WHERE PROPERTY_ID=".IntVal($PROPERTY_ID)."
			", $bIgnoreError
		);
	}


	/**
	 * <p>Функция удаляет вариант значения свойства типа "список".</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код варианта значения свойства.
	 *
	 *
	 *
	 * @return bool <p>В случае успешного удаления возвращается true, иначе - false.</p>
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpropertyenum/delete.php
	 * @author Bitrix
	 */
	function Delete($ID)
	{
		global $DB, $CACHE_MANAGER;

		if(CACHED_b_iblock_property_enum !== false)
			$CACHE_MANAGER->CleanDir("b_iblock_property_enum");

		$DB->Query("
			DELETE FROM b_iblock_property_enum
			WHERE ID=".IntVal($ID)."
			"
		);

		return true;
	}


	/**
	 * <p>Функция возвращает значения <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fpropertyenum">всех полей</a> варианта значения свойства типа "список".Или false если такой вариант отсутствует. <br></p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код варианта значения свойства.
	 *
	 *
	 *
	 * @return array <p>Массив если такой вариант есть и false если вариант отсутствует.</p>
	 *
	 *
	 * <h4>See Also</h4>
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fpropertyenum">Поля вариантов
	 * значения свойств типа "список" </a> </li> </ul><br>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpropertyenum/GetByID.php
	 * @author Bitrix
	 */
	public static function GetByID($ID)
	{
		global $DB, $CACHE_MANAGER;
		static $BX_IBLOCK_ENUM_CACHE = array();

		$ID=intval($ID);

		if(!array_key_exists($ID, $BX_IBLOCK_ENUM_CACHE))
		{
			if(CACHED_b_iblock_property_enum===false)
			{
				$rs = $DB->Query("SELECT * from b_iblock_property_enum WHERE ID=".$ID);
				$BX_IBLOCK_ENUM_CACHE[$ID] = $rs->Fetch();
			}
			else
			{
				$bucket_size = intval(CACHED_b_iblock_property_enum_bucket_size);
				if($bucket_size<=0) $bucket_size = 10;

				$bucket = intval($ID/$bucket_size);
				if($CACHE_MANAGER->Read(CACHED_b_iblock_property_enum, $cache_id="b_iblock_property_enum".$bucket, "b_iblock_property_enum"))
				{
					$arEnums = $CACHE_MANAGER->Get($cache_id);
				}
				else
				{
					$arEnums = array();
					$rs = $DB->Query("
						SELECT *
						FROM b_iblock_property_enum
						WHERE ID between ".($bucket*$bucket_size)." AND ".(($bucket+1)*$bucket_size-1)
					);
					while($ar = $rs->Fetch())
						$arEnums[$ar["ID"]]=$ar;
					$CACHE_MANAGER->Set($cache_id, $arEnums);
				}
				$max = ($bucket+1)*$bucket_size;
				for($i=$bucket*$bucket_size;$i<$max;$i++)
				{
					if(array_key_exists($i, $arEnums))
						$BX_IBLOCK_ENUM_CACHE[$i]=$arEnums[$i];
					else
						$BX_IBLOCK_ENUM_CACHE[$i]=false;
				}
			}
		}

		return $BX_IBLOCK_ENUM_CACHE[$ID];
	}
}
?>