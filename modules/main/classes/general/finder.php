<?
IncludeModuleLangFile(__FILE__);
class CFinder
{
	static public function __construct()
	{
	}

	public static function GetFinderAppearance($arParams, $arPanels)
	{
		$arResult['PROVIDER'] = CUtil::AddSlashes($arParams['PROVIDER']);
		
		$bselectFirstFilledPanel = true;
		foreach($arPanels as $panel)
			if (isset($panel['SELECTED']) && ($panel['SELECTED'] == 'Y' || $panel['SELECTED'] == true))
				$bselectFirstFilledPanel = false;
	
		$tabs = '';
		$elements = '';
		$bsearchable = false;
		$tabCount = count($arPanels);
				
		foreach($arPanels as $panel)
		{	
			if (!empty($panel['ELEMENTS']) && $bselectFirstFilledPanel)
			{
				$panel['SELECTED'] = true;
				$bselectFirstFilledPanel = false;
			}
		
			$bsearch = false;
			if (isset($panel['SEARCH']) && ($panel['SEARCH'] == 'Y' || $panel['SEARCH'] == true))
				$bsearch = $bsearchable = true;
				
			$bselect = false;
			if (isset($panel['SELECTED']) && ($panel['SELECTED'] == 'Y' || $panel['SELECTED'] == true))
				$bselect = true;
			
			$panel['NAME'] = htmlspecialcharsEx($panel['NAME']);
			if ($tabCount == 1)
			{
				$tabs .= '<a class="bx-finder-box-tab'.($bselect? ' bx-finder-box-tab-selected': '').($bsearch? ' bx-finder-box-tab-search': '').'" hidefocus="true">'.
					$panel['NAME'].
				'</a>';
			}
			else
			{
				$tabs .= '<a href="#switchTab" class="bx-finder-box-tab'.($bselect? ' bx-finder-box-tab-selected': '').($bsearch? ' bx-finder-box-tab-search': '').'" onclick="return BX.Finder.SwitchTab(this)" hidefocus="true">'.
					$panel['NAME'].
				'</a>';
			}
			$elements .= '<div class="bx-finder-box-tab-content'.($bselect? ' bx-finder-box-tab-content-selected': '').'">'.$panel['ELEMENTS'].'</div>';
		}

		$html = '<div class="bx-finder-box">'.
			($bsearchable? '<div class="bx-finder-box-search"><input class="bx-finder-box-search-textbox" name="" onkeyup="BX.Finder.Search(this, \''.$arResult['PROVIDER'].'\')"></div>': '').
			'<div class="bx-finder-box-tabs">'.$tabs.'</div><div class="popup-window-hr popup-window-buttons-hr"><i></i></div>'.
			'<div class="bx-finder-box-tabs-content bx-finder-box-tabs-content-window">'.
				'<table class="bx-finder-box-tabs-content-table">'.
					'<tr>'.
						'<td class="bx-finder-box-tabs-content-cell">'.
							$elements.
						'</td>'.
					'</tr>'.
				'</table>'.
			'</div>'.
		'</div>';
		
		return $html;
	}
	
	public static function GetFinderItem($arParams, $arItem)
	{
		$html = '';
		switch ($arParams['TYPE']) {
			case 1:
				$html = self::GetFinderItemType1($arParams, $arItem);
			break;
			case 2:
				$html = self::GetFinderItemType2($arParams, $arItem);
			break;
			case 3:
				$html = self::GetFinderItemType3($arParams, $arItem);
			break;
			case 4:
				$html = self::GetFinderItemType4($arParams, $arItem);
			break;
			case 5:
				$html = self::GetFinderItemType5($arParams, $arItem);
			break;
			case 'structure':
				$html = self::GetFinderItemStructure($arParams, $arItem);
			break;
			case 'structure-item':
				$html = self::GetFinderItemStructureItem($arParams, $arItem);
			break;
			case 'text':
				$html = self::GetFinderItemText($arParams, $arItem);
			break;
		}
		return $html;
	}
	
