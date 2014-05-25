<?
IncludeModuleLangFile(__FILE__);

class CCloudStorageService_GoogleStorage extends CCloudStorageService
{
	protected $status = 0;
	protected $headers = array();
	protected $errno = 0;
	protected $errstr = '';
	protected $result = '';
	protected $new_end_point;

	public function GetLastRequestStatus()
	{
		return $this->status;
	}

	public static function GetObject()
	{
		return new CCloudStorageService_GoogleStorage();
	}

	public static function GetID()
	{
		return "google_storage";
	}

	public static function GetName()
	{
		return "Google Storage";
	}

	public static function GetLocationList()
	{
		return array(
			"EU" => "Europe",
			"US" => "United States",
		);
	}

	public function GetSettingsHTML($arBucket, $bServiceSet, $cur_SERVICE_ID, $bVarsFromForm)
	{
		if($bVarsFromForm)
			$arSettings = $_POST["SETTINGS"][$this->GetID()];
		else
			$arSettings = unserialize($arBucket["SETTINGS"]);

		if(!is_array($arSettings))
			$arSettings = array("PROJECT_ID" => "", "ACCESS_KEY" => "", "SECRET_KEY" => "");

		$htmlID = htmlspecialcharsbx($this->GetID());

		$result = '
		<tr id="SETTINGS_0_'.$htmlID.'" style="display:'.($cur_SERVICE_ID == $this->GetID() || !$bServiceSet? '': 'none').'" class="settings-tr adm-detail-required-field">
			<td>'.GetMessage("CLO_STORAGE_GOOGLE_EDIT_PROJECT_ID").':</td>
			<td><input type="hidden" name="SETTINGS['.$htmlID.'][PROJECT_ID]" id="'.$htmlID.'PROJECT_ID" value="'.htmlspecialcharsbx($arSettings['PROJECT_ID']).'"><input type="text" size="55" name="'.$htmlID.'INP_" id="'.$htmlID.'INP_PROJECT_ID" value="'.htmlspecialcharsbx($arSettings['PROJECT_ID']).'" '.($arBucket['READ_ONLY'] == 'Y'? '"disabled"': '').' onchange="BX(\''.$htmlID.'PROJECT_ID\').value = this.value"></td>
		</tr>
		<tr id="SETTINGS_1_'.$htmlID.'" style="display:'.($cur_SERVICE_ID == $this->GetID() || !$bServiceSet? '': 'none').'" class="settings-tr adm-detail-required-field">
			<td>'.GetMessage("CLO_STORAGE_GOOGLE_EDIT_ACCESS_KEY").':</td>
			<td><input type="hidden" name="SETTINGS['.$htmlID.'][ACCESS_KEY]" id="'.$htmlID.'ACCESS_KEY" value="'.htmlspecialcharsbx($arSettings['ACCESS_KEY']).'"><input type="text" size="55" name="'.$htmlID.'INP_ACCESS_KEY" id="'.$htmlID.'INP_ACCESS_KEY" value="'.htmlspecialcharsbx($arSettings['ACCESS_KEY']).'" '.($arBucket['READ_ONLY'] == 'Y'? '"disabled"': '').' onchange="BX(\''.$htmlID.'ACCESS_KEY\').value = this.value"></td>
		</tr>
		<tr id="SETTINGS_2_'.$htmlID.'" style="display:'.($cur_SERVICE_ID == $this->GetID() || !$bServiceSet? '': 'none').'" class="settings-tr adm-detail-required-field">
			<td>'.GetMessage("CLO_STORAGE_GOOGLE_EDIT_SECRET_KEY").':</td>
			<td><input type="hidden" name="SETTINGS['.$htmlID.'][SECRET_KEY]" id="'.$htmlID.'SECRET_KEY" value="'.htmlspecialcharsbx($arSettings['SECRET_KEY']).'"><input type="text" size="55" name="'.$htmlID.'INP_SECRET_KEY" id="'.$htmlID.'INP_SECRET_KEY" value="'.htmlspecialcharsbx($arSettings['SECRET_KEY']).'" autocomplete="off" '.($arBucket['READ_ONLY'] == 'Y'? '"disabled"': '').' onchange="BX(\''.$htmlID.'SECRET_KEY\').value = this.value"></td>
		</tr>
		<tr id="SETTINGS_3_'.$htmlID.'" style="display:'.($cur_SERVICE_ID == $this->GetID() || !$bServiceSet? '': 'none').'" class="settings-tr">
			<td>&nbsp;</td>
			<td>'.BeginNote().GetMessage("CLO_STORAGE_GOOGLE_EDIT_HELP").EndNote().'</td>
		</tr>
		';
		return $result;
	}

