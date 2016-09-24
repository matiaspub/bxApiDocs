<?
namespace Bitrix\Main\UI\Uploader;
use \Bitrix\Main\UI\FileInputUtility;
use \Bitrix\Main\Web\HttpClient;
use \Bitrix\Main\Web\Uri;
use \Bitrix\Main\Web\Json;


class Log
{
	/*
	 * @var \CBXVirtualFileFileSystem $file
	 */
	protected $file = null;
	var $data = array(
		"executed" => false,
		"files" => array()
	);

	static function __construct()
	{

	}

	public function setPath($path)
	{
		$this->file = \CBXVirtualIo::GetInstance()->GetFile($path);

		if ($this->file->IsExists())
		{
			$this->setValues(unserialize($this->file->GetContents()));
		}
	}
	public function setValue($key, $value)
	{
		if (array_key_exists($key, $this->data) && is_array($this->data) && is_array($value))
			$this->data[$key] = array_merge($this->data[$key], $value);
		else
			$this->data[$key] = $value;

		if ($this->file instanceof \CBXVirtualFileFileSystem)
		{
			$this->file->PutContents(serialize($this->data));
		}
		return true;
	}
	public function getValue($key)
	{
		return $this->data[$key];
	}
	public function setValues($data)
	{
		$tmp = $this->data;
		if (is_array($data))
		{
			foreach($data as $key => $val)
			{
				if (array_key_exists($key , $this->data) && is_array($this->data[$key]) && is_array($val))
					$this->data[$key] = array_merge($this->data[$key], $val);
				else
					$this->data[$key] = $val;
			}
		}
		if ($tmp != $this->data && $this->file instanceof \CBXVirtualFileFileSystem)
		{
			$this->file->PutContents(serialize($this->data));
		}
	}
	public function getValues()
	{
		return $this->data;
	}
	public function unlink()
	{
		if ($this->file instanceof \CBXVirtualFileFileSystem && $this->file->IsExists())
			$this->file->unlink();
	}
}

class Uploader
{
	public $files = array();
	public $controlId = "fileUploader";
	public $params = array(
		"allowUpload" => "A",
/*		"allowUploadExt" => "",
		"copies" => array(
			"copyName" => array(
				"width" => 100,
				"height" => 100
			)
		)*/
	);
/*
 * @var string $script Url to uploading page for forming url to view
 * @var string $path Path to temp directory
 * @var string $CID Controller ID
 * @var string $PID Package ID
 * @var string $mode
 * @var Status $status Uploading status
 * @var array $processTime Time limits
 * @var Log $log
 * @var Log $packLog
 * @var HttpClient $http
*/
	public $script;
	protected $path = "";
	protected $CID = "";
	protected $PID = "";
	protected $mode = "view";
	protected $status = "";
	protected $processTime = array( // Time limits
		"max" => 30,
		"current" => 0);

	protected $log;
	protected $packLog;

	protected $http;

	const FILE_NAME = "bxu_files";
	const INFO_NAME = "bxu_info";
	const EVENT_NAME = "main_bxu";
	const SESSION_LIST = "MFI_SESSIONS";
	const SESSION_TTL = 86400;

	public function __construct($params = array())
	{
		$this->status = new Status("ready");

		global $APPLICATION;
		$this->script = $APPLICATION->GetCurPageParam();

		$this->setParams($params);

		$this->path = \CTempFile::GetDirectoryName(
			12,
			array(
				"bxu",
				md5(serialize(array(
					$this->controlId,
					bitrix_sessid(),
					\CMain::GetServerUniqID()
					))
				)
			)
		);

		$this->processTime["max"] = intval(ini_get("max_execution_time")) * 0.75;
		$this->processTime["start"] = time();

		$this->log = new Log;
		$this->packLog = new Log;

		$this->http = new HttpClient;

		set_time_limit(0);

		return $this;
	}

