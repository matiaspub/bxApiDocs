<?php
namespace Bitrix\Sale\PaySystem;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\BusinessValue;
use Bitrix\Sale\Internals\PersonTypeTable;
use Bitrix\Sale\Internals\YandexSettingsTable;

Loc::loadMessages(__FILE__);

class YandexCert
{
	static public $pkey = null;
	static public $csr = null;
	static public $sign = null;
	static public $cn = '';
	static public $fatalError = false;
	static public $errors = array();

	/**
	 * @param $paySystemId
	 * @param $personTypeId
	 * @return mixed
	 */
	static private function getSid($paySystemId, $personTypeId)
	{
		return BusinessValue::get('YANDEX_SHOP_ID', 'PAYSYSTEM_'.$paySystemId, $personTypeId);
	}

	/**
	 * @param $paySystemId
	 * @param $personTypeId
	 */
	static private function generate($paySystemId, $personTypeId)
	{
		$shopId = self::getSid($paySystemId, $personTypeId);
		self::$cn = "/business/bx-cn-yandex-".$shopId;

		$config = array(
			"digest_alg" => "sha1",
			"private_key_bits" => 2048,
			"private_key_type" => OPENSSL_KEYTYPE_RSA,
		);

		$dnFull = array(
			"countryName" => "RU",
			"stateOrProvinceName" => "Russia",
			"localityName" => "Moscow",
			"commonName" => self::$cn,
		);

		$res = openssl_pkey_new($config);
		$csr_origin = openssl_csr_new($dnFull, $res);
		if ($csr_origin === false)
			return;
		$csr_full = "";
		openssl_pkey_export($res, self::$pkey);
		openssl_csr_export($csr_origin, self::$csr);

		openssl_csr_export($csr_origin, $csr_full, false);
		preg_match('"Signature Algorithm\: (.*)-----BEGIN"ims', $csr_full, $sign);
		$sign = str_replace("\t", "", $sign);
		if ($sign)
		{
			$sign = $sign[1];
			$a = explode("\n", $sign);
			unset($a[0]);
			$sign = str_replace("         ", "", trim(join("\n", $a)));
		}
		self::$sign = $sign;

		//Save csr, pkey, sign
		$dbRes = YandexSettingsTable::getById($shopId);
		if ($dbRes->fetch())
			YandexSettingsTable::update($shopId, array('SIGN' => self::$sign, 'CSR' => self::$csr, 'PKEY' => self::$pkey, 'CERT' => ''));
		else
			YandexSettingsTable::add(array('SHOP_ID' => $shopId, 'SIGN' => self::$sign, 'CSR' => self::$csr, 'PKEY' => self::$pkey));
    }

	/**
	 * @param $paySystemId
	 * @param $personTypeId
	 * @param bool $all
	 * @throws \Exception
	 */
    static public function clear($paySystemId, $personTypeId, $all = false)
	{
		$shopId = self::getSid($paySystemId, $personTypeId);
		if ($shopId)
		{
			if ($all)
				$settings = array('CERT' => '', 'SIGN' => '', 'CSR' => '', 'PKEY' => '');
			else
				$settings = array('CERT' => '');

			YandexSettingsTable::update($shopId, $settings);
		}
    }

