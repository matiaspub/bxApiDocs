<?
global $IBLOCK_CACHE_PROPERTY;
$IBLOCK_CACHE_PROPERTY = Array();
IncludeModuleLangFile(__FILE__);


/**
 * <b>CIBlockProperty</b> - класс для работы со свойствами информационных разделов.
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/index.php
 * @author Bitrix
 */
class CAllIBlockProperty
{
	
	/**
	 * <p>Возвращает список свойств по фильтру <i>arFilter</i> отсортированные в порядке <i>arOrder</i>. Функция учитывает права доступа. <br></p>
	 *
	 *
	 *
	 *
	 * @param array $arrayarOrder = Array() Массив для сортировки, имеющий вид <i>by1=&gt;order1[,by2=&gt;order2 [, ..]]</i>,
	 * где:<i> by</i> - поле сортировки, может принимать значения: <ul> <li> <b>id</b> -
	 * код;</li> <li> <b>block_id</b> - код информационного блока, которому
	 * принадлежит свойство; <b>name</b> - название;</li> <li> <b>name</b> - название;</li>
	 * <li> <b>active</b> - активность;</li> <li> <b>sort</b> - индекс сортировки;</li> <li>
	 * <b>timestamp_x</b> - дата последнего изменения</li> <li> <b>searchable</b> - по
	 * признаку участия значения свойства в поиске; </li> <li> <b>filtrable</b> - по
	 * признаку участия свойства в фильре на странице списка элементов;
	 * </li> <li> <b>order</b> - порядок сортировки, может принимать значения: <ul> <li>
	 * <b>asc</b> - по возрастанию;</li> <li> <b>desc</b> - по убыванию;</li> </ul> </li> </ul>
	 *
	 *
	 *
	 * @param array $arrayarFilter = Array() Массив вида <i> array("фильтруемое поле"=&gt;"значение" [, ...])</i>. Ни один
	 * параметр фильтра не принимает массивы. <i>Фильтруемое поле</i>
	 * может принимать значения: <ul> <li> <b>ACTIVE</b> - фильтр по активности
	 * (Y|N);</li> <li> <b>ID</b> - по ID свойтсва, только число.;</li> <li> <b>CODE</b> - по
	 * мнемоническому коду (по шаблону [%_]), только строка;</li> <li> <b>NAME</b> -
	 * по названию свойства (по шаблону [%_]);</li> <li> <b>EXTERNAL_ID</b> или <b>XML_ID </b>-
	 * по внешнему коду(по шаблону [%_]). Возможно использование
	 * отрицания, указав <i>!EXTERNAL_ID</i>;</li> <li> <b>PROPERTY_TYPE</b> - по типу свойства:
	 * <ul> <li> <b>S</b> - строка</li> <li> <b>N</b> - число</li> <li> <b>L</b> - список</li> <li> <b>F</b> -
	 * файл</li> <li> <b>G</b> - привязка к разделу</li> <li> <b>E</b> - привязка к
	 * элементу</li> <li> <b>S:UserID</b> - Привязка к пользователю</li> <li> <b>S:DateTime</b> -
	 * Дата/Время</li> <li> <b>E:EList</b> - Привязка к элементам в виде списка</li> <li>
	 * <b>S:FileMan</b> - Привязка к файлу (на сервере)</li> <li> <b>S:map_yandex</b> - Привязка
	 * к Яndex.Карте</li> <li> <b>S:HTML</b> - HTML/текст</li> <li> <b>S:map_google</b> - Привязка к
	 * карте Google Maps</li> <li> <b>S:ElementXmlID</b> - Привязка к элементам по XML_ID</li> </ul>
	 * </li> <li> <b>USER_TYPE</b> - по пользовательскому типу свойства; </li> <li>
	 * <b>SEARCHABLE</b> - по признаку участия значения свойства в поиске (Y|N); </li>
	 * <li> <b>FILTRABLE</b> - по признаку участия свойства в фильре на странице
	 * списка элементов (Y|N);</li> <li> <b>VERSION</b> - по флагу хранения значений
	 * свойств элементов инфоблока;</li> <li> <b>MIN_PERMISSION</b> - фильтр по правам
	 * доступа, по умолчанию принимает <i>R</i> (уровень доступа
	 * <i>Чтение</i>);</li> <li> <b>CHECK_PERMISSIONS</b> - если установлено значение "N", то
	 * проверки прав не происходит; <br> </li> <li> <b>IBLOCK_ID</b> - по коду
	 * информационного блока, которому принадлежит свойство;</li> <li>
	 * <b>IBLOCK_CODE</b> - по мнемоническому коду информационного блока,
	 * которому принадлежит свойство;</li> <li> <b>LINK_IBLOCK_ID</b> - по коду
	 * связанного информационного блока (может быть указан для свойств
	 * типа "E" и "G");</li> <li> <b>ID</b> - по коду.</li> </ul> Необязательное. По
	 * умолчанию записи не фильтруются.
	 *
	 *
	 *
	 * @return CDBResult <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$IBLOCK_ID = 11;<br>$properties = CIBlockProperty::GetList(Array("sort"=&gt;"asc", "name"=&gt;"asc"), Array("ACTIVE"=&gt;"Y", "IBLOCK_ID"=&gt;$IBLOCK_ID));<br>while ($prop_fields = $properties-&gt;GetNext())<br>{<br>  echo $prop_fields["ID"]." - ".$prop_fields["NAME"]."&lt;br&gt;";<br>}<br>?&gt;<br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fproperty">Поля свойства</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/getlist.php
	 * @author Bitrix
	 */
	public static function GetList($arOrder=Array(), $arFilter=Array())
	{
		global $DB;

		$strSql = "
			SELECT BP.*
			FROM b_iblock_property BP
		";

		$bJoinIBlock = false;
		$arSqlSearch = "";
		foreach($arFilter as $key => $val)
		{
			$val = $DB->ForSql($val);
			$key = strtoupper($key);

			switch($key)
			{
			case "ACTIVE":
			case "SEARCHABLE":
			case "FILTRABLE":
			case "IS_REQUIRED":
			case "MULTIPLE":
			case "FILTRABLE":
				if($val=="Y" || $val=="N")
					$arSqlSearch[] = "BP.".$key." = '".$val."'";
				break;
			case "?CODE":
			case "?NAME":
				$arSqlSearch[] = CIBlock::FilterCreate("BP.".substr($key, 1), $val, "string", "E");
				break;
			case "CODE":
			case "NAME":
				$arSqlSearch[] = "UPPER(BP.".$key.") LIKE UPPER('".$val."')";
				break;
			case "XML_ID":
			case "EXTERNAL_ID":
				$arSqlSearch[] = "BP.XML_ID LIKE '".$val."'";
				break;
			case "!XML_ID":
			case "!EXTERNAL_ID":
				$arSqlSearch[] = "(BP.XML_ID IS NULL OR NOT (BP.XML_ID LIKE '".$val."'))";
				break;
			case "TMP_ID":
				$arSqlSearch[] = "BP.TMP_ID LIKE '".$val."'";
				break;
			case "!TMP_ID":
				$arSqlSearch[] = "(BP.TMP_ID IS NULL OR NOT (BP.TMP_ID LIKE '".$val."'))";
				break;
			case "PROPERTY_TYPE":
				$ar = explode(":", $val);
				if(count($ar) == 2)
				{
					$val = $ar[0];
					$arSqlSearch[] = "BP.USER_TYPE = '".$val[1]."'";
				}
				$arSqlSearch[] = "BP.".$key." = '".$val."'";
				break;
			case "USER_TYPE":
				$arSqlSearch[] = "BP.".$key." = '".$val."'";
				break;
			case "ID":
			case "IBLOCK_ID":
			case "LINK_IBLOCK_ID":
			case "VERSION":
				$arSqlSearch[] = "BP.".$key." = ".intval($val);
				break;
			case "IBLOCK_CODE":
				$arSqlSearch[] = "UPPER(B.CODE) = UPPER('".$val."')";
				$bJoinIBlock = true;
				break;
			}
		}

		if($bJoinIBlock)
			$strSql .= "
				INNER JOIN b_iblock B ON B.ID = BP.IBLOCK_ID
			";

		if(!empty($arSqlSearch))
			$strSql .= "
				WHERE ".implode("\n\t\t\t\tAND ", $arSqlSearch)."
			";

		$arSqlOrder = array();
		foreach($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order) == "ASC"? "ASC": "DESC";

			if(
				$by === "ID"
				|| $by === "BLOCK_ID"
				|| $by === "NAME"
				|| $by === "ACTIVE"
				|| $by === "SORT"
				|| $by === "FILTRABLE"
				|| $by === "SEARCHABLE"
			)
				$arSqlOrder[] = " BP.".$by." ".$order;
			else
				$arSqlOrder[] = " BP.TIMESTAMP_X ".$order;
		}

