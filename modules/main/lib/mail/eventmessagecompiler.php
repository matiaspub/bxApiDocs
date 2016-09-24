<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main\Mail;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Mail\Internal as MailInternal;
use Bitrix\Main\Config as Config;

class EventMessageCompiler
{
	protected $mailTo;
	protected $mailHeaders;
	protected $mailSubject;
	protected $mailBody;
	protected $mailCharset;
	protected $mailId;
	protected $mailContentType;
	protected $mailAttachment;

	protected $eventMessageId = null;
	protected $event = array();
	protected $eventMessageFields;
	protected $eventFields;
	protected $siteFields;
	protected $siteId;
	protected $languageId;
	protected $eventSiteFields;

	/*
	 *  'MESSAGE' = array(
			'BODY_TYPE' => 'html',
			'SUBJECT' => '',
			'EMAIL_TO' => '',
			'EMAIL_FROM' => '',
			'MESSAGE' => '',
			'ID' => '',
			'DATE_INSERT' => '',
			'SITE_TEMPLATE_ID' => '',
			'BCC' => '',
			'CC' => '',
			'REPLY_TO' => '',
			'IN_REPLY_TO' => '',
			'PRIORITY' => '',
			'ADDITIONAL_FIELD' => array(),
			'FILE' => array(),
		)
	 */
	public function __construct(array $arMessageParams)
	{
		if(!array_key_exists('FIELDS', $arMessageParams))
			throw new \Bitrix\Main\ArgumentTypeException("FIELDS");
		if(!array_key_exists('MESSAGE', $arMessageParams))
			throw new \Bitrix\Main\ArgumentTypeException("MESSAGE");
		if(!array_key_exists('SITE', $arMessageParams))
			throw new \Bitrix\Main\ArgumentTypeException("SITE");
		if(!array_key_exists('CHARSET', $arMessageParams))
			throw new \Bitrix\Main\ArgumentTypeException("CHARSET");


		$this->eventFields = $arMessageParams['FIELDS'];
		if(array_key_exists('EVENT', $arMessageParams))
			$this->event = $arMessageParams['EVENT'];

		$this->eventMessageFields = $arMessageParams['MESSAGE'];
		if(array_key_exists('ID', $arMessageParams['MESSAGE']))
			$this->eventMessageId = $arMessageParams['MESSAGE']['ID'];

		$this->siteFields = $this->getSiteFieldsArray($arMessageParams['SITE']);
		$this->eventSiteFields = array_merge($this->siteFields, $this->eventFields);
		foreach($this->eventSiteFields as $k => $v) $this->eventSiteFields[$k] = static::getFieldFlatValue($v);
		$this->setMailCharset($arMessageParams['CHARSET']);
	}

	/**
	 * @return EventMessageCompiler
	 */
	public static function createInstance(array $arMessageParams)
	{
		return new static($arMessageParams);
	}

	/**
	 * @param
	 */
	public function compile()
	{
		$this->setMailHeaders();

		$this->setMailId();
		$this->setMailContentType();

		$this->setMailTo();
		$this->setMailSubject();
		$this->setMailBody();
		$this->setMailAttachment();
	}
	/**
	 * @param
	 */
	protected function setMailBody()
	{
		$isHtml = $this->eventMessageFields["BODY_TYPE"] == "html";

		// replace placeholders in body
		$message = $this->eventMessageFields["MESSAGE_PHP"];
		if(empty($message) && !empty($this->eventMessageFields["MESSAGE"]))
		{
			$message = MailInternal\EventMessageTable::replaceTemplateToPhp($this->eventMessageFields["MESSAGE"]);
			if($this->eventMessageFields["ID"]>0)
				MailInternal\EventMessageTable::update($this->eventMessageFields["ID"], array('MESSAGE_PHP' => $message));
		}

		if(!empty($this->eventMessageFields['SITE_TEMPLATE_ID']))
			$siteTemplateId = $this->eventMessageFields['SITE_TEMPLATE_ID'];
		else
			$siteTemplateId = null;

		$themeCompiler = EventMessageThemeCompiler::createInstance($siteTemplateId, $message, $isHtml);

		if(empty($siteTemplateId))
			$siteTemplateId = ".default";

		// set context variables for components
		$themeCompiler->setSiteTemplateId($siteTemplateId);
		$themeCompiler->setSiteId($this->siteId);
		$themeCompiler->setLanguageId($this->languageId);
		Loc::setCurrentLang($themeCompiler->getLanguageId());

		// set values of $arParams, used by components
		$eventSiteFields = $this->eventSiteFields;
		if($isHtml)
		{
			foreach ($eventSiteFields as $fieldKey => $fieldValue)
				if (strpos($fieldValue, "<") === false)
					$eventSiteFields[$fieldKey] = nl2br($fieldValue);
		}
		$themeCompiler->setParams($eventSiteFields);
		// eval site template and body
		$themeCompiler->execute();
		// get eval result of site template and body
		$message = $themeCompiler->getResult();

		$this->mailBody = $message;

		Loc::setCurrentLang(null);
		EventMessageThemeCompiler::unsetInstance();
	}

