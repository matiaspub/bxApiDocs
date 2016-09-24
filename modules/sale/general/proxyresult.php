<?
class CSaleProxyResult extends CDBResult
{
	private $parameters = array();
	private $entityName = '';

	public function __construct($parameters, $entityName)
	{
		$this->parameters = $parameters;
		$this->entityName = $entityName;
		parent::__construct(array());
	}

	public function NavStart($nPageSize = 20, $bShowAll = true, $iNumPage = false)
	{
		$this->InitNavStartVars(intval($nPageSize), $bShowAll, $iNumPage);

		// force to db resource type, although we got empty array on input
		$en = $this->entityName;

		$runtime = is_array($this->parameters['runtime']) ? $this->parameters['runtime'] : array();
		$filter = is_array($this->parameters['filter']) ? $this->parameters['filter'] : array();

		// to increase perfomance, have to throw away unused (in filter) runtimes
		foreach($runtime as $fld => $desc)
		{
			$found = false;
			foreach($filter as $condition => $value)
			{
				if(strpos($condition, $fld) !== false)
				{
					$found = true;
					break;
				}
			}

			if(!$found)
				unset($runtime[$fld]);
		}

		$count = $en::getList(array(
			'filter' => $filter,
			'select' => array('REC_CNT'),
			'runtime' => array_merge($runtime, array(
				'REC_CNT' => array(
					'data_type' => 'integer',
					'expression' => array(
						'count(*)'
					)
				)
			))
		))->fetch();
		$this->NavRecordCount = $count['REC_CNT'];
		
		// the following code was taken from DBNavStart()

		// here we could use Bitrix\Main\DB\Paginator

		//calculate total pages depend on rows count. start with 1
		$this->NavPageCount = floor($this->NavRecordCount/$this->NavPageSize);
		if($this->NavRecordCount % $this->NavPageSize > 0)
			$this->NavPageCount++;

		//page number to display. start with 1
		$this->NavPageNomer = ($this->PAGEN < 1 || $this->PAGEN > $this->NavPageCount? ($_SESSION[$this->SESS_PAGEN] < 1 || $_SESSION[$this->SESS_PAGEN] > $this->NavPageCount? 1:$_SESSION[$this->SESS_PAGEN]):$this->PAGEN);

		$parameters = $this->parameters;
		$parameters['limit'] = $this->NavPageSize;
		$parameters['offset'] = ($this->NavPageNomer - 1) * $this->NavPageSize;

		$res = $en::getList($parameters);
		$this->arResult = array();
		while($item = $res->Fetch())
			$this->arResult[] = $item;
	}
}