	private static function GetFinderItemType1($arParams, $arItem)
	{
		$arResult['PROVIDER'] = CUtil::AddSlashes($arParams['PROVIDER']);
	
		$arResult['ID'] = CUtil::AddSlashes($arItem['ID']);
		$arResult['NAME'] = htmlspecialcharsEx($arItem['NAME']);
		
		$html = '<a href="#'.$arResult['ID'].'" class="bx-finder-box-item bx-finder-element" rel="'.$arResult['ID'].'" onclick="return BX.Finder.onAddItem(\''.$arResult['PROVIDER'].'\', 1, this, \''.$arResult['ID'].'\')" hidefocus="true">'.
						'<div class="bx-finder-box-item-text">'.$arResult['NAME'].'</div>'.
					'</a>';
		return $html;
	}
	
	private static function GetFinderItemType2($arParams, $arItem)
	{
		$arResult['PROVIDER'] = CUtil::AddSlashes($arParams['PROVIDER']);
	
		$arResult['ID'] = CUtil::AddSlashes($arItem['ID']);
		$arResult['NAME'] = htmlspecialcharsEx($arItem['NAME']);
		
		$html = '<a href="#'.$arResult['ID'].'"  class="bx-finder-box-item-t2 bx-finder-element" rel="'.$arResult['ID'].'" onclick="return BX.Finder.onAddItem(\''.$arResult['PROVIDER'].'\', 2, this, \''.$arResult['ID'].'\')" hidefocus="true">
						<div class="bx-finder-box-item-t2-text">'.$arResult['NAME'].'</div>
					</a>';
	
		return $html;
	}
	
	private static function GetFinderItemType3($arParams, $arItem)
	{
		$arResult['PROVIDER'] = CUtil::AddSlashes($arParams['PROVIDER']);
	
		$arResult['ID'] = CUtil::AddSlashes($arItem['ID']);
		$arResult['AVATAR'] = CUtil::AddSlashes($arItem['AVATAR']);
		$arResult['NAME'] = htmlspecialcharsEx($arItem['NAME']);
		$arResult['DESC'] = htmlspecialcharsEx($arItem['DESC']);
		
		if (isset($arItem['SUBMENU']) && !empty($arItem['SUBMENU']))
		{
		}
				
		$html = '<a href="#'.$arResult['ID'].'" class="bx-finder-box-item-t3 bx-finder-element" rel="'.$arResult['ID'].'" onclick="return BX.Finder.onAddItem(\''.$arResult['PROVIDER'].'\', 3, this, \''.$arResult['ID'].'\')" hidefocus="true">
						<div style="'.(strlen($arResult['AVATAR'])>0? 'background:url(\''.$arResult['AVATAR'].'\') no-repeat center center': '').'" class="bx-finder-box-item-t3-avatar"></div>
						<div class="bx-finder-box-item-t3-info">
							<div class="bx-finder-box-item-t3-name">'.$arResult['NAME'].'</div>
							<div class="bx-finder-box-item-t3-desc">'.$arResult['DESC'].'</div>
						</div>
						<div class="bx-clear"></div>
					</a>';
	
		return $html;
	}
	
	private static function GetFinderItemType4($arParams, $arItem)
	{
		$arResult['PROVIDER'] = CUtil::AddSlashes($arParams['PROVIDER']);
	
		$arResult['ID'] = CUtil::AddSlashes($arItem['ID']);
		$arResult['AVATAR'] = CUtil::AddSlashes($arItem['AVATAR']);
		$arResult['NAME'] = htmlspecialcharsEx($arItem['NAME']);
		$arResult['DESC'] = htmlspecialcharsEx($arItem['DESC']);
		
		$bopened = isset($arItem['OPEN']) && ($arItem['OPEN'] == 'Y' || $arItem['OPEN'] == true)? true: false; 
		
		$html = '<div class="bx-finder-box-item-t4">
					<a href="#'.$arResult['ID'].'" '.($bopened? 'id="bx-finder-box-item-t3-'.$arResult['ID'].'"': '').' class="bx-finder-box-item-t3 bx-finder-element" rel="'.$arResult['ID'].'" onclick="return BX.Finder.OpenItemFolder(this)" hidefocus="true">
						<div style="'.(strlen($arResult['AVATAR'])>0? 'background:url(\''.$arResult['AVATAR'].'\') no-repeat center center': '').'" class="bx-finder-box-item-t3-avatar"></div>
						<div class="bx-finder-box-item-t3-info">
							<div class="bx-finder-box-item-t3-name">'.$arResult['NAME'].'</div>
							<div class="bx-finder-box-item-t3-desc">'.$arResult['DESC'].'</div>
						</div>
						<div class="bx-clear"></div>
					</a>
					<div class="bx-finder-company-department-children">';
		foreach($arItem['CHECKBOX'] as $template => $name)
		{
			$arCheck = Array(
				'ID' => str_replace("#ID#", $arResult['ID'], $template),
				'NAME' => $name,
				'DESC' => $arResult['NAME'].': '.$name,
			);
			$html .= self::GetFinderItemCheckbox($arParams, $arCheck);
		}		
		$html .= '	</div>
					</div>';

		if ($bopened)
			$html .= '<script type="text/javascript">BX.ready(function(){setTimeout(function(){BX.Finder.OpenItemFolder(BX(\'bx-finder-box-item-t3-'.$arResult['ID'].'\'))}, 100)});</script>';
		
	
		return $html;
	}
	
