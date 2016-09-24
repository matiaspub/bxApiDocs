<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class FinderDestTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_finder_dest';
	}

	public static function getMap()
	{
		global $USER;

		return array(
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			new Entity\ReferenceField(
				'USER',
				'Bitrix\Main\UserTable',
				array('=this.USER_ID' => 'ref.ID')
			),
			'CODE' => array(
				'data_type' => 'string',
				'primary' => true
			),
			'CODE_USER_ID' => array(
				'data_type' => 'integer'
			),
			'CODE_TYPE' => array(
				'data_type' => 'string'
			),
			new Entity\ReferenceField(
				'CODE_USER',
				'Bitrix\Main\UserTable',
				array('=this.CODE_USER_ID' => 'ref.ID')
			),
			new Entity\ReferenceField(
				'CODE_USER_CURRENT',
				'Bitrix\Main\UserTable',
				array(
					'=this.CODE_USER_ID' => 'ref.ID',
					'=this.USER_ID' => new SqlExpression('?i', $USER->GetId())
				)
			),
			'CONTEXT' => array(
				'data_type' => 'string',
				'primary' => true
			),
			'LAST_USE_DATE' => array(
				'data_type' => 'datetime'
			)
		);
	}

	/**
	 * Adds or updates data about using destinations by a user
	 *
	 * @param $data
     */
	public static function merge($data)
	{
		global $USER;

		static $connection = false;
		static $helper = false;

		$userId = (
			isset($data['USER_ID'])
			&& intval($data['USER_ID']) > 0
				? intval($data['USER_ID'])
				: (is_object($GLOBALS['USER']) ? $USER->getId() : 0)
		);

		if ($userId <= 0)
		{
			return;
		}

		if (!$connection)
		{
			$connection = \Bitrix\Main\Application::getConnection();
			$helper = $connection->getSqlHelper();
		}

		if (is_array($data['CODE']))
		{
			$dataModified = $data;

			foreach ($data['CODE'] as $code)
			{
				$dataModified['CODE'] = $code;
				FinderDestTable::merge($dataModified);
			}
			return;
		}
		else
		{
			$insertFields = array(
				'USER_ID' => $userId,
				'CODE' => strtoupper($data['CODE']),
				'CONTEXT' => (isset($data['CONTEXT']) ? strtoupper($data['CONTEXT']) : ''),
				'LAST_USE_DATE' => new \Bitrix\Main\DB\SqlExpression($helper->getCurrentDateTimeFunction())
			);

			if (preg_match('/^U(\d+)$/i', $data['CODE'], $matches))
			{
				$insertFields['CODE_USER_ID'] = intval($matches[1]);
				$insertFields['CODE_TYPE'] = 'U';
			}
			elseif (preg_match('/^SG(\d+)$/i', $data['CODE'], $matches))
			{
				$insertFields['CODE_TYPE'] = 'SG';
			}
			elseif (
				preg_match('/^D(\d+)$/i', $data['CODE'], $matches)
				|| preg_match('/^DR(\d+)$/i', $data['CODE'], $matches)
			)
			{
				$insertFields['CODE_TYPE'] = 'D';
			}
			elseif (
				preg_match('/^CRMCONTACT(\d+)$/i', $data['CODE'], $matches)
				|| preg_match('/^CRMCOMPANY(\d+)$/i', $data['CODE'], $matches)
				|| preg_match('/^CRMDEAL(\d+)$/i', $data['CODE'], $matches)
				|| preg_match('/^CRMLEAD(\d+)$/i', $data['CODE'], $matches)
			)
			{
				$insertFields['CODE_TYPE'] = 'CRM';
			}

			$merge = $helper->prepareMerge(
				'b_finder_dest',
				array('USER_ID', 'CODE'),
				$insertFields,
				array(
					'LAST_USE_DATE' => new \Bitrix\Main\DB\SqlExpression($helper->getCurrentDateTimeFunction())
				)
			);

			if ($merge[0] != "")
			{
				$connection->query($merge[0]);
			}

			$cache = new \CPHPCache;
			$cache->cleanDir('/sonet/log_dest_sort/'.intval($userId / 100));
		}
	}

	public static function convertRights($rights, $excludeCodes = array())
	{
		$result = array();

		if (is_array($rights))
		{
			foreach ($rights as $right)
			{
				if (
					!in_array($right, $excludeCodes)
					&& (
						preg_match('/^SG(\d+)$/i', $right, $matches)
						|| preg_match('/^U(\d+)$/i', $right, $matches)
						|| preg_match('/^DR(\d+)$/i', $right, $matches)
						|| preg_match('/^CRMCONTACT(\d+)$/i', $right, $matches)
						|| preg_match('/^CRMCOMPANY(\d+)$/i', $right, $matches)
						|| preg_match('/^CRMLEAD(\d+)$/i', $right, $matches)
						|| preg_match('/^CRMDEAL(\d+)$/i', $right, $matches)
					)
				)
				{
					$result[] = strtoupper($right);
				}
			}

			$result = array_unique($result);
		}

		return $result;
	}

	public static function onAfterDiskAjaxAction($sharings)
	{
		if (is_array($sharings))
		{
			$destinationCodes = array();
			foreach($sharings as $key => $sharing)
			{
				$destinationCodes[] = $sharing->getToEntity();
			}

			if (!empty($destinationCodes))
			{
				$destinationCodes = array_unique($destinationCodes);
				\Bitrix\Main\FinderDestTable::merge(array(
					"CONTEXT" => "DISK_SHARE",
					"CODE" => \Bitrix\Main\FinderDestTable::convertRights($destinationCodes)
				));
			}
		}
	}

	public static function migrateData()
	{
		$res = \CUserOptions::getList(
			array(),
			array(
				"CATEGORY" => "socialnetwork",
				"NAME" => "log_destination"
			)
		);

		while ($option = $res->fetch())
		{
			if (!empty($option["VALUE"]))
			{
				$optionValue = unserialize($option["VALUE"]);

				if (is_array($optionValue))
				{
					foreach($optionValue as $key => $val)
					{
						if (in_array($key, array("users", "sonetgroups", "department", "companies", "contacts", "leads", "deals")))
						{
							$codes = \CUtil::jsObjectToPhp($val);
							if (is_array($codes))
							{
								\Bitrix\Main\FinderDestTable::merge(array(
									"USER_ID" => $option["USER_ID"],
									"CONTEXT" => "blog_post",
									"CODE" => array_keys($codes)
								));
							}
						}
					}
				}
			}
		}

		$res = \CUserOptions::getList(
			array(),
			array(
				"CATEGORY" => "crm",
				"NAME" => "log_destination"
			)
		);

		while ($option = $res->fetch())
		{
			if (!empty($option["VALUE"]))
			{
				$optionValue = unserialize($option["VALUE"]);

				if (is_array($optionValue))
				{
					foreach($optionValue as $key => $val)
					{
						$codes = explode(',', $val);
						if (is_array($codes))
						{
							\Bitrix\Main\FinderDestTable::merge(array(
								"USER_ID" => $option["USER_ID"],
								"CONTEXT" => "crm_post",
								"CODE" => $codes
							));
						}
					}
				}
			}
		}
	}

	public static function getMailUserId($code)
	{
		$userId = array();
		$result = array();

		if (!is_array($code))
		{
			$code = array($code);
		}

		foreach($code as $val)
		{
			if (preg_match('/^U(\d+)$/', $val, $matches))
			{
				$userId[] = $matches[1];
			}
		}

		if (!empty($userId))
		{
			$res = \Bitrix\Main\UserTable::getList(array(
				'order' => array(),
				'filter' => array(
					"ID" => $userId,
					"=EXTERNAL_AUTH_ID" => 'email'
				),
				'select' => array("ID")
			));

			while ($user = $res->fetch())
			{
				$result[] = $user["ID"];
			}
		}

		return $result;
	}

}
