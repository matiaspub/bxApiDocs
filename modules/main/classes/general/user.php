<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

use Bitrix\Main;
use Bitrix\Main\Authentication\ApplicationPasswordTable;

IncludeModuleLangFile(__FILE__);

global $BX_GROUP_POLICY;
$BX_GROUP_POLICY = array(
	"SESSION_TIMEOUT"	=>	0, //minutes
	"SESSION_IP_MASK"	=>	"0.0.0.0",
	"MAX_STORE_NUM"		=>	10,
	"STORE_IP_MASK"		=>	"0.0.0.0",
	"STORE_TIMEOUT"		=>	60*24*365, //minutes
	"CHECKWORD_TIMEOUT"	=>	60*24*365,  //minutes
	"PASSWORD_LENGTH"	=>	false,
	"PASSWORD_UPPERCASE"	=>	"N",
	"PASSWORD_LOWERCASE"	=>	"N",
	"PASSWORD_DIGITS"	=>	"N",
	"PASSWORD_PUNCTUATION"	=>	"N",
	"LOGIN_ATTEMPTS"	=>	0,
);


/**
 * <b>CUser</b> - класс для работы с пользователями.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php
 * @author Bitrix
 */
abstract class CAllUser extends CDBResult
{
	var $LAST_ERROR = "";
	var $bLoginByHash = false;
	protected $admin = null;
	protected static $CURRENT_USER = false;
	protected $justAuthorized = false;
	protected static $userGroupCache = array();

	
	/**
	* <p>Метод добавляет нового пользователя. При успешном выполнении возвращает ID нового пользователя, в противном случае - вернет "false", а в свойстве LAST_ERROR объекта будет содержаться текст ошибки. Нестатический метод.</p> <p></p> <div class="note"> <b>Примечание</b>: CUser::Add можно вызывать только как метод инициализированного объекта, а не как статический метод класса CUser.</div>
	*
	*
	* @param array $fields  Массив значений полей, в качестве ключей данного массива
	* допустимо использовать: 	<ul> <li> <b>LOGIN</b><font color="red">*</font> - логин (имя
	* входа) 		</li> <li> <b>NAME</b> - имя пользователя 		</li> <li> <b>LAST_NAME</b> - фамилия
	* пользователя 		</li> <li> <b>SECOND_NAME</b> - отчество пользователя 		</li> <li>
	* <b>EMAIL</b><font color="red">*</font> - E-Mail адрес пользователя 		</li> <li> <b>PASSWORD</b><font
	* color="red">*</font> - пароль пользователя 		</li> <li> <b>CONFIRM_PASSWORD</b><font
	* color="red">*</font> - подтверждение пароля (должно быть равным <b>PASSWORD</b>)
	* 		</li> <li> <b>GROUP_ID</b> - массив ID групп к которым будет приписан
	* пользователь 		</li> <li> <b>ACTIVE</b> - флаг активности пользователя [Y|N]
	* 		</li> <li> <b>LID</b> - ID сайта по умолчанию для уведомлений 		</li> <li>
	* <b>ADMIN_NOTES</b> - заметки администратора 		</li> <li> <b>XML_ID</b> - ID
	* пользователя для связи с внешними источниками (например, ID
	* пользователя в какой-либо внешний базе) 		</li> <li> <b>EXTERNAL_AUTH_ID</b> - код
	* источника [link=89611]внешней  авторизации[/link] 		</li> <li> <b>PERSONAL_PROFESSION</b> -
	* наименование профессии 		</li> <li> <b>PERSONAL_WWW</b> - персональная
	* WWW-страница 		</li> <li> <b>PERSONAL_ICQ</b> - ICQ 		</li> <li> <b>PERSONAL_GENDER</b> - пол ["M" -
	* мужчина; "F" - женщина] 		</li> <li> <b>PERSONAL_BIRTHDAY</b> - дата рождения в
	* формате текущего сайта (или текущего языка для административной
	* части) 		</li> <li> <b>PERSONAL_PHOTO</b> - массив описывающий фотографию,
	* допустимы следующие ключи этого массива: 			<ul> <li> <b>name</b> - имя файла
	* 				</li> <li> <b>size</b> - размер файла 				</li> <li> <b>tmp_name</b> - временный путь на
	* сервере 				</li> <li> <b>type</b> - тип загружаемого файла 				</li> <li> <b>del</b> -
	* если значение равно "Y", то изображение будет удалено 				</li> <li>
	* <b>MODULE_ID</b> - идентификатор главного модуля - "main" 			</li> </ul> </li> <li>
	* <b>PERSONAL_PHONE</b> - телефон 		</li> <li> <b>PERSONAL_FAX</b> - факс 		</li> <li> <b>PERSONAL_MOBILE</b> -
	* мобильный телефон 		</li> <li> <b>PERSONAL_PAGER</b> - пэйджер 		</li> <li>
	* <b>PERSONAL_STREET</b> - улица, дом 		</li> <li> <b>PERSONAL_MAILBOX</b> - почтовый ящик 		</li>
	* <li> <b>PERSONAL_CITY</b> - город 		</li> <li> <b>PERSONAL_STATE</b> - область / край 		</li> <li>
	* <b>PERSONAL_ZIP</b> - индекс 		</li> <li> <b>PERSONAL_COUNTRY</b> - страна 		</li> <li>
	* <b>PERSONAL_NOTES</b> - личные заметки 		</li> <li> <b>WORK_COMPANY</b>  - наименование
	* компании 		</li> <li> <b>WORK_DEPARTMENT</b> - департамент / отдел 		</li> <li>
	* <b>WORK_POSITION</b> - должность 		</li> <li> <b>WORK_WWW</b> - WWW-страница компании 		</li>
	* <li> <b>WORK_PHONE</b> - рабочий телефон 		</li> <li> <b>WORK_FAX</b> - рабочий факс 		</li>
	* <li> <b>WORK_PAGER</b> - рабочий пэйджер 		</li> <li> <b>WORK_STREET</b> - улица, дом
	* компании 		</li> <li> <b>WORK_MAILBOX</b> - почтовый ящик компании 		</li> <li>
	* <b>WORK_CITY</b> - город компании 		</li> <li> <b>WORK_STATE</b> - область / край
	* компании 		</li> <li> <b>WORK_ZIP</b> - индекс компании 		</li> <li> <b>WORK_COUNTRY</b> -
	* страна компании 		</li> <li> <b>WORK_PROFILE</b> - направления деятельности
	* компании 		</li> <li> <b>WORK_LOGO</b> - массив описывающий логотип компании,
	* допустимы следующие ключи этого массива: 			<ul> <li> <b>name</b> - имя файла
	* 				</li> <li> <b>size</b> - размер файла 				</li> <li> <b>tmp_name</b> - временный путь на
	* сервере 				</li> <li> <b>type</b> - тип загружаемого файла 				</li> <li> <b>del</b> -
	* если значение равно "Y", то изображение будет удалено 				</li> <li>
	* <b>MODULE_ID</b> - идентификатор главного модуля - "main" 			</li> </ul> </li> <li>
	* <b>WORK_NOTES</b> - заметки касаемо работы пользователя 	</li> </ul> <font
	* color="red">*</font> - обязательные поля.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // создадим массив описывающий изображение 
	* // находящееся в файле на сервере
	* $arIMAGE = CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"]."/images/photo.gif");
	* $arIMAGE["MODULE_ID"] = "main";
	* 
	* $user = new CUser;
	* $arFields = Array(
	*   "NAME"              =&gt; "Сергей",
	*   "LAST_NAME"         =&gt; "Иванов",
	*   "EMAIL"             =&gt; "ivanov@microsoft.com",
	*   "LOGIN"             =&gt; "ivan",
	*   "LID"               =&gt; "ru",
	*   "ACTIVE"            =&gt; "Y",
	*   "GROUP_ID"          =&gt; array(10,11),
	*   "PASSWORD"          =&gt; "123456",
	*   "CONFIRM_PASSWORD"  =&gt; "123456",
	*   "PERSONAL_PHOTO"    =&gt; $arIMAGE
	* );
	* 
	* $ID = <b>$user-&gt;Add</b>($arFields);
	* if (intval($ID) &gt; 0)
	*     echo "Пользователь успешно добавлен.";
	* else
	*     echo $user-&gt;LAST_ERROR;
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php#flds">Поля CUser</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/update.php">CUser::Update</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/delete.php">CUser::Delete</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/add.php
	* @author Bitrix
	*/
	abstract public function Add($arFields);

	
	/**
	* <p>Возвращает один из параметров пользователя хранимых в сессии авторизации (как правило вызывается с объекта $USER). Нестатический метод.</p>
	*
	*
	* @param string $param_name  Название параметра. Возможны следующие значения: 		<ul> <li> <b>AUTHORIZED</b>
	* - если пользователь авторизован, то "Y" 			</li> <li> <b>USER_ID</b> - ID
	* пользователя 			</li> <li> <b>LOGIN</b> - логин 			</li> <li> <b>EMAIL</b> - E-mail 			</li> <li>
	* <b>NAME</b> - полное имя (не только имя пользователя, но и фамилию) 			</li>
	* <li> <b>GROUPS</b> - массив групп, которым принадлежит пользователь 			</li>
	* <li> <b>ADMIN</b> - true, если пользователь принадлежит группе
	* администраторов 			</li> <li> <b>PASSWORD_HASH</b> - соль и хеш пароля с солью <pre
	* class="syntax">$salt . md5($salt . $pass)</pre> где <code>$salt</code> - 8 случайных символов,
	* которые меняются при каждой смене пароля.  			</li> <li> <b>FIRST_NAME</b> - имя
	* пользователя 			</li> <li> <b>LAST_NAME</b> - фамилия пользователя 			</li> <li>
	* <b>SECOND_NAME</b> - отчество пользователя  		</li> </ul>
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* global $USER;
	* echo "E-Mail: ".<b>$USER-&gt;GetParam</b>("EMAIL");
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php">Поля CUser</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/isadmin.php">CUser::IsAdmin</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/isauthorized.php">CUser::IsAuthorized</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getlogin.php">CUser::GetID</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getlogin.php">CUser::GetLogin</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getemail.php">CUser::GetEmail</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getfullname.php">CUser::GetFullName</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getfirstname.php">CUser::GetFirstName</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getlastname.php">CUser::GetLastName</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergrouparray.php">CUser::GetUserGroupArray</a> </li>
	* <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergroupstring.php">CUser::GetUserGroupString</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getparam.php
	* @author Bitrix
	*/
	static public function GetParam($name)
	{
		if(isset($_SESSION["SESS_AUTH"][$name]))
			return $_SESSION["SESS_AUTH"][$name];
		else
			return null;
	}

	static public function GetSecurityPolicy()
	{
		if(!is_set($_SESSION["SESS_AUTH"], "POLICY"))
			$_SESSION["SESS_AUTH"]["POLICY"] = CUser::GetGroupPolicy($_SESSION["SESS_AUTH"]["USER_ID"]);
		return $_SESSION["SESS_AUTH"]["POLICY"];
	}

	
	/**
	* <p>Метод устанавливает произвольный параметр пользователя<i> param_name</i> для хранения в сессии авторизации (как правило вызывается для объекта $USER). Получить значение установленного параметра можно методом <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getparam.php">CUser::GetParam</a>.  Нестатический метод.</p>
	*
	*
	* @param string $name  Произвольный параметр.
	*
	* @param mixed $value  Значение параметра.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* global $USER;
	* <b>$USER-&gt;SetParam</b>("IP_LOGIN", $_SERVER['REMOTE_ADDR']);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getparam.php">CUser::GetParam</a> </li></ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/setparam.php
	* @author Bitrix
	*/
	static public function SetParam($name, $value)
	{
		$_SESSION["SESS_AUTH"][$name] = $value;
	}

	
	/**
	* <p>Возвращает ID текущего авторизованного пользователя (как правило вызывается с объекта $USER). Нестатический метод.</p>
	*
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* global $USER;
	* echo "[".<b>$USER-&gt;GetID</b>()."] (".$USER-&gt;GetLogin().") ".$USER-&gt;GetFullName();
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php">Поля CUser</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getlogin.php">CUser::GetLogin</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getemail.php">CUser::GetEmail</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getfullname.php">CUser::GetFullName</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getfirstname.php">CUser::GetFirstName</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getlastname.php">CUser::GetLastName</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getparam.php">CUser::GetParam</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergrouparray.php">CUser::GetUserGroupArray</a> </li>
	* <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergroupstring.php">CUser::GetUserGroupString</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getid.php
	* @author Bitrix
	*/
	static public function GetID()
	{
		if(isset($_SESSION["SESS_AUTH"]["USER_ID"]))
			return $_SESSION["SESS_AUTH"]["USER_ID"];
		else
			return null;
	}

	
	/**
	* <p>Возвращает логин текущего авторизованного пользователя (как правило вызывается с объекта $USER). Нестатический метод.</p>
	*
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* global $USER;
	* echo "[".$USER-&gt;GetID()."] (".<b>$USER-&gt;GetLogin</b>().") ".$USER-&gt;GetFullName();
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php">Поля CUser</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getid.php">CUser::GetID</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getemail.php">CUser::GetEmail</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getfullname.php">CUser::GetFullName</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getfirstname.php">CUser::GetFirstName</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getlastname.php">CUser::GetLastName</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getparam.php">CUser::GetParam</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergrouparray.php">CUser::GetUserGroupArray</a> </li>
	* <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergroupstring.php">CUser::GetUserGroupString</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getlogin.php
	* @author Bitrix
	*/
	static public function GetLogin()
	{
		return $_SESSION["SESS_AUTH"]["LOGIN"];
	}

	
	/**
	* <p>Возвращает E-Mail текущего авторизованного пользователя (как правило вызывается с объекта $USER). Данные берутся из сессии.  Нестатический метод.</p>
	*
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* global $USER;
	* echo "Ваш E-Mail: ".<b>$USER-&gt;GetEmail</b>();
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php">Поля CUser</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getid.php">CUser::GetID</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getfullname.php">CUser::GetFullName</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getfirstname.php">CUser::GetFirstName</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getlastname.php">CUser::GetLastName</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getparam.php">CUser::GetParam</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergroup.php">CUser::GetUserGroup</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergrouparray.php">CUser::GetUserGroupArray</a> </li>
	* <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergroupstring.php">CUser::GetUserGroupString</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getemail.php
	* @author Bitrix
	*/
	static public function GetEmail()
	{
		return $_SESSION["SESS_AUTH"]["EMAIL"];
	}

	
	/**
	* <p>Возвращает имя и фамилию авторизованного пользователя разделенные пробелом (как правило вызывается с объекта $USER). Нестатический метод.</p>
	*
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* global $USER;
	* echo "[".$USER-&gt;GetID()."] (".$USER-&gt;GetLogin().") ".<b>$USER-&gt;GetFullName</b>();
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php">Поля CUser</a> </li>   <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getid.php">CUser::GetID</a> </li>   <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getlogin.php">CUser::GetLogin</a> </li>   <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getemail.php">CUser::GetEmail</a> </li>   <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getfirstname.php">CUser::GetFirstName</a> </li>   <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getlastname.php">CUser::GetLastName</a> </li>   <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getparam.php">CUser::GetParam</a> </li>   <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergrouparray.php">CUser::GetUserGroupArray</a> </li>  
	* <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergroupstring.php">CUser::GetUserGroupString</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getfullname.php
	* @author Bitrix
	*/
	static public function GetFullName()
	{
		return $_SESSION["SESS_AUTH"]["NAME"];
	}

	
	/**
	* <p>Возвращает имя авторизованного пользователя (как правило вызывается с объекта $USER). Нестатический метод.</p>
	*
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* global $USER;
	* echo "[".$USER-&gt;GetID()."] (".$USER-&gt;GetLogin().") ".<b>$USER-&gt;GetFirstName</b>()." ".$USER-&gt;GetLastName();
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php">Поля CUser</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getid.php">CUser::GetID</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getlogin.php">CUser::GetLogin</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getemail.php">CUser::GetEmail</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getfullname.php">CUser::GetFullName</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getlastname.php">CUser::GetLastName</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getparam.php">CUser::GetParam</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergrouparray.php">CUser::GetUserGroupArray</a> </li>
	* <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergroupstring.php">CUser::GetUserGroupString</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getfirstname.php
	* @author Bitrix
	*/
	static public function GetFirstName()
	{
		return $_SESSION["SESS_AUTH"]["FIRST_NAME"];
	}

	
	/**
	* <p>Возвращает фамилию авторизованного пользователя (как правило вызывается с объекта $USER). Нестатический метод.</p>
	*
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* global $USER;
	* echo "[".$USER-&gt;GetID()."] (".$USER-&gt;Login().") ".$USER-&gt;GetFirstName()." ".<b>$USER-&gt;GetLastName</b>();
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php">Поля CUser</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getid.php">CUser::GetID</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getlogin.php">CUser::GetLogin</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getemail.php">CUser::GetEmail</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getfullname.php">CUser::GetFullName</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getfirstname.php">CUser::GetFirstName</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getparam.php">CUser::GetParam</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergrouparray.php">CUser::GetUserGroupArray</a> </li>
	* <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergroupstring.php">CUser::GetUserGroupString</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getlastname.php
	* @author Bitrix
	*/
	static public function GetLastName()
	{
		return $_SESSION["SESS_AUTH"]["LAST_NAME"];
	}

	static public function GetSecondName()
	{
		return $_SESSION["SESS_AUTH"]["SECOND_NAME"];
	}

	public function GetFormattedName($bUseBreaks = true, $bHTMLSpec = true)
	{
		return CUser::FormatName(CSite::GetNameFormat($bUseBreaks),
			array(
				"TITLE" => $this->GetParam("TITLE"),
				"NAME" => $this->GetFirstName(),
				"SECOND_NAME" => $this->GetSecondName(),
				"LAST_NAME" => $this->GetLastName(),
				"LOGIN" => $this->GetLogin(),
			),
			true,
			$bHTMLSpec
		);
	}

	
	/**
	* <p>Метод возвращает ID групп которым принадлежит текущий авторизованный пользователь (как правило вызывается с объекта $USER). Нестатический метод.</p>
	*
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // получим массив групп текущего пользователя
	* global $USER;
	* $arGroups = <b>$USER-&gt;GetUserGroupArray</b>();
	* echo "&lt;pre&gt;"; print_r($arGroups); echo "&lt;/pre&gt;";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/index.php">Класс CGroup</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergroupstring.php">CUser::GetUserGroupString</a>  </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergroup.php">CUser::GetUserGroup</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergrouparray.php
	* @author Bitrix
	*/
	static public function GetUserGroupArray()
	{
		if(
			!isset($_SESSION["SESS_AUTH"]["GROUPS"])
			|| !is_array($_SESSION["SESS_AUTH"]["GROUPS"])
			|| empty($_SESSION["SESS_AUTH"]["GROUPS"])
		)
			return array(2);

		//always unique and sorted, containing group ID=2
		return $_SESSION["SESS_AUTH"]["GROUPS"];
	}

	
	/**
	* <p>Метод устанавливает привязку текущего пользователя к группам <i>groups</i> (как правило вызывается для объекта $USER). Данные получаются из сессионной переменной, значение которой соответствует привязке пользователя <b>на момент авторизации</b>. Привязка к группам не сохраняется в базе данных и при следующей авторизации теряется. Для сохранения привязки в базе данных воспользуйтесь методом <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/setusergroup.php">CUser::SetUserGroup</a>. Нестатический метод.</p>
	*
	*
	* @param array $groups  Массив со значениями идентификаторов групп пользователей.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // привязка текущего пользователя дополнительно к группе c кодом 5
	* global $USER;
	* $arGroups = <b>$USER-&gt;GetUserGroupArray</b>();
	* $arGroups[] = 5;
	* <b>$USER-&gt;SetUserGroupArray</b>($arGroups);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergrouparray.php">CUser::GetUserGroupArray</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/setusergrouparray.php
	* @author Bitrix
	*/
	static public function SetUserGroupArray($arr)
	{
		$arr[] = 2;
		$arr = array_values(array_unique($arr));
		sort($arr);
		$_SESSION["SESS_AUTH"]["GROUPS"] = $arr;
	}

	
	/**
	* <p>Метод возвращает строку c перечисленными через запятую ID всех групп которым принадлежит текущий авторизованный пользователь (как правило вызывается с объекта $USER). Данные получаются из сессионной переменной, значение которой соответствует привязке пользователя <b>на момент авторизации</b>. Если пользователь не авторизован, то будет возвращён идентификатор группы "все пользователи". Нестатический метод.</p>
	*
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // получим массив групп текущего пользователя
	* global $USER;
	* $strGroups = <b>$USER-&gt;GetUserGroupString</b>();
	* echo $strGroups; // "1,2,3"
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/index.php">Класс CGroup</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergrouparray.php">CUser::GetUserGroupArray</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergroup.php">CUser::GetUserGroup</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergroupstring.php
	* @author Bitrix
	*/
	public function GetUserGroupString()
	{
		return $this->GetGroups();
	}

	public function GetGroups()
	{
		return implode(",", $this->GetUserGroupArray());
	}

	static public function RequiredHTTPAuthBasic($Realm = "Bitrix")
	{
		header("WWW-Authenticate: Basic realm=\"{$Realm}\"");
		if(stristr(php_sapi_name(), "cgi") !== false)
			header("Status: 401 Unauthorized");
		else
			header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized");

		return false;
	}

	static public function LoginByCookies()
	{
		global $USER;

		if(COption::GetOptionString("main", "store_password", "Y") == "Y")
		{
			$bLogout = isset($_REQUEST["logout"]) && (strtolower($_REQUEST["logout"]) == "yes");

			$cookie_prefix = COption::GetOptionString('main', 'cookie_name', 'BITRIX_SM');
			$cookie_login = strval($_COOKIE[$cookie_prefix.'_UIDL']);
			if($cookie_login == '')
			{
				//compatibility reasons
				$cookie_login = strval($_COOKIE[$cookie_prefix.'_LOGIN']);
			}
			$cookie_md5pass = strval($_COOKIE[$cookie_prefix.'_UIDH']);

			if($cookie_login <> '' && $cookie_md5pass <> '' && !$bLogout)
			{
				if($_SESSION["SESS_PWD_HASH_TESTED"] != md5($cookie_login."|".$cookie_md5pass))
				{
					$USER->LoginByHash($cookie_login, $cookie_md5pass);
					$_SESSION["SESS_PWD_HASH_TESTED"] = md5($cookie_login."|".$cookie_md5pass);
				}
			}
		}
	}

	
	/**
	* <p>Метод проверяет логин и специальный хеш от пароля, и если они корректные, то авторизует пользователя. Если авторизация успешная, то возвращает <b>true</b>, иначе возвращает массив с ошибкой для метода <a href="http://dev.1c-bitrix.ru/api_help/main/general/admin.section/classes/cadminmessage/showmessage.php">ShowMessage</a>. Хэш хранится не для пользователя, а для его сессии и не может быть получен средствами API. Нестатический метод.</p>
	*
	*
	* @param string $login  Логин пользователя.
	*
	* @param string $hash  Специальный хеш от пароля пользователя.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>global $USER;<br>if (!is_object($USER)) $USER = new CUser;<br>$cookie_login = ${COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_LOGIN"};<br>$cookie_md5pass = ${COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_UIDH"};<br><b>$USER-&gt;LoginByHash</b>($cookie_login, $cookie_md5pass);<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/savepasswordhash.php">SavePasswordHash</a>
	* </li>     <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getpasswordhash.php">GetPasswordHash</a>
	* </li>     <li>Событие <a
	* href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserloginbyhash.php">OnBeforeUserLoginByHash</a> </li>    
	* <li>Событие <a
	* href="http://dev.1c-bitrix.ru/api_help/main/events/onafteruserloginbyhash.php">OnAfterUserLoginByHash</a> </li>  </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/loginbyhash.php
	* @author Bitrix
	*/
	public function LoginByHash($login, $hash)
	{
		/** @global CMain $APPLICATION */
		global $DB, $APPLICATION;

		$result_message = true;
		$user_id = 0;
		$arParams = array(
			"LOGIN" => &$login,
			"HASH" => &$hash,
		);

		$APPLICATION->ResetException();
		$bOk = true;
		foreach(GetModuleEvents("main", "OnBeforeUserLoginByHash", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array(&$arParams))===false)
			{
				if($err = $APPLICATION->GetException())
					$result_message = array("MESSAGE"=>$err->GetString()."<br>", "TYPE"=>"ERROR");
				else
				{
					$APPLICATION->ThrowException("Unknown error");
					$result_message = array("MESSAGE"=>"Unknown error"."<br>", "TYPE"=>"ERROR");
				}

				$bOk = false;
				break;
			}
		}

		if($bOk && $arParams['HASH'] <> '')
		{
			$strSql =
				"SELECT U.ID, U.ACTIVE, U.STORED_HASH, U.EXTERNAL_AUTH_ID ".
				"FROM b_user U ".
				"WHERE U.LOGIN='".$DB->ForSQL($arParams['LOGIN'], 50)."' ";
			$result = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

			$bFound = false;
			$bHashFound = false;
			while(($arUser = $result->Fetch()))
			{
				$bFound = true;
				//there is no stored auth for external authorization, but domain spread auth should work
				$bExternal = ($arUser["EXTERNAL_AUTH_ID"] <> '');
				if(
					// if old method (STORED_HASH <> '') and exact match
					($arUser["STORED_HASH"] <> '' && $arUser["STORED_HASH"] == $arParams['HASH'])
					|| // or new method
					(CUser::CheckStoredHash($arUser["ID"], $arParams['HASH'], $bExternal))
				)
				{
					$bHashFound = true;
					if($arUser["ACTIVE"] == "Y")
					{
						$_SESSION["SESS_AUTH"]["SESSION_HASH"] = $arParams['HASH'];
						$this->bLoginByHash = true;
						$this->Authorize($arUser["ID"], !$bExternal);
					}
					else
					{
						$APPLICATION->ThrowException(GetMessage("LOGIN_BLOCK"));
						$result_message = array("MESSAGE"=>GetMessage("LOGIN_BLOCK")."<br>", "TYPE"=>"ERROR");
					}
					break;
				}
				else
				{
					//Delete invalid stored auth cookie
					$APPLICATION->set_cookie("UIDH", "", 0, '/', false, false, COption::GetOptionString("main", "auth_multisite", "N")=="Y", false, true);
				}
			}
			if(!$bFound)
			{
				$APPLICATION->ThrowException(GetMessage("WRONG_LOGIN"));
				$result_message = array("MESSAGE"=>GetMessage("WRONG_LOGIN")."<br>", "TYPE"=>"ERROR");
			}
			elseif(!$bHashFound)
			{
				$APPLICATION->ThrowException(GetMessage("USER_WRONG_HASH"));
				$result_message = array("MESSAGE"=>GetMessage("USER_WRONG_HASH")."<br>", "TYPE"=>"ERROR");
			}
		}

