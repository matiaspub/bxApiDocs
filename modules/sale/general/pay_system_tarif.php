<?
/**
* CSalePaySystemTarif
*	Abstract class for counting the pay system service's price etc.
*/
abstract class CSalePaySystemTarif
{
	private static $arItems = array();
	/**
	 * getPrice
	 * Calculate price for pay system service
	 * @return float
	 */
	abstract public static function getPrice(&$arPaySystem, $orderPrice, $deliveryPrice, $buyerLocationId);

	/**
	 * getStructure
	 * Describe tarif params structure
	 * @return array
	 */
	abstract public static function getStructure($psId, $persId);

	/**
	 * checkCompability
	 * Check if we can use this pay system
	 * @return bool
	 */
	abstract public static function checkCompability(&$arOrder, $orderPrice, $deliveryPrice, $buyerLocationId);

	public static function extractFromField($strFieldContent)
	{
		return unserialize($strFieldContent);
	}

	public static function prepareToField($arTarif)
	{
		return serialize($arTarif);
	}

	/**
	 * getByPaySystemId
	 * returns saved tarif's values
	 * @return array
	 */
	protected static function getValuesByPSAId($psaId)
	{
		$arResult = array();

		if(isset(self::$arItems[$psaId]))
		{
			$arResult = self::$arItems[$psaId];
		}
		else
		{
			$psa = CSalePaySystemAction::GetByID($psaId);

			if(is_array($psa) && isset($psa['TARIF']) && is_array($psa['TARIF']))
				$arResult = self::$arItems[$psaId] = unserialize($psa['TARIF']);
		}

		return $arResult;
	}
}
?>