	public function setParams($params)
	{
		global $APPLICATION;
		$params = (is_array($params) ? $params : array());

		if (array_key_exists("urlToUpload", $params))
			$this->script = $params["urlToUpload"];

		if (array_key_exists("copies", $params) && is_array($params["copies"]))
		{
			$copies = array();
			foreach($params["copies"] as $key => $val)
			{
				if (is_array($val) && (array_key_exists("width", $val) || array_key_exists("height", $val)))
				{
					$copies[$key] = array("width" => $val["width"], "height" => $val["height"]);
				}
			}
			if (!empty($copies))
			{
				$this->params["copies"] = $copies;
			}
		}

		if (array_key_exists("uploadFileWidth", $params))
			$this->params["uploadFileWidth"] = $params["uploadFileWidth"];
		if (array_key_exists("uploadFileHeight", $params))
			$this->params["uploadFileHeight"] = $params["uploadFileHeight"];
		if (array_key_exists("uploadMaxFilesize", $params))
			$this->params["uploadMaxFilesize"] = $params["uploadMaxFilesize"];

		if (array_key_exists("events", $params) && is_array($params["events"]))
		{
			foreach($params["events"] as $key => $val)
			{
				AddEventHandler(self::EVENT_NAME, $key, $val);
			}
		}

		if (array_key_exists("allowUpload", $params))
		{
			// ALLOW_UPLOAD = 'A'll files | 'I'mages | 'F'iles with selected extensions
			// ALLOW_UPLOAD_EXT = comma-separated list of allowed file extensions (ALLOW_UPLOAD='F')
			$this->params["allowUpload"] = (in_array($params["allowUpload"], array("A", "I", "F")) ? $params["allowUpload"] : "A");
			if ($params["allowUpload"] == "F" && empty($params["allowUploadExt"]))
				$this->params["allowUpload"] = "A";
			$this->params["allowUploadExt"] = $params["allowUploadExt"];
		}

		if (array_key_exists("controlId", $params))
			$this->controlId = $params["controlId"];
		$this->params["controlId"] = $this->controlId;
	}

	static public function setHandler($name, $callback)
	{
		AddEventHandler(self::EVENT_NAME, $name, $callback);
		return $this;
	}

	public function showError($status = false)
	{
		$status = ($status === false ? $this->status : $status);
		if ($status instanceof Error)
		{
			$result = array(
				"status" => $status->getStatus(),
				"error" => $status->getMessage());
			$this->showJsonAnswer($result);
		}
		return false;
	}

	public function checkTime()
	{
		if ($this->processTime["max"] > 0)
		{
			$res = (getmicrotime() - START_EXEC_TIME);
			return $res < $this->processTime["max"];
		}
		return true;
	}

	protected function glueChunk($fdst, &$chunk)
	{
		$buff = 4096;
		if (($fsrc = fopen($chunk['tmp_name'], 'r')) && $fsrc)
		{
			$status = new Status("written");
			fseek($fdst, $chunk["start"], SEEK_SET);
			while(!feof($fsrc) && ($data = fread($fsrc, $buff)))
			{
				if ($data !== '' && $data !== false)
				{
					fwrite($fdst, $data);
				}
				else
				{
					$status = new Error("BXU349.2");
					break;
				}
			}
			fclose($fsrc);
			unlink($chunk['tmp_name']);
		}
		else
		{
			$status = new Error("BXU349.3");
		}
		return $status;
	}

