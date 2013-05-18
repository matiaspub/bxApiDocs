<?
/**
 * 
 * Класс-контейнер событий модуля <b>sale</b>
 * 
 */
class _CEventsSale {
	/**
	 * Вызывается перед добавлением типа плательщика, может быть использовано для отмены или модификации данных. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>&amp;arFields</i></span></td> <td>Массив полей типа плательщика</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalepersontype/csalepersontype__add.a7f60787.php">Add</a>
	 */
	public static function OnBeforePersonTypeAdd(&$arFields){}

	/**
	 * Вызывается после добавления типа плательщика. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор добавленного типа плательщика</td> </tr>
<tr>
<td><span class="syntax"><i>arFields</i></span></td> <td>Массив полей типа плательщика</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalepersontype/csalepersontype__add.a7f60787.php">Add</a>
	 */
	public static function OnPersonTypeAdd($ID, $arFields){}

	/**
	 * Вызывается перед изменением типа плательщика, может быть использовано для отмены или модификации данных. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор типа плательщика</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;arFields</i></span></td> <td>Массив полей типа плательщика</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalepersontype/csalepersontype__update.c02002e6.php">Update</a>
	 */
	public static function OnBeforePersonTypeUpdate($ID, &$arFields){}

	/**
	 * Вызывается после изменения типа плательщика. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор типа плательщика</td> </tr>
<tr>
<td><span class="syntax"><i>arFields</i></span></td> <td>Массив полей типа плательщика</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalepersontype/csalepersontype__update.c02002e6.php">Update</a>
	 */
	public static function OnPersonTypeUpdate($ID, $arFields){}

	/**
	 * Вызывается перед удалением типа плательщика, может быть использовано для отмены. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор типа плательщика</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalepersontype/csalepersontype__delete.c2566ed3.php">Delete</a>
	 */
	public static function OnBeforePersonTypeDelete($ID){}

	/**
	 * Вызывается после удаления типа плательщика 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор типа плательщика</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalepersontype/csalepersontype__delete.c2566ed3.php">Delete</a>
	 */
	public static function OnPersonTypeDelete($ID){}

	/**
	 * Вызывается после добавления города. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><i>ID</i></td> <td>Идентификатор города</td> </tr>
<tr>
<td><i>arFields</i></td> <td>Массив полей города</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__addcity.d2d048d2.php">AddCity</a>
	 */
	public static function OnCityAdd($ID, $arFields){}

	/**
	 * Вызывается после удаления города. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><i>ID</i></td> <td>Идентификатор города</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__deletecity.339c5a43.php">DeleteCity</a>
	 */
	public static function OnCityDelete($ID){}

	/**
	 * Вызывается после изменения города. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><i>ID</i></td> <td>Идентификатор города</td> </tr>
<tr>
<td><i>arFields</i></td> <td>Массив полей полей города</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__updatecity.3fe4165d.php">UpdateCity</a>
	 */
	public static function OnCityUpdate($ID, $arFields){}

	/**
	 * Вызывается перед добавлением города 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><i>arFields</i></td> <td>Массив новых параметров.</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__addcity.d2d048d2.php">AddCity</a>
	 */
	public static function OnBeforeCityAdd($arFields){}

	/**
	 * Вызывается перед удалением города. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><i>ID</i></td> <td>Код записи города</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__deletecity.339c5a43.php">DeleteCity</a>
	 */
	public static function OnBeforeCityDelete($ID){}

	/**
	 * Вызывается перед обновлением города 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><i>ID</i></td> <td>Код записи города.</td> </tr>
<tr>
<td><i>arFields</i></td> <td>Массив новых параметров города.</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__updatecity.3fe4165d.php">UpdateCity</a>
	 */
	public static function OnBeforeCityUpdate($ID, $arFields){}

	/**
	 * Вызывается после удаления региона. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><i>ID</i></td> <td>Идентификатор региона</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleLocation::DeleteRegion
	 */
	public static function OnRegionDelete($ID){}

	/**
	 * Вызывается до удаления региона, может быть использовано для отмены удаления. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><i>ID</i></td> <td>Идентификатор региона</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleLocation::DeleteRegion
	 */
	public static function OnBeforeRegionDelete($ID){}

	/**
	 * Вызывается после обновления региона. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><i>ID</i></td> <td>Идентификатор региона</td> </tr>
<tr>
<td><i>arFields</i></td> <td>Массив полей региона</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleLocation::UpdateRegion
	 */
	public static function OnRegionUpdate($ID, $arFields){}

	/**
	 * Вызывается до обновления региона, может быть использовано для отмены или модификации данных 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><i>ID</i></td> <td>Идентификатор региона</td> </tr>
<tr>
<td><i>arFields</i></td> <td>Массив полей региона</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleLocation::UpdateRegion
	 */
	public static function OnBeforeRegionUpdate($ID, $arFields){}

	/**
	 * Вызывается перед добавлением региона. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><i>arFields</i></td> <td>Массив полей региона.</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleLocation::AddRegion
	 */
	public static function OnBeforeRegionAdd($arFields){}

