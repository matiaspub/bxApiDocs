<?
class CSaleMeasure
{
	function GetList($category = false)
	{
		static $arMeasurementsTable;

		if (!is_array($arMeasurementsTable))
		{
			$tablePath = COption::GetOptionString('sale', 'measurement_path', '/bitrix/modules/sale/measurements.php');
			$fullPath = $_SERVER["DOCUMENT_ROOT"].$tablePath;
			if (strlen($tablePath) > 0 && file_exists($fullPath) && !is_dir($fullPath))
			{
				require_once($fullPath);
				
				if (!is_array($arMeasurementsTable)) 
					return false;
			}
			else
				return false;
		}

		if (!$category)
			return $arMeasurementsTable;
		else
		{
			$arList = array();
			foreach ($arMeasurementsTable as $key => $arM)
			{
				if ($arM["CATEGORY"] == $category) $arList[$key] = $arM;
			}
			return $arList;
		}
	}

	public static function Convert($value, $measureFrom, $measureTo = "G")
	{
		if (!is_numeric($value)) 
			return false;

		if (!$arMeasurementsTable = CSaleMeasure::GetList())
			return false;
		
		if (is_set($arMeasurementsTable, $measureFrom) && is_set($arMeasurementsTable, $measureTo))
			return $value * $arMeasurementsTable[$measureFrom]['KOEF'] / $arMeasurementsTable[$measureTo]['KOEF'];
		else
			return false;
	}
}
?>