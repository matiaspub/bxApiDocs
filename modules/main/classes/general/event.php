<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

use Bitrix\Main\Mail;

IncludeModuleLangFile(__FILE__);

global $BX_EVENT_SITE_PARAMS;
$BX_EVENT_SITE_PARAMS = array();


/**
 * <b>CEvent</b> - класс для работы с почтовыми событиями.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cevent/index.php
 * @author Bitrix
 */
class CAllEvent
{
	
	/**
	* <p>Собирает неотправленные почтовые события и отправляет их в виде E-Mail сообщений с помощью функции <a href="http://dev.1c-bitrix.ru/api_help/main/functions/other/bxmail.php">bxmail</a>. Метод автоматически вызывается при загрузке каждой страницы и не требует ручного вызова. Нестатический метод.</p>
	*
	*
	* @return mixed 
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/general/mailevents.php">Почтовая система</a>
	* </li>   <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/other/bxmail.php">bxmail</a> </li> </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cevent/checkevents.php
	* @author Bitrix
	*/
	public static function CheckEvents()
	{
		return Mail\EventManager::checkEvents();
	}

	public static function ExecuteEvents()
	{
		return Mail\EventManager::executeEvents();
	}

	public static function CleanUpAgent()
	{
		return Mail\EventManager::cleanUpAgent();
	}

	
	/**
	* <p>Отправляет сообщение немедленно. В отличие от <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cevent/send.php">CEvent::Send</a> не возвращает идентификатор созданного сообщения. При отправке сообщения данным методом запись в таблицу <b>b_event</b> не производится. Нестатический метод.</p> <p>Аналог метода в новом ядре D7: <i>Bitrix\Main\Mail\Event::sendImmediate</i>.</p>
	*
	*
	* @param mixed $event  Идентификатор типа почтового события.
	*
	* @param $even $lid  Идентификатор сайта, либо массив идентификаторов сайта.
	*
	* @param $li $arFields  Массив полей типа почтового события идентификатор которого
	* задается в параметре <i>event_type</i>. Массив имеет следующий формат:
	* array("поле"=&gt;"значение" [, ...]).
	*
	* @param $arField $Duplicate = "Y" Отправить ли копию письма на адрес указанный в настройках
	* главного модуля в поле "<b>E-Mail адрес или список адресов через
	* запятую на который будут дублироваться все исходящие
	* сообщения</b>".          <br>       Необязательный. По умолчанию "Y".
	*
	* @param mixed $message_id = "" Идентификатор почтового шаблона по которому будет отправлено
	* письмо.         <br>        Если данный параметр не задан, либо равен "", то
	* письма будут отправлены по всем шаблонам привязанным к типу
	* почтового события, идентификатор которого задается в параметре
	* <i>event_type</i>, а также привязанных к сайту(ам) идентификатор которого
	* указан в параметре <i>site</i>.         <br>       Необязательный. По умолчанию
	* - "".
	*
	* @return mixed <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* <br><br>
	* Смотрите также<li><a href="http://dev.1c-bitrix.ru/community/webdev/user/17138/blog/1740/">Пароль в письме при регистрации</a></li>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cevent/sendimmediate.php
	* @author Bitrix
	*/
	public static function SendImmediate($event, $lid, $arFields, $Duplicate = "Y", $message_id="", $files=array())
	{
		foreach(GetModuleEvents("main", "OnBeforeEventAdd", true) as $arEvent)
			if(ExecuteModuleEventEx($arEvent, array(&$event, &$lid, &$arFields, &$message_id, &$files)) === false)
				return false;

		if(!is_array($arFields))
		{
			$arFields = array();
		}

		$arLocalFields = array(
			"EVENT_NAME" => $event,
			"C_FIELDS" => $arFields,
			"LID" => is_array($lid)? implode(",", $lid): $lid,
			"DUPLICATE" => $Duplicate != "N"? "Y": "N",
			"MESSAGE_ID" => intval($message_id) > 0? intval($message_id): "",
			"DATE_INSERT" => GetTime(time(), "FULL"),
			"FILE" => $files,
			"ID" => "0",
		);

		return Mail\Event::sendImmediate($arLocalFields);
	}

	
	/**
	* <p>Метод создает почтовое событие которое будет в дальнейшем отправлено в качестве E-Mail сообщения. Возвращает идентификатор созданного события. Нестатический метод.</p> <p>Аналог метода в новом ядре D7: <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/event/send.php" >\Bitrix\Main\Mail\Event::send</a>.</p>
	*
	*
	* @param string $event  Идентификатор типа почтового события.
	*
	* @param mixed $lid  Идентификатор сайта, либо массив идентификаторов сайта.
	*
	* @param array $fields  Массив полей типа почтового события идентификатор которого
	* задается в параметре <i>event_type</i>. Массив имеет следующий формат:
	* array("поле"=&gt;"значение" [, ...]).
	*
	* @param string $duplicate = "Y" Отправить ли копию письма на адрес указанный в настройках
	* главного модуля в поле "<b>E-Mail адрес или список адресов через
	* запятую на который будут дублироваться все исходящие
	* сообщения</b>". <br>Необязательный. По умолчанию "Y".
	*
	* @param int $message_id = "" Идентификатор почтового шаблона по которому будет отправлено
	* письмо.<br> Если данный параметр не задан, либо равен "", то письма
	* будут отправлены по всем шаблонам привязанным к типу почтового
	* события, идентификатор которого задается в параметре <i>event_type</i>, а
	* также привязанных к сайту(ам) идентификатор которого указан в
	* параметре <i>site</i>.<br>Необязательный. По умолчанию - "".
	*
	* @param array $files  Массив id файлов, которые используются классом <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cfile/index.php">CFile</a>. Либо можно передать
	* массив абсолютных путей до файлов.
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $arEventFields = array(
	*     "ID"                  =&gt; $CONTRACT_ID,
	*     "MESSAGE"             =&gt; $mess,
	*     "EMAIL_TO"            =&gt; implode(",", $EMAIL_TO),
	*     "ADMIN_EMAIL"         =&gt; implode(",", $ADMIN_EMAIL),
	*     "ADD_EMAIL"           =&gt; implode(",", $ADD_EMAIL),
	*     "STAT_EMAIL"          =&gt; implode(",", $VIEW_EMAIL),
	*     "EDIT_EMAIL"          =&gt; implode(",", $EDIT_EMAIL),
	*     "OWNER_EMAIL"         =&gt; implode(",", $OWNER_EMAIL),
	*     "BCC"                 =&gt; implode(",", $BCC),
	*     "INDICATOR"           =&gt; GetMessage("AD_".strtoupper($arContract["LAMP"]."_CONTRACT_STATUS")),
	*     "ACTIVE"              =&gt; $arContract["ACTIVE"],
	*     "NAME"                =&gt; $arContract["NAME"],
	*     "DESCRIPTION"         =&gt; $description,
	*     "MAX_SHOW_COUNT"      =&gt; $arContract["MAX_SHOW_COUNT"],
	*     "SHOW_COUNT"          =&gt; $arContract["SHOW_COUNT"],
	*     "MAX_CLICK_COUNT"     =&gt; $arContract["MAX_CLICK_COUNT"],
	*     "CLICK_COUNT"         =&gt; $arContract["CLICK_COUNT"],
	*     "BANNERS"             =&gt; $arContract["BANNER_COUNT"],
	*     "DATE_SHOW_FROM"      =&gt; $arContract["DATE_SHOW_FROM"],
	*     "DATE_SHOW_TO"        =&gt; $arContract["DATE_SHOW_TO"],
	*     "DATE_CREATE"         =&gt; $arContract["DATE_CREATE"],
	*     "CREATED_BY"          =&gt; $CREATED_BY,
	*     "DATE_MODIFY"         =&gt; $arContract["DATE_MODIFY"],
	*     "MODIFIED_BY"         =&gt; $MODIFIED_BY
	*     );
	* $arrSITE =  CAdvContract::GetSiteArray($CONTRACT_ID);
	* <b>CEvent::Send</b>("ADV_CONTRACT_INFO", $arrSITE, $arEventFields);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/general/mailevents.php">Почтовая система</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cevent/send.php
	* @author Bitrix
	*/
	public static function Send($event, $lid, $arFields, $Duplicate = "Y", $message_id="", $files=array())
	{
		foreach(GetModuleEvents("main", "OnBeforeEventAdd", true) as $arEvent)
			if(ExecuteModuleEventEx($arEvent, array(&$event, &$lid, &$arFields, &$message_id, &$files)) === false)
				return false;

		$arLocalFields = array(
			"EVENT_NAME" => $event,
			"C_FIELDS" => $arFields,
			"LID" => is_array($lid)? implode(",", $lid): $lid,
			"DUPLICATE" => $Duplicate != "N"? "Y": "N",
			"FILE" => $files,
		);
		if(intval($message_id) > 0)
			$arLocalFields["MESSAGE_ID"] = intval($message_id);

		$result = Mail\Event::send($arLocalFields);

		$id = false;
		if ($result->isSuccess())
		{
			$id = $result->getId();
		}
		return $id;
	}

