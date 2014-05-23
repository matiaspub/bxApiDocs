<?
class CIBlockResult extends CDBResult
{
	var $arIBlockMultProps=false;
	var $arIBlockConvProps=false;
	var $arIBlockAllProps =false;
	var $arIBlockNumProps =false;
	var $arIBlockLongProps = false;

	var $nInitialSize;
	var $table_id;
	var $strDetailUrl = false;
	var $strSectionUrl = false;
	var $strListUrl = false;
	var $arSectionContext = false;
	var $bIBlockSection = false;
	var $nameTemplate = "";

	var $_LAST_IBLOCK_ID = "";
	var $_FILTER_IBLOCK_ID = array();

	function CIBlockResult($res)
	{
		parent::CDBResult($res);
	}


	/**
	 * <p>Устанавливает шаблоны путей для элементов, разделов и списка элементов вместо тех которые указаны в настройках информационного блока. Шаблоны будут использованы функцией  <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/getnext.php">CIBlockResult::GetNext</a>. </p> <p><b>Примечание</b>: Используется в компонентах для корректного формирования путей, если соответствующие параметры указаны.</p>
	 *
	 *
	 *
	 *
	 * @param array $DetailUrl = "" Шаблон для пути к элементу. Если не задан, то путь будет взят из
	 * настроек инфоблока. <br>
	 *
	 *
	 *
	 * @param array $SectionUrl = "" Шаблон для пути к разделу. Если не задан, то путь будет взят из
	 * настроек инфоблока.
	 *
	 *
	 *
	 * @param array $ListUrl = "" Шаблон для пути к списку элементов. Если не задан, то путь будет
	 * взят из настроек инфоблока.
	 *
	 *
	 *
	 * @return void <p>Ничего.</p>
	 *
	 *
	 * <h4>Example</h4>
	 * <pre>
	 * &lt;?<br>$rsElements = CIBlockElement::GetList(array(), array("ID" =&gt; $ID), false, false, array("ID", "NAME", "DETAIL_PAGE_URL"));<br>$rsElements-&gt;SetUrlTemplates("/catalog/#SECTION_CODE#/#ELEMENT_CODE#.php");<br>$arElement = $rsElements-&gt;GetNext();<br>?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4>
	 * <ul> <li><a
	 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/getnext.php">CIBlockResult::GetNext</a></li> <li><a
	 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/setsectioncontext.php">CIBlockResult::SetSectionContext</a></li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/seturltemplates.php
	 * @author Bitrix
	 */
	function SetUrlTemplates($DetailUrl = "", $SectionUrl = "", $ListUrl = "")
	{
		$this->strDetailUrl = $DetailUrl;
		$this->strSectionUrl = $SectionUrl;
		$this->strListUrl = $ListUrl;
	}


	/**
	 * <p>Функция устанавливает поля раздела в качестве родителя элемента для подстановки в шаблоны путей функцией <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/getnext.php">CIBlockResult::GetNext</a>. Если родительский раздел не определен с помощью вызова этого метода, то для подстановки шаблона будут использованы поля из раздела с минимальным ID к которому привязан элемент. <br></p> <p><b>Примечание</b>: Используется в компонентах для сохранения текущего просматриваемого пользователем раздела в случае множественной привязки элементов.</p>
	 *
	 *
	 *
	 *
	 * @param array $arSection  Массив полей раздела поля которого будут использованы для
	 * подстановки значений в шаблон пути. <br>
	 *
	 *
	 *
	 * @return void <p>Функция ничего не возвращает.</p>
	 *
	 *
	 * <h4>Example</h4>
	 * <pre>
	 * &lt;?<br>$rsElements = CIBlockElement::GetList(array(), array("ID" =&gt; $ID), false, false, array("ID", "NAME", "DETAIL_PAGE_URL"));<br>$rsElements-&gt;SetUrlTemplates("/catalog/#SECTION_CODE#/#ELEMENT_CODE#.php");<br>$rsElements-&gt;SetSectionContext($arSection);<br>$arElement = $rsElements-&gt;GetNext();<br>?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4>
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/getnext.php">CIBlockResult::GetNext</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/getnext.php">CIBlockResult::</a><a
	 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/seturltemplates.php">SetUrlTemplates</a> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/setsectioncontext.php
	 * @author Bitrix
	 */
	function SetSectionContext($arSection)
	{
		if(is_array($arSection) && array_key_exists("ID", $arSection))
		{
			$this->arSectionContext = array(
				"ID" => intval($arSection["ID"]) > 0? intval($arSection["ID"]): "",
				"CODE" => urlencode(isset($arSection["~CODE"])? $arSection["~CODE"]: $arSection["CODE"]),
				"IBLOCK_ID" => intval($arSection["IBLOCK_ID"]),
			);
		}
		else
		{
			$this->arSectionContext = false;
		}
	}

