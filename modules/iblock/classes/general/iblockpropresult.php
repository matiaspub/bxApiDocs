<?
class CIBlockPropertyResult extends CDBResult
{
	protected $IBLOCK_ID = 0;
	protected $VERSION = 0;
	protected $arProperties = array();
	protected $arPropertiesValues = array();
	protected $lastRes = null;

	public function Fetch()
	{
		global $DB;

		if (isset($this->lastRes))
		{
			$res = $this->lastRes;
			$this->lastRes = null;
		}
		else
		{
			$res = parent::Fetch();
		}

		if($res && $res["USER_TYPE"]!="")
		{
			$arUserType = CIBlockProperty::GetUserType($res["USER_TYPE"]);
			if(array_key_exists("ConvertFromDB", $arUserType))
			{
				if(array_key_exists("VALUE", $res))
				{
					$value = array("VALUE"=>$res["VALUE"],"DESCRIPTION"=>"");
					$value = call_user_func_array($arUserType["ConvertFromDB"],array($res,$value));
					$res["VALUE"] = $value["VALUE"];
				}

				if(array_key_exists("DEFAULT_VALUE", $res))
				{
					$value = array("VALUE"=>$res["DEFAULT_VALUE"],"DESCRIPTION"=>"");
					$value = call_user_func_array($arUserType["ConvertFromDB"],array($res,$value));
					$res["DEFAULT_VALUE"] = $value["VALUE"];
				}
			}
			if(strlen($res["USER_TYPE_SETTINGS"]))
				$res["USER_TYPE_SETTINGS"] = unserialize($res["USER_TYPE_SETTINGS"]);
		}

		if($res && !empty($this->arProperties))
		{
			$this->initPropertiesValues($res["IBLOCK_ELEMENT_ID"]);
			if ($this->VERSION == 2)
			{
				$arUpdate = array();
				foreach ($this->arProperties as $arProp)
				{
					$field_name = "PROPERTY_".$arProp["ID"];
					if ($arProp["MULTIPLE"] == "Y")
					{
						$descr_name = "DESCRIPTION_".$arProp["ID"];

						if(is_object($res[$field_name]))
							$res[$field_name] = $res[$field_name]->load();

						if(strlen($res[$field_name]) <= 0)
						{
							$strSql = "
								SELECT VALUE, DESCRIPTION
								FROM b_iblock_element_prop_m".$arProp["IBLOCK_ID"]."
								WHERE
									IBLOCK_ELEMENT_ID = ".intval($res["IBLOCK_ELEMENT_ID"])."
									AND IBLOCK_PROPERTY_ID = ".intval($arProp["ID"])."
								ORDER BY ID
							";
							$rs = $DB->Query($strSql);
							$res[$field_name] = array();
							$res[$descr_name] = array();
							while($ar=$rs->Fetch())
							{
								$res[$field_name][]=$ar["VALUE"];
								$res[$descr_name][]=$ar["DESCRIPTION"];
							}
							$arUpdate["b_iblock_element_prop_s".$arProp["IBLOCK_ID"]]["PROPERTY_".$arProp["ID"]] = serialize(array("VALUE"=>$res[$field_name],"DESCRIPTION"=>$res[$descr_name]));
						}
						else
						{
							$tmp = unserialize($res[$field_name]);
							$res[$field_name] = $tmp["VALUE"];
							$res[$descr_name] = $tmp["DESCRIPTION"];
						}

						foreach($res[$field_name] as $VALUE)
							$this->addPropertyValue($arProp["ID"], $VALUE);
					}
					else
					{
						if ($res[$field_name] != "")
							$this->addPropertyValue($arProp["ID"], $res[$field_name]);
					}
				}
				foreach($arUpdate as $strTable=>$arFields)
				{
					$strUpdate = $DB->PrepareUpdate($strTable, $arFields);
					if($strUpdate!="")
					{
						$strSql = "UPDATE ".$strTable." SET ".$strUpdate." WHERE IBLOCK_ELEMENT_ID = ".intval($res["ID"]);
						$DB->QueryBind($strSql, $arFields);
					}
				}
				$res = $this->arPropertiesValues;
			}
			else
			{
				do {
					if ($this->arProperties[$res["IBLOCK_PROPERTY_ID"]]["PROPERTY_TYPE"] == "N")
						$this->addPropertyValue($res["IBLOCK_PROPERTY_ID"], $res["VALUE_NUM"]);
					else
						$this->addPropertyValue($res["IBLOCK_PROPERTY_ID"], $res["VALUE"]);
					$res = parent::Fetch();
				} while ($res && ($res["IBLOCK_ELEMENT_ID"] == $this->arPropertiesValues["IBLOCK_ELEMENT_ID"]));

				$this->lastRes = $res;
				$res = $this->arPropertiesValues;
				$this->arPropertiesValues = array();
			}
		}

		return $res;
	}

	private function addPropertyValue($IBLOCK_PROPERTY_ID, $VALUE)
	{
		if (isset($this->arProperties[$IBLOCK_PROPERTY_ID]))
		{
			if ($this->arProperties[$IBLOCK_PROPERTY_ID]["MULTIPLE"] == "Y")
				$this->arPropertiesValues[$IBLOCK_PROPERTY_ID][] = $VALUE;
			else
				$this->arPropertiesValues[$IBLOCK_PROPERTY_ID] = $VALUE;
		}
	}

	private function initPropertiesValues($IBLOCK_ELEMENT_ID)
	{
		$this->arPropertiesValues["IBLOCK_ELEMENT_ID"] = $IBLOCK_ELEMENT_ID;
		foreach ($this->arProperties as $arProp)
		{
			if ($arProp["MULTIPLE"] == "Y")
				$this->arPropertiesValues[$arProp["ID"]] = array();
			else
				$this->arPropertiesValues[$arProp["ID"]] = false;
		}
	}

	public function setIBlock($IBLOCK_ID)
	{
		$this->VERSION = CIBlockElement::GetIBVersion($IBLOCK_ID);

		$this->arProperties = array();
		$rsProperty = CIBlockProperty::GetList(array("ID" => "ASC"), array("IBLOCK_ID" => $IBLOCK_ID));
		while($arProp = $rsProperty->Fetch())
			$this->arProperties[$arProp["ID"]] = $arProp;
	}
}
?>