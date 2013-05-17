<?
IncludeModuleLangFile(__FILE__);


/**
 * CIBlockType - класс для работы с типами информационных блоков.
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/index.php
 * @author Bitrix
 */
class CIBlockType
{
	///////////////////////////////////////////////////////////////////
	// Get list if information blocks function
	///////////////////////////////////////////////////////////////////
	
	/**
	 * <p>Возвращает список типов информационных блоков по фильтру <i>arFilter</i> с сортировкой <i>arOrder</i>.</p>
	 *
	 *
	 *
	 *
	 * @param array $arrayarOrder = Array("SORT"=>"ASC") Массив полей для сортировки, содержащий пары "поле
	 * сортировки"=&gt;"направление сортировки". <br> Поля сортировки могут
	 * принимать значения <br>     <i>id</i> - код типа; <br>     <i>sort</i> - индекс
	 * сортировки; <br>
	 *
	 *
	 *
	 * @param array $arrayarFilter = Array() Массив вида array("фильтруемое поле"=&gt;"значение" [, ...]) <br> может
	 * принимать значения <br>     <i>ID</i> - регистронезависимый по
	 * подстроке в коде типа; <br>     <i>=ID</i> - точное совпадение с кодом
	 * типа; <br>     <i>NAME</i> - регистронезависимый по подстроке в названии
	 * типа (для всех языков); <br> Необязательное. По умолчанию записи не
	 * фильтруются.
	 *
	 *
	 *
	 * @return CDBResult <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$db_iblock_type = CIBlockType::GetList();<br>while($ar_iblock_type = $db_iblock_type-&gt;Fetch())<br>{<br>   if($arIBType = CIBlockType::GetByIDLang($ar_iblock_type["ID"], LANG))<br>   {<br>      echo htmlspecialcharsEx($arIBType["NAME"])."&lt;br&gt;";<br>   }   <br>}<br>?&gt;<br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblocktype">Поля CIBlockType</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/getlist.php
	 * @author Bitrix
	 */
	public static function GetList($arOrder = Array("SORT"=>"ASC"), $arFilter=Array())
	{
		global $DB;
		$bLang = false;
		$strSqlSearch = "1=1\n";
		foreach($arFilter as $key=>$val)
		{
			if(!is_array($val) && strlen($val) <= 0)
				continue;

			switch(strtoupper($key))
			{
			case "ID":
				$strSqlSearch .= "AND UPPER(T.ID) LIKE UPPER('".$DB->ForSql($val)."')\n";
				break;
			case "=ID":
				if(is_array($val))
				{
					if(!empty($val))
					{
						$sqlVal = array_map(array($DB, 'ForSQL'), $val);
						$strSqlSearch .= "AND T.ID in ('".implode("', '", $sqlVal)."')\n";
					}
				}
				else
				{
					$strSqlSearch .= "AND T.ID = '".$DB->ForSql($val)."'\n";
				}
				break;
			case "NAME":
				$strSqlSearch .= "AND UPPER(TL.NAME) LIKE UPPER('%".$DB->ForSql($val)."%')\n";
				$bLang = true;
				break;
			}
		}

		$strSqlOrder = '';
		foreach($arOrder as $by=>$order)
		{
			$by = strtoupper($by);
			if($by != "ID")
				$by = "SORT";

			$order = strtolower($order);
			if($order!="desc")
				$order = "asc";

			if($strSqlOrder=='')
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ', ';

			$strSqlOrder .= "T.".$by." ".$order;
		}

		$strSql = "
			SELECT ".($bLang?"DISTINCT":"")." T.*
			FROM b_iblock_type T
			".($bLang?" LEFT JOIN b_iblock_type_lang TL ON TL.IBLOCK_TYPE_ID = T.ID ":"")."
			WHERE ".$strSqlSearch.$strSqlOrder;

		if(CACHED_b_iblock_type===false)
		{
			$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}
		else
		{
			global $CACHE_MANAGER;
			if($CACHE_MANAGER->Read(CACHED_b_iblock_type, $cache_id = "b_iblock_type".md5($strSql), "b_iblock_type"))
			{
				$arResult = $CACHE_MANAGER->Get($cache_id);
			}
			else
			{
				$arResult = array();
				$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
				while($ar = $res->Fetch())
					$arResult[]=$ar;
				$CACHE_MANAGER->Set($cache_id, $arResult);
			}
			$res = new CDBResult;
			$res->InitFromArray($arResult);
		}

		return $res;
	}