	function SetIBlockTag($iblock_id)
	{
		if(is_array($iblock_id))
		{
			foreach($iblock_id as $id)
			{
				if(!is_array($id))
				{
					$id = intval($id);
					if($id > 0)
						$this->_FILTER_IBLOCK_ID[$id] = true;
				}
			}
		}
		else
		{
			$id = intval($iblock_id);
			if($id > 0)
				$this->_FILTER_IBLOCK_ID[$id] = true;
		}
	}

	function SetNameTemplate($nameTemplate)
	{
		$this->nameTemplate = $nameTemplate;
	}

	function Fetch()
	{
		/** @global CCacheManager $CACHE_MANAGER */
		global $CACHE_MANAGER;
		/** @global CDatabase $DB */
		global $DB;
		$res = parent::Fetch();

		if(!is_object($this))
			return $res;

		$arUpdate = array();
		if($res)
		{
			if(is_array($this->arIBlockLongProps))
			{
				foreach($res as $k=>$v)
				{
					if(preg_match("#^ALIAS_(\\d+)_(.*)$#", $k, $match))
					{
						$res[$this->arIBlockLongProps[$match[1]].$match[2]] = $v;
						unset($res[$k]);
					}
				}
			}

			if(
				isset($res["IBLOCK_ID"])
				&& defined("BX_COMP_MANAGED_CACHE")
				&& $res["IBLOCK_ID"] != $this->_LAST_IBLOCK_ID
			)
			{
				CIBlock::registerWithTagCache($res["IBLOCK_ID"]);
				$this->_LAST_IBLOCK_ID = $res["IBLOCK_ID"];
			}

			if(isset($res["ID"]) && $res["ID"] != "" && is_array($this->arIBlockMultProps))
			{
				foreach($this->arIBlockMultProps as $field_name => $db_prop)
				{
					if(array_key_exists($field_name, $res))
					{
						if(is_object($res[$field_name]))
							$res[$field_name]=$res[$field_name]->load();

						if(preg_match("/(_VALUE)$/", $field_name))
						{
							$descr_name = preg_replace("/(_VALUE)$/", "_DESCRIPTION", $field_name);
							$value_id_name = preg_replace("/(_VALUE)$/", "_PROPERTY_VALUE_ID", $field_name);;
						}
						else
						{
							$descr_name = preg_replace("/^(PROPERTY_)/", "DESCRIPTION_", $field_name);
							$value_id_name = preg_replace("/^(PROPERTY_)/", "PROPERTY_VALUE_ID_", $field_name);
						}

						$update = false;
						if (strlen($res[$field_name]) <= 0)
						{
							$update = true;
						}
						else
						{
							$tmp = unserialize($res[$field_name]);
							if (!isset($tmp['ID']))
								$update = true;
						}
						if ($update)
						{
							$strSql = "
								SELECT ID, VALUE, DESCRIPTION
								FROM b_iblock_element_prop_m".$db_prop["IBLOCK_ID"]."
								WHERE
									IBLOCK_ELEMENT_ID = ".intval($res["ID"])."
									AND IBLOCK_PROPERTY_ID = ".intval($db_prop["ORIG_ID"])."
								ORDER BY ID
							";
							$rs = $DB->Query($strSql);
							$res[$field_name] = array();
							$res[$descr_name] = array();
							$res[$value_id_name] = array();
							while($ar=$rs->Fetch())
							{
								$res[$field_name][]=$ar["VALUE"];
								$res[$descr_name][]=$ar["DESCRIPTION"];
								$res[$value_id_name][] = $ar['ID'];
							}
							$arUpdate["b_iblock_element_prop_s".$db_prop["IBLOCK_ID"]]["PROPERTY_".$db_prop["ORIG_ID"]] = serialize(array("VALUE"=>$res[$field_name],"DESCRIPTION"=>$res[$descr_name],"ID"=>$res[$value_id_name]));
						}
						else
						{
							$res[$field_name] = $tmp["VALUE"];
							$res[$descr_name] = $tmp["DESCRIPTION"];
							$res[$value_id_name] = $tmp["ID"];
						}

						if(is_array($res[$field_name]) && $db_prop["PROPERTY_TYPE"]=="L")
						{
							$arTemp = array();
							foreach($res[$field_name] as $key=>$val)
							{
								$arEnum = CIBlockPropertyEnum::GetByID($val);
								if($arEnum!==false)
									$arTemp[$val] = $arEnum["VALUE"];
							}
							$res[$field_name] = $arTemp;
						}
					}
				}
				foreach($arUpdate as $strTable=>$arFields)
				{
					$strUpdate = $DB->PrepareUpdate($strTable, $arFields);
					if($strUpdate!="")
					{
						$strSql = "UPDATE ".$strTable." SET ".$strUpdate." WHERE IBLOCK_ELEMENT_ID = ".intval($res["ID"]);
						$DB->QueryBind($strSql, $arFields);
					}
				}
			}
			if(is_array($this->arIBlockConvProps))
			{
				foreach($this->arIBlockConvProps as $strFieldName=>$arCallback)
				{
					if(is_array($res[$strFieldName]))
					{

						foreach($res[$strFieldName] as $key=>$value)
						{
							$arValue = call_user_func_array($arCallback["ConvertFromDB"], array($arCallback["PROPERTY"], array("VALUE"=>$value,"DESCRIPTION"=>"")));
							$res[$strFieldName][$key] = $arValue["VALUE"];
						}
					}
					else
					{
						$arValue = call_user_func_array($arCallback["ConvertFromDB"], array($arCallback["PROPERTY"], array("VALUE"=>$res[$strFieldName],"DESCRIPTION"=>"")));
						$res[$strFieldName] = $arValue["VALUE"];
					}
				}
			}
			if(is_array($this->arIBlockNumProps))
			{
				foreach($this->arIBlockNumProps as $field_name => $db_prop)
				{
					if(strlen($res[$field_name]) > 0)
						$res[$field_name] = htmlspecialcharsex(CIBlock::NumberFormat($res[$field_name]));
				}
			}
			if (isset($res["UC_ID"]))
			{
				$res["CREATED_BY_FORMATTED"] = CUser::FormatName($this->nameTemplate, array(
					"NAME" => $res["UC_NAME"],
					"LAST_NAME" => $res["UC_LAST_NAME"],
					"SECOND_NAME" => $res["UC_SECOND_NAME"],
					"EMAIL" => $res["UC_EMAIL"],
					"ID" => $res["UC_ID"],
					"LOGIN" => $res["UC_LOGIN"],
				), true, false);
				unset($res["UC_NAME"]);
				unset($res["UC_LAST_NAME"]);
				unset($res["UC_SECOND_NAME"]);
				unset($res["UC_EMAIL"]);
				unset($res["UC_ID"]);
				unset($res["UC_LOGIN"]);
			}
		}
		elseif(
			defined("BX_COMP_MANAGED_CACHE")
			&& $this->_LAST_IBLOCK_ID == ""
			&& count($this->_FILTER_IBLOCK_ID)
		)
		{
			foreach($this->_FILTER_IBLOCK_ID as $iblock_id => $t)
				CIBlock::registerWithTagCache($iblock_id);
		}

		return $res;
	}


