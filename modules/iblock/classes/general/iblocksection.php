<?
IncludeModuleLangFile(__FILE__);


/**
 * <b>CIBlockSection</b> - класс для работы с разделами (группами) информационных блоков.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/index.php
 * @author Bitrix
 */
class CAllIBlockSection
{
	var $LAST_ERROR;
	protected static $arSectionCodeCache = array();
	protected static $arSectionPathCache = array();
	protected static $arSectionNavChainCache = array();

	public static function GetFilter($arFilter=Array())
	{
		global $DB;
		$arIBlockFilter = Array();
		$arSqlSearch = Array();
		$bSite = false;
		foreach($arFilter as $key => $val)
		{
			$res = CIBlock::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = strtoupper($key);
			switch($key)
			{
			case "ACTIVE":
			case "GLOBAL_ACTIVE":
				$arSqlSearch[] = CIBlock::FilterCreate("BS.".$key, $val, "string_equal", $cOperationType);
				break;
			case "IBLOCK_ACTIVE":
				$arIBlockFilter[] = CIBlock::FilterCreate("B.ACTIVE", $val, "string_equal", $cOperationType);
				break;
			case "LID":
			case "SITE_ID":
				$str = CIBlock::FilterCreate("BS.SITE_ID", $val, "string_equal", $cOperationType);
				if(strlen($str) > 0)
				{
					$arIBlockFilter[] = $str;
					$bSite = true;
				}
				break;
			case "IBLOCK_NAME":
				$arIBlockFilter[] = CIBlock::FilterCreate("B.NAME", $val, "string", $cOperationType);
				break;
			case "IBLOCK_EXTERNAL_ID":
			case "IBLOCK_XML_ID":
				$arIBlockFilter[] = CIBlock::FilterCreate("B.XML_ID", $val, "string", $cOperationType);
				break;
			case "IBLOCK_TYPE":
				$arIBlockFilter[] = CIBlock::FilterCreate("B.IBLOCK_TYPE_ID", $val, "string", $cOperationType);
				break;
			case "TIMESTAMP_X":
			case "DATE_CREATE":
				$arSqlSearch[] = CIBlock::FilterCreate("BS.".$key, $val, "date", $cOperationType);
				break;
			case "IBLOCK_CODE":
				$arIBlockFilter[] = CIBlock::FilterCreate("B.CODE", $val, "string", $cOperationType);
				break;
			case "IBLOCK_ID":
				$arSqlSearch[] = CIBlock::FilterCreate("BS.".$key, $val, "number", $cOperationType);
				$arIBlockFilter[] = CIBlock::FilterCreate("B.ID", $val, "number", $cOperationType);
				break;
			case "NAME":
			case "XML_ID":
			case "TMP_ID":
			case "CODE":
				$arSqlSearch[] = CIBlock::FilterCreate("BS.".$key, $val, "string", $cOperationType);
				break;
			case "EXTERNAL_ID":
				$arSqlSearch[] = CIBlock::FilterCreate("BS.XML_ID", $val, "string", $cOperationType);
				break;
			case "ID":
			case "DEPTH_LEVEL":
			case "MODIFIED_BY":
			case "CREATED_BY":
			case "SOCNET_GROUP_ID":
				$arSqlSearch[] = CIBlock::FilterCreate("BS.".$key, $val, "number", $cOperationType);
				break;
			case "SECTION_ID":
				if(!is_array($val) && IntVal($val)<=0)
					$arSqlSearch[] = CIBlock::FilterCreate("BS.IBLOCK_SECTION_ID", "", "number", $cOperationType, false);
				else
					$arSqlSearch[] = CIBlock::FilterCreate("BS.IBLOCK_SECTION_ID", $val, "number", $cOperationType);
				break;
			case "RIGHT_MARGIN":
				$arSqlSearch[] = "BS.RIGHT_MARGIN ".($cOperationType=="N"?">":"<=").IntVal($val);
				break;
			case "LEFT_MARGIN":
				$arSqlSearch[] = "BS.LEFT_MARGIN ".($cOperationType=="N"?"<":">=").IntVal($val);
				break;
			case "LEFT_BORDER":
				$arSqlSearch[] = CIBlock::FilterCreate("BS.LEFT_MARGIN", $val, "number", $cOperationType);
				break;
			case "RIGHT_BORDER":
				$arSqlSearch[] = CIBlock::FilterCreate("BS.RIGHT_MARGIN", $val, "number", $cOperationType);
				break;
			case "HAS_ELEMENT":
				$arSqlSearch[] = "EXISTS (
					SELECT BS1.ID
					FROM b_iblock_section BS1
					INNER JOIN b_iblock_section_element BSE1 ON BSE1.IBLOCK_SECTION_ID = BS1.ID
						AND BSE1.ADDITIONAL_PROPERTY_ID IS NULL
					INNER JOIN b_iblock_element BE1 ON BE1.ID = BSE1.IBLOCK_ELEMENT_ID
					WHERE BE1.ID = ".intval($val)."
					AND BS1.LEFT_MARGIN >= BS.LEFT_MARGIN
					AND BS1.RIGHT_MARGIN <= BS.RIGHT_MARGIN
				)";
				break;
			}
		}

		static $IBlockFilter_cache = array();
		if($bSite)
		{
			if(is_array($arIBlockFilter) && count($arIBlockFilter)>0)
			{
				$sIBlockFilter = "";
				foreach($arIBlockFilter as $val)
					if(strlen($val)>0)
						$sIBlockFilter .= "  AND ".$val;

				if(!array_key_exists($sIBlockFilter, $IBlockFilter_cache))
				{
					$strSql =
						"SELECT DISTINCT B.ID ".
						"FROM b_iblock B, b_iblock_site BS ".
						"WHERE B.ID = BS.IBLOCK_ID ".
							$sIBlockFilter;

					$arIBLOCKFilter = array();
					$dbRes = $DB->Query($strSql);
					while($arRes = $dbRes->Fetch())
						$arIBLOCKFilter[] = $arRes["ID"];
					$IBlockFilter_cache[$sIBlockFilter] = $arIBLOCKFilter;
				}
				else
				{
					$arIBLOCKFilter = $IBlockFilter_cache[$sIBlockFilter];
				}

				if(count($arIBLOCKFilter) > 0)
					$arSqlSearch[] = "B.ID IN (".implode(", ", $arIBLOCKFilter).") ";
			}
		}
		else
		{
			foreach($arIBlockFilter as $val)
				if(strlen($val) > 0)
					$arSqlSearch[] = $val;
		}

