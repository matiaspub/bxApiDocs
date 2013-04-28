<?
class CControllerServerRequestTo extends __CControllerPacketRequest
{
	var $url;
	var $debug_const = "CONTROLLER_SERVER_DEBUG";
	var $debug_file_const = "CONTROLLER_SERVER_LOG_DIR";

	public static function CControllerServerRequestTo($member, $operation, $arParameters = Array())
	{
		if(is_array($member))
			$arMember = $member;
		else
		{
			$dbr_member = CControllerMember::GetById($member);
			$arMember = $dbr_member->Fetch();
			if(!$arMember)
				return false;
		}

		$this->url = $arMember["URL"];

		$this->member_id = $arMember["MEMBER_ID"];
		$this->secret_id = $arMember["SECRET_ID"];
		$this->operation = $operation;
		$this->arParameters = $arParameters;
		$this->session_id = md5(uniqid(rand(), true));
	}

	public static function Send($page="/bitrix/admin/main_controller.php")
	{
		$this->Sign();
		$result = parent::Send($this->url, $page);
		if($result===false)
			return false;
		$oResponse = new CControllerServerResponseFrom($result);
		return $oResponse;
	}
}

class CControllerServerResponseFrom extends __CControllerPacketResponse
{
	var $debug_const = "CONTROLLER_SERVER_DEBUG";
	var $debug_file_const = "CONTROLLER_SERVER_LOG_DIR";

	public static function CControllerServerResponseFrom($oPacket = false)
	{
		$this->_InitFromRequest($oPacket, Array());
	}
}
//
// This class handles clients queries
//
class CControllerServerRequestFrom extends __CControllerPacketRequest
{
	var $debug_const = "CONTROLLER_SERVER_DEBUG";
	var $debug_file_const = "CONTROLLER_SERVER_LOG_DIR";

	public static function CControllerServerRequestFrom()
	{
		$this->InitFromRequest();
		$this->Debug('Request received from #'.$this->member_id.' (security check '.($this->Check()?'passed':'failed').') ('.$this->secret_id.") :\r\nPacket: ".print_r($this, true))."\r\n";
	}

	public static function Check()
	{
		$dbr_member = CControllerMember::GetByGuid($this->member_id);
		if(!($ar_member=$dbr_member->Fetch()))
		{
			$e = new CApplicationException("Bad member_id: ".$this->member_id."");
			$GLOBALS["APPLICATION"]->ThrowException($e);

			return false;
		}

		$this->secret_id = $ar_member["SECRET_ID"];
		return parent::Check();
	}
}

class CControllerServerResponseTo extends __CControllerPacketResponse
{
	var $debug_const = "CONTROLLER_SERVER_DEBUG";
	var $debug_file_const = "CONTROLLER_SERVER_LOG_DIR";

	public static function CControllerServerResponseTo($oPacket = false)
	{
		$this->_InitFromRequest($oPacket);
		$this->secret_id = false;
	}

	public static function Sign()
	{
		if($this->secret_id === false)
		{
			$dbr_member = CControllerMember::GetByGuid($this->member_id);
			if(!($ar_member=$dbr_member->Fetch()))
			{
				$e = new CApplicationException("Bad member_id: ".$this->member_id."");
				$GLOBALS["APPLICATION"]->ThrowException($e);

				return false;
			}
			$this->secret_id = $ar_member["SECRET_ID"];
		}

		return parent::Sign();
	}

	public static function Send()
	{
		//AddMessage2Log(print_r($this, true));
		parent::Send();
	}

}
?>