	public static function fieldencode($s)
	{
		if(is_array($s))
		{
			$ret_val = '';
			foreach($s as $v)
				$ret_val .= ($ret_val <> ''? ', ':'').CEvent::fieldencode($v);
		}
		else
		{
			$ret_val = str_replace("%", "%2", $s);
			$ret_val = str_replace("&","%1", $ret_val);
			$ret_val = str_replace("=", "%3", $ret_val);
		}
		return $ret_val;
	}

	public static function ExtractMailFields($str)
	{
		$ar = explode("&", $str);
		$newar = array();
		while (list (, $val) = each ($ar))
		{
			$val = str_replace("%1", "&", $val);
			$tar = explode("=", $val);
			$key = $tar[0];
			$val = $tar[1];
			$key = str_replace("%3", "=", $key);
			$val = str_replace("%3", "=", $val);
			$key = str_replace("%2", "%", $key);
			$val = str_replace("%2", "%", $val);
			if($key != "")
				$newar[$key] = $val;
		}
		return $newar;
	}

	public static function GetSiteFieldsArray($site_id)
	{
		global $BX_EVENT_SITE_PARAMS;
		if($site_id !== false && isset($BX_EVENT_SITE_PARAMS[$site_id]))
			return $BX_EVENT_SITE_PARAMS[$site_id];

		$SITE_NAME = COption::GetOptionString("main", "site_name", $GLOBALS["SERVER_NAME"]);
		$SERVER_NAME = COption::GetOptionString("main", "server_name", $GLOBALS["SERVER_NAME"]);
		$DEFAULT_EMAIL_FROM = COption::GetOptionString("main", "email_from", "admin@".$GLOBALS["SERVER_NAME"]);

		if(strlen($site_id)>0)
		{
			$dbSite = CSite::GetByID($site_id);
			if($arSite = $dbSite->Fetch())
			{
				$BX_EVENT_SITE_PARAMS[$site_id] = array(
					"SITE_NAME" => ($arSite["SITE_NAME"]<>''? $arSite["SITE_NAME"] : $SITE_NAME),
					"SERVER_NAME" => ($arSite["SERVER_NAME"]<>''? $arSite["SERVER_NAME"] : $SERVER_NAME),
					"DEFAULT_EMAIL_FROM" => ($arSite["EMAIL"]<>''? $arSite["EMAIL"] : $DEFAULT_EMAIL_FROM),
					"SITE_ID" => $arSite['ID'],
					"SITE_DIR" => $arSite['DIR'],
				);
				return $BX_EVENT_SITE_PARAMS[$site_id];
			}
		}

		return array(
			"SITE_NAME" => $SITE_NAME,
			"SERVER_NAME" => $SERVER_NAME,
			"DEFAULT_EMAIL_FROM" => $DEFAULT_EMAIL_FROM
		);
	}

	public static function ReplaceTemplate($str, $ar, $bNewLineToBreak=false)
	{
		$str = str_replace("%", "%2", $str);
		foreach($ar as $key=>$val)
		{
			if($bNewLineToBreak && strpos($val, "<") === false)
				$val = nl2br($val);
			$val = str_replace("%", "%2", $val);
			$val = str_replace("#", "%1", $val);
			$str = str_replace("#".$key."#", $val, $str);
		}
		$str = str_replace("%1", "#", $str);
		$str = str_replace("%2", "%", $str);

		return $str;
	}

	/**
	 * @deprecated See \Bitrix\Main\Mail\Mail::is8Bit()
	 */
	public static function Is8Bit($str)
	{
		return Mail\Mail::is8Bit($str);
	}

	/**
	 * @deprecated See \Bitrix\Main\Mail\Mail::encodeMimeString()
	 */
	public static function EncodeMimeString($text, $charset)
	{
		return Mail\Mail::encodeMimeString($text, $charset);
	}

	/**
	 * @deprecated See \Bitrix\Mail\Mail::encodeSubject()
	 */
	public static function EncodeSubject($text, $charset)
	{
		return Mail\Mail::encodeSubject($text, $charset);
	}

	/**
	 * @deprecated See \Bitrix\Main\Mail\Mail::encodeHeaderFrom()
	 */
	public static function EncodeHeaderFrom($text, $charset)
	{
		return Mail\Mail::encodeHeaderFrom($text, $charset);
	}

	/**
	 * @deprecated See \Bitrix\Main\Mail\Mail::getMailEol()
	 */
	public static function GetMailEOL()
	{
		return Mail\Mail::getMailEol();
	}

	/**
	 * @deprecated See \Bitrix\Main\Mail\Event::handleEvent()
	 */
	public static function HandleEvent($arEvent)
	{
		if(isset($arEvent['C_FIELDS']))
		{
			$arEvent['FIELDS'] = $arEvent['C_FIELDS'];
			unset($arEvent['C_FIELDS']);
		}

		return Mail\Event::handleEvent($arEvent);
	}
}


/**
 * <b>CEventMessage</b> - класс предназначеный для работы с почтовыми шаблонами.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/index.php
 * @author Bitrix
 */
class CAllEventMessage
{
	var $LAST_ERROR;

	public function CheckFields($arFields, $ID=false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		$this->LAST_ERROR = "";
		$arMsg = array();

		if(is_set($arFields, "EMAIL_FROM") && strlen($arFields["EMAIL_FROM"])<3)
		{
			$this->LAST_ERROR .= GetMessage("BAD_EMAIL_FROM")."<br>";
			$arMsg[] = array("id"=>"EMAIL_FROM", "text"=> GetMessage("BAD_EMAIL_FROM"));
		}
		if(is_set($arFields, "EMAIL_TO") && strlen($arFields["EMAIL_TO"])<3)
		{
			$this->LAST_ERROR .= GetMessage("BAD_EMAIL_TO")."<br>";
			$arMsg[] = array("id"=>"EMAIL_TO", "text"=> GetMessage("BAD_EMAIL_TO"));
		}

		if($ID===false && !is_set($arFields, "EVENT_NAME"))
		{
			$this->LAST_ERROR .= GetMessage(GetMessage("MAIN_BAD_EVENT_NAME_NA"))."<br>";
			$arMsg[] = array("id"=>"EVENT_NAME", "text"=> GetMessage("MAIN_BAD_EVENT_NAME_NA"));
		}
		if(is_set($arFields, "EVENT_NAME"))
		{
			$r = CEventType::GetListEx(array(), array("EVENT_NAME"=>$arFields["EVENT_NAME"]), array("type"=>"none"));
			if(!$r->Fetch())
			{
				$this->LAST_ERROR .= GetMessage("BAD_EVENT_TYPE")."<br>";
				$arMsg[] = array("id"=>"EVENT_NAME", "text"=> GetMessage("BAD_EVENT_TYPE"));
			}
		}

		if(
			($ID===false && !is_set($arFields, "LID")) ||
			(is_set($arFields, "LID")
			&& (
				(is_array($arFields["LID"]) && count($arFields["LID"])<=0)
				||
				(!is_array($arFields["LID"]) && strlen($arFields["LID"])<=0)
				)
			)
		)
		{
			$this->LAST_ERROR .= GetMessage("MAIN_BAD_SITE_NA")."<br>";
			$arMsg[] = array("id"=>"LID", "text"=> GetMessage("MAIN_BAD_SITE_NA"));
		}
		elseif(is_set($arFields, "LID"))
		{
			if(!is_array($arFields["LID"]))
				$arFields["LID"] = array($arFields["LID"]);

			foreach($arFields["LID"] as $v)
			{
				$r = CSite::GetByID($v);
				if(!$r->Fetch())
				{
					$this->LAST_ERROR .= "'".$v."' - ".GetMessage("MAIN_EVENT_BAD_SITE")."<br>";
					$arMsg[] = array("id"=>"LID", "text"=> GetMessage("MAIN_EVENT_BAD_SITE"));
				}
			}
		}

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
		}

