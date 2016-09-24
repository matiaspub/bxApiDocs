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

		CAdminMobilePush::OnAdminMobileGetPushSettings();

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

			if(is_array($arSection["ROWS"]))
			{
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

	public function setTitle($strTitle)
	{

		$this->arDetail["TITLE"] = $strTitle;
	}

	public function addUpperButton($arButton)
	{
		$this->arDetail["UPPER_BUTTONS"][] = $arButton;
	}

	public function addSection($arSection)
	{
		$this->arDetail["SECTIONS"][] = $arSection;
	}

	public function getHtml()
	{
		return CAdminMobileDetailTmpl::getHtml($this->arDetail);
	}

	public function getItem()
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

class CMobileLazyLoad
{
	public static function getBase64Stub()
	{
		return "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIW2N88f7jfwAJWAPJBTw90AAAAABJRU5ErkJggg==";
	}
}

class CAdminMobileEdit
{
	private static function getCustomAttribs($arField)
	{
		$strResult = '';

		if(isset($arField["CUSTOM_ATTRS"]) && is_array($arField["CUSTOM_ATTRS"]))
		{
			$strResult .= ' ';
			foreach ($arField["CUSTOM_ATTRS"] as $attrName => $attrVal)
				$strResult .= ' '.$attrName.'="'.$attrVal.'"';
		}

		return $strResult;
	}

	private static function getCommonAttribs($arField)
	{
		$strResult = '';
		$arCommonAttrs = array("ID", "NAME", "HIDDEN");

		if(is_array($arField))
		{
			$strResult .= ' ';
			foreach ($arCommonAttrs as $attrName)
			{
				if(isset($arField[$attrName]))
				{
					$strResult .= ' '.strtolower($attrName).'="'.$arField[$attrName].'"';
				}
			}
		}

		return $strResult;
	}

	public static function getFieldHtml($arField)
	{
		global $APPLICATION;
		$resultHtml = '';
		$someAttribs = self::getCommonAttribs($arField);
		$someAttribs .= self::getCustomAttribs($arField);



		switch ($arField["TYPE"])
		{
			case 'BLOCK':
				$resultHtml =
					'<div class="mapp_edit_infoblock"'.
					$someAttribs.
					'>'.
						'<div class="mapp_edit_infoblock_title">'.$arField["TITLE"].'</div>';

				if(is_array($arField["DATA"]))
					foreach ($arField["DATA"] as $fieldData)
						$resultHtml .= self::getFieldHtml($fieldData);

				$resultHtml .= '</div>';

				break;

			case 'TEXT_RO':
				$resultHtml =
					'<ul>
						<li>
							<div class="mapp_edit_li_container"'.
							$someAttribs.
							'>
								<label>'.$arField["VALUE"].'</label>
							</div>
						</li>
					</ul>';

				break;

			case 'HIDDEN':
				$resultHtml = '<input type="hidden" value="'.$arField["VALUE"].'"'.$someAttribs.'>';
				break;

			case 'CHECKBOXES':
				$arFieldParams = array(
					"NOWRAP" => "Y",
					"NAME" => $arField["NAME"],
					"ITEMS" => $arField["VALUES"]
					);

				if(isset($arField["CHECKED"]) && is_array($arField["CHECKED"]))
					$arFieldParams["CHECKED"] = $arField["CHECKED"];

				ob_start();
				$APPLICATION->IncludeComponent(
					'bitrix:mobileapp.interface.checkboxes',
					'.default',
					$arFieldParams,
					false);

				$resultHtml = ob_get_contents();
				ob_end_clean();

				break;

			case 'CHECKBOX':

				$arItemParams = array(
					"NAME" => $arField["NAME"],
					"TITLE" => $arField["TITLE"]
				);

				if(isset($arField["VALUE"]) && $arField["VALUE"] == true)
					$arItemParams["VALUE"] = $arField["VALUE"];

				if(isset($arField["CHECKED"]) && $arField["CHECKED"] == true)
					$arItemParams["CHECKED"] = $arField["CHECKED"];

				if(isset($arField["TITLE"]) && $arField["TITLE"] == true)
					$arItemParams["TITLE"] = $arField["TITLE"];

				$arFieldParams = array(
					"NOWRAP" => "Y",
					"ITEMS" => array($arItemParams)
					);

				ob_start();
				$APPLICATION->IncludeComponent(
					'bitrix:mobileapp.interface.checkboxes',
					'.default',
					$arFieldParams,
					false);

				$resultHtml = ob_get_contents();
				ob_end_clean();

				break;

			case 'RADIO':

				$arFieldsParams = array(
					"ITEMS" => $arField["VALUES"],
					"TITLE" => $arField["TITLE"],
					"RADIO_NAME" => $arField["NAME"],
					"NOWRAP" => "Y"
					);

				if(isset($arField["SELECTED"]))
					$arFieldsParams["SELECTED"] = $arField["SELECTED"];

				ob_start();

				$APPLICATION->IncludeComponent(
					'bitrix:mobileapp.interface.radiobuttons',
					'.default',
					$arFieldsParams,
					false);

				$resultHtml = ob_get_contents();
				ob_end_clean();

				break;

			case 'TEXT':
				if(!isset($arField["VALUES"]))
					$values = array($arField["VALUE"]);
				else
					$values = $arField["VALUES"];

					$resultHtml = '<ul>';

					foreach ($values as $value)
					{
						$resultHtml .= '
							<li>
								<div class="mapp_edit_input_container">
									<input '.$someAttribs.' type="text"';

						if(strlen($value) <= 0 && isset($arField["TITLE"]))
						{
							$resultHtml .= ' onblur="if (this.value==\'\'){this.value=\''.$arField["TITLE"].'\'; BX.addClass(this, \'mapp_edit_input_empty\');}"'.
								' value="'.$arField["TITLE"].'"'.
								' onfocus="if (this.value==\''.$arField["TITLE"].'\') {this.value=\'\';  BX.removeClass(this, \'mapp_edit_input_empty\');}"'.
								' class = "mapp_edit_input_empty"';
						}
						elseif(strlen($value) > 0)
						{
							$resultHtml .= ' value="'.$value.'"';
						}

						$resultHtml .='>
								</div>
							</li>';
					}

				$resultHtml .= '</ul>';
				break;

			case 'BUTTON':
				$resultHtml = '<input type="button" class="mapp_edit_button"'.$someAttribs;

				if(isset($arField["VALUE"]))
					$resultHtml .= ' value="'.$arField["VALUE"].'"';

				$resultHtml .= '>';
				break;

			case 'TEXTAREA':
				$resultHtml = '';

				if(!isset($arField["TITLE"]))
					$arField["TITLE"] = "";

				$resultHtml .= '<div class="mapp_edit_textarea_title">'.
									$arField["TITLE"].
								'</div>';

				$resultHtml .= '
				<div class="mapp_edit_textarea_container">
					<textarea'.
						' class="mapp_edit_textarea"'.$someAttribs;

						$resultHtml .= '>'.$arField["VALUE"].
					'</textarea>
				</div>';

				if(!isset($arField["HINT"]))
					$arField["HINT"] = "";

				$resultHtml .= '<span class="mapp_edit_textarea_hint">'.
									$arField["HINT"].
								'</span>';

				break;

			case '2_RADIO_BUTTONS':

				if(isset($arField["ID"]))
					$id = $arField["ID"];
				else
					$id = "2rb_".rand();

				$value = isset($arField['VALUE']) && $arField['VALUE'] == 'Y' ? 'Y' : 'N';

				$resultHtml .= '
						<div class="mapp_edit_li_container mapp_edit_tac">
							<div class="mapp_edit_title_tac">'.$arField["TITLE"].'</div>
							<div class="mapp_edit_button_yn">
								<a'.
									' id="'.$id.'_b1'.'"'.
									' href="javascript:void(0);"'.
									($value == 'Y' ? ' class="current"' : '').
									self::getCustomAttribs($arField["BUTT_Y"]).
								'>'.
									$arField["BUTT_Y"]["TITLE"].
								'</a>
								<a'.
									' id="'.$id.'_b2'.'"'.
									' href="javascript:void(0);"'
									.($value != 'Y' ? ' class="current"' : '').
								'>'
									.$arField["BUTT_N"]["TITLE"].
									self::getCustomAttribs($arField["BUTT_N"]).
								'</a>
								<input'.
									' type="hidden"'.
									' name="'.$arField["NAME"].'"'.
									' id="'.$id.'"'.
									' value ="'.$value.'"'.
									self::getCustomAttribs($arField).
								'>
								<div class="mapp_edit_clb"></div>
							</div>
						</div>
					<script type="text/javascript">
						new FastButton(BX("'.$id.'_b1'.'"), function(){ toggle'.$id.'(); '.$arField["BUTT_Y"]["ONCLICK"].'}, false);
						new FastButton(BX("'.$id.'_b2'.'"), function(){ toggle'.$id.'(); '.$arField["BUTT_N"]["ONCLICK"].'}, false);
						function toggle'.$id.'()
						{
							BX.toggleClass(BX("'.$id.'_b1'.'"),"current");
							BX.toggleClass(BX("'.$id.'_b2'.'"),"current");

							var input = BX("'.$id.'");

							if(input && input.value)
							{
								if(input.value == "Y")
									input.value = "N";
								else
									input.value = "Y";
							}
						}
					</script>
					';

				break;

			case 'CUSTOM':
						$resultHtml = $arField["HTML_DATA"];
				break;

		}

		return $resultHtml;
	}
}
?>
