<?
/**
 * 
 * Класс-контейнер событий модуля <b>search</b>
 * 
 */
class _CEventsSearch {
	/**
	 * Вызывается перед индексацией элемента.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function BeforeIndex(){}

	/**
	 * Вызывается перед выполнением поисковых запросов.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnSearch(){}

	/**
	 * Вызывается при построении поискового индекса.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnReIndex(){}

	/**
	 * Вызывается при индексации статических файлов.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnSearchGetFileContent(){}

	/**
	 * Вызывается при форматировании элемента в результатах поиска.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnSearchGetURL(){}

	/**
	 * Вызывается при разборе тегов.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnSearchGetTag(){}

	/**
	 * Вызывается в начале первого шага полной переиндексации, непосредственно перед удалением всех данных поискового индекса.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnBeforeFullReindexClear(){}

	/**
	 * Вызывается перед удалением части поискового индекса. 
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnBeforeIndexDelete(){}

	/**
	 * Вызывается перед обновлением поискового индекса. 
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnBeforeIndexUpdate(){}

	/**
	 * Вызывается после добавления новых данных в поисковый индекс. 
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnAfterIndexAdd(){}

	/**
	 * Вызывается при построении поискового запроса.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnSearchCheckPermissions(){}

	/**
	 * Массив описывающий элемент поискового индекса. 	
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function arFields(){}

	/**
	 * Уникальный идентификатор записи в поисковом индексе.
	 *         <br>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function ID(){}

	/**
	 * SQL условие для удаления. Представляет собой фильтр по полю SEARCH_CONTENT_ID. 
	 *         <br>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function strWhere(){}

	/**
	 * Массив в котором передается информация о начале текущего шага. 		
	 *         <ul>
<li>
<b>MODULE</b> - идентификатор модуля;</li>
	 *          			
	 *           <li>
<b>ID</b> - идентификатор элемента;</li>
	 *          			
	 *           <li>
<b>SITE_ID</b> - массив сайтов;</li>
	 *          		</ul>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function NS(){}

	/**
	 * Объект модуля поиска для вызова метода индексации элемента. 	
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function oCallback(){}

	/**
	 * Метод объекта модуля поиска для индексации элемента. 	
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function callback_method(){}

	/**
	 * Столбец таблицы поискового индекса для использования в подзапросе (например: SC.ID или scsite.SEARCH_CONTENT_ID). 
	 *         <br>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function FIELD(){}

	/**
	 * Абсолютный путь к индексируемому файлу. 	
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function absolute_path(){}

	/**
	 * Идентификатор текущей сессии индексации. Может использоваться в обработчике события для добавления в поисковый индекс дополнительного контента с помощью метода <a href="/api_help/search/classes/csearch/indexs.php">CSearch::Index</a>.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function SEARCH_SESS_ID(){}

	/**
	 * Тег. 	
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function tag(){}


}
?>