<?
IncludeModuleLangFile(__FILE__);

class CCloudSecurityService_AmazonS3
{
	protected $status = 0;
	protected $headers = array();
	protected $errno = 0;
	protected $errstr = '';
	protected $result = '';

	public function GetLastRequestStatus()
	{
		return $this->status;
	}

	public static function GetObject()
	{
		return new CCloudSecurityService_AmazonS3();
	}

	public static function GetID()
	{
		return "amazon_sts";
	}

	public static function GetName()
	{
		return "AWS Security Token Service";
	}

	public static function GetDefaultBucketControlPolicy($bucket, $prefix)
	{
		return array(
			'Statement' => array(
				array(
					'Effect' => 'Allow',
					'Action' => array(
						's3:DeleteObject',
						's3:GetObject',
						's3:PutObject',
						's3:PutObjectAcl'
					),
					'Resource' => 'arn:aws:s3:::'.$bucket.'/'.$prefix.'/*',
				),
				array(
					'Effect' => 'Allow',
					'Action' => array(
						's3:ListBucket'
					),
					'Resource' => 'arn:aws:s3:::'.$bucket,
					'Condition' => array(
						'StringLike' => array(
							's3:prefix' => $prefix."/*"
						),
					),
				),
			),
		);
	}

	public function GetFederationToken($arBucket, $Policy, $Name, $DurationSeconds = 129600/*36h*/)
	{
		global $APPLICATION;

		$response = $this->SendRequest(
			$arBucket["SETTINGS"]["ACCESS_KEY"],
			$arBucket["SETTINGS"]["SECRET_KEY"],
			'GET',
			$arBucket["BUCKET"],
			'/',
			array(
				'Action' => "GetFederationToken",
				'DurationSeconds' => intval($DurationSeconds),
				'Name' => $Name,
				'Policy' => $this->PHPToJSObject($Policy),
			)
		);

		if(
			is_array($response)
			&& isset($response["GetFederationTokenResponse"])
			&& is_array($response["GetFederationTokenResponse"])
			&& isset($response["GetFederationTokenResponse"]["#"])
			&& is_array($response["GetFederationTokenResponse"]["#"])
			&& isset($response["GetFederationTokenResponse"]["#"]["GetFederationTokenResult"])
			&& is_array($response["GetFederationTokenResponse"]["#"]["GetFederationTokenResult"])
			&& isset($response["GetFederationTokenResponse"]["#"]["GetFederationTokenResult"][0])
			&& is_array($response["GetFederationTokenResponse"]["#"]["GetFederationTokenResult"][0])
			&& isset($response["GetFederationTokenResponse"]["#"]["GetFederationTokenResult"][0]["#"])
			&& is_array($response["GetFederationTokenResponse"]["#"]["GetFederationTokenResult"][0]["#"])
			&& isset($response["GetFederationTokenResponse"]["#"]["GetFederationTokenResult"][0]["#"]["Credentials"])
			&& is_array($response["GetFederationTokenResponse"]["#"]["GetFederationTokenResult"][0]["#"]["Credentials"])
		)
		{
			$Credentials = $response["GetFederationTokenResponse"]["#"]["GetFederationTokenResult"][0]["#"]["Credentials"];

			if(
				isset($Credentials[0])
				&& is_array($Credentials[0])
				&& isset($Credentials[0]["#"])
				&& is_array($Credentials[0]["#"])
				&& isset($Credentials[0]["#"]["SessionToken"])
				&& is_array($Credentials[0]["#"]["SessionToken"])
				&& isset($Credentials[0]["#"]["SessionToken"][0])
				&& is_array($Credentials[0]["#"]["SessionToken"][0])
				&& isset($Credentials[0]["#"]["SessionToken"][0]["#"])
			)
				$SessionToken = $Credentials[0]["#"]["SessionToken"][0]["#"];
			else
				return 1;

			if(
				isset($Credentials[0])
				&& is_array($Credentials[0])
				&& isset($Credentials[0]["#"])
				&& is_array($Credentials[0]["#"])
				&& isset($Credentials[0]["#"]["SecretAccessKey"])
				&& is_array($Credentials[0]["#"]["SecretAccessKey"])
				&& isset($Credentials[0]["#"]["SecretAccessKey"][0])
				&& is_array($Credentials[0]["#"]["SecretAccessKey"][0])
				&& isset($Credentials[0]["#"]["SecretAccessKey"][0]["#"])
			)
				$SecretAccessKey = $Credentials[0]["#"]["SecretAccessKey"][0]["#"];
			else
				return 2;

			if(
				isset($Credentials[0])
				&& is_array($Credentials[0])
				&& isset($Credentials[0]["#"])
				&& is_array($Credentials[0]["#"])
				&& isset($Credentials[0]["#"]["AccessKeyId"])
				&& is_array($Credentials[0]["#"]["AccessKeyId"])
				&& isset($Credentials[0]["#"]["AccessKeyId"][0])
				&& is_array($Credentials[0]["#"]["AccessKeyId"][0])
				&& isset($Credentials[0]["#"]["AccessKeyId"][0]["#"])
			)
				$AccessKeyId = $Credentials[0]["#"]["AccessKeyId"][0]["#"];
			else
				return 3;

			return array(
				"ACCESS_KEY" => $AccessKeyId,
				"SECRET_KEY" => $SecretAccessKey,
				"SESSION_TOKEN" => $SessionToken,
			);
		}
		else
		{
			return false;
		}
	}

