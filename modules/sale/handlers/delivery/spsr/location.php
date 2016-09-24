<?
namespace Sale\Handlers\Delivery\Spsr;

use Bitrix\Main\Text\Encoding;
use Bitrix\Sale\Location\ExternalServiceTable;
use Bitrix\Sale\Location\ExternalTable;
use Bitrix\Sale\Result;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Location\LocationTable;

Loc::loadMessages(__FILE__);

class Location
{
	const EXTERNAL_SERVICE_CODE = 'SPSR';

	public static function getInner($externalCode)
	{
		if(strlen($externalCode) <= 0)
			return 0;

		$srvId = self::getExternalServiceId();

		if($srvId <= 0)
			return 0;

		$res = ExternalTable::getList(array(
			'filter' => array(
				'=XML_ID' => $externalCode,
				'=SERVICE_ID' => $srvId
				)
		));

		if($loc = $res->fetch())
			return $loc['ID'];

		return 0;
	}

	public static function getExternal($locationId)
	{
		if(strlen($locationId) <= 0)
			return '';

		$srvId = self::getExternalServiceId();

		if($srvId <= 0)
			return 0;

		$res = LocationTable::getList(array(
			'filter' => array(
				array(
					'LOGIC' => 'OR',
					'=CODE' => $locationId,
					'=ID' => $locationId
				),
				'=EXTERNAL.SERVICE_ID' => $srvId
			),
			'select' => array(
				'ID', 'CODE',
				'XML_ID' => 'EXTERNAL.XML_ID'
			)
		));

		if($loc = $res->fetch())
			return $loc['XML_ID'];

		return '';
	}

	protected static function getExternalServiceId()
	{
		static $result = null;

		if($result !== null)
			return $result;

		$res = ExternalServiceTable::getList(array(
			'filter' => array('=CODE' => self::EXTERNAL_SERVICE_CODE)
		));

		if($srv = $res->fetch())
		{
			$result = $srv['ID'];
			return $result;
		}

		$res = ExternalServiceTable::add(array('CODE' => self::EXTERNAL_SERVICE_CODE));

		if($res->isSuccess())
			$result =  $res->getId();
		else
			$result = 0;

		return $result;
	}

	static public function getLocationsRequest($cityName = '', $countryName = '')
	{
		set_time_limit(0);
		$result = new Result();

		$requestData = '
			<root xmlns="http://spsr.ru/webapi/Info/GetCities/1.0">
				<p:Params Name="WAGetCities" Ver="1.0" xmlns:p="http://spsr.ru/webapi/WA/1.0" />
				<GetCities CityName="'.strtolower($cityName).'" CountryName="'.strtolower($countryName).'" />
			</root>';

		$request = new Request();
		$res = $request->send($requestData);

		if($res->isSuccess())
		{
			$data = $res->getData();
			$xmlAnswer = new \SimpleXMLElement($data[0]);
			$cities = array();

			foreach($xmlAnswer->City->Cities as $city)
			{
				$cities[(string)$city['City_ID']."|".(string)$city['City_owner_ID']] = array(
					'City_ID' => (string)$city['City_ID'],
					'City_owner_ID' => (string)$city['City_owner_ID'],
					'CityName' => self::utfDecode(
						(string)$city['CityName']
					),
					'RegionName' => self::utfDecode(
						(string)$city['RegionName']
					)
				);
			}

			if(!empty($cities))
				self::setMap($cities);
		}
		else
		{
			$result->addErrors($res->getErrors());
		}

		return $result;
	}

	protected static function utfDecode($str)
	{
		if(strtolower(SITE_CHARSET) != 'utf-8')
			$str = Encoding::convertEncoding($str, 'UTF-8', SITE_CHARSET);

		return $str;
	}

	protected static function setMap(array $cities)
	{
		if(empty($cities))
			return;

		$srvId = self::getExternalServiceId();

		$res = ExternalTable::getList(array(
			'filter' => array(
				'=XML_ID' => array_keys($cities),
				'=SERVICE_ID' => $srvId
			)
		));

		while($map = $res->fetch())
			unset($cities[$map['XML_ID']]);

		if(empty($cities))
			return;

		foreach($cities as $xmlId => $city)
		{
			$locId = self::getLocationIdByNames($city['CityName'], $city['RegionName']);

			if($locId > 0)
			{
				ExternalTable::add(array(
					'SERVICE_ID' => $srvId,
					'LOCATION_ID' => $locId,
					'XML_ID' => $city['City_ID']."|".$city['City_owner_ID']
				));
			}

			unset($cities[$xmlId]);
		}
	}

