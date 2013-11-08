<?php

abstract class CPushMessage
{
	protected $_aDeviceTokens = array();
	protected $_sText;
	protected $_nBadge;
	protected $_sSound;
	protected $_nExpiryValue = 7200;

	protected $_mCustomIdentifier;
	protected $_sTitle;
	public $_sound;
	public $_aCustomProperties = array();

	public function addRecipient($sDeviceToken)
	{
		$this->_aDeviceTokens[] = $sDeviceToken;
	}

	public function getRecipient($nRecipient = 0)
	{
		if (!isset($this->_aDeviceTokens[$nRecipient]))
		{
			throw new Exception(
				"No recipient at index '{$nRecipient}'"
			);
		}

		return $this->_aDeviceTokens[$nRecipient];
	}

	public function getRecipients()
	{
		return $this->_aDeviceTokens;
	}

	public function setText($sText)
	{
		$this->_sText = str_replace("\n", " ", $sText);
	}

	public function getText()
	{
		return $this->_sText;
	}

	public function setTitle($sTitle)
	{
		$this->_sTitle = $sTitle;
	}

	public function getTitle()
	{
		return $this->_sTitle;
	}

	public function setBadge($nBadge)
	{
		if (!is_int($nBadge))
		{
			throw new Exception(
				"Invalid badge number '{$nBadge}'"
			);
		}
		$this->_nBadge = $nBadge;
	}

	public function getBadge()
	{
		return $this->_nBadge;
	}

	public function setSound($sSound = 'default')
	{
		$this->_sSound = $sSound;
	}

	public function getSound()
	{
		return $this->_sSound;
	}

	public function setCustomProperty($sName, $mValue)
	{
		$this->_aCustomProperties[trim($sName)] = $mValue;
	}

	public function getCustomProperty($sName)
	{
		if (!array_key_exists($sName, $this->_aCustomProperties))
		{
			throw new Exception(
				"No property exists with the specified name '{$sName}'."
			);
		}

		return $this->_aCustomProperties[$sName];
	}

	public function setExpiry($nExpiryValue)
	{
		if (is_int($nExpiryValue))
			$this->_nExpiryValue = $nExpiryValue;
	}

	public function getExpiry()
	{
		return $this->_nExpiryValue;
	}

	public function setCustomIdentifier($mCustomIdentifier)
	{
		$this->_mCustomIdentifier = $mCustomIdentifier;
	}

	public function getCustomIdentifier()
	{
		return $this->_mCustomIdentifier;
	}

	abstract function getBatch();

}

