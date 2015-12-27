<?
/***************************************
	Form validator class
***************************************/


/**
 * <b>CFormValidator</b> - класс для работы с <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#validator">валидаторами</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/index.php
 * @author Bitrix
 */
class CAllFormValidator
{
	public static function err_mess()
	{
		$module_id = "form";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." (".$arModuleVersion["VERSION"].")<br>Class: CAllFormValidator<br>File: ".__FILE__;
	}

	/**
	 * Get filtered list of validators assigned to current field
	 *
	 * @param int $FIELD_ID
	 * @param array $arFilter
	 * @return CDBResult
	 */
	
	/**
	* <p>Возвращает список заданных для поля валидаторов в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	*
	*
	* @param int $FIELD_ID  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>.</bod
	*
	* @param mixed $arFilter = array() Массив для фильтрации. Необязательный параметр. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ACTIVE</b> - флаг активности
	* валидатора;</li> <li> <b>NAME</b> - идентификатор валидатора;</li> </ul>
	*
	* @param string &$by = "s_sort" Ссылка на переменную с полем для сортировки результирующего
	* списка. Может принимать значения: <ul> <li> <b>VALIDATOR_SID</b> - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#validator">валидатора</a>; </li> <li> <b>C_SORT</b> -
	* индекс сортировки. </li> </ul>
	*
	* @param string &$order = "asc" Ссылка на переменную с порядком сортировки. Может принимать
	* значения: <ul> <li> <b>asc</b> - по возрастанию; </li> <li> <b>desc</b> - по убыванию.
	* </li> </ul>
	*
	* @return CDBResult 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/getlist.php
	* @author Bitrix
	*/
	public static function GetList($FIELD_ID, $arFilter = array(), &$by, &$order)
	{
		$arFilter["FIELD_ID"] = $FIELD_ID;
		return CFormValidator::__getList($arFilter, $by, $order);
	}
	
	/**
	 * Get filtered list of validators assigned to current form
	 *
	 * @param int $WEB_FORM_ID
	 * @param array $arFilter
	 * @return CDBResult
	 */
	
	/**
	* <p>Возвращает список валидаторов, заданных для полей формы, в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	*
	*
	* @param int $FORM_ID  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a>.</bod
	*
	* @param mixed $arFilter = array() Массив для фильтрации. Необязательный параметр. В массиве
	* допустимы следующие ключи: <ul> <li> <b>FIELD_ID</b> - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>;</li> <li> <b>ACTIVE</b> - флаг
	* активности валидатора;</li> <li> <b>NAME</b> - идентификатор валидатора;</li>
	* </ul>
	*
	* @param string &$by = "s_sort" Ссылка на переменную с полем для сортировки результирующего
	* списка. Может принимать значения: <ul> <li> <b>VALIDATOR_SID</b> - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#validator">валидатора</a>; </li> <li> <b>C_SORT</b> -
	* индекс сортировки. </li> </ul>
	*
	* @param string &$order = "asc" Ссылка на переменную с порядком сортировки. Может принимать
	* значения: <ul> <li> <b>asc</b> - по возрастанию; </li> <li> <b>desc</b> - по убыванию.
	* </li> </ul>
	*
	* @return CDBResult 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/getlistform.php
	* @author Bitrix
	*/
	public static function GetListForm($WEB_FORM_ID, $arFilter = array(), &$by, &$order)
	{
		$arFilter["WEB_FORM_ID"] = $WEB_FORM_ID;
		return CFormValidator::__getList($arFilter, $by, $order);
	}

