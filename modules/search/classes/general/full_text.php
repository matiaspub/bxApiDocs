<?
class CSearchFullText
{
	/**
	 * @var CSearchFullText
	 */
	protected static $instance = null;
	/**
	 * Returns current instance of the full text indexer.
	 *
	 * @return CSearchFullText
	 */
	public static function getInstance()
	{
		if (!isset(static::$instance))
		{
			if (COption::GetOptionString("search", "full_text_engine") === "sphinx")
			{
				self::$instance = new CSearchSphinx;
				self::$instance->connect(
					COption::GetOptionString("search", "sphinx_connection"),
					COption::GetOptionString("search", "sphinx_index_name")
				);
			}
			else
			{
				self::$instance = new CSearchStemTable();
			}
		}
		return static::$instance;
	}
	static public function connect($connectionString)
	{
		return true;
	}
	static public function truncate()
	{
	}
	public function deleteById($ID)
	{
	}
	static public function replace($ID, $arFields)
	{
	}
	static public function update($ID, $arFields)
	{
	}
	static public function search($arParams, $aSort, $aParamsEx, $bTagsCloud)
	{
		return false;
	}
	public static function searchTitle($phrase = "", $arPhrase = array(), $nTopCount = 5, $arParams = array(), $bNotFilter = false, $order = "")
	{
		return false;
	}
	static public function getErrorText()
	{
		return "";
	}
	static public function getErrorNumber()
	{
		return 0;
	}
}
?>