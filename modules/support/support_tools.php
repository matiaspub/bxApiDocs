<?
function __PrintRussian($num, $ext)//$ext - 3 end of digit 1, 2, 5
{
	if(strlen($num)>1 && substr($num,strlen($num)-2,1)=="1")
		return $ext[2];

	$c=IntVal(substr($num,strlen($num)-1,1));
	if($c==0 || ($c>=5 && $c<=9))
		return $ext[2];

	if($c==1)
		return $ext[0];

	return $ext[1];
}

function __sup_debug($v, $name = false)
{
	if (!is_scalar($v))
	{
		$v = var_export($v, true);
	}

	$str = date('r') . ( $name ? " ### $name\n" :"\n");
	$str .= $v;
	$str .= "\n========================================\n\n";

	file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/xxx_support_debug.txt', $str, FILE_APPEND);
}

?>