	/**
	 * Вызывается после добавлением региона 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><i>ID</i></td> <td>Идентификатор региона</td> </tr>
<tr>
<td><i>arFields</i></td> <td>Массив полей региона</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleLocation::AddRegion
	 */
	public static function OnRegionAdd($ID, $arFields){}

	/**
	 * Вызывается после добавления страны 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><i>ID</i></td> <td>Идентификатор страны</td> </tr>
<tr>
<td><i>arFields</i></td> <td>Массив полей страны</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__addcountry.cbe82f7a.php">AddCountry</a>
	 */
	public static function OnCountryAdd($ID, $arFields){}

	/**
	 * Вызывается после удаления страны. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><i>ID</i></td> <td>Идентификатор страны</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__deletecountry.e37a14ed.php">DeleteCountry</a>
	 */
	public static function OnCountryDelete($ID){}

	/**
	 * Вызывается после изменения страны 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><i>ID</i></td> <td>Идентификатор страны</td> </tr>
<tr>
<td><i>arFields</i></td> <td>Массив полей страны</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__updatecountry.d8fa5b90.php">UpdateCountry</a>
	 */
	public static function OnCountryUpdate($ID, $arFields){}

	/**
	 * Вызывается перед добавлением страны. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><i>arFields</i></td> <td>Массив новых параметров страны</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__addcountry.cbe82f7a.php">AddCountry</a>
	 */
	public static function OnBeforeCountryAdd($arFields){}

	/**
	 * Вызывается перед удалением страны. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><i>ID</i></td> <td>Идентификатор страны</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__deletecountry.e37a14ed.php">DeleteCountry</a>
	 */
	public static function OnBeforeCountryDelete($ID){}

	/**
	 * Вызывается перед обновлением страны. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><i>ID</i></td> <td>Идентификатор страны</td> </tr>
<tr>
<td><i>arFields</i></td> <td>Массив новых параметров страны</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__updatecountry.d8fa5b90.php">UpdateCountry</a>
	 */
	public static function OnBeforeCountryUpdate($ID, $arFields){}

	/**
	 * Вызывается после удаления местоположения 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><i>ID</i></td> <td>Идентификатор местоположения</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__delete.008e0aa2.php">Delete</a>
	 */
	public static function OnLocationDelete($ID){}

	/**
	 * Вызывается после удаления всех местоположений. 
	 *         <br>
	 *       Параметров нет 		 
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__deleteall.1cda6559.php">DeleteAll</a>
	 */
	public static function OnLocationDeleteAll(){}

	/**
	 * Вызывается после добавления местоположения 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><i>ID</i></td> <td>Идентификатор местоположения</td> </tr>
<tr>
<td><i>arFields</i></td> <td>Массив полей местоположения</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__addlocation.21fe0465.php">AddLocation</a>
	 */
	public static function OnLocationAdd($ID, $arFields){}

	/**
	 * Вызывается после обновления местоположения. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><i>ID</i></td> <td>Идентификатор местоположения</td> </tr>
<tr>
<td><i>arFields</i></td> <td>Массив полей местоположения</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__updatelocation.3c5a6205.php">UpdateLocation</a>
	 */
	public static function OnLocationUpdate($ID, $arFields){}

	/**
	 * Вызывается перед добавлением местоположения 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><i>arFields</i></td> <td>Массив новых параметров местоположения</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__addlocation.21fe0465.php">AddLocation</a>
	 */
	public static function OnBeforeLocationAdd($arFields){}

	/**
	 * Вызывается перед удалением местоположения. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><i>ID</i></td> <td>Код записи местоположения</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__delete.008e0aa2.php">Delete</a>
	 */
	public static function OnBeforeLocationDelete($ID){}

	/**
	 * Вызывается перед удалением всех местоположений. 
	 *         <br>
	 *       Параметров нет. 		 
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__deleteall.1cda6559.php">DeleteAll</a>
	 */
	public static function OnBeforeLocationDeleteAll(){}

	/**
	 * Вызывается перед изменением местоположения. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><i>ID</i></td> <td>Идентификатор местоположения</td> </tr>
<tr>
<td><i>arFields</i></td> <td>Массив полей местоположения</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__updatelocation.3c5a6205.php">UpdateLocation</a>
	 */
	public static function OnBeforeLocationUpdate($ID, $arFields){}

	/**
	 * Вызывается после добавления группы местоположений 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><i>ID</i></td> <td>Идентификатор группы местоположений</td> </tr>
<tr>
<td><i>arFields</i></td> <td>Массив полей группы местоположений</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocationgroup/csalelocationgroup__add.3520254b.php">Add</a>
	 */
	public static function OnLocationGroupAdd($ID, $arFields){}

	/**
	 * Вызывается после удаления группы местоположений. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><i>ID</i></td> <td>Идентификатор группы местоположений</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocationgroup/csalelocationgroup__delete.d96420be.php">Delete</a>
	 */
	public static function OnLocationGroupDelete($ID){}

	/**
	 * Вызывается после после группы местоположений 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><i>ID</i></td> <td>Идентификатор группы местоположений</td> </tr>
<tr>
<td><i>arFields</i></td> <td>Массив полей группы местоположений</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocationgroup/csalelocationgroup__update.c02c467b.php">Update</a>
	 */
	public static function OnLocationGroupUpdate($ID, $arFields){}

