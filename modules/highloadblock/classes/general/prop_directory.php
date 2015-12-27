<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Highloadblock as HL;

Loc::loadMessages(__FILE__);

/**
 * Class CIBlockPropertyDirectory
 */
class CIBlockPropertyDirectory
{
	const TABLE_PREFIX = 'b_hlbd_';

	protected static $arFullCache = array();
	protected static $arItemCache = array();
	protected static $directoryMap = array();
	protected static $hlblockCache = array();

	/**
	 * Returns property type description.
	 *
	 * @return array
	 */
	public static function GetUserTypeDescription()
	{
		return array(
			'PROPERTY_TYPE' => 'S',
			'USER_TYPE' => 'directory',
			'DESCRIPTION' => Loc::getMessage('HIBLOCK_PROP_DIRECTORY_DESCRIPTION'),
			'GetSettingsHTML' => array(__CLASS__, 'GetSettingsHTML'),
			'GetPropertyFieldHtml' => array(__CLASS__, 'GetPropertyFieldHtml'),
			'GetPropertyFieldHtmlMulty' => array(__CLASS__, 'GetPropertyFieldHtmlMulty'),
			'PrepareSettings' => array(__CLASS__, 'PrepareSettings'),
			'GetAdminListViewHTML' => array(__CLASS__, 'GetAdminListViewHTML'),
			'GetPublicViewHTML' => array(__CLASS__, 'GetPublicViewHTML'),
			'GetAdminFilterHTML' => array(__CLASS__, 'GetAdminFilterHTML'),
			'GetExtendedValue' => array(__CLASS__, 'GetExtendedValue'),
			'GetSearchContent' => array(__CLASS__, 'GetSearchContent'),
			'AddFilterFields' => array(__CLASS__, 'AddFilterFields')
		);
	}

	/**
	 * Prepare settings for property.
	 *
	 * @param array $arProperty				Property description.
	 * @return array
	 */
	public static function PrepareSettings($arProperty)
	{
		$size = 1;
		$width = 0;
		$multiple = "N";
		$group = "N";
		$directoryTableName = '';

		if (!empty($arProperty["USER_TYPE_SETTINGS"]) && is_array($arProperty["USER_TYPE_SETTINGS"]))
		{
			if (isset($arProperty["USER_TYPE_SETTINGS"]["size"]))
			{
				$size = (int)$arProperty["USER_TYPE_SETTINGS"]["size"];
				if ($size <= 0)
					$size = 1;
			}

			if (isset($arProperty["USER_TYPE_SETTINGS"]["width"]))
			{
				$width = (int)$arProperty["USER_TYPE_SETTINGS"]["width"];
				if ($width < 0)
					$width = 0;
			}

			if (isset($arProperty["USER_TYPE_SETTINGS"]["group"]) && $arProperty["USER_TYPE_SETTINGS"]["group"] === "Y")
				$group = "Y";

			if (isset($arProperty["USER_TYPE_SETTINGS"]["multiple"]) && $arProperty["USER_TYPE_SETTINGS"]["multiple"] === "Y")
				$multiple = "Y";

			if (isset($arProperty["USER_TYPE_SETTINGS"]["TABLE_NAME"]))
				$directoryTableName = (string)$arProperty["USER_TYPE_SETTINGS"]['TABLE_NAME'];
		}
		return array(
			"size" =>  $size,
			"width" => $width,
			"group" => $group,
			"multiple" => $multiple,
			"TABLE_NAME" => $directoryTableName,
		);
	}

