<?
IncludeModuleLangFile(__FILE__);

class CCloudStorageService_OpenStackStorage extends CCloudStorageService
{
	protected $status = 0;
	protected $errno = 0;
	protected $errstr = '';
	protected $result = '';
	protected $verb = '';
	protected $url = '';

	public function GetLastRequestStatus()
	{
		return $this->status;
	}

	public static function GetObject()
	{
		return new CCloudStorageService_OpenStackStorage();
	}

	public static function GetID()
	{
		return "openstack_storage";
	}

	public static function GetName()
	{
		return "OpenStack Object Storage";
	}

	public static function GetLocationList()
	{
		return array(
			"" => "N/A",
		);
	}

	public function GetSettingsHTML($arBucket, $bServiceSet, $cur_SERVICE_ID, $bVarsFromForm)
	{
		if($bVarsFromForm)
			$arSettings = $_POST["SETTINGS"][$this->GetID()];
		else
			$arSettings = unserialize($arBucket["SETTINGS"]);

		if(!is_array($arSettings))
		{
			$arSettings = array(
				"HOST" => "",
				"USER" => "",
				"KEY" => "",
				"FORCE_HTTP" => "N",
			);
		}

		$htmlID = htmlspecialcharsbx($this->GetID());
		$show = (($cur_SERVICE_ID == $this->GetID()) || !$bServiceSet)? '': 'none';

		$result = '
		<tr id="SETTINGS_2_'.$htmlID.'" style="display:'.$show.'" class="settings-tr adm-detail-required-field">
			<td>'.GetMessage("CLO_STORAGE_OPENSTACK_EDIT_HOST").':</td>
			<td><input type="hidden" name="SETTINGS['.$htmlID.'][HOST]" id="'.$htmlID.'HOST" value="'.htmlspecialcharsbx($arSettings['HOST']).'"><input type="text" size="55" name="'.$htmlID.'INP_HOST" id="'.$htmlID.'INP_HOST" value="'.htmlspecialcharsbx($arSettings['HOST']).'" '.($arBucket['READ_ONLY'] == 'Y'? '"disabled"': '').' onchange="BX(\''.$htmlID.'HOST\').value = this.value"></td>
		</tr>
		<tr id="SETTINGS_0_'.$htmlID.'" style="display:'.$show.'" class="settings-tr adm-detail-required-field">
			<td>'.GetMessage("CLO_STORAGE_OPENSTACK_EDIT_USER").':</td>
			<td><input type="hidden" name="SETTINGS['.$htmlID.'][USER]" id="'.$htmlID.'USER" value="'.htmlspecialcharsbx($arSettings['USER']).'"><input type="text" size="55" name="'.$htmlID.'INP_" id="'.$htmlID.'INP_USER" value="'.htmlspecialcharsbx($arSettings['USER']).'" '.($arBucket['READ_ONLY'] == 'Y'? '"disabled"': '').' onchange="BX(\''.$htmlID.'USER\').value = this.value"></td>
		</tr>
		<tr id="SETTINGS_1_'.$htmlID.'" style="display:'.$show.'" class="settings-tr adm-detail-required-field">
			<td>'.GetMessage("CLO_STORAGE_OPENSTACK_EDIT_KEY").':</td>
			<td><input type="hidden" name="SETTINGS['.$htmlID.'][KEY]" id="'.$htmlID.'KEY" value="'.htmlspecialcharsbx($arSettings['KEY']).'"><input type="text" size="55" name="'.$htmlID.'INP_KEY" id="'.$htmlID.'INP_KEY" value="'.htmlspecialcharsbx($arSettings['KEY']).'" autocomplete="off" '.($arBucket['READ_ONLY'] == 'Y'? '"disabled"': '').' onchange="BX(\''.$htmlID.'KEY\').value = this.value"></td>
		</tr>
		<tr id="SETTINGS_3_'.$htmlID.'" style="display:'.$show.'" class="settings-tr">
			<td>'.GetMessage("CLO_STORAGE_OPENSTACK_FORCE_HTTP").':</td>
			<td><input type="hidden" name="SETTINGS['.$htmlID.'][FORCE_HTTP]" id="'.$htmlID.'KEY" value="N"><input type="checkbox" name="SETTINGS['.$htmlID.'][FORCE_HTTP]" id="'.$htmlID.'FORCE_HTTP" value="Y" '.($arSettings['FORCE_HTTP'] == 'Y'? 'checked="checked"': '').'></td>
		</tr>
		';
		return $result;
	}

