<?
/*
##############################################
# Bitrix Site Manager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
*/
IncludeModuleLangFile(__FILE__);

/**
 * <b>CDiskQuota</b> - класс для работы с дисковыми квотами.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdiskquota/index.php
 * @author Bitrix
 */
class CAllDiskQuota
{
	var $max_execution_time = 20; // 20 sec
	var $LAST_ERROR = false;

	public function __construct($params = array())
	{
		if(array_key_exists("max_execution_time", $params) && intval($params["max_execution_time"]) > 0)
			$this->max_execution_time = intval($params["max_execution_time"]);
	}

	static function SetDBSize()
	{
	}

	public function SetDirSize($path="", $name="", $recount=false, $skip_dir = false)
	{
		if (empty($name))
			$name = $path;
		if ((empty($name) && empty($path)) || ($path == "files"))
		{
			$name = "files";
			$path = "";
		}

		$abs_path = str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$path."/");
		$path =  str_replace("//", "/", "/".$path."/");
		$size = 0;
		$result = array();

		if (empty($abs_path))
			return false;

		$record = array("size" => COption::GetOptionString("main_size", "~".$name));

		if ($skip_dir)
		{
			if (!is_array($skip_dir))
				$skip_dir = array($skip_dir);
			foreach ($skip_dir as $key => $path_to_skip_dir)
				$skip_dir[$key] = str_replace("//", "/", $abs_path.$path_to_skip_dir."/");
		}

		if (!empty($record["size"]) && !$recount)
		{
			$record = array_merge(
				unserialize(COption::GetOptionString("main_size", "~".$name."_params")),
				$record);

			$record["path_to_last_file"] = str_replace("//", "/", $abs_path.$record["file"]);
			if (is_file($record["path_to_last_file"]) && $record["status"] == "c")
			{
				$res = $this->GetDirListFromLastFile($abs_path, $record["path_to_last_file"], true, $skip_dir);
				if ($res["status"] == "done" || $res["status"] == "continue")
				{
					$properties = array(
						"status" => substr($res["status"], 0, 1),
						"file" => str_replace($abs_path, "", str_replace("//", "/", $res["last_file"])),
						"time" => time());

					$record["size"] = doubleVal($record["size"])+doubleVal($res["size"]);
					COption::SetOptionString("main_size", "~".$name, $record["size"]);
					COption::SetOptionString("main_size", "~".$name."_params", serialize($properties));
					$result = $res;
					$result["size"] = $record["size"];
				}
			}
			elseif ($record["status"] == "d" && !$recount)
			{
				return array("status" => "done", "last_file" => $record["file"], "size" => $record["size"]);
			}
		}

		if (empty($result))
		{

			$res = $this->GetDirListSimple($abs_path, true, $skip_dir);

			if ($res["status"] == "done" || $res["status"] == "continue")
			{
				$properties = array(
					"status" => substr($res["status"], 0, 1),
					"file" => str_replace($abs_path, "", str_replace("//", "/", $res["last_file"])),
					"time" => time());

				COption::SetOptionString("main_size", "~".$name, doubleVal($res["size"]));
				COption::SetOptionString("main_size", "~".$name."_params", serialize($properties));
				$result = $res;
			}
		}

