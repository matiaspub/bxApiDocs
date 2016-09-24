<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2015 Bitrix
 */
namespace Bitrix\Main\UI;

use Bitrix\Main\Web;

/**
 * Class PageNavigation
 *
 * This class helps to calculate limits for DB queries and other data sources
 * to organize page navigation through results.
 *
 * Examples of supported URLs:
 * /page.php?nav-cars=page-5&nav-books=page-2&other=params
 * /page.php?nav-cars=page-5-size-20&nav-books=page-2
 * /page.php?nav-cars=page-all&nav-books=page-2
 * /page/nav-cars/page-2/size-20/something/
 * /page/nav-cars/page-all/something/?other=params
 * /page/nav-cars/page-5/nav-books/page-2/size-10
 */
class PageNavigation
{
	protected $id;
	protected $pageSizes = array();
	protected $pageSize = 20;
	protected $recordCount;
	protected $currentPage;
	protected $allowAll = false;
	protected $allRecords = false;

	/**
	 * @param string $id Navigation identity like "nav-cars".
	 */
	public function __construct($id)
	{
		$this->id = $id;
	}

	/**
	 * Initializes the navigation from URI.
	 */
	
	/**
	* <p>Нестатический метод инициализирует навигацию от URI.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/pagenavigation/initfromuri.php
	* @author Bitrix
	*/
	public function initFromUri()
	{
		$navParams = array();

		$request = \Bitrix\Main\Context::getCurrent()->getRequest();

		if(($value = $request->getQuery($this->id)) !== null)
		{
			//parameters are in the QUERY_STRING
			$params = explode("-", $value);
			for($i = 0, $n = count($params); $i < $n; $i += 2)
			{
				$navParams[$params[$i]] = $params[$i+1];
			}
		}
		else
		{
			//probably parametrs are in the SEF URI
			$matches = array();
			if(preg_match("'/".preg_quote($this->id, "'")."/page-([\\d]|all)+(/size-([\\d]+))?'", $request->getRequestUri(), $matches))
			{
				$navParams["page"] = $matches[1];
				if(isset($matches[3]))
				{
					$navParams["size"] = $matches[3];
				}
			}
		}

		if(isset($navParams["size"]))
		{
			//set page size from user request
			if(in_array($navParams["size"], $this->pageSizes))
			{
				$this->setPageSize((int)$navParams["size"]);
			}
		}

		if(isset($navParams["page"]))
		{
			if($navParams["page"] == "all" && $this->allowAll == true)
			{
				//show all records in one page
				$this->allRecords = true;
			}
			else
			{
				//set current page within boundaries
				$currentPage = (int)$navParams["page"];
				if($currentPage >= 1)
				{
					if($this->recordCount !== null)
					{
						$maxPage = $this->getPageCount();
						if($currentPage > $maxPage)
						{
							$currentPage = $maxPage;
						}
					}
					$this->setCurrentPage($currentPage);
				}
			}
		}
	}

	/**
	 * Returns number of pages or 0 if recordCount is not set.
	 * @return int
	 */
	
	/**
	* <p>Нестатический метод возвращает число страниц или 0 если не существует записи числа страниц.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/pagenavigation/getpagecount.php
	* @author Bitrix
	*/
	public function getPageCount()
	{
		if($this->allRecords)
		{
			return 1;
		}
		$maxPages = floor($this->recordCount/$this->pageSize);
		if(($this->recordCount % $this->pageSize) > 0)
		{
			$maxPages++;
		}
		return $maxPages;
	}

	/**
	 * @param int $n Page size.
	 * @return $this
	 */
	public function setPageSize($n)
	{
		$this->pageSize = (int)$n;
		return $this;
	}