	/**
	 * Вызывается перед добавлением группы местоположений. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><i>arFields</i></td> <td>Массив новых параметров группы местоположений</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocationgroup/csalelocationgroup__add.3520254b.php">Add</a>
	 */
	public static function OnBeforeLocationGroupAdd($arFields){}

	/**
	 * Вызывается перед удалением группы местоположений. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><i>ID</i></td> <td>Код записи группы местоположений</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocationgroup/csalelocationgroup__delete.d96420be.php">Delete</a>
	 */
	public static function OnBeforeLocationGroupDelete($ID){}

	/**
	 * Вызывается перед изменением группы местоположений 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><i>ID</i></td> <td>Идентификатор группы местоположений</td> </tr>
<tr>
<td><i>arFields</i></td> <td>Массив полей группы местоположений</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocationgroup/csalelocationgroup__update.c02c467b.php">Update</a>
	 */
	public static function OnBeforeLocationGroupUpdate($ID, $arFields){}

	/**
	 * Вызывается перед добавлением заказа, может быть использовано для отмены или модификации данных. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>&amp;arFields</i></span></td> 				<td>Массив полей заказа</td> 			</tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__add.5a463c02.php">Add</a>
	 */
	public static function OnBeforeOrderAdd(&$arFields){}

	/**
	 * Вызывается после добавления заказа. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> 				<td>Идентификатор добавленного заказа</td> 			</tr>
<tr>
<td><span class="syntax"><i>arFields</i></span></td> 				<td>Массив полей заказа</td> 			</tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__add.5a463c02.php">Add</a>
	 */
	public static function OnOrderAdd($ID, $arFields){}

	/**
	 * Вызывается перед изменением заказа, может быть использовано для отмены или модификации данных. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> 				<td>Идентификатор заказа</td> 			</tr>
<tr>
<td><span class="syntax"><i>&amp;arFields</i></span></td> 				<td>Массив полей заказа</td> 			</tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__update.a8be5ffa.php">Update</a>
	 */
	public static function OnBeforeOrderUpdate($ID, &$arFields){}

	/**
	 * Вызывается после изменения заказа. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> 				<td>Идентификатор заказа</td> 			</tr>
<tr>
<td><span class="syntax"><i>arFields</i></span></td> 				<td>Массив полей заказа</td> 			</tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__update.a8be5ffa.php">Update</a>
	 */
	public static function OnOrderUpdate($ID, $arFields){}

	/**
	 * Вызывается перед удалением заказа, может быть использовано для отмены. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>ID</i></span></td> 				<td>Идентификатор заказа</td> 			</tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__delete.f9925f50.php">Delete</a>
	 */
	public static function OnBeforeOrderDelete($ID){}

	/**
	 * вызывается после удаления заказа 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>ID</i></span></td> 				<td>Идентификатор заказа</td> 			</tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__delete.f9925f50.php">Delete</a>
	 */
	public static function OnOrderDelete($ID){}

	/**
	 * Вызывается после калькуляции заказа. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>arOrder</i></span></td> <td>Массив параметров заказа</td> 			</tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleOrder::DoCalculateOrder
	 */
	public static function OnSaleCalculateOrder($arOrder){}

	/**
	 * Вызывается после расчёта скидки на заказ. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>arOrder</i></span></td> <td>Массив параметров заказа</td> 			</tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleOrder::DoCalculateOrder
	 */
	public static function OnSaleCalculateOrderDiscount($arOrder){}

	/**
	 * Вызывается после расчёта доставки.
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>arOrder</i></span></td> <td>Массив параметров заказа</td> 			</tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleOrder::DoCalculateOrder
	 */
	public static function OnSaleCalculateOrderDelivery($arOrder){}

	/**
	 * Вызывается после расчёта налога на доставку.
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>arOrder</i></span></td> <td>Массив параметров заказа</td> 			</tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleOrder::DoCalculateOrder
	 */
	public static function OnSaleCalculateOrderDeliveryTax($arOrder){}

	/**
	 * Вызывается после определения платёжной системы.
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>arOrder</i></span></td> <td>Массив параметров заказа</td> 			</tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleOrder::DoCalculateOrder
	 */
	public static function OnSaleCalculateOrderPaySystem($arOrder){}

	/**
	 * Вызывается после определения типа плательщика.
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>arOrder</i></span></td> <td>Массив параметров заказа</td> 			</tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleOrder::DoCalculateOrder
	 */
	public static function OnSaleCalculateOrderPersonType($arOrder){}

	/**
	 * Вызывается после формирования свойств плательщика.
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>arOrder</i></span></td> <td>Массив параметров заказа</td> 			</tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleOrder::DoCalculateOrder
	 */
	public static function OnSaleCalculateOrderProps($arOrder){}

	/**
	 * Вызывается после формирования массива заказа из корзины.
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>arOrder</i></span></td> <td>Массив параметров заказа</td> 			</tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleOrder::DoCalculateOrder
	 */
	public static function OnSaleCalculateOrderShoppingCart($arOrder){}