	/**
	 * @param $path
	 * @param $chunks
	 * @param array $chunksInfo
	 * @return Error|Status|bool
	 */
	protected function copyChunks($path, &$chunks, &$chunksInfo = array())
	{
		$status = true;
		$chunksToWrite = array();

		foreach ($chunks as $chunkNumber => $chunk)
		{
			$result = new Status($chunk["status"]);
			if ($result->getStatus() == "inprogress")
			{
				$tmp_name = $path.".".$chunkNumber;
				@unlink($tmp_name);
				if (!move_uploaded_file($chunk["tmp_name"], $tmp_name))
				{
					$result = new Error("BXU347.2");
					$status = $result;
					break;
				}
				else
				{
					$chunks[$chunkNumber]["tmp_name"] = $tmp_name;
					$chunks[$chunkNumber]["number"] = $chunkNumber;
					if (array_key_exists($chunkNumber, $chunksInfo["uploaded"]))
						$chunksInfo["uploaded"][$chunkNumber]++;
					else
						$chunksInfo["uploaded"][$chunkNumber] = 0;
					$result = new Status("uploaded");
				}
				$chunks[$chunkNumber]["tmp_name"] = $tmp_name;
			}

			$chunks[$chunkNumber]["status"] = $result->getStatus();
			if ($result->getStatus() == "uploaded")
				$chunksToWrite[$chunkNumber] = $chunks[$chunkNumber];
		}

		if ($status === true && !empty($chunksToWrite) && $this->checkTime())
		{
			if (!(($fdst = fopen($path, 'cb')) && $fdst))
			{
				fclose($fdst);
				$status = new Error("BXU349.1");
			}
			else if (!flock($fdst, LOCK_EX))
			{
				fclose($fdst);
				$status = new Status("inprogress", "BXU349.100");
			}
			else
			{
				foreach ($chunksToWrite as $chunkNumber => $chunk)
				{
					$chunk = $chunks[$chunkNumber];
					$result = $this->glueChunk($fdst, $chunk);
					$chunks[$chunkNumber]["status"] = $result->getStatus();
					if ($result instanceof Error)
					{
						$status = $result;
						break;
					}
					else if (array_key_exists($chunkNumber, $chunksInfo["written"]))
					{
						$chunksInfo["written"][$chunkNumber]++;
					}
					else
					{
						$chunksInfo["written"][$chunkNumber] = 0;
					}

					if (!$this->checkTime())
						break;
				}
				@fflush($fdst);
				@flock($fdst, LOCK_UN);
				fclose($fdst);
				@chmod($path, BX_FILE_PERMISSIONS);
			}
		}
		if ($status === true || $status instanceof Status)
		{
			if (count($chunksInfo["written"]) == $chunksInfo["count"])
			{
				$status = new Status("uploaded");
			}
			else
			{
				$status = new Status("inprogress");
			}
		}
		return $status;
	}
	/**
	 * Copies file from really tmp dir to repo
	 * @param $file
	 * @param $canvas
	 * @param $res
	 * @return Status|Error
	 */
	protected function copyFile($file, $canvas, &$res)
	{
		if (is_array($res) && array_key_exists("url", $res))
		{
			return new Status("uploaded");
		}
		$hash = $this->getHash($file);
		$io = \CBXVirtualIo::GetInstance();
		$directory = $io->getDirectory($this->path.$hash);
		$path = $this->path.$hash."/".$canvas;
		$status = new Error("BXU347.2");
		if (!$directory->create())
		{
			$status = new Error("BXU347.1");
		}
		elseif (array_key_exists('tmp_url', $res))
		{
			if ((!file_exists($path) || @unlink($path)) && $this->http->download($res["tmp_url"], $path) !== false)
			{
				$status = new Status("uploaded");
			}
		}
		elseif (array_key_exists('chunks', $res))
		{
			$status = $this->copyChunks($path, $res['chunks'], $res['chunksInfo']);
		}
		elseif (!file_exists($res['tmp_name']))
		{
			if ($canvas != "default" &&
				!empty($file["files"]["default"]) &&
				$res["width"] <= $file["files"]["default"]["width"] &&
				$res["height"] <= $file["files"]["default"]["height"] &&
				@copy($file["files"]["default"]["tmp_path"], $path) &&
				is_file($path))
			{
				@chmod($path, BX_FILE_PERMISSIONS);
				$res["tmp_name"] = $path;
				$status = new Status("uploaded");
			}
			else
			{
				$status = new Error("BXU347.2");
			}
		}
		elseif ((!file_exists($path) || @unlink($path)) && move_uploaded_file($res['tmp_name'], $path))
		{
			$status = new Status("uploaded");
		}
		$res["name"] = $file["name"];
		if ($status->getStatus() == "uploaded")
		{
			$res["tmp_name"] = $path;
			$res["size"] = filesize($path);
			unset($res['chunks']);
			unset($res['chunksInfo']);

			if (empty($res["type"]) || $canvas != "default")
				$res["type"] = (array_key_exists("type", $file) ? $file["type"] : \CFile::GetContentType($path));

			$res["url"] = $this->getUrl($file["hash"]."_".$canvas);
			$res["sizeFormatted"] = \CFile::FormatSize($res["size"]);
		}

		return $status;
	}

	/**
	 * @param array $file
	 * @return string
	 */
	public function getHash($file = array())
	{
		if (empty($file["id"]))
			return $this->controlId.md5($file["id"]);
		return $file["id"];
	}

	/**
	 * this function just merge 2 arrays with a lot of deep keys
	 * array_merge replaces keys in second level and deeper
	 * array_merge_recursive multiplies similar keys
	 * @param $res
	 * @param $res2
	 * @return array
	 */
	
	/**
	* <p>Статический метод соединяет два массива с несколькими вложенными ключами: <code>array_merge</code> перемещает ключи на второй уровень и вложенный <code>array_merge_recursive</code> подобных умноженных ключей.</p>
	*
	*
	* @param mixed $res  
	*
	* @param $re $res2  
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/uploader/uploader/merge.php
	* @author Bitrix
	*/
	static function merge($res, $res2)
	{
		$res = is_array($res) ? $res : array();
		$res2 = is_array($res2) ? $res2 : array();
		foreach ($res2 as $key => $val)
		{
			if (array_key_exists($key, $res) && is_array($val))
				$res[$key] = self::merge($res[$key], $val);
			else
				$res[$key] = $val;
		}
		return $res;
	}
	/**
	 * Decodes and converts keys(!) and values
	 * @param $data
	 * @return array
	 */
	protected static function __UnEscape($data)
	{
		global $APPLICATION;

		if(is_array($data))
		{
			$res = array();
			foreach($data as $k => $v)
			{
				$k = $APPLICATION->ConvertCharset(\CHTTP::urnDecode($k), "UTF-8", LANG_CHARSET);
				$res[$k] = self::__UnEscape($v);
			}
		}
		else
		{
			$res = $APPLICATION->ConvertCharset(\CHTTP::urnDecode($data), "UTF-8", LANG_CHARSET);
		}

		return $res;
	}
	/**
	 * Generates hash from info about file
	 * @param $chunksCount
	 * @param $chunkNumber
	 * @return string
	 */
	protected static function getChunkKey($chunksCount, $chunkNumber)
	{
		$chunksCount = max(ceil(log10($chunksCount)), 4);
		return "p".str_pad($chunkNumber, $chunksCount, "0", STR_PAD_LEFT);
	}
	/**
	 * excludes real paths from array
	 * @param $item - array
	 * @return array
	 */
	protected static function removeTmpPath($item)
	{
		if (is_array($item))
		{
			if (array_key_exists("tmp_name", $item))
			{
				unset($item["tmp_name"]);
			}
			foreach ($item as $key => $val)
			{
				if ($key == "chunksInfo")
				{
					$item[$key]["uploaded"] = count($item[$key]["uploaded"]);
					$item[$key]["written"] = count($item[$key]["written"]);
				}
				else if (is_array($val))
				{
					$item[$key] = self::removeTmpPath($val);
				}
			}
		}
		return $item;
	}