	/**
	 * @param int $n The current page number.
	 * @return $this
	 */
	public function setCurrentPage($n)
	{
		$this->currentPage = (int)$n;
		return $this;
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/pagenavigation/getcurrentpage.php
	* @author Bitrix
	*/
	public function getCurrentPage()
	{
		if($this->currentPage !== null)
		{
			return $this->currentPage;
		}
		return 1;
	}

	/**
	 * @param bool $mode Allows to show all records, yes or no.
	 * @return $this
	 */
	public function allowAllRecords($mode)
	{
		$this->allowAll = (bool)$mode;
		return $this;
	}

	/**
	 * @param int $n Number of records (to calculate number of pages).
	 * @return $this
	 */
	public function setRecordCount($n)
	{
		$this->recordCount = (int)$n;
		return $this;
	}

	/**
	 * Returns number of records.
	 * @return int|null
	 */
	
	/**
	* <p>Нестатический метод возвращает число записей.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/pagenavigation/getrecordcount.php
	* @author Bitrix
	*/
	public function getRecordCount()
	{
		return $this->recordCount;
	}

	/**
	 * This controls which sizes are available via user interface.
	 * @param array $sizes Array of integers.
	 * @return $this
	 */
	
	/**
	* <p>Нестатический метод контролирует размеры страниц доступных через пользовательский интерфейс.</p>
	*
	*
	* @param array $sizes  Массив целых чисел.
	*
	* @return \Bitrix\Main\UI\PageNavigation 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/pagenavigation/setpagesizes.php
	* @author Bitrix
	*/
	public function setPageSizes(array $sizes)
	{
		$this->pageSizes = $sizes;
		return $this;
	}

	/**
	 * Returns allowed page sizes.
	 * @return array
	 */
	
	/**
	* <p>Нестатический метод возвращает разрешённые размеры страниц.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/pagenavigation/getpagesizes.php
	* @author Bitrix
	*/
	public function getPageSizes()
	{
		return $this->pageSizes;
	}

	/**
	 * Returns "formal" page size.
	 * @return int
	 */
	
	/**
	* <p>Нестатический метод возвращает "формальный" размер страницы.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/pagenavigation/getpagesize.php
	* @author Bitrix
	*/
	public function getPageSize()
	{
		return $this->pageSize;
	}

	/**
	 * Returns navigation ID.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает ID навигации.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/pagenavigation/getid.php
	* @author Bitrix
	*/
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Returns offset of the first record of the current page.
	 * @return int
	 */
	
	/**
	* <p>Нестатический метод возвращает смещение первой записи для текущей страницы.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/pagenavigation/getoffset.php
	* @author Bitrix
	*/
	public function getOffset()
	{
		if($this->allRecords)
		{
			return 0;
		}
		return ($this->getCurrentPage() - 1) * $this->pageSize;
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/pagenavigation/getlimit.php
	* @author Bitrix
	*/
	public function getLimit()
	{
		if($this->allRecords)
		{
			return $this->getRecordCount();
		}
		return $this->pageSize;
	}

	/**
	 * Returns true if all the records are shown in one page.
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод возвращает <i>true</i> если показаны все записи на одной странице.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/pagenavigation/allrecordsshown.php
	* @author Bitrix
	*/
	public function allRecordsShown()
	{
		return $this->allRecords;
	}

	/**
	 * Returns true if showing all records is allowed.
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод возвращает <i>true</i> если показаны все разрешённые записи.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/pagenavigation/allrecordsallowed.php
	* @author Bitrix
	*/
	public function allRecordsAllowed()
	{
		return $this->allowAll;
	}

	/**
	 * Returns an URI with navigation parameters compatible with initFromUri().
	 * @param Web\Uri $uri
	 * @param bool $sef SEF mode.
	 * @param string $page Page number.
	 * @param string $size Page size.
	 * @return Web\Uri
	 */
	
	/**
	* <p>Нестатический метод возвращает URI с параметрами навигации совместимыми с <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/pagenavigation/initfromuri.php">initFromUri</a>.</p>
	*
	*
	* @param mixed $Bitrix  
	*
	* @param Bitri $Main  Режим SEF.
	*
	* @param Mai $Web  Номер страницы.
	*
	* @param Uri $uri  Размер страницы.
	*
	* @param boolean $sef  
	*
	* @param string $page  
	*
	* @param string $size = null 
	*
	* @return \Bitrix\Main\Web\Uri 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/pagenavigation/addparams.php
	* @author Bitrix
	*/
	public function addParams(Web\Uri $uri, $sef, $page, $size = null)
	{
		if($sef == true)
		{
			$this->clearParams($uri, $sef);

			$path = $uri->getPath();
			$pos = strrpos($path, "/");
			$path = substr($path, 0, $pos+1).$this->id."/page-".$page."/".($size !== null? "size-".$size."/" : '').substr($path, $pos+1);
			$uri->setPath($path);
		}
		else
		{
			$uri->addParams(array($this->id => "page-".$page.($size !== null? "-size-".$size : '')));
		}
		return $uri;
	}

	/**
	 * Clears an URI from navigation parameters and returns it.
	 * @param Web\Uri $uri
	 * @param bool $sef SEF mode.
	 * @return Web\Uri
	 */
	
	/**
	* <p>Нестатический метод сбрасывает URI из навигационных параметров и возвращает его.</p>
	*
	*
	* @param mixed $Bitrix  
	*
	* @param Bitri $Main  Режим SEF.
	*
	* @param Mai $Web  
	*
	* @param Uri $uri  
	*
	* @param boolean $sef  
	*
	* @return \Bitrix\Main\Web\Uri 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/ui/pagenavigation/clearparams.php
	* @author Bitrix
	*/
	public function clearParams(Web\Uri $uri, $sef)
	{
		if($sef == true)
		{
			$path = $uri->getPath();
			$path = preg_replace("'/".preg_quote($this->id, "'")."/page-([\\d]|all)+(/size-([\\d]+))?'", "", $path);
			$uri->setPath($path);
		}
		else
		{
			$uri->deleteParams(array($this->id));
		}
		return $uri;
	}
}