	function _GetCache($ID)
	{
		global $DB,$CACHE_MANAGER;
		if($CACHE_MANAGER->Read(CACHED_b_iblock_type, "b_iblock_type", "b_iblock_type"))
			$arIBlocks = $CACHE_MANAGER->Get("b_iblock_type");
		else
		{
			$arIBlocks = array();
			$rs = $DB->Query("SELECT * FROM b_iblock_type");
			while($ar = $rs->GetNext())
			{
				$ar["_lang"]=array();
				$arIBlocks[$ar['ID']] = $ar;
			}
			$rs = $DB->Query("SELECT * FROM b_iblock_type_lang");
			while($ar = $rs->GetNext())
			{
				$arIBlocks[$ar['IBLOCK_TYPE_ID']]["_lang"][$ar["LID"]] = $ar;
			}
			$CACHE_MANAGER->Set("b_iblock_type", $arIBlocks);
		}
		$ID = trim($ID);
		if(array_key_exists($ID, $arIBlocks))
			return $arIBlocks[$ID];
		else
			return false;
	}
	///////////////////////////////////////////////////////////////////
	// This function will return information block by ID
	///////////////////////////////////////////////////////////////////
	
	/**
	 * <p>Возвращает тип информационных блоков по его коду <i>ID</i>.</p>
	 *
	 *
	 *
	 *
	 * @param string $ID  Код типа.
	 *
	 *
	 *
	 * @return CDBResult <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a></li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocktype/index.php">CIBlockType</a></li> </ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/getbyid.php
	 * @author Bitrix
	 */
	public static function GetByID($ID)
	{
		if(CACHED_b_iblock_type===false)
		{
			return CIBlockType::GetList(array(),array("=ID"=>$ID));
		}
		else
		{
			$arResult = CIBlockType::_GetCache($ID);
			$res = new CDBResult;
			if($arResult!==false)
			{
				unset($arResult["_lang"]);
				$res->InitFromArray(array($arResult));
			}
			else
			{
				$res->InitFromArray(array());
			}
			return $res;
		}
	}

	///////////////////////////////////////////////////////////////////
	// This function will get language information by ID
	///////////////////////////////////////////////////////////////////
	
