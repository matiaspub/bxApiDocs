<?
/**
* Make some routines for pay systems
*/
class CSalePaySystemsHelper
{
	private static function getTarifClassName($actionFile)
	{
		if(strlen($actionFile) <= 0 )
			return '';

		static $arClassNames = array();

		if(!isset($arClassNames[$actionFile]))
		{
			$PSTarifClassName = '';  //must be defined in file tarif.php and contain class
			$tarifFileName = $_SERVER["DOCUMENT_ROOT"].$actionFile."/tarif.php";

			if(file_exists($tarifFileName))
				include_once($tarifFileName);

			$arClassNames[$actionFile] = $PSTarifClassName;  // todo: may be object/instance instead name ?
		}

		return $arClassNames[$actionFile];
	}

	public static function prepareTarifForSaving($actionFile, $arTarif)
	{
		$arResult = array();

		$PSTarifClassName = self::getTarifClassName($actionFile);

		if(strlen($PSTarifClassName) > 0 && is_callable($PSTarifClassName.'::prepareToField'))
			$arResult = call_user_func($PSTarifClassName.'::prepareToField', $arTarif);

		return $arResult;
	}

	public static function getPaySystemTarif($actionFile, $psId, $persId)
	{
		$arTarif = array();
		$PSTarifClassName = self::getTarifClassName($actionFile);

		if(strlen($PSTarifClassName) > 0 && is_callable($PSTarifClassName.'::getStructure'))
			$arTarif = call_user_func($PSTarifClassName.'::getStructure', $psId, $persId);

		return $arTarif;
	}

	public static function getPSPrice($arPaySystem, $orderPrice, $deliveryPrice, $buyerLocationId)
	{
		$result = 0;
		$PSTarifClassName = self::getTarifClassName($arPaySystem["PSA_ACTION_FILE"]);

		if(strlen($PSTarifClassName) > 0 && is_callable($PSTarifClassName.'::getPrice'))
			$result = call_user_func_array(
							$PSTarifClassName.'::getPrice',
							array(
								&$arPaySystem,
								$orderPrice,
								$deliveryPrice,
								$buyerLocationId
							)
						);

		return $result;
	}

	public static function checkPSCompability($actionFile, &$arOrder, $orderPrice, $deliveryPrice, $buyerLocationId)
	{
		$PSTarifClassName = self::getTarifClassName($actionFile);

		if(strlen($PSTarifClassName) > 0 && is_callable($PSTarifClassName.'::checkCompability'))
		{
			$result = call_user_func_array(
							$PSTarifClassName.'::checkCompability',
							array(
								&$arOrder,
								$orderPrice,
								$deliveryPrice,
								$buyerLocationId
							)
						);
		}
		else
		{
			$result = true;
		}

		return $result;
	}

	public static function getPSActionTitle($fileName)
	{
		$psTitle = "";

		if (file_exists($fileName) && is_file($fileName))
			include($fileName);

		return $psTitle;
	}

	public static function getPSActionTitle_old($fileName)
	{
		if (!file_exists($fileName))
			return false;

		$handle = fopen($fileName, "r");
		$contents = fread($handle, filesize($fileName));
		fclose($handle);

		$rep_title = "";

		$arMatches = array();
		if (preg_match("#<title_".LANGUAGE_ID."[^>]*>([^<]*?)</title_".LANGUAGE_ID."[\s]*>#i", $contents, $arMatches))
		{
			$arMatches[1] = Trim($arMatches[1]);
			if (strlen($arMatches[1])>0) $rep_title = $arMatches[1];
		}
		if (strlen($rep_title)<=0
			&& preg_match("#<title[^>]*>([^<]*?)</title[\s]*>#i", $contents, $arMatches))
		{
			$arMatches[1] = Trim($arMatches[1]);
			if (strlen($arMatches[1])>0) $rep_title = $arMatches[1];
		}
		if (strlen($rep_title)<=0)
			$rep_title = basename($strPathFull, ".php");

		return $rep_title;
	}

	public static function isPSActionAffordPdf($actionFile)
	{
		$isAffordPdf = false;

		$descriptionFile = $_SERVER['DOCUMENT_ROOT'] . $actionFile . '/.description.php';

		if (is_file($descriptionFile))
			include($descriptionFile);

		return $isAffordPdf;
	}

}
?>