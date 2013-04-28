<?
/*.require_module 'standard';.*/
/*.require_module 'bitrix_main';.*/
/**
 * @global CDatabase $DB
 */
if(!defined("CACHED_b_clouds_file_bucket")) // define("CACHED_b_clouds_file_bucket", 360000);
if(!defined("CACHED_clouds_file_resize")) // define("CACHED_clouds_file_resize", 360000);

$db_type = strtolower($DB->type);
CModule::AddAutoloadClasses(
	"clouds",
	array(
		"CCloudUtil" =>  "classes/general/util.php",
		"CCloudStorage" =>  "classes/general/storage.php",
		"CAllCloudStorageBucket" =>  "classes/".$db_type."/storage_bucket.php",
		"CCloudStorageBucket" =>  "classes/general/storage_bucket.php",
		"CCloudStorageService" => "classes/general/storage_service.php",
		"CCloudStorageService_AmazonS3" =>  "classes/general/storage_service_s3.php",
		"CCloudStorageService_GoogleStorage" =>  "classes/general/storage_service_google.php",
		"CCloudStorageService_OpenStackStorage" =>  "classes/general/storage_service_openstack.php",
		"CCloudStorageService_RackSpaceCloudFiles" =>  "classes/general/storage_service_rackspace.php",
		"CCloudStorageService_ClodoRU" =>  "classes/general/storage_service_clodo.php",
		"CCloudStorageService_Selectel" =>  "classes/general/storage_service_selectel.php",
		"CCloudStorageUpload" => "classes/general/storage_upload.php",
		"CCloudSecurityService_AmazonS3" => "classes/general/security_service_s3.php",
	)
);
?>