	/**
	 * <p>Возвращает массив значений полей приведенный в HTML безопасный вид. Также в полях <i>DETAIL_PAGE_URL</i> и <i>LIST_PAGE_URL</i> заменяются шаблоны вида #IBLOCK_ID# и т.п. на их реальные значения, в результате чего в этих полях будут ссылки на страницу детального просмотра и страницу списка элементов. <br></p> <p>Если выборка была из инфоблока свойства которого хранятся отдельно (<a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2723" >Режим хранения свойств в отдельных таблицах</a>), то для правильной обработки значений множественных свойств требуется наличие полей ID и IBLOCK_ID. <br></p>
	 *
	 *
	 *
	 *
	 * @param bool $text_html = true
	 *
	 *
	 *
	 * @param bool $original = true
	 *
	 *
	 *
	 * @return mixed <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#felement">полями элемента
	 * информационного блока</a><br>
	 *
	 *
	 * <h4>Example</h4>
	 * <pre>
	 * &lt;?<br>$res = CIBlockElement::GetByID($_GET["PID"]);<br>if($ar_res = $res-&gt;GetNext())<br>  echo '&lt;a href="'.$ar_res['DETAIL_PAGE_URL'].'"&gt;'.$ar_res['NAME'].'&lt;/a&gt;';<br>else<br>  echo 'Элемент не найден.';<br>?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4>
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/index.php">CIBlockResult</a>::<a
	 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/getnextelement.php">GetNextElement()</a> </li> <li>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#felement">Поля элемента
	 * информационного блока </a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/index.php">CIBlockElement</a>::<a
	 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php">GetList()</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/getnext.php
	 * @author Bitrix
	 */
	function GetNext($bTextHtmlAuto=true, $use_tilda=true)
	{
		static $arSectionPathCache = array();

		$res = parent::GetNext($bTextHtmlAuto, $use_tilda);
		if($res)
		{
			//Handle List URL for Element, Section or IBlock
			if($this->strListUrl)
				$TEMPLATE = $this->strListUrl;
			elseif(array_key_exists("~LIST_PAGE_URL", $res))
				$TEMPLATE = $res["~LIST_PAGE_URL"];
			elseif(!$use_tilda && array_key_exists("LIST_PAGE_URL", $res))
				$TEMPLATE = $res["LIST_PAGE_URL"];
			else
				$TEMPLATE = "";

			if($TEMPLATE)
			{
				$res_tmp = $res;
				if((intval($res["IBLOCK_ID"]) <= 0) && (intval($res["ID"]) > 0))
				{
					$res_tmp["IBLOCK_ID"] = $res["ID"];
					$res_tmp["IBLOCK_CODE"] = $res["CODE"];
					$res_tmp["IBLOCK_EXTERNAL_ID"] = $res["EXTERNAL_ID"];
					if($use_tilda)
					{
						$res_tmp["~IBLOCK_ID"] = $res["~ID"];
						$res_tmp["~IBLOCK_CODE"] = $res["~CODE"];
						$res_tmp["~IBLOCK_EXTERNAL_ID"] = $res["~EXTERNAL_ID"];
					}
				}

				if($use_tilda)
				{
					$res["~LIST_PAGE_URL"] = CIBlock::ReplaceDetailUrl($TEMPLATE, $res_tmp, true, false);
					$res["LIST_PAGE_URL"] = htmlspecialcharsbx($res["~LIST_PAGE_URL"]);
				}
				else
				{
					$res["LIST_PAGE_URL"] = CIBlock::ReplaceDetailUrl($TEMPLATE, $res_tmp, true, false);
				}
			}

			//If this is Element or Section then process it's detail and section URLs
			if(strlen($res["IBLOCK_ID"]))
			{

				if(array_key_exists("GLOBAL_ACTIVE", $res))
					$type = "S";
				else
					$type = "E";

				if($this->strDetailUrl)
					$TEMPLATE = $this->strDetailUrl;
				elseif(array_key_exists("~DETAIL_PAGE_URL", $res))
					$TEMPLATE = $res["~DETAIL_PAGE_URL"];
				elseif(!$use_tilda && array_key_exists("DETAIL_PAGE_URL", $res))
					$TEMPLATE = $res["DETAIL_PAGE_URL"];
				else
					$TEMPLATE = "";

				if($TEMPLATE)
				{
					if($this->arSectionContext)
					{
						$TEMPLATE = str_replace("#SECTION_ID#", $this->arSectionContext["ID"], $TEMPLATE);
						$TEMPLATE = str_replace("#SECTION_CODE#", $this->arSectionContext["CODE"], $TEMPLATE);
						if(
							$this->arSectionContext["ID"] > 0
							&& $this->arSectionContext["IBLOCK_ID"] > 0
							&& strpos($TEMPLATE, "#SECTION_CODE_PATH#") !== false
						)
						{
							if(!array_key_exists($this->arSectionContext["ID"], $arSectionPathCache))
							{
								$rs = CIBlockSection::GetNavChain($this->arSectionContext["IBLOCK_ID"], $this->arSectionContext["ID"], array("ID", "IBLOCK_SECTION_ID", "CODE"));
								while ($a = $rs->Fetch())
									$arSectionPathCache[$this->arSectionContext["ID"]] .= urlencode($a["CODE"])."/";

							}
							if(isset($arSectionPathCache[$this->arSectionContext["ID"]]))
								$SECTION_CODE_PATH = rtrim($arSectionPathCache[$this->arSectionContext["ID"]], "/");
							else
								$SECTION_CODE_PATH = "";
							$TEMPLATE = str_replace("#SECTION_CODE_PATH#", $SECTION_CODE_PATH, $TEMPLATE);
						}
					}

					if($use_tilda)
					{
						$res["~DETAIL_PAGE_URL"] = CIBlock::ReplaceDetailUrl($TEMPLATE, $res, true, $type);
						$res["DETAIL_PAGE_URL"] = htmlspecialcharsbx($res["~DETAIL_PAGE_URL"]);
					}
					else
					{
						$res["DETAIL_PAGE_URL"] = CIBlock::ReplaceDetailUrl($TEMPLATE, $res, true, $type);
					}
				}

				if($this->strSectionUrl)
					$TEMPLATE = $this->strSectionUrl;
				elseif(array_key_exists("~SECTION_PAGE_URL", $res))
					$TEMPLATE = $res["~SECTION_PAGE_URL"];
				elseif(!$use_tilda && array_key_exists("SECTION_PAGE_URL", $res))
					$TEMPLATE = $res["SECTION_PAGE_URL"];
				else
					$TEMPLATE = "";

				if($TEMPLATE)
				{
					if($use_tilda)
					{
						$res["~SECTION_PAGE_URL"] = CIBlock::ReplaceSectionUrl($TEMPLATE, $res, true, $type);
						$res["SECTION_PAGE_URL"] = htmlspecialcharsbx($res["~SECTION_PAGE_URL"]);
					}
					else
					{
						$res["SECTION_PAGE_URL"] = CIBlock::ReplaceSectionUrl($TEMPLATE, $res, true, $type);
					}
				}
			}
		}
		return $res;
	}


