<?php

namespace Bitrix\Blog\Internals;

use Bitrix\Conversion\Config;
use Bitrix\Conversion\DayContext;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class ConversionHandlers
{

	/**
	 * Returns conversion counter types
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает типы счетчиков конверсии. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/blog/internals/conversionhandlers/ongetcountertypes.php
	* @author Bitrix
	*/
	public static function onGetCounterTypes()
	{
		return array(
			'blog_post_add_day' => array('MODULE' => 'blog', 'GROUP' => 'day', 'NAME' => 'Post'),
		);
	}

	/**
	 * Returns conversion rate types
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает типы конверсии. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/blog/internals/conversionhandlers/ongetratetypes.php
	* @author Bitrix
	*/
	public static function onGetRateTypes()
	{
		$scale = array(0.5, 1, 1.5, 2, 5);

		return array(
			'blog_post' => array(
				'NAME'      => Loc::getMessage('BLOG_CONVERSION_RATE_POST_TITLE'),
				'SCALE'     => $scale,
				'MODULE'    => 'blog',
				'SORT'      => 4000,
				'COUNTERS'  => array('conversion_visit_day', 'blog_post_add_day'),
				'CALCULATE' => function(array $counters)
				{
					$denominator = $counters['conversion_visit_day'] ?: 0;
					$numerator   = $counters['blog_post_add_day'] ?: 0;

					return array(
						'DENOMINATOR' => $denominator,
						'NUMERATOR'   => $numerator,
						'RATE'        => $denominator ? $numerator / $denominator : 0,
					);
				},
			),
		);
	}

	/**
	 * Increments conversion post day counter
	 *
	 * @param int $id Post ID.
	 * @param array $fields Post fields.
	 * @return void
	 */
	
	/**
	* <p>Метод увеличивает значение счетчика при добавлении поста. Метод статический.</p>
	*
	*
	* @param integer $id  Идентификатор поста.
	*
	* @param array $fields  Массив полей поста.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/blog/internals/conversionhandlers/onpostadd.php
	* @author Bitrix
	*/
	public static function onPostAdd($id, $fields)
	{
		if (Loader::includeModule('conversion'))
		{
			$context = DayContext::getInstance();
			$context->addDayCounter('blog_post_add_day', 1);
		}
	}

}
