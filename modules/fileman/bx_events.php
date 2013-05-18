<?
/**
 * 
 * Класс-контейнер событий модуля <b>fileman</b>
 * 
 */
class _CEventsFileman {
	/**
	 * непосредственно перед подключением JavaScript-файлов редактора. Позволяет добавить в список подключаемых файлов дополнительный JavaScript файл или файл стилей.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CFileMan::ShowHTMLEditControl
	 */
	public static function OnBeforeHTMLEditorScriptsGet(){}

	/**
	 * перед подключением JavaScript-файлов упрощенного редактора.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CLightHTMLEditor::Init
	 */
	public static function OnBeforeLightEditorScriptsGet(){}

	/**
	 * непосредственно после подключения редактора, на странице вызова, но до его инициализации.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CFileMan::ShowHTMLEditControl
	 */
	public static function OnIncludeHTMLEditorScript(){}

	/**
	 * непосредственно после подключения упрощенного редактора, на странице вызова, но до его инициализации.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CLightHTMLEditor::InitScripts
	 */
	public static function OnIncludeLightEditorScript(){}


}
?>