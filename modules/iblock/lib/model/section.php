<?php
namespace Bitrix\Iblock\Model;

use Bitrix\Iblock;

class Section
{
	private static $entityInstance = array();

	final public static function compileEntityByIblock($iblockId)
	{
		$iblockId = (int)$iblockId;
		if ($iblockId <= 0)
			return null;

		if (!isset(self::$entityInstance[$iblockId]))
		{
			$className = 'Section'.$iblockId.'Table';
			$entityName = "\\Bitrix\\Iblock\\".$className;
			$referenceName = 'Bitrix\Iblock\Section'.$iblockId;
			$entity = '
			namespace Bitrix\Iblock;
			class '.$className.' extends \Bitrix\Iblock\SectionTable
			{
				public static function getUfId()
				{
					return "IBLOCK_'.$iblockId.'_SECTION";
				}
				
				public static function getMap()
				{
					$fields = parent::getMap();
					$fields["PARENT_SECTION"] = array(
						"data_type" => "'.$referenceName.'",
						"reference" => array("=this.IBLOCK_SECTION_ID" => "ref.ID"),
					);
					return $fields;
				}
			}';
			eval($entity);
			self::$entityInstance[$iblockId] = $entityName;
		}

		return self::$entityInstance[$iblockId];
	}
}