	/**
	 * Returns html for show in edit property page.
	 *
	 * @param array $arProperty				Property description.
	 * @param array $strHTMLControlName		Control description.
	 * @param array $arPropertyFields		Property fields for edit form.
	 * @return string
	 */
	public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
	{
		$iblockID = 0;
		if (isset($arProperty['IBLOCK_ID']))
			$iblockID = (int)$arProperty['IBLOCK_ID'];
		CJSCore::Init(array('translit'));
		$settings = CIBlockPropertyDirectory::PrepareSettings($arProperty);
		$arPropertyFields = array(
			"HIDE" => array("ROW_COUNT", "COL_COUNT", "MULTIPLE_CNT", "DEFAULT_VALUE", "WITH_DESCRIPTION"),
		);

		$cellOption = '<option value="-1"'.('' == $settings["TABLE_NAME"] ? ' selected' : '').'>'.Loc::getMessage('HIBLOCK_PROP_DIRECTORY_NEW_DIRECTORY').'</option>';

		$rsData = HL\HighloadBlockTable::getList(array(
			'select' => array('TABLE_NAME', 'NAME')
		));
		while($arData = $rsData->fetch())
		{
			$selected = ($settings["TABLE_NAME"] == $arData['TABLE_NAME']) ? ' selected' : '';
			$cellOption .= '<option '.$selected.' value="'.htmlspecialcharsbx($arData["TABLE_NAME"]).'">'.htmlspecialcharsex($arData["NAME"].' ('.$arData["TABLE_NAME"].')').'</option>';
		}

		$tablePrefix = self::TABLE_PREFIX;
		$selectDir = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_SELECT_DIR");
		$headingXmlId = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_XML_ID");
		$headingName = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_NAME");
		$headingSort = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_SORT");
		$headingDef = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_DEF");
		$headingLink = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_LINK");
		$headingFile = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_FILE");
		$headingDescription = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_DECSRIPTION");
		$headingFullDescription = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_FULL_DESCRIPTION");
		$directoryName = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_NEW_NAME");
		$directoryMore = Loc::getMessage("HIBLOCK_PROP_DIRECTORY_MORE");
		return <<<"HIBSELECT"
<script type="text/javascript">
function getTableHead()
{
	BX('hlb_directory_table').innerHTML = '<tr class="heading"><td></td><td>$headingName</td><td>$headingSort</td><td>$headingXmlId</td><td>$headingFile</td><td>$headingLink</td><td>$headingDef</td><td>$headingDescription</td><td>$headingFullDescription</td></tr>';
}

function getDirectoryTableRow(addNew)
{
	addNew = (addNew === 'row' ? 'row' : 'full');
	var obSelectHLBlock = BX('hlb_directory_table_id');
	if (!!obSelectHLBlock)
	{
		var rowNumber = parseInt(BX('hlb_directory_row_number').value, 10);
		if (BX('IB_MAX_ROWS_COUNT'))
			rowNumber = parseInt(BX('IB_MAX_ROWS_COUNT').value, 10);
		if (isNaN(rowNumber))
			rowNumber = 0;
		var hlBlock = (-1 < obSelectHLBlock.selectedIndex ? obSelectHLBlock.options[obSelectHLBlock.selectedIndex].value : '');
		var selectHLBlockValue = hlBlock;

		if (addNew === 'full')
		{
			if (selectHLBlockValue == '-1')
			{
				getTableHead();
				BX('hlb_directory_table_tr').style.display = 'table-row';
				BX('hlb_directory_title_tr').style.display = 'table-row';
				BX('hlb_directory_table_name').style.display = 'table-row';
				BX('hlb_directory_table_name').disabled = false;

				addNew = 'row';
				rowNumber = 0;
			}
			else
			{
				BX('hlb_directory_table_name').disabled = true;
				BX('hlb_directory_title_tr').style.display = 'none';

				BX.ajax.post(
					'/bitrix/admin/highloadblock_directory_ajax.php',
					{
						lang: BX.message('LANGUAGE_ID'),
						sessid: BX.bitrix_sessid(),
						hlBlock: hlBlock,
						rowNumber: rowNumber,
						getTitle: 'Y',
						IBLOCK_ID: '{$iblockID}'
					},
					BX.delegate(function(result) {
						BX('hlb_directory_table').innerHTML = result;
						BX('hlb_directory_row_number').value = parseInt(BX('hlb_directory_row_number').value, 10) + 1;
						if(BX('IB_MAX_ROWS_COUNT'))
							BX('IB_MAX_ROWS_COUNT').value = parseInt(BX('IB_MAX_ROWS_COUNT').value, 10) + 1;
					})
				);

			}
		}
		if (addNew === 'row')
		{
			BX.ajax.loadJSON(
				'/bitrix/admin/highloadblock_directory_ajax.php',
				{
					lang: BX.message('LANGUAGE_ID'),
					sessid: BX.bitrix_sessid(),
					hlBlock: hlBlock,
					rowNumber: rowNumber,
					addEmptyRow: 'Y',
					IBLOCK_ID: '{$iblockID}'
				},
				BX.delegate(function(result) {
					var obRow = null,
						obTable = BX('hlb_directory_table'),
						i = '',
						obCell = null,
						rowNumber = 0;

					if (!!obTable && 'object' === typeof result)
					{
						rowNumber = parseInt(BX('hlb_directory_row_number').value, 10);
						if (!!BX('IB_MAX_ROWS_COUNT'))
							rowNumber = parseInt(BX('IB_MAX_ROWS_COUNT').value, 10);
						if (isNaN(rowNumber))
							rowNumber = 0;
						obRow = obTable.insertRow(obTable.rows.length);
						obRow.id = 'hlbl_property_tr_'+rowNumber;
						for (i in result)
						{
							obCell = obRow.insertCell(-1);
							BX.adjust(obCell, { style: result[i].style, html: result[i].html });
						}
						BX('hlb_directory_row_number').value = rowNumber + 1;
						if(BX('IB_MAX_ROWS_COUNT'))
							BX('IB_MAX_ROWS_COUNT').value = rowNumber + 1;
					}
				})
			);
		}
	}
}
function getDirectoryTableHead(e)
{
	e.value = BX.translit(e.value, {
		'max_len' : 35,
		'change_case' : 'L',
		'replace_space' : '',
		'replace_other' : '',
		'delete_repeat_replace' : true
	});

	var obSelectHLBlock = BX('hlb_directory_table_id');
	if (!!obSelectHLBlock)
	{
		if (-1 < obSelectHLBlock.selectedIndex && '-1' == obSelectHLBlock.options[obSelectHLBlock.selectedIndex].value)
		{
			BX('hlb_directory_table_id_hidden').disabled = false;
			BX('hlb_directory_table_id_hidden').value = '{$tablePrefix}'+BX('hlb_directory_table_name').value;
			BX('hlb_directory_table_id_hidden').value = BX('hlb_directory_table_id_hidden').value.substr(0, 30);
		}
	}
}

</script>
<tr>
	<td>{$selectDir}:</td>
	<td>
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[TABLE_NAME]" disabled id="hlb_directory_table_id_hidden">
		<select name="{$strHTMLControlName["NAME"]}[TABLE_NAME]" id="hlb_directory_table_id" onchange="getDirectoryTableRow('full');"/>
			$cellOption
		</select>
	</td>
</tr>
<tr id="hlb_directory_title_tr" class="adm-detail-required-field">
	<td>$directoryName</td>
	<td>
		<input type="hidden" value="0" id="hlb_directory_row_number">
		<input type="text" name="HLB_NEW_TITLE" size="30" id="hlb_directory_table_name" onblur="getDirectoryTableHead(this);">
	</td>
</tr>
<tr id="hlb_directory_table_tr">
	<td colspan="2" style="text-align: center;">
		<table class="internal" id="hlb_directory_table" style="margin: 0 auto;">
			<script type="text/javascript">getDirectoryTableRow('full');</script>
		</table>
	</td>
</tr>
<tr>
	<td colspan="2" style="text-align: center;">
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[LANG][UF_NAME]" value="{$headingName}">
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[LANG][UF_SORT]" value="{$headingSort}">
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[LANG][UF_XML_ID]" value="{$headingXmlId}">
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[LANG][UF_FILE]" value="{$headingFile}">
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[LANG][UF_LINK]" value="{$headingLink}">
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[LANG][UF_DEF]" value="{$headingDef}">
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[LANG][UF_DESCRIPTION]" value="{$headingDescription}">
		<input type="hidden" name="{$strHTMLControlName["NAME"]}[LANG][UF_FULL_DESCRIPTION]" value="{$headingFullDescription}">
		<div style="width: 100%; text-align: center; margin: 10px 0;">
		<input type="button" value="{$directoryMore}" onclick="getDirectoryTableRow('row');" id="hlb_directory_table_button" class="adm-btn-big">
		</div>
	</td>
</tr>
HIBSELECT;
	}

