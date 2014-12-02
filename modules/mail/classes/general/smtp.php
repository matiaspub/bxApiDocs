<?
class CSMTPServer
{
	var $arServers = Array();

	var $logFile;
	var $logFileName = "/bitrix/modules/smtpd.log";
	var $logLevel = 10;
	var $logMaxSize = 2000000;
	var $startPeriodTimeTruncate;
	var $startTime;

	public function WriteToLog($txt, $level)
	{
		$this->logLevel = IntVal(COption::GetOptionString("mail", "smtp_log_level", "4"));

		if ($this->logLevel < $level)
			return;

		if (MicroTime(true) - $this->startPeriodTimeTruncate > 600)
		{
			if ($this->logFile)
				FClose($this->logFile);

			$this->logFile = null;

			if (File_Exists($_SERVER["DOCUMENT_ROOT"].$this->logFileName))
			{
				$logSize = @FileSize($_SERVER["DOCUMENT_ROOT"].$this->logFileName);
				$logSize = IntVal($logSize);

				if ($logSize > $this->logMaxSize)
				{
					if (($fp = @FOpen($_SERVER["DOCUMENT_ROOT"].$this->logFileName, "rb"))
						&& ($fp1 = @FOpen($_SERVER["DOCUMENT_ROOT"].$this->logFileName."_", "wb")))
					{
						$iSeekLen = IntVal($logSize - $this->logMaxSize / 2.0);
						FSeek($fp, $iSeekLen);

						@FWrite($fp1, "Truncated ".Date("Y-m-d H:i:s")."\n---------------------------------\n");
						do
						{
							$data = FRead($fp, 8192);
							if (StrLen($data) == 0)
								break;

							@FWrite($fp1, $data);
						}
						while (true);

						@FClose($fp);
						@FClose($fp1);

						@Copy($_SERVER["DOCUMENT_ROOT"].$this->logFileName."_", $_SERVER["DOCUMENT_ROOT"].$this->logFileName);
						@UnLink($_SERVER["DOCUMENT_ROOT"].$this->logFileName."_");
					}
				}
				ClearStatCache();
			}

			$this->startPeriodTimeTruncate = MicroTime(true);
		}

		if (!$this->logFile || $this->logFile == null)
			$this->logFile = FOpen($_SERVER["DOCUMENT_ROOT"].$this->logFileName, "a");

		if (!$this->logFile)
		{
			echo "Can't write to log\n---------------------------------\n";
			return;
		}

		FWrite($this->logFile, Date("Y-m-d H:i:s")."\t".trim($txt)."\n");
		FFlush($this->logFile);

		//if ($level > 4)
			echo trim($txt)."\n---------------------------------\n";
	}

	public static function Run()
	{
		$var = new CSMTPServer();
		$var->startTime = time();
		$var->Start();
		$var->Listen();
	}

	public function Start()
	{
		global $CACHE_MANAGER;
		$CACHE_MANAGER->Clean("smtpd_stop");
		$CACHE_MANAGER->Clean("smtpd_reload");

		ini_set('max_execution_time', 0);
		set_time_limit(0);
		ob_implicit_flush(true);

		while(@ob_end_clean());

		$dbr = CMailBox::GetList(array(), array("ACTIVE"=>"Y", "SERVER_TYPE"=>"smtp"));
		while($arr = $dbr->Fetch())
		{
			$server = new CSMTPServerHost($this, $arr);
			$server->Start();
			$this->arServers[] = $server;
		}
	}

	public function ReloadServers()
	{
		global $BX_MAIL_FILTER_CACHE;
		$BX_MAIL_FILTER_CACHE = Array();

		$rnd = uniqid();
		$dbr = CMailBox::GetList(array(), array("ACTIVE"=>"Y", "SERVER_TYPE"=>"smtp"));
		$arFounded = Array();
		while($arr = $dbr->Fetch())
		{
			$bFound = false;
			foreach($this->arServers as $server)
			{
				if(
					$server->arFields["PORT"] == $arr["PORT"]
					&& $server->arFields["SERVER"] == ($arr["SERVER"]=="*"?"0.0.0.0":$arr["SERVER"])
					)
					{
						$server->arFields = $arr;
						$server->rnd = $rnd;
						$bFound = true;
						break;
					}
			}

			if(!$bFound)
			{
				$server = new CSMTPServerHost($this, $arr);
				$server->rnd = $rnd;
				$server->Start();
				$this->arServers[] = $server;
			}
		}

		$arServers = $this->arServers;
		foreach($arServers as $k=>$server)
		{
			if($server->rnd!=$rnd)
				$server->Stop($k);
		}
	}

