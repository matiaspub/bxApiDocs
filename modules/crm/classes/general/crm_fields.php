<?
IncludeModuleLangFile(__FILE__);

class CCrmFields
{
	private $sUFEntityID = '';

	protected $cUFM = null;

	protected $cdb = null;

	private $arUFList = array();

	private $arEntityType = array();

	private $arFieldType = array();

	private $arErrors = array();

	private $bError = false;

	function __construct(CUserTypeManager $cUFM, $sUFEntityID)
	{
		global $DB;

		$this->cUFM = $cUFM;

		$this->sUFEntityID = $sUFEntityID;

		$this->arEntityId = self::GetEntityTypes();

		if (!isset($this->arEntityId[$sUFEntityID]))
		{
			$this->SetError(array('id' => 'ENTITY_ID', 'text' => GetMessage("CRM_FIELDS_ERROR_ENTITY_ID")));

			return false;
		}

		$this->arFieldType = self::GetFieldTypes();

		$this->arUFList = $this->cUFM->GetUserFields($sUFEntityID, 0, LANGUAGE_ID);

		$this->cdb = $DB;
	}

	public function GetFields()
	{
		return $this->arUFList;
	}

	public function GetFieldById($ID)
	{
		if (isset($this->arUFList[$ID]))
			return $this->arUFList[$ID];
		else
			return false;
	}

	public static function GetFieldTypes()
	{
		$arFieldType = Array(
			'string' 	=> array( 'ID' =>'string', 'NAME' => GetMessage('CRM_FIELDS_TYPE_S')),
			'integer'	=> array( 'ID' =>'integer', 'NAME' => GetMessage('CRM_FIELDS_TYPE_I')),
			'double'	=> array( 'ID' =>'double', 'NAME' => GetMessage('CRM_FIELDS_TYPE_D')),
			'boolean'	=> array( 'ID' =>'boolean', 'NAME' => GetMessage('CRM_FIELDS_TYPE_B')),
			'enumeration' => array( 'ID' =>'enumeration', 'NAME' => GetMessage('CRM_FIELDS_TYPE_E')),
			'datetime'	=> array( 'ID' =>'datetime', 'NAME' => GetMessage('CRM_FIELDS_TYPE_DT')),
			'file'		=> array( 'ID' =>'file', 'NAME' => GetMessage('CRM_FIELDS_TYPE_F')),
			'employee'	=> array( 'ID' =>'employee', 'NAME' => GetMessage('CRM_FIELDS_TYPE_EM')),
			'crm_status'=> array( 'ID' =>'crm_status', 'NAME' => GetMessage('CRM_FIELDS_TYPE_CRM_STATUS')),
			'iblock_section'=> array( 'ID' =>'iblock_section', 'NAME' => GetMessage('CRM_FIELDS_TYPE_IBLOCK_SECTION')),
			'iblock_element'=> array( 'ID' =>'iblock_element', 'NAME' => GetMessage('CRM_FIELDS_TYPE_IBLOCK_ELEMENT')),
			'crm'		=> array( 'ID' =>'crm', 'NAME' => GetMessage('CRM_FIELDS_TYPE_CRM_ELEMENT')),
		);
		return $arFieldType;
	}

	public static function GetEntityTypes()
	{
		$arEntityType = Array(
			'CRM_LEAD' => array(
				'ID' =>'CRM_LEAD',
				'NAME' => GetMessage('CRM_FIELDS_LEAD'),
				'DESC' => GetMessage('CRM_FIELDS_LEAD_DESC')
			),
			'CRM_CONTACT' => array(
				'ID' =>'CRM_CONTACT',
				'NAME' => GetMessage('CRM_FIELDS_CONTACT'),
				'DESC' => GetMessage('CRM_FIELDS_CONTACT_DESC')
			),
			'CRM_COMPANY' => array(
				'ID' =>'CRM_COMPANY',
				'NAME' => GetMessage('CRM_FIELDS_COMPANY'),
				'DESC' => GetMessage('CRM_FIELDS_COMPANY_DESC')
			),
			'CRM_DEAL'=> array(
				'ID' =>'CRM_DEAL',
				'NAME' => GetMessage('CRM_FIELDS_DEAL'),
				'DESC' => GetMessage('CRM_FIELDS_DEAL_DESC')
			),
			'CRM_QUOTE'=> array(
				'ID' =>'CRM_QUOTE',
				'NAME' => GetMessage('CRM_FIELDS_QUOTE'),
				'DESC' => GetMessage('CRM_FIELDS_QUOTE_DESC')
			)
		);
		return $arEntityType;
	}

