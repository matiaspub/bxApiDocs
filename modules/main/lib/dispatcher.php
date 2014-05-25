<?php
namespace Bitrix\Main;

/*dispatchermutatormark1*/			//Do not remove this

final class Dispatcher
{
	private $licenseKey = '';

	private $isInitialized = false;

	public function __construct()
	{
		$this->isInitialized = false;
	}

	public function initialize()
	{
		$LICENSE_KEY = '';
		if (file_exists(($filename = Application::getDocumentRoot()."/bitrix/license_key.php")))
			include($filename);
		if (empty($LICENSE_KEY))
			$LICENSE_KEY = 'DEMO';

		$this->licenseKey = $LICENSE_KEY;

		/*dispatchermutatormark2*/			//Do not remove this

		$this->isInitialized = true;
	}

	public function getLicenseKey()
	{
		if (!$this->isInitialized)
			throw new NotSupportedException();

		return $this->licenseKey;
	}
}
