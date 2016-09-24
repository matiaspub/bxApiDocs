<?
IncludeModuleLangFile(__FILE__);

if (!CModule::IncludeModule("bizproc"))
	return;

class CIBlockDocument
{
	public static function getEntityName()
	{
		return GetMessage('IBD_DOCUMENT_ENTITY_NAME');
	}

	public static function GetFieldInputControl($documentType, $arFieldType, $arFieldName, $fieldValue, $bAllowSelection = false, $publicMode = false)
	{
		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		global $APPLICATION;
		if(!$publicMode)
			$APPLICATION->showAjaxHead();

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

		if ($arFieldType["Type"] == "select")
		{
			$fieldValueTmp = $fieldValue;
			?>
			<select id="id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>" name="<?= htmlspecialcharsbx($arFieldName["Field"]).($arFieldType["Multiple"] ? "[]" : "") ?>"<?= ($arFieldType["Multiple"] ? ' size="5" multiple' : '') ?>>
				<?
				if (!$arFieldType["Required"])
					echo '<option value="">['.GetMessage("BPCGHLP_NOT_SET").']</option>';
				if(!empty($arFieldType["Options"]))
				{
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
		elseif ($arFieldType["Type"] == "user")
		{
			$fieldValue = CBPHelper::UsersArrayToString($fieldValue, null, array("iblock", "CIBlockDocument", $documentType));
			?><input type="text" size="40" id="id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>" name="<?= htmlspecialcharsbx($arFieldName["Field"]) ?>" value="<?= htmlspecialcharsbx($fieldValue) ?>"><input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>', 'user');"><?
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
					if (CBPDocument::IsExpression($vTrim))
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

			foreach ($fieldValueTmp2 as &$fld)
				if (!isset($fld['VALUE']))
					$fld = array("VALUE" => $fld);

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
				<input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text', 'user',  '<?= $arFieldType["Type"] == 'S:employee'? 'employee' : '' ?>');">
				<?
			}
		}
		else
		{
			if (!array_key_exists("CBPVirtualDocumentCloneRowPrinted", $GLOBALS) && $arFieldType["Multiple"])
			{
				$GLOBALS["CBPVirtualDocumentCloneRowPrinted"] = 1;
				?>
				<script language="JavaScript">
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
				function createAdditionalHtmlEditor(tableId)
				{
					var tbl = document.getElementById(tableId);
					var cnt = tbl.rows.length-1;
					var name = tableId.replace(/(?:CBPVirtualDocument_)(.*)(?:_Table)/, '$1')
					var idEditor = 'id_'+name+'__n'+cnt+'_';
					var inputNameEditor = name+'[n'+cnt+']';
					window.BXHtmlEditor.Show(
						{
							'id':idEditor,
							'inputName':inputNameEditor,
							'content':'',
							'useFileDialogs':false,
							'width':'100%',
							'height':'200',
							'allowPhp':false,
							'limitPhpAccess':false,
							'templates':[],
							'templateId':'',
							'templateParams':[],
							'componentFilter':'',
							'snippets':[],
							'placeholder':'Text here...',
							'actionUrl':'/bitrix/tools/html_editor_action.php',
							'cssIframePath':'/bitrix/js/fileman/html_editor/iframe-style.css?1412693817',
							'bodyClass':'',
							'bodyId':'',
							'spellcheck_path':'/bitrix/js/fileman/html_editor/html-spell.js?v=1412693817',
							'usePspell':'N',
							'useCustomSpell':'Y',
							'bbCode':false,
							'askBeforeUnloadPage':true,
							'settingsKey':'user_settings_1',
							'showComponents':true,
							'showSnippets':true,
							'view':'wysiwyg',
							'splitVertical':false,
							'splitRatio':'1',
							'taskbarShown':false,
							'taskbarWidth':'250',
							'lastSpecialchars':false,
							'cleanEmptySpans':true,
							'lazyLoad':false,
							'showTaskbars':false,
							'showNodeNavi':false,
							'controlsMap':[
								{'id':'Bold','compact':true,'sort':'80'},
								{'id':'Italic','compact':true,'sort':'90'},
								{'id':'Underline','compact':true,'sort':'100'},
								{'id':'Strikeout','compact':true,'sort':'110'},
								{'id':'RemoveFormat','compact':true,'sort':'120'},
								{'id':'Color','compact':true,'sort':'130'},
								{'id':'FontSelector','compact':false,'sort':'135'},
								{'id':'FontSize','compact':false,'sort':'140'},
								{'separator':true,'compact':false,'sort':'145'},
								{'id':'OrderedList','compact':true,'sort':'150'},
								{'id':'UnorderedList','compact':true,'sort':'160'},
								{'id':'AlignList','compact':false,'sort':'190'},
								{'separator':true,'compact':false,'sort':'200'},
								{'id':'InsertLink','compact':true,'sort':'210','wrap':'bx-b-link-'+idEditor},
								{'id':'InsertImage','compact':false,'sort':'220'},
								{'id':'InsertVideo','compact':true,'sort':'230','wrap':'bx-b-video-'+idEditor},
								{'id':'InsertTable','compact':false,'sort':'250'},
								{'id':'Code','compact':true,'sort':'260'},
								{'id':'Quote','compact':true,'sort':'270','wrap':'bx-b-quote-'+idEditor},
								{'id':'Smile','compact':false,'sort':'280'},
								{'separator':true,'compact':false,'sort':'290'},
								{'id':'Fullscreen','compact':false,'sort':'310'},
								{'id':'BbCode','compact':true,'sort':'340'},
								{'id':'More','compact':true,'sort':'400'}],
							'autoResize':true,
							'autoResizeOffset':'40',
							'minBodyWidth':'350',
							'normalBodyWidth':'555'
						});
					var htmlEditor = BX.findChildrenByClassName(BX(tableId), 'bx-html-editor');
					for(var k in htmlEditor)
					{
						var editorId = htmlEditor[k].getAttribute('id');
						var frameArray = BX.findChildrenByClassName(BX(editorId), 'bx-editor-iframe');
						if(frameArray.length > 1)
						{
							for(var i = 0; i < frameArray.length - 1; i++)
							{
								frameArray[i].parentNode.removeChild(frameArray[i]);
							}
						}

					}
				}
				</script>
				<?
			}

			if ($arFieldType["Multiple"])
				echo '<table width="100%" border="0" cellpadding="2" cellspacing="2" id="CBPVirtualDocument_'.htmlspecialcharsbx($arFieldName["Field"]).'_Table">';

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
					if($arFieldType["Type"] == "S:HTML")
					{
						if (CModule::includeModule("fileman"))
						{
							$editor = new \CHTMLEditor;
							$res = array_merge(
								array(
									'useFileDialogs' => false,
									'height' => 200,
									'useFileDialogs' => false,
									'minBodyWidth' => 350,
									'normalBodyWidth' => 555,
									'bAllowPhp' => false,
									'limitPhpAccess' => false,
									'showTaskbars' => false,
									'showNodeNavi' => false,
									'askBeforeUnloadPage' => true,
									'bbCode' => false,
									'siteId' => SITE_ID,
									'autoResize' => true,
									'autoResizeOffset' => 40,
									'saveOnBlur' => true,
									'controlsMap' => array(
										array('id' => 'Bold',  'compact' => true, 'sort' => 80),
										array('id' => 'Italic',  'compact' => true, 'sort' => 90),
										array('id' => 'Underline',  'compact' => true, 'sort' => 100),
										array('id' => 'Strikeout',  'compact' => true, 'sort' => 110),
										array('id' => 'RemoveFormat',  'compact' => true, 'sort' => 120),
										array('id' => 'Color',  'compact' => true, 'sort' => 130),
										array('id' => 'FontSelector',  'compact' => false, 'sort' => 135),
										array('id' => 'FontSize',  'compact' => false, 'sort' => 140),
										array('separator' => true, 'compact' => false, 'sort' => 145),
										array('id' => 'OrderedList',  'compact' => true, 'sort' => 150),
										array('id' => 'UnorderedList',  'compact' => true, 'sort' => 160),
										array('id' => 'AlignList', 'compact' => false, 'sort' => 190),
										array('separator' => true, 'compact' => false, 'sort' => 200),
										array('id' => 'InsertLink',  'compact' => true, 'sort' => 210, 'wrap' => 'bx-b-link-'.$fieldNameId),
										array('id' => 'InsertImage',  'compact' => false, 'sort' => 220),
										array('id' => 'InsertVideo',  'compact' => true, 'sort' => 230, 'wrap' => 'bx-b-video-'.$fieldNameId),
										array('id' => 'InsertTable',  'compact' => false, 'sort' => 250),
										array('id' => 'Code',  'compact' => true, 'sort' => 260),
										array('id' => 'Quote',  'compact' => true, 'sort' => 270, 'wrap' => 'bx-b-quote-'.$fieldNameId),
										array('id' => 'Smile',  'compact' => false, 'sort' => 280),
										array('separator' => true, 'compact' => false, 'sort' => 290),
										array('id' => 'Fullscreen',  'compact' => false, 'sort' => 310),
										array('id' => 'BbCode',  'compact' => true, 'sort' => 340),
										array('id' => 'More',  'compact' => true, 'sort' => 400)
									)
								),
								array(
									'name' => $fieldNameName,
									'inputName' => $fieldNameName,
									'id' => $fieldNameId,
									'width' => '100%',
									'content' => htmlspecialcharsBack($value),
								)
							);
							$editor->show($res);
						}
						else
						{
							?><textarea rows="5" cols="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?= htmlspecialcharsbx($value) ?></textarea><?
						}
					}
					else
					{
						$value1 = $value;
						if ($bAllowSelection && CBPDocument::IsExpression(trim($value1)))
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
				}
				else
				{
					switch ($arFieldType["Type"])
					{
						case "int":
						case "double":
							unset($fieldValueTmp[$key]);
							?><input type="text" size="10" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>" value="<?= htmlspecialcharsbx($value) ?>"><?
							break;
						case "file":
							if ($publicMode)
							{
								//unset($fieldValueTmp[$key]);
								?><input type="file" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?
							}
							break;
						case "bool":
							if (in_array($value, array("Y", "N")))
								unset($fieldValueTmp[$key]);
							?>
							<select id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>">
								<?
								if (!$arFieldType["Required"])
									echo '<option value="">['.GetMessage("BPCGHLP_NOT_SET").']</option>';
								?>
								<option value="Y"<?= (in_array("Y", $fieldValue) ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_YES") ?></option>
								<option value="N"<?= (in_array("N", $fieldValue) ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_NO") ?></option>
							</select>
							<?
							break;
						case "text":
							unset($fieldValueTmp[$key]);
							?><textarea rows="5" cols="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?= htmlspecialcharsbx($value) ?></textarea><?
							break;
						case "date":
						case "datetime":

							if (defined("ADMIN_SECTION") && ADMIN_SECTION)
							{
								$v = "";
								if (!CBPDocument::IsExpression(trim($value)))
								{
									$v = $value;
									unset($fieldValueTmp[$key]);
								}
								require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/init_admin.php");
								echo CAdminCalendar::CalendarDate($fieldNameName, $v, 19, ($arFieldType["Type"] != "date"));
							}
							else
							{
								$value1 = $value;
								if ($bAllowSelection && CBPDocument::IsExpression(trim($value1)))
									$value1 = null;
								else
									unset($fieldValueTmp[$key]);

								if($arFieldType["Type"] == "date")
									$type = "Date";
								else
									$type = "DateTime";
								$ar = CIBlockProperty::GetUserType($type);
								echo call_user_func_array(
									$ar["GetPublicEditHTML"],
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

							break;
						default:
							unset($fieldValueTmp[$key]);
							?><input type="text" size="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>" value="<?= htmlspecialcharsbx($value) ?>"><?
					}
				}

				if ($bAllowSelection)
				{
					if (!in_array($arFieldType["Type"], array("file", "bool", "date", "datetime")) && (is_array($customMethodName) && count($customMethodName) <= 0 || !is_array($customMethodName) && strlen($customMethodName) <= 0))
					{
						?><input type="button" value="..." onclick="BPAShowSelector('<?= $fieldNameId ?>', '<?= htmlspecialcharsbx($arFieldType["BaseType"]) ?>');"><?
					}
				}

				if ($arFieldType["Multiple"])
					echo '</td></tr>';
			}

			if ($arFieldType["Multiple"])
				echo "</table>";

			if ($arFieldType["Multiple"] && $arFieldType["Type"] != "S:HTML" && (($arFieldType["Type"] != "file") || $publicMode))
			{
				echo '<input type="button" value="'.GetMessage("BPCGHLP_ADD").'" onclick="CBPVirtualDocumentCloneRow(\'CBPVirtualDocument_'.$arFieldName["Field"].'_Table\')"/><br />';
			}
			elseif($arFieldType["Multiple"] && $arFieldType["Type"] == "S:HTML")
			{
				$functionOnclick = 'CBPVirtualDocumentCloneRow(\'CBPVirtualDocument_'.$arFieldName["Field"].'_Table\');createAdditionalHtmlEditor(\'CBPVirtualDocument_'.$arFieldName["Field"].'_Table\');';
				echo '<input type="button" value="'.GetMessage("BPCGHLP_ADD").'" onclick="'.$functionOnclick.'"/><br />';
			}

			if ($bAllowSelection)
			{
				if (in_array($arFieldType["Type"], array("file", "bool", "date", "datetime")) || (is_array($customMethodName) && count($customMethodName) > 0 || !is_array($customMethodName) && strlen($customMethodName) > 0))
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

		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		$result = array();

		if ($arFieldType["Type"] == "user")
		{
			$value = $arRequest[$arFieldName["Field"]];
			if (strlen($value) > 0)
			{
				$result = CBPHelper::UsersStringToArray($value, array("iblock", "CIBlockDocument", $documentType), $arErrors);
				if (count($arErrors) > 0)
				{
					foreach ($arErrors as $e)
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
				if (is_array($value) || !is_array($value) && !CBPDocument::IsExpression(trim($value)))
				{
					if ($arFieldType["Type"] == "int")
					{
						if (strlen($value) > 0)
						{
							$value = str_replace(" ", "", $value);
							if ($value."|" == intval($value)."|")
							{
								$value = intval($value);
							}
							else
							{
								$value = null;
								$arErrors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("BPCGWTL_INVALID1"),
									"parameter" => $arFieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif ($arFieldType["Type"] == "double")
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
									"message" => GetMessage("BPCGWTL_INVALID11"),
									"parameter" => $arFieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif ($arFieldType["Type"] == "select")
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
										"message" => GetMessage("BPCGWTL_INVALID35"),
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
										"message" => GetMessage("BPCGWTL_INVALID35"),
										"parameter" => $arFieldName["Field"],
									);
								}
							}
						}
					}
					elseif ($arFieldType["Type"] == "bool")
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
										"message" => GetMessage("BPCGWTL_INVALID45"),
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
					elseif ($arFieldType["Type"] == "file")
					{
						if (is_array($value) && array_key_exists("name", $value) && strlen($value["name"]) > 0)
						{
							if (!array_key_exists("MODULE_ID", $value) || strlen($value["MODULE_ID"]) <= 0)
								$value["MODULE_ID"] = "bizproc";

							$value = CFile::SaveFile($value, "bizproc_wf", true, true);
							if (!$value)
							{
								$value = null;
								$arErrors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("BPCGWTL_INVALID915"),
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

						if (($value != null) && array_key_exists("CheckFields", $arCustomType))
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
						elseif (!array_key_exists("GetLength", $arCustomType) && $value === '')
							$value = null;

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
			case "user":
				if (!is_array($fieldValue))
					$fieldValue = array($fieldValue);

				$result = CBPHelper::UsersArrayToString($fieldValue, null, array("iblock", "CIBlockDocument", $documentType));
				break;

			case "bool":
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

			case "file":
				if (is_array($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $r)
					{
						$r = intval($r);
						$dbImg = CFile::GetByID($r);
						if ($arImg = $dbImg->Fetch())
							$result[] = "[url=/bitrix/tools/bizproc_show_file.php?f=".urlencode($arImg["FILE_NAME"])."&i=".$r."&h=".md5($arImg["SUBDIR"])."]".htmlspecialcharsbx($arImg["ORIGINAL_NAME"])."[/url]";
					}
				}
				else
				{
					$fieldValue = intval($fieldValue);
					$dbImg = CFile::GetByID($fieldValue);
					if ($arImg = $dbImg->Fetch())
						$result = "[url=/bitrix/tools/bizproc_show_file.php?f=".urlencode($arImg["FILE_NAME"])."&i=".$fieldValue."&h=".md5($arImg["SUBDIR"])."]".htmlspecialcharsbx($arImg["ORIGINAL_NAME"])."[/url]";
				}
				break;

			case "select":
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
					$result = array();
					foreach ($fieldValue as $value)
					{
						$r = call_user_func_array(
							$arCustomType["GetPublicViewHTML"],
							array(
								array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
								array("VALUE" => $value),
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

	public static function GetFieldValuePrintable($documentId, $fieldName, $fieldType, $fieldValue, $arFieldType)
	{
		$documentType = null;

		if ($fieldType == "user")
		{
			static $arCache = array();
			if (!array_key_exists($documentId, $arCache))
			{
				if (substr($documentId, 0, strlen("iblock_")) == "iblock_")
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

	public static function GetVersion()
	{
		static $v = null;
		if (is_null($v))
			$v = intval(COption::GetOptionString("iblock", "~iblock_document_version", 2));
		return $v;
	}

	/**
	* @param string $documentId - document id.
	* @return string - document admin page url.
	*/
	static public function GetDocumentAdminPage($documentId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$db = CIBlockElement::GetList(
			array(),
			array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y"),
			false,
			false,
			array("ID", "IBLOCK_ID", "IBLOCK_TYPE_ID", "DETAIL_PAGE_URL")
		);
		if ($ar = $db->Fetch())
		{
			foreach(GetModuleEvents("iblock", "CIBlockDocument_OnGetDocumentAdminPage", true) as $arEvent)
			{
				$url = ExecuteModuleEventEx($arEvent, array($ar));
				if($url)
					return $url;
			}
			return "/bitrix/admin/iblock_element_edit.php?view=Y&ID=".$documentId."&IBLOCK_ID=".$ar["IBLOCK_ID"]."&type=".$ar["IBLOCK_TYPE_ID"];
		}

		return null;
	}

	/**
	 * @param $documentId
	 * @return null|string
	 * @throws CBPArgumentNullException
	 */

	static public function getDocumentName($documentId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$db = CIBlockElement::GetList(
			array(),
			array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y"),
			false,
			false,
			array("ID", "NAME")
		);
		if ($ar = $db->fetch())
		{
			return $ar["NAME"];
		}

		return null;
	}

	public static function getDocumentTypeName($documentType)
	{
		if (strpos($documentType, 'iblock_') !== 0)
			return $documentType;

		$id = (int) substr($documentType, 7);

		$iterator = CIBlock::GetList(false,array('ID' => $id));
		$result = $iterator->fetch();
		if (!$result)
			return $documentType;

		return '['.$result['IBLOCK_TYPE_ID'].'] '.$result['NAME'];
	}

	/**
	 * @param $documentId
	 * @return array - document fields array.
	 * @throws CBPArgumentNullException
	 * @throws CBPArgumentOutOfRangeException
	 */
	static public function GetDocument($documentId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$arResult = null;

		$dbDocumentList = CIBlockElement::GetList(
			array(),
			array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y")
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
					if ($propertyValue["USER_TYPE"] == "UserID" || $propertyValue["USER_TYPE"] == "employee" &&
						(COption::GetOptionString("bizproc", "employee_compatible_mode", "N") != "Y"))
					{
						if(empty($propertyValue["VALUE"]))
						{
							continue;
						}
						if(!is_array($propertyValue["VALUE"]))
						{
							$propertyValue["VALUE"] = array($propertyValue["VALUE"]);
						}
						$listUsers = implode(' | ', $propertyValue["VALUE"]);
						$userQuery = CUser::getList($by = 'ID', $order = 'ASC',
							array('ID' => $listUsers) ,
							array('FIELDS' => array('ID' ,'LOGIN', 'NAME', 'LAST_NAME')));
						while($user = $userQuery->fetch())
						{
							if($propertyValue["MULTIPLE"] == "Y")
							{
								$arResult["PROPERTY_".$propertyKey][$user['ID']] = "user_".intval($user['ID']);
								$arResult["PROPERTY_".$propertyKey."_PRINTABLE"][$user['ID']] = "(".$user["LOGIN"].")".
									((strlen($user["NAME"]) > 0 || strlen($user["LAST_NAME"]) > 0) ? " " : "").$user["NAME"].
									((strlen($user["NAME"]) > 0 && strlen($user["LAST_NAME"]) > 0) ? " " : "").$user["LAST_NAME"];
							}
							else
							{
								$arResult["PROPERTY_".$propertyKey] = "user_".intval($user['ID']);
								$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = "(".$user["LOGIN"].")".
									((strlen($user["NAME"]) > 0 || strlen($user["LAST_NAME"]) > 0) ? " " : "").$user["NAME"].
									((strlen($user["NAME"]) > 0 && strlen($user["LAST_NAME"]) > 0) ? " " : "").$user["LAST_NAME"];
							}
						}
					}
					elseif($propertyValue["USER_TYPE"] == "DiskFile")
					{
						if(is_array($propertyValue["VALUE"]))
						{
							if($propertyValue["MULTIPLE"] == "Y")
							{
								$propertyValue["VALUE"] = current($propertyValue["VALUE"]);
							}

							if(!is_array($propertyValue["VALUE"]))
							{
								continue;
							}

							foreach($propertyValue["VALUE"] as $attachedId)
							{
								$userType = \CIBlockProperty::getUserType($propertyValue['USER_TYPE']);
								$fileId = null;
								if (array_key_exists('GetObjectId', $userType))
								{
									$fileId = call_user_func_array($userType['GetObjectId'], array($attachedId));
								}
								if(!$fileId)
								{
									continue;
								}
								$printableUrl = '';
								if (array_key_exists('GetUrlAttachedFileElement', $userType))
								{
									$printableUrl = call_user_func_array($userType['GetUrlAttachedFileElement'], array($documentId, $fileId));
								}

								$arResult["PROPERTY_".$propertyKey][$attachedId] = $fileId;
								$arResult["PROPERTY_".$propertyKey."_PRINTABLE"][$attachedId] = $printableUrl;
							}
						}
						else
						{
							continue;
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
					$arPropertyKey = ((self::GetVersion() > 1) ? $propertyValue["VALUE_XML_ID"] : $propertyValue["VALUE_ENUM_ID"]);
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
					$arPropertyValue = $propertyValue["VALUE"];
					if (!is_array($arPropertyValue))
						$arPropertyValue = array($arPropertyValue);

					foreach ($arPropertyValue as $v)
					{
						$ar = CFile::GetFileArray($v);
						if ($ar)
						{
							$arResult["PROPERTY_".$propertyKey][intval($v)] = $ar["SRC"];
							$arResult["PROPERTY_".$propertyKey."_printable"][intval($v)] = "[url=/bitrix/tools/bizproc_show_file.php?f=".urlencode($ar["FILE_NAME"])."&i=".$v."&h=".md5($ar["SUBDIR"])."]".htmlspecialcharsbx($ar["ORIGINAL_NAME"])."[/url]";
						}
					}
				}
				else
				{
					$arResult["PROPERTY_".$propertyKey] = $propertyValue["VALUE"];
				}
			}

			$documentFields = static::GetDocumentFields(static::GetDocumentType($documentId));
			foreach ($documentFields as $fieldKey => $field)
			{
				if (!array_key_exists($fieldKey, $arResult))
					$arResult[$fieldKey] = null;
			}
		}

		return $arResult;
	}

	static public function GetDocumentType($documentId)
	{
		if (substr($documentId, 0, strlen("iblock_")) == "iblock_")
			return $documentId;

		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$dbResult = CIBlockElement::GetList(array(), array("ID" => $documentId, "SHOW_NEW" => "Y", "SHOW_HISTORY" => "Y"), false, false, array("ID", "IBLOCK_ID"));
		$arResult = $dbResult->Fetch();
		if (!$arResult)
			throw new Exception("Element is not found");

		return "iblock_".$arResult["IBLOCK_ID"];
	}

	static public function GetDocumentFields($documentType)
	{
		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		$arDocumentFieldTypes = self::GetDocumentFieldTypes($documentType);

		$arResult = array(
			"ID" => array(
				"Name" => GetMessage("IBD_FIELD_ID"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"TIMESTAMP_X" => array(
				"Name" => GetMessage("IBD_FIELD_TIMESTAMP_X"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"MODIFIED_BY" => array(
				"Name" => GetMessage("IBD_FIELD_MODYFIED"),
				"Type" => "user",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"MODIFIED_BY_PRINTABLE" => array(
				"Name" => GetMessage("IBD_FIELD_MODIFIED_BY_USER_PRINTABLE"),
				"Type" => "string",
				"Filterable" => false,
				"Editable" => false,
				"Required" => false,
			),
			"DATE_CREATE" => array(
				"Name" => GetMessage("IBD_FIELD_DATE_CREATE"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"CREATED_BY" => array(
				"Name" => GetMessage("IBD_FIELD_CREATED"),
				"Type" => "user",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"CREATED_BY_PRINTABLE" => array(
				"Name" => GetMessage("IBD_FIELD_CREATED_BY_USER_PRINTABLE"),
				"Type" => "string",
				"Filterable" => false,
				"Editable" => false,
				"Required" => false,
			),
			"IBLOCK_ID" => array(
				"Name" => GetMessage("IBD_FIELD_IBLOCK_ID"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => true,
				"Required" => true,
			),
			"ACTIVE" => array(
				"Name" => GetMessage("IBD_FIELD_ACTIVE"),
				"Type" => "bool",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"BP_PUBLISHED" => array(
				"Name" => GetMessage("IBD_FIELD_BP_PUBLISHED"),
				"Type" => "bool",
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),
			"ACTIVE_FROM" => array(
				"Name" => GetMessage("IBD_FIELD_DATE_ACTIVE_FROM"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"ACTIVE_TO" => array(
				"Name" => GetMessage("IBD_FIELD_DATE_ACTIVE_TO"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"SORT" => array(
				"Name" => GetMessage("IBD_FIELD_SORT"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"NAME" => array(
				"Name" => GetMessage("IBD_FIELD_NAME"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => true,
			),
			"PREVIEW_PICTURE" => array(
				"Name" => GetMessage("IBD_FIELD_PREVIEW_PICTURE"),
				"Type" => "file",
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),
			"PREVIEW_TEXT" => array(
				"Name" => GetMessage("IBD_FIELD_PREVIEW_TEXT"),
				"Type" => "text",
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),
			"PREVIEW_TEXT_TYPE" => array(
				"Name" => GetMessage("IBD_FIELD_PREVIEW_TEXT_TYPE"),
				"Type" => "select",
				"Options" => array(
					"text" => GetMessage("IBD_DESC_TYPE_TEXT"),
					"html" => "Html",
				),
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),
			"DETAIL_PICTURE" => array(
				"Name" => GetMessage("IBD_FIELD_DETAIL_PICTURE"),
				"Type" => "file",
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),
			"DETAIL_TEXT" => array(
				"Name" => GetMessage("IBD_FIELD_DETAIL_TEXT"),
				"Type" => "text",
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),
			"DETAIL_TEXT_TYPE" => array(
				"Name" => GetMessage("IBD_FIELD_DETAIL_TEXT_TYPE"),
				"Type" => "select",
				"Options" => array(
					"text" => GetMessage("IBD_DESC_TYPE_TEXT"),
					"html" => "Html",
				),
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),
			"CODE" => array(
				"Name" => GetMessage("IBD_FIELD_CODE"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"XML_ID" => array(
				"Name" => GetMessage("IBD_FIELD_XML_ID"),
				"Type" => "string",
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
			array("IBLOCK_ID" => $iblockId, 'ACTIVE' => 'Y')
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
			);

			if(strlen(trim($arProperty["CODE"])) > 0)
				$arResult[$key]["Alias"] = "PROPERTY_".$arProperty["ID"];

			if (strlen($arProperty["USER_TYPE"]) > 0)
			{
				if ($arProperty["USER_TYPE"] == "UserID"
					|| $arProperty["USER_TYPE"] == "employee" && (COption::GetOptionString("bizproc", "employee_compatible_mode", "N") != "Y"))
				{
					$arResult[$key]["Type"] = "user";
					$arResult[$key."_PRINTABLE"] = array(
						"Name" => $arProperty["NAME"].GetMessage("IBD_FIELD_USERNAME_PROPERTY"),
						"Filterable" => false,
						"Editable" => false,
						"Required" => false,
						"Multiple" => ($arProperty["MULTIPLE"] == "Y"),
						"Type" => "string",
					);
				}
				elseif ($arProperty["USER_TYPE"] == "DateTime")
				{
					$arResult[$key]["Type"] = "datetime";
				}
				elseif ($arProperty["USER_TYPE"] == "Date")
				{
					$arResult[$key]["Type"] = "date";
				}
				elseif ($arProperty["USER_TYPE"] == "EList")
				{
					$arResult[$key]["Type"] = "E:EList";
					$arResult[$key]["Options"] = $arProperty["LINK_IBLOCK_ID"];
				}
				elseif($arProperty["USER_TYPE"] == "DiskFile")
				{
					$arResult[$key]["Type"] = "S:DiskFile";
					$arResult[$key."_PRINTABLE"] = array(
						"Name" => $arProperty["NAME"].GetMessage("IBD_FIELD_USERNAME_PROPERTY"),
						"Filterable" => false,
						"Editable" => false,
						"Required" => false,
						"Multiple" => ($arProperty["MULTIPLE"] == "Y"),
						"Type" => "int",
					);
				}
				elseif ($arProperty["USER_TYPE"] == "HTML")
				{
					$arResult[$key]["Type"] = "S:HTML";
				}
				else
				{
					$arResult[$key]["Type"] = "string";
				}
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "L")
			{
				$arResult[$key]["Type"] = "select";

				$arResult[$key]["Options"] = array();
				$dbPropertyEnums = CIBlockProperty::GetPropertyEnum($arProperty["ID"]);
				while ($arPropertyEnum = $dbPropertyEnums->GetNext())
					$arResult[$key]["Options"][(self::GetVersion() > 1) ? $arPropertyEnum["XML_ID"] : $arPropertyEnum["ID"]] = $arPropertyEnum["VALUE"];
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "N")
			{
				$arResult[$key]["Type"] = "int";
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "F")
			{
				$arResult[$key]["Type"] = "file";
				$arResult[$key."_printable"] = array(
					"Name" => $arProperty["NAME"].GetMessage("IBD_FIELD_USERNAME_PROPERTY"),
					"Filterable" => false,
					"Editable" => false,
					"Required" => false,
					"Multiple" => ($arProperty["MULTIPLE"] == "Y"),
					"Type" => "string",
				);
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "S")
			{
				$arResult[$key]["Type"] = "string";
			}
			else
			{
				$arResult[$key]["Type"] = "string";
			}
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
		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		$arResult = array(
			"string" => array("Name" => GetMessage("BPCGHLP_PROP_STRING"), "BaseType" => "string"),
			"text" => array("Name" => GetMessage("BPCGHLP_PROP_TEXT"), "BaseType" => "text"),
			"int" => array("Name" => GetMessage("BPCGHLP_PROP_INT"), "BaseType" => "int"),
			"double" => array("Name" => GetMessage("BPCGHLP_PROP_DOUBLE"), "BaseType" => "double"),
			"select" => array("Name" => GetMessage("BPCGHLP_PROP_SELECT"), "BaseType" => "select", "Complex" => true),
			"bool" => array("Name" => GetMessage("BPCGHLP_PROP_BOOL"), "BaseType" => "bool"),
			"date" => array("Name" => GetMessage("BPCGHLP_PROP_DATA"), "BaseType" => "date"),
			"datetime" => array("Name" => GetMessage("BPCGHLP_PROP_DATETIME"), "BaseType" => "datetime"),
			"user" => array("Name" => GetMessage("BPCGHLP_PROP_USER"), "BaseType" => "user"),
			"file" => array("Name" => GetMessage("BPCGHLP_PROP_FILE"), "BaseType" => "file"),
		);

		$ignoredTypes = array('map_yandex');

		//TODO: remove class_exists, add version_control
		if (class_exists('\Bitrix\Bizproc\BaseType\InternalSelect'))
		{
			$arResult[\Bitrix\Bizproc\FieldType::INTERNALSELECT] = array(
				"Name" => GetMessage("BPCGHLP_PROP_SELECT_INTERNAL"),
				"BaseType" => "string",
				"Complex" => true,
			);
		}

		foreach (CIBlockProperty::GetUserType() as  $ar)
		{
			if(in_array($ar["USER_TYPE"], $ignoredTypes))
			{
				continue;
			}

			$t = $ar["PROPERTY_TYPE"].":".$ar["USER_TYPE"];
			if (COption::GetOptionString("bizproc", "SkipNonPublicCustomTypes", "N") == "Y"
				&& !array_key_exists("GetPublicEditHTML", $ar) || $t == "S:UserID" || $t == "S:DateTime" || $t == "S:Date")
				continue;

			$arResult[$t] = array("Name" => $ar["DESCRIPTION"], "BaseType" => "string", 'typeClass' => '\Bitrix\Iblock\BizprocType\UserTypeProperty');
			if ($t == "S:employee")
			{
				if (COption::GetOptionString("bizproc", "employee_compatible_mode", "N") != "Y")
					$arResult[$t]["BaseType"] = "user";
				$arResult[$t]['typeClass'] = '\Bitrix\Iblock\BizprocType\UserTypePropertyEmployee';
			}
			elseif ($t == "E:EList")
			{
				$arResult[$t]["BaseType"] = "string";
				$arResult[$t]["Complex"] = true;
				$arResult[$t]['typeClass'] = '\Bitrix\Iblock\BizprocType\UserTypePropertyElist';
			}
			elseif ($t == 'S:HTML')
			{
				$arResult[$t]['typeClass'] = '\Bitrix\Iblock\BizprocType\UserTypePropertyHtml';
			}
			elseif($t == 'S:DiskFile')
			{
				$arResult[$t]["BaseType"] = "int";
				$arResult[$t]['typeClass'] = '\Bitrix\Iblock\BizprocType\UserTypePropertyDiskFile';
			}
		}

		return $arResult;
	}

	public static function generateMnemonicCode($integerCode = 0)
	{
		if(!$integerCode)
			$integerCode = time();

		$code = '';
		for ($i = 1; $integerCode >= 0 && $i < 10; $i++)
		{
			$code = chr(0x41 + ($integerCode % pow(26, $i) / pow(26, $i - 1))) . $code;
			$integerCode -= pow(26, $i);
		}
		return $code;
	}

	static public function AddDocumentField($documentType, $arFields)
	{
		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		if (substr($arFields["code"], 0, strlen("PROPERTY_")) == "PROPERTY_")
			$arFields["code"] = substr($arFields["code"], strlen("PROPERTY_"));

		$arFieldsTmp = array(
			"NAME" => $arFields["name"],
			"ACTIVE" => "Y",
			"SORT" => 150,
			"CODE" => $arFields["code"],
			'MULTIPLE' => $arFields['multiple'] == 'Y' || (string)$arFields['multiple'] === '1' ? 'Y' : 'N',
			'IS_REQUIRED' => $arFields['required'] == 'Y' || (string)$arFields['required'] === '1' ? 'Y' : 'N',
			"IBLOCK_ID" => $iblockId,
			"FILTRABLE" => "Y",
		);

		if (strpos("0123456789", substr($arFieldsTmp["CODE"], 0, 1))!==false)
			$arFieldsTmp["CODE"] = self::generateMnemonicCode($arFieldsTmp["CODE"]);

		if (array_key_exists("additional_type_info", $arFields))
			$arFieldsTmp["LINK_IBLOCK_ID"] = intval($arFields["additional_type_info"]);

		if (strstr($arFields["type"], ":") !== false)
		{
			if($arFields["type"] == "S:DiskFile")
			{
				$arFieldsTmp["TYPE"] = $arFields["type"];
			}
			else
			{
				list($arFieldsTmp["PROPERTY_TYPE"], $arFieldsTmp["USER_TYPE"]) = explode(":", $arFields["type"], 2);
				if ($arFields["type"] == "E:EList")
					$arFieldsTmp["LINK_IBLOCK_ID"] = $arFields["options"];
			}
		}
		elseif ($arFields["type"] == "user")
		{
			$arFieldsTmp["PROPERTY_TYPE"] = "S";
			$arFieldsTmp["USER_TYPE"]= "UserID";
		}
		elseif ($arFields["type"] == "date")
		{
			$arFieldsTmp["PROPERTY_TYPE"] = "S";
			$arFieldsTmp["USER_TYPE"]= "Date";
		}
		elseif ($arFields["type"] == "datetime")
		{
			$arFieldsTmp["PROPERTY_TYPE"] = "S";
			$arFieldsTmp["USER_TYPE"]= "DateTime";
		}
		elseif ($arFields["type"] == "file")
		{
			$arFieldsTmp["PROPERTY_TYPE"] = "F";
			$arFieldsTmp["USER_TYPE"]= "";
		}
		elseif ($arFields["type"] == "select")
		{
			$arFieldsTmp["PROPERTY_TYPE"] = "L";
			$arFieldsTmp["USER_TYPE"]= false;

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
					if (!$v)
						continue;
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
		elseif($arFields["type"] == "bool")
		{
			$arFieldsTmp["TYPE"] = "L";
			$arFieldsTmp["VALUES"][] = array(
				"XML_ID" => 'Y',
				"VALUE" => GetMessage("BPVDX_YES"),
				"DEF" => "N",
				"SORT" => 10
			);
			$arFieldsTmp["VALUES"][] = array(
				"XML_ID" => 'N',
				"VALUE" => GetMessage("BPVDX_NO"),
				"DEF" => "N",
				"SORT" => 20
			);
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

		$properties = CIBlockProperty::getList(
			array(),
			array("IBLOCK_ID" => $arFieldsTmp["IBLOCK_ID"], "CODE" => $arFieldsTmp["CODE"])
		);
		if(!$properties->fetch())
		{
			$ibp = new CIBlockProperty;
			$propId = $ibp->Add($arFieldsTmp);
			if (intval($propId) <= 0)
				throw new Exception($ibp->LAST_ERROR);
		}

		return "PROPERTY_".$arFields["code"];
	}

	static public function UpdateDocument($documentId, $arFields)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		CIBlockElement::WF_CleanUpHistoryCopies($documentId, 0);

		$arFieldsPropertyValues = array();

		$dbResult = CIBlockElement::GetList(
			array(),
			array("ID" => $documentId, "SHOW_NEW" => "Y", "SHOW_HISTORY" => "Y"),
			false, false,
			array("ID", "IBLOCK_ID")
		);
		$arResult = $dbResult->Fetch();
		if (!$arResult)
			throw new Exception("Element is not found");

		$arDocumentFields = self::GetDocumentFields("iblock_".$arResult["IBLOCK_ID"]);

		$arKeys = array_keys($arFields);
		foreach ($arKeys as $key)
		{
			if (!array_key_exists($key, $arDocumentFields))
				continue;

			$arFields[$key] = (is_array($arFields[$key]) && !CBPHelper::IsAssociativeArray($arFields[$key])) ?
				$arFields[$key] : array($arFields[$key]);
			$realKey = ((substr($key, 0, strlen("PROPERTY_")) == "PROPERTY_") ?
				substr($key, strlen("PROPERTY_")) : $key);

			if ($arDocumentFields[$key]["Type"] == "user")
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
			elseif ($arDocumentFields[$key]["Type"] == "select")
			{
				$arV = array();
				$db = CIBlockProperty::GetPropertyEnum(
					$realKey,
					false,
					array("IBLOCK_ID" => $arResult["IBLOCK_ID"])
				);
				while ($ar = $db->GetNext())
					$arV[$ar["XML_ID"]] = $ar["ID"];

				$listValue = array();
				foreach ($arFields[$key] as &$value)
				{
					if(is_array($value) && CBPHelper::isAssociativeArray($value))
					{
						$listXmlId = array_keys($value);
						foreach($listXmlId as $xmlId)
						{
							$listValue[] = $arV[$xmlId];
						}
					}
					else
					{
						if (array_key_exists($value, $arV))
							$value = $arV[$value];
					}
				}
				if(!empty($listValue))
				{
					$arFields[$key] = $listValue;
				}
			}
			elseif ($arDocumentFields[$key]["Type"] == "file")
			{
				$files = array();
				foreach ($arFields[$key] as $value)
				{
					if (is_array($value))
					{
						foreach($value as $file)
						{
							$files[] = CFile::MakeFileArray($file);
						}
					}
					else
						$files[] = CFile::MakeFileArray($value);
				}
				$arFields[$key] = $files;
			}
			elseif($arDocumentFields[$key]["Type"] == "S:DiskFile")
			{
				foreach ($arFields[$key] as &$value)
				{
					if(!empty($value))
					{
						$value = "n".$value;
					}
				}
				$arFields[$key] = array("VALUE" => $arFields[$key], "DESCRIPTION" => "workflow");
			}
			elseif ($arDocumentFields[$key]["Type"] == "S:HTML")
			{
				foreach ($arFields[$key] as &$value)
					$value = array("VALUE" => $value);
			}

			unset($value);

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
				$arFieldsPropertyValues[$realKey] = (is_array($arFields[$key]) &&
					!CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);
				if(empty($arFieldsPropertyValues[$realKey]))
					$arFieldsPropertyValues[$realKey] = array(null);
				unset($arFields[$key]);
			}
		}

		if (count($arFieldsPropertyValues) > 0)
			$arFields["PROPERTY_VALUES"] = $arFieldsPropertyValues;

		$iblockElement = new CIBlockElement();
		if (count($arFields["PROPERTY_VALUES"]) > 0)
			$iblockElement->SetPropertyValuesEx($documentId, $arResult["IBLOCK_ID"], $arFields["PROPERTY_VALUES"]);

		UnSet($arFields["PROPERTY_VALUES"]);
		$res = $iblockElement->Update($documentId, $arFields, false, true, true);
		if (!$res)
			throw new Exception($iblockElement->LAST_ERROR);
	}

	static public function LockDocument($documentId, $workflowId)
	{
		global $DB;
		$strSql = "
			SELECT * FROM b_iblock_element_lock
			WHERE IBLOCK_ELEMENT_ID = ".intval($documentId)."
			AND LOCKED_BY = '".$DB->ForSQL($workflowId, 32)."'
		";
		$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
		if($z->Fetch())
		{
			//Success lock because documentId already locked by workflowId
			return true;
		}
		else
		{
			$strSql = "
				INSERT INTO b_iblock_element_lock (IBLOCK_ELEMENT_ID, DATE_LOCK, LOCKED_BY)
				SELECT E.ID, ".$DB->GetNowFunction().", '".$DB->ForSQL($workflowId, 32)."'
				FROM b_iblock_element E
				LEFT JOIN b_iblock_element_lock EL on EL.IBLOCK_ELEMENT_ID = E.ID
				WHERE ID = ".intval($documentId)."
				AND EL.IBLOCK_ELEMENT_ID IS NULL
			";
			$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
			return $z->AffectedRowsCount() > 0;
		}
	}

	static public function UnlockDocument($documentId, $workflowId)
	{
		global $DB;

		$strSql = "
			SELECT * FROM b_iblock_element_lock
			WHERE IBLOCK_ELEMENT_ID = ".intval($documentId)."
		";
		$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
		if($z->Fetch())
		{
			$strSql = "
				DELETE FROM b_iblock_element_lock
				WHERE IBLOCK_ELEMENT_ID = ".intval($documentId)."
				AND (LOCKED_BY = '".$DB->ForSQL($workflowId, 32)."' OR '".$DB->ForSQL($workflowId, 32)."' = '')
			";
			$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
			$result = $z->AffectedRowsCount();
		}
		else
		{//Success unlock when there is no locks at all
			$result = 1;
		}

		if ($result > 0)
		{
			foreach (GetModuleEvents("iblock", "CIBlockDocument_OnUnlockDocument", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array(array("iblock", "CIBlockDocument", $documentId)));
			}
		}

		return $result > 0;
	}

	static public function IsDocumentLocked($documentId, $workflowId)
	{
		global $DB;
		$strSql = "
			SELECT * FROM b_iblock_element_lock
			WHERE IBLOCK_ELEMENT_ID = ".intval($documentId)."
			AND LOCKED_BY <> '".$DB->ForSQL($workflowId, 32)."'
		";
		$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
		if($z->Fetch())
			return true;
		else
			return false;
	}

	public static function CanUserOperateDocument($operation, $userId, $documentId, $arParameters = array())
	{
		$documentId = trim($documentId);
		if (strlen($documentId) <= 0)
			return false;

		if (!array_key_exists("IBlockId", $arParameters)
			&& (
				!array_key_exists("IBlockPermission", $arParameters)
				|| !array_key_exists("DocumentStates", $arParameters)
				|| !array_key_exists("IBlockRightsMode", $arParameters)
				|| array_key_exists("IBlockRightsMode", $arParameters) && ($arParameters["IBlockRightsMode"] === "E")
			)
			|| !array_key_exists("CreatedBy", $arParameters) && !array_key_exists("AllUserGroups", $arParameters))
		{
			$dbElementList = CIBlockElement::GetList(
				array(),
				array("ID" => $documentId, "SHOW_NEW" => "Y", "SHOW_HISTORY" => "Y"),
				false,
				false,
				array("ID", "IBLOCK_ID", "CREATED_BY")
			);
			$arElement = $dbElementList->Fetch();

			if (!$arElement)
				return false;

			$arParameters["IBlockId"] = $arElement["IBLOCK_ID"];
			$arParameters["CreatedBy"] = $arElement["CREATED_BY"];
		}

		if (!array_key_exists("IBlockRightsMode", $arParameters))
			$arParameters["IBlockRightsMode"] = CIBlock::GetArrayByID($arParameters["IBlockId"], "RIGHTS_MODE");

		if ($arParameters["IBlockRightsMode"] === "E")
		{
			if ($operation === CBPCanUserOperateOperation::ReadDocument)
				return CIBlockElementRights::UserHasRightTo($arParameters["IBlockId"], $documentId, "element_read");
			elseif ($operation === CBPCanUserOperateOperation::WriteDocument)
				return CIBlockElementRights::UserHasRightTo($arParameters["IBlockId"], $documentId, "element_edit");
			elseif (
				$operation === CBPCanUserOperateOperation::StartWorkflow
				|| $operation === CBPCanUserOperateOperation::ViewWorkflow
			)
			{
				if (CIBlockElementRights::UserHasRightTo($arParameters["IBlockId"], $documentId, "element_edit"))
					return true;

				if (!array_key_exists("WorkflowId", $arParameters))
					return false;

				if (!CIBlockElementRights::UserHasRightTo($arParameters["IBlockId"], $documentId, "element_read"))
					return false;

				$userId = intval($userId);
				if (!array_key_exists("AllUserGroups", $arParameters))
				{
					if (!array_key_exists("UserGroups", $arParameters))
						$arParameters["UserGroups"] = CUser::GetUserGroup($userId);

					$arParameters["AllUserGroups"] = $arParameters["UserGroups"];
					if ($userId == $arParameters["CreatedBy"])
						$arParameters["AllUserGroups"][] = "Author";
				}

				if (!array_key_exists("DocumentStates", $arParameters))
				{
					if ($operation === CBPCanUserOperateOperation::StartWorkflow)
						$arParameters["DocumentStates"] = CBPWorkflowTemplateLoader::GetDocumentTypeStates(array("iblock", "CIBlockDocument", "iblock_".$arParameters["IBlockId"]));
					else
						$arParameters["DocumentStates"] = CBPDocument::GetDocumentStates(
							array("iblock", "CIBlockDocument", "iblock_".$arParameters["IBlockId"]),
							array("iblock", "CIBlockDocument", $documentId)
						);
				}

				if (array_key_exists($arParameters["WorkflowId"], $arParameters["DocumentStates"]))
					$arParameters["DocumentStates"] = array($arParameters["WorkflowId"] => $arParameters["DocumentStates"][$arParameters["WorkflowId"]]);
				else
					return false;

				$arAllowableOperations = CBPDocument::GetAllowableOperations(
					$userId,
					$arParameters["AllUserGroups"],
					$arParameters["DocumentStates"],
					true
				);

				if (!is_array($arAllowableOperations))
					return false;

				if (($operation === CBPCanUserOperateOperation::ViewWorkflow) && in_array("read", $arAllowableOperations)
					|| ($operation === CBPCanUserOperateOperation::StartWorkflow) && in_array("write", $arAllowableOperations))
					return true;

				$chop = ($operation === CBPCanUserOperateOperation::ViewWorkflow) ? "element_read" : "element_edit";

				foreach ($arAllowableOperations as $op)
				{
					$ar = CTask::GetOperations($op, true);
					if (in_array($chop, $ar))
						return true;
				}
			}
			elseif (
				$operation === CBPCanUserOperateOperation::CreateWorkflow
			)
			{
				return CBPDocument::CanUserOperateDocumentType(
					CBPCanUserOperateOperation::CreateWorkflow,
					$userId,
					array("iblock", "CIBlockDocument", $documentId),
					$arParameters
				);
			}

			return false;
		}

		if (!array_key_exists("IBlockPermission", $arParameters))
		{
			if (CModule::IncludeModule('lists'))
				$arParameters["IBlockPermission"] = CLists::GetIBlockPermission($arParameters["IBlockId"], $userId);
			else
				$arParameters["IBlockPermission"] = CIBlock::GetPermission($arParameters["IBlockId"], $userId);
		}

		if ($arParameters["IBlockPermission"] <= "R")
			return false;
		elseif ($arParameters["IBlockPermission"] >= "W")
			return true;

		$userId = intval($userId);
		if (!array_key_exists("AllUserGroups", $arParameters))
		{
			if (!array_key_exists("UserGroups", $arParameters))
				$arParameters["UserGroups"] = CUser::GetUserGroup($userId);

			$arParameters["AllUserGroups"] = $arParameters["UserGroups"];
			if ($userId == $arParameters["CreatedBy"])
				$arParameters["AllUserGroups"][] = "Author";
		}

		if (!array_key_exists("DocumentStates", $arParameters))
		{
			$arParameters["DocumentStates"] = CBPDocument::GetDocumentStates(
				array("iblock", "CIBlockDocument", "iblock_".$arParameters["IBlockId"]),
				array("iblock", "CIBlockDocument", $documentId)
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

		if (!is_array($arAllowableOperations))
			return false;

		$r = false;
		switch ($operation)
		{
			case CBPCanUserOperateOperation::ViewWorkflow:
				$r = in_array("read", $arAllowableOperations);
				break;
			case CBPCanUserOperateOperation::StartWorkflow:
				$r = in_array("write", $arAllowableOperations);
				break;
			case CBPCanUserOperateOperation::CreateWorkflow:
				$r = false;
				break;
			case CBPCanUserOperateOperation::WriteDocument:
				$r = in_array("write", $arAllowableOperations);
				break;
			case CBPCanUserOperateOperation::ReadDocument:
				$r = in_array("read", $arAllowableOperations) || in_array("write", $arAllowableOperations);
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

		$arParameters["IBlockId"] = intval(substr($documentType, strlen("iblock_")));
		$arParameters['sectionId'] = !empty($arParameters['sectionId']) ? (int)$arParameters['sectionId'] : 0;

		if (!array_key_exists("IBlockRightsMode", $arParameters))
			$arParameters["IBlockRightsMode"] = CIBlock::GetArrayByID($arParameters["IBlockId"], "RIGHTS_MODE");

		if ($arParameters["IBlockRightsMode"] === "E")
		{
			if ($operation === CBPCanUserOperateOperation::CreateWorkflow)
				return CIBlockRights::UserHasRightTo($arParameters["IBlockId"], $arParameters["IBlockId"], "iblock_rights_edit");
			elseif ($operation === CBPCanUserOperateOperation::WriteDocument)
				return CIBlockSectionRights::UserHasRightTo($arParameters["IBlockId"], $arParameters["sectionId"], "section_element_bind");
			elseif ($operation === CBPCanUserOperateOperation::ViewWorkflow
				|| $operation === CBPCanUserOperateOperation::StartWorkflow)
			{
				if (!array_key_exists("WorkflowId", $arParameters))
					return false;

				if ($operation === CBPCanUserOperateOperation::ViewWorkflow)
					return CIBlockRights::UserHasRightTo($arParameters["IBlockId"], 0, "element_read");

				if ($operation === CBPCanUserOperateOperation::StartWorkflow)
					return CIBlockSectionRights::UserHasRightTo($arParameters["IBlockId"], $arParameters['sectionId'], "section_element_bind");

				$userId = intval($userId);
				if (!array_key_exists("AllUserGroups", $arParameters))
				{
					if (!array_key_exists("UserGroups", $arParameters))
						$arParameters["UserGroups"] = CUser::GetUserGroup($userId);

					$arParameters["AllUserGroups"] = $arParameters["UserGroups"];
					$arParameters["AllUserGroups"][] = "Author";
				}

				if (!array_key_exists("DocumentStates", $arParameters))
				{
					if ($operation === CBPCanUserOperateOperation::StartWorkflow)
						$arParameters["DocumentStates"] = CBPWorkflowTemplateLoader::GetDocumentTypeStates(array("iblock", "CIBlockDocument", "iblock_".$arParameters["IBlockId"]));
					else
						$arParameters["DocumentStates"] = CBPDocument::GetDocumentStates(
							array("iblock", "CIBlockDocument", "iblock_".$arParameters["IBlockId"]),
							null
						);
				}

				if (array_key_exists($arParameters["WorkflowId"], $arParameters["DocumentStates"]))
					$arParameters["DocumentStates"] = array($arParameters["WorkflowId"] => $arParameters["DocumentStates"][$arParameters["WorkflowId"]]);
				else
					return false;

				$arAllowableOperations = CBPDocument::GetAllowableOperations(
					$userId,
					$arParameters["AllUserGroups"],
					$arParameters["DocumentStates"],
					true
				);

				if (!is_array($arAllowableOperations))
					return false;

				if (($operation === CBPCanUserOperateOperation::ViewWorkflow) && in_array("read", $arAllowableOperations)
					|| ($operation === CBPCanUserOperateOperation::StartWorkflow) && in_array("write", $arAllowableOperations))
					return true;

				$chop = ($operation === CBPCanUserOperateOperation::ViewWorkflow) ? "element_read" : "section_element_bind";

				foreach ($arAllowableOperations as $op)
				{
					$ar = CTask::GetOperations($op, true);
					if (in_array($chop, $ar))
						return true;
				}
			}

			return false;
		}

		if (!array_key_exists("IBlockPermission", $arParameters))
		{
			if(CModule::IncludeModule('lists'))
				$arParameters["IBlockPermission"] = CLists::GetIBlockPermission($arParameters["IBlockId"], $userId);
			else
				$arParameters["IBlockPermission"] = CIBlock::GetPermission($arParameters["IBlockId"], $userId);
		}

		if ($arParameters["IBlockPermission"] <= "R")
			return false;
		elseif ($arParameters["IBlockPermission"] >= "W")
			return true;

		$userId = intval($userId);
		if (!array_key_exists("AllUserGroups", $arParameters))
		{
			if (!array_key_exists("UserGroups", $arParameters))
				$arParameters["UserGroups"] = CUser::GetUserGroup($userId);

			$arParameters["AllUserGroups"] = $arParameters["UserGroups"];
			$arParameters["AllUserGroups"][] = "Author";
		}

		if (!array_key_exists("DocumentStates", $arParameters))
		{
			$arParameters["DocumentStates"] = CBPDocument::GetDocumentStates(
				array("iblock", "CIBlockDocument", "iblock_".$arParameters["IBlockId"]),
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

		if (!is_array($arAllowableOperations))
			return false;

		$r = false;
		switch ($operation)
		{
			case CBPCanUserOperateOperation::ViewWorkflow:
				$r = in_array("read", $arAllowableOperations);
				break;
			case CBPCanUserOperateOperation::StartWorkflow:
				$r = in_array("write", $arAllowableOperations);
				break;
			case CBPCanUserOperateOperation::CreateWorkflow:
				$r = in_array("write", $arAllowableOperations);
				break;
			case CBPCanUserOperateOperation::WriteDocument:
				$r = in_array("write", $arAllowableOperations);
				break;
			case CBPCanUserOperateOperation::ReadDocument:
				$r = false;
				break;
			default:
				$r = false;
		}

		return $r;
	}

	/**
	* Метод создает новый документ с указанными свойствами (полями).
	*
	* @param array $arFields - массив значений свойств документа в виде array(код_свойства => значение, ...). Коды свойств соответствуют кодам свойств, возвращаемым методом GetDocumentFields.
	* @return int - код созданного документа.
	*/
	static public function CreateDocument($parentDocumentId, $arFields)
	{
		if (!array_key_exists("IBLOCK_ID", $arFields) || intval($arFields["IBLOCK_ID"]) <= 0)
			throw new Exception("IBlock ID is not found");

		$arFieldsPropertyValues = array();

		$arDocumentFields = self::GetDocumentFields("iblock_".$arFields["IBLOCK_ID"]);

		$arKeys = array_keys($arFields);
		foreach ($arKeys as $key)
		{
			if (!array_key_exists($key, $arDocumentFields))
				continue;

			$arFields[$key] = (is_array($arFields[$key]) &&
				!CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);
			$realKey = ((substr($key, 0, strlen("PROPERTY_")) == "PROPERTY_") ? substr($key, strlen("PROPERTY_")) : $key);

			if ($arDocumentFields[$key]["Type"] == "user")
			{
				$ar = array();
				$arFields[$key] = CBPHelper::MakeArrayFlat($arFields[$key]);
				foreach ($arFields[$key] as $v1)
				{
					if (substr($v1, 0, strlen("user_")) == "user_")
					{
						$ar[] = substr($v1, strlen("user_"));
					}
					else
					{
						$a1 = self::GetUsersFromUserGroup($v1, $parentDocumentId);
						foreach ($a1 as $a11)
							$ar[] = $a11;
					}
				}

				$arFields[$key] = $ar;
			}
			elseif ($arDocumentFields[$key]["Type"] == "select")
			{
				$arV = array();
				$db = CIBlockProperty::GetPropertyEnum($realKey, false, array("IBLOCK_ID" => $arFields["IBLOCK_ID"]));
				while ($ar = $db->GetNext())
					$arV[$ar["XML_ID"]] = $ar["ID"];

				$listValue = array();
				foreach ($arFields[$key] as &$value)
				{
					if(CBPHelper::isAssociativeArray($value))
					{
						$listXmlId = array_keys($value);
						foreach($listXmlId as $xmlId)
						{
							$listValue[] = $arV[$xmlId];
						}
					}
					else
					{
						if (array_key_exists($value, $arV))
							$value = $arV[$value];
					}
				}
				if(!empty($listValue))
				{
					$arFields[$key] = $listValue;
				}
			}
			elseif ($arDocumentFields[$key]["Type"] == "file")
			{
				$files = array();
				foreach ($arFields[$key] as $value)
				{
					if (is_array($value))
					{
						foreach($value as $file)
						{
							$files[] = CFile::MakeFileArray($file);
						}
					}
					else
						$files[] = CFile::MakeFileArray($value);
				}
				$arFields[$key] = $files;
			}
			elseif ($arDocumentFields[$key]["Type"] == "S:DiskFile")
			{
				foreach ($arFields[$key] as &$value)
				{
					if(!empty($value))
					{
						$value = "n".$value;
					}
				}
				$arFields[$key] = array("VALUE" => $arFields[$key], "DESCRIPTION" => "workflow");
			}
			elseif ($arDocumentFields[$key]["Type"] == "S:HTML")
			{
				foreach ($arFields[$key] as &$value)
					$value = array("VALUE" => $value);
			}

			unset($value);

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
				$arFieldsPropertyValues[$realKey] = (is_array($arFields[$key]) &&
					!CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);
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

	/**
	* Метод удаляет указанный документ.
	*
	* @param string $documentId - код документа.
	*/
	static public function DeleteDocument($documentId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		CIBlockElement::Delete($documentId);
	}

	/**
	* Метод публикует документ. То есть делает его доступным в публичной части сайта.
	*
	* @param string $documentId - код документа.
	*/
	static public function PublishDocument($documentId)
	{
		global $DB;
		$ID = intval($documentId);

		$db_element = CIBlockElement::GetList(array(), array("ID"=>$ID, "SHOW_HISTORY"=>"Y"), false, false,
			array(
				"ID",
				"TIMESTAMP_X",
				"MODIFIED_BY",
				"DATE_CREATE",
				"CREATED_BY",
				"IBLOCK_ID",
				"ACTIVE",
				"ACTIVE_FROM",
				"ACTIVE_TO",
				"SORT",
				"NAME",
				"PREVIEW_PICTURE",
				"PREVIEW_TEXT",
				"PREVIEW_TEXT_TYPE",
				"DETAIL_PICTURE",
				"DETAIL_TEXT",
				"DETAIL_TEXT_TYPE",
				"WF_STATUS_ID",
				"WF_PARENT_ELEMENT_ID",
				"WF_NEW",
				"WF_COMMENTS",
				"IN_SECTIONS",
				"CODE",
				"TAGS",
				"XML_ID",
				"TMP_ID",
			)
		);
		if($ar_element = $db_element->Fetch())
		{
			$PARENT_ID = intval($ar_element["WF_PARENT_ELEMENT_ID"]);
			if($PARENT_ID)
			{
				// TODO: Если в документе $documentId поле WF_PARENT_ELEMENT_ID не NULL, то при публикации нужно перенести данные
				// (скопировать документ) из документа $documentId в документ WF_PARENT_ELEMENT_ID,
				$obElement = new CIBlockElement;
				$ar_element["WF_PARENT_ELEMENT_ID"] = false;

				if($ar_element["PREVIEW_PICTURE"])
					$ar_element["PREVIEW_PICTURE"] = CFile::MakeFileArray($ar_element["PREVIEW_PICTURE"]);
				else
					$ar_element["PREVIEW_PICTURE"] = array("tmp_name" => "", "del" => "Y");

				if($ar_element["DETAIL_PICTURE"])
					$ar_element["DETAIL_PICTURE"] = CFile::MakeFileArray($ar_element["DETAIL_PICTURE"]);
				else
					$ar_element["DETAIL_PICTURE"] = array("tmp_name" => "", "del" => "Y");

				$ar_element["IBLOCK_SECTION"] = array();
				if($ar_element["IN_SECTIONS"] == "Y")
				{
					$rsSections = CIBlockElement::GetElementGroups($ar_element["ID"], true, array('ID', 'IBLOCK_ELEMENT_ID'));
					while($arSection = $rsSections->Fetch())
						$ar_element["IBLOCK_SECTION"][] = $arSection["ID"];
				}

				$ar_element["PROPERTY_VALUES"] = array();
				$arProps = &$ar_element["PROPERTY_VALUES"];

				//Delete old files
				$rsProps = CIBlockElement::GetProperty($ar_element["IBLOCK_ID"], $PARENT_ID, array("value_id" => "asc"), array("PROPERTY_TYPE" => "F", "EMPTY" => "N"));
				while($arProp = $rsProps->Fetch())
				{
					if(!array_key_exists($arProp["ID"], $arProps))
						$arProps[$arProp["ID"]] = array();
					$arProps[$arProp["ID"]][$arProp["PROPERTY_VALUE_ID"]] = array(
						"VALUE" => array("tmp_name" => "", "del" => "Y"),
						"DESCRIPTION" => false,
					);
				}

				//Add new proiperty values
				$rsProps = CIBlockElement::GetProperty($ar_element["IBLOCK_ID"], $ar_element["ID"], array("value_id" => "asc"));
				$i = 0;
				while($arProp = $rsProps->Fetch())
				{
					$i++;
					if(!array_key_exists($arProp["ID"], $arProps))
						$arProps[$arProp["ID"]] = array();

					if($arProp["PROPERTY_VALUE_ID"])
					{
						if($arProp["PROPERTY_TYPE"] == "F")
							$arProps[$arProp["ID"]]["n".$i] = array(
								"VALUE" => CFile::MakeFileArray($arProp["VALUE"]),
								"DESCRIPTION" => $arProp["DESCRIPTION"],
							);
						else
							$arProps[$arProp["ID"]]["n".$i] = array(
								"VALUE" => $arProp["VALUE"],
								"DESCRIPTION" => $arProp["DESCRIPTION"],
							);
					}
				}

				$obElement->Update($PARENT_ID, $ar_element);
				// вызвать CBPDocument::MergeDocuments(WF_PARENT_ELEMENT_ID, $documentId) для переноса состояний и истории БП,
				CBPDocument::MergeDocuments(
					array("iblock", "CIBlockDocument", $PARENT_ID),
					array("iblock", "CIBlockDocument", $documentId)
				);
				// грохнуть документ $documentId,
				CIBlockElement::Delete($ID);
				// опубликовать документ WF_PARENT_ELEMENT_ID
				CIBlockElement::WF_CleanUpHistoryCopies($PARENT_ID, 0);
				$strSql = "update b_iblock_element set WF_STATUS_ID='1', WF_NEW=NULL WHERE ID=".$PARENT_ID." AND WF_PARENT_ELEMENT_ID IS NULL";
				$DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
				CIBlockElement::UpdateSearch($PARENT_ID);
				\Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex($ar_element["IBLOCK_ID"], $PARENT_ID);
				return $PARENT_ID;
			}
			else
			{
				// Если WF_PARENT_ELEMENT_ID равно NULL, то все как раньше.
				CIBlockElement::WF_CleanUpHistoryCopies($ID, 0);
				$strSql = "update b_iblock_element set WF_STATUS_ID='1', WF_NEW=NULL WHERE ID=".$ID." AND WF_PARENT_ELEMENT_ID IS NULL";
				$DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
				CIBlockElement::UpdateSearch($ID);
				\Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex($ar_element["IBLOCK_ID"], $ID);
				return $ID;
			}
		}
		return false;
	}

	static public function CloneElement($ID, $arFields = array())
	{
		global $DB;
		$ID = intval($ID);

		$db_element = CIBlockElement::GetList(array(), array("ID"=>$ID, "SHOW_HISTORY"=>"Y"), false, false,
			array(
				"ID",
				"MODIFIED_BY",
				"DATE_CREATE",
				"CREATED_BY",
				"IBLOCK_ID",
				"ACTIVE",
				"ACTIVE_FROM",
				"ACTIVE_TO",
				"SORT",
				"NAME",
				"PREVIEW_PICTURE",
				"PREVIEW_TEXT",
				"PREVIEW_TEXT_TYPE",
				"DETAIL_PICTURE",
				"DETAIL_TEXT",
				"DETAIL_TEXT_TYPE",
				"WF_STATUS_ID",
				"WF_PARENT_ELEMENT_ID",
				"WF_COMMENTS",
				"IN_SECTIONS",
				"CODE",
				"TAGS",
				"XML_ID",
				"TMP_ID",
			)
		);
		if($ar_element = $db_element->Fetch())
		{
			$IBLOCK_ID = $ar_element["IBLOCK_ID"];
			if($ar_element["WF_PARENT_ELEMENT_ID"] > 0)
			{
				throw new Exception(GetMessage("IBD_ELEMENT_NOT_FOUND"));
			}
			else
			{
				if($ar_element["PREVIEW_PICTURE"])
					$ar_element["PREVIEW_PICTURE"] = CFile::MakeFileArray($ar_element["PREVIEW_PICTURE"]);

				if($ar_element["DETAIL_PICTURE"])
					$ar_element["DETAIL_PICTURE"] = CFile::MakeFileArray($ar_element["DETAIL_PICTURE"]);

				$ar_element["IBLOCK_SECTION"] = array();
				if($ar_element["IN_SECTIONS"] == "Y")
				{
					$rsSections = CIBlockElement::GetElementGroups($ar_element["ID"], true, array('ID', 'IBLOCK_ELEMENT_ID'));
					while($arSection = $rsSections->Fetch())
						$ar_element["IBLOCK_SECTION"][] = $arSection["ID"];
				}

				$ar_element["PROPERTY_VALUES"] = array();

				foreach($arFields as $field_id => $value)
					if(array_key_exists($field_id, $ar_element))
						$ar_element[$field_id] = $value;

				$ar_element["WF_PARENT_ELEMENT_ID"] = $ID;
				$ar_element["IBLOCK_ID"] = $IBLOCK_ID;

				$arProps = &$ar_element["PROPERTY_VALUES"];

				//Add new proiperty values
				$rsProps = CIBlockElement::GetProperty($ar_element["IBLOCK_ID"], $ar_element["ID"], array("value_id" => "asc"));
				$i = 0;
				while($arProp = $rsProps->Fetch())
				{
					if(array_key_exists($arProp["CODE"], $ar_element["PROPERTY_VALUES"]))
						continue;

					$i++;
					if(!array_key_exists($arProp["ID"], $arProps))
						$arProps[$arProp["ID"]] = array();

					if($arProp["PROPERTY_VALUE_ID"])
					{
						if($arProp["PROPERTY_TYPE"] == "F")
							$arProps[$arProp["ID"]]["n".$i] = array(
								"VALUE" => CFile::MakeFileArray($arProp["VALUE"]),
								"DESCRIPTION" => $arProp["DESCRIPTION"],
							);
						else
							$arProps[$arProp["ID"]]["n".$i] = array(
								"VALUE" => $arProp["VALUE"],
								"DESCRIPTION" => $arProp["DESCRIPTION"],
							);
					}
				}

				if (CIBlock::GetArrayByID($IBLOCK_ID, "RIGHTS_MODE") === "E")
				{
					$ibRights = new CIBlockElementRights(intval($IBLOCK_ID), $ID);
					$arRights = $ibRights->GetRights();
					$arNewRights = array();
					$rightIndex = 0;
					foreach($arRights as $rightID=>$right)
					{
						if ($right['IS_INHERITED'] !== 'Y')
							$arNewRights['n'.$rightIndex++] = $right;
					}
					$ar_element['RIGHTS'] = $arNewRights;
				}

				$obElement = new CIBlockElement;
				$NEW_ID = $obElement->Add($ar_element);
				if(!$NEW_ID)
					throw new Exception($obElement->LAST_ERROR);
				else
					return $NEW_ID;
			}
		}
		else
		{
			throw new Exception(GetMessage("IBD_ELEMENT_NOT_FOUND"));
		}
	}

	/**
	* Метод снимает документ с публикации. То есть делает его недоступным в публичной части сайта.
	*
	* @param string $documentId - код документа.
	*/
	static public function UnpublishDocument($documentId)
	{
		global $DB;
		CIBlockElement::WF_CleanUpHistoryCopies($documentId, 0);
		$strSql = "update b_iblock_element set WF_STATUS_ID='2', WF_NEW='Y' WHERE ID=".intval($documentId)." AND WF_PARENT_ELEMENT_ID IS NULL";
		$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
		CIBlockElement::UpdateSearch($documentId);
	}

	// array("read" => "Ета чтение", "write" => "Ета запысь")
	static public function GetAllowableOperations($documentType)
	{
		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		if (CIBlock::GetArrayByID($iblockId, "RIGHTS_MODE") === "E")
		{
			$ob = new CIBlockRights($iblockId);
			return $ob->GetRightsList();
		}

		return array("read" => GetMessage("IBD_OPERATION_READ"), "write" => GetMessage("IBD_OPERATION_WRITE"));
	}

	static public function GetJSFunctionsForFields($documentType, $objectName, $arDocumentFields = array(), $arDocumentFieldTypes = array())
	{
		return "";
	}

	public static function GetFieldInputControlOptions($documentType, &$arFieldType, $jsFunctionName, &$value)
	{
		$result = "";

		static $arDocumentFieldTypes = array();
		if (!array_key_exists($documentType, $arDocumentFieldTypes))
			$arDocumentFieldTypes[$documentType] = self::GetDocumentFieldTypes($documentType);

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

			$result .= '<select id="WFSFormOptionsX" onchange="'.htmlspecialcharsbx($jsFunctionName).'(this.options[this.selectedIndex].value)">';
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
		elseif ($arFieldType["Type"] == "select")
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

	static public function GetAllowableUserGroups($documentType, $withExtended = false)
	{
		$documentType = trim($documentType);
		if (strlen($documentType) <= 0)
			return false;

		$iblockId = intval(substr($documentType, strlen("iblock_")));

		$result = array('Author' => GetMessage('IBD_DOCUMENT_AUTHOR'));

		$groupsId = array(1);
		$extendedGroupsCode = array();
		if(CIBlock::GetArrayByID($iblockId, "RIGHTS_MODE") === "E")
		{
			$obRights = new CIBlockRights($iblockId);
			foreach($obRights->GetGroups(/*"element_bizproc_start"*/) as $GROUP_CODE)
				if(preg_match("/^G(\\d+)\$/", $GROUP_CODE, $match))
					$groupsId[] = $match[1];
				else
					$extendedGroupsCode[] = $GROUP_CODE;
		}
		else
		{
			foreach(CIBlock::GetGroupPermissions($iblockId) as $groupId => $perm)
			{
				if ($perm > "R")
					$groupsId[] = $groupId;
			}
		}

		$dbGroupsList = CGroup::GetListEx(array("NAME" => "ASC"), array("ID" => $groupsId));
		while ($arGroup = $dbGroupsList->Fetch())
			$result[$arGroup["ID"]] = $arGroup["NAME"];

		if ($withExtended && $extendedGroupsCode)
		{
			foreach ($extendedGroupsCode as $groupCode)
			{
				$result['group_'.$groupCode] = CBPHelper::getExtendedGroupName($groupCode);
			}
		}

		return $result;
	}

	static public function GetUsersFromUserGroup($group, $documentId)
	{
		$group = strtolower($group);
		if ($group == 'author')
		{
			$documentId = (int)$documentId;
			if ($documentId <= 0)
				return array();

			$db = CIBlockElement::GetList(array(), array("ID" => $documentId, "SHOW_NEW" => "Y", "SHOW_HISTORY" => "Y"), false, false, array("ID", "IBLOCK_ID", "CREATED_BY"));
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

		$arFilter = array("ACTIVE" => "Y");
		if ($group != 2)
			$arFilter["GROUPS_ID"] = $group;

		$dbUsersList = CUser::GetList(($b = "ID"), ($o = "ASC"), $arFilter);
		while ($arUser = $dbUsersList->Fetch())
			$arResult[] = $arUser["ID"];
		return $arResult;
	}

	static public function SetPermissions($documentId, $workflowId, $arPermissions, $bRewrite = true)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$documentType = self::GetDocumentType($documentId);
		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		if (CIBlock::GetArrayByID($iblockId, "RIGHTS_MODE") !== "E")
			return;

		$ob = new CIBlockElementRights($iblockId, $documentId);
		$documentRights = $ob->GetRights();

		$mode = 'Hold';
		$scope = 'ScopeWorkflow';

		if (is_array($bRewrite) && class_exists('CBPSetPermissionsMode'))
		{
			if (isset($bRewrite['setMode']))
				$mode = CBPSetPermissionsMode::outMode($bRewrite['setMode']);
			if (isset($bRewrite['setScope']))
				$scope = CBPSetPermissionsMode::outScope($bRewrite['setScope']);
		}
		elseif ($bRewrite == true)
		{
			$mode = 'Clear';
		}

		$overrideCodes = array();
		if ($mode == 'Clear' || $mode == 'Rewrite')
		{
			foreach ($documentRights as $i => $arRight)
			{
				if ($scope == 'ScopeDocument' || $scope == 'ScopeWorkflow' && $arRight["XML_ID"] == $workflowId)
				{
					if ($mode == 'Clear')
						unset($documentRights[$i]);

					if ($mode == 'Rewrite')
						$overrideCodes[$arRight["GROUP_CODE"]] = $i;
				}

			}
		}

		$i = 0;
		$l = strlen("user_");
		foreach ($arPermissions as $taskId => $arUsers)
		{
			foreach ($arUsers as $user)
			{
				if (!$user)
					continue;
				$gc = null;
				if ($user == 'author')
				{
					$u = self::GetUsersFromUserGroup('author', $documentId);
					foreach ($u as $u1)
						$gc = "U".$u1;
				}
				elseif (strpos($user, 'group_') === 0)
				{
					$gc = strtoupper(substr($user, strlen('group_')));
				}
				else
				{
					$gc = ((substr($user, 0, $l) == "user_") ? "U".substr($user, $l) : "G".$user);
				}
				if ($gc != null)
				{
					$documentRights["n".$i] = array("GROUP_CODE" => $gc, "TASK_ID" => $taskId, "XML_ID" => $workflowId);
					$i++;

					if (isset($overrideCodes[$gc]))
						unset($documentRights[$overrideCodes[$gc]]);
				}
			}
		}

		$ob->SetRights($documentRights);
	}

	/**
	* Method return array with all information about document. Array used for method RecoverDocumentFromHistory.
	*
	* @param string $documentId - document id.
	* @return array - document information array.
	*/
	static public function GetDocumentForHistory($documentId, $historyIndex)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$arResult = null;

		$dbDocumentList = CIBlockElement::GetList(
			array(),
			array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y")
		);
		if ($objDocument = $dbDocumentList->GetNextElement())
		{
			$arDocumentFields = $objDocument->GetFields();
			$arDocumentProperties = $objDocument->GetProperties();

			$arResult["NAME"] = $arDocumentFields["~NAME"];

			$arResult["FIELDS"] = array();
			foreach ($arDocumentFields as $fieldKey => $fieldValue)
			{
				if ($fieldKey == "~PREVIEW_PICTURE" || $fieldKey == "~DETAIL_PICTURE")
				{
					$arResult["FIELDS"][substr($fieldKey, 1)] = CBPDocument::PrepareFileForHistory(
						array("iblock", "CIBlockDocument", $documentId),
						$fieldValue,
						$historyIndex
					);
				}
				elseif (substr($fieldKey, 0, 1) == "~")
				{
					$arResult["FIELDS"][substr($fieldKey, 1)] = $fieldValue;
				}
			}

			$arResult["PROPERTIES"] = array();
			foreach ($arDocumentProperties as $propertyKey => $propertyValue)
			{
				if (strlen($propertyValue["USER_TYPE"]) > 0)
				{
					$arResult["PROPERTIES"][$propertyKey] = array(
						"VALUE" => $propertyValue["VALUE"],
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "L")
				{
					$arResult["PROPERTIES"][$propertyKey] = array(
						"VALUE" => $propertyValue["VALUE_ENUM_ID"],
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "F")
				{
					$arResult["PROPERTIES"][$propertyKey] = array(
						"VALUE" => CBPDocument::PrepareFileForHistory(
							array("iblock", "CIBlockDocument", $documentId),
							$propertyValue["VALUE"],
							$historyIndex
						),
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
				else
				{
					$arResult["PROPERTIES"][$propertyKey] = array(
						"VALUE" => $propertyValue["VALUE"],
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
			}
		}

		return $arResult;
	}

	/**
	* Method recover document from array. Array must be created by method RecoverDocumentFromHistory.
	*
	* @param string $documentId - document id.
	* @param array $arDocument - array.
	*/
	static public function RecoverDocumentFromHistory($documentId, $arDocument)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$arFields = $arDocument["FIELDS"];
		if (strlen($arFields["PREVIEW_PICTURE"]) > 0)
			$arFields["PREVIEW_PICTURE"] = CFile::MakeFileArray($arFields["PREVIEW_PICTURE"]);
		if (strlen($arFields["DETAIL_PICTURE"]) > 0)
			$arFields["DETAIL_PICTURE"] = CFile::MakeFileArray($arFields["DETAIL_PICTURE"]);

		$arFields["PROPERTY_VALUES"] = array();

		$dbProperties = CIBlockProperty::GetList(
			array("sort" => "asc", "name" => "asc"),
			array("IBLOCK_ID" => $arFields["IBLOCK_ID"])
		);
		while ($arProperty = $dbProperties->Fetch())
		{
			if (strlen(trim($arProperty["CODE"])) > 0)
				$key = $arProperty["CODE"];
			else
				$key = $arProperty["ID"];

			if (!array_key_exists($key, $arDocument["PROPERTIES"]))
				continue;

			$documentValue = $arDocument["PROPERTIES"][$key]["VALUE"];

			if(strlen($arProperty["USER_TYPE"]) <= 0 && $arProperty["PROPERTY_TYPE"] == "F")
			{
				$arFields["PROPERTY_VALUES"][$key] = array();
				//Mark files to be deleted
				$rsFiles = CIBlockElement::GetProperty($arFields["IBLOCK_ID"], $documentId, array("ID"=>$arProperty["ID"], "EMPTY"=>"N"));
				while($arFile = $rsFiles->Fetch())
				{
					if($arFile["PROPERTY_VALUE_ID"] > 0)
						$arFields["PROPERTY_VALUES"][$key][$arFile["PROPERTY_VALUE_ID"]] = array(
							"VALUE" => array("del"=>"Y"),
							"DESCRIPTION" => "",
						);
				}
				//Restore from history
				$io = CBXVirtualIo::GetInstance();
				if(is_array($documentValue))
				{
					$n = 0;
					foreach ($documentValue as $i => $v)
						if(strlen($v) > 0)
						{
							$arFields["PROPERTY_VALUES"][$key]["n".($n++)] = array(
								"VALUE" => CFile::MakeFileArray($io->GetPhysicalName($v)),
								"DESCRIPTION" => $arDocument["PROPERTIES"][$key]["DESCRIPTION"][$i]
							);
						}
				}
				else
				{
					if(strlen($documentValue) > 0)
					{
						$arFields["PROPERTY_VALUES"][$key]["n0"] = array(
							"VALUE" => CFile::MakeFileArray($io->GetPhysicalName($documentValue)),
							"DESCRIPTION" => $arDocument["PROPERTIES"][$key]["DESCRIPTION"]
						);
					}
				}
			}
			else
			{
				if(is_array($documentValue))
				{
					$n = 0;
					foreach ($documentValue as $i => $v)
						if(strlen($v) > 0)
							$arFields["PROPERTY_VALUES"][$key]["n".($n++)] = array(
								"VALUE" => $v,
								"DESCRIPTION" => $arDocument["PROPERTIES"][$key]["DESCRIPTION"][$i]
							);
				}
				else
				{
					if(strlen($documentValue) > 0)
						$arFields["PROPERTY_VALUES"][$key]["n0"] = array(
							"VALUE" => $documentValue,
							"DESCRIPTION" => $arDocument["PROPERTIES"][$key]["DESCRIPTION"]
						);
				}
			}
		}

		$iblockElement = new CIBlockElement();
		$res = $iblockElement->Update($documentId, $arFields);
		if (intVal($arFields["WF_STATUS_ID"]) > 1 && intVal($arFields["WF_PARENT_ELEMENT_ID"]) <= 0)
			self::UnpublishDocument($documentId);
		if (!$res)
			throw new Exception($iblockElement->LAST_ERROR);

		return true;
	}

	public static function isExtendedPermsSupported($documentType)
	{
		$iblockId = (int)substr($documentType, strlen("iblock_"));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		return CIBlock::GetArrayByID($iblockId, "RIGHTS_MODE");
	}

	public static function generatePropertyCode($name, $code, $iblockId, $propertyId = 0)
	{
		if(empty($code))
		{
			$code = CUtil::translit($name, LANGUAGE_ID, array("change_case" => "U"));
		}

		$object = CIBlockProperty::getList(array(), array("IBLOCK_ID" => $iblockId));
		while($property = $object->fetch())
		{
			if($property["CODE"] == $code && $property["ID"] != $propertyId)
			{
				$code = $code.'_'.self::generateMnemonicCode();
				break;
			}
		}

		return $code;
	}
}
?>