	/**
	 * Вызывается после определения налогов.
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>arOrder</i></span></td> <td>Массив параметров заказа</td> 			</tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleOrder::DoCalculateOrder
	 */
	public static function OnSaleCalculateOrderShoppingCartTax($arOrder){}

	/**
	 * Вызывается перед добавлением статуса заказа, может быть использовано для отмены или модификации данных. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>&amp;arFields</i></span></td> 	<td>Массив полей статуса заказа</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalestatus/csalestatus__add.c7ce74b1.php">Add</a>
	 */
	public static function OnBeforeStatusAdd(&$arFields){}

	/**
	 * Вызывается после добавления статуса заказа. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор добавленного статуса заказа</td> </tr>
<tr>
<td><span class="syntax"><i>arFields</i></span></td> <td>Массив полей статуса заказа</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalestatus/csalestatus__add.c7ce74b1.php">Add</a>
	 */
	public static function OnStatusAdd($ID, $arFields){}

	/**
	 * Вызывается перед изменением статуса заказа, может быть использовано для отмены или модификации данных. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор статуса заказа</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;arFields</i></span></td> <td>Массив полей статуса заказа</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalestatus/csalestatus__update.145077bd.php">Update</a>
	 */
	public static function OnBeforeStatusUpdate($ID, &$arFields){}

	/**
	 * Вызывается после изменения статуса заказа. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор статуса заказа</td> </tr>
<tr>
<td><span class="syntax"><i>arFields</i></span></td> <td>Массив полей статуса заказа</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalestatus/csalestatus__update.145077bd.php">Update</a>
	 */
	public static function OnStatusUpdate($ID, $arFields){}

	/**
	 * Вызывается перед удалением статуса заказа, может быть использовано для отмены. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор статуса заказа</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalestatus/csalestatus__delete.11104aab.php">Delete</a>
	 */
	public static function OnBeforeStatusDelete($ID){}

	/**
	 * Вызывается после удаления статуса заказа 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>ID</i></span></td> 				<td>Идентификатор статуса заказа</td> 			</tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalestatus/csalestatus__delete.11104aab.php">Delete</a>
	 */
	public static function OnStatusDelete($ID){}

	/**
	 * Вызывается до добавления аффилиата.
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>$arFields</i></span></td> <td>Массив изменяемых параметров</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleAffiliate::Add
	 */
	public static function OnBeforeBAffiliateAdd($arFields){}

	/**
	 * Вызывается после добавления аффилиата.
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>$ID</i></span></td> <td>Код добавленного аффилиата.</td> </tr>
<tr>
<td><span class="syntax"><i>arFields</i></span></td> <td>Массив параметров</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleAffiliate::Add
	 */
	public static function OnAfterBAffiliateAdd($ID, $arFields){}

	/**
	 * Вызывается до обновления
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Код аффилиата</td> </tr>
<tr>
<td><span class="syntax"><i>$arFields</i></span></td> <td>Массив изменяемых параметров</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleAffiliate::Update
	 */
	public static function OnBeforeAffiliateUpdate($ID, $arFields){}

	/**
	 * Вызывается после обновления
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Код аффилиата</td> </tr>
<tr>
<td><span class="syntax"><i>$arFields</i></span></td> <td>Массив параметров</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleAffiliate::Update
	 */
	public static function OnAfterAffiliateUpdate($ID, $arFields){}

	/**
	 * Вызывается перед удалением
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Код аффилиата</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleAffiliate::Delete
	 */
	public static function OnBeforeAffiliateDelete($ID){}

	/**
	 * Вызывается после удаления
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Код аффилиата</td> </tr>
<tr>
<td><span class="syntax"><i>$bResult</i></span></td> <td>Результат удаления (true/false)Массив параметров</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleAffiliate::Delete
	 */
	public static function OnAfterAffiliateDelete($ID, $bResult){}

	/**
	 * Вызывается перед калькуляцией
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>$arAffiliate</i></span></td> <td>Массив параметров аффилиата</td> </tr>
<tr>
<td><span class="syntax"><i>$dateFrom</i>, <i>$dateTo</i></span></td> <td>Период калькуляции</td> </tr>
<tr>
<td><span class="syntax"><i>$datePlanFrom</i>, <i>$datePlanTo</i></span></td> <td>Период определения плана</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleAffiliate::CalculateAffiliate
	 */
	public static function OnBeforeAffiliateCalculate($arAffiliate, $dateFrom, dateTo, $datePlanFrom, datePlanTo){}

	/**
	 * Вызывается после калькуляции
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>affiliateID </i></span></td> <td>Код аффилиата</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleAffiliate::CalculateAffiliate
	 */
	public static function OnAfterAffiliateCalculate($affiliateID ){}

	/**
	 * Вызывается перед выплатой суммы на счёт        <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>$arAffiliate</i></span></td> <td>Массив данных аффилиата.</td> </tr>
<tr>
<td><span class="syntax"><i>$payType</i></span></td> <td>Статус что делать с суммой.</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleAffiliate::PayAffiliate
	 */
	public static function OnBeforePayAffiliate($arAffiliate, $payType){}

	/**
	 * Вызывается после выплат
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>affiliateID </i></span></td> <td>Код аффилиата</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleAffiliate::PayAffiliate
	 */
	public static function OnAfterPayAffiliate($affiliateID ){}

