<?
//This function will protect against utf-7 xss
//on page with no character setting
function htmlspecialchars_plus($str)
{
	return str_replace("+","&#43;", htmlspecialchars($str));
}

if(!isset($_GET["img"]) || !is_string($_GET["img"]))
	die();

if(isset($_GET["alt"]) && is_string($_GET["alt"]))
	$alt = htmlspecialchars_plus($_GET["alt"]);
else
	$alt = "";

$img = $_GET["img"];
if(substr($img, 0, 1) !== "/" && strtolower(substr($img, 0, 4) !== "http"))
	$img = "/".$img; // some browsers run javascript: in img src tag
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script language="JavaScript">
<!--
function KeyPress()
{
	if(window.event.keyCode == 27)
		window.close();
}
//-->
</script>
<style type="text/css">
<!--
body {margin-left:0; margin-top:0; margin-right:0; margin-bottom:0;}
-->
</style>
<title><?echo $alt?></title></head>
<body topmargin="0" leftmargin="0" marginwidth="0" marginheight="0" onKeyPress="KeyPress()">
<img src="<?echo htmlspecialchars_plus($img)?>" border="0" alt="<?echo $alt?>">
</body>
</html>
