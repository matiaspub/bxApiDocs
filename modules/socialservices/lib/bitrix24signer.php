<?
namespace Bitrix\Socialservices;

use Bitrix\Main\Web\Json;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Security\Sign\HmacAlgorithm;

class Bitrix24Signer
	extends Signer
{
	static public function __construct()
	{
		parent::__construct(new HmacAlgorithm('sha256'));
	}

	static public function sign($value, $salt = null)
	{
		$valueEnc = base64_encode(Json::encode($value));
		return parent::sign($valueEnc, $salt);
	}

	static public function unsign($signedValue, $salt = null)
	{
		$encodedValue = parent::unsign($signedValue, $salt);
		return Json::decode(base64_decode($encodedValue));
	}

	/**
	 * Return encoded signature
	 *
	 * @param string $value
	 * @return mixed
	 */
	protected function encodeSignature($value)
	{
		return base64_encode($value);
	}

	/**
	 * Return decoded signature
	 *
	 * @param string $value
	 * @return string
	 */
	protected function decodeSignature($value)
	{
		return base64_decode($value);
	}
}