<?php
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Sale\Location\Import\Compiler\Db;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Sale\Location;

class ExportTreeRussiaTable extends ExportTreeTable
{
	protected $regionCodeIndex = array();

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_tmp_export_tree_russia';
	}

	public function dropCodeIndex()
	{
		unset($this->codeIndex);

		if(!empty($this->regionCodeIndex))
			$this->codeIndex = $this->regionCodeIndex;
	}

	public function insert($data)
	{
		if(isset($this->codeIndex[$data['SYS_CODE']])) // already in there
			return;

		if($data['TYPE_CODE'] == 'REGION')
			$this->regionCodeIndex[$data['SYS_CODE']] = $this->formatCode($this->exportOffset);

		$this->codeIndex[$data['SYS_CODE']] = $this->formatCode($this->exportOffset);

		$data['CODE'] = $this->codeIndex[$data['SYS_CODE']];
		$data['PARENT_CODE'] = strlen($data['PARENT_SYS_CODE']) ? $this->codeIndex[$data['PARENT_SYS_CODE']] : '';

		unset($data['PARENT_SYS_CODE']);

		if(is_array($data['LANGNAMES']))
			$data['LANGNAMES'] = serialize($data['LANGNAMES']);

		if(is_array($data['EXTERNALS']))
			$data['EXTERNALS'] = serialize($data['EXTERNALS']);

		$this->exportOffset++;

		$this->inserter->insert($data);
	}

	public static function getMap()
	{
		$map = parent::getMap();
		$map['ZIP'] = array(
			'data_type' => 'string',
		);

		return $map;
	}
}
