<?
IncludeModuleLangFile(__FILE__);

/**
 * Класс поддержки правил сортировки. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/index.php
 * @author Bitrix
 */
class CSearchCustomRank
{
	var $LAST_ERROR="";

	
	/**
	* <p>Получение списка правил сортировки по фильтру. Метод динамичный.</p>
	*
	*
	* @param array $arrayaSort = array() Массив, содержащий признак сортировки в виде наборов "название
	* поля"=&gt;"направление". <br><br> Название поля может принимать
	* значение названия любого из полей <a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/fields.php">объекта правила
	* сортировки</a>. Не обязательный параметр. По умолчанию равен: <pre
	* class="syntax"> array( "SITE_ID"=&gt;"ASC", "MODULE_ID"=&gt;"ASC", "PARAM1"=&gt;"DESC", "PARAM2"=&gt;"DESC",
	* "ITEM_ID"=&gt;"DESC", ) </pre>
	*
	* @param array $arrayaFilter = array() Массив, содержащий фильтр в виде наборов "название
	* поля"=&gt;"значение фильтра". <br><br> Название поля может принимать
	* значение названия любого из полей <a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/fields.php">объекта правила
	* сортировки</a>. Фильтрация осуществляется по точному совпадению
	* значения фильтра и правила. Не обязательный параметр.
	*
	* @return CDBResult <p>Возвращается результат запроса типа <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. При выборке из
	* результата методами класса CDBResult становятся доступны поля <a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/fields.php">объекта правила
	* сортировки</a>.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/fields.php">Поля объекта
	* правила сортировки</a></li> </ul> <br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/getlist.php
	* @author Bitrix
	*/
	public static function GetList($aSort=array(), $aFilter=array())
	{
		$DB = CDatabase::GetModuleConnection('search');

		$arFilter = array();
		foreach($aFilter as $key=>$val)
		{
			$val = $DB->ForSql($val);
			$key = strtoupper($key);
			if(strlen($val)<=0)
				continue;
			switch($key)
			{
				case "SITE_ID":
				case "MODULE_ID":
				case "PARAM1":
				case "PARAM2":
				case "ITEM_ID":
				case "ID":
				case "APPLIED":
					$arFilter[] = "CR.".$key."='".$val."'";
					break;
			}
		}

		$arOrder = array();
		foreach($aSort as $key=>$val)
		{
			$ord = (strtoupper($val) <> "ASC"?"DESC":"ASC");
			$key = strtoupper($key);
			switch($key)
			{
				case "SITE_ID":
				case "MODULE_ID":
				case "PARAM1":
				case "PARAM2":
				case "ITEM_ID":
				case "ID":
				case "APPLIED":
				case "RANK":
					$arOrder[] = "CR.".$key." ".$ord;
					break;
			}
		}

		if(count($arOrder) == 0)
			$arOrder = array(
				"CR.SITE_ID ASC"
				,"CR.MODULE_ID ASC"
				,"CR.PARAM1 DESC"
				,"CR.PARAM2 DESC"
				,"CR.ITEM_ID DESC"
			);
		$sOrder = "\nORDER BY ".implode(", ",$arOrder);

		if(count($arFilter) == 0)
			$sFilter = "";
		else
			$sFilter = "\nWHERE ".implode("\nAND ", $arFilter);

		$strSql = "
			SELECT
				CR.ID
				,CR.SITE_ID
				,CR.MODULE_ID
				,CR.PARAM1
				,CR.PARAM2
				,CR.ITEM_ID
				,CR.RANK
			FROM
				b_search_custom_rank CR
			".$sFilter.$sOrder;

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	
	/**
	* <p>Получение правила сортировки по идентификатору. Метод динамичный.</p>
	*
	*
	* @param int $ID  Идентификатор правила сортировки.
	*
	* @return CDBResult <p>Возвращается результат запроса типа <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. При выборке из
	* результата методами класса CDBResult становятся доступны поля <a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/fields.php">объекта правила
	* сортировки</a>.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/fields.php">Поля объекта
	* правила сортировки</a></li> </ul> <br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$ID = intval($ID);

		$strSql = "
			SELECT CR.*
			FROM b_search_custom_rank CR
			WHERE CR.ID = ".$ID."
		";

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	
	/**
	* <p>Удаление правила сортировки по идентификатору. Метод динамичный.</p> <p>После удаления всех требуемых правил необходимо пересчитать поисковый индекс методами <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/startupdate.php">CSearchCustomRank::StartUpdates</a> и <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/nextupdate.php">CSearchCustomRank::NextUpdate</a>.</p>
	*
	*
	* @param int $ID  Идентификатор правила сортировки.
	*
	* @return CDBResult <p>Если правило успешно удалено, то возвращается результат
	* запроса типа <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>, в
	* противном случае метод вернет false.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/startupdate.php">CSearchCustomRank::StartUpdates</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/nextupdate.php">CSearchCustomRank::NextUpdate</a></li>
	* </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$ID = intval($ID);

		return $DB->Query("DELETE FROM b_search_custom_rank WHERE ID=".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public function CheckFields($arFields)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$this->LAST_ERROR = "";

		if(is_set($arFields, "SITE_ID") && strlen($arFields["SITE_ID"]) == 0)
			$this->LAST_ERROR .= GetMessage("customrank_error_site")."<br>";
		if(is_set($arFields, "MODULE_ID") && strlen($arFields["MODULE_ID"]) == 0)
			$this->LAST_ERROR .= GetMessage("customrank_error_module")."<br>";

		if(strlen($this->LAST_ERROR)>0)
			return false;
		else
			return true;
	}

	
	/**
	* <p>Метод добавляет новое правило. Метод динамичный.</p> <p>После удаления всех требуемых правил необходимо пересчитать поисковый индекс методами <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/startupdate.php">CSearchCustomRank::StartUpdates</a> и <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/nextupdate.php">CSearchCustomRank::NextUpdate</a>.</p>
	*
	*
	* @param array $arFields  Массив со значениями полей <a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/fields.php">объекта правила
	* сортировки</a>.
	*
	* @return int <p>В случае успешного добавления возвращается ID нового правила. В
	* противном случае возвращается false.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/fields.php">Поля объекта
	* правила сортировки</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/startupdate.php">CSearchCustomRank::StartUpdates</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/nextupdate.php">CSearchCustomRank::NextUpdate</a></li>
	* </ul> <br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/add.php
	* @author Bitrix
	*/
	public function Add($arFields)
	{
		$DB = CDatabase::GetModuleConnection('search');

		if(!$this->CheckFields($arFields))
			return false;

		return $DB->Add("b_search_custom_rank", $arFields);
	}

	
	/**
	* <p>Изменение правила сортировки. Метод динамичный.</p> <p>После удаления всех требуемых правил необходимо пересчитать поисковый индекс методами <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/startupdate.php">CSearchCustomRank::StartUpdate</a> и <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/nextupdate.php">CSearchCustomRank::NextUpdate</a>.</p>
	*
	*
	* @param int $ID  Идентификатор правила.
	*
	* @param array $arFields  Массив со значениями полей <a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/fields.php">объекта правила
	* сортировки</a>.
	*
	* @return bool <p>В случае успешного добавления возвращается true. В противном
	* случает возвращается false.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/fields.php">Поля объекта
	* правила сортировки</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/startupdate.php">CSearchCustomRank::StartUpdate</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/nextupdate.php">CSearchCustomRank::NextUpdate</a></li>
	* </ul> <br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/update.php
	* @author Bitrix
	*/
	public function Update($ID, $arFields)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$ID = intval($ID);

		if(!$this->CheckFields($arFields))
			return false;

		unset($arFields["ID"]);

		$strUpdate = $DB->PrepareUpdate("b_search_custom_rank", $arFields);
		if($strUpdate!="")
		{
			$strSql =
				"UPDATE b_search_custom_rank SET ".$strUpdate." ".
				"WHERE ID=".$ID;
			return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return true;
	}

	
	/**
	* <p>Подготовка к применению изменений в правилах. Метод динамичный.</p> <p>Данный метод начинает процедуру применения правил сортировки к поисковому индексу. Применение правил требуется всякий раз когда происходят их изменения (<a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/add.php">CSearchCustomRank::Add</a>, <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/update.php">CSearchCustomRank::Update</a> и <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/delete.php">CSearchCustomRank::Delete</a>). При индексации данных или после переиндексации данная процедура не требуется, т.к. правила сортировки учитываются в процессе построения поискового индекса.</p> <p>Фактически данный метод сбрасывает все весовые коэффициенты. А для собственно применения правил необходимо воспользоваться пошаговой <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/nextupdate.php">CSearchCustomRank::NextUpdate</a>.</p>
	*
	*
	* @return CDBResult <p>В случае успешного выполнения возвращается объект CDBResult. В
	* противном случае возвращается false.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/nextupdate.php">CSearchCustomRank::NextUpdate</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/add.php">CSearchCustomRank::Add</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/update.php">CSearchCustomRank::Update</a>
	* </li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/delete.php">CSearchCustomRank::Delete</a></li>
	* </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/startupdate.php
	* @author Bitrix
	*/
	public static function StartUpdate()
	{
		$DB = CDatabase::GetModuleConnection('search');
		$strSql = "
			UPDATE b_search_custom_rank
			SET APPLIED='N'
		";
		$rs=$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if($rs)
		{
			$strSql = "
				UPDATE b_search_content
				SET CUSTOM_RANK=0
				WHERE CUSTOM_RANK<>0
			";
			$rs=$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return $rs;
	}

	
	/**
	* <p>Шаг применения изменений правил сортировки. Метод динамичный.</p> <p>Данный метод применяет следующее непримененное правило сортировки к поисковому индексу. Применение правил требуется всякий раз когда происходят их изменения (<a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/add.php">CSearchCustomRank::Add</a>, <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/update.php">CSearchCustomRank::Update</a> и <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/delete.php">CSearchCustomRank::Delete</a>). При индексации данных или после переиндексации данная процедура не требуется, т.к. правила сортировки учитываются в процессе построения поискового индекса.</p> <p>Перед началом применения правил необходимо инициировать процесс вызвав <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/startupdate.php">CSearchCustomRank::StartUpdates</a>.</p>
	*
	*
	* @return array <p>В случае успешного выполнения возвращается массив следующей
	* структуры:</p> <ul> <li> <b>DONE</b> - количество уже примененных правил; </li>
	* <li> <b>TODO</b> - сколько правил еще надо применить.</li> </ul> <p>В противном
	* случае возвращается false и через LAST_ERROR экземпляра класса можно
	* получить текст сообщения об ошибке.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/startupdate.php">CSearchCustomRank::StartUpdates</a>
	* </li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/add.php">CSearchCustomRank::Add</a></li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/update.php">CSearchCustomRank::Update</a> </li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/delete.php">CSearchCustomRank::Delete</a></li>
	* </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/search/classes/csearchcustomrank/nextupdate.php
	* @author Bitrix
	*/
	public function NextUpdate()
	{
		$DB = CDatabase::GetModuleConnection('search');

		$rs = $this->GetList(
			array(
				"SITE_ID"=>"ASC"
				,"MODULE_ID"=>"ASC"
				,"PARAM1"=>"ASC"
				,"PARAM2"=>"ASC"
				,"ITEM_ID"=>"ASC"
			)
			,array(
				"APPLIED"=>"N"
			)
		);
		if($ar=$rs->Fetch())
		{
			$strSql = "
				UPDATE b_search_content
				SET CUSTOM_RANK=".intval($ar["RANK"])."
				WHERE CUSTOM_RANK<>".intval($ar["RANK"])."
				AND EXISTS (
					SELECT *
					FROM b_search_content_site scs
					WHERE scs.SEARCH_CONTENT_ID = b_search_content.ID
					AND scs.SITE_ID = '".$DB->ForSQL($ar["SITE_ID"])."'
				)
				AND MODULE_ID='".$DB->ForSQL($ar["MODULE_ID"])."'
				".($ar["PARAM1"]!=""?"AND PARAM1='".$DB->ForSQL($ar["PARAM1"])."'":"")."
				".($ar["PARAM2"]!=""?"AND PARAM2='".$DB->ForSQL($ar["PARAM2"])."'":"")."
				".($ar["ITEM_ID"]!=""?"AND ITEM_ID='".$DB->ForSQL($ar["ITEM_ID"])."'":"")."
			";
			$upd=$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if($upd)
				$upd=$this->Update($ar["ID"], array("APPLIED"=>"Y"));
			else
				$this->LAST_ERROR=GetMessage("customrank_error_update")."<br>";
		}
		if($this->LAST_ERROR=="")
		{
			$res=array("DONE"=>0, "TODO"=>0);
			$strSql = "
				SELECT APPLIED,COUNT(*) C
				FROM b_search_custom_rank
				GROUP BY APPLIED
			";
			$rs=$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while($ar=$rs->Fetch())
				if($ar["APPLIED"]=="Y")
					$res["DONE"]=$ar["C"];
				elseif($ar["APPLIED"]=="N")
					$res["TODO"]=$ar["C"];
			return $res;
		}
		else
			return false;
	}
	public static function __GetParam($lang, $site_id, $module_id=false, $param1=false, $param2=false, $item_id=false)
	{
		$name="";
		if($module_id=="iblock" && CModule::IncludeModule("iblock"))
		{
			if($item_id!==false)
			{
				$rs = CIBlockElement::GetByID($item_id);
				if($ar = $rs->GetNext())
					$name=$ar["NAME"];
			}
			elseif($param2!==false)
			{
				$rs=CIBlock::GetByID($param2);
				if($ar = $rs->GetNext())
					$name=$ar["NAME"];
			}
			elseif($param1!==false)
			{
				$rs=CIBlockType::GetByIDLang($param1, $lang);
				if(is_array($rs))
					$name=$rs["NAME"];
			}
			else
			{
				$name=GetMessage("customrank_iblocks");
			}
		}
		elseif($module_id=="forum"&& CModule::IncludeModule("forum"))
		{
			if($item_id!==false)
			{
				$name="";
			}
			elseif($param2!==false)
			{
				$rs = CForumTopic::GetByID($param2);
				if(is_array($rs))
					$name=htmlspecialcharsex($rs["TITLE"]);
			}
			elseif($param1!==false)
			{
				$rs = CForumNew::GetByID($param1);
				if(is_array($rs))
					$name=htmlspecialcharsex($rs["NAME"]);
			}
			else
			{
				$name=GetMessage("customrank_forum");
			}
		}
		elseif($module_id=="main")
		{
			if($item_id!==false)
			{
				$name="";
			}
			else
			{
				$name=GetMessage("customrank_files");
			}

		}
		elseif($module_id===false)
		{
			$rs = CSite::GetByID($site_id);
			if($ar = $rs->GetNext())
				$name=$ar["NAME"];
		}
		else
		{
			$name=false;
		}
		return $name;
	}
	///////////////////////////////////////////////////////////////////
	// Returns drop down list with modules
	///////////////////////////////////////////////////////////////////
	public static function ModulesList()
	{
		return array_merge(array("main" => GetMessage("customrank_files")), CSearchParameters::GetModulesList());
	}
	public static function ModulesSelectBox($sFieldName, $sValue, $sDefaultValue="", $sFuncName="", $field="class=\"typeselect\"")
	{
		$s = '<select name="'.$sFieldName.'" id="'.$sFieldName.'" '.$field;
		if(strlen($sFuncName)>0) $s .= ' OnChange="'.$sFuncName.'"';
		$s .= '>'."\n";

		$s1 = '<option value="main"'.($sValue=="main"?' selected':'').'>'.GetMessage("customrank_files").'</option>'."\n";
		foreach(CSearchParameters::GetModulesList() as $module_id => $module_name)
			$s1 .= '<option value="'.$module_id.'"'.($sValue==$module_id?' selected':'').'>'.htmlspecialcharsex($module_name).'</option>'."\n";

		if(strlen($sDefaultValue)>0)
			$s .= "<option value='NOT_REF'>".htmlspecialcharsex($sDefaultValue)."</option>";
		return $s.$s1.'</select>';
	}
}
?>