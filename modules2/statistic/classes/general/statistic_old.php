<?
class CStat extends CTraffic {}
class CVisit extends CPage {}
class CStatCountry extends CCountry {}
class CAllStatistic extends CStatistics {}
class CStatistic extends CStatistics 
{
	public static function Stoplist($test="N") { return CStopList::Check($test); }
	public static function KeepStatistic($HANDLE_CALL=false) { return CStatistics::Keep($HANDLE_CALL); }
}
?>