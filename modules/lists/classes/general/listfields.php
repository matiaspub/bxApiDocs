<?
IncludeModuleLangFile(__FILE__);

class CListFieldList
{
	protected $iblock_id = 0;
	protected $form_id = "";
	/** @var array[string]CListField  */
	protected $fields = array();

	public function __construct($iblock_id)
	{
		if($iblock_id > 0)
		{
			$this->iblock_id = intval($iblock_id);
			$this->form_id = "form_element_".$this->iblock_id;
			$this->fields = $this->_read_form_settings($this->form_id);
		}
		else
		{
			$this->iblock_id = 0;
			$this->form_id = "";
			$this->fields = array();
		}

		if(!count($this->fields) || !isset($this->fields["NAME"]))
		{
			$this->fields["NAME"] = new CListElementField($this->iblock_id, "NAME", GetMessage("LISTS_LIST_NAME_FIELD_DEFAULT_LABEL"), (count($this->fields)+1)*10);
		}
	}

	/**
	 * @param $field_id string
	 * @return CListField|null
	 */
	public function GetByID($field_id)
	{
		if(isset($this->fields[$field_id]))
			return $this->fields[$field_id];
		else
			return null;
	}

	public function GetFields()
	{
		return array_keys($this->fields);
	}

	public function GetArrayByID($field_id)
	{
		if(isset($this->fields[$field_id]))
		{
			/** @var CListField $obField */
			$obField = $this->fields[$field_id];
			$result = $obField->GetArray();
		}
		else
		{
			$result = array();
		}

		$result["IBLOCK_ID"] = $this->iblock_id;

		return $result;
	}

	public function DeleteField($field_id)
	{
		if($field_id != "NAME" && isset($this->fields[$field_id]))
		{
			/** @var CListField $obField */
			$obField = $this->fields[$field_id];
			$obField->Delete();
			unset($this->fields[$field_id]);

			$this->_save_form_settings($this->form_id);
		}
		return true;
	}

	public function AddField($arFields)
	{
		$new_field_id = false;
		$newField = null;

		if(CListFieldTypeList::IsField($arFields["TYPE"]))
		{
			if(!isset($this->fields[$arFields["TYPE"]]))
			{
				$newField = CListElementField::Add($this->iblock_id, $arFields);
			}
		}
		elseif(CListFieldTypeList::IsExists($arFields["TYPE"]))
		{
				$newField = CListPropertyField::Add($this->iblock_id, $arFields);
		}

		if(is_object($newField))
		{
			if(isset($arFields["SETTINGS"]))
				$newField->SetSettings($arFields["SETTINGS"]);

			$new_field_id = $newField->GetID();
			$this->fields[$new_field_id] = $newField;

			$this->_resort();
			$this->_save_form_settings($this->form_id);
		}

		return $new_field_id;
	}

	public function UpdateField($field_id, $arFields)
	{
		$new_field_id = false;
		$newField = null;

		if(isset($this->fields[$field_id]))
		{
			/** @var CListField $obField */
			$obField = $this->fields[$field_id];

			if(isset($arFields["TYPE"]) && ($arFields["TYPE"] != $obField->GetTypeID()))
			{
				if(CListFieldTypeList::IsField($obField->GetTypeID()))
				{
					if(CListFieldTypeList::IsField($arFields["TYPE"]))
					{
						$newField = $obField->Update($arFields);
					}
					else
					{
						$obField->Delete();
						$newField = CListPropertyField::Add($this->iblock_id, $arFields);
					}
				}
				else
				{
					if(!CListFieldTypeList::IsField($arFields["TYPE"]))
					{
						$newField = $obField->Update($arFields);
					}
					else
					{
						$obField->Delete();
						$newField = CListElementField::Add($this->iblock_id, $arFields);
					}
				}
			}
			else
			{
				$newField = $obField->Update($arFields);
			}
		}

		if(is_object($newField))
		{
			if(isset($arFields["SETTINGS"]))
				$newField->SetSettings($arFields["SETTINGS"]);

			unset($this->fields[$field_id]);
			$new_field_id = $newField->GetID();
			$this->fields[$new_field_id] = $newField;

			$this->_resort();
			$this->_save_form_settings($this->form_id);
		}

		return $new_field_id;
	}

	protected function _save_form_settings($form_id)
	{
		if($form_id && $this->iblock_id)
		{
			$arFormLayout = array();
			$arFormLayout[] = "edit1--#--".CIBlock::GetArrayByID($this->iblock_id, "ELEMENT_NAME");
			foreach($this->fields as $field_id => $sort)
			{
				/** @var CListField $obField */
				$obField = $this->fields[$field_id];
				$arFormLayout[] =
						$obField->GetID()
						."--#--"
						.($obField->IsRequired()? "*": "")
						.str_replace("-", "", $obField->GetLabel())
				;
			}
			$tab1 = implode("--,--", $arFormLayout);

			$arFormLayout = array();
			$arFormLayout[] = "edit2--#--".CIBlock::GetArrayByID($this->iblock_id, "SECTION_NAME");
			$arFormLayout[] = "SECTIONS--#--".CIBlock::GetArrayByID($this->iblock_id, "SECTION_NAME");
			$tab2 = implode("--,--", $arFormLayout);

			global $USER;
			if (is_object($USER) && ((get_class($USER) === 'CUser') || ($USER instanceof CUser)))
				CUserOptions::DeleteOption("form", $form_id); //This clears custom user settings
			CUserOptions::SetOption("form", $form_id, array("tabs" => $tab1."--;--".$tab2."--;--"), true);
		}
	}

