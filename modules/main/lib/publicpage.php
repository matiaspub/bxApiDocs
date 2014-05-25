<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\Context;

class PublicPage
	extends HtmlPage
{
	/** @var \Bitrix\Main\Context\Site */
	protected $site;

	/** @var \Bitrix\Main\SiteTemplate */
	protected $siteTemplate;

	static public function __construct()
	{
		parent::__construct();
	}

	protected function initializeRequest()
	{
		$this->initializeSite();
		$this->initializeSiteTemplate();

		parent::initializeRequest();
	}

	protected function initializeSite()
	{
		$request = $this->getRequest();

		$currentDirectory = $request->getRequestedPageDirectory();
		$currentDomain = $request->getHttpHost(false);

		$site = SiteTable::getByDomainAndPath($currentDomain, $currentDirectory);

		if ($site === false)
		{
			$siteList = SiteTable::getList(
				array(
					'filter' => array('ACTIVE' => 'Y'),
					'order' => array('DEF' => 'DESC', 'SORT' => 'ASC'),
					'select' => array('*', 'ID' => 'LID')
				)
			);
			$site = $siteList->fetch();
		}

		if ($site === false)
			throw new SystemException("Site not found");

		$culture = Context\Culture::wakeUp($site["CULTURE_ID"]);
		if ($culture === null)
			$culture = new Context\Culture();

		$this->site = new Context\Site($site);
		$this->site->setCulture($culture);

		$this->setContextCulture($culture, $this->site->getLanguage());
	}

	protected function initializeSiteTemplate()
	{
		$siteTemplateId = null;

		$request = $this->getRequest();
		$previewSiteTemplate = $request->get("bitrix_preview_site_template");
		if (!empty($previewSiteTemplate) && $GLOBALS["USER"]->canDoOperation('view_other_settings'))
		{
			$recordset = SiteTemplateTable::getById($previewSiteTemplate);
			if ($record = $recordset->Fetch())
				$siteTemplateId = (int)$record["ID"];
		}

		if ($siteTemplateId === null)
		{
			if ($this->site == null)
				throw new NotSupportedException("Site must be initialized first");

			$siteTemplateId = SiteTemplateTable::getCurrentTemplateId($this->site->getId());
		}

		// deprecated
		// define("SITE_TEMPLATE_ID", $siteTemplateId);
		// define("SITE_TEMPLATE_PATH", BX_PERSONAL_ROOT.'/templates/'.SITE_TEMPLATE_ID);

		$this->siteTemplate = new SiteTemplate($siteTemplateId);
	}

	public function getSite()
	{
		return $this->site;
	}

	public function getSiteTemplate()
	{
		return $this->siteTemplate;
	}
}
