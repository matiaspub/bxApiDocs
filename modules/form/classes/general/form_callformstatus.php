<?

/***************************************
	Статус результата веб-формы
***************************************/


/**
 * <b>CFormStatus</b> - класс для работы со <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статусами</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/index.php
 * @author Bitrix
 */
class CAllFormStatus
{
	fpublic static unction err_mess()
	{
		$module_id = "form";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." (".$arModuleVersion["VERSION"].")<br>Class: CAllFormStatus<br>File: ".__FILE__;
	}

	// права на статус по группам

	/**
	* <p> Возвращает массивы групп пользователей, имеющих определённые <a href="http://dev.1c-bitrix.ru/api_help/form/permissions.php#result">права</a> на указанный <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статус</a>.</p>
	*
	*
	* @param int $status_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a>.
	*
	* @param array &$can_view  Ссылка на массив для хранения ID групп пользователей, обладающих
	* правом на просмотр результата, находящемся в статусе <i>status_id</i>.
	*
	* @param array &$can_move  Ссылка на массив для хранения ID групп пользователей, обладающих
	* правом перевода результатов в статус <i>status_id</i>.
	*
	* @param array &$can_edit  Ссылка на массив для хранения ID групп пользователей, обладающих
	* правом редактирования результатов, находящихся в статусе
	* <i>status_id</i>.
	*
	* @param array &$can_delete  Ссылка на массив для хранения ID групп пользователей, обладающих
	* правом удаления результатов, находящихся в статусе <i>status_id</i>.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $STATUS_ID = 1;
	* 
	* // получим массив групп обладающих определёнными правами на статус #1
	* <b>CFormStatus::GetPermissionList</b>($STATUS_ID, $arVIEW, $arMOVE, $arEDIT, $arDELETE);
	* 
	* // выведем массив групп обладающих правом 
	* // просмотра результатов находящихся в статусе #1
	* echo "&lt;pre&gt;"; print_r($arVIEW); echo "&lt;/pre&gt;";
	* 
	* // выведем массив групп обладающих правом 
	* // перевода результатов в статус #1
	* echo "&lt;pre&gt;"; print_r($arMOVE); echo "&lt;/pre&gt;";
	* 
	* // выведем массив групп обладающих правом 
	* // редактирования результатов находящихся в статусе #1
	* echo "&lt;pre&gt;"; print_r($arEDIT); echo "&lt;/pre&gt;";
	* 
	* // выведем массив групп обладающих правом 
	* // удаления результатов находящихся в статусе #1
	* echo "&lt;pre&gt;"; print_r($arDELETE); echo "&lt;/pre&gt;";
	* 
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/permissions.php#result">Права на результат</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getpermission.php">CForm::GetPermission</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getpermissions.php">CFormResult::GetPermissions</a>
	* </li> </ul> </ht<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/getpermissionlist.php
	* @author Bitrix
	*/
	public static 	function GetPermissionList($STATUS_ID, &$arPERMISSION_VIEW, &$arPERMISSION_MOVE, &$arPERMISSION_EDIT, &$arPERMISSION_DELETE)
	{
		$err_mess = (CAllFormStatus::err_mess())."<br>Function: GetPermissionList<br>Line: ";
		global $DB, $strError;
		$STATUS_ID = intval($STATUS_ID);
		$arPERMISSION_VIEW = $arPERMISSION_MOVE = $arPERMISSION_EDIT = $arPERMISSION_DELETE = array();
		$strSql = "
			SELECT
				GROUP_ID,
				PERMISSION
			FROM
				b_form_status_2_group
			WHERE
				STATUS_ID='$STATUS_ID'
			";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		while ($zr=$z->Fetch())
		{
			if ($zr["PERMISSION"]=="VIEW")		$arPERMISSION_VIEW[] = $zr["GROUP_ID"];
			if ($zr["PERMISSION"]=="MOVE")		$arPERMISSION_MOVE[] = $zr["GROUP_ID"];
			if ($zr["PERMISSION"]=="EDIT")		$arPERMISSION_EDIT[] = $zr["GROUP_ID"];
			if ($zr["PERMISSION"]=="DELETE")	$arPERMISSION_DELETE[] = $zr["GROUP_ID"];
		}

	}

	// возвращает массив максимальных прав на результат
public static 	function GetMaxPermissions()
	{
		return array("VIEW","MOVE","EDIT","DELETE");
	}

	// права на статус