	protected static function getLocationIdByNames($cityName, $regionName)
	{
		$regionName1 = ToUpper(str_replace('.', '', trim($regionName)));
		$regionName2 = '';
		$regionName3 = '';

		$resp = Loc::getMessage("SALE_DLV_SRV_SPSR_RESP");
		$republic = Loc::getMessage("SALE_DLV_SRV_SPSR_REPUBLIC");
		$aut = Loc::getMessage("SALE_DLV_SRV_SPSR_AUT");
		$autonomous = Loc::getMessage("SALE_DLV_SRV_SPSR_AUTONOMOUS");

		if($regionName == Loc::getMessage('SALE_DLV_SRV_SPSR_LOC_EX_CHUV_1'))
		{
			$regionName1 = Loc::getMessage('SALE_DLV_SRV_SPSR_LOC_EX_CHUV_2');
		}
		elseif($regionName == Loc::getMessage('SALE_DLV_SRV_SPSR_LOC_EX_KR_1'))
		{
			$regionName1 = Loc::getMessage('SALE_DLV_SRV_SPSR_LOC_EX_KR_2');
		}
		elseif($regionName == Loc::getMessage('SALE_DLV_SRV_SPSR_LOC_EX_HM_1'))
		{
			$regionName1 = Loc::getMessage('SALE_DLV_SRV_SPSR_LOC_EX_HM_2');
		}
		elseif($regionName == Loc::getMessage('SALE_DLV_SRV_SPSR_LOC_EX_EAO_1'))
		{
			$regionName1 = Loc::getMessage('SALE_DLV_SRV_SPSR_LOC_EX_EAO_2');
		}
		elseif(strpos($regionName1, $resp))
		{
			$regionName2 = ToUpper(preg_replace('/(.*)\s('.$resp.')$/i'.BX_UTF_PCRE_MODIFIER, $republic.' $1', $regionName1));
			$regionName3 = ToUpper(preg_replace('/(.*)\s('.$resp.')$/i'.BX_UTF_PCRE_MODIFIER, '$1 '.$republic, $regionName1));
		}
		elseif(strpos($regionName1, $aut))
		{
			$regionName2 = ToUpper(preg_replace('/(.*)\s('.$aut.')\s(.*)$/i'.BX_UTF_PCRE_MODIFIER, '$1 '.$autonomous.' $3', $regionName1));
		}

		$ids = array();

		$cityName = ToUpper(
			trim(
				preg_replace('/\(.*\)/i'.BX_UTF_PCRE_MODIFIER, '', $cityName)
			)
		);

		$res = LocationTable::getList(array(
			'filter' => array(
				'=NAME.NAME_UPPER' => ToUpper($cityName),
				'=NAME.LANGUAGE_ID' => LANGUAGE_ID,
				'=PARENTS.NAME.LANGUAGE_ID' => LANGUAGE_ID
			),
			'select' => array(
				'ID',
				'NAME_UPPER' => 'NAME.NAME_UPPER',
				'PARENTS_TYPE_CODE' => 'PARENTS.TYPE.CODE' ,
				'PARENTS_NAME_UPPER' => 'PARENTS.NAME.NAME_UPPER'
			)
		));

		while($loc = $res->fetch())
		{
			if(!in_array($loc['ID'], $ids))
				$ids[] = $loc['ID'];

			if($loc['PARENTS_TYPE_CODE'] == 'REGION')
			{
				if(
					strpos($loc['PARENTS_NAME_UPPER'], ToUpper($regionName1)) !== false
					|| (strlen($regionName2) > 0 && strpos($loc['PARENTS_NAME_UPPER'], ToUpper($regionName2)) !== false)
					|| (strlen($regionName3) > 0 && strpos($loc['PARENTS_NAME_UPPER'], ToUpper($regionName3)) !== false)
				)
				{
					return $loc['ID'];
				}
			}
		}

		return current($ids);
	}

	public static function exportToCsv($path)
	{
		set_time_limit(0);
		$srvId = self::getExternalServiceId();

		if($srvId <= 0)
			return false;

		$res = LocationTable::getList(array(
			'filter' => array(
				'=EXTERNAL.SERVICE_ID' => $srvId
			),
			'select' => array(
				'CODE',
				'XML_ID' => 'EXTERNAL.XML_ID'
			)
		));

		$content = '';

		while($row = $res->fetch())
			if(strlen($row['CODE']) > 0)
				$content .= $row['CODE'].";".$row['XML_ID']."\n";


		return file_put_contents($path, $content);
	}

	public static function importFromCsv($path)
	{
		set_time_limit(0);
		$imported  = 0;

		$content = file_get_contents($path);

		if($content === false)
			return false;

		$lines = explode("\n", $content);

		if(!is_array($lines))
			return false;

		$srvId = self::getExternalServiceId();

		if($srvId <= 0)
			return false;

		foreach($lines as $line)
		{
			$codes = explode(';', $line);

			if(!is_array($codes) || count($codes) != 2)
				continue;

			$res = LocationTable::getList(array(
				'filter' => array('=CODE' => $codes[0]),
				'select' => array('ID')
			));

			if(!$loc = $res->fetch())
				continue;

			$res = ExternalTable::add(array(
				'SERVICE_ID' => $srvId,
				'LOCATION_ID' => $loc['ID'],
				'XML_ID' => $codes[1]
			));

			if($res->isSuccess())
				$imported++;
		}

		return $imported;
	}

	public static function install()
	{
		$imported = self::importFromCsv($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/sale/handlers/delivery/spsr/location.csv');

		if(intval($imported) <= 0)
		{
			$res = self::getLocationsRequest('', Loc::getMessage('SALE_DLV_SRV_SPSR_RUSSIA'));

			if(!$res->isSuccess())
			{
				$eventLog = new \CEventLog;
				$eventLog->Add(array(
					"SEVERITY" => $eventLog::SEVERITY_ERROR,
					"AUDIT_TYPE_ID" => "SALE_DELIVERY_HANDLER_SPSR_LOCATION_INSTALL_ERROR",
					"MODULE_ID" => "sale",
					"ITEM_ID" => 'LOCATION',
					"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_LOC_INST_ERROR').": ".implode('. ', $res->getErrorMessages()),
				));
			}
		}
	}

	public static function unInstall()
	{
		$con = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();
		$srvId = $sqlHelper->forSql(self::getExternalServiceId());
		$con->queryExecute("DELETE FROM b_sale_loc_ext WHERE SERVICE_ID=".$srvId);
		ExternalServiceTable::delete($srvId);
		return true;
	}

	public static function isInstalled()
	{
		$res = ExternalServiceTable::getList(array(
			'filter' => array('=CODE' => self::EXTERNAL_SERVICE_CODE)
		));

		if($res->fetch())
			return true;

		return false;
	}

}