		if(strlen($this->LAST_ERROR)>0)
			return false;

		return true;
	}

	///////////////////////////////////////////////////////////////////
	// New event message template
	///////////////////////////////////////////////////////////////////
	
	/**
	* <p>Метод добавляет новый почтовый шаблон. Возвращает ID вставленного шаблона. При возникновении ошибки, метод вернет false, а в свойстве LAST_ERROR объекта будет содержаться текст ошибки. Нестатический метод.</p>
	*
	*
	* @param array $fields  Массив значений полей вида array("поле"=&gt;"значение" [, ...]).  В качестве
	* "полей" допустимо использовать: 	<ul> <li> <b>ACTIVE</b> - флаг активности
	* почтового шаблона: "Y" - активен; "N" - не активен; 		</li> <li> <b>EVENT_NAME</b> -
	* идентификатор типа почтового события; 		</li> <li> <b>LID</b> -
	* идентификатор сайта; 		</li> <li> <b>EMAIL_FROM</b> - поле "From" ("Откуда"); 		</li> <li>
	* <b>EMAIL_TO</b> - поле "To" ("Куда"); 		</li> <li> <b>BCC</b> - поле "BCC" ("Скрытая копия");
	* 		</li> <li> <b>SUBJECT</b> - заголовок сообщения; 		</li> <li> <b>BODY_TYPE</b> - тип тела
	* почтового сообщения: "text" - текст; "html" - HTML; 		</li> <li> <b>MESSAGE</b> - тело
	* почтового сообщения. 	</li> </ul>
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $arr["ACTIVE"] = "Y";
	* $arr["EVENT_NAME"] = "ADV_CONTRACT_INFO";
	* $arr["LID"] = array("ru","en");
	* $arr["EMAIL_FROM"] = "#DEFAULT_EMAIL_FROM#";
	* $arr["EMAIL_TO"] = "#EMAIL_TO#";
	* $arr["BCC"] = "#BCC#";
	* $arr["SUBJECT"] = "Тема сообщения";
	* $arr["BODY_TYPE"] = "text";
	* $arr["MESSAGE"] = "
	* Текст сообщения
	* ";
	* 
	* $emess = new CEventMessage;
	* <b>$emess-&gt;Add</b>($arr);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/index.php">Поля шаблона
	* почтового сообщения</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/update.php">CEventMessage::Update</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/add.php
	* @author Bitrix
	*/
	public function Add($arFields)
	{
		unset($arFields["ID"]);

		if(!$this->CheckFields($arFields))
			return false;

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";

		$arLID = array();
		if(is_set($arFields, "LID"))
		{
			if(is_array($arFields["LID"]))
				$arLID = $arFields["LID"];
			else
				$arLID[] = $arFields["LID"];

			$arFields["LID"] = false;
			foreach($arLID as $v)
			{
				$arFields["LID"] = $v;
			}
		}

		$arATTACHMENT_FILE = array();
		if(is_set($arFields, "ATTACHMENT_FILE"))
		{
			if(is_array($arFields["ATTACHMENT_FILE"]))
				$arATTACHMENT_FILE = $arFields["ATTACHMENT_FILE"];
			else
				$arATTACHMENT_FILE[] = $arFields["ATTACHMENT_FILE"];

			$arATTACHMENT_FILE_tmp = array();
			foreach($arATTACHMENT_FILE as $v)
			{
				$v = intval($v);
				$arATTACHMENT_FILE_tmp[] = $v;
			}
			$arATTACHMENT_FILE = $arATTACHMENT_FILE_tmp;

			unset($arFields['ATTACHMENT_FILE']);
		}

		$arDeleteFields = array(
			'EVENT_MESSAGE_TYPE_ID', 'EVENT_MESSAGE_TYPE_ID',
			'EVENT_MESSAGE_TYPE_NAME', 'EVENT_MESSAGE_TYPE_EVENT_NAME',
			'SITE_ID', 'EVENT_TYPE'
		);
		foreach($arDeleteFields as $deleteField)
			if(array_key_exists($deleteField, $arFields))
				unset($arFields[$deleteField]);


		$ID = false;
		$result = Mail\Internal\EventMessageTable::add($arFields);
		if ($result->isSuccess())
		{
			$ID = $result->getId();

			if(count($arLID)>0)
			{
				Mail\Internal\EventMessageSiteTable::delete($ID);
				$resultDb = \Bitrix\Main\SiteTable::getList(array(
					'select' => array('LID'),
					'filter' => array('LID' => $arLID),
				));
				while($arResultSite = $resultDb->fetch())
				{
					Mail\Internal\EventMessageSiteTable::add(array(
						'EVENT_MESSAGE_ID' => $ID,
						'SITE_ID' => $arResultSite['LID'],
					));
				}
			}

			if(count($arATTACHMENT_FILE)>0)
			{
				foreach($arATTACHMENT_FILE as $file_id)
				{
					Mail\Internal\EventMessageAttachmentTable::add(array(
						'EVENT_MESSAGE_ID' => $ID,
						'FILE_ID' => $file_id,
					));
				}
			}
		}

		return $ID;
	}

	
	/**
	* <p>Изменяет почтовый шаблон с кодом <i>id</i>. Возвращает <i>true</i>, если изменение прошло успешно, при возникновении ошибки метод вернет <i>false</i>, а в свойстве LAST_ERROR объекта будет содержаться текст ошибки. Нестатический метод.</p>
	*
	*
	* @param mixed $intid  ID изменяемой записи.
	*
	* @param array $fields  Массив значений полей вида array("поле"=&gt;"значение" [, ...]).
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* if($REQUEST_METHOD=="POST" &amp;&amp; (strlen($save)&gt;0 || strlen($apply)&gt;0)&amp;&amp; $MAIN_RIGHT=="W")
	* {
	*     $em = new CEventMessage;
	*     $arFields = Array(
	*         "ACTIVE"        =&gt; $ACTIVE,
	*         "EVENT_NAME"    =&gt; $EVENT_NAME,
	*         "LID"           =&gt; $LID,
	*         "EMAIL_FROM"    =&gt; $EMAIL_FROM,
	*         "EMAIL_TO"      =&gt; $EMAIL_TO,
	*         "BCC"           =&gt; $BCC,
	*         "SUBJECT"       =&gt; $SUBJECT,
	*         "MESSAGE"       =&gt; $MESSAGE,
	*         "BODY_TYPE"     =&gt; $BODY_TYPE
	*         );
	*     if($ID&gt;0)
	*     {
	*         $res = <b>$em-&gt;Update</b>($ID, $arFields);
	*     }
	*     else
	*     {
	*         $ID = $em-&gt;Add($arFields);
	*         $res = ($ID&gt;0);
	*     }
	*     if(!$res)
	*     {
	*         $strError .= $em-&gt;LAST_ERROR."&lt;br&gt;";
	*         $bVarsFromForm = true;
	*     }
	*     else
	*     {
	*         if (strlen($save)&gt;0) 
	*             LocalRedirect(BX_ROOT."/admin/message_admin.php?lang=".LANGUAGE_ID);
	*         else
	*             LocalRedirect(BX_ROOT."/admin/message_edit.php?lang=".LANGUAGE_ID."&amp;ID=".$ID);
	*     }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/index.php">Поля шаблона
	* почтового сообщения</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/add.php">CEventMessage::Add</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/update.php
	* @author Bitrix
	*/
	public function Update($ID, $arFields)
	{
		global $DB;

		if(!$this->CheckFields($arFields, $ID))
			return false;

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";

		$arLID = array();
		if(is_set($arFields, "LID"))
		{
			if(is_array($arFields["LID"]))
				$arLID = $arFields["LID"];
			else
				$arLID[] = $arFields["LID"];

			$arFields["LID"] = false;
			foreach($arLID as $v)
			{
				$arFields["LID"] = $v;
			}
		}

		$arATTACHMENT_FILE = array();
		if(is_set($arFields, "ATTACHMENT_FILE"))
		{
			if(is_array($arFields["ATTACHMENT_FILE"]))
				$arATTACHMENT_FILE = $arFields["ATTACHMENT_FILE"];
			else
				$arATTACHMENT_FILE[] = $arFields["ATTACHMENT_FILE"];

			$arATTACHMENT_FILE_tmp = array();
			foreach($arATTACHMENT_FILE as $v)
			{
				$v = intval($v);
				$arATTACHMENT_FILE_tmp[] = $v;
			}
			$arATTACHMENT_FILE = $arATTACHMENT_FILE_tmp;

			unset($arFields['ATTACHMENT_FILE']);
		}

		if(array_key_exists('NAME', $arFields))
			unset($arFields['NAME']);

		$ID = intval($ID);
		Mail\Internal\EventMessageTable::update($ID, $arFields);
		if(count($arLID)>0)
		{
			Mail\Internal\EventMessageSiteTable::delete($ID);
			$resultDb = \Bitrix\Main\SiteTable::getList(array(
				'select' => array('LID'),
				'filter' => array('LID' => $arLID),
			));
			while($arResultSite = $resultDb->fetch())
			{
				Mail\Internal\EventMessageSiteTable::add(array(
					'EVENT_MESSAGE_ID' => $ID,
					'SITE_ID' => $arResultSite['LID'],
				));
			}
		}

		if(count($arATTACHMENT_FILE)>0)
		{
			foreach($arATTACHMENT_FILE as $file_id)
			{
				Mail\Internal\EventMessageAttachmentTable::add(array(
					'EVENT_MESSAGE_ID' => $ID,
					'FILE_ID' => $file_id,
				));
			}
		}

		return true;
	}

	///////////////////////////////////////////////////////////////////
	// Query
	///////////////////////////////////////////////////////////////////
	
	/**
	* <p>Возвращает почтовый шаблон по его коду <i>id</i> в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. Статический метод.</p>
	*
	*
	* @param mixed $intid  ID шаблона.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $rsEM = <b>CEventMessage::GetByID</b>($ID);
	* $arEM = $rsEM-&gt;Fetch();
	* echo "&lt;pre&gt;"; print_r($arEM); echo "&lt;/pre&gt;";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/index.php">Поля шаблона
	* почтового сообщения</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/getlist.php">CEventMessage::GetList</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">Класс CDBResult</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		return CEventMessage::GetList($o = "", $b = "", array("ID"=>$ID));
	}

	public static function GetSite($event_message_id)
	{
		$event_message_id = intval($event_message_id);

		$resultDb = Mail\Internal\EventMessageSiteTable::getList(array(
			'select' => array('*', ''=> 'SITE.*'),
			'filter' => array('EVENT_MESSAGE_ID' => $event_message_id),
			'runtime' => array(
				'SITE' => array(
					'data_type' => 'Bitrix\Main\Site',
					'reference' => array('=this.SITE_ID' => 'ref.LID'),
				)
			)
		));

		return new CDBResult($resultDb);
	}

	public static function GetLang($event_message_id)
	{
		return CEventMessage::GetSite($event_message_id);
	}

	
	/**
	* <p>Удаляет почтовый шаблон. Если шаблон удален успешно, то возвращается объект <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>, в противном случае - <i>false</i>. Статический метод.</p>
	*
	*
	* @param mixed $intid  ID шаблона.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* if(intval($del_id)&gt;0 &amp;&amp; $MAIN_RIGHT=="W")
	* {
	*     $emessage = new CEventMessage;
	*     $DB-&gt;StartTransaction();
	*     if(!<b>$emessage-&gt;Delete</b>(intval($del_id)))
	*     {
	*         $DB-&gt;Rollback();
	*         $strError.=GetMessage("DELETE_ERROR");
	*     }
	*     else $DB-&gt;Commit();
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">Класс CDBResult</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/events/oneventmessagedelete.php">Событие
	* "OnEventMessageDelete"</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeeventmessagedelete.php">Событие
	* "OnBeforeEventMessageDelete"</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		/**
		 * @global CMain $APPLICATION
		 * @global CDatabase $DB
		 */
		global $APPLICATION;
		$ID = Intval($ID);

		foreach(GetModuleEvents("main", "OnBeforeEventMessageDelete", true) as $arEvent)
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

		@set_time_limit(600);

		//check module event for OnDelete
		foreach(GetModuleEvents("main", "OnEventMessageDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID));

		Mail\Internal\EventMessageSiteTable::delete($ID);
		$result = Mail\Internal\EventMessageTable::delete($ID);

		if($result->isSuccess())
		{
			$res = new CDBResultEventMultiResult();
			$res->affectedRowsCount = 1;
		}
		else
		{
			$res = false;
		}

		return $res;
	}

	public static function GetListDataModifier($data)
	{
		if(!isset($data['EVENT_MESSAGE_TYPE_ID']) || intval($data['EVENT_MESSAGE_TYPE_ID'])<=0)
		{
			$data['EVENT_TYPE'] = $data['EVENT_NAME'];
		}
		else
		{
			$data['EVENT_TYPE'] = '[ '.$data['EVENT_MESSAGE_TYPE_EVENT_NAME'].' ] '.$data['EVENT_MESSAGE_TYPE_NAME'];

			unset($data['EVENT_MESSAGE_TYPE_ID']);
			unset($data['EVENT_MESSAGE_TYPE_NAME']);
			unset($data['EVENT_MESSAGE_TYPE_EVENT_NAME']);
		}
	}

	
	/**
	* <p>Возвращает список почтовых шаблонов в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. Статический метод.</p>
	*
	*
	* @param string &$by = "id" Ссылка на переменную с полем для сортировки, может принимать
	* значения: 	<ul> <li> <b>site_id</b> - идентификатор сайта;</li> 		<li> <b>subject</b> -
	* тема;</li> 		<li> <b>timestamp_x</b> - дата изменения;</li> 		<li> <b>event_name</b> - тип
	* события;</li> 		<li> <b>id</b> - ID шаблона;</li> 		<li> <b>active</b> - активность;</li> 	</ul>
	*
	* @param string &$order = "desc" Ссылка на переменную с порядком сортировки, может принимать
	* значения:	 <ul> <li> <b>asc</b> - по возрастанию;</li> 	<li> <b>desc</b> - по
	* убыванию;</li> </ul>
	*
	* @param array $filter  Массив вида array("фильтруемое поле"=&gt;"значение" [, ...]), может
	* принимать значения: <ul> <li> <b>ID</b> - ID шаблона;</li> 	<li> <b>TYPE</b> - код и
	* заголовок типа события (допустима <a
	* href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>);</li> 	<li> <b>TYPE_ID</b>
	* - код типа события (допустима <a
	* href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>);</li> 	<li>
	* <b>TIMESTAMP_1</b> - левая часть интервала ("c") для поиска по дате
	* изменения;</li> 	<li> <b>TIMESTAMP_2</b> - правая часть интервала ("по") для
	* поиска по дате изменения;</li> 	<li> <b>SITE_ID</b> - идентификатор сайта
	* (допустимо задание массива для поиска по логике "или", либо
	* допустимо использование <a
	* href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложной логики</a>);</li> 	<li> <b>ACTIVE</b> -
	* флаг активности (Y|N);</li> 	<li> <b>FROM</b> - поле "От кого" (допустима <a
	* href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>);</li> 	<li> <b>TO</b> -
	* поле "Кому" (допустима <a href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная
	* логика</a>);</li> 	<li> <b>BCC</b> - поле "Скрытая копия" (допустима <a
	* href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>);</li> 	<li> <b>SUBJECT</b>
	* - по теме сообщения (допустима <a
	* href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>);</li> 	<li>
	* <b>BODY_TYPE</b> - по типу тела сообщения (text|html);</li> 	<li> <b>BODY</b> - по телу
	* сообщения (допустима <a href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная
	* логика</a>);</li> </ul>
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $arFilter = Array(
	*     "ID"            =&gt; "12 | 134",
	*     "TYPE"          =&gt; "контракт &amp; рекл",
	*     "TYPE_ID"       =&gt; array("ADV_BANNER", "ADV_CONTRACT"),
	*     "TIMESTAMP_1"   =&gt; "12.11.2001",
	*     "TIMESTAMP_2"   =&gt; "12.11.2005",
	*     "SITE_ID"       =&gt; "ru | en",
	*     "ACTIVE"        =&gt; "Y",
	*     "FROM"          =&gt; "bitrixsoft.ru",
	*     "TO"            =&gt; "#TO#",
	*     "BCC"           =&gt; "admin",
	*     "SUBJECT"       =&gt; "конктракт",
	*     "BODY_TYPE"     =&gt; "text",
	*     "BODY"          =&gt; "auto"
	*     );
	* $rsMess = <b>CEventMessage::GetList</b>($by="site_id", $order="desc", $arFilter);
	* $is_filtered = $rsMess-&gt;is_filtered;
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/index.php">Поля шаблона
	* почтового сообщения</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/getbyid.php">CEventMessage::GetByID</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">Класс CDBResult</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/getlist.php
	* @author Bitrix
	*/
	public static function GetList(&$by, &$order, $arFilter=Array())
	{
		$arSearch = Array();
		$arSqlSearch = Array();
		$strSqlSearch = "";
		$bIsLang = false;
		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				if(is_array($val))
				{
					if(count($val) <= 0)
						continue;
				}
				else
				{
					if( (strlen($val) <= 0) || ($val === "NOT_REF") )
						continue;
				}
				$match_value_set = array_key_exists($key."_EXACT_MATCH", $arFilter);
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						if($match == 'Y') $val = '%'.$val.'%';
						$arSearch['%='.$key] = $val;
						break;
					case "TYPE":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						if($match == 'Y') $val = '%'.$val.'%';
						$arSearch[] = array('LOGIC' => 'OR', 'EVENT_NAME' => $val, 'EVENT_MESSAGE_TYPE.NAME' => $val);
						break;
					case "EVENT_NAME":
					case "TYPE_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						if($match == 'Y') $val = '%'.$val.'%';
						$arSearch['%=EVENT_NAME'] = $val;
						break;
					case "TIMESTAMP_1":
						$arSqlSearch[] = "M.TIMESTAMP_X>=TO_DATE('".FmtDate($val, "D.M.Y")." 00:00:00','dd.mm.yyyy hh24:mi:ss')";
						$arSearch['>=TIMESTAMP_X'] = $val." 00:00:00";
						break;
					case "TIMESTAMP_2":
						$arSqlSearch[] = "M.TIMESTAMP_X<=TO_DATE('".FmtDate($val, "D.M.Y")." 23:59:59','dd.mm.yyyy hh24:mi:ss')";
						$arSearch['<=TIMESTAMP_X'] = $val." 23:59:59";
						break;
					case "LID":
					case "LANG":
					case "SITE_ID":
						$bIsLang = true;
						$arSearch["=SITE_ID"] = $val;
						break;
					case "ACTIVE":
						$arSearch['='.$key] = $val;
						break;
					case "FROM":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						if($match == 'Y') $val = '%'.$val.'%';
						$arSearch['%=EMAIL_FROM'] = $val;
						break;
					case "TO":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						if($match == 'Y') $val = '%'.$val.'%';
						$arSearch['%=EMAIL_TO'] = $val;
						break;
					case "BCC":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						if($match == 'Y') $val = '%'.$val.'%';
						$arSearch['%='.$key] = $val;
						break;
					case "SUBJECT":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						if($match == 'Y') $val = '%'.$val.'%';
						$arSearch['%='.$key] = $val;
						break;
					case "BODY_TYPE":
						$arSearch[$key] = ($val=="text") ? 'text' : 'html';
						break;
					case "BODY":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						if($match == 'Y') $val = '%'.$val.'%';
						$arSearch['%=MESSAGE'] = $val;
						break;
				}
			}
		}

		if ($by == "id") $strSqlOrder = "ID";
		elseif ($by == "active") $strSqlOrder = "ACTIVE";
		elseif ($by == "event_name") $strSqlOrder = "EVENT_NAME";
		elseif ($by == "from") $strSqlOrder = "EMAIL_FROM";
		elseif ($by == "to") $strSqlOrder = "EMAIL_TO";
		elseif ($by == "bcc") $strSqlOrder = "BCC";
		elseif ($by == "body_type") $strSqlOrder = "BODY_TYPE";
		elseif ($by == "subject") $strSqlOrder = "SUBJECT";
		else
		{
			$strSqlOrder = "ID";
			$by = "id";
		}

		if ($order!="asc")
		{
			$strSqlOrderBy = "DESC";
			$order = "desc";
		}
		else
		{
			$strSqlOrderBy = "ASC";
			$order = "asc";
		}

		$arSelect = array(
			'*',
			'EVENT_MESSAGE_TYPE_ID' => 'EVENT_MESSAGE_TYPE.ID',
			'EVENT_MESSAGE_TYPE_NAME' => 'EVENT_MESSAGE_TYPE.NAME',
			'EVENT_MESSAGE_TYPE_EVENT_NAME' => 'EVENT_MESSAGE_TYPE.EVENT_NAME',
		);

		if($bIsLang)
		{
			$arSelect['SITE_ID'] = 'EVENT_MESSAGE_SITE.SITE_ID';
		}
		else
		{
			$arSelect['SITE_ID'] = 'LID';
		}

		$resultDb = Mail\Internal\EventMessageTable::getList(array(
			'select' => $arSelect,
			'filter' => $arSearch,
			'order' => array($strSqlOrder => $strSqlOrderBy),
			'runtime' => array(
				'EVENT_MESSAGE_TYPE' => array(
					'data_type' => 'Bitrix\Main\Mail\Internal\EventType',
					'reference' => array('=this.EVENT_NAME' => 'ref.EVENT_NAME', '=ref.LID' => new \Bitrix\Main\DB\SqlExpression('?', LANGUAGE_ID)),
				),
			)
		));
		$resultDb->addFetchDataModifier(array('CEventMessage', 'GetListDataModifier'));
		$res = new CDBResult($resultDb);

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$res->is_filtered = (IsFiltered($strSqlSearch));

		return $res;
	}
}