		if (!empty($result))
			return $result;
		return array("status" => "error");
	}

	public function GetDirListSimple($path, $check_time = true, $skip_dir=false)
	{
		$path = str_replace("//", "/", $path."/");
		$res = array();
		$size = 0;
		$handle = @opendir($path);

		if ($handle)
		{
			while($file = readdir($handle))
			{
				if($file == "." || $file == "..")
				{
					continue;
				}

				if(is_dir($path.$file))
				{
					if (is_array($skip_dir) && (in_array(str_replace("//", "/", $path.$file."/"), $skip_dir)))
					{
						continue;
					}

					$res_rec = $this->GetDirListSimple($path.$file, $check_time, $skip_dir);
					$res = array_merge($res, $res_rec["tree"]);
					$size += doubleVal($res_rec["size"]);
					if ($res_rec["status"] == "continue")
					{
						$res_rec["tree"] = $res;
						$res_rec["size"] = doubleVal($size);
						return $res_rec;
					}
				}
				else
				{
					$res[] = $path.$file;
					$size += filesize($path.$file);
					if ($check_time && intVal(getmicrotime() - START_EXEC_TIME) >= $this->max_execution_time)
					{
						return array("tree" => $res, "status" => "continue", "last_file" => $path.$file, "size" => $size);
					}
				}
			}
			@closedir($handle);
		}
		else
		{
			return array("status" => "error");
		}
		return array("tree" => $res, "status" => "done", "last_file" => $path.$file, "size" => $size);
	}

	public function GetDirListFromLastFile($path, $path_to_last_file="", $check_time = true, $skip_dir = false)
	{
		$path = str_replace("//", "/", $path."/");
		$path_to_last_file = str_replace("//", "/", $path_to_last_file);
		$path_to_lf = str_replace($path, "", $path_to_last_file);
		$size = 0;
		$res = array();
		$path_tree = explode("/", $path_to_lf);
		while ($lf = array_pop($path_tree))
		{
			$path_to_dir = str_replace("//", "/", $path.implode("/", $path_tree)."/");
			$handle = @opendir($path_to_dir);
			$search = true;
			if ($handle)
			{
				while($file = readdir($handle))
				{
					if($file == "." || $file == ".." || $search)
					{
						if ($file == $lf)
							$search = false;
						continue;
					}

					if(is_dir($path_to_dir.$file))
					{
						if (is_array($skip_dir) && (in_array(str_replace("//", "/", $path.$file."/"), $skip_dir)))
						{
							continue;
						}
						$res_rec = $this->GetDirListSimple($path_to_dir.$file, $check_time);
						$res = array_merge($res, $res_rec["tree"]);
						$size += doubleVal($res_rec["size"]);
						if ($res_rec["status"] == "continue")
						{
							$res_rec["tree"] = $res;
							$res_rec["size"] = doubleVal($size);
							return $res_rec;
						}
					}
					else
					{

						$res[] = $path_to_dir.$file;
						$size += filesize($path_to_dir.$file);
						if ($check_time && intVal(getmicrotime() - START_EXEC_TIME) >= $this->max_execution_time)
						{
							return array("tree" => $res, "status" => "continue", "last_file" => $path_to_dir.$file, "size" => $size);
						}
					}
				}
			}
			@closedir($handle);
		}
		return array("tree" => $res, "status" => "done", "last_file" => $path.$file, "size" => $size);
	}

	public function Recount($id, $recount=false)
	{
		if ((COption::GetOptionInt("main", "disk_space") <= 0))
			return true;

		if ($id != "db" && $id != "files" && (!is_dir($_SERVER['DOCUMENT_ROOT']."/".$id)))
			return array("status" => "error");

		if ($id == "db")
		{
			if ($recount)
				$_SESSION["SESS_RECOUNT_DB"] = "Y";
			$result = array("size" => COption::GetOptionString("main_size", "~db"));
			$result["params"] = COption::GetOptionString("main_size", "~db_params");
			if (!empty($result["params"]) && is_array(unserialize($result["params"])))
			{
				$result = array_merge(unserialize($result["params"]), $result);
			}
		}
		elseif ($id == "files")
		{
			$result = $this->SetDirSize("", "files", $recount, array("bitrix"));
		}
		else
		{
			$result = $this->SetDirSize($id, "", $recount);
		}

		if (empty($result["time"]))
		{
			$result["time"] = time();
		}
		return $result;
	}

	public function GetDiskQuota()
	{
		if (COption::GetOptionInt("main", "disk_space") <= 0)
			return true;

		$this->LAST_ERROR = "";
		$arMsg = Array();

		if (COption::GetOptionInt("main_size", "~db") <= 0)
		{
			$_SESSION["SESS_RECOUNT_DB"] = "Y";
		}

		$quota = doubleVal(COption::GetOptionInt("main", "disk_space")*1024*1024 -
			COption::GetOptionInt("main_size", "~db") -
			COption::GetOptionInt("main_size", "~files"));

		if ($quota > 0)
		{
			return $quota;
		}

		$this->LAST_ERROR = GetMessage("MAIN_QUOTA_BAD");
		$arMsg[] = array("id"=>"QUOTA_BAD", "text"=> GetMessage("MAIN_QUOTA_BAD"));

		$e = new CAdminException($arMsg);
		$GLOBALS["APPLICATION"]->ThrowException($e);

		return false;
	}

	public static function UpdateDiskQuota($type, $size, $action)
	{
		if (COption::GetOptionInt("main", "disk_space") <= 0)
			return true;

		if (empty($type) || empty($size) || empty($action))
			return false;

		if (is_array($size))
			$size = strlen(implode("", $size));
		elseif (doubleval($size) > 0)
			$size = doubleval($size);
		else
			$size = strlen($size);

		$size = doubleval($size);

		$name = strtolower($type) == "db" ? "db" : "files";

		if (in_array(strtolower($action), array("delete", "del")))
		{
			COption::SetOptionString("main_size", "~".$name,
				doubleval(COption::GetOptionInt("main_size", "~".$name) - $size));
			return true;
		}
		if (in_array(strtolower($action), array("update", "insert", "add", "copy")))
		{
			COption::SetOptionString("main_size", "~".$name,
				doubleval(COption::GetOptionInt("main_size", "~".$name) + $size));
			return true;
		}
		return false;
	}

	public function CheckDiskQuota($params = array())
	{
		if (COption::GetOptionInt("main", "disk_space") <= 0)
			return true;

		if (defined("SKIP_DISK_QUOTA_CHECK") && constant("SKIP_DISK_QUOTA_CHECK") === true)
			return true;

		$quota = $this->GetDiskQuota();

		if ($quota === true || $quota === false)
			return $quota;

		if (!empty($params))
		{
			if (is_array($params))
			{
				$size = false;
				if (is_set($params, "FILE_SIZE"))
					$size = $params["FILE_SIZE"];
				elseif (is_set($params, "SIZE"))
					$size = $params["SIZE"];
				elseif (is_set($params, "file_size"))
					$size = $params["file_size"];
				elseif (is_set($params, "size"))
					$size = $params["size"];
				else
					$size = strlen(serialize($params));

				if ($size !== false)
					return ((double)$quota - $size) > 0;
			}
			if (!is_array($params) && doubleVal($params) > 0 && ((double)$quota - $params) > 0)
				return true;
			if (((double)$quota - strLen($params)) > 0)
				return true;
		}
		return false;
	}
}
?>
