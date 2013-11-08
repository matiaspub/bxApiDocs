<?php
/**
* Bitrix Framework
* @package bitrix
* @subpackage security
* @copyright 2001-2013 Bitrix
*/

final class CSecurityEventMessageFormatter
{
	const AUDIT_TYPE = "#AUDIT_TYPE#";
	const SITE_ID = "#SITE_ID#";
	const USER_INFO = "#USER_INFO#";
	const URL = "#URL#";
	const VARIABLE_NAME = "#VARIABLE_NAME#";
	const VARIABLE_VALUE = "#VARIABLE_VALUE#";
	const VARIABLE_VALUE_BASE64 = "#VARIABLE_VALUE_BASE64#";
	const REMOTE_ADDR = "#REMOTE_ADDR#";
	const USER_AGENT = "#USER_AGENT#";
	const USER_ID = "#USER_ID#";
	const BX24_HOST = "#BX24_HOST_NAME#";

	private $messageFormat = "";
	private $userInfoFormat = "";
	private $isUserInfoNeeded = false;
	private $isB64MessageNeeded = false;

	private $siteId = "";
	private $userInfo = "";
	private $url = "/";

	private static $messagePlaceholders = array(
		self::AUDIT_TYPE,
		self::SITE_ID,
		self::USER_INFO,
		self::URL,
		self::VARIABLE_NAME,
		self::VARIABLE_VALUE,
		self::VARIABLE_VALUE_BASE64
	);

	private static $userInfoPlaceholders = array(
		self::REMOTE_ADDR,
		self::USER_AGENT,
		self::USER_ID
	);

	/**
	 * @param string $messageFormat
	 * @param string $userInfoFormat
	 */
	public function __construct($messageFormat = "", $userInfoFormat = "")
	{
		if ($messageFormat)
			$this->messageFormat = $messageFormat;
		else
			$this->messageFormat = self::getDefaultMessageFormat();

		if ($userInfoFormat)
			$this->userInfoFormat = $userInfoFormat;
		else
			$this->userInfoFormat = self::getDefaultUserInfoFormat();

		$this->isUserInfoNeeded = strpos($messageFormat, self::USER_INFO) !== false;
		$this->isB64MessageNeeded = strpos($messageFormat, self::VARIABLE_VALUE_BASE64) !== false;

		if (!defined("ADMIN_SECTION") || ADMIN_SECTION != true)
			$this->siteId = SITE_ID;

		$this->userInfo = $this->getUserInfo();
		$this->url = preg_replace("/(&?sessid=[0-9a-z]+)/", "", $_SERVER["REQUEST_URI"]);
	}

	/**
	 * @return string
	 */
	public static function getDefaultMessageFormat()
	{
		return implode(
			' | ',
			array(self::AUDIT_TYPE, self::SITE_ID, self::USER_INFO, self::URL, self::VARIABLE_NAME, self::VARIABLE_VALUE_BASE64)
		);
	}

	/**
	 * @return string
	 */
	public static function getDefaultUserInfoFormat()
	{
		return implode(
			' | ',
			array(self::REMOTE_ADDR, self::USER_ID)
		);
	}

	/**
	 * @return array
	 */
	public static function getAvailableMessagePlaceholders()
	{
		return self::$messagePlaceholders;
	}

	/**
	 * @return array
	 */
	public static function getAvailableUserInfoPlaceholders()
	{
		return self::$userInfoPlaceholders;
	}

	/**
	 * @param string $auditType
	 * @param string $itemName
	 * @param string $itemDescription
	 * @return string
	 */
	public function format($auditType, $itemName, $itemDescription)
	{
		$description = substr($itemDescription,0,2000);

		$replacement = array(
			self::AUDIT_TYPE => $auditType,
			self::SITE_ID => $this->siteId,
			self::USER_INFO => $this->userInfo,
			self::URL => $this->url,
			self::VARIABLE_NAME => $itemName,
			self::VARIABLE_VALUE => $description
		);

		if ($this->isB64MessageNeeded)
			$replacement[self::VARIABLE_VALUE_BASE64] = base64_encode($description);

		if (defined("BX24_HOST_NAME"))
			$replacement[self::BX24_HOST] = BX24_HOST_NAME;

		return str_replace(
			array_keys($replacement),
			$replacement,
			$this->messageFormat
		);
	}

	/**
	 * @return string
	 */
	private function getUserInfo()
	{
		if (!$this->isUserInfoNeeded)
			return "";

		global $USER;

		if (is_object($USER))
			$userId = $USER->GetID();
		else
			$userId = 0;

		$replacement = array(
			self::REMOTE_ADDR => $_SERVER["REMOTE_ADDR"],
			self::USER_AGENT => $_SERVER["HTTP_USER_AGENT"],
			self::USER_ID => $userId
		);

		return str_replace(
			array_keys($replacement),
			$replacement,
			$this->userInfoFormat
		);
	}
}