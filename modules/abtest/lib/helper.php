<?php

namespace Bitrix\ABTest;

use Bitrix\Main\Type;

class Helper
{

	/**
	 * Returns active A/B-test
	 *
	 * @return array|null
	 */
	
	/**
	* <p>Статический метод возвращает текущее A/B-тестирование.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/abtest/helper/getactivetest.php
	* @author Bitrix
	*/
	public static function getActiveTest()
	{
		static $abtest;
		static $defined;

		if (!defined('SITE_ID') || !SITE_ID)
			return null;

		if (empty($defined))
		{
			$cache = new \CPHPCache();

			if ($cache->initCache(30*24*3600, 'abtest_active_'.SITE_ID, '/abtest'))
			{
				$abtest = $cache->getVars();
			}
			else
			{
				$abtest = ABTestTable::getList(array(
					'order' => array('SORT' => 'ASC'),
					'filter' => array(
						'SITE_ID'      => SITE_ID,
						'ACTIVE'       => 'Y',
						'<=START_DATE' => new Type\DateTime()
					)
				))->fetch() ?: null;

				$cache->startDataCache();
				$cache->endDataCache($abtest);
			}

			$defined = true;

			if (!empty($abtest))
			{
				if (!$abtest['MIN_AMOUNT'])
				{
					$capacity = AdminHelper::getSiteCapacity($abtest['SITE_ID']);
					if ($capacity['min'] > 0)
					{
						$result = ABTestTable::update($abtest['ID'], array('MIN_AMOUNT' => $capacity['min']));
						if ($result->isSuccess())
						{
							$cache->clean('abtest_active_'.SITE_ID, '/abtest');
							$abtest['MIN_AMOUNT'] = $capacity['min'];
						}
					}
				}

				if (intval($abtest['DURATION']) == -1)
				{
					if (intval($abtest['MIN_AMOUNT']) > 0)
					{
						$capacity = AdminHelper::getTestCapacity($abtest['ID']);
						if ($capacity['A'] >= $abtest['MIN_AMOUNT'] && $capacity['B'] >= $abtest['MIN_AMOUNT'])
						{
							Helper::stopTest($abtest['ID'], true);
							$abtest = null;
						}
					}
				}
				else if (intval($abtest['DURATION']) > 0)
				{
					$end = clone $abtest['START_DATE'];
					$end->add(intval($abtest['DURATION']).' days');

					if (time() > $end->format('U'))
					{
						Helper::stopTest($abtest['ID'], true);
						$abtest = null;
					}
				}
			}
		}

		return $abtest;
	}

	/**
	 * Returns an A/B-test context array
	 *
	 * @param array $abtest A/B-test.
	 * @param string $section Section.
	 * @return array
	 */
	private static function context($abtest, $section)
	{
		return array(
			'abtest'  => intval($abtest['ID']),
			'section' => $section,
			'data'    => $abtest['TEST_DATA']
		);
	}

	/**
	 * Returns current A/B-test context
	 *
	 * @return array|null
	 */
	
	/**
	* <p>Статический метод возвращает контекст текущего А/В-тестирования.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/abtest/helper/getcontext.php
	* @author Bitrix
	*/
	public static function getContext()
	{
		global $USER, $APPLICATION;

		static $context;

		if (!defined('SITE_ID') || !SITE_ID)
			return null;

		if (empty($context))
		{
			$activeTest = Helper::getActiveTest();

			if ($USER->canDoOperation('view_other_settings') && !empty($_SESSION['ABTEST_MODE']))
			{
				if ($_SESSION['ABTEST_MODE'] == 'reset')
				{
					if (!empty($activeTest))
						$context = Helper::context($activeTest, 'N');

					unset($_SESSION['ABTEST_MODE']);
				}
				else if (preg_match('/^(\d+)\|(A|B|N)$/', $_SESSION['ABTEST_MODE'], $matches))
				{
					if (!empty($activeTest) && $activeTest['ID'] == intval($matches[1]))
					{
						$context = Helper::context($activeTest, $matches[2]);

						unset($_SESSION['ABTEST_MODE']);
					}
					else
					{
						$abtest = ABTestTable::getList(array(
							'filter' => array('=ID' => intval($matches[1]), 'ENABLED' => 'Y')
						))->fetch();

						if (!empty($abtest) && $abtest['SITE_ID'] == SITE_ID)
							$context = Helper::context($abtest, $matches[2]);
					}
				}
			}

			if (empty($context) && !empty($activeTest))
			{
				$abtest = $activeTest;

				if ($cookie = $APPLICATION->get_cookie('ABTEST_'.SITE_ID))
				{
					if (preg_match('/^'.intval($abtest['ID']).'\|(A|B|N)$/i', $cookie, $matches))
						$section = $matches[1];
				}

				if (empty($section))
				{
					$dice = mt_rand(1, 100);

					if ($dice <= intval($abtest['PORTION'])/2)
						$section = 'A';
					else if ($dice <= intval($abtest['PORTION']))
						$section = 'B';
					else
						$section = 'N';
				}

				$context = Helper::context($abtest, $section);
			}

			if (empty($activeTest))
				$APPLICATION->set_cookie('ABTEST_'.SITE_ID, null);
			else if ($activeTest['ID'] == $context['abtest'])
				$APPLICATION->set_cookie('ABTEST_'.SITE_ID, intval($context['abtest']).'|'.$context['section']);
		}

		return $context;
	}

