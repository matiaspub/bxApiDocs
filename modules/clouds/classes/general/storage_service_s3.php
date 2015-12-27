<?
IncludeModuleLangFile(__FILE__);

class CCloudStorageService_AmazonS3 extends CCloudStorageService
{
	protected $status = 0;
	protected $verb = '';
	protected $host = '';
	protected $url = '';
	protected $headers =/*.(array[string]string).*/array();
	protected $set_headers =/*.(array[string]string).*/array();
	protected $errno = 0;
	protected $errstr = '';
	protected $result = '';
	protected $new_end_point = '';
	protected $_public = true;
	protected $location = '';
	/**
	 * @return int
	*/
	public function GetLastRequestStatus()
	{
		return $this->status;
	}
	/**
	 * @return CCloudStorageService
	*/
	public static function GetObject()
	{
		return new CCloudStorageService_AmazonS3();
	}
	/**
	 * @return string
	*/
	public static function GetID()
	{
		return "amazon_s3";
	}
	/**
	 * @return string
	*/
	public static function GetName()
	{
		return "Amazon Simple Storage Service";
	}
	/**
	 * @return array[string]string
	*/
	public static function GetLocationList()
	{
		return array(
			"" => "US Standard",
			"us-west-2" => "US West (Oregon)",
			"us-west-1" => "US West (Northern California)",
			"eu-west-1" => "EU (Ireland)",
			"eu-central-1" => "EU (Frankfurt)",
			"ap-southeast-1" => "Asia Pacific (Singapore)",
			"ap-southeast-2" => "Asia Pacific (Sydney)",
			"ap-northeast-1" => "Asia Pacific (Tokyo)",
			"sa-east-1" => "South America (Sao Paulo)",
		);
	}
	/**
	 * @param array[string]string $arBucket
	 * @param bool $bServiceSet
	 * @param string $cur_SERVICE_ID
	 * @param bool $bVarsFromForm
	 * @return string
	*/
	public function GetSettingsHTML($arBucket, $bServiceSet, $cur_SERVICE_ID, $bVarsFromForm)
	{
		if($bVarsFromForm)
			$arSettings = $_POST["SETTINGS"][$this->GetID()];
		else
			$arSettings = unserialize($arBucket["SETTINGS"]);

		if(!is_array($arSettings))
			$arSettings = array("ACCESS_KEY" => "", "SECRET_KEY" => "");

		$htmlID = htmlspecialcharsbx($this->GetID());

		$result = '
		<tr id="SETTINGS_0_'.$htmlID.'" style="display:'.($cur_SERVICE_ID === $this->GetID() || !$bServiceSet? '': 'none').'" class="settings-tr adm-detail-required-field">
			<td>'.GetMessage("CLO_STORAGE_S3_EDIT_ACCESS_KEY").':</td>
			<td><input type="hidden" name="SETTINGS['.$htmlID.'][ACCESS_KEY]" id="'.$htmlID.'ACCESS_KEY" value="'.htmlspecialcharsbx($arSettings['ACCESS_KEY']).'"><input type="text" size="55" name="'.$htmlID.'INP_ACCESS_KEY" id="'.$htmlID.'INP_ACCESS_KEY" value="'.htmlspecialcharsbx($arSettings['ACCESS_KEY']).'" '.($arBucket['READ_ONLY'] === 'Y'? '"disabled"': '').' onchange="BX(\''.$htmlID.'ACCESS_KEY\').value = this.value"></td>
		</tr>
		<tr id="SETTINGS_1_'.$htmlID.'" style="display:'.($cur_SERVICE_ID === $this->GetID() || !$bServiceSet? '': 'none').'" class="settings-tr adm-detail-required-field">
			<td>'.GetMessage("CLO_STORAGE_S3_EDIT_SECRET_KEY").':</td>
			<td><input type="hidden" name="SETTINGS['.$htmlID.'][SECRET_KEY]" id="'.$htmlID.'SECRET_KEY" value="'.htmlspecialcharsbx($arSettings['SECRET_KEY']).'"><input type="text" size="55" name="'.$htmlID.'INP_SECRET_KEY" id="'.$htmlID.'INP_SECRET_KEY" value="'.htmlspecialcharsbx($arSettings['SECRET_KEY']).'" autocomplete="off" '.($arBucket['READ_ONLY'] === 'Y'? '"disabled"': '').' onchange="BX(\''.$htmlID.'SECRET_KEY\').value = this.value"></td>
		</tr>
		<tr id="SETTINGS_2_'.$htmlID.'" style="display:'.($cur_SERVICE_ID === $this->GetID() || !$bServiceSet? '': 'none').'" class="settings-tr">
			<td>&nbsp;</td>
			<td>'.BeginNote().GetMessage("CLO_STORAGE_S3_EDIT_HELP").EndNote().'</td>
		</tr>
		';
		return $result;
	}
	/**
	 * @param array[string]string $arBucket
	 * @param array[string]string $arSettings
	 * @return bool
	*/
	public function CheckSettings($arBucket, &$arSettings)
	{
		global $APPLICATION;
		$aMsg =/*.(array[int][string]string).*/array();

		$result = array(
			"ACCESS_KEY" => is_array($arSettings)? trim($arSettings["ACCESS_KEY"]): '',
			"SECRET_KEY" => is_array($arSettings)? trim($arSettings["SECRET_KEY"]): '',
		);
		if(is_array($arSettings) && array_key_exists("SESSION_TOKEN", $arSettings))
		{
			$result["SESSION_TOKEN"] = trim($arSettings["SESSION_TOKEN"]);
		}

		if($arBucket["READ_ONLY"] !== "Y" && $result["ACCESS_KEY"] === '')
		{
			$aMsg[] = array(
				"id" => $this->GetID()."INP_ACCESS_KEY",
				"text" => GetMessage("CLO_STORAGE_S3_EMPTY_ACCESS_KEY"),
			);
		}

		if($arBucket["READ_ONLY"] !== "Y" && $result["SECRET_KEY"] === '')
		{
			$aMsg[] = array(
				"id" => $this->GetID()."INP_SECRET_KEY",
				"text" => GetMessage("CLO_STORAGE_S3_EMPTY_SECRET_KEY"),
			);
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
	/**
	 * @param string $data
	 * @param string $key
	 * @return string
	*/
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
	/**
	 * @param array[string]string $arSettings
	 * @param string $RequestMethod
	 * @param string $bucket
	 * @param string $RequestURI
	 * @param string $ContentType
	 * @param array[string]string $additional_headers
	 * @param string $params
	 * @param string|resource $content
	 * @return array[string]string
	*/
	public function SignRequest($arSettings, $RequestMethod, $bucket, $RequestURI, $ContentType, $additional_headers, $params = "", $content = "")
	{
		static $search = array("+", "=");
		static $replace = array("%20", "%3D");

		$CanonicalizedResource = strlen($RequestURI)? str_replace($search, $replace, $RequestURI): "/";

		$CanonicalQuery = explode("&", ltrim($params, "?"));
		sort($CanonicalQuery);
		$CanonicalQueryString = implode("&", $CanonicalQuery);

		$CanonicalHeaders = array();
		foreach($additional_headers as $key => $value)
		{
			$key = strtolower($key);
			if (isset($CanonicalHeaders[$key]))
				$CanonicalHeaders[$key] .= ",";
			else
				$CanonicalHeaders[$key] = $key.":";
			$CanonicalHeaders[$key] .= trim($value, " \t\n\r");
		}
		ksort($CanonicalHeaders);
		$CanonicalHeadersString = implode("\n", $CanonicalHeaders);
		$SignedHeaders = implode(";", array_keys($CanonicalHeaders));

		if (is_resource($content))
		{
			$streamPosition = ftell($content);
			$hashResource = hash_init("sha256");
			hash_update_stream($hashResource, $content);
			$HashedPayload = hash_final($hashResource);
			fseek($content, $streamPosition);
		}
		else
		{
			$HashedPayload = hash("sha256", $content, false);
		}

		$CanonicalRequest = "";
		$CanonicalRequest .= $RequestMethod."\n";
		$CanonicalRequest .= $CanonicalizedResource."\n";
		$CanonicalRequest .= $CanonicalQueryString."\n";
		$CanonicalRequest .= $CanonicalHeadersString."\n\n";
		$CanonicalRequest .= $SignedHeaders."\n";
		$CanonicalRequest .= $HashedPayload;

		$Algorithm = "AWS4-HMAC-SHA256";
		$Time = time();
		$RequestDate = gmdate('Ymd', $Time);
		$RequestTime = gmdate('D, d M Y H:i:s', $Time).' GMT';
		$Region = $this->location? $this->location: 'us-east-1';
		$Service = "s3";
		$Scope = $RequestDate."/".$Region."/".$Service."/aws4_request";

		$StringToSign = "";
		$StringToSign .= $Algorithm."\n";
		$StringToSign .= $RequestTime."\n";
		$StringToSign .= $Scope."\n";
		$StringToSign .= hash("sha256", $CanonicalRequest, false);

		$kSecret  = $arSettings["SECRET_KEY"];
		$kDate    = hash_hmac("sha256", $RequestDate, "AWS4".$kSecret, true);
		$kRegion  = hash_hmac("sha256", $Region, $kDate, true);
		$kService = hash_hmac("sha256", $Service, $kRegion, true);
		$kSigning = hash_hmac("sha256", "aws4_request", $kService, true);

		$Signature = hash_hmac("sha256", $StringToSign, $kSigning, false);

		$Authorization = "$Algorithm Credential=$arSettings[ACCESS_KEY]/$Scope, SignedHeaders=$SignedHeaders, Signature=$Signature";

		return array(
			"Date" => gmdate('D, d M Y H:i:s', $Time).' GMT',
			"Authorization" => $Authorization,
			"x-amz-content-sha256" => $HashedPayload,
		);
	}
	/**
	 * @param string $location
	 * @return void
	 **/
	public function SetLocation($location)
	{
		if ($location)
			$this->location = $location;
		else
			$this->location = "";
	}
	/**
	 * @param array[string]string $arSettings
	 * @param string $verb
	 * @param string $bucket
	 * @param string $file_name
	 * @param string $params
	 * @param string $content
	 * @param array[string]string $additional_headers
	 * @return mixed
	*/
	public function SendRequest($arSettings, $verb, $bucket, $file_name='/', $params='', $content='', $additional_headers=/*.(array[string]string).*/array())
	{
		global $APPLICATION;
		$this->status = 0;

		$obRequest = new CHTTP;
		if (isset($additional_headers["option-file-result"]))
		{
			$obRequest->fp = $additional_headers["option-file-result"];
		}

		if(isset($additional_headers["Content-Type"]))
		{
			$ContentType = $additional_headers["Content-Type"];
			unset($additional_headers["Content-Type"]);
		}
		else
		{
			$ContentType = $content != ""? 'text/plain': '';
		}

		foreach($this->set_headers as $key => $value)
		{
			$additional_headers[$key] = $value;
		}

		if(array_key_exists("SESSION_TOKEN", $arSettings))
		{
			$additional_headers["x-amz-security-token"] = $arSettings["SESSION_TOKEN"];
		}

		if(
			$this->new_end_point != ""
			&& preg_match('#^(http|https)://'.preg_quote($bucket, '#').'(.+?)/#', $this->new_end_point, $match) > 0
		)
		{
			$additional_headers["host"] = $bucket.$match[2];
		}
		elseif ($this->location)
		{
			$additional_headers["host"] = $bucket.".s3-".$this->location.".amazonaws.com";
		}
		else
		{
			$additional_headers["host"] = $bucket.".s3.amazonaws.com";
		}

		foreach($this->SignRequest($arSettings, $verb, $bucket, $file_name, $ContentType, $additional_headers, $params, $content) as $key => $value)
		{
			$obRequest->additional_headers[$key] = $value;
		}

		foreach($additional_headers as $key => $value)
			if(preg_match("/^(option-|host\$)/", $key) == 0)
				$obRequest->additional_headers[$key] = $value;

		$host = $additional_headers["host"];

		$was_end_point = $this->new_end_point;
		$this->new_end_point = '';

		$obRequest->Query($verb, $host, 80, $file_name.$params, $content, '', $ContentType);
		$this->status = $obRequest->status;
		$this->host = $host;
		$this->verb = $verb;
		$this->url =  $file_name.$params;
		$this->headers = $obRequest->headers;
		$this->errno = $obRequest->errno;
		$this->errstr = $obRequest->errstr;
		$this->result = $obRequest->result;

		if($obRequest->status == 200)
		{
			if(
				isset($additional_headers["option-raw-result"])
				|| isset($additional_headers["option--result"])
			)
			{
				return $obRequest->result;
			}
			elseif($obRequest->result != "")
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
				$e = new CApplicationException(GetMessage('CLO_STORAGE_S3_XML_PARSE_ERROR', array('#errno#'=>'1')));
				$APPLICATION->ThrowException($e);
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
			&& $was_end_point === "" //No recurse yet
		)
		{
			$this->new_end_point = $obRequest->headers["Location"];
			return $this->SendRequest(
				$arSettings,
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
			if($obRequest->result != "")
			{
				$obXML = new CDataXML;
				if($obXML->LoadString($obRequest->result))
				{
					$node = $obXML->SelectNodes("/Error/Message");
					if (is_object($node))
					{
						$errorMessage = trim($node->textContent(), '.');
						$e = new CApplicationException(GetMessage('CLO_STORAGE_S3_XML_ERROR', array(
							'#errmsg#' => $errorMessage,
						)));
						$APPLICATION->ThrowException($e);
						return false;
					}
				}
			}
			$e = new CApplicationException(GetMessage('CLO_STORAGE_S3_XML_PARSE_ERROR', array('#errno#'=>'2')));
			$APPLICATION->ThrowException($e);
			return false;
		}
		else
		{
			$e = new CApplicationException(GetMessage('CLO_STORAGE_S3_XML_PARSE_ERROR', array('#errno#'=>'3')));
			$APPLICATION->ThrowException($e);
			return false;
		}
	}
	/**
	 * @param array[string]string $arBucket
	 * @return bool
	*/
	public function CreateBucket($arBucket)
	{
		global $APPLICATION;

		$arFiles = $this->ListFiles($arBucket, '/');
		if(is_array($arFiles))
			return true;

		if($arBucket["LOCATION"] != "")
			$content =
				'<CreateBucketConfiguration xmlns="http://s3.amazonaws.com/doc/2006-03-01/">'.
				'<LocationConstraint>'.$arBucket["LOCATION"].'</LocationConstraint>'.
				'</CreateBucketConfiguration>';
		else
			$content = '';

		$this->SetLocation($arBucket["LOCATION"]);
		$response = $this->SendRequest(
			$arBucket["SETTINGS"],
			'PUT',
			$arBucket["BUCKET"],
			'/',
			'',
			$content
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
	/**
	 * @param array[string]string $arBucket
	 * @return bool
	*/
	public function DeleteBucket($arBucket)
	{
		global $APPLICATION;

		if($arBucket["PREFIX"] != "")
		{
			//Do not delete bucket if there is some files left
			if(!$this->IsEmptyBucket($arBucket))
				return false;

			//Let's pretend we deleted the bucket
			return true;
		}

		$this->SetLocation($arBucket["LOCATION"]);
		$response = $this->SendRequest(
			$arBucket["SETTINGS"],
			'DELETE',
			$arBucket["BUCKET"]
		);

		if(
			$this->status == 204/*No content*/
			|| $this->status == 404/*Not exists*/
			|| $this->status == 403/*Access denied*/
		)
		{
			$APPLICATION->ResetException();
			return true;
		}
		else
		{
			return is_array($response);
		}
	}
	/**
	 * @param array[string]string $arBucket
	 * @return bool
	*/
	public function IsEmptyBucket($arBucket)
	{
		global $APPLICATION;

		$this->SetLocation($arBucket["LOCATION"]);
		$response = $this->SendRequest(
			$arBucket["SETTINGS"],
			'GET',
			$arBucket["BUCKET"],
			'/',
			'?max-keys=1'.($arBucket["PREFIX"] != ""? '&prefix='.$arBucket["PREFIX"].'/': '')
		);

		if($this->status == 404 || $this->status == 403)
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
	/**
	 * @param array[string]string $arBucket
	 * @param mixed $arFile
	 * @return string
	*/
	public static function GetFileSRC($arBucket, $arFile)
	{
		if($arBucket["CNAME"] != "")
		{
			$host = $arBucket["CNAME"];
		}
		else
		{
			switch($arBucket["LOCATION"])
			{
			case "us-west-1":
				$host = $arBucket["BUCKET"].".s3-us-west-1.amazonaws.com";
				break;
			case "eu-west-1":
				$host = $arBucket["BUCKET"].".s3-eu-west-1.amazonaws.com";
				break;
			case "ap-southeast-1":
				$host = $arBucket["BUCKET"].".s3-ap-southeast-1.amazonaws.com";
				break;
			case "ap-northeast-1":
				$host = $arBucket["BUCKET"].".s3-ap-northeast-1.amazonaws.com";
				break;
			default:
				$host = $arBucket["BUCKET"].".s3.amazonaws.com";
				break;
			}
		}

		if(is_array($arFile))
			$URI = ltrim($arFile["SUBDIR"]."/".$arFile["FILE_NAME"], "/");
		else
			$URI = ltrim($arFile, "/");

		if($arBucket["PREFIX"] != "")
		{
			if(substr($URI, 0, strlen($arBucket["PREFIX"])+1) !== $arBucket["PREFIX"]."/")
				$URI = $arBucket["PREFIX"]."/".$URI;
		}

		$proto = CMain::IsHTTPS()? "https": "http";

		return $proto."://$host/".CCloudUtil::URLEncode($URI, "UTF-8");
	}
	/**
	 * @param array[string]string $arBucket
	 * @param string $filePath
	 * @return bool
	*/
	public function FileExists($arBucket, $filePath)
	{
		global $APPLICATION;

		if($arBucket["PREFIX"] != "")
		{
			if(substr($filePath, 0, strlen($arBucket["PREFIX"])+2) != "/".$arBucket["PREFIX"]."/")
				$filePath = "/".$arBucket["PREFIX"]."/".ltrim($filePath, "/");
		}
		$filePath = CCloudUtil::URLEncode($filePath, "UTF-8");

		$this->SetLocation($arBucket["LOCATION"]);
		$this->SendRequest(
			$arBucket["SETTINGS"],
			'HEAD',
			$arBucket["BUCKET"],
			$filePath
		);

		if($this->status == 200)
		{
			if (isset($this->headers["Content-Length"]) && $this->headers["Content-Length"] > 0)
				return $this->headers["Content-Length"];
			else
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

		$additional_headers = array();
		if($this->_public)
			$additional_headers["x-amz-acl"] = "public-read";
		$additional_headers["x-amz-copy-source"] = CCloudUtil::URLEncode("/".$arBucket["BUCKET"]."/".($arBucket["PREFIX"]? $arBucket["PREFIX"]."/": "").($arFile["SUBDIR"]? $arFile["SUBDIR"]."/": "").$arFile["FILE_NAME"], "UTF-8");
		$additional_headers["Content-Type"] = $arFile["CONTENT_TYPE"];

		$this->SetLocation($arBucket["LOCATION"]);
		$this->SendRequest(
			$arBucket["SETTINGS"],
			'PUT',
			$arBucket["BUCKET"],
			CCloudUtil::URLEncode($filePath, "UTF-8"),
			'',
			'',
			$additional_headers
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

		$this->SetLocation($arBucket["LOCATION"]);
		$this->SendRequest(
			$arBucket["SETTINGS"],
			'DELETE',
			$arBucket["BUCKET"],
			$filePath
		);

		if($this->status == 204)
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

	public function SaveFile($arBucket, $filePath, $arFile)
	{
		global $APPLICATION;

		if($arBucket["PREFIX"])
		{
			if(substr($filePath, 0, strlen($arBucket["PREFIX"])+2) != "/".$arBucket["PREFIX"]."/")
				$filePath = "/".$arBucket["PREFIX"]."/".ltrim($filePath, "/");
		}
		$filePath = CCloudUtil::URLEncode($filePath, "UTF-8");

		$additional_headers = array();
		if($this->_public)
			$additional_headers["x-amz-acl"] = "public-read";
		$additional_headers["Content-Type"] = $arFile["type"];
		$additional_headers["Content-Length"] = (array_key_exists("content", $arFile)? CUtil::BinStrlen($arFile["content"]): filesize($arFile["tmp_name"]));

		$this->SetLocation($arBucket["LOCATION"]);
		$this->SendRequest(
			$arBucket["SETTINGS"],
			'PUT',
			$arBucket["BUCKET"],
			$filePath,
			'',
			(array_key_exists("content", $arFile)? $arFile["content"]: fopen($arFile["tmp_name"], "rb")),
			$additional_headers
		);

		if($this->status == 200)
		{
			return true;
		}
		elseif($this->status == 403)
		{
			return false;
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

		$this->SetLocation($arBucket["LOCATION"]);
		$marker = '';
		while(true)
		{
			$response = $this->SendRequest(
				$arBucket["SETTINGS"],
				'GET',
				$arBucket["BUCKET"],
				'/',
				'?'.($bRecursive? '': 'delimiter=%2F&').'prefix='.urlencode($filePath)
					.'&marker='.str_replace("+", "%20", urlencode($marker))
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

				$lastKey = null;
				if(
					isset($response["ListBucketResult"]["#"]["Contents"])
					&& is_array($response["ListBucketResult"]["#"]["Contents"])
				)
				{
					foreach($response["ListBucketResult"]["#"]["Contents"] as $a)
					{
						$file_name = substr($a["#"]["Key"][0]["#"], strlen($filePath));
						$result["file"][] = $APPLICATION->ConvertCharset($file_name, "UTF-8", LANG_CHARSET);
						$result["file_size"][] = $a["#"]["Size"][0]["#"];
						$lastKey = $a["#"]["Key"][0]["#"];
					}
				}

				if(
					isset($response["ListBucketResult"]["#"]["IsTruncated"])
					&& is_array($response["ListBucketResult"]["#"]["IsTruncated"])
					&& $response["ListBucketResult"]["#"]["IsTruncated"][0]["#"] === "true"
				)
				{
					if (strlen($response["ListBucketResult"]["#"]["NextMarker"][0]["#"]) > 0)
					{
						$marker = $response["ListBucketResult"]["#"]["NextMarker"][0]["#"];
						continue;
					}
					elseif ($lastKey !== null);
					{
						$marker = $lastKey;
						continue;
					}
				}

				break;
			}
			else
			{
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

		$additional_headers = array();
		if($this->_public)
			$additional_headers["x-amz-acl"] = "public-read";
		$additional_headers["Content-Type"] = $ContentType;

		$this->SetLocation($arBucket["LOCATION"]);
		$response = $this->SendRequest(
			$arBucket["SETTINGS"],
			'POST',
			$arBucket["BUCKET"],
			$filePathU,
			'?uploads=',
			'',
			$additional_headers
		);

		if(
			$this->status == 200
			&& is_array($response)
			&& isset($response["InitiateMultipartUploadResult"])
			&& is_array($response["InitiateMultipartUploadResult"])
			&& isset($response["InitiateMultipartUploadResult"]["#"])
			&& is_array($response["InitiateMultipartUploadResult"]["#"])
			&& isset($response["InitiateMultipartUploadResult"]["#"]["UploadId"])
			&& is_array($response["InitiateMultipartUploadResult"]["#"]["UploadId"])
			&& isset($response["InitiateMultipartUploadResult"]["#"]["UploadId"][0])
			&& is_array($response["InitiateMultipartUploadResult"]["#"]["UploadId"][0])
			&& isset($response["InitiateMultipartUploadResult"]["#"]["UploadId"][0]["#"])
			&& is_string($response["InitiateMultipartUploadResult"]["#"]["UploadId"][0]["#"])
		)
		{
			$NS = array(
				"filePath" => $filePath,
				"UploadId" => $response["InitiateMultipartUploadResult"]["#"]["UploadId"][0]["#"],
				"Parts" => array(),
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
		$filePath = '/'.trim($NS["filePath"], '/');
		if($arBucket["PREFIX"])
		{
			if(substr($filePath, 0, strlen($arBucket["PREFIX"])+2) != "/".$arBucket["PREFIX"]."/")
				$filePath = "/".$arBucket["PREFIX"].$filePath;
		}
		$filePath = CCloudUtil::URLEncode($filePath, "UTF-8");

		$this->SetLocation($arBucket["LOCATION"]);
		$this->SendRequest(
			$arBucket["SETTINGS"],
			'PUT',
			$arBucket["BUCKET"],
			$filePath,
			'?partNumber='.(count($NS["Parts"])+1).'&uploadId='.urlencode($NS["UploadId"]),
			$data
		);

		if($this->status == 200 && is_array($this->headers) && isset($this->headers["ETag"]))
		{
			$NS["Parts"][] = $this->headers["ETag"];
			return true;
		}
		else
		{
			return false;
		}
	}

	public function CompleteMultipartUpload($arBucket, &$NS)
	{
		$filePath = '/'.trim($NS["filePath"], '/');
		if($arBucket["PREFIX"])
		{
			if(substr($filePath, 0, strlen($arBucket["PREFIX"])+2) != "/".$arBucket["PREFIX"]."/")
				$filePath = "/".$arBucket["PREFIX"].$filePath;
		}
		$filePath = CCloudUtil::URLEncode($filePath, "UTF-8");

		$data = "";
		foreach($NS["Parts"] as $PartNumber => $ETag)
			$data .= "<Part><PartNumber>".($PartNumber+1)."</PartNumber><ETag>".$ETag."</ETag></Part>\n";

		$this->SetLocation($arBucket["LOCATION"]);
		$this->SendRequest(
			$arBucket["SETTINGS"],
			'POST',
			$arBucket["BUCKET"],
			$filePath,
			'?uploadId='.urlencode($NS["UploadId"]),
			"<CompleteMultipartUpload>".$data."</CompleteMultipartUpload>"
		);

		return $this->status == 200;
	}

	public function setPublic($state = true)
	{
		$this->_public = $state !== false;
	}

	public function setHeader($key, $value)
	{
		$this->set_headers[$key] = $value;
	}

	public function unsetHeader($key)
	{
		unset($this->set_headers[$key]);
	}

	public function getHeaders()
	{
		return $this->headers;
	}
}
