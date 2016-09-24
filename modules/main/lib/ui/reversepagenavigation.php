<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2015 Bitrix
 */
namespace Bitrix\Main\UI;

use Bitrix\Main\Web;

class ReversePageNavigation extends PageNavigation
{
	/**
	 * @param string $id Navigation identity like "nav-cars".
	 * @param int $count Record count.
	 */
	public function __construct($id, $count)
	{
		parent::__construct($id);
		$this->setRecordCount($count);
	}

	/**
	 * Returns number of pages.
	 * @return int
	 */
	
	/**
	* <p>Нестатический метод возвращает число страниц.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/reversepagenavigation/getpagecount.php
	* @author Bitrix
	*/
	public function getPageCount()
	{
		if($this->allRecords)
		{
			return 1;
		}
		$maxPages = floor($this->recordCount/$this->pageSize);
		if($this->recordCount > 0 && $maxPages == 0)
		{
			$maxPages = 1;
		}
		return $maxPages;
	}

	/**
	 * Returns the current page number.
	 * @return int
	 */
	
	/**
	* <p>Нестатический метод возвращает номер текущей страницы.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/reversepagenavigation/getcurrentpage.php
	* @author Bitrix
	*/
	public function getCurrentPage()
	{
		if($this->currentPage !== null)
		{
			return $this->currentPage;
		}
		return $this->getPageCount();
	}

	/**
	 * Returns offset of the first record of the current page.
	 * @return int
	 */
	
	/**
	* <p>Нестатический метод возвращает смещение первой записи текущей страницы.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/reversepagenavigation/getoffset.php
	* @author Bitrix
	*/
	public function getOffset()
	{
		if($this->allRecords)
		{
			return 0;
		}

		$offset = 0;
		$pageCount = $this->getPageCount();
		$currentPage = $this->getCurrentPage();

		if($currentPage <> $pageCount)
		{
			//counting the last page (wich is the first one on reverse paging)
			$offset += ($this->recordCount % $this->pageSize);
		}

		$offset += ($pageCount - $currentPage) * $this->pageSize;

		return $offset;
	}

	/**
	 * Returns the number of records in the current page.
	 * @return int
	 */
	
	/**
	* <p>Нестатический метод возвращает число записей на текущей странице.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/reversepagenavigation/getlimit.php
	* @author Bitrix
	*/
	public function getLimit()
	{
		if($this->allRecords)
		{
			return $this->getRecordCount();
		}
		if($this->getCurrentPage() == $this->getPageCount())
		{
			//the last page (displayed first)
			return $this->pageSize + ($this->recordCount % $this->pageSize);
		}
		else
		{
			return $this->pageSize;
		}
	}
}