	private static function GetFinderItemType5($arParams, $arItem)
	{
		$arResult['PROVIDER'] = CUtil::AddSlashes($arParams['PROVIDER']);
	
		$arResult['ID'] = CUtil::AddSlashes($arItem['ID']);
		$arResult['AVATAR'] = CUtil::AddSlashes($arItem['AVATAR']);
		$arResult['NAME'] = htmlspecialcharsEx($arItem['NAME']);
		$arResult['DESC'] = htmlspecialcharsEx($arItem['DESC']);
						
		$html = '<a href="#'.$arResult['ID'].'" class="bx-finder-box-item-t5 bx-finder-element" rel="'.$arResult['ID'].'" onclick="return BX.Finder.onAddItem(\''.$arResult['PROVIDER'].'\', 5, this, \''.$arResult['ID'].'\')" hidefocus="true">
						<div style="'.(strlen($arResult['AVATAR'])>0? 'background:url(\''.$arResult['AVATAR'].'\') no-repeat center center': '').'" class="bx-finder-box-item-t5-avatar"></div>
						<div class="bx-finder-box-item-t5-info">
							<div class="bx-finder-box-item-t5-name">'.$arResult['NAME'].'</div>
							<div class="bx-finder-box-item-t5-desc">'.$arResult['DESC'].'</div>
						</div>
						<div class="bx-clear"></div>
					</a>';
		return $html;
	}
	
	private static function GetFinderItemText($arParams, $arItem)
	{
		$arResult['TEXT'] = htmlspecialcharsEx($arItem['TEXT']);
		
		$html = '<div class="bx-finder-item-text">'.$arResult['TEXT'].'</div>';
		
		return $html;
	}		
	private static function GetFinderItemStructure($arParams, $arItem)
	{
		$html = '';
		foreach($arItem as $value)
		{
			if ($value['TYPE'] == 'category')
			{
				$html .= self::GetFinderItemStructureCategory($arParams, $value);
				$html .= '<div class="bx-finder-company-department-children">';	
					foreach($value['CHECKBOX'] as $template => $name)
					{
						$arCheck = Array(
							'ID' => str_replace("#ID#", $value['ID'], $template),
							'NAME' => $name,
							'DESC' => $value['NAME'].': '.$name,
						);
						$html .= self::GetFinderItemCheckbox($arParams, $arCheck);
					}	
					$html .= self::GetFinderItemStructure($arParams, (!empty($value['CHILD'])? $value['CHILD']: Array()));
				$html .= '</div>';
			} 
		}
		if (!isset($value['HIDE_ITEM']) || $value['HIDE_ITEM'] == false)
		{
			$html .= '<div class="bx-finder-company-department-employees">';
			$bEmptyItem = true;
			foreach($arItem as $value)
			{
				if ($value['TYPE'] == 'item')
				{
					$html .= self::GetFinderItemStructureItem($arParams, $value);
					$bEmptyItem = false;
				}
			}
			if ($bEmptyItem)
				$html .= '<div class="bx-finder-company-department-employees-loading">'.GetMessage('FINDER_PLEASE_WAIT').'</div>';
			$html .= '</div>';
		}	
		return $html;
	}
	