	/**
	 * Returns all saved data for this file hash
	 * @param $hash
	 * @param bool $copies
	 * @param bool $watermark
	 * @return array
	 */
	
	/**
	* <p>Нестатический метод возвращает все сохранённые данные для хеша этого файла.</p>
	*
	*
	* @param mixed $hash  
	*
	* @param boolean $copies = false 
	*
	* @param boolean $watermark = false 
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/uploader/uploader/getfile.php
	* @author Bitrix
	*/
	public function getFile($hash, $copies = false, $watermark = false)
	{
		if ($copies === false && array_key_exists("copies", $this->params))
		{
			$copies = $this->params["copies"];
			$default = array();
			if (array_key_exists("uploadFileWidth", $this->params))
				$default["width"] = $this->params["uploadFileWidth"];
			if (array_key_exists("uploadFileHeight", $this->params))
				$default["height"] = $this->params["uploadFileHeight"];
			if (!empty($default))
				$copies["default"] = $default;
		}
		$files = array();
		$hashes = FileInputUtility::instance()->checkFiles($this->controlId, (is_array($hash) ? $hash : array($hash)));
		if (!empty($hashes))
		{
			foreach ($hashes as $h)
			{
				$file = $this->getFromCache($h);
				if (!!$file && (!empty($copies) || !empty($watermark)))
				{
					$this->checkCanvases($hash, $file, $copies, $watermark);
				}
				$files[$h] = $file;
			}
		}
		return (is_array($hash) ? $files : $files[$hash]);
	}

	protected function getFromCache($hash, $data = array())
	{
		$file = \CBXVirtualIo::GetInstance()->GetFile($this->path.$hash."/.log");
		;
		return self::merge(unserialize($file->GetContents()), $data);
	}

	protected function setIntoCache($hash, $data)
	{
		$io = \CBXVirtualIo::GetInstance();
		$directory = $io->GetDirectory($this->path.$hash);
		if ($directory->Create())
		{
			$file = $io->GetFile($this->path.$hash."/.log");
			$file->PutContents(serialize($data));
		}
	}

	/**
	 * @param string $hash
	 * @param string $act
	 * @return string
	 */
	public function getUrl($hash, $act = "view")
	{
		return \CHTTP::URN2URI($this->script.(strpos($this->script, "?") === false ? "?" : "&").
			\CHTTP::PrepareData(
				array(
					self::INFO_NAME => array(
						"CID" => $this->CID,
						"mode" => $act,
						"hash" => $hash
					)
				)
			)
		);
	}

	protected function saveFile(&$file)
	{
		if (empty($file["files"]))
		{
			$status = new Error("BXU344.2");
		}
		else
		{
			$status = new Status("uploaded");

			//Check declared instances
			$instances = array_merge((array_key_exists("canvases", $file) ? $file["canvases"] : array()), array("default" => "nothing"));
			foreach ($file["files"] as $instanceName => $res)
			{
				$result = $this->copyFile($file, $instanceName, $file["files"][$instanceName]);
				if ($result instanceof Error)
				{
					$status = $result;
					break;
				}
				elseif($result->getStatus() == "inprogress")
				{
					$status = $result;
				}
				else
				{
					unset($instances[$instanceName]);
				}
			}

			if ($status->getStatus() == "uploaded" && !empty($instances))
			{
				$status = new Status("inprogress");
			}
		}
		return $status;
	}

