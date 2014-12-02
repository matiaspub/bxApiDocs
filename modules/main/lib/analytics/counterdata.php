<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Main\Analytics;

use Bitrix\Main\Entity;

class CounterDataTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_counter_data';
	}

	public static function getMap()
	{
		return array(
			new Entity\StringField('ID', array(
				'primary' => true,
				'default_value' => array(__CLASS__ , 'getUniqueEventId')
			)),
			new Entity\StringField('TYPE', array(
				'required' => true
			)),
			new Entity\TextField('DATA', array(
				'serialized' => true
			))
		);
	}

	public static function getUniqueEventId()
	{
		list($usec, $sec) = explode(" ", microtime());

		return $sec . substr($usec, 2, 6);
	}

	public static function submitData($limit = 5)
	{
		if (!Catalog::isOn())
		{
			return '\\'.__METHOD__.'(5);';
		}

		$ids = array();
		$rows = array();

		$r = static::getList(array(
			'order' => array('ID' => 'ASC'),
			'limit' => $limit
		));

		while ($row = $r->fetch())
		{
			$rows[] = array(
				'type' => $row['TYPE'],
				'data' => $row['DATA']
			);

			$ids[] = $row['ID'];
		}

		if (!empty($rows))
		{
			// get queue size
			$totalCount = static::getCount();
			$queueSize = $totalCount > $limit ? $totalCount-$limit : 0;

			// send data
			$data = \http_build_query(array(
				'op' => 'e',
				'aid' => Counter::getAccountId(),
				'ad[cd][value]' => base64_encode(json_encode($rows)),
				'ad[cd][queue]' => $queueSize
			));

			if (strlen($data) > 30000)
			{
				if ($limit > 1)
				{
					return '\\'.__METHOD__.'('.max(1, $limit-1).');';
				}
			}

			$f = fsockopen('bitrix.info', 80, $errno, $errstr, 3);

			if ($f)
			{
				$out = "POST /bx_stat HTTP/1.1\r\n";
				$out .= "Host: bitrix.info\r\n";
				$out .= "Content-type: application/x-www-form-urlencoded\r\n";
				$out .= "Content-length: ".strlen($data)."\r\n";
				$out .= "User-Agent: Bitrix Stats Counter\r\n";
				$out .= "Connection: Close\r\n";
				$out .= "\r\n";
				$out .= $data."\r\n\r\n";

				fwrite($f, $out);

				$response = '';

				while (!feof($f))
				{
					$response .= fgets($f, 128);
				}

				fclose($f);

				// delete rows if service received data
				if (strpos($response, 'uid'))
				{
					foreach ($ids as $id)
					{
						static::delete($id);
					}
				}
			}
		}

		return '\\'.__METHOD__.'(5);';
	}
}