	/**
	 * Returns alternative test value for current A/B-test context
	 *
	 * @param string $type Test type.
	 * @param string $value Test original value.
	 * @return string|null
	 */
	
	/**
	* <p>Статический метод возвращает значения альтернативного варианта для текущего контекста A/B-тестирования.</p>
	*
	*
	* @param string $type  Тип тестирования.
	*
	* @param string $value  Значение оригинального варианта.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/abtest/helper/getalternative.php
	* @author Bitrix
	*/
	public static function getAlternative($type, $value)
	{
		$result = null;

		if ($context = Helper::getContext())
		{
			foreach ($context['data']['list'] as $item)
			{
				if ($item['type'] == $type && $item['old_value'] == $value)
				{
					$result = $item['new_value'];
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Starts an A/B-test
	 *
	 * @param int $id A/B-test ID.
	 * @return bool
	 */
	
	/**
	* <p>Статический метод запускает A/B-тестирование.</p>
	*
	*
	* @param integer $id  Идентификатор A/B-теста.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/abtest/helper/starttest.php
	* @author Bitrix
	*/
	public static function startTest($id)
	{
		global $USER;

		if ($abtest = ABTestTable::getById($id)->fetch())
		{
			$fields = array(
				'START_DATE' => new Type\DateTime(),
				'STOP_DATE'  => null,
				'ACTIVE'     => 'Y',
				'USER_ID'    => $USER->getID()
			);

			if (!$abtest['MIN_AMOUNT'])
			{
				$capacity = AdminHelper::getSiteCapacity($abtest['SITE_ID']);
				if ($capacity['min'] > 0)
					$fields['MIN_AMOUNT'] = $capacity['min'];
			}

			$result = ABTestTable::update(intval($id), $fields);

			if ($result->isSuccess())
			{
				Helper::clearCache($abtest['SITE_ID']);

				return true;
			}
		}

		return false;
	}

	/**
	 * Stops an A/B-test
	 *
	 * @param int $id A/B-test ID.
	 * @param bool $auto Auto-stop flag.
	 * @return bool
	 */
	
	/**
	* <p>Статический метод останавливает A/B-тестирование.</p>
	*
	*
	* @param integer $id  Идентификатор A/B-теста.
	*
	* @param boolean $auto = false Флаг авто-остановки.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/abtest/helper/stoptest.php
	* @author Bitrix
	*/
	public static function stopTest($id, $auto = false)
	{
		global $USER;

		if ($abtest = ABTestTable::getById($id)->fetch())
		{
			$fields = array(
				'STOP_DATE' => new Type\DateTime(),
				'ACTIVE'    => 'N',
			);

			if (!$auto)
				$fields['USER_ID'] = $USER->getID();

			$result = ABTestTable::update(intval($id), $fields);

			if ($result->isSuccess())
			{
				Helper::clearCache($abtest['SITE_ID']);

				return true;
			}
		}

		return false;
	}

	/**
	 * Deletes an A/B-test
	 *
	 * @param int $id A/B-test ID.
	 * @return bool
	 */
	
	/**
	* <p>Статический метод удаляет A/B-тестирование.</p>
	*
	*
	* @param integer $id  Идентификатор A/B-теста.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/abtest/helper/deletetest.php
	* @author Bitrix
	*/
	public static function deleteTest($id)
	{
		if ($abtest = ABTestTable::getById($id)->fetch())
		{
			$result = ABTestTable::delete(intval($id));

			if ($result->isSuccess())
			{
				if ($abtest['ACTIVE'] == 'Y')
					Helper::clearCache($abtest['SITE_ID']);

				return true;
			}
		}

		return false;
	}

	/**
	 * Cleans active A/B-test cache
	 *
	 * @param int $siteId Site ID.
	 * @return void
	 */
	
	/**
	* <p>Статический метод очищает кеш активного A/B-теста.</p>
	*
	*
	* @param integer $siteId  Идентификатор сайта.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/abtest/helper/clearcache.php
	* @author Bitrix
	*/
	public static function clearCache($siteId)
	{
		$cache = new \CPHPCache();
		$cache->clean('abtest_active_'.$siteId, '/abtest');
	}

}
