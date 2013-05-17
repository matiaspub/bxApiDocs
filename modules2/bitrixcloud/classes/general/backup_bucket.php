<?
class CBitrixCloudBackupBucket extends CCloudStorageBucket
{
	private $file_name = "";
	private $check_word = "";
	/**
	 *
	 * @param string $bucket_name
	 * @param string $prefix
	 * @param string $access_key
	 * @param string $secret_key
	 * @param string $session_token
	 * @param string $check_word
	 * @param string $file_name
	 */
	function __construct($bucket_name, $prefix, $access_key, $secret_key, $session_token, $check_word, $file_name)
	{
		$this->_ID = 0;
		$this->arBucket = array(
			"ACTIVE" => "Y",
			"SORT" => 0,
			"READ_ONLY" => "N",
			"SERVICE_ID" => "amazon_s3",
			"BUCKET" => $bucket_name,
			"LOCATION" => "",
			"CNAME" => "",
			"FILE_COUNT" => 0,
			"FILE_SIZE" => 0,
			"LAST_FILE_ID" => 0,
			"PREFIX" => $prefix,
			"SETTINGS" => array(
				"ACCESS_KEY" => $access_key,
				"SECRET_KEY" => $secret_key,
				"SESSION_TOKEN" => $session_token,
			),
			"FILE_RULES" => 'a:1:{i:0;a:3:{s:6:"MODULE";s:0:"";s:9:"EXTENSION";s:0:"";s:4:"SIZE";s:0:"";}}',
			"FILE_RULES_COMPILED" => array(
				array(
					"MODULE_MASK" => "",
					"EXTENTION_MASK" => "",
					"SIZE_ARRAY" => array(
					),
				),
			),
		);
		$this->file_name = $file_name;
		$this->check_word = $check_word;
	}
	/**
	 *
	 * @return string
	 *
	 */
	public static function getFileName()
	{
		return $this->GetFileSRC($this->file_name);
	}

	public static function getHeaders()
	{
		$service = new CCloudStorageService_AmazonS3;
		$headers = $service->SignRequest(
			$this->arBucket["SETTINGS"],
			"GET",
			$this->arBucket["BUCKET"],
			"/".$this->arBucket["PREFIX"]."/".$this->file_name,
			'',
			array(
				"x-amz-security-token" => $this->arBucket["SETTINGS"]["SESSION_TOKEN"],
			)
		);
		$headers["x-amz-security-token"] = $this->arBucket["SETTINGS"]["SESSION_TOKEN"];
		return $headers;
	}

	static function setPublic($public)
	{
		$this->service->setPublic($public);
	}

	public static function unsetCheckWordHeader()
	{
		$this->service->unsetHeader("x-amz-meta-check-word");
	}

	public static function setCheckWordHeader()
	{
		$this->service->setHeader("x-amz-meta-check-word" , $this->check_word);
	}
}
?>