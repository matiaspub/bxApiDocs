<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Main\Analytics;

use Bitrix\Main\Entity;
use Bitrix\Main\Security\Random;

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

		$uniqid = substr(base_convert($sec.substr($usec, 2), 10, 36), 0, 16);

		if (strlen($uniqid) < 16)
		{
			$uniqid .= Random::getString(16-strlen($uniqid));
		}

		return $uniqid;
	}

	public static function submitData($limit = 50)
	{
		if (!Catalog::isOn())
		{
			return '\\'.__METHOD__.'();';
		}

		$rows = array();

		$r = static::getList(array(
			'order' => array('ID' => 'ASC'),
			'limit' => $limit
		));

		while ($row = $r->fetch())
		{
			$rows[$row['ID']] = array(
				'type' => $row['TYPE'],
				'data' => $row['DATA']
			);
		}

		if (!empty($rows))
		{
			// get queue size
			$totalCount = static::getCount();
			$queueSize = $totalCount > $limit ? $totalCount-$limit : 0;

			// set limit
			$dataSizeLimit = 45000;

			// make an optimal dataset
			$dataSize = strlen(base64_encode(json_encode(array_values($rows))));

			// records to delete
			$toDelete = array();

			if ($dataSize > $dataSizeLimit)
			{
				$reducedRows = array();

				foreach ($rows as $id => $row)
				{
					$rowSize = strlen(base64_encode(json_encode(array_values($row))));
					$reducedDataSize = strlen(base64_encode(json_encode(array_values($reducedRows))));

					if ($rowSize > $dataSizeLimit)
					{
						// abnormally big row, delete it
						$toDelete[] = $id;
					}
					elseif (!empty($reducedRows) && ($reducedDataSize + $rowSize) > $dataSizeLimit)
					{
						// it's enough
						break;
					}
					else
					{
						$reducedRows[$id] = $row;
					}
				}

				$rows = $reducedRows;
			}

			if (!empty($rows))
			{
				// if there are still some data, send it
				$data = \http_build_query(array(
					'op' => 'e',
					'aid' => Counter::getAccountId(),
					'ad[cd][value]' => base64_encode(json_encode(array_values($rows))),
					'ad[cd][queue]' => $queueSize
				));

				$f = fsockopen('bitrix.info', 80, $errno, $errstr, 3);

				if ($f)
				{
					$out = "POST /bx_stat HTTP/1.1\r\n";
					$out .= "Host: bitrix.info\r\n";
					$out .= "Content-type: application/x-www-form-urlencoded\r\n";
					$out .= "Content-length: " . strlen($data) . "\r\n";
					$out .= "User-Agent: Bitrix Stats Counter\r\n";
					$out .= "Connection: Close\r\n";
					$out .= "\r\n";
					$out .= $data . "\r\n\r\n";

					fwrite($f, $out);

					$response = '';

					while (!feof($f))
					{
						$response .= fgets($f, 128);
					}

					fclose($f);

					// delete rows if service received data
					if (strpos($response, '200 OK'))
					{
						$toDelete = array_merge($toDelete, array_keys($rows));
					}
				}
			}

			// delete abnormally big and sent rows
			foreach ($toDelete as $id)
			{
				static::delete($id);
			}
		}

		return '\\'.__METHOD__.'();';
	}
}