	public static function GetAdditionalFields($entityType, $fieldValue = Array())
	{
		$arFields = Array();
		switch ($entityType) {
			case 'string':
			case 'integer':
			case 'double':
				$arFields[] = array(
					'id' => 'DEFAULT_VALUE',
					'name' => GetMessage('CRM_FIELDS_DEFAULT_VALUE'),
					'type' => 'text',
				);
			break;

			case 'boolean':
				$arFields[] = array(
					'id' => 'B_DEFAULT_VALUE',
					'name' => GetMessage('CRM_FIELDS_TYPE_B_VALUE'),
					'type' => 'list',
					'items' => array(
						'1' => GetMessage('CRM_FIELDS_TYPE_B_VALUE_YES'),
						'0' => GetMessage('CRM_FIELDS_TYPE_B_VALUE_NO')
					),
				);
				$arFields[] = array(
					'id' => 'B_DISPLAY',
					'name' => GetMessage('CRM_FIELDS_TYPE_B_DISPLAY'),
					'type' => 'list',
					'items' => array(
						'CHECKBOX' 	=> GetMessage('CRM_FIELDS_TYPE_B_DISPLAY_CHECKBOX'),
						'RADIO'		=> GetMessage('CRM_FIELDS_TYPE_B_DISPLAY_RADIO'),
						'DROPDOWN' 	=> GetMessage('CRM_FIELDS_TYPE_B_DISPLAY_DROPDOWN'),
					),
				);
			break;

			case 'datetime':
				$arFields[] = array(
					'id' => 'DT_TYPE',
					'name' => GetMessage('CRM_FIELDS_TYPE_DT_TYPE'),
					'type' => 'list',
					'items' => array(
						'NONE' 	=> GetMessage('CRM_FIELDS_TYPE_DT_TYPE_NONE'),
						'NOW'	=> GetMessage('CRM_FIELDS_TYPE_DT_TYPE_NOW'),
						'FIXED' => GetMessage('CRM_FIELDS_TYPE_DT_TYPE_FIXED'),
					),
				);
				$arFields[] = array(
					'id' => 'DT_DEFAULT_VALUE',
					'name' => GetMessage('CRM_FIELDS_TYPE_DT_FIXED'),
					'type' => 'date',
					'params' => array(
						'size' => 25
					)
				);
			break;

			case 'enumeration':
				$arFields[] = array(
					'id' => 'E_DISPLAY',
					'name' => GetMessage('CRM_FIELDS_TYPE_E_DISPLAY'),
					'type' => 'list',
					'items' => array(
						'LIST'		=> GetMessage('CRM_FIELDS_TYPE_E_DISPLAY_LIST'),
						'CHECKBOX' 	=> GetMessage('CRM_FIELDS_TYPE_E_DISPLAY_CHECKBOX'),
					),
				);
				$arFields[] = array(
					'id' => 'E_LIST_HEIGHT',
					'name' => GetMessage('CRM_FIELDS_TYPE_E_LIST_HEIGHT'),
					'type' => 'text',
				);
			break;

			case 'iblock_section':
				$id = isset($fieldValue['IB_IBLOCK_ID'])? $fieldValue['IB_IBLOCK_ID']: 0;
				$bActiveFilter = isset($fieldValue['IB_ACTIVE_FILTER']) && $fieldValue['IB_ACTIVE_FILTER'] == 'Y'? 'Y': 'N';

				$arFields[] = array(
					'id' => 'IB_IBLOCK_TYPE_ID',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_IBLOCK_TYPE_ID'),
					'type' => 'custom',
					'value' => GetIBlockDropDownList($id, 'IB_IBLOCK_TYPE_ID', 'IB_IBLOCK_ID')
				);

				$arFilter = Array("IBLOCK_ID"=>$id);
				if($bActiveFilter === "Y")
					$arFilter["GLOBAL_ACTIVE"] = "Y";

				$rs = CIBlockElement::GetList(
					array("SORT" => "DESC", "NAME"=>"ASC"),
					$arFilter,
					false,
					false,
					array("ID", "NAME")
				);
				$rsSections = CIBlockSection::GetList(
					Array("left_margin"=>"asc"),
					$arFilter,
					false,
					array("ID", "DEPTH_LEVEL", "NAME")
				);
				$arDefault = Array(''=>GetMessage('CRM_FIELDS_TYPE_IB_DEFAULT_VALUE_ANY'));
				while($arSection = $rsSections->GetNext())
					$arDefaul[$arSection["ID"]] = str_repeat("&nbsp;.&nbsp;", $arSection["DEPTH_LEVEL"]).$arSection["NAME"];

				$arFields[] = array(
					'id' => 'IB_DEFAULT_VALUE',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_DEFAULT_VALUE'),
					'items' => $arDefault,
					'type' => 'list',
				);

				$arFields[] = array(
					'id' => 'IB_DISPLAY',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_DISPLAY'),
					'type' => 'list',
					'items' => array(
						'LIST'		=> GetMessage('CRM_FIELDS_TYPE_IB_DISPLAY_LIST'),
						'CHECKBOX' 	=> GetMessage('CRM_FIELDS_TYPE_IB_DISPLAY_CHECKBOX'),
					),
				);
				$arFields[] = array(
					'id' => 'IB_LIST_HEIGHT',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_LIST_HEIGHT'),
					'type' => 'text',
				);
				$arFields[] = array(
					'id' => 'IB_ACTIVE_FILTER',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_ACTIVE_FILTER'),
					'type' => 'checkbox',
				);
			break;


			case 'iblock_element':
				$id = isset($fieldValue['IB_IBLOCK_ID'])? $fieldValue['IB_IBLOCK_ID']: 0;
				$bActiveFilter = isset($fieldValue['IB_ACTIVE_FILTER']) && $fieldValue['IB_ACTIVE_FILTER'] == 'Y'? 'Y': 'N';

				$arFields[] = array(
					'id' => 'IB_IBLOCK_TYPE_ID',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_IBLOCK_TYPE_ID'),
					'type' => 'custom',
					'value' => GetIBlockDropDownList($id, 'IB_IBLOCK_TYPE_ID', 'IB_IBLOCK_ID')
				);

				$arFilter = Array("IBLOCK_ID"=>$id);
				if($bActiveFilter === "Y")
					$arFilter["ACTIVE"] = "Y";

				$rs = CIBlockElement::GetList(
					array("SORT" => "DESC", "NAME"=>"ASC"),
					$arFilter,
					false,
					false,
					array("ID", "NAME")
				);

				$arDefault = Array(''=>GetMessage('CRM_FIELDS_TYPE_IB_DEFAULT_VALUE_ANY'));
				while($ar = $rs->GetNext())
					$arDefault[$ar["ID"]] = $ar["NAME"];

				$arFields[] = array(
					'id' => 'IB_DEFAULT_VALUE',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_DEFAULT_VALUE'),
					'items' => $arDefault,
					'type' => 'list',
				);

				$arFields[] = array(
					'id' => 'IB_DISPLAY',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_DISPLAY'),
					'type' => 'list',
					'items' => array(
						'LIST'		=> GetMessage('CRM_FIELDS_TYPE_IB_DISPLAY_LIST'),
						'CHECKBOX' 	=> GetMessage('CRM_FIELDS_TYPE_IB_DISPLAY_CHECKBOX'),
					),
				);
				$arFields[] = array(
					'id' => 'IB_LIST_HEIGHT',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_LIST_HEIGHT'),
					'type' => 'text',
				);
				$arFields[] = array(
					'id' => 'IB_ACTIVE_FILTER',
					'name' => GetMessage('CRM_FIELDS_TYPE_IB_ACTIVE_FILTER'),
					'type' => 'checkbox',
				);

			break;

			case 'crm_status':

				$arItems = Array();
				$ar = CCrmStatus::GetEntityTypes();
				foreach ($ar as $data)
					$arItems[$data['ID']] = $data['NAME'];

				$arFields[] = array(
					'id' => 'ENTITY_TYPE',
					'name' => GetMessage('CRM_FIELDS_TYPE_CRM_STATUS_ENTITY_TYPE'),
					'type' => 'list',
					'items' => $arItems,
				);
			break;

			case 'crm':
				$entityTypeLead = isset($fieldValue['ENTITY_TYPE_LEAD']) && $fieldValue['ENTITY_TYPE_LEAD'] == 'Y'? 'Y': 'N';
				$entityTypeContact = isset($fieldValue['ENTITY_TYPE_CONTACT']) && $fieldValue['ENTITY_TYPE_CONTACT'] == 'Y'? 'Y': 'N';
				$entityTypeCompany = isset($fieldValue['ENTITY_TYPE_COMPANY']) && $fieldValue['ENTITY_TYPE_COMPANY'] == 'Y'? 'Y': 'N';
				$entityTypeDeal = isset($fieldValue['ENTITY_TYPE_DEAL']) && $fieldValue['ENTITY_TYPE_DEAL'] == 'Y'? 'Y': 'N';

				$sVal = '
					<input type="checkbox" name="ENTITY_TYPE_LEAD" value="Y" '.($entityTypeLead=="Y"? 'checked="checked"': '').'> '.GetMessage('USER_TYPE_CRM_ENTITY_TYPE_LEAD').' <br/>
					<input type="checkbox" name="ENTITY_TYPE_CONTACT" value="Y" '.($entityTypeContact=="Y"? 'checked="checked"': '').'> '.GetMessage('USER_TYPE_CRM_ENTITY_TYPE_CONTACT').'<br/>
					<input type="checkbox" name="ENTITY_TYPE_COMPANY" value="Y" '.($entityTypeCompany=="Y"? 'checked="checked"': '').'> '.GetMessage('USER_TYPE_CRM_ENTITY_TYPE_COMPANY').'<br/>
					<input type="checkbox" name="ENTITY_TYPE_DEAL" value="Y" '.($entityTypeDeal=="Y"? 'checked="checked"': '').'> '.GetMessage('USER_TYPE_CRM_ENTITY_TYPE_DEAL').'<br/>
				';

				$arFields[] = array(
					'id' => 'ENTITY_TYPE',
					'name' => GetMessage('CRM_FIELDS_TYPE_CRM_ELEMENT_ENTITY_TYPE'),
					'type' => 'custom',
					'value' => $sVal
				);
			break;
		}
		return $arFields;
	}