	/**
	* <p> Возвращает массив <a href="http://dev.1c-bitrix.ru/api_help/form/permissions.php#result">прав</a> текущего пользователя на указанный <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статус</a>. В качестве значений данного массива допустимы: </p> <ul> <li> <b>VIEW</b> - право на просмотр <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результатов</a> в данном статусе; </li> <li> <b>MOVE</b> - право на перевод результатов в данный статус; </li> <li> <b>EDIT</b> - право на редактирование результатов в данном статусе; </li> <li> <b>DELETE</b> - право на удаление результатов в данном статусе. </li> </ul>
	*
	*
	* @param int $status_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a>.
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $STATUS_ID = 1;
	* 
	* // получим права текущего пользователя для указанного статуса
	* $arPerm = <b>CFormStatus::GetPermissions</b>($STATUS_ID);
	* 
	* if (in_array("VIEW", $arPerm)) 
	*     echo "У вас есть право на просмотр результатов в данном статусе";
	* 
	* if (in_array("EDIT", $arPerm)) 
	*     echo "У вас есть право на редактирование результатов в данном статусе";
	* 
	* if (in_array("MOVE", $arPerm)) 
	*     echo "У вас есть право на установку данного статуса результатам";
	* 
	* if (in_array("DELETE", $arPerm)) 
	*     echo "У вас есть право на удаление результатов в данном статусе";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/permissions.php#result">Права на результат</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getpermission.php">CForm::GetPermission</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getpermissions.php">CFormResult::GetPermissions</a>
	* </li> </ul> </ht<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/getpermissions.php
	* @author Bitrix
	*/
	public static 	function GetPermissions($STATUS_ID)
	{
		$err_mess = (CAllFormStatus::err_mess())."<br>Function: GetPermissions<br>Line: ";

		global $DB, $USER, $strError;

		$USER_ID = $USER->GetID();
		$STATUS_ID = intval($STATUS_ID);
		$arReturn = array();
		$arGroups = $USER->GetUserGroupArray();

		if (!is_array($arGroups) || count($arGroups) <= 0)
			$arGroups = array(2);

		if (CForm::IsAdmin())
		{
			$arReturn = CFormStatus::GetMaxPermissions();
		}
		else
		{
			$groups = implode(",",$arGroups);

			$strSql = "
				SELECT
					G.PERMISSION
				FROM
					b_form_status_2_group G
				WHERE
					G.STATUS_ID = $STATUS_ID
				AND
					G.GROUP_ID IN (0,".$groups.")";

			$z = $DB->Query($strSql, false, $err_mess.__LINE__);
			while ($zr = $z->Fetch())
				$arReturn[] = $zr["PERMISSION"];
		}

		return $arReturn;
	}

public static 	function GetNextSort($WEB_FORM_ID)
	{
		$err_mess = (CAllFormStatus::err_mess())."<br>Function: GetNextSort<br>Line: ";
		global $DB, $strError;
		$WEB_FORM_ID = intval($WEB_FORM_ID);
		$strSql = "SELECT max(C_SORT) MAX_SORT FROM b_form_status WHERE FORM_ID=$WEB_FORM_ID";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		return intval($zr["MAX_SORT"])+100;
	}

public static 	function GetDefault($WEB_FORM_ID)
	{
		$err_mess = (CAllFormStatus::err_mess())."<br>Function: GetDefault<br>Line: ";
		global $DB, $USER, $strError;
		$WEB_FORM_ID = intval($WEB_FORM_ID);
		$strSql = "SELECT ID FROM b_form_status WHERE FORM_ID=$WEB_FORM_ID and ACTIVE='Y' and DEFAULT_VALUE='Y'";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		return intval($zr["ID"]);
	}

	// проверка статуса
public static 	function CheckFields($arFields, $STATUS_ID, $CHECK_RIGHTS="Y")
	{
		$err_mess = (CAllFormStatus::err_mess())."<br>Function: CheckFields<br>Line: ";
		global $DB, $strError, $APPLICATION, $USER;
		$str = "";
		$STATUS_ID = intval($STATUS_ID);
		$FORM_ID = intval($arFields["FORM_ID"]);
		if ($FORM_ID <= 0) $str .= GetMessage("FORM_ERROR_FORM_ID_NOT_DEFINED")."<br>";
		else
		{
			$RIGHT_OK = "N";
			if ($CHECK_RIGHTS!="Y" || CForm::IsAdmin()) $RIGHT_OK = "Y";
			else
			{
				$FORM_RIGHT = $APPLICATION->GetGroupRight("form");
				$F_RIGHT = CForm::GetPermission($FORM_ID);
				if ($FORM_RIGHT>"D" && $F_RIGHT>=30) $RIGHT_OK = "Y";
			}
			if ($RIGHT_OK=="Y")
			{
				if ($STATUS_ID<=0 || ($STATUS_ID>0 && is_set($arFields, "TITLE")))
				{
					if (strlen(trim($arFields["TITLE"]))<=0) $str .= GetMessage("FORM_ERROR_FORGOT_TITLE")."<br>";
				}
			}
			else $str .= GetMessage("FORM_ERROR_ACCESS_DENIED");
		}
		$strError .= $str;
		if (strlen($str)>0) return false; else return true;
	}