	/**
	 * @param $paySystemId
	 * @param $personTypeId
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	static public function getCn($paySystemId, $personTypeId)
	{
		$yandexCsr = self::getValue('CSR', $paySystemId, $personTypeId);
		if ($yandexCsr === '')
		{
			self::loadFromOptions($paySystemId);
			$yandexCsr = self::getValue('CSR', $paySystemId, $personTypeId);
		}

		$subjects = openssl_csr_get_subject($yandexCsr);
		if (!isset($subjects['CN']) || empty($subjects['CN']))
		{
			self::generate($paySystemId, $personTypeId);
			return self::$cn;
		}
		else
		{
			return $subjects['CN'];
		}
	}

	/**
	 * @param $paySystemId
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Exception
	 */
	static private function loadFromOptions($paySystemId)
	{
		$dbRes = PersonTypeTable::getList(array('select' => array('ID', 'PT_SITE_ID' => 'PERSON_TYPE_SITE.SITE_ID')));
		while ($data = $dbRes->fetch())
		{
			$csr = Option::get('yandexmoney.ycms', 'KASSA_MWS_CSR', '', $data['PT_SITE_ID']);
			if ($csr === '')
				continue;

			$shopId = self::getSid($paySystemId, $data['ID']);
			$csr = Option::get('yandexmoney.ycms', 'KASSA_MWS_CSR', '', $data['PT_SITE_ID']);
			$pkey = Option::get('yandexmoney.ycms', 'KASSA_MWS_PKEY', '', $data['PT_SITE_ID']);
			$sign = Option::get('yandexmoney.ycms', 'KASSA_MWS_SIGN', '', $data['PT_SITE_ID']);
			$cert = Option::get('yandexmoney.ycms', 'KASSA_MWS_CERT', '', $data['PT_SITE_ID']);

			$dbRes = YandexSettingsTable::getById($shopId);
			if (!$dbRes->fetch())
				YandexSettingsTable::add(array('SHOP_ID' => $shopId, 'CSR' => $csr, 'PKEY' => $pkey, 'SIGN' => $sign, 'CERT' => $cert));
		}
	}

	/**
	 * @param $paySystemId
	 * @param $personTypeId
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	static public function isLoaded($paySystemId, $personTypeId)
	{
		$cert = self::getValue('CERT', $paySystemId, $personTypeId);
        return !empty($cert);
	}

	/**
	 * @param $file
	 * @param $paySystemId
	 * @param $personTypeId
	 */
	static public function setCert($file, $paySystemId, $personTypeId)
	{
		if (!empty($file['name']))
		{
			if (substr($file['name'], -4) != '.cer')
				self::$errors[$personTypeId][]  = Loc::getMessage('YANDEX_CERT_ERR_EXT');
			elseif ($file['error'] != UPLOAD_ERR_OK)
				self::$errors[$personTypeId][]  = Loc::getMessage('YANDEX_CERT_ERR_LOAD');
			elseif (filesize($file['tmp_name']) > 2048)
				self::$errors[$personTypeId][]  = Loc::getMessage('YANDEX_CERT_ERR_SIZE');
		}
		else
		{
			self::$errors[$personTypeId][]  = Loc::getMessage('YANDEX_CERT_ERR_LOAD');
		}

		if (empty(self::$errors))
		{
			$cert = file_get_contents($file['tmp_name']);
			$cert_info = openssl_x509_parse($cert);
			if (isset($cert_info['subject']['CN']))
			{
				if ($cert_info['subject']['CN'] != self::getCn($paySystemId, $personTypeId))
				{
					self::$errors[$personTypeId][] = Loc::getMessage('YANDEX_CERT_ERR_CN');
				}
				else
				{
					$shopId = self::getSid($paySystemId, $personTypeId);
					YandexSettingsTable::update($shopId, array('CERT' => $cert));
				}
			}
			else
			{
				self::$errors[$personTypeId][] = Loc::getMessage('YANDEX_CERT_ERR_NULL');
			}
		}
	}

	/**
	 * @param $paySystemId
	 * @param $personTypeId
	 * @return mixed
	 */
	static public function getSign($paySystemId, $personTypeId)
	{
		return self::getValue('SIGN', $paySystemId, $personTypeId);
	}

	/**
	 * @param $paySystemId
	 * @param $personTypeId
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	static public function getCsr($paySystemId, $personTypeId)
	{
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=csr_for_yamoney.csr');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        echo self::getValue('CSR', $paySystemId, $personTypeId);
        die();
    }

	/**
	 * @param $field
	 * @param $paySystemId
	 * @param $personTypeId
	 * @return mixed|string
	 */
	static public function getValue($field, $paySystemId, $personTypeId)
	{
		$shopId = self::getSid($paySystemId, $personTypeId);
		if ($shopId)
		{
			$dbRes = YandexSettingsTable::getList(array('filter' => array('SHOP_ID' => $shopId)));
			if ($data = $dbRes->fetch())
				return $data[$field];
		}

		return '';
	}
}