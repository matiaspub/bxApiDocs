<?php

namespace Bitrix\Sale\TradingPlatform;

use \Bitrix\Main\SystemException;

/**
 * Class Sftp
 * Transfer files via sftp
 * @package Bitrix\Sale\TradingPlatform
 */
class Sftp
{
	protected $login;
	protected $pass;
	protected $host;
	protected $port;
	protected $fingerprint;

	protected $connection;
	protected $sftp;

	/**
	 * Constructor.
	 * @param string $login Sftp login.
	 * @param string $pass Sftp password.
	 * @param string $host Sftp host.
	 * @param int $port Sftp port.
	 * @param string $fingerprint Hostkey hash.
	 */
	public function __construct($login, $pass, $host="mip.ebay.com" , $port=22, $fingerprint="A9429730355B91EC642AE6E6186DA3DC")
	{
		$this->host = $host;
		$this->login = $login;
		$this->pass = $pass;
		$this->port = $port;
		$this->fingerprint = $fingerprint;
	}

	/**
	 * Makes connection via SFTP
	 * @return bool.
	 * @throws \Bitrix\Main\SystemException
	 */
	public function connect()
	{
		$this->connection = @ssh2_connect($this->host, $this->port);

		if(!$this->connection)
			throw new SystemException("Can't connect via ssh to: ".$this->host.":".$this->port);

		if($this->fingerprint != "")
		{
			$fingerprint = ssh2_fingerprint($this->connection, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX);

			if ($fingerprint != $this->fingerprint)
				throw new SystemException("HOSTKEY MISMATCH! Possible Man-In-The-Middle Attack? Actual fingerint:".$fingerprint." expected: ".$this->fingerprint);
		}

		if(!@ssh2_auth_password($this->connection, $this->login, $this->pass))
			throw new SystemException("Incorrect sftp login or password ");

		$this->sftp = ssh2_sftp($this->connection);

		if(!$this->sftp)
			throw new SystemException("Could not initialize SFTP subsystem.");

		return true;
	}

	/**
	 * @param string $localFile Path to local file.
	 * @param string $remoteFile Path to remote file.
	 * @return bool.
	 * @throws \Bitrix\Main\SystemException
	 */
	public function uploadFile($localFile, $remoteFile)
	{
		$stream = fopen("ssh2.sftp://".$this->sftp.$remoteFile, 'w');

		if (!$stream)
			throw new SystemException("Could not open file: $remoteFile");

		$data = file_get_contents($localFile);

		if ($data === false)
			throw new SystemException("Could not open local file: ". $localFile);

		if (fwrite($stream, $data) === false)
			throw new SystemException("Could not write to remote file : ".$remoteFile);

		@fclose($stream);

		return true;
	}

	/**
	 * @param string $remoteFile Path to remote file.
	 * @param string $localFile Path to local file.
	 * @return bool.
	 * @throws \Bitrix\Main\SystemException
	 */
	public function downloadFile($remoteFile, $localFile)
	{
		$stream = @fopen("ssh2.sftp://".$this->sftp.$remoteFile, 'r');

		if (!$stream)
			throw new SystemException("Could not open remote file: ".$remoteFile);

		$contents = stream_get_contents($stream);

		if(file_put_contents($localFile, $contents) === false)
			throw new SystemException("Could not write to local file: ".$localFile);

		@fclose($stream);
		return true;
	}

	/**
	 * @param string $remotePath Remote path.
	 * @return array List of files from remote path.
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getFilesList($remotePath)
	{
		$result = array();
		$dirHandle = opendir("ssh2.sftp://".$this->sftp."/".$remotePath);

		if($dirHandle === false)
			throw new SystemException("Could not open remote path: ".$remotePath);

		while (false !== ($file = readdir($dirHandle)))
			if(is_file("ssh2.sftp://".$this->sftp."/".$remotePath."/".$file))
				$result[] = $file;

		return $result;
	}

	/**
	 * @param $remoteFile Remote path.
	 * @return int Filesize.
	 */
	public function getFileSize($remoteFile)
	{
		return filesize("ssh2.sftp://".$this->sftp.$remoteFile);
	}
} 