	/**
	 * Checks file params
	 * @param $file
	 * @param $arFile
	 * @return mixed|null|string
	 */
	protected function checkFile($file, &$arFile)
	{
		$status = new Status("checked");
		if ($file["error"] > 0)
			$status = new Error("BXU347.2", $file["error"]);
		else if (array_key_exists("tmp_url", $file))
		{
			$url = new Uri($file["tmp_url"]);

			if ($url->getHost() == '' && ($tmp = \CFile::MakeFileArray($url->getPath())) && is_array($tmp))
			{
				$file = array_merge($tmp, $file);
			}
			else if ($url->getHost() <> '' && $this->http->query("HEAD", $file["tmp_url"]) && $this->http->getStatus() == "200")
			{
				$file = array_merge($file, array(
					"size" => (int) $this->http->getHeaders()->get("content-length"),
					"type" => $this->http->getHeaders()->get("content-type")
				));
			}
			else
			{
				$status = new Error("BXU347.2");
			}
		}
		else if (!is_uploaded_file($file['tmp_name']) || !file_exists($file['tmp_name']))
		{
			$status = new Error("BXU347.2");
		}

		if ($status instanceof Error)
		{
			//
		}
		elseif ($this->params["allowUpload"] == "I")
		{
			$error = \CFile::CheckFile($file, $this->params["uploadMaxFilesize"], "image/", \CFile::GetImageExtensions());
			if (!empty($error))
				$status = new Error("BXU347.3", $error);
		}
		elseif ($this->params["allowUpload"] == "F")
		{
			$error = \CFile::CheckFile($file, $this->params["uploadMaxFilesize"], false, $this->params["allowUploadExt"]);
			if (!empty($error))
				$status = new Error("BXU347.3", $error);
		}
		else
		{
			$error = \CFile::CheckFile($file, $this->params["uploadMaxFilesize"]);
			if (!empty($error))
				$status = new Error("BXU347.3", $error);
		}

		if ($status instanceof Status)
		{
			$matches = array();
			$name = $file["~name"];
			if (preg_match("/^(.+?)\\.ch(\\d+)\\.(\\d+)\\.chs(\\d+)$/", $file["~name"], $matches))
				$name = $matches[1];

			$key = (!empty($name) ? $name : 'default');
			$file["copy"] = $key;

			if (empty($matches))
			{
				$arFile["files"][$key] = $file;
			}
			else
			{
				$fileAddInfo = array(
					"chunks" => array(),
					"chunksInfo" => array(
						"count" => $matches[4],
						"uploaded" => array(),
						"written" => array()
					)
				);
				if (array_key_exists($key, $arFile["files"]))
					$fileAddInfo = $arFile["files"][$key];

				$file["status"] = "inprogress";
				$file["number"] = $matches[2];
				$file["start"] = $matches[3];

				$fileAddInfo["chunks"][self::getChunkKey($fileAddInfo["chunksInfo"]["count"], $file["number"])] = $file;
				$arFile["files"][$key] = $fileAddInfo;
			}
		}

		return $status;
	}

	protected function getUploadedFiles($data)
	{
		$uploadedFiles = self::__UnEscape($_FILES);
		$uploadedFilesInPost = self::__UnEscape($_POST);
		$filesPostInfo = array();
		if (array_key_exists(self::FILE_NAME, $uploadedFilesInPost))
		{
			foreach($uploadedFilesInPost[self::FILE_NAME] as $fileID => $file)
			{
				if (!array_key_exists($fileID, $data) || $data[$fileID]["status"] !== "inprogress" || !is_array($file) ||
					!array_key_exists("files", $file) || !is_array($file["files"]))
					continue;
				$filesPostInfo[$fileID] = $file["files"];
			}
		}
		$files = array();
		if (array_key_exists(self::FILE_NAME, $uploadedFiles) && !empty($uploadedFiles[self::FILE_NAME]["name"]))
		{
			foreach($uploadedFiles[self::FILE_NAME]["name"] as $fileID => $fileNames)
			{
				if (!array_key_exists($fileID, $data) || $data[$fileID]["status"] !== "inprogress")
					continue;
				$arFile = $data[$fileID];
				$files[$fileID] = array();
				if (is_array($fileNames))
				{
					foreach ($fileNames as $fileName => $val)
					{
						$postData = array();
						if (array_key_exists($fileID, $filesPostInfo) && is_array($filesPostInfo[$fileID]) &&
							array_key_exists($fileName, $filesPostInfo[$fileID]))
							$postData = $filesPostInfo[$fileID][$fileName];

						$file = array(
							"name" => $arFile["name"],
							"~name" => $fileName,
							"tmp_name" => $uploadedFiles[self::FILE_NAME]["tmp_name"][$fileID][$fileName],
							"type" => $uploadedFiles[self::FILE_NAME]["type"][$fileID][$fileName],
							"size" => intval($uploadedFiles[self::FILE_NAME]["size"][$fileID][$fileName]),
							"error" => intval($uploadedFiles[self::FILE_NAME]["error"][$fileID][$fileName])
						);
						if ($file["type"] == "application/octet-stream" && array_key_exists("type", $arFile))
							$file["type"] = $arFile["type"];
						$files[$fileID][] = array_merge($postData, $file);
					}
				}
				else
				{
					$fileName = $fileNames;
					$postData = array();
					if (array_key_exists($fileID, $filesPostInfo) && is_array($filesPostInfo[$fileID]) &&
						array_key_exists($fileName, $filesPostInfo[$fileID]))
						$postData = $filesPostInfo[$fileID][$fileName];
					$file = array(
						"name" => $arFile["name"],
						"~name" => $fileName,
						"tmp_name" => $uploadedFiles[self::FILE_NAME]["tmp_name"][$fileID],
						"type" => $uploadedFiles[self::FILE_NAME]["type"][$fileID],
						"size" => intval($uploadedFiles[self::FILE_NAME]["size"][$fileID]),
						"error" => intval($uploadedFiles[self::FILE_NAME]["error"][$fileID])
					);
					$files[$fileID][] = array_merge($postData, $file);
				}
			}
		}
		else if (!empty($filesPostInfo))
		{
			foreach ($filesPostInfo as $fileID => $fileData)
			{
				if (is_array($fileData))
				{
					$arFile = $data[$fileID];
					$files[$fileID] = array();
					foreach ($fileData as $copyName => $copyData)
					{
						if (is_array($copyData) && array_key_exists("tmp_url", $copyData))
						{
							$file = array(
								"name" => $arFile["name"],
								"~name" => $copyName,
								"tmp_url" => $copyData["tmp_url"]
							);
							$files[$fileID][] = array_merge($copyData, $file);
						}
					}
				}
			}
		}
		return $files;
	}
	/**
	 * Main function for uploading data
	 */
	
