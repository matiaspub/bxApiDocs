<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\Context;
use Bitrix\Main\Localization\LanguageTable;

class AdminPage
	extends HtmlPage
{
	protected $languageId;

	static public function __construct()
	{
		parent::__construct();
	}

	protected function initializeRequest()
	{
		$this->initializeCulture();

		parent::initializeRequest();
	}

	protected function initializeCulture()
	{
		$language = $this->getCurrentLanguage();
		$this->languageId = $language["LID"];

		$culture = Context\Culture::wakeUp($language["CULTURE_ID"]);
		$this->setContextCulture($culture, $language["LID"]);
	}

	public function getLanguageId()
	{
		return $this->languageId;
	}

	protected function getCurrentLanguage()
	{
		$request = $this->getRequest();

		$defaultLang = $request->get("lang");
		if (empty($defaultLang))
			$defaultLang = Config\Option::get("main", "admin_lid", 'en');

		if (!empty($defaultLang))
		{
			$recordset = LanguageTable::getById($defaultLang);
			if (($record = $recordset->fetch()))
				return $record;
		}

		$recordset = LanguageTable::getList(
			array(
				'filter' => array('ACTIVE' => 'Y'),
				'order' => array('DEF' => 'DESC', 'SORT' => 'ASC'),
				'select' => array('*')
			)
		);
		if (($record = $recordset->fetch()))
			return $record;

		throw new SystemException('Language is not found');
	}
}