		DelDuplicateSort($arSqlOrder);

		if(!empty($arSqlOrder))
			$strSql .= "
				ORDER BY ".implode(", ", $arSqlOrder)."
			";

		$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		$res = new CIBlockPropertyResult($res);
		return $res;
	}

	///////////////////////////////////////////////////////////////////
	// Delete by property ID
	///////////////////////////////////////////////////////////////////
	
	/**
	 * <p>Функция удаляет свойство и все его значения.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код свойства.
	 *
	 *
	 *
	 * @return bool 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/delete.php
	 * @author Bitrix
	 */
	public static function Delete($ID)
	{
		global $DB, $APPLICATION;
		$ID = IntVal($ID);

		$APPLICATION->ResetException();
		foreach (GetModuleEvents("iblock", "OnBeforeIBlockPropertyDelete", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array($ID))===false)
			{
				$err = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
				if($ex = $APPLICATION->GetException())
					$err .= ': '.$ex->GetString();
				$APPLICATION->throwException($err);
				return false;
			}
		}

		foreach (GetModuleEvents("iblock", "OnIBlockPropertyDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID));

		if(!CIBlockPropertyEnum::DeleteByPropertyID($ID, true))
			return false;

		CIBlockSectionPropertyLink::DeleteByProperty($ID);

		$rsProperty = CIBlockProperty::GetByID($ID);
		$arProperty = $rsProperty->Fetch();
		if($arProperty["VERSION"] == 2)
		{
			if($arProperty["PROPERTY_TYPE"]=="F")
			{
				if($arProperty["MULTIPLE"]=="Y")
				{
					$strSql = "
						SELECT	VALUE
						FROM	b_iblock_element_prop_m".$arProperty["IBLOCK_ID"]."
						WHERE	IBLOCK_PROPERTY_ID=".$ID."
					";
				}
				else
				{
					$strSql = "
						SELECT	PROPERTY_".$ID." VALUE
						FROM	b_iblock_element_prop_s".$arProperty["IBLOCK_ID"]."
						WHERE	PROPERTY_".$ID." is not null
					";
				}
				$res = $DB->Query($strSql);
				while($arr = $res->Fetch())
					CFile::Delete($arr["VALUE"]);
			}
			if(!$DB->Query("DELETE FROM b_iblock_section_element WHERE ADDITIONAL_PROPERTY_ID=".$ID, true))
				return false;
			$strSql = "
				DELETE
				FROM b_iblock_element_prop_m".$arProperty["IBLOCK_ID"]."
				WHERE IBLOCK_PROPERTY_ID=".$ID."
			";
			if(!$DB->Query($strSql))
				return false;
			$arSql = CIBlockProperty::DropColumnSQL("b_iblock_element_prop_s".$arProperty["IBLOCK_ID"], array("PROPERTY_".$ID,"DESCRIPTION_".$ID));
			foreach($arSql as $strSql)
				if(!$DB->Query($strSql))
					return false;
		}
		else
		{
			$res = $DB->Query("SELECT EP.VALUE FROM b_iblock_property P, b_iblock_element_property EP WHERE P.ID=".$ID." AND P.ID=EP.IBLOCK_PROPERTY_ID AND P.PROPERTY_TYPE='F'");
			while($arr = $res->Fetch())
				CFile::Delete($arr["VALUE"]);
			if(!$DB->Query("DELETE FROM b_iblock_section_element WHERE ADDITIONAL_PROPERTY_ID=".$ID, true))
				return false;
			if(!$DB->Query("DELETE FROM b_iblock_element_property WHERE IBLOCK_PROPERTY_ID=".$ID, true))
				return false;
		}

		$seq = new CIBlockSequence($arProperty["IBLOCK_ID"], $ID);
		$seq->Drop();

		return $DB->Query("DELETE FROM b_iblock_property WHERE ID=".$ID, true);
	}
	///////////////////////////////////////////////////////////////////
	// Update
	///////////////////////////////////////////////////////////////////
	
	/**
	 * <p>Функция добавляет новое свойство.Отменить добавление или изменить поля свойства можно в обработчике события <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onbeforeiblockpropertyadd.php">OnBeforeIBlockPropertyAdd</a>. После добавления нового свойства вызываются обработчики события <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onafteriblockpropertyadd.php">OnAfterIBlockPropertyAdd</a>.</p>
	 *
	 *
	 *
	 *
	 * @param array $fields  Массив Array("поле"=&gt;"значение", ...). Содержит значения <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fproperty">всех полей</a> свойства. Кроме
	 * того, с помощью поля "VALUES", значением которого должен быть массив
	 * структуры array(array("VALUE"=&gt;"значение", "DEF"=&gt;"по умолчанию (Y/N)",
	 * "SORT"=&gt;"индекс сортировки"),...), можно установить варианты выбора
	 * для свойств типа "список" (подробнее смотрите метод <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/index.php">CIBlockProperty</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/updateenum.php">UpdateEnum()</a>).
	 *
	 *
	 *
	 * @return int 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$arFields = Array(<br>  "NAME" =&gt; "Цвет",<br>  "ACTIVE" =&gt; "Y",<br>  "SORT" =&gt; "100",<br>  "CODE" =&gt; "color",<br>  "PROPERTY_TYPE" =&gt; "L",<br>  "IBLOCK_ID" =&gt; 11<br>  );<br><br>$arFields["VALUES"][0] = Array(<br>  "VALUE" =&gt; "Красный",<br>  "DEF" =&gt; "N",<br>  "SORT" =&gt; "100"<br>);<br><br>$arFields["VALUES"][1] = Array(<br>  "VALUE" =&gt; "Желтый",<br>  "DEF" =&gt; "N",<br>  "SORT" =&gt; "200"<br>);<br><br>$arFields["VALUES"][2] = Array(<br>  "VALUE" =&gt; "Зеленый",<br>  "DEF" =&gt; "Y",<br>  "SORT" =&gt; "300"<br>);<br><br>$ibp = new CIBlockProperty;<br>$PropID = $ibp-&gt;Add($arFields);<br>?&gt;<br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/update.php">CIBlockProperty::Update</a> </li>
	 * <li><a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fproperty">Поля свойства</a></li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/events/onbeforeiblockpropertyadd.php">OnBeforeIBlockPropertyAdd</a></li>
	 * <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/events/onafteriblockpropertyadd.php">OnAfterIBlockPropertyAdd</a></li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/add.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB;

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";
		if(!isset($arFields["SEARCHABLE"]) || $arFields["SEARCHABLE"] != "Y")
			$arFields["SEARCHABLE"]="N";
		if(!isset($arFields["FILTRABLE"]) || $arFields["FILTRABLE"] != "Y")
			$arFields["FILTRABLE"]="N";
		if(is_set($arFields, "MULTIPLE") && $arFields["MULTIPLE"]!="Y")
			$arFields["MULTIPLE"]="N";
		if(is_set($arFields, "LIST_TYPE") && $arFields["LIST_TYPE"]!="C")
			$arFields["LIST_TYPE"]="L";

		if(!$this->CheckFields($arFields))
		{
			$Result = false;
			$arFields["RESULT_MESSAGE"] = &$this->LAST_ERROR;
		}
		else
		{
			$arFields["VERSION"] = CIBlockElement::GetIBVersion($arFields["IBLOCK_ID"]);
			unset($arFields["ID"]);
			if(isset($arFields["USER_TYPE"]))
			{
				$arUserType = CIBlockProperty::GetUserType($arFields["USER_TYPE"]);
				if(array_key_exists("ConvertToDB", $arUserType))
				{
					$arValue=array("VALUE"=>$arFields["DEFAULT_VALUE"]);
					$arValue = call_user_func_array($arUserType["ConvertToDB"], array($arFields, $arValue));
					if(is_array($arValue) && isset($arValue["VALUE"]) && strlen($arValue["VALUE"]))
						$arFields["DEFAULT_VALUE"] = $arValue["VALUE"];
					else
						$arFields["DEFAULT_VALUE"] = false;
				}
				if(array_key_exists("PrepareSettings", $arUserType))
					$arFields["USER_TYPE_SETTINGS"] = serialize(call_user_func_array($arUserType["PrepareSettings"], array($arFields)));
				else
					$arFields["USER_TYPE_SETTINGS"] = false;
			}
			else
			{
				$arFields["USER_TYPE_SETTINGS"] = false;
			}
			$ID = $DB->Add("b_iblock_property", $arFields, array('USER_TYPE_SETTINGS'), "iblock");

			if($arFields["VERSION"]==2)
			{
				if($this->_Add($ID, $arFields))
				{
					$Result = $ID;
					$arFields["ID"] = &$ID;
				}
				else
				{
					$DB->Query("DELETE FROM b_iblock_property WHERE ID = ".intval($ID));
					$this->LAST_ERROR = GetMessage("IBLOCK_PROPERTY_ADD_ERROR",array(
						"#ID#"=>$ID,
						"#CODE#"=>"[14]".$DB->GetErrorSQL(),
					));
					$Result = false;
					$arFields["RESULT_MESSAGE"] = &$this->LAST_ERROR;
				}
			}
			else
			{
				$Result = $ID;
				$arFields["ID"] = &$ID;
			}

			if($Result)
			{
				if(array_key_exists("VALUES", $arFields))
					$this->UpdateEnum($ID, $arFields["VALUES"]);

				if(CIBlock::GetArrayByID($arFields["IBLOCK_ID"], "SECTION_PROPERTY") === "Y")
				{
					if(
						!array_key_exists("SECTION_PROPERTY", $arFields)
						|| $arFields["SECTION_PROPERTY"] !== "N"
					)
						CIBlockSectionPropertyLink::Add(0, $ID, array("SMART_FILTER" => $arFields["SMART_FILTER"]));
				}
			}
		}

		$arFields["RESULT"] = &$Result;

		foreach (GetModuleEvents("iblock", "OnAfterIBlockPropertyAdd", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arFields));

		return $Result;
	}
	///////////////////////////////////////////////////////////////////
	// This one called before any Update or Add
	///////////////////////////////////////////////////////////////////
	public static function CheckFields(&$arFields, $ID=false, $bFormValidate=false)
	{
		global $DB, $APPLICATION;
		$this->LAST_ERROR = "";
		if($ID===false && strlen($arFields["NAME"])<=0)
			$this->LAST_ERROR .= GetMessage("IBLOCK_PROPERTY_BAD_NAME")."<br>";

		if(array_key_exists("CODE", $arFields) && strlen($arFields["CODE"]))
		{
			if(strpos("0123456789", substr($arFields["CODE"], 0, 1))!==false)
				$this->LAST_ERROR .= GetMessage("IBLOCK_PROPERTY_CODE_FIRST_LETTER")."<br>";
			if(preg_match("/[^A-Za-z0-9_]/",  $arFields["CODE"]))
				$this->LAST_ERROR .= GetMessage("IBLOCK_PROPERTY_WRONG_CODE")."<br>";
		}

		if(!$bFormValidate)
		{
			if($ID===false && !is_set($arFields, "IBLOCK_ID"))
				$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_BLOCK_ID")."<br>";

			if(is_set($arFields, "IBLOCK_ID"))
			{
				$arFields["IBLOCK_ID"] = intval($arFields["IBLOCK_ID"]);
				$r = CIBlock::GetList(Array(), Array("ID"=>$arFields["IBLOCK_ID"], "CHECK_PERMISSIONS" => "N"));
				if(!$r->Fetch())
					$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_BLOCK_ID")."<br>";
			}
		}

		if(isset($arFields["USER_TYPE"]))
		{
			$arUserType = CIBlockProperty::GetUserType($arFields["USER_TYPE"]);
			if(array_key_exists("CheckFields", $arUserType))
			{
				$value=array("VALUE"=>$arFields["DEFAULT_VALUE"]);
				$arError = call_user_func_array($arUserType["CheckFields"],array($arFields,$value));
				if(is_array($arError) && count($arError)>0)
					$this->LAST_ERROR .= implode("<br>", $arError)."<br>";
			}
		}

		if(!$bFormValidate)
		{
			$APPLICATION->ResetException();
			if($ID===false)
			{
				$db_events = GetModuleEvents("iblock", "OnBeforeIBlockPropertyAdd", true);
			}
			else
			{
				$arFields["ID"] = $ID;
				$db_events = GetModuleEvents("iblock", "OnBeforeIBlockPropertyUpdate", true);
			}

			foreach($db_events as $arEvent)
			{
				$bEventRes = ExecuteModuleEventEx($arEvent, array(&$arFields));
				if($bEventRes===false)
				{
					if($err = $APPLICATION->GetException())
					{
						$this->LAST_ERROR .= $err->GetString()."<br>";
					}
					else
					{
						$APPLICATION->ThrowException("Unknown error");
						$this->LAST_ERROR .= "Unknown error.<br>";
					}
					break;
				}
			}
		}

		if(strlen($this->LAST_ERROR)>0)
			return false;

		return true;
	}

	///////////////////////////////////////////////////////////////////
	// Update method
	///////////////////////////////////////////////////////////////////
	
	/**
	 * <p>Функция изменяет параметры свойства с кодом <i>ID</i>. Перед изменением параметров вызываются обработчики события <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onbeforeiblockpropertyupdate.php">OnBeforeIBlockPropertyUpdate</a> из которых можно отменить изменения или переопределить поля. А после изменения параметром вызывается событие <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onafteriblockpropertyupdate.php">OnAfterIBlockPropertyUpdate</a>.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID изменяемой записи.
	 *
	 *
	 *
	 * @param array $arFields  Массив Array("поле"=&gt;"значение", ...). Содержит значения <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fproperty">всех полей</a> изменяемого
	 * свойства. Кроме того, с помощью поля "VALUES", значением которого
	 * должен быть массив вида Array(Array("VALUE"=&gt;"значение", "DEF"=&gt;"по
	 * умолчанию (Y/N)", "SORT"=&gt;"индекс сортировки"),...), можно установить
	 * варианты выбора для свойств типа "список" (подробнее смотрите
	 * метод <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/index.php">CIBlockProperty</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/updateenum.php">UpdateEnum()</a>).
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$arFields = Array(<br>  "NAME" =&gt; "Цвет",<br>  "ACTIVE" =&gt; "Y",<br>  "SORT" =&gt; "100",<br>  "CODE" =&gt; "color",<br>  "PROPERTY_TYPE" =&gt; "L",<br>  "IBLOCK_ID" =&gt; 11<br>  );<br><br>$arFields["VALUES"][0] = Array(<br>  "VALUE" =&gt; "Красный",<br>  "DEF" =&gt; "N",<br>  "SORT" =&gt; "100"<br>}<br><br>$arFields["VALUES"][1] = Array(<br>  "VALUE" =&gt; "Желтый",<br>  "DEF" =&gt; "N",<br>  "SORT" =&gt; "200"<br>}<br><br>$arFields["VALUES"][2] = Array(<br>  "VALUE" =&gt; "Зеленый",<br>  "DEF" =&gt; "Y",<br>  "SORT" =&gt; "300"<br>}<br><br>$ibp = new CIBlockProperty;<br>if(!$ibp-&gt;Update($ID, $arFields))<br>	echo $ibp-&gt;LAST_ERROR;<br>?
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/add.php">CIBlockProperty::Add</a>
	 * </li> <li><a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fproperty">Поля свойства</a></li>
	 * <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/events/onbeforeiblockpropertyupdate.php">OnBeforeIBlockPropertyUpdate</a></li>
	 * <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/events/onafteriblockpropertyupdate.php">OnAfterIBlockPropertyUpdate</a></li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/update.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields)
	{
		global $DB;
		$ID = IntVal($ID);

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";
		if(is_set($arFields, "SEARCHABLE") && $arFields["SEARCHABLE"]!="Y")
			$arFields["SEARCHABLE"]="N";
		if(is_set($arFields, "FILTRABLE") && $arFields["FILTRABLE"]!="Y")
			$arFields["FILTRABLE"]="N";
		if(is_set($arFields, "MULTIPLE") && $arFields["MULTIPLE"]!="Y")
			$arFields["MULTIPLE"]="N";
		if(is_set($arFields, "LIST_TYPE") && $arFields["LIST_TYPE"]!="C")
			$arFields["LIST_TYPE"]="L";

		if(!$this->CheckFields($arFields, $ID))
		{
			$Result = false;
			$arFields["RESULT_MESSAGE"] = &$this->LAST_ERROR;
		}
		elseif(!$this->_Update($ID, $arFields))
		{
			$Result = false;
			$arFields["RESULT_MESSAGE"] = &$this->LAST_ERROR;
		}
		else
		{
			if(isset($arFields["USER_TYPE"]))
			{
				$arUserType = CIBlockProperty::GetUserType($arFields["USER_TYPE"]);
				if(array_key_exists("ConvertToDB", $arUserType))
				{
					$arValue=array("VALUE"=>$arFields["DEFAULT_VALUE"]);
					$arValue = call_user_func_array($arUserType["ConvertToDB"], array($arFields, $arValue));
					if(is_array($arValue) && isset($arValue["VALUE"]) && strlen($arValue["VALUE"]))
						$arFields["DEFAULT_VALUE"] = $arValue["VALUE"];
					else
						$arFields["DEFAULT_VALUE"] = false;
				}

				if(array_key_exists("PrepareSettings", $arUserType))
					$arFields["USER_TYPE_SETTINGS"] = serialize(call_user_func_array($arUserType["PrepareSettings"], array($arFields)));
				else
					$arFields["USER_TYPE_SETTINGS"] = false;
			}
			else
			{
				$arFields["USER_TYPE_SETTINGS"] = false;
			}

			unset($arFields["ID"]);
			unset($arFields["VERSION"]);
			unset($arFields["TIMESTAMP_X"]);

			$strUpdate = $DB->PrepareUpdate("b_iblock_property", $arFields);
			if(strlen($strUpdate) > 0)
			{
				$strSql = "UPDATE b_iblock_property SET ".$strUpdate." WHERE ID=".$ID;
				$DB->QueryBind($strSql, array("USER_TYPE_SETTINGS"=>$arFields["USER_TYPE_SETTINGS"]));
			}

			if(is_set($arFields, "VALUES"))
				$this->UpdateEnum($ID, $arFields["VALUES"]);

			if(
				array_key_exists("IBLOCK_ID", $arFields)
				&& CIBlock::GetArrayByID($arFields["IBLOCK_ID"], "SECTION_PROPERTY") === "Y"
			)
			{
				CIBlockSectionPropertyLink::Delete(0, $ID);
				if(
					!array_key_exists("SECTION_PROPERTY", $arFields)
					|| $arFields["SECTION_PROPERTY"] !== "N"
				)
					CIBlockSectionPropertyLink::Add(0, $ID, array("SMART_FILTER" => $arFields["SMART_FILTER"]));
			}

			global $BX_IBLOCK_PROP_CACHE;
			if(is_set($arFields, "IBLOCK_ID"))
				UnSet($BX_IBLOCK_PROP_CACHE[$arFields["IBLOCK_ID"]]);
			else
				$BX_IBLOCK_PROP_CACHE = Array();

			$Result = true;
		}

		$arFields["ID"] = $ID;
		$arFields["RESULT"] = &$Result;

		foreach (GetModuleEvents("iblock", "OnAfterIBlockPropertyUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arFields));

		return $Result;
	}


	///////////////////////////////////////////////////////////////////
	// Get property information by ID
	///////////////////////////////////////////////////////////////////
	
	/**
	 * <p>Возвращает свойство по его коду <i>ID</i>.</p>
	 *
	 *
	 *
	 *
	 * @param mixed $ID  Числовой или мнемонический код свойства.
	 *
	 *
	 *
	 * @param int $IBLOCK_ID = false Код информационного блока. Используется для уточнения свойства
	 * если его <i>ID</i> задано мнемоническим кодом и с таким мнемоническим
	 * кодом свойства присутствует в нескольких информационных блоках.
	 *
	 *
	 *
	 * @param string $IBLOCK_CODE = false Мнемонический код информационного блока. Используется для
	 * уточнения свойства если его <i>ID</i> задано мнемоническим кодом и с
	 * таким мнемоническим кодом свойства присутствует в нескольких
	 * информационных блоках.
	 *
	 *
	 *
	 * @return CDBResult <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a><h4>Примечание</h4><p>Если
	 * заданы оба значения <i>IBLOCK_ID</i> и <i>IBLOCK_CODE</i>, то будет производиться
	 * попытка найти свойство в одном из них (логика "или").</p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $res = CIBlockProperty::GetByID("SRC", false, "company_news");<br>if($ar_res = $res-&gt;GetNext())
	 *   echo $ar_res['NAME'];
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fproperty">Поля свойства</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/getbyid.php
	 * @author Bitrix
	 */
	public static function GetByID($ID, $IBLOCK_ID=false, $IBLOCK_CODE=false)
	{
		global $DB;
		$cond = "";
		if($IBLOCK_CODE && $IBLOCK_ID)
			$cond = " AND (B.ID = ".IntVal($IBLOCK_ID)." OR B.CODE = '".$DB->ForSql($IBLOCK_CODE)."') ";
		elseif($IBLOCK_CODE)
			$cond = " AND B.CODE = '".$DB->ForSql($IBLOCK_CODE)."' ";
		elseif($IBLOCK_ID)
			$cond = " AND B.ID = ".IntVal($IBLOCK_ID)." ";
		else
			$cond = "";

		$strSql =
			"SELECT BP.* ".
			"FROM b_iblock_property BP, b_iblock B ".
			"WHERE BP.IBLOCK_ID=B.ID ".
			$cond.
			(is_numeric(substr($ID, 0, 1))
			?
				"	AND BP.ID=".IntVal($ID)
			:
				"	AND UPPER(BP.CODE)=UPPER('".$DB->ForSql($ID)."') "
			);

		$res = new CIBlockPropertyResult($DB->Query($strSql));
		return $res;
	}

	public static function GetPropertyArray($ID, $IBLOCK_ID, $bCached=true)
	{
		global $DB;

		$block_id = false;
		$block_code = false;
		if(is_array($IBLOCK_ID))
		{
			foreach($IBLOCK_ID as $k=>$v)
			{
				if(is_numeric($v))
				{
					if($block_id)
						$block_id .= ", ";
					else
						$block_id = "";

					$block_id .= IntVal($v);
				}
				elseif(strlen($v)>0)
				{
					if($block_code)
						$block_code .= ", ";
					else
						$block_code = "";

					$block_code .= "'".$DB->ForSQL($v, 200)."'";
				}
			}
		}
		elseif(is_numeric($IBLOCK_ID))
			$block_id = IntVal($IBLOCK_ID);
		elseif(strlen($IBLOCK_ID)>0)
			$block_code = "'".$DB->ForSQL($IBLOCK_ID, 200)."'";

		global $IBLOCK_CACHE_PROPERTY;
		if($bCached && is_set($IBLOCK_CACHE_PROPERTY, $ID."|".$block_id."|".$block_code))
			return $IBLOCK_CACHE_PROPERTY[$ID."|".$block_id."|".$block_code];

		if($block_code && $block_id)
			$cond = " AND (B.ID IN (".$block_id.") OR B.CODE IN (".$block_code.")) ";
		elseif($block_code)
			$cond = " AND B.CODE IN (".$block_code.") ";
		elseif($block_id)
			$cond = " AND B.ID IN (".$block_id.") ";
		else
			$cond = "";

		$upperID = strtoupper($ID);

		$strSql = "
			SELECT BP.*
			FROM
				b_iblock_property BP
				,b_iblock B
			WHERE BP.IBLOCK_ID=B.ID
			".$cond."
			".(substr($upperID, -6)=='_VALUE'?
				(is_numeric(substr($ID, 0, 1))?
					"AND BP.ID=".IntVal($ID)
				:
					"AND ((UPPER(BP.CODE)='".$DB->ForSql($upperID)."' AND BP.PROPERTY_TYPE!='L') OR (UPPER(BP.CODE)='".$DB->ForSql(substr($upperID, 0, -6))."' AND BP.PROPERTY_TYPE='L'))"
				)
			:
				(is_numeric(substr($ID, 0, 1))?
					"AND BP.ID=".IntVal($ID)
				:
					"AND UPPER(BP.CODE)='".$DB->ForSql($upperID)."'"
				)
			);

		$res = $DB->Query($strSql);
		if($arr = $res->Fetch())
		{
			$arr["ORIG_ID"] = $arr["ID"];    //it saves original (digital) id
			$arr["IS_CODE_UNIQUE"] = true;   //boolean check for global code uniquess
			$arr["IS_VERSION_MIXED"] = false;//boolean check if varios versions of ibformation block properties
			while($arr2 = $res->Fetch())
			{
				$arr["IS_CODE_UNIQUE"] = false;
				if($arr["VERSION"] != $arr2["VERSION"])
					$arr["IS_VERSION_MIXED"] = true;
			}

			if(
				substr($upperID, -6) == '_VALUE'
				&& $arr["PROPERTY_TYPE"] == "L"
				&& strtoupper($arr["CODE"]) == substr($upperID, 0, -6)
			)
				$arr["ID"] = substr($ID, 0, -6);
			else
				$arr["ID"] = $ID;
		}

		$IBLOCK_CACHE_PROPERTY[$ID."|".$block_id."|".$block_code] = $arr;
		return $arr;
	}

	
	/**
	 * <p>Возвращает варианты для значения свойства <i>PROP_ID </i>типа "список" отсортированные в порядке <i>arOrder</i> и отфильтрованные по <i>arFilter</i>.</p>
	 *
	 *
	 *
	 *
	 * @param mixed $PROP_ID  Числовой или мнемонический код свойства.
	 *
	 *
	 *
	 * @param array $arOrder = Array("SORT"=>"asc") Массив для сортировки, имеющий вид <i>by1</i>=&gt;<i>order1</i>[,
	 * <i>by2</i>=&gt;<i>order2</i> [, ..]], где <i> <br> by</i> - поле сортировки, может
	 * принимать значения: <br>     <i>id</i> - код; <br>     <i>value</i> - значение, <br>
	 *     <i>sort</i> - поле сортировки<i>, <br></i>    <i>external_id</i> - внешний код,
	 * <br><i>order</i> - порядок сортировки, может принимать значения: <br>
	 *     <i>asc</i> - по возрастанию; <br>     <i>desc</i> - по убыванию;
	 *
	 *
	 *
	 * @param array $arFilter = Array() Массив вида array("фильтруемое поле"=&gt;"значение" [, ...]) <br>
	 * "фильтруемое поле" может принимать значения: <br>     <i>VALUE</i> - по
	 * значению варианта свойства (можно искать по шаблону [%_]); <br>    
	 * <i>EXTERNAL_ID</i> - по значению внешнего кода варианта свойства (можно
	 * искать по шаблону [%_]); <br>     <i>IBLOCK_ID</i> - по коду информационного
	 * блока, которому принадлежит свойство; <br>     <i>ID</i> - по коду
	 * значения варианта свойства; <br> Необязательное. По умолчанию
	 * записи не фильтруются.
	 *
	 *
	 *
	 * @return CDBResult <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a><a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblockpropertyenum">полей вариантов
	 * свойства</a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$db_enum_list = CIBlockProperty::GetPropertyEnum("IMPORTANT_NEWS", Array(), Array("IBLOCK_ID"=&gt;$BID, "VALUE"=&gt;"Yes"));<br>if($ar_enum_list = $db_enum_list-&gt;GetNext())<br>{<br>  $db_important_news = CIBlockElement::GetList(Array(), Array("IBLOCK_ID"=&gt;$BID, "PROPERTY"=&gt;array("IMPORTANT_NEWS"=&gt;$ar_enum_list["ID"])));<br>}<br>?&gt;<br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblockpropertyenum">Поля вариантов
	 * свойства</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockpropertyenum/index.php">CIBlockPropertyEnum</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockpropertyenum/getlist.php">GetList() </a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/getpropertyenum.php
	 * @author Bitrix
	 */
	public static function GetPropertyEnum($PROP_ID, $arOrder = array("SORT"=>"asc"), $arFilter = array())
	{
		global $DB;

		$strSqlSearch = "";
		if(is_array($arFilter))
		{
			foreach($arFilter as $key => $val)
			{
				$key = strtoupper($key);
				switch($key)
				{
				case "ID":
					$strSqlSearch .= "AND (BPE.ID=".intval($val).")\n";
					break;
				case "IBLOCK_ID":
					$strSqlSearch .= "AND (BP.IBLOCK_ID=".intval($val).")\n";
					break;
				case "VALUE":
					$strSqlSearch .= "AND (BPE.VALUE LIKE '".$DB->ForSql($val)."')\n";
					break;
				case "EXTERNAL_ID":
				case "XML_ID":
					$strSqlSearch .= "AND (BPE.XML_ID LIKE '".$DB->ForSql($val)."')\n";
					break;
				}
			}
		}

		$arSqlOrder = array();
		if(is_array($arOrder))
		{
			foreach($arOrder as $by => $order)
			{
				$by = strtolower($by);
				$order = strtolower($order);
				if ($order!="asc")
					$order = "desc";

				if ($by == "value")
					$arSqlOrder["BPE.VALUE"] = "BPE.VALUE ".$order;
				elseif ($by == "id")
					$arSqlOrder["BPE.ID"] = "BPE.ID ".$order;
				elseif ($by == "external_id")
					$arSqlOrder["BPE.XML_ID"] = "BPE.XML_ID ".$order;
				elseif ($by == "xml_id")
					$arSqlOrder["BPE.XML_ID"] = "BPE.XML_ID ".$order;
				else
					$arSqlOrder["BPE.SORT"] = "BPE.SORT ".$order;
			}
		}

		if(empty($arSqlOrder))
			$strSqlOrder = "";
		else
			$strSqlOrder = " ORDER BY ".implode(", ", $arSqlOrder);

		$res = $DB->Query($s = "
			SELECT BPE.*, BPE.XML_ID as EXTERNAL_ID
			FROM
				b_iblock_property_enum BPE
				INNER JOIN b_iblock_property BP ON BP.ID = BPE.PROPERTY_ID
			WHERE
			".(
				is_numeric(substr($PROP_ID, 0, 1))?
				"BP.ID = ".intval($PROP_ID):
				"BP.CODE = '".$DB->ForSql($PROP_ID)."'"
			)."
			".$strSqlSearch."
			".$strSqlOrder."
		");

		return $res;
	}

	
	/**
	 * <p>Функция устанавливает значения перечислений свойства типа "список".</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код свойства.
	 *
	 *
	 *
	 * @param array $arVALUES  Массив всех значений в формате Array("код существующего
	 * значения"=&gt;"массив полей значения", ..., "массив полей нового
	 * значения", ...). Где массив полей имеет вид: Array("VALUE"=&gt;"значение"[,
	 * "SORT"=&gt;"порядок сортировки"][, "DEF"=&gt;"является значением по
	 * умолчанию (Y|N)"][, "XML_ID"=&gt;"внешний код"]).
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $cnt = 0;<br>$ar_all_values = Array();<br>$db_enum_list = CIBlockProperty::GetPropertyEnum($PROP_ID, Array('SORT'=&gt;'ASC'));<br>while($ar_enum = $db_enum_list-&gt;Fetch())<br>{<br>	$cnt++;<br>	$ar_all_values[$ar_enum['ID']] = Array('SORT'=&gt;$cnt, 'VALUE'=&gt;$ar_enum['VALUE']);<br>}<br>$CIBlockProp = new CIBlockProperty;
	 * $CIBlockProp-&gt;UpdateEnum($PROP_ID, $ar_all_values); ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblockpropertyenum">Поля значений
	 * свойства типа "список</a><a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/ceventmessage/index.php#flds">" </a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockpropertyenum/index.php">CIBlockPropetyEnum</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockpropertyenum/update.php">Update()</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockpropertyenum/index.php">CIBlockPropetyEnum</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockpropertyenum/add.php">Add()</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/updateenum.php
	 * @author Bitrix
	 */
	public static function UpdateEnum($ID, $arVALUES, $bForceDelete = true)
	{
		global $DB, $CACHE_MANAGER;
		$ID = IntVal($ID);

		if(!is_array($arVALUES) || (empty($arVALUES) && $bForceDelete))
		{
			CIBlockPropertyEnum::DeleteByPropertyID($ID);
			return true;
		}

		$ar_XML_ID = array();
		$db_res = $this->GetPropertyEnum($ID);
		while($res = $db_res->Fetch())
		{
			$ar_XML_ID[$res["XML_ID"]] = $res["ID"];
		}

		$sqlWhere = "";
		if(!$bForceDelete)
		{
			$rsProp = CIBlockProperty::GetByID($ID);
			if($arProp = $rsProp->Fetch())
			{
				if($arProp["VERSION"] == 1)
					$sqlWhere = "AND NOT EXISTS (
						SELECT *
						FROM b_iblock_element_property
						WHERE b_iblock_element_property.IBLOCK_PROPERTY_ID = b_iblock_property_enum.PROPERTY_ID
						AND b_iblock_element_property.VALUE_ENUM = b_iblock_property_enum.ID
					)";
				elseif($arProp["MULTIPLE"] == "N")
					$sqlWhere = "AND NOT EXISTS (
						SELECT *
						FROM b_iblock_element_prop_s".$arProp["IBLOCK_ID"]."
						WHERE b_iblock_element_prop_s".$arProp["IBLOCK_ID"].".PROPERTY_".$arProp["ID"]." = b_iblock_property_enum.ID
					)";
				else
					$sqlWhere = "AND NOT EXISTS (
						SELECT *
						FROM b_iblock_element_prop_m".$arProp["IBLOCK_ID"]."
						WHERE b_iblock_element_prop_m".$arProp["IBLOCK_ID"].".IBLOCK_PROPERTY_ID = b_iblock_property_enum.PROPERTY_ID
						AND b_iblock_element_prop_m".$arProp["IBLOCK_ID"].".VALUE_ENUM = b_iblock_property_enum.ID
					)";
			}
		}

		$db_res = $this->GetPropertyEnum($ID);
		while($res = $db_res->Fetch())
		{
			$VALUE = $arVALUES[$res["ID"]];
			$VAL = is_array($VALUE)? $VALUE["VALUE"]: $VALUE;
			UnSet($arVALUES[$res["ID"]]);

			if(strlen($VAL)<=0)
			{
				unset($ar_XML_ID[$res["XML_ID"]]);

				$strSql = "
					DELETE FROM b_iblock_property_enum
					WHERE ID=".$res["ID"]."
					".$sqlWhere."
				";

				$DB->Query($strSql);
			}
			else
			{
				$DEF = "";
				$SORT = 0;
				$XML_ID = "";
				if(is_array($VALUE))
				{
					if(array_key_exists("DEF", $VALUE))
						$DEF = $VALUE["DEF"]=="Y"? "Y": "N";

					if(array_key_exists("SORT", $VALUE))
						$SORT = intval($VALUE["SORT"]);
					if($SORT < 0)
						$SORT = 0;

					if(array_key_exists("XML_ID", $VALUE) && strlen($VALUE["XML_ID"]))
						$XML_ID = substr($VALUE["XML_ID"], 0, 200);
					elseif(array_key_exists("EXTERNAL_ID", $VALUE) && strlen($VALUE["EXTERNAL_ID"]))
						$XML_ID = substr($VALUE["EXTERNAL_ID"], 0, 200);
				}

				if($XML_ID)
				{
					unset($ar_XML_ID[$res["XML_ID"]]);
					if(array_key_exists($XML_ID, $ar_XML_ID))
						$XML_ID = md5(uniqid(""));
					$ar_XML_ID[$XML_ID] = $res["ID"];
				}

				$strSql = "
					UPDATE b_iblock_property_enum
					SET
						".($DEF? " DEF = '".$DEF."', ":"")."
						".($SORT? " SORT = ".$SORT.", ":"")."
						".($XML_ID? " XML_ID = '".$DB->ForSQL($XML_ID, 200)."', ":"")."
						VALUE = '".$DB->ForSQL($VAL, 255)."'
					WHERE
						ID = ".$res["ID"]."
				";

				$DB->Query($strSql);
			}
		}

		foreach($arVALUES as $id => $VALUE)
		{
			$VAL = is_array($VALUE)? $VALUE["VALUE"]: $VALUE;
			if(strlen($id) > 0 && strlen($VAL) > 0)
			{
				$DEF = "";
				$SORT = 0;
				$XML_ID = "";
				if(is_array($VALUE))
				{
					if(array_key_exists("DEF", $VALUE))
						$DEF = $VALUE["DEF"]=="Y"? "Y": "N";

					if(array_key_exists("SORT", $VALUE))
						$SORT = intval($VALUE["SORT"]);
					if($SORT < 0)
						$SORT = 0;

					if(array_key_exists("XML_ID", $VALUE) && strlen($VALUE["XML_ID"]))
						$XML_ID = substr($VALUE["XML_ID"], 0, 200);
					elseif(array_key_exists("EXTERNAL_ID", $VALUE) && strlen($VALUE["EXTERNAL_ID"]))
						$XML_ID = substr($VALUE["EXTERNAL_ID"], 0, 200);
				}

				if($XML_ID)
				{
					if(array_key_exists($XML_ID, $ar_XML_ID))
						$XML_ID = md5(uniqid(""));
					$ar_XML_ID[$XML_ID] = 0;
				}
				else
				{
					$XML_ID = md5(uniqid(""));
				}

				$strSql = "
					INSERT INTO b_iblock_property_enum
					(
						PROPERTY_ID
						".($DEF? ",DEF": "")."
						".($SORT? ",SORT": "")."
						,VALUE
						,XML_ID
					) VALUES (
						".$ID."
						".($DEF? ",'".$DEF."'": "")."
						".($SORT? ",".$SORT."": "")."
						,'".$DB->ForSQL($VAL, 255)."'
						,'".$DB->ForSQL($XML_ID, 200)."'
					)
				";
				$DB->Query($strSql);
			}
		}

		if(CACHED_b_iblock_property_enum !== false)
			$CACHE_MANAGER->CleanDir("b_iblock_property_enum");

		if (defined("BX_COMP_MANAGED_CACHE"))
			$CACHE_MANAGER->ClearByTag("iblock_property_enum_".$ID);
	}

	
	/**
	 * <p>Функция возвращает описание пользовательского типа. <br></p> <p><b>Примечание</b>: если параметр USER_TYPE не задан, то функция вернет массив всех пользовательских типов свойств модуля инфоблоков.</p>
	 *
	 *
	 *
	 *
	 * @param string $USER_TYPE = false Идентификатор пользовательского типа <br>
	 *
	 *
	 *
	 * @return array <p>Массив описывающий пользовательский тип.</p>
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/user_properties/index.php">Пользовательские
	 * свойства</a></li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/user_properties/GetUserTypeDescription.php">GetUserTypeDescription</a></li>
	 * </ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/GetUserType.php
	 * @author Bitrix
	 */
	public static function GetUserType($USER_TYPE = false)
	{
		static $CACHE = null;

		if(!isset($CACHE))
		{
			$CACHE = array();
			foreach(GetModuleEvents("iblock", "OnIBlockPropertyBuildList", true) as $arEvent)
			{
				$res = ExecuteModuleEventEx($arEvent);
				$CACHE[$res["USER_TYPE"]] = $res;
			}
		}

		if($USER_TYPE !== false)
		{
			if(array_key_exists($USER_TYPE, $CACHE))
				return $CACHE[$USER_TYPE];
			else
				return array();
		}
		else
		{
			return $CACHE;
		}
	}

	public static function FormatUpdateError($ID, $CODE)
	{
		return GetMessage("IBLOCK_PROPERTY_CHANGE_ERROR",array("#ID#"=>$ID,"#CODE#"=>$CODE));
	}

	public static function FormatNotFoundError($ID)
	{
		return GetMessage("IBLOCK_PROPERTY_NOT_FOUND",array("#ID#"=>$ID));
	}

	function _DateTime_GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE" => "S",
			"USER_TYPE" => "DateTime",
			"DESCRIPTION" => GetMessage("IBLOCK_PROP_DATETIME_DESC"),
			//optional handlers
			"GetPublicViewHTML" => array("CIBlockPropertyDateTime","GetPublicViewHTML"),
			"GetPublicEditHTML" => array("CIBlockPropertyDateTime","GetPublicEditHTML"),
			"GetAdminListViewHTML" => array("CIBlockPropertyDateTime","GetAdminListViewHTML"),
			"GetPropertyFieldHtml" => array("CIBlockPropertyDateTime","GetPropertyFieldHtml"),
			"CheckFields" => array("CIBlockPropertyDateTime","CheckFields"),
			"ConvertToDB" => array("CIBlockPropertyDateTime","ConvertToDB"),
			"ConvertFromDB" => array("CIBlockPropertyDateTime","ConvertFromDB"),
			"GetSettingsHTML" => array("CIBlockPropertyDateTime","GetSettingsHTML"),
			"GetAdminFilterHTML" => array("CIBlockPropertyDateTime","GetAdminFilterHTML"),
			"GetPublicFilterHTML" => array("CIBlockPropertyDateTime","GetPublicFilterHTML"),
			"AddFilterFields" => array("CIBlockPropertyDateTime","AddFilterFields"),
		);
	}

	function _XmlID_GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE"		=>"S",
			"USER_TYPE"		=>"ElementXmlID",
			"DESCRIPTION"		=>GetMessage("IBLOCK_PROP_XMLID_DESC"),
			"GetPublicViewHTML"	=>array("CIBlockPropertyXmlID","GetPublicViewHTML"),
			"GetAdminListViewHTML"	=>array("CIBlockPropertyXmlID","GetAdminListViewHTML"),
			"GetPropertyFieldHtml"	=>array("CIBlockPropertyXmlID","GetPropertyFieldHtml"),
			"GetSettingsHTML"	=>array("CIBlockPropertyXmlID","GetSettingsHTML"),
		);
	}

	function _FileMan_GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE"		=>"S",
			"USER_TYPE"		=>"FileMan",
			"DESCRIPTION"		=>GetMessage("IBLOCK_PROP_FILEMAN_DESC"),
			"GetPropertyFieldHtml"	=>array("CIBlockPropertyFileMan","GetPropertyFieldHtml"),
			"GetPropertyFieldHtmlMulty" => array('CIBlockPropertyFileMan','GetPropertyFieldHtmlMulty'),
			"ConvertToDB"		=>array("CIBlockPropertyFileMan","ConvertToDB"),
			"ConvertFromDB"		=>array("CIBlockPropertyFileMan","ConvertFromDB"),
			"GetSettingsHTML" => array("CIBlockPropertyFileMan","GetSettingsHTML"),
		);
	}

	function _HTML_GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE" => "S",
			"USER_TYPE" => "HTML",
			"DESCRIPTION" => GetMessage("IBLOCK_PROP_HTML_DESC"),
			"GetPublicViewHTML" => array("CIBlockPropertyHTML","GetPublicViewHTML"),
			"GetPublicEditHTML" => array("CIBlockPropertyHTML","GetPublicEditHTML"),
			"GetAdminListViewHTML" => array("CIBlockPropertyHTML","GetAdminListViewHTML"),
			"GetPropertyFieldHtml" => array("CIBlockPropertyHTML","GetPropertyFieldHtml"),
			"ConvertToDB" => array("CIBlockPropertyHTML","ConvertToDB"),
			"ConvertFromDB" => array("CIBlockPropertyHTML","ConvertFromDB"),
			"GetLength" =>array("CIBlockPropertyHTML","GetLength"),
			"PrepareSettings" =>array("CIBlockPropertyHTML","PrepareSettings"),
			"GetSettingsHTML" =>array("CIBlockPropertyHTML","GetSettingsHTML"),
		);
	}

	function _ElementList_GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE" => "E",
			"USER_TYPE" => "EList",
			"DESCRIPTION" => GetMessage("IBLOCK_PROP_ELIST_DESC"),
			"GetPropertyFieldHtml" => array("CIBlockPropertyElementList","GetPropertyFieldHtml"),
			"GetPropertyFieldHtmlMulty" => array("CIBlockPropertyElementList","GetPropertyFieldHtmlMulty"),
			"GetPublicEditHTML" => array("CIBlockPropertyElementList","GetPropertyFieldHtml"),
			"GetPublicEditHTMLMulty" => array("CIBlockPropertyElementList","GetPropertyFieldHtmlMulty"),
			"GetPublicViewHTML" => array("CIBlockPropertyElementList", "GetPublicViewHTML"),
			"GetAdminFilterHTML" => array("CIBlockPropertyElementList","GetAdminFilterHTML"),
			"PrepareSettings" =>array("CIBlockPropertyElementList","PrepareSettings"),
			"GetSettingsHTML" =>array("CIBlockPropertyElementList","GetSettingsHTML"),
		);
	}

	function _Sequence_GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE" => "N",
			"USER_TYPE" => "Sequence",
			"DESCRIPTION" => GetMessage("IBLOCK_PROP_SEQUENCE_DESC"),
			"GetPropertyFieldHtml" => array("CIBlockPropertySequence","GetPropertyFieldHtml"),
			"GetPublicEditHTML" => array("CIBlockPropertySequence","GetPropertyFieldHtml"),
			"PrepareSettings" =>array("CIBlockPropertySequence","PrepareSettings"),
			"GetSettingsHTML" =>array("CIBlockPropertySequence","GetSettingsHTML"),
			"GetAdminFilterHTML" => array("CIBlockPropertySequence","GetPublicFilterHTML"),
			"GetPublicFilterHTML" => array("CIBlockPropertySequence","GetPublicFilterHTML"),
			"AddFilterFields" => array("CIBlockPropertySequence","AddFilterFields"),
		);
	}

	function _ElementAutoComplete_GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE" => "E",
			"USER_TYPE" => "EAutocomplete",
			"DESCRIPTION" => GetMessage("IBLOCK_PROP_EAUTOCOMPLETE_DESC"),
			"GetPropertyFieldHtml" => array("CIBlockPropertyElementAutoComplete", "GetPropertyFieldHtml"),
			"GetPropertyFieldHtmlMulty" => array('CIBlockPropertyElementAutoComplete','GetPropertyFieldHtmlMulty'),
			"GetAdminListViewHTML" => array("CIBlockPropertyElementAutoComplete","GetAdminListViewHTML"),
			"GetPublicViewHTML" => array("CIBlockPropertyElementAutoComplete", "GetPublicViewHTML"),
			"GetAdminFilterHTML" => array('CIBlockPropertyElementAutoComplete','GetAdminFilterHTML'),
			"GetSettingsHTML" => array('CIBlockPropertyElementAutoComplete','GetSettingsHTML'),
			"PrepareSettings" => array('CIBlockPropertyElementAutoComplete','PrepareSettings'),
			"AddFilterFields" => array('CIBlockPropertyElementAutoComplete','AddFilterFields'),
		);
	}

	function _SKU_GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE" => "E",
			"USER_TYPE" =>"SKU",
			"DESCRIPTION" => GetMessage('IBLOCK_PROP_SKU_DESC'),
			"GetPropertyFieldHtml" => array("CIBlockPropertySKU", "GetPropertyFieldHtml"),
			"GetPublicViewHTML" => array("CIBlockPropertySKU", "GetPublicViewHTML"),
			"GetAdminListViewHTML" => array("CIBlockPropertySKU","GetAdminListViewHTML"),
			"GetAdminFilterHTML" => array('CIBlockPropertySKU','GetAdminFilterHTML'),
			"GetSettingsHTML" => array('CIBlockPropertySKU','GetSettingsHTML'),
			"PrepareSettings" => array('CIBlockPropertySKU','PrepareSettings'),
			"AddFilterFields" => array('CIBlockPropertySKU','AddFilterFields'),
			//"GetOffersFieldHtml" => array('CIBlockPropertySKU','GetOffersFieldHtml'),
		);
	}
}
?>
