<?php
IncludeModuleLangFile(__FILE__);
/**
 * Class CIBlockType
 *
 * Fields:
 * <ul>
 * <li> ID string(50) mandatory
 * <li> SECTIONS bool optional default 'Y'
 * <li> EDIT_FILE_BEFORE string(255) optional
 * <li> EDIT_FILE_AFTER string(255) optional
 * <li> IN_RSS bool optional default 'N'
 * <li> SORT int optional default 500
 * </ul>
 */

/**
 * CIBlockType - класс для работы с типами информационных блоков.
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
	/**
	 * @var string Contains an error message in case of error in last Update or Add functions.
	 */
	public $LAST_ERROR = "";
	/**
	 * Returns list of iblock types.
	 * @example iblocktype.php
	 *
	 * @param array $arOrder Order of the list.<br>
	 * 	keys are case insensitive:
	 * 		<ul>
	 * 		<li>SORT - by SORT field.
	 * 		<li>ID - by ID field.
	 * 		<li>NAME - by language depended NAME field (must be used with LANGUAGE_ID in the filter).
	 * 		</ul>
	 * 	values are case insensitive:
	 * 		<ul>
	 * 		<li>DESC - in descending order.
	 * 		<li>ASC - in ascending order.
	 * 		</ul>
	 * @param array $arFilter Filter criteria.<br>
	 * 	keys are case insensitive:
	 * 		<ul>
	 * 		<li>ID - uses <i>like</i> operator and is <i>case insensitive</i>.
	 * 		<li>=ID - when contains string uses <i>strict equal</i> operator.
	 * 		<li>=ID - when contains array[]string uses <i>in</i> operator.
	 * 		<li>NAME - uses <i>like</i> operator and is <i>case insensitive</i>.
	 * 		<li>LANGUAGE_ID - uses <i>strict equal</i> operator and is <i>case sensitive</i>.
	 * 		</ul>
	 * 	values with zero string length are ignored.
	 * @return CDBResult
	 */
	
	/**
	* <p>Возвращает список типов информационных блоков по фильтру <i>arFilter</i> с сортировкой <i>arOrder</i>. Метод статический.</p>
	*
	*
	* @param array $arrayarOrder = Array("SORT"=>"ASC") Массив полей для сортировки, содержащий пары "поле
	* сортировки"=&gt;"направление сортировки".         <br>       		Поля
	* сортировки могут принимать значения:         <br>    <i>id</i> - код типа;    
	*     <br>    <i>sort</i> - индекс сортировки;         <br>    <i>NAME</i> - название
	* типа.         <br>
	*
	* @param array $arrayarFilter = Array() Массив вида array("фильтруемое поле"=&gt;"значение" [, ...])         <br>      
	* 		может принимать значения:         <br>    <i>ID</i> - регистронезависимый
	* по подстроке в коде типа;         <br>    <i>=ID</i> - точное совпадение с
	* кодом типа;         <br>    <i>NAME</i> - регистронезависимый по подстроке в
	* названии типа (для всех языков);         <br>       		 	 Необязательное. По
	* умолчанию записи не фильтруются.	  <br>    <i>LANGUAGE_ID</i> - код языка.       
	*  <br>
	*
	* @return CDBResult <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$db_iblock_type = CIBlockType::GetList();<br>while($ar_iblock_type = $db_iblock_type-&gt;Fetch())<br>{<br>   if($arIBType = CIBlockType::GetByIDLang($ar_iblock_type["ID"], LANG))<br>   {<br>      echo htmlspecialcharsEx($arIBType["NAME"])."&lt;br&gt;";<br>   }   <br>}<br>?&gt;<br>
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li>   <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblocktype">Поля CIBlockType</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array("SORT" => "ASC"), $arFilter = array())
	{
		/** @global CDatabase $DB */
		global $DB;
		/** @global CCacheManager $CACHE_MANAGER */
		global $CACHE_MANAGER;
		$bLang = false;
		$bNameSort = false;
		$strSqlSearch = "1=1\n";

		foreach ($arFilter as $key => $val)
		{
			if (!is_array($val) && strlen($val) <= 0)
				continue;

			switch (strtoupper($key))
			{
			case "ID":
				$strSqlSearch .= "AND UPPER(T.ID) LIKE UPPER('".$DB->ForSql($val)."')\n";
				break;

			case "=ID":
				if (is_array($val))
				{
					if (!empty($val))
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
			case "LANGUAGE_ID":
				$strSqlSearch .= "AND TL.LID = '".$DB->ForSql($val)."'\n";
				$bLang = true;
				break;
			}
		}

		$strSqlOrder = '';
		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			if ($by == "ID")
				$by = "T.ID";
			elseif ($by == "NAME")
			{
				$by = "TL.NAME";
				$bLang = true;
				$bNameSort = true;
			}
			else
				$by = "T.SORT";

			$order = strtolower($order);
			if ($order != "desc")
				$order = "asc";

			if ($strSqlOrder == '')
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ', ';

			$strSqlOrder .= $by." ".$order;
		}

		$strSql = "
			SELECT ".($bLang ? "DISTINCT" : "")." T.*".($bNameSort ? ",TL.NAME" : "")."
			FROM b_iblock_type T
			".($bLang ? " LEFT JOIN b_iblock_type_lang TL ON TL.IBLOCK_TYPE_ID = T.ID " : "")."
			WHERE ".$strSqlSearch.$strSqlOrder;

		if (CACHED_b_iblock_type === false)
		{
			$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}
		else
		{
			if ($CACHE_MANAGER->Read(CACHED_b_iblock_type, $cache_id = "b_iblock_type".md5($strSql), "b_iblock_type"))
			{
				$arResult = $CACHE_MANAGER->Get($cache_id);
			}
			else
			{
				$arResult = array();
				$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
				while ($ar = $res->Fetch())
					$arResult[] = $ar;

				$CACHE_MANAGER->Set($cache_id, $arResult);
			}
			$res = new CDBResult;
			$res->InitFromArray($arResult);
		}
		return $res;
	}

	/**
	 * Returns cached version of the iblock type information.
	 *
	 * @param string $ID
	 * @return bool|array
	 */
	protected static function _GetCache($ID)
	{
		/** @global CDatabase $DB */
		global $DB;
		/** @global CCacheManager $CACHE_MANAGER */
		global $CACHE_MANAGER;
		$ID = trim($ID);

		if ($CACHE_MANAGER->Read(CACHED_b_iblock_type, "b_iblock_type", "b_iblock_type"))
		{
			$arIBlocks = $CACHE_MANAGER->Get("b_iblock_type");
		}
		else
		{
			$arIBlocks = array();
			$rs = $DB->Query("SELECT * FROM b_iblock_type");
			while ($ar = $rs->GetNext())
			{
				$ar["_lang"] = array();
				$arIBlocks[$ar['ID']] = $ar;
			}
			$rs = $DB->Query("SELECT * FROM b_iblock_type_lang");
			while ($ar = $rs->GetNext())
			{
				if (array_key_exists($ar['IBLOCK_TYPE_ID'], $arIBlocks))
				{
					$arIBlocks[$ar['IBLOCK_TYPE_ID']]["_lang"][$ar["LID"]] = $ar;
				}
			}
			$CACHE_MANAGER->Set("b_iblock_type", $arIBlocks);
		}
		if (array_key_exists($ID, $arIBlocks))
			return $arIBlocks[$ID];
		else
			return false;
	}
	/**
	 * Returns iblock type information by ID.
	 * @see CIBlockType
	 * <code>
	 * if (CModule::IncludeModule('iblock'))
	 * &#123;
	 * 	$rsType = CIBlockType::GetByID('test');
	 * 	$arType = $rsType->GetNext();
	 * 	if ($arType)
	 * 	&#123;
	 * 		echo '&lt;pre&gt;', htmlspecialcharsEx(print_r($arType, true)), '&lt;/pre&gt;';
	 * 	&#125;
	 * &#125;
	 * </code>
	 * @param string $ID iblock type ID
	 * @return CDBResult
	 */
	
	/**
	* <p>Возвращает тип информационных блоков по его коду <i>ID</i>. Метод статический.</p>
	*
	*
	* @param string $stringID  Код типа.
	*
	* @return CDBResult <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a></li>  	    <li><a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/index.php">CIBlockType</a></li>  </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		if (CACHED_b_iblock_type === false)
		{
			return CIBlockType::GetList(array(), array(
				"=ID" => $ID,
			));
		}
		else
		{
			$arResult = CIBlockType::_GetCache($ID);
			$res = new CDBResult;
			if ($arResult !== false && isset($arResult["ID"]))
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
	/**
	 * Returns iblock type information with additional language depended messages.<br>
	 *
	 * Additional to {@link CIBlockType} language depended fields:
	 * <ul>
	 * <li>NAME - Name of the type
	 * <li>SECTION_NAME - How sections are called
	 * <li>ELEMENT_NAME - How elements are called
	 * </ul>
	 *
	 * <code>
	 * if (CModule::IncludeModule('iblock'))
	 * &#123;
	 * 	$rsTypeLang = CIBlockType::GetByIDLang('test', 'en');
	 * 	$arTypeLang = $rsTypeLang->GetNext();
	 * 	if ($arTypeLang)
	 * 	&#123;
	 * 		echo '&lt;pre&gt;', htmlspecialcharsEx(print_r($arTypeLang, true)), '&lt;/pre&gt;';
	 * 	&#125;
	 * &#125;
	 * </code>
	 * @param string $ID iblock type ID
	 * @param string $LID language ID
	 * @param bool $bFindAny Forces strict search
	 * @return array|bool
	 */
	
	/**
	* <p>Метод возвращает языковые настройки типа информационных блоков по его коду <i>ID</i>, для языка <i>LANGUAGE_ID</i>. Если <span class="syntax">для языка <i>LANGUAGE_ID</i> нет настроек и параметр <i>bFindAny</i> установлен в true, метод вернет настройки типа для языка по умолчанию.</span> Метод статический.</p>
	*
	*
	* @param string $stringID  Код типа.
	*
	* @param string $LANGUAGE_ID  Код языка.
	*
	* @param bool $bFindAny = true Возвращать настройки для языка по умолчинию или нет.
	* Необязательный. По умолчанию - возвращать.
	*
	* @return mixed <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblocktype">полей</a><a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblocktypelang">параметров</a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$db_iblock_type = CIBlockType::GetList();<br>while($ar_iblock_type = $db_iblock_type-&gt;Fetch())<br>{<br>   if($arIBType = CIBlockType::GetByIDLang($ar_iblock_type["ID"], LANG))<br>   {<br>      echo htmlspecialcharsex($arIBType["NAME"])."&lt;br&gt;";<br>   }   <br>}<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblocktype">Поля CIBlockType</a> </li>    
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblocktypelang">Языкозависимые поля
	* CIBlockType</a> </li>  </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/getbyidlang.php
	* @author Bitrix
	*/
	public static function GetByIDLang($ID, $LID, $bFindAny = true)
	{
		/** @global CDatabase $DB */
		global $DB;
		$LID = $DB->ForSQL($LID, 2);

		if (CACHED_b_iblock_type === false)
		{
			$strSql = "
				SELECT BTL.*, BT.*
				FROM b_iblock_type BT, b_iblock_type_lang BTL
				WHERE BTL.IBLOCK_TYPE_ID = '".$DB->ForSQL($ID)."'
				AND BTL.LID='".$LID."'
				AND BT.ID=BTL.IBLOCK_TYPE_ID
			";
			$res = $DB->Query($strSql);
			if ($r = $res->GetNext())
				return $r;
		}
		else
		{
			$arResult = CIBlockType::_GetCache($ID);
			if ($arResult !== false && array_key_exists($LID, $arResult["_lang"]))
			{
				$res = $arResult["_lang"][$LID];
				unset($arResult["_lang"]);
				return array_merge($res, $arResult);
			}
		}

		if (!$bFindAny)
			return false;

		$strSql = "
			SELECT BTL.*, BT.*
			FROM b_iblock_type BT, b_iblock_type_lang BTL, b_language L
			WHERE BTL.IBLOCK_TYPE_ID = '".$DB->ForSQL($ID)."'
			AND BTL.LID = L.LID
			AND BT.ID=BTL.IBLOCK_TYPE_ID
			ORDER BY L.DEF DESC, L.SORT
		";
		$res = $DB->Query($strSql);
		if ($r = $res->GetNext())
			return $r;

		return false;
	}
	/**
	 * Deletes iblock type including all iblocks.<br>
	 * When there is an error occured on iblock deletion
	 * it stops and returns false.
	 *
	 * @param string $ID iblock type ID.
	 * @return bool|CDBResult
	 */
	
	/**
	* <p>Метод удаляет тип информационных блоков по его коду <i>ID</i>. Также удаляются все информационные блоки указанного типа. Метод статический.   <br></p>
	*
	*
	* @param mixed $stringID  Код типа.
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$DB-&gt;StartTransaction();<br>if(!CIBlockType::Delete('catalog'))<br>{<br>    $DB-&gt;Rollback();<br>    echo 'Delete error!';<br>}<br>$DB-&gt;Commit();<br>?&gt;<br>
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/delete.php">CIBlock::Delete</a></li>  </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		/** @global CDatabase $DB */
		global $DB;
		/** @global CCacheManager $CACHE_MANAGER */
		global $CACHE_MANAGER;

		if (CACHED_b_iblock_type !== false)
		{
			$CACHE_MANAGER->CleanDir("b_iblock_type");
		}

		$iblocks = CIBlock::GetList(array(), array(
			"=TYPE" => $ID,
		));
		while ($iblock = $iblocks->Fetch())
		{
			if (!CIBlock::Delete($iblock["ID"]))
			{
				return false;
			}
		}

		if (!$DB->Query("DELETE FROM b_iblock_type_lang WHERE IBLOCK_TYPE_ID='".$DB->ForSql($ID)."'", true))
		{
			return false;
		}
		return $DB->Query("DELETE FROM b_iblock_type WHERE ID='".$DB->ForSql($ID)."'", true);
	}
	/**
	 * Helper internal function.<br>
	 * Checks correctness of the information. Called by Add and Update methods.
	 * List of errors returned by LAST_ERROR member variable.
	 *
	 * @param array $arFields
	 * @param bool $ID iblock type ID. false - if new one.
	 * @return bool
	 */
	public function CheckFields($arFields, $ID = false)
	{
		/** @global CDatabase $DB */
		global $DB;
		$this->LAST_ERROR = "";

		if ($ID === false)
		{
			if (!isset($arFields["ID"]) || strlen($arFields["ID"]) <= 0)
			{
				$this->LAST_ERROR .= GetMessage("IBLOCK_TYPE_BAD_ID")."<br>";
			}
			elseif (preg_match("/[^A-Za-z0-9_]/", $arFields["ID"]))
			{
				$this->LAST_ERROR .= GetMessage("IBLOCK_TYPE_ID_HAS_WRONG_CHARS")."<br>";
			}
			else
			{
				$chk = $DB->Query("SELECT 'x' FROM b_iblock_type WHERE ID='".$DB->ForSQL($arFields["ID"])."'");
				if ($chk->Fetch())
				{
					$this->LAST_ERROR .= GetMessage("IBLOCK_TYPE_DUBL_ID")."<br>";
					return false;
				}
			}
			if (empty($arFields["LANG"]) || !is_array($arFields["LANG"]))
			{
				$this->LAST_ERROR .= GetMessage("IBLOCK_TYPE_EMPTY_NAMES")."<br>";
				return false;
			}
		}

		if (is_set($arFields, "LANG") && is_array($arFields["LANG"]))
		{
			foreach ($arFields["LANG"] as $lid => $arFieldsLang)
			{
				if (strlen($arFieldsLang["NAME"]) <= 0)
				{
					$this->LAST_ERROR .= GetMessage("IBLOCK_TYPE_BAD_NAME")." ".$lid.".<br>";
				}
			}
		}

		if ($this->LAST_ERROR != "")
			return false;

		return true;
	}
	/**
	 * Creates new iblock type in the database.
	 * For arFields see {@link CIBlockType} class description.<br>
	 * In addition it may contain key "LANG" with and array of language depended parameters.<br>
	 * For example:
	 * <code>
	 * $arFields = array(
	 * 	"ID" =&gt; "test",
	 * 	"LANG" =&gt; array(
	 * 		"en" =&gt; array(
	 * 			"NAME" => "Test",
	 * 			"ELEMENT_NAME" =&gt; "Test element",
	 * 			"SECTION_NAME" =&gt; "Test section",
	 * 		),
	 * 	),
	 * );
	 * </code>
	 *
	 * @param array $arFields
	 * @return bool
	 */
	
	/**
	* <p> Метод добавляет новый тип информационных блоков. В случае ошибки в свойстве объекта LAST_ERROR будет содержаться текст ошибки. Нестатический метод.</p> <p></p> <div class="note"> <b>Примечание:</b> вызов метода без ключа <b>LANG</b> (или с пустым ключом) вызывает ошибку.</div>
	*
	*
	* @param array $arFields  Массив поле=&gt;значение... 	Содержит значения <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblocktype">полей типа информационных
	* блоков</a>. 	В элементе массива arFields["LANG"] должен содержаться
	* ассоциативный массив <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblocktypelang">языковых свойств</a>
	* типа.Ключами этого массива служат идентификаторы языков.         <br>
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$arFields = Array(<br>	'ID'=&gt;'catalog',<br>	'SECTIONS'=&gt;'Y',<br>	'IN_RSS'=&gt;'N',<br>	'SORT'=&gt;100,<br>	'LANG'=&gt;Array(<br>		'en'=&gt;Array(<br>			'NAME'=&gt;'Catalog',<br>			'SECTION_NAME'=&gt;'Sections',<br>			'ELEMENT_NAME'=&gt;'Products'<br>			)<br>		)<br>	);<br><br>$obBlocktype = new CIBlockType;<br>$DB-&gt;StartTransaction();<br>$res = $obBlocktype-&gt;Add($arFields);<br>if(!$res)<br>{<br>   $DB-&gt;Rollback();<br>   echo 'Error: '.$obBlocktype-&gt;LAST_ERROR.'&lt;br&gt;';<br>}<br>else<br>   $DB-&gt;Commit();<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/index.php">CIBlockType</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/update.php">Update()</a> </li>   <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblocktype">Поля типа информационных
	* блоков</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/add.php
	* @author Bitrix
	*/
	public function Add($arFields)
	{
		/** @global CDatabase $DB */
		global $DB;
		/** @global CCacheManager $CACHE_MANAGER */
		global $CACHE_MANAGER;

		$arFields["SECTIONS"] = isset($arFields["SECTIONS"]) && $arFields["SECTIONS"] === "Y" ? "Y" : "N";
		$arFields["IN_RSS"] = isset($arFields["IN_RSS"]) && $arFields["IN_RSS"] === "Y" ? "Y" : "N";

		if (!$this->CheckFields($arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_iblock_type", $arFields);
		$DB->Query("INSERT INTO b_iblock_type(".$arInsert[0].") VALUES(".$arInsert[1].")");

		if (isset($arFields["LANG"]))
		{
			$this->SetLang($arFields["ID"], $arFields["LANG"]);
		}

		if (CACHED_b_iblock_type !== false)
		{
			$CACHE_MANAGER->cleanDir("b_iblock_type");
		}

		return $arFields["ID"];
	}
	/**
	 * Updates iblock type in the database.
	 *
	 * $arFields is the same as for {@link CIBlockType::Add} method.
	 * @see CIBlockType::Add
	 *
	 * @param string $ID
	 * @param array $arFields
	 * @return bool
	 */
	
	/**
	* <p>Метод изменяет параметры типа информационных блоков с кодом <i>ID</i>. Нестатический метод.</p>
	*
	*
	* @param string $stringID  Код изменяемой записи.
	*
	* @param array $arFields  Массив поле=&gt;значение... 	 	Содержит значения <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblocktype">полей типа информационных
	* блоков</a>. В элементе массива arFields["LANG"] должен содержаться
	* ассоциативный массив <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblocktypelang">языковых свойств</a> типа.
	* Ключами этого массива служат идентификаторы языков.
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$arFields = Array(<br>	'SECTIONS'=&gt;'Y',<br>	'IN_RSS'=&gt;'N',<br>	'SORT'=&gt;100,<br>	'LANG'=&gt;Array(<br>		'en'=&gt;Array(<br>			'NAME'=&gt;'Catalog',<br>			'SECTION_NAME'=&gt;'Sections',<br>			'ELEMENT_NAME'=&gt;'Products'<br>			)<br>		)<br>	);<br>
	* $obBlocktype = new CIBlockType;<br>$DB-&gt;StartTransaction();<br>$res = $obBlocktype-&gt;Update('catalog', $arFields);<br>if(!$res)<br>{<br>   $DB-&gt;Rollback();<br>   echo 'Error: '.$obBlocktype-&gt;LAST_ERROR.'&lt;br&gt;';<br>}<br>else<br>   $DB-&gt;Commit();<br>?&gt;<br>
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/index.php">CIBlockType</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/add.php">Add()</a> </li>   <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblocktype">Поля типа информационных
	* блоков</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocktype/update.php
	* @author Bitrix
	*/
	public function Update($ID, $arFields)
	{
		/** @global CDatabase $DB */
		global $DB;
		/** @global CCacheManager $CACHE_MANAGER */
		global $CACHE_MANAGER;

		$arFields["SECTIONS"] = $arFields["SECTIONS"] == "Y" ? "Y" : "N";
		$arFields["IN_RSS"] = $arFields["IN_RSS"] == "Y" ? "Y" : "N";

		if (!$this->CheckFields($arFields, $ID))
			return false;

		$str_update = $DB->PrepareUpdate("b_iblock_type", $arFields);
		$DB->Query("UPDATE b_iblock_type SET ".$str_update." WHERE ID='".$DB->ForSQL($ID)."'");

		if (isset($arFields["LANG"]))
		{
			$this->SetLang($ID, $arFields["LANG"]);
		}

		if (CACHED_b_iblock_type !== false)
		{
			$CACHE_MANAGER->CleanDir("b_iblock_type");
		}
		return true;
	}
	/**
	 * Internal helper function which helps to store language depended fields into database.
	 *
	 * @param string $ID iblock type ID
	 * @param array $arLang language depended fields
	 */
	protected static function SetLang($ID, $arLang)
	{
		/** @global CDatabase $DB */
		global $DB;

		if (is_array($arLang))
		{
			$DB->Query("DELETE FROM b_iblock_type_lang WHERE IBLOCK_TYPE_ID='".$DB->ForSQL($ID)."'");
			foreach ($arLang as $lid => $arFieldsLang)
			{
				if (strlen($arFieldsLang["NAME"]) > 0 || strlen($arFieldsLang["ELEMENT_NAME"]) > 0)
				{
					$DB->Query("
						INSERT INTO b_iblock_type_lang(IBLOCK_TYPE_ID, LID, NAME, SECTION_NAME, ELEMENT_NAME)
						SELECT
							BT.ID,
							L.LID,
							'".$DB->ForSql($arFieldsLang["NAME"], 100)."',
							'".$DB->ForSql($arFieldsLang["SECTION_NAME"], 100)."',
							'".$DB->ForSql($arFieldsLang["ELEMENT_NAME"], 100)."'
						FROM
							b_iblock_type BT,
							b_language L
						WHERE
							BT.ID = '".$DB->ForSQL($ID)."'
							AND L.LID = '".$DB->ForSQL($lid)."'
					");
				}
			}
		}
	}
}