	protected function _read_form_settings($form_id)
	{
		if(!$form_id)
			return null;

		global $DB;

		//read list meta from module table
		$rsFields = $DB->Query("
			SELECT * FROM b_lists_field
			WHERE IBLOCK_ID = ".$this->iblock_id."
			ORDER BY SORT ASC
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$dbFields = array();
		while($arField = $rsFields->Fetch())
			$dbFields[$arField["FIELD_ID"]] = $arField;

		$fields = array();
		$customTabs = CUserOptions::GetOption("form", $form_id);

		//read list meta from interface settings
		if($customTabs && $customTabs["tabs"])
		{
			$sort = 10;
			$arTabs = explode("--;--", $customTabs["tabs"]);
			foreach($arTabs as $customFields)
			{
				if($customFields)
				{
					$arCustomFields = explode("--,--", $customFields);
					array_shift($arCustomFields);
					foreach($arCustomFields as $customField)
					{
						list($FIELD_ID, $customName) = explode("--#--", $customField);
						if($FIELD_ID != "SECTIONS")
						{
							$customName = ltrim($customName, "* -\xa0");

							if(CListFieldTypeList::IsField($FIELD_ID))
								$obField = $fields[$FIELD_ID] = new CListElementField($this->iblock_id, $FIELD_ID, $customName, $sort);
							else
								$obField = $fields[$FIELD_ID] = new CListPropertyField($this->iblock_id, $FIELD_ID, $customName, $sort);

							//check if property was deleted from admin interface
							if(!is_array($obField->GetArray()))
							{
								unset($fields[$FIELD_ID]);
							}
							else
							{
								$sort += 10;
								unset($dbFields[$FIELD_ID]);
							}
						}
					}
				}
			}
			//There were some fields "deleted" from interface
			foreach($dbFields as $FIELD_ID => $arField)
			{
				$DB->Query("
					DELETE FROM b_lists_field
					WHERE IBLOCK_ID = ".$this->iblock_id."
					AND FIELD_ID = '".$DB->ForSQL($FIELD_ID)."'
				", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
		else//or from module metadata
		{
			foreach($dbFields as $FIELD_ID => $arField)
			{
				if(CListFieldTypeList::IsField($FIELD_ID))
					$obField = $fields[$FIELD_ID] = new CListElementField($this->iblock_id, $FIELD_ID, $arField["NAME"], $arField["SORT"]);
				else
					$obField = $fields[$FIELD_ID] = new CListPropertyField($this->iblock_id, $FIELD_ID, $arField["NAME"], $arField["SORT"]);
				//check if property was deleted from admin interface
				if(!is_array($obField->GetArray()))
				{
					unset($fields[$FIELD_ID]);
					$DB->Query("
						DELETE FROM b_lists_field
						WHERE IBLOCK_ID = ".$this->iblock_id."
						AND FIELD_ID = '".$DB->ForSQL($FIELD_ID)."'
					", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				}
			}
		}

		return $fields;
	}

	/**
	 * @param $a CListField
	 * @param $b CListField
	 * @return int
	 */
	public static function Order($a, $b)
	{
		$a_sort = $a->GetSort();
		$b_sort = $b->GetSort();

		if($a_sort < $b_sort)
			return -1;
		elseif($a_sort > $b_sort)
			return 1;
		else
		{
			$a_name = $a->GetLabel();
			$b_name = $b->GetLabel();

			if($a_name < $b_name)
				return -1;
			elseif($a_name > $b_name)
				return 1;
			else
				return 0;
		}
	}

	protected function _resort()
	{
		uasort($this->fields, array('CListFieldList', 'Order'));
		$sort = 10;
		foreach($this->fields as $field_id => $obField)
		{
			/** @var CListField $obField */
			$obField->SetSort($sort);
			$sort += 10;
		}
	}

	static function DeleteFields($iblock_id)
	{
		global $DB;
		$iblock_id = intval($iblock_id);
		$DB->Query("
			DELETE FROM b_lists_field
			WHERE IBLOCK_ID = ".$iblock_id."
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$rsOptions = CUserOptions::GetList(array("ID" => "ASC"), array(
			"CATEGORY" => "form",
			"NAME" => "form_element_".$iblock_id,
		));
		while($arOption = $rsOptions->Fetch())
		{
			CUserOptions::DeleteOption(
				$arOption["CATEGORY"],
				$arOption["NAME"],
				$arOption["COMMON"] == "Y",
				$arOption["USER_ID"]
			);
		}
	}
}

?>