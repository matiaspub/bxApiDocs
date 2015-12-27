<?
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Type\Collection;

class CIBlockPropertyResult extends CDBResult
{
	protected $IBLOCK_ID = 0;
	protected $VERSION = 0;
	protected $arProperties = array();
	protected static $propertiesCache = array();
	protected $arPropertiesValues = array();
	protected $lastRes = null;
	protected $extMode = false;
	protected $arPropertyValuesID = array();
	protected $arDescriptions = array();

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

		if ($res && $res["USER_TYPE"]!="")
		{
			$arUserType = CIBlockProperty::GetUserType($res["USER_TYPE"]);
			if (isset($arUserType["ConvertFromDB"]))
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
						$value_id_name = "PROPERTY_VALUE_ID_".$arProp["ID"];

						if(is_object($res[$field_name]))
							$res[$field_name] = $res[$field_name]->load();

						$update = false;
						if(strlen($res[$field_name]) <= 0)
						{
							$update = true;
						}
						else
						{
							$tmp = unserialize($res[$field_name]);
							if (!isset($tmp['ID']))
								$update = true;
						}
						if ($update)
						{
							$strSql = "
								SELECT ID, VALUE, DESCRIPTION
								FROM b_iblock_element_prop_m".$arProp["IBLOCK_ID"]."
								WHERE
									IBLOCK_ELEMENT_ID = ".(int)$res["IBLOCK_ELEMENT_ID"]."
									AND IBLOCK_PROPERTY_ID = ".(int)$arProp["ID"]."
								ORDER BY ID
							";
							$rs = $DB->Query($strSql);
							$res[$field_name] = array();
							$res[$descr_name] = array();
							$res[$value_id_name] = array();
							while($ar=$rs->Fetch())
							{
								$res[$field_name][] = $ar["VALUE"];
								$res[$descr_name][] = $ar["DESCRIPTION"];
								$res[$value_id_name][] = $ar['ID'];
							}
							$arUpdate["b_iblock_element_prop_s".$arProp["IBLOCK_ID"]]["PROPERTY_".$arProp["ID"]] = serialize(array("VALUE"=>$res[$field_name],"DESCRIPTION"=>$res[$descr_name],"ID"=>$res[$value_id_name]));
						}
						else
						{
							$res[$field_name] = $tmp["VALUE"];
							$res[$descr_name] = $tmp["DESCRIPTION"];
							$res[$value_id_name] = $tmp["ID"];
						}

						if ($this->extMode)
						{
							foreach ($res[$field_name] as $field_key => $VALUE)
							{
								$this->addPropertyValue($arProp["ID"], $VALUE);
								$this->addPropertyData($arProp["ID"], $res[$value_id_name][$field_key], $res[$descr_name][$field_key]);
							}
						}
						else
						{
							foreach($res[$field_name] as $VALUE)
								$this->addPropertyValue($arProp["ID"], $VALUE);
						}
					}
					else
					{
						if ($res[$field_name] != "")
						{
							if ($this->extMode && $arProp["PROPERTY_TYPE"] == PropertyTable::TYPE_NUMBER)
							{
								$res[$field_name] = CIBlock::NumberFormat($res[$field_name]);
							}
							$this->addPropertyValue($arProp["ID"], $res[$field_name]);
						}
						if ($this->extMode)
						{
							$this->addPropertyData($arProp["ID"], $res["IBLOCK_ELEMENT_ID"].':'.$arProp["ID"], $res["DESCRIPTION_".$arProp["ID"]]);
						}
					}
				}
				foreach($arUpdate as $strTable=>$arFields)
				{
					$strUpdate = $DB->PrepareUpdate($strTable, $arFields);
					if($strUpdate!="")
					{
						$strSql = "UPDATE ".$strTable." SET ".$strUpdate." WHERE IBLOCK_ELEMENT_ID = ".intval($res["IBLOCK_ELEMENT_ID"]);
						$DB->QueryBind($strSql, $arFields);
					}
				}
				$res = $this->arPropertiesValues;
				if ($this->extMode)
				{
					$res['PROPERTY_VALUE_ID'] = $this->arPropertyValuesID;
					$res['DESCRIPTION'] = $this->arDescriptions;
				}
			}
			else
			{
				do {
					if (isset($this->arProperties[$res["IBLOCK_PROPERTY_ID"]]))
					{
						if ($this->arProperties[$res["IBLOCK_PROPERTY_ID"]]["PROPERTY_TYPE"] == "N" && !$this->extMode)
							$this->addPropertyValue($res["IBLOCK_PROPERTY_ID"], $res["VALUE_NUM"]);
						else
							$this->addPropertyValue($res["IBLOCK_PROPERTY_ID"], $res["VALUE"]);
						if ($this->extMode)
						{
							$this->addPropertyData($res["IBLOCK_PROPERTY_ID"], $res['PROPERTY_VALUE_ID'], $res['DESCRIPTION']);
						}
					}
					$res = parent::Fetch();
				} while ($res && ($res["IBLOCK_ELEMENT_ID"] == $this->arPropertiesValues["IBLOCK_ELEMENT_ID"]));

				$this->lastRes = $res;
				$res = $this->arPropertiesValues;
				$this->arPropertiesValues = array();
				if ($this->extMode)
				{
					$res['PROPERTY_VALUE_ID'] = $this->arPropertyValuesID;
					$res['DESCRIPTION'] = $this->arDescriptions;
					$this->arPropertyValuesID = array();
					$this->arDescriptions = array();
				}
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
			if ($this->extMode)
			{
				if ($arProp["MULTIPLE"] == "Y")
				{
					$this->arPropertyValuesID[$arProp["ID"]] = array();
					$this->arDescriptions[$arProp["ID"]] = array();
				}
				else
				{
					$this->arPropertyValuesID[$arProp["ID"]] = '';
					$this->arDescriptions[$arProp["ID"]] = null;
				}
			}
		}
	}

	private function addPropertyData($IBLOCK_PROPERTY_ID, $VALUE_ID, $DESCRIPTION)
	{
		if (isset($this->arProperties[$IBLOCK_PROPERTY_ID]))
		{
			if ($this->arProperties[$IBLOCK_PROPERTY_ID]["MULTIPLE"] == "Y")
			{
				$this->arPropertyValuesID[$IBLOCK_PROPERTY_ID][] = $VALUE_ID;
				$this->arDescriptions[$IBLOCK_PROPERTY_ID][] = $DESCRIPTION;
			}
			else
			{
				$this->arPropertyValuesID[$IBLOCK_PROPERTY_ID] = $VALUE_ID;
				$this->arDescriptions[$IBLOCK_PROPERTY_ID] = $DESCRIPTION;
			}
		}
	}

	public function setIBlock($IBLOCK_ID, $propertyID = array())
	{
		$this->VERSION = CIBlockElement::GetIBVersion($IBLOCK_ID);

		if (!empty($propertyID))
		{
			Collection::normalizeArrayValuesByInt($propertyID);
		}
		$this->arProperties = array();
		if (
			!empty($propertyID)
			|| (empty($propertyID) && !isset(self::$propertiesCache[$IBLOCK_ID]))
		)
		{
			$propertyIterator = PropertyTable::getList(array(
				'select' => array(
					'ID', 'IBLOCK_ID', 'NAME', 'ACTIVE', 'SORT', 'CODE', 'DEFAULT_VALUE', 'PROPERTY_TYPE',
					'MULTIPLE', 'LINK_IBLOCK_ID', 'VERSION', 'USER_TYPE', 'USER_TYPE_SETTINGS'
				),
				'filter' => (empty($propertyID) ? array('IBLOCK_ID' => $IBLOCK_ID) : array('ID' => $propertyID, 'IBLOCK_ID' => $IBLOCK_ID)),
				'order' => array('ID' => 'ASC')
			));
			while ($property = $propertyIterator->fetch())
			{
				if ($property['USER_TYPE'])
				{
					$userType = CIBlockProperty::GetUserType($property['USER_TYPE']);
					if (isset($userType["ConvertFromDB"]))
					{
						if (array_key_exists("DEFAULT_VALUE", $property))
						{
							$value = array("VALUE" => $property["DEFAULT_VALUE"], "DESCRIPTION" => "");
							$value = call_user_func_array($userType["ConvertFromDB"], array($property, $value));
							$property["DEFAULT_VALUE"] = $value["VALUE"];
						}
					}
				}
				if ($property['USER_TYPE_SETTINGS'] !== '' || $property['USER_TYPE_SETTINGS'] !== null)
					$property['USER_TYPE_SETTINGS'] = unserialize($property['USER_TYPE_SETTINGS']);
				$this->arProperties[$property['ID']] = $property;
			}
			unset($property, $propertyIterator);
			if (empty($propertyID))
			{
				self::$propertiesCache[$IBLOCK_ID] = $this->arProperties;
			}
		}
		else
		{
			$this->arProperties = self::$propertiesCache[$IBLOCK_ID];
		}
	}

	public function setMode($extMode)
	{
		$this->extMode = $extMode;
		$this->arPropertyValuesID = array();
		$this->arDescriptions = array();
	}
}
?>