/**
 * <b>CEventType</b> - класс для работы с типами почтовых событий.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventtype/index.php
 * @author Bitrix
 */
class CEventType
{
	public static function CheckFields($arFields = array(), $action = "ADD", $ID = array())
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$arFilter = array();
		$aMsg = array();
		//ID, LID, EVENT_NAME, NAME, DESCRIPTION, SORT
		if ($action == "ADD")
		{
			if (empty($arFields["EVENT_NAME"]))
				$aMsg[] = array("id"=>"EVENT_NAME_EMPTY", "text"=>GetMessage("EVENT_NAME_EMPTY"));

			if(!is_set($arFields, "LID") && is_set($arFields, "SITE_ID"))
				$arFields["LID"] = $arFields["SITE_ID"];
			if (is_set($arFields, "LID") && empty($arFields["LID"]))
				$aMsg[] = array("id"=>"LID_EMPTY", "text"=>GetMessage("LID_EMPTY"));

			if (empty($aMsg))
			{
				$db_res = CEventType::GetList(array("LID" => $arFields["LID"], "EVENT_NAME" => $arFields["EVENT_NAME"]));
				if ($db_res && $db_res->Fetch())
				{
					$aMsg[] = array("id"=>"EVENT_NAME_EXIST", "text"=>str_replace(
						array("#SITE_ID#", "#EVENT_NAME#"),
						array($arFields["LID"], $arFields["EVENT_NAME"]),
						GetMessage("EVENT_NAME_EXIST")));
				}
			}
		}
		elseif ($action == "UPDATE")
		{
			if (empty($ID) && (empty($ID["ID"]) || (empty($ID["EVENT_NAME"]))))
			{
				if (empty($ID))
					$aMsg[] = array("id"=>"EVENT_ID_EMPTY", "text"=>GetMessage("EVENT_ID_EMPTY"));
				else
					$aMsg[] = array("id"=>"EVENT_NAME_LID_EMPTY", "text"=>GetMessage("EVENT_ID_EMPTY"));
			}

			if (empty($aMsg) && is_set($arFields, "EVENT_NAME") && (is_set($arFields, "LID")))
			{
				if (is_set($arFields, "EVENT_NAME"))
					$arFilter["EVENT_NAME"] = $arFields["EVENT_NAME"];
				if (is_set($arFields, "LID"))
					$arFilter["LID"] = $arFields["LID"];

				if (!empty($arFilter) && (count($arFilter) < 2) && is_set($arFilter, "LID"))
				{
					unset($arFields["LID"]);
				}
				else
				{
					$db_res = CEventType::GetList($arFilter);

					if ($db_res && ($res = $db_res->Fetch()))
					{
						if (($action == "UPDATE") &&
							((is_set($ID, "EVENT_NAME") && is_set($ID, "LID") &&
								(($res["EVENT_NAME"] != $ID["EVENT_NAME"]) || ($res["LID"] != $ID["LID"]))) ||
								(is_set($ID, "ID") && $res["ID"] != $ID["ID"]) ||
								(is_set($ID, "EVENT_NAME") && ($res["EVENT_NAME"] != $ID["EVENT_NAME"]))))
						{
							$aMsg[] = array("id"=>"EVENT_NAME_EXIST", "text"=>str_replace(
								array("#SITE_ID#", "#EVENT_NAME#"),
								array($arFields["LID"], $arFields["EVENT_NAME"]),
								GetMessage("EVENT_NAME_EXIST")));
						}
					}
				}
			}
		}
		else
		{
			$aMsg[] = array("id"=>"ACTION_EMPTY", "text"=>GetMessage("ACTION_EMPTY"));
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		return true;
	}

	
	/**
	* <p>Добавляет тип почтового события. Возвращает ID вставленного типа. При возникновении ошибки метод вернет <i>false</i>, а в свойстве LAST_ERROR объекта будет содержаться текст ошибки. Статический метод.</p>
	*
	*
	* @param array $fields  Массив значений <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventtype/index.php">полей</a> вида
	* array("поле"=&gt;"значение" [, ...]). В качестве "полей" допустимо
	* использовать: 	         <ul> <li> <b>LID</b> - язык интерфейса</li>                    <li>
	* <b>EVENT_NAME</b> - идентификатор типа почтового события 		</li>                   
	* <li> <b>NAME</b> - заголовок типа почтового события 		</li>                    <li>
	* <b>DESCRIPTION</b> - описание задающее поля типа почтового события 	</li>       
	*  </ul>
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* function UET($EVENT_NAME, $NAME, $LID, $DESCRIPTION)
	* {
	*     $et = new CEventType;
	*     <b>$et-&gt;Add</b>(array(
	*         "LID"           =&gt; $LID,
	*         "EVENT_NAME"    =&gt; $EVENT_NAME,
	*         "NAME"          =&gt; $NAME,
	*         "DESCRIPTION"   =&gt; $DESCRIPTION
	*         ));
	* }
	* 
	* UET(
	* "ADV_BANNER_STATUS_CHANGE","Изменился статус баннера","ru",
	* "
	* #EMAIL_TO# - EMail получателя сообщения (#OWNER_EMAIL#)
	* #ADMIN_EMAIL# - EMail пользователей имеющих роль \"менеджер баннеров\" и \"администратор\"
	* #ADD_EMAIL# - EMail пользователей имеющих право управления баннерами контракта
	* #STAT_EMAIL# - EMail пользователей имеющих право просмотра баннеров конракта
	* #EDIT_EMAIL# - EMail пользователей имеющих право модификации некоторых полей контракта
	* #OWNER_EMAIL# - EMail пользователей имеющих какое либо право на контракт
	* #BCC# - скрытая копия (#ADMIN_EMAIL#)
	* #ID# - ID баннера
	* #CONTRACT_ID# - ID контракта
	* #CONTRACT_NAME# - заголовок контракта
	* #TYPE_SID# - ID типа
	* #TYPE_NAME# - заголовок типа
	* #STATUS# - статус
	* #STATUS_COMMENTS# - комментарий к статусу
	* #NAME# - заголовок баннера
	* #GROUP_SID# - группа баннера
	* #INDICATOR# - показывается ли баннер на сайте ?
	* #ACTIVE# - флаг активности баннера [Y | N]
	* #MAX_SHOW_COUNT# - максимальное количество показов баннера
	* #SHOW_COUNT# - сколько раз баннер был показан на сайте
	* #MAX_CLICK_COUNT# - максимальное количество кликов на баннер
	* #CLICK_COUNT# - сколько раз кликнули на баннер
	* #DATE_LAST_SHOW# - дата последнего показа баннера
	* #DATE_LAST_CLICK# - дата последнего клика на баннер
	* #DATE_SHOW_FROM# - дата начала показа баннера
	* #DATE_SHOW_TO# - дата окончания показа баннера
	* #IMAGE_LINK# - ссылка на изображение баннера
	* #IMAGE_ALT# - текст всплывающей подсказки на изображении
	* #URL# - URL на изображении
	* #URL_TARGET# - где развернуть URL изображения
	* #CODE# - код баннера
	* #CODE_TYPE# - тип кода баннера (text | html)
	* #COMMENTS# - комментарий к баннеру
	* #DATE_CREATE# - дата создания баннера
	* #CREATED_BY# - кем был создан баннер
	* #DATE_MODIFY# - дата изменения баннера
	* #MODIFIED_BY# - кем изменен баннер
	* "
	* );
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventtype/index.php">Поля типа
	* почтового события</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventtype/add.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		if(!is_set($arFields, "LID") && is_set($arFields, "SITE_ID"))
			$arFields["LID"] = $arFields["SITE_ID"];

		unset($arFields["ID"]);

		if (CEventType::CheckFields($arFields))
		{
			$result = Mail\Internal\EventTypeTable::add($arFields);

			return $result->getId();
		}
		return false;
	}

	
	/**
	* <p>Изменяет параметры типа почтового события. Возвращается объект класса CDBResult. При возникновении ошибки метод вернет <i>false</i>, а в свойстве LAST_ERROR объекта будет содержаться текст ошибки. Статический метод.</p>
	*
	*
	* @param array $arrayID  Массив значений ID почтовых событий, которые нужно изменить. В
	* массиве допустимо использовать: <ul> <li> <b>ID</b> - идентификатор типа
	* почтового события</li> <li> <b>LID</b> - язык интерфейса</li>  <li> <b>EVENT_NAME</b>  -
	* идентификатор почтового события </li>   </ul>
	*
	* @param array $fields  Массив значений <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventtype/index.php">полей</a> вида
	* array("поле"=&gt;"значение" [, ...]). В качестве "полей" допустимо
	* использовать: 	         <ul> <li> <b>LID</b> - язык интерфейса</li>                    <li>
	* <b>EVENT_NAME</b> - идентификатор типа почтового события </li>                    <li>
	* <b>NAME</b> - заголовок типа почтового события 		</li>                    <li>
	* <b>DESCRIPTION</b> - описание задающее поля типа почтового события 	</li>       
	*  </ul>
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $arType = array( 
	*     "SORT" =&gt; $_POST["SORT"], 
	*     "NAME" =&gt; $_POST["NAME"], 
	*     "DESCRIPTION" =&gt; $_POST["DESCRIPTION"], 
	*     "LID" =&gt; $_POST["LID"], 
	*     "EVENT_NAME" =&gt; $_POST["EVENT_NAME"], 
	* ); 
	* CEventType::Update(array("ID" =&gt; $_POST["ID"]), $arType);
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventtype/index.php">Поля типа
	* почтового события</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventtype/update.php
	* @author Bitrix
	*/
	public static function Update($arID = array(), $arFields = array())
	{
		$ID = array();

		// update event type by ID, or (LID+EVENT_NAME)
		if (is_array($arID) && !empty($arID))
		{
			foreach ($arID as $key => $val)
			{
				if (in_array($key, array("ID", "LID", "EVENT_NAME")))
					$ID[$key] = $val;
			}
		}
		if (!empty($ID) && CEventType::CheckFields($arFields, "UPDATE", $ID))
		{
			unset($arFields["ID"]);

			$affectedRowsCount = 0;
			$result = false;
			$listDb = Mail\Internal\EventTypeTable::getList(array(
				'select' => array('ID'),
				'filter' => $ID
			));
			while($arListId = $listDb->fetch())
			{
				$result = Mail\Internal\EventTypeTable::update($arListId['ID'], $arFields);
				$affectedRowsCount += $result->getAffectedRowsCount();
			}

			$res = new CDBResultEventMultiResult();
			$res->affectedRowsCount = $affectedRowsCount;

			return $res;
		}
		return false;
	}

	
	/**
	* <p>Удаляет тип почтового события. Возвращается объект класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. Статический метод.</p>
	*
	*
	* @param string $EVENT_NAME  Тип почтового события.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $et = new CEventType;
	* <b>$et-&gt;Delete</b>("ADV_BANNER_STATUS_CHANGE");
	* ?&gt;С версии 6.0.3 возможно использование массива:CEventType::Delete(
	*  array (
	*   "ID" =&gt; 1,
	*   "LID"=&gt; "ru",
	*   "EVENT_NAME" =&gt; "EVENT_NAME",
	*   "NAME" =&gt; "NAME",
	*   "SORT" =&gt; 100500
	* )
	* );
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">Класс CDBResult</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventtype/delete.php
	* @author Bitrix
	*/
	public static function Delete($arID)
	{

		$ID = array();
		if (!is_array($arID))
			$arID = array("EVENT_NAME" => $arID);
		foreach ($arID as $k => $v)
		{
			if (!in_array(strToUpper($k), array("ID", "LID", "EVENT_NAME", "NAME", "SORT")))
				continue;
			$ID[$k] = $v;
		}

		if (!empty($ID))
		{
			$res = null;
			$affectedRowsCount = 0;
			$listDb = Mail\Internal\EventTypeTable::getList(array(
				'select' => array('ID'),
				'filter' => $ID
			));
			while($arListId = $listDb->fetch())
			{
				$result = Mail\Internal\EventTypeTable::delete($arListId['ID']);
				if($result->isSuccess())
				{
					$affectedRowsCount++;
				}
				else
				{
					$res = false;
					break;
				}
			}

			if($res === null)
			{
				$res = new CDBResultEventMultiResult();
				$res->affectedRowsCount = $affectedRowsCount;
			}

			return $res;
		}
		return false;
	}

	
	/**
	* <p>Возвращает список типов почтовых событий по фильтру <i>filter</i> в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. Статический метод.</p>
	*
	*
	* @param array $arFilter = array() Массив фильтрации вида array("фильтруемое поле"=&gt;"значение" [, ...]).
	* "Фильтруемое поле" может принимать значения: 		<ul> <li> <b>TYPE_ID</b> -
	* идентификатор типа события;</li> 		<li> <b>LID</b> - идентификатор языка;</li>
	* 		</ul>
	*
	* @param array $arOrder = array() Массив сортировки вида array("фильтруемое поле"=&gt;"значение" [, ...]).
	* "Фильтруемое поле" может принимать значения: 		<ul> <li> <b>TYPE_ID</b> -
	* идентификатор типа события;</li> 		<li> <b>LID</b> - идентификатор языка;</li>
	* 		</ul>
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $arFilter = array(
	*     "TYPE_ID" =&gt; "ADV_BANNER_STATUS_CHANGE",
	*     "LID"     =&gt; "ru"
	*     );
	* $rsET = <b>CEventType::GetList</b>($arFilter);
	* while ($arET = $rsET-&gt;Fetch())
	* {
	*     echo "&lt;pre&gt;"; print_r($arET); echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventtype/index.php">Поля типа
	* события</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventtype/getbyid.php">CEventType::GetByID</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">Класс CDBResult</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventtype/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arFilter=array(), $arOrder=array())
	{
		global $DB;

		$arSqlSearch = $arSqlOrder = array();

		foreach($arFilter as $key => $val)
		{
			$val_escaped = $DB->ForSQL($val);
			if($val_escaped == '')
				continue;

			$key = strtoupper($key);
			switch($key)
			{
				case "EVENT_NAME":
				case "TYPE_ID":
					$arSqlSearch["EVENT_NAME"] = (string) $val;
					break;
				case "LID":
					$arSqlSearch["LID"] = (string) $val;
					break;
				case "ID":
					$arSqlSearch["ID"] = intval($val);
					break;
			}
		}

		if(is_array($arOrder))
		{
			static $arFields = array("ID"=>1, "LID"=>1, "EVENT_NAME"=>1, "NAME"=>1, "SORT"=>1);
			foreach($arOrder as $by => $ord)
			{
				$by = strtoupper($by);
				$ord = strtoupper($ord);
				if(array_key_exists($by, $arFields))
					$arSqlOrder[$by] = ($ord == "DESC"? "DESC":"ASC");
			}
		}
		if(empty($arSqlOrder))
			$arSqlOrder['ID'] = 'ASC';

		$result = Mail\Internal\EventTypeTable::getList(array(
			'select' => array('ID', 'LID', 'EVENT_NAME', 'NAME', 'DESCRIPTION', 'SORT'),
			'filter' => $arSqlSearch,
			'order' => $arSqlOrder
		));


		$res = new CDBResult($result);

		return $res;
	}

	public static function GetListExFetchDataModifier($data)
	{
		if(isset($data['ID1']) && !isset($data['ID']))
		{
			$data['ID'] = $data['ID1'];
			unset($data['ID1']);
		}

		if(isset($data['EVENT_NAME1']) && !isset($data['EVENT_NAME']))
		{
			$data['EVENT_NAME'] = $data['EVENT_NAME1'];
			unset($data['EVENT_NAME1']);
		}

		return $data;
	}

	public static function GetListEx($arOrder = array(), $arFilter = array(), $arParams = array())
	{
		global $DB;

		$arSearch = $arSearch1 = $arSearch2 = array();
		$arSelect = array();

		$arSqlSearch = array();
		$strSqlSearch = "";
		$arSqlOrder = array();
		foreach($arFilter as $key => $val)
		{
			if(strlen($val) <= 0)
				continue;
			$val = $DB->ForSql($val);
			$key_res = CEventType::GetFilterOperation($key);
			$key = strToUpper($key_res["FIELD"]);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$strNOperation = $key_res["NOPERATION"];

			$arOperationReplace = array(
				'LIKE' => '=%',
				'QUERY' => '',
				'IN' => '',
			);

			switch($key)
			{
				case "EVENT_NAME":
				case "TYPE_ID":
					if ($strOperation == "LIKE")
						$val = "%".$val."%";
					$arSearch[] = array($strNOperation.'EVENT_NAME' => $val);
					break;
				case "DESCRIPTION":
				case "NAME":
					if ($strOperation == "LIKE")
						$val = "%".$val."%";
					$arSearch1[] = array($strNOperation.'EVENT_MESSAGE_TYPE.'.$key => $val);
					$arSearch2[] = array($strNOperation.$key => $val);
					break;
				case "LID":
					$arSearch1[] = array($strNOperation.'EVENT_MESSAGE_TYPE.'.$key => $val);
					$arSearch2[] = array($strNOperation.$key => $val);
					break;
				case "ID":
					$val = intVal($val);
					$arSearch1[] = array($strNOperation.'EVENT_MESSAGE_TYPE.'.$key => $val);
					$arSearch2[] = array($strNOperation.$key => $val);
					break;
				case "MESSAGE_ID":
					$val = intVal($val);
					$arSearch1[] = array($strNOperation."ID" => $val);
					$arSearch2[] = array($strNOperation.'EVENT_MESSAGE.ID' => $val);
					break;
			}
		}

		if (is_array($arOrder))
		{
			foreach($arOrder as $by=>$order)
			{
				$by = strtoupper($by);
				$order = strtoupper($order);
				$order = ($order <> "DESC"? "ASC" : "DESC");
				if($by == "EVENT_NAME" || $by == "ID")
					$arSqlOrder["EVENT_NAME"] = "EVENT_NAME1 ".$order;
			}
		}
		if(empty($arSqlOrder))
			$arSqlOrder["EVENT_NAME"] = "EVENT_NAME1 ASC";
		$strSqlOrder = " ORDER BY ".implode(", ", $arSqlOrder);

		$arSearch['!EVENT_NAME'] = null;
		$arQuerySelect = array('ID1' => 'EVENT_NAME', 'EVENT_NAME1' => 'EVENT_NAME');
		$query1 = new \Bitrix\Main\Entity\Query(Mail\Internal\EventMessageTable::getEntity());
		$query1->setSelect($arQuerySelect);
		$query1->setFilter(array_merge($arSearch, $arSearch1));
		$query1->registerRuntimeField('EVENT_MESSAGE_TYPE', array(
			'data_type' => 'Bitrix\Main\Mail\Internal\EventType',
			'reference' => array('=this.EVENT_NAME' => 'ref.EVENT_NAME'),
		));

		$query2 = new \Bitrix\Main\Entity\Query(Mail\Internal\EventTypeTable::getEntity());
		$query2->setSelect($arQuerySelect);
		$query2->setFilter(array_merge($arSearch, $arSearch2));
		$query2->registerRuntimeField('EVENT_MESSAGE', array(
			'data_type' => 'Bitrix\Main\Mail\Internal\EventMessage',
			'reference' => array('=this.EVENT_NAME' => 'ref.EVENT_NAME'),
		));

		$connection = \Bitrix\Main\Application::getConnection();
		$strSql = $query1->getQuery() . " UNION " . $query2->getQuery(). " ".$strSqlOrder;
		$db_res = $connection->query($strSql);
		$db_res->addFetchDataModifier(array('CEventType', 'GetListExFetchDataModifier'));


		$db_res = new _CEventTypeResult($db_res, $arParams);
		return $db_res;
	}

	///////////////////////////////////////////////////////////////////
	// selecting type
	///////////////////////////////////////////////////////////////////
	
	/**
	* <p>Возвращает тип почтового события в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. Статический метод.</p>
	*
	*
	* @param mixed $stringID  Идентификатор типа почтового события.
	*
	* @param string $LID  Идентификатор языка.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $rsET = <b>CEventType::GetByID</b>("ADV_BANNER_STATUS_CHANGE", "ru");
	* $arET = $rsET-&gt;Fetch();
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventtype/index.php">Поля типа
	* события</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventtype/getlist.php">CEventType::GetList</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">Класс CDBResult</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventtype/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID, $LID)
	{
		$result = Mail\Internal\EventTypeTable::getList(array(
			'filter' => array('LID' => $LID, 'EVENT_NAME' => $ID),
		));

		return new CDBResult($result);
	}

	public static function GetFilterOperation($key)
	{
		$strNegative = "N";
		if (substr($key, 0, 1)=="!")
		{
			$key = subStr($key, 1);
			$strNegative = "Y";
		}

		$strOrNull = "N";
		if (subStr($key, 0, 1)=="+")
		{
			$key = subStr($key, 1);
			$strOrNull = "Y";
		}

		if (subStr($key, 0, 2)==">=")
		{
			$key = subStr($key, 2);
			$strOperation = ">=";
			$strNOperation = ($strNegative == "Y" ? '<' : $strOperation);
		}
		elseif (subStr($key, 0, 1)==">")
		{
			$key = subStr($key, 1);
			$strOperation = ">";
			$strNOperation = ($strNegative == "Y" ? '<=' : $strOperation);
		}
		elseif (subStr($key, 0, 2)=="<=")
		{
			$key = subStr($key, 2);
			$strOperation = "<=";
			$strNOperation = ($strNegative == "Y" ? '>' : $strOperation);
		}
		elseif (subStr($key, 0, 1)=="<")
		{
			$key = subStr($key, 1);
			$strOperation = "<";
			$strNOperation = ($strNegative == "Y" ? '>=' : $strOperation);
		}
		elseif (subStr($key, 0, 1)=="@")
		{
			$key = subStr($key, 1);
			$strOperation = "IN";
			$strNOperation = ($strNegative == "Y" ? '' : '');
		}
		elseif (subStr($key, 0, 1)=="~")
		{
			$key = subStr($key, 1);
			$strOperation = "LIKE";
			$strNOperation = ($strNegative == "Y" ? '!=%' : '=%');
		}
		elseif (subStr($key, 0, 1)=="%")
		{
			$key = subStr($key, 1);
			$strOperation = "QUERY";
			$strNOperation = ($strNegative == "Y" ? '' : '');
		}
		else
		{
			$strOperation = "=";
			$strNOperation = ($strNegative == "Y" ? '!=' : '=');
		}

		return array("FIELD" => $key, "NEGATIVE" => $strNegative, "OPERATION" => $strOperation, "NOPERATION" => $strNOperation, "OR_NULL" => $strOrNull);
	}
}

class _CEventTypeResult extends CDBResult
{
	var $type = "type";
	var $LID = LANGUAGE_ID;
	var $SITE_ID = SITE_ID;

	public function __construct($res, $arParams = array())
	{
		$this->type = empty($arParams["type"]) ? "type" : $arParams["type"];
		$this->LID = empty($arParams["LID"]) ? LANGUAGE_ID : $arParams["LID"];
		$this->SITE_ID = empty($arParams["SITE_ID"]) ? SITE_ID : $arParams["SITE_ID"];
		parent::__construct($res);
	}

	public function Fetch()
	{
		$arr = array();
		$arr_lid = array();
		$arr_lids = array();

		if($res = parent::Fetch())
		{
			if ($this->type != "none")
			{
				$db_res_ = CEventType::GetList(array("EVENT_NAME" => $res["EVENT_NAME"]));
				if ($db_res_ && $res_ = $db_res_->Fetch())
				{
					do
					{
						$arr[$res_["ID"]] = $res_;
						$arr_lid[] = $res_["LID"];
						$arr_lids[$res_["LID"]] = $res_;
					}while($res_ = $db_res_->Fetch());
				}
				$res["ID"] = array_keys($arr);
				$res["LID"] = $arr_lid;

				$res["NAME"] = empty($arr_lids[$this->LID]["NAME"]) ? $arr_lids["en"]["NAME"] : $arr_lids[$this->LID]["NAME"];
				$res["SORT"] = empty($arr_lids[$this->LID]["SORT"]) ? $arr_lids["en"]["SORT"] : $arr_lids[$this->LID]["SORT"];
				$res["DESCRIPTION"] = empty($arr_lids[$this->LID]["DESCRIPTION"]) ? $arr_lids["en"]["DESCRIPTION"] : $arr_lids[$this->LID]["DESCRIPTION"];
				$res["TYPE"] = $arr;
				if ($this->type != "type")
				{
					$arr = array();
					$db_res_ = CEventMessage::GetList($sort = "sort", $by = "asc", array("EVENT_NAME" => $res["EVENT_NAME"]));
					if ($db_res_ && $res_ = $db_res_->Fetch())
					{
						do
						{
							$arr[$res_["ID"]] = $res_;
						}while($res_ = $db_res_->Fetch());
					}
					$res["TEMPLATES"] = $arr;
				}
			}
		}
		return $res;
	}
}

class CDBResultEventMultiResult extends CDBResult
{
	public $affectedRowsCount;

	public function AffectedRowsCount()
	{
		if($this->affectedRowsCount !== false)
			return $this->affectedRowsCount;
		else
			return parent::AffectedRowsCount();
	}
}
