<?php


/**
 * <br><br>
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * // пример пересчета журнала
 * $gradebook = new CGradeBook; 
 * $gradebook-&gt;RecountAttempts($STUDENT_ID,$TEST_ID);
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/cgradebook/index.php
 * @author Bitrix
 */
class CGradeBook extends CAllGradeBook
{
	/**
	 * This function is for internal use only.
	 * It can be changed without any notification.
	 *
	 * @access private
	 */
	final protected static function __getSqlFromClause($SqlSearchLang)
	{
		$strSqlFrom =
			"FROM b_learn_gradebook G ".
			"INNER JOIN b_learn_test T ON G.TEST_ID = T.ID ".
			"INNER JOIN b_user U ON U.ID = G.STUDENT_ID ".
			"LEFT JOIN b_learn_course C ON C.ID = T.COURSE_ID ".
			"LEFT JOIN b_learn_lesson TUL ON TUL.ID = C.LINKED_LESSON_ID ".
			"LEFT JOIN b_learn_test_mark TM ON G.TEST_ID = TM.TEST_ID ".
			(strlen($SqlSearchLang) > 2 ? "LEFT JOIN b_learn_course_site CS ON C.ID = CS.COURSE_ID " : "")
			. "WHERE
				(TM.SCORE IS NULL
				OR TM.SCORE =
					(SELECT SCORE
					FROM b_learn_test_mark
					WHERE SCORE >= (G.RESULT/G.MAX_RESULT*100) AND TEST_ID = G.TEST_ID
					ORDER BY SCORE ASC
					LIMIT 1)
				) ";

		if (strlen($SqlSearchLang) > 2)
			$strSqlFrom .= " AND CS.SITE_ID IN (" . $SqlSearchLang . ")";

		return ($strSqlFrom);
	}
}
