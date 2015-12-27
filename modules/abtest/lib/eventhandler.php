<?php

namespace Bitrix\ABTest;

use Bitrix\Main\Application;
use Bitrix\Main\Localization;

Localization\Loc::loadMessages(__FILE__);

class EventHandler
{

	/**
	 * Passes an A/B-test mode string from request to session
	 *
	 * @return void
	 */
	public static function onPageStart()
	{
		$applicationContext = Application::getInstance()->getContext();

		if ($mode = $applicationContext->getRequest()->get('abtest_mode'))
			$_SESSION['ABTEST_MODE'] = $mode;
	}

	/**
	 * Defines if site template should be overridden
	 * 
	 * @param \Bitrix\Main\Event $event Event.
	 * @return string|null
	 */
	public static function onGetCurrentSiteTemplate(\Bitrix\Main\Event $event)
	{
		$template = $event->getParameter('template');
		$result   = null;

		if ($context = Helper::getContext())
		{
			if ($context['section'] == 'B')
				$result = Helper::getAlternative('template', $template);
		}

		return $result;
	}

	/**
	 * Defines if page file should be overridden
	 * 
	 * @param \Bitrix\Main\Event $event Event.
	 * @return string|null
	 */
	public static function onFileRewrite(\Bitrix\Main\Event $event)
	{
		$path   = $event->getParameter('path');
		$result = null;

		if ($context = Helper::getContext())
		{
			if ($context['section'] == 'B')
				$result = Helper::getAlternative('page', $path);
		}

		return $result;
	}

	/**
	 * Returns A/B-test related attribute types
	 * 
	 * @return array
	 */
	public static function onGetAttributeTypes()
	{
		$sections = array(
			'A' => array('NAME' => Localization\Loc::getMessage('ABTEST_CONV_TEST_SECTION_A_NAME')),
			'B' => array('NAME' => Localization\Loc::getMessage('ABTEST_CONV_TEST_SECTION_B_NAME'))
		);

		return array(
			'abtest' => array(
				'MODULE' => 'abtest',
				'NAME'   => Localization\Loc::getMessage('ABTEST_CONVATTR_TEST_NAME'),
				'SORT'   => 5000,
				'GET_VALUES' => function(array $ids)
				{
					$result = ABTestTable::getList(array(
						'select' => array('ID', 'NAME'),
						'filter' => array('ID' => $ids),
						'order'  => array('SORT' => 'ASC'),
					));

					$values = array();
					while ($abtest = $result->fetch())
					{
						if (empty($abtest['NAME']))
							$abtest['NAME'] = str_replace('#ID#', $abtest['ID'], Localization\Loc::getMessage('ABTEST_CONV_TEST_TITLE'));

						$values[$abtest['ID']] = array(
							'NAME' => $abtest['NAME']
						);
					}

					return $values;
				}
			),
			'abtest_section' => array(
				'MODULE' => 'abtest',
				'NAME'   => Localization\Loc::getMessage('ABTEST_CONVATTR_TEST_SECTION_NAME'),
				'SORT'   => 5100,
				'GET_VALUES' => function(array $ids) use ($sections)
				{
					$values = array();
					foreach ($ids as $id)
					{
						if (!empty($sections[$id]))
							$values[$id] = $sections[$id];
					}

					return $values;
				}
			),
		);
	}

	/**
	 * Sets A/B-test related attributes to conversion context
	 * 
	 * @param \Bitrix\Conversion\DayContext $conversionContext Conversion context.
	 * @return void
	 */
	public static function onConversionSetContextAttributes(\Bitrix\Conversion\DayContext $conversionContext)
	{
		if ($abtest = Helper::getActiveTest())
		{
			if ($context = Helper::getContext())
			{
				if ($context['abtest'] != $abtest['ID'])
					return;

				if (!in_array($context['section'], array('A', 'B')))
					return;

				$conversionContext->setAttribute('abtest', $context['abtest']);
				$conversionContext->setAttribute('abtest_section', $context['section']);
			}
		}
	}

	/**
	 * Adds A/B-test related buttons to Control Panel
	 * 
	 * @return void
	 */
	public static function onPanelCreate()
	{
		global $USER, $APPLICATION;

		if ($USER->canDoOperation('view_other_settings'))
		{
			if ($context = Helper::getContext())
			{
				$baseUri = \CHTTP::urlDeleteParams($APPLICATION->getCurPage(), array('abtest_mode'));

				$groupAUri = \CHTTP::urlAddParams($baseUri, array('abtest_mode' => intval($context['abtest']).'|A'));
				$groupBUri = \CHTTP::urlAddParams($baseUri, array('abtest_mode' => intval($context['abtest']).'|B'));
				$resetUri  = \CHTTP::urlAddParams($baseUri, array('abtest_mode' => 'reset'));

				$APPLICATION->addPanelButton(array(
					'ID'   => 'abtest_options',
					'SRC'  => $context['section'] == 'B' ? '/bitrix/images/abtest/ab-icon-b.png' : '/bitrix/images/abtest/ab-icon-a.png',
					'TEXT' => str_replace('#ID#', intval($context['abtest']), Localization\Loc::getMessage('ABTEST_PANEL_MENU_BTN')),
					'TYPE' => 'BIG',
					'MAIN_SORT' => 900,
					'SORT' => 10,
					'MENU' => array(
						array(
							'ACTION'  => "jsUtils.Redirect([], '".\CUtil::jsEscape($groupAUri)."'); ",
							'TEXT'    => Localization\Loc::getMessage('ABTEST_PANEL_MENU_MODE_A'),
							'CHECKED' => $context['section'] == 'A' ? true : false,
						),
						array(
							'ACTION'  => "jsUtils.Redirect([], '".\CUtil::jsEscape($groupBUri)."'); ",
							'TEXT'    => Localization\Loc::getMessage('ABTEST_PANEL_MENU_MODE_B'),
							'CHECKED' => $context['section'] == 'B' ? true : false,
						),
						array(
							'ACTION'  => "jsUtils.Redirect([], '".\CUtil::jsEscape($resetUri)."'); ",
							'TEXT'    => Localization\Loc::getMessage('ABTEST_PANEL_MENU_RESET'),
						)
					)
				));
			}
		}
	}

}