	// добавление/обновление статуса

	/**
	* <p>Добавляет новый <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статус</a> или обновляет существующий. Возвращает ID обновленного или добавленного <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a> в случае положительного результата, в противном случае - "false".</p>
	*
	*
	* @param array $fields  Массив значений, в качестве ключей массива допустимы: <ul> <li>
	* <b>FORM_ID</b><font color="red">*</font> - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a>; </li> <li> <b>TITLE</b><font
	* color="red">*</font> - заголовок <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a>; </li> <li> <b>C_SORT</b> -
	* порядок сортировки; </li> <li> <b>ACTIVE</b> - флаг активности; допустимы
	* следующие значения: <ul> <li> <b>Y</b> - ответ активен; </li> <li> <b>N</b> - ответ
	* не активен (по умолчанию). </li> </ul> </li> <li> <b>DESCRIPTION</b> - описание <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a>; </li> <li> <b>CSS</b> - имя CSS
	* класса для вывода заголовка <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a>; </li> <li> <b>HANDLER_OUT</b> -
	* путь относительно корня к <a
	* href="http://dev.1c-bitrix.ru/api_help/form/status_processing.php">обработчику</a>, вызываемому
	* при смене у <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>
	* данного <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a> на какой
	* либо другой; </li> <li> <b>HANDLER_IN</b> - путь относительно корня к <a
	* href="http://dev.1c-bitrix.ru/api_help/form/status_processing.php">обработчику</a>, вызываемому
	* при смене у <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>
	* какого либо <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a> на
	* данный; </li> <li> <b>DEFAULT_VALUE</b> - флаг установки <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a> по умолчанию,
	* допустимы следующие значения: <ul> <li> <b>Y</b> - статус будет
	* устанавливаться; </li> <li> <b>N</b> - статус не будет устанавливаться (по
	* умолчанию). </li> </ul> </li> <li> <b>arPERMISSION_VIEW</b>* - массив ID групп
	* пользователей, имеющих <a
	* href="http://dev.1c-bitrix.ru/api_help/form/permissions.php#result">право</a> "Просмотр
	* результатов в данном статусе"; </li> <li> <b>arPERMISSION_MOVE</b>* - массив ID
	* групп пользователей, имеющих <a
	* href="http://dev.1c-bitrix.ru/api_help/form/permissions.php#result">право</a> "Перевод
	* результатов в данный статус"; </li> <li> <b>arPERMISSION_EDIT</b>* - массив ID групп
	* пользователей, имеющих <a
	* href="http://dev.1c-bitrix.ru/api_help/form/permissions.php#result">право</a> "Редактирование
	* результатов в данном статусе"; </li> <li> <b>arPERMISSION_DELETE</b>* - массив ID
	* групп пользователей, имеющих <a
	* href="http://dev.1c-bitrix.ru/api_help/form/permissions.php#result">право</a> "Удаление
	* результатов в данном статусе". </li> </ul> <br><font color="red">*</font> -
	* обязательно к заполнению. <br>* - в данных массивах может быть
	* элемент со значением "0", означающий создателя <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>.
	*
	* @param mixed $status_id = false ID обновляемого <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a>.<br>
	* Параметр необязательный. По умолчанию - "false" (добавление нового <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a>).
	*
	* @param string $check_rights = "Y" Флаг необходимости проверки прав текущего пользователя.
	* Возможны следующие значения: <ul> <li> <b>Y</b> - права необходимо
	* проверить; </li> <li> <b>N</b> - право не нужно проверять. </li> </ul> Для
	* обновления статуса, либо создания нового статуса необходимо
	* иметь право <b>[30] Полный доступ</b> на форму указанную в
	* <i>fields</i>["FORM_ID"].<br><br>Параметр необязательный. По умолчанию - "Y"
	* (права необходимо проверить).
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $FORM_ID = 4; // ID веб-формы
	* 
	* $arFields = array(
	*     "FORM_ID"             =&gt; $FORM_ID,               // ID веб-формы
	*     "C_SORT"              =&gt; 100,                    // порядок сортировки
	*     "ACTIVE"              =&gt; "Y",                    // статус активен
	*     "TITLE"               =&gt; "Опубликовано",         // заголовок статуса
	*     "DESCRIPTION"         =&gt; "Окончательный статус", // описание статуса
	*     "CSS"                 =&gt; "statusgreen",          // CSS класс
	*     "HANDLER_OUT"         =&gt; "",                     // обработчик
	*     "HANDLER_IN"          =&gt; "",                     // обработчик
	*     "DEFAULT_VALUE"       =&gt; "N",                    // не по умолчанию
	*     "arPERMISSION_VIEW"   =&gt; array(2),               // право просмотра для всех
	*     "arPERMISSION_MOVE"   =&gt; array(),                // право перевода только админам
	*     "arPERMISSION_EDIT"   =&gt; array(),                // право редактирование для админам
	*     "arPERMISSION_DELETE" =&gt; array(),                // право удаления только админам
	* );
	* 
	* $NEW_ID = <b>CFormStatus::Set</b>($arFields);
	* if ($NEW_ID&gt;0) echo "Успешно добавлен ID=".$NEW_ID;
	* else // ошибка
	* {
	*     // выводим текст ошибки
	*     global $strError;
	*     echo $strError;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/index.php">Поля CFormStatus</a>
	* </li></ul></b<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/set.php
	* @author Bitrix
	*/
	public static 	function Set($arFields, $STATUS_ID=false, $CHECK_RIGHTS="Y")
	{
		$err_mess = (CAllFormStatus::err_mess())."<br>Function: Set<br>Line: ";
		global $DB, $USER, $strError, $APPLICATION;
		$STATUS_ID = intval($STATUS_ID);
		if (CFormStatus::CheckFields($arFields, $STATUS_ID, $CHECK_RIGHTS))
		{
			$arFields_i = array();

			$arFields_i["TIMESTAMP_X"] = $DB->GetNowFunction();

			if (is_set($arFields, "C_SORT"))
				$arFields_i["C_SORT"] = "'".intval($arFields["C_SORT"])."'";

			if (is_set($arFields, "ACTIVE"))
				$arFields_i["ACTIVE"] = ($arFields["ACTIVE"]=="Y") ? "'Y'" : "'N'";

			if (is_set($arFields, "TITLE"))
				$arFields_i["TITLE"] = "'".$DB->ForSql($arFields["TITLE"],255)."'";

			if (is_set($arFields, "DESCRIPTION"))
				$arFields_i["DESCRIPTION"] = "'".$DB->ForSql($arFields["DESCRIPTION"],2000)."'";

			if (is_set($arFields, "CSS"))
				$arFields_i["CSS"] = "'".$DB->ForSql($arFields["CSS"],255)."'";

			if (is_set($arFields, "HANDLER_OUT"))
				$arFields_i["HANDLER_OUT"] = "'".$DB->ForSql($arFields["HANDLER_OUT"],255)."'";

			if (is_set($arFields, "HANDLER_IN"))
				$arFields_i["HANDLER_IN"] = "'".$DB->ForSql($arFields["HANDLER_IN"],255)."'";

			if (is_set($arFields, "MAIL_EVENT_TYPE"))
				$arFields_i["MAIL_EVENT_TYPE"] = "'".$DB->ForSql($arFields["MAIL_EVENT_TYPE"],255)."'";

			$DEFAULT_STATUS_ID = intval(CFormStatus::GetDefault($arFields["FORM_ID"]));
			if ($DEFAULT_STATUS_ID<=0 || $DEFAULT_STATUS_ID==$STATUS_ID)
			{
				if (is_set($arFields, "DEFAULT_VALUE"))
					$arFields_i["DEFAULT_VALUE"] = ($arFields["DEFAULT_VALUE"]=="Y") ? "'Y'" : "'N'";
			}

			//echo '<pre>'; print_r($arFields); echo '</pre>';
			//die();

			if ($STATUS_ID>0)
			{
				$DB->Update("b_form_status", $arFields_i, "WHERE ID='".$STATUS_ID."'", $err_mess.__LINE__);
			}
			else
			{
				$arFields_i["FORM_ID"] = "'".intval($arFields["FORM_ID"])."'";
				$STATUS_ID = $DB->Insert("b_form_status", $arFields_i, $err_mess.__LINE__);
			}

			$STATUS_ID = intval($STATUS_ID);

			if ($STATUS_ID>0)
			{
				// право на просмотр
				if (is_set($arFields, "arPERMISSION_VIEW"))
				{
					$DB->Query("DELETE FROM b_form_status_2_group WHERE STATUS_ID='".$STATUS_ID."' and PERMISSION='VIEW'", false, $err_mess.__LINE__);
					if (is_array($arFields["arPERMISSION_VIEW"]))
					{
						reset($arFields["arPERMISSION_VIEW"]);
						foreach($arFields["arPERMISSION_VIEW"] as $gid)
						{
							$arFields_i = array(
								"STATUS_ID"		=> "'".intval($STATUS_ID)."'",
								"GROUP_ID"		=> "'".intval($gid)."'",
								"PERMISSION"	=> "'VIEW'"
							);
							$DB->Insert("b_form_status_2_group",$arFields_i, $err_mess.__LINE__);
						}
					}
				}

				// право на перевод
				if (is_set($arFields, "arPERMISSION_MOVE"))
				{
					$DB->Query("DELETE FROM b_form_status_2_group WHERE STATUS_ID='".$STATUS_ID."' and PERMISSION='MOVE'", false, $err_mess.__LINE__);
					if (is_array($arFields["arPERMISSION_MOVE"]))
					{
						reset($arFields["arPERMISSION_MOVE"]);
						foreach($arFields["arPERMISSION_MOVE"] as $gid)
						{
							$arFields_i = array(
								"STATUS_ID"		=> "'".intval($STATUS_ID)."'",
								"GROUP_ID"		=> "'".intval($gid)."'",
								"PERMISSION"	=> "'MOVE'"
							);
							$DB->Insert("b_form_status_2_group",$arFields_i, $err_mess.__LINE__);
						}
					}
				}

				// право на редактирование
				if (is_set($arFields, "arPERMISSION_EDIT"))
				{
					$DB->Query("DELETE FROM b_form_status_2_group WHERE STATUS_ID='".$STATUS_ID."' and PERMISSION='EDIT'", false, $err_mess.__LINE__);
					if (is_array($arFields["arPERMISSION_EDIT"]))
					{
						reset($arFields["arPERMISSION_EDIT"]);
						foreach($arFields["arPERMISSION_EDIT"] as $gid)
						{
							$arFields_i = array(
								"STATUS_ID"		=> "'".intval($STATUS_ID)."'",
								"GROUP_ID"		=> "'".intval($gid)."'",
								"PERMISSION"	=> "'EDIT'"
							);
							$DB->Insert("b_form_status_2_group",$arFields_i, $err_mess.__LINE__);
						}
					}
				}

				// право на удаление
				if (is_set($arFields, "arPERMISSION_DELETE"))
				{
					$DB->Query("DELETE FROM b_form_status_2_group WHERE STATUS_ID='".$STATUS_ID."' and PERMISSION='DELETE'", false, $err_mess.__LINE__);
					if (is_array($arFields["arPERMISSION_DELETE"]))
					{
						reset($arFields["arPERMISSION_DELETE"]);
						foreach($arFields["arPERMISSION_DELETE"] as $gid)
						{
							$arFields_i = array(
								"STATUS_ID"		=> "'".intval($STATUS_ID)."'",
								"GROUP_ID"		=> "'".intval($gid)."'",
								"PERMISSION"	=> "'DELETE'"
							);
							$DB->Insert("b_form_status_2_group",$arFields_i, $err_mess.__LINE__);
						}
					}
				}

				if (is_set($arFields, "arMAIL_TEMPLATE"))
				{
					$DB->Query("DELETE FROM b_form_status_2_mail_template WHERE STATUS_ID='".$STATUS_ID."'", false, $err_mess.__LINE__);
					if (is_array($arFields["arMAIL_TEMPLATE"]))
					{
						reset($arFields["arMAIL_TEMPLATE"]);
						foreach($arFields["arMAIL_TEMPLATE"] as $mid)
						{
							$strSql = "
								INSERT INTO b_form_status_2_mail_template (STATUS_ID, MAIL_TEMPLATE_ID) VALUES (
									'".$STATUS_ID."',
									'".intval($mid)."'
								)
								";
							$DB->Query($strSql, false, $err_mess.__LINE__);
						}
					}
				}
			}
			return $STATUS_ID;
		}
		return false;
	}