	public function CheckSettings($arBucket, &$arSettings)
	{
		global $APPLICATION;
		$aMsg = array();

		$result = array(
			"HOST" => is_array($arSettings)? trim($arSettings["HOST"]): '',
			"USER" => is_array($arSettings)? trim($arSettings["USER"]): '',
			"KEY" => is_array($arSettings)? trim($arSettings["KEY"]): '',
			"FORCE_HTTP" => is_array($arSettings) && $arSettings["FORCE_HTTP"] == "Y"? "Y": "N",
		);

		if($arBucket["READ_ONLY"] !== "Y" && !strlen($result["HOST"]))
			$aMsg[] = array("id" => $this->GetID()."INP_HOST", "text" => GetMessage("CLO_STORAGE_OPENSTACK_EMPTY_HOST"));

		if($arBucket["READ_ONLY"] !== "Y" && !strlen($result["USER"]))
			$aMsg[] = array("id" => $this->GetID()."INP_USER", "text" => GetMessage("CLO_STORAGE_OPENSTACK_EMPTY_USER"));

		if($arBucket["READ_ONLY"] !== "Y" && !strlen($result["KEY"]))
			$aMsg[] = array("id" => $this->GetID()."INP_KEY", "text" => GetMessage("CLO_STORAGE_OPENSTACK_EMPTY_KEY"));


		if(empty($aMsg))
		{
			if(!$this->_GetToken($result["HOST"], $result["USER"], $result["KEY"]))
				$aMsg[] = array("text" => GetMessage("CLO_STORAGE_OPENSTACK_ERROR_GET_TOKEN"));
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		else
		{
			$arSettings = $result;
		}

		return true;
	}

	public static function _GetToken($host, $user, $key)
	{
		global $APPLICATION;
		static $results = array();
		$cache_id = "v0|".$host."|".$user."|".$key;

		if(array_key_exists($cache_id, $results))
		{
			$result = $results[$cache_id];
		}
		else
		{
			$result = false;
			$obCache = new CPHPCache;

			if($obCache->InitCache(600, $cache_id, "/")) /*TODO make setting*/
			{
				$result = $obCache->GetVars();
			}
			else
			{
				$obRequest = new CHTTP;
				$obRequest->additional_headers["X-Auth-User"] = $user;
				$obRequest->additional_headers["X-Auth-Key"] = $key;
				$obRequest->Query("GET", $host, 80, "/v1.0");
				if($obRequest->status == 412)
				{
					$APPLICATION->ResetException();
					$obRequest = new CHTTP;
					$obRequest->additional_headers["X-Auth-User"] = $user;
					$obRequest->additional_headers["X-Auth-Key"] = $key;
					$obRequest->Query("GET", $host, 80, "/auth/v1.0");
				}

				if($obRequest->status == 204 || $obRequest->status == 200)
				{
					$arStorage = array();
					if(preg_match("#^http://(.*?)(|:\d+)(/.*)\$#", $obRequest->headers["X-Storage-Url"], $arStorage))
					{
						$result = $obRequest->headers;
						$result["X-Storage-Host"] = $arStorage[1];
						$result["X-Storage-Port"] = $arStorage[2]? substr($arStorage[2], 1): 80;
						$result["X-Storage-Urn"] = $arStorage[3];
						$result["X-Storage-Proto"] = "";
					}
				}
			}

			if(is_array($result))
			{
				if($obCache->StartDataCache())
					$obCache->EndDataCache($result);
			}

			$results[$cache_id] = $result;
		}

		return $result;
	}

	public function SendRequest($settings, $verb, $bucket, $file_name='', $params='', $content=false, $additional_headers=array())
	{
		$arToken = $this->_GetToken($settings["HOST"], $settings["USER"], $settings["KEY"]);
		if(!$arToken)
			return false;

		$this->status = 0;
		$obRequest = new CHTTP;

		$RequestURI = $file_name;

		$ContentType = "N";
		$obRequest->additional_headers["X-Auth-Token"] = $arToken["X-Auth-Token"];
		foreach($additional_headers as $key => $value)
		{
			if($key == "Content-Type")
				$ContentType = $value;
			else
				$obRequest->additional_headers[$key] = $value;
		}

		@$obRequest->Query(
			$this->verb = $verb,
			$arToken["X-Storage-Host"],
			$arToken["X-Storage-Port"],
			$this->url = $arToken["X-Storage-Urn"]."/".$bucket.$RequestURI.$params,
			$content,
			$arToken["X-Storage-Proto"],
			$ContentType
		);
		$this->status = $obRequest->status;
		$this->errno = $obRequest->errno;
		$this->errstr = $obRequest->errstr;
		$this->result = $obRequest->result;

		return $obRequest;
	}

	public function CreateBucket($arBucket)
	{
		global $APPLICATION;

		$obRequest = $this->SendRequest(
			$arBucket["SETTINGS"],
			"PUT",
			$arBucket["BUCKET"],
			'', //filename
			'', //params
			false, //content
			array(
				"X-Container-Read" => ".r:*",
				"X-Container-Meta-Web-Listings" => "false",
				"X-Container-Meta-Type" => "public",
			)
		);

		return ($this->status == 201)/*Created*/ || ($this->status == 202) /*Accepted*/;
	}

	public function DeleteBucket($arBucket)
	{
		global $APPLICATION;

		if($arBucket["PREFIX"])
		{
			//Do not delete bucket if there is some files left
			if(!$this->IsEmptyBucket($arBucket))
				return false;

			//Do not delete bucket if there is some files left in other prefixes
			$arAllBucket = $arBucket;
			$arBucket["PREFIX"] = "";
			if(!$this->IsEmptyBucket($arAllBucket))
				return true;
		}

		$obRequest = $this->SendRequest(
			$arBucket["SETTINGS"],
			"DELETE",
			$arBucket["BUCKET"]
		);

		if(
			$this->status == 204/*No Content*/
			|| $this->status == 404/*Not Found*/
		)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function IsEmptyBucket($arBucket)
	{
		global $APPLICATION;

		$obRequest = $this->SendRequest(
			$arBucket["SETTINGS"],
			"GET",
			$arBucket["BUCKET"],
			'',
			"?limit=1&format=xml".($arBucket["PREFIX"]? '&prefix='.$arBucket["PREFIX"]: '')
		);

		$arXML = false;
		if(is_object($obRequest) && $obRequest->result)
		{
			$obXML = new CDataXML;
			$text = preg_replace("/<"."\\?XML.*?\\?".">/i", "", $obRequest->result);
			if($obXML->LoadString($text))
			{
				$arXML = $obXML->GetArray();
			}
		}

		if($this->status == 404)
		{
			return true;
		}
		elseif(is_array($arXML))
		{
			return
				!isset($arXML["container"])
				|| !is_array($arXML["container"])
				|| !isset($arXML["container"]["#"])
				|| !is_array($arXML["container"]["#"])
				|| !isset($arXML["container"]["#"]["object"])
				|| !is_array($arXML["container"]["#"]["object"]);
		}
		else
		{
			return false;
		}
	}

	public function GetFileSRC($arBucket, $arFile)
	{
		global $APPLICATION;
		
		if ($arBucket["SETTINGS"]["FORCE_HTTP"] === "Y")
			$proto = "http";
		else
			$proto = ($APPLICATION->IsHTTPS()? "https": "http");

		if($arBucket["CNAME"])
		{
			$host = $proto."://".$arBucket["CNAME"];
		}
		else
		{
			$arToken = $this->_GetToken(
				$arBucket["SETTINGS"]["HOST"],
				$arBucket["SETTINGS"]["USER"],
				$arBucket["SETTINGS"]["KEY"]
			);

			if(is_array($arToken))
				$host = $arToken["X-Storage-Url"]."/".$arBucket["BUCKET"];
			else
				return "/404.php";
		}

		if(is_array($arFile))
			$URI = ltrim($arFile["SUBDIR"]."/".$arFile["FILE_NAME"], "/");
		else
			$URI = ltrim($arFile, "/");

		if($arBucket["PREFIX"])
		{
			if(substr($URI, 0, strlen($arBucket["PREFIX"])+1) !== $arBucket["PREFIX"]."/")
				$URI = $arBucket["PREFIX"]."/".$URI;
		}

		return $host."/".CCloudUtil::URLEncode($URI, "UTF-8");
	}

	public function FileExists($arBucket, $filePath)
	{
		global $APPLICATION;

		if($arBucket["PREFIX"])
		{
			if(substr($filePath, 0, strlen($arBucket["PREFIX"])+2) != "/".$arBucket["PREFIX"]."/")
				$filePath = "/".$arBucket["PREFIX"]."/".ltrim($filePath, "/");
		}
		$filePath = CCloudUtil::URLEncode($filePath, "UTF-8");

		$obRequest = $this->SendRequest(
			$arBucket["SETTINGS"],
			"HEAD",
			$arBucket["BUCKET"],
			$filePath
		);

		return ($this->status == 200 || $this->status == 206);
	}

	public function FileCopy($arBucket, $arFile, $filePath)
	{
		global $APPLICATION;

		if($arBucket["PREFIX"])
		{
			if(substr($filePath, 0, strlen($arBucket["PREFIX"])+2) != "/".$arBucket["PREFIX"]."/")
				$filePath = "/".$arBucket["PREFIX"]."/".ltrim($filePath, "/");
		}

		$obRequest = $this->SendRequest(
			$arBucket["SETTINGS"],
			"PUT",
			$arBucket["BUCKET"],
			CCloudUtil::URLEncode($filePath, "UTF-8"),
			'',
			false,
			array(
				"X-Copy-From" => CCloudUtil::URLEncode("/".$arBucket["BUCKET"]."/".($arBucket["PREFIX"]? $arBucket["PREFIX"]."/": "").($arFile["SUBDIR"]? $arFile["SUBDIR"]."/": "").$arFile["FILE_NAME"], "UTF-8"),
			)
		);

		if($this->status == 200 || $this->status == 201)
			return $this->GetFileSRC($arBucket, $filePath);
		else
			return false;
	}

	public function DownloadToFile($arBucket, $arFile, $filePath)
	{
		$io = CBXVirtualIo::GetInstance();
		$obRequest = new CHTTP;
		$obRequest->follow_redirect = true;
		return $obRequest->Download($this->GetFileSRC($arBucket, $arFile), $io->GetPhysicalName($filePath));
	}

	public function DeleteFile($arBucket, $filePath)
	{
		global $APPLICATION;

		if($arBucket["PREFIX"])
		{
			if(substr($filePath, 0, strlen($arBucket["PREFIX"])+2) != "/".$arBucket["PREFIX"]."/")
				$filePath = "/".$arBucket["PREFIX"]."/".ltrim($filePath, "/");
		}
		$filePath = CCloudUtil::URLEncode($filePath, "UTF-8");

		$obRequest = $this->SendRequest(
			$arBucket["SETTINGS"],
			"DELETE",
			$arBucket["BUCKET"],
			$filePath
		);

		return ($obRequest->status == 204 || $obRequest->status == 404);
	}

	public function SaveFile($arBucket, $filePath, $arFile)
	{
		global $APPLICATION;

		if($arBucket["PREFIX"])
		{
			if(substr($filePath, 0, strlen($arBucket["PREFIX"])+2) != "/".$arBucket["PREFIX"]."/")
				$filePath = "/".$arBucket["PREFIX"]."/".ltrim($filePath, "/");
		}
		$filePath = CCloudUtil::URLEncode($filePath, "UTF-8");

		if (array_key_exists("content", $arFile))
		{
			$obRequest = $this->SendRequest(
				$arBucket["SETTINGS"],
				"PUT",
				$arBucket["BUCKET"],
				$filePath,
				"",
				$arFile["content"],
				array(
					"Content-Type" => $arFile["type"],
					"Content-Length" => CUtil::BinStrlen($arFile["content"]),
				)
			);
		}
		else
		{
			$obRequest = $this->SendRequest(
				$arBucket["SETTINGS"],
				"PUT",
				$arBucket["BUCKET"],
				$filePath,
				"",
				fopen($arFile["tmp_name"], "rb"),
				array(
					"Content-Type" => $arFile["type"],
					"Content-Length" => filesize($arFile["tmp_name"]),
				)
			);
		}

		if($obRequest->status == 201)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function ListFiles($arBucket, $filePath, $bRecursive = false)
	{
		global $APPLICATION;

		$result = array(
			"dir" => array(),
			"file" => array(),
			"file_size" => array(),
		);

		$filePath = trim($filePath, '/');
		if(strlen($filePath))
			$filePath .= '/';

		if($arBucket["PREFIX"])
		{
			if(substr($filePath, 0, strlen($arBucket["PREFIX"])+2) != "/".$arBucket["PREFIX"]."/")
				$filePath = $arBucket["PREFIX"]."/".ltrim($filePath, "/");
		}
		$filePath = $APPLICATION->ConvertCharset($filePath, LANG_CHARSET, "UTF-8");

		$marker = '';
		$new_marker = false;
		while(true)
		{
			$obRequest = $this->SendRequest(
				$arBucket["SETTINGS"],
				"GET",
				$arBucket["BUCKET"],
				'/',
				$s='?format=xml&'.($bRecursive? '': '&delimiter=/').'&prefix='.urlencode($filePath).'&marker='.urlencode($marker)
			);
			$bFound = false;
			if(is_object($obRequest) && $obRequest->result && $this->status == 200)
			{
				$obXML = new CDataXML;
				$text = preg_replace("/<"."\\?XML.*?\\?".">/i", "", $obRequest->result);
				if($obXML->LoadString($text))
				{
					$arXML = $obXML->GetArray();
					if(
						isset($arXML["container"])
						&& is_array($arXML["container"])
						&& isset($arXML["container"]["#"])
						&& is_array($arXML["container"]["#"])
						&& !empty($arXML["container"]["#"])
					)
					{
						if(
							isset($arXML["container"]["#"]["object"])
							&& is_array($arXML["container"]["#"]["object"])
							&& !empty($arXML["container"]["#"]["object"])
						)
						{
							$bFound = true;
							foreach($arXML["container"]["#"]["object"] as $a)
							{
								$new_marker = $a["#"]["name"][0]["#"];
								if($a["#"]["content_type"][0]["#"] === "application/directory")
								{
									$dir_name = trim(substr($a["#"]["name"][0]["#"], strlen($filePath)), "/");
									$result["dir"][$APPLICATION->ConvertCharset(urldecode($dir_name), "UTF-8", LANG_CHARSET)] = true;
								}
								else
								{
									$file_name = substr($a["#"]["name"][0]["#"], strlen($filePath));
									$file_name = $APPLICATION->ConvertCharset(urldecode($file_name), "UTF-8", LANG_CHARSET);
									if (!in_array($file_name, $result["file"]))
									{
										$result["file"][] = $file_name;
										$result["file_size"][] = $a["#"]["bytes"][0]["#"];
									}
								}
							}
						}

						if(
							isset($arXML["container"]["#"]["subdir"])
							&& is_array($arXML["container"]["#"]["subdir"])
							&& !empty($arXML["container"]["#"]["subdir"])
						)
						{
							$bFound = true;
							foreach($arXML["container"]["#"]["subdir"] as $a)
							{
								$new_marker = $a["@"]["name"];
								$dir_name = trim(substr($a["@"]["name"], strlen($filePath)), "/");
								$result["dir"][$APPLICATION->ConvertCharset(urldecode($dir_name), "UTF-8", LANG_CHARSET)] = true;
							}
						}
					}
				}
			}
			else
			{
				return false;
			}

			if($new_marker === $marker)
				break;

			if(!$bFound)
				break;

			$marker = $new_marker;
		}
		$result["dir"] = array_keys($result["dir"]);
		return $result;
	}

	public static function InitiateMultipartUpload($arBucket, &$NS, $filePath, $fileSize, $ContentType)
	{
		$filePath = '/'.trim($filePath, '/');
		if($arBucket["PREFIX"])
		{
			if(substr($filePath, 0, strlen($arBucket["PREFIX"])+2) != "/".$arBucket["PREFIX"]."/")
				$filePath = "/".$arBucket["PREFIX"].$filePath;
		}

		$NS = array(
			"filePath" => $filePath,
			"fileTemp" => CCloudStorage::translit("/tmp".$filePath, "/"),
			"partsCount" => 0,
			"Content-Type" => $ContentType,
		);

		return true;
	}

	public static function GetMinUploadPartSize()
	{
		return 5*1024*1024; //5MB
	}

	public function UploadPart($arBucket, &$NS, $data)
	{
		$filePath = $NS["fileTemp"]."/".sprintf("%06d", $NS["partsCount"]+1);
		$filePath = CCloudUtil::URLEncode($filePath, "UTF-8");

		$obRequest = $this->SendRequest(
			$arBucket["SETTINGS"],
			"PUT",
			$arBucket["BUCKET"],
			$filePath,
			"",
			$data
		);

		if(is_object($obRequest) && $this->status == 201)
		{
			$NS["partsCount"]++;
			return true;
		}
		else
		{
			return false;
		}
	}

	public function CompleteMultipartUpload($arBucket, &$NS)
	{
		global $APPLICATION;
		$filePath = CCloudUtil::URLEncode($NS["fileTemp"], "UTF-8");

		$obRequest = $this->SendRequest(
			$arBucket["SETTINGS"],
			"PUT",
			$arBucket["BUCKET"],
			$filePath,
			"",
			false,
			array(
				"Content-Type" => $NS["Content-Type"],
				"X-Object-Manifest" => $arBucket["BUCKET"].$filePath."/",
			)
		);

		if(is_object($obRequest) && $this->status == 201)
		{
			$obRequest = $this->SendRequest(
				$arBucket["SETTINGS"],
				"PUT",
				$arBucket["BUCKET"],
				CCloudUtil::URLEncode($NS["filePath"], "UTF-8"),
				'',
				false,
				array(
					"Content-Type" => $NS["Content-Type"],
					"X-Copy-From" => "/".$arBucket["BUCKET"].$filePath,
				)
			);

			if(is_object($obRequest) && $this->status == 201)
				$result = true;
			else
				$result = false;

			$this->DeleteFile($arBucket, $NS["fileTemp"]);
			for($part = $NS["partsCount"]; $part > 0; $part--)
				$this->DeleteFile($arBucket, $NS["fileTemp"]."/".sprintf("%06d", $part));

			return $result;
		}
		else
		{
			//May be delete uploaded tmp file?
			return false;
		}
	}
}
?>