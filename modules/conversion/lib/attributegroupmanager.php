<?php

namespace Bitrix\Conversion;

final class AttributeGroupManager extends Internals\TypeManager
{
	static protected $event = 'OnGetAttributeGroupTypes';
	static protected $types = array();
	static protected $ready = false;
	static protected $checkModule = false;
}
