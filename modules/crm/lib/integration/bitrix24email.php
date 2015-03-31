<?php
namespace Bitrix\Crm\Integration;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;

class Bitrix24Email
{
	private static $LANG_INCLUDED = false;
	public static function isEnabled()
	{
		return ModuleManager::isModuleInstalled('bitrix24');
	}
	public static function allowDisableSignature()
	{
		return Bitrix24Manager::isPaidAccount();
	}
	public static function isSignatureEnabled()
	{
		return strtoupper(\COption::GetOptionString('crm', 'enable_b24_email_sign', 'Y')) === 'Y';
	}
	public static function enableSignature($enable)
	{
		$enable = (bool)$enable;
		if($enable)
		{
			\COption::RemoveOption('crm', 'enable_b24_email_sign');
		}
		else
		{
			\COption::SetOptionString('crm', 'enable_b24_email_sign', 'N');
		}
	}
	public static function getSignatureExplanation()
	{
		self::includeLangFile();
		return self::isSignatureEnabled()
			? GetMessage('CRM_B24_EMAIL_SIGNATURE_ENABLED')
			: GetMessage('CRM_B24_EMAIL_SIGNATURE_DISABLED');
	}
	public static function addSignature(&$message, $contentType = 0)
	{
		if(!Bitrix24Manager::isEnabled())
		{
			return false;
		}

		self::includeLangFile();

		$text = '';
		if(!Bitrix24Manager::isPaidAccount())
		{
			$text = GetMessage('CRM_B24_EMAIL_FREE_LICENSE_SIGNATURE');
		}
		elseif(self::isSignatureEnabled())
		{
			$text = GetMessage('CRM_B24_EMAIL_PAID_LICENSE_SIGNATURE');
		}

		if($text === '')
		{
			return false;
		}

		if(!\CCrmContentType::IsDefined($contentType))
		{
			$contentType = \CCrmContentType::PlainText;
		}

		if($contentType === \CCrmContentType::BBCode)
		{
			$message .= "\n\n".$text;
		}
		elseif($contentType === \CCrmContentType::Html)
		{
			//Convert BBCODE to HTML
			$parser = new \CTextParser();
			$message .= "<br/><br/>".$parser->convertText($text);
		}
		elseif($contentType === \CCrmContentType::PlainText)
		{
			$message .= "\n\n".preg_replace('/\[[^\]]+\]/', '', $text);
		}

		return true;
	}
	private static function includeLangFile()
	{
		if(!self::$LANG_INCLUDED)
		{
			self::$LANG_INCLUDED = IncludeModuleLangFile(__FILE__);
		}
	}
}