<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CPullDemoComponent extends CBitrixComponent
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

		// set pull watch tag for current user
		CPullWatch::Add($USER->GetId(), 'PULL_TEST');

		$this->arResult['ajaxLink'] = $this->getPath().'/ajax.php';

		$this->includeComponentTemplate();

		return true;
	}
}
