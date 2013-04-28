<?php
namespace Bitrix\Main;

class Environment
	extends \Bitrix\Main\System\ReadonlyDictionary
{
	/**
	 * Creates env object.
	 *
	 * @param array $arEnv
	 */
	static public function __construct(array $arEnv)
	{
		parent::__construct($arEnv);
	}
}