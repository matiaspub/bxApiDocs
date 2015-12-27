<?php
/**
 * Code in this file is for temporary backward compatibility only, don't relay on it!
 */

/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/cchapter/index.php
 * @author Bitrix
 */
class CChapter
{
	// simple & stupid stub
	
	/**
	* <p>Метод возвращает путь по дереву от корня до главы <i>chapterId</i>.</p>
	*
	*
	* @param int $courseId  Идентификатор курса. <br><br> До версии 12.0.0 параметр назывался COURSE_ID.
	*
	* @param int $chapterId  Идентификатор главы.<br><br> До версии 12.0.0 параметр назывался CHAPTER_ID.
	*
	* @return CDBResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> </h
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	* 
	*     $COURSE_ID = 90;
	*     $CHAPTER_ID = 116;
	* 
	*     $nav = CChapter::GetNavChain($COURSE_ID, $CHAPTER_ID);
	*     $i = 1;
	*     while($arChain = $nav-&gt;GetNext())
	*     {
	*         if ($i &gt; 1) echo " -&gt; ";
	*         echo $arChain["NAME"];
	*         $i++;
	*     }
	* 
	*     //<
	*     The above example will output something similar to:
	*     
	*     Chapter 1 -&gt; Chapter 1.1 -&gt; Chapter 1.1.3 -&gt; Chapter 1.1.2
	*     $CHAPTER_ID - ID of Chapter 1.1.2;
	*     >//
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cchapter/index.php">CChapter</a>::<a
	* href="getlist.php.html">GetList</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cchapter/index.php">CChapter</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cchapter/getbyid.php">GetByID</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#chapter">Поля главы</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/cchapter/getnavchain.php
	* @author Bitrix
	*/
	public static function GetNavChain ($courseId, $chapterId)
	{
		global $DB;

		$rc = $DB->Query("SELECT ID FROM b_learn_lesson WHERE ID < 0 AND ID = 13");
		return ($rc);
	}
}