	// удаляет статус

	/**
	* <p>Удаляет <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статус</a>. Возвращает "true" в случае положительного результата, и "false" - в противном случае.</p> <p class="note"><b>Примечание</b><br>Статусы, в которых находится хотя бы один <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результат</a>, невозможно удалить.</p>
	*
	*
	* @param int $status_id  ID удаляемого <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a>. </htm
	*
	* @param string $check_rights = "Y" Флаг необходимости проверки <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#permissions">прав</a> текущего
	* пользователя. Возможны следующие значения: <ul> <li> <b>Y</b> - права
	* необходимо проверить; </li> <li> <b>N</b> - право не нужно проверять. </li>
	* </ul> Для успешного выполнения удаления необходимо иметь <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#permissions#form">право</a> <b>[30] Полный
	* доступ</b> на веб-форму, к которой принадлежит <i>status_id</i>. <br>Параметр
	* необязательный. По умолчанию - "Y" (права необходимо проверить).
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $status_id = 1; // ID статуса
	* // удалим статус
	* if (<b>CFormStatus::Delete</b>($status_id))
	* {
	*     echo "Статус #1 успешно удален.";
	* }
	* else
	* {
	*     // выведем текст ошибки
	*     global $strError;
	*     echo $strError;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/delete.php">CForm::Delete</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/delete.php">CFormField::Delete</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/delete.php">CFormAnswer::Delete</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/delete.php">CFormResult::Delete</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/delete.php
	* @author Bitrix
	*/
	public static 	function Delete($ID, $CHECK_RIGHTS="Y")
	{
		global $DB, $APPLICATION, $strError;
		$ID = intval($ID);
		$rsStatus = CFormStatus::GetByID($ID);
		if ($arStatus = $rsStatus->Fetch())
		{
			$RIGHT_OK = "N";
			if ($CHECK_RIGHTS!="Y" || CForm::IsAdmin())
				$RIGHT_OK="Y";
			else
			{
				$F_RIGHT = CForm::GetPermission($arStatus["FORM_ID"]);
				if ($F_RIGHT>=30) $RIGHT_OK="Y";
			}
			if ($RIGHT_OK=="Y")
			{
				$strSql = "SELECT 'x' FROM b_form_result WHERE STATUS_ID='$ID'";
				$z = $DB->Query($strSql, false, $err_mess.__LINE__);
				if (!$zr = $z->Fetch())
				{
					if ($DB->Query("DELETE FROM b_form_status WHERE ID='$ID'", false, $err_mess.__LINE__))
					{
						if ($DB->Query("DELETE FROM b_form_status_2_group WHERE STATUS_ID='$ID'", false, $err_mess.__LINE__))
							return true;
					}
				}
				else
					$strError .= GetMessage("FORM_ERROR_CANNOT_DELETE_STATUS")."<br>";
			}
		}
		else
			$strError .= GetMessage("FORM_ERROR_STATUS_NOT_FOUND")."<br>";
		return false;
	}

