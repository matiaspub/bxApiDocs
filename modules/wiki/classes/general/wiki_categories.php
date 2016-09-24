<?
/* Categories list*/
class CWikiCategories
{
	private $arItems=array();

	public function addItem($catParams)
	{
		$this->arItems[strtolower($catParams->sName)] = array(
			'TITLE' => $catParams->sTitle,
			'NAME' => $catParams->sName,
			'CNT' => $catParams->iItemsCount,
			'IS_RED' => $catParams->bIsRed,
			'LINK' => $catParams->sLink
		);

		return true;
	}

	public function getItems()
	{
		return $this->arItems;
	}

	public function getItemsNames()
	{
		$arItemsNames = array();

		foreach ($this->arItems as $arItem)
			$arItemsNames[] = 'category:'.$arItem["NAME"];

		return $arItemsNames;
	}
}

class CWikiCategoryParams
{
	public $sName = "";
	public $sTitle = "";
	public $iItemsCount = 0;
	public $bIsRed = false;
	public $sLink = "";

	private $sPathTemplate = "";

	public function setPathTemplate($sTemplate)
	{
		$this->sPathTemplate = $sTemplate;
	}

	public function createLinkFromTemplate($sTemplate="")
	{
		if((!$this->sPathTemplate && !$sTemplate) || !$this->sName)
			return false;

		$this->sLink = CComponentEngine::MakePathFromTemplate($sTemplate != "" ? $sTemplate : $this->sPathTemplate,
						array(
						'wiki_name' => urlencode('Category:'.$this->sName),
						'group_id' => CWikiSocnet::$iSocNetId
						));
		return true;
	}

	public function clear($bClearTemplate = false)
	{
		$this->sName = $this->sTitle = $this->sLink = "";
		$this->iItemsCount = 0;
		$this->bIsRed = false;

		if($bClearTemplate)
			$this->sPathTemplate = "";

		return true;
	}
}

?>