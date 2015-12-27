<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage ldap
 * @copyright 2001-2014 Bitrix
 */

IncludeModuleLangFile(__FILE__);

class CLDAP
{
	var $arFields, $arGroupList = false;
	var $conn;

	protected static $PHOTO_ATTRIBS = array("thumbnailPhoto", "jpegPhoto");
	protected $arGroupMaps;

	public static function Connect($arFields = Array())
	{
		if(!is_object($this))
		{
			$ldap = new CLDAP();
			$ldap->arFields = $arFields;
			if($ldap->Connect())
				return $ldap;
			return false;
		}

		if($this->conn = @ldap_connect($this->arFields["SERVER"], $this->arFields['PORT']))
		{
			@ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3);
			@ldap_set_option($this->conn, LDAP_OPT_REFERRALS, 0);
			@ldap_set_option($this->conn, LDAP_OPT_SIZELIMIT, COption::GetOptionInt("ldap", "group_limit", 0));
			@ldap_set_option($this->conn, LDAP_OPT_TIMELIMIT, 100);
			@ldap_set_option($this->conn, LDAP_OPT_TIMEOUT, 5);
			@ldap_set_option($this->conn, LDAP_OPT_NETWORK_TIMEOUT, 5);

			if(@ldap_bind($this->conn))
				return true;
		}
		return false;
	}

	public function BindAdmin()
	{
		if(strlen($this->arFields["ADMIN_LOGIN"])<=0)
			return false;

		$r = @ldap_bind($this->conn,
			(isset($this->arFields["~ADMIN_LOGIN"])?$this->arFields["~ADMIN_LOGIN"]:$this->arFields["ADMIN_LOGIN"]),
			(isset($this->arFields["~ADMIN_PASSWORD"])?$this->arFields["~ADMIN_PASSWORD"]:$this->arFields["ADMIN_PASSWORD"])
			);
		return $r;
	}

	public function Bind($login, $password)
	{
		global $APPLICATION;

		if($this->arFields["CONVERT_UTF8"]=="Y")
		{
			$login = $APPLICATION->ConvertCharset($login, SITE_CHARSET, "utf-8");
			$password = $APPLICATION->ConvertCharset($password, SITE_CHARSET, "utf-8");
		}

		if(strpos($password, "\0") !== false || strlen($password) <= 0)
			$r = false;
		else
			$r = @ldap_bind($this->conn, $login, $password);

		return $r;
	}

	public function Disconnect()
	{
		ldap_close($this->conn);
	}

	public function RootDSE()
	{
		$values = $this->_RootDSE('namingcontexts');
		if ($values == false)
			$values = $this->_RootDSE('namingContexts');
		return $this->WorkAttr($values);
	}

	public function _RootDSE($filtr)
	{
		$sr = ldap_read($this->conn, '', 'objectClass=*', Array($filtr), 0);
		//$sr = ldap_read($this->conn, '', 'objectClass=*');
		$entry = ldap_first_entry($this->conn, $sr);

		$attributes = ldap_get_attributes($this->conn, $entry);
		$values = false;

		if ($attributes['count'] > 0)
			$values = @ldap_get_values_len($this->conn, $entry, $filtr);
		return $values;
	}

	public function WorkAttr($values)
	{
		global $APPLICATION;

		if(is_array($values) && $values['count']==1)
		{
			if($this->arFields["CONVERT_UTF8"]=="Y")
				return $APPLICATION->ConvertCharset($values[0], "utf-8", SITE_CHARSET);

			return $values[0];
		}

		unset($values['count']);

		if($this->arFields["CONVERT_UTF8"]=="Y")
			foreach($values as $key=>$val)
				$values[$key] = $APPLICATION->ConvertCharset($val, "utf-8", SITE_CHARSET);

		return $values;
	}

	public function QueryArray($str = '(ObjectClass=*)', $fields = false)
	{
		global $APPLICATION;

		if(strlen($this->arFields['BASE_DN'])<=0)
			return false;

		$arBaseDNs = explode(";", $this->arFields['BASE_DN']);
		$info = false;
		$i=0;

		if($this->arFields["CONVERT_UTF8"] == "Y")
			$str = $APPLICATION->ConvertCharset($str, SITE_CHARSET, "utf-8");

		foreach($arBaseDNs as $BaseDN)
		{
			global $APPLICATION;

			$BaseDN = trim($BaseDN);
			if($BaseDN == "")
				continue;

			if($this->arFields["CONVERT_UTF8"]=="Y")
				$BaseDN = $APPLICATION->ConvertCharset($BaseDN, SITE_CHARSET, "utf-8");

			$defaultMaxPageSizeAD = 1000;
			$pageSize = isset($this->arFields['MAX_PAGE_SIZE']) && intval($this->arFields['MAX_PAGE_SIZE'] > 0) ? intval($this->arFields['MAX_PAGE_SIZE']) : $defaultMaxPageSizeAD;
			$cookie = '';

			do
			{
				if(CLdapUtil::isLdapPaginationAviable())
					ldap_control_paged_result($this->conn, $pageSize, false, $cookie);

				if($fields === false)
					$sr = @ldap_search($this->conn, $BaseDN, $str);
				else
					$sr = @ldap_search($this->conn, $BaseDN, $str, $fields);

				if($sr)
				{
					$entry = ldap_first_entry($this->conn, $sr);

					if($entry)
					{
						if(!is_array($info))
						{
							$info = Array();
							$i=0;
						}

						do
						{
							$attributes = ldap_get_attributes($this->conn, $entry);

							for($j=0; $j<$attributes['count']; $j++)
							{
								$values = @ldap_get_values_len($this->conn, $entry, $attributes[$j]);

								if($values === false)
									continue;

								$bPhotoAttr = in_array($attributes[$j], self::$PHOTO_ATTRIBS);
								$info[$i][strtolower($attributes[$j])] = $bPhotoAttr ? $values : $this->WorkAttr($values);
							}
							if(!is_set($info[$i], 'dn'))
							{
								if($this->arFields["CONVERT_UTF8"]=="Y")
									$info[$i]['dn'] = $APPLICATION->ConvertCharset(ldap_get_dn($this->conn, $entry), "utf-8", SITE_CHARSET);
								else
									$info[$i]['dn'] = ldap_get_dn($this->conn, $entry);
							}
							$i++;

						}
						while($entry = ldap_next_entry($this->conn, $entry));
					}
				}
				elseif($sr === false)
				{
					$APPLICATION->ThrowException("LDAP_SEARCH_ERROR");
				}

				if(CLdapUtil::isLdapPaginationAviable())
					ldap_control_paged_result_response($this->conn, $sr, $cookie);

			} while($cookie !== null && $cookie != '');
		}

		return $info;
	}

	public function Query($str = '(ObjectClass=*)', $fields = false)
	{
		$info = $this->QueryArray($str, $fields);
		$result = new CDBResult;
		$result->InitFromArray($info);

		return $result;
	}

	protected function setFieldAsAttr(array $attrArray, $fieldName)
	{
		$field = isset($this->arFields["~".$fieldName]) ? $this->arFields["~".$fieldName] : $this->arFields[$fieldName];
		$field = strtolower($field);

		if(!in_array($field, $attrArray))
			$attrArray[] = $field;

		return $attrArray;
	}

	// query for group list from AD - server
	public function GetGroupListArray($query = '')
	{
		static $groupslists = array();

		$group_filter = $this->arFields['GROUP_FILTER'];
		if(strlen(trim($group_filter))>0 && substr(trim($group_filter), 0, 1)!='(')
			$group_filter = '('.trim($group_filter).')';
		$query = '(&'.$group_filter.$query.')';

		if (!array_key_exists($query, $groupslists))
		{
			$this->BindAdmin();

			$arGroupAttr = array(
				"name", "cn", "gidNumber", "description", "memberof",
				"primarygrouptoken", "primarygroupid", "samaccountname",
				"distinguishedname"
			);

			foreach(array("GROUP_ID_ATTR", "GROUP_NAME_ATTR", "GROUP_MEMBERS_ATTR") as $fieldName)
				$arGroupAttr = $this->setFieldAsAttr($arGroupAttr, $fieldName);

			if ($this->arFields['USER_GROUP_ACCESSORY'] == 'Y')
				$arGroupAttr = $this->setFieldAsAttr($arGroupAttr, "USER_GROUP_ATTR");

			$arGroupsTmp = $this->QueryArray($query, $arGroupAttr);

			if (!$arGroupsTmp)
				return false;

			$arGroups = array();
			$group_id_attr = strtolower($this->arFields['GROUP_ID_ATTR']);

			if(is_set($this->arFields, 'GROUP_NAME_ATTR'))
				$group_name_attr = strtolower($this->arFields['GROUP_NAME_ATTR']);
			else
				$group_name_attr = false;

			foreach ($arGroupsTmp as $grp)
			{
				$grp['ID'] = $grp[$group_id_attr];

				if ($group_name_attr && is_set($grp, $group_name_attr))
					$grp['NAME'] = $grp[$group_name_attr];

				$arGroups[$grp['ID']] = $grp;
			}

			$groupslists[$query] = $arGroups;
		}

		return $groupslists[$query];
	}

	public function GetGroupList($query = '')
	{
		$arGroups = $this->GetGroupListArray($query);
		$result = new CDBResult();
		$result->InitFromArray($arGroups);

		return $result;
	}

	public static function OnUserLogin($arArgs)
	{
		global $APPLICATION;

		if(!function_exists("ldap_connect"))
			return false;

		$LOGIN = $arArgs["LOGIN"];
		$PASSWORD = $arArgs["PASSWORD"];

		if(strlen($LOGIN)<=0 || strlen($PASSWORD)<=0)
			return false;

		$arFilter = Array("ACTIVE"=>"Y");
		$p = strpos($LOGIN, "\\");

		if( $p===false && COption::GetOptionString("ldap", "ntlm_auth_without_prefix", "Y") != "Y")
		{
			return false;
		}
		elseif( $p > 0 )
		{
			$arFilter["CODE"] = substr($LOGIN, 0, $p);
			$LOGIN = substr($LOGIN, $p+1);
		}

		$arParams = Array(
			"LOGIN" => &$LOGIN,
			"PASSWORD" => &$PASSWORD,
			"LDAP_FILTER" => &$arFilter,
		);

		$APPLICATION->ResetException();
		foreach(GetModuleEvents("ldap", "OnBeforeUserLogin", true) as $arEvent)
		{
			// TODO check whether wrapping of &$arParams into another array is reasonable as part of migration from ExecuteModuleEvent to ExecuteModuleEventEx
			if(ExecuteModuleEventEx($arEvent, array(&$arParams))===false)
			{
				if($err = $APPLICATION->GetException())
				{
					$result_message = Array("MESSAGE"=>$err->GetString()."<br>", "TYPE"=>"ERROR");
				}
				else
				{
					$APPLICATION->ThrowException("Unknown error");
					$result_message = Array("MESSAGE"=>"Unknown error"."<br>", "TYPE"=>"ERROR");
				}

				return false;
			}
		}

		$db_ldap_serv = CLdapServer::GetList(Array(), $arFilter);

		while($xLDAP = $db_ldap_serv->GetNextServer())
		{
			if($xLDAP->Connect())
			{
				// user AD parameters are queried here, inside FindUser function
				if($arLdapUser = $xLDAP->FindUser($LOGIN, $PASSWORD))
				{
					$ID = $xLDAP->SetUser($arLdapUser, (COption::GetOptionString("ldap", "add_user_when_auth", "Y")=="Y"));

					if($ID > 0)
					{
						$arArgs["STORE_PASSWORD"] = "N";
						$xLDAP->Disconnect();
						return $ID;
					}
				}

				$xLDAP->Disconnect();
			}
		}

		return false;
	}

	// this function is called on user logon (either normal or ntlm) to find user in ldap
	public function FindUser($LOGIN, $PASSWORD = false)
	{
		$login_field = $LOGIN;
		$password_field = $PASSWORD;

		$this->BindAdmin();

		$user_filter = "(&".$this->arFields["~USER_FILTER"]."(".$this->arFields["~USER_ID_ATTR"]."=".$this->specialchars($login_field)."))";
		$dbLdapUsers = $this->Query($user_filter);
		if (!$dbLdapUsers)
			return false;

		if($arLdapUser = $dbLdapUsers->Fetch())
		{
			if($PASSWORD !== false) // also check auth
			{
				$user_dn = $arLdapUser['dn'];

				if (!$this->Bind($user_dn, $password_field))
					return false;
			}

			return $this->GetUserFields($arLdapUser);
		}

		return false;
	}
	/**
	 * Returns value of ldap user field mapped to bitrix field.
	 * @param string $fieldName Name of user field in Bitrix system.
	 * @param array $arLdapUser User params received from ldap.
	 * @return mixed.
	 */
	public function getLdapValueByBitrixFieldName($fieldName, $arLdapUser)
	{
		global $USER_FIELD_MANAGER;
		if(!isset($this->arFields["FIELD_MAP"][$fieldName]))
			return false;

		$attr = $this->arFields["FIELD_MAP"][$fieldName];
		$arRes = $USER_FIELD_MANAGER->GetUserFields("USER", 0, LANGUAGE_ID);
		$result = false;

		if(is_array($arRes[$fieldName]))
		{
			if($arRes[$fieldName]["MULTIPLE"]=="Y")
			{
				if (is_array($arLdapUser[strtolower($attr)]))
					$result = array_values($arLdapUser[strtolower($attr)]);
				else
					$result = array($arLdapUser[strtolower($attr)]);
			}
			else if (!empty($arLdapUser[strtolower($attr)]))
				$result = $arLdapUser[strtolower($attr)];
			else if (!empty($arRes[$fieldName]['SETTINGS']['DEFAULT_VALUE']))
			{
				if (is_array($arRes[$fieldName]['SETTINGS']['DEFAULT_VALUE']))
				{
					if (!empty($arRes[$fieldName]['SETTINGS']['DEFAULT_VALUE']['VALUE']))
						$result = $arRes[$fieldName]['SETTINGS']['DEFAULT_VALUE']['VALUE'];
				}
				else
					$result = $arRes[$fieldName]['SETTINGS']['DEFAULT_VALUE'];
			}

		}
		elseif(preg_match("/(.*)&([0-9]+)/", $attr, $arMatch))
		{
			if(intval($arLdapUser[strtolower($arMatch[1])]) & intval($arMatch[2]))
				$result = "N";
			else
				$result = "Y";
		}
		elseif ($fieldName == "PERSONAL_PHOTO")
		{
			if($arLdapUser[strtolower($attr)] == "")
				return false;

			$fExt = CLdapUtil::GetImgTypeBySignature($arLdapUser[strtolower($attr)][0]);

			if(!$fExt)
				return false;

			$tmpDir = CTempFile::GetDirectoryName();
			CheckDirPath($tmpDir);

			$fname = "ad_".rand().".".$fExt;

			if(!file_put_contents($tmpDir.$fname,$arLdapUser[strtolower($attr)][0]))
				return false;

			$result = array(
				"name" => $fname,
				"type" => CFile::GetContentType($tmpDir.$fname),
				"tmp_name" => $tmpDir.$fname
			);
		}
		else
			$result = $arLdapUser[strtolower($attr)];

		if(is_null($result))
			$result = false;

		return $result;
	}


	// converts LDAP values to those suitable for user fields
	public function GetUserFields($arLdapUser, &$departmentCache=FALSE)
	{
		global $APPLICATION;

		$arFields = array(
			'DN'				=> $arLdapUser['dn'],
			'LOGIN'				=> $arLdapUser[strtolower($this->arFields['~USER_ID_ATTR'])],
			'EXTERNAL_AUTH_ID'	=> 'LDAP#'.$this->arFields['ID'],
			'LDAP_GROUPS'		=> $arLdapUser[strtolower($this->arFields['~USER_GROUP_ATTR'])],
		);

		// for each field, do the conversion

		foreach($this->arFields["FIELD_MAP"] as $userField=>$attr)
			$arFields[$userField] = $this->getLdapValueByBitrixFieldName($userField, $arLdapUser);

		$APPLICATION->ResetException();
		$db_events = GetModuleEvents("ldap", "OnLdapUserFields");
		while($arEvent = $db_events->Fetch())
		{
			$arParams = array(array(&$arFields, $arLdapUser));
			if(ExecuteModuleEventEx($arEvent, $arParams)===false)
			{
				if(!($err = $APPLICATION->GetException()))
					$APPLICATION->ThrowException("Unknown error");
				return false;
			}
			$arFields = $arParams[0][0];
		}

		// set a department field, if needed
		if (empty($arFields['UF_DEPARTMENT']) && isModuleInstalled('intranet')
			&& $this->arFields['IMPORT_STRUCT'] && $this->arFields['IMPORT_STRUCT']=='Y')
		{
			//$arLdapUser[$this->arFields['USER_DN_ATTR']]
			$username = $arLdapUser[$this->arFields['USER_ID_ATTR']];
			if ($arDepartment = $this->GetDepartmentIdForADUser($arLdapUser[$this->arFields['USER_DEPARTMENT_ATTR']],$arLdapUser[$this->arFields['USER_MANAGER_ATTR']],$username,$departmentCache))
			{
				// fill in cache. it is done outside the function because it has many exit points
				if ($departmentCache)
					$departmentCache[$username] = $arDepartment;

				// this is not final assignment
				// $arFields['UF_DEPARTMENT'] sould contain array of department ids
				// but somehow we have to return an information whether this user is a department head
				// so we'll save this data here temporarily
				$arFields['UF_DEPARTMENT'] = $arDepartment;
			}
			else
				$arFields['UF_DEPARTMENT'] = array();

			// at this point $arFields['UF_DEPARTMENT'] should be set to some value, even an empty array is ok
		}

		if (!is_array($arFields['LDAP_GROUPS']))
			$arFields['LDAP_GROUPS'] = (!empty($arFields['LDAP_GROUPS']) ? array($arFields['LDAP_GROUPS']) : array());

		$primarygroupid_name_attr = 'primarygroupid';
		$primarygrouptoken_name_attr = 'primarygrouptoken';

		$groupMemberAttr = null;
		$userIdAttr = null;

		if ($this->arFields['USER_GROUP_ACCESSORY'] == 'Y')
		{
			$primarygroupid_name_attr = strtolower($this->arFields['GROUP_ID_ATTR']);
			$primarygrouptoken_name_attr = strtolower($this->arFields['USER_GROUP_ATTR']);
			$userIdAttr = strtolower($this->arFields['USER_ID_ATTR']);
			$groupMemberAttr = strtolower($this->arFields['GROUP_MEMBERS_ATTR']);
		}

		$arAllGroups = $this->GetGroupListArray();

		if (!is_array($arAllGroups) || count($arAllGroups) <= 0)
			return $arFields;

		$arGroup = reset($arAllGroups);

		do
		{
			if(in_array($arGroup['ID'], $arFields['LDAP_GROUPS']))
				continue;

			if	(
					(is_set($arLdapUser, $primarygroupid_name_attr)
					&& $arGroup[$primarygrouptoken_name_attr] == $arLdapUser[$primarygroupid_name_attr]
					)
					||
					($this->arFields['USER_GROUP_ACCESSORY'] == 'Y'
					&& is_set($arGroup, $groupMemberAttr)
					&& (
							(is_array($arGroup[$groupMemberAttr])
							&& in_array($arLdapUser[$userIdAttr], $arGroup[$groupMemberAttr])
							)
						||
						$arLdapUser[$userIdAttr] == $arGroup[$groupMemberAttr]
						)
					)
				)

			{
				$arFields['LDAP_GROUPS'][] = $arGroup['ID'];
				if ($this->arFields['USER_GROUP_ACCESSORY'] == 'N')
					break;
			}
		}
		while ($arGroup = next($arAllGroups));

		return $arFields;
	}

	// Gets department ID for AD user. If department doesn't exist, creates a new one. Returns FALSE if there should be no department set.
	// returns array:
	// 'ID' - department id
	// 'IS_HEAD' - true if this user is head of the department, false if not
	public function GetDepartmentIdForADUser($department, $managerDN, $username, &$cache=FALSE, $iblockId = FALSE, $names = FALSE)
	{
		global $USER_FIELD_MANAGER;

		// check for loops in manager structure, if loop is found - quit
		// should be done before cache lookup
		if ($names && isset($names[$username]))
			return false;

		// if department id for this user is already stored in cache
		if ($cache)
		{
			$departmentCached = $cache[$username];
			// if user was not set as head earlier, then do not get his id from cache
			if ($departmentCached)
				return $departmentCached;
		}

		// if it is a first call in recursive chain
		if (!$iblockId)
		{
			// check module inclusions
			if (!IsModuleInstalled('intranet') || !CModule::IncludeModule('iblock'))
				return false;

			// get structure's iblock id
			$iblockId=COption::GetOptionInt("intranet", "iblock_structure",  false, false);
			if (!$iblockId)
				return false;

			$names = array();
		}

		// save current username as already visited
		$names[$username] = true;

		$arManagerDep = null;
		$mgrDepartment = null;

		// if there's a manager - query it
		if ($managerDN)
		{
			preg_match('/^(CN=.*?)(\,){1}([^\,])*(=){1}/i', $managerDN, $matches); //Extract "CN=User Name" from full name
			$user = isset($matches[1]) ? str_replace('\\', '',$matches[1]) : "";
			$userArr = $this->GetUserArray($user);

			if (count($userArr)>0)
			{
				// contents of userArr are already in local encoding, no need for conversion here
				$mgrDepartment = $userArr[0][$this->arFields['USER_DEPARTMENT_ATTR']];
				if ($mgrDepartment && trim($mgrDepartment)!='')
				{
					// if manager's department name is set - then get it's id
					$mgrManagerDN = $userArr[0][$this->arFields['USER_MANAGER_ATTR']];
					$mgrUserName = $userArr[0][$this->arFields['USER_ID_ATTR']];
					$arManagerDep = $this->GetDepartmentIdForADUser($mgrDepartment, $mgrManagerDN, $mgrUserName, $cache, $iblockId, $names);
					// fill in cache
					if ($cache && $arManagerDep)
						$cache[$mgrUserName] = $arManagerDep;
				}
			}
		}

		// prepare result and create department (if needed)
		$arResult = array('IS_HEAD'=>true); // by default, thinking of user as a head of the department

		if ($arManagerDep)
		{
			// if got manager's data correctly
			if ($department && trim($department)!='' && ($mgrDepartment!=$department))
			{
				// if our department is set && differs from manager's, set manager's as parent
				$parentSectionId = $arManagerDep['ID'];
			}
			else
			{
				// - if user has no department, but somehow have manager - then he is assumed to be in manager's department
				// - if user has same department name as manager - then he is not head
				// here we can return manager's department id immediately
				$arResult = $arManagerDep;
				$arResult['IS_HEAD'] = false;
				return $arResult;
			}
		}
		else
		{
			// if there's no manager's data
			if ($department && trim($department)!='')
			{
				$parentSectionId = $this->arFields['ROOT_DEPARTMENT'];
			}
			else
			{
				// if have no manager's department and no own department:
				// - use default as our department and root as parent section if default is set
				// - or just root if default has empty value
				// - or return false, if setting of default department is turned off
				if ($this->arFields['STRUCT_HAVE_DEFAULT'] && $this->arFields['STRUCT_HAVE_DEFAULT'] == "Y")
				{
					// if can use default department
					$department = $this->arFields['DEFAULT_DEPARTMENT_NAME'];
					if ($department && trim($department)!='')
					{
						// if department is not empty
						$parentSectionId = $this->arFields['ROOT_DEPARTMENT'];
					}
					else
					{
						// if it is empty - return parent
						return array('ID' => $this->arFields['ROOT_DEPARTMENT']);
					}
				}
				else
				{
					// if have no department in AD and no default - then do not set a department
					return false;
				}
			}
		}
		// 3. if there's no department set for this user, this means there was no default department name (which substituted in *) - then there's no need to set department id for this user at all
		if (!$department || trim($department)=='')
			return false;

		// 4. detect this user's department ID, using parent id and department name string, which we certainly have now (these 2 parameters are required to get an ID)

		// see if this department already exists
		$bs = new CIBlockSection();
		$dbExistingSections = GetIBlockSectionList(
			$iblockId,
			($parentSectionId >= 0 ? $parentSectionId : false),
			$arOrder = Array("left_margin" => "asc"),
			$cnt = 0,
			$arFilter = Array('NAME' => $department)
		);

		$departmentId = false;
		if($arItem = $dbExistingSections->GetNext())
			$departmentId = $arItem['ID'];
		if (!$departmentId)
		{
			//create new department
			$arNewSectFields = Array(
				"ACTIVE" => "Y",
				"IBLOCK_ID" => $iblockId,
				"NAME" => $department
			);
			if ($parentSectionId>=0)
				$arNewSectFields["IBLOCK_SECTION_ID"] = $parentSectionId;
			// and get it's Id
			$departmentId = $bs->Add($arNewSectFields);
		}

		$arElement = $USER_FIELD_MANAGER->GetUserFields(
			'IBLOCK_'.$iblockId.'_SECTION',
			$departmentId
		);

		// if the head of the department is already set, do not change it
		if (!empty($arElement['UF_HEAD']['VALUE']))
			$arResult['IS_HEAD'] = false;

		$arResult['ID'] = $departmentId;
		return $arResult;
	}


	// get user list (with attributes) from AD server
	public function GetUserList($arFilter = Array())
	{
		$query = '';
		foreach($arFilter as $key=>$value)
		{
			$key = strtoupper($key);
			switch($key)
			{
				case 'GROUP_ID':
					//"SELECT ".
					//	"FROM "

				case 'GROUP_DN':
					$temp = '';
					$temp_cnt = 0;
					if(!is_array($value))
						$value = array($value);
					foreach($value as $group)
					{
						if(strlen($group)<=0)
							continue;
						$temp_cnt++;
						$temp .= '('.$this->arFields['USER_GROUP_ATTR'].'='.$this->specialchars($group).')';
					}
					$query .= '(|'.$temp.')';
					break;
			}
		}

		$user_filter = $this->arFields['USER_FILTER'];
		if(strlen(trim($user_filter))>0 && substr(trim($user_filter), 0, 1)!='(')
			$user_filter = '('.trim($user_filter).')';
		$query = '(&'.$user_filter.$query.')';
		$arResult = $this->Query($query);
		return $arResult;
	}

	public function GetUserArray($cn)
	{
		$user_filter = $this->arFields['USER_FILTER'];
		if(strlen(trim($user_filter))>0 && substr(trim($user_filter), 0, 1)!='(')
			$user_filter = '('.trim($user_filter).')';
		$query = '(&'.$user_filter.'('.$cn.'))';

		return $this->QueryArray($query);
	}

	public static function specialchars($str)
	{
		$from = Array("\\", ',', '+', '"', '<', '>', ';', "\n", "\r", '=', '*');
		$to = Array('\5C', '\2C', '\2B', '\22', '\3C', '\3E', '\3B', '\0A', '\0D', '\3D', '\*');
		return str_replace($from, $to, $str);
	}

	public static function OnExternalAuthList()
	{
		$arResult = Array();
		$db_ldap_serv = CLdapServer::GetList();
		while($arLDAP = $db_ldap_serv->Fetch())
		{
			$arResult[] = Array(
				'ID' => 'LDAP#'.$arLDAP['ID'],
				'NAME' => $arLDAP['NAME']
			);
		}
		return $arResult;
	}

	public static function NTLMAuth()
	{
		global $USER;

		if ($USER->IsAuthorized())
			return;

		if(!array_key_exists("AUTH_TYPE", $_SERVER) || $_SERVER["AUTH_TYPE"] != "NTLM")
			return;

		$ntlm_varname = trim(COption::GetOptionString('ldap', 'ntlm_varname', 'REMOTE_USER'));

		if (array_key_exists($ntlm_varname, $_SERVER) && strlen($LOGIN = $_SERVER[$ntlm_varname]) > 0)
		{
			$DOMAIN = "";
			if (($pos = strpos($LOGIN, "\\")) !== false)
			{
				$DOMAIN = substr($LOGIN, 0, $pos);
				$LOGIN = substr($LOGIN, $pos + 1);
			}
			$arFilterServer = array('ACTIVE' => 'Y');
			if (strlen($DOMAIN) > 0)
				$arFilterServer['CODE'] = $DOMAIN;
			else
			{
				$DEF_DOMAIN_ID = intval(COption::GetOptionInt('ldap', 'ntlm_default_server', 0));
				if($DEF_DOMAIN_ID > 0)
					$arFilterServer['ID'] = $DEF_DOMAIN_ID;
				else
					return;
			}

			$db_ldap_serv = CLdapServer::GetList(Array(), $arFilterServer);

			/*@var $xLDAP CLDAP*/
			while($xLDAP = $db_ldap_serv->GetNextServer())
			{
				if($xLDAP->Connect())
				{
					if($arLdapUser = $xLDAP->FindUser($LOGIN))
					{
						$ID = $xLDAP->SetUser($arLdapUser);

						if($ID > 0)
						{
							$USER->Authorize($ID);
							$xLDAP->Disconnect();
							return;
						}
					}

					$xLDAP->Disconnect();
				}
			}
		}
	}

	/**
	 *
	 * Recieves the users groups list includes all groups parents list
	 * searching by memberOf in group properties
	 * @param $arFindGroups - user groups
	 * @param $arUserGroups - full array with uppergroups
	 * @param $arAllGroups - list of all ldap groups
	 */
	public function GetAllMemberOf($arFindGroups, &$arUserGroups, $arAllGroups)
	{
		if(!$arFindGroups || $arFindGroups=='')
			return;

		if(!is_array($arFindGroups))
			$arFindGroups = Array($arFindGroups);

		foreach($arFindGroups as $group_id)
		{
			if(in_array($group_id, $arUserGroups))
				continue;

			$arUserGroups[] = $group_id;
			$this->GetAllMemberOf($arAllGroups[$group_id]["memberof"], $arUserGroups, $arAllGroups);
		}
	}

	public function GetGroupMaps()
	{
		global $DB;

		if(!is_array($this->arGroupMaps))
		{
			$this->arGroupMaps = array();
			$rsCorellations = $DB->Query("SELECT LDAP_GROUP_ID, GROUP_ID FROM b_ldap_group WHERE LDAP_SERVER_ID=".intval($this->arFields['ID']));

			while ($arCorellation = $rsCorellations->Fetch())
			{
				if(!is_array($this->arGroupMaps[$arCorellation["LDAP_GROUP_ID"]]))
					$this->arGroupMaps[$arCorellation["LDAP_GROUP_ID"]] = array();

				$this->arGroupMaps[$arCorellation["LDAP_GROUP_ID"]][] = $arCorellation["GROUP_ID"];
			}
		}

		return $this->arGroupMaps;
	}

	//Need this to delete old photo
	static function PrepareUserPhoto($uid, &$arLdapUser)
	{
		if(!isset($arLdapUser["PERSONAL_PHOTO"]))
			return false;

		$dbRes = CUser::GetById($uid);
		$arUser = $dbRes->Fetch();

		if(!isset($arUser["PERSONAL_PHOTO"]) || is_null($arUser["PERSONAL_PHOTO"]))
			return false;

		if($arLdapUser["PERSONAL_PHOTO"] == "")
			$arLdapUser["PERSONAL_PHOTO"]["del"] = "Y";

		$arLdapUser["PERSONAL_PHOTO"]["old_file"] = $arUser["PERSONAL_PHOTO"];

		return true;
	}

	// update user info, using previously loaded data from AD, make additional calls to AD if needed
	public function SetUser($arLdapUser, $bAddNew = true)
	{
		global $USER;

		$isHead = false;
		$bUSERGen = false;

		if(!is_object($USER))
		{
			$USER = new CUser();
			$bUSERGen = true;
		}

		// process previously saved department data
		if (IsModuleInstalled('intranet') && is_array($arLdapUser['UF_DEPARTMENT']))
		{
			$isHead = $arLdapUser['UF_DEPARTMENT']['IS_HEAD'];
			// replace temporary value with a real one
			$arLdapUser['UF_DEPARTMENT'] = array($arLdapUser['UF_DEPARTMENT']['ID']);
		}

		if(isset($arLdapUser["ID"]))
		{
			$ID = intval($arLdapUser["ID"]);
			self::PrepareUserPhoto($ID,$arLdapUser);
			$USER->Update($ID, $arLdapUser);
		}
		else
		{
			$ldapUserID = 0;
			if (isset($_REQUEST["ldap_user_id"]) && strlen($_REQUEST["ldap_user_id"]) == 32)
			{
				$dbUser = CUser::GetList($O="", $B="", Array("XML_ID" => $_REQUEST["ldap_user_id"], 'EXTERNAL_AUTH_ID' => $arLdapUser['EXTERNAL_AUTH_ID']));
				if ($arUser = $dbUser->Fetch())
				{
					if($arUser["XML_ID"])
						$ldapUserID = $arUser["ID"];
				}
			}

			$res = CUser::GetList($O="", $B="", Array('LOGIN_EQUAL_EXACT' => $arLdapUser['LOGIN'], 'EXTERNAL_AUTH_ID' => $arLdapUser['EXTERNAL_AUTH_ID']));
			$arLdapUser['PASSWORD'] = uniqid(rand(), true);

			if(!($ar_res = $res->Fetch()) && $ldapUserID < 1)
			{
				if($bAddNew)
				{
					if(strlen($arLdapUser["EMAIL"])<=0)
					{
						$arLdapUser["EMAIL"] = COption::GetOptionString("ldap", "default_email", 'no@email');
					}
					$ID = $USER->Add($arLdapUser);
				}
				else
				{
					$ID = 0;
				}
			}
			else
			{
				$ID = ($ldapUserID > 1 ? $ldapUserID : $ar_res['ID']);
				self::PrepareUserPhoto($ID,$arLdapUser);
				$USER->Update($ID, $arLdapUser);
			}

			$ID = intval($ID);
		}

		// - add this user to groups
		if ($ID > 0)
		{
			// - set as head of department
			if (IsModuleInstalled('intranet') && $isHead)
			{
				CLdapUtil::SetDepartmentHead($ID,$arLdapUser['UF_DEPARTMENT'][0]);
			}

			// For each group finding all superior ones
			$arUserLdapGroups = Array();
			$arLdapGroups = $this->GetGroupListArray();
			$this->GetAllMemberOf($arLdapUser['LDAP_GROUPS'], $arUserLdapGroups, $arLdapGroups);

			$arGroupMaps = $this->GetGroupMaps();
			$arUserBitrixGroups = $USER->GetUserGroup($ID);
			$arUserBitrixGroupsNew = array();

			$prevGroups = $arUserBitrixGroups;
			sort($prevGroups);

			foreach($arGroupMaps as $fromLdapGroup=>$arToUserGroups)
			{
				foreach($arToUserGroups as $toUserGroup)
				{
					if (($k = array_search($toUserGroup, $arUserBitrixGroups)) !== false)
					{
						unset($arUserBitrixGroups[$k]);
					}

					// If there is such a group among user's
					if (in_array($fromLdapGroup, $arUserLdapGroups))
					{
						$arUserBitrixGroupsNew[] = $toUserGroup;
					}
				}
			}
			$arUserBitrixGroups = array_merge($arUserBitrixGroups, array_unique($arUserBitrixGroupsNew));
			sort($arUserBitrixGroups);

			if($arUserBitrixGroups <> $prevGroups)
			{
				$USER->SetUserGroup($ID, $arUserBitrixGroups);
			}
		}

		if($bUSERGen)
		{
			unset($USER);
		}

		return $ID;
	}
}