	public static function __getList($arFilter = array(), &$by, &$order)
	{
		global $DB;
		
		$arBy = array("ACTIVE", "C_SORT", "VALIDATOR_SID", "FIELD_ID");
		$by = strtoupper($by);
		if (!in_array($by, $arBy)) 
			$by = "C_SORT";
		
		$order = strtoupper($order);
		if ($order != "ASC" && $order != "DESC")
			$order = "ASC";
		
		$arWhere = array();
		foreach ($arFilter as $key => $value)
		{
			switch ($key)
			{
				case "WEB_FORM_ID":
					$arWhere[] = "FORM_ID='".intval($value)."'";
				break;
				
				case "FIELD_ID":
					$arWhere[] = "FIELD_ID='".intval($value)."'";
				break;
			
				case "ACTIVE":
					$arWhere[] = "ACTIVE='".($value == "N" ? "N" : "Y")."'";
				break;
				
				case "NAME":
					$arWhere[] = "VALIDATOR_SID='".$DB->ForSql($value)."'";
				break;
			}
		}
		
		if (count($arWhere) > 0)
			$strWhere = "WHERE ".implode(" AND ", $arWhere);
		else
			$strWhere = "";
		
		$query = "SELECT * FROM b_form_field_validator ".$strWhere." ORDER BY ".$by." ".$order;
		$rsList = $DB->Query($query, false, __LINE__);
		
		$arCurrentValidators = array();
		$rsFullList = CFormValidator::GetAllList();
		$arFullList = $rsFullList->arResult;
		while ($arCurVal = $rsList->Fetch())
		{
			foreach ($arFullList as $key => $arVal)
			{
				if ($arVal["NAME"] == $arCurVal["VALIDATOR_SID"])
				{
					$arCurVal["NAME"] = $arVal["NAME"];
					unset($arCurVal["VALIDATOR_SID"]);
					if (strlen($arCurVal["PARAMS"]) > 0)
					{
						$arCurVal["PARAMS"] = CFormValidator::GetSettingsArray($arVal, $arCurVal["PARAMS"]);
						$arCurVal["PARAMS_FULL"] = CFormValidator::GetSettings($arVal);
					}
					$arCurrentValidators[] = $arCurVal;
					break;
				}
			}
		}
		
		unset($rsList);
		$rsList = new CDBResult();
		$rsList->InitFromArray($arCurrentValidators);

		return $rsList;
	}
	
	/**
	 * Get filtered list of all registered validators. Filter params: TYPE = array|string;
	 *
	 * @param array $arFilter
	 * @return array
	 */
	
	/**
	* <p>Возвращает список зарегистрированных валидаторов в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	*
	*
	* @param array $mixedarFilter = array() Массив для фильтрации. Необязательный параметр. В массиве
	* допустимы следующие ключи: <ul> <li> <b>TYPE</b> - список типов полей;</li> </ul>
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* if (CModule::IncludeModule("form"))
	* {
	*   $arFilter = array("TYPE" =&gt; array("text", "textarea"));
	*   
	*   $sType = "&lt;b&gt;".implode("&lt;/b&gt;, &lt;b&gt;", $arFilter["TYPE"])."&lt;/b&gt;";
	* 
	*   $rsValidators = CFormValidator::GetAllList($arFilter);
	*   if ($rsValidators-&gt;SelectedRowsCount() &gt; 0)
	*   {
	*     echo "Найденные валидаторы для полей типа ".$sType.":&lt;ul&gt;";
	*     while ($arValidator = $rsValidators-&gt;GetNext())
	*     {
	*       echo "&lt;li&gt;[".$arValidator["NAME"]."] ".$arValidator["DESCRIPTION"]."&lt;/li&gt;";
	*     }
	*     echo "&lt;/ul&gt;";
	*   }
	*   else
	*   {
	*     echo "Валидаторов, применимых к полям типа ".$sType." не обнаружено.";
	*   }
	* }
	* else
	* {
	*   ShowError('Модуль веб-форм не установлен');
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/getalllist.php
	* @author Bitrix
	*/
	public static function GetAllList($arFilter = array())
	{
		if (is_array($arFilter) && count($arFilter) > 0)
		{
			$arType = $arFilter["TYPE"];
			
			$is_filtered = true;
		}
		else
		{
			$is_filtered = false;
		}
		
		$rsValList = GetModuleEvents("form", "onFormValidatorBuildList");
		if ($rsValList->SelectedRowsCount() > 0)
		{
			$arResult = array();
			while ($arValidator = $rsValList->Fetch())
			{
				$arValidatorInfo = ExecuteModuleEventEx($arValidator, $arParams = array());
				
				if ($is_filtered)
				{
					if (is_array($arValidatorInfo["TYPES"]))
					{
						if (
							(is_array($arType) && count(array_intersect($arType, $arValidatorInfo["TYPES"])))
							||
							(!is_array($arType) && in_array($arType, $arValidatorInfo["TYPES"]))
						)
						
						$arResult[] = $arValidatorInfo;
					}
				}
				else
				{
					$arResult[] = $arValidatorInfo;
				}
			}
		}
		else
		{
			return false;
		}
		
		unset($rsValList);
		$rsValList = new CDBResult;
		$rsValList->InitFromArray($arResult);
		
		return $rsValList;
	}
	
	/**
	 * Apply validator to value
	 *
	 * @param string $sValSID
	 * @param array $arParams
	 * @param mixed $arValue
	 * @return bool
	 */
	