	/**
	 * @return mixed
	 */
	public function getMailBody()
	{
		return $this->mailBody;
	}

	/**
	 * @param mixed $mailCharset
	 */
	protected function setMailCharset($mailCharset)
	{
		$this->mailCharset = $mailCharset;
	}

	/**
	 * @return mixed
	 */
	public function getMailCharset()
	{
		return $this->mailCharset;
	}

	/**
	 * @param
	 */
	protected function setMailContentType()
	{
		$this->mailContentType = $this->eventMessageFields["BODY_TYPE"];
	}

	/**
	 * @return mixed
	 */
	public function getMailContentType()
	{
		return $this->mailContentType;
	}

	/**
	 * @param mixed $mailAttachment
	 */
	protected function setMailAttachment()
	{
		$eventMessageAttachment = array();

		// Attach files from message template
		if(array_key_exists('FILE', $this->eventMessageFields))
		{
			$eventMessageAttachment = $this->eventMessageFields["FILE"];
		}

		// Attach files from event
		if(isset($this->event["FILE"]) && is_array($this->event["FILE"]))
		{
			$eventFileList = array();
			foreach($this->event["FILE"] as $fileId)
			{
				if(is_numeric($fileId))
				{
					$eventFileList[] = $fileId;
				}
			}

			$eventMessageAttachment = array_merge($eventMessageAttachment, $eventFileList);
		}


		if(count($eventMessageAttachment)>0)
		{
			$attachFileList = array();
			$eventMessageAttachment = array_unique($eventMessageAttachment);

			$strId = implode(',', $eventMessageAttachment);
			$conn = \Bitrix\Main\Application::getConnection();
			$strSql = "SELECT * FROM b_file WHERE ID IN(".$strId.")";
			$resultDb = $conn->query($strSql);
			while($file = $resultDb->fetch())
			{
				$tempFile = \CFile::MakeFileArray($file["ID"]);
				$attachFileList[] = array(
					'PATH' => $tempFile['tmp_name'],
					'ID' => $file['ID'],
					'CONTENT_TYPE' => $file['CONTENT_TYPE'],
					'NAME' => ($file['ORIGINAL_NAME'] <> "" ? $file['ORIGINAL_NAME'] : $file['FILE_NAME']),
				);
			}

			$this->mailAttachment = $attachFileList;
		}
	}

	/**
	 * @return mixed
	 */
	public function getMailAttachment()
	{
		return $this->mailAttachment;
	}



	/**
	 * @param
	 */
	protected function setMailHeaders()
	{
		$arMailFields = array();
		$messageFields = $this->eventMessageFields;
		$arFields = $this->eventFields + $this->siteFields;


		$arMailFields["From"] = $this->replaceTemplate($messageFields["EMAIL_FROM"], $arFields);

		if(isset($messageFields["BCC"]) && $messageFields["BCC"]!='')
		{
			$bcc = $this->replaceTemplate($messageFields["BCC"], $arFields);
			if(strpos($bcc, "@")!==false)
				$arMailFields["BCC"] = $bcc;
		}

		if(isset($messageFields["CC"]) && $messageFields["CC"]!='')
			$arMailFields["CC"] = $this->replaceTemplate($messageFields["CC"], $arFields);

		if(isset($messageFields["REPLY_TO"]) && $messageFields["REPLY_TO"]!='')
			$arMailFields["Reply-To"] = $this->replaceTemplate($messageFields["REPLY_TO"], $arFields);
		else
			$arMailFields["Reply-To"] = preg_replace("/(.*)\\<(.*)\\>/i", '$2', $arMailFields["From"]);

		if(isset($messageFields["IN_REPLY_TO"]) && $messageFields["IN_REPLY_TO"]!='')
			$arMailFields["In-Reply-To"] = $this->replaceTemplate($messageFields["IN_REPLY_TO"], $arFields);

		if(isset($messageFields["ADDITIONAL_FIELD"]) && is_array($messageFields['ADDITIONAL_FIELD']))
		{
			foreach($messageFields['ADDITIONAL_FIELD'] as $additionalField)
				$arMailFields[$additionalField['NAME']] = static::replaceTemplate($additionalField['VALUE'], $arFields);
		}

		if(isset($messageFields["PRIORITY"]) && $messageFields["PRIORITY"]!='')
			$arMailFields["X-Priority"] = $this->replaceTemplate($messageFields["PRIORITY"], $arFields);

		foreach($arFields as $f=>$v)
		{
			if(substr($f, 0, 1) == "=")
				$arMailFields[substr($f, 1)] = $v;
		}

		foreach($arMailFields as $k=>$v)
			$arMailFields[$k] = trim($v, "\r\n");

		//add those who want to receive all emails
		if(isset($this->event["DUPLICATE"]) && $this->event["DUPLICATE"]=="Y")
		{
			$all_bcc = Config\Option::get("main", "all_bcc", "");
			if(strpos($all_bcc, "@")!==false)
				$arMailFields["BCC"] .= (strlen($all_bcc)>0?(strlen($arMailFields["BCC"])>0?",":"").$all_bcc:"");
		}

		if(isset($this->event["EVENT_NAME"]))
			$arMailFields['X-EVENT_NAME'] = $this->event["EVENT_NAME"];


		$this->mailHeaders = $arMailFields;
	}