	/**
	* <p>Нестатический метод. Главный метод для загрузки данных.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/uploader/uploader/uploaddata.php
	* @author Bitrix
	*/
	public function uploadData()
	{
		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		$post = array_merge($request->getQueryList()->toArray(), $request->getPostList()->toArray());
		$error = "";
		$post = self::__UnEscape($post);

		$status = null;

		if ($this->getPost("type") != "brief") // If it is IE8
		{
			$eventName = ($this->log->getValue("executed") == false ? "onUploadIsStarted" : "onUploadIsContinued");
			$this->log->setValue("executed", true);
			$logData = $this->log->getValues(); $packData = $this->packLog->getValues();
			foreach(GetModuleEvents(self::EVENT_NAME, $eventName, true) as $arEvent)
			{
				if (ExecuteModuleEventEx($arEvent, array(&$packData, &$logData, &$post, &$_FILES, &$error)) === false)
				{
					$status = new Error("BXU350.1", $error);
					break;
				}
			}
			if ($status === null)
			{
				$eventName = ($this->packLog->getValue("executed") == false ? "onPackageIsContinued" : "onPackageIsStarted");
				$this->packLog->setValue("executed", true);
				foreach(GetModuleEvents(self::EVENT_NAME, $eventName, true) as $arEvent)
				{
					if (ExecuteModuleEventEx($arEvent, array(&$packData, &$logData, &$post, &$_FILES, &$error)) === false)
					{
						$status = new Error("BXU350.1", $error);
						break;
					}
				}
			}
			$this->log->setValues($logData);
			$this->packLog->setValues($packData);
		}
		if ($status === null)
		{
			$data = array();
			if (!empty($post[self::FILE_NAME]))
			{
				foreach($post[self::FILE_NAME] as $fileID => $props)
				{
					$hash = $this->getHash(array("id" => $fileID, "name" => $props["name"]));
					$data[$fileID] = array_merge($props, array(
						"status" => "inprogress",
						"hash" => $hash,
						"id" => $fileID,
						"files" => array())
					);
					if (FileInputUtility::instance()->checkFile($this->CID, $hash))
					{
						$data[$fileID] = self::merge($data[$fileID], $this->getFromCache($data[$fileID]["hash"]));
						if ($props["restored"] == "Y")
						{
							$data[$fileID]["status"] = "inprogress";
						}
					}
				}
			}
			/*@var $files array*/
			$files = $this->getUploadedFiles($data);
			$logData = $this->log->getValues(); $packData = $this->packLog->getValues();
			foreach ($data as $fileID => $file)
			{
				if (!$this->checkTime())
					break;
				$result = new Status($file["status"]);
				if ($result->getStatus() == "inprogress")
				{
					unset($file["restored"]);
					unset($file["executed"]);
					if (array_key_exists($fileID, $files))
					{
						FileInputUtility::instance()->registerFile($this->CID, $file["hash"]);
						foreach ($files[$fileID] as $f)
						{
							$res = $this->checkFile($f, $file);
							if ($res instanceof Error)
							{
								$result = $res;
								break;
							}
						}
					}
					if ($result->getStatus() == "inprogress")
					{
						$result = $this->saveFile($file);
					}
				}
				if (array_key_exists("restored", $file) &&
					array_key_exists("executed", $file) &&
					array_key_exists("~status", $file))
				{
					unset($file["restored"]);
					unset($file["executed"]);
					$result = new Status($file["~status"]);
				}
				$file["~status"] = $result->getStatus();
				if ($result->getStatus() == "uploaded" && $this->getPost("type") != "brief" && !array_key_exists("executed", $file))
				{
					$packData1 = $packData;
					$logData1 = $logData;
					$file["executed"] = "Y";
					foreach(GetModuleEvents(self::EVENT_NAME, "onFileIsUploaded", true) as $arEvent)
					{
						$error = "";
						if (!ExecuteModuleEventEx($arEvent, array($file["hash"], &$file, &$packData, &$logData, &$error)))
						{
							$result = new Error("BXU350.1", $error);
							$file["executed"] = "error";
							break;
						}
					}
					if ($logData1 != $logData)
						$this->log->setValues($logData);
					if ($packData1 != $packData)
						$this->packLog->setValues($packData);
				}
				$file["status"] = $result->getStatus();
				$this->setIntoCache($file["hash"], $file);

				// it is a compatibility
				$log = array(
					"status" => $file["status"],
					"hash" => $file["hash"]);
				if ($result instanceof Error)
				{
					$log += array(
						"error" => $result->getMessage(),
						"errorCode" => $result->getCode(),
					);
					if (empty($log["error"]))
						$log["error"] = "Unknown error.";

//					trigger_error("Uploading error: ".$file["name"]." wasn't uploaded: ".$log["error"]." [".$log["errorCode"]."]", E_USER_WARNING);
				}
				$this->files[$fileID] = $log + array("file" => $file);
				if ($file["status"] == "uploaded" || $file["status"] == "error")
					$this->packLog->setValue("files", array($file["id"] => $file["status"]));
			}
			$declaredFiles = (int) $this->packLog->getValue("filesCount");
			if ($declaredFiles > 0 && $declaredFiles == count($this->packLog->getValue("files")))
			{
				$status = new Status("done");
				if ($this->getPost("type") !== "brief")
				{
					foreach(GetModuleEvents(self::EVENT_NAME, "onPackageIsFinished", true) as $arEvent)
					{
						if (ExecuteModuleEventEx($arEvent, array($packData, $logData, $post, $this->files)) === false)
						{
							$status = new Error("BXU350.1", $error);
							break;
						}
					}
				}
			}
			else
			{
				$status = new Status("inprogress");
			}
			if ($this->getPost("type") == "brief" || $status->getStatus() == "inprogress")
			{
				$this->log->setValues($logData);
				$this->packLog->setValues($packData);
			}
			else
			{
				$this->packLog->unlink();
			}
		}
		$this->status = $status->getStatus();
		return $status;
	}