		$arParams["USER_ID"] = &$user_id;
		$arParams["RESULT_MESSAGE"] = &$result_message;

		foreach (GetModuleEvents("main", "OnAfterUserLoginByHash", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arParams));

		if(($result_message !== true) && (COption::GetOptionString("main", "event_log_login_fail", "N") === "Y"))
			CEventLog::Log("SECURITY", "USER_LOGINBYHASH", "main", $login, $result_message["MESSAGE"]);

		return $arParams["RESULT_MESSAGE"];
	}

	public function LoginByHttpAuth()
	{
		$arAuth = CHTTP::ParseAuthRequest();

		foreach(GetModuleEvents("main", "onBeforeUserLoginByHttpAuth", true) as $arEvent)
		{
			$res = ExecuteModuleEventEx($arEvent, array(&$arAuth));
			if($res !== null)
			{
				return $res;
			}
		}

		if(isset($arAuth["basic"]) && $arAuth["basic"]["username"] <> '' && $arAuth["basic"]["password"] <> '')
		{
			// Authorize user, if it is http basic authorization, with no remembering
			if(!$this->IsAuthorized() || $this->GetLogin() <> $arAuth["basic"]["username"])
			{
				return $this->Login($arAuth["basic"]["username"], $arAuth["basic"]["password"], "N");
			}
		}
		elseif(isset($arAuth["digest"]) && $arAuth["digest"]["username"] <> '' && COption::GetOptionString('main', 'use_digest_auth', 'N') == 'Y')
		{
			// Authorize user by http digest authorization
			if(!$this->IsAuthorized() || $this->GetLogin() <> $arAuth["digest"]["username"])
			{
				return $this->LoginByDigest($arAuth["digest"]);
			}
		}

		return null;
	}

	public function LoginByDigest($arDigest)
	{
		//array("username"=>"", "nonce"=>"", "uri"=>"", "response"=>"")
		/** @global CMain $APPLICATION */
		global $DB, $APPLICATION;

		$APPLICATION->ResetException();

		$strSql =
			"SELECT U.ID, U.PASSWORD, UD.DIGEST_HA1, U.EXTERNAL_AUTH_ID ".
			"FROM b_user U LEFT JOIN b_user_digest UD ON UD.USER_ID=U.ID ".
			"WHERE U.LOGIN='".$DB->ForSQL($arDigest["username"])."' ";
		$res = $DB->Query($strSql);

		if($arUser = $res->Fetch())
		{
			$method = (isset($_SERVER['REDIRECT_REQUEST_METHOD']) ? $_SERVER['REDIRECT_REQUEST_METHOD'] : $_SERVER['REQUEST_METHOD']);
			$HA2 = md5($method.':'.$arDigest['uri']);

			if($arUser["EXTERNAL_AUTH_ID"] == '' && $arUser["DIGEST_HA1"] <> '')
			{
				//digest is for internal authentication only
				$_SESSION["BX_HTTP_DIGEST_ABSENT"] = false;

				$HA1 = $arUser["DIGEST_HA1"];
				$valid_response = md5($HA1.':'.$arDigest['nonce'].':'.$HA2);

				if($arDigest["response"] === $valid_response)
				{
					//regular user password
					return $this->Login($arDigest["username"], $arUser["PASSWORD"], "N", "N");
				}
			}

			//check for an application password, including external users
			if(($appPassword = \Bitrix\Main\Authentication\ApplicationPasswordTable::findDigestPassword($arUser["ID"], $arDigest)) !== false)
			{
				return $this->Login($arDigest["username"], $appPassword["PASSWORD"], "N", "N");
			}

			if($arUser["DIGEST_HA1"] == '')
			{
				//this indicates that we still have no user digest hash
				$_SESSION["BX_HTTP_DIGEST_ABSENT"] = true;
			}
		}

		$APPLICATION->ThrowException(GetMessage("USER_AUTH_DIGEST_ERR"));
		return array("MESSAGE"=>GetMessage("USER_AUTH_DIGEST_ERR")."<br>", "TYPE"=>"ERROR");
	}

	public static function UpdateDigest($ID, $pass)
	{
		global $DB;
		$ID = intval($ID);

		$res = $DB->Query("
			SELECT U.LOGIN, UD.DIGEST_HA1
			FROM b_user U LEFT JOIN b_user_digest UD on UD.USER_ID=U.ID
			WHERE U.ID=".$ID
		);
		if($arRes = $res->Fetch())
		{
			if(defined('BX_HTTP_AUTH_REALM'))
				$realm = BX_HTTP_AUTH_REALM;
			else
				$realm = "Bitrix Site Manager";

			$digest = md5($arRes["LOGIN"].':'.$realm.':'.$pass);

			if($arRes["DIGEST_HA1"] == '')
			{
				//new digest
				$DB->Query("insert into b_user_digest (user_id, digest_ha1) values('".$ID."', '".$DB->ForSQL($digest)."')");
			}
			else
			{
				//update digest (login, password or realm were changed)
				if($arRes["DIGEST_HA1"] !== $digest)
					$DB->Query("update b_user_digest set digest_ha1='".$DB->ForSQL($digest)."' where user_id=".$ID);
			}
		}
	}

	public function LoginHitByHash()
	{
		/** @global CMain $APPLICATION */
		global $DB, $APPLICATION;

		$hash = trim($_REQUEST["bx_hit_hash"]);
		if ($hash == '')
			return false;

		$APPLICATION->ResetException();

		$strSql =
			"SELECT UH.USER_ID AS USER_ID ".
			"FROM b_user_hit_auth UH ".
			"INNER JOIN b_user U ON U.ID = UH.USER_ID AND U.ACTIVE ='Y' ".
			"WHERE UH.HASH = '".$DB->ForSQL($hash, 32)."' ".
			"	AND '".$DB->ForSqlLike($APPLICATION->GetCurPageParam("", array(), true), 500)."' LIKE ".$DB->Concat("UH.URL", "'%'");

		if(!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
			$strSql .= " AND UH.SITE_ID = '".SITE_ID."'";

		$result = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		if($arUser = $result->Fetch())
		{
			setSessionExpired(true);
			$this->Authorize($arUser["USER_ID"], false);

			$DB->Query("UPDATE b_user_hit_auth SET TIMESTAMP_X = ".$DB->GetNowFunction()." WHERE HASH='".$DB->ForSQL($hash, 32)."'");
			return true;
		}
		else
			return false;
	}

	public static function AddHitAuthHash($url, $user_id = false, $site_id = false)
	{
		global $USER, $DB;

		if ($url == '')
			return false;

		if (!$user_id)
			$user_id = $USER->GetID();

		if (!$site_id && (!defined("ADMIN_SECTION") || ADMIN_SECTION !== true))
			$site_id = SITE_ID;

		$hash = false;

		if ($user_id)
		{
			$hash = md5(uniqid(rand(), true));
			$arFields = array(
				'USER_ID' => $user_id,
				'URL' => $DB->ForSqlLike(trim($url), 500),
				'HASH' => $hash,
				'SITE_ID' => $DB->ForSQL(trim($site_id), 2),
				'~TIMESTAMP_X'=>$DB->CurrentTimeFunction()
			);
			$DB->Add("b_user_hit_auth", $arFields);
		}

		return $hash;
	}

	public static function GetHitAuthHash($url_mask, $userID = false)
	{
		global $USER, $DB;

		$url_mask = trim($url_mask);
		if ($url_mask == '')
			return false;

		if (!$userID)
		{
			if (!$USER->IsAuthorized())
				return false;
			else
				$userID = $USER->GetID();
		}

		$strSql =
			"SELECT ID, HASH ".
			"FROM b_user_hit_auth ".
			"WHERE URL = '".$DB->ForSqlLike($url_mask, 500)."' AND USER_ID = ".intval($userID);

		$result = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		if($arTmp = $result->Fetch())
			return $arTmp["HASH"];
		else
			return false;
	}

	public static function CleanUpHitAuthAgent()
	{
		global $DB;
		$cleanup_days = COption::GetOptionInt("main", "hit_auth_cleanup_days", 30);
		if($cleanup_days > 0)
		{
			$arDate = localtime(time());
			$date = mktime(0, 0, 0, $arDate[4]+1, $arDate[3]-$cleanup_days, 1900+$arDate[5]);
			$DB->Query("DELETE FROM b_user_hit_auth WHERE TIMESTAMP_X <= ".$DB->CharToDateFunction(ConvertTimeStamp($date, "FULL")));

		}
		return "CUser::CleanUpHitAuthAgent();";
	}

	/**
	 * Performs the user authorization:
	 *    fills session parameters;
	 *    remembers auth;
	 *    spreads auth through sites
	 */
	
	/**
	* <p>Метод непосредственно осуществляет процесс авторизации пользователя. Инициализирует необходимые сессионные переменные и переменные объекта класса CUser. Если авторизация успешна, то возвращает "true", иначе - "false". Нестатический метод.</p>
	*
	*
	* @param int $user_id  ID пользователя.
	*
	* @param bool $Save = false Флаг указывающий на необходимость запоминания авторизации
	* пользователя. Если равен true, то будет сгененрирован случайный
	* хэш, выставлена кука с его значением и этот хэш будет сохранен в
	* базе данных для последующей авторизации методом <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/loginbyhash.php">CUser::LoginByHash</a>.
	*
	* @param bool $Update = true Необязательный. По умолчанию "true".
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>// пример динамического добавления текущего пользователя в группу <br>// и его дальнейшая переавторизация<br>global $USER;<br>$arrGroups_new = array(3,4); // в какие группы хотим добавить<br>$arrGroups_old = $USER-&gt;GetUserGroupArray(); // получим текущие группы<br>$arrGroups = array_unique(array_merge($arrGroups_old, $arrGroups_new)); // объединим два массива и удалим дубли<br>$USER-&gt;Update($USER-&gt;GetID(), array("GROUP_ID" =&gt; $arrGroups)); // обновим профайл пользователя в базе<br><b>$USER-&gt;Authorize</b>($USER-&gt;GetID()); // авторизуем<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/login.php">CUser::Login</a> </li>   <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/loginbyhash.php">CUser::LoginByHash</a> </li>   <li><a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/isauthorized.php">CUser::IsAuthorized</a></li>   <li><a
	* href="http://dev.1c-bitrix.ru/api_help/main/events/onafteruserauthorize.php">Событие
	* "OnAfterUserAuthorize"</a></li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/authorize.php
	* @author Bitrix
	*/
	public function Authorize($id, $bSave = false, $bUpdate = true, $applicationId = null)
	{
		/** @global CMain $APPLICATION */
		global $DB, $APPLICATION;

		unset($_SESSION["SESS_OPERATIONS"]);
		unset($_SESSION["MODULE_PERMISSIONS"]);
		$_SESSION["BX_LOGIN_NEED_CAPTCHA"] = false;

		$strSql =
			"SELECT U.* ".
			"FROM b_user U  ".
			"WHERE U.ID='".intval($id)."' ";
		$result = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		if($arUser = $result->Fetch())
		{
			$this->justAuthorized = true;

			$_SESSION["SESS_AUTH"]["AUTHORIZED"] = "Y";
			$_SESSION["SESS_AUTH"]["USER_ID"] = $arUser["ID"];
			$_SESSION["SESS_AUTH"]["LOGIN"] = $arUser["LOGIN"];
			$_SESSION["SESS_AUTH"]["LOGIN_COOKIES"] = $arUser["LOGIN"];
			$_SESSION["SESS_AUTH"]["EMAIL"] = $arUser["EMAIL"];
			$_SESSION["SESS_AUTH"]["PASSWORD_HASH"] = $arUser["PASSWORD"];
			$_SESSION["SESS_AUTH"]["TITLE"] = $arUser["TITLE"];
			$_SESSION["SESS_AUTH"]["NAME"] = $arUser["NAME"].($arUser["NAME"] == '' || $arUser["LAST_NAME"] == ''? "":" ").$arUser["LAST_NAME"];
			$_SESSION["SESS_AUTH"]["FIRST_NAME"] = $arUser["NAME"];
			$_SESSION["SESS_AUTH"]["SECOND_NAME"] = $arUser["SECOND_NAME"];
			$_SESSION["SESS_AUTH"]["LAST_NAME"] = $arUser["LAST_NAME"];
			$_SESSION["SESS_AUTH"]["PERSONAL_PHOTO"] = $arUser["PERSONAL_PHOTO"];
			$_SESSION["SESS_AUTH"]["PERSONAL_GENDER"] = $arUser["PERSONAL_GENDER"];
			$_SESSION["SESS_AUTH"]["ADMIN"] = false;
			$_SESSION["SESS_AUTH"]["CONTROLLER_ADMIN"] = false;
			$_SESSION["SESS_AUTH"]["POLICY"] = CUser::GetGroupPolicy($arUser["ID"]);
			$_SESSION["SESS_AUTH"]["AUTO_TIME_ZONE"] = trim($arUser["AUTO_TIME_ZONE"]);
			$_SESSION["SESS_AUTH"]["TIME_ZONE"] = $arUser["TIME_ZONE"];
			$_SESSION["SESS_AUTH"]["APPLICATION_ID"] = $applicationId;
			$_SESSION["SESS_AUTH"]["BX_USER_ID"] = $arUser["BX_USER_ID"];

			// groups
			$_SESSION["SESS_AUTH"]["GROUPS"] = Main\UserTable::getUserGroupIds($arUser["ID"]);

			foreach ($_SESSION["SESS_AUTH"]["GROUPS"] as $groupId)
			{
				if ($groupId == 1)
				{
					$_SESSION["SESS_AUTH"]["ADMIN"] = true;
					break;
				}
			}

			//sometimes we don't need to update db (REST)
			if($bUpdate)
			{
				$tz = '';
				if(CTimeZone::Enabled())
				{
					if(!CTimeZone::IsAutoTimeZone(trim($arUser["AUTO_TIME_ZONE"])) || CTimeZone::GetCookieValue() !== null)
					{
						$tz = ', TIME_ZONE_OFFSET = '.CTimeZone::GetOffset();
					}
				}

				$bxUid = '';
				if (!empty($_COOKIE['BX_USER_ID']) && preg_match('/^[0-9a-f]{32}$/', $_COOKIE['BX_USER_ID']))
				{
					if ($_COOKIE['BX_USER_ID'] != $arUser['BX_USER_ID'])
					{
						// save new bxuid value
						$bxUid = ", BX_USER_ID = '".$_COOKIE['BX_USER_ID']."'";

						$arUser['BX_USER_ID'] = $_COOKIE['BX_USER_ID'];
						$_SESSION["SESS_AUTH"]["BX_USER_ID"] = $_COOKIE['BX_USER_ID'];
					}
				}

				$DB->Query("
					UPDATE b_user SET
						STORED_HASH = NULL,
						LAST_LOGIN = ".$DB->GetNowFunction().",
						TIMESTAMP_X = TIMESTAMP_X,
						LOGIN_ATTEMPTS = 0
						".$tz."
						".$bxUid."
					WHERE
						ID=".$arUser["ID"]
				);

				if($applicationId === null && ($bSave || COption::GetOptionString("main", "auth_multisite", "N") == "Y"))
				{
					$hash = $this->GetSessionHash();
					$secure = (COption::GetOptionString("main", "use_secure_password_cookies", "N")=="Y" && CMain::IsHTTPS());

					if($bSave)
					{
						$period = time()+60*60*24*30*60;
						$spread = BX_SPREAD_SITES | BX_SPREAD_DOMAIN;
					}
					else
					{
						$period = 0;
						$spread = BX_SPREAD_SITES;
					}
					$APPLICATION->set_cookie("UIDH", $hash, $period, '/', false, $secure, $spread, false, true);
					$APPLICATION->set_cookie("UIDL", $arUser["LOGIN"], $period, '/', false, $secure, $spread, false, true);

					$stored_id = CUser::CheckStoredHash($arUser["ID"], $hash);
					if($stored_id)
					{
						$DB->Query(
							"UPDATE b_user_stored_auth SET
								LAST_AUTH=".$DB->CurrentTimeFunction().",
								".($this->bLoginByHash?"":"TEMP_HASH='".($bSave?"N":"Y")."', ")."
								IP_ADDR='".sprintf("%u", ip2long($_SERVER["REMOTE_ADDR"]))."'
							WHERE ID=".$stored_id
						);
					}
					else
					{
						$arFields = array(
							'USER_ID'=>$arUser["ID"],
							'~DATE_REG'=>$DB->CurrentTimeFunction(),
							'~LAST_AUTH'=>$DB->CurrentTimeFunction(),
							'TEMP_HASH'=>($bSave?"N":"Y"),
							'~IP_ADDR'=>sprintf("%u", ip2long($_SERVER["REMOTE_ADDR"])),
							'STORED_HASH'=>$hash
						);
						$stored_id = $DB->Add("b_user_stored_auth", $arFields);
					}
					$_SESSION["SESS_AUTH"]["STORED_AUTH_ID"] = $stored_id;
				}
			}

			$this->admin = null;

			$arParams = array(
				"user_fields" => $arUser,
				"save" => $bSave,
				"update" => $bUpdate,
				"applicationId" => $applicationId,
			);

			foreach (GetModuleEvents("main", "OnAfterUserAuthorize", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($arParams));

			foreach (GetModuleEvents("main", "OnUserLogin", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($_SESSION["SESS_AUTH"]["USER_ID"]));

			if(COption::GetOptionString("main", "event_log_login_success", "N") === "Y")
				CEventLog::Log("SECURITY", "USER_AUTHORIZE", "main", $arUser["ID"], $applicationId);

			CHTMLPagesCache::OnUserLogin();

			return true;
		}
		return false;
	}

	static public function GetSessionHash()
	{
		if($_SESSION["SESS_AUTH"]["SESSION_HASH"] == '')
		{
			$_SESSION["SESS_AUTH"]["SESSION_HASH"] = md5(CMain::GetServerUniqID().uniqid("", true));
		}
		return $_SESSION["SESS_AUTH"]["SESSION_HASH"];
	}

	/** @deprecated */
	
	/**
	* <p>Возвращает специальный хеш от пароля пользователя который может быть использован в методах <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/loginbyhash.php">LoginByHash</a> и <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/savepasswordhash.php">SavePasswordHash</a>.  Нестатический метод.</p>
	*
	*
	* @param string $PASSWORD_HASH  Хеш (MD5) от реального пароля пользователя. Для текущего
	* авторизованного пользователя MD5 от реального пароля можно
	* получить с помощью метода <nobr><a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getparam.php">$USER-&gt;GetParam("PASSWORD_HASH")</a></nobr>.
	* Для произвольного пользователя MD5 от пароля можно получить с
	* помощью метода <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getbyid.php">CUser::GetByID</a> (поле "PASSWORD").
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* function SetCurrentUserAuthCookie()
	* {
	*   global $USER;
	*   $hash = <b>CUser::GetPasswordHash</b>($USER-&gt;GetParam("PASSWORD_HASH"));
	*   $name = COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_UIDH";
	*   @setcookie($name, $hash, time()+60*60*24*30*60, "/"); 
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/loginbyhash.php">CUser::LoginByHash</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/savepasswordhash.php">CUser::SavePasswordHash</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getpasswordhash.php
	* @author Bitrix
	* @deprecated
	*/
	static public function GetPasswordHash($PASSWORD_HASH)
	{
		$add = COption::GetOptionString("main", "pwdhashadd", "");
		if($add == '')
		{
			$add = md5(uniqid(rand(), true));
			COption::SetOptionString("main", "pwdhashadd", $add);
		}

		return md5($add.$PASSWORD_HASH);
	}

	/** @deprecated */
	
	/**
	* <p>Сохраняет специальный хеш в куках пользователя в целях дальнейшей автоматической авторизации. Для разных сайтов на базе "Битрикс: Управление сайтом", метод всегда сохраняет свой уникальный хеш от одного и того же пароля. Таким образом достигается невозможность использовать одно и тоже значение для авторизации на различных сайтах. Нестатический метод.</p>
	*
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* global $USER;
	* if ($USER-&gt;IsAuthorized()) <b>$USER-&gt;SavePasswordHash</b>();
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/loginbyhash.php">CUser::LoginByHash</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getpasswordhash.php">CUser::GetPasswordHash</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/savepasswordhash.php
	* @author Bitrix
	* @deprecated
	*/
	public function SavePasswordHash()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$hash = $this->GetSessionHash();
		$time = time()+60*60*24*30*60;
		$secure = 0;
		if(COption::GetOptionString("main", "use_secure_password_cookies", "N")=="Y" && CMain::IsHTTPS())
			$secure=1;

		$APPLICATION->set_cookie("UIDH", $hash, $time, '/', false, $secure, COption::GetOptionString("main", "auth_multisite", "N")=="Y");
	}

	/**
	 * Authenticates the user and then authorizes him
	 */
	
	/**
	* <p>Метод проверяет логин и пароль и если они корректные, то авторизует пользователя. Если авторизация успешная, то возвращает "true", иначе если логин и пароль некорректные, то возвращает массив с ошибкой для функции <a href="http://dev.1c-bitrix.ru/api_help/main/functions/other/showmessage.php">ShowMessage</a>. Если было превышено количество попыток подключения метод просто не будет авторизовывать пользователя с ошибкой "Неправильный логин или пароль". Нестатический метод.</p>
	*
	*
	* @param string $login  Логин пользователя.
	*
	* @param string $password  Пароль. Если параметр <i>convert_password_to_md5</i> = "Y", то в данном параметре
	* необходимо передавать оригинальный пароль, в противном случае
	* необходимо передавать md5 от оригинального пароля.
	*
	* @param string $remember = "N" Если значение равно "Y", то авторизация пользователя будет
	* сохранена в куках (при следующем заходе посетитель будет
	* автоматически авторизован), в противном случае - авторизация не
	* будет сохранена в куках. В куках <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/savepasswordhash.php">сохраняется</a>
	* специальный хеш получаемый с помощью  <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getpasswordhash.php">CUser::GetPasswordHash</a>. Затем
	* когда посетитель снова приходит на сайт, система его
	* автоматически авторизует используя  <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/loginbyhash.php">CUser::LoginByHash</a><br>
	* Необязательный. По умолчанию "N".
	*
	* @param string $password_original = "Y" Если значение равно "Y", то это означает что <i>password</i> ещё не
	* сконвертирован в MD5 (т.е. в параметре <i>password</i> передается реальный
	* пароль вводимый пользователем с клавиатуры), если значение равно
	* "N", то это означает что <i>password</i> уже сконвертирован в MD5.<br>Для
	* текущего авторизованного пользователя MD5 от реального пароля
	* можно получить с помощью метода <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getparam.php">$USER-&gt;GetParam("PASSWORD_HASH")</a>.
	* Для произвольного пользователя MD5 от пароля можно получить с
	* помощью <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getbyid.php">CUser::GetByID</a> (поле
	* "PASSWORD").  	<br>Необязательный. По умолчанию "Y". До версии 4.0.6 назывался
	* <i>pass2md5</i>.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* global $USER;
	* if (!is_object($USER)) $USER = new CUser;
	* $arAuthResult = <b>$USER-&gt;Login</b>("admin", "123456", "Y");
	* $APPLICATION-&gt;arAuthResult = $arAuthResult;
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/authorize.php">CUser::Authorize</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/isauthorized.php">CUser::IsAuthorized</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/loginbyhash.php">CUser::LoginByHash</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/logout.php">CUser::Logout</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserlogin.php">Событие "OnBeforeUserLogin"</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/events/onafteruserlogin.php">Событие "OnAfterUserLogin"</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/login.php
	* @author Bitrix
	*/
	public function Login($login, $password, $remember="N", $password_original="Y")
	{
		/** @global CMain $APPLICATION */
		global $DB, $APPLICATION;

		$result_message = true;
		$user_id = 0;
		$applicationId = null;
		$applicationPassId = null;

		$arParams = array(
			"LOGIN" => &$login,
			"PASSWORD" => &$password,
			"REMEMBER" => &$remember,
			"PASSWORD_ORIGINAL" => &$password_original,
		);

		unset($_SESSION["SESS_OPERATIONS"]);
		unset($_SESSION["MODULE_PERMISSIONS"]);
		$_SESSION["BX_LOGIN_NEED_CAPTCHA"] = false;

		$bOk = true;
		$APPLICATION->ResetException();
		foreach(GetModuleEvents("main", "OnBeforeUserLogin", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array(&$arParams))===false)
			{
				if($err = $APPLICATION->GetException())
				{
					$result_message = array("MESSAGE"=>$err->GetString()."<br>", "TYPE"=>"ERROR");
				}
				else
				{
					$APPLICATION->ThrowException("Unknown login error");
					$result_message = array("MESSAGE"=>"Unknown login error"."<br>", "TYPE"=>"ERROR");
				}

				$bOk = false;
				break;
			}
		}

		if($bOk)
		{
			//external authentication
			foreach(GetModuleEvents("main", "OnUserLoginExternal", true) as $arEvent)
			{
				$user_id = ExecuteModuleEventEx($arEvent, array(&$arParams));
				if($user_id > 0)
				{
					break;
				}
			}

			if($user_id <= 0)
			{
				//internal authentication OR application password for external user

				$foundUser = false;

				$strSql =
					"SELECT U.ID, U.LOGIN, U.ACTIVE, U.PASSWORD, U.LOGIN_ATTEMPTS, U.CONFIRM_CODE, U.EMAIL ".
					"FROM b_user U  ".
					"WHERE U.LOGIN='".$DB->ForSQL($arParams["LOGIN"])."' ".
					"	AND (EXTERNAL_AUTH_ID IS NULL OR EXTERNAL_AUTH_ID='') ";

				$result = $DB->Query($strSql);

				if(($arUser = $result->Fetch()))
				{
					//internal authentication by login and password

					$foundUser = true;

					if(strlen($arUser["PASSWORD"]) > 32)
					{
						$salt = substr($arUser["PASSWORD"], 0, strlen($arUser["PASSWORD"]) - 32);
						$db_password = substr($arUser["PASSWORD"], -32);
					}
					else
					{
						$salt = "";
						$db_password = $arUser["PASSWORD"];
					}

					$user_password_no_otp = "";
					if($arParams["PASSWORD_ORIGINAL"] == "Y")
					{
						$user_password =  md5($salt.$arParams["PASSWORD"]);
						if($arParams["OTP"] <> '')
						{
							$user_password_no_otp =  md5($salt.substr($arParams["PASSWORD"], 0, -6));
						}
					}
					else
					{
						if(strlen($arParams["PASSWORD"]) > 32)
							$user_password = substr($arParams["PASSWORD"], -32);
						else
							$user_password = $arParams["PASSWORD"];
					}

					$passwordCorrect = ($db_password === $user_password || ($arParams["OTP"] <> '' && $db_password === $user_password_no_otp));

					if($db_password === $user_password)
					{
						//this password has no added otp for sure
						$arParams["OTP"] = '';
					}

					if(!$passwordCorrect)
					{
						//let's try to find application password
						if(($appPassword = ApplicationPasswordTable::findPassword($arUser["ID"], $arParams["PASSWORD"], ($arParams["PASSWORD_ORIGINAL"] == "Y"))) !== false)
						{
							$passwordCorrect = true;
							$applicationId = $appPassword["APPLICATION_ID"];
							$applicationPassId = $appPassword["ID"];
						}
					}

					$arPolicy = CUser::GetGroupPolicy($arUser["ID"]);
					$pol_login_attempts = intval($arPolicy["LOGIN_ATTEMPTS"]);
					$usr_login_attempts = intval($arUser["LOGIN_ATTEMPTS"])+1;
					if($pol_login_attempts > 0 && $usr_login_attempts > $pol_login_attempts)
					{
						$_SESSION["BX_LOGIN_NEED_CAPTCHA"] = true;
						if(!$APPLICATION->CaptchaCheckCode($_REQUEST["captcha_word"], $_REQUEST["captcha_sid"]))
						{
							$passwordCorrect = false;
						}
					}

					if($passwordCorrect)
					{
						if($salt == '' && $arParams["PASSWORD_ORIGINAL"] == "Y" && $applicationId === null)
						{
							$salt = randString(8, array(
								"abcdefghijklnmopqrstuvwxyz",
								"ABCDEFGHIJKLNMOPQRSTUVWXYZ",
								"0123456789",
								",.<>/?;:[]{}\\|~!@#\$%^&*()-_+=",
							));
							$new_password = $salt.md5($salt.$arParams["PASSWORD"]);
							$DB->Query("UPDATE b_user SET PASSWORD='".$DB->ForSQL($new_password)."', TIMESTAMP_X = TIMESTAMP_X WHERE ID = ".intval($arUser["ID"]));
						}

						if($arUser["ACTIVE"] == "Y")
						{
							$user_id = $arUser["ID"];

							//update digest hash for http digest authorization
							if($arParams["PASSWORD_ORIGINAL"] == "Y" && $applicationId === null && COption::GetOptionString('main', 'use_digest_auth', 'N') == 'Y')
							{
								CUser::UpdateDigest($arUser["ID"], $arParams["PASSWORD"]);
							}
						}
						elseif($arUser["CONFIRM_CODE"] <> '')
						{
							//unconfirmed registration
							$message = GetMessage("MAIN_LOGIN_EMAIL_CONFIRM", array("#EMAIL#" => $arUser["EMAIL"]));
							$APPLICATION->ThrowException($message);
							$result_message = array("MESSAGE"=>$message."<br>", "TYPE"=>"ERROR");
						}
						else
						{
							$APPLICATION->ThrowException(GetMessage("LOGIN_BLOCK"));
							$result_message = array("MESSAGE"=>GetMessage("LOGIN_BLOCK")."<br>", "TYPE"=>"ERROR");
						}
					}
					else
					{
						$DB->Query("UPDATE b_user SET LOGIN_ATTEMPTS = ".$usr_login_attempts.", TIMESTAMP_X = TIMESTAMP_X WHERE ID = ".intval($arUser["ID"]));
						$APPLICATION->ThrowException(GetMessage("WRONG_LOGIN"));
						$result_message = array("MESSAGE"=>GetMessage("WRONG_LOGIN")."<br>", "TYPE"=>"ERROR", "ERROR_TYPE" => "LOGIN");
					}
				}
				else
				{
					//no user found by login - try to find an external user
					foreach(GetModuleEvents("main", "OnFindExternalUser", true) as $arEvent)
					{
						if(($external_user_id = intval(ExecuteModuleEventEx($arEvent, array($arParams["LOGIN"])))) > 0)
						{
							//external user authentication
							//let's try to find application password for the external user
							if(($appPassword = ApplicationPasswordTable::findPassword($external_user_id, $arParams["PASSWORD"], ($arParams["PASSWORD_ORIGINAL"] == "Y"))) !== false)
							{
								//bingo, the user has the application password
								$foundUser = true;
								$user_id = $external_user_id;
								$applicationId = $appPassword["APPLICATION_ID"];
								$applicationPassId = $appPassword["ID"];
							}
							break;
						}
					}
				}

				if(!$foundUser)
				{
					$APPLICATION->ThrowException(GetMessage("WRONG_LOGIN"));
					$result_message = array("MESSAGE"=>GetMessage("WRONG_LOGIN")."<br>", "TYPE"=>"ERROR", "ERROR_TYPE" => "LOGIN");
				}
			}
		}

		// All except Admin
		if ($user_id > 1 && $arParams["CONTROLLER_ADMIN"] !== "Y")
		{
			$limitUsersCount = intval(COption::GetOptionInt("main", "PARAM_MAX_USERS", 0));
			if ($limitUsersCount > 0)
			{
				$by = "ID";
				$order = "ASC";
				$arFilter = array(
					"LAST_LOGIN_1" => ConvertTimeStamp(),
				);
				//Intranet users only
				if (IsModuleInstalled("intranet"))
					$arFilter["!=UF_DEPARTMENT"] = false;

				$rsUsers = CUser::GetList($by, $order, $arFilter, array(
					"FIELDS" => array("ID", "LOGIN"),
				));

				while ( $user = $rsUsers->fetch())
				{
					if ($user["ID"] == $user_id)
					{
						$limitUsersCount = 1;
						break;
					}
					$limitUsersCount--;
				}

				if ($limitUsersCount < 0)
				{
					$user_id = 0;
					$APPLICATION->ThrowException(GetMessage("LIMIT_USERS_COUNT"));
					$result_message = array(
						"MESSAGE" => GetMessage("LIMIT_USERS_COUNT")."<br>",
						"TYPE" => "ERROR",
					);
				}
			}
		}

		$arParams["USER_ID"] = $user_id;

		$doAuthorize = true;

		if($user_id > 0)
		{
			if($applicationId === null && CModule::IncludeModule("security"))
			{
				/*
				MFA can allow or disallow authorization.
				Allowed if:
				- OTP is not active for the user;
				- correct "OTP" in the $arParams (filled by the OnBeforeUserLogin event handler).
				Disallowed if:
				- OTP is not provided;
				- OTP is not correct.
				When authorization is disallowed the OTP form will be shown on the next hit.
				Note: there is no MFA check for an application password.
				*/

				$arParams["CAPTCHA_WORD"] = $_REQUEST["captcha_word"];
				$arParams["CAPTCHA_SID"] = $_REQUEST["captcha_sid"];

				$doAuthorize = \Bitrix\Security\Mfa\Otp::verifyUser($arParams);
			}

			if($doAuthorize)
			{
				$this->Authorize($user_id, ($arParams["REMEMBER"] == "Y"), true, $applicationId);

				if($applicationPassId !== null)
				{
					//update usage statistics for the application
					Main\Authentication\ApplicationPasswordTable::update($applicationPassId, array(
						'DATE_LOGIN' => new Main\Type\DateTime(),
						'LAST_IP' => $_SERVER["REMOTE_ADDR"],
					));
				}
			}
			else
			{
				$result_message = false;
			}

			if($applicationId === null && $arParams["LOGIN"] <> '')
			{
				//the cookie is for authentication forms mostly, does not make sense for applications
				$APPLICATION->set_cookie("LOGIN", $arParams["LOGIN"], time()+60*60*24*30*60, '/', false, false, COption::GetOptionString("main", "auth_multisite", "N")=="Y");
			}
		}

		$arParams["RESULT_MESSAGE"] = $result_message;

		$APPLICATION->ResetException();
		foreach(GetModuleEvents("main", "OnAfterUserLogin", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arParams));

		if($doAuthorize == true && $result_message !== true && (COption::GetOptionString("main", "event_log_login_fail", "N") === "Y"))
			CEventLog::Log("SECURITY", "USER_LOGIN", "main", $login, $result_message["MESSAGE"]);

		return $arParams["RESULT_MESSAGE"];
	}

	public function LoginByOtp($otp, $remember_otp = "N", $captcha_word = "", $captcha_sid = "")
	{
		if(!CModule::IncludeModule("security") || !\Bitrix\Security\Mfa\Otp::isOtpRequired())
		{
			return array("MESSAGE" => GetMessage("USER_LOGIN_OTP_ERROR")."<br>", "TYPE" => "ERROR");
		}

		$userParams = \Bitrix\Security\Mfa\Otp::getDeferredParams();

		$userParams["OTP"] = $otp;
		$userParams["OTP_REMEMBER"] = ($remember_otp === "Y");
		$userParams["CAPTCHA_WORD"] = $captcha_word;
		$userParams["CAPTCHA_SID"] = $captcha_sid;

		if(!\Bitrix\Security\Mfa\Otp::verifyUser($userParams))
		{
			return array("MESSAGE" => GetMessage("USER_LOGIN_OTP_INCORRECT")."<br>", "TYPE" => "ERROR");
		}

		$this->Authorize($userParams["USER_ID"], ($userParams["REMEMBER"] == "Y"));
		return true;
	}

	public function AuthorizeWithOtp($user_id)
	{
		$doAuthorize = true;

		if(CModule::IncludeModule("security"))
		{
			/*
			MFA can allow or disallow authorization.
			Allowed only if:
			- OTP is not active for the user;
			When authorization is disallowed the OTP form will be shown on the next hit.
			*/
			$doAuthorize = \Bitrix\Security\Mfa\Otp::verifyUser(array("USER_ID" => $user_id));
		}

		if($doAuthorize)
		{
			return $this->Authorize($user_id);
		}

		return false;
	}

	
	/**
	* <p>Изменяет пароль пользователя, затем вызывает на исполнение метод <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/senduserinfo.php">CUser::SendUserInfo</a>, которая в свою очередь отсылает почтовое сообщение по шаблону типа USER_INFO. Возвращает массив с сообщением о результате выполнения (массив может быть обработан методом <a href="http://dev.1c-bitrix.ru/api_help/main/functions/other/showmessage.php">ShowMessage</a>). Нестатический метод.</p>
	*
	*
	* @param string $login  Логин пользователя.
	*
	* @param string $checkword  Контрольная строка для смены пароля.
	*
	* @param string $password  Новый пароль.
	*
	* @param string $CONFIRM_PASSWORD  Подтверждение пароля (для успешной смены пароля он должен
	* совпадать с <i>new_password</i>).
	*
	* @param string $site_id = SITE_ID ID сайта почтового шаблона типа USER_INFO для отсылки уведомления.<br>
	* Необязательный. По умолчанию - текущий сайт.
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* global $USER;
	* $arResult = <b>$USER-&gt;ChangePassword</b>("admin", "WRD45GT", "123456", "123456");
	* if($arResult["TYPE"] == "OK") echo "Пароль успешно сменен.";
	* else ShowMessage($arResult);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/sendpassword.php">CUser::SendPassword</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/senduserinfo.php">CUser::SendUserInfo</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/update.php">CUser::Update</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/changepassword.php
	* @author Bitrix
	*/
	public function ChangePassword($LOGIN, $CHECKWORD, $PASSWORD, $CONFIRM_PASSWORD, $SITE_ID=false)
	{
		/** @global CMain $APPLICATION */
		global $DB, $APPLICATION;

		$result_message = array("MESSAGE"=>GetMessage('PASSWORD_CHANGE_OK')."<br>", "TYPE"=>"OK");

		$arParams = array(
			"LOGIN"			=>	&$LOGIN,
			"CHECKWORD"			=>	&$CHECKWORD,
			"PASSWORD" 		=>	&$PASSWORD,
			"CONFIRM_PASSWORD" =>	&$CONFIRM_PASSWORD,
			"SITE_ID"		=>	&$SITE_ID
			);

		$APPLICATION->ResetException();
		$bOk = true;
		foreach(GetModuleEvents("main", "OnBeforeUserChangePassword", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array(&$arParams))===false)
			{
				if($err = $APPLICATION->GetException())
					$result_message = array("MESSAGE"=>$err->GetString()."<br>", "TYPE"=>"ERROR");

				$bOk = false;
				break;
			}
		}

		if($bOk)
		{
			$strAuthError = "";
			if(strlen($arParams["LOGIN"])<3)
				$strAuthError .= GetMessage('MIN_LOGIN')."<br>";
			if($arParams["PASSWORD"]<>$arParams["CONFIRM_PASSWORD"])
				$strAuthError .= GetMessage('WRONG_CONFIRMATION')."<br>";

			if($strAuthError <> '')
				return array("MESSAGE"=>$strAuthError, "TYPE"=>"ERROR");

			CTimeZone::Disable();
			$db_check = $DB->Query(
				"SELECT ID, LID, CHECKWORD, ".$DB->DateToCharFunction("CHECKWORD_TIME", "FULL")." as CHECKWORD_TIME ".
				"FROM b_user ".
				"WHERE LOGIN='".$DB->ForSql($arParams["LOGIN"], 0)."' AND (EXTERNAL_AUTH_ID IS NULL OR EXTERNAL_AUTH_ID='')");
			CTimeZone::Enable();

			if(!($res = $db_check->Fetch()))
				return array("MESSAGE"=>preg_replace("/#LOGIN#/i", htmlspecialcharsbx($arParams["LOGIN"]), GetMessage('LOGIN_NOT_FOUND')), "TYPE"=>"ERROR", "FIELD" => "LOGIN");

			$salt = substr($res["CHECKWORD"], 0, 8);
			if($res["CHECKWORD"] == '' || $res["CHECKWORD"] != $salt.md5($salt.$arParams["CHECKWORD"]))
				return array("MESSAGE"=>preg_replace("/#LOGIN#/i", htmlspecialcharsbx($arParams["LOGIN"]), GetMessage("CHECKWORD_INCORRECT"))."<br>", "TYPE"=>"ERROR", "FIELD"=>"CHECKWORD");

			$arPolicy = CUser::GetGroupPolicy($res["ID"]);

			$passwordErrors = $this->CheckPasswordAgainstPolicy($arParams["PASSWORD"], $arPolicy);
			if (!empty($passwordErrors))
			{
				return array(
					"MESSAGE" => implode("<br>", $passwordErrors)."<br>",
					"TYPE" => "ERROR"
				);
			}

			$site_format = CSite::GetDateFormat();
			if(mktime()-$arPolicy["CHECKWORD_TIMEOUT"]*60 > MakeTimeStamp($res["CHECKWORD_TIME"], $site_format))
				return array("MESSAGE"=>preg_replace("/#LOGIN#/i", htmlspecialcharsbx($arParams["LOGIN"]), GetMessage("CHECKWORD_EXPIRE"))."<br>", "TYPE"=>"ERROR", "FIELD"=>"CHECKWORD_EXPIRE");

			if($arParams["SITE_ID"] === false)
			{
				if(defined("ADMIN_SECTION") && ADMIN_SECTION===true)
					$arParams["SITE_ID"] = CSite::GetDefSite($res["LID"]);
				else
					$arParams["SITE_ID"] = SITE_ID;
			}

			// change the password
			$ID = $res["ID"];
			$obUser = new CUser;
			$res = $obUser->Update($ID, array("PASSWORD"=>$arParams["PASSWORD"]));
			if(!$res && $obUser->LAST_ERROR <> '')
				return array("MESSAGE"=>$obUser->LAST_ERROR."<br>", "TYPE"=>"ERROR");
			CUser::SendUserInfo($ID, $arParams["SITE_ID"], GetMessage('CHANGE_PASS_SUCC'), true, 'USER_PASS_CHANGED');
		}

		return $result_message;
	}

	static public function CheckPasswordAgainstPolicy($password, $arPolicy)
	{
		$errors = array();

		$password_min_length = intval($arPolicy["PASSWORD_LENGTH"]);
		if($password_min_length <= 0)
			$password_min_length = 6;
		if(strlen($password) < $password_min_length)
			$errors[] = GetMessage("MAIN_FUNCTION_REGISTER_PASSWORD_LENGTH", array("#LENGTH#" => $arPolicy["PASSWORD_LENGTH"]));

		if(($arPolicy["PASSWORD_UPPERCASE"] === "Y") && !preg_match("/[A-Z]/", $password))
			$errors[] = GetMessage("MAIN_FUNCTION_REGISTER_PASSWORD_UPPERCASE");

		if(($arPolicy["PASSWORD_LOWERCASE"] === "Y") && !preg_match("/[a-z]/", $password))
			$errors[] = GetMessage("MAIN_FUNCTION_REGISTER_PASSWORD_LOWERCASE");

		if(($arPolicy["PASSWORD_DIGITS"] === "Y") && !preg_match("/[0-9]/", $password))
			$errors[] = GetMessage("MAIN_FUNCTION_REGISTER_PASSWORD_DIGITS");

		if(($arPolicy["PASSWORD_PUNCTUATION"] === "Y") && !preg_match("/[,.<>\\/?;:'\"[\\]\\{\\}\\\\|`~!@#\$%^&*()_+=-]/", $password))
			$errors[] = GetMessage("MAIN_FUNCTION_REGISTER_PASSWORD_PUNCTUATION");

		return $errors;
	}

	/**
	 * Sends a profile information to email
	 */
	
	/**
	* <p>Отсылает почтовое сообщение с параметрами пользователя по шаблону типа USER_INFO. Нестатический метод.</p>
	*
	*
	* @param mixed $intid  ID пользователя.
	*
	* @param string $site_id  ID сайта почтового шаблона. До версии 3.3.21 назывался <i>lang</i>.
	*
	* @param string $MSG  Произвольный текст сообщения (#MESSAGE#).
	*
	* @param bool $Immediate = false По умолчанию <i>false</i>. Если передать <i>true</i>, письмо будет
	* отправлено сразу, без записи в БД.
	*
	* @param string $eventName = "USER_INFO" Параметр, в котором передаётся строкой тип отправки событий.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $USER_ID = 1;
	* // отсылаем почтовое сообщение пользователю с ID=1, 
	* // по шаблону привязанному к текущему сайту
	* <b>CUser::SendUserInfo</b>($USER_ID, SITE_ID, "Приветствуем Вас как нового пользователя нашего сайта!");
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/sendpassword.php">SendPassword</a> </li></ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/senduserinfo.php
	* @author Bitrix
	*/
	public static function SendUserInfo($ID, $SITE_ID, $MSG, $bImmediate=false, $eventName="USER_INFO")
	{
		global $DB;

		// change CHECKWORD
		$ID = intval($ID);
		$salt = randString(8);
		$checkword = md5(CMain::GetServerUniqID().uniqid());
		$strSql = "UPDATE b_user SET ".
			"	CHECKWORD = '".$salt.md5($salt.$checkword)."', ".
			"	CHECKWORD_TIME = ".$DB->CurrentTimeFunction().", ".
			"	LID = '".$DB->ForSql($SITE_ID, 2)."', ".
			"   TIMESTAMP_X = TIMESTAMP_X ".
			"WHERE ID = '".$ID."'".
			"	AND (EXTERNAL_AUTH_ID IS NULL OR EXTERNAL_AUTH_ID='') ";

		$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		$res = $DB->Query(
			"SELECT u.* ".
			"FROM b_user u ".
			"WHERE ID='".$ID."'".
			"	AND (EXTERNAL_AUTH_ID IS NULL OR EXTERNAL_AUTH_ID='') "
		);

		if($res_array = $res->Fetch())
		{
			$event = new CEvent;
			$arFields = array(
				"USER_ID"=>$res_array["ID"],
				"STATUS"=>($res_array["ACTIVE"]=="Y"?GetMessage("STATUS_ACTIVE"):GetMessage("STATUS_BLOCKED")),
				"MESSAGE"=>$MSG,
				"LOGIN"=>$res_array["LOGIN"],
				"URL_LOGIN"=>urlencode($res_array["LOGIN"]),
				"CHECKWORD"=>$checkword,
				"NAME"=>$res_array["NAME"],
				"LAST_NAME"=>$res_array["LAST_NAME"],
				"EMAIL"=>$res_array["EMAIL"]
			);

			$arParams = array(
				"FIELDS" => &$arFields,
				"USER_FIELDS" => $res_array,
				"SITE_ID" => &$SITE_ID,
				"EVENT_NAME" => &$eventName,
			);

			foreach (GetModuleEvents("main", "OnSendUserInfo", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array(&$arParams));

			if (!$bImmediate)
				$event->Send($eventName, $SITE_ID, $arFields);
			else
				$event->SendImmediate($eventName, $SITE_ID, $arFields);
		}
	}

	
	/**
	* <p>Отсылает пользователю почтовое сообщение с контрольной строкой для смены пароля. Сообщение отсылается по шаблону типа USER_PASS_REQUEST. Пользователь определяется по логину <i>login</i> или E-Mail адресу - параметр <i>email</i>. Возвращает массив с сообщением о результате выполнения (массив может быть обработан функцией <a href="http://dev.1c-bitrix.ru/api_help/main/functions/other/showmessage.php">ShowMessage</a>). Нестатический метод.</p>
	*
	*
	* @param string $login  Логин пользователя.
	*
	* @param string $email  E-Mail адрес пользователя.
	*
	* @param string $site_id = SITE_ID ID сайта почтового шаблона типа USER_PASS_REQUEST.<br> Необязательный. По
	* умолчанию - текущий сайт.
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* global $USER;
	* $arResult = <b>$USER-&gt;SendPassword</b>($USER-&gt;GetLogin(), $USER-&gt;GetParam("EMAIL"));
	* if($arResult["TYPE"] == "OK") echo "Контрольная строка для смены пароля выслана.";
	* else echo "Введенные логин (email) не найдены.";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/senduserinfo.php">CUser::SendUserInfo</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/changepassword.php">CUser::ChangePassword</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/sendpassword.php
	* @author Bitrix
	*/
	public static function SendPassword($LOGIN, $EMAIL, $SITE_ID = false)
	{
		/** @global CMain $APPLICATION */
		global $DB, $APPLICATION;

		$arParams = array(
			"LOGIN" => $LOGIN,
			"EMAIL" => $EMAIL,
			"SITE_ID" => $SITE_ID
		);

		$result_message = array("MESSAGE"=>GetMessage('ACCOUNT_INFO_SENT')."<br>", "TYPE"=>"OK");
		$APPLICATION->ResetException();
		$bOk = true;
		foreach(GetModuleEvents("main", "OnBeforeUserSendPassword", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array(&$arParams))===false)
			{
				if($err = $APPLICATION->GetException())
					$result_message = array("MESSAGE"=>$err->GetString()."<br>", "TYPE"=>"ERROR");

				$bOk = false;
				break;
			}
		}

		if($bOk)
		{
			$f = false;
			if($arParams["LOGIN"] <> '' || $arParams["EMAIL"] <> '')
			{
				$confirmation = (COption::GetOptionString("main", "new_user_registration_email_confirmation", "N") == "Y");

				$strSql = "";
				if($arParams["LOGIN"] <> '')
				{
					$strSql =
						"SELECT ID, LID, ACTIVE, CONFIRM_CODE, LOGIN, EMAIL, NAME, LAST_NAME ".
						"FROM b_user u ".
						"WHERE LOGIN='".$DB->ForSQL($arParams["LOGIN"])."' ".
						"	AND (ACTIVE='Y' OR NOT(CONFIRM_CODE IS NULL OR CONFIRM_CODE='')) ".
						"	AND (EXTERNAL_AUTH_ID IS NULL OR EXTERNAL_AUTH_ID='') ";
				}
				if($arParams["EMAIL"] <> '')
				{
					if($strSql <> '')
					{
						$strSql .= "\nUNION\n";
					}
					$strSql .=
						"SELECT ID, LID, ACTIVE, CONFIRM_CODE, LOGIN, EMAIL, NAME, LAST_NAME ".
						"FROM b_user u ".
						"WHERE EMAIL='".$DB->ForSQL($arParams["EMAIL"])."' ".
						"	AND (ACTIVE='Y' OR NOT(CONFIRM_CODE IS NULL OR CONFIRM_CODE='')) ".
						"	AND (EXTERNAL_AUTH_ID IS NULL OR EXTERNAL_AUTH_ID='') ";
				}
				$res = $DB->Query($strSql);

				while($arUser = $res->Fetch())
				{
					if($arParams["SITE_ID"]===false)
					{
						if(defined("ADMIN_SECTION") && ADMIN_SECTION===true)
							$arParams["SITE_ID"] = CSite::GetDefSite($arUser["LID"]);
						else
							$arParams["SITE_ID"] = SITE_ID;
					}

					if($arUser["ACTIVE"] == "Y")
					{
						CUser::SendUserInfo($arUser["ID"], $arParams["SITE_ID"], GetMessage("INFO_REQ"), true, 'USER_PASS_REQUEST');
						$f = true;
					}
					elseif($confirmation)
					{
						//unconfirmed registration - resend confirmation email
						$arFields = array(
							"USER_ID" => $arUser["ID"],
							"LOGIN" => $arUser["LOGIN"],
							"EMAIL" => $arUser["EMAIL"],
							"NAME" => $arUser["NAME"],
							"LAST_NAME" => $arUser["LAST_NAME"],
							"CONFIRM_CODE" => $arUser["CONFIRM_CODE"],
							"USER_IP" => $_SERVER["REMOTE_ADDR"],
							"USER_HOST" => @gethostbyaddr($_SERVER["REMOTE_ADDR"]),
						);

						$event = new CEvent;
						$event->SendImmediate("NEW_USER_CONFIRM", $arParams["SITE_ID"], $arFields);

						$result_message = array("MESSAGE"=>GetMessage("MAIN_SEND_PASS_CONFIRM")."<br>", "TYPE"=>"OK");
						$f = true;
					}

					if(COption::GetOptionString("main", "event_log_password_request", "N") === "Y")
					{
						CEventLog::Log("SECURITY", "USER_INFO", "main", $arUser["ID"]);
					}
				}
			}
			if(!$f)
			{
				return array("MESSAGE"=>GetMessage('DATA_NOT_FOUND')."<br>", "TYPE"=>"ERROR");
			}
		}
		return $result_message;
	}

	
	/**
	* <p>Регистрирует нового пользователя, авторизует его и отсылает письмо по шаблону типа NEW_USER. Возвращает массив с сообщением о результате выполнения (массив может быть обработан функцией <a href="http://dev.1c-bitrix.ru/api_help/main/functions/other/showmessage.php">ShowMessage</a>). Нестатический метод.</p> <p class="note"><b>Важно!</b> Метод может использоваться только в публичной части сайта!</p>
	*
	*
	* @param string $USER_LOGIN  Логин нового пользователя (не менее 3-х символов).
	*
	* @param string $USER_NAME  Имя нового пользователя (может быть пустым).
	*
	* @param string $USER_LAST_NAME  Фамилия нового пользователя (может быть пустым).
	*
	* @param string $USER_PASSWORD  Пароль (не менее 3-х символов).
	*
	* @param string $USER_CONFIRM_PASSWORD  Подтверждение пароля (для успешной регистрации должен совпадать
	* с <i>password</i>).
	*
	* @param string $USER_EMAIL  E-Mail нового пользователя (не менее 3-х символов). E-Mail будет проверен
	* функцией <a href="http://dev.1c-bitrix.ru/api_help/main/functions/other/check_email.php">check_email</a>.
	*
	* @param string $site_id = false ID сайта почтового шаблона для отсылки уведомлений (NEW_USER, USER_INFO и
	* др.).<br> Необязательный. По умолчанию - "false", что означает текущий
	* сайт.
	*
	* @param string $captcha_word = "" Слово для CAPTCHA. Добавляется если в настройках главного модуля
	* выставлен флаг "Использовать CAPTCHA при регистрации". Если не
	* заполнено вернет: "Слово для защиты от автоматической
	* регистрации введено неверно".
	*
	* @param string $captcha_sid = 0 ID CAPTCHA. Добавляется если в настройках главного модуля выставлен
	* флаг "Использовать CAPTCHA при регистрации". Если не заполнено
	* вернет: "Слово для защиты от автоматической регистрации введено
	* неверно".
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* global $USER;
	* $arResult = <b>$USER-&gt;Register</b>("admin", "", "", "123456", "123456", "admin@mysite.ru");
	* ShowMessage($arResult); // выводим результат в виде сообщения
	* echo $USER-&gt;GetID(); // ID нового пользователя
	* ?&gt;Смотрите также<li><a href="http://dev.1c-bitrix.ru/community/webdev/user/61475/blog/updated-without-a-page-reload-captcha/">Обновление капчи без перезагрузки страницы</a></li>
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/simpleregister.php">CUser::SimpleRegister</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/login.php">CUser::Login</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/add.php">CUser::Add</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/authorize.php">CUser::Authorize</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/events/onafteruserregister.php">Событие "OnAfterUserRegister"</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserregister.php">Событие
	* "OnBeforeUserRegister"</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/register.php
	* @author Bitrix
	*/
	public function Register($USER_LOGIN, $USER_NAME, $USER_LAST_NAME, $USER_PASSWORD, $USER_CONFIRM_PASSWORD, $USER_EMAIL, $SITE_ID = false, $captcha_word = "", $captcha_sid = 0, $bSkipConfirm = false)
	{
		/**
		 * @global CMain $APPLICATION
		 * @global CUserTypeManager $USER_FIELD_MANAGER
		 */
		global $APPLICATION, $DB, $USER_FIELD_MANAGER;

		$APPLICATION->ResetException();
		if(defined("ADMIN_SECTION") && ADMIN_SECTION===true && $SITE_ID!==false)
		{
			$APPLICATION->ThrowException(GetMessage("MAIN_FUNCTION_REGISTER_NA_INADMIN"));
			return array("MESSAGE"=>GetMessage("MAIN_FUNCTION_REGISTER_NA_INADMIN"), "TYPE"=>"ERROR");
		}

		$strError = "";

		if (COption::GetOptionString("main", "captcha_registration", "N") == "Y")
		{
			if (!($APPLICATION->CaptchaCheckCode($captcha_word, $captcha_sid)))
			{
				$strError .= GetMessage("MAIN_FUNCTION_REGISTER_CAPTCHA")."<br>";
			}
		}

		if($strError)
		{
			if(COption::GetOptionString("main", "event_log_register_fail", "N") === "Y")
			{
				CEventLog::Log("SECURITY", "USER_REGISTER_FAIL", "main", false, $strError);
			}

			$APPLICATION->ThrowException($strError);
			return array("MESSAGE"=>$strError, "TYPE"=>"ERROR");
		}

		if($SITE_ID===false)
			$SITE_ID = SITE_ID;

		$checkword = md5(CMain::GetServerUniqID().uniqid());
		$bConfirmReq = !$bSkipConfirm && (COption::GetOptionString("main", "new_user_registration_email_confirmation", "N") == "Y" && COption::GetOptionString("main", "new_user_email_required", "Y") <> "N");
		$arFields = array(
			"LOGIN" => $USER_LOGIN,
			"NAME" => $USER_NAME,
			"LAST_NAME" => $USER_LAST_NAME,
			"PASSWORD" => $USER_PASSWORD,
			"CHECKWORD" => $checkword,
			"~CHECKWORD_TIME" => $DB->CurrentTimeFunction(),
			"CONFIRM_PASSWORD" => $USER_CONFIRM_PASSWORD,
			"EMAIL" => $USER_EMAIL,
			"ACTIVE" => $bConfirmReq? "N": "Y",
			"CONFIRM_CODE" => $bConfirmReq? randString(8): "",
			"SITE_ID" => $SITE_ID,
			"USER_IP" => $_SERVER["REMOTE_ADDR"],
			"USER_HOST" => @gethostbyaddr($_SERVER["REMOTE_ADDR"]),
		);
		$USER_FIELD_MANAGER->EditFormAddFields("USER", $arFields);

		$def_group = COption::GetOptionString("main", "new_user_registration_def_group", "");
		if($def_group!="")
			$arFields["GROUP_ID"] = explode(",", $def_group);

		$bOk = true;
		$result_message = true;
		foreach(GetModuleEvents("main", "OnBeforeUserRegister", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
			{
				if($err = $APPLICATION->GetException())
					$result_message = array("MESSAGE"=>$err->GetString()."<br>", "TYPE"=>"ERROR");
				else
				{
					$APPLICATION->ThrowException("Unknown error");
					$result_message = array("MESSAGE"=>"Unknown error"."<br>", "TYPE"=>"ERROR");
				}

				$bOk = false;
				break;
			}
		}

		$ID = false;
		if($bOk)
		{
			$arFields["LID"] = $arFields["SITE_ID"];
			if($ID = $this->Add($arFields))
			{
				$arFields["USER_ID"] = $ID;

				$arEventFields = $arFields;
				unset($arEventFields["PASSWORD"]);
				unset($arEventFields["CONFIRM_PASSWORD"]);
				unset($arEventFields["~CHECKWORD_TIME"]);

				$event = new CEvent;
				$event->SendImmediate("NEW_USER", $arEventFields["SITE_ID"], $arEventFields);
				if($bConfirmReq)
					$event->SendImmediate("NEW_USER_CONFIRM", $arEventFields["SITE_ID"], $arEventFields);
				$result_message = array("MESSAGE"=>GetMessage("USER_REGISTER_OK"), "TYPE"=>"OK", "ID"=>$ID);
			}
			else
			{
				$APPLICATION->ThrowException($this->LAST_ERROR);
				$result_message = array("MESSAGE"=>$this->LAST_ERROR, "TYPE"=>"ERROR");
			}
		}

		if(is_array($result_message))
		{
			if($result_message["TYPE"] == "OK")
			{
				if(COption::GetOptionString("main", "event_log_register", "N") === "Y")
				{
					$res_log["user"] = ($USER_NAME != "" || $USER_LAST_NAME != "") ? trim($USER_NAME." ".$USER_LAST_NAME) : $USER_LOGIN;
					CEventLog::Log("SECURITY", "USER_REGISTER", "main", $ID, serialize($res_log));
				}
			}
			else
			{
				if(COption::GetOptionString("main", "event_log_register_fail", "N") === "Y")
				{
					CEventLog::Log("SECURITY", "USER_REGISTER_FAIL", "main", $ID, $result_message["MESSAGE"]);
				}
			}
		}

		//authorize succesfully registered user
		if($ID !== false && $arFields["ACTIVE"] === "Y")
			$this->Authorize($ID);

		$arFields["RESULT_MESSAGE"] = $result_message;
		foreach (GetModuleEvents("main", "OnAfterUserRegister", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arFields));

		return $arFields["RESULT_MESSAGE"];
	}

	
	/**
	* <p>Создает нового пользователя предварительно сгенерировав случайный логин и пароль. Возвращает массив с сообщением о результате выполнения (массив может быть обработан функцией <a href="http://dev.1c-bitrix.ru/api_help/main/functions/other/showmessage.php">ShowMessage</a>). Нестатический метод.</p> <p class="note"><b>Важно!</b> Метод может использоваться только в публичной части сайта!</p>
	*
	*
	* @param string $USER_EMAIL  E-Mail нового пользователя (не менее 3-х символов). E-Mail будет проверен
	* функцией <a href="http://dev.1c-bitrix.ru/api_help/main/functions/other/check_email.php">check_email</a>.
	*
	* @param string $site_id = SITE_ID ID сайта почтового шаблона для отсылки уведомлений (NEW_USER, USER_INFO и
	* др.).<br> Необязательный. По умолчанию - текущий сайт.
	*
	* @param string $captcha_word = "" Слово для CAPTCHA. Добавляется если в настройках главного модуля
	* выставлен флаг "Использовать CAPTCHA при регистрации". Если не
	* заполнено вернет: "Слово для защиты от автоматической
	* регистрации введено неверно".
	*
	* @param string $captcha_sid = 0 ID CAPTCHA. Добавляется если в настройках главного модуля выставлен
	* флаг "Использовать CAPTCHA при регистрации". Если не заполнено
	* вернет: "Слово для защиты от автоматической регистрации введено
	* неверно".
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* global $USER;
	* $arResult = <b>$USER-&gt;SimpleRegister</b>("admin@mysite.ru");
	* ShowMessage($arResult); // выводим результат в виде сообщения
	* echo $USER-&gt;GetID(); // ID нового пользователя
	* ?&gt;Смотрите также<li><a href="http://dev.1c-bitrix.ru/community/webdev/user/61475/blog/updated-without-a-page-reload-captcha/">Обновление капчи без перезагрузки страницы</a></li>
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/register.php">CUser::Register</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/login.php">CUser::Login</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/add.php">CUser::Add</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/authorize.php">CUser::Authorize</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/events/onafterusersimpleregister.php">Событие
	* "OnAfterUserSimpleRegister"</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeusersimpleregister.php">Событие
	* "OnBeforeUserSimpleRegister"</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/simpleregister.php
	* @author Bitrix
	*/
	public function SimpleRegister($USER_EMAIL, $SITE_ID = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $DB;

		$APPLICATION->ResetException();
		if(defined("ADMIN_SECTION") && ADMIN_SECTION===true && $SITE_ID===false)
		{
			$APPLICATION->ThrowException(GetMessage("MAIN_FUNCTION_SIMPLEREGISTER_NA_INADMIN"));
			return array("MESSAGE"=>GetMessage("MAIN_FUNCTION_SIMPLEREGISTER_NA_INADMIN"), "TYPE"=>"ERROR");
		}

		if($SITE_ID===false)
			$SITE_ID = SITE_ID;

		global $REMOTE_ADDR;

		$checkword = md5(CMain::GetServerUniqID().uniqid());
		$arFields = array(
			"CHECKWORD" => $checkword,
			"~CHECKWORD_TIME" => $DB->CurrentTimeFunction(),
			"EMAIL" => $USER_EMAIL,
			"ACTIVE" => "Y",
			"NAME"=>"",
			"LAST_NAME"=>"",
			"USER_IP"=>$REMOTE_ADDR,
			"USER_HOST"=>@gethostbyaddr($REMOTE_ADDR),
			"SITE_ID" => $SITE_ID
		);

		$def_group = COption::GetOptionString("main", "new_user_registration_def_group", "");
		if($def_group!="")
		{
			$arFields["GROUP_ID"] = explode(",", $def_group);
			$arPolicy = $this->GetGroupPolicy($arFields["GROUP_ID"]);
		}
		else
		{
			$arPolicy = $this->GetGroupPolicy(array());
		}
		$password_min_length = intval($arPolicy["PASSWORD_LENGTH"]);
		if($password_min_length <= 0)
			$password_min_length = 6;
		$password_chars = array(
			"abcdefghijklnmopqrstuvwxyz",
			"ABCDEFGHIJKLNMOPQRSTUVWXYZ",
			"0123456789",
		);
		if($arPolicy["PASSWORD_PUNCTUATION"] === "Y")
			$password_chars[] = ",.<>/?;:'\"[]{}\\|`~!@#\$%^&*()-_+=";
		$arFields["PASSWORD"] = $arFields["CONFIRM_PASSWORD"] = randString($password_min_length, $password_chars);

		$bOk = true;
		$result_message = false;
		foreach(GetModuleEvents("main", "OnBeforeUserSimpleRegister", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
			{
				if($err = $APPLICATION->GetException())
					$result_message = array("MESSAGE"=>$err->GetString()."<br>", "TYPE"=>"ERROR");
				else
				{
					$APPLICATION->ThrowException("Unknown error");
					$result_message = array("MESSAGE"=>"Unknown error"."<br>", "TYPE"=>"ERROR");
				}

				$bOk = false;
				break;
			}
		}

		$bRandLogin = false;
		if(!is_set($arFields, "LOGIN"))
		{
			$arFields["LOGIN"] = randString(50);
			$bRandLogin = true;
		}

		$ID = 0;
		if($bOk)
		{
			$arFields["LID"] = $arFields["SITE_ID"];
			$arFields["CHECKWORD"] = $checkword;
			if($ID = $this->Add($arFields))
			{
				if($bRandLogin)
				{
					$this->Update($ID, array("LOGIN"=>"user".$ID));
					$arFields["LOGIN"] = "user".$ID;
				}

				$this->Authorize($ID);

				$event = new CEvent;
				$arFields["USER_ID"] = $ID;

				$arEventFields = $arFields;
				unset($arEventFields["PASSWORD"]);
				unset($arEventFields["CONFIRM_PASSWORD"]);

				$event->SendImmediate("NEW_USER", $arEventFields["SITE_ID"], $arEventFields);
				CUser::SendUserInfo($ID, $arEventFields["SITE_ID"], GetMessage("USER_REGISTERED_SIMPLE"), true);
				$result_message = array("MESSAGE"=>GetMessage("USER_REGISTER_OK"), "TYPE"=>"OK");
			}
			else
				$result_message = array("MESSAGE"=>$this->LAST_ERROR, "TYPE"=>"ERROR");
		}

		if(is_array($result_message))
		{
			if($result_message["TYPE"] == "OK")
			{
				if(COption::GetOptionString("main", "event_log_register", "N") === "Y")
				{
					$res_log["user"] = $arFields["LOGIN"];
					CEventLog::Log("SECURITY", "USER_REGISTER", "main", $ID, serialize($res_log));
				}
			}
			else
			{
				if(COption::GetOptionString("main", "event_log_register_fail", "N") === "Y")
				{
					CEventLog::Log("SECURITY", "USER_REGISTER_FAIL", "main", $ID, $result_message["MESSAGE"]);
				}
			}
		}

		$arFields["RESULT_MESSAGE"] = $result_message;
		foreach(GetModuleEvents("main", "OnAfterUserSimpleRegister", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arFields));

		return $arFields["RESULT_MESSAGE"];
	}

	
	/**
	* <p>Проверяет авторизован ли посетитель сайта (как правило вызывается с объекта $USER). Возвращает "true" если посетитель авторизован, иначе "false". Нестатический метод.</p>
	*
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* global $USER;
	* if (<b>$USER-&gt;IsAuthorized</b>()) echo "Вы авторизованы!";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/authorize.php">CUser::Authorize</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/login.php">CUser::Login</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/loginbyhash.php">CUser::LoginByHash</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/isadmin.php">CUser::IsAdmin</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/isauthorized.php
	* @author Bitrix
	*/
	static public function IsAuthorized()
	{
		return ($_SESSION["SESS_AUTH"]["AUTHORIZED"]=="Y");
	}

	public function IsJustAuthorized()
	{
		return $this->justAuthorized;
	}

	
	/**
	* <p>Проверяет принадлежность текущего авторизованного пользователя группе администраторов (как правило вызывается с объекта $USER). Возращает "true" - если пользователь принадлежит группе администраторов, в противном случае вернет "false". Нестатический метод.</p>
	*
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* global $USER;
	* if (<b>$USER-&gt;IsAdmin</b>()) echo "Вы администратор!";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/isauthorized.php">CUser::IsAuthorized</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getparam.php">CUser::GetParam</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/isadmin.php
	* @author Bitrix
	*/
	public function IsAdmin()
	{
		if ($this->admin === null)
		{
			if(
				COption::GetOptionString("main", "controller_member", "N") == "Y"
				&& COption::GetOptionString("main", "~controller_limited_admin", "N") == "Y"
			)
			{
				if(
					isset($_SESSION["SESS_AUTH"])
					&& is_array($_SESSION["SESS_AUTH"])
					&& isset($_SESSION["SESS_AUTH"]["CONTROLLER_ADMIN"])
				)
					$this->admin = ($_SESSION["SESS_AUTH"]["CONTROLLER_ADMIN"] === true);
				else
					$this->admin = false;
			}
			else
			{
				if(
					isset($_SESSION["SESS_AUTH"])
					&& is_array($_SESSION["SESS_AUTH"])
					&& isset($_SESSION["SESS_AUTH"]["ADMIN"])
				)
					$this->admin = ($_SESSION["SESS_AUTH"]["ADMIN"] === true);
				else
					$this->admin = false;
			}
		}
		return $this->admin;
	}

	static public function SetControllerAdmin($isAdmin=true)
	{
		$_SESSION["SESS_AUTH"]["CONTROLLER_ADMIN"] = $isAdmin;
	}

	
	/**
	* <p>Заканчивает сеанс авторизации пользователя, при этом удаляются те куки пользователя, которые используются при автоматической авторизации. Нестатический метод.</p>
	*
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* <b>$USER-&gt;Logout</b>();
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/login.php">CUser::Login</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/authorize.php">CUser::Authorize</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserlogout.php">Событие "OnBeforeUserLogout"</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/events/onafteruserlogout.php">Событие "OnAfterUserLogout"</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/logout.php
	* @author Bitrix
	*/
	public function Logout()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $DB;

		$USER_ID = $_SESSION["SESS_AUTH"]["USER_ID"];

		$arParams = array(
			"USER_ID" => &$USER_ID
		);

		$APPLICATION->ResetException();
		$bOk = true;
		foreach(GetModuleEvents("main", "OnBeforeUserLogout", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array(&$arParams))===false)
			{
				if(!($APPLICATION->GetException()))
				{
					$APPLICATION->ThrowException("Unknown logout error");
				}

				$bOk = false;
				break;
			}
		}

		if($bOk)
		{
			foreach(GetModuleEvents("main", "OnUserLogout", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($USER_ID));

			if($_SESSION["SESS_AUTH"]["STORED_AUTH_ID"]>0)
				$DB->Query("DELETE FROM b_user_stored_auth WHERE ID=".intval($_SESSION["SESS_AUTH"]["STORED_AUTH_ID"]));

			$this->justAuthorized = false;

			$_SESSION["SESS_AUTH"] = array();
			unset($_SESSION["SESS_AUTH"]);
			unset($_SESSION["SESS_OPERATIONS"]);
			unset($_SESSION["MODULE_PERMISSIONS"]);
			unset($_SESSION["SESS_PWD_HASH_TESTED"]);

			//change session id for security reason after logout
			if(COption::GetOptionString("security", "session", "N") === "Y" && CModule::IncludeModule("security"))
				CSecuritySession::UpdateSessID();
			else
				session_regenerate_id(true);

			$multi = (COption::GetOptionString("main", "auth_multisite", "N") == "Y");
			$APPLICATION->set_cookie("UIDH", "", 0, '/', false, false, $multi, false, true);
			$APPLICATION->set_cookie("UIDL", "", 0, '/', false, false, $multi, false, true);

			CHTMLPagesCache::OnUserLogout();
		}

		$arParams["SUCCESS"] = $bOk;
		foreach(GetModuleEvents("main", "OnAfterUserLogout", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arParams));

		if(COption::GetOptionString("main", "event_log_logout", "N") === "Y")
			CEventLog::Log("SECURITY", "USER_LOGOUT", "main", $USER_ID);
	}

	
	/**
	* <p>Возвращает массив ID групп, которым принадлежит пользователь с кодом <i>id</i>. <b>GetUserGroup</b> получает данные из записи о пользователях в базе данных. Нестатический метод.</p>
	*
	*
	* @param mixed $intid  ID пользователя.
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // получим массив групп пользователя ID=12
	* $arGroups = <b>CUser::GetUserGroup</b>(12);
	* echo "&lt;pre&gt;"; print_r($arGroups); echo "&lt;/pre&gt;";
	* ?&gt;Принадлежит ли пользователь группе:// для любого пользователя
	* echo in_array($group_id, CUser::GetUserGroup($user_id));
	* 
	* // для текущего пользователя
	* echo in_array($group_id, $USER-&gt;GetUserGroupArray());Принадлежит ли пользователь, который состоит во многих группах заданным:&lt;?$arGroupAvalaible = array(1,9,12,13,14,15); // массив групп, которые в которых нужно проверить доступность пользователя
	* $arGroups = CUser::GetUserGroup($USER-&gt;GetID()); // массив групп, в которых состоит пользователь
	* $result_intersect = array_intersect($arGroupAvalaible, $arGroups);// далее проверяем, если пользователь вошёл хотя бы в одну из групп, то позволяем ему что-либо делать
	* if(!empty($result_intersect)):     print "мне разрешено находится на данной странице или просматривать данную часть страницы";endif;&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/index.php">Класс CGroup</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergrouparray.php">CUser::GetUserGroupArray</a>  </li>
	* <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergroupstring.php">CUser::GetUserGroupString</a>  </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergroup.php
	* @author Bitrix
	*/
	public static function GetUserGroup($ID)
	{
		global $DB;

		$ID = (int)$ID;
		if (!isset(self::$userGroupCache[$ID]))
		{
			$strSql =
				"SELECT UG.GROUP_ID ".
				"FROM b_user_group UG ".
				"WHERE UG.USER_ID = ".$ID." ".
				"	AND ((UG.DATE_ACTIVE_FROM IS NULL) OR (UG.DATE_ACTIVE_FROM <= ".$DB->CurrentTimeFunction().")) ".
				"	AND ((UG.DATE_ACTIVE_TO IS NULL) OR (UG.DATE_ACTIVE_TO >= ".$DB->CurrentTimeFunction().")) ";

			$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			$arr = array();
			while ($r = $res->Fetch())
				$arr[] = $r["GROUP_ID"];

			if (!in_array(2, $arr))
				$arr[] = 2;
			self::$userGroupCache[$ID] = $arr;
		}

		return self::$userGroupCache[$ID];
	}

	public static function GetUserGroupEx($ID)
	{
		global $DB;

		$strSql = "
			SELECT UG.GROUP_ID, G.STRING_ID,
				".$DB->DateToCharFunction("UG.DATE_ACTIVE_FROM", "FULL")." as DATE_ACTIVE_FROM,
				".$DB->DateToCharFunction("UG.DATE_ACTIVE_TO", "FULL")." as DATE_ACTIVE_TO
			FROM b_user_group UG INNER JOIN b_group G ON G.ID=UG.GROUP_ID
			WHERE UG.USER_ID = ".intval($ID)."
			and ((UG.DATE_ACTIVE_FROM IS NULL) OR (UG.DATE_ACTIVE_FROM <= ".$DB->CurrentTimeFunction()."))
			and ((UG.DATE_ACTIVE_TO IS NULL) OR (UG.DATE_ACTIVE_TO >= ".$DB->CurrentTimeFunction()."))
			UNION SELECT 2, 'everyone', NULL, NULL ".(strtoupper($DB->type) == "ORACLE"? " FROM dual " : "");

		$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		return $res;
	}

	
	/**
	* <p>Метод возвращает группы пользователя и не учитывает даты вхождения в них. Статический метод.</p>
	*
	*
	* @param int $ID  ID пользователя
	*
	* @return mixed <p>Возвращает массив групп и период пребывания пользователя в
	* этой группе:</p><ul> <li> <b>GROUP_ID</b> ID группы</li> <li> <b>DATE_ACTIVE_FROM</b> Дата
	* начала активности</li>  <li> <b>DATE_ACTIVE_TO</b> Дата окончания активности
	* </li>   </ul><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $res = CUser::GetUserGroupList(1);
	* while ($arGroup = $res-&gt;Fetch()){
	*    print "&lt;pre&gt;"; print_r($arGroup); print "&lt;/pre&gt;";
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergrouplist.php
	* @author Bitrix
	*/
	public static function GetUserGroupList($ID)
	{
		global $DB;

		$strSql = "
			SELECT
				UG.GROUP_ID,
				".$DB->DateToCharFunction("UG.DATE_ACTIVE_FROM", "FULL")." as DATE_ACTIVE_FROM,
				".$DB->DateToCharFunction("UG.DATE_ACTIVE_TO", "FULL")." as DATE_ACTIVE_TO
			FROM
				b_user_group UG
			WHERE
				UG.USER_ID = ".intval($ID)."
			UNION SELECT 2, NULL, NULL ".(strtoupper($DB->type) == "ORACLE"? " FROM dual " : "");

		$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		return $res;
	}

	public function CheckFields(&$arFields, $ID=false)
	{
		/**
		 * @global CMain $APPLICATION
		 * @global CUserTypeManager $USER_FIELD_MANAGER
		 */
		global $DB, $APPLICATION, $USER_FIELD_MANAGER;

		$this->LAST_ERROR = "";

		$bInternal = false;
		$bExternal = (is_set($arFields, "EXTERNAL_AUTH_ID") && trim($arFields["EXTERNAL_AUTH_ID"]) <> '');
		$oldEmail = "";
		if($ID > 0 && !$bExternal)
		{
			$strSql = "SELECT EXTERNAL_AUTH_ID, EMAIL FROM b_user WHERE ID=".intval($ID);
			$dbr = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			if(($ar = $dbr->Fetch()))
			{
				$oldEmail = $ar['EMAIL'];
				if($ar['EXTERNAL_AUTH_ID'] == '')
					$bInternal = true;
			}

		}
		elseif(!$bExternal)
		{
			$bInternal = true;
		}


		if($bInternal)
		{
			$emailRequired = (COption::GetOptionString("main", "new_user_email_required", "Y") <> "N");

			if($ID === false)
			{
				if(!isset($arFields["LOGIN"]))
					$this->LAST_ERROR .= GetMessage("user_login_not_set")."<br>";

				if(!isset($arFields["PASSWORD"]))
					$this->LAST_ERROR .= GetMessage("user_pass_not_set")."<br>";

				if($emailRequired && !isset($arFields["EMAIL"]))
					$this->LAST_ERROR .= GetMessage("user_email_not_set")."<br>";
			}
			if(is_set($arFields, "LOGIN") && $arFields["LOGIN"]!=Trim($arFields["LOGIN"]))
				$this->LAST_ERROR .= GetMessage("LOGIN_WHITESPACE")."<br>";

			if(is_set($arFields, "LOGIN") && strlen($arFields["LOGIN"])<3)
				$this->LAST_ERROR .= GetMessage("MIN_LOGIN")."<br>";

			if(is_set($arFields, "PASSWORD"))
			{
				if(array_key_exists("GROUP_ID", $arFields))
				{
					$arGroups = array();
					if(is_array($arFields["GROUP_ID"]))
					{
						foreach($arFields["GROUP_ID"] as $arGroup)
						{
							if(is_array($arGroup))
								$arGroups[] = $arGroup["GROUP_ID"];
							else
								$arGroups[] = $arGroup;
						}
					}
					$arPolicy = $this->GetGroupPolicy($arGroups);
				}
				elseif($ID !== false)
				{
					$arPolicy = $this->GetGroupPolicy($ID);
				}
				else
				{
					$arPolicy = $this->GetGroupPolicy(array());
				}

				$passwordErrors = $this->CheckPasswordAgainstPolicy($arFields["PASSWORD"], $arPolicy);
				if (!empty($passwordErrors))
				{
					$this->LAST_ERROR .= implode("<br>", $passwordErrors)."<br>";
				}
			}

			if(is_set($arFields, "EMAIL"))
			{
				if(($emailRequired && strlen($arFields["EMAIL"]) < 3) || ($arFields["EMAIL"] <> '' && !check_email($arFields["EMAIL"], true)))
				{
					$this->LAST_ERROR .= GetMessage("WRONG_EMAIL")."<br>";
				}
				elseif(COption::GetOptionString("main", "new_user_email_uniq_check", "N") === "Y")
				{
					if($arFields["EMAIL"] <> '')
					{
						if($ID == false || $arFields["EMAIL"] <> $oldEmail)
						{
							$b = "";
							$o = "";
							$res = CUser::GetList($b, $o, array(
								"=EMAIL" => $arFields["EMAIL"],
								"EXTERNAL_AUTH_ID"=>''
							), array(
								"FIELDS" => array("ID")
							));
							while($ar = $res->Fetch())
							{
								if (intval($ar["ID"]) !== intval($ID))
									$this->LAST_ERROR .= GetMessage("USER_WITH_EMAIL_EXIST", array("#EMAIL#" => htmlspecialcharsbx($arFields["EMAIL"])))."<br>";
							}
						}
					}
				}
			}

			if(is_set($arFields, "PASSWORD") && is_set($arFields, "CONFIRM_PASSWORD") && $arFields["PASSWORD"] !== $arFields["CONFIRM_PASSWORD"])
				$this->LAST_ERROR .= GetMessage("WRONG_CONFIRMATION")."<br>";

			if (is_array($arFields["GROUP_ID"]) && count($arFields["GROUP_ID"]) > 0)
			{
				if (is_array($arFields["GROUP_ID"][0]) && count($arFields["GROUP_ID"][0]) > 0)
				{
					foreach($arFields["GROUP_ID"] as $arGroup)
					{
						if($arGroup["DATE_ACTIVE_FROM"] <> '' && !CheckDateTime($arGroup["DATE_ACTIVE_FROM"]))
						{
							$error = str_replace("#GROUP_ID#", $arGroup["GROUP_ID"], GetMessage("WRONG_DATE_ACTIVE_FROM"));
							$this->LAST_ERROR .= $error."<br>";
						}

						if($arGroup["DATE_ACTIVE_TO"] <> '' && !CheckDateTime($arGroup["DATE_ACTIVE_TO"]))
						{
							$error = str_replace("#GROUP_ID#", $arGroup["GROUP_ID"], GetMessage("WRONG_DATE_ACTIVE_TO"));
							$this->LAST_ERROR .= $error."<br>";
						}
					}
				}
			}
		}

		if(is_set($arFields, "PERSONAL_PHOTO") && $arFields["PERSONAL_PHOTO"]["name"] == '' && $arFields["PERSONAL_PHOTO"]["del"] == '')
			unset($arFields["PERSONAL_PHOTO"]);

		if(is_set($arFields, "PERSONAL_PHOTO"))
		{
			$res = CFile::CheckImageFile($arFields["PERSONAL_PHOTO"]);
			if($res <> '')
				$this->LAST_ERROR .= $res."<br>";
		}

		if(is_set($arFields, "PERSONAL_BIRTHDAY") && $arFields["PERSONAL_BIRTHDAY"] <> '' && !CheckDateTime($arFields["PERSONAL_BIRTHDAY"]))
			$this->LAST_ERROR .= GetMessage("WRONG_PERSONAL_BIRTHDAY")."<br>";

		if(is_set($arFields, "WORK_LOGO") && $arFields["WORK_LOGO"]["name"] == '' && $arFields["WORK_LOGO"]["del"] == '')
			unset($arFields["WORK_LOGO"]);

		if(is_set($arFields, "WORK_LOGO"))
		{
			$res = CFile::CheckImageFile($arFields["WORK_LOGO"]);
			if($res <> '')
				$this->LAST_ERROR .= $res."<br>";
		}

		if(is_set($arFields, "LOGIN"))
		{
			$res = $DB->Query(
				"SELECT 'x' ".
				"FROM b_user ".
				"WHERE LOGIN='".$DB->ForSql($arFields["LOGIN"], 50)."'	".
				"	".($ID===false ? "" : " AND ID<>".intval($ID)).
				"	".(!$bInternal ? "	AND EXTERNAL_AUTH_ID='".$DB->ForSql($arFields["EXTERNAL_AUTH_ID"])."' " : " AND (EXTERNAL_AUTH_ID IS NULL OR ".$DB->Length("EXTERNAL_AUTH_ID")."<=0)")
				);

			if($res->Fetch())
				$this->LAST_ERROR .= str_replace("#LOGIN#", htmlspecialcharsbx($arFields["LOGIN"]), GetMessage("USER_EXIST"))."<br>";
		}

		if(is_object($APPLICATION))
		{
			$APPLICATION->ResetException();

			if($ID===false)
				$events = GetModuleEvents("main", "OnBeforeUserAdd", true);
			else
			{
				$arFields["ID"] = $ID;
				$events = GetModuleEvents("main", "OnBeforeUserUpdate", true);
			}

			foreach($events as $arEvent)
			{
				$bEventRes = ExecuteModuleEventEx($arEvent, array(&$arFields));
				if($bEventRes===false)
				{
					if($err = $APPLICATION->GetException())
						$this->LAST_ERROR .= $err->GetString()." ";
					else
					{
						$APPLICATION->ThrowException("Unknown error");
						$this->LAST_ERROR .= "Unknown error. ";
					}
					break;
				}
			}
		}

		if(is_object($APPLICATION))
			$APPLICATION->ResetException();
		if (!$USER_FIELD_MANAGER->CheckFields("USER", $ID, $arFields))
		{
			if(is_object($APPLICATION) && $APPLICATION->GetException())
			{
				$e = $APPLICATION->GetException();
				$this->LAST_ERROR .= $e->GetString();
				$APPLICATION->ResetException();
			}
			else
			{
				$this->LAST_ERROR .= "Unknown error. ";
			}
		}

		if($this->LAST_ERROR <> '')
			return false;

		return true;
	}

	
	/**
	* <p>Возвращает пользователя по его коду <i>id</i> в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. Статический метод.</p>
	*
	*
	* @param mixed $intid  ID пользователя.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $rsUser = <b>CUser::GetByID</b>(23);
	* $arUser = $rsUser-&gt;Fetch();
	* echo "&lt;pre&gt;"; print_r($arUser); echo "&lt;/pre&gt;";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php">Поля CUser</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getlist.php">CUser::GetList</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getbylogin.php">CUser::GetByLogin</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $USER;

		$userID = (is_object($USER)? intval($USER->GetID()): 0);
		$ID = intval($ID);
		if($userID > 0 && $ID == $userID && is_array(self::$CURRENT_USER))
		{
			$rs = new CDBResult;
			$rs->InitFromArray(self::$CURRENT_USER);
		}
		else
		{
			$rs = CUser::GetList(($by="id"), ($order="asc"), array("ID_EQUAL_EXACT"=>intval($ID)), array("SELECT"=>array("UF_*")));
			if($userID > 0 && $ID == $userID)
			{
				self::$CURRENT_USER = array($rs->Fetch());
				$rs = new CDBResult;
				$rs->InitFromArray(self::$CURRENT_USER);
			}
		}
		return $rs;
	}

	
	/**
	* <p>Возвращает пользователя по его логину <i>login</i> в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. Статический метод.</p>
	*
	*
	* @param string $login  Логин пользователя.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $rsUser = <b>CUser::GetByLogin</b>("admin");
	* $arUser = $rsUser-&gt;Fetch();
	* echo "&lt;pre&gt;"; print_r($arUser); echo "&lt;/pre&gt;";
	* ?&gt;$UserLogin = "user";
	* $rsUser = CUser::GetByLogin($UserLogin);
	* if($arUser = $rsUser-&gt;Fetch())
	* {
	*       echo "<pre bgcolor="#323232" style="padding:5px;">"; print_r(); echo "</pre>";
	* } else {
	*       echo 'Пользователь с логином "'.$UserLogin.'" не найден!';
	* }
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php">Поля CUser</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getlist.php">CUser::GetList</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getbyid.php">CUser::GetByID</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getbylogin.php
	* @author Bitrix
	*/
	public static function GetByLogin($LOGIN)
	{
		$rs = CUser::GetList(($by="id"), ($order="asc"), array("LOGIN_EQUAL_EXACT"=>$LOGIN), array("SELECT"=>array("UF_*")));
		return $rs;
	}

	
	/**
	* <p>Метод изменяет параметры пользователя с идентификатором <i>id</i>. Возвращает "true", если изменение прошло успешно, при возникновении ошибки метод вернет "false", а в свойстве LAST_ERROR объекта будет содержаться текст ошибки. Нестатический метод.</p>
	*
	*
	* @param mixed $intid  ID пользователя.
	*
	* @param array $fields  Массив значений полей вида array("поле"=&gt;"значение" [, ...]).  	В
	* качестве полей могут быть использованы все поля CUser, а также GROUP_ID -
	* массив с ID групп пользователей, в которые входит этот
	* пользователь.
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $user = new CUser;
	* $fields = Array(
	*   "NAME"              =&gt; "Сергей",
	*   "LAST_NAME"         =&gt; "Иванов",
	*   "EMAIL"             =&gt; "ivanov@microsoft.com",
	*   "LOGIN"             =&gt; "ivan",
	*   "LID"               =&gt; "ru",
	*   "ACTIVE"            =&gt; "Y",
	*   "GROUP_ID"          =&gt; array(1,2),
	*   "PASSWORD"          =&gt; "123456",
	*   "CONFIRM_PASSWORD"  =&gt; "123456",
	*   );
	* <b>$user-&gt;Update</b>($ID, $fields);
	* $strError .= $user-&gt;LAST_ERROR;
	* ?&gt;Для обновления пользовательского поля, вида "список" (где 11,12,13 - это ID значений списка.):$user = new CUser;
	* $fields = Array( 
	* "UF_SHOP" =&gt; array(11,12,13), 
	* ); 
	* $user-&gt;Update($ID, $fields);
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php">Поля CUser</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/add.php">CUser::Add</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/delete.php">CUser::Delete</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/update.php
	* @author Bitrix
	*/
	public function Update($ID, $arFields)
	{
		/** @global CUserTypeManager $USER_FIELD_MANAGER */
		global $DB, $USER_FIELD_MANAGER, $CACHE_MANAGER;

		$ID = intval($ID);

		if(!$this->CheckFields($arFields, $ID))
		{
			$Result = false;
			$arFields["RESULT_MESSAGE"] = &$this->LAST_ERROR;
		}
		else
		{
			unset($arFields["ID"]);

			if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
				$arFields["ACTIVE"]="N";

			if(is_set($arFields, "PERSONAL_GENDER") && ($arFields["PERSONAL_GENDER"]!="M" && $arFields["PERSONAL_GENDER"]!="F"))
				$arFields["PERSONAL_GENDER"] = "";

			if(is_set($arFields, "PASSWORD"))
			{
				$original_pass = $arFields["PASSWORD"];
				$salt = randString(8, array(
					"abcdefghijklnmopqrstuvwxyz",
					"ABCDEFGHIJKLNMOPQRSTUVWXYZ",
					"0123456789",
					",.<>/?;:[]{}\\|~!@#\$%^&*()-_+=",
				));
				$arFields["PASSWORD"] = $salt.md5($salt.$arFields["PASSWORD"]);
				$rUser = CUser::GetByID($ID);
				if($arUser = $rUser->Fetch())
				{
					if($arUser["PASSWORD"] != $arFields["PASSWORD"])
						$DB->Query("DELETE FROM b_user_stored_auth WHERE USER_ID=".$ID);
				}
				if(COption::GetOptionString("main", "event_log_password_change", "N") === "Y")
					CEventLog::Log("SECURITY", "USER_PASSWORD_CHANGED", "main", $ID);
				//$arFields["STORED_HASH"] = CUser::GetPasswordHash($arFields["PASSWORD"]);
			}
			unset($arFields["STORED_HASH"]);

			$checkword = '';
			if(!is_set($arFields, "CHECKWORD"))
			{
				if(is_set($arFields, "PASSWORD") || is_set($arFields, "EMAIL") || is_set($arFields, "LOGIN")  || is_set($arFields, "ACTIVE"))
				{
					$salt =  randString(8);
					$checkword = md5(CMain::GetServerUniqID().uniqid());
					$arFields["CHECKWORD"] = $salt.md5($salt.$checkword);
				}
			}
			else
			{
				$salt =  randString(8);
				$checkword = $arFields["CHECKWORD"];
				$arFields["CHECKWORD"] = $salt.md5($salt.$checkword);
			}

			if(is_set($arFields, "CHECKWORD") && !is_set($arFields, "CHECKWORD_TIME"))
				$arFields["~CHECKWORD_TIME"] = $DB->CurrentTimeFunction();

			if(is_set($arFields, "WORK_COUNTRY"))
				$arFields["WORK_COUNTRY"] = intval($arFields["WORK_COUNTRY"]);

			if(is_set($arFields, "PERSONAL_COUNTRY"))
				$arFields["PERSONAL_COUNTRY"] = intval($arFields["PERSONAL_COUNTRY"]);

			if (
				array_key_exists("PERSONAL_PHOTO", $arFields)
				&& is_array($arFields["PERSONAL_PHOTO"])
				&& (
					!array_key_exists("MODULE_ID", $arFields["PERSONAL_PHOTO"])
					|| $arFields["PERSONAL_PHOTO"]["MODULE_ID"] == ''
				)
			)
			{
				$arFields["PERSONAL_PHOTO"]["MODULE_ID"] = "main";
			}

			CFile::SaveForDB($arFields, "PERSONAL_PHOTO", "main");

			if (
				array_key_exists("WORK_LOGO", $arFields)
				&& is_array($arFields["WORK_LOGO"])
				&& (
					!array_key_exists("MODULE_ID", $arFields["WORK_LOGO"])
					|| $arFields["WORK_LOGO"]["MODULE_ID"] == ''
				)
			)
			{
				$arFields["WORK_LOGO"]["MODULE_ID"] = "main";
			}

			CFile::SaveForDB($arFields, "WORK_LOGO", "main");

			$strUpdate = $DB->PrepareUpdate("b_user", $arFields);

			if(!is_set($arFields, "TIMESTAMP_X"))
				$strUpdate .= ($strUpdate <> ""? ",":"")." TIMESTAMP_X = ".$DB->GetNowFunction();

			$strSql = "UPDATE b_user SET ".$strUpdate." WHERE ID=".$ID;

			$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

			$USER_FIELD_MANAGER->Update("USER", $ID, $arFields);

			if(COption::GetOptionString("main", "event_log_user_edit", "N") === "Y")
			{
				$res_log["user"] = ($arFields["NAME"] != "" || $arFields["LAST_NAME"] != "") ? trim($arFields["NAME"]." ".$arFields["LAST_NAME"]) : $arFields["LOGIN"];
				CEventLog::Log("SECURITY", "USER_EDIT", "main", $ID, serialize($res_log));
			}

			if(is_set($arFields, "GROUP_ID"))
				CUser::SetUserGroup($ID, $arFields["GROUP_ID"]);

			//update digest hash for http digest authorization
			if(isset($arUser["ID"]) && is_set($arFields, "PASSWORD") && COption::GetOptionString('main', 'use_digest_auth', 'N') == 'Y')
			{
				/** @noinspection PhpUndefinedVariableInspection */
				CUser::UpdateDigest($arUser["ID"], $original_pass);
			}

			$Result = true;
			$arFields["CHECKWORD"] = $checkword;

			//update session information and cache for current user
			global $USER;
			if(is_object($USER) && $USER->GetID() == $ID)
			{
				static $arSessFields = array(
					'LOGIN'=>'LOGIN', 'EMAIL'=>'EMAIL', 'TITLE'=>'TITLE', 'FIRST_NAME'=>'NAME', 'SECOND_NAME'=>'SECOND_NAME', 'LAST_NAME'=>'LAST_NAME',
					'PERSONAL_PHOTO'=>'PERSONAL_PHOTO', 'PERSONAL_GENDER'=>'PERSONAL_GENDER', 'AUTO_TIME_ZONE'=>'AUTO_TIME_ZONE', 'TIME_ZONE'=>'TIME_ZONE');
				foreach($arSessFields as $key => $val)
					if(isset($arFields[$val]))
						$USER->SetParam($key, $arFields[$val]);
				$name = $USER->GetParam("FIRST_NAME");
				$last_name = $USER->GetParam("LAST_NAME");
				$USER->SetParam("NAME", $name.($name == '' || $last_name == ''? "":" ").$last_name);

				//cache for GetByID()
				self::$CURRENT_USER = false;
			}
		}

		$arFields["ID"] = $ID;
		$arFields["RESULT"] = &$Result;

		foreach (GetModuleEvents("main", "OnAfterUserUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arFields));

		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			$CACHE_MANAGER->ClearByTag("USER_CARD_".intval($ID / TAGGED_user_card_size));
			$CACHE_MANAGER->ClearByTag("USER_CARD");

			static $arNameFields = array("NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "EMAIL", "PERSONAL_GENDER", "PERSONAL_PHOTO", "WORK_POSITION", "PERSONAL_PROFESSION", "PERSONAL_BIRTHDAY", "TITLE", "EXTERNAL_AUTH_ID");
			$bClear = false;
			foreach($arNameFields as $val)
			{
				if(isset($arFields[$val]))
				{
					$bClear = true;
					break;
				}
			}
			if ($bClear)
			{
				$CACHE_MANAGER->ClearByTag("USER_NAME_".$ID);
				$CACHE_MANAGER->ClearByTag("USER_NAME");
			}
		}

		return $Result;
	}

	
	/**
	* <p>Метод устанавливает привязку пользователя <i>user_id</i> к группам <i>groups</i>. Привязка к группам сохраняется в базе данных, но не влияет на уже авторизованного посетителя <i>user_id</i>. Нестатический метод.</p>
	*
	*
	* @param int $user_id  Идентификатор пользователя.
	*
	* @param array $groups  Массив со значениями идентификаторов групп пользователей.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // привязка пользователя с кодом 10 дополнительно к группе c кодом 5
	* $arGroups = <b>CUser::GetUserGroup</b>(10);
	* $arGroups[] = 5;
	* <b>CUser::SetUserGroup</b>(10, $arGroups);
	* ?&gt;Если требуется изменить также период активности в группе, массив groups будет иметь вид:$arGroups = array(
	*   array(
	*   'GROUP_ID' =&gt; 5,
	*   'DATE_ACTIVE_FROM'=&gt;'01.02.2009',
	*   'DATE_ACTIVE_TO'=&gt;'02.02.2009'
	*   ),
	*   array(
	*   'GROUP_ID' =&gt; 6,
	*   'DATE_ACTIVE_FROM'=&gt;'01.03.2009',
	*   'DATE_ACTIVE_TO'=&gt;'02.03.2009'
	*   )
	* );Добавление группы "одной строкой" (где <code>array(4,5,6)</code> - массив добавляемых групп.):CUser::SetUserGroup($userID, array_merge(CUser::GetUserGroup($userID), array(4,5,6)));
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getusergroup.php">CUser::GetUserGroup</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/setusergroup.php
	* @author Bitrix
	*/
	public static function SetUserGroup($USER_ID, $arGroups)
	{
		global $DB;

		$USER_ID = intval($USER_ID);

		if ($USER_ID === 0)
		{
			return false;
		}

		$log = (COption::GetOptionString("main", "event_log_user_groups", "N") === "Y");
		if($log)
		{
			//remember previous groups of the user
			$aPrevGroups = array();
			$res = CUser::GetUserGroupList($USER_ID);
			while($res_arr = $res->Fetch())
				if($res_arr["GROUP_ID"] <> 2)
					$aPrevGroups[] = $res_arr["GROUP_ID"];
		}

		$DB->Query("DELETE FROM b_user_group WHERE USER_ID=".$USER_ID, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		if(is_array($arGroups))
		{
			$arTmp = array();
			foreach($arGroups as $group)
			{
				if(!is_array($group))
				{
					$group = array("GROUP_ID" => $group);
				}

				$group_id = intval($group["GROUP_ID"]);
				if($group_id > 0 && $group_id <> 2 && !isset($arTmp[$group_id]))
				{
					$arInsert = $DB->PrepareInsert("b_user_group", $group);
					$strSql = "
						INSERT INTO b_user_group (
							USER_ID, ".$arInsert[0]."
						) VALUES (
							".$USER_ID.",
							".$arInsert[1]."
						)
					";
					$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
					$arTmp[$group_id] = true;
				}
			}
			$arGroups = array_keys($arTmp);
		}
		else
		{
			$arGroups = array();
		}
		self::clearUserGroupCache($USER_ID);

		foreach (GetModuleEvents("main", "OnAfterSetUserGroup", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array("USER_ID"=>$USER_ID, "GROUPS"=>$arGroups));
		}

		if($log)
		{
			//compare previous groups of the user with new
			/** @noinspection PhpUndefinedVariableInspection */
			$aDiff = array_diff($aPrevGroups, $arGroups);
			if(empty($aDiff))
				$aDiff = array_diff($arGroups, $aPrevGroups);
			if(!empty($aDiff))
			{
				sort($aPrevGroups);
				sort($arGroups);
				$UserName = '';
				$rsUser = CUser::GetByID($USER_ID);
				if($arUser = $rsUser->GetNext())
					$UserName = ($arUser["NAME"] != "" || $arUser["LAST_NAME"] != "") ? trim($arUser["NAME"]." ".$arUser["LAST_NAME"]) : $arUser["LOGIN"];
				$res_log = array(
					"groups" => "(".implode(", ", $aPrevGroups).") => (".implode(", ", $arGroups).")",
					"user" => $UserName
				);
				CEventLog::Log("SECURITY", "USER_GROUP_CHANGED", "main", $USER_ID, serialize($res_log));
			}
		}
		return null;
	}

	
	/**
	* <p>Возвращает количество всех пользователей зарегистрированных на сайте. Нестатический метод.</p>
	*
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* echo "На сегодняшний день у нас зарегистрировалось пользователей: ".<b>CUser::GetCount</b>();
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getcount.php
	* @author Bitrix
	*/
	public static function GetCount()
	{
		global $DB;
		$r = $DB->Query("SELECT COUNT('x') as C FROM b_user");
		$r = $r->Fetch();
		return Intval($r["C"]);
	}

	
	/**
	* <p>Метод удаляет пользователя. Возвращается объект <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. Нестатический метод.</p>
	*
	*
	* @param mixed $intid  ID пользователя.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* if (<b>CUser::Delete</b>(5)) echo "Пользователь удален.";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/add.php">CUser::Add</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/update.php">CUser::Update</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserdelete.php">Событие "OnBeforeUserDelete"</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/events/onuserdelete.php">Событие "OnUserDelete"</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		/** @global CMain $APPLICATION */
		/** @global CUserTypeManager $USER_FIELD_MANAGER */
		global $DB, $APPLICATION, $USER_FIELD_MANAGER, $CACHE_MANAGER;

		$ID = intval($ID);

		@set_time_limit(600);

		$rsUser = $DB->Query("SELECT ID, LOGIN, NAME, LAST_NAME FROM b_user WHERE ID=".$ID." AND ID<>1");
		$arUser = $rsUser->Fetch();
		if(!$arUser)
			return false;

		foreach(GetModuleEvents("main", "OnBeforeUserDelete", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array($ID))===false)
			{
				$err = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
				if($ex = $APPLICATION->GetException())
					$err .= ': '.$ex->GetString();
				$APPLICATION->throwException($err);
				if(COption::GetOptionString("main", "event_log_user_delete", "N") === "Y")
				{
					$UserName = ($arUser["NAME"] != "" || $arUser["LAST_NAME"] != "") ? trim($arUser["NAME"]." ".$arUser["LAST_NAME"]) : $arUser["LOGIN"];
					$res_log = array(
						"user" => $UserName,
						"err" => $err
					);
					CEventLog::Log("SECURITY", "USER_DELETE", "main", $ID, serialize($res_log));
				}
				return false;
			}
		}

		foreach(GetModuleEvents("main", "OnUserDelete", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array($ID))===false)
			{
				$err = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
				if($ex = $APPLICATION->GetException())
					$err .= ': '.$ex->GetString();
				$APPLICATION->throwException($err);
				if(COption::GetOptionString("main", "event_log_user_delete", "N") === "Y")
				{
					$UserName = ($arUser["NAME"] != "" || $arUser["LAST_NAME"] != "") ? trim($arUser["NAME"]." ".$arUser["LAST_NAME"]) : $arUser["LOGIN"];
					$res_log = array(
						"user" => $UserName,
						"err" => $err
					);
					CEventLog::Log("SECURITY", "USER_DELETE", "main", $ID, serialize($res_log));
				}
				return false;
			}
		}

		$strSql = "SELECT F.ID FROM	b_user U, b_file F WHERE U.ID='$ID' and (F.ID=U.PERSONAL_PHOTO or F.ID=U.WORK_LOGO)";
		$z = $DB->Query($strSql, false, "FILE: ".__FILE__." LINE:".__LINE__);
		while ($zr = $z->Fetch())
			CFile::Delete($zr["ID"]);

		if(!$DB->Query("DELETE FROM b_user_group WHERE USER_ID=".$ID))
			return false;

		if(!$DB->Query("DELETE FROM b_user_digest WHERE USER_ID=".$ID))
			return false;

		if(!$DB->Query("DELETE FROM b_app_password WHERE USER_ID=".$ID))
			return false;

		$USER_FIELD_MANAGER->Delete("USER", $ID);

		if(COption::GetOptionString("main", "event_log_user_delete", "N") === "Y")
		{
			$res_log["user"] = ($arUser["NAME"] != "" || $arUser["LAST_NAME"] != "") ? trim($arUser["NAME"]." ".$arUser["LAST_NAME"]) : $arUser["LOGIN"];
			CEventLog::Log("SECURITY", "USER_DELETE", "main", $arUser["LOGIN"], serialize($res_log));
		}

		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			$CACHE_MANAGER->ClearByTag("USER_CARD_".intval($ID / TAGGED_user_card_size));
			$CACHE_MANAGER->ClearByTag("USER_CARD");
			$CACHE_MANAGER->ClearByTag("USER_NAME_".$ID);
			$CACHE_MANAGER->ClearByTag("USER_NAME");
		}
		self::clearUserGroupCache($ID);

		$res = $DB->Query("DELETE FROM b_user WHERE ID=".$ID." AND ID<>1");

		if($res)
		{
			foreach(GetModuleEvents("main", "OnAfterUserDelete", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($ID));
			}
		}

		return $res;
	}

	
	/**
	* <p>Возвращает список всех источников внешней авторизации. Чтобы зарегистрировать свой внешний источник авторизации, необходимо установить обработчик события <a href="http://dev.1c-bitrix.ru/api_help/main/events/onexternalauthlist.php">OnExternalAuthList</a>. Нестатический метод.</p>
	*
	*
	* @return CDBResult <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a><i>ID</i><i>NAME</i>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$rExtAuth = <b>CUser::GetExternalAuthList</b>();<br>if($arExtAuth = $rExtAuth-&gt;GetNext()):<br>    ?&gt;&lt;select name="EXTERNAL_AUTH_ID"&gt;<br>    &lt;option value=""&gt;(внутренняя авторизация)&lt;/option&gt;<br>    &lt;?do{?&gt;<br>        &lt;option value="&lt;?=$arExtAuth['ID']?&gt;"&lt;?<br>            if($str_EXTERNAL_AUTH_ID==$arExtAuth['ID']) echo ' selected';<br>        ?&gt;&gt;&lt;?=$arExtAuth['NAME']?&gt;&lt;/option&gt;<br>    &lt;?}while($arExtAuth = $rExtAuth-&gt;GetNext());?&gt;<br>    &lt;/select&gt;<br>&lt;?endif?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3574" >Внешняя
	* авторизация</a></li>     <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/events/onexternalauthlist.php">OnExternalAuthList</a> </li>     <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">Класс CDBResult</a> </li>  </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getexternalauthlist.php
	* @author Bitrix
	*/
	public static function GetExternalAuthList()
	{
		$arAll = array();
		foreach(GetModuleEvents("main", "OnExternalAuthList", true) as $arEvent)
		{
			$arRes = ExecuteModuleEventEx($arEvent);
			if(is_array($arRes))
			{
				foreach($arRes as $v)
				{
					$arAll[] = $v;
				}
			}
		}

		$result = new CDBResult;
		$result->InitFromArray($arAll);
		return $result;
	}

	public static function GetGroupPolicy($iUserId)
	{
		global $DB;
		static $arPOLICY_CACHE;
		if(!is_array($arPOLICY_CACHE))
			$arPOLICY_CACHE = array();
		$CACHE_ID = md5(serialize($iUserId));
		if(array_key_exists($CACHE_ID, $arPOLICY_CACHE))
			return $arPOLICY_CACHE[$CACHE_ID];

		global $BX_GROUP_POLICY;
		$arPolicy = $BX_GROUP_POLICY;
		if($arPolicy["SESSION_TIMEOUT"]<=0)
			$arPolicy["SESSION_TIMEOUT"] = ini_get("session.gc_maxlifetime")/60;

		$arSql = array();
		$arSql[] =
			"SELECT G.SECURITY_POLICY ".
			"FROM b_group G ".
			"WHERE G.ID=2";

		if(is_array($iUserId))
		{
			$arGroups = array();
			foreach($iUserId as $value)
			{
				$value = intval($value);
				if($value > 0 && $value != 2)
					$arGroups[$value] = $value;
			}
			if(count($arGroups) > 0)
			{
				$arSql[] =
					"SELECT G.ID GROUP_ID, G.SECURITY_POLICY ".
					"FROM b_group G ".
					"WHERE G.ID in (".implode(", ", $arGroups).")";
			}
		}
		elseif(intval($iUserId) > 0)
		{
			$arSql[] =
				"SELECT UG.GROUP_ID, G.SECURITY_POLICY ".
				"FROM b_user_group UG, b_group G ".
				"WHERE UG.USER_ID = ".intval($iUserId)." ".
				"	AND UG.GROUP_ID = G.ID ".
				"	AND ((UG.DATE_ACTIVE_FROM IS NULL) OR (UG.DATE_ACTIVE_FROM <= ".$DB->CurrentTimeFunction().")) ".
				"	AND ((UG.DATE_ACTIVE_TO IS NULL) OR (UG.DATE_ACTIVE_TO >= ".$DB->CurrentTimeFunction().")) ";
		}

		foreach($arSql as $strSql)
		{
			$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			while($ar = $res->Fetch())
			{
				if($ar["SECURITY_POLICY"])
					$arGroupPolicy = unserialize($ar["SECURITY_POLICY"]);
				else
					continue;

				if(!is_array($arGroupPolicy))
					continue;

				foreach($arGroupPolicy as $key=>$val)
				{
					switch($key)
					{
					case "STORE_IP_MASK":
					case "SESSION_IP_MASK":
						if($arPolicy[$key]<$val)
							$arPolicy[$key] = $val;
						break;
					case "SESSION_TIMEOUT":
						if($arPolicy[$key]<=0 || $arPolicy[$key]>$val)
							$arPolicy[$key] = $val;
						break;
					case "PASSWORD_LENGTH":
						if($arPolicy[$key]<=0 || $arPolicy[$key] < $val)
							$arPolicy[$key] = $val;
						break;
					case "PASSWORD_UPPERCASE":
					case "PASSWORD_LOWERCASE":
					case "PASSWORD_DIGITS":
					case "PASSWORD_PUNCTUATION":
						if($val === "Y")
							$arPolicy[$key] = "Y";
						break;
					case "LOGIN_ATTEMPTS":
						if($val > 0 && ($arPolicy[$key] <= 0 || $arPolicy[$key] > $val))
							$arPolicy[$key] = $val;
						break;
					default:
						if($arPolicy[$key]>$val)
							$arPolicy[$key] = $val;
					}
				}
			}
			if($arPolicy["PASSWORD_LENGTH"] === false)
				$arPolicy["PASSWORD_LENGTH"] = 6;
		}
		$ar = array(
			GetMessage("MAIN_GP_PASSWORD_LENGTH", array("#LENGTH#" => intval($arPolicy["PASSWORD_LENGTH"])))
		);
		if($arPolicy["PASSWORD_UPPERCASE"] === "Y")
			$ar[] = GetMessage("MAIN_GP_PASSWORD_UPPERCASE");
		if($arPolicy["PASSWORD_LOWERCASE"] === "Y")
			$ar[] = GetMessage("MAIN_GP_PASSWORD_LOWERCASE");
		if($arPolicy["PASSWORD_DIGITS"] === "Y")
			$ar[] = GetMessage("MAIN_GP_PASSWORD_DIGITS");
		if($arPolicy["PASSWORD_PUNCTUATION"] === "Y")
			$ar[] = GetMessage("MAIN_GP_PASSWORD_PUNCTUATION");
		$arPolicy["PASSWORD_REQUIREMENTS"] = implode(", ", $ar).".";

		if(count($arPOLICY_CACHE)<=10)
			$arPOLICY_CACHE[$CACHE_ID] = $arPolicy;

		return $arPolicy;
	}

	public static function CheckStoredHash($iUserId, $sHash, $bTempHashOnly=false)
	{
		global $DB;
		$arPolicy = CUser::GetGroupPolicy($iUserId);

		$cnt = 0;
		$auth_id = false;
		$site_format = CSite::GetDateFormat();

		CTimeZone::Disable();
		$strSql =
			"SELECT A.*, ".
			"	".$DB->DateToCharFunction("A.DATE_REG", "FULL")." as DATE_REG, ".
			"	".$DB->DateToCharFunction("A.LAST_AUTH", "FULL")." as LAST_AUTH ".
			"FROM b_user_stored_auth A ".
			"WHERE A.USER_ID = ".intval($iUserId)." ".
			"ORDER BY A.LAST_AUTH DESC";
		$res = $DB->Query($strSql);
		CTimeZone::Enable();

		while($ar = $res->Fetch())
		{
			if($ar["TEMP_HASH"]=="N")
				$cnt++;
			if($arPolicy["MAX_STORE_NUM"] < $cnt
				|| ($ar["TEMP_HASH"]=="N" && mktime()-$arPolicy["STORE_TIMEOUT"]*60 > MakeTimeStamp($ar["LAST_AUTH"], $site_format))
				|| ($ar["TEMP_HASH"]=="Y" && mktime()-$arPolicy["SESSION_TIMEOUT"]*60 > MakeTimeStamp($ar["LAST_AUTH"], $site_format))
			)
			{
				$DB->Query("DELETE FROM b_user_stored_auth WHERE ID=".$ar["ID"]);
			}
			elseif(!$auth_id)
			{
				//for domain spreaded external auth we should check only temporary hashes
				if($bTempHashOnly == false || $ar["TEMP_HASH"] == "Y")
				{
					$remote_net = ip2long($arPolicy["STORE_IP_MASK"]) & ip2long($_SERVER["REMOTE_ADDR"]);
					$stored_net = ip2long($arPolicy["STORE_IP_MASK"]) & (float)$ar["IP_ADDR"];
					if($sHash == $ar["STORED_HASH"] && $remote_net == $stored_net)
						$auth_id = $ar["ID"];
				}
			}
		}
		return $auth_id;
	}


	public function GetAllOperations($arGroups = false)
	{
		global $DB;

		if ($arGroups)
		{
			$userGroups = "2,".implode(",", array_map("intval", $arGroups));
		}
		else
		{
			$userGroups = $this->GetGroups();
		}

		$sql_str = "
			SELECT O.NAME OPERATION_NAME
			FROM b_group_task GT
				INNER JOIN b_task_operation T_O ON T_O.TASK_ID=GT.TASK_ID
				INNER JOIN b_operation O ON O.ID=T_O.OPERATION_ID
			WHERE GT.GROUP_ID IN(".$userGroups.")
			UNION
			SELECT O.NAME OPERATION_NAME
			FROM b_option OP
				INNER JOIN b_task_operation T_O ON T_O.TASK_ID=".$DB->ToChar("OP.VALUE", 18)."
				INNER JOIN b_operation O ON O.ID=T_O.OPERATION_ID
			WHERE OP.NAME='GROUP_DEFAULT_TASK'
			UNION
			SELECT O.NAME OPERATION_NAME
			FROM b_option OP
				INNER JOIN b_task T ON T.MODULE_ID=OP.MODULE_ID AND T.BINDING='module' AND T.LETTER=".$DB->ToChar("OP.VALUE", 1)." AND T.SYS='Y'
				INNER JOIN b_task_operation T_O ON T_O.TASK_ID=T.ID
				INNER JOIN b_operation O ON O.ID=T_O.OPERATION_ID
			WHERE OP.NAME='GROUP_DEFAULT_RIGHT'
		";

		$z = $DB->Query($sql_str, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		$arr = array();
		while($r = $z->Fetch())
			$arr[$r['OPERATION_NAME']] = $r['OPERATION_NAME'];

		return $arr;
	}

	public function CanDoOperation($op_name, $user_id = 0)
	{
		if ($user_id > 0)
		{
			$arGroups = array();
			$rsGroups = $this->GetUserGroupEx($user_id);
			while ($group = $rsGroups->Fetch())
			{
				$arGroups[] = $group["GROUP_ID"];
			}
			if (!$arGroups)
				return false;

			$op = $this->GetAllOperations($arGroups);
			return isset($op[$op_name]);
		}
		else
		{
			if ($this->IsAdmin())
				return true;

			if(!isset($_SESSION["SESS_OPERATIONS"]))
				$_SESSION["SESS_OPERATIONS"] = $this->GetAllOperations();

			return isset($_SESSION["SESS_OPERATIONS"][$op_name]);
		}
	}

	public static function GetFileOperations($arPath, $arGroups=false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$ar = $APPLICATION->GetFileAccessPermission($arPath, $arGroups, true);
		$arFileOperations = array();

		for ($i = 0, $len = count($ar); $i < $len; $i++)
			$arFileOperations = array_merge($arFileOperations, CTask::GetOperations($ar[$i], true));
		$arFileOperations = array_values(array_unique($arFileOperations));

		return $arFileOperations;
	}


	
	/**
	* <p>Выполнение операций над файлом. Нестатический метод.</p>
	*
	*
	* @param array $op_name  Операция
	*
	* @param array $arPath  Путь. Массив вида: <pre class="syntax">($arPath = Array($site, $path);)</pre>
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $USER-&gt;CanDoFileOperation('fm_create_new_file', $arPath)
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/candofileoperation.php
	* @author Bitrix
	*/
	public function CanDoFileOperation($op_name, $arPath)
	{
		global $APPLICATION, $USER;

		if ($this->IsAdmin())
			return true;

		if(!isset($APPLICATION->FILEMAN_OPERATION_CACHE))
			$APPLICATION->FILEMAN_OPERATION_CACHE = array();

		$k = addslashes($arPath[0].'|'.$arPath[1]);
		if(array_key_exists($k, $APPLICATION->FILEMAN_OPERATION_CACHE))
		{
			$arFileOperations = $APPLICATION->FILEMAN_OPERATION_CACHE[$k];
		}
		else
		{
			$arFileOperations = $this->GetFileOperations($arPath);
			$APPLICATION->FILEMAN_OPERATION_CACHE[$k] = $arFileOperations;
		}

		$arAlowedOperations = array('fm_delete_file','fm_rename_folder','fm_view_permission');
		if(substr($arPath[1], -10)=="/.htaccess" && !$USER->CanDoOperation('edit_php') && !in_array($op_name,$arAlowedOperations))
			return false;
		if(substr($arPath[1], -12)=="/.access.php")
			return false;

		return in_array($op_name, $arFileOperations);
	}

	public static function UserTypeRightsCheck($entity_id)
	{
		global $USER;

		if($entity_id == "USER" && $USER->CanDoOperation('edit_other_settings'))
		{
			return "W";
		}
		else
			return "D";
	}

	public function CanAccess($arCodes)
	{
		if(!is_array($arCodes) || empty($arCodes))
			return false;

		if(in_array('G2', $arCodes))
			return true;

		if($this->IsAuthorized() && in_array('AU', $arCodes))
			return true;

		$bEmpty = true;
		foreach($arCodes as $code)
		{
			if(trim($code) <> '')
			{
				$bEmpty = false;
				break;
			}
		}

		if($bEmpty)
			return false;

		$res = CAccess::GetUserCodes($this->GetID(), array("ACCESS_CODE"=>$arCodes));
		if($res->Fetch())
			return true;

		return false;
	}

	public function GetAccessCodes()
	{
		if(!$this->IsAuthorized())
			return array('G2');

		static $arCodes = array();

		$USER_ID = intval($this->GetID());

		if(!array_key_exists($USER_ID, $arCodes))
		{
			$arCodes[$USER_ID] = CAccess::GetUserCodesArray($USER_ID);

			if($this->IsAuthorized())
				$arCodes[$USER_ID][] = "AU";
		}

		return $arCodes[$USER_ID];
	}

	public static function CleanUpAgent()
	{
		$bTmpUser = false;
		if (!isset($GLOBALS["USER"]) || !is_object($GLOBALS["USER"]))
		{
			$bTmpUser = true;
			$GLOBALS["USER"] = new CUser;
		}

		$cleanup_days = COption::GetOptionInt("main", "new_user_registration_cleanup_days", 7);
		if($cleanup_days > 0 && COption::GetOptionString("main", "new_user_registration_email_confirmation", "N") === "Y")
		{
			$arDate = localtime(time());
			$date = mktime(0, 0, 0, $arDate[4]+1, $arDate[3]-$cleanup_days, 1900+$arDate[5]);
			$arFilter = array(
				"!CONFIRM_CODE" => false,
				"ACTIVE" => "N",
				"DATE_REGISTER_2" => ConvertTimeStamp($date),
			);
			$rsUsers = CUser::GetList(($by=""), ($order=""), $arFilter);
			while($arUser = $rsUsers->Fetch())
			{
				CUser::Delete($arUser["ID"]);
			}
		}
		if ($bTmpUser)
		{
			unset($GLOBALS["USER"]);
		}

		return "CUser::CleanUpAgent();";
	}

	public static function GetActiveUsersCount()
	{
		global $DB;

		$q = "SELECT COUNT(ID) as C FROM b_user WHERE ACTIVE = 'Y' AND LAST_LOGIN IS NOT NULL";
		if (IsModuleInstalled("intranet"))
			$q = "SELECT COUNT(U.ID) as C FROM b_user U WHERE U.ACTIVE = 'Y' AND U.LAST_LOGIN IS NOT NULL AND EXISTS(SELECT 'x' FROM b_utm_user UF, b_user_field F WHERE F.ENTITY_ID = 'USER' AND F.FIELD_NAME = 'UF_DEPARTMENT' AND UF.FIELD_ID = F.ID AND UF.VALUE_ID = U.ID AND UF.VALUE_INT IS NOT NULL AND UF.VALUE_INT <> 0)";

		$dbRes = $DB->Query($q, true);
		if ($dbRes && ($arRes = $dbRes->Fetch()))
			return $arRes["C"];
		else
			return 0;
	}

	
	/**
	* <p>Метод обновляет LAST_ACTIVITY_DATE. Нестатический метод.</p>
	*
	*
	* @param mixed $mixedid  Идентификатор пользователя, у которого обновляется LAST_ACTIVITY_DATE.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cuser/setlastactivitydate.php
	* @author Bitrix
	*/
	public static function SetLastActivityDate($ID)
	{
		self::SetLastActivityDateByArray(array($ID));
	}

	public static function SetLastActivityDateByArray($arUsers)
	{
		global $DB;

		if (!is_array($arUsers) || count($arUsers) <= 0)
			return false;

		$strSqlPrefix = "UPDATE b_user SET ".
			"TIMESTAMP_X = ".(strtoupper($DB->type) == "ORACLE"? "NULL":"TIMESTAMP_X").", ".
			"LAST_ACTIVITY_DATE = ".$DB->CurrentTimeFunction()." WHERE ID IN (";
		$strSqlPostfix = ")";
		$maxValuesLen = 2048;
		$strSqlValues = "";

		$arUsers = array_map("intval", $arUsers);
		foreach($arUsers as $userId)
		{
			$strSqlValues .= ",$userId";
			if(strlen($strSqlValues) > $maxValuesLen)
			{
				$DB->Query($strSqlPrefix.substr($strSqlValues, 1).$strSqlPostfix, false, "", array("ignore_dml"=>true));
				$strSqlValues = "";
			}
		}

		if(strlen($strSqlValues) > 0)
		{
			$DB->Query($strSqlPrefix.substr($strSqlValues, 1).$strSqlPostfix, false, "", array("ignore_dml"=>true));
		}

		$event = new \Bitrix\Main\Event("main", "OnUserSetLastActivityDate", array($arUsers));
		$event->send();

		return true;
	}

	public static function SearchUserByName($arName, $email = "", $bLoginMode = false)
	{
		global $DB;

		$arNameReady = array();
		foreach ($arName as $s)
		{
			$s = Trim($s);
			if (StrLen($s) > 0)
				$arNameReady[] = $s;
		}

		if (Count($arNameReady) <= 0)
			return false;

		$strSqlWhereEMail = ((StrLen($email) > 0) ? " AND upper(U.EMAIL) = upper('".$DB->ForSql($email)."') " : "");

		if ($bLoginMode)
		{
			if (count($arNameReady) > 3)
			{
				$strSql =
					"SELECT U.ID, U.NAME, U.LAST_NAME, U.SECOND_NAME, U.LOGIN, U.EMAIL ".
					"FROM b_user U ".
					"WHERE (";
				$bFirst = true;
				for ($i = 0; $i < 4; $i++)
				{
					for ($j = 0; $j < 4; $j++)
					{
						if ($i == $j)
							continue;

						for ($k = 0; $k < 4; $k++)
						{
							if ($i == $k || $j == $k)
								continue;

							for ($l = 0; $l < 4; $l++)
							{
								if ($i == $l || $j == $l || $k == $l)
									continue;

								if (!$bFirst)
									$strSql .= " OR ";

								$strSql .= "(U.NAME IS NOT NULL AND upper(U.NAME) LIKE upper('".$DB->ForSql($arNameReady[$i])."%') ".
									"AND U.LAST_NAME IS NOT NULL AND upper(U.LAST_NAME) LIKE upper('".$DB->ForSql($arNameReady[$j])."%') ".
									"AND U.LOGIN IS NOT NULL AND upper(U.LOGIN) LIKE upper('".$DB->ForSql($arNameReady[$k])."%') ".
									"AND U.EMAIL IS NOT NULL AND upper(U.EMAIL) LIKE upper('".$DB->ForSql($arNameReady[$l])."%'))";

								$bFirst = false;
							}
						}
					}
				}
				$strSql .= ")";
			}
			elseif (Count($arNameReady) == 3)
			{
				$strSql =
					"SELECT U.ID, U.NAME, U.LAST_NAME, U.SECOND_NAME, U.LOGIN, U.EMAIL ".
					"FROM b_user U ".
					"WHERE (";
				$bFirst = true;
				for ($i = 0; $i < 3; $i++)
				{
					for ($j = 0; $j < 3; $j++)
					{
						if ($i == $j)
							continue;

						for ($k = 0; $k < 3; $k++)
						{
							if ($i == $k || $j == $k)
								continue;

							if (!$bFirst)
								$strSql .= " OR ";

							$strSql .= "(";
							$strSql .= "(U.NAME IS NOT NULL AND upper(U.NAME) LIKE upper('".$DB->ForSql($arNameReady[$i])."%') ".
								"AND U.LAST_NAME IS NOT NULL AND upper(U.LAST_NAME) LIKE upper('".$DB->ForSql($arNameReady[$j])."%') ".
								"AND U.LOGIN IS NOT NULL AND upper(U.LOGIN) LIKE upper('".$DB->ForSql($arNameReady[$k])."%'))";
							$strSql .= " OR ";
							$strSql .= "(U.NAME IS NOT NULL AND upper(U.NAME) LIKE upper('".$DB->ForSql($arNameReady[$i])."%') ".
								"AND U.LAST_NAME IS NOT NULL AND upper(U.LAST_NAME) LIKE upper('".$DB->ForSql($arNameReady[$j])."%') ".
								"AND U.EMAIL IS NOT NULL AND upper(U.EMAIL) LIKE upper('".$DB->ForSql($arNameReady[$k])."%'))";
							$strSql .= " OR ";
							$strSql .= "(U.NAME IS NOT NULL AND upper(U.NAME) LIKE upper('".$DB->ForSql($arNameReady[$i])."%') ".
								"AND U.LOGIN IS NOT NULL AND upper(U.LOGIN) LIKE upper('".$DB->ForSql($arNameReady[$j])."%') ".
								"AND U.EMAIL IS NOT NULL AND upper(U.EMAIL) LIKE upper('".$DB->ForSql($arNameReady[$k])."%'))";
							$strSql .= " OR ";
							$strSql .= "(U.LAST_NAME IS NOT NULL AND upper(U.LAST_NAME) LIKE upper('".$DB->ForSql($arNameReady[$i])."%') ".
								"AND U.LOGIN IS NOT NULL AND upper(U.LOGIN) LIKE upper('".$DB->ForSql($arNameReady[$j])."%') ".
								"AND U.EMAIL IS NOT NULL AND upper(U.EMAIL) LIKE upper('".$DB->ForSql($arNameReady[$k])."%'))";
							$strSql .= ")";

							$bFirst = false;
						}
					}
				}
				$strSql .= ")";
			}
			elseif (Count($arNameReady) == 2)
			{
				$strSql =
					"SELECT U.ID, U.NAME, U.LAST_NAME, U.SECOND_NAME, U.LOGIN, U.EMAIL ".
					"FROM b_user U ".
					"WHERE (";
				$bFirst = true;
				for ($i = 0; $i < 2; $i++)
				{
					for ($j = 0; $j < 2; $j++)
					{
						if ($i == $j)
							continue;

						if (!$bFirst)
							$strSql .= " OR ";

						$strSql .= "(";
						$strSql .= "(U.NAME IS NOT NULL AND upper(U.NAME) LIKE upper('".$DB->ForSql($arNameReady[$i])."%') ".
							"AND U.LAST_NAME IS NOT NULL AND upper(U.LAST_NAME) LIKE upper('".$DB->ForSql($arNameReady[$j])."%'))";
						$strSql .= " OR ";
						$strSql .= "(U.NAME IS NOT NULL AND upper(U.NAME) LIKE upper('".$DB->ForSql($arNameReady[$i])."%') ".
							"AND U.LOGIN IS NOT NULL AND upper(U.LOGIN) LIKE upper('".$DB->ForSql($arNameReady[$j])."%'))";
						$strSql .= " OR ";
						$strSql .= "(U.LAST_NAME IS NOT NULL AND upper(U.LAST_NAME) LIKE upper('".$DB->ForSql($arNameReady[$i])."%') ".
							"AND U.LOGIN IS NOT NULL AND upper(U.LOGIN) LIKE upper('".$DB->ForSql($arNameReady[$j])."%'))";
						$strSql .= " OR ";
						$strSql .= "(U.LAST_NAME IS NOT NULL AND upper(U.LAST_NAME) LIKE upper('".$DB->ForSql($arNameReady[$i])."%') ".
							"AND U.EMAIL IS NOT NULL AND upper(U.EMAIL) LIKE upper('".$DB->ForSql($arNameReady[$j])."%'))";
						$strSql .= " OR ";
						$strSql .= "(U.NAME IS NOT NULL AND upper(U.NAME) LIKE upper('".$DB->ForSql($arNameReady[$i])."%') ".
							"AND U.EMAIL IS NOT NULL AND upper(U.EMAIL) LIKE upper('".$DB->ForSql($arNameReady[$j])."%'))";
						$strSql .= " OR ";
						$strSql .= "(U.LOGIN IS NOT NULL AND upper(U.LOGIN) LIKE upper('".$DB->ForSql($arNameReady[$i])."%') ".
							"AND U.EMAIL IS NOT NULL AND upper(U.EMAIL) LIKE upper('".$DB->ForSql($arNameReady[$j])."%'))";
						$strSql .= ")";
						$bFirst = false;
					}
				}
				$strSql .= ")";
			}
			else
			{
				$strSql =
					"SELECT U.ID, U.NAME, U.LAST_NAME, U.SECOND_NAME, U.LOGIN, U.EMAIL ".
					"FROM b_user U ".
					"WHERE (U.LAST_NAME IS NOT NULL AND upper(U.LAST_NAME) LIKE upper('".$DB->ForSql($arNameReady[0])."%') ".
					"	OR U.LOGIN IS NOT NULL AND upper(U.LOGIN) LIKE upper('".$DB->ForSql($arNameReady[0])."%') ".
					"	OR U.EMAIL IS NOT NULL AND upper(U.EMAIL) LIKE upper('".$DB->ForSql($arNameReady[0])."%') ".
					"	OR U.NAME IS NOT NULL AND upper(U.NAME) LIKE upper('".$DB->ForSql($arNameReady[0])."%')) ";
			}
			$strSql .= $strSqlWhereEMail;
		}
		else
		{
			if (Count($arNameReady) >= 3)
			{
				$strSql =
					"SELECT U.ID, U.NAME, U.LAST_NAME, U.SECOND_NAME, U.LOGIN, U.EMAIL ".
					"FROM b_user U ".
					"WHERE ";
				$bFirst = true;
				for ($i = 0; $i < 3; $i++)
				{
					for ($j = 0; $j < 3; $j++)
					{
						if ($i == $j)
							continue;

						for ($k = 0; $k < 3; $k++)
						{
							if ($i == $k || $j == $k)
								continue;

							if (!$bFirst)
								$strSql .= " OR ";

							$strSql .= "(U.NAME IS NOT NULL AND upper(U.NAME) LIKE upper('".$DB->ForSql($arNameReady[$i])."%') ".
								"AND U.LAST_NAME IS NOT NULL AND upper(U.LAST_NAME) LIKE upper('".$DB->ForSql($arNameReady[$j])."%') ".
								"AND U.SECOND_NAME IS NOT NULL AND upper(U.SECOND_NAME) LIKE upper('".$DB->ForSql($arNameReady[$k])."%')".$strSqlWhereEMail.")";

							$bFirst = false;
						}
					}
				}
			}
			elseif (Count($arNameReady) == 2)
			{
				$strSql =
					"SELECT U.ID, U.NAME, U.LAST_NAME, U.SECOND_NAME, U.LOGIN, U.EMAIL ".
					"FROM b_user U ".
					"WHERE ";
				$bFirst = true;
				for ($i = 0; $i < 2; $i++)
				{
					for ($j = 0; $j < 2; $j++)
					{
						if ($i == $j)
							continue;

						if (!$bFirst)
							$strSql .= " OR ";

						$strSql .= "(U.NAME IS NOT NULL AND upper(U.NAME) LIKE upper('".$DB->ForSql($arNameReady[$i])."%') ".
							"AND U.LAST_NAME IS NOT NULL AND upper(U.LAST_NAME) LIKE upper('".$DB->ForSql($arNameReady[$j])."%')".$strSqlWhereEMail.")";

						$bFirst = false;
					}
				}
			}
			else
			{
				$strSql =
					"SELECT U.ID, U.NAME, U.LAST_NAME, U.SECOND_NAME, U.LOGIN, U.EMAIL ".
					"FROM b_user U ".
					"WHERE U.LAST_NAME IS NOT NULL AND upper(U.LAST_NAME) LIKE upper('".$DB->ForSql($arNameReady[0])."%') ".
					$strSqlWhereEMail;
			}
		}

		$dbRes = $DB->Query($strSql);
		return $dbRes;
	}

	public static function FormatName($NAME_TEMPLATE, $arUser, $bUseLogin = false, $bHTMLSpec = true)
	{
		if (isset($arUser["ID"]))
			$ID = intval($arUser['ID']);
		else
			$ID = '';

		$NAME_SHORT = ($arUser['NAME'] <> ''? substr($arUser['NAME'], 0, 1).'.' : '');
		$LAST_NAME_SHORT = ($arUser['LAST_NAME'] <> ''? substr($arUser['LAST_NAME'], 0, 1).'.' : '');
		$SECOND_NAME_SHORT = ($arUser['SECOND_NAME'] <> ''? substr($arUser['SECOND_NAME'], 0, 1).'.' : '');

		$res = str_replace(
			array('#TITLE#', '#NAME#', '#LAST_NAME#', '#SECOND_NAME#', '#NAME_SHORT#', '#LAST_NAME_SHORT#', '#SECOND_NAME_SHORT#', '#EMAIL#', '#ID#'),
			array($arUser['TITLE'], $arUser['NAME'], $arUser['LAST_NAME'], $arUser['SECOND_NAME'], $NAME_SHORT, $LAST_NAME_SHORT, $SECOND_NAME_SHORT, $arUser['EMAIL'], $ID),
			$NAME_TEMPLATE
		);

		while(strpos($res, "  ") !== false)
		{
			$res = str_replace("  ", " ", $res);
		}
		$res = trim($res);

		$res_check = "";
		if (strpos($NAME_TEMPLATE, '#NAME#') !== false || strpos($NAME_TEMPLATE, '#NAME_SHORT#') !== false)
			$res_check .= $arUser['NAME'];
		if (strpos($NAME_TEMPLATE, '#LAST_NAME#') !== false || strpos($NAME_TEMPLATE, '#LAST_NAME_SHORT#') !== false)
			$res_check .= $arUser['LAST_NAME'];
		if (strpos($NAME_TEMPLATE, '#SECOND_NAME#') !== false || strpos($NAME_TEMPLATE, '#SECOND_NAME_SHORT#') !== false)
			$res_check .= $arUser['SECOND_NAME'];

		if (trim($res_check) == '')
		{
			if ($bUseLogin && $arUser['LOGIN'] <> '')
				$res = $arUser['LOGIN'];
			else
				$res = GetMessage('FORMATNAME_NONAME');

			if (strpos($NAME_TEMPLATE, '[#ID#]') !== false)
				$res .= " [".$ID."]";
		}

		if ($bHTMLSpec)
			$res = htmlspecialcharsbx($res);

		$res = str_replace(array('#NOBR#', '#/NOBR#'), '', $res);

		return $res;
	}

	public static function clearUserGroupCache($ID = false)
	{
		if ($ID === false)
		{
			self::$userGroupCache = array();
		}
		else
		{
			$ID = (int)$ID;
			if (isset(self::$userGroupCache[$ID]))
				unset(self::$userGroupCache[$ID]);
		}
	}
}