	/**
	 * Вызывается до сохранения плана
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>$arFields</i></span></td> <td>Массив параметров плана</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleAffiliatePlan::Add
	 */
	public static function OnBeforeAffiliatePlanAdd($arFields){}

	/**
	 * Вызывается после сохранения плана
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>$ID</i></span></td> <td>Код плана</td> </tr>
<tr>
<td><span class="syntax"><i>$arFields</i></span></td> <td>Массив параметров плана</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleAffiliatePlan::Add
	 */
	public static function OnAfterAffiliatePlanAdd($ID, $arFields){}

	/**
	 * Вызывается до обновления плана.
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>$ID</i></span></td> <td>Код плана.</td> </tr>
<tr>
<td><span class="syntax"><i>$arFields</i></span></td> <td>Массив параметров плана</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleAffiliatePlan::Update
	 */
	public static function OnBeforeAffiliatePlanUpdate($ID, $arFields){}

	/**
	 * Вызывается после обновления плана.
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>$ID</i></span></td> <td>Код плана.</td> </tr>
<tr>
<td><span class="syntax"><i>$arFields</i></span></td> <td>Массив параметров плана</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleAffiliatePlan::Update
	 */
	public static function OnAfterAffiliatePlanUpdate($ID, $arFields){}

	/**
	 * Вызывается до удаления плана
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>$ID</i></span></td> <td>Код плана</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleAffiliatePlan::Delete
	 */
	public static function OnBeforeAffiliatePlanDelete($ID){}

	/**
	 * Вызывается после удаления плана
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>$ID</i></span></td> <td>Код плана</td> </tr>
<tr>
<td><span class="syntax"><i>$bResult</i></span></td> <td>Результат удаления (true/false)</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleAffiliatePlan::Delete
	 */
	public static function OnAfterAffiliatePlanDelete($ID, $bResult){}

	/**
	 * Вызывается перед изменением флага оплаты заказа, может быть использовано для отмены. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>val</i></span></td> <td>Флаг оплаты (Y - оплатить заказ, N - снять оплату заказа)</td> </tr>
<tr>
<td><span class="syntax"><i>bWithdraw</i></span></td> <td>Значение true отражает изменение флага на внутреннем счете пользователя; значение false изменяет только флаг, не затрагивая счет</td> </tr>
<tr>
<td><span class="syntax"><i>bPay</i></span></td> <td>Если параметр bWithdraw установлен в true, то установка параметра bPay в true приведет к тому, что необходимая сумма денег будет внесена на счет покупателя перед оплатой, а установка в false приведет к тому, что оплата будет происходить целиком с внутреннего счета; если параметр bWithdraw установлен в false, то операции со счетом не производятся и значение параметра bPay не играет роли.</td> </tr>
<tr>
<td><span class="syntax"><i>recurringID</i></span></td> <td>Должен быть равен 0</td> </tr>
<tr>
<td><span class="syntax"><i>arAdditionalFields</i></span></td> <td>Массив дополнительно обновляемых параметров (обычно это номер и дата платежного поручения)</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__payorder.88101c0f.php">PayOrder</a>
	 */
	public static function OnSaleBeforePayOrder($ID, $val, $bWithdraw, $bPay, $recurringID, $arAdditionalFields){}

	/**
	 * Вызывается после изменения флага оплаты заказа. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>val</i></span></td> <td>Флаг оплаты (Y - выставление оплаты, N - снятие оплаты)</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__payorder.88101c0f.php">PayOrder</a>
	 */
	public static function OnSalePayOrder($ID, $val){}

	/**
	 * Вызывается перед изменением флага разрешения доставки заказа, может быть использовано для отмены. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>val</i></span></td> <td>Флаг разрешения доставки (Y - разрешено, N - запрещено)</td> </tr>
<tr>
<td><span class="syntax"><i>recurringID</i></span></td> <td>Должен быть равен 0</td> </tr>
<tr>
<td><span class="syntax"><i>arAdditionalFields</i></span></td> <td>Массив дополнительно обновляемых параметров (обычно это номер и дата документа отгрузки)</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__deliverorder.fd0e66d5.php">DeliverOrder</a>
	 */
	public static function OnSaleBeforeDeliveryOrder($ID, $val, $recurringID, $arAdditionalFields){}

	/**
	 * Вызывается после изменения флага разрешения доставки заказа. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>val</i></span></td> <td>Флаг разрешения доставки (Y - разрешено, N - запрещено)</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__deliverorder.fd0e66d5.php">DeliverOrder</a>
	 */
	public static function OnSaleDeliveryOrder($ID, $val){}

	/**
	 * Вызывается перед изменением флага отмены заказа, может быть использовано для отмены. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>val</i></span></td> <td>Флаг отмены заказа (Y - отменено, N - не отменено)</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__cancelorder.fa562b77.php">CancelOrder</a>
	 */
	public static function OnSaleBeforeCancelOrder($ID, $val){}

	/**
	 * Вызывается после изменения флага отмены заказа. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>val</i></span></td> <td>Флаг отмены заказа (Y - отменено, N - не отменено)</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__cancelorder.fa562b77.php">CancelOrder</a>
	 */
	public static function OnSaleCancelOrder($ID, $val){}