	public function DeleteField($ID)
	{
		$obUserField = new CUserTypeEntity();
		@set_time_limit(0);
		$this->cdb->StartTransaction();
		if (!$obUserField->Delete($ID))
		{
			$this->cdb->Rollback();

			$this->SetError(array('id' => 'DELETE_ENTITY_ID', 'text' => GetMessage('CRM_FIELDS_ERROR_DELETE_ENTITY_ID')));

			return false;
		}
		$this->cdb->Commit();

		$this->arUFList = $this->cUFM->GetUserFields($this->sUFEntityID, 0, LANGUAGE_ID);

		return true;
	}

	public function AddField($arField)
	{
		$obUserField = new CUserTypeEntity();
		$ID = $obUserField->Add($arField);
		$res = ($ID>0);

		if ($res)
		{
			if ($arField['USER_TYPE_ID'] == 'enumeration' && is_array($arField['LIST']))
			{
				$obEnum = new CUserFieldEnum();
				$res = $obEnum->SetEnumValues($ID, $arField['LIST']);
				if (!$res)
				{
					$ex = $GLOBALS["APPLICATION"]->GetException();
				}

			}

			$this->cUFM->CleanCache();
			$this->arUFList = $this->cUFM->GetUserFields($this->sUFEntityID, 0, LANGUAGE_ID);
		}
		else
		{
			$ex = $GLOBALS["APPLICATION"]->GetException();

		}


		return $res;
	}

	public function UpdateField($ID, $arField)
	{
		$obUserField  = new CUserTypeEntity();
		$res = $obUserField->Update($ID, $arField);

		if ($res && $arField['USER_TYPE_ID'] == 'enumeration' && is_array($arField['LIST']))
		{
			$obEnum = new CUserFieldEnum();
			$res = $obEnum->SetEnumValues($ID, $arField['LIST']);
		}

		$this->arUFList = $this->cUFM->GetUserFields($this->sUFEntityID, 0, LANGUAGE_ID);

		return $res;
	}

	public function GetNextFieldId()
	{
		return 'UF_CRM_'.time();
	}

	private function SetError($arMsg)
	{
		$this->arErrors[] = $arMsg;

		$this->bError = true;

		return true;
	}

	public function CheckError()
	{
		global $APPLICATION;

		$e = new CAdminException($this->arErrors);
		$APPLICATION->ThrowException($e);

		return $this->bError;
	}
}
?>