	/**
	* <p>Выполняет валидатор для заданных значений ответов в применении к вопросу.</p>
	*
	*
	* @param array $arValidator  Описательный массив валидатора, один из элементов массива,
	* возвращаемого методом CFormValidator::GetList. Должен содержать следующие
	* параметры: <ul> <li> <b>NAME</b> - идентификатор валидатора</li> <li> <b>PARAMS</b> -
	* массив значений настроек валидатора</li> </ul>
	*
	* @param array $arQuestion  Описание вопроса. Во входящих в поставку валидаторах не
	* используется, но может быть использован в собственных
	* валидаторах.
	*
	* @param array $arAnswers  Массив описаний ответов вопроса. Во входящих в поставку
	* валидаторах не используется, но может быть использован в
	* собственных валидаторах.
	*
	* @param array $arValues  Массив ответов на вопрос в формате <code>array('значение1', 'значение2',
	* .... 'значение n')</code>.
	*
	* @return bool 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/execute.php
	* @author Bitrix
	*/
	public static function Execute($arValidator, $arQuestion, $arAnswers, $arAnswerValues)
	{
		$rsValidators = CFormValidator::GetAllList();
		while ($arValidatorInfo = $rsValidators->Fetch())
		{
			if ($arValidatorInfo["NAME"] == $arValidator["NAME"])
				break;
		}
		
		if ($arValidatorInfo)
		{
			if ($arValidatorInfo["HANDLER"])
			{
				return call_user_func($arValidatorInfo["HANDLER"], $arValidator["PARAMS"], $arQuestion, $arAnswers, $arAnswerValues);
			}
		}
		
		return true;
	}
	
	/**
	 * Assign validator to the field
	 *
	 * @param int $WEB_FORM_ID
	 * @param int $FIELD_ID
	 * @param string $sValSID
	 * @param array $arParams
	 * @return int|bool
	 */
	
	/**
	* <p>Прикрепляет валидатор с заданными настройками к полю формы. Возвращает true в случае успеха операции и false в случае ошибки (валидатора с таким ID не существует, ошибка в валидаторе и т.д.)</p>
	*
	*
	* @param int $WEB_FORM_ID  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a>.</bod
	*
	* @param int $FIELD_ID  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>.</bod
	*
	* @param string $VALIDATOR_SID  Идентификатор валидатора.
	*
	* @param array $arParams = array() Массив значений параметров валидатора. Необязательный параметр.
	*
	* @return bool 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/set.php
	* @author Bitrix
	*/
	public static function Set($WEB_FORM_ID, $FIELD_ID, $sValSID, $arParams = array(), $C_SORT = 100)
	{
		global $DB;
		
		$rsValList = CFormValidator::GetAllList();
		while ($arVal = $rsValList->Fetch())
		{
			if ($arVal["NAME"] == $sValSID)
			{
				$arQueryFields = array(
					"~TIMESTAMP_X" => $DB->CurrentTimeFunction(),
					"FORM_ID" => intval($WEB_FORM_ID),
					"FIELD_ID" => intval($FIELD_ID),
					"ACTIVE" => "Y",
					"C_SORT" => intval($C_SORT),
					"VALIDATOR_SID" => $DB->ForSql($sValSID),
				);
				
				if (count($arParams) > 0)
				{
					$strParams = CFormValidator::GetSettingsString($arVal, $arParams);
					$arQueryFields["PARAMS"] = $strParams;
				}
				
				return $DB->Add("b_form_field_validator", $arQueryFields);
			}
		}
	
		return false;
	}	
	
	/**
	 * Assign multiple validators to the field
	 *
	 * @param int $WEB_FORM_ID
	 * @param int $FIELD_ID
	 * @param array $arValidators
	 */
	
