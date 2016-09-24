<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2015 Bitrix
 */
namespace Bitrix\Main\UI;

class AdminPageNavigation extends PageNavigation
{
	protected $pageSizes = array(10, 20, 50, 100, 200, 500);
	protected $allowAll = true;

	/**
	 * @param string $id Navigation identity like "nav-cars".
	 */
	public function __construct($id)
	{
		parent::__construct($id);
		$this->initFromUri();
	}
}
