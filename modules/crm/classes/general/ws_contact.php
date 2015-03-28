<?php

if (!CModule::IncludeModule('webservice'))
	return;

IncludeModuleLangFile(__FILE__);

class CCrmContactWS extends IWebService
{

	protected function __getFieldsDefinition()
	{
		$obFields = new CXMLCreator('Fields');

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="CONTENTTYPEID" ColName="tp_ContentTypeId" Sealed="TRUE" Hidden="TRUE" RowOrdinal="0" ReadOnly="TRUE" Type="ContentTypeId" Name="ContentTypeId" DisplaceOnUpgrade="TRUE" DisplayName="Content Type ID" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ContentTypeId" FromBaseType="TRUE"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="ID" ColName="tp_ID" RowOrdinal="0" ReadOnly="TRUE" Type="Counter" Name="ID" PrimaryKey="TRUE" DisplayName="ID" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ID" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="ATTACHMENTS" ColName="tp_HasAttachment" RowOrdinal="0" Type="Attachments" Name="Attachments" DisplayName="Attachments" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Attachments" FromBaseType="TRUE"'));

		//<Field ID="{67df98f4-9dec-48ff-a553-29bece9c5bf4}" ColName="tp_HasAttachment" RowOrdinal="0" Type="Attachments" Name="Attachments" DisplayName="Attachments" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Attachments" FromBaseType="TRUE"/>

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="TITLE" Type="Text" Name="Title" Sealed="TRUE" DisplayName="Last Name" Required="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Title" FromBaseType="TRUE" ColName="nvarchar1"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="FIRSTNAME" Name="FirstName" DisplayName="First Name" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="FirstName" ColName="nvarchar4"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="FULLNAME" Name="FullName" DisplayName="Full Name" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="FullName" ColName="nvarchar6"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="EMAIL" Name="Email" DisplayName="E-mail Address" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Email" ColName="nvarchar7"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="EMAIL2" Name="Email2" DisplayName="E-mail Address" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Email2" ColName="nvarchar7"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="EMAIL3" Name="Email3" DisplayName="E-mail Address" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Email3" ColName="nvarchar7"'));

		//$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="BCPICTURE" Name="BCPicture" DisplayName="BCPicture" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="BCPicture" ColName="bcpicture" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="PHOTO" Name="Photo" DisplayName="Photo" Type="URL" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Photo" ColName="PERSONAL_PHOTO" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="COMPANY" Name="Company" DisplayName="Company" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Company" ColName="nvarchar8"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="JOBTITLE" Name="JobTitle" DisplayName="Job Title" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="JobTitle" ColName="nvarchar10"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="DEPARTMENT" Name="ol_Department" DisplayName="Department" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ol_Department" ColName="nvarchar100"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="OTHERNUMBER" Name="OtherNumber" DisplayName="Other Phone" IMEMode="inactive" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="OtherNumber" ColName="nvarchar11"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="PAGERNUMBER" Name="PagerNumber" DisplayName="Pager Phone" IMEMode="inactive" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="PagerNumber" ColName="nvarchar11"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="WORKPHONE" Name="WorkPhone" DisplayName="Business Phone" IMEMode="inactive" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="WorkPhone" ColName="nvarchar11"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="HOMEPHONE" Name="HomePhone" DisplayName="Home Phone" IMEMode="inactive" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="HomePhone" ColName="nvarchar12"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="MOBILEPHONE" Name="CellPhone" DisplayName="Mobile Phone" IMEMode="inactive" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="CellPhone" ColName="nvarchar13"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="WORKFAX" Name="WorkFax" DisplayName="Fax Number" IMEMode="inactive" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="WorkFax" ColName="nvarchar14"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="WORKADDRESS" Name="WorkAddress" DisplayName="Address" Type="Note" NumLines="2" Sortable="FALSE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="WorkAddress" ColName="ntext2"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="WORKFREEFORM" Name="WorkFreeForm" DisplayName="Address" Type="Note" NumLines="2" Sortable="FALSE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="WorkAddress" ColName="ntext2"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="WEBPAGE" Name="WebPage" DisplayName="Web Page" Type="URL" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="WebPage" ColName="nvarchar19" ColName2="nvarchar20"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="IMADDRESS" Name="IMAddress" DisplayName="IM Address" IMEMode="inactive" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="IMAddress" ColName="nvarchar11"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="COMMENTS" Name="Comments" DisplayName="Comment" Type="Note" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Comments" ColName="ntext2"'));

		//$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="CONTENTTYPE" ColName="tp_ContentType" RowOrdinal="0" ReadOnly="TRUE" Type="Text" Name="ContentType" DisplaceOnUpgrade="TRUE" DisplayName="Content Type" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ContentType" FromBaseType="TRUE" PITarget="MicrosoftWindowsSharePointServices" PIAttribute="ContentTypeID"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="MODIFIED" ColName="tp_Modified" RowOrdinal="0" ReadOnly="TRUE" Type="DateTime" Name="Modified" DisplayName="Modified" StorageTZ="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Modified" FromBaseType="TRUE"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="CREATED" ColName="tp_Created" RowOrdinal="0" ReadOnly="TRUE" Type="DateTime" Name="Created" DisplayName="Created" StorageTZ="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Created" FromBaseType="TRUE"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="AUTHOR" ColName="tp_Author" RowOrdinal="0" ReadOnly="TRUE" Type="User" List="UserInfo" Name="Author" DisplayName="Created By" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Author" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="EDITOR" ColName="tp_Editor" RowOrdinal="0" ReadOnly="TRUE" Type="User" List="UserInfo" Name="Editor" DisplayName="Modified By" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Editor" FromBaseType="TRUE"'));

		// ******************* //
		//$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="OWSHIDDENVERSION" ColName="tp_Version" RowOrdinal="0" Hidden="TRUE" ReadOnly="TRUE" Type="Integer" SetAs="owshiddenversion" Name="owshiddenversion" DisplayName="owshiddenversion" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="owshiddenversion" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="FSOBJTYPE" Name="FSObjType" DisplaceOnUpgrade="TRUE" ReadOnly="TRUE" Hidden="TRUE" ShowInFileDlg="FALSE" Type="Lookup" DisplayName="Item Type" List="Docs" FieldRef="ID" ShowField="FSType" JoinColName="DoclibRowId" JoinRowOrdinal="0" JoinType="INNER" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="FSObjType" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="PERMMASK" Name="PermMask" DisplaceOnUpgrade="TRUE" ReadOnly="TRUE" Hidden="TRUE" RenderXMLUsingPattern="TRUE" ShowInFileDlg="FALSE" Type="Computed" DisplayName="Effective Permissions Mask" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="PermMask" FromBaseType="TRUE"'));
		$obField->addChild($obFieldRefs = new CXMLCreator('FieldRefs'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="ID"'));

		$obField->addChild($obDisplayPattern = new CXMLCreator('DisplayPattern'));
		$obDisplayPattern->addChild(new CXMLCreator('CurrentRights'));


		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="UNIQUEID" Name="UniqueId" DisplaceOnUpgrade="TRUE" ReadOnly="TRUE" Hidden="TRUE" ShowInFileDlg="FALSE" Type="Lookup" DisplayName="Unique Id" List="Docs" FieldRef="ID" ShowField="UniqueId" JoinColName="DoclibRowId" JoinRowOrdinal="0" JoinType="INNER" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="UniqueId" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="METAINFO" Name="MetaInfo" DisplaceOnUpgrade="TRUE" Hidden="TRUE" ShowInFileDlg="FALSE" Type="Lookup" DisplayName="Property Bag" List="Docs" FieldRef="ID" ShowField="MetaInfo" JoinColName="DoclibRowId" JoinType="INNER" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="MetaInfo" FromBaseType="TRUE"'));

		return $obFields;
	}

	protected function __makeDateTime($ts = null)
	{
		if (null === $ts)
			$ts = time();

		return date('Y-m-d', $ts).'T'.date('H:i:s', $ts).'Z';
	}

	protected function __makeTS($datetime = null)
	{
		if (null === $datetime)
			return time();

		if (intval(substr($datetime, 0, 4)) >= 2037)
			$datetime = '2037'.substr($datetime, 4);

		return MakeTimeStamp(substr($datetime, 0, 10).' '.substr($datetime, 11, -1), 'YYYY-MM-DD HH:MI:SS');
	}

	public function GetList($listName)
	{
		global $APPLICATION;

		if (!$listName_original = self::checkGUID($listName))
		{
			return new CSoapFault(
				'Data error',
				'Wrong GUID - '.$listName
			);
		}

		$listName = ToUpper(self::makeGUID($listName_original));


		//$dbAuthor = CUser::GetByID($arSection['CREATED_BY']);
		//$arAuthor = $dbAuthor->Fetch();

		$data = new CXMLCreator('List');
		$data->setAttribute('ID', $listName);
		$data->setAttribute('Name', $listName);
		$data->setAttribute('Title', GetMessage('INTR_OUTLOOK_TITLE_CONTACTS')); // BITRIX CRM
		$data->setAttribute('Created', date('Ymd H:i:s'));
		$data->setAttribute('Modified', date('Ymd H:i:s'));
		$data->setAttribute('Direction', 'none'); // RTL, LTR

		$data->setAttribute('ReadSecurity', '2');
		$data->setAttribute('WriteSecurity', '2');

		$data->setAttribute('Author', '1;#admin');

		$data->setAttribute('EnableAttachments', 'True');

		// it's strange and awful but this thing doesn't work at outlook.
		// he always make 2 additional hits: GetAttachmentCollection and direct attachment call, independently from this settings
		//$data->setAttribute('IncludeAttachmentUrls', 'True');
		//$data->setAttribute('IncludeAttachmentVersion', 'False');

		$data->addChild($this->__getFieldsDefinition());

		$data->addChild($obNode = new CXMLCreator('RegionalSettings'));

		//$obNode->addChild(CXMLCreator::createTagAttributed('Language', '1033'));
		//$obNode->addChild(CXMLCreator::createTagAttributed('Locale', '1033'));
		$obNode->addChild(CXMLCreator::createTagAttributed('AdvanceHijri', '0'));
		$obNode->addChild(CXMLCreator::createTagAttributed('CalendarType', '0'));
		$obNode->addChild(CXMLCreator::createTagAttributed('Time24', 'True'));
		//$obNode->addChild(CXMLCreator::createTagAttributed('TimeZone', '59'));
		$obNode->addChild(CXMLCreator::createTagAttributed('Presence', 'True'));

		$data->addChild($obNode = new CXMLCreator('ServerSettings'));

		$obNode->addChild(CXMLCreator::createTagAttributed('ServerVersion', '12.0.0.6219'));
		$obNode->addChild(CXMLCreator::createTagAttributed('RecycleBinEnabled', 'False'));
		$obNode->addChild(CXMLCreator::createTagAttributed('ServerRelativeUrl', '/crm/contact/'));

		return array('GetListResult' => $data);
	}

	public function __getRow($arRes, $listName, &$last_change)
	{
		global $APPLICATION, $USER;

		$change = MakeTimeStamp($arRes['DATE_MODIFY']);

		if ($last_change < $change)
			$last_change = $change;

		$obRow = new CXMLCreator('z:row');
		$obRow->setAttribute('ows_ID', $arRes['ID']);

		$version = $arRes['VERSION'] ? $arRes['VERSION'] : 1;

		if (strlen($arRes['PHOTO']) > 0)
		{
			$arImage = self::InitImage($arRes['PHOTO'], 100, 100);
			$obRow->setAttribute('ows_Attachments', ';#'.CHTTP::URN2URI($arImage['CACHE']['src']).';#'.self::makeGUID(md5($arRes['PHOTO'])).',1;#');
			$obRow->setAttribute('ows_MetaInfo_AttachProps', '<File Photo="-1">'.$arImage['FILE']['FILE_NAME'].'</File>');
		}
		else
		{
			$obRow->setAttribute('ows_Attachments', 0);
		}

		$obRow->setAttribute('ows_owshiddenversion', $version);
		//$obRow->setAttribute('ows_MetaInfo_vti_versionhistory', md5($arRes['ID']).':'.$version);

		$obRow->setAttribute('ows_Created', $this->__makeDateTime(MakeTimeStamp($arRes['DATE_CREATE'])));
		$obRow->setAttribute('ows_Modified', $this->__makeDateTime(MakeTimeStamp($arRes['DATE_MODIFY'])));
		$obRow->setAttribute('ows_Editor', $this->__makeDateTime($change));

		$obRow->setAttribute('ows_Title', $arRes['LAST_NAME']);
		$obRow->setAttribute('ows_FirstName', $arRes['NAME']);
		$obRow->setAttribute('ows_Birthday', $arRes['BIRTHDATE']);

		$obRow->setAttribute('ows_FullName', $arRes['NAME'].' '.$arRes['SECOND_NAME'].' '.$arRes['LAST_NAME']);

		$obRow->setAttribute('ows_Email', $arRes['EMAIL_WORK']);
		$obRow->setAttribute('ows_Email2', $arRes['EMAIL_HOME']);
		$obRow->setAttribute('ows_Email3', $arRes['EMAIL_OTHER']);

		$obRow->setAttribute('ows_CellPhone', $arRes['PHONE_MOBILE']);
		$obRow->setAttribute('ows_HomePhone', $arRes['PHONE_HOME']);
		$obRow->setAttribute('ows_WorkPhone', $arRes['PHONE_WORK']);
		$obRow->setAttribute('ows_WorkFax', $arRes['PHONE_FAX']);
		$obRow->setAttribute('ows_OtherNumber', $arRes['PHONE_OTHER']);
		$obRow->setAttribute('ows_PagerNumber', $arRes['PHONE_PAGER']);

		$obRow->setAttribute('ows_WebPage', $arRes['WEB'].', '.$arRes['WEB']);

		$obRow->setAttribute('ows_IMAddress', $arRes['IM']);

		$obRow->setAttribute('ows_UniqueId', $arRes['ID'].';#'.$listName);
		$obRow->setAttribute('ows_FSObjType', $arRes['ID'].';#0');

		$obRow->setAttribute('ows_Company', $arRes['COMPANY']);
		$obRow->setAttribute('ows_JobTitle', $arRes['POST']);

		$obRow->setAttribute('ows_Comments', $arRes['COMMENTS']);
		//$obRow->setAttribute('ows_WorkAddress', $arRes['ADDRESS']);
		$obRow->setAttribute('ows_WorkFreeForm', $arRes['ADDRESS']);


		$obRow->setAttribute('ows_PermMask', '0x7fffffffffffffff');
		$obRow->setAttribute('ows_ContentTypeId', '0x010600BAAFA34998B23642B33F6D26E30D55EF');

		return $obRow;
	}

	public function GetListItemChanges($listName, $viewFields = '', $since = '', $contains = '')
	{
		define ('OLD_OUTLOOK_VERSION', true);

		$res = $this->GetListItemChangesSinceToken($listName, $viewFields, '', 0, $since ? $this->__makeTS($since) : '');

		if (is_object($res))
			return $res;
		else
			return array('GetListItemChangesResult' => $res['GetListItemChangesSinceTokenResult']);
	}

	public function GetListItemChangesSinceToken($listName, $viewFields = '', $query = '', $rowLimit = 0, $changeToken = '')
	{
		global $APPLICATION, $USER;

		if (!$listName_original = self::checkGUID($listName))
		{
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);
		}

		$listName = ToUpper(self::makeGUID($listName_original));

		$arFilter = array('EXPORT' => 'Y');

		$page = 1;
		$bUpdateFields = false;

		$tsLastFieldsChange = COption::GetOptionString('crm', 'ws_contacts_last_fields_change', false);
		if (strlen($changeToken) > 0)
		{
			if ($pos = strpos($changeToken, ';'))
			{
				list($newChangeToken, $page, $last_change) = explode(';', $changeToken);

				$page++;
				$changeToken = $newChangeToken;
			}

			$arFilter['>DATE_MODIFY'] = ConvertTimeStamp($changeToken, 'FULL');
			if (!$arFilter['>DATE_MODIFY'])
			{
				return new CSoapFault(
					'Params error',
					'Wrong changeToken: '.$changeToken
				);
			}

			if ($tsLastFieldsChange !== false && $tsLastFieldsChange > $changeToken)
			{
				$bUpdateFields = true;
			}
		}

		CTimeZone::Disable();
		$obContact = CCrmContact::GetListEx(
			array(),
			$arFilter,
			false,
			array(
				'nPageSize' => $rowLimit,
				'bShowAll' => false,
				'iNumPage' => $page
			)
		);
		CTimeZone::Enable();

		if (!isset($last_change))
			$last_change = 0;

		$data = new CXMLCreator('listitems');
		$data->setAttribute('MinTimeBetweenSyncs', 0);
		$data->setAttribute('RecommendedTimeBetweenSyncs', 180);
		$data->setAttribute('TimeStamp', $this->__makeDateTime());
		$data->setAttribute('EffectivePermMask', 'FullMask');

		$data->addChild($obChanges = new CXMLCreator('Changes'));

		if ((!$changeToken || $bUpdateFields) && $page <= 1)
		{
			$arGetListResult = $this->GetList($listName);
			$obChanges->addChild($arGetListResult['GetListResult']);
		}

		$data->addChild($obData = new CXMLCreator('rs:data'));

		$counter = 0;
		$arContacts = array();
		while ($arContact = $obContact->NavNext())
		{
			$counter++;

			if(isset($arContact['COMPANY_TITLE']))
			{
				$arContact['COMPANY'] = $arContact['COMPANY_TITLE'];
				unset($arContact['COMPANY_TITLE']);
			}
			else
			{
				$arContact['COMPANY'] = '';
			}
			$arContacts[$arContact['ID']] = $arContact;
		}

		$arCID = array_keys($arContacts);
		if (!empty($arCID))
		{
			$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $arCID));
			while($ar = $res->Fetch())
			{
				$fieldName = $ar['COMPLEX_ID'];
				if (($ar['TYPE_ID'] == 'WEB' || $ar['TYPE_ID'] == 'IM'))
					$fieldName = $ar['TYPE_ID'];
				if (empty($arContacts[$ar['ELEMENT_ID']][$fieldName]))
					$arContacts[$ar['ELEMENT_ID']][$fieldName] = $ar['VALUE'];
			}
		}
		
