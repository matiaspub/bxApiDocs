<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;

// define('STOP_STATISTICS', true);
// define('BX_SECURITY_SHOW_MESSAGE', true);
// define('NO_AGENT_CHECK', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');

Loc::loadMessages(__FILE__);

$result = array(
	'STATUS' => '',
	'MESSAGE' => '',
	'RATE_CNT' => '',
	'RATE' => ''
);

if (!check_bitrix_sessid())
{
	$result['STATUS'] = 'ERROR';
	$result['MESSAGE'] = Loc::getMessage('BX_CURRENCY_GET_RATE_ERR_SESSION');
}
else
{
	if (!Loader::includeModule('currency'))
	{
		$result['STATUS'] = 'ERROR';
		$result['MESSAGE'] = Loc::getMessage('BX_CURRENCY_GET_RATE_ERR_MODULE_ABSENT');
	}
	else
	{
		$baseCurrency = CCurrency::GetBaseCurrency();
		$date = '';
		$currency = '';
		if (isset($_REQUEST['DATE_RATE']))
			$date = (string)$_REQUEST['DATE_RATE'];
		if (isset($_REQUEST['CURRENCY']))
			$currency = (string)$_REQUEST['CURRENCY'];
		if ($baseCurrency == '')
		{
			$result['STATUS'] = 'ERROR';
			$result['MESSAGE'] = Loc::getMessage('BX_CURRENCY_GET_RATE_ERR_BASE_CURRENCY_ABSENT');
		}
		elseif ($date == '' || !$DB->IsDate($date))
		{
			$result['STATUS'] = 'ERROR';
			$result['MESSAGE'] = Loc::getMessage('BX_CURRENCY_GET_RATE_ERR_DATE_RATE');
		}
		elseif ($currency == '')
		{
			$result['STATUS'] = 'ERROR';
			$result['MESSAGE'] = Loc::getMessage('BX_CURRENCY_GET_RATE_ERR_CURRENCY');
		}
		else
		{
			switch ($baseCurrency)
			{
				case 'UAH':
					$url = 'http://pfsoft.com.ua//service/currency/?date='.$DB->FormatDate($date, CLang::GetDateFormat('SHORT', LANGUAGE_ID), 'DMY');
					break;
				case 'BYR':
					$url = 'http://www.nbrb.by//Services/XmlExRates.aspx?ondate='.$DB->FormatDate($date, CLang::GetDateFormat('SHORT', LANGUAGE_ID), 'Y-M-D');
					break;
				case 'RUB':
				case 'RUR':
					$url = 'http://www.cbr.ru/scripts/XML_daily.asp?date_req='.$DB->FormatDate($date, CLang::GetDateFormat('SHORT', LANGUAGE_ID), 'D.M.Y');
					break;
			}
			$http = new HttpClient();
			$data = $http->get($url);

			$charset = 'windows-1251';
			$matches = array();
			if (preg_match("/<"."\?XML[^>]{1,}encoding=[\"']([^>\"']{1,})[\"'][^>]{0,}\?".">/i", $data, $matches))
			{
				$charset = trim($matches[1]);
			}
			$data = preg_replace("#<!DOCTYPE[^>]+?>#i", '', $data);
			$data = preg_replace("#<"."\\?XML[^>]+?\\?".">#i", '', $data);
			$data = $APPLICATION->ConvertCharset($data, $charset, SITE_CHARSET);

			$objXML = new CDataXML();
			$res = $objXML->LoadString($data);
			if ($res !== false)
				$data = $objXML->GetArray();
			else
				$data = false;

			switch ($baseCurrency)
			{
				case 'UAH':
					if (is_array($data) && count($data["ValCurs"]["#"]["Valute"])>0)
					{
						for ($j1 = 0, $intCount = count($data["ValCurs"]["#"]["Valute"]); $j1 < $intCount; $j1++)
						{
							if ($data["ValCurs"]["#"]["Valute"][$j1]["#"]["CharCode"][0]["#"] == $currency)
							{
								$result['STATUS'] = 'OK';
								$result['RATE_CNT'] = (int)$data["ValCurs"]["#"]["Valute"][$j1]["#"]["Nominal"][0]["#"];
								$result['RATE'] = (float)str_replace(",", ".", $data["ValCurs"]["#"]["Valute"][$j1]["#"]["Value"][0]["#"]);
								break;
							}
						}
					}
					break;
				case 'BYR':
					if (is_array($data) && count($data["DailyExRates"]["#"]["Currency"])>0)
					{
						for ($j1 = 0, $intCount = count($data["DailyExRates"]["#"]["Currency"]); $j1 < $intCount; $j1++)
						{
							if ($data["DailyExRates"]["#"]["Currency"][$j1]["#"]["CharCode"][0]["#"] == $currency)
							{
								$result['STATUS'] = 'OK';
								$result['RATE_CNT'] = (int)$data["DailyExRates"]["#"]["Currency"][$j1]["#"]["Scale"][0]["#"];
								$result['RATE'] = (float)str_replace(",", ".", $data["DailyExRates"]["#"]["Currency"][$j1]["#"]["Rate"][0]["#"]);
								break;
							}
						}
					}
					break;
				case 'RUB':
				case 'RUR':
					if (is_array($data) && count($data["ValCurs"]["#"]["Valute"])>0)
					{
						for ($j1 = 0, $intCount = count($data["ValCurs"]["#"]["Valute"]); $j1 < $intCount; $j1++)
						{
							if ($data["ValCurs"]["#"]["Valute"][$j1]["#"]["CharCode"][0]["#"] == $currency)
							{
								$result['STATUS'] = 'OK';
								$result['RATE_CNT'] = (int)$data["ValCurs"]["#"]["Valute"][$j1]["#"]["Nominal"][0]["#"];
								$result['RATE'] = (float)str_replace(",", ".", $data["ValCurs"]["#"]["Valute"][$j1]["#"]["Value"][0]["#"]);
								break;
							}
						}
					}
					break;
			}
		}
		if ($result['STATUS'] != 'OK')
		{
			$result['STATUS'] = 'ERROR';
			$result['MESSAGE'] = Loc::getMessage('BX_CURRENCY_GET_RATE_ERR_RESULT_ABSENT');
		}
	}
}
echo CUtil::PhpToJSObject($result, false, true, true);
?>