	private static function GetFinderItemStructureCategory($arParams, $arItem)
	{
		$arResult['PROVIDER'] = CUtil::AddSlashes($arParams['PROVIDER']);
		
		$arResult['ID'] = CUtil::AddSlashes($arItem['ID']);
		$arResult['NAME'] = htmlspecialcharsEx($arItem['NAME']);
		
		$bopened = isset($arItem['OPEN']) && ($arItem['OPEN'] == 'Y' || $arItem['OPEN'] == true)? true: false; 
		
		$html = '<div class="bx-finder-company-department" '.($bopened? 'id="bx-finder-company-department-'.$arResult['ID'].'"': '').'><a href="#'.$arResult['ID'].'" class="bx-finder-company-department-inner" onclick="return BX.Finder.OpenCompanyDepartment(\''.$arResult['PROVIDER'].'\', \''.$arResult['ID'].'\', this.parentNode)" hidefocus="true"><div class="bx-finder-company-department-arrow"></div><div class="bx-finder-company-department-text">'.$arResult['NAME'].'</div></a></div>';
		if ($bopened)
			$html .= '<script type="text/javascript">BX.ready(function(){setTimeout(function(){BX.Finder.OpenCompanyDepartment(\''.$arResult['PROVIDER'].'\', \''.$arResult['ID'].'\', BX(\'bx-finder-company-department-'.$arResult['ID'].'\'))}, 100)});</script>';
		
		return $html;
	}
		
	private static function GetFinderItemCheckbox($arParams, $arItem)
	{	
		$arResult['PROVIDER'] = CUtil::AddSlashes($arParams['PROVIDER']);
	
		$arResult['ID'] = CUtil::AddSlashes($arItem['ID']);
		$arResult['NAME'] = htmlspecialcharsEx($arItem['NAME']);
		$arResult['DESC'] = CUtil::AddSlashes(htmlspecialcharsbx($arItem['DESC']));
		
		$html = '<a href="#'.$arResult['ID'].'" class="bx-finder-company-department-check bx-finder-element" rel="'.$arResult['ID'].'"  onclick="return BX.Finder.onAddItem(\''.$arResult['PROVIDER'].'\', \'structure-checkbox\', this, \''.$arResult['ID'].'\')" hidefocus="true">
						<span class="bx-finder-company-department-check-inner"><div class="bx-finder-company-department-check-arrow"></div><div class="bx-finder-company-department-check-text" rel="'.$arResult['DESC'].'">'.$arResult['NAME'].'</div></span>
					</a>';
		
		return $html;
	}
	private static function GetFinderItemStructureItem($arParams, $arItem)
	{
		$arResult['PROVIDER'] = CUtil::AddSlashes($arParams['PROVIDER']);
	
		$arResult['ID'] = CUtil::AddSlashes($arItem['ID']);
		$arResult['AVATAR'] = CUtil::AddSlashes($arItem['AVATAR']);
		$arResult['NAME'] = htmlspecialcharsEx($arItem['NAME']);
		$arResult['DESC'] = empty($arItem['DESC'])? '&nbsp;': htmlspecialcharsEx($arItem['DESC']);
	
		$html = '<a href="#'.$arResult['ID'].'" class="bx-finder-company-department-employee bx-finder-element" rel="'.$arResult['ID'].'" onclick="return BX.Finder.onAddItem(\''.$arResult['PROVIDER'].'\', \'structure\', this, \''.$arResult['ID'].'\')" hidefocus="true">
						<div class="bx-finder-company-department-employee-info">
							<div class="bx-finder-company-department-employee-name">'.$arResult['NAME'].'</div>
							<div class="bx-finder-company-department-employee-position">'.$arResult['DESC'].'</div>
						</div>
						<div style="'.(strlen($arResult['AVATAR'])>0? 'background:url(\''.$arResult['AVATAR'].'\') no-repeat center center': '').'" class="bx-finder-company-department-employee-avatar"></div>
					</a>';
	
		return $html;
	}
}

?>