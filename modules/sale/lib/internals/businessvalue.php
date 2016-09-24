<?php

namespace Bitrix\Sale\Internals;

use Bitrix\Main;

class BusinessValueTable extends Main\Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_bizval';
	}

	const COMMON_PERSON_TYPE_ID = 0;
	const COMMON_CONSUMER_KEY = 'COMMON';

	public static function getMap()
	{
		return array(

			new Main\Entity\StringField('CODE_KEY', array(
				'primary' => true,
				'size' => 50,
			)),

			new Main\Entity\StringField('CONSUMER_KEY', array(
				'primary' => true,
				'size' => 50,
				'save_data_modification' => function ()
				{
					return array(
						function ($value)
						{
							return $value ?: BusinessValueTable::COMMON_CONSUMER_KEY;
						}
					);
				},
				'fetch_data_modification' => function ()
				{
					return array(
						function ($value)
						{
							return $value == BusinessValueTable::COMMON_CONSUMER_KEY ? null : $value;
						}
					);
				}
			)),

			new Main\Entity\IntegerField('PERSON_TYPE_ID', array(
				'primary' => true,
				'size' => 50,
				'save_data_modification' => function ()
				{
					return array(
						function ($value)
						{
							return $value ?: BusinessValueTable::COMMON_PERSON_TYPE_ID;
						}
					);
				},
				'fetch_data_modification' => function ()
				{
					return array(
						function ($value)
						{
							return $value == BusinessValueTable::COMMON_PERSON_TYPE_ID ? null : (int) $value;
						}
					);
				}
			)),

			new Main\Entity\StringField('PROVIDER_KEY', array(
				'required' => true,
				'size' => 50,
			)),

			new Main\Entity\StringField('PROVIDER_VALUE', array(
				'size' => 255,
			)),

		);
	}
}