	public function Listen()
	{
		global $DB, $CACHE_MANAGER;
		$cnt = 100;
		while (true)
		{
			$cnt++;
			if($cnt>5)
			{
				$cnt = 0;
				$stats = Array(
						'started'=>$this->startTime,
						'uptime'=>time() - $this->startTime,
						'messages'=>0,
						'connections'=>0,
						'connections_now'=>0,
						'servers'=>Array()
						);

				foreach($this->arServers as $arServer)
				{
					$stats["servers"][] = Array(
							'id'=>$arServer->arFields["ID"],
							'server'=>$arServer->arFields["SERVER"],
							'port'=>$arServer->arFields["PORT"],
							'started'=>$arServer->startTime
						);
					$stats["messages"] += $arServer->msgCount;
					$stats["connections"] += $arServer->conCount;
					$stats["connections_now"] += count($arServer->arClients);
				}

				$CACHE_MANAGER->Read(33, "smtpd_stats");
				$CACHE_MANAGER->SetImmediate("smtpd_stats", $stats);

				if($CACHE_MANAGER->Read(3600000, "smtpd_reload"))
					$this->ReloadServers();
				$CACHE_MANAGER->Clean("smtpd_reload");

				$bStop = $CACHE_MANAGER->Read(3600000, "smtpd_stop");
				$CACHE_MANAGER->Clean("smtpd_stop");

				if($bStop)
				{
					$CACHE_MANAGER->Clean("smtpd_stats");
					return;
				}

				$DB->Query("SELECT 'x' FROM b_user WHERE 1=0"); // nop
			}

			$arReadSockets = Array();

			foreach($this->arServers as $server)
				$arReadSockets = array_merge($arReadSockets, $server->GetSockets());

			if(count($arReadSockets)<=0)
				sleep(1);
			else
			{
				$n = @stream_select($arReadSockets, $w = null, $e = null, 3);
				if($n > 0)
				{
					foreach($arReadSockets as $r)
					{
						if(($server = $this->FindServerSocket($r))!==false)
						{
							$server->AddConnection();
						}
						else
						{
							if(($conn = $this->FindServerConnection($r))!==false)
							{
								$conn->Receive();
							}
						}
					}
				}
			}

			$arServers = $this->arServers;
			foreach($arServers as $server)
				$server->CheckTimeout(600);
		}
	}

	public function FindServerSocket($s)
	{
		$arServers = $this->arServers;
		foreach($arServers as $server)
			if($s == $server->sockServer)
				return $server;

		return false;
	}

	public function FindServerConnection($s)
	{
		$arServers = $this->arServers;
		foreach($arServers as $server)
			if(($conn = $server->FindConnection($s))!==false)
				return $conn;
		return false;
	}

	public function Stop()
	{
		if ($this->logFile)
			FClose($this->logFile);
	}

	public function RemoveHost($i)
	{
		unset($this->arServers[$i]);
	}
}

class CSMTPServerHost
{
	var $sockServer;
	var $server;
	var $initialized;

	var $arClients = array();
	var $arClientsIndex = array();
	var $lastClientId;

	var $arSockets = array();

	var $startPeriodTime;
	var $arFields = Array();
	var $msgCount = 0;
	var $conCount = 0;

	public function FindConnection($s)
	{
		$id = array_search($s, $this->arSockets);
		if($id !== false)
			return $this->arClients[$id];
		return false;
	}

	public function GetSockets()
	{
		if($this->sockServer)
			return array_merge(array($this->sockServer), $this->arSockets);

		return array();
	}

	public function CSMTPServerHost($server, $arFields)
	{
		$this->server = $server;
		$this->arFields = $arFields;

		$this->arClients = array();
		$this->arClientsIndex = array();
		$this->lastClientId = -1;
	}

	public function AddConnection()
	{
		if(Is_Resource($sock = stream_socket_accept($this->sockServer, 0, $ip)))
		{
			$this->lastClientId++;
			$id = $this->lastClientId;

			$this->WriteToLog("Client connected (".$id.", ".$ip.", ".$sock.")", 5);

			stream_set_timeout($sock, 5);
			$this->arClients[$id] = new CSMTPConnection($id, $sock, $this);
			$this->arClients[$id]->ip = $ip;

			$this->arSockets[$id] = $sock;
			$this->conCount++;

			return true;
		}
		return false;
	}

