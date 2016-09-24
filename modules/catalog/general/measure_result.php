<?
/**
 * Class CCatalogMeasureAdminResult
 */
class CCatalogMeasureAdminResult extends CAdminResult
{
	protected $measureResult;

	public function __construct($res, $table_id)
	{
		parent::__construct($res, $table_id);
		$this->measureResult = new CCatalogMeasureResult($this);
	}

	/**
	 * @return array
	 */
	public function Fetch()
	{
		return $this->measureResult->Fetch();
	}
}