	/**
	 * Вызывается перед изменением статуса заказа, может быть использовано для отмены. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>val</i></span></td> <td>Идентификатор статуса</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__statusorder.f21c0322.php">StatusOrder</a>
	 */
	public static function OnSaleBeforeStatusOrder($ID, $val){}

	/**
	 * Вызывается после изменения статуса заказа. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>val</i></span></td> <td>Идентификатор статуса</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__statusorder.f21c0322.php">StatusOrder</a>
	 */
	public static function OnSaleStatusOrder($ID, $val){}

	/**
	 * Вызывается перед добавлением записи в корзину, может быть использовано для отмены или модификации данных. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><i>&amp;arFields</i></td> <td>Массив полей записи корзины</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalebasket/csalebasket__add.php">Add</a>
	 */
	public static function OnBeforeBasketAdd(&$arFields){}

	/**
	 * Вызывается после добавления записи в корзину. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор добавленной записи</td> </tr>
<tr>
<td><span class="syntax"><i>arFields</i></span></td> <td>Массив полей записи корзины</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalebasket/csalebasket__add.php">Add</a>
	 */
	public static function OnBasketAdd($ID, $arFields){}

	/**
	 * Вызывается перед изменением записи в корзине, может быть использовано для отмены или модификации данных. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор записи в корзине</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;arFields</i></span></td> 	<td>Массив полей записи корзины</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalebasket/csalebasket__update.3dd628d0.php">Update</a>
	 */
	public static function OnBeforeBasketUpdate($ID, &$arFields){}

	/**
	 * Вызывается после изменения записи в корзине. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор записи в корзине</td>
</tr>
<tr>
<td><span class="syntax"><i>arFields</i></span></td> <td>Массив полей записи корзины</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleBasket::_Update
	 */
	public static function OnBasketUpdate($ID, $arFields){}

	/**
	 * Вызывается перед изменением корзины после проверки массива <b>$arFields</b>. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Код записи товара в корзине</td> </tr>
<tr>
<td><span class="syntax"><i>arFields</i></span></td> <td>Массив новых параметров элемента корзины</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleBasket::_Update
	 */
	public static function OnBeforeBasketUpdateAfterCheck($ID, $arFields){}

	/**
	 * Вызывается перед удалением записи из корзины, может быть использовано для отмены. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>ID</i></span></td> 				<td>Идентификатор записи в корзине</td> 			</tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalebasket/csalebasket__delete.e0d06223.php">Delete</a>
	 */
	public static function OnBeforeBasketDelete($ID){}

	/**
	 * Вызывается после удаления записи из корзины 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор записи в корзине</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csalebasket/csalebasket__delete.e0d06223.php">Delete</a>
	 */
	public static function OnBasketDelete($ID){}

	/**
	 * Системное, не описывается. 
	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleBasket::DoSaveOrderBasket
	 */
	public static function OnSetCouponList(){}

	/**
	 * Системное, не описывается.
	 *       
	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleBasket::DoSaveOrderBasket
	 */
	public static function OnClearCouponList(){}

	/**
	 * Вызывается перед отправкой письма о новом заказе, может быть использовано для модификации данных, изменения идентификатора типа почтового события, по которому будет осуществлена отправка, и отмены отправки письма. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;eventName</i></span></td> <td>Тип почтового события по которому будет осуществлена отправка</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;arFields</i></span></td> 	<td>Массив данных о заказе, которые будут подставлены в почтовый шаблон</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleOrder::DoSaveOrder
	 */
	public static function OnOrderNewSendEmail($ID, &$eventName, &$arFields){}

	/**
	 * Вызывается перед отправкой письма о разрешении доставки заказа, может быть использовано для модификации данных, изменения идентификатора типа почтового события, по которому будет осуществлена отправка, и отмены отправки письма. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;eventName</i></span></td> <td>Тип почтового события по которому будет осуществлена отправка</td> 	</tr>
<tr>
<td><span class="syntax"><i>&amp;arFields</i></span></td> <td>Массив данных о заказе, которые будут подставлены в почтовый шаблон</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__deliverorder.fd0e66d5.php">DeliverOrder</a>
	 */
	public static function OnOrderDeliverSendEmail($ID, &$eventName, &$arFields){}

	/**
	 * Вызывается перед отправкой письма об оплате заказа, может быть использовано для модификации данных, изменения идентификатора типа почтового события, по которому будет осуществлена отправка, и отмены отправки письма. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;eventName</i></span></td> <td>Тип почтового события по которому будет осуществлена отправка</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;arFields</i></span></td> <td>Массив данных о заказе, которые будут подставлены в почтовый шаблон</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__payorder.88101c0f.php">PayOrder</a>
	 */
	public static function OnOrderPaySendEmail($ID, &$eventName, &$arFields){}

