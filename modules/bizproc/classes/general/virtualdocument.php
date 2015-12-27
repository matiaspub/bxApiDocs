<?

use Bitrix\Bizproc\FieldType;

if (!CModule::IncludeModule("iblock") || !class_exists("CIBlockDocument"))
	return;

IncludeModuleLangFile(__FILE__);

class CBPVirtualDocument
	extends CIBlockDocument
{
	public static function GetFieldInputControlOptions($documentType, &$arFieldType, $jsFunctionName, &$value)
	{
		$result = "";

		static $arDocumentFieldTypes = array();
		if (!array_key_exists($documentType, $arDocumentFieldTypes))
		{
			$arDocumentFieldTypes[$documentType] = self::GetDocumentFieldTypes($documentType);
		}

		if (!array_key_exists($arFieldType["Type"], $arDocumentFieldTypes[$documentType])
			|| !$arDocumentFieldTypes[$documentType][$arFieldType["Type"]]["Complex"])
		{
			return "";
		}

		if ($arFieldType["Type"] == "E:EList")
		{
			if (is_array($value))
			{
				reset($value);
				$valueTmp = intval(current($value));
			}
			else
			{
				$valueTmp = intval($value);
			}

			$iblockId = 0;
			if ($valueTmp > 0)
			{
				$dbResult = CIBlockElement::GetList(array(), array("ID" => $valueTmp), false, false, array("ID", "IBLOCK_ID"));
				if ($arResult = $dbResult->Fetch())
					$iblockId = $arResult["IBLOCK_ID"];
			}
			if ($iblockId <= 0 && intval($arFieldType["Options"]) > 0)
				$iblockId = intval($arFieldType["Options"]);

			$defaultIBlockId = 0;

			$result .= '<select onchange="'.htmlspecialcharsbx($jsFunctionName).'(this.options[this.selectedIndex].value)">';
			$arIBlockType = CIBlockParameters::GetIBlockTypes();
			foreach ($arIBlockType as $iblockTypeId => $iblockTypeName)
			{
				$result .= '<optgroup label="'.$iblockTypeName.'">';

				$dbIBlock = CIBlock::GetList(array("SORT" => "ASC"), array("TYPE" => $iblockTypeId, "ACTIVE" => "Y"));
				while ($arIBlock = $dbIBlock->GetNext())
				{
					$result .= '<option value="'.$arIBlock["ID"].'"'.(($arIBlock["ID"] == $iblockId) ? " selected" : "").'>'.$arIBlock["NAME"].'</option>';
					if (($defaultIBlockId <= 0) || ($arIBlock["ID"] == $iblockId))
						$defaultIBlockId = $arIBlock["ID"];
				}

				$result .= '</optgroup>';
			}
			$result .= '</select><!--__defaultOptionsValue:'.$defaultIBlockId.'--><!--__modifyOptionsPromt:'.GetMessage("IBD_DOCUMENT_MOPROMT").'-->';

			$arFieldType["Options"] = $defaultIBlockId;
		}
		elseif ($arFieldType["Type"] == "L")
		{
			$valueTmp = $arFieldType["Options"];
			if (!is_array($valueTmp))
				$valueTmp = array($valueTmp => $valueTmp);

			$str = '';
			foreach ($valueTmp as $k => $v)
			{
				if (is_array($v) && count($v) == 2)
				{
					$v1 = array_values($v);
					$k = $v1[0];
					$v = $v1[1];
				}

				if ($k != $v)
					$str .= '['.$k.']'.$v;
				else
					$str .= $v;

				$str .= "\n";
			}
			$result .= '<textarea id="WFSFormOptionsX" rows="5" cols="30">'.htmlspecialcharsbx($str).'</textarea><br />';
			$result .= GetMessage("IBD_DOCUMENT_XFORMOPTIONS1").'<br />';
			$result .= GetMessage("IBD_DOCUMENT_XFORMOPTIONS2").'<br />';
			$result .= '<script type="text/javascript">
				function WFSFormOptionsXFunction()
				{
					var result = {};
					var i, id, val, str = document.getElementById("WFSFormOptionsX").value;

					var arr = str.split(/[\r\n]+/);
					var p, re = /\[([^\]]+)\].+/;
					for (i in arr)
					{
						str = arr[i].replace(/^\s+|\s+$/g, \'\');
						if (str.length > 0)
						{
							id = str.match(re);
							if (id)
							{
								p = str.indexOf(\']\');
								id = id[1];
								val = str.substr(p + 1);
							}
							else
							{
								val = str;
								id = val;
							}
							result[id] = val;
						}
					}

					return result;
				}
				</script>';
			$result .= '<input type="button" onclick="'.htmlspecialcharsbx($jsFunctionName).'(WFSFormOptionsXFunction())" value="'.GetMessage("IBD_DOCUMENT_XFORMOPTIONS3").'">';
		}

		return $result;
	}

	public static function GetFieldInputControl($documentType, $arFieldType, $arFieldName, $fieldValue, $bAllowSelection = false, $publicMode = false)
	{
		$v = substr($documentType, strlen("type_"));
		if (intval($v)."!" != $v."!")
			return "";
		$iblockId = intval($v);

		static $arDocumentFieldTypes = array();
		if (!array_key_exists($documentType, $arDocumentFieldTypes))
			$arDocumentFieldTypes[$documentType] = self::GetDocumentFieldTypes($documentType);

		$arFieldType["BaseType"] = "string";
		$arFieldType["Complex"] = false;
		if (array_key_exists($arFieldType["Type"], $arDocumentFieldTypes[$documentType]))
		{
			$arFieldType["BaseType"] = $arDocumentFieldTypes[$documentType][$arFieldType["Type"]]["BaseType"];
			$arFieldType["Complex"] = $arDocumentFieldTypes[$documentType][$arFieldType["Type"]]["Complex"];
		}

		if (!is_array($fieldValue) || is_array($fieldValue) && CBPHelper::IsAssociativeArray($fieldValue))
			$fieldValue = array($fieldValue);

		$customMethodName = "";
		$customMethodNameMulty = "";
		if (strpos($arFieldType["Type"], ":") !== false)
		{
			$ar = CIBlockProperty::GetUserType(substr($arFieldType["Type"], 2));
			if (array_key_exists("GetPublicEditHTML", $ar))
				$customMethodName = $ar["GetPublicEditHTML"];
			if (array_key_exists("GetPublicEditHTMLMulty", $ar))
				$customMethodNameMulty = $ar["GetPublicEditHTMLMulty"];
		}

		ob_start();

		if ($arFieldType["Type"] == "L")
		{
			$fieldValueTmp = $fieldValue;
			?>
			<select id="id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>" name="<?= htmlspecialcharsbx($arFieldName["Field"]).($arFieldType["Multiple"] ? "[]" : "") ?>"<?= ($arFieldType["Multiple"] ? ' size="5" multiple' : '') ?>>
				<?
				if (!$arFieldType["Required"])
					echo '<option value="">['.GetMessage("BPVDX_NOT_SET").']</option>';
				foreach ($arFieldType["Options"] as $k => $v)
				{
					if (is_array($v) && count($v) == 2)
					{
						$v1 = array_values($v);
						$k = $v1[0];
						$v = $v1[1];
					}

					$ind = array_search($k, $fieldValueTmp);
					echo '<option value="'.htmlspecialcharsbx($k).'"'.($ind !== false ? ' selected' : '').'>'.htmlspecialcharsbx($v).'</option>';
					if ($ind !== false)
						unset($fieldValueTmp[$ind]);
				}
				?>
			</select>
			<?
			if ($bAllowSelection)
			{
				?>
				<br /><input type="text" id="id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text" name="<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text" value="<?
				if (count($fieldValueTmp) > 0)
				{
					$a = array_values($fieldValueTmp);
					echo htmlspecialcharsbx($a[0]);
				}
				?>">
				<input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text', 'select');">
				<?
			}
		}
		elseif ($arFieldType["Type"] == "S:UserID")
		{
			$fieldValue = CBPHelper::UsersArrayToString($fieldValue, null, array("bizproc", "CBPVirtualDocument", $documentType));
			?><input type="text" size="40" style="max-width: 85%" id="id_<?= $arFieldName["Field"] ?>" name="<?= $arFieldName["Field"] ?>" value="<?= htmlspecialcharsbx($fieldValue) ?>"><input type="button" value="..." onclick="BPAShowSelector('id_<?= $arFieldName["Field"] ?>', 'user');"><?
		}
		elseif ((strpos($arFieldType["Type"], ":") !== false)
			&& $arFieldType["Multiple"]
			&& (
				is_array($customMethodNameMulty) && count($customMethodNameMulty) > 0
				|| !is_array($customMethodNameMulty) && strlen($customMethodNameMulty) > 0
			)
		)
		{
			if (!is_array($fieldValue))
				$fieldValue = array();

			if ($bAllowSelection)
			{
				$fieldValueTmp1 = array();
				$fieldValueTmp2 = array();
				foreach ($fieldValue as $v)
				{
					$vTrim = trim($v);
					if (CBPActivity::isExpression($vTrim))
						$fieldValueTmp1[] = $vTrim;
					else
						$fieldValueTmp2[] = $v;
				}
			}
			else
			{
				$fieldValueTmp1 = array();
				$fieldValueTmp2 = $fieldValue;
			}

			if (($arFieldType["Type"] == "S:employee") && COption::GetOptionString("bizproc", "employee_compatible_mode", "N") != "Y")
				$fieldValueTmp2 = CBPHelper::StripUserPrefix($fieldValueTmp2);
			if ($arFieldType["Type"] == "E:EList")
			{
				static $fl = true;
				if ($fl)
				{
					if (!empty($_SERVER['HTTP_BX_AJAX']))
						$GLOBALS["APPLICATION"]->ShowAjaxHead();
					$GLOBALS["APPLICATION"]->AddHeadScript('/bitrix/js/iblock/iblock_edit.js');
				}

				$fl = false;
			}

			$fieldValueTmp21 = array();
			foreach ($fieldValueTmp2 as $k => $fld)
			{
				if ($fld === null || $fld === "")
					continue;
				if (is_array($fld) && isset($fld["VALUE"]))
					$fieldValueTmp21[$k] = $fld;
				else
					$fieldValueTmp21[$k] = array("VALUE" => $fld);
			}
			$fieldValueTmp2 = $fieldValueTmp21;

			echo call_user_func_array(
				$customMethodNameMulty,
				array(
					array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
					$fieldValueTmp2,
					array(
						"FORM_NAME" => $arFieldName["Form"],
						"VALUE" => htmlspecialcharsbx($arFieldName["Field"])
					),
					true
				)
			);

			if ($bAllowSelection)
			{
				?>
				<br /><input type="text" id="id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text" name="<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text" value="<?
				if (count($fieldValueTmp1) > 0)
				{
					$a = array_values($fieldValueTmp1);
					echo htmlspecialcharsbx($a[0]);
				}
				?>">
				<input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text', '<?= htmlspecialcharsbx($arFieldType["BaseType"]) ?>', '<?= $arFieldType["Type"] == 'S:employee'? 'employee' : '' ?>');">
				<?
			}
		}
		else
		{
			if (!array_key_exists("CBPVirtualDocumentCloneRowPrinted_".$documentType, $GLOBALS) && $arFieldType["Multiple"])
			{
				$GLOBALS["CBPVirtualDocumentCloneRowPrinted_".$documentType] = 1;
				?>
				<script language="JavaScript">
				<!--
				function CBPVirtualDocumentCloneRow(tableID)
				{
					var tbl = document.getElementById(tableID);
					var cnt = tbl.rows.length;
					var oRow = tbl.insertRow(cnt);
					var oCell = oRow.insertCell(0);
					var sHTML = tbl.rows[cnt - 1].cells[0].innerHTML;
					var p = 0;
					while (true)
					{
						var s = sHTML.indexOf('[n', p);
						if (s < 0)
							break;
						var e = sHTML.indexOf(']', s);
						if (e < 0)
							break;
						var n = parseInt(sHTML.substr(s + 2, e - s));
						sHTML = sHTML.substr(0, s) + '[n' + (++n) + ']' + sHTML.substr(e + 1);
						p = s + 1;
					}
					var p = 0;
					while (true)
					{
						var s = sHTML.indexOf('__n', p);
						if (s < 0)
							break;
						var e = sHTML.indexOf('_', s + 2);
						if (e < 0)
							break;
						var n = parseInt(sHTML.substr(s + 3, e - s));
						sHTML = sHTML.substr(0, s) + '__n' + (++n) + '_' + sHTML.substr(e + 1);
						p = e + 1;
					}
					oCell.innerHTML = sHTML;
					var patt = new RegExp('<' + 'script' + '>[^\000]*?<' + '\/' + 'script' + '>', 'ig');
					var code = sHTML.match(patt);
					if (code)
					{
						for (var i = 0; i < code.length; i++)
						{
							if (code[i] != '')
							{
								var s = code[i].substring(8, code[i].length - 9);
								jsUtils.EvalGlobal(s);
							}
						}
					}
				}
				//-->
				</script>
				<?
			}

			if ($arFieldType["Multiple"])
				echo '<table width="100%" border="0" cellpadding="2" cellspacing="2" id="CBPVirtualDocument_'.$arFieldName["Field"].'_Table">';

			$fieldValueTmp = $fieldValue;

			if (sizeof($fieldValue) == 0)
				$fieldValue[] = null;

			$ind = -1;
			foreach ($fieldValue as $key => $value)
			{
				$ind++;
				$fieldNameId = 'id_'.htmlspecialcharsbx($arFieldName["Field"]).'__n'.$ind.'_';
				$fieldNameName = htmlspecialcharsbx($arFieldName["Field"]).($arFieldType["Multiple"] ? "[n".$ind."]" : "");

				if ($arFieldType["Multiple"])
					echo '<tr><td>';

				if (is_array($customMethodName) && count($customMethodName) > 0 || !is_array($customMethodName) && strlen($customMethodName) > 0)
				{
					$value1 = $value;
					if ($bAllowSelection && CBPActivity::isExpression($value1))
						$value1 = null;
					else
						unset($fieldValueTmp[$key]);

					if (($arFieldType["Type"] == "S:employee") && COption::GetOptionString("bizproc", "employee_compatible_mode", "N") != "Y")
						$value1 = CBPHelper::StripUserPrefix($value1);

					echo call_user_func_array(
						$customMethodName,
						array(
							array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
							array("VALUE" => $value1),
							array(
								"FORM_NAME" => $arFieldName["Form"],
								"VALUE" => $fieldNameName
							),
							true
						)
					);
				}
				else
				{
					switch ($arFieldType["Type"])
					{
						case "N":
							unset($fieldValueTmp[$key]);
							?><input type="text" size="10" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>" value="<?= htmlspecialcharsbx($value) ?>"><?
							break;
						case "F":
							if ($publicMode)
							{
								//unset($fieldValueTmp[$key]);
								?><input type="file" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?
							}
							break;
						case "B":
							if (in_array($value, array("Y", "N")))
								unset($fieldValueTmp[$key]);
							?>
							<select id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>">
								<?
								if (!$arFieldType["Required"])
									echo '<option value="">['.GetMessage("BPVDX_NOT_SET").']</option>';
								?>
								<option value="Y"<?= (in_array("Y", $fieldValue) ? ' selected' : '') ?>><?= GetMessage("BPVDX_YES") ?></option>
								<option value="N"<?= (in_array("N", $fieldValue) ? ' selected' : '') ?>><?= GetMessage("BPVDX_NO") ?></option>
							</select>
							<?
							break;
						case "T":
							unset($fieldValueTmp[$key]);
							?><textarea rows="5" cols="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?= htmlspecialcharsbx($value) ?></textarea><?
							break;
						default:
							unset($fieldValueTmp[$key]);
							?><input type="text" size="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>" value="<?= htmlspecialcharsbx($value) ?>"><?
					}
				}

				if ($bAllowSelection)
				{
					if (!in_array($arFieldType["Type"], array("F", "B")) && (is_array($customMethodName) && count($customMethodName) <= 0 || !is_array($customMethodName) && strlen($customMethodName) <= 0))
					{
						?><input type="button" value="..." onclick="BPAShowSelector('<?= $fieldNameId ?>', '<?= htmlspecialcharsbx($arFieldType["BaseType"]) ?>');"><?
					}
				}

				if ($arFieldType["Multiple"])
					echo '</td></tr>';
			}

			if ($arFieldType["Multiple"])
				echo "</table>";

			if ($arFieldType["Multiple"] && (($arFieldType["Type"] != "F") || $publicMode))
				echo '<input type="button" value="'.GetMessage("BPVDX_ADD").'" onclick="CBPVirtualDocumentCloneRow(\'CBPVirtualDocument_'.htmlspecialcharsbx($arFieldName["Field"]).'_Table\')"/><br />';

			if ($bAllowSelection)
			{
				if (in_array($arFieldType["Type"], array("F", "B")) || (is_array($customMethodName) && count($customMethodName) > 0 || !is_array($customMethodName) && strlen($customMethodName) > 0))
				{
					?>
					<input type="text" id="id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text" name="<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text" value="<?
					if (count($fieldValueTmp) > 0)
					{
						$a = array_values($fieldValueTmp);
						echo htmlspecialcharsbx($a[0]);
					}
					?>">
					<input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text', '<?= htmlspecialcharsbx($arFieldType["BaseType"]) ?>', '<?= $arFieldType["Type"] == 'S:employee'? 'employee' : '' ?>');">
					<?
				}
			}
		}

		$s = ob_get_contents();
		ob_end_clean();

		return $s;
	}

	public static function GetFieldInputValue($documentType, $arFieldType, $arFieldName, $arRequest, &$arErrors)
	{
		$v = substr($documentType, strlen("type_"));
		if (intval($v)."!" != $v."!")
			return null;
		$iblockId = intval($v);

		$result = array();

		if ($arFieldType["Type"] == "S:UserID")
		{
			$value = $arRequest[$arFieldName["Field"]];
			if (strlen($value) > 0)
			{
				$arErrorsTmp1 = array();
				$result = CBPHelper::UsersStringToArray($value, array("bizproc", "CBPVirtualDocument", $documentType), $arErrorsTmp1);
				if (count($arErrorsTmp1) > 0)
				{
					foreach ($arErrorsTmp1 as $e)
						$arErrors[] = $e;
				}
			}
		}
		elseif (array_key_exists($arFieldName["Field"], $arRequest) || array_key_exists($arFieldName["Field"]."_text", $arRequest))
		{
			$arValue = array();
			if (array_key_exists($arFieldName["Field"], $arRequest))
			{
				$arValue = $arRequest[$arFieldName["Field"]];
				if (!is_array($arValue) || is_array($arValue) && CBPHelper::IsAssociativeArray($arValue))
					$arValue = array($arValue);
			}
			if (array_key_exists($arFieldName["Field"]."_text", $arRequest))
				$arValue[] = $arRequest[$arFieldName["Field"]."_text"];

			foreach ($arValue as $value)
			{
				if (is_array($value)
					|| !is_array($value) && !CBPActivity::isExpression($value))
				{
					if ($arFieldType["Type"] == "N")
					{
						if (strlen($value) > 0)
						{
							$value = str_replace(" ", "", str_replace(",", ".", $value));
							if (is_numeric($value))
							{
								$value = doubleval($value);
							}
							else
							{
								$value = null;
								$arErrors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("BPCGWTL_INVALID1N"),
									"parameter" => $arFieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif ($arFieldType["Type"] == "L")
					{
						if (!is_array($arFieldType["Options"]) || count($arFieldType["Options"]) <= 0 || strlen($value) <= 0)
						{
							$value = null;
						}
						else
						{
							$ar = array_values($arFieldType["Options"]);
							if (is_array($ar[0]))
							{
								$b = false;
								foreach ($ar as $a)
								{
									if ($a[0] == $value)
									{
										$b = true;
										break;
									}
								}
								if (!$b)
								{
									$value = null;
									$arErrors[] = array(
										"code" => "ErrorValue",
										"message" => GetMessage("BPCGWTL_INVALID3N"),
										"parameter" => $arFieldName["Field"],
									);
								}
							}
							else
							{
								if (!array_key_exists($value, $arFieldType["Options"]))
								{
									$value = null;
									$arErrors[] = array(
										"code" => "ErrorValue",
										"message" => GetMessage("BPCGWTL_INVALID3N"),
										"parameter" => $arFieldName["Field"],
									);
								}
							}
						}
					}
					elseif ($arFieldType["Type"] == "B")
					{
						if ($value !== "Y" && $value !== "N")
						{
							if ($value === true)
							{
								$value = "Y";
							}
							elseif ($value === false)
							{
								$value = "N";
							}
							elseif (strlen($value) > 0)
							{
								$value = strtolower($value);
								if (in_array($value, array("y", "yes", "true", "1")))
								{
									$value = "Y";
								}
								elseif (in_array($value, array("n", "no", "false", "0")))
								{
									$value = "N";
								}
								else
								{
									$value = null;
									$arErrors[] = array(
										"code" => "ErrorValue",
										"message" => GetMessage("BPCGWTL_INVALID4N"),
										"parameter" => $arFieldName["Field"],
									);
								}
							}
							else
							{
								$value = null;
							}
						}
					}
					elseif ($arFieldType["Type"] == "F")
					{
						if (array_key_exists("name", $value) && strlen($value["name"]) > 0)
						{
							if (!array_key_exists("MODULE_ID", $value))
								$value["MODULE_ID"] = "bizproc";

							$value = CFile::SaveFile($value, "bizproc_wf", true, true);
							if (!$value)
							{
								$value = null;
								$arErrors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("BPCGWTL_INVALID9N"),
									"parameter" => $arFieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif (strpos($arFieldType["Type"], ":") !== false)
					{
						$arCustomType = CIBlockProperty::GetUserType(substr($arFieldType["Type"], 2));
						if (array_key_exists("GetLength", $arCustomType))
						{
							if (call_user_func_array(
								$arCustomType["GetLength"],
								array(
									array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
									array("VALUE" => $value)
								)
							) <= 0)
							{
								$value = null;
							}
						}
						else
						{
							if (!is_array($value) && (strlen($value) == 0) || is_array($value) && (count($value) == 0 || count($value) == 1 && isset($value["VALUE"]) && !is_array($value["VALUE"]) && strlen($value["VALUE"]) == 0))
								$value = null;
						}

						if (($value !== null) && array_key_exists("CheckFields", $arCustomType))
						{
							$arErrorsTmp1 = call_user_func_array(
								$arCustomType["CheckFields"],
								array(
									array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
									array("VALUE" => $value)
								)
							);
							if (count($arErrorsTmp1) > 0)
							{
								$value = null;
								foreach ($arErrorsTmp1 as $e)
									$arErrors[] = array(
										"code" => "ErrorValue",
										"message" => $e,
										"parameter" => $arFieldName["Field"],
									);
							}
						}

						if (($value !== null)
							&& ($arFieldType["Type"] == "S:employee")
							&& COption::GetOptionString("bizproc", "employee_compatible_mode", "N") != "Y")
						{
							$value = "user_".$value;
						}
					}
					else
					{
						if (!is_array($value) && strlen($value) <= 0)
							$value = null;
					}
				}

				if ($value !== null)
					$result[] = $value;
			}
		}

		if (!$arFieldType["Multiple"])
		{
			if (count($result) > 0)
				$result = $result[0];
			else
				$result = null;
		}

		return $result;
	}

	public static function GetFieldInputValuePrintable($documentType, $arFieldType, $fieldValue)
	{
		$result = $fieldValue;

		switch ($arFieldType['Type'])
		{
			case "S:UserID":
				if (!is_array($fieldValue))
					$fieldValue = array($fieldValue);

				$result = CBPHelper::UsersArrayToString($fieldValue, null, array("bizproc", "CBPVirtualDocument", $documentType));
				break;

			case "B":
				if (is_array($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $r)
						$result[] = ((strtoupper($r) != "N" && !empty($r)) ? GetMessage("BPVDX_YES") : GetMessage("BPVDX_NO"));
				}
				else
				{
					$result = ((strtoupper($fieldValue) != "N" && !empty($fieldValue)) ? GetMessage("BPVDX_YES") : GetMessage("BPVDX_NO"));
				}
				break;

			case "F":
				if (is_array($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $r)
					{
						$r = intval($r);
						$dbImg = CFile::GetByID($r);
						if ($arImg = $dbImg->Fetch())
							$result[] = "[url=/bitrix/tools/bizproc_show_file.php?f=".urlencode($arImg["FILE_NAME"])."&i=".$r."]".htmlspecialcharsbx($arImg["ORIGINAL_NAME"])."[/url]";
					}
				}
				else
				{
					$fieldValue = intval($fieldValue);
					$dbImg = CFile::GetByID($fieldValue);
					if ($arImg = $dbImg->Fetch())
						$result = "[url=/bitrix/tools/bizproc_show_file.php?f=".urlencode($arImg["FILE_NAME"])."&i=".$fieldValue."]".htmlspecialcharsbx($arImg["ORIGINAL_NAME"])."[/url]";
				}
				break;
			case "L":
				if (is_array($arFieldType["Options"]))
				{
					if (is_array($fieldValue))
					{
						$result = array();
						foreach ($fieldValue as $r)
						{
							if (array_key_exists($r, $arFieldType["Options"]))
								$result[] = $arFieldType["Options"][$r];
						}
					}
					else
					{
						if (array_key_exists($fieldValue, $arFieldType["Options"]))
							$result = $arFieldType["Options"][$fieldValue];
					}
				}
				break;
		}

		if (strpos($arFieldType['Type'], ":") !== false)
		{
			if ($arFieldType["Type"] == "S:employee")
				$fieldValue = CBPHelper::StripUserPrefix($fieldValue);

			$arCustomType = CIBlockProperty::GetUserType(substr($arFieldType['Type'], 2));
			if (array_key_exists("GetPublicViewHTML", $arCustomType))
			{
				if (is_array($fieldValue) && !CBPHelper::IsAssociativeArray($fieldValue))
				{
					$checkValue = $arCustomType["GetPublicViewHTML"][0] == "CIBlockPropertyElementList";
					$result = array();
					foreach ($fieldValue as $value)
					{
						$v = $checkValue && isset($value['VALUE']) ? $value : array('VALUE' => $value);
						$r = call_user_func_array(
							$arCustomType["GetPublicViewHTML"],
							array(
								array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
								$v,
								""
							)
						);

						$result[] = HTMLToTxt($r);
					}
				}
				else
				{
					$result = call_user_func_array(
						$arCustomType["GetPublicViewHTML"],
						array(
							array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
							array("VALUE" => $fieldValue),
							""
						)
					);

					$result = HTMLToTxt($result);
				}
			}
		}

		return $result;
	}

	public static function CanUserOperateDocument($operation, $userId, $documentId, $arParameters = array())
	{
		$documentId = trim($documentId);
		if (strlen($documentId) <= 0)
			return false;

		$userId = intval($userId);

		if (array_key_exists("UserIsAdmin", $arParameters))
		{
			if ($arParameters["UserIsAdmin"] === true)
				return true;
		}
		else
		{
			$arGroups = CUser::GetUserGroup($userId);
			if (in_array(1, $arGroups))
				return true;
		}

		if (!array_key_exists("TargetUser", $arParameters) || !array_key_exists("DocumentType", $arParameters))
		{
			$dbElementList = CIBlockElement::GetList(
				array(),
				array("ID" => $documentId, "SHOW_NEW" => "Y"),
				false,
				false,
				array("ID", "IBLOCK_ID", "CREATED_BY")
			);
			$arElement = $dbElementList->Fetch();

			if (!$arElement)
				return false;

			$arParameters["TargetUser"] = $arElement["CREATED_BY"];
			$arParameters["DocumentType"] = "type_".$arElement["IBLOCK_ID"];
		}

		if (!array_key_exists("AllUserGroups", $arParameters))
		{
			if (!array_key_exists("UserGroups", $arParameters))
				$arParameters["UserGroups"] = CUser::GetUserGroup($userId);

			$arParameters["AllUserGroups"] = $arParameters["UserGroups"];
			if ($userId == $arParameters["TargetUser"])
				$arParameters["AllUserGroups"][] = "author";
		}

		if (!array_key_exists("DocumentStates", $arParameters))
		{
			$arParameters["DocumentStates"] = CBPDocument::GetDocumentStates(
				array("bizproc", "CBPVirtualDocument", $arParameters["DocumentType"]),
				array("bizproc", "CBPVirtualDocument", $documentId)
			);
		}

		if (array_key_exists("WorkflowId", $arParameters))
		{
			if (array_key_exists($arParameters["WorkflowId"], $arParameters["DocumentStates"]))
				$arParameters["DocumentStates"] = array($arParameters["WorkflowId"] => $arParameters["DocumentStates"][$arParameters["WorkflowId"]]);
			else
				return false;
		}

		$arAllowableOperations = CBPDocument::GetAllowableOperations(
			$userId,
			$arParameters["AllUserGroups"],
			$arParameters["DocumentStates"]
		);

		// $arAllowableOperations == null - workflow is not a statemachine
		// $arAllowableOperations == array() - no allowable operations
		// $arAllowableOperations == array("read", ...) - allowable operations list
		if (!is_array($arAllowableOperations))
			return in_array("author", $arParameters["AllUserGroups"]);

		$r = false;
		switch ($operation)
		{
			case 0:				// DOCUMENT_OPERATION_VIEW_WORKFLOW
				$r = in_array("read", $arAllowableOperations);
				break;
			case 1:				// DOCUMENT_OPERATION_START_WORKFLOW
				$r = in_array("create", $arAllowableOperations);
				break;
			case 4:				// DOCUMENT_OPERATION_CREATE_WORKFLOW
				$r = false;
				break;
			case 2:				// DOCUMENT_OPERATION_WRITE_DOCUMENT
				$r = in_array("create", $arAllowableOperations);
				break;
			case 3:				// DOCUMENT_OPERATION_READ_DOCUMENT
				$r = in_array("read", $arAllowableOperations);
				break;
			default:
				$r = false;
		}

		return $r;
	}

	public static function CanUserOperateDocumentType($operation, $userId, $documentType, $arParameters = array())
	{
		$documentType = trim($documentType);
		if (strlen($documentType) <= 0)
			return false;

		$userId = intval($userId);

		if (array_key_exists("UserIsAdmin", $arParameters))
		{
			if ($arParameters["UserIsAdmin"] === true)
				return true;
		}
		else
		{
			$arGroups = CUser::GetUserGroup($userId);
			if (in_array(1, $arGroups))
				return true;
		}

		if (!array_key_exists("AllUserGroups", $arParameters))
		{
			if (!array_key_exists("UserGroups", $arParameters))
				$arParameters["UserGroups"] = CUser::GetUserGroup($userId);

			$arParameters["AllUserGroups"] = $arParameters["UserGroups"];
			$arParameters["AllUserGroups"][] = "author";
		}

		if (!array_key_exists("DocumentStates", $arParameters))
		{
			$arParameters["DocumentStates"] = CBPDocument::GetDocumentStates(
				array("bizproc", "CBPVirtualDocument", $documentType),
				null
			);
		}

		if (array_key_exists("WorkflowId", $arParameters))
		{
			if (array_key_exists($arParameters["WorkflowId"], $arParameters["DocumentStates"]))
				$arParameters["DocumentStates"] = array($arParameters["WorkflowId"] => $arParameters["DocumentStates"][$arParameters["WorkflowId"]]);
			else
				return false;
		}

		$arAllowableOperations = CBPDocument::GetAllowableOperations(
			$userId,
			$arParameters["AllUserGroups"],
			$arParameters["DocumentStates"]
		);

		// $arAllowableOperations == null - workflow is not a statemachine
		// $arAllowableOperations == array() - no allowable operations
		// $arAllowableOperations == array("read", ...) - allowable operations list
		if (!is_array($arAllowableOperations) && $operation != 4)
			return true;

		if ($operation == 4)
			return true;

		$r = false;
		switch ($operation)
		{
			case 0:				// DOCUMENT_OPERATION_VIEW_WORKFLOW
				$r = false;
				break;
			case 1:				// DOCUMENT_OPERATION_START_WORKFLOW
				$r = in_array("create", $arAllowableOperations);
				break;
			case 4:				// DOCUMENT_OPERATION_CREATE_WORKFLOW
				$r = false;
				break;
			case 2:				// DOCUMENT_OPERATION_WRITE_DOCUMENT
				$r = in_array("create", $arAllowableOperations);
				break;
			case 3:				// DOCUMENT_OPERATION_READ_DOCUMENT
				$r = false;
				break;
			default:
				$r = false;
		}

		return $r;
	}

	public static function GetList($arOrder = array("SORT" => "ASC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields=array())
	{
		global $USER;

		$arFilter["SHOW_NEW"] = "Y";
		$arFilter["ACTIVE"] = "Y";

		if (count($arSelectFields) > 0)
		{
			if (!in_array("ID", $arSelectFields))
				$arSelectFields[] = "ID";
			if (!in_array("IBLOCK_ID", $arSelectFields))
				$arSelectFields[] = "IBLOCK_ID";
			if (!in_array("CREATED_BY", $arSelectFields))
				$arSelectFields[] = "CREATED_BY";
		}

		$arResultList = array();

		$arIDMap = array();

		$dbTasksList = CIBlockElement::GetList(
			$arOrder,
			$arFilter,
			$arGroupBy,
			$arNavStartParams,
			$arSelectFields
		);
		while ($obTask = $dbTasksList->GetNextElement())
		{
			$arResult = array();

			$arFields = $obTask->GetFields();
			foreach ($arFields as $fieldKey => $fieldValue)
			{
				if (substr($fieldKey, 0, 1) != "~")
					continue;
				$fieldKey = substr($fieldKey, 1);

				$arResult[$fieldKey] = $fieldValue;
				$arResult[$fieldKey."_PRINTABLE"] = $fieldValue;

				if (in_array($fieldKey, array("MODIFIED_BY", "CREATED_BY")))
				{
					$arResult[$fieldKey] = "user_".$fieldValue;
					$arResult[$fieldKey."_PRINTABLE"] = self::PrepareUserForPrint($fieldValue);
				}
			}

			$arProperties = $obTask->GetProperties();
			foreach ($arProperties as $propertyKey => $propertyValue)
			{
				$arResult["PROPERTY_".$propertyKey] = $propertyValue["~VALUE"];
				$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = $propertyValue["~VALUE"];

				if (strlen($propertyValue["USER_TYPE"]) > 0)
				{
					if ($propertyValue["USER_TYPE"] == "UserID")
					{
						if (is_array($propertyValue["VALUE"]))
						{
							$arResult["PROPERTY_".$propertyKey] = array();
							foreach ($propertyValue["VALUE"] as $v)
							{
								$v = intval($v);
								if ($v > 0)
									$arResult["PROPERTY_".$propertyKey][] = "user_".$v;
							}
						}
						else
						{
							$arResult["PROPERTY_".$propertyKey] = "";
							if (intval($propertyValue["VALUE"]) > 0)
								$arResult["PROPERTY_".$propertyKey] = "user_".intval($propertyValue["VALUE"]);
						}
						$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = self::PrepareUserForPrint($propertyValue["VALUE"]);
					}
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "G")
				{
					$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = array();
					$vx = self::PrepareSectionForPrint($propertyValue["VALUE"], $propertyValue["LINK_IBLOCK_ID"]);
					foreach ($vx as $vx1 => $vx2)
						$arResult["PROPERTY_".$propertyKey."_PRINTABLE"][$vx1] = $vx2["NAME"];
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "L")
				{
					$arResult["PROPERTY_".$propertyKey] = array();

					$arPropertyValue = $propertyValue["VALUE"];
					$arPropertyKey = $propertyValue["VALUE_ENUM_ID"];
					if (!is_array($arPropertyValue))
					{
						$arPropertyValue = array($arPropertyValue);
						$arPropertyKey = array($arPropertyKey);
					}

					for ($i = 0, $cnt = count($arPropertyValue); $i < $cnt; $i++)
						$arResult["PROPERTY_".$propertyKey][$arPropertyKey[$i]] = $arPropertyValue[$i];

					$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = $arResult["PROPERTY_".$propertyKey];
				}
			}

			if (array_key_exists($arFields["ID"], $arIDMap))
			{
				foreach ($arResultList[$arIDMap[$arFields["ID"]]] as $key => &$value)
				{
					if ($value != $arResult[$key])
					{
						if (!is_array($value))
							$value = array($value);
						$value[] = $arResult[$key];
					}
				}
			}
			else
			{
				$index = count($arResultList);
				$arResultList[$index] = $arResult;
				$arIDMap[$arFields["ID"]] = $index;
			}
		}

		$dbTasksList1 = new CDBResult();
		$dbTasksList1->InitFromArray($arResultList);

		return array($dbTasksList1, $dbTasksList);
	}

	private function PrepareUserForPrint($value)
	{
		$arReturn = array();

		$valueTmp = $value;
		if (!is_array($valueTmp))
			$valueTmp = array($valueTmp);

		if (empty($nameTemplate))
			$nameTemplate = COption::GetOptionString("bizproc", "name_template", CSite::GetNameFormat(false), SITE_ID);

		foreach ($valueTmp as $val)
		{
			$dbUser = CUser::GetByID($val);
			if ($arUser = $dbUser->GetNext())
			{
				$formatName = CUser::FormatName($nameTemplate, $arUser, true);
				$arReturn[] = $formatName." <".$arUser["EMAIL"]."> [".$arUser["ID"]."]";
			}
		}

		return (is_array($value) ? $arReturn : ((count($arReturn) > 0) ? $arReturn[0] : ""));
	}

	private function PrepareSectionForPrint($value, $iblockId = 0)
	{
		if ($iblockId <= 0)
			$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
		if ($iblockId <= 0)
			return false;

		$arReturn = array();

		$valueTmp = $value;
		if (!is_array($valueTmp))
			$valueTmp = array($valueTmp);

		foreach ($valueTmp as $val)
		{
			$ar = array();

			$dbSectionsList = CIBlockSection::GetNavChain($iblockId, $val);
			while ($arSection = $dbSectionsList->GetNext())
				$ar[$arSection["ID"]] = array("NAME" => $arSection["NAME"], "XML_ID" => $arSection["XML_ID"]);

			$arReturn[] = $ar;
		}

		return (is_array($value) ? $arReturn : ((count($arReturn) > 0) ? $arReturn[0] : array()));
	}

	/**
	* @param string $documentId
	* @return string - document admin page url.
	*/
	static public function GetDocumentAdminPage($documentId)
	{
		return null;

		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$db = CIBlockElement::GetList(
			array(),
			array("ID" => $documentId, "SHOW_NEW"=>"Y"),
			false,
			false,
			array("ID", "IBLOCK_ID", "IBLOCK_TYPE_ID")
		);
		if ($ar = $db->Fetch())
			return "/bitrix/admin/iblock_element_edit.php?view=Y&ID=".$documentId."&IBLOCK_ID=".$ar["IBLOCK_ID"]."&type=".$ar["IBLOCK_TYPE_ID"];

		return null;
	}

	static public function GetDocument($documentId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$arResult = null;

		$dbDocumentList = CIBlockElement::GetList(
			array(),
			array("ID" => $documentId, "SHOW_NEW" => "Y")
		);
		if ($objDocument = $dbDocumentList->GetNextElement(false, true))
		{
			$arDocumentFields = $objDocument->GetFields();
			$arDocumentProperties = $objDocument->GetProperties();

			foreach ($arDocumentFields as $fieldKey => $fieldValue)
			{
				if (substr($fieldKey, 0, 1) == "~")
					continue;

				$arResult[$fieldKey] = $fieldValue;
				if (in_array($fieldKey, array("MODIFIED_BY", "CREATED_BY")))
				{
					$arResult[$fieldKey] = "user_".$fieldValue;
					$arResult[$fieldKey."_PRINTABLE"] = $arDocumentFields[($fieldKey == "MODIFIED_BY") ? "USER_NAME" : "CREATED_USER_NAME"];
				}
				elseif (in_array($fieldKey, array("PREVIEW_TEXT", "DETAIL_TEXT")))
				{
					if ($arDocumentFields[$fieldKey."_TYPE"] == "html")
						$arResult[$fieldKey] = HTMLToTxt($arDocumentFields["~".$fieldKey]);
				}
			}

			foreach ($arDocumentProperties as $propertyKey => $propertyValue)
			{
				if (strlen($propertyValue["USER_TYPE"]) > 0)
				{
					if ($propertyValue["USER_TYPE"] == "UserID"
						|| $propertyValue["USER_TYPE"] == "employee" && (COption::GetOptionString("bizproc", "employee_compatible_mode", "N") != "Y"))
					{
						if (!is_array($propertyValue["VALUE"]))
						{
							$db = CUser::GetByID($propertyValue["VALUE"]);
							if ($ar = $db->GetNext())
							{
								$arResult["PROPERTY_".$propertyKey] = "user_".intval($propertyValue["VALUE"]);
								$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = "(".$ar["LOGIN"].")".((strlen($ar["NAME"]) > 0 || strlen($ar["LAST_NAME"]) > 0) ? " " : "").CUser::FormatName(COption::GetOptionString("bizproc", "name_template", CSite::GetNameFormat(false), SITE_ID), $ar);
							}
						}
						else
						{
							for ($i = 0, $cnt = count($propertyValue["VALUE"]); $i < $cnt; $i++)
							{
								$db = CUser::GetByID($propertyValue["VALUE"][$i]);
								if ($ar = $db->GetNext())
								{
									$arResult["PROPERTY_".$propertyKey][] = "user_".intval($propertyValue["VALUE"][$i]);
									$arResult["PROPERTY_".$propertyKey."_PRINTABLE"][$propertyValue["VALUE"][$i]] = "(".$ar["LOGIN"].")".((strlen($ar["NAME"]) > 0 || strlen($ar["LAST_NAME"]) > 0) ? " " : "").CUser::FormatName(COption::GetOptionString("bizproc", "name_template", CSite::GetNameFormat(false), SITE_ID), $ar);
								}
							}
						}
					}
					else
					{
						$arResult["PROPERTY_".$propertyKey] = $propertyValue["VALUE"];
					}
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "L")
				{
					$arPropertyValue = $propertyValue["VALUE"];
					$arPropertyKey = $propertyValue["VALUE_XML_ID"];
					if (!is_array($arPropertyValue))
					{
						$arPropertyValue = array($arPropertyValue);
						$arPropertyKey = array($arPropertyKey);
					}

					for ($i = 0, $cnt = count($arPropertyValue); $i < $cnt; $i++)
						$arResult["PROPERTY_".$propertyKey][$arPropertyKey[$i]] = $arPropertyValue[$i];
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "F")
				{
					if (!is_array($propertyValue["VALUE"]))
					{
						if ((intval($propertyValue["VALUE"]) > 0) && ($ar = CFile::GetFileArray($propertyValue["VALUE"])))
						{
							$arResult["PROPERTY_".$propertyKey] = $propertyValue["VALUE"];
							$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = $ar["SRC"];
						}
					}
					else
					{
						for ($i = 0, $cnt = count($propertyValue["VALUE"]); $i < $cnt; $i++)
						{
							if ((intval($propertyValue["VALUE"][$i]) > 0) && ($ar = CFile::GetFileArray($propertyValue["VALUE"][$i])))
							{
								$arResult["PROPERTY_".$propertyKey][] = $propertyValue["VALUE"][$i];
								$arResult["PROPERTY_".$propertyKey."_PRINTABLE"][$propertyValue["VALUE"][$i]] = $ar["SRC"];
							}
						}
					}
				}
				else
				{
					$arResult["PROPERTY_".$propertyKey] = $propertyValue["VALUE"];
				}
			}
		}

		return $arResult;
	}

	static public function GetDocumentType($documentId)
	{
		if (substr($documentId, 0, strlen("type_")) == "type_")
			return $documentId;

		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$dbResult = CIBlockElement::GetList(array(), array("ID" => $documentId, "SHOW_NEW" => "Y"), false, false, array("ID", "IBLOCK_ID"));
		$arResult = $dbResult->Fetch();
		if (!$arResult)
			throw new Exception("Element is not found");

		return "type_".$arResult["IBLOCK_ID"];
	}

	static public function GetDocumentFields($documentType)
	{
		$v = substr($documentType, strlen("type_"));
		if (intval($v)."!" != $v."!")
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);
		$iblockId = intval($v);

		$arDocumentFieldTypes = self::GetDocumentFieldTypes($documentType);

		$arResult = array(
			"ID" => array(
				"Name" => GetMessage("BPVDX_FIELD_ID"),
				"Type" => "N",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"TIMESTAMP_X" => array(
				"Name" => GetMessage("BPVDX_FIELD_TIMESTAMP_X"),
				"Type" => "S:DateTime",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"MODIFIED_BY" => array(
				"Name" => GetMessage("BPVDX_FIELD_MODYFIED"),
				"Type" => "S:UserID",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"MODIFIED_BY_PRINTABLE" => array(
				"Name" => GetMessage("BPVDX_FIELD_MODIFIED_BY_USER_PRINTABLE"),
				"Type" => "S",
				"Filterable" => false,
				"Editable" => false,
				"Required" => false,
			),
			"DATE_CREATE" => array(
				"Name" => GetMessage("BPVDX_FIELD_DATE_CREATE"),
				"Type" => "S:DateTime",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"CREATED_BY" => array(
				"Name" => GetMessage("BPVDX_FIELD_CREATED"),
				"Type" => "S:UserID",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"CREATED_BY_PRINTABLE" => array(
				"Name" => GetMessage("BPVDX_FIELD_CREATED_BY_USER_PRINTABLE"),
				"Type" => "S",
				"Filterable" => false,
				"Editable" => false,
				"Required" => false,
			),
			"IBLOCK_ID" => array(
				"Name" => GetMessage("BPVDX_FIELD_IBLOCK_ID"),
				"Type" => "N",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"ACTIVE" => array(
				"Name" => GetMessage("BPVDX_FIELD_ACTIVE"),
				"Type" => "B",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			/*"BP_PUBLISHED" => array(
				"Name" => GetMessage("BPVDX_FIELD_BP_PUBLISHED"),
				"Type" => "B",
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),*/
			"ACTIVE_FROM" => array(
				"Name" => GetMessage("BPVDX_FIELD_DATE_ACTIVE_FROM"),
				"Type" => "S:DateTime",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"ACTIVE_TO" => array(
				"Name" => GetMessage("BPVDX_FIELD_DATE_ACTIVE_TO"),
				"Type" => "S:DateTime",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"SORT" => array(
				"Name" => GetMessage("BPVDX_FIELD_SORT"),
				"Type" => "N",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"NAME" => array(
				"Name" => GetMessage("BPVDX_FIELD_NAME"),
				"Type" => "S",
				"Filterable" => true,
				"Editable" => true,
				"Required" => true,
			),
			"PREVIEW_PICTURE" => array(
				"Name" => GetMessage("BPVDX_FIELD_PREVIEW_PICTURE"),
				"Type" => "F",
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),
			"PREVIEW_TEXT" => array(
				"Name" => GetMessage("BPVDX_FIELD_PREVIEW_TEXT"),
				"Type" => "T",
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),
			"PREVIEW_TEXT_TYPE" => array(
				"Name" => GetMessage("BPVDX_FIELD_PREVIEW_TEXT_TYPE"),
				"Type" => "L",
				"Options" => array(
					"text" => GetMessage("BPVDX_DESC_TYPE_TEXT"),
					"html" => "Html",
				),
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),
			"DETAIL_PICTURE" => array(
				"Name" => GetMessage("BPVDX_FIELD_DETAIL_PICTURE"),
				"Type" => "F",
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),
			"DETAIL_TEXT" => array(
				"Name" => GetMessage("BPVDX_FIELD_DETAIL_TEXT"),
				"Type" => "T",
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),
			"DETAIL_TEXT_TYPE" => array(
				"Name" => GetMessage("BPVDX_FIELD_DETAIL_TEXT_TYPE"),
				"Type" => "L",
				"Options" => array(
					"text" => GetMessage("BPVDX_DESC_TYPE_TEXT"),
					"html" => "Html",
				),
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),
			"CODE" => array(
				"Name" => GetMessage("BPVDX_FIELD_CODE"),
				"Type" => "S",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"XML_ID" => array(
				"Name" => GetMessage("BPVDX_FIELD_XML_ID"),
				"Type" => "S",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
		);

		$arKeys = array_keys($arResult);
		foreach ($arKeys as $key)
			$arResult[$key]["Multiple"] = false;

		$dbProperties = CIBlockProperty::GetList(
			array("sort" => "asc", "name" => "asc"),
			array("IBLOCK_ID" => $iblockId)
		);
		while ($arProperty = $dbProperties->Fetch())
		{
			if (strlen(trim($arProperty["CODE"])) > 0)
				$key = "PROPERTY_".$arProperty["CODE"];
			else
				$key = "PROPERTY_".$arProperty["ID"];

			$arResult[$key] = array(
				"Name" => $arProperty["NAME"],
				"Filterable" => ($arProperty["FILTRABLE"] == "Y"),
				"Editable" => true,
				"Required" => ($arProperty["IS_REQUIRED"] == "Y"),
				"Multiple" => ($arProperty["MULTIPLE"] == "Y"),
				"Type" => $arProperty["PROPERTY_TYPE"],
			);

			if (strlen($arProperty["USER_TYPE"]) > 0)
			{
				$arResult[$key]["Type"] = "S:".$arProperty["USER_TYPE"];

				if ($arProperty["USER_TYPE"] == "UserID"
					|| $arProperty["USER_TYPE"] == "employee" && (COption::GetOptionString("bizproc", "employee_compatible_mode", "N") != "Y"))
				{
					$arResult[$key."_PRINTABLE"] = array(
						"Name" => $arProperty["NAME"].GetMessage("BPVDX_FIELD_USERNAME_PROPERTY"),
						"Filterable" => false,
						"Editable" => false,
						"Required" => false,
						"Multiple" => ($arProperty["MULTIPLE"] == "Y"),
						"Type" => "S",
					);
				}
				elseif ($arProperty["USER_TYPE"] == "EList")
				{
					$arResult[$key]["Type"] = "E:EList";
					$arResult[$key]["Options"] = $arProperty["LINK_IBLOCK_ID"];
				}
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "L")
			{
				$arResult[$key]["Options"] = array();
				$dbPropertyEnums = CIBlockProperty::GetPropertyEnum($arProperty["ID"]);
				while ($arPropertyEnum = $dbPropertyEnums->GetNext())
					$arResult[$key]["Options"][$arPropertyEnum["XML_ID"]] = $arPropertyEnum["VALUE"];
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "F")
			{
				$arResult[$key."_PRINTABLE"] = array(
					"Name" => $arProperty["NAME"].GetMessage("BPVDX_FIELD_USERNAME_PROPERTY"),
					"Filterable" => false,
					"Editable" => false,
					"Required" => false,
					"Multiple" => ($arProperty["MULTIPLE"] == "Y"),
					"Type" => "S",
				);
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "S" && intval($arProperty["ROW_COUNT"]) > 1)
				$arResult[$key]["Type"] = "T";
		}

		$arKeys = array_keys($arResult);
		foreach ($arKeys as $k)
		{
			$arResult[$k]["BaseType"] = $arDocumentFieldTypes[$arResult[$k]["Type"]]["BaseType"];
			$arResult[$k]["Complex"] = $arDocumentFieldTypes[$arResult[$k]["Type"]]["Complex"];
		}

		return $arResult;
	}

	static public function GetDocumentFieldTypes($documentType)
	{
		$v = substr($documentType, strlen("type_"));
		if (intval($v)."!" != $v."!")
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);
		$iblockId = intval($v);

		$typesMap = FieldType::getBaseTypesMap();

		$arResult = array(
			"S" => array("Name" => GetMessage("BPVDX_STRING"), "BaseType" => "string", 'typeClass' => $typesMap[FieldType::STRING]),
			"T" => array("Name" => GetMessage("BPVDX_TEXT"), "BaseType" => "text", 'typeClass' => $typesMap[FieldType::TEXT]),
			"N" => array("Name" => GetMessage("BPVDX_NUM"), "BaseType" => "double", 'typeClass' => $typesMap[FieldType::DOUBLE]),
			"L" => array("Name" => GetMessage("BPVDX_LIST"), "BaseType" => "select", "Complex" => true, 'typeClass' => $typesMap[FieldType::SELECT]),
			"F" => array("Name" => GetMessage("BPVDX_FILE"), "BaseType" => "file", 'typeClass' => $typesMap[FieldType::FILE]),
			//"G" => array("Name" => GetMessage("BPVDX_SECT"), "BaseType" => "int"),
			//"E" => array("Name" => GetMessage("BPVDX_ELEM"), "BaseType" => "int"),
			"B" => array("Name" => GetMessage("BPVDX_YN"), "BaseType" => "bool", 'typeClass' => $typesMap[FieldType::BOOL]),
		);

		foreach (CIBlockProperty::GetUserType() as  $ar)
		{
			$t = $ar["PROPERTY_TYPE"].":".$ar["USER_TYPE"];

			if (COption::GetOptionString("bizproc", "SkipNonPublicCustomTypes", "N") == "Y"
				&& !array_key_exists("GetPublicEditHTML", $ar) && $t != "S:UserID" && $t != "S:DateTime")
				continue;

			$arResult[$t] = array("Name" => $ar["DESCRIPTION"], "BaseType" => "string");
			if ($t == "S:UserID")
				$arResult[$t]["BaseType"] = "user";
			elseif ($t == "S:employee" && COption::GetOptionString("bizproc", "employee_compatible_mode", "N") != "Y")
				$arResult[$t]["BaseType"] = "user";
			elseif ($t == "S:DateTime")
			{
				$arResult[$t]["BaseType"] = "datetime";
				$arResult[$t]["typeClass"] = $typesMap[FieldType::DATETIME];
			}
			elseif ($t == "S:Date")
			{
				$arResult[$t]["BaseType"] = "date";
				$arResult[$t]["typeClass"] = $typesMap[FieldType::DATE];
			}
			elseif ($t == "E:EList")
			{
				$arResult[$t]["BaseType"] = "string";
				$arResult[$t]["Complex"] = true;
			}
			elseif ($t == 'S:HTML')
			{
				$arResult[$t]['typeClass'] = '\Bitrix\Iblock\BizprocType\UserTypePropertyHtml';
			}
		}

		return $arResult;
	}

	static public function AddDocumentField($documentType, $arFields)
	{
		$iblockId = intval(substr($documentType, strlen("type_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		if (substr($arFields["code"], 0, strlen("PROPERTY_")) == "PROPERTY_")
			$arFields["code"] = substr($arFields["code"], strlen("PROPERTY_"));

		$arFieldsTmp = array(
			"NAME" => $arFields["name"],
			"ACTIVE" => "Y",
			"SORT" => 150,
			"CODE" => $arFields["code"],
			"MULTIPLE" => $arFields["multiple"],
			"IS_REQUIRED" => $arFields["required"],
			"IBLOCK_ID" => $iblockId,
			"FILTRABLE" => "Y",
		);

		if (strpos("0123456789", substr($arFieldsTmp["CODE"], 0, 1))!==false)
			unset($arFieldsTmp["CODE"]);

		if (array_key_exists("additional_type_info", $arFields))
			$arFieldsTmp["LINK_IBLOCK_ID"] = intval($arFields["additional_type_info"]);

		if (strstr($arFields["type"], ":") !== false)
		{
			list($arFieldsTmp["PROPERTY_TYPE"], $arFieldsTmp["USER_TYPE"]) = explode(":", $arFields["type"], 2);
			if ($arFields["type"] == "E:EList")
				$arFieldsTmp["LINK_IBLOCK_ID"] = $arFields["options"];
		}
		else
		{
			$arFieldsTmp["PROPERTY_TYPE"] = $arFields["type"];
			$arFieldsTmp["USER_TYPE"] = false;
		}

		if ($arFieldsTmp["PROPERTY_TYPE"] == "T")
		{
			$arFieldsTmp["PROPERTY_TYPE"] = "S";
			$arFieldsTmp["ROW_COUNT"] = 5;
		}

		if ($arFields["type"] == "L")
		{
			if (is_array($arFields["options"]))
			{
				$i = 10;
				foreach ($arFields["options"] as $k => $v)
				{
					$arFieldsTmp["VALUES"][] = array("XML_ID" => $k, "VALUE" => $v, "DEF" => "N", "SORT" => $i);
					$i = $i + 10;
				}
			}
			elseif (is_string($arFields["options"]) && (strlen($arFields["options"]) > 0))
			{
				$a = explode("\n", $arFields["options"]);
				$i = 10;
				foreach ($a as $v)
				{
					$v = trim(trim($v), "\r\n");
					$v1 = $v2 = $v;
					if (substr($v, 0, 1) == "[" && strpos($v, "]") !== false)
					{
						$v1 = substr($v, 1, strpos($v, "]") - 1);
						$v2 = trim(substr($v, strpos($v, "]") + 1));
					}
					$arFieldsTmp["VALUES"][] = array("XML_ID" => $v1, "VALUE" => $v2, "DEF" => "N", "SORT" => $i);
					$i = $i + 10;
				}
			}
		}

		$ibp = new CIBlockProperty;
		$propId = $ibp->Add($arFieldsTmp);

		if (intval($propId) <= 0)
			throw new Exception($ibp->LAST_ERROR);

		return "PROPERTY_".$arFields["code"];
	}

	static public function UpdateDocument($documentId, $arFields)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		CIBlockElement::WF_CleanUpHistoryCopies($documentId, 0);

		$arFieldsPropertyValues = array();

		$dbResult = CIBlockElement::GetList(array(), array("ID" => $documentId, "SHOW_NEW" => "Y"), false, false, array("ID", "IBLOCK_ID"));
		$arResult = $dbResult->Fetch();
		if (!$arResult)
			throw new Exception("Element is not found");

		$arDocumentFields = self::GetDocumentFields("type_".$arResult["IBLOCK_ID"]);

		$arKeys = array_keys($arFields);
		foreach ($arKeys as $key)
		{
			if (!array_key_exists($key, $arDocumentFields))
				continue;

			if ($arDocumentFields[$key]["Multiple"] && is_string($arFields[$key]))
			{
				$arFieldsTmp = explode(",", $arFields[$key]);
				$arFields[$key] = array();
				foreach ($arFieldsTmp as $value)
					$arFields[$key][] = trim($value);
			}

			$arFields[$key] = (is_array($arFields[$key]) && !CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);

			if ($arDocumentFields[$key]["Type"] == "S:UserID")
			{
				$ar = array();
				foreach ($arFields[$key] as $v1)
				{
					if (substr($v1, 0, strlen("user_")) == "user_")
					{
						$ar[] = substr($v1, strlen("user_"));
					}
					else
					{
						$a1 = self::GetUsersFromUserGroup($v1, $documentId);
						foreach ($a1 as $a11)
							$ar[] = $a11;
					}
				}

				$arFields[$key] = $ar;
			}
			elseif ($arDocumentFields[$key]["Type"] == "L")
			{
				$realKey = ((substr($key, 0, strlen("PROPERTY_")) == "PROPERTY_") ? substr($key, strlen("PROPERTY_")) : $key);

				$arV = array();
				$db = CIBlockProperty::GetPropertyEnum($realKey, false, array("IBLOCK_ID" => $arResult["IBLOCK_ID"]));
				while ($ar = $db->GetNext())
					$arV[$ar["XML_ID"]] = $ar["ID"];

				foreach ($arFields[$key] as &$value)
				{
					if (array_key_exists($value, $arV))
						$value = $arV[$value];
				}
			}
			elseif ($arDocumentFields[$key]["Type"] == "F")
			{
				foreach ($arFields[$key] as &$value)
					$value = CFile::MakeFileArray($value);
			}
			elseif ($arDocumentFields[$key]["Type"] == "S:HTML")
			{
				foreach ($arFields[$key] as &$value)
					$value = array("VALUE" => $value);
			}

			if (!$arDocumentFields[$key]["Multiple"] && is_array($arFields[$key]))
			{
				if (count($arFields[$key]) > 0)
				{
					$a = array_values($arFields[$key]);
					$arFields[$key] = $a[0];
				}
				else
				{
					$arFields[$key] = null;
				}
			}

			if (substr($key, 0, strlen("PROPERTY_")) == "PROPERTY_")
			{
				$realKey = substr($key, strlen("PROPERTY_"));
				$arFieldsPropertyValues[$realKey] = (is_array($arFields[$key]) && !CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);
				unset($arFields[$key]);
			}
		}

		$iblockElement = new CIBlockElement();

		if (count($arFieldsPropertyValues) > 0)
			$iblockElement->SetPropertyValuesEx($documentId, $arResult["IBLOCK_ID"], $arFieldsPropertyValues);

		if (count($arFields) > 0)
		{
			$res = $iblockElement->Update($documentId, $arFields, false, true, true);
			if (!$res)
				throw new Exception($iblockElement->LAST_ERROR);
		}
	}

	static public function CreateDocument($parentDocumentId, $arFields)
	{
		if (!array_key_exists("IBLOCK_ID", $arFields) || intval($arFields["IBLOCK_ID"]) <= 0)
			throw new Exception("IBlock ID is not found");

		$arDocumentFields = self::GetDocumentFields("type_".$arFields["IBLOCK_ID"]);

		$arKeys = array_keys($arFields);
		foreach ($arKeys as $key)
		{
			if (!array_key_exists($key, $arDocumentFields))
				continue;

			if ($arDocumentFields[$key]["Multiple"] && is_string($arFields[$key]))
			{
				$arFieldsTmp = explode(",", $arFields[$key]);
				$arFields[$key] = array();
				foreach ($arFieldsTmp as $value)
					$arFields[$key][] = trim($value);
			}

			$arFields[$key] = (is_array($arFields[$key]) && !CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);

			if ($arDocumentFields[$key]["Type"] == "S:UserID")
			{
				$ar = array();
				foreach ($arFields[$key] as $v1)
				{
					if (substr($v1, 0, strlen("user_")) == "user_")
					{
						$ar[] = substr($v1, strlen("user_"));
					}
					else
					{
						$a1 = self::GetUsersFromUserGroup($v1, $documentId);
						foreach ($a1 as $a11)
							$ar[] = $a11;
					}
				}

				$arFields[$key] = $ar;
			}
			elseif ($arDocumentFields[$key]["Type"] == "L")
			{
				$realKey = ((substr($key, 0, strlen("PROPERTY_")) == "PROPERTY_") ? substr($key, strlen("PROPERTY_")) : $key);

				$arV = array();
				$db = CIBlockProperty::GetPropertyEnum($realKey, false, array("IBLOCK_ID" => $arFields["IBLOCK_ID"]));
				while ($ar = $db->GetNext())
					$arV[$ar["XML_ID"]] = $ar["ID"];

				foreach ($arFields[$key] as &$value)
				{
					if (array_key_exists($value, $arV))
						$value = $arV[$value];
				}
			}
			elseif ($arDocumentFields[$key]["Type"] == "F")
			{
				foreach ($arFields[$key] as &$value)
					$value = CFile::MakeFileArray($value);
			}
			elseif ($arDocumentFields[$key]["Type"] == "S:HTML")
			{
				foreach ($arFields[$key] as &$value)
					$value = array("VALUE" => $value);
			}

			if (!$arDocumentFields[$key]["Multiple"] && is_array($arFields[$key]))
			{
				if (count($arFields[$key]) > 0)
				{
					$a = array_values($arFields[$key]);
					$arFields[$key] = $a[0];
				}
				else
				{
					$arFields[$key] = null;
				}
			}

			if (substr($key, 0, strlen("PROPERTY_")) == "PROPERTY_")
			{
				$realKey = substr($key, strlen("PROPERTY_"));
				$arFieldsPropertyValues[$realKey] = (is_array($arFields[$key]) && !CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);
				unset($arFields[$key]);
			}
		}

		if (count($arFieldsPropertyValues) > 0)
			$arFields["PROPERTY_VALUES"] = $arFieldsPropertyValues;

		$iblockElement = new CIBlockElement();
		$id = $iblockElement->Add($arFields, false, true, true);
		if (!$id || $id <= 0)
			throw new Exception($iblockElement->LAST_ERROR);
		return $id;
	}

	static public function GetAllowableOperations($documentType)
	{
		return array("read" => GetMessage("BPVDX_OP_READ"), "create" => GetMessage("BPVDX_OP_CREATE")/*, "admin" => GetMessage("BPVDX_OP_ADMIN")*/);
	}

	// array("1" => "Admins", 2 => "Guests", 3 => ..., "Author" => "Author")
	static public function GetAllowableUserGroups($documentType)
	{
		$documentType = trim($documentType);
		if (strlen($documentType) <= 0)
			return false;

		$iblockId = intval(substr($documentType, strlen("type_")));

		$arResult = array("Author" => GetMessage("BPVDX_DOCUMENT_AUTHOR"));

//		$arRes = array(1);
//		$arGroups = CIBlock::GetGroupPermissions($iblockId);
//		foreach ($arGroups as $groupId => $perm)
//		{
//			if ($perm > "R")
//				$arRes[] = $groupId;
//		}

		$dbGroupsList = CGroup::GetListEx(array("NAME" => "ASC"), array("ACTIVE" => "Y"));	//array("ID" => $arRes)
		while ($arGroup = $dbGroupsList->Fetch())
			$arResult[$arGroup["ID"]] = $arGroup["NAME"];

		return $arResult;
	}

	static public function GetUsersFromUserGroup($group, $documentId)
	{
		$group = strtolower($group);
		if ($group == "author")
		{
			$documentId = intval($documentId);
			if ($documentId <= 0)
				return array();

			$db = CIBlockElement::GetList(array(), array("ID" => $documentId, "SHOW_NEW"=>"Y"), false, false, array("ID", "IBLOCK_ID", "CREATED_BY"));
			if ($ar = $db->Fetch())
				return array($ar["CREATED_BY"]);

			return array();
		}

		if ((string)intval($group) !== (string)$group)
			return array();

		$group = (int)$group;
		if ($group <= 0)
			return array();

		$arResult = array();

		$dbUsersList = CUser::GetList(($b = "ID"), ($o = "ASC"), array("GROUPS_ID" => $group, "ACTIVE" => "Y"));
		while ($arUser = $dbUsersList->Fetch())
			$arResult[] = $arUser["ID"];
		return $arResult;
	}

	static public function GetJSFunctionsForFields($documentType, $objectName, $arDocumentFields = array(), $arDocumentFieldTypes = array())
	{
		$iblockId = intval(substr($documentType, strlen("type_")));
		if ($iblockId <= 0)
			return "";

		ob_start();

		echo CAdminCalendar::ShowScript();
		?>
		<script type="text/javascript">
		<?= $objectName ?>.GetGUIFieldEdit = function(field, value, showAddButton, inputName)
		{
			alert("Deprecated method GetGUIFieldEdit used");

			if (!this.arDocumentFields[field])
				return "";

			if (typeof showAddButton == "undefined")
				showAddButton = false;

			if (typeof inputName == "undefined")
				inputName = field;

			var type = this.arDocumentFields[field]["Type"];

			var bAddSelection = false;
			var bAddButton = true;

			s = "";
			if (type == "N")
			{
				s += '<input type="text" size="10" id="id_' + field + '" name="' + inputName + '" value="' + this.HtmlSpecialChars(value) + '">';
			}
			else if (type == "L")
			{
				s += '<select name="' + inputName + '_1">';
				s += '<option value=""></option>';
				for (k in this.arDocumentFields[field]["Options"])
				{
					s += '<option value="' + this.arDocumentFields[field]["Options"][k][0] + '"' + (value == this.arDocumentFields[field]["Options"][k][0] ? " selected" : "") + '>' + this.arDocumentFields[field]["Options"][k][1] + '</option>';
					if (value == this.arDocumentFields[field]["Options"][k][0])
						value = "";
				}
				s += '</select>';
				bAddSelection = true;
			}
			else if (type == "F")
			{
				s += '<input type="file" id="id_' + field + '_1" name="' + inputName + '">';
				bAddSelection = true;
				bAddButton = true;
			}
			else if (type == "B")
			{
				s += '<select name="' + inputName + '_1" id="id_' + name + '">';
				s += '<option value=""></option>';
				s += '<option value="Y"' + (value == "Y" ? " selected" : "") + '><?= GetMessage("BPVDX_YES") ?></option>';
				s += '<option value="N"' + (value == "N" ? " selected" : "") + '><?= GetMessage("BPVDX_NO") ?></option>';
				s += '</select>';
				bAddSelection = true;
				if (value == "Y" || value == "N")
					value = "";
			}
			else if (type == "S:DateTime")
			{
				s += '<span style="white-space:nowrap;">';
				s += '<input type="text" name="' + inputName + '" id="id_' + field + '" size="10" value="' + this.HtmlSpecialChars(value) + '">';
				s += '<a href="javascript:void(0);" title="<?= GetMessage("BPVDX_CALENDAR") ?>">';
				s += '<img src="<?= ADMIN_THEMES_PATH ?>/<?= ADMIN_THEME_ID ?>/images/calendar/icon.gif" alt="<?= GetMessage("BPVDX_CALENDAR") ?>" class="calendar-icon" onclick="jsAdminCalendar.Show(this, \'' + inputName + '\', \'\', \'\', ' + ((type == "datetime") ? 'true' : 'false') + ', <?= time() + date("Z") + CTimeZone::GetOffset() ?>);" onmouseover="this.className+=\' calendar-icon-hover\';" onmouseout="this.className = this.className.replace(/\s*calendar-icon-hover/ig, \'\');">';
				s += '</a></span>';
			}
			//else if (type.substr(0, 2) == "S:" && this.arUserTypes[type.substr(2)])
			//{
			//	s += eval(this.arUserTypes[type.substr(2)] + "(\"" + field + "\", \"" + value + "\")");
			//}
			else // type == "S"
			{
				s += '<input type="text" size="40" id="id_' + field + '" name="' + inputName + '" value="' + this.HtmlSpecialChars(value) + '">';
			}

			if (bAddSelection)
				s += '<br /><input type="text" id="id_' + field + '" name="' + inputName + '" value="' + this.HtmlSpecialChars(value) + '">';

			if (bAddButton && showAddButton)
				s += '<input type="button" value="..." onclick="BPAShowSelector(\'id_' + field + '\', \'' + type + '\');">';

			return s;
		}

		<?= $objectName ?>.SetGUIFieldEdit = function(field)
		{
			alert("Deprecated method SetGUIFieldEdit used");
		}

		<?= $objectName ?>.GetGUIFieldEditSimple = function(type, value, name)
		{
			alert("Deprecated method GetGUIFieldEditSimple used");

			if (typeof name == "undefined" || name.length <= 0)
				name = "BPVDDefaultValue";

			if (typeof value == "undefined")
			{
				value = "";

				var obj = document.getElementById('id_' + name);
				if (obj)
				{
					if (obj.type.substr(0, "select".length) == "select")
						value = obj.options[obj.selectedIndex].value;
					else
						value = obj.value;
				}
			}

			s = "";
			if (type == "F")
			{
				s += '';
			}
			else if (type == "B")
			{
				s += '<select name="' + name + '" id="id_' + name + '">';
				s += '<option value=""></option>';
				s += '<option value="Y"' + (value == "Y" ? " selected" : "") + '><?= GetMessage("BPVDX_YES") ?></option>';
				s += '<option value="N"' + (value == "N" ? " selected" : "") + '><?= GetMessage("BPVDX_NO") ?></option>';
				s += '</select>';
			}
			else if (type == "S:UserID")
			{
				s += '<input type="text" size="10" id="id_' + name + '" name="' + name + '" value="' + this.HtmlSpecialChars(value) + '">';
				s += '<input type="button" value="..." onclick="BPAShowSelector(\'id_' + name + '\', \'user\')">';
			}
			else
			{
				s += '<input type="text" size="10" id="id_' + name + '" name="' + name + '" value="' + this.HtmlSpecialChars(value) + '">';
			}

			return s;
		}

		<?= $objectName ?>.SetGUIFieldEditSimple = function(type, name)
		{
			alert("Deprecated method SetGUIFieldEditSimple used");

			if (typeof name == "undefined" || name.length <= 0)
				name = "BPVDDefaultValue";

			s = "";
			if (type != "F")
			{
				var obj = document.getElementById('id_' + name);
				if (obj)
				{
					if (obj.type.substr(0, "select".length) == "select")
						s = obj.options[obj.selectedIndex].value;
					else
						s = obj.value;
				}
			}

			return s;
		}
		</script>
		<?

		$str = ob_get_contents();
		ob_end_clean();

		return $str;
	}

	public static function GetGUIFieldEdit($documentType, $formName, $fieldName, $fieldValue, $arDocumentField = null, $bAllowSelection = false)
	{
		return self::GetFieldInputControl(
			$documentType,
			$arDocumentField,
			array("Form" => $formName, "Field" => $fieldName),
			$fieldValue,
			$bAllowSelection
		);
	}

	public static function SetGUIFieldEdit($documentType, $fieldName, $arRequest, &$arErrors, $arDocumentField = null)
	{
		return self::GetFieldInputValue($documentType, $arDocumentField, array("Field" => $fieldName), $arRequest, $arErrors);
	}

	public static function GetFieldValuePrintable($documentId, $fieldName, $fieldType, $fieldValue, $arFieldType)
	{
		$documentType = null;

		if ($fieldType == "S:UserID")
		{
			static $arCache = array();
			if (!array_key_exists($documentId, $arCache))
			{
				if (substr($documentId, 0, strlen("type_")) == "type_")
					$arCache[$documentId] = $documentId;
				else
					$arCache[$documentId] = self::GetDocumentType($documentId);
			}
			$documentType = $arCache[$documentId];
		}

		if (is_null($arFieldType) || !is_array($arFieldType) || count($arFieldType) <= 0)
			$arFieldType = array();
		$arFieldType["Type"] = $fieldType;

		return self::GetFieldInputValuePrintable($documentType, $arFieldType, $fieldValue);
	}

	static public function SetPermissions($documentId, $workflowId, $arPermissions, $bRewrite = true)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");
	}

	static public function OnAfterIBlockElementDelete($arFields)
	{
		CBPDocument::OnDocumentDelete(array("bizproc", "CBPVirtualDocument", $arFields["ID"]), $arErrorsTmp);
	}

	public static function isExtendedPermsSupported($documentType)
	{
		return false;
	}
}
?>