<?php

namespace Bitrix\Main\Web;

use Bitrix\Main\Application;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Type;

class PostDecodeFilter implements Type\IRequestFilter
{
	/**
	 * @param array $values
	 * @return array
	 */
	static public function filter(array $values)
	{
		if(Application::getInstance()->isUtfMode())
		{
			return null;
		}
		if(empty($values['post']) || !is_array($values['post']))
		{
			return null;
		}

		return array(
			'post' => Encoding::convertEncoding($values['post'], 'UTF-8', SITE_CHARSET),
		);
	}
}