	public function RemoveConnection($id)
	{
		$this->WriteToLog("Connection removed (".$id.", ".$this->arClients[$id]->ip.", ".$this->arClients[$id]->sock.")", 3);
		unset($this->arClients[$id]);
		unset($this->arSockets[$id]);
		if($this->_stopAfterDisconnect && count($this->arClients)<=0)
			$this->_Stop();
	}

	public function WriteToLog($txt, $level)
	{
		$this->server->WriteToLog($txt, $level);
	}

	public function Start()
	{
		$this->startPeriodTime = microtime(true);
		$this->startPeriodTimeTruncate = microtime(true);

		$this->sockServer = stream_socket_server("tcp://".($this->arFields["SERVER"]=="*" ? "0.0.0.0" : $this->arFields["SERVER"]).":".$this->arFields["PORT"], $errno, $errstr);

		if (!$this->sockServer)
		{
			$this->WriteToLog("Create socket error: $errstr ($errno)", 1);
			return false;
		}

		$this->WriteToLog("Server #".$this->arFields["ID"]." started: ".($this->arFields["SERVER"]=="*"?"0.0.0.0":$this->arFields["SERVER"]).":".$this->arFields["PORT"], 1);
		return true;
	}

	public function Stop($num)
	{
		$this->num = $num;
		if(count($this->arClients)<=0)
			$this->_Stop();
		else
			$this->_stopAfterDisconnect = true;
	}

	public function _Stop()
	{
		if($this->sockServer)
		{
			@FClose($this->sockServer);
			$this->WriteToLog("Server #".$this->arFields["ID"]." stopped: ".($this->arFields["SERVER"]=="*"?"0.0.0.0":$this->arFields["SERVER"]).":".$this->arFields["PORT"], 1);
		}

		$this->server->RemoveHost($this->num);
	}

	public function CheckTimeout($timeout)
	{
		$arConns = $this->arClients;
		foreach($arConns as $k=>$c)
			if(time() - $c->lastRecieve > $timeout)
				$this->RemoveConnection($k);
	}
}


class CSMTPConnection
{
	var $id;
	var $sock;
	var $connected = false;
	var $authenticated = false;
	var $readBuffer = "";
	var $__listenFunc = false;
	var $arMsg = Array();
	var $server;
	var $lastRecieve;
	var $auth_user_id = 0;
	var $msgCount = 0;

	public function CSMTPConnection($id, $sock, $serv)
	{
		$this->id = $id;
		$this->sock = $sock;
		$this->connected = true;
		$this->authenticated = false;
		$this->server = $serv;
		$this->lastRecieve = time();
		$this->uid = md5(uniqid());
		$this->arMsg = array('LOCAL_ID'=>md5(uniqid()));
		$this->Send('220');
	}

	public function WriteToLog($txt, $level)
	{
		$this->server->WriteToLog($txt." (C:".$this->uid.")", $level);
	}

	public function Receive()
	{
		$this->readBuffer .= FRead($this->sock, 8192);
		$this->WriteToLog("C<- (".$this->id.")\t".$this->readBuffer, 10);
		$res = $this->__ParseBuffer();
		$this->lastRecieve = time();

		if($this->sock && feof($this->sock))
			$this->Disconnect();

		return $res;
	}

	public function __ParseBuffer()
	{
		if(StrLen($this->readBuffer) <= 0)
			return false;

		if($this->__listenFunc == '__AuthLoginHandler')
			return $this->__AuthLoginHandler();

		if($this->__listenFunc == '__AuthPlainHandler')
			return $this->__AuthPlainHandler();

		if($this->__listenFunc == '__DataHandler')
			return $this->__DataHandler();

		if(strpos($this->readBuffer, "\r\n")===false)
			return false;

		$this->readBuffer = Trim($this->readBuffer);

		$res = false;
		if(($p = strpos($this->readBuffer, " "))!==false)
		{
			$command = substr($this->readBuffer, 0, $p);
			$res = $this->__ProcessCommand($command, substr($this->readBuffer, $p+1));
		}
		else
		{
			$res = $this->__ProcessCommand($this->readBuffer);
		}

		if($res)
			$this->readBuffer = "";

		return true;
	}