	public function SendRequest($access_key, $secret_key, $verb, $bucket, $file_name='/', $params='')
	{
		global $APPLICATION;
		$this->status = 0;

		$RequestMethod = $verb;
		$RequestHost = "sts.amazonaws.com";
		$RequestURI = "/";
		$RequestParams = "";

		$params['SignatureVersion'] = 2;
		$params['SignatureMethod'] = 'HmacSHA1';
		$params['AWSAccessKeyId'] = $access_key;
		$params['Timestamp'] = gmdate('Y-m-d').'T'.gmdate('H:i:s');//.preg_replace("/(\d\d)\$/", ":\\1", date("O"));
		$params['Version'] = '2011-06-15';
		ksort($params);
		foreach($params as $name => $value)
		{
			if($RequestParams != '')
				$RequestParams .= '&';
			$RequestParams .= urlencode($name)."=".urlencode($value);
		}

		$StringToSign =  "$RequestMethod\n"
				."$RequestHost\n"
				."$RequestURI\n"
				."$RequestParams"
		;
		$Signature = urlencode(base64_encode($this->hmacsha1($StringToSign, $secret_key)));

		$obRequest = new CHTTP;
		$obRequest->Query($RequestMethod, $RequestHost, 443, $RequestURI."?$RequestParams&Signature=$Signature", false, 'ssl://');
		$this->status = $obRequest->status;
		$this->headers = $obRequest->headers;
		$this->errno = $obRequest->errno;
		$this->errstr = $obRequest->errstr;
		$this->result = $obRequest->result;

		if($obRequest->status == 200)
		{
			if($obRequest->result)
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
				$APPLICATION->ThrowException(GetMessage('CLO_SECSERV_S3_XML_PARSE_ERROR', array('#errno#'=>1)));
				return false;
			}
			else
			{
				//Empty success result
				return array();
			}
		}
		elseif($obRequest->status > 0)
		{
			if($obRequest->result)
			{
				$APPLICATION->ThrowException(GetMessage('CLO_SECSERV_S3_XML_ERROR', array('#errmsg#'=>$obRequest->result)));
				return false;
			}
			$APPLICATION->ThrowException(GetMessage('CLO_SECSERV_S3_XML_PARSE_ERROR', array('#errno#'=>2)));
			return false;
		}
		else
		{
			$APPLICATION->ThrowException(GetMessage('CLO_SECSERV_S3_XML_PARSE_ERROR', array('#errno#'=>3)));
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

	public function PhpToJSObject($arData, $bWS = false, $bSkipTilda = false)
	{
		static $aSearch = array("\r", "\n");
		if(is_array($arData))
		{
			if($arData == array_values($arData))
			{
				foreach($arData as $key => $value)
				{
					if(is_array($value))
					{
						$arData[$key] = $this->PhpToJSObject($value, $bWS, $bSkipTilda);
					}
					elseif(is_bool($value))
					{
						if($value === true)
							$arData[$key] = 'true';
						else
							$arData[$key] = 'false';
					}
					else
					{
						if(preg_match("#['\"\\n\\r<\\\\]#", $value))
							$arData[$key] = '"'.CUtil::JSEscape($value).'"';
						else
							$arData[$key] = '"'.$value.'"';
					}
				}
				return '['.implode(',', $arData).']';
			}

			$sWS = ','.($bWS ? "\n" : '');
			$res = ($bWS ? "\n" : '').'{';
			$first = true;
			foreach($arData as $key => $value)
			{
				if ($bSkipTilda && substr($key, 0, 1) == '~')
					continue;

				if($first)
					$first = false;
				else
					$res .= $sWS;

				if(preg_match("#['\"\\n\\r<\\\\]#", $key))
					$res .= '"'.str_replace($aSearch, '', CUtil::JSEscape($key)).'":';
				else
					$res .= '"'.$key.'":';

				if(is_array($value))
				{
					$res .= $this->PhpToJSObject($value, $bWS, $bSkipTilda);
				}
				elseif(is_bool($value))
				{
					if($value === true)
						$res .= 'true';
					else
						$res .= 'false';
				}
				else
				{
					if(preg_match("#['\"\\n\\r<\\\\]#", $value))
						$res .= '"'.CUtil::JSEscape($value).'"';
					else
						$res .= '"'.$value.'"';
				}
			}
			$res .= ($bWS ? "\n" : '').'}';

			return $res;
		}
		elseif(is_bool($arData))
		{
			if($arData === true)
				return 'true';
			else
				return 'false';
		}
		else
		{
			if(preg_match("#['\"\\n\\r<\\\\]#", $arData))
				return '"'.CUtil::JSEscape($arData).'"';
			else
				return '"'.$arData.'"';
		}
	}
}
?>