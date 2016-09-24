<?php
namespace Bitrix\Main\UI\Uploader;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
class Status
{
	/** @var string */
	protected $code;
	/** @var string */
	protected $message;
	/** @var string */
	protected $status = "inprogress";

	public function __construct($status = "", $code = "", $message = "")
	{
		if ($status != "")
			$this->status = $status;
		if ($code != "")
		{
			$codes = array(
				"BXU349.100" => Loc::getMessage("BXU_FileIsBlockedByOtherProcess")
			);
			if ($message == '' && in_array($code, $codes))
				$message = $codes[$code];
			$this->message = $message;
			$this->code = $code;
		}
	}
	public function getStatus()
	{
		return $this->status;
	}

	public function getCode()
	{
		return $this->code;
	}

	public function getMessage()
	{
		return $this->message;
	}
}
class Error extends Status
{
	/** @var string */
	protected $status = "error";
	public function __construct($code, $message = '')
	{
		$codes = array(
			// required fields
			"BXU344" => Loc::getMessage("BXU_RequiredParamCIDIsNotEntered"),
			"BXU344.1" => Loc::getMessage("BXU_RequiredParamPackageIndexIsNotEntered"),
			"BXU344.2" => Loc::getMessage("BXU_EmptyData"),
			// permission
			"BXU345.1" => Loc::getMessage("BXU_SessionIsExpired"),
			"BXU345.2" => Loc::getMessage("BXU_AccessDenied"),
			// Uploading Errors
			"BXU347.1" => Loc::getMessage("BXU_TemporaryDirectoryIsNotCreated"),
			"BXU347.2" => Loc::getMessage("BXU_FileIsNotUploaded"),
			"BXU347.3" => Loc::getMessage("BXU_FileIsFailed"),
			"BXU347.4" => Loc::getMessage("BXU_FileIsNotFullyUploaded"),
			"BXU347.5" => Loc::getMessage("BXU_FileNameIsNotValid"),

			"BXU347" => Loc::getMessage("BXU_FileIsLost"),
			// Processing Errors
			"BXU349.1" => Loc::getMessage("BXU_TemporaryFileIsNotCreated"),
			"BXU349.2" => Loc::getMessage("BXU_FilePartCanNotBeRead"),
			"BXU349.3" => Loc::getMessage("BXU_FilePartCanNotBeOpened"),
			"BXU349.4" => Loc::getMessage("BXU_FilesIsNotGlued"),

			"BXU350.1" => Loc::getMessage("BXU_UserHandlerError"),
		);

		if ($code == "BXU347.2")
		{
			switch ($message)
			{
				case UPLOAD_ERR_INI_SIZE:
					$message = Loc::getMessage("BXU_UPLOAD_ERR_INI_SIZE");
					break;
				case UPLOAD_ERR_FORM_SIZE:
					$message = Loc::getMessage("BXU_UPLOAD_ERR_FORM_SIZE");
					break;
				case UPLOAD_ERR_PARTIAL:
					$message = Loc::getMessage("BXU_UPLOAD_ERR_PARTIAL");
					break;
				case UPLOAD_ERR_NO_FILE:
					$message = Loc::getMessage("BXU_UPLOAD_ERR_NO_FILE");
					break;
				case UPLOAD_ERR_NO_TMP_DIR:
					$message = Loc::getMessage("BXU_UPLOAD_ERR_NO_TMP_DIR");
					break;
				case UPLOAD_ERR_CANT_WRITE:
					$message = Loc::getMessage("BXU_UPLOAD_ERR_CANT_WRITE");
					break;
				case UPLOAD_ERR_EXTENSION:
					$message = Loc::getMessage("BXU_UPLOAD_ERR_EXTENSION");
					break;
				default:
					$message = 'Unknown uploading error ['.$message.']';
					break;
			}
		}
		if (empty($message) && array_key_exists($code, $codes))
		{
			$message = $codes[$code];
		}
		$this->message = $message;
		$this->code = $code;
	}
}