	/**
	* <p>Прикрепляет группу валидаторов с заданными настройками к полю формы. Аналогична вызову <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/set.php">CFormValidator::Set</a> для каждого валидатора группы.</p>
	*
	*
	* @param int $WEB_FORM_ID  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a>.</bod
	*
	* @param int $FIELD_ID  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>.</bod
	*
	* @param array $arValidators = array() Массив валидаторов. Каждый элемент массива должен представлять
	* собой ассоциативный массив с ключами: <ul> <li> <b>NAME</b> - идентификатор
	* валидатора;</li> <li> <b>PARAMS</b> - массив параметрова валидатора.</li> </ul>
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/setbatch.php
	* @author Bitrix
	*/
	public static function SetBatch($WEB_FORM_ID, $FIELD_ID, $arValidators)
	{
		global $DB;

		$rsValList = CFormValidator::GetAllList();
		$arValList = array();
		while ($arVal = $rsValList->Fetch())
		{
			$arValList[$arVal["NAME"]] = $arVal;
		}

		$C_SORT = 0;
		foreach ($arValidators as $key => $arFieldVal)
		{
			if ($arVal = $arValList[$arFieldVal["NAME"]])
			{
				$C_SORT += 100;
				$arQueryFields = array(
					"~TIMESTAMP_X" => $DB->CurrentTimeFunction(),
					"FORM_ID" => intval($WEB_FORM_ID),
					"FIELD_ID" => intval($FIELD_ID),
					"ACTIVE" => "Y",
					"C_SORT" => $C_SORT,
					"VALIDATOR_SID" => $arFieldVal["NAME"],
				);

				if (is_array($arFieldVal["PARAMS"]) && is_set($arVal, "CONVERT_TO_DB"))
				{
					$arParams = array();
					foreach ($arFieldVal["PARAMS"] as $key => $arParam)
					{
						$arParams[$arParam["NAME"]] = $arParam["VALUE"];
					}
				
					if (count($arParams) > 0)
					{
						$strParams = CFormValidator::GetSettingsString($arVal, $arParams);
						$arQueryFields["PARAMS"] = $strParams;
					}
				}
				
				$DB->Add("b_form_field_validator", $arQueryFields);
			}
		}
	}

	
	/**
	* <p>Возвращает список настроек валидатора со значениями, преобразованный для занесения в базу. Аналогичен прямому вызову метода преобразования настроек (<code>CONVERT_TO_DB</code>) валидатора. При вызове методов <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/getlist.php">CFormValidator::Set</a> и <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/getlistform.php">CFormValidator::SetBatch</a> вызывается автоматически.</p>
	*
	*
	* @param array $arValidator  Описательный массив валидатора (например, полученный из
	* результата метода CFormValidators::GetList).
	*
	* @param array $arParams  Массив значений настроек валидатора.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/getsettingsstring.php
	* @author Bitrix
	*/
	public static function GetSettingsString($arValidator, $arParams)
	{
		if (count($arParams) > 0 && is_set($arValidator, "CONVERT_TO_DB"))
		{
			$strParams = call_user_func($arValidator["CONVERT_TO_DB"], $arParams);
			return $strParams;
		}
	}
	
	
	/**
	* <p>Возвращает список настроек валидатора со значениями после обратного преобразования строки, занесенной в базу. Аналогичен прямому вызову метода обратного преобразования (<code>CONVERT_FROM_DB</code>) валидатора. При вызове методов <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/getlist.php">CFormValidator::GetList</a> и <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/getlistform.php">CFormValidator::GetListForm</a> вызывается автоматически.</p>
	*
	*
	* @param array $arValidator  Описательный массив валидатора (например, полученный из
	* результата метода CFormValidators::GetList).
	*
	* @param string $strParams  Строка со значениями настроек, хранящаяся в БД.
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/getsettingsarray.php
	* @author Bitrix
	*/
	public static function GetSettingsArray($arValidator, $strParams)
	{
		if (strlen($strParams) > 0 && is_set($arValidator, "CONVERT_FROM_DB"))
		{
			$arParams = call_user_func($arValidator["CONVERT_FROM_DB"], $strParams);
			return $arParams;
		}
	}
	
	
	/**
	* <p>Возвращает список настроек валидатора. Аналогично прямому вызову метода возврата настроек валидатора.</p>
	*
	*
	* @param array $arValidator  Описательный массив валидатора (например, полученный из
	* результата метода CFormValidators::GetAllList).
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/getsettings.php
	* @author Bitrix
	*/
	public static function GetSettings($arValidator)
	{
		if (is_set($arValidator, "SETTINGS"))
		{
			$arSettings = call_user_func($arValidator["SETTINGS"]);
			return $arSettings;
		}
	}
	
	/**
	 * Clear all field validators
	 *
	 * @param int $FIELD_ID
	 */
	
	/**
	* <p>Удаляет все валидаторы, назначенные данному вопросу/полю.</p>
	*
	*
	* @param int $FIELD_ID  ID поля.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/clear.php
	* @author Bitrix
	*/
	public static function Clear($FIELD_ID)
	{
		global $DB;
		$query = "DELETE FROM b_form_field_validator WHERE FIELD_ID='".$DB->ForSql($FIELD_ID)."'";
		$DB->Query($query, false, __LINE__);
	}
}
?>