	public function deleteFile($hash)
	{
		if (FileInputUtility::instance()->unRegisterFile($this->CID, $hash))
		{
			$io = \CBXVirtualIo::GetInstance();
			$directory = $io->GetDirectory($this->path.$hash);
			$res = $directory->GetChildren();
			foreach($res as $file)
				$file->unlink();
			$directory->rmdir();
			return true;
		}
		return false;
	}

	public function viewFile($hash)
	{
		$file = false;
		$copy = "";
		if (strpos($hash, "_") > 0)
		{
			$copy = explode("_", $hash);
			$hash = $copy[0]; $copy = $copy[1];
		}
		$copy = (!!$copy ? $copy : "default");
		if (FileInputUtility::instance()->checkFile($this->CID, $hash))
		{
			$file = $this->getFromCache($hash);
			$file = $file["files"][$copy];
		}
		if ($file)
			\CFile::ViewByUser($file, array("content_type" => $file["type"]));
	}

	static public function getData($data)
	{
		array_walk_recursive($data, create_function('&$v,$k',
					'if($k=="error"){$v=preg_replace("/<(.+?)>/is".BX_UTF_PCRE_MODIFIER, "", $v);}'));
		return self::removeTmpPath($data);
	}
	protected function fillRequireData($requestType)
	{
		$this->mode = $this->getPost("mode", $requestType);
		$this->CID = FileInputUtility::instance()->registerControl($this->getPost("CID", $requestType), $this->controlId);
		if (in_array($this->mode, array("upload", "delete", "view")))
		{
			$directory = \CBXVirtualIo::GetInstance()->GetDirectory($this->path);
			$directoryExists = $directory->IsExists();

			if ($this->mode != "view" && !check_bitrix_sessid())
				$this->status = new Status("BXU345.1");
			else if (!$directory->Create())
				$this->status = new Status("BXU345.2");
			else if ($this->getPost("packageIndex", $requestType))
			{
				$this->PID = $this->getPost("packageIndex");
				$this->packLog->setPath($this->path.$this->getPost("packageIndex").".package");
				$this->packLog->setValue("filesCount", $this->getPost("filesCount"));
			}
			else if ($this->mode == "upload")
				$this->status = new Status("BXU344.1");

			$this->log->setPath($this->path.$this->CID.".log");

			if (!$directoryExists)
			{
				$access = \CBXVirtualIo::GetInstance()->GetFile($directory->GetPath()."/.access.php");
				$content = '<?$PERM["'.$directory->GetName().'"]["*"]="X";?>';

				if (!$access->IsExists() || strpos($access->GetContents(), $content) === false)
				{
					if (($fd = $access->Open('ab')) && $fd)
						fwrite($fd, $content);
					fclose($fd);
				}
			}


			return true;
		}
		return false;
	}