	/**
	 * <p>Метод возвращает языковые настройки типа информационных блоков по его коду <i>ID</i>, для языка <i>LANGUAGE_ID</i>. Если <span class="syntax">для языка <i>LANGUAGE_ID</i> нет настроек и параметр <i>bFindAny</i> установлен в true, функция вернет настройки типа для языка по умолчанию. </span></p>
	 *
	 *
	 *
	 *
	 * @param string $ID  Код типа.
	 *
	 *
	 *
	 * @param string $LANGUAGE_ID  Код языка.
	 *
	 *
	 *
	 * @param bool $bFindAny = true Возвращать настройки для языка по умолчинию или нет.
	 * Необязательный. По умолчанию - возвращать.
	 *
	 *
	 *
	 * @return mixed <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblocktype">полей</a><a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblocktypelang">параметров</a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$db_iblock_type = CIBlockType::GetList();<br>while($ar_iblock_type = $db_iblock_type-&gt;Fetch())<br>{<br>   if($arIBType = CIBlockType::GetByIDLang($ar_iblock_type["ID"], LANG))<br>   {<br>      echo htmlspecialcharsex($arIBType["NAME"])."&lt;br&gt;";<br>   }   <br>}<br>?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblocktype">Поля CIBlockType</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblocktypelang">Языкозависимые поля
	 * CIBlockType</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/getbyidlang.php
	 * @author Bitrix
	 */
	public static function GetByIDLang($ID, $LID, $bFindAny=true)
	{
		global $DB;
		$LID = $DB->ForSQL($LID, 2);

		if(CACHED_b_iblock_type===false)
		{
			$strSql =
				"SELECT BTL.*, BT.* ".
				"FROM b_iblock_type BT, b_iblock_type_lang BTL ".
				"WHERE BTL.IBLOCK_TYPE_ID = '".$DB->ForSQL($ID)."' ".
				"	AND BTL.LID='".$LID."'".
				"	AND BT.ID=BTL.IBLOCK_TYPE_ID ";

			$res = $DB->Query($strSql);

			if($r = $res->GetNext())
				return $r;
		}
		else
		{
			$arResult = CIBlockType::_GetCache($ID);
			if($arResult!==false && array_key_exists($LID, $arResult["_lang"]))
			{
				$res = $arResult["_lang"][$LID];
				unset($arResult["_lang"]);
				return array_merge($res, $arResult);
			}
		}

		if(!$bFindAny)
			return false;

		$strSql =
			"SELECT BTL.*, BT.* ".
			"FROM b_iblock_type BT, b_iblock_type_lang BTL, b_language L ".
			"WHERE BTL.IBLOCK_TYPE_ID = '".$DB->ForSQL($ID)."' ".
			"	AND BTL.LID = L.LID ".
			"	AND BT.ID=BTL.IBLOCK_TYPE_ID ".
			"ORDER BY L.DEF DESC, L.SORT";

		$res = $DB->Query($strSql);

		if($r = $res->GetNext())
			return $r;

		return false;
	}

	///////////////////////////////////////////////////////////////////
	// Delete function
	///////////////////////////////////////////////////////////////////
	
	/**
	 * <p>Функция удаляет тип информационных блоков по его коду <i>ID</i>. Также удаляются все информационные блоки указанного типа. <br></p>
	 *
	 *
	 *
	 *
	 * @param string $ID  Код типа.
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$DB-&gt;StartTransaction();<br>if(!CIBlockType::Delete('catalog'))<br>{<br>    $DB-&gt;Rollback();<br>    echo 'Delete error!';<br>}<br>$DB-&gt;Commit();<br>?&gt;<br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblock/delete.php">CIBlock::Delete</a></li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/delete.php
	 * @author Bitrix
	 */
	public static function Delete($ID)
	{
		global $DB, $USER;
		if(CACHED_b_iblock_type!==false) $GLOBALS["CACHE_MANAGER"]->CleanDir("b_iblock_type");
		$iblocks = CIBlock::GetList(Array(), Array("=TYPE"=>$ID));
		while($iblock = $iblocks->Fetch())
		{
			if(!CIBlock::Delete($iblock["ID"]))
				return false;
		}

		if(!$DB->Query("DELETE FROM b_iblock_type_lang WHERE IBLOCK_TYPE_ID='".$DB->ForSql($ID)."'", true))
			return false;
		return $DB->Query("DELETE FROM b_iblock_type WHERE ID='".$DB->ForSql($ID)."'", true);
	}

	///////////////////////////////////////////////////////////////////
	// This one called before any Add or Update action
	///////////////////////////////////////////////////////////////////
	public static function CheckFields($arFields, $ID=false)
	{
		global $DB;
		$this->LAST_ERROR = "";

		if($ID === false)
		{
			if(!isset($arFields["ID"]) || strlen($arFields["ID"]) <= 0)
			{
				$this->LAST_ERROR .= GetMessage("IBLOCK_TYPE_BAD_ID")."<br>";
			}
			elseif(preg_match("/[^A-Za-z0-9_]/", $arFields["ID"]))
			{
				$this->LAST_ERROR .= GetMessage("IBLOCK_TYPE_ID_HAS_WRONG_CHARS")."<br>";
			}
			else
			{
				$chk = $DB->Query("SELECT 'x' FROM b_iblock_type WHERE ID='".$DB->ForSQL($arFields["ID"])."'");
				if($chk->Fetch())
				{
					$this->LAST_ERROR .= GetMessage("IBLOCK_TYPE_DUBL_ID")."<br>";
					return false;
				}
			}
		}

		if(is_set($arFields, "LANG") && is_array($arFields["LANG"]))
		{
			foreach($arFields["LANG"] as $lid => $arFieldsLang)
			{
				if(strlen($arFieldsLang["NAME"])<=0)
				{
					$this->LAST_ERROR .= GetMessage("IBLOCK_TYPE_BAD_NAME")." ".$lid.".<br>";
				}
			}
		}

		if(strlen($this->LAST_ERROR)>0)
			return false;

		return true;
	}

