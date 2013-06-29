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
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function BeforeIndex(){}

	/**
	 * Вызывается перед выполнением поисковых запросов.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function OnSearch(){}

	/**
	 * Вызывается при построении поискового индекса.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function OnReIndex(){}

	/**
	 * Вызывается при индексации статических файлов.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function OnSearchGetFileContent(){}

	/**
	 * Вызывается при форматировании элемента в результатах поиска.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function OnSearchGetURL(){}

	/**
	 * Вызывается при разборе тегов.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function OnSearchGetTag(){}

	/**
	 * Вызывается в начале первого шага полной переиндексации, непосредственно перед удалением всех данных поискового индекса.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function OnBeforeFullReindexClear(){}

	/**
	 * Вызывается перед удалением части поискового индекса. 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function OnBeforeIndexDelete(){}

	/**
	 * Вызывается перед обновлением поискового индекса. 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function OnBeforeIndexUpdate(){}

	/**
	 * Вызывается после добавления новых данных в поисковый индекс. 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function OnAfterIndexAdd(){}

	/**
	 * Вызывается при построении поискового запроса.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function OnSearchCheckPermissions(){}


}
?>