	protected function getPost($key = "", $checkPost = true)
	{
		static $request = false;
		if ($key === true || $key === false)
		{
			$checkPost = $key;
			$key = "";
		}

		$checkPost = ($checkPost === true ? "postAndGet" : "onlyGet");
		if ($request === false)
		{
			$req = \Bitrix\Main\Context::getCurrent()->getRequest();
			$request = array(
				"onlyGet" => $req->getQueryList()->toArray(),
				"postAndGet" => array_merge($req->getQueryList()->toArray(), $req->getPostList()->toArray())
			);
		}
		$post = $request[$checkPost];
		if ($key == "")
			return array_key_exists(self::INFO_NAME, $post) ? $post[self::INFO_NAME] : false;
		else if (array_key_exists(self::INFO_NAME, $post) && array_key_exists($key, $post[self::INFO_NAME]))
			return $post[self::INFO_NAME][$key];
		return false;
	}


	protected function showJsonAnswer($result)
	{
		if (!defined("PUBLIC_AJAX_MODE"))
			// define("PUBLIC_AJAX_MODE", true);
		if (!defined("NO_KEEP_STATISTIC"))
			// define("NO_KEEP_STATISTIC", "Y");
		if (!defined("NO_AGENT_STATISTIC"))
			// define("NO_AGENT_STATISTIC", "Y");
		if (!defined("NO_AGENT_CHECK"))
			// define("NO_AGENT_CHECK", true);
		if (!defined("DisableEventsCheck"))
			// define("DisableEventsCheck", true);
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

		$GLOBALS["APPLICATION"]->RestartBuffer();
		while(ob_end_clean());

		$version = IsIE();
		if ( !(0 < $version && $version < 10) )
			header('Content-Type:application/json; charset=UTF-8');

		echo Json::encode($result);
		\CMain::finalActions();
		die;
	}


	public function checkPost($checkPost = true)
	{
		if ($this->getPost("", $checkPost) && $this->fillRequireData($checkPost) && !$this->showError())
		{
			if ($this->mode == "upload")
			{
				$status = $this->uploadData();
				$result = array(
					"status" => $status->getStatus(),
					"package" => $this->getData(array($this->PID => $this->packLog->getValues())),
					"report" => array("uploading" => array($this->CID => $this->getData($this->log->getValues()))),
					"files" => $this->getData($this->files)
				);
				if ($status instanceof Error)
					$result["error"] = $status->getMessage();
				$this->showJsonAnswer($result);
			}
			else if ($this->mode == "delete")
			{
				$this->showJsonAnswer(array("result" => $this->deleteFile($this->getPost("hash"))));
			}
			else
			{
				$this->viewFile($this->getPost("hash"));
			}
		}
		return false;
	}

	protected static function createCanvas($source, $dest, $canvasParams = array(), $watermarkParams = array())
	{
		$watermark = (array_key_exists("watermark", $source) ? array() : $watermarkParams);
		if (\CFile::ResizeImageFile(
			$source["tmp_name"],
			$dest["tmp_name"],
			$canvasParams,
			BX_RESIZE_IMAGE_PROPORTIONAL,
			$watermark,
			$canvasParams["quality"],
			array()
		))
		{
			$dest = array_merge($source, $dest);
			if (array_key_exists("watermark", $source) || !empty($watermarkParams))
				$dest["watermark"] = true;
		}
		else
			$dest["error"] = 348;
		return $dest;
	}

	public function checkCanvases($hash, &$file, $canvases = array(), $watermark = array())
	{
		if (!empty($watermark))
		{
			$file["files"]["default"] = self::createCanvas(
				$file["files"]["default"],
				$file["files"]["default"],
				array(),
				$watermark
			);
		}
		if (is_array($canvases))
		{
			foreach ($canvases as $canvas => $canvasParams)
			{
				if (!array_key_exists($canvas, $file["files"]))
				{
					$sourceKey = "default"; $source = $file["files"][$sourceKey]; // TODO pick up more appropriate copy by params
					$res = array(
						"copy" => $canvas,
						"tmp_name" => $this->path.$hash."/".$canvas,
						"url" => $this->getUrl($hash."_".$canvas)
					);
					$file["files"][$canvas] = $res + self::createCanvas($source, $res, $canvasParams, $watermark);

				}
			}
		}
		return $file;
	}
}