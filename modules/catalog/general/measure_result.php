<?
/**
 * Class CCatalogMeasureAdminResult
 */
class CCatalogMeasureAdminResult extends CAdminResult
{
	/**
	 * @return array
	 */
	public static function Fetch()
	{
		return CCatalogMeasureResult::fetch();
	}
}