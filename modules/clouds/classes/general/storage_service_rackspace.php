<?
class CCloudStorageService_RackSpaceCloudFiles extends CCloudStorageService_OpenStackStorage
{
	public static function GetObject()
	{
		return new CCloudStorageService_RackSpaceCloudFiles();
	}

	public static function GetID()
	{
		return "rackspace_storage";
	}

	public static function GetName()
	{
		return "Rackspace Cloud Files";
	}

	public static function _GetToken($host, $user, $key)
	{
		$result = false;
		$cache_id = "v0|".$host."|".$user."|".$key;
		$obCache = new CPHPCache;

		if($obCache->InitCache(3600, $cache_id, "/"))
		{
			$result = $obCache->GetVars();
		}
		else
		{
			$obRequest = new CHTTP;
			$obRequest->additional_headers["X-Auth-User"] = $user;
			$obRequest->additional_headers["X-Auth-Key"] = $key;
			$obRequest->Query("GET", $host, 80, "/v1.0");

			if($obRequest->status == 301 && strlen($obRequest->headers["Location"]) > 0)
			{
				if(preg_match("#^https://(.*?)(/.*)\$#", $obRequest->headers["Location"], $arNewLocation))
				{
					$obRequest = new CHTTP;
					$obRequest->additional_headers["X-Auth-User"] = $user;
					$obRequest->additional_headers["X-Auth-Key"] = $key;
					@$obRequest->Query("GET", $arNewLocation[1], 443, "/v1.0", false, "ssl://");

					if($obRequest->status == 204)
					{
						if(preg_match("#^https://(.*?)(/.*)\$#", $obRequest->headers["X-Storage-Url"], $arStorage))
						{
							$result = $obRequest->headers;
							$result["X-Storage-Host"] = $arStorage[1];
							$result["X-Storage-Port"] = 443;
							$result["X-Storage-Urn"] = $arStorage[2];
							$result["X-Storage-Proto"] = "ssl://";
						}
					}
				}
			}
		}

		if(is_array($result))
		{
			if($obCache->StartDataCache())
				$obCache->EndDataCache($result);
		}

		return $result;
	}

	public function SendCDNRequest($settings, $verb, $bucket, $file_name='', $params='', $content=false, $additional_headers=array())
	{
		$arToken = $this->_GetToken($settings["HOST"], $settings["USER"], $settings["KEY"]);
		if(!$arToken)
			return false;

		if(isset($arToken["X-CDN-Management-Url"]))
		{
			if(preg_match("#^http://(.*?)(|:\d+)(/.*)\$#", $arToken["X-CDN-Management-Url"], $arCDN))
			{
				$Host = $arCDN[1];
				$Port = $arCDN[2]? substr($arCDN[2], 1): 80;
				$Urn = $arCDN[3];
				$Proto = "";
			}
			elseif(preg_match("#^https://(.*?)(|:\d+)(/.*)\$#", $arToken["X-CDN-Management-Url"], $arCDN))
			{
				$Host = $arCDN[1];
				$Port = $arCDN[2]? substr($arCDN[2], 1): 443;
				$Urn = $arCDN[3];
				$Proto = "ssl://";
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}

		$obRequest = new CHTTP;
		$obRequest->additional_headers["X-Auth-Token"] = $arToken["X-Auth-Token"];
		foreach($additional_headers as $key => $value)
			$obRequest->additional_headers[$key] = $value;

		$obRequest->Query(
			$verb,
			$Host,
			$Port,
			$Urn.CCloudUtil::URLEncode("/".$bucket.$file_name.$params, "UTF-8"),
			$content,
			$Proto
		);
		return $obRequest;
	}

	public function CreateBucket($arBucket)
	{
		global $APPLICATION;

		$obRequest = $this->SendRequest(
			$arBucket["SETTINGS"],
			"PUT",
			$arBucket["BUCKET"]
		);

		//CDN Enable
		if($this->status == 201)
		{
			$obCDNRequest = $this->SendCDNRequest(
				$arBucket["SETTINGS"],
				"PUT",
				$arBucket["BUCKET"],
				'', //filename
				'', //params
				false, //content
				array(
					"X-CDN-Enabled" => "True",
				)
			);
		}

		return ($this->status == 201)/*Created*/ || ($this->status == 202) /*Accepted*/;
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
			$result = false;
			$cache_id = md5(serialize($arBucket));
			$obCache = new CPHPCache;
			if($obCache->InitCache(3600, $cache_id, "/"))
			{
				$result = $obCache->GetVars();
			}
			else
			{
				$obCDNRequest = $this->SendCDNRequest(
					$arBucket["SETTINGS"],
					"HEAD",
					$arBucket["BUCKET"]
				);
				if(is_object($obCDNRequest))
				{
					if($obCDNRequest->status == 204)
					{
						$result = array();
						foreach($obCDNRequest->headers as $key => $value)
							$result[strtolower($key)] = $value;
					}
				}
			}

			if($obCache->StartDataCache())
				$obCache->EndDataCache($result);

			if(is_array($result))
				$host = $result["x-cdn-uri"];
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
}
?>