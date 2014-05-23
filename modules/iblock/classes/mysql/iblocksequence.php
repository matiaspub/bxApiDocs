<?
class CIBlockSequence
{
	var $iblock_id = 0;
	var $property_id = 0;

	function __construct($iblock_id, $property_id = 0)
	{
		return $this->CIBlockSequence($iblock_id, $property_id);
	}

	function CIBlockSequence($iblock_id, $property_id = 0)
	{
		$this->iblock_id = $iblock_id;
		$this->property_id = $property_id;
	}

	function Drop($bAll = false)
	{
		global $DB;
		//OR part of the where is just for some cleanup
		$strSql = "
			DELETE
			FROM b_iblock_sequence
			WHERE IBLOCK_ID = ".intval($this->iblock_id)."
			".(!$bAll? "AND CODE = 'PROPERTY_".intval($this->property_id)."'": "")."
			OR NOT EXISTS (
				SELECT * FROM
				b_iblock_property
				WHERE concat('PROPERTY_', b_iblock_property.ID) = b_iblock_sequence.CODE
				AND b_iblock_property.IBLOCK_ID = b_iblock_sequence.IBLOCK_ID
			)
		";
		$rs = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		return $rs;
	}

	function GetCurrent()
	{
		global $DB;
		$strSql = "
			SELECT *
			FROM b_iblock_sequence
			WHERE IBLOCK_ID = ".intval($this->iblock_id)."
			AND CODE = 'PROPERTY_".intval($this->property_id)."'
		";
		$rs = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		$ar = $rs->Fetch();
		if($ar)
			return $ar["SEQ_VALUE"];
		else
			return 0;
	}

	function GetNext()
	{
		global $DB;
		$strSql = "
			INSERT INTO b_iblock_sequence (IBLOCK_ID, CODE, SEQ_VALUE)
			VALUES (".intval($this->iblock_id).", 'PROPERTY_".intval($this->property_id)."', LAST_INSERT_ID(1))
			ON DUPLICATE KEY UPDATE SEQ_VALUE = LAST_INSERT_ID(SEQ_VALUE + 1)
		";
		$rs = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		return $DB->LastID();
	}

	function SetNext($value)
	{
		global $DB;
		$value = intval($value);
		$strSql = "
			INSERT INTO b_iblock_sequence (IBLOCK_ID, CODE, SEQ_VALUE)
			VALUES (".intval($this->iblock_id).", 'PROPERTY_".intval($this->property_id)."', LAST_INSERT_ID(".$value."))
			ON DUPLICATE KEY UPDATE SEQ_VALUE = LAST_INSERT_ID(".$value.")
		";
		$rs = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		return $DB->LastID();
	}
}
?>
