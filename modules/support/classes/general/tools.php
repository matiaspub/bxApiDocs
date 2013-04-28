<?
class CSupportTools
{
	static $speedArray = array();
	
	static function PrepareParamArray($param, $res = array())
	{
		if(is_string($param) && strlen($param) > 0)
		{
			$res = explode(",", $param);
			foreach( $res as $k => $v ) $res[$k] = trim($v);
		}
		elseif(is_array($param) && count($param) > 0) $res = $param;
		elseif(is_int($param)) $res = array($param);
		return $res;
	}
	
	// $more0 = "strlen || count || intval"
	static function array_keys_exists($key, $arr, $more0 = "", $andOr = "&&")
	{
		$arrKeys = self::prepareParamArray($key);
		$arrMore0 = self::prepareParamArray($more0);
		$res = true;
		foreach($arrKeys as $k => $v) 
		{
			$resC = (is_array($arr) && array_key_exists($v,  $arr)
				&& (!in_array("strlen", $arrMore0) || strlen($arr[$v]) > 0)
				&& (!in_array("count", $arrMore0) || ( is_array($arr[$v]) && count($arr[$v] ) > 0))
				&& (!in_array("intval", $arrMore0) || intval($arr[$v]) > 0)
			);
			if($andOr == "||") $res = ($res || $resC);
			elseif(!$resC) return false;
		}
		return $res;
	}
	
}


?>