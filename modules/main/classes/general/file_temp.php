<?
class CTempFile
{
	private static $arFiles = array();

	public static function GetAbsoluteRoot()
	{
		$io = CBXVirtualIo::GetInstance();

		if(defined('BX_TEMPORARY_FILES_DIRECTORY'))
		{
			return rtrim(BX_TEMPORARY_FILES_DIRECTORY, '/');
		}
		else
		{
			return $io->CombinePath(
				$_SERVER["DOCUMENT_ROOT"],
				COption::GetOptionString("main", "upload_dir", "upload"),
				"tmp"
			);
		}
	}

	public static function GetFileName($file_name = '')
	{
		$dir_name = self::GetAbsoluteRoot();
		$file_name = rel2abs("/", "/".$file_name);
		$i = 0;

		while(true)
		{
			$i++;

			if($file_name == '/')
				$dir_add = md5(mt_rand());
			elseif($i < 25)
				$dir_add = substr(md5(mt_rand()), 0, 3);
			else
				$dir_add = md5(mt_rand());

			$temp_path = $dir_name."/".$dir_add.$file_name;

			if(!file_exists($temp_path))
			{
				//Delayed unlink
				if(empty(self::$arFiles))
					register_shutdown_function(array('CTempFile', 'Cleanup'));

				self::$arFiles[$temp_path] = $dir_name."/".$dir_add;

				//Function ends only here
				return $temp_path;
			}
		}
	}

	public static function GetDirectoryName($hours_to_keep_files = 0, $subdir = "")
	{
		if($hours_to_keep_files <= 0)
			return self::GetFileName('');

		if($subdir === "")
		{
			$dir_name = self::GetAbsoluteRoot().'/BXTEMP-'.date('Y-m-d/H/', time()+3600*$hours_to_keep_files);
			$i = 0;
			while(true)
			{
				$i++;
				$dir_add = md5(mt_rand());
				$temp_path = $dir_name.$dir_add."/";

				if(!file_exists($temp_path))
					break;
			}
		}
		else //Fixed name during the session
		{
			$subdir = implode("/", (is_array($subdir) ? $subdir : array($subdir, bitrix_sessid())))."/";
			while (strpos($subdir, "//") !== false)
				$subdir = str_replace("//", "/", $subdir);
			$bFound = false;
			for($i = $hours_to_keep_files-1; $i > 0; $i--)
			{
				$dir_name = self::GetAbsoluteRoot().'/BXTEMP-'.date('Y-m-d/H/', time()+3600*$i);
				$temp_path = $dir_name.$subdir;
				if(file_exists($temp_path) && is_dir($temp_path))
				{
					$bFound = true;
					break;
				}
			}

			if(!$bFound)
			{
				$dir_name = self::GetAbsoluteRoot().'/BXTEMP-'.date('Y-m-d/H/', time()+3600*$hours_to_keep_files);
				$temp_path = $dir_name.$subdir;
			}
		}

		//Delayed unlink
		if(empty(self::$arFiles))
			register_shutdown_function(array('CTempFile', 'Cleanup'));

		//Function ends only here
		return $temp_path;
	}

	//PHP shutdown cleanup
	public static function Cleanup()
	{
		foreach(self::$arFiles as $temp_path => $temp_dir)
		{
			if(file_exists($temp_path))
			{
				//Clean a file from CTempFile::GetFileName('some.jpg');
				if(is_file($temp_path))
				{
					unlink($temp_path);
					@rmdir($temp_dir);
				}
				//Clean whole temporary directory from CTempFile::GetFileName('');
				elseif(
					substr($temp_path, -1) == '/'
					&& strpos($temp_path, "BXTEMP") === false
					&& is_dir($temp_path)
				)
				{
					CTempFile::_absolute_path_recursive_delete($temp_path);
				}
			}
		}

		//Clean directories with $hours_to_keep_files > 0
		$dir_name = self::GetAbsoluteRoot()."/";
		if($handle = opendir($dir_name))
		{
			while(($day_files_dir = readdir($handle)) !== false)
			{
				if(preg_match("/^BXTEMP-(.*?)\$/", $day_files_dir, $match) && is_dir($dir_name.$day_files_dir))
				{
					$this_day_name = 'BXTEMP-'.date('Y-m-d');
					if($day_files_dir < $this_day_name)
						CTempFile::_absolute_path_recursive_delete($dir_name.$day_files_dir);
					elseif($day_files_dir == $this_day_name)
					{
						if($hour_handle = opendir($dir_name.$day_files_dir))
						{
							$this_hour_name = date('H');
							while(($hour_files_dir = readdir($hour_handle)) !== false)
							{
								if($hour_files_dir == '.' || $hour_files_dir == '..')
									continue;
								if($hour_files_dir < $this_hour_name)
									CTempFile::_absolute_path_recursive_delete($dir_name.$day_files_dir.'/'.$hour_files_dir);
							}
						}
					}
				}
			}
			closedir($handle);
		}
	}

	private static function _absolute_path_recursive_delete($path)
	{
		if(strlen($path) == 0 || $path == '/')
			return false;

		$f = true;
		if(is_file($path) || is_link($path))
		{
			if(@unlink($path))
				return true;
			return false;
		}
		elseif(is_dir($path))
		{
			if($handle = opendir($path))
			{
				while(($file = readdir($handle)) !== false)
				{
					if($file == "." || $file == "..")
						continue;

					if(!CTempFile::_absolute_path_recursive_delete($path."/".$file))
						$f = false;
				}
				closedir($handle);
			}
			if(!@rmdir($path))
				return false;
			return $f;
		}
		return false;
	}

}
?>
