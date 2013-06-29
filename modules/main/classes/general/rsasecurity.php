<?
abstract class CRsaProvider
{
	//$_M, $_E - public components
	//$_D - secret component
	//$_chunk - key length in bytes
	protected $_M = '';
	protected $_E = '';
	protected $_D = '';
	protected $_chunk = 0;

	public function SetKeys($arKeys)
	{
		$this->_M = $arKeys["M"];
		$this->_E = $arKeys["E"];
		$this->_D = $arKeys["D"];
		$this->_chunk = $arKeys["chunk"];
	}

	public function GetPublicKey()
	{
		return array("M"=>$this->_M, "E"=>$this->_E, "chunk"=>$this->_chunk);
	}

	abstract public function LoadKeys();
	abstract public function SaveKeys($arKeys);
	abstract public function Decrypt($data);
	abstract public function Keygen($keylen=false);
}

class CRsaSecurity
{
	//max size of encrypted packet against DOS attacks.
	const MAX_ENCRIPTED_DATA = 40120;

	//error codes
	const ERROR_NO_LIBRARY = 1; //no crypto library found
	const ERROR_EMPTY_DATA = 2; //no encrypted data
	const ERROR_BIG_DATA = -3; //too big encrypted data
	const ERROR_DECODE = -4; //decoding error
	const ERROR_INTEGRITY = -5; //integrity check error
	const ERROR_SESS_VALUE = -6; //no session control value
	const ERROR_SESS_CHECK = -7; //session control value does not match

	protected $provider = false;
	protected $lib = '';

	public function __construct($lib=false)
	{
		if(extension_loaded('openssl') && ($lib == false || $lib == 'openssl'))
		{
			$this->provider = new CRsaOpensslProvider();
			$this->lib = 'openssl';
		}
		elseif(extension_loaded('bcmath') && ($lib == false || $lib == 'bcmath'))
		{
			$this->provider = new CRsaBcmathProvider();
			$this->lib = 'bcmath';
		}
	}

	public static function Possible()
	{
		return (extension_loaded('openssl') || extension_loaded('bcmath'));
	}

	public function SetKeys($arKeys)
	{
		if($this->provider)
			$this->provider->SetKeys($arKeys);
	}

	public function LoadKeys()
	{
		if($this->provider)
		{
			$arKeys = $this->provider->LoadKeys();
			if(is_array($arKeys) && $arKeys["M"] <> '' && $arKeys["E"] <> '' && $arKeys["D"] <> '')
				return $arKeys;
		}
		return false;
	}

	public function SaveKeys($arKeys)
	{
		if($this->provider)
			$this->provider->SaveKeys($arKeys);
	}

	public function Keygen($keylen=false)
	{
		if($this->provider)
			return $this->provider->Keygen($keylen);
		return false;
	}

	public function AddToForm($formid, $arParams)
	{
		if(!$this->provider)
			return;

		$formid = preg_replace("/[^a-z0-9_]/is", "", $formid);

		if(!isset($_SESSION['__STORED_RSA_RAND']))
			$_SESSION['__STORED_RSA_RAND'] = $this->GetNewRsaRand();

		$arSafeParams = array();
		foreach($arParams as $param)
			$arSafeParams[] = preg_replace("/[^a-z0-9_\\[\\]]/is", "", $param);

		$arData = array(
			"formid" => $formid,
			"key" => $this->provider->GetPublicKey(),
			"rsa_rand" => $_SESSION['__STORED_RSA_RAND'],
			"params" => $arSafeParams,
		);

		CJSCore::Init();
		$GLOBALS["APPLICATION"]->AddHeadScript("/bitrix/js/main/rsasecurity.js");

		echo '
<script type="text/javascript">
top.BX.defer(top.rsasec_form_bind)('.CUtil::PhpToJSObject($arData).');
</script>
';
	}

	public function AcceptFromForm($arParams)
	{
		if(!$this->provider)
			return self::ERROR_NO_LIBRARY; //no crypto library found

		$data = $_REQUEST['__RSA_DATA'];

		unset($_POST['__RSA_DATA']);
		unset($_REQUEST['__RSA_DATA']);
		unset($GLOBALS['__RSA_DATA']);

		if($data == '')
			return self::ERROR_EMPTY_DATA; //no encrypted data

		if(strlen($data) >= self::MAX_ENCRIPTED_DATA)
			return self::ERROR_BIG_DATA; //too big encrypted data

		$data = $this->provider->Decrypt($data);
		if($data == '')
			return self::ERROR_DECODE; //decoding error

		$data1 = substr($data, 0, -47);
		$sha1 = substr($data, -40);

		if($sha1 <> sha1($data1))
	  		return self::ERROR_INTEGRITY; //integrity check error

		parse_str($data, $accepted_params);
		if($accepted_params['__RSA_RAND'] == '')
			return self::ERROR_SESS_VALUE; //no session control value

		if($accepted_params['__RSA_RAND'] <> $_SESSION['__STORED_RSA_RAND'])
			return self::ERROR_SESS_CHECK; //session control value does not match

		CUtil::decodeURIComponent($accepted_params);
		foreach($arParams as $k)
		{
			if(isset($accepted_params[$k]))
			{
				if(is_array($accepted_params[$k]))
				{
					foreach($accepted_params[$k] as $key=>$val)
						$GLOBALS[$k][$key] = $_REQUEST[$k][$key] = $_POST[$k][$key] = $val;
				}
				else
				{
					$GLOBALS[$k] = $_REQUEST[$k] = $_POST[$k] = $accepted_params[$k];
				}
			}
		}

		return 0; //OK
	}

	public function GetLib()
	{
		return $this->lib;
	}

	protected function GetNewRsaRand()
	{
		return uniqid("", true);
	}
}
?>