		return $arSqlSearch;
	}

	
	/**
	* <p>Метод возвращает список разделов, отсортированный в порядке "полного развернутого дерева". Метод статический. По сути является оберткой метода <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/index.php">CIBlockSection</a>::<a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/getlist.php">GetList</a>() с предустановленным параметром сортировки:</p>   <pre class="syntax" id="xmpF1F83FB0"> CIBlockSection::GetList(Array("left_margin"=&gt;"asc"), $arFilter);</pre>
	*
	*
	* @param array $arrayarFilter = Array() 
	*
	* @param array $arrayarSelect = Array() 
	*
	* @return CDBResult 
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/index.php">CIBlockSection</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/getlist.php">GetList</a> </li></ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/gettreelist.php
	* @author Bitrix
	*/
	public static function GetTreeList($arFilter = array(), $arSelect = array())
	{
		return CIBlockSection::GetList(Array("left_margin"=>"asc"), $arFilter, false, $arSelect);
	}

	
	/**
	* Метод возвращает путь по дереву от корня до раздела <i>SECTION_ID</i> (пользовательские поля не возвращаются). Метод статический.
	*
	*
	* @param int $IBLOCK_ID  Код информационного блока, служит для проверки что раздел
	* <i>SECTION_ID</i>, находится в заданном информационном блоке. Если
	* значение <i>IBLOCK_ID</i> ноль, то проверка не будет выполнена и код
	* информационного блока не будет учитываться.
	*
	* @param int $SECTION_ID  Код раздела информационного раздела, путь до которого будет
	* выбран.
	*
	* @param array $arSelect = array() Массив возвращаемых полей раздела. Необязательный параметр. По
	* умолчанию будут возвращены все доступные поля.
	*
	* @return CIBlockResult <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/index.php">CIBlockResult</a><a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblocksection">раздела информационного
	* блока.</a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$nav = CIBlockSection::GetNavChain(false, $SECTION_ID);<br>while($nav-&gt;ExtractFields("nav_")):<br>?&gt; &amp;raquo; <br>  &lt;?if($SECTION_ID == $nav_ID):?&gt;<br>    &lt;?echo $nav_NAME?&gt;<br>  &lt;?else:?&gt;<br>    &lt;a class="navchain" href="&lt;?=$application-&gt;getcurpage()?&gt;?iblock_id=&lt;?=$iblock_id?&gt;&amp;section_id=&lt;?=$nav_id?&gt;#tb"&gt;&lt;?echo $nav_NAME?&gt;&lt;/a&gt;<br>  &lt;?endif?&gt;<br>&lt;?endwhile;?&gt;<br>
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/index.php">CIBlockResult</a> </li>  
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblocksection">Поля раздела
	* информационного блока </a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/getnavchain.php
	* @author Bitrix
	*/
	public static function GetNavChain($IBLOCK_ID, $SECTION_ID, $arSelect = array())
	{
		global $DB;

		$IBLOCK_ID = (int)$IBLOCK_ID;

		$arFields = array(
			"ID" => "BS.ID",
			"CODE" => "BS.CODE",
			"XML_ID" => "BS.XML_ID",
			"EXTERNAL_ID" => "BS.XML_ID",
			"IBLOCK_ID" => "BS.IBLOCK_ID",
			"IBLOCK_SECTION_ID" => "BS.IBLOCK_SECTION_ID",
			"SORT" => "BS.SORT",
			"NAME" => "BS.NAME",
			"ACTIVE" => "BS.ACTIVE",
			"GLOBAL_ACTIVE" => "BS.GLOBAL_ACTIVE",
			"PICTURE" => "BS.PICTURE",
			"DESCRIPTION" => "BS.DESCRIPTION",
			"DESCRIPTION_TYPE" => "BS.DESCRIPTION_TYPE",
			"LEFT_MARGIN" => "BS.LEFT_MARGIN",
			"RIGHT_MARGIN" => "BS.RIGHT_MARGIN",
			"DEPTH_LEVEL" => "BS.DEPTH_LEVEL",
			"SEARCHABLE_CONTENT" => "BS.SEARCHABLE_CONTENT",
			"MODIFIED_BY" => "BS.MODIFIED_BY",
			"CREATED_BY" => "BS.CREATED_BY",
			"DETAIL_PICTURE" => "BS.DETAIL_PICTURE",
			"TMP_ID" => "BS.TMP_ID",

			"LIST_PAGE_URL" => "B.LIST_PAGE_URL",
			"SECTION_PAGE_URL" => "B.SECTION_PAGE_URL",
			"IBLOCK_TYPE_ID" => "B.IBLOCK_TYPE_ID",
			"IBLOCK_CODE" => "B.CODE",
			"IBLOCK_EXTERNAL_ID" => "B.XML_ID",
			"SOCNET_GROUP_ID" => "BS.SOCNET_GROUP_ID",
		);

		$arSqlSelect = array();
		foreach($arSelect as $field)
		{
			$field = strtoupper($field);
			if (isset($arFields[$field]))
				$arSqlSelect[$field] = $arFields[$field]." AS ".$field;
		}

		if (isset($arSqlSelect["DESCRIPTION"]))
			$arSqlSelect["DESCRIPTION_TYPE"] = $arFields["DESCRIPTION_TYPE"]." AS DESCRIPTION_TYPE";

		if (isset($arSqlSelect["LIST_PAGE_URL"]) || isset($arSqlSelect["SECTION_PAGE_URL"]))
		{
			$arSqlSelect["ID"] = $arFields["ID"]." AS ID";
			$arSqlSelect["CODE"] = $arFields["CODE"]." AS CODE";
			$arSqlSelect["EXTERNAL_ID"] = $arFields["EXTERNAL_ID"]." AS EXTERNAL_ID";
			$arSqlSelect["IBLOCK_TYPE_ID"] = $arFields["IBLOCK_TYPE_ID"]." AS IBLOCK_TYPE_ID";
			$arSqlSelect["IBLOCK_ID"] = $arFields["IBLOCK_ID"]." AS IBLOCK_ID";
			$arSqlSelect["IBLOCK_CODE"] = $arFields["IBLOCK_CODE"]." AS IBLOCK_CODE";
			$arSqlSelect["IBLOCK_EXTERNAL_ID"] = $arFields["IBLOCK_EXTERNAL_ID"]." AS IBLOCK_EXTERNAL_ID";
			$arSqlSelect["GLOBAL_ACTIVE"] = $arFields["GLOBAL_ACTIVE"]." AS GLOBAL_ACTIVE";
			//$arr["LANG_DIR"],
		}

		if (!empty($arSelect))
		{
			$field = "IBLOCK_SECTION_ID";
			$arSqlSelect[$field] = $arFields[$field]." AS ".$field;
			$strSelect = implode(", ", $arSqlSelect);
		}
		else
		{
			$strSelect = "
				BS.*,
				B.LIST_PAGE_URL,
				B.SECTION_PAGE_URL,
				B.IBLOCK_TYPE_ID,
				B.CODE as IBLOCK_CODE,
				B.XML_ID as IBLOCK_EXTERNAL_ID,
				BS.XML_ID as EXTERNAL_ID
			";
		}

		$key = md5($strSelect);
		if (!isset(self::$arSectionNavChainCache[$key]))
			self::$arSectionNavChainCache[$key] = array();

		$sectionPath = array();
		do
		{
			$SECTION_ID = (int)$SECTION_ID;

			if (!isset(self::$arSectionNavChainCache[$key][$SECTION_ID]))
			{
				$rsSection = $DB->Query("
					SELECT
						".$strSelect."
					FROM
						b_iblock_section BS
						INNER JOIN b_iblock B ON B.ID = BS.IBLOCK_ID
					WHERE BS.ID=".$SECTION_ID."
						".($IBLOCK_ID > 0 ? "AND BS.IBLOCK_ID=".$IBLOCK_ID : "")."
				");
				self::$arSectionNavChainCache[$key][$SECTION_ID] = $rsSection->Fetch();
			}

			if (self::$arSectionNavChainCache[$key][$SECTION_ID])
			{
				$sectionPath[] = self::$arSectionNavChainCache[$key][$SECTION_ID];
				$SECTION_ID = self::$arSectionNavChainCache[$key][$SECTION_ID]["IBLOCK_SECTION_ID"];
			}
			else
			{
				$SECTION_ID = 0;
			}
		}
		while ($SECTION_ID > 0);

		$res = new CDBResult;
		$res->InitFromArray(array_reverse($sectionPath));
		$res = new CIBlockResult($res);
		$res->bIBlockSection = true;
		return $res;
	}

	///////////////////////////////////////////////////////////////////
	// Function returns section by ID
	///////////////////////////////////////////////////////////////////
	
	/**
	* <p>Возвращает параметры раздела по его коду <i>ID</i>. Метод статический.</p>
	*
	*
	* @param int $intID  Код раздела.
	*
	* @return CIBlockResult <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/index.php">CIBlockResult</a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$res = CIBlockSection::GetByID($_GET["GID"]);<br>if($ar_res = $res-&gt;GetNext())<br>  echo $ar_res['NAME'];<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/index.php">CIBlockResult</a> </li>  
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fsection">Поля раздела</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		return CIBlockSection::GetList(Array(), Array("ID"=>IntVal($ID)));
	}

	///////////////////////////////////////////////////////////////////
	// New section
	///////////////////////////////////////////////////////////////////
	
	/**
	* <p>Метод добавляет новый раздел в информационный блок. Перед добавлением раздела вызываются обработчики события <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblocksectionadd.php">OnBeforeIBlockSectionAdd</a> из которых можно изменить значения полей или отменить добавление раздела вернув сообщение об ошибке. После добавления раздела вызывается событие <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblocksectionadd.php">OnAfterIBlockSectionAdd</a>. Нестатический метод.</p>
	*
	*
	* @param array $arFields  Массив вида Array("поле"=&gt;"значение", ...), содержащий значения <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fsection">полей раздела</a> инфоблоков. 
	* <br><br> Пользовательские свойства UF_XXX можно тоже занести в массив и
	* они будут добавляться.
	*
	* @param bool $bResort = true Флаг, указывающий пересчитывать ли правую и левую границы после
	* изменения (поля <i>LEFT_MARGIN</i> и <i>RIGHT_MARGIN</i>). Установите значение в
	* <i>false</i>, если необходимо выполнить большое количество добавлений
	* при небольшом исходном количестве разделов (например
	* автоматизированное создание дерева разделов "с нуля"), в этом
	* случае для повышения производительности можно установить
	* параметр в <i>false</i>, а после всех изменений вызвать метод <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/index.php">CIBlockSection</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/resort.php">ReSort</a>()
	*
	* @param bool $bUpdateSearch = true Флаг, указывающий, что раздел должен быть проиндексирован для
	* поиска сразу же после сохранения.
	*
	* @param bool $bResizePictures = false Использовать настройки инфоблока для обработки изображений. По
	* умолчанию настройки не применяются. Если этот параметр имеет
	* значение true, то к полям PICTURE и DETAIL_PICTURE будут применены правила
	* генерации и масштабирования в соответствии с настройками
	* информационного блока.
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$bs = new CIBlockSection;<br>$arFields = Array(<br>  "ACTIVE" =&gt; $ACTIVE,<br>  "IBLOCK_SECTION_ID" =&gt; $IBLOCK_SECTION_ID,<br>  "IBLOCK_ID" =&gt; $IBLOCK_ID,<br>  "NAME" =&gt; $NAME,<br>  "SORT" =&gt; $SORT,<br>  "PICTURE" =&gt; $_FILES["PICTURE"],<br>  "DESCRIPTION" =&gt; $DESCRIPTION,<br>  "DESCRIPTION_TYPE" =&gt; $DESCRIPTION_TYPE<br>  );<br><br>if($ID &gt; 0)<br>{<br>  $res = $bs-&gt;Update($ID, $arFields);<br>}<br>else<br>{<br>  $ID = $bs-&gt;Add($arFields);<br>  $res = ($ID&gt;0);<br>}<br><br>if(!$res)<br>  echo $bs-&gt;LAST_ERROR;<br>?&gt;<br>
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/update.php">CIBlockSection::Update</a></li>  
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblocksectionadd.php">OnBeforeIBlockSectionAdd</a></li>  
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblocksectionadd.php">OnAfterIBlockSectionAdd</a></li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/add.php
	* @author Bitrix
	*/
	public function Add($arFields, $bResort=true, $bUpdateSearch=true, $bResizePictures=false)
	{
		global $USER, $DB, $APPLICATION;

		if(is_set($arFields, "EXTERNAL_ID"))
			$arFields["XML_ID"] = $arFields["EXTERNAL_ID"];
		Unset($arFields["GLOBAL_ACTIVE"]);
		Unset($arFields["DEPTH_LEVEL"]);
		Unset($arFields["LEFT_MARGIN"]);
		Unset($arFields["RIGHT_MARGIN"]);

		$arIBlock = CIBlock::GetArrayByID($arFields["IBLOCK_ID"]);
		if($bResizePictures && is_array($arIBlock))
		{
			$arDef = $arIBlock["FIELDS"]["SECTION_PICTURE"]["DEFAULT_VALUE"];

			if(
				$arDef["FROM_DETAIL"] === "Y"
				&& is_array($arFields["DETAIL_PICTURE"])
				&& $arFields["DETAIL_PICTURE"]["size"] > 0
				&& (
					$arDef["UPDATE_WITH_DETAIL"] === "Y"
					|| $arFields["PICTURE"]["size"] <= 0
				)
			)
			{
				$arNewPreview = $arFields["DETAIL_PICTURE"];
				$arNewPreview["COPY_FILE"] = "Y";
				$arNewPreview["description"] = $arFields["PICTURE"]["description"];
				$arFields["PICTURE"] = $arNewPreview;
			}

			if(
				array_key_exists("PICTURE", $arFields)
				&& is_array($arFields["PICTURE"])
				&& $arDef["SCALE"] === "Y"
			)
			{
				$arNewPicture = CIBlock::ResizePicture($arFields["PICTURE"], $arDef);
				if(is_array($arNewPicture))
				{
					$arNewPicture["description"] = $arFields["PICTURE"]["description"];
					$arFields["PICTURE"] = $arNewPicture;
				}
				elseif($arDef["IGNORE_ERRORS"] !== "Y")
				{
					unset($arFields["PICTURE"]);
					$strWarning .= GetMessage("IBLOCK_FIELD_PREVIEW_PICTURE").": ".$arNewPicture."<br>";
				}
			}

			if(
				array_key_exists("PICTURE", $arFields)
				&& is_array($arFields["PICTURE"])
				&& $arDef["USE_WATERMARK_FILE"] === "Y"
			)
			{
				if(
					strlen($arFields["PICTURE"]["tmp_name"]) > 0
					&& (
						$arFields["PICTURE"]["tmp_name"] === $arFields["DETAIL_PICTURE"]["tmp_name"]
						|| ($arFields["PICTURE"]["COPY_FILE"] == "Y" && !$arFields["PICTURE"]["copy"])
					)
				)
				{
					$tmp_name = CTempFile::GetFileName(basename($arFields["PICTURE"]["tmp_name"]));
					CheckDirPath($tmp_name);
					copy($arFields["PICTURE"]["tmp_name"], $tmp_name);
					$arFields["PICTURE"]["copy"] = true;
					$arFields["PICTURE"]["tmp_name"] = $tmp_name;
				}

				CIBLock::FilterPicture($arFields["PICTURE"]["tmp_name"], array(
					"name" => "watermark",
					"position" => $arDef["WATERMARK_FILE_POSITION"],
					"type" => "file",
					"size" => "real",
					"alpha_level" => 100 - min(max($arDef["WATERMARK_FILE_ALPHA"], 0), 100),
					"file" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_FILE"]),
				));
			}

			if(
				array_key_exists("PICTURE", $arFields)
				&& is_array($arFields["PICTURE"])
				&& $arDef["USE_WATERMARK_TEXT"] === "Y"
			)
			{
				if(
					strlen($arFields["PICTURE"]["tmp_name"]) > 0
					&& (
						$arFields["PICTURE"]["tmp_name"] === $arFields["DETAIL_PICTURE"]["tmp_name"]
						|| ($arFields["PICTURE"]["COPY_FILE"] == "Y" && !$arFields["PICTURE"]["copy"])
					)
				)
				{
					$tmp_name = CTempFile::GetFileName(basename($arFields["PICTURE"]["tmp_name"]));
					CheckDirPath($tmp_name);
					copy($arFields["PICTURE"]["tmp_name"], $tmp_name);
					$arFields["PICTURE"]["copy"] = true;
					$arFields["PICTURE"]["tmp_name"] = $tmp_name;
				}

				CIBLock::FilterPicture($arFields["PICTURE"]["tmp_name"], array(
					"name" => "watermark",
					"position" => $arDef["WATERMARK_TEXT_POSITION"],
					"type" => "text",
					"coefficient" => $arDef["WATERMARK_TEXT_SIZE"],
					"text" => $arDef["WATERMARK_TEXT"],
					"font" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_TEXT_FONT"]),
					"color" => $arDef["WATERMARK_TEXT_COLOR"],
				));
			}

			$arDef = $arIBlock["FIELDS"]["SECTION_DETAIL_PICTURE"]["DEFAULT_VALUE"];

			if(
				array_key_exists("DETAIL_PICTURE", $arFields)
				&& is_array($arFields["DETAIL_PICTURE"])
				&& $arDef["SCALE"] === "Y"
			)
			{
				$arNewPicture = CIBlock::ResizePicture($arFields["DETAIL_PICTURE"], $arDef);
				if(is_array($arNewPicture))
				{
					$arNewPicture["description"] = $arFields["DETAIL_PICTURE"]["description"];
					$arFields["DETAIL_PICTURE"] = $arNewPicture;
				}
				elseif($arDef["IGNORE_ERRORS"] !== "Y")
				{
					unset($arFields["DETAIL_PICTURE"]);
					$strWarning .= GetMessage("IBLOCK_FIELD_DETAIL_PICTURE").": ".$arNewPicture."<br>";
				}
			}

			if(
				array_key_exists("DETAIL_PICTURE", $arFields)
				&& is_array($arFields["DETAIL_PICTURE"])
				&& $arDef["USE_WATERMARK_FILE"] === "Y"
			)
			{
				if(
					strlen($arFields["DETAIL_PICTURE"]["tmp_name"]) > 0
					&& (
						$arFields["DETAIL_PICTURE"]["tmp_name"] === $arFields["PICTURE"]["tmp_name"]
						|| ($arFields["DETAIL_PICTURE"]["COPY_FILE"] == "Y" && !$arFields["DETAIL_PICTURE"]["copy"])
					)
				)
				{
					$tmp_name = CTempFile::GetFileName(basename($arFields["DETAIL_PICTURE"]["tmp_name"]));
					CheckDirPath($tmp_name);
					copy($arFields["DETAIL_PICTURE"]["tmp_name"], $tmp_name);
					$arFields["DETAIL_PICTURE"]["copy"] = true;
					$arFields["DETAIL_PICTURE"]["tmp_name"] = $tmp_name;
				}

				CIBLock::FilterPicture($arFields["DETAIL_PICTURE"]["tmp_name"], array(
					"name" => "watermark",
					"position" => $arDef["WATERMARK_FILE_POSITION"],
					"type" => "file",
					"size" => "real",
					"alpha_level" => 100 - min(max($arDef["WATERMARK_FILE_ALPHA"], 0), 100),
					"file" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_FILE"]),
				));
			}

			if(
				array_key_exists("DETAIL_PICTURE", $arFields)
				&& is_array($arFields["DETAIL_PICTURE"])
				&& $arDef["USE_WATERMARK_TEXT"] === "Y"
			)
			{
				if(
					strlen($arFields["DETAIL_PICTURE"]["tmp_name"]) > 0
					&& (
						$arFields["DETAIL_PICTURE"]["tmp_name"] === $arFields["PICTURE"]["tmp_name"]
						|| ($arFields["DETAIL_PICTURE"]["COPY_FILE"] == "Y" && !$arFields["DETAIL_PICTURE"]["copy"])
					)
				)
				{
					$tmp_name = CTempFile::GetFileName(basename($arFields["DETAIL_PICTURE"]["tmp_name"]));
					CheckDirPath($tmp_name);
					copy($arFields["DETAIL_PICTURE"]["tmp_name"], $tmp_name);
					$arFields["DETAIL_PICTURE"]["copy"] = true;
					$arFields["DETAIL_PICTURE"]["tmp_name"] = $tmp_name;
				}

				CIBLock::FilterPicture($arFields["DETAIL_PICTURE"]["tmp_name"], array(
					"name" => "watermark",
					"position" => $arDef["WATERMARK_TEXT_POSITION"],
					"type" => "text",
					"coefficient" => $arDef["WATERMARK_TEXT_SIZE"],
					"text" => $arDef["WATERMARK_TEXT"],
					"font" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_TEXT_FONT"]),
					"color" => $arDef["WATERMARK_TEXT_COLOR"],
				));
			}
		}

		$ipropTemplates = new \Bitrix\Iblock\InheritedProperty\SectionTemplates($arFields["IBLOCK_ID"], 0);
		if(is_set($arFields, "PICTURE"))
		{
			if(strlen($arFields["PICTURE"]["name"]) <= 0 && strlen($arFields["PICTURE"]["del"]) <= 0)
			{
				unset($arFields["PICTURE"]);
			}
			else
			{
				$arFields["PICTURE"]["MODULE_ID"] = "iblock";
				$arFields["PICTURE"]["name"] = \Bitrix\Iblock\Template\Helper::makeFileName(
					$ipropTemplates
					,"SECTION_PICTURE_FILE_NAME"
					,$arFields
					,$arFields["PICTURE"]
				);
			}
		}

		if(is_set($arFields, "DETAIL_PICTURE"))
		{
			if(strlen($arFields["DETAIL_PICTURE"]["name"]) <= 0 && strlen($arFields["DETAIL_PICTURE"]["del"]) <= 0)
			{
				unset($arFields["DETAIL_PICTURE"]);
			}
			else
			{
				$arFields["DETAIL_PICTURE"]["MODULE_ID"] = "iblock";
				$arFields["DETAIL_PICTURE"]["name"] = \Bitrix\Iblock\Template\Helper::makeFileName(
					$ipropTemplates
					,"SECTION_DETAIL_PICTURE_FILE_NAME"
					,$arFields
					,$arFields["DETAIL_PICTURE"]
				);
			}
		}

		$arFields["IBLOCK_SECTION_ID"] = isset($arFields["IBLOCK_SECTION_ID"])? intval($arFields["IBLOCK_SECTION_ID"]): 0;
		if($arFields["IBLOCK_SECTION_ID"] == 0)
			$arFields["IBLOCK_SECTION_ID"] = false;

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"] != "Y")
			$arFields["ACTIVE"] = "N";
		else
			$arFields["ACTIVE"] = "Y";

		if(!array_key_exists("DESCRIPTION_TYPE", $arFields) || $arFields["DESCRIPTION_TYPE"]!="html")
			$arFields["DESCRIPTION_TYPE"]="text";

		if(!isset($arFields["DESCRIPTION"]))
			$arFields["DESCRIPTION"] = false;

		$arFields["SEARCHABLE_CONTENT"] =
			ToUpper(
				$arFields["NAME"]."\r\n".
				($arFields["DESCRIPTION_TYPE"]=="html" ?
					HTMLToTxt($arFields["DESCRIPTION"]) :
					$arFields["DESCRIPTION"]
				)
			);

		unset($arFields["DATE_CREATE"]);
		$arFields["~DATE_CREATE"] = $DB->CurrentTimeFunction();
		if(is_object($USER))
		{
			$user_id = intval($USER->GetID());
			if(!isset($arFields["CREATED_BY"]) || intval($arFields["CREATED_BY"]) <= 0)
				$arFields["CREATED_BY"] = $user_id;
			if(!isset($arFields["MODIFIED_BY"]) || intval($arFields["MODIFIED_BY"]) <= 0)
				$arFields["MODIFIED_BY"] = $user_id;
		}

		$IBLOCK_ID = intval($arFields["IBLOCK_ID"]);

		if(!$this->CheckFields($arFields))
		{
			$Result = false;
			$arFields["RESULT_MESSAGE"] = &$this->LAST_ERROR;
		}
		elseif($IBLOCK_ID && !$GLOBALS["USER_FIELD_MANAGER"]->CheckFields("IBLOCK_".$IBLOCK_ID."_SECTION", 0, $arFields))
		{
			$Result = false;
			$err = $APPLICATION->GetException();
			if(is_object($err))
				$this->LAST_ERROR .= str_replace("<br><br>", "<br>", $err->GetString()."<br>");
			$arFields["RESULT_MESSAGE"] = &$this->LAST_ERROR;
		}
		else
		{
			if(array_key_exists("PICTURE", $arFields))
			{
				$SAVED_PICTURE = $arFields["PICTURE"];
				CFile::SaveForDB($arFields, "PICTURE", "iblock");
			}

			if(array_key_exists("DETAIL_PICTURE", $arFields))
			{
				$SAVED_DETAIL_PICTURE = $arFields["DETAIL_PICTURE"];
				CFile::SaveForDB($arFields, "DETAIL_PICTURE", "iblock");
			}

			CIBlock::_transaction_lock($IBLOCK_ID);

			unset($arFields["ID"]);
			$ID = intval($DB->Add("b_iblock_section", $arFields, Array("DESCRIPTION","SEARCHABLE_CONTENT"), "iblock"));

			if(array_key_exists("PICTURE", $arFields))
				$arFields["PICTURE"] = $SAVED_PICTURE;
			if(array_key_exists("DETAIL_PICTURE", $arFields))
				$arFields["DETAIL_PICTURE"] = $SAVED_DETAIL_PICTURE;

			if($bResort)
			{
				if(!array_key_exists("SORT", $arFields))
					$arFields["SORT"] = 500;

				$arParent = false;
				if($arFields["IBLOCK_SECTION_ID"] !== false)
				{
					$strSql = "
						SELECT BS.ID, BS.ACTIVE, BS.GLOBAL_ACTIVE, BS.DEPTH_LEVEL, BS.LEFT_MARGIN, BS.RIGHT_MARGIN
						FROM b_iblock_section BS
						WHERE BS.IBLOCK_ID = ".$IBLOCK_ID."
						AND BS.ID = ".$arFields["IBLOCK_SECTION_ID"]."
					";
					$rsParent = $DB->Query($strSql);
					$arParent = $rsParent->Fetch();
				}

				$NAME = $arFields["NAME"];
				$SORT = intval($arFields["SORT"]);

				//Find rightmost child of the parent
				$strSql = "
					SELECT BS.ID, BS.RIGHT_MARGIN, BS.GLOBAL_ACTIVE, BS.DEPTH_LEVEL
					FROM b_iblock_section BS
					WHERE BS.IBLOCK_ID = ".$IBLOCK_ID."
					AND ".($arFields["IBLOCK_SECTION_ID"] !== false? "BS.IBLOCK_SECTION_ID=".$arFields["IBLOCK_SECTION_ID"]: "BS.IBLOCK_SECTION_ID IS NULL")."
					AND (
						(BS.SORT < ".$SORT.")
						OR (BS.SORT = ".$SORT." AND BS.NAME < '".$DB->ForSQL($NAME)."')
					)
					AND BS.ID <> ".$ID."
					ORDER BY BS.SORT DESC, BS.NAME DESC
				";
				$rsChild = $DB->Query($strSql);
				if($arChild = $rsChild->Fetch())
				{
					//We found the left neighbour
					$arUpdate = array(
						"LEFT_MARGIN" => intval($arChild["RIGHT_MARGIN"])+1,
						"RIGHT_MARGIN" => intval($arChild["RIGHT_MARGIN"])+2,
						"DEPTH_LEVEL" => intval($arChild["DEPTH_LEVEL"]),
					);
					//in case we adding active section
					if($arFields["ACTIVE"] != "N")
					{
						//Look up GLOBAL_ACTIVE of the parent
						//if none then take our own
						if($arParent)//We must inherit active from the parent
							$arUpdate["GLOBAL_ACTIVE"] = $arParent["ACTIVE"] == "Y"? "Y": "N";
						else //No parent was found take our own
							$arUpdate["GLOBAL_ACTIVE"] = "Y";
					}
					else
					{
						$arUpdate["GLOBAL_ACTIVE"] = "N";
					}
				}
				else
				{
					//If we have parent, when take its left_margin
					if($arParent)
					{
						$arUpdate = array(
							"LEFT_MARGIN" => intval($arParent["LEFT_MARGIN"])+1,
							"RIGHT_MARGIN" => intval($arParent["LEFT_MARGIN"])+2,
							"GLOBAL_ACTIVE" => ($arParent["GLOBAL_ACTIVE"] == "Y") && ($arFields["ACTIVE"] != "N")? "Y": "N",
							"DEPTH_LEVEL" => intval($arParent["DEPTH_LEVEL"])+1,
						);
					}
					else
					{
						//We are only one/leftmost section in the iblock.
						$arUpdate = array(
							"LEFT_MARGIN" => 1,
							"RIGHT_MARGIN" => 2,
							"GLOBAL_ACTIVE" => $arFields["ACTIVE"] != "N"? "Y": "N",
							"DEPTH_LEVEL" => 1,
						);
					}
				}
				$DB->Query("
					UPDATE b_iblock_section SET
						TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
						,LEFT_MARGIN = ".$arUpdate["LEFT_MARGIN"]."
						,RIGHT_MARGIN = ".$arUpdate["RIGHT_MARGIN"]."
						,DEPTH_LEVEL = ".$arUpdate["DEPTH_LEVEL"]."
						,GLOBAL_ACTIVE = '".$arUpdate["GLOBAL_ACTIVE"]."'
					WHERE
						ID = ".$ID."
				");
				$DB->Query("
					UPDATE b_iblock_section SET
						TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
						,LEFT_MARGIN = LEFT_MARGIN + 2
						,RIGHT_MARGIN = RIGHT_MARGIN + 2
					WHERE
						IBLOCK_ID = ".$IBLOCK_ID."
						AND LEFT_MARGIN >= ".$arUpdate["LEFT_MARGIN"]."
						AND ID <> ".$ID."
				");
				if($arParent)
					$DB->Query("
						UPDATE b_iblock_section SET
							TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
							,RIGHT_MARGIN = RIGHT_MARGIN + 2
						WHERE
							IBLOCK_ID = ".$IBLOCK_ID."
							AND LEFT_MARGIN <= ".$arParent["LEFT_MARGIN"]."
							AND RIGHT_MARGIN >= ".$arParent["RIGHT_MARGIN"]."
					");
			}

			$GLOBALS["USER_FIELD_MANAGER"]->Update("IBLOCK_".$IBLOCK_ID."_SECTION", $ID, $arFields);

			if($bUpdateSearch)
				CIBlockSection::UpdateSearch($ID);

			if(
				CIBlock::GetArrayByID($IBLOCK_ID, "SECTION_PROPERTY") === "Y"
				&& array_key_exists("SECTION_PROPERTY", $arFields)
				&& is_array($arFields["SECTION_PROPERTY"])
			)
			{
				foreach($arFields["SECTION_PROPERTY"] as $PROPERTY_ID => $arLink)
					CIBlockSectionPropertyLink::Add($ID, $PROPERTY_ID, $arLink);
			}

			if($arIBlock["FIELDS"]["LOG_SECTION_ADD"]["IS_REQUIRED"] == "Y")
			{
				$USER_ID = is_object($USER)? intval($USER->GetID()) : 0;
				$arEvents = GetModuleEvents("main", "OnBeforeEventLog", true);
				if(empty($arEvents) || ExecuteModuleEventEx($arEvents[0], array($USER_ID))===false)
				{
					$rsSection = CIBlockSection::GetList(array(), array("=ID"=>$ID), false,  array("LIST_PAGE_URL", "NAME", "CODE"));
					$arSection = $rsSection->GetNext();
					$res = array(
						"ID" => $ID,
						"CODE" => $arSection["CODE"],
						"NAME" => $arSection["NAME"],
						"SECTION_NAME" => $arIBlock["SECTION_NAME"],
						"USER_ID" => $USER_ID,
						"IBLOCK_PAGE_URL" => $arSection["LIST_PAGE_URL"],
					);
					CEventLog::Log(
						"IBLOCK",
						"IBLOCK_SECTION_ADD",
						"iblock",
						$arIBlock["ID"],
						serialize($res)
					);
				}
			}

			if($arIBlock["RIGHTS_MODE"] === "E")
			{
				$obSectionRights = new CIBlockSectionRights($arIBlock["ID"], $ID);
				$obSectionRights->ChangeParents(array(), array($arFields["IBLOCK_SECTION_ID"]));

				if(array_key_exists("RIGHTS", $arFields) && is_array($arFields["RIGHTS"]))
					$obSectionRights->SetRights($arFields["RIGHTS"]);
			}

			if (array_key_exists("IPROPERTY_TEMPLATES", $arFields))
			{
				$ipropTemplates = new \Bitrix\Iblock\InheritedProperty\SectionTemplates($arIBlock["ID"], $ID);
				$ipropTemplates->set($arFields["IPROPERTY_TEMPLATES"]);
			}

			$Result = $ID;
			$arFields["ID"] = &$ID;

			/************* QUOTA *************/
			$_SESSION["SESS_RECOUNT_DB"] = "Y";
			/************* QUOTA *************/
		}

		$arFields["RESULT"] = &$Result;

		foreach (GetModuleEvents("iblock", "OnAfterIBlockSectionAdd", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arFields));

		CIBlock::clearIblockTagCache($arIBlock['ID']);

		return $Result;
	}

	///////////////////////////////////////////////////////////////////
	// Update section properties
	///////////////////////////////////////////////////////////////////
	
	/**
	* <p>Метод изменяет параметры раздела с кодом <i>ID</i>.  Перед изменением раздела вызываются обработчики события <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblocksectionupdate.php">OnBeforeIBlockSectionUpdate</a> из которых можно изменить значения полей или отменить изменение параметров раздела вернув сообщение об ошибке. После изменения раздела вызывается событие <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblocksectionupdate.php">OnAfterIBlockSectionUpdate</a>. Нестатический метод.</p> <p></p> <div class="note"> <b>Примечание</b>: Изменить значения полей GLOBAL_ACTIVE, DEPTH_LEVEL, LEFT_MARGIN, RIGHT_MARGIN, IBLOCK_ID, DATE_CREATE и CREATED_BY нельзя. Значение первого определяется флагом активности раздела и его родителей. DEPTH_LEVEL, LEFT_MARGIN и RIGHT_MARGIN расчитываются автоматически в зависимости от положения раздела в дереве.</div>
	*
	*
	* @param int $intID  Код изменяемой записи.
	*
	* @param array $arFields  Массив вида Array("поле"=&gt;"значение", ...), содержащий значения <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fsection">полей раздела</a> инфоблоков.
	*
	* @param bool $bResort = true Флаг, указывающий пересчитывать ли правую и левую границы после
	* изменения (поля <i>LEFT_MARGIN</i> и <i>RIGHT_MARGIN</i>). Установите значение в
	* <i>false</i>, в случае если поля которые влияют на значения границ не
	* изменяются или необходимо выполнить серию изменений приводящих
	* к полной перестройке дерева разделов, в последнем случае для
	* повышения производительности можно установить параметр в
	* <i>false</i>, а после всех изменений вызвать метод <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/index.php">CIBlockSection</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/resort.php">ReSort</a>()
	*
	* @param bool $bUpdateSearch = true Флаг, указывающий, что раздел должен 	быть проиндексирован для
	* поиска сразу же 	после сохранения.
	*
	* @param bool $bResizePictures = false Использовать настройки инфоблока для обработки изображений. По
	* умолчанию настройки не применяются. Если этот параметр имеет
	* значение true, то к полям PICTURE и DETAIL_PICTURE будут применены правила
	* генерации и масштабирования в соответствии с настройками
	* информационного блока.
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$bs = new CIBlockSection;<br><br>$arPICTURE = $_FILES["PICTURE"];<br>$arPICTURE["MODULE_ID"] = "iblock";<br><br>$arFields = Array(<br>  "ACTIVE" =&gt; $ACTIVE,<br>  "IBLOCK_SECTION_ID" =&gt; $IBLOCK_SECTION_ID,<br>  "IBLOCK_ID" =&gt; $IBLOCK_ID,<br>  "NAME" =&gt; $NAME,<br>  "SORT" =&gt; $SORT,<br>  "PICTURE" =&gt; $arPICTURE,<br>  "DESCRIPTION" =&gt; $DESCRIPTION,<br>  "DESCRIPTION_TYPE" =&gt; $DESCRIPTION_TYPE<br>  );<br><br>if($ID &gt; 0)<br>{<br>  $res = $bs-&gt;Update($ID, $arFields);<br>}<br>else<br>{<br>  $ID = $bs-&gt;Add($arFields);<br>  $res = ($ID&gt;0);<br>}<br>?&gt;<br>
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/add.php">CIBlockSection::Add</a></li>  
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblocksectionupdate.php">OnBeforeIBlockSectionUpdate</a></li>
	*   <li><a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblocksectionupdate.php">OnAfterIBlockSectionUpdate</a></li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/update.php
	* @author Bitrix
	*/
	public function Update($ID, $arFields, $bResort=true, $bUpdateSearch=true, $bResizePictures=false)
	{
		global $USER, $DB, $APPLICATION;

		$ID = (int)$ID;

		$db_record = CIBlockSection::GetList(Array(), Array("ID"=>$ID, "CHECK_PERMISSIONS"=>"N"));
		if(!($db_record = $db_record->Fetch()))
			return false;

		if(is_set($arFields, "EXTERNAL_ID"))
			$arFields["XML_ID"] = $arFields["EXTERNAL_ID"];

		Unset($arFields["GLOBAL_ACTIVE"]);
		Unset($arFields["DEPTH_LEVEL"]);
		Unset($arFields["LEFT_MARGIN"]);
		Unset($arFields["RIGHT_MARGIN"]);
		unset($arFields["IBLOCK_ID"]);
		unset($arFields["DATE_CREATE"]);
		unset($arFields["CREATED_BY"]);

		$arIBlock = CIBlock::GetArrayByID($db_record["IBLOCK_ID"]);
		if($bResizePictures)
		{
			$arDef = $arIBlock["FIELDS"]["SECTION_PICTURE"]["DEFAULT_VALUE"];

			if(
				$arDef["DELETE_WITH_DETAIL"] === "Y"
				&& $arFields["DETAIL_PICTURE"]["del"] === "Y"
			)
			{
				$arFields["PICTURE"]["del"] = "Y";
			}

			if(
				$arDef["FROM_DETAIL"] === "Y"
				&& (
					$arFields["PICTURE"]["size"] <= 0
					|| $arDef["UPDATE_WITH_DETAIL"] === "Y"
				)
				&& is_array($arFields["DETAIL_PICTURE"])
				&& $arFields["DETAIL_PICTURE"]["size"] > 0
			)
			{
				if(
					$arFields["PICTURE"]["del"] !== "Y"
					&& $arDef["UPDATE_WITH_DETAIL"] !== "Y"
				)
				{
					$arOldSection = $db_record;
				}
				else
				{
					$arOldSection = false;
				}

				if(!$arOldSection || !$arOldSection["PICTURE"])
				{
					$arNewPreview = $arFields["DETAIL_PICTURE"];
					$arNewPreview["COPY_FILE"] = "Y";
					$arNewPreview["description"] = $arFields["PICTURE"]["description"];
					$arFields["PICTURE"] = $arNewPreview;
				}
			}

			if(
				array_key_exists("PICTURE", $arFields)
				&& is_array($arFields["PICTURE"])
				&& $arFields["PICTURE"]["size"] > 0
				&& $arDef["SCALE"] === "Y"
			)
			{
				$arNewPicture = CIBlock::ResizePicture($arFields["PICTURE"], $arDef);
				if(is_array($arNewPicture))
				{
					$arNewPicture["description"] = $arFields["PICTURE"]["description"];
					$arFields["PICTURE"] = $arNewPicture;
				}
				elseif($arDef["IGNORE_ERRORS"] !== "Y")
				{
					unset($arFields["PICTURE"]);
					$strWarning .= GetMessage("IBLOCK_FIELD_PREVIEW_PICTURE").": ".$arNewPicture."<br>";
				}
			}

			if(
				array_key_exists("PICTURE", $arFields)
				&& is_array($arFields["PICTURE"])
				&& $arDef["USE_WATERMARK_FILE"] === "Y"
			)
			{
				if(
					strlen($arFields["PICTURE"]["tmp_name"]) > 0
					&& (
						$arFields["PICTURE"]["tmp_name"] === $arFields["DETAIL_PICTURE"]["tmp_name"]
						|| ($arFields["PICTURE"]["COPY_FILE"] == "Y" && !$arFields["PICTURE"]["copy"])
					)
				)
				{
					$tmp_name = CTempFile::GetFileName(basename($arFields["PICTURE"]["tmp_name"]));
					CheckDirPath($tmp_name);
					copy($arFields["PICTURE"]["tmp_name"], $tmp_name);
					$arFields["PICTURE"]["copy"] = true;
					$arFields["PICTURE"]["tmp_name"] = $tmp_name;
				}

				CIBLock::FilterPicture($arFields["PICTURE"]["tmp_name"], array(
					"name" => "watermark",
					"position" => $arDef["WATERMARK_FILE_POSITION"],
					"type" => "file",
					"size" => "real",
					"alpha_level" => 100 - min(max($arDef["WATERMARK_FILE_ALPHA"], 0), 100),
					"file" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_FILE"]),
				));
			}

			if(
				array_key_exists("PICTURE", $arFields)
				&& is_array($arFields["PICTURE"])
				&& $arDef["USE_WATERMARK_TEXT"] === "Y"
			)
			{
				if(
					strlen($arFields["PICTURE"]["tmp_name"]) > 0
					&& (
						$arFields["PICTURE"]["tmp_name"] === $arFields["DETAIL_PICTURE"]["tmp_name"]
						|| ($arFields["PICTURE"]["COPY_FILE"] == "Y" && !$arFields["PICTURE"]["copy"])
					)
				)
				{
					$tmp_name = CTempFile::GetFileName(basename($arFields["PICTURE"]["tmp_name"]));
					CheckDirPath($tmp_name);
					copy($arFields["PICTURE"]["tmp_name"], $tmp_name);
					$arFields["PICTURE"]["copy"] = true;
					$arFields["PICTURE"]["tmp_name"] = $tmp_name;
				}

				CIBLock::FilterPicture($arFields["PICTURE"]["tmp_name"], array(
					"name" => "watermark",
					"position" => $arDef["WATERMARK_TEXT_POSITION"],
					"type" => "text",
					"coefficient" => $arDef["WATERMARK_TEXT_SIZE"],
					"text" => $arDef["WATERMARK_TEXT"],
					"font" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_TEXT_FONT"]),
					"color" => $arDef["WATERMARK_TEXT_COLOR"],
				));
			}

			$arDef = $arIBlock["FIELDS"]["SECTION_DETAIL_PICTURE"]["DEFAULT_VALUE"];

			if(
				array_key_exists("DETAIL_PICTURE", $arFields)
				&& is_array($arFields["DETAIL_PICTURE"])
				&& $arDef["SCALE"] === "Y"
			)
			{
				$arNewPicture = CIBlock::ResizePicture($arFields["DETAIL_PICTURE"], $arDef);
				if(is_array($arNewPicture))
				{
					$arNewPicture["description"] = $arFields["DETAIL_PICTURE"]["description"];
					$arFields["DETAIL_PICTURE"] = $arNewPicture;
				}
				elseif($arDef["IGNORE_ERRORS"] !== "Y")
				{
					unset($arFields["DETAIL_PICTURE"]);
					$strWarning .= GetMessage("IBLOCK_FIELD_DETAIL_PICTURE").": ".$arNewPicture."<br>";
				}
			}

			if(
				array_key_exists("DETAIL_PICTURE", $arFields)
				&& is_array($arFields["DETAIL_PICTURE"])
				&& $arDef["USE_WATERMARK_FILE"] === "Y"
			)
			{
				if(
					strlen($arFields["DETAIL_PICTURE"]["tmp_name"]) > 0
					&& (
						$arFields["DETAIL_PICTURE"]["tmp_name"] === $arFields["PICTURE"]["tmp_name"]
						|| ($arFields["DETAIL_PICTURE"]["COPY_FILE"] == "Y" && !$arFields["DETAIL_PICTURE"]["copy"])
					)
				)
				{
					$tmp_name = CTempFile::GetFileName(basename($arFields["DETAIL_PICTURE"]["tmp_name"]));
					CheckDirPath($tmp_name);
					copy($arFields["DETAIL_PICTURE"]["tmp_name"], $tmp_name);
					$arFields["DETAIL_PICTURE"]["copy"] = true;
					$arFields["DETAIL_PICTURE"]["tmp_name"] = $tmp_name;
				}

				CIBLock::FilterPicture($arFields["DETAIL_PICTURE"]["tmp_name"], array(
					"name" => "watermark",
					"position" => $arDef["WATERMARK_FILE_POSITION"],
					"type" => "file",
					"size" => "real",
					"alpha_level" => 100 - min(max($arDef["WATERMARK_FILE_ALPHA"], 0), 100),
					"file" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_FILE"]),
					"fill" => "resize",
				));
			}

			if(
				array_key_exists("DETAIL_PICTURE", $arFields)
				&& is_array($arFields["DETAIL_PICTURE"])
				&& $arDef["USE_WATERMARK_TEXT"] === "Y"
			)
			{
				if(
					strlen($arFields["DETAIL_PICTURE"]["tmp_name"]) > 0
					&& (
						$arFields["DETAIL_PICTURE"]["tmp_name"] === $arFields["PICTURE"]["tmp_name"]
						|| ($arFields["DETAIL_PICTURE"]["COPY_FILE"] == "Y" && !$arFields["DETAIL_PICTURE"]["copy"])
					)
				)
				{
					$tmp_name = CTempFile::GetFileName(basename($arFields["DETAIL_PICTURE"]["tmp_name"]));
					CheckDirPath($tmp_name);
					copy($arFields["DETAIL_PICTURE"]["tmp_name"], $tmp_name);
					$arFields["DETAIL_PICTURE"]["copy"] = true;
					$arFields["DETAIL_PICTURE"]["tmp_name"] = $tmp_name;
				}

				CIBLock::FilterPicture($arFields["DETAIL_PICTURE"]["tmp_name"], array(
					"name" => "watermark",
					"position" => $arDef["WATERMARK_TEXT_POSITION"],
					"type" => "text",
					"coefficient" => $arDef["WATERMARK_TEXT_SIZE"],
					"text" => $arDef["WATERMARK_TEXT"],
					"font" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_TEXT_FONT"]),
					"color" => $arDef["WATERMARK_TEXT_COLOR"],
				));
			}
		}

		$ipropTemplates = new \Bitrix\Iblock\InheritedProperty\SectionTemplates($db_record["IBLOCK_ID"], $db_record["ID"]);
		if(is_set($arFields, "PICTURE"))
		{
			if(strlen($arFields["PICTURE"]["name"])<=0 && strlen($arFields["PICTURE"]["del"])<=0)
			{
				unset($arFields["PICTURE"]);
			}
			else
			{
				$arFields["PICTURE"]["old_file"] = $db_record["PICTURE"];
				$arFields["PICTURE"]["MODULE_ID"] = "iblock";
				$arFields["PICTURE"]["name"] = \Bitrix\Iblock\Template\Helper::makeFileName(
						$ipropTemplates
						,"SECTION_PICTURE_FILE_NAME"
						,array_merge($db_record, $arFields)
						,$arFields["PICTURE"]
				);
			}
		}

		if(is_set($arFields, "DETAIL_PICTURE"))
		{
			if(strlen($arFields["DETAIL_PICTURE"]["name"])<=0 && strlen($arFields["DETAIL_PICTURE"]["del"])<=0)
			{
				unset($arFields["DETAIL_PICTURE"]);
			}
			else
			{
				$arFields["DETAIL_PICTURE"]["old_file"] = $db_record["DETAIL_PICTURE"];
				$arFields["DETAIL_PICTURE"]["MODULE_ID"] = "iblock";
				$arFields["DETAIL_PICTURE"]["name"] = \Bitrix\Iblock\Template\Helper::makeFileName(
					$ipropTemplates
					,"SECTION_DETAIL_PICTURE_FILE_NAME"
					,array_merge($db_record, $arFields)
					,$arFields["DETAIL_PICTURE"]
				);
			}
		}

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";

		if(is_set($arFields, "DESCRIPTION_TYPE") && $arFields["DESCRIPTION_TYPE"]!="html")
			$arFields["DESCRIPTION_TYPE"] = "text";

		if(isset($arFields["IBLOCK_SECTION_ID"]))
		{
			$arFields["IBLOCK_SECTION_ID"] = intval($arFields["IBLOCK_SECTION_ID"]);
			if($arFields["IBLOCK_SECTION_ID"] <= 0)
				$arFields["IBLOCK_SECTION_ID"] = false;
		}

		$DESC_tmp = is_set($arFields, "DESCRIPTION")? $arFields["DESCRIPTION"]: $db_record["DESCRIPTION"];
		$DESC_TYPE_tmp = is_set($arFields, "DESCRIPTION_TYPE")? $arFields["DESCRIPTION_TYPE"]: $db_record["DESCRIPTION_TYPE"];

		$arFields["SEARCHABLE_CONTENT"] = ToUpper(
			(is_set($arFields, "NAME")? $arFields["NAME"]: $db_record["NAME"])."\r\n".
			($DESC_TYPE_tmp=="html"? HTMLToTxt($DESC_tmp): $DESC_tmp)
		);

		if(is_object($USER))
		{
			if(!isset($arFields["MODIFIED_BY"]) || intval($arFields["MODIFIED_BY"]) <= 0)
				$arFields["MODIFIED_BY"] = intval($USER->GetID());
		}

		if(!$this->CheckFields($arFields, $ID))
		{
			$Result = false;
			$arFields["RESULT_MESSAGE"] = &$this->LAST_ERROR;
		}
		elseif(!$GLOBALS["USER_FIELD_MANAGER"]->CheckFields("IBLOCK_".$db_record["IBLOCK_ID"]."_SECTION", $ID, $arFields))
		{
			$Result = false;
			$err = $APPLICATION->GetException();
			if(is_object($err))
				$this->LAST_ERROR .= str_replace("<br><br>", "<br>", $err->GetString()."<br>");
			$arFields["RESULT_MESSAGE"] = &$this->LAST_ERROR;
		}
		else
		{
			if(array_key_exists("PICTURE", $arFields))
			{
				$SAVED_PICTURE = $arFields["PICTURE"];
				CFile::SaveForDB($arFields, "PICTURE", "iblock");
			}

			if(array_key_exists("DETAIL_PICTURE", $arFields))
			{
				$SAVED_DETAIL_PICTURE = $arFields["DETAIL_PICTURE"];
				CFile::SaveForDB($arFields, "DETAIL_PICTURE", "iblock");
			}

			unset($arFields["ID"]);
			$strUpdate = $DB->PrepareUpdate("b_iblock_section", $arFields, "iblock");

			if(array_key_exists("PICTURE", $arFields))
				$arFields["PICTURE"] = $SAVED_PICTURE;
			if(array_key_exists("DETAIL_PICTURE", $arFields))
				$arFields["DETAIL_PICTURE"] = $SAVED_DETAIL_PICTURE;

			CIBlock::_transaction_lock($db_record["IBLOCK_ID"]);

			if(strlen($strUpdate) > 0)
			{
				$strSql = "UPDATE b_iblock_section SET ".$strUpdate." WHERE ID = ".$ID;
				$arBinds=Array();
				if(array_key_exists("DESCRIPTION", $arFields))
					$arBinds["DESCRIPTION"] = $arFields["DESCRIPTION"];
				if(array_key_exists("SEARCHABLE_CONTENT", $arFields))
					$arBinds["SEARCHABLE_CONTENT"] = $arFields["SEARCHABLE_CONTENT"];
				$DB->QueryBind($strSql, $arBinds);
			}

			if($bResort)
			{
				//Move inside the tree
				if((isset($arFields["SORT"]) && $arFields["SORT"]!=$db_record["SORT"])
					|| (isset($arFields["NAME"]) && $arFields["NAME"]!=$db_record["NAME"])
					|| (isset($arFields["IBLOCK_SECTION_ID"]) && $arFields["IBLOCK_SECTION_ID"]!=$db_record["IBLOCK_SECTION_ID"]))
				{
					//First "delete" from the tree
					$distance = intval($db_record["RIGHT_MARGIN"]) - intval($db_record["LEFT_MARGIN"]) + 1;
					$DB->Query("
						UPDATE b_iblock_section SET
							TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
							,LEFT_MARGIN = -LEFT_MARGIN
							,RIGHT_MARGIN = -RIGHT_MARGIN
						WHERE
							IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
							AND LEFT_MARGIN >= ".intval($db_record["LEFT_MARGIN"])."
							AND LEFT_MARGIN <= ".intval($db_record["RIGHT_MARGIN"])."
					");
					$DB->Query("
						UPDATE b_iblock_section SET
							TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
							,RIGHT_MARGIN = RIGHT_MARGIN - ".$distance."
						WHERE
							IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
							AND RIGHT_MARGIN > ".$db_record["RIGHT_MARGIN"]."
					");
					$DB->Query("
						UPDATE b_iblock_section SET
							TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
							,LEFT_MARGIN = LEFT_MARGIN - ".$distance."
						WHERE
							IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
							AND LEFT_MARGIN > ".$db_record["LEFT_MARGIN"]."
					");

					//Next insert into the the tree almost as we do when inserting the new one

					$PARENT_ID = isset($arFields["IBLOCK_SECTION_ID"])? intval($arFields["IBLOCK_SECTION_ID"]): intval($db_record["IBLOCK_SECTION_ID"]);
					$NAME = isset($arFields["NAME"])? $arFields["NAME"]: $db_record["NAME"];
					$SORT = isset($arFields["SORT"])? intval($arFields["SORT"]): intval($db_record["SORT"]);

					$arParents = array();
					$strSql = "
						SELECT BS.ID, BS.ACTIVE, BS.GLOBAL_ACTIVE, BS.DEPTH_LEVEL, BS.LEFT_MARGIN, BS.RIGHT_MARGIN
						FROM b_iblock_section BS
						WHERE BS.IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
						AND BS.ID in (".intval($db_record["IBLOCK_SECTION_ID"]).", ".$PARENT_ID.")
					";
					$rsParents = $DB->Query($strSql);
					while($arParent = $rsParents->Fetch())
					{
						$arParents[$arParent["ID"]] = $arParent;
					}
					//Find rightmost child of the parent
					$strSql = "
						SELECT BS.ID, BS.RIGHT_MARGIN, BS.DEPTH_LEVEL
						FROM b_iblock_section BS
						WHERE BS.IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
						AND ".($PARENT_ID > 0? "BS.IBLOCK_SECTION_ID=".$PARENT_ID: "BS.IBLOCK_SECTION_ID IS NULL")."
						AND (
							(BS.SORT < ".$SORT.")
							OR (BS.SORT = ".$SORT." AND BS.NAME < '".$DB->ForSQL($NAME)."')
						)
						AND BS.ID <> ".$ID."
						ORDER BY BS.SORT DESC, BS.NAME DESC
					";
					$rsChild = $DB->Query($strSql);
					if($arChild = $rsChild->Fetch())
					{
						//We found the left neighbour
						$arUpdate = array(
							"LEFT_MARGIN" => intval($arChild["RIGHT_MARGIN"])+1,
							"DEPTH_LEVEL" => intval($arChild["DEPTH_LEVEL"]),
						);
					}
					else
					{
						//If we have parent, when take its left_margin
						if(isset($arParents[$PARENT_ID]) && $arParents[$PARENT_ID])
						{
							$arUpdate = array(
								"LEFT_MARGIN" => intval($arParents[$PARENT_ID]["LEFT_MARGIN"])+1,
								"DEPTH_LEVEL" => intval($arParents[$PARENT_ID]["DEPTH_LEVEL"])+1,
							);
						}
						else
						{
							//We are only one/leftmost section in the iblock.
							$arUpdate = array(
								"LEFT_MARGIN" => 1,
								"DEPTH_LEVEL" => 1,
							);
						}
					}

					$move_distance = intval($db_record["LEFT_MARGIN"]) - $arUpdate["LEFT_MARGIN"];

					$DB->Query("
						UPDATE b_iblock_section SET
							TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
							,LEFT_MARGIN = LEFT_MARGIN + ".$distance."
							,RIGHT_MARGIN = RIGHT_MARGIN + ".$distance."
						WHERE
							IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
							AND LEFT_MARGIN >= ".$arUpdate["LEFT_MARGIN"]."
					");
					$DB->Query("
						UPDATE b_iblock_section SET
							TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
							,LEFT_MARGIN = -LEFT_MARGIN - ".$move_distance."
							,RIGHT_MARGIN = -RIGHT_MARGIN - ".$move_distance."
							".($arUpdate["DEPTH_LEVEL"] != intval($db_record["DEPTH_LEVEL"])? ",DEPTH_LEVEL = DEPTH_LEVEL - ".($db_record["DEPTH_LEVEL"] - $arUpdate["DEPTH_LEVEL"]): "")."
						WHERE
							IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
							AND LEFT_MARGIN <= ".(-intval($db_record["LEFT_MARGIN"]))."
							AND LEFT_MARGIN >= ".(-intval($db_record["RIGHT_MARGIN"]))."
					");

					if(isset($arParents[$PARENT_ID]))
					{
						$DB->Query("
							UPDATE b_iblock_section SET
								TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
								,RIGHT_MARGIN = RIGHT_MARGIN + ".$distance."
							WHERE
								IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
								AND LEFT_MARGIN <= ".$arParents[$PARENT_ID]["LEFT_MARGIN"]."
								AND RIGHT_MARGIN >= ".$arParents[$PARENT_ID]["RIGHT_MARGIN"]."
						");
					}
				}
				//Check if parent was changed
				if(isset($arFields["IBLOCK_SECTION_ID"]) && $arFields["IBLOCK_SECTION_ID"]!=$db_record["IBLOCK_SECTION_ID"])
				{
					$rsSection = CIBlockSection::GetByID($ID);
					$arSection = $rsSection->Fetch();

					$strSql = "
						SELECT ID, GLOBAL_ACTIVE
						FROM b_iblock_section
						WHERE IBLOCK_ID = ".$arSection["IBLOCK_ID"]."
						AND ID = ".intval($arFields["IBLOCK_SECTION_ID"])."
					";
					$rsParent = $DB->Query($strSql);
					$arParent = $rsParent->Fetch();
					//If new parent is not globally active
					//or we are not active either
					//we must be not globally active too
					if(($arParent && $arParent["GLOBAL_ACTIVE"] == "N") || ($arFields["ACTIVE"] == "N"))
					{
						$DB->Query("
							UPDATE b_iblock_section SET
								TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
								,GLOBAL_ACTIVE = 'N'
							WHERE
								IBLOCK_ID = ".$arSection["IBLOCK_ID"]."
								AND LEFT_MARGIN >= ".intval($arSection["LEFT_MARGIN"])."
								AND RIGHT_MARGIN <= ".intval($arSection["RIGHT_MARGIN"])."
						");
					}
					//New parent is globally active
					//And we WAS NOT active
					//But is going to be
					elseif($arSection["ACTIVE"] == "N" && $arFields["ACTIVE"] == "Y")
					{
						$this->RecalcGlobalActiveFlag($arSection);
					}
					//New parent is globally active
					//And we WAS active but NOT globally active
					//But is going to be
					elseif(
						(!$arParent || $arParent["GLOBAL_ACTIVE"] == "Y")
						&& $arSection["GLOBAL_ACTIVE"] == "N"
						&& ($arSection["ACTIVE"] == "Y" || $arFields["ACTIVE"] == "Y")
					)
					{
						$this->RecalcGlobalActiveFlag($arSection);
					}
					//Otherwise we may not to change anything
				}
				//Parent not changed
				//but we are going to change activity flag
				elseif(isset($arFields["ACTIVE"]) && $arFields["ACTIVE"] != $db_record["ACTIVE"])
				{
					//Make all children globally inactive
					if($arFields["ACTIVE"] == "N")
					{
						$DB->Query("
							UPDATE b_iblock_section SET
								TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
								,GLOBAL_ACTIVE = 'N'
							WHERE
								IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
								AND LEFT_MARGIN >= ".intval($db_record["LEFT_MARGIN"])."
								AND RIGHT_MARGIN <= ".intval($db_record["RIGHT_MARGIN"])."
						");
					}
					else
					{
						//Check for parent activity
						$strSql = "
							SELECT ID, GLOBAL_ACTIVE
							FROM b_iblock_section
							WHERE IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
							AND ID = ".intval($db_record["IBLOCK_SECTION_ID"])."
						";
						$rsParent = $DB->Query($strSql);
						$arParent = $rsParent->Fetch();
						//Parent is active
						//and we changed
						//so need to recalc
						if(!$arParent || $arParent["GLOBAL_ACTIVE"] == "Y")
							$this->RecalcGlobalActiveFlag($db_record);
					}
				}
			}

			unset(self::$arSectionCodeCache[$ID]);
			self::$arSectionPathCache = array();
			self::$arSectionNavChainCache = array();

			if($arIBlock["RIGHTS_MODE"] === "E")
			{
				$obSectionRights = new CIBlockSectionRights($arIBlock["ID"], $ID);
				//Check if parent changed with extended rights mode
				if(
					isset($arFields["IBLOCK_SECTION_ID"])
					&& $arFields["IBLOCK_SECTION_ID"] != $db_record["IBLOCK_SECTION_ID"]
				)
				{
					$obSectionRights->ChangeParents(array($db_record["IBLOCK_SECTION_ID"]), array($arFields["IBLOCK_SECTION_ID"]));
				}

				if(array_key_exists("RIGHTS", $arFields) && is_array($arFields["RIGHTS"]))
					$obSectionRights->SetRights($arFields["RIGHTS"]);
			}

			if (array_key_exists("IPROPERTY_TEMPLATES", $arFields))
			{
				$ipropTemplates = new \Bitrix\Iblock\InheritedProperty\SectionTemplates($arIBlock["ID"], $ID);
				$ipropTemplates->set($arFields["IPROPERTY_TEMPLATES"]);
			}

			$uf_updated = $GLOBALS["USER_FIELD_MANAGER"]->Update("IBLOCK_".$db_record["IBLOCK_ID"]."_SECTION", $ID, $arFields);
			if($uf_updated)
			{
				$DB->Query("UPDATE b_iblock_section SET TIMESTAMP_X = ".$DB->CurrentTimeFunction()." WHERE ID = ".$ID);
			}

			if(
				CIBlock::GetArrayByID($db_record["IBLOCK_ID"], "SECTION_PROPERTY") === "Y"
				&& array_key_exists("SECTION_PROPERTY", $arFields)
				&& is_array($arFields["SECTION_PROPERTY"])
			)
			{
				CIBlockSectionPropertyLink::DeleteBySection($ID, array_keys($arFields["SECTION_PROPERTY"]));
				foreach($arFields["SECTION_PROPERTY"] as $PROPERTY_ID => $arLink)
					CIBlockSectionPropertyLink::Set($ID, $PROPERTY_ID, $arLink);
			}

			if($bUpdateSearch)
				CIBlockSection::UpdateSearch($ID);

			if($arIBlock["FIELDS"]["LOG_SECTION_EDIT"]["IS_REQUIRED"] == "Y")
			{
				$USER_ID = is_object($USER)? intval($USER->GetID()) : 0;
				$arEvents = GetModuleEvents("main", "OnBeforeEventLog", true);
				if(empty($arEvents) || ExecuteModuleEventEx($arEvents[0],  array($USER_ID))===false)
				{
					$rsSection = CIBlockSection::GetList(array(), array("=ID"=>$ID), false,  array("LIST_PAGE_URL", "NAME", "CODE"));
					$arSection = $rsSection->GetNext();
					$res = array(
						"ID" => $ID,
						"CODE" => $arSection["CODE"],
						"NAME" => $arSection["NAME"],
						"SECTION_NAME" => $arIBlock["SECTION_NAME"],
						"USER_ID" => $USER_ID,
						"IBLOCK_PAGE_URL" => $arSection["LIST_PAGE_URL"],
					);
					CEventLog::Log(
						"IBLOCK",
						"IBLOCK_SECTION_EDIT",
						"iblock",
						$arIBlock["ID"],
						serialize($res)
					);
				}
			}

			$Result = true;

			/*********** QUOTA ***************/
			$_SESSION["SESS_RECOUNT_DB"] = "Y";
			/*********** QUOTA ***************/
		}

		$arFields["ID"] = $ID;
		$arFields["IBLOCK_ID"] = $db_record["IBLOCK_ID"];
		$arFields["RESULT"] = &$Result;

		foreach (GetModuleEvents("iblock", "OnAfterIBlockSectionUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arFields));

		CIBlock::clearIblockTagCache($arIBlock['ID']);

		return $Result;
	}

	///////////////////////////////////////////////////////////////////
	// Function delete section by its ID
	///////////////////////////////////////////////////////////////////
	
	/**
	* <p>Метод удаляет раздел с кодом <i>ID</i>, вместе со всеми подразделами и элементами, которые привязаны только к этому разделу. Также удаляются значения свойств типа "Привязка к разделу" указывающие на удаляемый. При установленном модуле поиска раздел удаляется из поискового индекса. Перед удалением раздела вызываются обработчики события <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblocksectiondelete.php">OnBeforeIBlockSectionDelete</a> из которых можно отменить это действие. После удаления вызывается обработчик события OnAfterIBlockSectionDelete. Метод статический.   <br></p>
	*
	*
	* @param int $intID  Код раздела.
	*
	* @param bool $bCheckPermissions = true Флаг проверки прав доступа. Необязательный параметр.
	*
	* @return bool <br>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>if(CIBlock::GetPermission($IBLOCK_ID)&gt;='W')<br>{<br>	$DB-&gt;StartTransaction();<br>	if(!CIBlockSection::Delete($SECTION_ID))<br>	{<br>		$strWarning .= 'Error.';<br>		$DB-&gt;Rollback();<br>	}<br>	else<br>		$DB-&gt;Commit();<br>}<br>?&gt;<br>
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblocksectiondelete.php">OnBeforeIBlockSectionDelete</a></li>
	*   <li>OnAfterIBlockSectionDelete     <br> </li> </ul><br><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID, $bCheckPermissions = true)
	{
		$err_mess = "FILE: ".__FILE__."<br>LINE: ";
		global $DB, $APPLICATION, $USER;
		$ID = (int)$ID;

		$APPLICATION->ResetException();
		foreach (GetModuleEvents("iblock", "OnBeforeIBlockSectionDelete", true) as $arEvent)
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

		$s = CIBlockSection::GetList(Array(), Array("ID"=>$ID, "CHECK_PERMISSIONS"=>($bCheckPermissions? "Y": "N")));
		if($s = $s->Fetch())
		{
			CIBlock::_transaction_lock($s["IBLOCK_ID"]);

			$iblockelements = CIBlockElement::GetList(Array(), Array("SECTION_ID"=>$ID, "SHOW_HISTORY"=>"Y", "IBLOCK_ID"=>$s["IBLOCK_ID"]), false, false, array("ID", "IBLOCK_ID", "WF_PARENT_ELEMENT_ID"));
			while($iblockelement = $iblockelements->Fetch())
			{
				$strSql = "
					SELECT IBLOCK_SECTION_ID
					FROM b_iblock_section_element
					WHERE
						IBLOCK_ELEMENT_ID = ".$iblockelement["ID"]."
						AND IBLOCK_SECTION_ID<>".$ID."
						AND ADDITIONAL_PROPERTY_ID IS NULL
					ORDER BY
						IBLOCK_SECTION_ID
				";
				$db_section_element = $DB->Query($strSql);
				if($ar_section_element = $db_section_element->Fetch())
				{
					$DB->Query("
						UPDATE b_iblock_element
						SET IBLOCK_SECTION_ID=".$ar_section_element["IBLOCK_SECTION_ID"]."
						WHERE ID=".IntVal($iblockelement["ID"])."
					", false, $err_mess.__LINE__);
				}
				elseif(IntVal($iblockelement["WF_PARENT_ELEMENT_ID"])<=0)
				{
					if(!CIBlockElement::Delete($iblockelement["ID"]))
						return false;
				}
				else
				{
					$DB->Query("
						UPDATE b_iblock_element
						SET IBLOCK_SECTION_ID=NULL, IN_SECTIONS='N'
						WHERE ID=".IntVal($iblockelement["ID"])."
					", false, $err_mess.__LINE__);
				}
			}

			$iblocksections = CIBlockSection::GetList(
				array(),
				array("SECTION_ID"=>$ID, "CHECK_PERMISSIONS"=>($bCheckPermissions? "Y": "N")),
				false,
				array("ID")
			);
			while($iblocksection = $iblocksections->Fetch())
			{
				if(!CIBlockSection::Delete($iblocksection["ID"], $bCheckPermissions))
					return false;
			}

			CFile::Delete($s["PICTURE"]);
			CFile::Delete($s["DETAIL_PICTURE"]);

			static $arDelCache;
			if(!is_array($arDelCache))
				$arDelCache = Array();
			if(!is_set($arDelCache, $s["IBLOCK_ID"]))
			{
				$arDelCache[$s["IBLOCK_ID"]] = false;
				$db_ps = $DB->Query("SELECT ID,IBLOCK_ID,VERSION,MULTIPLE FROM b_iblock_property WHERE PROPERTY_TYPE='G' AND (LINK_IBLOCK_ID=".$s["IBLOCK_ID"]." OR LINK_IBLOCK_ID=0 OR LINK_IBLOCK_ID IS NULL)", false, $err_mess.__LINE__);
				while($ar_ps = $db_ps->Fetch())
				{
					if($ar_ps["VERSION"]==2)
					{
						if($ar_ps["MULTIPLE"]=="Y")
							$strTable = "b_iblock_element_prop_m".$ar_ps["IBLOCK_ID"];
						else
							$strTable = "b_iblock_element_prop_s".$ar_ps["IBLOCK_ID"];
					}
					else
					{
						$strTable = "b_iblock_element_property";
					}
					$arDelCache[$s["IBLOCK_ID"]][$strTable][] = $ar_ps["ID"];
				}
			}

			if($arDelCache[$s["IBLOCK_ID"]])
			{
				foreach($arDelCache[$s["IBLOCK_ID"]] as $strTable=>$arProps)
				{
					if(strncmp("b_iblock_element_prop_s", $strTable, 23)==0)
					{
						$tableFields = $DB->GetTableFields($strTable);
						foreach($arProps as $prop_id)
						{
							$strSql = "UPDATE ".$strTable." SET PROPERTY_".$prop_id."=null";
							if (isset($tableFields["DESCRIPTION_".$prop_id]))
								$strSql .= ",DESCRIPTION_".$prop_id."=null";
							$strSql .= " WHERE PROPERTY_".$prop_id."=".$s["ID"];
							if(!$DB->Query($strSql, false, $err_mess.__LINE__))
								return false;
						}
					}
					elseif(strncmp("b_iblock_element_prop_m", $strTable, 23)==0)
					{
						$tableFields = $DB->GetTableFields(str_replace("prop_m", "prop_s", $strTable));
						$strSql = "SELECT IBLOCK_PROPERTY_ID, IBLOCK_ELEMENT_ID FROM ".$strTable." WHERE IBLOCK_PROPERTY_ID IN (".implode(", ", $arProps).") AND VALUE_NUM=".$s["ID"];
						$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
						while($ar = $rs->Fetch())
						{
							$strSql = "
								UPDATE ".str_replace("prop_m", "prop_s", $strTable)."
								SET	PROPERTY_".$ar["IBLOCK_PROPERTY_ID"]."=null
									".(isset($tableFields["DESCRIPTION_".$ar["IBLOCK_PROPERTY_ID"]])? ",DESCRIPTION_".$ar["IBLOCK_PROPERTY_ID"]."=null": "")."
								WHERE IBLOCK_ELEMENT_ID = ".$ar["IBLOCK_ELEMENT_ID"]."
							";
							if(!$DB->Query($strSql, false, $err_mess.__LINE__))
								return false;
						}
						$strSql = "DELETE FROM ".$strTable." WHERE IBLOCK_PROPERTY_ID IN (".implode(", ", $arProps).") AND VALUE_NUM=".$s["ID"];
						if(!$DB->Query($strSql, false, $err_mess.__LINE__))
							return false;
					}
					else
					{
						$strSql = "DELETE FROM ".$strTable." WHERE IBLOCK_PROPERTY_ID IN (".implode(", ", $arProps).") AND VALUE_NUM=".$s["ID"];
						if(!$DB->Query($strSql, false, $err_mess.__LINE__))
							return false;
					}
				}
			}

			CIBlockSectionPropertyLink::DeleteBySection($ID);
			$DB->Query("DELETE FROM b_iblock_section_element WHERE IBLOCK_SECTION_ID=".IntVal($ID), false, $err_mess.__LINE__);

			if(CModule::IncludeModule("search"))
				CSearch::DeleteIndex("iblock", "S".$ID);

			$GLOBALS["USER_FIELD_MANAGER"]->Delete("IBLOCK_".$s["IBLOCK_ID"]."_SECTION", $ID);

			//Delete the hole in the tree
			$ss = $DB->Query("
				SELECT
					IBLOCK_ID,
					LEFT_MARGIN,
					RIGHT_MARGIN
				FROM
					b_iblock_section
				WHERE
					ID = ".$s["ID"]."
			");
			$ss = $ss->Fetch();
			if(($ss["RIGHT_MARGIN"] > 0) && ($ss["LEFT_MARGIN"] > 0))
			{
				$DB->Query("
					UPDATE b_iblock_section SET
						TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
						,RIGHT_MARGIN = RIGHT_MARGIN - 2
					WHERE
						IBLOCK_ID = ".$ss["IBLOCK_ID"]."
						AND RIGHT_MARGIN > ".$ss["RIGHT_MARGIN"]."
				");

				$DB->Query("
					UPDATE b_iblock_section SET
						TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
						,LEFT_MARGIN = LEFT_MARGIN - 2
					WHERE
						IBLOCK_ID = ".$ss["IBLOCK_ID"]."
						AND LEFT_MARGIN > ".$ss["LEFT_MARGIN"]."
				");
			}

			$obSectionRights = new CIBlockSectionRights($s["IBLOCK_ID"], $ID);
			$obSectionRights->DeleteAllRights();

			$ipropTemplates = new \Bitrix\Iblock\InheritedProperty\SectionTemplates($s["IBLOCK_ID"], $ID);
			$ipropTemplates->delete();

			/************* QUOTA *************/
			$_SESSION["SESS_RECOUNT_DB"] = "Y";
			/************* QUOTA *************/

			$arIBlockFields = CIBlock::GetArrayByID($s["IBLOCK_ID"], "FIELDS");
			if($arIBlockFields["LOG_SECTION_DELETE"]["IS_REQUIRED"] == "Y")
			{
				$USER_ID = is_object($USER)? intval($USER->GetID()) : 0;
				$arEvents = GetModuleEvents("main", "OnBeforeEventLog", true);
				if(empty($arEvents) || ExecuteModuleEventEx($arEvents[0],  array($USER_ID))===false)
				{
					$rsSection = CIBlockSection::GetList(
						array(),
						array("=ID"=>$ID, "CHECK_PERMISSIONS"=>($bCheckPermissions? "Y": "N")),
						false,
						array("LIST_PAGE_URL", "NAME", "CODE")
					);
					$arSection = $rsSection->GetNext();
					$res = array(
						"ID" => $ID,
						"CODE" => $arSection["CODE"],
						"NAME" => $arSection["NAME"],
						"SECTION_NAME" => CIBlock::GetArrayByID($s["IBLOCK_ID"], "SECTION_NAME"),
						"USER_ID" => $USER_ID,
						"IBLOCK_PAGE_URL" => $arSection["LIST_PAGE_URL"],
					);
					CEventLog::Log(
						"IBLOCK",
						"IBLOCK_SECTION_DELETE",
						"iblock",
						$s["IBLOCK_ID"],
						serialize($res)
					);
				}
			}

			$res = $DB->Query("DELETE FROM b_iblock_section WHERE ID=".IntVal($ID), false, $err_mess.__LINE__);

			if($res)
			{
				foreach (GetModuleEvents("iblock", "OnAfterIBlockSectionDelete", true) as $arEvent)
					ExecuteModuleEventEx($arEvent, array($s));

				CIBlock::clearIblockTagCache($s['IBLOCK_ID']);
			}

			return $res;
		}

		return true;
	}

	///////////////////////////////////////////////////////////////////
	// Check function called from Add and Update
	///////////////////////////////////////////////////////////////////
	public function CheckFields(&$arFields, $ID=false)
	{
		global $DB, $APPLICATION;
		$this->LAST_ERROR = "";

		if(($ID===false || is_set($arFields, "NAME")) && strlen($arFields["NAME"])<=0)
			$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_SECTION")."<br>";

		if(
			is_array($arFields["PICTURE"])
			&& array_key_exists("bucket", $arFields["PICTURE"])
			&& is_object($arFields["PICTURE"]["bucket"])
		)
		{
			//This is trusted image from xml import
		}
		elseif(
			isset($arFields["PICTURE"])
			&& is_array($arFields["PICTURE"])
			&& isset($arFields["PICTURE"]["name"])
		)
		{
			$error = CFile::CheckImageFile($arFields["PICTURE"]);
			if (strlen($error) > 0)
				$this->LAST_ERROR .= $error."<br>";
		}

		if(
			is_array($arFields["DETAIL_PICTURE"])
			&& array_key_exists("bucket", $arFields["DETAIL_PICTURE"])
			&& is_object($arFields["DETAIL_PICTURE"]["bucket"])
		)
		{
			//This is trusted image from xml import
		}
		elseif(
			isset($arFields["DETAIL_PICTURE"])
			&& is_array($arFields["DETAIL_PICTURE"])
			&& isset($arFields["DETAIL_PICTURE"]["name"])
		)
		{
			$error = CFile::CheckImageFile($arFields["DETAIL_PICTURE"]);
			if (strlen($error) > 0)
				$this->LAST_ERROR .= $error."<br>";
		}

		$arIBlock = false;
		$arThis = false;

		if($ID === false)
		{
			if(!array_key_exists("IBLOCK_ID", $arFields))
			{
				$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_BLOCK_ID")."<br>";
			}
			else
			{
				$arIBlock = CIBlock::GetArrayByID($arFields["IBLOCK_ID"]);
				if(!$arIBlock)
					$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_BLOCK_ID")."<br>";
			}
		}
		else
		{
			$rsThis = $DB->Query("SELECT ID, IBLOCK_ID, DETAIL_PICTURE, PICTURE FROM b_iblock_section WHERE ID = ".intval($ID));
			$arThis = $rsThis->Fetch();
			if(!$arThis)
			{
				$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_SECTION_ID", array("#ID#" => intval($ID)))."<br>";
			}
			else
			{
				$arIBlock = CIBlock::GetArrayByID($arThis["IBLOCK_ID"]);
				if(!$arIBlock)
					$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_BLOCK_ID")."<br>";
			}
		}

		$arParent = false;
		$IBLOCK_SECTION_ID = isset($arFields["IBLOCK_SECTION_ID"])? intval($arFields["IBLOCK_SECTION_ID"]): 0;

		if(($IBLOCK_SECTION_ID > 0) && (strlen($this->LAST_ERROR) <= 0))
		{
			$rsParent = $DB->Query("SELECT ID, IBLOCK_ID FROM b_iblock_section WHERE ID = ".$IBLOCK_SECTION_ID);
			$arParent = $rsParent->Fetch();
			if(!$arParent)
				$this->LAST_ERROR = GetMessage("IBLOCK_BAD_BLOCK_SECTION_PARENT")."<br>";
		}

		if($arParent && $arIBlock)
		{
			if($arParent["IBLOCK_ID"] != $arIBlock["ID"])
				$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_BLOCK_SECTION_ID_PARENT")."<br>";
		}

		if($arParent && (strlen($this->LAST_ERROR) <= 0))
		{
			$rch = $DB->Query("
				SELECT 'x'
				FROM
					b_iblock_section bsto
					,b_iblock_section bsfrom
				WHERE
					bsto.ID = ".$arParent["ID"]."
					AND bsfrom.ID = ".intval($ID)."
					AND bsto.LEFT_MARGIN >= bsfrom.LEFT_MARGIN
					AND bsto.LEFT_MARGIN <= bsfrom.RIGHT_MARGIN
			");
			if($rch->Fetch())
				$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_BLOCK_SECTION_RECURSE")."<br>";
		}

		if($arIBlock)
		{
			if(
				array_key_exists("CODE", $arFields)
				&& strlen($arFields["CODE"])
				&& is_array($arIBlock["FIELDS"]["SECTION_CODE"]["DEFAULT_VALUE"])
				&& $arIBlock["FIELDS"]["SECTION_CODE"]["DEFAULT_VALUE"]["UNIQUE"] == "Y"
			)
			{
				$res = $DB->Query("
					SELECT ID
					FROM b_iblock_section
					WHERE IBLOCK_ID = ".$arIBlock["ID"]."
					AND CODE = '".$DB->ForSQL($arFields["CODE"])."'
					AND ID <> ".intval($ID)
				);
				if($res->Fetch())
					$this->LAST_ERROR .= GetMessage("IBLOCK_DUP_SECTION_CODE")."<br>";
			}

			foreach($arIBlock["FIELDS"] as $FIELD_ID => $field)
			{
				if(!preg_match("/^SECTION_(.+)$/", $FIELD_ID, $match))
					continue;

				$FIELD_ID = $match[1];

				if($field["IS_REQUIRED"] === "Y")
				{
					switch($FIELD_ID)
					{
					case "NAME":
					case "DESCRIPTION_TYPE":
						//We should never check for this fields
						break;
					case "PICTURE":
						$field["NAME"] = GetMessage("IBLOCK_FIELD_PICTURE");
					case "DETAIL_PICTURE":
						if($arThis && $arThis[$FIELD_ID] > 0)
						{//There was an picture so just check that it is not deleted
							if(
								array_key_exists($FIELD_ID, $arFields)
								&& is_array($arFields[$FIELD_ID])
								&& $arFields[$FIELD_ID]["del"] === "Y"
							)
								$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_SECTION_FIELD", array("#FIELD_NAME#" => $field["NAME"]))."<br>";
						}
						else
						{//There was NO picture so it MUST be present
							if(!array_key_exists($FIELD_ID, $arFields))
							{
								$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_SECTION_FIELD", array("#FIELD_NAME#" => $field["NAME"]))."<br>";
							}
							elseif(is_array($arFields[$FIELD_ID]))
							{
								if(
									$arFields[$FIELD_ID]["del"] === "Y"
									|| (array_key_exists("error", $arFields[$FIELD_ID]) && $arFields[$FIELD_ID]["error"] !== 0)
									|| $arFields[$FIELD_ID]["size"] <= 0
								)
									$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_SECTION_FIELD", array("#FIELD_NAME#" => $field["NAME"]))."<br>";
							}
							else
							{
								if(intval($arFields[$FIELD_ID]) <= 0)
									$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_SECTION_FIELD", array("#FIELD_NAME#" => $field["NAME"]))."<br>";
							}
						}
						break;
					default:
						if($ID===false || array_key_exists($FIELD_ID, $arFields))
						{
							if(is_array($arFields[$FIELD_ID]))
								$val = implode("", $arFields[$FIELD_ID]);
							else
								$val = $arFields[$FIELD_ID];
							if(strlen($val) <= 0)
								$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_SECTION_FIELD", array("#FIELD_NAME#" => $field["NAME"]))."<br>";
						}
						break;
					}
				}
			}
		}

		$APPLICATION->ResetException();
		if($ID===false)
			$db_events = GetModuleEvents("iblock", "OnBeforeIBlockSectionAdd", true);
		else
		{
			$arFields["ID"] = $ID;
			$arFields["IBLOCK_ID"] = $arIBlock["ID"];
			$db_events = GetModuleEvents("iblock", "OnBeforeIBlockSectionUpdate", true);
		}

		/****************************** QUOTA ******************************/
		if(empty($this->LAST_ERROR) && (COption::GetOptionInt("main", "disk_space") > 0))
		{
			$quota = new CDiskQuota();
			if(!$quota->checkDiskQuota($arFields))
				$this->LAST_ERROR = $quota->LAST_ERROR;
		}
		/****************************** QUOTA ******************************/

		foreach ($db_events as $arEvent)
		{
			$bEventRes = ExecuteModuleEventEx($arEvent, array(&$arFields));
			if($bEventRes===false)
			{
				if($err = $APPLICATION->GetException())
					$this->LAST_ERROR .= $err->GetString()."<br>";
				else
				{
					$APPLICATION->ThrowException("Unknown error");
					$this->LAST_ERROR .= "Unknown error.<br>";
				}
				break;
			}
		}

		if(strlen($this->LAST_ERROR)>0)
			return false;

		return true;
	}

	public static function TreeReSort($IBLOCK_ID, $ID=0, $cnt=0, $depth=0, $ACTIVE="Y")
	{
		global $DB;
		$IBLOCK_ID = IntVal($IBLOCK_ID);

		if($ID==0)
		{
			CIBlock::_transaction_lock($IBLOCK_ID);
		}

		if($ID > 0)
		{
			$DB->Query("
				UPDATE
					b_iblock_section
				SET
					TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
					,RIGHT_MARGIN=".IntVal($cnt)."
					,LEFT_MARGIN=".IntVal($cnt)."
				WHERE
					ID=".IntVal($ID)
			);
		}

		$strSql = "
			SELECT BS.ID, BS.ACTIVE
			FROM b_iblock_section BS
			WHERE BS.IBLOCK_ID = ".$IBLOCK_ID."
			AND ".($ID>0? "BS.IBLOCK_SECTION_ID=".IntVal($ID): "BS.IBLOCK_SECTION_ID IS NULL")."
			ORDER BY BS.SORT, BS.NAME
		";

		$cnt++;
		$res = $DB->Query($strSql);
		while($arr = $res->Fetch())
			$cnt = CIBlockSection::TreeReSort($IBLOCK_ID, $arr["ID"], $cnt, $depth+1, ($ACTIVE=="Y" && $arr["ACTIVE"]=="Y" ? "Y" : "N"));

		if($ID==0)
		{
			return true;
		}

		$DB->Query("
			UPDATE
				b_iblock_section
			SET
				TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
				,RIGHT_MARGIN=".IntVal($cnt)."
				,DEPTH_LEVEL=".IntVal($depth)."
				,GLOBAL_ACTIVE='".$ACTIVE."'
			WHERE
				ID=".IntVal($ID)
		);

		return $cnt+1;
	}

	public static function ReSort($IBLOCK_ID, $ID=0, $cnt=0, $depth=0, $ACTIVE="Y")
	{
		$cnt = self::TreeReSort($IBLOCK_ID, $ID, $cnt, $depth, $ACTIVE);
		$obIBlockRights = new CIBlockRights($IBLOCK_ID);
		$obIBlockRights->Recalculate();

		return $cnt;
	}

	public static function UpdateSearch($ID, $bOverWrite=false)
	{
		if(!CModule::IncludeModule("search")) return;

		global $DB;
		$ID = Intval($ID);

		static $arGroups = array();
		static $arSITE = array();

		$strSql = "
			SELECT BS.ID, BS.NAME, BS.DESCRIPTION_TYPE, BS.DESCRIPTION, BS.XML_ID as EXTERNAL_ID,
				BS.CODE, BS.IBLOCK_ID, B.IBLOCK_TYPE_ID,
				".$DB->DateToCharFunction("BS.TIMESTAMP_X")." as LAST_MODIFIED,
				B.CODE as IBLOCK_CODE, B.XML_ID as IBLOCK_EXTERNAL_ID, B.SECTION_PAGE_URL,
				B.ACTIVE as ACTIVE1,
				BS.GLOBAL_ACTIVE as ACTIVE2,
				B.INDEX_SECTION, B.RIGHTS_MODE
			FROM b_iblock_section BS, b_iblock B
			WHERE BS.IBLOCK_ID=B.ID
				AND BS.ID=".$ID;

		$dbrIBlockSection = $DB->Query($strSql);

		if($arIBlockSection = $dbrIBlockSection->Fetch())
		{
			$IBLOCK_ID = $arIBlockSection["IBLOCK_ID"];
			$SECTION_URL =
					"=ID=".$arIBlockSection["ID"].
					"&EXTERNAL_ID=".$arIBlockSection["EXTERNAL_ID"].
					"&IBLOCK_TYPE_ID=".$arIBlockSection["IBLOCK_TYPE_ID"].
					"&IBLOCK_ID=".$arIBlockSection["IBLOCK_ID"].
					"&IBLOCK_CODE=".$arIBlockSection["IBLOCK_CODE"].
					"&IBLOCK_EXTERNAL_ID=".$arIBlockSection["IBLOCK_EXTERNAL_ID"].
					"&CODE=".$arIBlockSection["CODE"];

			if($arIBlockSection["ACTIVE1"]!="Y" || $arIBlockSection["ACTIVE2"]!="Y" || $arIBlockSection["INDEX_SECTION"]!="Y")
			{
				CSearch::DeleteIndex("iblock", "S".$arIBlockSection["ID"]);
				return;
			}

			if(!array_key_exists($IBLOCK_ID, $arGroups))
			{
				$arGroups[$IBLOCK_ID] = array();
				$strSql =
					"SELECT GROUP_ID ".
					"FROM b_iblock_group ".
					"WHERE IBLOCK_ID= ".$IBLOCK_ID." ".
					"	AND PERMISSION>='R' ".
					"ORDER BY GROUP_ID";

				$dbrIBlockGroup = $DB->Query($strSql);
				while($arIBlockGroup = $dbrIBlockGroup->Fetch())
				{
					$arGroups[$IBLOCK_ID][] = $arIBlockGroup["GROUP_ID"];
					if($arIBlockGroup["GROUP_ID"]==2) break;
				}
			}

			if(!array_key_exists($IBLOCK_ID, $arSITE))
			{
				$arSITE[$IBLOCK_ID] = array();
				$strSql =
					"SELECT SITE_ID ".
					"FROM b_iblock_site ".
					"WHERE IBLOCK_ID= ".$IBLOCK_ID;

				$dbrIBlockSite = $DB->Query($strSql);
				while($arIBlockSite = $dbrIBlockSite->Fetch())
					$arSITE[$IBLOCK_ID][] = $arIBlockSite["SITE_ID"];
			}

			$BODY =
				($arIBlockSection["DESCRIPTION_TYPE"]=="html" ?
					CSearch::KillTags($arIBlockSection["DESCRIPTION"])
				:
					$arIBlockSection["DESCRIPTION"]
				);

			$BODY .= $GLOBALS["USER_FIELD_MANAGER"]->OnSearchIndex("IBLOCK_".$arIBlockSection["IBLOCK_ID"]."_SECTION", $arIBlockSection["ID"]);

			if($arIBlockSection["RIGHTS_MODE"] !== "E")
				$arPermissions = $arGroups[$IBLOCK_ID];
			else
			{
				$obSectionRights = new CIBlockSectionRights($IBLOCK_ID, $arIBlockSection["ID"]);
				$arPermissions = $obSectionRights->GetGroups(array("section_read"));
			}

			CSearch::Index("iblock", "S".$ID, array(
				"LAST_MODIFIED" => $arIBlockSection["LAST_MODIFIED"],
				"TITLE" => $arIBlockSection["NAME"],
				"PARAM1" => $arIBlockSection["IBLOCK_TYPE_ID"],
				"PARAM2" => $IBLOCK_ID,
				"SITE_ID" => $arSITE[$IBLOCK_ID],
				"PERMISSIONS" => $arPermissions,
				"URL" => $SECTION_URL,
				"BODY" => $BODY,
			), $bOverWrite);
		}
	}

	/**
	 * @param array $arOrder
	 * @param array $arFilter
	 * @param bool $bIncCnt
	 * @param bool|array $arSelectedFields
	 * @return CDBResult
	 */
	
	/**
	* <p>Возвращает список разделов и элементов, отсортированных в порядке <i>arOrder</i> по фильтру <i>arFilter</i>. Нестатический метод.</p>
	*
	*
	* @param array $arOrder = Array("SORT"=>"ASC") Массив для сортировки, имеющий вид <i>by1</i>=&gt;<i>order1</i>[,
	* <i>by2</i>=&gt;<i>order2</i> [, ..]], где <i> by1, ... </i> - поле сортировки, может
	* принимать значения: <br><br> для элементов:   <ul> <li> <b>id</b> - ID элемента;
	* 				</li>                     <li> <b>sort</b> - индекс сортировки; 				</li>                     <li>
	* <b>timestamp_x</b> - дата изменения; 				</li>                     <li> <b>name</b> - название;
	* 				</li>                     <li> <b>active_from</b> или <span style="font-weight: bold;">date_active_from</span> -
	* начало периода действия элемента; 				</li>                     <li> <b>active_to</b>
	* или <span style="font-weight: bold;">date_active_to</span> - окончание периода действия
	* элемента; 				</li>                     <li> <b>status</b> - код статуса элемента в
	* документообороте; 				</li>                     <li> <b>code</b> - символьный код
	* элемента; 				</li>                     <li> <b>iblock_id</b> - числовой код
	* информационного блока; 				</li>                     <li> <b>modified_by</b> - код
	* последнего изменившего пользователя; 				</li>                     <li> <b>active</b> -
	* признак активности элемента; 				</li>                     <li> <i>show_counter </i>-
	* количество показов элемента (учитывается методом <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/index.php">CIBlockElement</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/counterinc.php">CounterInc</a>); 				</li>         
	*            <li> <b>show_counter_start</b> - время первого показа элемента
	* (учитывается методом <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/index.php">CIBlockElement</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/counterinc.php">CounterInc</a>); 				</li>         
	*            <li> <b>shows</b> - усредненное количество показов (количество
	* показов / продолжительность показа); 				</li>                     <li> <b>rand</b> -
	* случайный порядок;</li>                     <li> <span style="font-weight: bold;">xml_id</span> или
	* <span style="font-weight: bold;">external_id</span> - внешний код;</li>                     <li> <span
	* style="font-weight: bold;">tags</span> - теги;</li>                     <li> <span style="font-weight:
	* bold;">created</span> - время создания;</li>                     <li> <span style="font-weight:
	* bold;">created_date</span> - дата создания без учета времени;</li>                     <li>
	* <span style="font-weight: bold;">cnt</span> - количество элементов (только при
	* заданной группировке);              <br> </li>                     <li>
	* <b>property_&lt;PROPERTY_CODE&gt;</b> - по значению свойства с числовым или
	* символьным кодом <i>PROPERTY_CODE</i> (например, PROPERTY_123 или PROPERTY_NEWS_SOURCE);
	* 				</li>                     <li> <b>propertysort_&lt;PROPERTY_CODE&gt;</b> - по индексу сортировки
	* варианта значения свойства. Только для свойств типа "Список" ;
	* 				</li>                     <li> <b>catalog_&lt;CATALOG_FIELD&gt;_&lt;PRICE_TYPE&gt;</b> - по полю CATALOG_FIELD
	* (может быть PRICE - цена или CURRENCY - валюта) из цены с типом <i>PRICE_TYPE</i>
	* (например, catalog_PRICE_1 или CATALOG_CURRENCY_3);</li>           <li> <b>CATALOG_QUANTITY</b> - общее
	* количество товара;</li>           <li> <b>CATALOG_WEIGHT</b> - вес товара;</li>           <li>
	* <b>CATALOG_AVAILABLE</b> - признак доступности к покупке (Y|N). Товар считается
	* недоступным, если его количество меньше либо равно нулю, включен
	* количественный учет и запрещена покупка при нулевом
	* количестве;</li>                     <li> <span style="font-weight:
	* bold;">PROPERTY_&lt;PROPERTY_CODE&gt;.&lt;FIELD&gt;</span> - по значению поля элемента
	* указанного в качестве привязки. PROPERTY_CODE - символьный код свойства
	* типа привязка к элементам. FIELD может принимать значения:</li>             
	*        <ul> <li>ID                <br> </li>                         <li>TIMESTAMP_X                <br> </li>          
	*               <li>MODIFIED_BY                <br> </li>                         <li>CREATED                <br> </li>   
	*                      <li>CREATED_DATE                <br> </li>                         <li>CREATED_BY               
	* <br> </li>                         <li>IBLOCK_ID                <br> </li>                         <li>ACTIVE           
	*     <br> </li>                         <li>ACTIVE_FROM                <br> </li>                         <li>ACTIVE_TO  
	*              <br> </li>                         <li>SORT                <br> </li>                         <li>NAME     
	*           <br> </li>                         <li>SHOW_COUNTER                <br> </li>                        
	* <li>SHOW_COUNTER_START                <br> </li>                         <li>CODE                <br> </li>             
	*            <li>TAGS                <br> </li>                         <li>XML_ID                <br> </li>              
	*           <li>STATUS </li>            </ul> <li> <span style="font-weight:
	* bold;">PROPERTY_&lt;PROPERTY_CODE&gt;.PROPERTY_&lt;</span><span style="font-weight: bold;">PROPERTY_CODE2</span><span
	* style="font-weight: bold;">&gt;</span> - по значению свойства элемента указанного в
	* качестве привязки. PROPERTY_CODE - символьный код свойства типа
	* привязки к элементам. PROPERTY_CODE2- код свойства связанных элементов.
	* </li>                     <li> <b>HAS_PREVIEW_PICTURE</b> и <b>HAS_DETAIL_PICTURE</b> - сортировка по
	* наличию и отсутствию картинок.</li> </ul> <br><br> для разделов:          <ul> <li>
	* <b>id</b> - код группы;</li>          	            <li> <b>section</b> - код родительской
	* группы;</li>          	            <li> <b>name</b> - название группы;</li>                     <li>
	* <b>code</b> - символьный код группы;</li>          	            <li> <b>active</b> -
	* активности группы;</li>          	            <li> <b>left_margin</b> - левая граница;</li> 
	*         	            <li> <b>depth_level</b> - глубина вложенности (начинается с 1);</li>  
	*        	            <li> <b>sort</b> - индекс сортировки;</li>                     <li> <b>created</b> -
	* по времени создания группы;</li>                     <li> <b>created_by</b> - по
	* идентификатору создателя группы;</li>                     <li> <b>modified_by</b> - по
	* идентификатору пользователя изменившего группу;</li>          	           
	* <li> <b>element_cnt</b> - количество элементов в группе, работает только
	* если <b>bIncCnt</b> = true;</li>          	            <li> <b>timestamp_x</b> - по времени
	* последнего изменения.</li>          </ul> <i>order1, ... </i> - порядок сортировки,
	* может принимать значения:          <ul> <li> <b>asc</b> - по возрастанию;</li>       
	*   	            <li> <b>desc</b> - по убыванию.</li>          </ul> <br> Значение по
	* умолчанию Array("SORT"=&gt;"ASC") означает, что результат выборки будет
	* отсортирован по возрастанию. Если задать пустой массив Array(), то
	* результат отсортирован не будет.
	*
	* @param array $arFilter = Array() Массив вида array("фильтруемое поле"=&gt;"значение" [, ...]).
	* 	<i>Фильтруемое поле</i> может принимать значения: <br><br> для
	* элементов:         <ul> <li> <b>ID_1</b>, <b>ID_2</b> - по числовому коду (<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/filters/number.php">Число</a>) от ID_1 до ID_2; 				</li>        
	*             <li> <b>ACTIVE</b> - фильтр по активности (Y|N); передача пустого
	* значения (<i>"ACTIVE"=&gt;""</i>) выводит все элементы без учета их
	* состояния (<a href="http://dev.1c-bitrix.ru/api_help/iblock/filters/string_equal.php">Строка</a>);
	* 				</li>                     <li> <b>NAME</b> - по названию (<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/filters/string.php">Маска</a>); 				</li>                     <li>
	* <b>CODE</b> - по символьному идентификатору (<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/filters/string.php">Маска</a>); 				</li>                     <li>
	* <b>TAGS</b> - по тегам (<a href="http://dev.1c-bitrix.ru/api_help/iblock/filters/string.php">Маска</a>);
	* 				</li>                     <li> <b> EXTERNAL_ID</b> - по внешнему коду (<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/filters/string.php">Маска</a>); 				</li>                            
	* <li> <b>TIMESTAMP_X_1</b>, <b>TIMESTAMP_X_2</b> - по времени изменения (<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/filters/date.php">Дата</a>) от TIMESTAMP_X_1 до
	* TIMESTAMP_X_2;</li>                        <li> <b>DATE_CREATE_1</b>, <b>DATE_CREATE_2</b> - по времени
	* создания (<a href="http://dev.1c-bitrix.ru/api_help/iblock/filters/date.php">Дата</a>) от DATE_CREATE_1
	* до DATE_CREATE_2; 				</li>                     <li> <b>CREATED_USER_ID </b>или<b> CREATED_BY</b> - по коду
	* пользователя, добавившего элемент (<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/filters/number.php">Число</a>); 				</li>                     <li>
	* <b>DATE_ACTIVE_FROM_1</b>, <b>DATE_ACTIVE_FROM_2</b> - по дате начала активности (<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/filters/date.php">Дата</a>) от DATE_ACTIVE_FROM_1 до
	* DATE_ACTIVE_FROM_2. Формат даты должен соответствовать <a
	* href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=35&amp;LESSON_ID=1992">формату даты</a>,
	* установленному на сайте.; 				</li>                     <li> <b>DATE_ACTIVE_TO_1</b>,
	* <b>DATE_ACTIVE_TO_2</b> - по дате окончания активности (<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/filters/date.php">Дата</a>) от DATE_ACTIVE_TO_1 до
	* DATE_ACTIVE_TO_2. Формат даты должен соответствовать <a
	* href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=35&amp;LESSON_ID=1992">формату даты</a>,
	* установленному на сайте.; 				</li>                             <li> <b>IBLOCK_ID</b> - по
	* коду информационного блока (<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/filters/number.php">Число</a>); 				</li>                            
	* <li> <b>CATALOG_AVAILABLE</b> - признак доступности к покупке (Y|N). Товар
	* считается недоступным, если его количество меньше либо равно
	* нулю, включен количественный учет и запрещена покупка при
	* нулевом количестве;                      </li>           <li> <b>CATALOG_CATALOG_GROUP_ID_N</b> - по
	* типу цен;             </li>           <li> <b>CATALOG_SHOP_QUANTITY_N</b> - фильтрация по
	* диапазону количества в цене;                        </li>           <li> <b>CATALOG_QUANTITY</b>
	* - по общему количеству товара;                         </li>     <li> <b>CATALOG_WEIGHT</b> -
	* по весу товара;                         </li>           <li> <b>SHOW_COUNTER</b> - по количеству
	* показов (<a href="http://dev.1c-bitrix.ru/api_help/iblock/filters/number.php">Число</a>); 				</li>         
	*            <li> <b>SHOW_COUNTER_START</b> - по времени первого показа (<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/filters/date.php">Дата</a>); 				</li>                     <li>
	* <b>WF_COMMENTS</b> - по комментарию документооборота (<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/filters/string.php">Маска</a>); 				</li>                     <li>
	* <b>WF_STATUS_ID</b> или <span style="font-weight: bold;">WF_STATUS</span> - по коду статуса
	* документооборота (<a href="http://dev.1c-bitrix.ru/api_help/iblock/filters/number.php">Число</a>);
	* 				</li>                             <li> <b>SHOW_NEW</b> - если <b>SHOW_HISTORY</b> не установлен
	* или не равен Y и <b>SHOW_NEW</b>=Y, то будут показываться ещё
	* неопубликованные элементы вместе с опубликованными; 				</li>             
	*                <li> <b>PROPERTY_&lt;PROPERTY_CODE</b><b>&gt;</b> - фильтр по значениям свойств,
	* где PROPERTY_CODE - код свойства или символьный код. Для свойств типа
	* "Список", "Число", "Привязка к элементам" и "Привязка к разделам"  - <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/filters/number.php">Число</a>. Для прочих - <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/filters/string.php">Маска</a>; 				</li>                     <li> <b
	* style="font-weight: bold;">PROPERTY_&lt;</b><b>PROPERTY_CODE<span style="font-weight: bold;">&gt;_VALUE</span></b> -
	* фильтр по значениям списка для свойств типа "список" (<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/filters/string.php">Маска</a>), поиск будет
	* осуществляться по строковому значению списка, а не по
	* идентификатору; 				</li>                     <li> <b>CATALOG_&lt;CATALOG_FIELD&gt;_&lt;PRICE_TYPE&gt;</b>
	* - по полю 				<i>CATALOG_FIELD</i> из цены типа <i>PRICE_TYPE</i> (ID типа цены), где
	* 				<i>CATALOG_FIELD</i> может быть: PRICE - цена, CURRENCY - валюта.</li>                     <li>
	* <span style="font-weight: bold;">PROPERTY_&lt;PROPERTY_CODE&gt;.&lt;FIELD&gt;</span> - фильтр по
	* значениям полей связанных элементов. , где PROPERTY_CODE - ID или
	* символьный код свойства привязки, а FIELD - поле указанного в
	* привязке элемента.</li>          </ul> для разделов:  <ul> <li> <b>IBLOCK_ID</b> - по
	* коду родительского информационного блока;</li>          	        	            <li>
	* <b>NAME</b> - по названию (можно искать по шаблону [%_]);</li>          	            <li>
	* <b>CODE</b> - по символьному коду (по шаблону [%_]);</li>          	            <li>
	* <b>EXTERNAL_ID</b> - по внешнему коду (по шаблону [%_]);</li>          	            <li>
	* <b>SECTION_ID</b> - по коду раздела-родителя;</li>          	            <li> <b>ID_1</b>,
	* <b>ID_2</b> - по кодам разделов от ID_1 до ID_2;</li>          	            <li>
	* <b>TIMESTAMP_X_1</b>, <b>TIMESTAMP_X_2</b> - по времени последнего изменения от
	* TIMESTAMP_X_1 до TIMESTAMP_X_2;</li>                     <li> <b>DATE_CREATE_1</b>, <b>DATE_CREATE_2</b> - по
	* времени создания от DATE_CREATE_1 до DATE_CREATE_2;</li>                     <li> <b>MODIFIED_BY
	* </b>- по коду пользователя изменившему раздел;              <br> </li>               
	*      <li> <b>CREATED_BY</b> - по создателю;              <br> </li>                 	            <li>
	* <b>PROPERTY </b><i> - </i>по значениям свойств внутрилежащих элементов, PROPERTY
	* - массив вида Array("код свойства"=&gt;"значение", ...).</li>          </ul> <br>       
	* Необязательное. По умолчанию записи не фильтруются.
	*
	* @param bool $bIncCnt = false Возвращать ли поле <i>ELEMENT_CNT</i> - количество элементов в разделе.
	* При этом arFilter дополнительно обрабатывает следующие фильтруемые
	* поля:<b>            <br></b>          <ul> <li> <b>ELEMENT_SUBSECTIONS</b> - подсчитывать
	* элементы вложенных подразделов или нет (Y|N). По умолчанию Y;</li>         
	*            <li> <b>CNT_ALL</b> - подсчитывать еще неопубликованные элементы
	* (Y|N). По умолчанию N. Актуально при установленном модуле
	* документооборота;</li>                     <li> <b>CNT_ACTIVE</b> - при подсчете
	* учитывать активность элементов (Y|N). По умолчанию N. Учитывается
	* флаг активности элемента ACTIVE и даты начала и окончания
	* активности.              <br> </li>          </ul>        Необязательный параметр, по
	* умолчанию равен false.
	*
	* @param array $arSelectedFields = false Массив для выборки. Задается только для элементов.
	*
	* @return CIBlockResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/index.php">CIBlockResult</a>.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/getlist.php">CIBlockSection::GetList</a></li>  
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php">CIBlockElement::GetList</a></li> 
	* </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/getmixedlist.php
	* @author Bitrix
	*/
	public static function GetMixedList($arOrder=Array("SORT"=>"ASC"), $arFilter=Array(), $bIncCnt = false, $arSelectedFields = false)
	{
		global $DB;

		$arResult = array();

		$arSectionFilter = array (
			"IBLOCK_ID"		=>$arFilter["IBLOCK_ID"],
			"?NAME"			=>$arFilter["NAME"],
			">=ID"			=>$arFilter["ID_1"],
			"<=ID"			=>$arFilter["ID_2"],
			">=TIMESTAMP_X"		=>$arFilter["TIMESTAMP_X_1"],
			"<=TIMESTAMP_X"		=>$arFilter["TIMESTAMP_X_2"],
			"MODIFIED_BY"		=>$arFilter["MODIFIED_USER_ID"]? $arFilter["MODIFIED_USER_ID"]: $arFilter["MODIFIED_BY"],
			">=DATE_CREATE"		=>$arFilter["DATE_CREATE_1"],
			"<=DATE_CREATE"		=>$arFilter["DATE_CREATE_2"],
			"CREATED_BY"		=>$arFilter["CREATED_USER_ID"]? $arFilter["CREATED_USER_ID"]: $arFilter["CREATED_BY"],
			"CODE"			=>$arFilter["CODE"],
			"EXTERNAL_ID"		=>$arFilter["EXTERNAL_ID"],
			"ACTIVE"		=>$arFilter["ACTIVE"],

			"CNT_ALL"		=>$arFilter["CNT_ALL"],
			"ELEMENT_SUBSECTIONS"	=>$arFilter["ELEMENT_SUBSECTIONS"],
		);
		if (isset($arFilter["CHECK_PERMISSIONS"]))
		{
			$arSectionFilter['CHECK_PERMISSIONS'] = $arFilter["CHECK_PERMISSIONS"];
			$arSectionFilter['MIN_PERMISSION'] = (isset($arFilter['MIN_PERMISSION']) ? $arFilter['MIN_PERMISSION'] : 'R');
		}
		if(array_key_exists("SECTION_ID", $arFilter))
			$arSectionFilter["SECTION_ID"] = $arFilter["SECTION_ID"];

		$obSection = new CIBlockSection;
		$rsSection = $obSection->GetList($arOrder, $arSectionFilter, $bIncCnt);
		while($arSection = $rsSection->Fetch())
		{
			$arSection["TYPE"]="S";
			$arResult[]=$arSection;
		}

		$arElementFilter = array (
			"IBLOCK_ID"		=>$arFilter["IBLOCK_ID"],
			"?NAME"			=>$arFilter["NAME"],
			"SECTION_ID"		=>$arFilter["SECTION_ID"],
			">=ID"			=>$arFilter["ID_1"],
			"<=ID"			=>$arFilter["ID_2"],
			"=ID"			=> $arFilter["ID"],
			">=TIMESTAMP_X"		=>$arFilter["TIMESTAMP_X_1"],
			"<=TIMESTAMP_X"		=>$arFilter["TIMESTAMP_X_2"],
			"CODE"			=>$arFilter["CODE"],
			"EXTERNAL_ID"		=>$arFilter["EXTERNAL_ID"],
			"MODIFIED_USER_ID"	=>$arFilter["MODIFIED_USER_ID"],
			"MODIFIED_BY"		=>$arFilter["MODIFIED_BY"],
			">=DATE_CREATE"		=>$arFilter["DATE_CREATE_1"],
			"<=DATE_CREATE"		=>$arFilter["DATE_CREATE_2"],
			"CREATED_BY"		=>$arFilter["CREATED_BY"],
			"CREATED_USER_ID"	=>$arFilter["CREATED_USER_ID"],
			">=DATE_ACTIVE_FROM"	=>$arFilter["DATE_ACTIVE_FROM_1"],
			"<=DATE_ACTIVE_FROM"	=>$arFilter["DATE_ACTIVE_FROM_2"],
			">=DATE_ACTIVE_TO"	=>$arFilter["DATE_ACTIVE_TO_1"],
			"<=DATE_ACTIVE_TO"	=>$arFilter["DATE_ACTIVE_TO_2"],
			"ACTIVE"		=>$arFilter["ACTIVE"],
			"?SEARCHABLE_CONTENT"	=>$arFilter["DESCRIPTION"],
			"?TAGS"			=>$arFilter["?TAGS"],
			"WF_STATUS"		=>$arFilter["WF_STATUS"],

			"SHOW_NEW"		=> ($arFilter["SHOW_NEW"] !== "N"? "Y": "N"),
			"SHOW_BP_NEW"		=> $arFilter["SHOW_BP_NEW"]
		);
		if (isset($arFilter["CHECK_PERMISSIONS"]))
		{
			$arElementFilter['CHECK_PERMISSIONS'] = $arFilter["CHECK_PERMISSIONS"];
			$arElementFilter['MIN_PERMISSION'] = (isset($arFilter['MIN_PERMISSION']) ? $arFilter['MIN_PERMISSION'] : 'R');
		}

		foreach($arFilter as $key=>$value)
		{
			$op = CIBlock::MkOperationFilter($key);
			$newkey = strtoupper($op["FIELD"]);
			if(
				substr($newkey, 0, 9) == "PROPERTY_"
				|| substr($newkey, 0, 8) == "CATALOG_"
			)
			{
				$arElementFilter[$key] = $value;
			}
		}

		if(strlen($arFilter["SECTION_ID"])<= 0)
			unset($arElementFilter["SECTION_ID"]);

		if(!is_array($arSelectedFields))
			$arSelectedFields = Array("*");

		if(isset($arFilter["CHECK_BP_PERMISSIONS"]))
			$arElementFilter["CHECK_BP_PERMISSIONS"] = $arFilter["CHECK_BP_PERMISSIONS"];

		$obElement = new CIBlockElement;

		$rsElement = $obElement->GetList($arOrder, $arElementFilter, false, false, $arSelectedFields);
		while($arElement = $rsElement->Fetch())
		{
			$arElement["TYPE"]="E";
			$arResult[]=$arElement;
		}

		$rsResult = new CDBResult;
		$rsResult->InitFromArray($arResult);

		return $rsResult;
	}

	///////////////////////////////////////////////////////////////////
	// GetSectionElementsCount($ID, $arFilter=Array())
	///////////////////////////////////////////////////////////////////
	
	/**
	* <p>Метод считает количество элементов внутри раздела <i>ID</i>, учитывая фильтр <i>arFilter</i>. Нестатический метод.</p>
	*
	*
	* @param mixed $intID  Код раздела.
	*
	* @param array $arFilter = Array() Массив вида Array("фильтруемое поле"=&gt;"значение", ...), где
	* фильтруемое поле может принимать значения:          <br><i>CNT_ACTIVE</i> -
	* активные элементы (Y|N),<i>            <br>          CNT_ALL</i> - учитывать ещё не
	* опубликованные элементы (если установлен модуль
	* документооборота), <i>            <br>          PROPERTY</i> - массив для фильтрации
	* элементов по значениям свойств, вида Array("код
	* свойства"=&gt;"значение", ...),
	*
	* @return int 
	*
	* <h4>See Also</h4> 
	* <ul><li> <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php">CIBlockElement::GetList</a>  
	* </li></ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/getsectionelementscount.php
	* @author Bitrix
	*/
	public static function GetSectionElementsCount($ID, $arFilter=Array())
	{
		global $DB, $USER;

		$arJoinProps = array();
		$bJoinFlatProp = false;
		$arSqlSearch = array();

		if(array_key_exists("PROPERTY", $arFilter))
		{
			$val = $arFilter["PROPERTY"];
			foreach($val as $propID=>$propVAL)
			{
				$res = CIBlock::MkOperationFilter($propID);
				$propID = $res["FIELD"];
				$cOperationType = $res["OPERATION"];
				if($db_prop = CIBlockProperty::GetPropertyArray($propID, CIBlock::_MergeIBArrays($arFilter["IBLOCK_ID"], $arFilter["IBLOCK_CODE"])))
				{

					$bSave = false;
					if(array_key_exists($db_prop["ID"], $arJoinProps))
						$iPropCnt = $arJoinProps[$db_prop["ID"]];
					elseif($db_prop["VERSION"]!=2 || $db_prop["MULTIPLE"]=="Y")
					{
						$bSave = true;
						$iPropCnt=count($arJoinProps);
					}

					if(!is_array($propVAL))
						$propVAL = Array($propVAL);

					if($db_prop["PROPERTY_TYPE"]=="N" || $db_prop["PROPERTY_TYPE"]=="G" || $db_prop["PROPERTY_TYPE"]=="E")
					{
						if($db_prop["VERSION"]==2 && $db_prop["MULTIPLE"]=="N")
						{
							$r = CIBlock::FilterCreate("FPS.PROPERTY_".$db_prop["ORIG_ID"], $propVAL, "number", $cOperationType);
							$bJoinFlatProp = $db_prop["IBLOCK_ID"];
						}
						else
							$r = CIBlock::FilterCreate("FPV".$iPropCnt.".VALUE_NUM", $propVAL, "number", $cOperationType);
					}
					else
					{
						if($db_prop["VERSION"]==2 && $db_prop["MULTIPLE"]=="N")
						{
							$r = CIBlock::FilterCreate("FPS.PROPERTY_".$db_prop["ORIG_ID"], $propVAL, "string", $cOperationType);
							$bJoinFlatProp = $db_prop["IBLOCK_ID"];
						}
						else
							$r = CIBlock::FilterCreate("FPV".$iPropCnt.".VALUE", $propVAL, "string", $cOperationType);
					}

					if(strlen($r)>0)
					{
						if($bSave)
						{
							$db_prop["iPropCnt"] = $iPropCnt;
							$arJoinProps[$db_prop["ID"]] = $db_prop;
						}
						$arSqlSearch[] = $r;
					}
				}
			}
		}

		$strSqlSearch = "";
		foreach($arSqlSearch as $r)
			if(strlen($r)>0)
				$strSqlSearch .= "\n\t\t\t\tAND  (".$r.") ";

		$strSqlSearchProp = "";
		foreach($arJoinProps as $propID=>$db_prop)
		{
			if($db_prop["VERSION"]==2)
				$strTable = "b_iblock_element_prop_m".$db_prop["IBLOCK_ID"];
			else
				$strTable = "b_iblock_element_property";
			$i = $db_prop["iPropCnt"];
			$strSqlSearchProp .= "
				INNER JOIN b_iblock_property FP".$i." ON FP".$i.".IBLOCK_ID=BS.IBLOCK_ID AND
				".(IntVal($propID)>0?" FP".$i.".ID=".IntVal($propID)." ":" FP".$i.".CODE='".$DB->ForSQL($propID, 200)."' ")."
				INNER JOIN ".$strTable." FPV".$i." ON FP".$i.".ID=FPV".$i.".IBLOCK_PROPERTY_ID AND FPV".$i.".IBLOCK_ELEMENT_ID=BE.ID
			";
		}
		if($bJoinFlatProp)
			$strSqlSearchProp .= "
				INNER JOIN b_iblock_element_prop_s".$bJoinFlatProp." FPS ON FPS.IBLOCK_ELEMENT_ID = BE.ID
			";

		$strHint = $DB->type=="MYSQL"?"STRAIGHT_JOIN":"";
		$strSql = "
			SELECT ".$strHint." COUNT(DISTINCT BE.ID) as CNT
			FROM b_iblock_section BS
				INNER JOIN b_iblock_section BSTEMP ON (BSTEMP.IBLOCK_ID=BS.IBLOCK_ID
					AND BSTEMP.LEFT_MARGIN >= BS.LEFT_MARGIN
					AND BSTEMP.RIGHT_MARGIN <= BS.RIGHT_MARGIN)
				INNER JOIN b_iblock_section_element BSE ON BSE.IBLOCK_SECTION_ID=BSTEMP.ID
				INNER JOIN b_iblock_element BE ON BE.ID=BSE.IBLOCK_ELEMENT_ID AND BE.IBLOCK_ID=BS.IBLOCK_ID
			".$strSqlSearchProp."
			WHERE BS.ID=".IntVal($ID)."
				AND ((BE.WF_STATUS_ID=1 AND BE.WF_PARENT_ELEMENT_ID IS NULL )
				".($arFilter["CNT_ALL"]=="Y"?" OR BE.WF_NEW='Y' ":"").")
				".($arFilter["CNT_ACTIVE"]=="Y"?
					" AND BE.ACTIVE='Y'
					AND (BE.ACTIVE_TO >= ".$DB->CurrentTimeFunction()." OR BE.ACTIVE_TO IS NULL)
					AND (BE.ACTIVE_FROM <= ".$DB->CurrentTimeFunction()." OR BE.ACTIVE_FROM IS NULL)"
				:"")."
				".$strSqlSearch;
		//echo "<pre>",htmlspecialcharsbx($strSql),"</pre>";
		$res = $DB->Query($strSql);
		$res = $res->Fetch();
		return $res["CNT"];
	}

	public static function _check_rights_sql($min_permission)
	{
		global $DB, $USER;
		$min_permission = (strlen($min_permission)==1) ? $min_permission : "R";

		if(is_object($USER))
		{
			$iUserID = intval($USER->GetID());
			$strGroups = $USER->GetGroups();
			$bAuthorized = $USER->IsAuthorized();
		}
		else
		{
			$iUserID = 0;
			$strGroups = "2";
			$bAuthorized = false;
		}

		$stdPermissions = "
			SELECT IBLOCK_ID
			FROM b_iblock_group IBG
			WHERE IBG.GROUP_ID IN (".$strGroups.")
			AND IBG.PERMISSION >= '".$DB->ForSQL($min_permission)."'
		";
		if(!defined("ADMIN_SECTION"))
			$stdPermissions .= "
				AND (IBG.PERMISSION='X' OR B.ACTIVE='Y')
			";

		if($min_permission >= "X")
			$operation = 'section_rights_edit';
		elseif($min_permission >= "W")
			$operation = 'section_edit';
		elseif($min_permission >= "R")
			$operation = 'section_read';
		else
			$operation = '';

		if($operation)
		{
			$acc = new CAccess;
			$acc->UpdateCodes();
		}

		if($operation == "section_read")
		{
			$extPermissions = "
				SELECT SR.SECTION_ID
				FROM b_iblock_section_right SR
				INNER JOIN b_iblock_right IBR ON IBR.ID = SR.RIGHT_ID
				".($iUserID > 0? "LEFT": "INNER")." JOIN b_user_access UA ON UA.ACCESS_CODE = IBR.GROUP_CODE AND UA.USER_ID = ".$iUserID."
				WHERE SR.SECTION_ID = BS.ID
				AND IBR.OP_SREAD = 'Y'
				".($bAuthorized || $iUserID > 0? "
					AND (UA.USER_ID IS NOT NULL
					".($bAuthorized? "OR IBR.GROUP_CODE = 'AU'": "")."
					".($iUserID > 0? "OR (IBR.GROUP_CODE = 'CR' AND BS.CREATED_BY = ".$iUserID.")": "")."
				)": "")."
			";

			$strResult = "(
				B.ID IN ($stdPermissions)
				OR (B.RIGHTS_MODE = 'E' AND EXISTS ($extPermissions))
			)";
		}
		elseif($operation)
		{
			$extPermissions = "
				SELECT SR.SECTION_ID
				FROM b_iblock_section_right SR
				INNER JOIN b_iblock_right IBR ON IBR.ID = SR.RIGHT_ID
				INNER JOIN b_task_operation T ON T.TASK_ID = IBR.TASK_ID
				INNER JOIN b_operation O ON O.ID = T.OPERATION_ID
				".($iUserID > 0? "LEFT": "INNER")." JOIN b_user_access UA ON UA.ACCESS_CODE = IBR.GROUP_CODE AND UA.USER_ID = ".$iUserID."
				WHERE SR.SECTION_ID = BS.ID
				AND O.NAME = '".$operation."'
				".($bAuthorized || $iUserID > 0? "
					AND (UA.USER_ID IS NOT NULL
					".($bAuthorized? "OR IBR.GROUP_CODE = 'AU'": "")."
					".($iUserID > 0? "OR (IBR.GROUP_CODE = 'CR' AND BS.CREATED_BY = ".$iUserID.")": "")."
				)": "")."
			";

			$strResult = "(
				B.ID IN ($stdPermissions)
				OR (B.RIGHTS_MODE = 'E' AND EXISTS ($extPermissions))
			)";
		}
		else
		{
			$strResult = "(
				B.ID IN ($stdPermissions)
			)";
		}

		return $strResult;
	}

	
	/**
	* <p>Возвращает количество разделов, удовлетворяющих фильтру <i>arFilter</i>. Нестатический метод.</p>
	*
	*
	* @param array $arrayarFilter = Array() Массив вида array("фильтруемое поле"=&gt;"значение" [, ...])<br>
	* "фильтруемое поле" может принимать значения:<br>     <i>ACTIVE</i> -
	* фильтр по активности (Y|N);<br>     <i>GLOBAL_ACTIVE</i> - фильтр по активности,
	* учитывая активность вышележащих разделов (Y|N);<br>     <i>NAME</i> - по
	* названию (можно искать по шаблону [%_]);<br>     <i>CODE</i> - по
	* символьному коду (по шаблону [%_]);<br>     <i>EXTERNAL_ID</i> - по внешнему
	* коду (по шаблону [%_]);<br>     <i>SECTION_ID</i> - по коду раздела-родителя;<br>
	*     <i>DEPTH_LEVEL</i> - по уровню вложенности;<br>     <i>LEFT_BORDER, RIGHT_BORDER</i> -
	* по левой и правой границе (поля <i>LEFT_MARGIN</i> и <i>RIGHT_MARGIN</i>, см.
	* примечание);<br>     <i>ID</i> - по коду раздела;<br>     <i>IBLOCK_ID</i> - по
	* коду родительского информационного блока;<br>     <i>IBLOCK_ACTIVE</i> - по
	* активности родительского информационного блока;<br>
	*     <i>IBLOCK_NAME</i> - по названию информационного блока (по шаблону
	* [%_]);<br>     <i>IBLOCK_TYPE</i> - по типу информационного блока; <br>
	*     <i>IBLOCK_CODE - </i>по символьному коду информационного блока (по
	* шаблону [%_]);<br>     <i>IBLOCK_EXTERNAL_ID</i> - по внешнему коду
	* информационного блока (по шаблону [%_]);<br><br> Перед названием
	* фильтруемого поля можно указать тип фильтрации:<br> "!" - не равно<br>
	* "&lt;" - меньше<br> "&lt;=" - меньше либо равно<br> "&gt;" - больше<br> "&gt;=" -
	* больше либо равно<br><br> "значения фильтра" одиночное значение или
	* массив.<br><br> Необязательное. По умолчанию записи не фильтруются.
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* 	$arFilter = Array(
	* 		"IBLOCK_ID"=&gt;$IBLOCK_ID,
	* 		"SECTION_ID"=&gt;$f_ID
	* 		);
	* 
	* 	echo CIBlockSection::GetCount($arFilter);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/index.php">CIBlockSection</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/getlist.php">GetList()</a> </li></ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/getcount.php
	* @author Bitrix
	*/
	public static function GetCount($arFilter=Array())
	{
		global $DB, $USER;

		$arSqlSearch = CIBlockSection::GetFilter($arFilter);

		$bCheckPermissions = !array_key_exists("CHECK_PERMISSIONS", $arFilter) || $arFilter["CHECK_PERMISSIONS"]!=="N";
		$bIsAdmin = is_object($USER) && $USER->IsAdmin();
		if($bCheckPermissions && !$bIsAdmin)
		{
			$min_permission = (strlen($arFilter["MIN_PERMISSION"])==1) ? $arFilter["MIN_PERMISSION"] : "R";
			$arSqlSearch[] = CIBlockSection::_check_rights_sql($min_permission);
		}

		$strSqlSearch = "";
		foreach($arSqlSearch as $i=>$strSearch)
			if(strlen($strSearch)>0)
				$strSqlSearch .= "\n\t\t\tAND  (".$strSearch.") ";

		$strSql = "
			SELECT COUNT(DISTINCT BS.ID) as C
			FROM b_iblock_section BS
				INNER JOIN b_iblock B ON BS.IBLOCK_ID = B.ID
			WHERE 1=1
			".$strSqlSearch."
		";

		$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		$res_cnt = $res->Fetch();
		return IntVal($res_cnt["C"]);
	}

	public static function UserTypeRightsCheck($entity_id)
	{
		if(preg_match("/^IBLOCK_(\d+)_SECTION$/", $entity_id, $match))
		{
			return CIBlock::GetPermission($match[1]);
		}
		else
			return "D";
	}

	public static function RecalcGlobalActiveFlag($arSection)
	{
		global $DB;

		//Make all children globally active
		$DB->Query("
			UPDATE b_iblock_section SET
				TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
				,GLOBAL_ACTIVE = 'Y'
			WHERE
				IBLOCK_ID = ".$arSection["IBLOCK_ID"]."
				AND LEFT_MARGIN >= ".intval($arSection["LEFT_MARGIN"])."
				AND RIGHT_MARGIN <= ".intval($arSection["RIGHT_MARGIN"])."
		");
		//Select those who is not active
		$strSql = "
			SELECT ID, LEFT_MARGIN, RIGHT_MARGIN
			FROM b_iblock_section
			WHERE IBLOCK_ID = ".$arSection["IBLOCK_ID"]."
			AND LEFT_MARGIN >= ".intval($arSection["LEFT_MARGIN"])."
			AND RIGHT_MARGIN <= ".intval($arSection["RIGHT_MARGIN"])."
			AND ACTIVE = 'N'
			ORDER BY LEFT_MARGIN
		";
		$arUpdate = array();
		$prev_right = 0;
		$rsChildren = $DB->Query($strSql);
		while($arChild = $rsChildren->Fetch())
		{
			if($arChild["RIGHT_MARGIN"] > $prev_right)
			{
				$prev_right = $arChild["RIGHT_MARGIN"];
				$arUpdate[] = "(LEFT_MARGIN >= ".$arChild["LEFT_MARGIN"]." AND RIGHT_MARGIN <= ".$arChild["RIGHT_MARGIN"].")\n";
			}
		}
		if(count($arUpdate) > 0)
		{
			$DB->Query("
				UPDATE b_iblock_section SET
					TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
					,GLOBAL_ACTIVE = 'N'
				WHERE
					IBLOCK_ID = ".$arSection["IBLOCK_ID"]."
					AND (".implode(" OR ", $arUpdate).")
			");
		}
	}

	public static function getSectionCodePath($sectionId)
	{
		if (!array_key_exists($sectionId, self::$arSectionPathCache))
		{
			self::$arSectionPathCache[$sectionId] = "";
			$res = CIBlockSection::GetNavChain(0, $sectionId, array("ID", "CODE"));
			while ($a = $res->Fetch())
			{
				self::$arSectionCodeCache[$a["ID"]] = urlencode($a["CODE"]);
				self::$arSectionPathCache[$sectionId] .= urlencode($a["CODE"])."/";
			}
			self::$arSectionPathCache[$sectionId] = rtrim(self::$arSectionPathCache[$sectionId], "/");

		}
		return self::$arSectionPathCache[$sectionId];
	}

	public static function getSectionCode($sectionId)
	{
		global $DB;

		$sectionId = intval($sectionId);
		if (!array_key_exists($sectionId, self::$arSectionCodeCache))
		{
			self::$arSectionCodeCache[$sectionId] = "";
			$res = $DB->Query("SELECT IBLOCK_ID, CODE FROM b_iblock_section WHERE ID = ".$sectionId);
			while ($a = $res->Fetch())
			{
				self::$arSectionCodeCache[$sectionId] = urlencode($a["CODE"]);
			}
		}
		return self::$arSectionCodeCache[$sectionId];
	}
}

?>