	/**
	 * <p>Метод возвращает из выборки объект <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/_cibelement/index.php">_CIBElement</a>.</p>
	 *
	 *
	 *
	 *
	 * @return _CIBElement <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/_cibelement/index.php">_CIBElement</a><br>
	 *
	 *
	 * <h4>Example</h4>
	 * <pre>
	 * &lt;?
	 * $res = CIBlockElement::GetByID($_GET["PID"]);
	 * if($obRes = $res-&gt;GetNextElement())
	 * {
	 *   $ar_res = $obRes-&gt;GetFields();
	 *   echo $ar_res['NAME'];
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4>
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/_cibelement/index.php">_CIBElement</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/index.php">CIBlockResult</a>::<a
	 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/getnext.php">GetNext()</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/index.php">CIBlockElement</a>::<a
	 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php">GetList()</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/getnextelement.php
	 * @author Bitrix
	 */
	function GetNextElement($bTextHtmlAuto=true, $use_tilda=true)
	{
		if(!($r = $this->GetNext($bTextHtmlAuto, $use_tilda)))
			return $r;

		$res = new _CIBElement;
		$res->fields = $r;
		if(count($this->arIBlockAllProps)>0)
			$res->props  = $this->arIBlockAllProps;
		return $res;
	}

	function SetTableID($table_id)
	{
		$this->table_id = $table_id;
	}

	function NavStart($nPageSize=20, $bShowAll=true, $iNumPage=false)
	{
		if($this->table_id)
		{
			if ($_REQUEST["mode"] == "excel")
				return;

			$nSize = CAdminResult::GetNavSize($this->table_id, $nPageSize);
			if(is_array($nPageSize))
			{
				$this->nInitialSize = $nPageSize["nPageSize"];
				$nPageSize["nPageSize"] = $nSize;
			}
			else
			{
				$this->nInitialSize = $nPageSize;
				$nPageSize = $nSize;
			}
		}
		parent::NavStart($nPageSize, $bShowAll, $iNumPage);
	}

	function GetNavPrint($title, $show_allways=true, $StyleText="", $template_path=false, $arDeleteParam=false)
	{
		if($this->table_id && ($template_path === false))
			$template_path = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/interface/navigation.php";
		return parent::GetNavPrint($title, $show_allways, $StyleText, $template_path, $arDeleteParam);
	}
}
?>