		foreach ($arContacts as $arContact)
			$obData->addChild($this->__getRow($arContact, $listName, $last_change));

		//$last_change = time();
		$obData->setAttribute('ItemCount', $counter);

		$data->setAttribute('xmlns:rs', 'urn:schemas-microsoft-com:rowset');
		$data->setAttribute('xmlns:z', '#RowsetSchema');

		if ($bUpdateFields && $tsLastFieldsChange)
		{
			$last_change = $tsLastFieldsChange;
		}

		if ($last_change > 0)
		{
			if ($rowLimit && $obContact->NavPageCount > 1 && $obContact->NavPageCount > $page)
			{
				$last_change = intval($changeToken).';'.$page.';'.$last_change;
				$obChanges->setAttribute('MoreChanges', 'TRUE');
			}
			else
			{
				$last_change += 1;
			}

			$obChanges->setAttribute('LastChangeToken', $last_change);
		}

		return array('GetListItemChangesSinceTokenResult' => $data);
	}

	public function GetAttachmentCollection($listName, $listItemID)
	{
		$start = microtime(true);

		if (!$listName_original = self::checkGUID($listName))
		{
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);
		}

		$listName = ToUpper(self::makeGUID($listName_original));
		$listItemID = intval($listItemID);

		$dbRes = CCrmContact::GetList(array(), array('ID' => $listItemID), array('PHOTO'));

		$obData = new CXMLCreator('Attachments');

		if (($arContact = $dbRes->Fetch()) && $arContact['PHOTO'])
		{
			$arImage = self::InitImage($arContact['PHOTO'], 100, 100);
			$obData->addChild($obAttachment = new CXMLCreator('Attachment'));
			$obAttachment->setData(CHTTP::URN2URI($arImage['CACHE']['src']));
		}

		return array('GetAttachmentCollectionResult' => $obData);
	}

	public static function CheckAuth()
	{
		$CCrmPerms = new CCrmPerms($GLOBALS['USER']->GetID());
		if ($CCrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE))
			return new CSOAPFault('Server Error', 'Unable to authorize user.');
		return false;
	}

	public function Add($data)
	{
		if (($r = self::CheckAuth()) !== false)
		{
			return $r;
		}

		$arFieldsInfo = CCrmContact::GetFields();

		$arFields = array();
		$arEl = $data->elementsByName('Field');
		foreach ($arEl as $el)
		{
			$children = $el->children();
			$sFieldName = $el->getAttribute('id');

			// Fix for issue #40193
			if(!isset($arFieldsInfo[$sFieldName]))
			{
				continue;
			}

			if (!is_null($children))
			{
				$arFields[$sFieldName] = array();
				foreach ($children as $child)
				{
					$arFields[$sFieldName][]  = $child->content;
				}
			}
			else
			{
				$arFields[$sFieldName]  = $el->content;
			}
		}

		CCrmFieldMulti::PrepareFields($arFields);

		if(isset($arFields['PHOTO']))
		{
			$arFile = null;
			if (CCrmUrlUtil::HasScheme($arFields['PHOTO']) && CCrmUrlUtil::IsSecureUrl($arFields['PHOTO']))
			{
				$arFile = CFile::MakeFileArray($arFields['PHOTO']);
				if (is_array($arFile))
				{
					$arFile += array('MODULE_ID' => 'crm');
				}
			}

			if (is_array($arFile))
			{
				$arFields['PHOTO'] = $arFile;
			}
			else
			{
				unset($arFields['PHOTO']);
			}
		}

		$arUserFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmContact::$sUFEntityID);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'file')
			{
				if (!isset($arFields[$FIELD_NAME]))
				{
					continue;
				}

				if(is_array($arFields[$FIELD_NAME]))
				{
					$arFiles = array();
					foreach ($arFields[$FIELD_NAME] as $sFilePath)
					{
						if(!(CCrmUrlUtil::HasScheme($sFilePath) && CCrmUrlUtil::IsSecureUrl($sFilePath)))
						{
							continue;
						}

						$arFile = CFile::MakeFileArray($sFilePath);
						if (is_array($arFile))
						{
							$arFile += array('MODULE_ID' => 'crm');
							$arFiles[] = $arFile;
						}
					}
					$arFields[$FIELD_NAME] = $arFiles;
				}
				else
				{
					$arFile = null;
					$sFilePath = $arFields[$FIELD_NAME];
					if(CCrmUrlUtil::HasScheme($sFilePath) && CCrmUrlUtil::IsSecureUrl($sFilePath))
					{
						$arFile = CFile::MakeFileArray($sFilePath);
						if (is_array($arFile))
						{
							$arFile += array('MODULE_ID' => 'crm');
						}
					}
					if (is_array($arFile))
					{
						$arFields[$FIELD_NAME] = $arFile;
					}
					else
					{
						unset($arFields[$FIELD_NAME]);
					}
				}
			}
		}

		$CCrmContact = new CCrmContact();
		return $CCrmContact->Add($arFields)
				? 'ok'
				: new CSoapFault('CCrmLead::Add', htmlspecialcharsbx(strip_tags(nl2br($arFields['RESULT_MESSAGE']))));
	}

	protected static function GetTypeList()
	{
		$ar = CCrmStatus::GetStatusList('CONTACT_TYPE');
		$CXMLCreatorR = new CXMLCreator('CHOISES');

		foreach ($ar as $key => $value)
		{
			$CXMLCreator = new CXMLCreator('CHOISE', true);
			$CXMLCreator->setAttribute('id', $key);
			$CXMLCreator->setData($value);
			$CXMLCreatorR->addChild($CXMLCreator);
		}

		return $CXMLCreatorR;
	}

	protected static function GetSourceList()
	{
		$ar = CCrmStatus::GetStatusListEx('SOURCE');
		$CXMLCreatorR = new CXMLCreator('CHOISES');

		foreach ($ar as $key => $value)
		{
			$CXMLCreator = new CXMLCreator('CHOISE', true);
			$CXMLCreator->setAttribute('id', $key);
			$CXMLCreator->setData($value);
			$CXMLCreatorR->addChild($CXMLCreator);
		}

		return $CXMLCreatorR;
	}

	function GetFieldsList()
	{
		$fields = new CXMLCreator('Fields');
		$fields->addChild(CXMLCreator::createTagAttributed('Field id="NAME" name="'.GetMessage('CRM_FIELD_NAME').'" type="string" require="false" default=""', ''));
		$fields->addChild(CXMLCreator::createTagAttributed('Field id="LAST_NAME" name="'.GetMessage('CRM_FIELD_LAST_NAME').'" type="string" require="false" default=""', ''));
		$fields->addChild(CXMLCreator::createTagAttributed('Field id="SECOND_NAME" name="'.GetMessage('CRM_FIELD_SECOND_NAME').'" type="string" require="false" default=""', ''));
		$fields->addChild(CXMLCreator::createTagAttributed('Field id="PHOTO" name="'.GetMessage('CRM_FIELD_PHOTO').'" type="file" require="false" default=""', ''));
		$ar = CCrmFieldMulti::GetEntityComplexList();
		foreach($ar as $fieldId => $fieldName)
			$fields->addChild(CXMLCreator::createTagAttributed('Field id="'.$fieldId.'" name="'.$fieldName.'" type="string" require="false" default=""', ''));

		$fields->addChild(CXMLCreator::createTagAttributed('Field id="POST" name="'.GetMessage('CRM_FIELD_POST').'" type="string" require="false" default=""', ''));
		$fields->addChild(CXMLCreator::createTagAttributed('Field id="ADDRESS" name="'.GetMessage('CRM_FIELD_ADDRESS').'" type="string" require="false" default=""', ''));
		$fields->addChild(CXMLCreator::createTagAttributed('Field id="COMMENTS" name="'.GetMessage('CRM_FIELD_COMMENTS').'" type="string" require="false" default=""', ''));
		$fieldList = CXMLCreator::createTagAttributed('Field id="TYPE_ID" name="'.GetMessage('CRM_FIELD_TYPE_ID').'" type="int" default=""', '');
			$fieldList->addChild(self::GetTypeList());
		$fields->addChild($fieldList);
		$fieldList = CXMLCreator::createTagAttributed('Field id="SOURCE_ID" name="'.GetMessage('CRM_FIELD_SOURCE_ID').'" type="int" default=""', '');
			$fieldList->addChild(self::GetSourceList());
		$fields->addChild($fieldList);
		$fields->addChild(CXMLCreator::createTagAttributed('Field id="SOURCE_DESCRIPTION" name="'.GetMessage('CRM_FIELD_SOURCE_DESCRIPTION').'" type="text" default=""', ''));

		$CCrmUserType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], CCrmLead::$sUFEntityID);
		$CCrmUserType->AddWebserviceFields($fields);

		return array('GetFieldsListResult' => $fields);
	}

	public function GetWebServiceDesc()
	{
		$wsdesc = new CWebServiceDesc();
		$wsdesc->wsname = "bitrix.crm.contact.webservice";
		$wsdesc->wsclassname = "CCrmContactWS";
		$wsdesc->wsdlauto = true;
		$wsdesc->wsendpoint = CWebService::GetDefaultEndpoint();
		$wsdesc->wstargetns = CWebService::GetDefaultTargetNS();

		$wsdesc->classTypes = array();
		$wsdesc->structTypes = array();

		$wsdesc->classes = array(
			"CCrmContactWS" => array(
				"GetList" => array(
					"type"		=> "public",
					"name"		=> "GetList",
					"input"		=> array(
						"listName" => array("varType" => "string"),
					),
					"output"	=> array(
						"GetListResult" => array("varType" => 'any'),
					),
					'httpauth' => 'Y'
				),
				'GetListItemChanges' => array(
					'type' => 'public',
					'name' => 'GetListItemChanges',
					'input' => array(
						"listName" => array("varType" => "string"),
						"viewFields" => array("varType" => "any", 'strict'=> 'no'),
						'since' => array('varType' => 'string', 'strict' => 'no'),
					),
					'output' => array(
						'GetListItemChangesResult' => array('varType' => 'any'),
					),
					'httpauth' => 'Y'
				),
				'GetListItemChangesSinceToken' => array(
					'type' => 'public',
					'name' => 'GetListItemChangesSinceToken',
					'input' => array(
						"listName" => array("varType" => "string"),
						"viewFields" => array("varType" => "any", 'strict'=> 'no'),
						'query' => array('varType' => 'any', 'strict' => 'no'),
						'rowLimit' => array('varType' => 'string', 'strict' => 'no'),
						'changeToken' => array('varType' => 'string', 'strict' => 'no'),
					),
					'output' => array(
						'GetListItemChangesSinceTokenResult' => array('varType' => 'any'),
					),
					'httpauth' => 'Y'
				),
				'GetAttachmentCollection' => array(
					'type' => 'public',
					'name' => 'GetAttachmentCollection',
					'input' => array(
						"listName" => array("varType" => "string"),
						"listItemID" => array("varType" => "string"),
					),
					'output' => array(
						'GetAttachmentCollectionResult' => array('varType' => 'any'),
					),
					'httpauth' => 'Y'
				),
				'GetFieldsList' => array(
					'type' => 'public',
					'name' => 'GetFieldsList',
					'input' => array(),
					'output' => array(
						'GetFieldsListResult' => array('varType' => 'any')
					),
					'httpauth' => 'Y'
				),
				'Add' => array(
					'type'		=> 'public',
					'name'		=> 'Add',
					'input'		=> array(
						'data' => array('varType' => 'any')
					),
					'output'	=> array(
						'result' => array('varType' => 'string')
					),
					'httpauth' => 'Y'
				)
			)
		);

		return $wsdesc;
	}

	public static function makeGUID($data)
	{
		if (strlen($data) !== 32) return false;
		else return
			'{'.
				substr($data, 0, 8).'-'.substr($data, 8, 4).'-'.substr($data, 12, 4).'-'.substr($data, 16, 4).'-'.substr($data, 20).
			'}';
	}

	protected static function checkGUID($data)
	{
		$data = str_replace(array('{', '-', '}'), '', $data);
		if (strlen($data) !== 32 || preg_match('/[^a-z0-9]/i', $data)) return false;
		else return $data;
	}

	protected function InitImage($imageID, $imageWidth, $imageHeight = 0)
	{
		$imageFile = false;
		$imageImg = "";

		if(($imageWidth = intval($imageWidth)) <= 0) $imageWidth = 100;
		if(($imageHeight = intval($imageHeight)) <= 0) $imageHeight = $imageWidth;

		$imageID = intval($imageID);

		if($imageID > 0)
		{
			$imageFile = CFile::GetFileArray($imageID);
			if ($imageFile !== false)
			{
				$arFileTmp = CFile::ResizeImageGet(
					$imageFile,
					array("width" => $imageWidth, "height" => $imageHeight),
					BX_RESIZE_IMAGE_PROPORTIONAL,
					false, false, true
				);
				$imageImg = CFile::ShowImage($arFileTmp["src"], $imageWidth, $imageHeight, "border=0", "");
			}
		}

		return array("FILE" => $imageFile, "CACHE" => $arFileTmp, "IMG" => $imageImg);
	}
}

?>
