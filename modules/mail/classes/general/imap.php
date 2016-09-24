<?php

IncludeModuleLangFile(__FILE__);

class CMailImap
{
	protected $imap_stream;
	protected $counter;

	public function __construct()
	{
		$this->counter = 0;
	}

	public function connect($host, $port, $timeout = 1, $skip_cert = false)
	{
		$skip_cert = PHP_VERSION_ID < 50600 ? true : $skip_cert;

		$imap_stream = @stream_socket_client(
			sprintf('%s:%s', $host, $port), $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT,
			stream_context_create(array('ssl' => array('verify_peer' => !$skip_cert, 'verify_peer_name' => !$skip_cert)))
		);

		if ($imap_stream === false)
			throw new Exception(GetMessage('MAIL_IMAP_ERR_CONNECT'));

		stream_set_timeout($imap_stream, $timeout);
		$this->imap_stream = $imap_stream;

		$prompt = $this->readLine();

		if ($prompt === false)
			throw new Exception(GetMessage('MAIL_IMAP_ERR_COMMUNICATE'));

		if (strpos($prompt, '* OK') !== 0)
		{
			$this->imap_stream = null;

			throw new Exception(GetMessage('MAIL_IMAP_ERR_CONNECT').': '.GetMessage('MAIL_IMAP_ERR_BAD_SERVER'));
		}
/*
		$tag = $this->sendCommand("STARTTLS\r\n");
		$res = $this->readResponse($tag);

		if (strpos($res, $tag.' OK') !== false)
		{
			$a = stream_socket_enable_crypto($this->imap_stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
		}
*/
	}

	public function authenticate($login, $password)
	{
		$tag = $this->sendCommand("AUTHENTICATE PLAIN\r\n");

		$prompt = $this->readLine();
		if (strpos($prompt, '+') !== 0)
		{
			if (strpos($prompt, $tag.' NO') === 0 || strpos($prompt, $tag.' BAD') === 0)
				throw new Exception(GetMessage('MAIL_IMAP_ERR_AUTH_MECH'));
			else
				throw new Exception(GetMessage('MAIL_IMAP_ERR_AUTH').': '.GetMessage('MAIL_IMAP_ERR_BAD_SERVER'));
		}

		fputs($this->imap_stream, base64_encode("\x00".$login."\x00".$password)."\r\n");

		$response = $this->readResponse($tag);

		if (strpos($response, $tag.' OK') === false)
		{
			if (strpos($response, $tag.' NO') === 0 || strpos($response, $tag.' BAD') === 0)
				throw new Exception(GetMessage('MAIL_IMAP_ERR_AUTH'));
			else
				throw new Exception(GetMessage('MAIL_IMAP_ERR_AUTH').': '.GetMessage('MAIL_IMAP_ERR_BAD_SERVER'));
		}
	}

	public function getUnseen()
	{
		$unseen = 0;

		$tag = $this->sendCommand("SELECT \"INBOX\"\r\n");
		$response = $this->readResponse($tag);

		if (strpos($response, $tag.' OK') === false)
			throw new Exception(GetMessage('MAIL_IMAP_ERR_BAD_SERVER'));

		$tag = $this->sendCommand("SEARCH UNSEEN\r\n");
		$response = $this->readResponse($tag);

		if (strpos($response, $tag.' OK') === false)
			throw new Exception(GetMessage('MAIL_IMAP_ERR_BAD_SERVER'));

		if (preg_match('/\* SEARCH( [0-9 ]*)?/i', $response, $matches))
		{
			if (isset($matches[1]))
				$unseen = count(preg_split('/\s+/', $matches[1], null, PREG_SPLIT_NO_EMPTY));
		}

		return $unseen;
	}

	protected function sendCommand($command)
	{
		$this->counter++;

		$tag = sprintf('A%03u', $this->counter);

		$command = sprintf('%s %s', $tag, $command);
		$bytes = fputs($this->imap_stream, $command);

		if ($bytes < strlen($command))
			throw new Exception(GetMessage('MAIL_IMAP_ERR_COMMUNICATE'));

		return $tag;
	}

	protected function readLine()
	{
		$line = '';

		do
		{
			$buffer = fgets($this->imap_stream, 4096);

			if ($buffer === false)
				return false;

			$line .= $buffer;
		}
		while (strpos($buffer, "\r\n") === false);

		return $line;
	}

	protected function readResponse($tag)
	{
		$response = '';

		do
		{
			$line = $this->readLine($this->imap_stream);

			if ($line === false)
				return false;

			$response .= $line;
		}
		while (strpos($line, $tag) !== 0);

		return $response;
	}

}