	// копирует статус
	f
	/**
	* <p>Копирует <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статус</a>. Возвращает ID нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a> в случае положительного результата, в противном случае - "false".</p>
	*
	*
	* @param int $status_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a> который
	* необходимо скопировать.
	*
	* @param string $check_rights = "Y" Флаг необходимости проверки <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#permissions">прав</a> текущего
	* пользователя. Возможны следующие значения: <ul> <li> <b>Y</b> - права
	* необходимо проверить; </li> <li> <b>N</b> - право не нужно проверять. </li>
	* </ul> Для копирования <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a>
	* необходимо обладать нижеследующими <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#permissions#module">правами</a>: <ol> <li> <b>[25]
	* просмотр параметров веб-формы</b> на ту веб-форму, из которой идет
	* копирование; </li> <li> <b>[30] полный доступ</b> на ту веб-форму, в которую
	* копируется. </li> </ol> Параметр необязательный. По умолчанию - "Y"
	* (права необходимо проверить).
	*
	* @param mixed $form_id = false ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a> в который
	* необходимо скопировать <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статус</a>.<br> Необязательный
	* параметр. По умолчанию - "false" (текущая <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-форма</a>).
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $status_id = 1; // ID статуса
	* // скопируем статус
	* if ($NEW_STATUS_ID = <b>CFormStatus::Copy</b>($status_id))
	* {
	*     echo "Статус #1 успешно скопирован в новый статус #".$NEW_STATUS_ID;
	* }
	* else
	* {
	*     // выведем текст ошибки
	*     global $strError;
	*     echo $strError;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/copy.php">CForm::Copy</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/copy.php">CFormField::Copy</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/copy.php">CFormAnswer::Copy</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/copy.php
	* @author Bitrix
	*/
	public static unction Copy($ID, $CHECK_RIGHTS="Y", $NEW_FORM_ID=false)
	{
		global $DB, $APPLICATION, $strError;
		$err_mess = (CAllFormStatus::err_mess())."<br>Function: Copy<br>Line: ";
		$ID = intval($ID);
		$NEW_FORM_ID = intval($NEW_FORM_ID);
		$rsStatus = CFormStatus::GetByID($ID);
		if ($arStatus = $rsStatus->Fetch())
		{
			$RIGHT_OK = "N";
			if ($CHECK_RIGHTS!="Y" || CForm::IsAdmin()) $RIGHT_OK="Y";
			else
			{
				$F_RIGHT = CForm::GetPermission($arStatus["FORM_ID"]);
				// если имеем право на просмотр параметров формы
				if ($F_RIGHT>=25)
				{
					// если задана новая форма
					if ($NEW_FORM_ID>0)
					{
						$NEW_F_RIGHT = CForm::GetPermission($NEW_FORM_ID);
						// если имеем полный доступ на новую форму
						if ($NEW_F_RIGHT>=30) $RIGHT_OK = "Y";
					}
					elseif ($F_RIGHT>=30) // если имеем полный доступ на исходную форму
					{
						$RIGHT_OK = "Y";
					}
				}
			}

			// если права проверили то
			if ($RIGHT_OK=="Y")
			{
				CFormStatus::GetPermissionList($ID, $arPERMISSION_VIEW, $arPERMISSION_MOVE, $arPERMISSION_EDIT, $arPERMISSION_DELETE);
				// копируем
				$arFields = array(
					"FORM_ID"				=> ($NEW_FORM_ID>0) ? $NEW_FORM_ID : $arStatus["FORM_ID"],
					"C_SORT"				=> $arStatus["C_SORT"],
					"ACTIVE"				=> $arStatus["ACTIVE"],
					"TITLE"					=> $arStatus["TITLE"],
					"DESCRIPTION"			=> $arStatus["DESCRIPTION"],
					"CSS"					=> $arStatus["CSS"],
					"HANDLER_OUT"			=> $arStatus["HANDLER_OUT"],
					"HANDLER_IN"			=> $arStatus["HANDLER_IN"],
					"DEFAULT_VALUE"			=> $arStatus["DEFAULT_VALUE"],
					"arPERMISSION_VIEW"		=> $arPERMISSION_VIEW,
					"arPERMISSION_MOVE"		=> $arPERMISSION_MOVE,
					"arPERMISSION_EDIT"		=> $arPERMISSION_EDIT,
					"arPERMISSION_DELETE"	=> $arPERMISSION_DELETE,
					);
				$NEW_ID = CFormStatus::Set($arFields);
				return $NEW_ID;
			}
			else $strError .= GetMessage("FORM_ERROR_ACCESS_DENIED")."<br>";
		}
		else $strError .= GetMessage("FORM_ERROR_STATUS_NOT_FOUND")."<br>";
		return false;
	}

public static 	function SetMailTemplate($WEB_FORM_ID, $STATUS_ID, $ADD_NEW_TEMPLATE="Y", $old_SID="", $bReturnFullInfo = false)
	{
		global $DB, $MESS, $strError;
		$err_mess = (CAllForm::err_mess())."<br>Function: SetMailTemplate<br>Line: ";
		$arrReturn = array();
		$WEB_FORM_ID = intval($WEB_FORM_ID);
		$q = CForm::GetByID($WEB_FORM_ID);
		if ($arrForm = $q->Fetch())
		{
			$dbRes = CFormStatus::GetByID($STATUS_ID);
			if ($arrStatus = $dbRes->Fetch())
			{
				$MAIL_EVENT_TYPE = "FORM_STATUS_CHANGE_".$arrForm["SID"]."_".$arrStatus['ID'];
				if (strlen($old_SID)>0)
					$old_MAIL_EVENT_TYPE = "FORM_STATUS_CHANGE_".$old_SID."_".$arrStatus['ID'];

				$et = new CEventType;
				$em = new CEventMessage;

				if (strlen($MAIL_EVENT_TYPE)>0)
					$et->Delete($MAIL_EVENT_TYPE);

				$z = CLanguage::GetList($v1, $v2);
				$OLD_MESS = $MESS;
				$MESS = array();
				while ($arLang = $z->Fetch())
				{
					IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/form/admin/form_status_mail.php", $arLang["LID"]);

					$str = "";
					$str .= "#EMAIL_TO# - ".GetMessage("FORM_L_EMAIL_TO")."\n";
					$str .= "#RS_FORM_ID# - ".GetMessage("FORM_L_FORM_ID")."\n";
					$str .= "#RS_FORM_NAME# - ".GetMessage("FORM_L_NAME")."\n";
					$str .= "#RS_FORM_SID# - ".GetMessage("FORM_L_SID")."\n";
					$str .= "#RS_RESULT_ID# - ".GetMessage("FORM_L_RESULT_ID")."\n";
					$str .= "#RS_DATE_CREATE# - ".GetMessage("FORM_L_DATE_CREATE")."\n";
					$str .= "#RS_USER_ID# - ".GetMessage("FORM_L_USER_ID")."\n";
					$str .= "#RS_USER_EMAIL# - ".GetMessage("FORM_L_USER_EMAIL")."\n";
					$str .= "#RS_USER_NAME# - ".GetMessage("FORM_L_USER_NAME")."\n";
					$str .= "#RS_STATUS_ID# - ".GetMessage("FORM_L_STATUS_ID")."\n";
					$str .= "#RS_STATUS_NAME# - ".GetMessage("FORM_L_STATUS_NAME")."\n";

					$et->Add(
							Array(
							"LID"			=> $arLang["LID"],
							"EVENT_NAME"	=> $MAIL_EVENT_TYPE,
							"NAME"			=> str_replace(array('#FORM_SID#', '#STATUS_NAME#'), array($arrForm['SID'], $arrStatus['TITLE']), GetMessage("FORM_CHANGE_STATUS")),
							"DESCRIPTION"	=> $str
							)
						);
				}
				// create new event type for old templates
				if (strlen($old_MAIL_EVENT_TYPE)>0 && $old_MAIL_EVENT_TYPE!=$MAIL_EVENT_TYPE)
				{
					$e = $em->GetList($by="id",$order="desc",array("EVENT_NAME"=>$old_MAIL_EVENT_TYPE));
					while ($er=$e->Fetch())
					{
						$em->Update($er["ID"],array("EVENT_NAME"=>$MAIL_EVENT_TYPE));
					}
					if (strlen($old_MAIL_EVENT_TYPE)>0)
						$et->Delete($old_MAIL_EVENT_TYPE);
				}

				if ($ADD_NEW_TEMPLATE=="Y")
				{
					$z = CSite::GetList($v1, $v2);
					while ($arSite = $z->Fetch()) $arrSiteLang[$arSite["ID"]] = $arSite["LANGUAGE_ID"];

					$arrFormSite = CForm::GetSiteArray($WEB_FORM_ID);
					if (is_array($arrFormSite) && count($arrFormSite)>0)
					{
						foreach($arrFormSite as $sid)
						{
							IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/form/admin/form_status_mail.php", $arrSiteLang[$sid]);

							$SUBJECT = GetMessage("FORM_CHANGE_STATUS_S");
							$MESSAGE = GetMessage("FORM_CHANGE_STATUS_B");

							// добавляем новый шаблон
							$arFields = Array(
								"ACTIVE"		=> "Y",
								"EVENT_NAME"	=> $MAIL_EVENT_TYPE,
								"LID"			=> $sid,
								"EMAIL_FROM"	=> "#DEFAULT_EMAIL_FROM#",
								"EMAIL_TO"		=> "#EMAIL_TO#",
								"SUBJECT"		=> $SUBJECT,
								"MESSAGE"		=> $MESSAGE,
								"BODY_TYPE"		=> "text"
								);
							//echo '<pre>'; print_r($arFields); echo '</pre>';
							$TEMPLATE_ID = $em->Add($arFields);
							if ($bReturnFullInfo)
								$arrReturn[] = array(
									'ID' => $TEMPLATE_ID,
									'FIELDS' => $arFields,
								);
							else
								$arrReturn[] = $TEMPLATE_ID;

						}
					}
				}

				CFormStatus::Set(array('FORM_ID' => $WEB_FORM_ID, 'MAIL_EVENT_TYPE' => $MAIL_EVENT_TYPE), $STATUS_ID, 'N');

				$MESS = $OLD_MESS;
			}
		}
		return $arrReturn;
	}

public static 	function GetMailTemplateArray($STATUS_ID)
	{
		$err_mess = (CAllFormStatus::err_mess())."<br>Function: GetMailTemplateArray<br>Line: ";

		global $DB, $USER, $strError;

		$STATUS_ID = intval($STATUS_ID);
		if ($STATUS_ID <= 0) return false;

		$arrRes = array();
		$strSql = "
SELECT
	FM.MAIL_TEMPLATE_ID
FROM
	b_form_status_2_mail_template FM
WHERE
	FM.STATUS_ID='".$STATUS_ID."'
";
		//echo "<pre>".$strSql."</pre>";
		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		while ($ar = $rs->Fetch()) $arrRes[] = $ar["MAIL_TEMPLATE_ID"];
		//echo "<pre>".print_r($arrRes, true)."</pre>";

		return $arrRes;
	}

public static 	function GetTemplateList($STATUS_ID)
	{
		$err_mess = (CAllForm::err_mess())."<br>Function: GetTemplateList<br>Line: ";
		global $DB, $strError;

		$STATUS_ID = intval($STATUS_ID);
		if ($STATUS_ID > 0)
		{
			$arrSITE = array();
			$strSql = "
SELECT
	F.MAIL_EVENT_TYPE,
	FS.SITE_ID
FROM b_form_status F
INNER JOIN b_form_2_site FS ON (FS.FORM_ID = F.FORM_ID)
WHERE
	F.ID='".$STATUS_ID."'
";

			//echo '<pre>',$strSql,'</pre>';
			$z = $DB->Query($strSql,false,$err_mess.__LINE__);
			while ($zr = $z->Fetch())
			{
				$MAIL_EVENT_TYPE = $zr["MAIL_EVENT_TYPE"];
				$arrSITE[] = $zr["SITE_ID"];
			}

			if (strlen($MAIL_EVENT_TYPE) <= 0)
				return false;

			$arReferenceId = array();
			$arReference = array();
			$arFilter = Array(
				"ACTIVE"		=> "Y",
				"SITE_ID"		=> $arrSITE,
				"EVENT_NAME"	=> $MAIL_EVENT_TYPE
				);
			$e = CEventMessage::GetList($by="id", $order="asc", $arFilter);
			while ($er=$e->Fetch())
			{
				if (!in_array($er["ID"], $arReferenceId))
				{
					$arReferenceId[] = $er["ID"];
					$arReference[] = "(".$er["LID"].") ".TruncateText($er["SUBJECT"],50);
				}
			}

			$arr = array("reference"=>$arReference,"reference_id"=>$arReferenceId);
			return $arr;
		}
		return false;
	}
}
?>