<?

/**
 * Класс для работы с файлами и изображениями.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cfile/index.php
 * @author Bitrix
 */
class CFile extends CAllFile
{
	
	/**
	* <p>Метод удаляет файл из таблицы зарегистрированных файлов (b_file) и с диска. Статический метод.</p>
	*
	*
	* @param mixed $intid  Цифровой идентификатор файла.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // удаляем изображение формы
	* $arFilter = array("ID" =&gt; 1, "ID_EXACT_MATCH" =&gt; "Y");
	* $rsForm = CForm::GetList($by, $order, $arFilter, $is_filtered);
	* if ($arForm = $rsForm-&gt;Fetch())
	* {
	*     if (intval($arForm["IMAGE_ID"])&gt;0) <b>CFile::Delete</b>($arForm["IMAGE_ID"]);	
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/file/deletedirfiles.php">DeleteDirFiles</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/file/deletedirfilesex.php">DeleteDirFilesEx</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cfile/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;
		$io = CBXVirtualIo::GetInstance();
		$ID = intval($ID);

		if($ID <= 0)
			return;

		$res = CFile::GetByID($ID);
		if($res = $res->Fetch())
		{
			$delete_size = 0;
			$upload_dir = COption::GetOptionString("main", "upload_dir", "upload");

			$dname = $_SERVER["DOCUMENT_ROOT"]."/".$upload_dir."/".$res["SUBDIR"];
			$fname = $dname."/".$res["FILE_NAME"];
			$file = $io->GetFile($fname);

			if($file->isExists() && $file->unlink())
					$delete_size += $res["FILE_SIZE"];

			$delete_size += CFile::ResizeImageDelete($res);

			$DB->Query("DELETE FROM b_file WHERE ID = ".$ID);

			$directory = $io->GetDirectory($dname);
			if($directory->isExists() && $directory->isEmpty())
				$directory->rmdir();

			CFile::CleanCache($ID);

			foreach(GetModuleEvents("main", "OnFileDelete", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($res));

			/****************************** QUOTA ******************************/
			if($delete_size > 0 && COption::GetOptionInt("main", "disk_space") > 0)
				CDiskQuota::updateDiskQuota("file", $delete_size, "delete");
			/****************************** QUOTA ******************************/
		}
	}

	public static function DoDelete($ID)
	{
		CFile::Delete($ID);
	}
}
?>