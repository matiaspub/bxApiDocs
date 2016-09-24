<?
if (file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/geshi/geshi.php"))
	require_once($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/geshi/geshi.php");

IncludeModuleLangFile(__FILE__);
/**
 * Formats float number according to flags.
 *
 * @param float $num Number value to be formatted.
 * @param integer $dec How many digits after decimal point.
 * @param integer $mode Output mode.
 *
 * @return string
**/
function perfmon_NumberFormat($num, $dec = 2, $mode = 0)
{
	switch ($mode)
	{
	case 1:
		$str = number_format($num, $dec, '.', '');
		break;
	case 2:
		$str = number_format($num, $dec, '.', ' ');
		$str = str_replace(' ', '<span></span>', $str);
		$str = '<span class="perfmon_number">'.$str.'</span>';
		break;
	default:
		if ($_REQUEST["mode"] == "excel")
			$str = perfmon_NumberFormat($num, $dec, 1);
		else
			$str = perfmon_NumberFormat($num, $dec, 2);
		break;
	}
	return $str;
}

class CAdminListColumn
{
	public $id = "";
	public $info = array();

	public function __construct($id, $info)
	{
		$this->id = $id;
		$this->info = $info;
	}

	public static function getRowView($arRes)
	{
		return false;
	}

	public static function getRowEdit($arRes)
	{
		return false;
	}

	public function getFilterInput()
	{
		return '<input type="text" name="'.$this->info["filter"].'" size="47" value="'.htmlspecialcharsbx($GLOBALS[$this->info["filter"]]).'">';
	}
}

class CAdminListColumnList extends CAdminListColumn
{
	public $list = array();

	public function __construct($id, $info, array $list = array())
	{
		parent::__construct($id, $info);
		$this->list = $list;
	}

	public function getRowView($arRes)
	{
		$value = $arRes[$this->id];
		return $this->list[$value];
	}

	public static function getRowEdit($arRes)
	{
		return false;
	}

	public function getFilterInput()
	{
		$arr = array(
			"reference" => array(),
			"reference_id" => array(),
		);
		foreach ($this->list as $key => $value)
		{
			$arr["reference"][] = $value;
			$arr["reference_id"][] = $key;
		}
		return SelectBoxFromArray($this->info["filter"], $arr, htmlspecialcharsbx($GLOBALS[$this->info["filter"]]), GetMessage("MAIN_ALL"));
	}
}

class CAdminListColumnNumber extends CAdminListColumn
{
	public $precision = 0;

	public function __construct($id, $info, $precision)
	{
		$info["align"] = "right";
		parent::__construct($id, $info);
		$this->precision = $precision;
	}

	public function getRowView($arRes)
	{
		if ($_REQUEST["mode"] == "excel")
			return number_format($arRes[$this->id], $this->precision, ".", "");
		else
			return str_replace(" ", "&nbsp;", number_format($arRes[$this->id], $this->precision, ".", " "));
	}
}

class CAdminListPage
{
	protected $pageTitle = "";
	protected $sTableID = "";
	protected $navLabel = "";
	protected $sort = null;
	protected $list = null;
	protected $data = null;
	protected $columns = array();

	/**
	 * @param string $pageTitle
	 * @param string $sTableID
	 * @param boolean|array[] $arSort
	 * @param string $navLabel
	 */
	public function __construct($pageTitle, $sTableID, $arSort = false, $navLabel = "")
	{
		$this->pageTitle = $pageTitle;
		$this->sTableID = $sTableID;
		$this->navLabel = $navLabel;
		if (is_array($arSort))
			$this->sort = new CAdminSorting($this->sTableID, key($arSort), current($arSort));
		else
			$this->sort = false;
		$this->list = new CAdminList($this->sTableID, $this->sort);
	}

	public function addColumn(CAdminListColumn $column)
	{
		$this->columns[$column->id] = $column;
	}

	public function initFilter()
	{
		$FilterArr = array(
			"find",
			"find_type",
		);
		foreach ($this->columns as $column)
		{
			if (isset($column->info["filter"]))
				$FilterArr[] = $column->info["filter"];
		}
		$this->list->InitFilter($FilterArr);
	}

	public function getFilter()
	{
		global $find, $find_type;

		$arFilter = array();
		foreach ($this->columns as $column)
		{
			if (
				isset($column->info["filter"])
				&& isset($column->info["filter_key"])
			)
			{
				if (
					isset($column->info["find_type"])
					&& $find != ""
					&& $find_type == $column->info["find_type"]
				)
				{

					$arFilter[$column->info["filter_key"]] = $find;
				}
				elseif (
					isset($GLOBALS[$column->info["filter"]])
				)
				{
					$arFilter[$column->info["filter_key"]] = $GLOBALS[$column->info["filter"]];
				}
			}
		}

		foreach ($arFilter as $key => $value)
		{
			if ($value == "")
				unset($arFilter[$key]);
		}

		return $arFilter;
	}

	public function getHeaders()
	{
		$arHeaders = array();
		foreach ($this->columns as $column)
		{
			$arHeaders[] = array(
				"id" => $column->id,
				"content" => $column->info["content"],
				"sort" => $column->info["sort"],
				"align" => $column->info["align"],
				"default" => $column->info["default"],
			);
		}
		return $arHeaders;
	}

	public function getSelectedFields()
	{
		$arSelectedFields = $this->list->GetVisibleHeaderColumns();
		if (!is_array($arSelectedFields) || empty($arSelectedFields))
		{
			$arSelectedFields = array();
			foreach ($this->columns as $column)
			{
				if ($column->info["default"])
					$arSelectedFields[] = $column->id;
			}
		}
		return $arSelectedFields;
	}

	public static function getDataSource($arOrder, $arFilter, $arSelect)
	{
		$rsData = new CDBResult;
		$rsData->InitFromArray(array());
		return $rsData;
	}

	public static function getOrder()
	{
		global $by, $order;
		return array($by => $order);
	}

	public static function getFooter()
	{
		return array();
	}

	public static function getContextMenu()
	{
		return array();
	}

	public function displayFilter()
	{
		global $APPLICATION, $find, $find_type;

		$findFilter = array(
			"reference" => array(),
			"reference_id" => array(),
		);
		$listFilter = array();
		$filterRows = array();
		foreach ($this->columns as $column)
		{
			if (isset($column->info["filter"]))
			{
				$listFilter[$column->info["filter"]] = $column->info["content"];
				if (isset($column->info["find_type"]))
				{
					$findFilter["reference"][] = $column->info["content"];
					$findFilter["reference_id"][] = $column->info["find_type"];
				}
			}
		}

		if (!empty($listFilter))
		{
			$this->filter = new CAdminFilter($this->sTableID."_filter", $listFilter);
			?>
			<form name="find_form" method="get" action="<? echo $APPLICATION->GetCurPage(); ?>">
				<? $this->filter->Begin(); ?>
				<? if (!empty($findFilter["reference"])): ?>
					<tr>
						<td><b><?=GetMessage("PERFMON_HIT_FIND")?>:</b></td>
						<td><input
							type="text" size="25" name="find"
							value="<? echo htmlspecialcharsbx($find) ?>"><? echo SelectBoxFromArray("find_type", $findFilter, $find_type, "", ""); ?>
						</td>
					</tr>
				<? endif; ?>
				<?
				foreach ($this->columns as $column)
				{
					if (isset($column->info["filter"]))
					{
						?>
						<tr>
						<td><? echo $column->info["content"] ?></td>
						<td><? echo $column->getFilterInput() ?></td>
						</tr><?
					}
				}
				$this->filter->Buttons(array(
					"table_id" => $this->sTableID,
					"url" => $APPLICATION->GetCurPage(),
					"form" => "find_form",
				));
				$this->filter->End();
				?>
			</form>
		<?
		}
	}

	public function show()
	{
		global $APPLICATION;

		$this->initFilter();
		$this->list->addHeaders($this->getHeaders());
		$select = $this->getSelectedFields();

		$dataSource = $this->getDataSource($this->getOrder(), $this->getFilter(), $select);
		$this->data = new CAdminResult($dataSource, $this->sTableID);
		$this->data->NavStart();
		$this->list->NavText($this->data->GetNavPrint($this->navLabel));

		$i = 0;
		while ($arRes = $this->data->NavNext(true, "f_"))
		{
			$row = $this->list->AddRow(++$i, $arRes);
			foreach ($select as $fieldId)
			{
				$column = $this->columns[$fieldId];
				if ($column)
				{
					$view = $column->getRowView($arRes);
					if ($view !== false)
						$row->AddViewField($column->id, $view);
					$edit = $column->getRowEdit($arRes);
					if ($edit !== false)
						$row->AddEdirField($column->id, $edit);
				}
			}
		}

		$this->list->AddFooter($this->getFooter());
		$this->list->AddAdminContextMenu($this->getContextMenu());
		$this->list->CheckListMode();
		$APPLICATION->SetTitle($this->pageTitle);
		global /** @noinspection PhpUnusedLocalVariableInspection */
		$adminPage, $adminMenu, $adminChain, $USER;
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
		$this->displayFilter();
		$this->list->DisplayList();
	}
}

?>