<?php

namespace Bitrix\Mail;

use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Imap
{

	const ERR_CONNECT          = 101;
	const ERR_REJECTED         = 102;
	const ERR_COMMUNICATE      = 103;
	const ERR_EMPTY_RESPONSE   = 104;
	const ERR_BAD_SERVER       = 105;

	const ERR_STARTTLS         = 201;
	const ERR_COMMAND_REJECTED = 202;
	const ERR_CAPABILITY       = 203;
	const ERR_AUTH             = 204;
	const ERR_AUTH_MECH        = 205;
	const ERR_LIST             = 206;
	const ERR_SELECT           = 207;
	const ERR_SEARCH           = 208;
	const ERR_FETCH            = 209;
	const ERR_APPEND           = 210;
	const ERR_STORE            = 211;

	protected $stream;

	protected $options = array();

	protected $sessState;
	protected $sessCounter = 0;
	protected $sessCapability;
	protected $sessUntagged = array();
	protected $sessMailbox = array(
		'name'        => null,
		'exists'      => null,
		'uidvalidity' => null,
	);

	protected static $atomRegex    = '[^\x00-\x20\x22\x25\x28-\x2a\x5c\x5d\x7b\x7f-\xff]+';
	protected static $qcharRegex   = '[^\x00\x0a\x0d\x22\x5c\x80-\xff]|\x5c[\x5c\x22]';
	protected static $astringRegex = '[^\x00-\x20\x22\x25\x28-\x2a\x5c\x7b\x7f-\xff]+';


	public function __construct($host, $port, $tls, $strict, $login, $password, $encoding)
	{
		$strict = PHP_VERSION_ID < 50600 ? false : (bool) $strict;

		$this->options = array(
			'host'    => $host,
			'port'    => $port,
			'tls'     => $tls,
			'socket'  => sprintf('%s://%s:%s', ($tls ? 'tls' : 'tcp'), $host, $port),
			'timeout' => \COption::getOptionInt('mail', 'connect_timeout', B_MAIL_TIMEOUT),
			'context' => stream_context_create(array(
				'ssl' => array(
					'verify_peer'      => $strict,
					'verify_peer_name' => $strict
				)
			)),
			'login'    => $login,
			'password' => $password,
			'encoding' => $encoding,
		);
	}

	public function getState()
	{
		return $this->sessState;
	}

	protected function connect(&$error)
	{
		$error = null;

		if ($this->sessState)
			return true;

		$resource = @stream_socket_client(
			$this->options['socket'], $errno, $errstr, $this->options['timeout'],
			STREAM_CLIENT_CONNECT, $this->options['context']
		);

		if ($resource === false)
		{
			$error = static::errorMessage(Imap::ERR_CONNECT, $errno ?: null);
			return false;
		}

		$this->stream = $resource;
		stream_set_timeout($this->stream, $this->options['timeout']);

		$prompt = $this->readLine();

		if ($prompt !== false && preg_match('/^\* (OK|PREAUTH)/i', $prompt, $matches))
		{
			if ($matches[1] == 'OK')
				$this->sessState = 'no_auth';
			elseif ($matches[1] == 'PREAUTH')
				$this->sessState = 'auth';
		}
		else
		{
			fclose($this->stream);
			unset($this->stream);

			if ($prompt === false)
				$error = Imap::ERR_EMPTY_RESPONSE;
			elseif (preg_match('/^\* BYE/i', $prompt))
				$error = Imap::ERR_REJECTED;
			else
				$error = Imap::ERR_BAD_SERVER;

			$error = static::errorMessage(array(Imap::ERR_CONNECT, $error));

			return false;
		}

		if (!$this->capability($error))
			return false;

		if (!$this->options['tls'] && preg_match('/ \x20 STARTTLS ( \x20 | \r\n ) /ix', $this->sessCapability))
		{
			if (!$this->starttls($error))
				return false;
		}

		return true;
	}

	protected function starttls(&$error)
	{
		$error = null;

		if (!$this->sessState)
		{
			$error = static::errorMessage(Imap::ERR_STARTTLS);
			return false;
		}

		$this->executeCommand('STARTTLS', $error);

		if (!$error)
		{
			if (stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT))
			{
				if (!$this->capability($error))
					return false;
			}
			else
			{
				fclose($this->stream);
				unset($this->stream);

				$error = static::errorMessage(Imap::ERR_STARTTLS);

				return false;
			}
		}

		return true;
	}

	protected function capability(&$error)
	{
		$error = null;

		if (!$this->sessState)
		{
			$error = static::errorMessage(Imap::ERR_CAPABILITY);
			return false;
		}

		$response = $this->executeCommand('CAPABILITY', $error);

		if ($error)
		{
			$error = $error == Imap::ERR_COMMAND_REJECTED ? null : $error;
			$error = static::errorMessage(array(Imap::ERR_CAPABILITY, $error), $response);

			return false;
		}

		$regex = '/^ \* \x20 CAPABILITY /ix';
		foreach ($this->getUntagged($regex, true) as $item)
			$this->sessCapability = $item[0];

		return true;
	}

	protected function authenticate(&$error)
	{
		$error = null;

		if (!$this->connect($error))
			return false;
		if (in_array($this->sessState, array('auth', 'select')))
			return true;

		$mech = false;
		if (preg_match('/ \x20 AUTH=PLAIN ( \x20 | \r\n ) /ix', $this->sessCapability))
			$mech = 'plain';
		elseif (!preg_match('/ \x20 LOGINDISABLED ( \x20 | \r\n ) /ix', $this->sessCapability))
			$mech = 'login';

		if (!$mech)
		{
			$error = static::errorMessage(array(Imap::ERR_AUTH, Imap::ERR_AUTH_MECH));
			return false;
		}

		if ($mech == 'plain')
		{
			$response = $this->executeCommand('AUTHENTICATE PLAIN', $error);

			if (strpos($response, '+') !== 0)
			{
				$error = $error == Imap::ERR_COMMAND_REJECTED ? Imap::ERR_AUTH_MECH : $error;
				$error = static::errorMessage(array(Imap::ERR_AUTH, $error), $response);

				return false;
			}

			$response = $this->exchange(base64_encode(sprintf(
				"\x00%s\x00%s", $this->options['login'], $this->options['password']
			)), $error);
		}
		else // if ($mech == 'login')
		{
			$response = $this->executeCommand(sprintf(
				'LOGIN %s %s',
				static::prepareString($this->options['login']),
				static::prepareString($this->options['password'])
			), $error);
		}

		if ($error)
		{
			$error = $error == Imap::ERR_COMMAND_REJECTED ? null : $error;
			$error = static::errorMessage(array(Imap::ERR_AUTH, $error), $response);

			return false;
		}

		$this->sessState = 'auth';

		if (!$this->capability($error))
			return false;

		return true;
	}

	protected function select($mailbox, &$error)
	{
		$error = null;

		if (!$this->authenticate($error))
			return false;
		if ($this->sessState == 'select' && $mailbox == $this->sessMailbox['name'])
			return true;

		$response = $this->executeCommand(sprintf(
			'SELECT "%s"', $this->encodeUtf7Imap($mailbox)
		), $error);

		if ($error)
		{
			$error = $error == Imap::ERR_COMMAND_REJECTED ? null : $error;
			$error = static::errorMessage(array(Imap::ERR_SELECT, $error), $response);

			return false;
		}

		$this->sessState = 'select';
		$this->sessMailbox['name'] = $mailbox;

		$regex = '/^ \* \x20 ( \d+ ) \x20 EXISTS /ix';
		foreach ($this->getUntagged($regex, true) as $item)
			$this->sessMailbox['exists'] = $item[1][1];

		$regex = '/^ \* \x20 OK \x20 \[ UIDVALIDITY \x20 ( \d+ ) \] /ix';
		foreach ($this->getUntagged($regex, true) as $item)
			$this->sessMailbox['uidvalidity'] = $item[1][1];

		if (!$this->capability($error))
			return false;

		return true;
	}

	/**
	 * Connects to server and authenticate client
	 *
	 * @param string &$error Error message.
	 * @return boolean
	 */
	public function singin(&$error)
	{
		$error = null;

		return $this->authenticate($error);
	}

	/**
	 * Returns unseen messages count
	 *
	 * @param string $mailbox Mailbox name.
	 * @param string &$error Error message.
	 * @return int|false
	 */
	public function getUnseen($mailbox, &$error)
	{
		$error = null;

		if (!$this->select($mailbox, $error))
			return false;

		$unseen = 0;

		$response = $this->executeCommand('SEARCH UNSEEN', $error);

		if ($error)
		{
			$error = $error == Imap::ERR_COMMAND_REJECTED ? null : $error;
			$error = static::errorMessage(array(Imap::ERR_SEARCH, $error), $response);

			return false;
		}

		$regex = '/^ \* \x20 SEARCH ( (?: \x20 \d+ )* ) /ix';
		foreach ($this->getUntagged($regex, true) as $item)
			$unseen = preg_match_all('/\d+/', $item[1][1], $dummy);

		return $unseen;
	}

	/**
	 * Returns mailboxes list
	 *
	 * @param string $mailbox Mailbox name pattern.
	 * @param string &$error Error message.
	 * @return array|false
	 */
	public function listMailboxes($mailbox, &$error)
	{
		$error = null;

		if (!$this->authenticate($error))
			return false;

		$mailbox = str_replace('*', '%', $mailbox, $recursive);

		$response = $this->executeCommand(sprintf(
			'LIST "" "%s"', $this->encodeUtf7Imap($mailbox)
		), $error);

		if ($error)
		{
			$error = $error == Imap::ERR_COMMAND_REJECTED ? null : $error;
			$error = static::errorMessage(array(Imap::ERR_LIST, $error), $response);

			return false;
		}

		$list = array();

		$regex = sprintf(
			'/^ \* \x20 LIST \x20
				\( (?<flags> ( \x5c? %1$s ( \x20 \x5c? %1$s )* )? ) \) \x20
				(?<delim> NIL | " ( %2$s ) " ) \x20
				(?<name> \{ \d+ \} | " ( %2$s )* " | %3$s ) \r\n
			/ix',
			self::$atomRegex, self::$qcharRegex, self::$astringRegex
		);
		foreach ($this->getUntagged($regex, true) as $item)
		{
			list($item, $matches) = $item;

			$sflags = $matches['flags'];
			$sdelim = $matches['delim'];
			$sname  = $matches['name'];

			if (preg_match('/^ " ( .+ ) " $/ix', $sdelim, $quoted))
				$sdelim = static::unescapeQuoted($quoted[1]);

			if (preg_match('/^ \{ ( \d+ ) \} $/ix', $sname, $literal))
				$sname = \CUtil::binSubstr($item, \CUtil::binStrlen($matches[0]), $literal[1]);
			elseif (preg_match('/^ " ( .* ) " $/ix', $sname, $quoted))
				$sname = static::unescapeQuoted($quoted[1]);

			$list[] = array(
				'flags' => $sflags,
				'delim' => strtoupper($sdelim) == 'NIL' ? null : $sdelim,
				'name'  => $this->decodeUtf7Imap($sname),
			);
		}

		if ($recursive > 0)
		{
			foreach ($list as $i => $item)
			{
				if (!preg_match('/ ( ^ | \x20 ) \x5c ( Noinferiors | HasNoChildren ) ( \x20 | $ ) /ix', $item['flags']))
				{
					$children = $this->listMailboxes(sprintf('%s%s*', $item['name'], $item['delim']), $error);

					if ($children === false)
						return false;

					if (!empty($children))
						$list[$i]['children'] = $children;
				}
			}
		}

		return $list;
	}

	public function listMessages($mailbox, &$uidtoken, &$error)
	{
		$error = null;

		if (!$this->select($mailbox, $error))
			return false;

		$uidtoken = $this->sessMailbox['uidvalidity'];

		$list = array();

		if ($this->sessMailbox['exists'] > 0)
		{
			$response = $this->executeCommand(
				$this->sessMailbox['uidvalidity'] > 0
					? 'FETCH 1:* (UID INTERNALDATE RFC822.SIZE FLAGS)'
					: 'FETCH 1:* (INTERNALDATE RFC822.SIZE FLAGS)',
				$error
			);

			if ($error)
			{
				$error = $error == Imap::ERR_COMMAND_REJECTED ? null : $error;
				$error = static::errorMessage(array(Imap::ERR_FETCH, $error), $response);

				return false;
			}

			$regex = '/^ \* \x20 (?<id> \d+ ) \x20 FETCH \x20 \( (?<data> .+ ) \) \r\n $/isx';
			foreach ($this->getUntagged($regex, true) as $item)
			{
				$data = array(
					'id'   => $item[1]['id'],
					'uid'  => null,
					'date' => null,
					'size' => null,
				);

				$regex = '/ ( ^ | \x20 ) UID \x20 (?<uid> \d+ ) ( \x20 | $ ) /ix';
				if (preg_match($regex, $item[1]['data'], $matches))
					$data['uid'] = $matches['uid'];

				$regex = sprintf(
					'/ ( ^ | \x20 ) INTERNALDATE \x20 " (?<date> ( %s )+ ) " ( \x20 | $ ) /ix',
					self::$qcharRegex
				);
				if (preg_match($regex, $item[1]['data'], $matches))
					$data['date'] = $matches['date'];

				$regex = '/ ( ^ | \x20 ) RFC822\.SIZE \x20 (?<size> \d+ ) ( \x20 | $ ) /ix';
				if (preg_match($regex, $item[1]['data'], $matches))
					$data['size'] = $matches['size'];

				$regex = sprintf(
					'/ ( ^ | \x20 ) FLAGS \x20 \( (?<flags> ( \x5c? %1$s ( \x20 \x5c? %1$s )* )? ) \) ( \x20 | $ ) /ix',
					self::$atomRegex
				);
				if (preg_match($regex, $item[1]['data'], $matches))
					$data['flags'] = $matches['flags'];

				$list[] = $data;
			}
		}

		return $list;
	}

	/**
	 * Adds message
	 *
	 * @param string $mailbox Mailbox name.
	 * @param string $data Message.
	 * @param string &$error Error message.
	 * @return string|false
	 */
	public function addMessage($mailbox, $data, &$error)
	{
		$error = null;

		if (!$this->authenticate($error))
			return false;

		$response = $this->executeCommand(sprintf(
			'APPEND "%s" (\Seen) %s',
			$this->encodeUtf7Imap($mailbox),
			static::prepareString($data)
		), $error);

		if ($error)
		{
			$error = $error == Imap::ERR_COMMAND_REJECTED ? null : $error;
			$error = static::errorMessage(array(Imap::ERR_APPEND, $error), $response);

			return false;
		}

		$regex = sprintf('/^ OK \x20 \[ APPENDUID \x20 ( \d+ ) \x20 ( \d+ ) \] /ix', $this->getTag());
		if (preg_match($regex, $response, $matches))
			return sprintf('%u:%u', $matches[1], $matches[2]);

		return true;
	}

	public function updateMessageFlags($mailbox, $id, $flags, &$error)
	{
		$error = null;

		if (!$this->select($mailbox, $error))
			return false;

		$addFlags = array();
		$delFlags = array();
		foreach ($flags as $name => $value)
		{
			if (preg_match(sprintf('/ ^ \x5c? %s $ /ix', self::$atomRegex), $name))
			{
				if ($value)
					$addFlags[] = $name;
				else
					$delFlags[] = $name;
			}
		}

		if ($addFlags)
			$response = $this->executeCommand(sprintf('STORE %u +FLAGS (%s)', $id, join(' ', $addFlags)), $error);

		if (!$error && $delFlags)
			$response = $this->executeCommand(sprintf('STORE %u -FLAGS (%s)', $id, join(' ', $delFlags)), $error);

		if ($error)
		{
			$error = $error == Imap::ERR_COMMAND_REJECTED ? null : $error;
			$error = static::errorMessage(array(Imap::ERR_STORE, $error), $response);

			return false;
		}

		$this->getUntagged(sprintf('/^ \* \x20 %u \x20 FETCH \x20 \( .+ \) \r\n $/isx', $id), true);

		return true;
	}

	/**
	 * Returns message
	 *
	 * @param string $mailbox Mailbox name.
	 * @param int $id Message ID.
	 * @param string $section Message section.
	 * @param string &$error Error message.
	 * @return string|false
	 */
	public function getMessage($mailbox, $id, $section, &$error)
	{
		$error = null;

		if (!$this->select($mailbox, $error))
			return false;

		if (!in_array(strtoupper($section), array('HEADER', 'TEXT')))
			$section = '';

		$response = $this->executeCommand(sprintf('FETCH %u (BODY.PEEK[%s])', $id, $section), $error);

		if ($error)
		{
			$error = $error == Imap::ERR_COMMAND_REJECTED ? null : $error;
			$error = static::errorMessage(array(Imap::ERR_FETCH, $error), $response);

			return false;
		}

		$sbody = false;
		$regex = sprintf('/^ \* \x20 %u \x20 FETCH \x20 \( (?<data> .+ ) \) \r\n $/isx', $id);
		foreach ($this->getUntagged($regex, true) as $item)
		{
			$data  = $item[1]['data'];
			$sbody = false;

			$regex = sprintf('/ ( ^ | \x20 ) BODY \[%s\] \x20 NIL ( \x20 | $ ) /ix', $section);
			if (preg_match($regex, $data))
			{
				$sbody = null;
				continue;
			}

			$regex = sprintf(
				'/ ( ^ | \x20 ) BODY \[%s\] \x20 " (?<body> ( %s )* ) " ( \x20 | $ ) /ix',
				$section, self::$qcharRegex
			);
			if (preg_match($regex, $data, $quoted))
			{
				$sbody = static::unescapeQuoted($quoted['body']);
				continue;
			}

			$regex = sprintf('/ ( ^ | \x20 ) BODY \[%s\] \x20  \{ (?<size> \d+ ) \} \r\n /ix', $section);
			if (preg_match($regex, $data, $literal, PREG_OFFSET_CAPTURE))
			{
				$sbody = \CUtil::binSubstr($data, $literal[0][1]+\CUtil::binStrlen($literal[0][0]), $literal['size'][0]);
				continue;
			}
		}

		return $sbody;
	}

	protected function getUntagged($regex, $unset = false)
	{
		$result = array();

		for ($i = 0; $i < count($this->sessUntagged); $i++)
		{
			if (!preg_match($regex, $this->sessUntagged[$i], $matches))
				continue;

			$result[] = array($this->sessUntagged[$i], $matches);

			if ($unset)
			{
				array_splice($this->sessUntagged, $i, 1);
				$i--;
			}
		}

		return $result;
	}

	protected function getTag($next = false)
	{
		if ($next)
			$this->sessCounter++;

		return sprintf('A%03u', $this->sessCounter);
	}

	protected function executeCommand($command, &$error)
	{
		$error = null;

		$chunks = explode("\x00", sprintf('%s %s', $this->getTag(true), $command));

		$k = count($chunks);
		foreach ($chunks as $chunk)
		{
			$k--;

			$response = $this->exchange($chunk, $error);

			if ($k > 0 && strpos($response, '+') !== 0)
				break;
		}

		return $response;
	}

	protected function exchange($data, &$error)
	{
		$error = null;

		if ($this->sendData(sprintf("%s\r\n", $data)) === false)
		{
			$error = Imap::ERR_COMMUNICATE;
			return false;
		}

		$response = $this->readResponse();

		if ($response === false)
		{
			$error = Imap::ERR_EMPTY_RESPONSE;
			return false;
		}

		$response = trim($response);

		if (!preg_match(sprintf('/^ %s \x20 OK /ix', $this->getTag()), $response))
		{
			if (preg_match(sprintf('/^ %s \x20 ( NO | BAD ) /ix', $this->getTag()), $response))
				$error = Imap::ERR_COMMAND_REJECTED;
			else
				$error = Imap::ERR_BAD_SERVER;
		}

		return preg_replace(sprintf('/^ %s \x20 /ix', $this->getTag()), '', $response);
		return $response;
	}

	protected function sendData($data)
	{
//echo '> ', $data;
		$bytes = fputs($this->stream, $data);

		if ($bytes < strlen($data))
			return false;

		return true;
	}

	protected function readBytes($bytes)
	{
		$data = '';

		while ($bytes > 0)
		{
			$buffer = fread($this->stream, $bytes);

			if ($buffer === false)
				return false;

			$data  .= $buffer;
			$bytes -= \CUtil::binStrlen($buffer);
		}

		return $data;
	}

	protected function readLine()
	{
		$line = '';

		do
		{
			$buffer = fgets($this->stream, 4096);

			if ($buffer === false)
				return false;

			$line .= $buffer;

			if ($literal = preg_match('/\{(?<bytes>\d+)\}\r\n$/', $line, $matches))
			{
				$data = $this->readBytes($matches['bytes']);

				if ($data === false)
					return false;

				$line .= $data;
			}
		}
		while ($literal || !preg_match('/\r\n$/', $line));

//echo '< ', $line;
		return $line;
	}

	protected function readResponse()
	{
		do
		{
			$line = $this->readLine();

			if ($line === false)
				return false;

			if (strpos($line, '*') === 0)
				$this->sessUntagged[] = $line;
		}
		while (strpos($line, '*') === 0);

		return $line;
	}

	protected static function prepareString($data)
	{
		if (preg_match('/^[^\x00\x0a\x0d\x80-\xff]*$/', $data))
			return sprintf('"%s"', static::escapeQuoted($data));
		else
			return sprintf("{%u}\x00%s", \CUtil::binStrlen($data), $data);
	}

	protected static function escapeQuoted($data)
	{
		return str_replace(array('\\', '"'), array('\\\\', '\\"'), $data);
	}

	protected static function unescapeQuoted($data)
	{
		return str_replace(array('\\\\', '\\"'), array('\\', '"'), $data);
	}

	protected function encodeUtf7Imap($data)
	{
		if (!$data)
			return $data;

		$result = Encoding::convertEncoding($data, $this->options['encoding'], 'UTF7-IMAP');

		if ($result === false)
		{
			$result = $data;

			$result = Encoding::convertEncoding($result, $this->options['encoding'], 'UTF-8');
			$result = str_replace('&', '&-', $result);

			$result = preg_replace_callback('/[\x00-\x1f\x7f-\xff]+/', function($matches)
			{
				$result = $matches[0];

				$result = Encoding::convertEncoding($result, 'UTF-8', 'UTF-16BE');
				$result = base64_encode($result);
				$result = str_replace('/', ',', $result);
				$result = str_replace('=', '', $result);
				$result = '&' . $result . '-';

				return $result;
			}, $result);
		}

		return $result;
	}

	protected function decodeUtf7Imap($data)
	{
		if (!$data)
			return $data;

		$result = Encoding::convertEncoding($data, 'UTF7-IMAP', $this->options['encoding']);

		if ($result === false)
		{
			$result = $data;

			$result = preg_replace_callback('/&([\x2b\x2c\x30-\x39\x41-\x5a\x61-\x7a]+)-/', function($matches)
			{
				$result = $matches[1];

				$result = str_replace(',', '/', $result);
				$result = base64_decode($result);
				$result = Encoding::convertEncoding($result, 'UTF-16BE', 'UTF-8');

				return $result;
			}, $result);

			$result = str_replace('&-', '&', $result);
			$result = Encoding::convertEncoding($result, 'UTF-8', $this->options['encoding']);
		}

		return $result;
	}

	protected static function errorMessage($errors, $details = null)
	{
		$errors  = array_filter((array) $errors);
		$details = array_filter((array) $details);

		foreach ($errors as $i => $error)
			$errors[$i] = static::decodeError($error);

		$error = join(': ', $errors);
		if ($details)
			$error .= sprintf(' (%s)', join(': ', $details));

		return $error;
	}

	/**
	 * Returns error message
	 *
	 * @param int $code Error code.
	 * @return string
	 */
	public static function decodeError($code)
	{
		switch ($code)
		{
			case self::ERR_CONNECT:
				return Loc::getMessage('MAIL_IMAP_ERR_CONNECT');
			case self::ERR_REJECTED:
				return Loc::getMessage('MAIL_IMAP_ERR_REJECTED');
			case self::ERR_COMMUNICATE:
				return Loc::getMessage('MAIL_IMAP_ERR_COMMUNICATE');
			case self::ERR_EMPTY_RESPONSE:
				return Loc::getMessage('MAIL_IMAP_ERR_EMPTY_RESPONSE');
			case self::ERR_BAD_SERVER:
				return Loc::getMessage('MAIL_IMAP_ERR_BAD_SERVER');
			case self::ERR_STARTTLS:
				return Loc::getMessage('MAIL_IMAP_ERR_STARTTLS');
			case self::ERR_COMMAND_REJECTED:
				return Loc::getMessage('MAIL_IMAP_ERR_COMMAND_REJECTED');
			case self::ERR_CAPABILITY:
				return Loc::getMessage('MAIL_IMAP_ERR_CAPABILITY');
			case self::ERR_AUTH:
				return Loc::getMessage('MAIL_IMAP_ERR_AUTH');
			case self::ERR_AUTH_MECH:
				return Loc::getMessage('MAIL_IMAP_ERR_AUTH_MECH');
			case self::ERR_LIST:
				return Loc::getMessage('MAIL_IMAP_ERR_LIST');
			case self::ERR_SELECT:
				return Loc::getMessage('MAIL_IMAP_ERR_SELECT');
			case self::ERR_SEARCH:
				return Loc::getMessage('MAIL_IMAP_ERR_SEARCH');
			case self::ERR_FETCH:
				return Loc::getMessage('MAIL_IMAP_ERR_FETCH');
			case self::ERR_APPEND:
				return Loc::getMessage('MAIL_IMAP_ERR_APPEND');
			case self::ERR_STORE:
				return Loc::getMessage('MAIL_IMAP_ERR_STORE');

			default:
				return Loc::getMessage('MAIL_IMAP_ERR_DEFAULT');
		}
	}

}