	public function CheckSettings($arBucket, &$arSettings)
	{
		global $APPLICATION;
		$aMsg = array();

		$result = array(
			"PROJECT_ID" => is_array($arSettings)? trim($arSettings["PROJECT_ID"]): '',
			"ACCESS_KEY" => is_array($arSettings)? trim($arSettings["ACCESS_KEY"]): '',
			"SECRET_KEY" => is_array($arSettings)? trim($arSettings["SECRET_KEY"]): '',
		);

		if($arBucket["READ_ONLY"] !== "Y" && !strlen($result["PROJECT_ID"]))
			$aMsg[] = array("id" => $this->GetID()."INP_PROJECT_ID", "text" => GetMessage("CLO_STORAGE_GOOGLE_EMPTY_PROJECT_ID"));

		if($arBucket["READ_ONLY"] !== "Y" && !strlen($result["ACCESS_KEY"]))
			$aMsg[] = array("id" => $this->GetID()."INP_ACCESS_KEY", "text" => GetMessage("CLO_STORAGE_GOOGLE_EMPTY_ACCESS_KEY"));

		if($arBucket["READ_ONLY"] !== "Y" && !strlen($result["SECRET_KEY"]))
			$aMsg[] = array("id" => $this->GetID()."INP_SECRET_KEY", "text" => GetMessage("CLO_STORAGE_GOOGLE_EMPTY_SECRET_KEY"));

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

	public function CreateBucket($arBucket)
	{
		global $APPLICATION;

		if($arBucket["LOCATION"])
			$content =
				'<CreateBucketConfiguration>'.
				'<LocationConstraint>'.$arBucket["LOCATION"].'</LocationConstraint>'.
				'</CreateBucketConfiguration>';
		else
			$content = '';

		$response = $this->SendRequest(
			$arBucket["SETTINGS"]["ACCESS_KEY"],
			$arBucket["SETTINGS"]["SECRET_KEY"],
			'PUT',
			$arBucket["BUCKET"],
			'/',
			'',
			$content,
			array(
				"x-goog-project-id" => $arBucket["SETTINGS"]["PROJECT_ID"],
			)
		);

		if($this->status == 409/*Already exists*/)
		{
			$APPLICATION->ResetException();
			return true;
		}
		else
		{
			return is_array($response);
		}
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

		$response = $this->SendRequest(
			$arBucket["SETTINGS"]["ACCESS_KEY"],
			$arBucket["SETTINGS"]["SECRET_KEY"],
			'DELETE',
			$arBucket["BUCKET"]
		);

		if($this->status == 204/*No content*/ || $this->status == 404/*Not exists*/)
		{
			$APPLICATION->ResetException();
			return true;
		}
		else
		{
			return is_array($response);
		}
	}

	public function IsEmptyBucket($arBucket)
	{
		global $APPLICATION;

		$response = $this->SendRequest(
			$arBucket["SETTINGS"]["ACCESS_KEY"],
			$arBucket["SETTINGS"]["SECRET_KEY"],
			'GET',
			$arBucket["BUCKET"],
			'/',
			'?max-keys=1'.($arBucket["PREFIX"]? '&prefix='.$arBucket["PREFIX"]: '')
		);

		if($this->status == 404)
		{
			$APPLICATION->ResetException();
			return true;
		}
		elseif(is_array($response))
		{
			return
				!isset($response["ListBucketResult"])
				|| !is_array($response["ListBucketResult"])
				|| !isset($response["ListBucketResult"]["#"])
				|| !is_array($response["ListBucketResult"]["#"])
				|| !isset($response["ListBucketResult"]["#"]["Contents"])
				|| !is_array($response["ListBucketResult"]["#"]["Contents"]);
		}
		else
		{
			return false;
		}
	}

	public static function GetFileSRC($arBucket, $arFile)
	{
		global $APPLICATION;

		if($arBucket["CNAME"])
		{
			$host = $arBucket["CNAME"];
		}
		else
		{
			switch($arBucket["LOCATION"])
			{
			case "EU":
				$host = $arBucket["BUCKET"].".commondatastorage.googleapis.com";
				break;
			case "US":
				$host = $arBucket["BUCKET"].".commondatastorage.googleapis.com";
				break;
			default:
				$host = $arBucket["BUCKET"].".commondatastorage.googleapis.com";
				break;
			}
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

		$proto = $APPLICATION->IsHTTPS()? "https": "http";

		return $proto."://$host/".CCloudUtil::URLEncode($URI, "UTF-8");
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

		$response = $this->SendRequest(
			$arBucket["SETTINGS"]["ACCESS_KEY"],
			$arBucket["SETTINGS"]["SECRET_KEY"],
			'HEAD',
			$arBucket["BUCKET"],
			$filePath
		);

		if($this->status == 200)
		{
			return true;
		}
		elseif($this->status == 206)
		{
			$APPLICATION->ResetException();
			return true;
		}
		else//if($this->status == 404)
		{
			$APPLICATION->ResetException();
			return false;
		}
	}

	public function FileCopy($arBucket, $arFile, $filePath)
	{
		global $APPLICATION;

		if($arBucket["PREFIX"])
		{
			if(substr($filePath, 0, strlen($arBucket["PREFIX"])+2) != "/".$arBucket["PREFIX"]."/")
				$filePath = "/".$arBucket["PREFIX"]."/".ltrim($filePath, "/");
		}

		$response = $this->SendRequest(
			$arBucket["SETTINGS"]["ACCESS_KEY"],
			$arBucket["SETTINGS"]["SECRET_KEY"],
			'PUT',
			$arBucket["BUCKET"],
			CCloudUtil::URLEncode($filePath, "UTF-8"),
			'',
			'',
			array(
				"x-goog-acl"=>"public-read",
				"x-goog-copy-source"=>CCloudUtil::URLEncode("/".$arBucket["BUCKET"]."/".($arBucket["PREFIX"]? $arBucket["PREFIX"]."/": "").($arFile["SUBDIR"]? $arFile["SUBDIR"]."/": "").$arFile["FILE_NAME"], "UTF-8"),
				"Content-Type"=>$arFile["CONTENT_TYPE"]
			)
		);

		if($this->status == 200)
		{
			return $this->GetFileSRC($arBucket, $filePath);
		}
		else//if($this->status == 404)
		{
			$APPLICATION->ResetException();
			return false;
		}
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

		$response = $this->SendRequest(
			$arBucket["SETTINGS"]["ACCESS_KEY"],
			$arBucket["SETTINGS"]["SECRET_KEY"],
			'DELETE',
			$arBucket["BUCKET"],
			$filePath
		);

		if($this->status == 204 || $this->status == 404)
		{
			$APPLICATION->ResetException();
			return true;
		}
		else
		{
			$APPLICATION->ResetException();
			return false;
		}
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

		$response = $this->SendRequest(
			$arBucket["SETTINGS"]["ACCESS_KEY"],
			$arBucket["SETTINGS"]["SECRET_KEY"],
			'PUT',
			$arBucket["BUCKET"],
			$filePath,
			'',
			(array_key_exists("content", $arFile)? $arFile["content"]: fopen($arFile["tmp_name"], "rb")),
			array(
				"x-goog-acl" => "public-read",
				"Content-Type" => $arFile["type"],
				"Content-Length" => (array_key_exists("content", $arFile)? CUtil::BinStrlen($arFile["content"]): filesize($arFile["tmp_name"])),
			)
		);

		if($this->status == 200)
		{
			return true;
		}
		else
		{
			$APPLICATION->ResetException();
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
		while(true)
		{
			$response = $this->SendRequest(
				$arBucket["SETTINGS"]["ACCESS_KEY"],
				$arBucket["SETTINGS"]["SECRET_KEY"],
				'GET',
				$arBucket["BUCKET"],
				'/',
				'?'.($bRecursive? '': 'delimiter=/&').'prefix='.urlencode($filePath).'&marker='.urlencode($marker)
			);
			if(
				$this->status == 200
				&& is_array($response)
				&& isset($response["ListBucketResult"])
				&& is_array($response["ListBucketResult"])
				&& isset($response["ListBucketResult"]["#"])
				&& is_array($response["ListBucketResult"]["#"])
			)
			{
				if(
					isset($response["ListBucketResult"]["#"]["CommonPrefixes"])
					&& is_array($response["ListBucketResult"]["#"]["CommonPrefixes"])
				)
				{
					foreach($response["ListBucketResult"]["#"]["CommonPrefixes"] as $a)
					{
						$dir_name = substr(rtrim($a["#"]["Prefix"][0]["#"], "/"), strlen($filePath));
						$result["dir"][] = $APPLICATION->ConvertCharset(urldecode($dir_name), "UTF-8", LANG_CHARSET);
					}
				}

				if(
					isset($response["ListBucketResult"]["#"]["Contents"])
					&& is_array($response["ListBucketResult"]["#"]["Contents"])
				)
				{
					foreach($response["ListBucketResult"]["#"]["Contents"] as $a)
					{
						$file_name = substr($a["#"]["Key"][0]["#"], strlen($filePath));
						$result["file"][] = $APPLICATION->ConvertCharset(urldecode($file_name), "UTF-8", LANG_CHARSET);
						$result["file_size"][] = $a["#"]["Size"][0]["#"];
					}
				}

				if(
					isset($response["ListBucketResult"]["#"]["IsTruncated"])
					&& is_array($response["ListBucketResult"]["#"]["IsTruncated"])
					&& $response["ListBucketResult"]["#"]["IsTruncated"][0]["#"] === "true"
					&& strlen($response["ListBucketResult"]["#"]["NextMarker"][0]["#"]) > 0
				)
				{
					$marker = $response["ListBucketResult"]["#"]["NextMarker"][0]["#"];
					continue;
				}
				else
				{
					break;
				}
			}
			else
			{
				$APPLICATION->ResetException();
				return false;
			}
		}

		return $result;
	}

	public function InitiateMultipartUpload($arBucket, &$NS, $filePath, $fileSize, $ContentType)
	{
		$filePath = '/'.trim($filePath, '/');
		if($arBucket["PREFIX"])
		{
			if(substr($filePath, 0, strlen($arBucket["PREFIX"])+2) != "/".$arBucket["PREFIX"]."/")
				$filePath = "/".$arBucket["PREFIX"].$filePath;
		}
		$filePathU = CCloudUtil::URLEncode($filePath, "UTF-8");

		$response = $this->SendRequest(
			$arBucket["SETTINGS"]["ACCESS_KEY"],
			$arBucket["SETTINGS"]["SECRET_KEY"],
			'POST',
			$arBucket["BUCKET"],
			$filePathU,
			'',
			'',
			array(
				"x-goog-acl"=>"public-read",
				"x-goog-resumable"=>"start",
				"Content-Type"=>$ContentType,
			)
		);

		if(
			$this->status == 201
			&& is_array($this->headers)
			&& isset($this->headers["Location"])
			&& preg_match("/upload_id=(.*)\$/", $this->headers["Location"], $match)
		)
		{
			$NS = array(
				"filePath" => $filePath,
				"fileSize" => $fileSize,
				"filePos" => 0,
				"upload_id" => $match[1],
			);
			return true;
		}
		else
		{
			return false;
		}
	}

	public static function GetMinUploadPartSize()
	{
		return 5*1024*1024; //5MB
	}

	public function UploadPart($arBucket, &$NS, $data)
	{
		global $APPLICATION;

		$filePath = '/'.trim($NS["filePath"], '/');
		if($arBucket["PREFIX"])
		{
			if(substr($filePath, 0, strlen($arBucket["PREFIX"])+2) != "/".$arBucket["PREFIX"]."/")
				$filePath = "/".$arBucket["PREFIX"].$filePath;
		}
		$filePathU = CCloudUtil::URLEncode($filePath, "UTF-8");

		$response = $this->SendRequest(
			$arBucket["SETTINGS"]["ACCESS_KEY"],
			$arBucket["SETTINGS"]["SECRET_KEY"],
			'PUT',
			$arBucket["BUCKET"],
			$filePathU.'?upload_id='.urlencode($NS["upload_id"]),
			'',
			'',
			array(
				"Content-Range" => "bytes */".$NS["fileSize"],
			)
		);

		$data_len = CUtil::BinStrlen($data);

		$response = $this->SendRequest(
			$arBucket["SETTINGS"]["ACCESS_KEY"],
			$arBucket["SETTINGS"]["SECRET_KEY"],
			'PUT',
			$arBucket["BUCKET"],
			$filePathU.'?upload_id='.urlencode($NS["upload_id"]),
			'',
			$data,
			array(
				"Content-Range" => "bytes ".$NS["filePos"]."-".($NS["filePos"]+$data_len-1)."/".$NS["fileSize"],
			)
		);

		if($this->status == 308 && is_array($this->headers) && preg_match("/^bytes=(\\d+)-(\\d+)\$/", $this->headers["Range"], $match))
		{
			$APPLICATION->ResetException();
			$NS["filePos"] = $match[2]+1;
			return true;
		}
		elseif($this->status == 200)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public static function CompleteMultipartUpload($arBucket, &$NS)
	{
		return true;
	}

	public function SendRequest($access_key, $secret_key, $verb, $bucket, $file_name='/', $params='', $content='', $additional_headers=array())
	{
		global $APPLICATION;
		$this->status = 0;

		if(isset($additional_headers["Content-Type"]))
		{
			$ContentType = $additional_headers["Content-Type"];
			unset($additional_headers["Content-Type"]);
		}
		else
		{
			$ContentType = $content? 'text/plain': '';
		}

		if(!array_key_exists("x-goog-api-version", $additional_headers))
			$additional_headers["x-goog-api-version"] = "1";

		$RequestMethod = $verb;
		$RequestURI = $file_name;
		$RequestDATE = gmdate('D, d M Y H:i:s', time()).' GMT';

		//Prepare Signature
		$CanonicalizedAmzHeaders = "";
		ksort($additional_headers);
		foreach($additional_headers as $key => $value)
			if(preg_match("/^x-goog-/", $key))
				$CanonicalizedAmzHeaders .= $key.":".$value."\n";

		$CanonicalizedResource = "/".$bucket.$RequestURI;

		$StringToSign = "$RequestMethod\n\n$ContentType\n$RequestDATE\n$CanonicalizedAmzHeaders$CanonicalizedResource";
		//$utf = $APPLICATION->ConvertCharset($StringToSign, LANG_CHARSET, "UTF-8");

		$Signature = base64_encode($this->hmacsha1($StringToSign, $secret_key));
		$Authorization = "GOOG1 ".$access_key.":".$Signature;

		$obRequest = new CHTTP;
		$obRequest->additional_headers["Date"] = $RequestDATE;
		$obRequest->additional_headers["Authorization"] = $Authorization;
		foreach($additional_headers as $key => $value)
			if(!preg_match("/^option-/", $key))
				$obRequest->additional_headers[$key] = $value;

		if(
			$this->new_end_point
			&& preg_match('#^(http|https)://'.preg_quote($bucket, '#').'(.+)/#', $this->new_end_point, $match))
		{
			$host = $match[2];
		}
		else
		{
			$host = $bucket.".commondatastorage.googleapis.com";
		}

		$was_end_point = $this->new_end_point;
		$this->new_end_point = '';

		$obRequest->Query($RequestMethod, $host, 80, $RequestURI.$params, $content, '', $ContentType);
		$this->status = $obRequest->status;
		$this->headers = $obRequest->headers;
		$this->errno = $obRequest->errno;
		$this->errstr = $obRequest->errstr;
		$this->result = $obRequest->result;

		if($obRequest->status == 200)
		{
			if(isset($additional_headers["option-raw-result"]))
			{
				return $obRequest->result;
			}
			elseif($obRequest->result)
			{
				$obXML = new CDataXML;
				$text = preg_replace("/<"."\\?XML.*?\\?".">/i", "", $obRequest->result);
				if($obXML->LoadString($text))
				{
					$arXML = $obXML->GetArray();
					if(is_array($arXML))
					{
						return $arXML;
					}
				}
				//XML parse error
				$APPLICATION->ThrowException(GetMessage('CLO_STORAGE_GOOGLE_XML_PARSE_ERROR', array('#errno#'=>1)));
				return false;
			}
			else
			{
				//Empty success result
				return array();
			}
		}
		elseif(
			$obRequest->status == 307  //Temporary redirect
			&& isset($obRequest->headers["Location"])
			&& !$was_end_point //No recurse yet
		)
		{
			$this->new_end_point = $obRequest->headers["Location"];
			return $this->SendRequest(
				$access_key,
				$secret_key,
				$verb,
				$bucket,
				$file_name,
				$params,
				$content,
				$additional_headers
			);
		}
		elseif($obRequest->status > 0)
		{
			if($obRequest->result)
			{
				$obXML = new CDataXML;
				if($obXML->LoadString($obRequest->result))
				{
					$arXML = $obXML->GetArray();
					if(is_array($arXML) && is_string($arXML["Error"]["#"]["Message"][0]["#"]))
					{
						$APPLICATION->ThrowException(GetMessage('CLO_STORAGE_GOOGLE_XML_ERROR', array('#errmsg#'=>trim($arXML["Error"]["#"]["Message"][0]["#"], '.'))));
						return false;
					}
				}
			}
			$APPLICATION->ThrowException(GetMessage('CLO_STORAGE_GOOGLE_XML_PARSE_ERROR', array('#errno#'=>2)));
			return false;
		}
		else
		{
			$APPLICATION->ThrowException(GetMessage('CLO_STORAGE_GOOGLE_XML_PARSE_ERROR', array('#errno#'=>3)));
			return false;
		}
	}

	public static function hmacsha1($data, $key)
	{
		if(strlen($key)>64)
			$key=pack('H*', sha1($key));
		$key = str_pad($key, 64, chr(0x00));
		$ipad = str_repeat(chr(0x36), 64);
		$opad = str_repeat(chr(0x5c), 64);
		$hmac = pack('H*', sha1(($key^$opad).pack('H*', sha1(($key^$ipad).$data))));
		return $hmac;
	}
}
?>