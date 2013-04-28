<?
class CAdminMobileMenu
{
	const DEFAULT_ITEM_SORT = 100;
	private static $arItems = array();

	public static function addItem($arItem)
	{
		if (!isset($arItem["sort"]))
			$arItem["sort"] = self::DEFAULT_ITEM_SORT;

		self::$arItems[] = $arItem;

		return count(self::$arItems);
	}

	static public function buildMenu($arParams)
	{
		if (!empty(self::$arItems))
			return self::$arItems;

		if (isset($arParams["MENU_FILE"]))
		{
			$arMobileMenuItems = array();

			require($_SERVER["DOCUMENT_ROOT"] . $arParams["MENU_FILE"]);

			if (!empty($arMobileMenuItems))
				foreach ($arMobileMenuItems as $arItem)
					self::addItem($arItem);
		}

		if (isset($arParams["EVENT_NAME"]))
		{
			foreach (GetModuleEvents("mobileapp", $arParams["EVENT_NAME"], true) as $arHandler)
				ExecuteModuleEventEx($arHandler);
		}

		sortByColumn(self::$arItems, "sort");

		return self::$arItems;
	}

	static public function getDefaultUrl($arParams)
	{
		if (!self::buildMenu($arParams))
			return false;

		$firstUrl = '';

		foreach (self::$arItems as $arSection)
		{
			if (!isset($arSection["items"]) || !is_array($arSection["items"]))
				continue;

			foreach ($arSection["items"] as $arItem)
			{
				if (isset($arItem["default"]) && $arItem["default"] == true && isset($arItem["data-url"]))
					return $arItem["data-url"];

				if (isset($arItem["data-url"]) && empty($firstUrl))
					$firstUrl = $arItem["data-url"];
			}
		}

		if ($firstUrl == '' && isset($arParams["MOBILE_APP_INDEX_PAGE"]))
			$firstUrl = $arParams["MOBILE_APP_INDEX_PAGE"];

		return $firstUrl;
	}
}

class CAdminMobileDetailTmpl
{
	private static function getTitleHtml($title)
	{
		return '<div class="order_title">' . $title . '</div>';
	}

	private static function getUpperButtonsHtml($arButtons)
	{
		$retHtml = '<div class="order_nav"><ul>';

		foreach ($arButtons as $arButton)
		{
			$buttonHtml = '<li';

			if (isset($arButton["CURRENT"]) && $arButton["CURRENT"])
				$buttonHtml .= ' class="current"';

			$buttonHtml .= '><a href=';

			if (isset($arButton["HREF"]))
				$buttonHtml .= '"' . $arButton["HREF"] . '"';
			else
				$buttonHtml .= '"javascript:void(0);"';

			if (isset($arButton["ONCLICK"]))
				$buttonHtml .= ' onclick="' . $arButton["ONCLICK"] . '"';

			$buttonHtml .= '>' . $arButton["TITLE"] . '</a></li>';

			$retHtml .= $buttonHtml;
		}

		$retHtml .= '</ul><div class="clb"></div></div>';

		return $retHtml;
	}

	private static function getSectionHtml($arSection)
	{
		$retHtml = '<div class="order_infoblock';

		if (!isset($arSection["OPEN"]) || !$arSection["OPEN"])
			$retHtml .= ' close';

		if (isset($arSection["TOTAL"]) && $arSection["TOTAL"])
			$retHtml .= ' total';

		$retHtml .= '">
			<div class="order_infoblock_title" onclick="BX.toggleClass(this.parentNode,\'close\');">' .
			$arSection["TITLE"] . '<span></span></div>';

		if ($arSection["TYPE"] == "container")
		{
			$retHtml .= $arSection["HTML"];
		}
		else
		{
			$retHtml .= '
			<div class="order_infoblock_content">
				<table class="order_infoblock_content_table">';

			foreach ($arSection["ROWS"] as $row)
			{
				$retHtml .= '<tr';

				if (isset($row["HIGLIGHTED"]) && $row["HIGLIGHTED"] == true)
					$retHtml .= ' class="order_detail_container_itogi_table_td_green"';

				$retHtml .= '>
						<td class="order_infoblock_content_table_tdtitle">' . $row["TITLE"] . '</td>
						<td class="order_infoblock_content_table_tdvalue';

				$retHtml .= '">' . $row["VALUE"] . '</td></tr>';
			}

			$retHtml .= '</table>';

			if (isset($arSection["BOTTOM"]) && isset($arSection["BOTTOM"]["VALUE"]))
			{
				$retHtml .= '<div class=';
				if (isset($arSection["BOTTOM"]["STYLE"]))
				{
					if ($arSection["BOTTOM"]["STYLE"] == 'green')
						$retHtml .= '"order_infoblock_order_green">';
					else
						$retHtml .= '"order_infoblock_order_canceled">';
				}

				$retHtml .= $arSection["BOTTOM"]["VALUE"] . '</div>';
			}

			$retHtml .= '</div>';
		}

		$retHtml .= '</div>';

		return $retHtml;
	}

	public static function getHtml($arAdminDetail)
	{
		$retHtml = '';

		if (isset($arAdminDetail["TITLE"]))
			$retHtml .= self::getTitleHtml($arAdminDetail["TITLE"]);

		if (isset($arAdminDetail["UPPER_BUTTONS"]))
			$retHtml .= self::getUpperButtonsHtml($arAdminDetail["UPPER_BUTTONS"]);

		if (isset($arAdminDetail["SECTIONS"]) && is_array($arAdminDetail["SECTIONS"]))
			foreach ($arAdminDetail["SECTIONS"] as $arSection)
				$retHtml .= self::getSectionHtml($arSection);

		return $retHtml;
	}
}

class CAdminMobileDetail
{
	private $arDetail;

	static public function setTitle($strTitle)
	{

		$this->arDetail["TITLE"] = $strTitle;
	}

	static public function addUpperButton($arButton)
	{
		$this->arDetail["UPPER_BUTTONS"][] = $arButton;
	}

	static public function addSection($arSection)
	{
		$this->arDetail["SECTIONS"][] = $arSection;
	}

	static public function getHtml()
	{
		return CAdminMobileDetailTmpl::getHtml($this->arDetail);
	}

	static public function getItem()
	{
		return $this->arDetail;
	}
}

class CAdminMobileInterface
{
	static public function getCheckBoxesHtml($arCB, $strTitle = '', $arChecked = array(), $arParams = array())
	{
		if (!is_array($arCB) || empty($arCB))
			return false;

		$arCBParams["ITEMS"] = $arCB;

		if (strlen($strTitle) > 0)
			$arCBParams["TITLE"] = $strTitle;

		if (!empty($arChecked))
			$arCBParams["CHECKED"] = $arChecked;

		if (is_array($arParams))
			foreach ($arParams as $key => $param)
				$arCBParams[$key] = $param;

		ob_start();
		$GLOBALS["APPLICATION"]->IncludeComponent(
			'bitrix:mobileapp.interface.checkboxes',
			'.default',
			$arCBParams,
			false
		);

		$resultHtml = ob_get_contents();
		ob_end_clean();

		return $resultHtml;
	}
}
?>
