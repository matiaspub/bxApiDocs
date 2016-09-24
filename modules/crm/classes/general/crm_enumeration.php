<?php
class CCrmEnumeration
{
	public static function PrepareListItems(&$arDescr, $arIgnored = array())
	{
		$ary = array();
		foreach($arDescr as $k => &$v)
		{
			if(in_array($k, $arIgnored, true))
			{
				continue;
			}

			$ary[] = array('text' => $v, 'value' => strval($k));
		}
		unset($v);

		return $ary;
	}

	public static function PrepareFilterItems(&$arDescr, $arIgnored = array())
	{
		$ary = array();
		foreach($arDescr as $k => &$v)
		{
			if(in_array($k, $arIgnored, true))
			{
				continue;
			}

			$ary[strval($k)] = $v;
		}
		unset($v);

		return $ary;
	}
}
