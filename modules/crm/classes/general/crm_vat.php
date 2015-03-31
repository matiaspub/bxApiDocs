<?

IncludeModuleLangFile(__FILE__);

class CCrmVat
{
	private static $VATS = null;

	public static function GetAll()
	{
		if (!CModule::IncludeModule('catalog'))
			return false;

		$VATS = isset(self::$VATS) ? self::$VATS : null;

		if(!$VATS)
		{
			$VATS = array();
			$dbResultList = CCatalogVat::GetList( array('C_SORT' => 'ASC'));

			while ($arVat = $dbResultList->Fetch())
				$VATS[$arVat['ID']] = $arVat;

			self::$VATS = $VATS;
		}

		return $VATS;
	}

	public static function GetByID($vatID)
	{
		if(intval($vatID) <= 0)
			return false;

		$arVats = self::GetAll();

		return isset($arVats[$vatID]) ? $arVats[$vatID] : false;
	}

	public static function GetVatRatesListItems()
	{
		$listItems = array('' => GetMessage('CRM_VAT_NOT_SELECTED'));
		foreach (self::GetAll() as $vatRate)
		{
			if ($vatRate['ACTIVE'] !== 'Y') continue;
			$listItems[$vatRate['ID']] = $vatRate['NAME'];
		}
		unset($vatRate);
		return $listItems;
	}
}

?>