	public function Send($code, $text = "")
	{
		if (!$this->connected)
			return false;

		if (intval($code) <= 0)
			return false;

		if($text=='')
		{
			$results = Array(
				'211'=>'System status, or system help reply',
				'214'=>'Help message', //[Information on how to use the receiver or the meaning of a particular non-standard command; this reply is useful only to the human user]
				'220'=>'<domain> Service ready',
				'221'=>'<domain> Service closing transmission channel',
				'250'=>'Requested mail action okay, completed',
				'251'=>'User not local; will forward to <forward-path>',
				'354'=>'Start mail input; end with <CRLF>.<CRLF>',
				'421'=>'<domain> Service not available,', //closing transmission channel [This may be a reply to any command if the service knows it must shut down]
				'450'=>'Requested mail action not taken: mailbox unavailable', //[E.g., mailbox busy]
				'451'=>'Requested action aborted: local error in processing',
				'452'=>'Requested action not taken: insufficient system storage',
				'500'=>'Syntax error, command unrecognized', //[This may include errors such as command line too long]
				'501'=>'Syntax error in parameters or arguments',
				'502'=>'Command not implemented',
				'503'=>'Bad sequence of commands',
				'504'=>'Command parameter not implemented',
				'550'=>'Requested action not taken: mailbox unavailable', //[E.g., mailbox not found, no access]
				'551'=>'User not local; please try <forward-path>',
				'552'=>'Requested mail action aborted: exceeded storage allocation',
				'553'=>'Requested action not taken: mailbox name not allowed', //[E.g., mailbox syntax incorrect]
				'554'=>'Transaction failed',
				);
			$text = $results[$code];
		}

		return $this->__Send($code." ".$text."\r\n");
	}

	public function __Send($message)
	{
		if (StrLen($message) <= 0)
			return false;

		$this->WriteToLog("S-> (".$this->id.")\t".$message, 10);

		$r = FWrite($this->sock, $message);

		return ($r !== false);
	}

	public function Disconnect()
	{
		@FClose($this->sock);
		$this->sock = false;

		$this->WriteToLog("Client disconnected (".$this->id.", ".$this->ip.")", 5);
		$this->server->RemoveConnection($this->id);
	}

	public function CheckRelaying($email)
	{
		$domains = preg_split('/[\s]+/', strtolower($this->server->arFields['DOMAINS']), -1, PREG_SPLIT_NO_EMPTY);
		if(count($domains)<=0)
			return true;

		if(!is_array($this->arMsg["FOR_RELAY"]))
			$this->arMsg["FOR_RELAY"] = array();

		$p = strpos($email, "@");
		$email_domain = substr($email, $p+1);

		if(in_array($email_domain, $domains))
		{
			$this->WriteToLog('['.$this->arMsg["LOCAL_ID"].'] Accepted for relaying '.$email, 8);
			return true;
		}

		if($this->server->arFields['RELAY']!='Y')
			return false;

		if($this->server->arFields['AUTH_RELAY']=='Y' && $this->auth_user_id<=0)
			return false;

		$this->WriteToLog('['.$this->arMsg["LOCAL_ID"].'] Accepted for relaying '.$email, 8);
		$this->arMsg["FOR_RELAY"][]	= $email;
		return true;
	}