	/**
	 * Return html for edit single value.
	 *
	 * @param array $arProperty				Property description.
	 * @param array $value					Current value.
	 * @param array $strHTMLControlName		Control description.
	 * @return string
	 */
	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		$settings = CIBlockPropertyDirectory::PrepareSettings($arProperty);
		$size = ($settings["size"] > 1 ? ' size="'.$settings["size"].'"' : '');
		$width = ($settings["width"] > 0 ? ' style="width:'.$settings["width"].'px"' : '');

		$options = CIBlockPropertyDirectory::GetOptionsHtml($arProperty, array($value["VALUE"]));
		$html = '<select name="'.$strHTMLControlName["VALUE"].'"'.$size.$width.'>';
		$html .= $options;
		$html .= '</select>';
		return  $html;
	}

	/**
	 * Return html for edit multiple value.
	 *
	 * @param array $arProperty				Property description.
	 * @param array $value					Current value.
	 * @param array $strHTMLControlName		Control description.
	 * @return string
	 */
	public static function GetPropertyFieldHtmlMulty($arProperty, $value, $strHTMLControlName)
	{
		$max_n = 0;
		$values = array();
		if(is_array($value))
		{
			$match = array();
			foreach($value as $property_value_id => $arValue)
			{
				$values[$property_value_id] = $arValue["VALUE"];
				if(preg_match("/^n(\\d+)$/", $property_value_id, $match))
				{
					if($match[1] > $max_n)
						$max_n = intval($match[1]);
				}
			}
		}

		$settings = CIBlockPropertyDirectory::PrepareSettings($arProperty);
		$size = ($settings["size"] > 1 ? ' size="'.$settings["size"].'"' : '');
		$width = ($settings["width"] > 0 ? ' style="width:'.$settings["width"].'px"' : ' style="margin-bottom:3px"');

		if($settings["multiple"]=="Y")
		{
			$options = CIBlockPropertyDirectory::GetOptionsHtml($arProperty, $values);
			$html = '<select multiple name="'.$strHTMLControlName["VALUE"].'[]"'.$size.$width.'>';
			$html .= $options;
			$html .= '</select>';
		}
		else
		{
			if(end($values) != "" || substr(key($values), 0, 1) != "n")
				$values["n".($max_n+1)] = "";

			$name = $strHTMLControlName["VALUE"]."VALUE";

			$html = '<table cellpadding="0" cellspacing="0" border="0" class="nopadding" width="100%" id="tb'.md5($name).'">';
			foreach($values as $property_value_id=>$value)
			{
				$html .= '<tr><td>';

				$options = CIBlockPropertyDirectory::GetOptionsHtml($arProperty, array($value));

				$html .= '<select name="'.$strHTMLControlName["VALUE"].'['.$property_value_id.'][VALUE]"'.$size.$width.'>';
				$html .= $options;
				$html .= '</select>';

				$html .= '</td></tr>';
			}
			$html .= '</table>';

			$html .= '<input type="button" value="'.Loc::getMessage("HIBLOCK_PROP_DIRECTORY_MORE").'" onclick="if(window.addNewRow){addNewRow(\'tb'.md5($name).'\', -1)}else{addNewTableRow(\'tb'.md5($name).'\', 1, /\[(n)([0-9]*)\]/g, 2)}">';
		}
		return  $html;
	}

	/**
	 * Returns list values.
	 *
	 * @param array $arProperty			Property description.
	 * @param array $values				Current value.
	 * @return string
	 */
	public static function GetOptionsHtml($arProperty, $values)
	{
		$selectedValue = false;
		$cellOption = '';
		$defaultOption = '';
		$highLoadIBTableName = (isset($arProperty["USER_TYPE_SETTINGS"]["TABLE_NAME"]) ? $arProperty["USER_TYPE_SETTINGS"]["TABLE_NAME"] : '');
		if($highLoadIBTableName != '')
		{
			if (empty(self::$arFullCache[$highLoadIBTableName]))
			{
				self::$arFullCache[$highLoadIBTableName] = self::getEntityFieldsByFilter(
					$highLoadIBTableName,
					array(
						'select' => array('UF_XML_ID', 'UF_NAME', 'ID')
					)
				);
			}
			foreach(self::$arFullCache[$highLoadIBTableName] as $data)
			{
				$options = '';
				if(in_array($data["UF_XML_ID"], $values))
				{
					$options = ' selected';
					$selectedValue = true;
				}
				$cellOption .= '<option '.$options.' value="'.htmlspecialcharsbx($data['UF_XML_ID']).'">'.htmlspecialcharsex($data["UF_NAME"].' ['.$data["ID"]).']</option>';
			}
			$defaultOption = '<option value=""'.($selectedValue ? '' : ' selected').'>'.Loc::getMessage('HIBLOCK_PROP_DIRECTORY_EMPTY_VALUE').'</option>';
		}
		else
		{
			$cellOption = '<option value="" selected>'.Loc::getMessage('HIBLOCK_PROP_DIRECTORY_EMPTY_VALUE').'</option>';
		}
		return $defaultOption.$cellOption;
	}

	/**
	 * Returns data for smart filter.
	 *
	 * @param array $arProperty				Property description.
	 * @param array $value					Current value.
	 * @return false|array
	 */
	public static function GetExtendedValue($arProperty, $value)
	{
		if (!isset($value['VALUE']))
			return false;
		if (empty($arProperty['USER_TYPE_SETTINGS']['TABLE_NAME']))
			return false;

		$tableName = $arProperty['USER_TYPE_SETTINGS']['TABLE_NAME'];
		if (!isset(self::$arItemCache[$tableName]))
			self::$arItemCache[$tableName] = array();

		if (!isset(self::$arItemCache[$tableName][$value['VALUE']]))
		{
			$arData = self::getEntityFieldsByFilter(
				$arProperty['USER_TYPE_SETTINGS']['TABLE_NAME'],
				array(
					'select' => array('UF_XML_ID', 'UF_NAME'),
					'filter' => array('=UF_XML_ID' => $value['VALUE'])
				)
			);
			if (!empty($arData))
			{
				$arData = current($arData);
				if (isset($arData['UF_XML_ID']) && $arData['UF_XML_ID'] == $value['VALUE'])
				{
					$arData['VALUE'] = $arData['UF_NAME'];
					if (isset($arData['UF_FILE']))
						$arData['FILE_ID'] = $arData['UF_FILE'];
					self::$arItemCache[$tableName][$value['VALUE']] = $arData;
				}
			}
		}

		if (isset(self::$arItemCache[$tableName][$value['VALUE']]))
		{
			return self::$arItemCache[$tableName][$value['VALUE']];
		}
		return false;
	}

	/**
	 * Returns admin list view html.
	 *
	 * @param array $arProperty				Property description.
	 * @param array $value					Current value.
	 * @param array $strHTMLControlName		Control description.
	 * @return string
	 */
	public static function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
	{
		$dataValue = self::GetExtendedValue($arProperty, $value);
		if ($dataValue)
		{
			return htmlspecialcharsbx($dataValue['UF_NAME']);
		}
		return '';
	}

	/**
	 * Return public list view html (module list).
	 *
	 * @param array $arProperty				Property description.
	 * @param array $value					Current value.
	 * @param array $strHTMLControlName		Control description.
	 * @return string
	 */
	public static function GetPublicViewHTML($arProperty, $value, $strHTMLControlName)
	{
		$dataValue = self::GetExtendedValue($arProperty, $value);
		if ($dataValue)
		{
			if (isset($strHTMLControlName['MODE']) && 'CSV_EXPORT' == $strHTMLControlName['MODE'])
				return $dataValue['UF_XML_ID'];
			elseif (isset($strHTMLControlName['MODE']) && ('SIMPLE_TEXT' == $strHTMLControlName['MODE'] || 'ELEMENT_TEMPLATE' == $strHTMLControlName['MODE']))
				return $dataValue['UF_NAME'];
			else
				return htmlspecialcharsbx($dataValue['UF_NAME']);
		}
		return '';
	}

	/**
	 * Return admin filter html.
	 *
	 * @param array $arProperty				Property description.
	 * @param array $strHTMLControlName		Control description.
	 * @return string
	 */
	public static function GetAdminFilterHTML($arProperty, $strHTMLControlName)
	{
		$lAdmin = new CAdminList($strHTMLControlName["TABLE_ID"]);
		$lAdmin->InitFilter(array($strHTMLControlName["VALUE"]));
		$filterValue = $GLOBALS[$strHTMLControlName["VALUE"]];

		if(isset($filterValue) && is_array($filterValue))
			$values = $filterValue;
		else
			$values = array();

		$settings = CIBlockPropertyDirectory::PrepareSettings($arProperty);
		$size = ($settings["size"] > 1 ? ' size="'.$settings["size"].'"' : '');
		$width = ($settings["width"] > 0 ? ' style="width:'.$settings["width"].'px"' : '');

		$options = CIBlockPropertyDirectory::GetOptionsHtml($arProperty, $values);
		$html = '<select name="'.$strHTMLControlName["VALUE"].'[]"'.$size.$width.' multiple>';
		$html .= $options;
		$html .= '</select>';
		return  $html;
	}

	/**
	 * Return property value for search.
	 *
	 * @param array $arProperty				Property description.
	 * @param array $value					Current value.
	 * @param array $strHTMLControlName		Control description.
	 * @return string
	 */
	public static function GetSearchContent($arProperty, $value, $strHTMLControlName)
	{
		$dataValue = self::GetExtendedValue($arProperty, $value);
		if ($dataValue)
		{
			if (isset($dataValue['UF_NAME']))
				return $dataValue['UF_NAME'];
			else
				return $dataValue['UF_XML_ID'];
		}
		return '';
	}

	/**
	 * Add values in filter.
	 *
	 * @param array $arProperty
	 * @param array $strHTMLControlName
	 * @param array &$arFilter
	 * @param bool &$filtered
	 * @return void
	 */
	public static function AddFilterFields($arProperty, $strHTMLControlName, &$arFilter, &$filtered)
	{
		$filtered = false;
		$values = array();

		if (isset($_REQUEST[$strHTMLControlName["VALUE"]]))
			$values = (is_array($_REQUEST[$strHTMLControlName["VALUE"]]) ? $_REQUEST[$strHTMLControlName["VALUE"]] : array($_REQUEST[$strHTMLControlName["VALUE"]]));
		elseif (isset($GLOBALS[$strHTMLControlName["VALUE"]]))
			$values = (is_array($GLOBALS[$strHTMLControlName["VALUE"]]) ? $GLOBALS[$strHTMLControlName["VALUE"]] : array($GLOBALS[$strHTMLControlName["VALUE"]]));

		if (!empty($values))
		{
			$clearValues = array();
			foreach ($values as $oneValue)
			{
				$oneValue = (string)$oneValue;
				if ($oneValue != '')
					$clearValues[] = $oneValue;
			}
			$values = $clearValues;
			unset($oneValue, $clearValues);
		}
		if (!empty($values))
		{
			$filtered = true;
			$arFilter['=PROPERTY_'.$arProperty['ID']] = $values;
		}
	}

	/**
	 * Returns table name for new entity.
	 *
	 * @param string $name			Entity name
	 * @return bool|string
	 */
	public static function createHighloadTableName($name)
	{
		$name = trim((string)$name);
		if ($name == '')
			return false;
		$name = substr(self::TABLE_PREFIX.$name, 0, 30);
		return $name;
	}
	/**
	 * Returns entity data.
	 *
	 * @param string $tableName				HL table name.
	 * @param array $listDescr				Params for getList.
	 * @return array
	 */
	private static function getEntityFieldsByFilter($tableName, $listDescr = array())
	{
		$arResult = array();
		$tableName = (string)$tableName;
		if (!is_array($listDescr))
			$listDescr = array();
		if (!empty($tableName))
		{
			if (!isset(self::$hlblockCache[$tableName]))
			{
				self::$hlblockCache[$tableName] = HL\HighloadBlockTable::getList(
					array(
						'select' => array('TABLE_NAME', 'NAME', 'ID'),
						'filter' => array('=TABLE_NAME' => $tableName)
					)
				)->fetch();
			}
			if (!empty(self::$hlblockCache[$tableName]))
			{
				$entity = HL\HighloadBlockTable::compileEntity(self::$hlblockCache[$tableName]);
				$entityDataClass = $entity->getDataClass();
				if (!isset(self::$directoryMap[$tableName]))
					self::$directoryMap[$tableName] = $entityDataClass::getEntity()->getFields();

				if (!isset(self::$directoryMap[$tableName]['UF_XML_ID']))
					return $arResult;

				$nameExist = isset(self::$directoryMap[$tableName]['UF_NAME']);
				if (!$nameExist)
					$listDescr['select'] = array('UF_XML_ID', 'ID');
				$fileExists = isset(self::$directoryMap[$tableName]['UF_FILE']);
				if ($fileExists)
					$listDescr['select'][] = 'UF_FILE';

				$sortExist = isset(self::$directoryMap[$tableName]['UF_SORT']);
				$listDescr['order'] = array();
				if ($sortExist)
				{
					$listDescr['order']['UF_SORT'] = 'ASC';
					$listDescr['select'][] = 'UF_SORT';
				}
				if ($nameExist)
					$listDescr['order']['UF_NAME'] = 'ASC';
				else
					$listDescr['order']['UF_XML_ID'] = 'ASC';
				$listDescr['order']['ID'] = 'ASC';

				$rsData = $entityDataClass::getList($listDescr);
				while($arData = $rsData->fetch())
				{
					if (!$nameExist)
						$arData['UF_NAME'] = $arData['UF_XML_ID'];
					$arData['SORT'] = ($sortExist ? $arData['UF_SORT'] : $arData['ID']);
					$arResult[] = $arData;
				}
				unset($arData, $rsData);
			}
		}
		return $arResult;
	}
}