	/**
	 * Вызывается перед отправкой письма об отмене заказа, может быть использовано для модификации данных, изменения идентификатора типа почтового события, по которому будет осуществлена отправка, и отмены отправки письма. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;eventName</i></span></td> <td>Тип почтового события по которому будет осуществлена отправка</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;arFields</i></span></td> <td>Массив данных о заказе, которые будут подставлены в почтовый шаблон</td>
</tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__cancelorder.fa562b77.php">CancelOrder</a>
	 */
	public static function OnOrderCancelSendEmail($ID, &$eventName, &$arFields){}

	/**
	 * Вызывается в момент формирования письма клиенту о смене статуса заказа. Может быть использовано для переопределения текста письма или его дополнения (для этого обработчик события должен возвращать необходимый текст). 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>val</i></span></td> <td>Идентификатор статуса заказа</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__statusorder.f21c0322.php">StatusOrder</a>
	 */
	public static function OnSaleStatusEMail($ID, $val){}

	/**
	 * Вызывается перед отправкой письма о cмене статуса заказа, может быть использовано для модификации данных, изменения идентификатора типа почтового события, по которому будет осуществлена отправка, и отмены отправки письма. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;eventName</i></span></td> <td>Тип почтового события по которому будет осуществлена отправка</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;arFields</i></span></td> <td>Массив данных о заказе, которые будут подставлены в почтовый шаблон</td>
</tr>
<tr>
<td><span class="syntax"><i>val</i></span></td> <td>Идентификатор статуса заказа</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__statusorder.f21c0322.php">StatusOrder</a>
	 */
	public static function OnOrderStatusSendEmail($ID, &$eventName, &$arFields, $val){}

	/**
	 * Вызывается перед отправкой письма о напоминании оплаты заказа, может быть использовано для модификации данных, изменения идентификатора типа почтового события, по которому будет осуществлена отправка, и отмены отправки письма. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;eventName</i></span></td> <td>Тип почтового события по которому будет осуществлена отправка</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;arFields</i></span></td> <td>Массив данных о заказе, которые будут подставлены в почтовый шаблон</td>
</tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleOrder::RemindPayment
	 */
	public static function OnOrderRemindSendEmail($ID, &$eventName, &$arFields){}

	/**
	 * Вызывается перед отправкой письма о добавлении заказа на продление подписки, может быть использовано для модификации данных, изменения идентификатора типа почтового события, по которому будет осуществлена отправка, и отмены отправки письма. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;eventName</i></span></td> <td>Тип почтового события по которому будет осуществлена отправка</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;arFields</i></span></td> <td>Массив данных о заказе, которые будут подставлены в почтовый шаблон</td>
</tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleOrder::RemindPayment
	 */
	public static function OnOrderRecurringSendEmail($ID, &$eventName, &$arFields){}

	/**
	 * Вызывается перед отправкой письма об отмене заказа на продление подписки, может быть использовано для модификации данных, изменения идентификатора типа почтового события, по которому будет осуществлена отправка, и отмены отправки письма. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;eventName</i></span></td> <td>Тип почтового события по которому будет осуществлена отправка</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;arFields</i></span></td> <td>Массив данных о заказе, которые будут подставлены в почтовый шаблон</td>
</tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleRecurring::CancelRecurring
	 */
	public static function OnOrderRecurringCancelSendEmail($ID, &$eventName, &$arFields){}

	/**
	 * Вызывается в компоненте <b>bitrix:sale.order.ajax</b> после формирования списка доступных типов плательщика, может быть использовано для модификации данных.
	 *     <br><b>Параметры</b> 		
	 *     <table class="tnormal" width="100%"><tbody>
<tr>
<td><span class="syntax"><i>&amp;arResult</i></span></td> 	<td>Массив arResult компонента</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;arUserResult</i></span></td> <td>Массив arUserResult компонента, содержащий текущие выбранные пользовательские данные.</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnSaleComponentOrderOneStepPersonType(&$arResult, &$arUserResult){}

	/**
	 * Вызывается в компоненте <b>bitrix:sale.order.ajax</b> после формирования списка доступных свойств заказа, может быть использовано для модификации данных.
	 *     <br><b>Параметры</b> 		
	 *     <table class="tnormal" width="100%"><tbody>
<tr>
<td><span class="syntax"><i>&amp;arResult</i></span></td> 	<td>Массив arResult компонента</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;arUserResult</i></span></td> <td>Массив arUserResult компонента, содержащий текущие выбранные пользовательские данные</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnSaleComponentOrderOneStepOrderProps(&$arResult, &$arUserResult){}

	/**
	 * Вызывается в компоненте <b>bitrix:sale.order.ajax</b> после формирования списка доступных служб доставки, может быть использовано для модификации данных.
	 *     <br><b>Параметры</b> 		
	 *     <table class="tnormal" width="100%"><tbody>
<tr>
<td><span class="syntax"><i>&amp;arResult</i></span></td> <td>Массив arResult компонента</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;arUserResult</i></span></td> <td>Массив arUserResult компонента, содержащий текущие выбранные пользовательские данные</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnSaleComponentOrderOneStepDelivery(&$arResult, &$arUserResult){}

	/**
	 * Вызывается в компоненте <b>bitrix:sale.order.ajax</b> после формирования списка доступных платежных систем, может быть использовано для модификации данных.
	 *     <br><b>Параметры</b> 		
	 *     <table class="tnormal" width="100%"><tbody>
<tr>
<td><span class="syntax"><i>&amp;arResult</i></span></td> <td>Массив arResult компонента</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;arUserResult</i></span></td> <td>Массив arUserResult компонента, содержащий текущие выбранные пользовательские данные.</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnSaleComponentOrderOneStepPaySystem(&$arResult, &$arUserResult){}

	/**
	 * Вызывается в компоненте <b>bitrix:sale.order.ajax</b> после формирования всех данных компонента на этапе заполнения формы заказа, может быть использовано для модификации данных.
	 *     <br><b>Параметры</b> 		
	 *     <table class="tnormal" width="100%"><tbody>
<tr>
<td><span class="syntax"><i>&amp;arResult</i></span></td> 	<td>Массив arResult компонента</td> </tr>
<tr>
<td><span class="syntax"><i>&amp;arUserResult</i></span></td> <td>Массив arUserResult компонента, содержащий текущие выбранные пользовательские данные</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnSaleComponentOrderOneStepProcess(&$arResult, &$arUserResult){}

	/**
	 * Вызывается в компоненте <b>bitrix:sale.order.ajax</b> после создания заказа и всех его параметров, после отправки письма, но до редиректа на страницу с информацией о созданном заказе и оплате заказа.
	 *     <br><b>Параметры</b> 		
	 *     <table class="tnormal" width="100%"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>arOrder</i></span></td> <td>Массив полей заказа</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleOrder::DoSaveOrder
	 */
	public static function OnSaleComponentOrderOneStepComplete($ID, $arOrder){}