	//обработчик команд
public 	function __ProcessCommand($command, $arg = '')
	{
		switch(strtoupper($command))
		{
		case "HELO":
			$this->Send('250', 'domain name should be qualified');
			if(trim($arg)=='')
				$this->host = $this->ip;
			else
				$this->host = $arg;
			//500, 501, 504, 421
			break;
		case "SEND":
		case "SOML":
		case "SAML":
		case "MAIL":
			if(!preg_match('#FROM[ ]*:[ ]*(.+)#i', $arg, $arMatches))
				$this->Send('501', 'Unrecognized parameter '.$arg);
			elseif($this->arMsg["FROM"])
					$this->Send('503', 'Sender already specified');
			else
			{
				$email = $arMatches[1];
				$email = CMailUtil::ExtractMailAddress($email);
				if($email=='' || !check_email($email))
					$this->Send('501', '<'.$email.'> Invalid Address');
				else
				{
					$this->arMsg["FROM"] = $email;
					$this->arMsg["TO"] = array();

					$this->Send('250', '<'.$email.'> Sender ok');
				}
			}
			//F: 552, 451, 452
			//E: 500, 501, 421
			break;
		case "RCPT":
			if(!preg_match('#TO[ ]*:[ ]*(.+)#i', $arg, $arMatches))
				$this->Send('501', 'Unrecognized parameter '.$arg);
			else
			{
				$email = $arMatches[1];
				$email = CMailUtil::ExtractMailAddress($email);
				if($email=='' || !check_email($email))
					$this->Send('501', '<'.$email.'> Invalid Address');
				elseif(false)
					$this->Send('550', '<'.$email.'> User unknown');
				elseif(!$this->CheckRelaying($email))
					$this->Send('550', '<'.$email.'>... Relaying denied.');
				elseif(!$this->arMsg["FROM"])
					$this->Send('503', 'Sender is not specified');
				else
				{
					$this->arMsg["TO"][] = $email;
					$this->Send('250', '<'.$email.'> ok');

	               //S: 250, 251
	               //F: 550, 551, 552, 553, 450, 451, 452
	               //E: 500, 501, 503, 421
				}
			}
			break;
		case "DATA":
			if(!$this->arMsg["FROM"] || !$this->arMsg["TO"] || count($this->arMsg["TO"])==0)
				$this->Send('503');
			else
			{
				$this->Send('354');
				$this->__listenFunc = '__DataHandler';
			}
            // I: 354 -> data -> S: 250
            //                      F: 552, 554, 451, 452
            //   F: 451, 554
            //   E: 500, 501, 503, 421
			break;
		case "RSET":
			$this->Send('250', 'Resetting');
			$this->arMsg = array('LOCAL_ID'=>md5(uniqid()));
			//E: 500, 501, 504, 421
			break;
		case "QUIT":
			$this->Send('221');
			$this->Disconnect();
            //E: 500
			break;
		case "EHLO":
			if(trim($arg)=='')
				$this->host = $this->ip;
			else
				$this->host = $arg;

			$this->Send('250-ehlo', '');
			$this->Send('250-AUTH LOGIN PLAIN', '');
			//$this->Send('250-SIZE', '');
			$this->Send('250-HELP', '');
			$this->Send('250', 'EHLO');
			/*
			250-mail.company2.tld is pleased to meet you
			250-DSN
			250-SIZE
			250-STARTTLS
			250-AUTH LOGIN PLAIN CRAM-MD5 DIGEST-MD5 GSSAPI MSN NTLM
			250-ETRN
			250-TURN
			250-ATRN
			250-NO-SOLICITING
			250-HELP
			250-PIPELINING
			250 EHLO
			*/
			break;
		case "AUTH":
			if($this->authorized)
				$this->Send('503', 'Already authorized');
			elseif(count($this->arMsg)>1)
				$this->Send('503', 'Mail transaction is active');
			elseif(!preg_match('#^([A-Z0-9-_]+)[ ]*(\S*)$#i', $arg, $arMatches))
				$this->Send('501', 'Unrecognized parameter '.$arg);
			else
			{
				switch(strtoupper($arMatches[1]))
				{
				case "LOGIN":
					$this->Send('334', 'VXNlcm5hbWU6');
					$this->__listenFunc = '__AuthLoginHandler';
					$this->__login = false;
					break;
				case "PLAIN":
					if($arMatches[2] && trim($arMatches[2])!='')
					{
						$pwd = base64_decode($arMatches[2]);
						$this->Authorize($pwd, $pwd);
					}
					else
					{
						$this->Send('334', '');
						$this->__listenFunc = '__AuthPlainHandler';
					}
					break;
				default:
					$this->Send('504', 'Unrecognized authentication type.');
				}
			}

			break;
		case "NOOP":
	        $this->Send('250');
	        //E: 500, 421
			break;
		case "HELP":
	        //       S: 211, 214
	        //       E: 500, 501, 502, 504, 421
			break;
		case "EXPN":
			//<string>
	        //       S: 250
	        //       F: 550
	        //       E: 500, 501, 502, 504, 421
			break;
		case "VRFY":
	        //       S: 250, 251
	        //       F: 550, 551, 553
	        //       E: 500, 501, 502, 504, 421
			break;
		default:
			$this->Send('500', $command.' command unrecognized');
		}
		return true;
	}

public 	function Authorize($login, $password)
	{
		$authResult = $GLOBALS["USER"]->Login($login, $password, "N");

		if($authResult === true)
		{
			$this->Send("235", "Authentication successful");
			$this->auth_user_id = $GLOBALS["USER"]->GetID();
			$this->authorized = true;
			$this->WriteToLog('Authentication successful '.$this->auth_user_id, 7);
			return true;
		}

		$this->Send("535", "authorization failed");

		return false;
	}

public 	function __AuthLoginHandler()
	{
		if(strpos($this->readBuffer, "\r\n")===false)
			return false;

		$this->readBuffer = trim($this->readBuffer);
		if($this->readBuffer=="*")
			$this->Send('501', 'AUTH aborted');
		else
		{
			$pwd = base64_decode($this->readBuffer);
			if($this->__login === false)
			{
				$this->__login = $pwd;
				$this->Send('334', 'UGFzc3dvcmQ6');
				$this->readBuffer = "";
				return false;
			}
			else
			{
				$this->Authorize($this->__login, $pwd);
			}
		}

		$this->__login = false;
		$this->readBuffer = "";
		$this->__listenFunc = false;
		return true;
	}

public 	function __AuthPlainHandler()
	{
		if(strpos($this->readBuffer, "\r\n")===false)
			return false;
		$this->readBuffer = trim($this->readBuffer);
		if($this->readBuffer=="*")
			$this->Send('501', 'AUTH aborted');
		else
		{
			$pwd = base64_decode($this->readBuffer);
			if($pwd == '')
				$this->Send('501', 'Base64 decode error');
			else
			{
				$pwd = ltrim($pwd, chr(0));
				$this->Authorize(substr($pwd, 0, strpos($pwd, chr(0))), substr($pwd, strpos($pwd, chr(0))+1));
			}
		}

		$this->readBuffer = "";
		$this->__listenFunc = false;
		return true;
	}

public 	function __DataHandler()
	{
		if(strpos($this->readBuffer, "\r\n.\r\n")===false)
			return false;

		$this->readBuffer = substr($this->readBuffer, 0, -5);

		$this->readBuffer = str_replace("\r\n..", "\r\n.", $this->readBuffer);

		// Добавление сообщения куда надо
		$message = $this->readBuffer;
		$this->arMsg["MSG"] = $message;

		$this->WriteToLog('['.$this->arMsg["LOCAL_ID"].'] Start processing mail...', 7);

		$p = strpos($message, "\r\n\r\n");
		if($p>0)
		{
			$message_header = substr($message, 0, $p);
			$message_text = substr($message, $p+2);

			$arLocalTo = Array();
			foreach($this->arMsg["TO"] as $to)
			{
				if(is_array($this->arMsg["FOR_RELAY"]) && in_array($to, $this->arMsg["FOR_RELAY"]))
				{
					$message_header_add =
						"Received: from ".$this->host." by ".$this->server->arFields["SERVER"]." with Bitrix SMTP Server \r\n".
						"\t".date("r")."\r\n".
						"\tfor <".$to.">; \r\n".
						"Return-Path: <".$this->arMsg["FROM"].">\r\n";

					$subject = "";
					$message_header_new = $message_header;
					if(preg_match('/(Subject:\s*([^\r\n]*\r\n(\t[^\r\n]*\r\n)*))\S/is', $message_header_new."\r\nx", $reg))
					{
						$message_header_new = trim(str_replace($reg[1], "", $message_header_new."\r\n"));
						$subject = trim($reg[2]);
					}

					$r = bxmail($to, $subject, $message_text, $message_header_add.$message_header_new);
					$this->WriteToLog('['.$this->arMsg["LOCAL_ID"].'] Relay message to '.$to.' ('.($r?'OK':'FAILED').')', 7);
				}
				else
					$arLocalTo[] = $to;
			}

			if(count($arLocalTo)>0)
			{
				$message_header_add =
					"Received: from ".$this->host." by ".$this->server->arFields["SERVER"]." with Bitrix SMTP Server \r\n".
					"\t".date("r")."\r\n".
					"Return-Path: <".$this->arMsg["FROM"].">\r\n".
					"X-Original-Rcpt-to: ".implode(", ", $arLocalTo)."\r\n";

				$this->WriteToLog('['.$this->arMsg["LOCAL_ID"].'] Message add: '.$message_header_add.$message, 9);

				if($this->server->arFields["CHARSET"]!='')
					$charset = $this->server->arFields["CHARSET"];
				else
					$charset = $this->server->arFields["LANG_CHARSET"];

				$message_id = CMailMessage::AddMessage($this->server->arFields["ID"], $message_header_add.$message, $charset);

				$this->WriteToLog('['.$this->arMsg["LOCAL_ID"].'] Message sent to '.implode(", ", $arLocalTo).' ('.$message_id.')', 7);
			}
			$this->Send('250', $message_id.' Message accepted for delivery');
		}
		else
			$this->Send('554', ' Bad message format');

		$this->WriteToLog('['.$this->arMsg["LOCAL_ID"].'] End processing mail...', 7);

		$this->readBuffer = "";
		$this->__listenFunc = false;
		$this->arMsg = array('LOCAL_ID'=>md5(uniqid()));

		$this->msgCount++;
		$this->server->msgCount++;
		return true;
	}
}
?>