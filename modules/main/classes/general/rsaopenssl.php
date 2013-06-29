<?
class CRsaOpensslProvider extends CRsaProvider
{
	//$_PRIV - secret key in PEM format
	protected $_PRIV = '';

	public function SetKeys($arKeys)
	{
		parent::SetKeys($arKeys);
		$this->_PRIV = $arKeys["PRIV"];
	}

	static public function LoadKeys()
	{
		$arKeys = unserialize(COption::GetOptionString("main", "~rsa_keys_openssl", ""));
		if(!is_array($arKeys))
			return false;
		$arKeys["PRIV"] = COption::GetOptionString("main", "~rsa_key_pem", "");
		return $arKeys;
	}

	static public function SaveKeys($arKeys)
	{
		$privKey = $arKeys["PRIV"];
		unset($arKeys["PRIV"]);
		COption::SetOptionString("main", "~rsa_keys_openssl", serialize($arKeys));
		COption::SetOptionString("main", "~rsa_key_pem", $privKey);
	}
	
	public function Decrypt($data)
	{
		$key = openssl_pkey_get_private($this->_PRIV);
	
		$out = '';
		$blocks = explode(' ', $data);
		foreach($blocks as $block)
		{
			$out1 = '';
			openssl_private_decrypt(strrev(base64_decode($block)), $out1, $key, OPENSSL_NO_PADDING);
			$out1 = strrev($out1);
	 		$out .= $out1;
		}
		$out = rtrim($out);
	
		return $out;
	}

	static public function Keygen($keylen=false)
	{
		if($keylen === false)
			$keylen = 1024;
		else
			$keylen = intval($keylen);

		$fname = $_SERVER["DOCUMENT_ROOT"]."/bitrix/tmp/openssl.cnf";
		if(!file_exists($fname))
		{
			CheckDirPath($fname);
			file_put_contents($fname, '');
		}

		$keys = openssl_pkey_new(array(
			"private_key_type"=>OPENSSL_KEYTYPE_RSA, 
			"private_key_bits"=>$keylen,
		    "config" => $fname,
		));

		if($keys)
		{
			openssl_pkey_export($keys, $privkey, null, array("config" => $fname));
			$k = self::get_openssl_key_details($privkey);

			if(is_array($k))
			{
				return array(
					"M" => base64_encode(strrev($k['n'])),
					"E" => base64_encode(strrev($k['e'])),
					"D" => base64_encode(strrev($k['d'])),
					"PRIV" => $privkey,
					"chunk" => $keylen/8,
				);
			}
		}
		return false;
	}

	private static function get_openssl_key_details($key)
	{
		//PEM to DER
		$lines = explode("\n", trim($key));
		unset($lines[count($lines)-1]);
		unset($lines[0]);
		$der = implode('', $lines);
		$der = base64_decode($der);

		//DER is in ASN.1 notation
		$body = new CASNReader();
		$body->Read($der);
		$bodyItems = $body->GetSequence();
	
		if(!empty($bodyItems))
		{
			if(is_object($bodyItems[1]) && is_object($bodyItems[2]) && is_object($bodyItems[3]))
			{
				$n = $bodyItems[1]->GetValue();
				$e = $bodyItems[2]->GetValue();
				$d = $bodyItems[3]->GetValue();
			
				return array("n"=>$n, "e"=>$e, "d"=>$d);
			}
			elseif(is_object($bodyItems[2]))
			{
				$body = new CASNReader();
				$body->Read($bodyItems[2]->GetValue());
				$bodyItems = $body->GetSequence();
				
				if(is_object($bodyItems[1]) && is_object($bodyItems[2]) && is_object($bodyItems[3]))
				{
					$n = $bodyItems[1]->GetValue();
					$e = $bodyItems[2]->GetValue();
					$d = $bodyItems[3]->GetValue();
	
					return array("n"=>$n, "e"=>$e, "d"=>$d);
				}
			}
		}
		return false;
	}
}
?>