	/**
	 * Вызывается в компоненте <b>bitrix:sale.order.ajax</b> после создания заказа и всех его параметров, после отправки письма, перед выводом страницы об успешно созданном заказе и оплате заказа.
	 *     <br><b>Параметры</b> 		
	 *     <table class="tnormal" width="100%"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>arOrder</i></span></td> <td>Массив полей заказа</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnSaleComponentOrderOneStepFinal($ID, $arOrder){}

	/**
	 * Вызывается в компоненте <b>bitrix:sale.order.full</b> после создания заказа и всех его параметров.
	 *     <br><b>Параметры</b> 		
	 *     <table class="tnormal" width="100%"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор заказа</td> </tr>
<tr>
<td><span class="syntax"><i>arOrder</i></span></td> <td>Массив полей заказа</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnSaleComponentOrderComplete($ID, $arOrder){}

	/**
	 * Вызывается в компоненте  <b>bitrix:sale.order.ajax</b> перед подсчётом скидки при оформлении заказа. Можно использовать в том числе для присвоения/отъёма у пользователя купонов соответствующих скидок для расчёта индивидуальной скидки.
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>arResult</i></span></td> <td>Массив параметров заказа</td> 			</tr>
<tr>
<td><span class="syntax"><i>arUserResult</i></span></td> <td>Массив параметров пользователя</td> 			</tr>
<tr>
<td><span class="syntax"><i>arParams</i></span></td> <td>Массив параметров компонента</td> 			</tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnSaleComponentOrderOneStepDiscountBefore($arResult, $arUserResult, $arParams){}

	/**
	 * Вызывается при формировании фильтра для списка заказов в административной части. Позволяет модифицировать значения фильтра. Для этого обработчик события должен вернуть модифицированный массив фильтра. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>arFilter</i></span></td> <td>Сформированный фильтр</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnOrderListFilter($arFilter){}

	/**
	 * Вызывается при формировании фильтра для выбора товаров для заказа. Позволяет модифицировать значения фильтра. Для этого обработчик события должен вернуть модифицированный массив фильтра. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>arFilter</i></span></td> <td>Сформированный фильтр</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnProductSearchFormIBlock($arFilter){}

	/**
	 * Вызывается для каждого товара в форме поиска товара. Возвращаемое значение - массив (в формате JScript) новых параметров товара. Может использоваться, если товары в заказе должны иметь не те параметры, которые они имеют в каталоге. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Код товара</td> </tr>
<tr>
<td><span class="syntax"><i>arParams</i></span></td> <td>Текущее значение, передающееся в качестве параметров товара (массив в формате JScript)</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnProductSearchForm($ID, $arParams){}

	/**
	 * Вызывается в методе CSaleAffiliate::CalculateAffiliate, если модуль товара не catalog. Позволяет задать секции для товара, расположенного не в модуле каталог, используемые для планов аффилиатов. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>MODULE</i></span></td> <td>Идентификатор модуля товара корзины</td> </tr>
<tr>
<td><span class="syntax"><i>PRODUCT_ID</i></span></td> <td>Идентификатор товара</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleAffiliate::CalculateAffiliate
	 */
	public static function OnAffiliateGetSections($MODULE, $PRODUCT_ID){}

	/**
	 * Вызывается при удалении пользователя Интернет-магазина. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор пользователя Интернет-магазина</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleUser::Delete
	 */
	public static function OnSaleUserDelete($ID){}

	/**
	 * Системное, не описывается.
	 *       
	 * 
	 * <i>Вызывается в методе:</i>
	 * CSaleDeliveryHandler::__getRegisteredHandlers
	 */
	public static function onSaleDeliveryHandlersBuildList(){}


}
?>