	///////////////////////////////////////////////////////////////////
	// Add action
	///////////////////////////////////////////////////////////////////
	
	/**
	 * <p> Метод добавляет новый тип информационных блоков. Этот метод не может быть использован как статическая функция. В случае ошибки в свойстве объекта LAST_ERROR будет содержаться текст ошибки. </p>
	 *
	 *
	 *
	 *
	 * @param array $arFields  Массив поле=&gt;значение... Содержит значения <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblocktype">полей типа информационных
	 * блоков</a>. В элементе массива arFields["LANG"] должен содержаться
	 * ассоциативный массив <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblocktypelang">языковых свойств</a>
	 * типа.Ключами этого массива служат идентификаторы языков. <br>
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$arFields = Array(<br>	'ID'=&gt;'catalog',<br>	'SECTIONS'=&gt;'Y',<br>	'IN_RSS'=&gt;'N',<br>	'SORT'=&gt;100,<br>	'LANG'=&gt;Array(<br>		'en'=&gt;Array(<br>			'NAME'=&gt;'Catalog',<br>			'SECTION_NAME'=&gt;'Sections',<br>			'ELEMENT_NAME'=&gt;'Products'<br>			)<br>		)<br>	);<br><br>$obBlocktype = new CIBlockType;<br>$DB-&gt;StartTransaction();<br>$res = $obBlocktype-&gt;Add($arFields);<br>if(!$res)<br>{<br>   $DB-&gt;Rollback();<br>   echo 'Error: '.$obBlocktype-&gt;LAST_ERROR.'&lt;br&gt;';<br>}<br>else<br>   $DB-&gt;Commit();<br>?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocktype/index.php">CIBlockType</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocktype/update.php">Update()</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblocktype">Поля типа информационных
	 * блоков</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/add.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB, $CACHE_MANAGER;

		if (CACHED_b_iblock_type !== false)
			$CACHE_MANAGER->cleanDir("b_iblock_type");

		$arFields["SECTIONS"] = isset($arFields["SECTIONS"]) && $arFields["SECTIONS"] === "Y"? "Y": "N";
		$arFields["IN_RSS"] = isset($arFields["IN_RSS"]) && $arFields["IN_RSS"] === "Y"? "Y": "N";

		if (!$this->CheckFields($arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_iblock_type", $arFields);
		$DB->Query("INSERT INTO b_iblock_type(".$arInsert[0].") VALUES(".$arInsert[1].")");

		if (isset($arFields["LANG"]) && is_array($arFields["LANG"]))
		{
			$DB->Query("DELETE FROM b_iblock_type_lang WHERE IBLOCK_TYPE_ID='".$DB->ForSQL($arFields["ID"])."'");
			foreach ($arFields["LANG"] as $lid => $arFieldsLang)
			{
				$NAME = isset($arFieldsLang["NAME"])? trim($arFieldsLang["NAME"]): "";
				$ELEMENT_NAME = isset($arFieldsLang["ELEMENT_NAME"])? trim($arFieldsLang["ELEMENT_NAME"]): "";
				$SECTION_NAME = isset($arFieldsLang["SECTION_NAME"])? trim($arFieldsLang["SECTION_NAME"]): "";
				if ( ($NAME !== "") || ($ELEMENT_NAME !== ""))
				{
					$arInsert = $DB->PrepareInsert("b_iblock_type_lang", array(
						"IBLOCK_TYPE_ID" => $arFields["ID"],
						"LID" => $lid,
						"NAME" => $NAME,
						"SECTION_NAME" => $SECTION_NAME,
						"ELEMENT_NAME" => $ELEMENT_NAME,
					));
					$DB->Query("INSERT INTO b_iblock_type_lang(".$arInsert[0].") VALUES(".$arInsert[1].")");
				}
			}
		}

		return $arFields["ID"];
	}


	
	/**
	 * <p>Функция изменяет параметры типа информационных блоков с кодом <i>ID</i>.</p>
	 *
	 *
	 *
	 *
	 * @param string $ID  Код изменяемой записи.
	 *
	 *
	 *
	 * @param array $arFields  Массив поле=&gt;значение... Содержит значения <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblocktype">полей типа информационных
	 * блоков</a>. В элементе массива arFields["LANG"] должен содержаться
	 * ассоциативный массив <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblocktypelang">языковых свойств</a> типа.
	 * Ключами этого массива служат идентификаторы языков.
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$arFields = Array(<br>	'SECTIONS'=&gt;'Y',<br>	'IN_RSS'=&gt;'N',<br>	'SORT'=&gt;100,<br>	'LANG'=&gt;Array(<br>		'en'=&gt;Array(<br>			'NAME'=&gt;'Catalog',<br>			'SECTION_NAME'=&gt;'Sections',<br>			'ELEMENT_NAME'=&gt;'Products'<br>			)<br>		)<br>	);<br>
	 * $obBlocktype = new CIBlockType;<br>$DB-&gt;StartTransaction();<br>$res = $obBlocktype-&gt;Update('catalog', $arFields);<br>if(!$res)<br>{<br>   $DB-&gt;Rollback();<br>   echo 'Error: '.$obBlocktype-&gt;LAST_ERROR.'&lt;br&gt;';<br>}<br>else<br>   $DB-&gt;Commit();<br>?&gt;<br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocktype/index.php">CIBlockType</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocktype/add.php">Add()</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblocktype">Поля типа информационных
	 * блоков</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/update.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields)
	{
		global $DB, $USER;
		if(CACHED_b_iblock_type!==false) $GLOBALS["CACHE_MANAGER"]->CleanDir("b_iblock_type");

		$arFields["SECTIONS"] = $arFields["SECTIONS"]=="Y"?"Y":"N";
		$arFields["IN_RSS"] = $arFields["IN_RSS"]=="Y"?"Y":"N";

		if(!$this->CheckFields($arFields, $ID))
			return false;

		$str_update = $DB->PrepareUpdate("b_iblock_type", $arFields);
		$strSql = "UPDATE b_iblock_type SET ".$str_update." WHERE ID='".$DB->ForSQL($ID)."'";
		$DB->Query($strSql);

		if(is_array($arFields["LANG"]))
		{
			$DB->Query("DELETE FROM b_iblock_type_lang WHERE IBLOCK_TYPE_ID='".$DB->ForSQL($ID)."'");
			foreach($arFields["LANG"] as $lid => $arFieldsLang)
			{
				if(strlen($arFieldsLang["NAME"])>0 || strlen($arFieldsLang["ELEMENT_NAME"])>0)
				{
					$strSql =
						"INSERT INTO b_iblock_type_lang(IBLOCK_TYPE_ID, LID, NAME, SECTION_NAME, ELEMENT_NAME) ".
						"SELECT BT.ID, L.LID, '".$DB->ForSql($arFieldsLang["NAME"], 100)."', '".$DB->ForSql($arFieldsLang["SECTION_NAME"], 100)."', '".$DB->ForSql($arFieldsLang["ELEMENT_NAME"], 100)."' ".
						"FROM b_iblock_type BT, b_language L ".
						"WHERE BT.ID='".$DB->ForSQL($ID)."' AND L.LID='".$DB->ForSQL($lid)."' ";
					$DB->Query($strSql);
				}
			}
		}

		return true;
	}
}

?>
