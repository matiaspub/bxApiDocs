<?
class CSeoUtils
{
	public static function CleanURL($URL)
	{
		if (false !== ($pos = strpos($URL, '?')))
		{
			$query = substr($URL, $pos+1);
			$URL = substr($URL, 0, $pos);
			
			$arQuery = explode('&', $query);
			
			$arExcludedParams = array('clear_cache', 'clear_cache_session', 'back_url_admin', 'back_url', 'backurl', 'login', 'logout', 'compress');
			foreach ($arQuery as $key => $param)
			{
				if (false !== ($pos = strpos($param, '=')))
				{
					$param_name = ToLower(substr($param, 0, $pos));
					if (
						substr($param_name, 0, 7) == 'bitrix_' 
						|| substr($param_name, 0, 5) == 'show_' 
						|| in_array($param_name, $arExcludedParams)
					)
					{
						unset($arQuery[$key]);
					}
				}
			}
			
			if (count($arQuery) > 0)
			{
				$URL .= '?'.implode('&', $arQuery);
			}
		}
		
		return $URL;
	}
}
?>