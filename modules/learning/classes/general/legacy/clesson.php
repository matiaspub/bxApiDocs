<?php
/**
 * Code in this class is for temporary backward compatibility only, don't relay on it!
 * @deprecated
 */

/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/clesson/index.php
 * @author Bitrix
 * @deprecated
 */
class CLesson
{
	/**
	 * simple & stupid stub
	 * @deprecated
	 */
	
	/**
	* <p>Возвращает список уроков по фильтру <b>arFilter</b>, отсортированный в порядке <b>arOrder</b>. Учитываются права доступа текущего пользователя. Метод устарел, рекомендуется использовать CLearnLesson::GetList.</p>
	*
	*
	* @param array $arrayarOrder = Array("TIMESTAMP_X"=>"DESC") Массив для сортировки результата. Массив вида <i>array("поле
	* сортировки"=&gt;"направление сортировки" [, ...])</i>.<br>Поле для
	* сортировки может принимать значения: <ul> <li> <b>ID</b> - идентификатор
	* урока; </li> <li> <b>NAME</b> - название урока; </li> <li> <b>ACTIVE</b> - активность
	* урока; </li> <li> <b>SORT</b> - индекс сортировки; </li> <li> <b>TIMESTAMP_X</b> - дата
	* изменения урока. </li> <li> <b>CREATED_BY</b> - код пользователя, создавшего
	* урок. </li> <li> <b>CHAPTER_NAME</b> - название главы, в . </li> <li> <b>DATE_CREATE</b> - дата
	* создания урока. </li> </ul>Направление сортировки может принимать
	* значения: <ul> <li> <b>asc</b> - по возрастанию; </li> <li> <b>desc</b> - по убыванию;
	* </li> </ul>Необязательный. По умолчанию сортируется по убыванию даты
	* изменения урока.
	*
	* @param array $arrayarFilter = Array() Массив вида <i>array("фильтруемое поле"=&gt;"значение фильтра" [, ...])</i>.
	* Фильтруемое поле может принимать значения: <ul> <li> <b>ID</b> -
	* идентификатор урока; </li> <li> <b>NAME</b> - название урока (можно искать
	* по шаблону [%_]); </li> <li> <b>SORT</b> - индекс сортировки; </li> <li> <b>ACTIVE</b> -
	* фильтр по активности (Y|N); </li> <li> <b>TIMESTAMP_X</b> - дата изменения урока;
	* </li> <li> <b>DATE_CREATE</b> - дата создания урока; </li> <li> <b>CHAPTER_ID</b> -
	* идентификатор главы. Для получения списка родительских глав
	* установите это поле в значение <i>пусто</i>; </li> <li> <b>COURSE_ID</b> -
	* идентификатор курса; </li> <li> <b>CREATED_BY</b> - код пользователя,
	* создавшего урок; </li> <li> <b>DETAIL_TEXT</b> - детальное описание (можно
	* искать по шаблону [%_]); </li> <li> <b>PREVIEW_TEXT</b> - предварительное описание
	* (можно искать по шаблону [%_]); </li> <li> <b>MIN_PERMISSION</b> - минимальный
	* уровень доcтупа. По умолчанию "R". Список прав доступа см. в <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/setpermission.php">CCourse::SetPermission</a>. </li>
	* <li> <b>CHECK_PERMISSIONS</b> - проверять уровень доступа. Если установлено
	* значение "N" - права доступа не проверяются. </li> </ul>Перед названием
	* фильтруемого поля может указать тип фильтрации: <ul> <li>"!" - не равно
	* </li> <li>"&lt;" - меньше </li> <li>"&lt;=" - меньше либо равно </li> <li>"&gt;" - больше
	* </li> <li>"&gt;=" - больше либо равно </li> </ul> <br>"<i>значения фильтра</i>" -
	* одиночное значение или массив.<br><br>Необязательный. По умолчанию
	* записи не фильтруются.
	*
	* @return CDBResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> </h
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $COURSE_ID = 8;
	*     $res = CLesson::GetList(
	*         Array("SORT"=&gt;"ASC"), 
	*         Array("ACTIVE" =&gt; "Y", "COURSE_ID" =&gt; $COURSE_ID)
	*     );
	* 
	*     while ($arLesson = $res-&gt;GetNext())
	*     {
	*         echo "Lesson name: ".$arLesson["NAME"]."&lt;br&gt;";
	*     }
	* }
	* 
	* ?&gt;
	* 
	* &lt;?
	* 
	* if (CModule::IncludeModule("learning"))
	* {
	*     $res = CLesson::GetList(
	*         Array("SORT"=&gt;"ASC"), 
	*         Array("?NAME" =&gt; "Site")
	*     );
	* 
	*     while ($arLesson = $res-&gt;GetNext())
	*     {
	*         echo "Lesson name: ".$arLesson["NAME"]."&lt;br&gt;";
	*     }
	* }
	* ?&gt;
	* 
	* &lt;?
	* 
	* if (CModule::IncludeModule("learning"))
	* {
	*     $COURSE_ID = 8;
	* 
	*     $res = CLesson::GetList(
	*         Array("NAME" =&gt; "ASC", "SORT"=&gt;"ASC"), 
	*         Array("CHECK_PERMISSIONS" =&gt; "N", "COURSE_ID" =&gt; $COURSE_ID)
	*     );
	* 
	*     while ($arLesson = $res-&gt;GetNext())
	*     {
	*         echo "Lesson name: ".$arLesson["NAME"]."&lt;br&gt;";
	*     }
	* }
	* 
	* ?&gt;
	* 
	* &lt;?
	* 
	* if (CModule::IncludeModule("learning"))
	* {
	*     $COURSE_ID = 8;
	* 
	*     $res = CLesson::GetList(
	*         Array("NAME" =&gt; "ASC", "SORT"=&gt;"ASC"), 
	*         Array("CHECK_PERMISSIONS" =&gt; "N", "CHAPTER_ID" =&gt; "", "COURSE_ID" =&gt; $COURSE_ID)
	*     );
	* 
	*     while ($arLesson = $res-&gt;GetNext())
	*     {
	*         echo "Lesson name: ".$arLesson["NAME"]."&lt;br&gt;";
	*     }
	* }
	* 
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clesson/index.php">CLesson</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clesson/getbyid.php">GetByID</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#lesson">Поля урока</a> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/clesson/getlist.php
	* @author Bitrix
	* @deprecated
	*/
	public static function GetList($arOrder = 'will be ignored', $arFilter = array())
	{
		// We must replace '...ID' => '...LESSON_ID', 
		// where '...' is some operation (such as '!', '<=', etc.)
		foreach ($arFilter as $key => $value)
		{
			// If key ends with 'ID'
			if ((strlen($key) >= 2) && (strtoupper(substr($key, -2)) === 'ID'))
			{
				// And prefix before 'ID' doesn't contains letters
				if ( ! preg_match ("/[a-zA-Z_]+/", substr($key, 0, -2)) )
				{
					$prefix = '';
					if (strlen($key) > 2)
						$prefix = substr($key, 0, -2);

					$arFields[$prefix . 'LESSON_ID'] = $arFilter[$key];
					unset ($arFilter[$key]);
				}
			}
		}

		return (CLearnLesson::GetList(array(), $arFilter));
	}
}