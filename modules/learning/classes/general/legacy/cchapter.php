<?php
/**
 * Code in this file is for temporary backward compatibility only, don't relay on it!
 */
class CChapter
{
	// simple & stupid stub
	public static function GetNavChain ($courseId, $chapterId)
	{
		global $DB;

		$rc = $DB->Query("SELECT ID FROM b_learn_lesson WHERE ID < 0 AND ID = 13");
		return ($rc);
	}
}