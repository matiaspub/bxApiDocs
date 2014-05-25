<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CPullDemoWebrtcComponent extends CBitrixComponent
{
	/**
	 * @return bool
	 */
	public function executeComponent()
	{
		if(!CModule::IncludeModule('pull'))
		{
			return false;
		}
		global $USER;

		$this->arResult['userId'] = $USER->getId();
		$this->arResult['signalingLink'] = $this->getPath().'/call.ajax.php';

		CJSCore::RegisterExt('pullDemoWebrtc', array(
			'js' => $this->getPath().'/demo_webrtc.js',
			'lang' => $this->getPath().'/lang/'.LANGUAGE_ID.'/js_demo_webrtc.php',
			'rel' => array('webrtc')
		));
		CJSCore::Init('pullDemoWebrtc');

		$this->includeComponentTemplate();

		return true;
	}
}
