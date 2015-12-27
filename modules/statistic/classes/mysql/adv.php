<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/classes/general/adv.php");

/**
 * <b>CAdv</b> - класс для работы с <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv">рекламными кампаниями</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cadv/index.php
 * @author Bitrix
 */
class CAdv extends CAllAdv
{
	public static function GetAnalysisGraphArray_SQL($strSqlSearch, $DATA_TYPE)
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		switch ($DATA_TYPE)
		{
			case "EVENT_SUMMA":
			case "EVENT":
			case "EVENT_BACK":
			case "EVENT_MONEY_SUMMA":
			case "EVENT_MONEY":
			case "EVENT_MONEY_BACK":
				$strSql = "
					SELECT
						".$DB->DateToCharFunction("D.DATE_STAT","SHORT")." DATE_STAT,
						DAYOFMONTH(D.DATE_STAT) DAY,
						MONTH(D.DATE_STAT) MONTH,
						YEAR(D.DATE_STAT) YEAR,
						sum(D.COUNTER) EVENTS,
						sum(D.COUNTER_BACK) EVENTS_BACK,
						sum(D.MONEY) MONEY,
						sum(D.MONEY_BACK) MONEY_BACK,
						D.ADV_ID,
						A.REFERER1,
						A.REFERER2
					FROM
						b_stat_adv_event_day D
					INNER JOIN b_stat_event E ON (E.ID = D.EVENT_ID)
					INNER JOIN b_stat_adv A ON (A.ID = D.ADV_ID)
					WHERE
						$strSqlSearch
					GROUP BY
						D.DATE_STAT, D.ADV_ID, A.REFERER1, A.REFERER2
					ORDER BY
						D.DATE_STAT
					";
				break;
			default:
				$strSql = "
					SELECT
						".$DB->DateToCharFunction("D.DATE_STAT","SHORT")."	DATE_STAT,
						DAYOFMONTH(D.DATE_STAT)					DAY,
						MONTH(D.DATE_STAT)					MONTH,
						YEAR(D.DATE_STAT)					YEAR,
						max(D.GUESTS_DAY)					GUESTS,
						max(D.NEW_GUESTS)					NEW_GUESTS,
						max(D.FAVORITES)					FAVORITES,
						max(D.C_HOSTS_DAY)					C_HOSTS,
						max(D.SESSIONS)						SESSIONS,
						max(D.HITS)						HITS,
						max(D.GUESTS_DAY_BACK)					GUESTS_BACK,
						max(D.FAVORITES_BACK)					FAVORITES_BACK,
						max(D.HOSTS_DAY_BACK)					HOSTS_BACK,
						max(D.SESSIONS_BACK)					SESSIONS_BACK,
						max(D.HITS_BACK)					HITS_BACK,
						D.ADV_ID,
						A.REFERER1,
						A.REFERER2
					FROM
						b_stat_adv_day D
					INNER JOIN b_stat_adv A ON (A.ID = D.ADV_ID)
					WHERE
						$strSqlSearch
					GROUP BY
						D.DATE_STAT, D.ADV_ID, A.REFERER1, A.REFERER2
					ORDER BY
						D.DATE_STAT
					";
				break;
		}
		return $strSql;
	}

	
	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv">рекламных кампаний</a> (РК) с рассчитанными статистическими показателями и со всеми данными по <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_traffic">трафику</a>.</p>
	*
	*
	* @param string &$by = "SESSIONS" Поле для сортировки. Возможные значения: <ul> <li> <b>ID</b> - ID РК; </li> <li>
	* <b>PRIORITY</b> - приоритет; </li> <li> <b>REFERER1</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_id">идентификатор</a> referer1 РК; </li>
	* <li> <b>REFERER2</b> - идентификатор referer2 РК; </li> <li> <b>C_TIME_FIRST</b> - время
	* начала РК (первый <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_first">прямой
	* заход</a>); </li> <li> <b>C_TIME_LAST</b> - последний прямой заход или <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_back">возврат</a> по РК; </li> <li>
	* <b>ADV_TIME</b> - длительность РК (разница <b>C_TIME_LAST</b> - <b>C_TIME_FIRST</b>); </li> <li>
	* <b>ATTENT</b> - коэфициент <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_attent">внимательности</a> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителей</a> на прямом
	* заходе по РК; </li> <li> <b>ATTENT_BACK</b> - коэфициент <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_attent">внимательности</a>
	* посетителей на возврате по РК; </li> <li> <b>NEW_VISITORS</b> - процент
	* посетителей впервые пришедших на сайт по данной РК от общего
	* количества посетителей пришедших по данной РК; </li> <li>
	* <b>RETURNED_VISITORS</b> - процент посетителей возвратившихся на сайт после
	* прямого захода по данной РК; </li> <li> <b>VISITORS_PER_DAY</b> - среднее
	* количество посетителей за день; </li> <li> <b>COST</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_cost">затраты</a> на РК; </li> <li>
	* <b>REVENUE</b> - <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_revenue">доходы</a> с РК;
	* </li> <li> <b>BENEFIT</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_benefit">прибыль</a> РК; </li> <li> <b>ROI</b> -
	* <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_roi">рентабельность</a> РК; </li> <li>
	* <b>SESSION_COST</b> - средняя стоимость <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">сессии</a> (затраты/кол-во
	* сессий на прямом заходе); </li> <li> <b>VISITOR_COST</b> - средняя стоимость
	* посетителя (затраты/кол-во посетителей на прямых заходах); </li> <li>
	* <b>GUESTS</b> - суммарное кол-во посетителей на прямых заходах; </li> <li>
	* <b>GUESTS_BACK</b> - суммарное кол-во посетителей на возвратах; </li> <li>
	* <b>NEW_GUESTS</b> - суммарное кол-во <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#new_guest">новых посетителей</a> по
	* данной РК; </li> <li> <b>FAVORITES</b> - суммарное кол-во посетителей,
	* добавивших сайт в "<a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#favorites">Избранное</a>" на прямом
	* заходе по РК; </li> <li> <b>FAVORITES_BACK</b> - суммарное кол-во посетителей,
	* добавивших сайт в "Избранное" на возврате по РК; </li> <li> <b>C_HOSTS</b> -
	* суммарное кол-во <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#host">хостов</a> на
	* прямом заходе по РК; </li> <li> <b>HOSTS_BACK</b> - суммарное кол-во хостов на
	* возврате по РК; </li> <li> <b>SESSIONS</b> - суммарное кол-во сессий на прямом
	* заходе по РК; </li> <li> <b>SESSIONS_BACK</b> - суммарное кол-во сессий на
	* возврате по РК; </li> <li> <b>HITS</b> - суммарное кол-во <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">хитов</a> на прямом заходе по РК;
	* </li> <li> <b>HITS_BACK</b> - суммарное кол-во хитов на возврате по РК; </li> <li>
	* <b>GUESTS_TODAY</b> - кол-во посетителей на прямом заходе за сегодня; </li> <li>
	* <b>GUESTS_BACK_TODAY</b> - кол-во посетителей на возврате за сегодня; </li> <li>
	* <b>NEW_GUESTS_TODAY</b> - кол-во новых посетителей за сегодня; </li> <li>
	* <b>FAVORITES_TODAY</b> - кол-во посетителей, добавивших сайт в "Избранное" на
	* прямом заходе за сегодня; </li> <li> <b>FAVORITES_BACK_TODAY</b> - кол-во
	* посетителей, добавивших сайт в "Избранное" на возврате за сегодня;
	* </li> <li> <b>C_HOSTS_TODAY</b> - кол-во хостов на прямом заходе за сегодня; </li>
	* <li> <b>HOSTS_BACK_TODAY</b> - кол-во хостов на возврате за сегодня; </li> <li>
	* <b>SESSIONS_TODAY</b> - кол-во сессий на прямом заходе за сегодня; </li> <li>
	* <b>SESSIONS_BACK_TODAY</b> - кол-во сессий на возврате за сегодня; </li> <li>
	* <b>HITS_TODAY</b> - кол-во хитов на прямом заходе за сегодня; </li> <li>
	* <b>HITS_BACK_TODAY</b> - кол-во хитов на возврате за сегодня; </li> <li>
	* <b>GUESTS_YESTERDAY</b> - кол-во посетителей на прямом заходе за вчера; </li> <li>
	* <b>GUESTS_BACK_YESTERDAY</b> - кол-во посетителей на возврате за вчера; </li> <li>
	* <b>NEW_GUESTS_YESTERDAY</b> - кол-во новых посетителей за вчера; </li> <li>
	* <b>FAVORITES_YESTERDAY</b> - кол-во посетителей, добавивших сайт в "Избранное"
	* на прямом заходе за вчера; </li> <li> <b>FAVORITES_BACK_YESTERDAY</b> - кол-во
	* посетителей, добавивших сайт в "Избранное" на возврате за вчера;
	* </li> <li> <b>C_HOSTS_YESTERDAY</b> - кол-во хостов на прямом заходе за вчера; </li>
	* <li> <b>HOSTS_BACK_YESTERDAY</b> - кол-во хостов на возврате за вчера; </li> <li>
	* <b>SESSIONS_YESTERDAY</b> - кол-во сессий на прямом заходе за вчера; </li> <li>
	* <b>SESSIONS_BACK_YESTERDAY</b> - кол-во сессий на возврате за вчера; </li> <li>
	* <b>HITS_YESTERDAY</b> - кол-во хитов на прямом заходе за вчера; </li> <li>
	* <b>HITS_BACK_YESTERDAY</b> - кол-во хитов на возврате за вчера; </li> <li>
	* <b>GUESTS_BEF_YESTERDAY</b> - кол-во посетителей на прямом заходе за позавчера;
	* </li> <li> <b>GUESTS_BACK_BEF_YESTERDAY</b> - кол-во посетителей на возврате за
	* позавчера; </li> <li> <b>NEW_GUESTS_BEF_YESTERDAY</b> - кол-во новых посетителей за
	* позавчера; </li> <li> <b>FAVORITES_BEF_YESTERDAY</b> - кол-во посетителей, добавивших
	* сайт в "Избранное" на прямом заходе за позавчера; </li> <li>
	* <b>FAVORITES_BACK_BEF_YESTERDAY</b> - кол-во посетителей, добавивших сайт в
	* "Избранное" на возврате за позавчера; </li> <li> <b>C_HOSTS_BEF_YESTERDAY</b> -
	* кол-во хостов на прямом заходе за позавчера; </li> <li>
	* <b>HOSTS_BACK_BEF_YESTERDAY</b> - кол-во хостов на возврате за позавчера; </li> <li>
	* <b>SESSIONS_BEF_YESTERDAY</b> - кол-во сессий на прямом заходе за позавчера; </li>
	* <li> <b>SESSIONS_BACK_BEF_YESTERDAY</b> - кол-во сессий на возврате за позавчера; </li>
	* <li> <b>HITS_BEF_YESTERDAY</b> - кол-во хитов на прямом заходе за позавчера; </li>
	* <li> <b>HITS_BACK_BEF_YESTERDAY</b> - кол-во хитов на возврате за позавчера; </li> <li>
	* <b>GUESTS_PERIOD</b> - кол-во посетителей на прямом заходе за установленный
	* в фильтре (<i>filter</i>) интервал времени; </li> <li> <b>GUESTS_BACK_PERIOD</b> - кол-во
	* посетителей на возврате за установленный в фильтре интервал
	* времени; </li> <li> <b>NEW_GUESTS_PERIOD</b> - кол-во новых посетителей за
	* установленный в фильтре интервал времени; </li> <li> <b>FAVORITES_PERIOD</b> -
	* кол-во посетителей, добавивших сайт в "Избранное" на прямом заходе
	* за установленный в фильтре интервал времени; </li> <li>
	* <b>FAVORITES_BACK_PERIOD</b> - кол-во посетителей, добавивших сайт в "Избранное"
	* на возврате за установленный в фильтре интервал времени; </li> <li>
	* <b>C_HOSTS_PERIOD</b> - кол-во хостов на прямом заходе за установленный в
	* фильтре интервал времени; </li> <li> <b>HOSTS_BACK_PERIOD</b> - кол-во хостов на
	* возврате за установленный в фильтре интервал времени; </li> <li>
	* <b>SESSIONS_PERIOD</b> - кол-во сессий на прямом заходе за установленный в
	* фильтре интервал времени; </li> <li> <b>SESSIONS_BACK_PERIOD</b> - кол-во сессий на
	* возврате за установленный в фильтре интервал времени; </li> <li>
	* <b>HITS_PERIOD</b> - кол-во хитов на прямом заходе за установленный в
	* фильтре интервал времени; </li> <li> <b>HITS_BACK_PERIOD</b> - кол-во хитов на
	* возврате за установленный в фильтре интервал времени. </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>GROUP</b> - список возможных
	* значений: <ul> <li> <b>referer1</b> - список РК будет сгруппирован по <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_id">идентификатору</a> referer1 РК; </li>
	* <li> <b>referer2</b> - список РК будет сгруппирован по <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_id">идентификатору</a> referer2 РК. </li>
	* </ul> </li> <li> <b>ID</b>* - ID РК; </li> <li> <b>ID_EXACT_MATCH</b> - если значение равно "N", то
	* при фильтрации по <b>ID</b> будет искаться вхождение; </li> <li>
	* <b>DATE1_PERIOD</b> - начальное значение <i>периода</i> за который необходимо
	* получить данные; </li> <li> <b>DATE2_PERIOD</b> - конечное значение <i>периода</i>
	* за который необходимо получить данные; </li> <li> <b>DATE1_FIRST</b> -
	* начальное значение интервала для поля "время начала РК"; </li> <li>
	* <b>DATE2_FIRST</b> - конечное значение интервала для поля "время начала
	* РК"; </li> <li> <b>DATE1_LAST</b> - начальное значение интервала для поля "время
	* окончания РК"; </li> <li> <b>DATE2_LAST</b> - конечное значение интервала для
	* поля "время окончания РК"; </li> <li> <b>REFERER1</b>* - идентификатор referer1 РК;
	* </li> <li> <b>REFERER1_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>REFERER1</b> будет искаться точное совпадение; </li> <li> <b>REFERER2</b>* -
	* идентификатор referer2 РК; </li> <li> <b>REFERER2_EXACT_MATCH</b> - если значение равно
	* "Y", то при фильтрации по <b>REFERER2</b> будет искаться точное
	* совпадение; </li> <li> <b>PRIORITY1</b> - начальное значение интервала для
	* поля "приоритет РК"; </li> <li> <b>PRIORITY2</b> - конечное значение интервала
	* для поля "приоритет РК"; </li> <li> <b>NEW_GUESTS1</b> - начальное значение
	* интервала для поля "новые посетители РК"; </li> <li> <b>NEW_GUESTS2</b> -
	* конечное значение интервала для поля "новые посетители РК"; </li> <li>
	* <b>GUESTS_BACK</b> - флаг означающий по какому полю фильтровать
	* посетителей, список возможных значений: <ul> <li> <b>N</b> - на прямом
	* заходе; </li> <li> <b>Y</b> - на возврате. </li> </ul> </li> <li> <b>GUESTS1</b> - начальное
	* значение интервала для поля "посетители на прямом заходе или на
	* возврате" (в зависимости от флага <b>GUESTS_BACK</b>); </li> <li> <b>GUESTS2</b> -
	* конечное значение интервала для поля "посетители на прямом
	* заходе или на возврате" (в зависимости от флага <b>GUESTS_BACK</b>); </li> <li>
	* <b>FAVORITES_BACK</b> - флаг означающий по какому полю фильтровать
	* посетителей, добавившие сайт в "Избранное", список возможных
	* значений: <ul> <li> <b>N</b> - на прямом заходе; </li> <li> <b>Y</b> - на возврате.
	* </li> </ul> </li> <li> <b>FAVORITES1</b> - начальное значение интервала для поля
	* "посетители, добавившие сайт в Избранное на прямом заходе или
	* возврате" (в зависимости от флага <b>FAVORITES_BACK</b>); </li> <li> <b>FAVORITES2</b> -
	* конечное значение интервала для поля "посетители, добавившие
	* сайт в Избранное на прямом заходе или возврате" (в зависимости от
	* флага <b>FAVORITES_BACK</b>); </li> <li> <b>HOSTS_BACK</b> - флаг означающий по какому
	* полю фильтровать хосты, список возможных значений: <ul> <li> <b>N</b> - на
	* прямом заходе; </li> <li> <b>Y</b> - на возврате. </li> </ul> </li> <li> <b>HOSTS1</b> -
	* начальное значение интервала для поля "хосты на прямом заходе или
	* возврате" (в зависимости от флага <b>HOSTS_BACK</b>); </li> <li> <b>HOSTS2</b> -
	* конечное значение интервала для поля "хосты на прямом заходе или
	* возврате" (в зависимости от флага <b>HOSTS_BACK</b>); </li> <li> <b>SESSIONS_BACK</b> -
	* флаг означающий по какому полю фильтровать сессии, список
	* возможных значений: <ul> <li> <b>N</b> - на прямом заходе; </li> <li> <b>Y</b> - на
	* возврате. </li> </ul> </li> <li> <b>SESSIONS1</b> - начальное значение интервала
	* для поля "сессии на прямом заходе или возврате" (в зависимости от
	* флага <b>SESSIONS_BACK</b>); </li> <li> <b>SESSIONS2</b> - конечное значение интервала
	* для поля "сессии на прямом заходе или возврате" (в зависимости от
	* флага <b>SESSIONS_BACK</b>); </li> <li> <b>HITS_BACK</b> - флаг означающий по какому полю
	* фильтровать хиты, список возможных значений: <ul> <li> <b>N</b> - на
	* прямом заходе; </li> <li> <b>Y</b> - на возврате. </li> </ul> </li> <li> <b>HITS1</b> -
	* начальное значение интервала для поля "хиты на прямом заходе или
	* возврате" (в зависимости от флага <b>HITS_BACK</b>); </li> <li> <b>HITS2</b> -
	* конечное значение интервала для поля "хиты на прямом заходе или
	* возврате" (в зависимости от флага <b>HITS_BACK</b>); </li> <li> <b>COST1</b> -
	* начальное значение интервала для поля "затраты на РК"; </li> <li>
	* <b>COST2</b> - конечное значение интервала для поля "затраты на РК"; </li>
	* <li> <b>REVENUE1</b> - начальное значение интервала для поля "доходы с РК";
	* </li> <li> <b>REVENUE2</b> - конечное значение интервала для поля "доходы с
	* РК"; </li> <li> <b>BENEFIT1</b> - начальное значение интервала для поля
	* "прибыль с РК"; </li> <li> <b>BENEFIT2</b> - конечное значение интервала для
	* поля "прибыль с РК"; </li> <li> <b>ROI1</b> - начальное значение интервала
	* для поля "рентабельность РК"; </li> <li> <b>ROI2</b> - конечное значение
	* интервала для поля "рентабельность РК"; </li> <li> <b>ATTENT1</b> - начальное
	* значение интервала для поля "коэфициент внимательности
	* посетителей РК"; </li> <li> <b>ATTENT2</b> - конечное значение интервала для
	* поля "коэфициент внимательности посетителей РК"; </li> <li>
	* <b>VISITORS_PER_DAY1</b> - начальное значение интервала для поля "среднее
	* кол-во посетителей в день"; </li> <li> <b>VISITORS_PER_DAY2</b> - конечное значение
	* интервала для поля "среднее кол-во посетителей в день"; </li> <li>
	* <b>DURATION1</b> - начальное значение интервала для поля "длительность
	* РК"; </li> <li> <b>DURATION2</b> - конечное значение интервала для поля
	* "длительность РК"; </li> <li> <b>CURRENCY</b> - валюта в которой заданы
	* финансовые показатели РК; </li> <li> <b>DESCRIPTION</b>* - описание РК; </li> <li>
	* <b>DESCRIPTION_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>DESCRIPTION</b> будет искаться точное совпадение. </li> </ul> * - допускается
	* <a href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная логика</a>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности списка рекламных кампаний. Если значение
	* равно "true", то список был отфильтрован.
	*
	* @param mixed $limit = "" Максимальное число РК которые будут выбраны в списке. Если
	* значение равно "", то кол-во РК будет ограничено в соответствии со
	* значением параметра "Максимальное кол-во показываемых записей в
	* таблицах" из настроек модуля "Статистика".
	*
	* @param array &$referer_group  Ссылка на массив инициализируемый только при установленной
	* группировке по referer1 или referer2 (если <i>filter</i>["GROUP"]="referer1" или
	* <i>filter</i>["GROUP"]="referer2"). Структура данного массива: <pre> [<i>referer1</i> или
	* <i>referer2</i>] =&gt; Array ( [REFERER1] =&gt; <i>referer1</i> или [REFERER2] =&gt; <i>referer2</i> [GUESTS_TODAY]
	* =&gt; посетителей на прямом заходе за сегодня [GUESTS_BACK_TODAY] =&gt;
	* посетителей на возврате за сегодня [NEW_GUESTS_TODAY] =&gt; новых
	* посетителей за сегодня [FAVORITES_TODAY] =&gt; посетителей, добавивших сайт
	* в "Избранное" на прямом заходе за сегодня [FAVORITES_BACK_TODAY] =&gt;
	* посетителей, добавившие сайт в "Избранное" на возврате за сегодня
	* [C_HOSTS_TODAY] =&gt; хостов на прямом заходе за сегодня [HOSTS_BACK_TODAY] =&gt;
	* хостов на возврате за сегодня [SESSIONS_TODAY] =&gt; сессий на прямом
	* заходе за сегодня [SESSIONS_BACK_TODAY] =&gt; сессий на возврате за сегодня
	* [HITS_TODAY] =&gt; хитов на прямом заходе за сегодня [HITS_BACK_TODAY] =&gt; хитов на
	* возврате за сегодня [GUESTS_YESTERDAY] =&gt; посетителей на возврате за
	* вчера [GUESTS_BACK_YESTERDAY] =&gt; посетителей на возврате за вчера
	* [NEW_GUESTS_YESTERDAY] =&gt; новых посетителей за вчера [FAVORITES_YESTERDAY] =&gt;
	* посетителей, добавившие сайт в "Избранное" на прямом заходе за
	* вчера [FAVORITES_BACK_YESTERDAY] =&gt; посетителей, добавившие сайт в
	* "Избранное" на возврате за вчера [C_HOSTS_YESTERDAY] =&gt; хостов на прямом
	* заходе за вчера [HOSTS_BACK_YESTERDAY] =&gt; хостов на возврате за вчера
	* [SESSIONS_YESTERDAY] =&gt; сессий на прямом заходе за вчера [SESSIONS_BACK_YESTERDAY] =&gt;
	* сессий на возврате за вчера [HITS_YESTERDAY] =&gt; хитов на прямом заходе за
	* вчера [HITS_BACK_YESTERDAY] =&gt; хитов на возврате за вчера [GUESTS_BEF_YESTERDAY] =&gt;
	* посетителей на прямом заходе за позавчера [NEW_GUESTS_BEF_YESTERDAY] =&gt;
	* новых посетителей за позавчера [FAVORITES_BEF_YESTERDAY] =&gt; посетителей,
	* добавившие сайт в "Избранное" на прямом заходе за позавчера
	* [C_HOSTS_BEF_YESTERDAY] =&gt; хостов на прямом заходе за позавчера
	* [SESSIONS_BEF_YESTERDAY] =&gt; сессий на прямом заходе за позавчера [HITS_BEF_YESTERDAY]
	* =&gt; хитов на прямом заходе за позавчера [GUESTS_BACK_BEF_YESTERDAY] =&gt;
	* посетителей на возврате за позавчера [FAVORITES_BACK_BEF_YESTERDAY] =&gt;
	* посетителей, добавившие сайт в "Избранное" на возврате за
	* позавчера [HOSTS_BACK_BEF_YESTERDAY] =&gt; хостов на возврате за позавчера
	* [SESSIONS_BACK_BEF_YESTERDAY] =&gt; сессий на возврате за позавчера [HITS_BACK_BEF_YESTERDAY]
	* =&gt; хитов на возврате за позавчера [GUESTS_PERIOD] =&gt; посетителей на
	* прямом заходе за период времени (установка периода времени
	* осушествляется инициализацией <i>filter</i>["DATE1_PERIOD"] и/или
	* <i>filter</i>["DATE2_PERIOD"]) [GUESTS_BACK_PERIOD] =&gt; посетителей на возврате за период
	* времени [NEW_GUESTS_PERIOD] =&gt; новые посетители на прямом заходе за
	* период времени [C_HOSTS_PERIOD] =&gt; хосты на прямом заходе за период
	* времени [HOSTS_BACK_PERIOD] =&gt; хостов на возврате за период времени
	* [FAVORITES_PERIOD] =&gt; посетителей, добавившие сайт в "Избранное" на прямом
	* заходе за период времени [FAVORITES_BACK_PERIOD] =&gt; посетителей, добавившие
	* сайт в "Избранное" на возврате за период времени [SESSIONS_PERIOD] =&gt;
	* сессий на прямом заходе за период времени [SESSIONS_BACK_PERIOD] =&gt; сессий
	* на возврате за период времени [HITS_PERIOD] =&gt; хитов на прямом заходе
	* за период времени [HITS_BACK_PERIOD] =&gt; хитов на возврате за период
	* времени ) </pre>
	*
	* @param string &$sql  Ссылка на результирующий SQL запрос по которому будет выбран
	* список РК.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // отфильтруем по рекламным кампаниям в referer1 которых входит "google"
	* // а также получим дополнительные данные за декабрь 2005 года
	* $arFilter = array(
	*     "REFERER1"     =&gt; "google",
	*     "DATE1_PERIOD" =&gt; "01.12.2005",
	*     "DATE2_PERIOD" =&gt; "31.12.2005"
	*     );
	* 
	* // получим список записей
	* $rs = <b>CAdv::GetList</b>(
	*     ($by="SESSIONS"), 
	*     ($order="desc"), 
	*     $arFilter, 
	*     $is_filtered,
	*     "",
	*     $referer_group,
	*     $sql
	*     );
	* 
	* // выведем все записи
	* while ($ar = $rs-&gt;Fetch())
	* {
	*     echo "&lt;pre&gt;"; print_r($ar); echo "&lt;/pre&gt;";    
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv">Термин "Рекламная
	* кампания"</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cadv/getsimplelist.php">CAdv::GetSimpleList</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cadv/getdropdownlist.php">CAdv::GetDropdownList</a> </li> </ul>
	* <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cadv/getlist.php
	* @author Bitrix
	*/
	public static function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered, $limit="", &$arrGROUP_DAYS, &$strSql_res)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$find_group = $arFilter["GROUP"];
		$arSqlSearch = Array();
		$arSqlSearch_h = Array();
		$strSqlSearch_h = "";
		$filter_period = false;
		$strSqlPeriod = "";
		$strT = "";
		$CURRENCY = "";

		if (is_array($arFilter))
		{
			$date1 = $arFilter["DATE1_PERIOD"];
			$date2 = $arFilter["DATE2_PERIOD"];
			$date_from = MkDateTime(ConvertDateTime($date1,"D.M.Y"),"d.m.Y");
			$date_to = MkDateTime(ConvertDateTime($date2,"D.M.Y")." 23:59","d.m.Y H:i");
			if (strlen($date1)>0)
			{
				$filter_period = true;
				if (strlen($date2)>0)
				{
					$strSqlPeriod = "sum(if(D.DATE_STAT<FROM_UNIXTIME('$date_from'),0, if(D.DATE_STAT>FROM_UNIXTIME('$date_to'),0,";
					$strT = ")))";
				}
				else
				{
					$strSqlPeriod = "sum(if(D.DATE_STAT<FROM_UNIXTIME('$date_from'),0,";
					$strT = "))";
				}
			}
			elseif (strlen($date2)>0)
			{
				$filter_period = true;
				$strSqlPeriod = "sum(if(D.DATE_STAT>FROM_UNIXTIME('$date_to'),0,";
				$strT = "))";
			}

			foreach ($arFilter as $key => $val)
			{
				if(is_array($val))
				{
					if(count($val) <= 0)
						continue;
				}
				else
				{
					if( (strlen($val) <= 0) || ($val === "NOT_REF") )
						continue;
				}
				$match_value_set = array_key_exists($key."_EXACT_MATCH", $arFilter);
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("A.".$key,$val,$match);
						break;
					case "DATE1_FIRST":
						if (CheckDateTime($val))
							$arSqlSearch_h[] = "C_TIME_FIRST >= ".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2_FIRST":
						if (CheckDateTime($val))
							$arSqlSearch_h[] = "C_TIME_FIRST < ".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "DATE1_LAST":
						if (CheckDateTime($val))
							$arSqlSearch_h[] = "C_TIME_LAST >= ".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2_LAST":
						if (CheckDateTime($val))
							$arSqlSearch_h[] = "C_TIME_LAST < ".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "REFERER1":
					case "REFERER2":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("A.".$key, $val, $match);
						break;
					case "PRIORITY1":
						$arSqlSearch[] = "A.PRIORITY>='".intval($val)."'";
						break;
					case "PRIORITY2":
						$arSqlSearch[] = "A.PRIORITY<='".intval($val)."'";
						break;
					case "NEW_GUESTS1":
						$arSqlSearch_h[] = "NEW_GUESTS>='".intval($val)."'";
						break;
					case "NEW_GUESTS2":
						$arSqlSearch_h[] = "NEW_GUESTS<='".intval($val)."'";
						break;
					case "GUESTS1":
						if ($arFilter["GUESTS_BACK"]=="Y")
							$arSqlSearch_h[] = "GUESTS_BACK>='".intval($val)."'";
						else
							$arSqlSearch_h[] = "GUESTS>='".intval($val)."'";
						break;
					case "GUESTS2":
						if ($arFilter["GUESTS_BACK"]=="Y")
							$arSqlSearch_h[] = "GUESTS_BACK<='".intval($val)."'";
						else
							$arSqlSearch_h[] = "GUESTS<='".intval($val)."'";
						break;
					case "FAVORITES1":
						if ($arFilter["FAVORITES_BACK"]=="Y")
							$arSqlSearch_h[] = "FAVORITES_BACK>='".intval($val)."'";
						else
							$arSqlSearch_h[] = "FAVORITES>='".intval($val)."'";
						break;
					case "FAVORITES2":
						if ($arFilter["FAVORITES_BACK"]=="Y")
							$arSqlSearch_h[] = "FAVORITES_BACK<='".intval($val)."'";
						else
							$arSqlSearch_h[] = "FAVORITES<='".intval($val)."'";
						break;
					case "HOSTS1":
						if ($arFilter["HOSTS_BACK"]=="Y")
							$arSqlSearch_h[] = "HOSTS_BACK>='".intval($val)."'";
						else
							$arSqlSearch_h[] = "C_HOSTS>='".intval($val)."'";
						break;
					case "HOSTS2":
						if ($arFilter["HOSTS_BACK"]=="Y")
							$arSqlSearch_h[] = "HOSTS_BACK<='".intval($val)."'";
						else
							$arSqlSearch_h[] = "C_HOSTS<='".intval($val)."'";
						break;
					case "SESSIONS1":
						if ($arFilter["SESSIONS_BACK"]=="Y")
							$arSqlSearch_h[] = "SESSIONS_BACK>='".intval($val)."'";
						else
							$arSqlSearch_h[] = "SESSIONS>='".intval($val)."'";
						break;
					case "SESSIONS2":
						if ($arFilter["SESSIONS_BACK"]=="Y")
							$arSqlSearch_h[] = "SESSIONS_BACK<='".intval($val)."'";
						else
							$arSqlSearch_h[] = "SESSIONS<='".intval($val)."'";
						break;
					case "HITS1":
						if ($arFilter["HITS_BACK"]=="Y")
							$arSqlSearch_h[] = "HITS_BACK>='".intval($val)."'";
						else
							$arSqlSearch_h[] = "HITS>='".intval($val)."'";
						break;
					case "HITS2":
						if ($arFilter["HITS_BACK"]=="Y")
							$arSqlSearch_h[] = "HITS_BACK<='".intval($val)."'";
						else
							$arSqlSearch_h[] = "HITS<='".intval($val)."'";
						break;
					case "COST1":
						$arSqlSearch_h[] = "COST>='".doubleval($val)."'";
						break;
					case "COST2":
						$arSqlSearch_h[] = "COST<='".doubleval($val)."'";
						break;
					case "REVENUE1":
						$arSqlSearch_h[] = "REVENUE>='".doubleval($val)."'";
						break;
					case "REVENUE2":
						$arSqlSearch_h[] = "REVENUE<='".doubleval($val)."'";
						break;
					case "BENEFIT1":
						$arSqlSearch_h[] = "BENEFIT>='".doubleval($val)."'";
						break;
					case "BENEFIT2":
						$arSqlSearch_h[] = "BENEFIT<='".doubleval($val)."'";
						break;
					case "ROI1":
						$arSqlSearch_h[] = "ROI>='".doubleval($val)."'";
						break;
					case "ROI2":
						$arSqlSearch_h[] = "ROI<='".doubleval($val)."'";
						break;
					case "ATTENT1":
						if ($arFilter["ATTENT_BACK"]=="Y")
							$arSqlSearch_h[] = "ATTENT_BACK>='".doubleval($val)."'";
						else
							$arSqlSearch_h[] = "ATTENT>='".doubleval($val)."'";
						break;
						break;
					case "ATTENT2":
						if ($arFilter["ATTENT_BACK"]=="Y")
							$arSqlSearch_h[] = "ATTENT_BACK<='".doubleval($val)."'";
						else
							$arSqlSearch_h[] = "ATTENT<='".doubleval($val)."'";
						break;
						break;
					case "VISITORS_PER_DAY1":
						$arSqlSearch_h[] = "VISITORS_PER_DAY>='".doubleval($val)."'";
						break;
					case "VISITORS_PER_DAY2":
						$arSqlSearch_h[] = "VISITORS_PER_DAY<='".doubleval($val)."'";
						break;
					case "DURATION1":
						$arSqlSearch_h[] = "ADV_TIME>=".doubleval($val)."*86400";
						break;
					case "DURATION2":
						$arSqlSearch_h[] = "ADV_TIME<=".doubleval($val)."*86400";
						break;
					case "CURRENCY":
						$CURRENCY = $val;
						break;
					case "DESCRIPTION":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("A.".$key, $val, $match);
						break;
				}
			}
		}

		$rate = 1;
		$base_currency = GetStatisticBaseCurrency();
		$view_currency = $base_currency;
		if (strlen($base_currency)>0)
		{
			if (CModule::IncludeModule("currency"))
			{
				if ($CURRENCY!=$base_currency && strlen($CURRENCY)>0)
				{
					$rate = CCurrencyRates::GetConvertFactor($base_currency, $CURRENCY);
					$view_currency = $CURRENCY;
				}
			}
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		foreach($arSqlSearch_h as $sqlWhere)
			$strSqlSearch_h .= " and (".$sqlWhere.") ";

		$group = false;
		$find_group = (strlen($find_group)<=0) ? "NOT_REF" : $find_group;

		$arrFields_1 = array(
				"C_TIME_FIRST", "C_TIME_LAST", "CURRENCY",
				"DATE_FIRST", "DATE_LAST", "ADV_TIME",
				"GUESTS", "NEW_GUESTS", "FAVORITES",
				"C_HOSTS", "SESSIONS", "HITS",
				"GUESTS_BACK", "FAVORITES_BACK", "HOSTS_BACK",
				"SESSIONS_BACK", "HITS_BACK", "ATTENT",
				"ATTENT_BACK", "NEW_VISITORS", "RETURNED_VISITORS",
				"VISITORS_PER_DAY", "COST", "REVENUE",
				"BENEFIT", "SESSION_COST", "VISITOR_COST", "ROI",
			);
		if ($find_group=="referer1") array_push($arrFields_1, "REFERER1");
		if ($find_group=="referer2") array_push($arrFields_1, "REFERER2");

		$arrFields_2 = array(
				"GUESTS_TODAY", "NEW_GUESTS_TODAY", "FAVORITES_TODAY",
				"C_HOSTS_TODAY", "SESSIONS_TODAY", "HITS_TODAY",
				"GUESTS_BACK_TODAY", "FAVORITES_BACK_TODAY", "HOSTS_BACK_TODAY",
				"SESSIONS_BACK_TODAY", "HITS_BACK_TODAY", "GUESTS_YESTERDAY",
				"NEW_GUESTS_YESTERDAY", "FAVORITES_YESTERDAY", "C_HOSTS_YESTERDAY",
				"SESSIONS_YESTERDAY", "HITS_YESTERDAY", "GUESTS_BACK_YESTERDAY",
				"FAVORITES_BACK_YESTERDAY", "HOSTS_BACK_YESTERDAY", "SESSIONS_BACK_YESTERDAY",
				"HITS_BACK_YESTERDAY", "GUESTS_BEF_YESTERDAY", "NEW_GUESTS_BEF_YESTERDAY",
				"FAVORITES_BEF_YESTERDAY", "C_HOSTS_BEF_YESTERDAY", "SESSIONS_BEF_YESTERDAY",
				"HITS_BEF_YESTERDAY", "GUESTS_BACK_BEF_YESTERDAY", "FAVORITES_BACK_BEF_YESTERDAY",
				"HOSTS_BACK_BEF_YESTERDAY", "SESSIONS_BACK_BEF_YESTERDAY", "HITS_BACK_BEF_YESTERDAY",
				"A.ID", "REFERER1", "REFERER2",
				"A.PRIORITY", "A.EVENTS_VIEW", "A.DESCRIPTION",
				"GUESTS_PERIOD", "C_HOSTS_PERIOD", "NEW_GUESTS_PERIOD",
				"FAVORITES_PERIOD", "SESSIONS_PERIOD", "HITS_PERIOD",
				"GUESTS_BACK_PERIOD", "HOSTS_BACK_PERIOD", "FAVORITES_BACK_PERIOD",
				"SESSIONS_BACK_PERIOD", "HITS_BACK_PERIOD",
			);

		$arrFields = $arrFields_1;
		if ($find_group=="NOT_REF")
			$arrFields = array_merge($arrFields, $arrFields_2);

		if ($order!="asc")
			$order = "desc";

		$key = array_search(strtoupper($by),$arrFields);
		if ($key===NULL || $key===false)
			$key = array_search("A.".strtoupper($by),$arrFields);

		if ($key!==NULL && $key!==false)
			$strSqlOrder = " ORDER BY ".$arrFields[$key];
		elseif ($by == "s_dropdown")
			$strSqlOrder = "ORDER BY A.ID desc, A.REFERER1, A.REFERER2";
		elseif ($by == "s_referers")
			$strSqlOrder = "ORDER BY A.REFERER1, A.REFERER2";
		else
		{
			if ($find_group=="NOT_REF")
			{
				$strSqlOrder = " ORDER BY SESSIONS_TODAY $order, SESSIONS_YESTERDAY $order, SESSIONS_BEF_YESTERDAY $order, SESSIONS_PERIOD $order, SESSIONS ";
			}
			else
			{
				$strSqlOrder = " ORDER BY SESSIONS ";
			}
			$by = "BY_DEFAULT";
		}
		$strSqlOrder .= " ".$order;

		$limit = (intval($limit)>0) ? intval($limit) : intval(COption::GetOptionString('statistic','RECORDS_LIMIT'));

		$sqlDays = "
			-- TODAY
			sum(if(to_days(curdate())=to_days(D.DATE_STAT),ifnull(D.GUESTS_DAY,0),0))			GUESTS_TODAY,
			sum(if(to_days(curdate())=to_days(D.DATE_STAT),ifnull(D.NEW_GUESTS,0),0))			NEW_GUESTS_TODAY,
			sum(if(to_days(curdate())=to_days(D.DATE_STAT),ifnull(D.FAVORITES,0),0))			FAVORITES_TODAY,
			sum(if(to_days(curdate())=to_days(D.DATE_STAT),ifnull(D.C_HOSTS_DAY,0),0))			C_HOSTS_TODAY,
			sum(if(to_days(curdate())=to_days(D.DATE_STAT),ifnull(D.SESSIONS,0),0))				SESSIONS_TODAY,
			sum(if(to_days(curdate())=to_days(D.DATE_STAT),ifnull(D.HITS,0),0))					HITS_TODAY,
			sum(if(to_days(curdate())=to_days(D.DATE_STAT),ifnull(D.GUESTS_DAY_BACK,0),0))		GUESTS_BACK_TODAY,
			sum(if(to_days(curdate())=to_days(D.DATE_STAT),ifnull(D.FAVORITES_BACK,0),0))		FAVORITES_BACK_TODAY,
			sum(if(to_days(curdate())=to_days(D.DATE_STAT),ifnull(D.HOSTS_DAY_BACK,0),0))		HOSTS_BACK_TODAY,
			sum(if(to_days(curdate())=to_days(D.DATE_STAT),ifnull(D.SESSIONS_BACK,0),0))		SESSIONS_BACK_TODAY,
			sum(if(to_days(curdate())=to_days(D.DATE_STAT),ifnull(D.HITS_BACK,0),0))			HITS_BACK_TODAY,

			-- YESTERDAY
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.GUESTS_DAY,0),0))			GUESTS_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.NEW_GUESTS,0),0))			NEW_GUESTS_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.FAVORITES,0),0))			FAVORITES_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.C_HOSTS_DAY,0),0))		C_HOSTS_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.SESSIONS,0),0))			SESSIONS_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.HITS,0),0))				HITS_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.GUESTS_DAY_BACK,0),0))	GUESTS_BACK_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.FAVORITES_BACK,0),0))		FAVORITES_BACK_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.HOSTS_DAY_BACK,0),0))		HOSTS_BACK_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.SESSIONS_BACK,0),0))		SESSIONS_BACK_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.HITS_BACK,0),0))			HITS_BACK_YESTERDAY,

			-- THE DAY BEFORE YESTERDAY
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.GUESTS_DAY,0),0))			GUESTS_BEF_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.NEW_GUESTS,0),0))			NEW_GUESTS_BEF_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.FAVORITES,0),0))			FAVORITES_BEF_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.C_HOSTS_DAY,0),0))		C_HOSTS_BEF_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.SESSIONS,0),0))			SESSIONS_BEF_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.HITS,0),0))				HITS_BEF_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.GUESTS_DAY_BACK,0),0))	GUESTS_BACK_BEF_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.FAVORITES_BACK,0),0))		FAVORITES_BACK_BEF_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.HOSTS_DAY_BACK,0),0))		HOSTS_BACK_BEF_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.SESSIONS_BACK,0),0))		SESSIONS_BACK_BEF_YESTERDAY,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.HITS_BACK,0),0))			HITS_BACK_BEF_YESTERDAY,
			";
		if ($find_group=="NOT_REF") // no grouping
		{
			$strSql =	"
				SELECT
					A.ID, A.REFERER1, A.REFERER2, A.PRIORITY, A.EVENTS_VIEW, A.DESCRIPTION,
					A.DATE_FIRST C_TIME_FIRST,
					A.DATE_LAST C_TIME_LAST,
					'".$DB->ForSql($view_currency)."' CURRENCY,
					".$DB->DateToCharFunction("A.DATE_FIRST","SHORT")." DATE_FIRST,
					".$DB->DateToCharFunction("A.DATE_LAST","SHORT")." DATE_LAST,
					UNIX_TIMESTAMP(ifnull(A.DATE_LAST,0))-UNIX_TIMESTAMP(ifnull(A.DATE_FIRST,0)) ADV_TIME,
					$sqlDays

					-- PERIOD
					".($filter_period ? $strSqlPeriod.'ifnull(D.GUESTS,0)'.$strT : 'A.GUESTS')." GUESTS_PERIOD,
					".($filter_period ? $strSqlPeriod.'ifnull(D.C_HOSTS,0)'.$strT : 'A.C_HOSTS')." C_HOSTS_PERIOD,
					".($filter_period ? $strSqlPeriod.'ifnull(D.NEW_GUESTS,0)'.$strT : 'A.NEW_GUESTS')." NEW_GUESTS_PERIOD,
					".($filter_period ? $strSqlPeriod.'ifnull(D.FAVORITES,0)'.$strT : 'A.FAVORITES')." FAVORITES_PERIOD,
					".($filter_period ? $strSqlPeriod.'ifnull(D.SESSIONS,0)'.$strT : 'A.SESSIONS')." SESSIONS_PERIOD,
					".($filter_period ? $strSqlPeriod.'ifnull(D.HITS,0)'.$strT : 'A.HITS')." HITS_PERIOD,
					".($filter_period ? $strSqlPeriod.'ifnull(D.GUESTS_DAY_BACK,0)'.$strT : 'A.GUESTS_BACK')." GUESTS_BACK_PERIOD,
					".($filter_period ? $strSqlPeriod.'ifnull(D.HOSTS_DAY_BACK,0)'.$strT : 'A.HOSTS_BACK')." HOSTS_BACK_PERIOD,
					".($filter_period ? $strSqlPeriod.'ifnull(D.FAVORITES_BACK,0)'.$strT : 'A.FAVORITES')." FAVORITES_BACK_PERIOD,
					".($filter_period ? $strSqlPeriod.'ifnull(D.SESSIONS_BACK,0)'.$strT : 'A.SESSIONS_BACK')." SESSIONS_BACK_PERIOD,
					".($filter_period ? $strSqlPeriod.'ifnull(D.HITS_BACK,0)'.$strT : 'A.HITS_BACK')." HITS_BACK_PERIOD,

					-- TOTAL
					A.GUESTS, A.NEW_GUESTS, A.FAVORITES, A.C_HOSTS, A.SESSIONS, A.HITS, A.GUESTS_BACK, A.FAVORITES_BACK, A.HOSTS_BACK, A.SESSIONS_BACK, A.HITS_BACK,

					-- AUDIENCE
					if(A.SESSIONS>0,round(A.HITS/A.SESSIONS,2),-1) ATTENT,
					if(A.SESSIONS_BACK>0,round(A.HITS_BACK/A.SESSIONS_BACK,2),-1) ATTENT_BACK,
					if(A.GUESTS>0,round((A.NEW_GUESTS/A.GUESTS)*100,2),-1) NEW_VISITORS,
					if(A.GUESTS>0,round((A.GUESTS_BACK/A.GUESTS)*100,2),-1) RETURNED_VISITORS,
					if(
					round((((UNIX_TIMESTAMP(ifnull(A.DATE_LAST,0))-UNIX_TIMESTAMP(ifnull(A.DATE_FIRST,0)))/86400)),0)>=1, round(A.GUESTS/((UNIX_TIMESTAMP(ifnull(A.DATE_LAST,0)) - UNIX_TIMESTAMP(ifnull(A.DATE_FIRST,0)))/86400),2),-1)  VISITORS_PER_DAY,

					-- FINANCES
					round(round(A.COST,2)*$rate,2) COST,
					round(round(A.REVENUE,2)*$rate,2) REVENUE,
					round(round(A.REVENUE-A.COST,2)*$rate,2) BENEFIT,
					round(round(if(A.SESSIONS>0,A.COST/A.SESSIONS,0),2)*$rate,2) SESSION_COST,
					round(round(if(A.GUESTS>0,A.COST/A.GUESTS,0),2)*$rate,2) VISITOR_COST,
					if(A.COST>0,round(((A.REVENUE-A.COST)/A.COST)*100,2),-1) ROI

				FROM
					b_stat_adv A
				LEFT JOIN b_stat_adv_day D ON (D.ADV_ID = A.ID)
				WHERE
					$strSqlSearch
				GROUP BY
					A.ID, A.REFERER1, A.REFERER2, A.COST, A.REVENUE, A.PRIORITY, A.EVENTS_VIEW, A.DESCRIPTION, A.DATE_FIRST, A.DATE_LAST, A.GUESTS, A.NEW_GUESTS, A.FAVORITES, A.C_HOSTS, A.SESSIONS, A.HITS, A.GUESTS_BACK, A.FAVORITES_BACK, A.HOSTS_BACK, A.SESSIONS_BACK, A.HITS_BACK
			";
		}
		else
		{
			if ($find_group=="referer1")
				$group = "REFERER1";
			else
				$group = "REFERER2";

			// total data
			$strSql =	"
				SELECT
					A.$group,
					min(A.DATE_LAST)											C_TIME_FIRST,
					max(A.DATE_LAST)											C_TIME_LAST,
					'".$DB->ForSql($view_currency)."'											CURRENCY,
					".$DB->DateToCharFunction("min(A.DATE_FIRST)","SHORT")."	DATE_FIRST,
					".$DB->DateToCharFunction("max(A.DATE_LAST)","SHORT")."		DATE_LAST,
					UNIX_TIMESTAMP(max(ifnull(A.DATE_LAST,0)))-UNIX_TIMESTAMP(min(ifnull(A.DATE_FIRST,0)))	ADV_TIME,

					-- TOTAL
					sum(A.GUESTS)			GUESTS,
					sum(A.NEW_GUESTS)		NEW_GUESTS,
					sum(A.FAVORITES)		FAVORITES,
					sum(A.C_HOSTS)			C_HOSTS,
					sum(A.SESSIONS)			SESSIONS,
					sum(A.HITS)				HITS,
					sum(A.GUESTS_BACK)		GUESTS_BACK,
					sum(A.FAVORITES_BACK)	FAVORITES_BACK,
					sum(A.HOSTS_BACK)		HOSTS_BACK,
					sum(A.SESSIONS_BACK)	SESSIONS_BACK,
					sum(A.HITS_BACK)		HITS_BACK,

					-- AUDIENCE
					if(sum(A.SESSIONS)>0,round(sum(A.HITS)/sum(A.SESSIONS),2),-1)					ATTENT,
					if(sum(A.SESSIONS_BACK)>0,round(sum(A.HITS_BACK)/sum(A.SESSIONS_BACK),2),-1)	ATTENT_BACK,
					if(sum(A.GUESTS)>0,round((sum(A.NEW_GUESTS)/sum(A.GUESTS))*100,2),-1)			NEW_VISITORS,
					if(sum(A.GUESTS)>0,round((sum(A.GUESTS_BACK)/sum(A.GUESTS))*100,2),-1)			RETURNED_VISITORS,
					if(
					round((((UNIX_TIMESTAMP(max(ifnull(A.DATE_LAST,0)))-UNIX_TIMESTAMP(min(ifnull(A.DATE_FIRST,0))))/86400)),0)>=1, round(sum(A.GUESTS)/((UNIX_TIMESTAMP(max(ifnull(A.DATE_LAST,0))) - UNIX_TIMESTAMP(min(ifnull(A.DATE_FIRST,0))))/86400),2),-1)  VISITORS_PER_DAY,

					-- FINANCES
					round(round(sum(A.COST),2)*$rate,2)												COST,
					round(round(sum(A.REVENUE),2)*$rate,2)											REVENUE,
					round(round((sum(A.REVENUE)-sum(A.COST)),2)*$rate,2)							BENEFIT,
					round(round(if(sum(A.SESSIONS)>0,sum(A.COST)/sum(A.SESSIONS),0),2)*$rate,2)		SESSION_COST,
					round(round(if(sum(A.GUESTS)>0,sum(A.COST)/sum(A.GUESTS),0),2)*$rate,2)			VISITOR_COST,
					if(sum(A.COST)>0,round(((sum(A.REVENUE)-sum(A.COST))/sum(A.COST))*100,2),-1)	ROI

				FROM
					b_stat_adv A
				WHERE
					$strSqlSearch
				GROUP BY
					A.$group
			";

			// period data
			$strSql_days = "
				SELECT
				A.$group,
				$sqlDays

				-- PERIOD
				".($filter_period ? $strSqlPeriod.'ifnull(D.GUESTS,0)'.$strT : 'sum(A.GUESTS)')."				GUESTS_PERIOD,
				".($filter_period ? $strSqlPeriod.'ifnull(D.C_HOSTS,0)'.$strT : 'sum(A.C_HOSTS)')."				C_HOSTS_PERIOD,
				".($filter_period ? $strSqlPeriod.'ifnull(D.NEW_GUESTS,0)'.$strT : 'sum(A.NEW_GUESTS)')."		NEW_GUESTS_PERIOD,
				".($filter_period ? $strSqlPeriod.'ifnull(D.FAVORITES,0)'.$strT : 'sum(A.FAVORITES)')."			FAVORITES_PERIOD,
				".($filter_period ? $strSqlPeriod.'ifnull(D.SESSIONS,0)'.$strT : 'sum(A.SESSIONS)')."			SESSIONS_PERIOD,
				".($filter_period ? $strSqlPeriod.'ifnull(D.HITS,0)'.$strT : 'sum(A.HITS)')."					HITS_PERIOD,
				".($filter_period ? $strSqlPeriod.'ifnull(D.GUESTS_BACK,0)'.$strT : 'A.GUESTS_BACK')."			GUESTS_BACK_PERIOD,
				".($filter_period ? $strSqlPeriod.'ifnull(D.HOSTS_BACK,0)'.$strT : 'A.HOSTS_BACK')."			HOSTS_BACK_PERIOD,
				".($filter_period ? $strSqlPeriod.'ifnull(D.FAVORITES_BACK,0)'.$strT : 'sum(A.FAVORITES)')."	FAVORITES_BACK_PERIOD,
				".($filter_period ? $strSqlPeriod.'ifnull(D.SESSIONS_BACK,0)'.$strT : 'sum(A.SESSIONS_BACK)')."		SESSIONS_BACK_PERIOD,
				".($filter_period ? $strSqlPeriod.'ifnull(D.HITS_BACK,0)'.$strT : 'sum(A.HITS_BACK)')."			HITS_BACK_PERIOD
				FROM
					b_stat_adv_day D
				LEFT JOIN b_stat_adv A ON (D.ADV_ID = A.ID)
				GROUP BY
					A.$group
				";

			$z = $DB->Query($strSql_days, false, $err_mess.__LINE__);
			while ($zr = $z->Fetch()) $arrGROUP_DAYS[$zr[$group]] = $zr;
		}
		$strSql_res = $strSql;

		$strSql .= "
				HAVING
					1=1
				$strSqlSearch_h
				$strSqlOrder
				LIMIT $limit
			";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch) || strlen($strSqlSearch_h)>0 || $group || $filter_period);
		return $res;
	}

	
	/**
	* <p>Возвращает настройки <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv">рекламной кампании</a>.</p>
	*
	*
	* @param int $adv_id  ID рекламной кампании. </htm
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $adv_id = 1;
	* if ($rs = <b>CAdv::GetByID</b>($adv_id))
	* {
	*     $ar = $rs-&gt;Fetch();
	*     // выведем настройки рекламной кампании
	*     echo "&lt;pre&gt;"; print_r($ar); echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv">Термин "Рекламная
	* кампания"</a> </li></ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cadv/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$ID = intval($ID);
		$strSql = "
			SELECT
				A.*,
				round(A.COST,2)									COST,
				round(A.REVENUE,2)								REVENUE,
				".$DB->DateToCharFunction("A.DATE_FIRST")."		DATE_FIRST,
				".$DB->DateToCharFunction("A.DATE_LAST")."		DATE_LAST
			FROM
				b_stat_adv A
			WHERE
				A.ID = '$ID'
		";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	
	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type">типов событий</a>, инициализированных <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителями</a>, зашедшими по определённой <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv">рекламной кампании</a> (РК).</p>
	*
	*
	* @param int $adv_id  ID рекламной кампании. </htm
	*
	* @param string &$by = "s_counter" Поле для сортировки. Возможные значения: <ul> <li> <b>s_id</b> - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type">типа события</a> </li> <li>
	* <b>s_event1</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type_id">идентификатор</a> event1 типа
	* события </li> <li> <b>s_event2</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type_id">идентификатор</a> event2 типа
	* события </li> <li> <b>s_sort</b> - индекс сортировки типа события </li> <li>
	* <b>s_name</b> - наименование типа события </li> <li> <b>s_counter</b> - количество
	* событий инициализированных посетителями на <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_first">прямом заходе</a> по
	* рекламной кампании <i>adv_id</i> </li> <li> <b>s_counter_back</b> - количество событий
	* инициализированных посетителями на <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_back">возврате</a> по рекламной
	* кампании <i>adv_id</i> </li> <li> <b>s_def</b> - сортировка по умолчанию (для
	* вывода в соответствующей таблице) </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию </li> <li> <b>desc</b> - по убыванию </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b>* - ID типа события </li> <li>
	* <b>ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по <b>ID</b>
	* будет искаться вхождение </li> <li> <b>EVENT1</b>* - идентификатор event1 типа
	* события </li> <li> <b>EVENT1_EXACT_MATCH</b> - если значение равно "Y", то при
	* фильтрации по <b>EVENT1</b> будет искаться точное совпадение </li> <li>
	* <b>EVENT2</b>* - идентификатор event2 типа события </li> <li> <b>EVENT2_EXACT_MATCH</b> -
	* если значение равно "Y", то при фильтрации по <b>EVENT2</b> будет
	* искаться точное совпадение </li> <li> <b>KEYWORDS</b>* - имя и описание типа
	* события </li> <li> <b>KEYWORDS_EXACT_MATCH</b> - если значение равно "Y", то при
	* фильтрации по <b>KEYWORDS</b> будет искаться точное совпадение </li> <li>
	* <b>DATE1_PERIOD</b> - начальная дата <i>периода</i> </li> <li> <b>DATE2_PERIOD</b> -
	* конечная дата <i>периода</i> </li> <li> <b>COUNTER_PERIOD_1</b> - если установлены
	* <b>DATE1_PERIOD</b> или <b>DATE2_PERIOD</b>, то в данном поле можно указать
	* начальное значение интервала количества событий
	* инициализированных посетителями на прямом заходе по рекламной
	* кампании <i>adv_id</i> </li> <li> <b>COUNTER_PERIOD_2</b> - если установлены <b>DATE1_PERIOD</b>
	* или <b>DATE2_PERIOD</b>, то в данном поле можно указать конечное значение
	* интервала количества событий инициализированных посетителями
	* на прямом заходе по рекламной кампании <i>adv_id</i> </li> <li>
	* <b>COUNTER_BACK_PERIOD_1</b> - если установлены <b>DATE1_PERIOD</b> или <b>DATE2_PERIOD</b>, то в
	* данном поле можно указать начальное значение интервала
	* количества событий инициализированных посетителями на возврате
	* по рекламной кампании <i>adv_id</i> </li> <li> <b>COUNTER_BACK_PERIOD_2</b> - если
	* установлены <b>DATE1_PERIOD</b> или <b>DATE2_PERIOD</b>, то в данном поле можно
	* указать конечное значение интервала количества событий
	* инициализированных посетителями на возврате по рекламной
	* кампании <i>adv_id</i> </li> <li> <b>MONEY_PERIOD_1</b> - если установлены <b>DATE1_PERIOD</b>
	* или <b>DATE2_PERIOD</b>, то в данном поле можно указать начальное значение
	* интервала количество денег инициализированных посетителями на
	* прямом заходе по рекламной кампании <i>adv_id</i> </li> <li> <b>MONEY_PERIOD_2</b> -
	* если установлены <b>DATE1_PERIOD</b> или <b>DATE2_PERIOD</b>, то в данном поле можно
	* указать конечное значение интервала количества денег
	* инициализированных посетителями на прямом заходе по рекламной
	* кампании <i>adv_id</i> </li> </ul> * - допускается <a
	* href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная логика</a>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности списка типов событий. Если значение равно
	* "true", то список был отфильтрован.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $adv_id = 1;
	* 
	* // отфильтруем по типам события "download / file1" и "download / file2"
	* // а также получим дополнительные данные за декабрь 2005 года
	* $arFilter = array(
	*     "EVENT1"       =&gt; "download",
	*     "EVENT2"       =&gt; "file1 | file2",
	*     "DATE1_PERIOD" =&gt; "01.12.2005",
	*     "DATE2_PERIOD" =&gt; "31.12.2005"
	*     );
	* 
	* // получим список записей
	* $rs = <b>CAdv::GetEventList</b>(
	*     $adv_id, 
	*     ($by="s_counter"), 
	*     ($order="desc"), 
	*     $arFilter, 
	*     $is_filtered
	*     );
	* 
	* // выведем все записи
	* while ($ar = $rs-&gt;Fetch())
	* {
	*     echo "&lt;pre&gt;"; print_r($ar); echo "&lt;/pre&gt;";    
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv">Термин "Рекламная
	* кампания"</a> </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">Термин
	* "Событие"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cadv/geteventlist.php
	* @author Bitrix
	*/
	public static function GetEventList($ID, &$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$find_group = $arFilter["GROUP"];
		$ID = intval($ID);
		$arSqlSearch = Array();
		$arSqlSearch_h = Array();
		$strSqlSearch_h = "";
		$filter_period = false;
		$strSqlPeriod = "";
		$strT = "";
		if (is_array($arFilter))
		{
			$date1 = $arFilter["DATE1_PERIOD"];
			$date2 = $arFilter["DATE2_PERIOD"];
			$date_from = MkDateTime(ConvertDateTime($date1,"D.M.Y"),"d.m.Y");
			$date_to = MkDateTime(ConvertDateTime($date2,"D.M.Y")." 23:59","d.m.Y H:i");
			if (strlen($date1)>0)
			{
				$filter_period = true;
				if (strlen($date2)>0)
				{
					$strSqlPeriod = "sum(if(AE.DATE_STAT<FROM_UNIXTIME('$date_from'),0, if(AE.DATE_STAT>FROM_UNIXTIME('$date_to'),0,";
					$strT=")))";
				}
				else
				{
					$strSqlPeriod = "sum(if(AE.DATE_STAT<FROM_UNIXTIME('$date_from'),0,";
					$strT="))";
				}
			}
			elseif (strlen($date2)>0)
			{
				$filter_period = true;
				$strSqlPeriod = "sum(if(AE.DATE_STAT>FROM_UNIXTIME('$date_to'),0,";
				$strT="))";
			}
			foreach ($arFilter as $key => $val)
			{
				if(is_array($val))
				{
					if(count($val) <= 0)
						continue;
				}
				else
				{
					if( (strlen($val) <= 0) || ($val === "NOT_REF") )
						continue;
				}
				$match_value_set = array_key_exists($key."_EXACT_MATCH", $arFilter);
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("E.ID", $val, $match);
						break;
					case "EVENT1":
					case "EVENT2":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("E.".$key, $val, $match);
						break;
					case "KEYWORDS":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("E.DESCRIPTION, E.NAME", $val, $match);
						break;
					case "COUNTER_PERIOD_1":
						$arSqlSearch_h[] = "COUNTER_PERIOD>='".intval($val)."'";
						break;
					case "COUNTER_PERIOD_2":
						$arSqlSearch_h[] = "COUNTER_PERIOD<='".intval($val)."'";
						break;
					case "COUNTER_BACK_PERIOD_1":
						$arSqlSearch_h[] = "COUNTER_BACK_PERIOD>='".intval($val)."'";
						break;
					case "COUNTER_BACK_PERIOD_2":
						$arSqlSearch_h[] = "COUNTER_BACK_PERIOD<='".intval($val)."'";
						break;
					case "COUNTER_ADV_DYNAMIC_LIST":
						$arSqlSearch_h[] = "(COUNTER_PERIOD>='".intval($val)."' or COUNTER_BACK_PERIOD>='".intval($val)."')";
						break;
					case "MONEY1":
						$arSqlSearch_h[] = "(MONEY+MONEY_BACK)>='".roundDB($val)."'";
						break;
					case "MONEY2":
						$arSqlSearch_h[] = "(MONEY+MONEY_BACK)<='".roundDB($val)."'";
						break;
					case "MONEY_PERIOD_1":
						$arSqlSearch_h[] = "(MONEY_PERIOD+MONEY_BACK_PERIOD)>='".roundDB($val)."'";
						break;
					case "MONEY_PERIOD_2":
						$arSqlSearch_h[] = "(MONEY_PERIOD+MONEY_BACK_PERIOD)<='".roundDB($val)."'";
						break;
				}
			}
		}

		if ($by == "s_id")			$strSqlOrder = "ORDER BY E.ID";
		elseif ($by == "s_event1")		$strSqlOrder = "ORDER BY E.EVENT1";
		elseif ($by == "s_event2")		$strSqlOrder = "ORDER BY E.EVENT2";
		elseif ($by == "s_sort")		$strSqlOrder = "ORDER BY C_SORT";
		elseif ($by == "s_name")		$strSqlOrder = "ORDER BY E.NAME";
		elseif ($by == "s_description")		$strSqlOrder = "ORDER BY E.DESCRIPTION";
		elseif ($by == "s_counter")		$strSqlOrder = "ORDER BY COUNTER";
		elseif ($by == "s_counter_back")	$strSqlOrder = "ORDER BY COUNTER_BACK";
		elseif ($by == "s_counter_period")	$strSqlOrder = "ORDER BY COUNTER_PERIOD";
		elseif ($by == "s_counter_back_period")	$strSqlOrder = "ORDER BY COUNTER_BACK_PERIOD";
		elseif ($by == "s_counter_today")	$strSqlOrder = "ORDER BY COUNTER_TODAY";
		elseif ($by == "s_counter_back_today")	$strSqlOrder = "ORDER BY COUNTER_BACK_TODAY";
		elseif ($by == "s_counter_yestoday")	$strSqlOrder = "ORDER BY COUNTER_YESTERDAY";
		elseif ($by == "s_counter_back_yestoday")	$strSqlOrder = "ORDER BY COUNTER_BACK_YESTERDAY";
		elseif ($by == "s_counter_bef_yestoday")	$strSqlOrder = "ORDER BY COUNTER_BEF_YESTERDAY";
		elseif ($by == "s_counter_back_bef_yestoday")	$strSqlOrder = "ORDER BY COUNTER_BACK_BEF_YESTERDAY";
		elseif ($by == "s_def")
		{
			$strSqlOrder = "
			ORDER BY
				E.C_SORT desc,
				COUNTER_TODAY desc, COUNTER_BACK_TODAY desc,
				COUNTER_YESTERDAY desc, COUNTER_BACK_YESTERDAY desc,
				COUNTER_BEF_YESTERDAY desc, COUNTER_BACK_BEF_YESTERDAY desc,
				".($filter_period? "COUNTER_PERIOD desc, COUNTER_BACK_PERIOD desc,": "")."
				COUNTER desc, COUNTER_BACK
			";
		}
		else
		{
			$by = "s_counter";
			$strSqlOrder = "ORDER BY COUNTER";
		}

		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		foreach($arSqlSearch_h as $sqlWhere)
			$strSqlSearch_h .= " and (".$sqlWhere.") ";

		$find_group = (strlen($find_group)<=0) ? "NOT_REF" : $find_group;

		$sqlDays = "
			sum(if(to_days(curdate())=to_days(AE.DATE_STAT),ifnull(AE.COUNTER,0),0))						COUNTER_TODAY,
			sum(if(to_days(curdate())-to_days(AE.DATE_STAT)=1,ifnull(AE.COUNTER,0),0))						COUNTER_YESTERDAY,
			sum(if(to_days(curdate())-to_days(AE.DATE_STAT)=2,ifnull(AE.COUNTER,0),0))						COUNTER_BEF_YESTERDAY,
			sum(if(to_days(curdate())=to_days(AE.DATE_STAT),ifnull(AE.COUNTER_BACK,0),0))					COUNTER_BACK_TODAY,
			sum(if(to_days(curdate())-to_days(AE.DATE_STAT)=1,ifnull(AE.COUNTER_BACK,0),0))					COUNTER_BACK_YESTERDAY,
			sum(if(to_days(curdate())-to_days(AE.DATE_STAT)=2,ifnull(AE.COUNTER_BACK,0),0))					COUNTER_BACK_BEF_YESTERDAY,
			".($filter_period ? $strSqlPeriod.'ifnull(AE.COUNTER,0)'.$strT : 'sum(AE.COUNTER)')."			COUNTER_PERIOD,
			".($filter_period ? $strSqlPeriod.'ifnull(AE.COUNTER_BACK,0)'.$strT : 'sum(AE.COUNTER_BACK)')."	COUNTER_BACK_PERIOD,

			sum(if(to_days(curdate())=to_days(AE.DATE_STAT),ifnull(AE.MONEY,0),0))							MONEY_TODAY,
			sum(if(to_days(curdate())-to_days(AE.DATE_STAT)=1,ifnull(AE.MONEY,0),0))						MONEY_YESTERDAY,
			sum(if(to_days(curdate())-to_days(AE.DATE_STAT)=2,ifnull(AE.MONEY,0),0))						MONEY_BEF_YESTERDAY,
			sum(if(to_days(curdate())=to_days(AE.DATE_STAT),ifnull(AE.MONEY_BACK,0),0))						MONEY_BACK_TODAY,
			sum(if(to_days(curdate())-to_days(AE.DATE_STAT)=1,ifnull(AE.MONEY_BACK,0),0))					MONEY_BACK_YESTERDAY,
			sum(if(to_days(curdate())-to_days(AE.DATE_STAT)=2,ifnull(AE.MONEY_BACK,0),0))					MONEY_BACK_BEF_YESTERDAY,
			".($filter_period ? $strSqlPeriod.'ifnull(AE.MONEY,0)'.$strT : 'sum(AE.MONEY)')."				MONEY_PERIOD,
			".($filter_period ? $strSqlPeriod.'ifnull(AE.MONEY_BACK,0)'.$strT : 'sum(AE.MONEY_BACK)')."		MONEY_BACK_PERIOD,
			";

		if ($find_group=="NOT_REF") // no grouping
		{
			$strSql = "
				SELECT
					E.ID, E.EVENT1, E.EVENT2, E.C_SORT, E.NAME, E.DESCRIPTION,
					sum(AE.COUNTER) COUNTER,
					sum(AE.COUNTER_BACK) COUNTER_BACK,
					sum(AE.MONEY) MONEY,
					sum(AE.MONEY_BACK) MONEY_BACK,
					$sqlDays
					if (length(E.NAME)>0, E.NAME,
						concat(ifnull(E.EVENT1,''),' / ',ifnull(E.EVENT2,''))) EVENT
				FROM
					b_stat_event E,
					b_stat_adv_event_day AE
				WHERE
				$strSqlSearch
				and	E.ADV_VISIBLE = 'Y'
				and AE.ADV_ID = '$ID'
				and AE.EVENT_ID = E.ID
				GROUP BY E.ID, E.EVENT1, E.EVENT2, E.C_SORT, E.NAME, E.DESCRIPTION
				HAVING
					1=1
				$strSqlSearch_h
				$strSqlOrder
				LIMIT ".intval(COption::GetOptionString('statistic','RECORDS_LIMIT'))."
				";
		}
		else
		{
			if ($find_group=="event1")
				$group = "E.EVENT1";
			else
				$group = "E.EVENT2";

			$strSql = "
				SELECT
					$group,
					sum(E.C_SORT) C_SORT,
					$sqlDays
					sum(AE.COUNTER) COUNTER,
					sum(AE.COUNTER_BACK) COUNTER_BACK,
					sum(AE.MONEY) MONEY,
					sum(AE.MONEY_BACK) MONEY_BACK
				FROM
					b_stat_event E,
					b_stat_adv_event_day AE
				WHERE
				$strSqlSearch
				and	E.ADV_VISIBLE = 'Y'
				and AE.ADV_ID = '$ID'
				and AE.EVENT_ID = E.ID
				GROUP BY $group
				HAVING
					1=1
				$strSqlSearch_h
				$strSqlOrder
				LIMIT ".intval(COption::GetOptionString('statistic','RECORDS_LIMIT'))."
			";
		}

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch) || $filter_period || strlen($strSqlSearch_h)>0 || $find_group!="NOT_REF");
		return $res;
	}

	public static function GetEventListByReferer($value, $arFilter)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		if ($arFilter["GROUP"]=="referer1")
			$group = "A.REFERER1";
		else
			$group = "A.REFERER2";

		$where = "";
		$filter_period = false;
		$strSqlPeriod = "";
		$strT = "";

		if (is_array($arFilter))
		{
			$date1 = $arFilter["DATE1_PERIOD"];
			$date2 = $arFilter["DATE2_PERIOD"];
			$date_from = MkDateTime(ConvertDateTime($date1,"D.M.Y"),"d.m.Y");
			$date_to = MkDateTime(ConvertDateTime($date2,"D.M.Y")." 23:59","d.m.Y H:i");
			if (strlen($date1)>0)
			{
				$filter_period = true;
				if (strlen($date2)>0)
				{
					$strSqlPeriod = "sum(if(AE.DATE_STAT<FROM_UNIXTIME('$date_from'),0, if(AE.DATE_STAT>FROM_UNIXTIME('$date_to'),0,";
					$strT=")))";
				}
				else
				{
					$strSqlPeriod = "sum(if(AE.DATE_STAT<FROM_UNIXTIME('$date_from'),0,";
					$strT="))";
				}
			}
			elseif (strlen($date2)>0)
			{
				$filter_period = true;
				$strSqlPeriod = "sum(if(AE.DATE_STAT>FROM_UNIXTIME('$date_to'),0,";
				$strT="))";
			}
		}

		$arFilter["GROUP"]="";
		$a = CAdv::GetList($by, $order, $arFilter, $is_filtered, "", $arrGROUP_DAYS, $strSql_res);
		if ($is_filtered)
		{
			$str_id = "0";
			while ($ar = $a->Fetch()) $str_id .= ",".intval($ar["ID"]);
			$where = "and A.ID in ($str_id)";
		}

		$strSql = "
			SELECT
				E.ID, E.EVENT1, E.EVENT2, E.C_SORT, E.NAME, E.DESCRIPTION,
				sum(AE.COUNTER)																				COUNTER,
				sum(AE.COUNTER_BACK)																		COUNTER_BACK,
				sum(if(to_days(curdate())=to_days(AE.DATE_STAT),ifnull(AE.COUNTER,0),0))					COUNTER_TODAY,
				sum(if(to_days(curdate())-to_days(AE.DATE_STAT)=1,ifnull(AE.COUNTER,0),0))					COUNTER_YESTERDAY,
				sum(if(to_days(curdate())-to_days(AE.DATE_STAT)=2,ifnull(AE.COUNTER,0),0))					COUNTER_BEF_YESTERDAY,
				sum(if(to_days(curdate())=to_days(AE.DATE_STAT),ifnull(AE.COUNTER_BACK,0),0))				COUNTER_BACK_TODAY,
				sum(if(to_days(curdate())-to_days(AE.DATE_STAT)=1,ifnull(AE.COUNTER_BACK,0),0))				COUNTER_BACK_YESTERDAY,
				sum(if(to_days(curdate())-to_days(AE.DATE_STAT)=2,ifnull(AE.COUNTER_BACK,0),0))				COUNTER_BACK_BEF_YESTERDAY,
				".($filter_period ? $strSqlPeriod.'ifnull(AE.COUNTER,0)'.$strT : 'sum(AE.COUNTER)')."		COUNTER_PERIOD,
				".($filter_period ? $strSqlPeriod.'ifnull(AE.COUNTER_BACK,0)'.$strT : 'sum(AE.COUNTER_BACK)')."	COUNTER_BACK_PERIOD,

				sum(AE.MONEY)																				MONEY,
				sum(AE.MONEY_BACK)																			MONEY_BACK,
				sum(if(to_days(curdate())=to_days(AE.DATE_STAT),ifnull(AE.MONEY,0),0))						MONEY_TODAY,
				sum(if(to_days(curdate())-to_days(AE.DATE_STAT)=1,ifnull(AE.MONEY,0),0))					MONEY_YESTERDAY,
				sum(if(to_days(curdate())-to_days(AE.DATE_STAT)=2,ifnull(AE.MONEY,0),0))					MONEY_BEF_YESTERDAY,
				sum(if(to_days(curdate())=to_days(AE.DATE_STAT),ifnull(AE.MONEY_BACK,0),0))					MONEY_BACK_TODAY,
				sum(if(to_days(curdate())-to_days(AE.DATE_STAT)=1,ifnull(AE.MONEY_BACK,0),0))				MONEY_BACK_YESTERDAY,
				sum(if(to_days(curdate())-to_days(AE.DATE_STAT)=2,ifnull(AE.MONEY_BACK,0),0))				MONEY_BACK_BEF_YESTERDAY,
				".($filter_period ? $strSqlPeriod.'ifnull(AE.MONEY,0)'.$strT : 'sum(AE.MONEY)')."			MONEY_PERIOD,
				".($filter_period ? $strSqlPeriod.'ifnull(AE.MONEY_BACK,0)'.$strT : 'sum(AE.MONEY_BACK)')."	MONEY_BACK_PERIOD,

				if (length(E.NAME)>0, E.NAME,
					concat(ifnull(E.EVENT1,''),' / ',ifnull(E.EVENT2,''))) EVENT
			FROM
				b_stat_adv A,
				b_stat_adv_event_day AE,
				b_stat_event E
			WHERE
				1=1
				$where
			and	$group='".$DB->ForSql($value,255)."'
			and AE.ADV_ID = A.ID
			and E.ID = AE.EVENT_ID
			and E.ADV_VISIBLE = 'Y'
			GROUP BY
				E.ID, E.EVENT1, E.EVENT2, E.C_SORT, E.NAME, E.DESCRIPTION
			ORDER BY
				E.C_SORT desc,
				COUNTER_TODAY desc, COUNTER_BACK_TODAY desc,
				COUNTER_YESTERDAY desc, COUNTER_BACK_YESTERDAY desc,
				COUNTER_BEF_YESTERDAY desc, COUNTER_BACK_BEF_YESTERDAY desc,
				COUNTER_PERIOD desc, COUNTER_BACK_PERIOD desc,
				COUNTER desc, COUNTER_BACK
			LIMIT ".intval(COption::GetOptionString('statistic','RECORDS_LIMIT'))."
			";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	
	/**
	* <p>Возвращает данные по <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_traffic">трафику</a> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv">рекламной кампании</a> в разрезе по датам.</p>
	*
	*
	* @param int $adv_id  ID рекламной кампании. </htm
	*
	* @param string &$by = "s_date" Поле для сортировки. Возможные значения: <ul><li> <b>s_date</b> - дата </li></ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию </li> <li> <b>desc</b> - по убыванию </li> </ul>
	*
	* @param array &$max_min  Ссылка на массив содержащий максимальную и минимальную даты
	* результирующего списка. Структура данного массива: <pre> Array (
	* [DATE_FIRST] =&gt; минимальная дата [MIN_DAY] =&gt; день минимальной даты (1-31)
	* [MIN_MONTH] =&gt; месяц минимальной даты (1-12) [MIN_YEAR] =&gt; год минимальной
	* даты [DATE_LAST] =&gt; максимальная дата [MAX_DAY] =&gt; день максимальной даты
	* (1-31) [MAX_MONTH] =&gt; месяц максимальной даты (1-12) [MAX_YEAR] =&gt; год
	* максимальной даты ) </pre>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>DATE_1</b> - дата "с" </li> <li> <b>DATE_2</b> -
	* дата "по" </li> </ul>
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $adv_id = 1;
	* 
	* // установим фильтр на декабрь 2005 года
	* $arFilter = array(
	*     "DATE1" =&gt; "01.12.2005",
	*     "DATE2" =&gt; "31.12.2005"
	*     );
	* 
	* // получим набор записей
	* $rs = <b>CAdv::GetDynamicList</b>(
	*     $adv_id, 
	*     ($by="s_date"), 
	*     ($order="desc"), 
	*     $arMaxMin, 
	*     $arFilter, 
	*     $is_filtered
	*     );
	* 
	* // выведем массив с максимальной и минимальной датами
	* echo "&lt;pre&gt;"; print_r($arMaxMin); echo "&lt;/pre&gt;";    
	* 
	* // выведем все записи
	* while ($ar = $rs-&gt;Fetch())
	* {
	*     echo "&lt;pre&gt;"; print_r($ar); echo "&lt;/pre&gt;";    
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv">Термин "Рекламная
	* кампания"</a> </li></ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cadv/getdynamiclist.php
	* @author Bitrix
	*/
	public static function GetDynamicList($ADV_ID, &$by, &$order, &$arMaxMin, $arFilter=Array())
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$ADV_ID = intval($ADV_ID);
		$arSqlSearch = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				if(is_array($val))
				{
					if(count($val) <= 0)
						continue;
				}
				else
				{
					if( (strlen($val) <= 0) || ($val === "NOT_REF") )
						continue;
				}

				$key = strtoupper($key);
				switch($key)
				{
					case "DATE1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "D.DATE_STAT>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "D.DATE_STAT<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
				}
			}
		}

		foreach($arSqlSearch as $sqlWhere)
			$strSqlSearch .= " and (".$sqlWhere.") ";

		if ($by == "s_date")
			$strSqlOrder = "ORDER BY D.DATE_STAT";
		else
		{
			$by = "s_date";
			$strSqlOrder = "ORDER BY D.DATE_STAT";
		}

		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}

		$strSql =	"
			SELECT
				".$DB->DateToCharFunction("D.DATE_STAT","SHORT")." DATE_STAT,
				DAYOFMONTH(D.DATE_STAT) DAY,
				MONTH(D.DATE_STAT) MONTH,
				YEAR(D.DATE_STAT) YEAR,
				D.GUESTS_DAY GUESTS,
				D.NEW_GUESTS NEW_GUESTS,
				D.FAVORITES FAVORITES,
				D.C_HOSTS_DAY C_HOSTS,
				D.SESSIONS SESSIONS,
				D.HITS HITS,
				D.GUESTS_DAY_BACK GUESTS_BACK,
				D.FAVORITES_BACK FAVORITES_BACK,
				D.HOSTS_DAY_BACK HOSTS_BACK,
				D.SESSIONS_BACK SESSIONS_BACK,
				D.HITS_BACK HITS_BACK
			FROM
				b_stat_adv_day D
			WHERE
				D.ADV_ID = $ADV_ID
			$strSqlSearch
			GROUP BY
				D.ADV_ID, D.DATE_STAT
			$strSqlOrder
		";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		$strSql = "
			SELECT
				max(D.DATE_STAT)				DATE_LAST,
				min(D.DATE_STAT)				DATE_FIRST,
				DAYOFMONTH(max(D.DATE_STAT))	MAX_DAY,
				MONTH(max(D.DATE_STAT))			MAX_MONTH,
				YEAR(max(D.DATE_STAT))			MAX_YEAR,
				DAYOFMONTH(min(D.DATE_STAT))	MIN_DAY,
				MONTH(min(D.DATE_STAT))			MIN_MONTH,
				YEAR(min(D.DATE_STAT))			MIN_YEAR
			FROM
				b_stat_adv_day D
			WHERE
				D.ADV_ID = $ADV_ID
			$strSqlSearch
			";

		$a = $DB->Query($strSql, false, $err_mess.__LINE__);
		$ar = $a->Fetch();
		$arMaxMin["MAX_DAY"]	= $ar["MAX_DAY"];
		$arMaxMin["MAX_MONTH"]	= $ar["MAX_MONTH"];
		$arMaxMin["MAX_YEAR"]	= $ar["MAX_YEAR"];
		$arMaxMin["MIN_DAY"]	= $ar["MIN_DAY"];
		$arMaxMin["MIN_MONTH"]	= $ar["MIN_MONTH"];
		$arMaxMin["MIN_YEAR"]	= $ar["MIN_YEAR"];

		return $res;
	}

	public static function GetDropDownList($strSqlOrder="ORDER BY REFERER1, REFERER2")
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$strSql = "
			SELECT
				ID as REFERENCE_ID,
				concat(ifnull(REFERER1,''),' / ',ifnull(REFERER2,''),' [',ID,']') as REFERENCE
			FROM
				b_stat_adv
			$strSqlOrder
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	
	/**
	* <p>Возвращает упрощённый список <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv">рекламных кампаний</a> (РК).</p>
	*
	*
	* @param string &$by = "s_referer1" Поле для сортировки. Возможные значения: <ul> <li> <b>s_id</b> - ID РК; </li> <li>
	* <b>s_referer1</b> - <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_id">идентификатор</a>
	* referer1 РК; </li> <li> <b>s_referer2</b> - идентификатор referer2 РК; </li> <li> <b>s_description</b>
	* - описание РК. </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b>* - ID РК; </li> <li> <b>ID_EXACT_MATCH</b> -
	* если значение равно "N", то при фильтрации по <b>ID</b> будет искаться
	* вхождение; </li> <li> <b>REFERER1</b>* - идентификатор referer1 РК; </li> <li>
	* <b>REFERER1_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>REFERER1</b> будет искаться точное совпадение; </li> <li> <b>REFERER2</b>* -
	* идентификатор referer2 РК; </li> <li> <b>REFERER2_EXACT_MATCH</b> - если значение равно
	* "Y", то при фильтрации по <b>REFERER2</b> будет искаться точное
	* совпадение; </li> <li> <b>DESCRIPTION</b>* - описание РК; </li> <li> <b>DESCRIPTION_EXACT_MATCH</b>
	* - если значение равно "Y", то при фильтрации по <b>DESCRIPTION</b> будет
	* искаться точное совпадение. </li> </ul> * - допускается <a
	* href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная логика</a>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности списка рекламных кампаний. Если значение
	* равно "true", то список был отфильтрован.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // выберем только те рекламные кампании у которых в referer1 входит "google"
	* $arFilter = array(
	*     "REFERER1" =&gt; "google"
	*     );
	* 
	* // получим список записей
	* $rs = <b>CAdv::GetSimpleList</b>(
	*     ($by="s_referer1"), 
	*     ($order="desc"), 
	*     $arFilter, 
	*     $is_filtered
	*     );
	* 
	* // выведем все записи
	* while ($ar = $rs-&gt;Fetch())
	* {
	*     echo "&lt;pre&gt;"; print_r($ar); echo "&lt;/pre&gt;";    
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv">Термин "Рекламная
	* кампания"</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cadv/getlist.php">CAdv::GetList</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cadv/getdropdownlist.php">CAdv::GetDropdownList</a> </li> </ul>
	* <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cadv/getsimplelist.php
	* @author Bitrix
	*/
	public static function GetSimpleList(&$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = Array();
		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				if(is_array($val))
				{
					if(count($val) <= 0)
						continue;
				}
				else
				{
					if( (strlen($val) <= 0) || ($val === "NOT_REF") )
						continue;
				}
				$match_value_set = array_key_exists($key."_EXACT_MATCH", $arFilter);
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("A.".$key, $val, $match);
						break;
					case "REFERER1":
					case "REFERER2":
					case "DESCRIPTION":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("A.".$key, $val, $match);
						break;
				}
			}
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$order= ($order!="desc") ? "asc" : "desc";
		if ($by == "s_id")				$strSqlOrder = "ORDER BY A.ID ".$order;
		elseif ($by == "s_referer1")	$strSqlOrder = "ORDER BY A.REFERER1 ".$order.", A.REFERER2";
		elseif ($by == "s_referer2")	$strSqlOrder = "ORDER BY A.REFERER2 ".$order;
		elseif ($by == "s_description")	$strSqlOrder = "ORDER BY A.DESCRIPTION ".$order;
		else
		{
			$by = "s_referer1";
			$strSqlOrder = "ORDER BY A.REFERER1 ".$order.", A.REFERER2";
		}
		$strSql = "
			SELECT
				A.ID,
				A.REFERER1,
				A.REFERER2,
				A.DESCRIPTION
			FROM
				b_stat_adv A
			WHERE
			$strSqlSearch
			$strSqlOrder
			LIMIT ".intval(COption::GetOptionString('statistic','RECORDS_LIMIT'))."
			";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}
}
?>
