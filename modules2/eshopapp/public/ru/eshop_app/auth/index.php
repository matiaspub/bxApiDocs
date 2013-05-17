<?
// define("NEED_AUTH", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

if ($USER->IsAuthorized()):?>
<?
$userName = ($userName= $USER->GetFullName()) ? $userName : $USER->GetLogin();
?>
<script>
	app.onCustomEvent('onAuthSuccess', {"user_name":"<?=$userName?>", "id":"<?=$USER->GetID()?>","open_left":true});
	app.loadPage("<?=SITE_DIR."eshop_app/"?>");
</script>
<?endif?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>