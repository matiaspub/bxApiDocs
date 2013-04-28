<?
if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/geshi/geshi.php"))
	require_once($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/geshi/geshi.php");

function perfmon_NumberFormat($num, $dec=2, $html=true)
{
	$str = number_format($num, $dec, ".", " ");
	if($html)
		return str_replace(" ", "&nbsp;", $str);
	else
		return $str;
}
?>