/**
 * <b>CGroup</b> - класс для работы с группами пользователей.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/index.php
 * @author Bitrix
 */
class CAllGroup
{
	var $LAST_ERROR;

	public static function err_mess()
	{
		return "<br>Class: CAllGroup<br>File: ".__FILE__;
	}

	public function CheckFields($arFields, $ID=false)
	{
		global $DB;
		$this->LAST_ERROR = "";

		if(is_set($arFields, "NAME") && $arFields["NAME"] == '')
			$this->LAST_ERROR .= GetMessage("BAD_GROUP_NAME")."<br>";

		if (is_array($arFields["USER_ID"]) && count($arFields["USER_ID"]) > 0)
		{
			if (is_array($arFields["USER_ID"][0]) && count($arFields["USER_ID"][0]) > 0)
			{
				foreach($arFields["USER_ID"] as $arUser)
				{
					if($arUser["DATE_ACTIVE_FROM"] <> '' && !CheckDateTime($arUser["DATE_ACTIVE_FROM"]))
					{
						$error = str_replace("#USER_ID#", $arUser["USER_ID"], GetMessage("WRONG_USER_DATE_ACTIVE_FROM"));
						$this->LAST_ERROR .= $error."<br>";
					}

					if($arUser["DATE_ACTIVE_TO"] <> '' && !CheckDateTime($arUser["DATE_ACTIVE_TO"]))
					{
						$error = str_replace("#USER_ID#", $arUser["USER_ID"], GetMessage("WRONG_USER_DATE_ACTIVE_TO"));
						$this->LAST_ERROR .= $error."<br>";
					}
				}
			}
		}
		if (isset($arFields['STRING_ID']) && $arFields['STRING_ID'] <> '')
		{
			$sql_str = "SELECT G.ID
					FROM b_group G
					WHERE G.STRING_ID='".$DB->ForSql($arFields['STRING_ID'])."'";
			$z = $DB->Query($sql_str, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			if ($r = $z->Fetch())
			{
				if ($ID === false || $ID != $r['ID'])
					$this->LAST_ERROR .= GetMessage('MAIN_ERROR_STRING_ID')."<br>";
			}
		}
		if($this->LAST_ERROR <> '')
			return false;

		return true;
	}

	
	/**
	* <p>Метод изменяет группу с кодом <i>id</i>. Возвращает "true" если изменение прошло успешно, при возникновении ошибки метод вернет "false", а в свойстве LAST_ERROR объекта будет содержаться текст ошибки. Нестатический метод.</p>
	*
	*
	* @param mixed $intid  ID изменяемой записи.
	*
	* @param array $fields  Массив значений <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/index.php#flds">полей</a> вида
	* array("поле"=&gt;"значение" [, ...]).
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $group = new CGroup;
	* $arFields = Array(
	*   "ACTIVE"       =&gt; "Y",
	*   "C_SORT"       =&gt; 100,
	*   "NAME"         =&gt; "Имя группы",
	*   "DESCRIPTION"  =&gt; "Описание группы",
	*   "USER_ID"      =&gt; array(128, 134)
	*   );
	* <b>$group-&gt;Update</b>($GROUP_ID, $arFields);
	* if (strlen($group-&gt;LAST_ERROR)&gt;0) ShowError($group-&gt;LAST_ERROR);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/index.php#flds">Поля CGroup</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/add.php">CGroup::Add</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/delete.php">CGroup::Delete</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/update.php
	* @author Bitrix
	*/
	public function Update($ID, $arFields)
	{
		/** @global CMain $APPLICATION */
		global $DB, $APPLICATION;

		$ID = intval($ID);

		if(!$this->CheckFields($arFields, $ID))
			return false;

		foreach(GetModuleEvents("main", "OnBeforeGroupUpdate", true) as $arEvent)
		{
			$bEventRes = ExecuteModuleEventEx($arEvent, array($ID, &$arFields));
			if($bEventRes===false)
			{
				if($err = $APPLICATION->GetException())
					$this->LAST_ERROR .= $err->GetString()."<br>";
				else
					$this->LAST_ERROR .= "Unknown error in OnBeforeGroupUpdate handler."."<br>";
				return false;
			}
		}

		if($ID<=2)
			unset($arFields["ACTIVE"]);

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";

		$strUpdate = $DB->PrepareUpdate("b_group", $arFields);

		if(!is_set($arFields, "TIMESTAMP_X"))
			$strUpdate .= ", TIMESTAMP_X = ".$DB->GetNowFunction();


		$strSql = "UPDATE b_group SET $strUpdate WHERE ID=".$ID;
		if(is_set($arFields, "SECURITY_POLICY"))
		{
			if(COption::GetOptionString("main", "event_log_group_policy", "N") === "Y")
			{
				//get old security policy
				$aPrevPolicy = array();
				$res = $DB->Query("SELECT SECURITY_POLICY FROM b_group WHERE ID=".$ID);
				if(($res_arr = $res->Fetch()) && $res_arr["SECURITY_POLICY"] <> '')
					$aPrevPolicy = unserialize($res_arr["SECURITY_POLICY"]);
				//compare with new one
				$aNewPolicy = array();
				if($arFields["SECURITY_POLICY"] <> '')
					$aNewPolicy = unserialize($arFields["SECURITY_POLICY"]);
				$aDiff = array_diff_assoc($aNewPolicy, $aPrevPolicy);
				if(empty($aDiff))
					$aDiff = array_diff_assoc($aPrevPolicy, $aNewPolicy);
				if(!empty($aDiff))
					CEventLog::Log("SECURITY", "GROUP_POLICY_CHANGED", "main", $ID, print_r($aPrevPolicy, true)." => ".print_r($aNewPolicy, true));
			}
			$DB->QueryBind($strSql, array("SECURITY_POLICY"=>$arFields["SECURITY_POLICY"]), false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}
		else
		{
			$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}

		if(is_set($arFields, "USER_ID") && is_array($arFields["USER_ID"]))
		{
			$log = (COption::GetOptionString("main", "event_log_user_groups", "N") === "Y");
			if($log)
			{
				//remember users in the group
				$aPrevUsers = array();
				$res = $DB->Query("SELECT USER_ID FROM b_user_group WHERE GROUP_ID=".$ID.($ID=="1"?" AND USER_ID<>1":""));
				while($res_arr = $res->Fetch())
					$aPrevUsers[] = $res_arr["USER_ID"];
			}

			$DB->Query("DELETE FROM b_user_group WHERE GROUP_ID=".$ID.($ID=="1"?" AND USER_ID<>1":""));

			$arUsers = $arFields["USER_ID"];
			$arTmp = array();
			foreach($arUsers as $user)
			{
				if(!is_array($user))
					$user = array("USER_ID" => $user);

				$user_id = intval($user["USER_ID"]);
				if(
					$user_id > 0
					&& !isset($arTmp[$user_id])
					&& ($ID != 1 || $user_id != 1)
				)
				{
					$arInsert = $DB->PrepareInsert("b_user_group", $user);
					$strSql = "
						INSERT INTO b_user_group (
							GROUP_ID, ".$arInsert[0]."
						) VALUES (
							".$ID.", ".$arInsert[1]."
						)
					";
					$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
					$arTmp[$user_id] = true;
				}
			}
			$aNewUsers = array_keys($arTmp);
			CUser::clearUserGroupCache();

			if($log)
			{
				/** @noinspection PhpUndefinedVariableInspection */
				foreach($aPrevUsers as $user_id)
				{
					if(!in_array($user_id, $aNewUsers))
					{
						$UserName = '';
						$rsUser = CUser::GetByID($user_id);
						if($arUser = $rsUser->GetNext())
							$UserName = ($arUser["NAME"] != "" || $arUser["LAST_NAME"] != "") ? trim($arUser["NAME"]." ".$arUser["LAST_NAME"]) : $arUser["LOGIN"];
						$res_log = array(
							"groups" => "-(".$ID.")",
							"user" => $UserName
						);
						CEventLog::Log("SECURITY", "USER_GROUP_CHANGED", "main", $user_id, serialize($res_log));
					}
				}

				foreach($aNewUsers as $user_id)
				{
					if(!in_array($user_id, $aPrevUsers))
					{
						$UserName = '';
						$rsUser = CUser::GetByID($user_id);
						if($arUser = $rsUser->GetNext())
							$UserName = ($arUser["NAME"] != "" || $arUser["LAST_NAME"] != "") ? trim($arUser["NAME"]." ".$arUser["LAST_NAME"]) : $arUser["LOGIN"];
						$res_log = array(
							"groups" =>  "+(".$ID.")",
							"user" => $UserName
						);
						CEventLog::Log("SECURITY", "USER_GROUP_CHANGED", "main", $user_id, serialize($res_log));
					}
				}
			}
		}

		foreach (GetModuleEvents("main", "OnAfterGroupUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, &$arFields));

		return true;
	}

	
	/**
	* <p>Метод удаляет группу. При успешном удалении возвращается объект класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>, в противном случае - "false". Статический  метод.</p>
	*
	*
	* @param mixed $intid  ID группы.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* if(IntVal($del_id)&gt;2)
	* {
	*   $del_id = IntVal($del_id);
	*   $group = new CGroup;
	*   $DB-&gt;StartTransaction();
	*   if(!<b>$group-&gt;Delete</b>($del_id))
	*   {
	*     $DB-&gt;Rollback();
	*     $strError.=GetMessage("DELETE_ERROR");
	*   }
	*   $DB-&gt;Commit();
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/add.php">CGroup::Add</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/update.php">CGroup::Update</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforegroupdelete.php">Событие "OnBeforeGroupDelete"</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/events/ongroupdelete.php">Событие "OnGroupDelete"</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $DB;

		$ID = intval($ID);
		if($ID<=2)
			return false;

		@set_time_limit(600);

		foreach(GetModuleEvents("main", "OnBeforeGroupDelete", true) as $arEvent)
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

		foreach(GetModuleEvents("main", "OnGroupDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID));

		CMain::DelGroupRight("",array($ID));

		if(!$DB->Query("DELETE FROM b_user_group WHERE GROUP_ID=".$ID." AND GROUP_ID>2", true))
			return false;
		CUser::clearUserGroupCache();

		return $DB->Query("DELETE FROM b_group WHERE ID=".$ID." AND ID>2", true);
	}

	
	/**
	* <p>Возвращает массив ID всех пользователей группы по ее коду <i>group_id</i>. Проводится проверка на <b>DATE_ACTIVE_FROM</b> и <b>DATE_ACTIVE_TO</b>. Статический  метод.</p>
	*
	*
	* @param mixed $intid  ID группы.
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $arUsers = <b>CGroup::GetGroupUser</b>(1);
	* echo "&lt;pre&gt;"; print_r($arUsers); echo "&lt;/pre&gt;";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/index.php#flds">Поля CGroup</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php">Поля CUser</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/getgroupuser.php
	* @author Bitrix
	*/
	public static function GetGroupUser($ID)
	{
		global $DB;
		$ID = intval($ID);

		if ($ID == 2)
		{
			$strSql = "SELECT U.ID as USER_ID FROM b_user U ";
		}
		else
		{
			$strSql =
				"SELECT UG.USER_ID ".
				"FROM b_user_group UG ".
				"WHERE UG.GROUP_ID = ".$ID." ".
				"	AND ((UG.DATE_ACTIVE_FROM IS NULL) OR (UG.DATE_ACTIVE_FROM <= ".$DB->CurrentTimeFunction().")) ".
				"	AND ((UG.DATE_ACTIVE_TO IS NULL) OR (UG.DATE_ACTIVE_TO >= ".$DB->CurrentTimeFunction().")) ";
		}

		$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		$arr = array();
		while($r = $res->Fetch())
			$arr[]=$r["USER_ID"];

		return $arr;
	}

	public static function GetGroupUserEx($ID)
	{
		global $DB;
		$ID = intval($ID);

		if ($ID == 2)
		{
			$strSql = "SELECT U.ID as USER_ID, NULL as DATE_ACTIVE_FROM, NULL as DATE_ACTIVE_TO FROM b_user U ";
		}
		else
		{
			$strSql =
				"SELECT UG.USER_ID, ".
				"	".$DB->DateToCharFunction("UG.DATE_ACTIVE_FROM", "FULL")." as DATE_ACTIVE_FROM, ".
				"	".$DB->DateToCharFunction("UG.DATE_ACTIVE_TO", "FULL")." as DATE_ACTIVE_TO ".
				"FROM b_user_group UG ".
				"WHERE UG.GROUP_ID = ".$ID." ".
				"	AND ((UG.DATE_ACTIVE_FROM IS NULL) OR (UG.DATE_ACTIVE_FROM <= ".$DB->CurrentTimeFunction().")) ".
				"	AND ((UG.DATE_ACTIVE_TO IS NULL) OR (UG.DATE_ACTIVE_TO >= ".$DB->CurrentTimeFunction().")) ";
		}
		$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		return $res;
	}

	public static function GetMaxSort()
	{
		global $DB;
		$err_mess = (CAllGroup::err_mess())."<br>Function: GetMaxSort<br>Line: ";
		$z = $DB->Query("SELECT max(C_SORT) M FROM b_group", false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		return intval($zr["M"])+100;
	}

	public static function GetSubordinateGroups($grId)
	{
		global $DB, $CACHE_MANAGER;

		$groupFilter = array();
		if (is_array($grId))
		{
			foreach ($grId as $id)
			{
				$id = intval($id);
				if ($id > 0)
					$groupFilter[$id] = $id;
			}
		}
		else
		{
			$id = intval($grId);
			if ($id > 0)
				$groupFilter[$id] = $id;
		}

		$result = array(2);
		if (!empty($groupFilter))
		{
			if (CACHED_b_group_subordinate === false)
			{
				$z = $DB->Query("SELECT AR_SUBGROUP_ID FROM b_group_subordinate WHERE ID in (".implode(", ", $groupFilter).")");
				while ($zr = $z->Fetch())
				{
					$subordinateGroups = explode(",", $zr['AR_SUBGROUP_ID']);
					if (count($subordinateGroups) == 1 && !$subordinateGroups[0])
						continue;
					$result = array_merge($result, $subordinateGroups);
				}
			}
			else
			{
				if ($CACHE_MANAGER->Read(CACHED_b_group_subordinate, "b_group_subordinate"))
				{
					$cache = $CACHE_MANAGER->Get("b_group_subordinate");
				}
				else
				{
					$cache = array();
					$z = $DB->Query("SELECT ID, AR_SUBGROUP_ID FROM b_group_subordinate");
					while ($zr = $z->Fetch())
					{
						$subordinateGroups = explode(",", $zr['AR_SUBGROUP_ID']);
						if (count($subordinateGroups) == 1 && !$subordinateGroups[0])
							continue;
						$cache[$zr["ID"]] = $subordinateGroups;
					}
					$CACHE_MANAGER->Set("b_group_subordinate", $cache);
				}

				foreach ($cache as $groupId => $subordinateGroups)
				{
					if (isset($groupFilter[$groupId]))
					{
						$result = array_merge($result, $subordinateGroups);
					}
				}
			}
		}

		return array_unique($result);
	}

	public static function SetSubordinateGroups($grId, $arSubGroups=false)
	{
		global $DB, $CACHE_MANAGER;
		$grId = intval($grId);

		$DB->Query("DELETE FROM b_group_subordinate WHERE ID = ".$grId);
		if(is_array($arSubGroups))
		{
			$arInsert = $DB->PrepareInsert("b_group_subordinate", array(
				"ID" => $grId,
				"AR_SUBGROUP_ID" => implode(",", $arSubGroups),
			));
			$DB->Query("INSERT INTO b_group_subordinate(".$arInsert[0].") VALUES (".$arInsert[1].")");
		}
		$CACHE_MANAGER->Clean("b_group_subordinate");
	}


	public static function GetTasks($ID, $onlyMainTasks=true, $module_id=false)
	{
		global $DB;

		$sql_str = 'SELECT GT.TASK_ID,T.MODULE_ID,GT.EXTERNAL_ID
			FROM b_group_task GT
			INNER JOIN b_task T ON (T.ID=GT.TASK_ID)
			WHERE GT.GROUP_ID='.intval($ID);
		if ($module_id !== false)
			$sql_str .= ' AND T.MODULE_ID="'.$DB->ForSQL($module_id).'"';

		$z = $DB->Query($sql_str, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		$arr = array();
		$ex_arr = array();
		while($r = $z->Fetch())
		{
			if (!$r['EXTERNAL_ID'])
				$arr[$r['MODULE_ID']] = $r['TASK_ID'];
			else
				$ex_arr[] = $r;
		}
		if ($onlyMainTasks)
			return $arr;
		else
			return array($arr,$ex_arr);
	}


	public static function SetTasks($ID, $arr)
	{
		global $DB;
		$ID = intval($ID);

		if(COption::GetOptionString("main", "event_log_module_access", "N") === "Y")
		{
			//get old values
			$arOldTasks = array();
			$rsTask = $DB->Query("SELECT TASK_ID FROM b_group_task WHERE GROUP_ID=".$ID);
			while($arTask = $rsTask->Fetch())
				$arOldTasks[] = $arTask["TASK_ID"];
			//compare with new ones
			$aNewTasks = array();
			foreach($arr as $task_id)
				if($task_id > 0)
					$aNewTasks[] = $task_id;
			$aDiff = array_diff($arOldTasks, $aNewTasks);
			if(empty($aDiff))
				$aDiff = array_diff($aNewTasks, $arOldTasks);
			if(!empty($aDiff))
				CEventLog::Log("SECURITY", "MODULE_RIGHTS_CHANGED", "main", $ID, "(".implode(", ", $arOldTasks).") => (".implode(", ", $aNewTasks).")");
		}

		$sql_str = "DELETE FROM b_group_task WHERE GROUP_ID=".$ID.
				" AND (EXTERNAL_ID IS NULL OR EXTERNAL_ID = '')";
		$DB->Query($sql_str, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		$sID = "0";
		if(is_array($arr))
			foreach($arr as $task_id)
				$sID .= ",".intval($task_id);

		$DB->Query(
			"INSERT INTO b_group_task (GROUP_ID, TASK_ID, EXTERNAL_ID) ".
			"SELECT '".$ID."', ID, '' ".
			"FROM b_task ".
			"WHERE ID IN (".$sID.") "
			, false, "File: ".__FILE__."<br>Line: ".__LINE__
		);
	}


	public static function GetTasksForModule($module_id, $onlyMainTasks = true)
	{
		global $DB;

		$sql_str = "SELECT GT.TASK_ID,GT.GROUP_ID,GT.EXTERNAL_ID,T.NAME
			FROM b_group_task GT
			INNER JOIN b_task T ON (T.ID=GT.TASK_ID)
			WHERE T.MODULE_ID='".$DB->ForSQL($module_id)."'";

		$z = $DB->Query($sql_str, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		$main_arr = array();
		$ext_arr = array();
		while($r = $z->Fetch())
		{
			if (!$r['EXTERNAL_ID'])
			{
				$main_arr[$r['GROUP_ID']] = array('ID'=>$r['TASK_ID'],'NAME'=>$r['NAME']);
			}
			elseif(!$onlyMainTasks)
			{
				if (!isset($ext_arr[$r['GROUP_ID']]))
					$ext_arr[$r['GROUP_ID']] = array();
				$ext_arr[$r['GROUP_ID']][] = array('ID'=>$r['TASK_ID'],'NAME'=>$r['NAME'],'EXTERNAL_ID'=>$r['EXTERNAL_ID']);
			}
		}
		if ($onlyMainTasks)
			return $main_arr;
		else
			return array($main_arr,$ext_arr);
	}


	public static function SetTasksForModule($module_id, $arGroupTask)
	{
		global $DB;

		$module_id = $DB->ForSql($module_id);
		$sql_str = "SELECT T.ID
			FROM b_task T
			WHERE T.MODULE_ID='".$module_id."'";
		$r = $DB->Query($sql_str, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$arIds = array();
		while($arR = $r->Fetch())
			$arIds[] = $arR['ID'];

		if(COption::GetOptionString("main", "event_log_module_access", "N") === "Y")
		{
			//get old values
			$arOldTasks = array();
			if(!empty($arIds))
			{
				$rsTask = $DB->Query("SELECT GROUP_ID, TASK_ID FROM b_group_task WHERE TASK_ID IN (".implode(",", $arIds).")");
				while($arTask = $rsTask->Fetch())
					$arOldTasks[$arTask["GROUP_ID"]] = $arTask["TASK_ID"];
			}
			//compare with new ones
			foreach($arOldTasks as $gr_id=>$task_id)
				if($task_id <> $arGroupTask[$gr_id]['ID'])
					CEventLog::Log("SECURITY", "MODULE_RIGHTS_CHANGED", "main", $gr_id, $module_id.": (".$task_id.") => (".$arGroupTask[$gr_id]['ID'].")");
			foreach($arGroupTask as $gr_id => $oTask)
				if(intval($oTask['ID']) > 0 && !array_key_exists($gr_id, $arOldTasks))
					CEventLog::Log("SECURITY", "MODULE_RIGHTS_CHANGED", "main", $gr_id, $module_id.": () => (".$oTask['ID'].")");
		}

		if(!empty($arIds))
		{
			$sql_str = "DELETE FROM b_group_task WHERE TASK_ID IN (".implode(",", $arIds).")";
			$DB->Query($sql_str, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		foreach($arGroupTask as $gr_id => $oTask)
		{
			if(intval($oTask['ID']) > 0)
			{
				$DB->Query(
					"INSERT INTO b_group_task (GROUP_ID, TASK_ID, EXTERNAL_ID) ".
					"SELECT G.ID, T.ID, '' ".
					"FROM b_group G, b_task T ".
					"WHERE G.ID = ".intval($gr_id)." AND
					T.ID = ".intval($oTask['ID']),
					false, "File: ".__FILE__."<br>Line: ".__LINE__
				);
			}
		}
	}

	public static function GetModulePermission($group_id, $module_id)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $DB;

		// check module permissions mode
		$strSql = "SELECT T.ID, GT.TASK_ID FROM b_task T LEFT JOIN b_group_task GT ON T.ID=GT.TASK_ID AND GT.GROUP_ID=".intval($group_id)." WHERE T.MODULE_ID='".$DB->ForSql($module_id)."'";
		$dbr_tasks = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if($ar_task = $dbr_tasks->Fetch())
		{
			do
			{
				if($ar_task["TASK_ID"]>0)
					return $ar_task["TASK_ID"];
			}
			while ($ar_task = $dbr_tasks->Fetch());

			return false;
		}

		return $APPLICATION->GetGroupRight($module_id, array($group_id), "N", "N");
	}

	public static function SetModulePermission($group_id, $module_id, $permission)
	{
		/** @global CMain $APPLICATION */
		global $DB, $APPLICATION;

		if(intval($permission)<=0 && $permission != false)
		{
			$strSql = "SELECT T.ID FROM b_task T WHERE T.MODULE_ID='".$DB->ForSql($module_id)."' AND NAME='".$DB->ForSql($permission)."'";
			$db_task = $DB->Query($strSql);
			if($ar_task=$db_task->Fetch())
				$permission = $ar_task['ID'];
		}

		$permission_letter = '';
		if(intval($permission)>0 || $permission === false)
		{
			$strSql = "SELECT T.ID FROM b_task T WHERE T.MODULE_ID='".$DB->ForSql($module_id)."'";
			$dbr_tasks = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$arIds = array();
			while($arTask = $dbr_tasks->Fetch())
				$arIds[] = $arTask['ID'];

			if(!empty($arIds))
			{
				$strSql = "DELETE FROM b_group_task WHERE GROUP_ID=".intval($group_id)." AND TASK_ID IN (".implode(",", $arIds).")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}

			if(intval($permission)>0)
			{
				$DB->Query(
					"INSERT INTO b_group_task (GROUP_ID, TASK_ID, EXTERNAL_ID) ".
					"SELECT G.ID, T.ID, '' ".
					"FROM b_group G, b_task T ".
					"WHERE G.ID = ".intval($group_id)." AND T.ID = ".intval($permission),
					false,
					"File: ".__FILE__."<br>Line: ".__LINE__
				);

				$permission_letter = CTask::GetLetter($permission);
			}
		}
		else
		{
			$permission_letter = $permission;
		}

		if($permission_letter <> '')
			$APPLICATION->SetGroupRight($module_id, $group_id, $permission_letter);
		else
			$APPLICATION->DelGroupRight($module_id, array($group_id));
	}

	public static function GetIDByCode($code)
	{
		if(strval(intval($code)) == $code && $code > 0)
			return $code;

		if(strtolower($code) == 'administrators')
			return 1;

		if(strtolower($code) == 'everyone')
			return 2;

		global $DB;

		$strSql = "SELECT G.ID FROM b_group G WHERE G.STRING_ID='".$DB->ForSQL($code)."'";
		$db_res = $DB->Query($strSql);

		if($ar_res = $db_res->Fetch())
			return $ar_res["ID"];

		return false;
	}
}


class CAllTask
{
	public static function err_mess()
	{
		return "<br>Class: CAllTask<br>File: ".__FILE__;
	}

	public static function CheckFields(&$arFields, $ID = false)
	{
		/** @global CMain $APPLICATION */
		global $DB, $APPLICATION;

		if($ID>0)
			unset($arFields["ID"]);

		$arMsg = array();

		if(($ID===false || is_set($arFields, "NAME")) && $arFields["NAME"] == '')
			$arMsg[] = array("id"=>"NAME", "text"=> GetMessage('MAIN_ERROR_STRING_ID_EMPTY'));

		$sql_str = "SELECT T.ID
			FROM b_task T
			WHERE T.NAME='".$DB->ForSQL($arFields['NAME'])."'";
		if ($ID !== false)
			$sql_str .= " AND T.ID <> ".intval($ID);

		$z = $DB->Query($sql_str, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		if ($r = $z->Fetch())
			$arMsg[] = array("id"=>"NAME", "text"=> GetMessage('MAIN_ERROR_STRING_ID_DOUBLE'));

		if (isset($arFields['LETTER']))
		{
			if (preg_match("/[^A-Z]/i", $arFields['LETTER']) || strlen($arFields['LETTER']) > 1)
				$arMsg[] = array("id"=>"LETTER", "text"=> GetMessage('MAIN_TASK_WRONG_LETTER'));
			$arFields['LETTER'] = strtoupper($arFields['LETTER']);
		}
		else
		{
			$arFields['LETTER'] = '';
		}

		if(count($arMsg)>0)
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		if (!isset($arFields['SYS']) || $arFields['SYS'] != "Y")
			$arFields['SYS'] = "N";
		if (!isset($arFields['BINDING']))
			$arFields['BINDING'] = 'module';

		return true;
	}

	public static function Add($arFields)
	{
		global $CACHE_MANAGER, $DB;

		if(!CTask::CheckFields($arFields))
			return false;

		if(CACHED_b_task !== false)
			$CACHE_MANAGER->CleanDir("b_task");

		$ID = $DB->Add("b_task", $arFields);
		return $ID;
	}

	public static function Update($arFields,$ID)
	{
		global $DB, $CACHE_MANAGER;

		if(!CTask::CheckFields($arFields,$ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_task", $arFields);

		if($strUpdate)
		{
			if(CACHED_b_task !== false)
				$CACHE_MANAGER->CleanDir("b_task");
			$strSql =
				"UPDATE b_task SET ".
					$strUpdate.
				" WHERE ID=".intval($ID);
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return true;
	}

	public static function UpdateModuleRights($id, $moduleId, $letter, $site_id = false)
	{
		global $DB;

		if (!isset($id, $moduleId))
			return false;

		$sql = "SELECT GT.GROUP_ID
				FROM b_group_task GT
				WHERE GT.TASK_ID=".intval($id);
		$z = $DB->Query($sql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		$arGroups = array();
		while($r = $z->Fetch())
		{
			$g = intval($r['GROUP_ID']);
			if ($g > 0)
				$arGroups[] = $g;
		}
		if (count($arGroups) == 0)
			return false;

		$str_groups = implode(',', $arGroups);
		$moduleId = $DB->ForSQL($moduleId);
		$DB->Query(
			"DELETE FROM b_module_group
			WHERE
				MODULE_ID = '".$moduleId."' AND
				SITE_ID ".($site_id ? "='".$site_id."'" : "IS NULL")." AND
				GROUP_ID IN (".$str_groups.")",
			false, "FILE: ".__FILE__."<br> LINE: ".__LINE__
		);

		if ($letter == '')
			return false;

		$letter = $DB->ForSQL($letter);
		$DB->Query(
			"INSERT INTO b_module_group (MODULE_ID, GROUP_ID, G_ACCESS, SITE_ID) ".
			"SELECT '".$moduleId."', G.ID, '".$letter."', ".($site_id ? "'".$site_id."'" : "NULL")." ".
			"FROM b_group G ".
			"WHERE G.ID IN (".$str_groups.")"
			, false, "File: ".__FILE__."<br>Line: ".__LINE__
		);
		return true;
	}

	public static function Delete($ID, $protect = true)
	{
		global $DB, $CACHE_MANAGER;

		$ID = intval($ID);

		if(CACHED_b_task !== false)
			$CACHE_MANAGER->CleanDir("b_task");

		$sql_str = "DELETE FROM b_task WHERE ID=".$ID;
		if ($protect)
			$sql_str .= " AND SYS='N'";
		$DB->Query($sql_str, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		if (!$protect)
		{
			if(CACHED_b_task_operation !== false)
				$CACHE_MANAGER->CleanDir("b_task_operation");

			$DB->Query("DELETE FROM b_task_operation WHERE TASK_ID=".$ID, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}
	}

	public static function GetList($arOrder = array('MODULE_ID'=>'asc','LETTER'=>'asc'),$arFilter=array())
	{
		global $DB, $CACHE_MANAGER;;

		if(CACHED_b_task !== false)
		{
			$cacheId = "b_task".md5(serialize($arOrder).".".serialize($arFilter));
			if($CACHE_MANAGER->Read(CACHED_b_task, $cacheId, "b_task"))
			{
				$arResult = $CACHE_MANAGER->Get($cacheId);
				$res = new CDBResult;
				$res->InitFromArray($arResult);
				return $res;
			}
		}

		static $arFields = array(
			"ID" => array("FIELD_NAME" => "T.ID", "FIELD_TYPE" => "int"),
			"NAME" => array("FIELD_NAME" => "T.NAME", "FIELD_TYPE" => "string"),
			"LETTER" => array("FIELD_NAME" => "T.LETTER", "FIELD_TYPE" => "string"),
			"MODULE_ID" => array("FIELD_NAME" => "T.MODULE_ID", "FIELD_TYPE" => "string"),
			"SYS" => array("FIELD_NAME" => "T.SYS", "FIELD_TYPE" => "string"),
			"BINDING" => array("FIELD_NAME" => "T.BINDING", "FIELD_TYPE" => "string")
		);

		$err_mess = (CAllTask::err_mess())."<br>Function: GetList<br>Line: ";
		$arSqlSearch = array();
		if(is_array($arFilter))
		{
			foreach($arFilter as $n => $val)
			{
				$n = strtoupper($n);
				if(strlen($val) <= 0 || strval($val) == "NOT_REF")
					continue;

				if(isset($arFields[$n]))
				{
					$arSqlSearch[] = GetFilterQuery($arFields[$n]["FIELD_NAME"], $val, ($n == 'NAME'? "Y" : "N"));
				}
			}
		}

		$strOrderBy = '';
		foreach($arOrder as $by=>$order)
			if(isset($arFields[strtoupper($by)]))
				$strOrderBy .= $arFields[strtoupper($by)]["FIELD_NAME"].' '.(strtolower($order)=='desc'?'desc'.(strtoupper($DB->type)=="ORACLE"?" NULLS LAST":""):'asc'.(strtoupper($DB->type)=="ORACLE"?" NULLS FIRST":"")).',';

		if($strOrderBy <> '')
			$strOrderBy = "ORDER BY ".rtrim($strOrderBy, ",");

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				T.ID, T.NAME, T.DESCRIPTION, T.MODULE_ID, T.LETTER, T.SYS, T.BINDING
			FROM
				b_task T
			WHERE
				".$strSqlSearch."
			".$strOrderBy;

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		$arResult = array();
		while($arRes = $res->Fetch())
		{
			$arRes['TITLE'] = CTask::GetLangTitle($arRes['NAME'], $arRes['MODULE_ID']);
			$arRes['DESC'] = CTask::GetLangDescription($arRes['NAME'], $arRes['DESCRIPTION'], $arRes['MODULE_ID']);
			$arResult[] = $arRes;
		}
		$res->InitFromArray($arResult);

		if(CACHED_b_task !== false)
		{
			/** @noinspection PhpUndefinedVariableInspection */
			$CACHE_MANAGER->Set($cacheId, $arResult);
		}

		return $res;
	}


	public static function GetOperations($ID, $return_names = false)
	{
		global $DB, $CACHE_MANAGER;
		static $TASK_OPERATIONS_CACHE = array();
		$ID = intval($ID);

		if (!isset($TASK_OPERATIONS_CACHE[$ID]))
		{
			if(CACHED_b_task_operation !== false)
			{
				$cacheId = "b_task_operation_".$ID;
				if($CACHE_MANAGER->Read(CACHED_b_task_operation, $cacheId, "b_task_operation"))
				{
					$TASK_OPERATIONS_CACHE[$ID] = $CACHE_MANAGER->Get($cacheId);
				}
			}
		}

		if (!isset($TASK_OPERATIONS_CACHE[$ID]))
		{
			$sql_str = '
				SELECT T_O.OPERATION_ID, O.NAME
				FROM b_task_operation T_O
				INNER JOIN b_operation O ON T_O.OPERATION_ID = O.ID
				WHERE T_O.TASK_ID = '.$ID.'
			';

			$TASK_OPERATIONS_CACHE[$ID] = array(
				'names' => array(),
				'ids' => array(),
			);
			$z = $DB->Query($sql_str, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			while($r = $z->Fetch())
			{
				$TASK_OPERATIONS_CACHE[$ID]['names'][] = $r['NAME'];
				$TASK_OPERATIONS_CACHE[$ID]['ids'][] = $r['OPERATION_ID'];
			}

			if(CACHED_b_task_operation !== false)
			{
				/** @noinspection PhpUndefinedVariableInspection */
				$CACHE_MANAGER->Set($cacheId, $TASK_OPERATIONS_CACHE[$ID]);
			}
		}

		return $TASK_OPERATIONS_CACHE[$ID][$return_names ? 'names' : 'ids'];
	}

	public static function SetOperations($ID, $arr, $bOpNames=false)
	{
		global $DB, $CACHE_MANAGER;

		$ID = intval($ID);

		//get old operations
		$aPrevOp = array();
		$res = $DB->Query("
			SELECT O.NAME
			FROM b_operation O
			INNER JOIN b_task_operation T_OP ON O.ID = T_OP.OPERATION_ID
			WHERE T_OP.TASK_ID = ".$ID."
			ORDER BY O.ID
		");
		while(($res_arr = $res->Fetch()))
			$aPrevOp[] = $res_arr["NAME"];

		$sql_str = 'DELETE FROM b_task_operation WHERE TASK_ID='.$ID;
		$DB->Query($sql_str, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		if(is_array($arr) && count($arr)>0)
		{
			if($bOpNames)
			{
				$sID = "";
				foreach($arr as $op_id)
					$sID .= ",'".$DB->ForSQL($op_id)."'";
				$sID = LTrim($sID, ",");

				$DB->Query(
					"INSERT INTO b_task_operation (TASK_ID, OPERATION_ID) ".
					"SELECT '".$ID."', O.ID ".
					"FROM b_operation O, b_task T ".
					"WHERE O.NAME IN (".$sID.") AND T.MODULE_ID=O.MODULE_ID AND T.ID=".$ID." "
					, false, "File: ".__FILE__."<br>Line: ".__LINE__
				);
			}
			else
			{
				$sID = "0";
				foreach($arr as $op_id)
					$sID .= ",".intval($op_id);

				$DB->Query(
					"INSERT INTO b_task_operation (TASK_ID, OPERATION_ID) ".
					"SELECT '".$ID."', ID ".
					"FROM b_operation ".
					"WHERE ID IN (".$sID.") "
					, false, "File: ".__FILE__."<br>Line: ".__LINE__
				);
			}
		}

		if(CACHED_b_task_operation !== false)
			$CACHE_MANAGER->CleanDir("b_task_operation");

		//get new operations
		$aNewOp = array();
		$res = $DB->Query("
			SELECT O.NAME
			FROM b_operation O
			INNER JOIN b_task_operation T_OP ON O.ID = T_OP.OPERATION_ID
			WHERE T_OP.TASK_ID = ".$ID."
			ORDER BY O.ID
		");
		while(($res_arr = $res->Fetch()))
			$aNewOp[] = $res_arr["NAME"];

		//compare with old one
		$aDiff = array_diff($aNewOp, $aPrevOp);
		if(empty($aDiff))
			$aDiff = array_diff($aPrevOp, $aNewOp);
		if(!empty($aDiff))
		{
			if(COption::GetOptionString("main", "event_log_task", "N") === "Y")
				CEventLog::Log("SECURITY", "TASK_CHANGED", "main", $ID, "(".implode(", ", $aPrevOp).") => (".implode(", ", $aNewOp).")");
			foreach(GetModuleEvents("main", "OnTaskOperationsChanged", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($ID, $aPrevOp, $aNewOp));
		}
	}

	public static function GetTasksInModules($mode=false, $module_id=false, $binding = false)
	{
		$arFilter = array();
		if ($module_id !== false)
			$arFilter["MODULE_ID"] = $module_id;
		if ($binding !== false)
			$arFilter["BINDING"] = $binding;

		$z = CTask::GetList(
			array(
				"MODULE_ID" => "asc",
				"LETTER" => "asc"
			),
			$arFilter
		);

		$arr = array();
		if ($mode)
		{
			while($r = $z->Fetch())
			{
				if (!is_array($arr[$r['MODULE_ID']]))
					$arr[$r['MODULE_ID']] = array('reference_id'=>array(),'reference'=>array());

				$arr[$r['MODULE_ID']]['reference_id'][] = $r['ID'];
				$arr[$r['MODULE_ID']]['reference'][] = '['.($r['LETTER'] ? $r['LETTER'] : '..').'] '.CTask::GetLangTitle($r['NAME'], $r['MODULE_ID']);
			}
		}
		else
		{
			while($r = $z->Fetch())
			{
				if (!is_array($arr[$r['MODULE_ID']]))
					$arr[$r['MODULE_ID']] = array();

				$arr[$r['MODULE_ID']][] = $r;
			}
		}
		return $arr;
	}

	public static function GetByID($ID)
	{
		return CTask::GetList(array(), array("ID" => intval($ID)));
	}

	protected static function GetDescriptions($module)
	{
		static $descriptions = array();

		if(preg_match("/[^a-z0-9._]/i", $module))
		{
			return array();
		}

		if(!isset($descriptions[$module]))
		{
			if(($path = getLocalPath("modules/".$module."/admin/task_description.php")) !== false)
			{
				$descriptions[$module] = include($_SERVER["DOCUMENT_ROOT"].$path);
			}
			else
			{
				$descriptions[$module] = array();
			}
		}

		return $descriptions[$module];
	}

	public static function GetLangTitle($name, $module = "main")
	{
		$descriptions = static::GetDescriptions($module);

		$nameUpper = strtoupper($name);

		if(isset($descriptions[$nameUpper]["title"]))
		{
			return $descriptions[$nameUpper]["title"];
		}

		return $name;
	}

	public static function GetLangDescription($name, $desc, $module = "main")
	{
		$descriptions = static::GetDescriptions($module);

		$nameUpper = strtoupper($name);

		if(isset($descriptions[$nameUpper]["description"]))
		{
			return $descriptions[$nameUpper]["description"];
		}

		return $desc;
	}

	public static function GetLetter($ID)
	{
		$z = CTask::GetById($ID);
		if ($r = $z->Fetch())
			if ($r['LETTER'])
				return $r['LETTER'];
		return false;
	}

	public static function GetIdByLetter($letter, $module, $binding='module')
	{
		static $TASK_LETTER_CACHE = array();
		if (!$letter)
			return false;

		if (!isset($TASK_LETTER_CACHE))
			$TASK_LETTER_CACHE = array();

		$k = strtoupper($letter.'_'.$module.'_'.$binding);
		if (isset($TASK_LETTER_CACHE[$k]))
			return $TASK_LETTER_CACHE[$k];

		$z = CTask::GetList(
			array(),
			array(
				"LETTER" => $letter,
				"MODULE_ID" => $module,
				"BINDING" => $binding,
				"SYS"=>"Y"
			)
		);

		if ($r = $z->Fetch())
		{
			$TASK_LETTER_CACHE[$k] = $r['ID'];
			if ($r['ID'])
				return $r['ID'];
		}

		return false;
	}
}

class CAllOperation
{
	public static function err_mess()
	{
		return "<br>Class: CAllOperation<br>File: ".__FILE__;
	}

	public static function GetList($arOrder = array('MODULE_ID'=>'asc'),$arFilter=array())
	{
		global $DB;

		static $arFields = array(
			"ID" => array("FIELD_NAME" => "O.ID", "FIELD_TYPE" => "int"),
			"NAME" => array("FIELD_NAME" => "O.NAME", "FIELD_TYPE" => "string"),
			"MODULE_ID" => array("FIELD_NAME" => "O.MODULE_ID", "FIELD_TYPE" => "string"),
			"BINDING" => array("FIELD_NAME" => "O.BINDING", "FIELD_TYPE" => "string")
		);

		$err_mess = (CAllOperation::err_mess())."<br>Function: GetList<br>Line: ";
		$arSqlSearch = array();
		if(is_array($arFilter))
		{
			foreach($arFilter as $n => $val)
			{
				$n = strtoupper($n);
				if($val == '' || strval($val)=="NOT_REF")
					continue;
				if ($n == 'ID' || $n == 'MODULE_ID' || $n == 'BINDING')
					$arSqlSearch[] = GetFilterQuery($arFields[$n]["FIELD_NAME"], $val, 'N');
				elseif(isset($arFields[$n]))
					$arSqlSearch[] = GetFilterQuery($arFields[$n]["FIELD_NAME"], $val);
			}
		}

		$strOrderBy = '';
		foreach($arOrder as $by=>$order)
			if(isset($arFields[strtoupper($by)]))
				$strOrderBy .= $arFields[strtoupper($by)]["FIELD_NAME"].' '.(strtolower($order)=='desc'?'desc'.(strtoupper($DB->type)=="ORACLE"?" NULLS LAST":""):'asc'.(strtoupper($DB->type)=="ORACLE"?" NULLS FIRST":"")).',';

		if($strOrderBy <> '')
			$strOrderBy = "ORDER BY ".rtrim($strOrderBy, ",");

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT *
			FROM
				b_operation O
			WHERE
				".$strSqlSearch."
			".$strOrderBy;

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	public static function GetAllowedModules()
	{
		global $DB;
		$sql_str = 'SELECT DISTINCT O.MODULE_ID FROM b_operation O';
		$z = $DB->Query($sql_str, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		$arr = array();
		while($r = $z->Fetch())
			$arr[] = $r['MODULE_ID'];
		return $arr;
	}

	public static function GetBindingList()
	{
		global $DB;
		$sql_str = 'SELECT DISTINCT O.MODULE_ID, O.BINDING FROM b_operation O';
		$z = $DB->Query($sql_str, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		$arr = array();
		while($r = $z->Fetch())
			$arr[] = $r;
		return $arr;
	}

	public static function GetIDByName($name)
	{
		$z = COperation::GetList(array('MODULE_ID' => 'asc'), array("NAME" => $name));
		if ($r = $z->Fetch())
			return $r['ID'];
		return false;
	}

	protected static function GetDescriptions($module)
	{
		static $descriptions = array();

		if(preg_match("/[^a-z0-9._]/i", $module))
		{
			return array();
		}

		if(!isset($descriptions[$module]))
		{
			if(($path = getLocalPath("modules/".$module."/admin/operation_description.php")) !== false)
			{
				$descriptions[$module] = include($_SERVER["DOCUMENT_ROOT"].$path);
			}
			else
			{
				$descriptions[$module] = array();
			}
		}

		return $descriptions[$module];
	}

	public static function GetLangTitle($name, $module = "main")
	{
		$descriptions = static::GetDescriptions($module);

		$nameUpper = strtoupper($name);

		if(isset($descriptions[$nameUpper]["title"]))
		{
			return $descriptions[$nameUpper]["title"];
		}

		return $name;
	}

	public static function GetLangDescription($name, $desc, $module = "main")
	{
		$descriptions = static::GetDescriptions($module);

		$nameUpper = strtoupper($name);

		if(isset($descriptions[$nameUpper]["description"]))
		{
			return $descriptions[$nameUpper]["description"];
		}

		return $desc;
	}
}