	/**
	 * @return mixed
	 */
	public function getMailHeaders()
	{
		return $this->mailHeaders;
	}

	/**
	 * @param
	 */
	protected function setMailId()
	{
		if(isset($this->event['ID']) && isset($this->eventMessageFields["ID"]))
			$this->mailId = $this->event['ID'].".".$this->eventMessageFields["ID"]." (".$this->event["DATE_INSERT"].")";
		else
			$this->mailId = '';
	}

	/**
	 * @return mixed
	 */
	public function getMailId()
	{
		return $this->mailId;
	}

	/**
	 * @param
	 */
	protected function setMailSubject()
	{
		$this->mailSubject = $this->replaceTemplate($this->eventMessageFields["SUBJECT"], $this->eventSiteFields);
	}

	/**
	 * @return mixed
	 */
	public function getMailSubject()
	{
		return $this->mailSubject;
	}

	/**
	 * @param
	 */
	protected function setMailTo()
	{
		$this->mailTo = $this->replaceTemplate($this->eventMessageFields["EMAIL_TO"], $this->eventSiteFields);
	}

	/**
	 * @return mixed
	 */
	public function getMailTo()
	{
		return $this->mailTo;
	}


	/**
	 * @param $str
	 * @param $ar
	 * @param bool $bNewLineToBreak
	 * @return mixed
	 */
	protected function replaceTemplate($str, $ar, $bNewLineToBreak=false)
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
	 * @param $sites
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	protected function getSiteFieldsArray($sites)
	{
		/*
		global $BX_EVENT_SITE_PARAMS;
		if($site_id !== false && isset($BX_EVENT_SITE_PARAMS[$site_id]))
			return $BX_EVENT_SITE_PARAMS[$site_id];
		*/


		$site_id = $sites[0];

		if(!empty($this->eventMessageId))
		{
			$messageSiteDb = MailInternal\EventMessageSiteTable::getList(array(
				'select' => array('SITE_ID'),
				'filter' => array(
					'EVENT_MESSAGE_ID' => $this->eventMessageId,
					'SITE_ID' => $sites
				)
			));
			if ($arMessageSite = $messageSiteDb->Fetch())
				$site_id = $arMessageSite['SITE_ID'];
		}

		$SITE_NAME = Config\Option::get("main", "site_name", $GLOBALS["SERVER_NAME"]);
		$SERVER_NAME = Config\Option::get("main", "server_name", $GLOBALS["SERVER_NAME"]);
		$DEFAULT_EMAIL_FROM = Config\Option::get("main", "email_from", "admin@".$GLOBALS["SERVER_NAME"]);

		if(strlen($site_id)>0)
		{
			$result = \Bitrix\Main\SiteTable::getById($site_id);
			if($arSite = $result->fetch())
			{
				$this->siteId = $arSite['LID'];
				$this->languageId = $arSite['LANGUAGE_ID'];

				$BX_EVENT_SITE_PARAMS[$site_id] = array(
					"SITE_NAME" => ($arSite["SITE_NAME"]<>''? $arSite["SITE_NAME"] : $SITE_NAME),
					"SERVER_NAME" => ($arSite["SERVER_NAME"]<>''? $arSite["SERVER_NAME"] : $SERVER_NAME),
					"DEFAULT_EMAIL_FROM" => ($arSite["EMAIL"]<>''? $arSite["EMAIL"] : $DEFAULT_EMAIL_FROM),
					"LANGUAGE_ID" => $arSite['LANGUAGE_ID'],
					"SITE_ID" => $arSite['LID'],
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

	/**
	 * @param string|array
	 * @return string
	 */
	protected static function getFieldFlatValue($value)
	{
		$flatValue = '';
		if(is_array($value))
		{
			foreach($value as $v)
				$flatValue .= ($flatValue <> ''? ', ':'').static::getFieldFlatValue($v);
		}
		else
			$flatValue = $value;

		return $flatValue;
	}

	/**
	 * @return string
	 */
	public function toString()
	{
		$result = '';
		$delimeter = str_repeat('-',5);

		$result .= $delimeter."CHARSET".$delimeter."\n".$this->getMailCharset()."\n\n";
		$result .= $delimeter."CONTENT_TYPE".$delimeter."\n".$this->getMailContentType()."\n\n";
		$result .= $delimeter."MESSAGE_ID".$delimeter."\n".$this->getMailId()."\n\n";
		$result .= $delimeter."TO".$delimeter."\n".$this->getMailTo()."\n\n";
		$result .= $delimeter."SUBJECT".$delimeter."\n".$this->getMailSubject()."\n\n";
		$result .= $delimeter."HEADERS".$delimeter."\n".print_r($this->getMailHeaders(), true)."\n\n";
		$result .= $delimeter."BODY".$delimeter."\n".$this->getMailBody()."\n\n";
		$result .= $delimeter."ATTACHMENT".$delimeter."\n".print_r($this->getMailAttachment(), true)."\n\n";

		return $result;
	}
}
