<?
class CSPListsClient extends CSOAPClient
{
	protected $bInit = false;
	protected $RESPONSE;
	protected $arConnectionParams = array();
	
	protected $ATTACHMENTS_PATH = '/upload/sharepoint';
	
	/* public section */
	
	public function __construct($arParams)
	{
		if (is_array($arParams))
		{
			$this->SetConnectionParams($arParams);
		}
	}
	
	public function SetConnectionParams($arParams)
	{
		$arDefaultParams = array(
			'scheme' => 'http',
			'host' => '',
			'port' => 80,
			'user' => '',
			'pass' => '',
			'path' => WS_SP_SERVICE_PATH,
			'query' => '',
			'fragment' => ''
		);
	
		$arParams['path'] = WS_SP_SERVICE_PATH; // temporary.
	
		foreach ($arDefaultParams as $param => $value)
			$this->arConnectionParams[$param] = isset($arParams[$param]) ? $arParams[$param] : $value;
		
		if ($this->arConnectionParams['scheme'] == 'https')
			$this->arConnectionParams['port'] = 443;
		elseif ($this->arConnectionParams['port'] == 443)
			$this->arConnectionParams['scheme'] = 'https';
		
		return $this->__initialize();
	}
	
	public function Call($method, $arParams = array())
	{
		$REQUEST = new CSOAPRequest($method, WS_SP_SERVICE_NS, $arParams);
		$this->RESPONSE = $this->send($REQUEST);
		
		if (!$this->RESPONSE)
		{
			$GLOBALS['APPLICATION']->ThrowException('Connection error!');
			return false;
		}
		elseif ($this->RESPONSE->isFault())
		{
			$GLOBALS['APPLICATION']->ThrowException('SOAP Fault '.$this->RESPONSE->faultCode().': '.$this->RESPONSE->faultString());
			return false;
		}
		else
		{
			return true;
		}
	}
	
	/* ws methods functions */
	public function GetListCollection()
	{
		if (
			$this->__initialize() 
			&& $this->Call('GetListCollection') 
			&& ($DOM = $this->RESPONSE->DOMDocument)
		)
		{
			$arListNodes = $DOM->elementsByName('List');
			
			if (!is_array($arListNodes) || count($arListNodes) <= 0)
			{
				return array();
			}
			else
			{
				$arLists = array();
				foreach ($arListNodes as $node)
				{
					$arLists[] = array(
						'ID' => $node->getAttribute('ID'),
						'URL' => $node->getAttribute('DefaultViewUrl'),
						'TITLE' => $node->getAttribute('Title'),
						'DESCRIPTION' => $node->getAttribute('Description'),
						'IMAGE' => $node->getAttribute('ImageUrl'),
					);
				}
				
				return $this->GetListCollectionProcessResult($arLists);
			}
		}
		else
			return false;
	}
	
	protected function _GetByID_query($XML_ID)
	{
		$node = new CXMLCreator('Eq');
		$node->addChild(CXMLCreator::createTagAttributed('FieldRef Name="ID"'));
		$node->addChild(CXMLCreator::createTagAttributed('Value Type="integer"', intval($XML_ID)));

		return $node;
	}
	
	public function GetByID($listName, $XML_ID)
	{
		$RESULT = false;
	
		$arMethodParams = array('listName' => $listName);
		
		$query = new CXMLCreator('Query');
		$query->addChild(new CXMLCreator('Where'));
		
		if (!is_array($XML_ID))
			$query->children[0]->addChild($this->_GetByID_query($XML_ID));
		elseif (count($XML_ID) == 1)
			$query->children[0]->addChild($this->_GetByID_query($XML_ID[0]));
		else
		{
			$obOr = new CXMLCreator('Or');
		
			foreach ($XML_ID as $item)
			{
				$obOr->addChild($this->_GetByID_query($item));
			}
			
			$query->children[0]->addChild($obOr);
		}
		
		$arMethodParams['query'] = $query;
	
		if (
			$this->__initialize() 
			&& $this->Call('GetListItems', $arMethodParams)
			&& ($DOM = $this->RESPONSE->DOMDocument)
		)
		{
			$DATA_NODE = $DOM->elementsByName('data');
			if (is_array($DATA_NODE) && count($DATA_NODE) > 0)
			{
				$RESULT = $this->ConvertRows($DATA_NODE[0]);
			}
		}
		
		$fp = fopen($_SERVER['DOCUMENT_ROOT'].'/sp_client6.log', 'a');
		fwrite($fp, $this->getRawRequest());
		fwrite($fp, $this->getRawResponse());
		fwrite($fp, "\n==========================================\n\n");
		fclose($fp);
		
		return $this->GetByIDProcessResult($RESULT);
	}
	
	public function GetList($listName)
	{
		if (
			$this->__initialize() 
			&& $this->Call('GetList', array('listName' => $listName)) 
			&& ($DOM = $this->RESPONSE->DOMDocument)
		)
		{
			$RESULT = array('PARAMS' => array(), 'FIELDS' => array());
		
			$LIST = $DOM->elementsByName('List');
			$LIST = $LIST[0];

			$ar = $LIST->getAttributes();
			foreach ($ar as $attr)
			{
				$RESULT['PARAMS'][$attr->name()] = $attr->textContent();
			}
			
			$arFieldNodes = $LIST->elementsByName('Field');
	
			if (is_array($arFieldNodes) && count($arFieldNodes) > 0)
			{
				foreach ($arFieldNodes as $node)
				{
					$ar = $node->getAttributes();
					$arField = array();
					foreach ($ar as $attr)
					{
						$arField[$attr->name()] = $attr->textContent();
					}
					
					if ($arField['ID'])
					{
						if ($arField['Type'] == 'Choice' || $arField['Type'] == 'MultiChoice')
						{
							$arChoiceNodes = $node->elementsByName('CHOICE');
							$arField['CHOICE'] = array();
							foreach ($arChoiceNodes as $choice_node)
							{
								$arField['CHOICE'][] = $choice_node->textContent();
							}
						
							$arDefaultNodes = $arChoiceNodes = $node->elementsByName('Default');
							if (count($arDefaultNodes) > 0)
								$arField['DEFAULT'] = $arDefaultNodes[0]->textContent();
						}
					
						$RESULT['FIELDS'][] = $arField;
					}
				}
			}
			
			return $this->GetListProcessResult($RESULT);
		}
		
		return false;
	}
	
/*
	Paging work algorithm:
	
	1. No changeToken
	1.1. Send Query w/o changeToken (TOKEN) and with rowLimit (NUM_ROWS);
	1.2. Recieve Changes section with LastChangeToken (TOKEN), first page of data (DATA) and ListItemCollectionPositionNext (PAGING) param. WARNING! Now's the only chance to remember LastChangeToken - it won't be available during pages navigation
	1.3. Send another request w/o changeToken (TOKEN) and with rowLimit (NUM_ROWS) and attached queryOptions element with ListItemCollectionPositionNext (PAGING) param taken from previous query
	1.4. Recieve more rows with new ListItemCollectionPositionNext (PAGING) value
	1.5. Continue from 1.3 till data is over - no ListItemCollectionPositionNext param recieved
	
	2. We have changeToken
	1.1. Send Query with changeToken (TOKEN) and rowLimit (NUM_ROWS)
	1.2. Recieve rows and Changes section with new LastChangeToken (TOKEN)
	1.3. Continue to 1.1. with new TOKEN till data is over
	1.4. Final query will result empty data and unchanged TOKEN;
	
	WARNING! Don't event try to analyze and change TOKEN and PAGING values! They SHOULD be unchanged for further queries.
	
	Input:
		listName - id of a list (ex. {9C00647C-836C-485B-A025-0663F6EE972A})
		arParams - array(
			'TOKEN' - token to set timing start of a list
			'PAGING' - token to set paging
			'NUM_ROWS' - number of a rows per page
			'FIELDS' - array with a list of a field names needed
		)
	
	Output: array(
		'MORE_ROWS' => {true|false} - flag "is there more data available"
		'TOKEN' => string that chould be used for further queries (or for next page query, if it's not a first query)
		'PAGING' => string that chould be used for next page query (if it's the query w/o TOKEN)
		'COUNT' => recieved data rows count
		'DATA' => array of data rows ('ows_' prefix is cutted from attrubute names)
	)
*/
	public function GetListItemChangesSinceToken($listName, $arParams = array())
	{
		$arMethodParams = array('listName' => $listName);
		
		if ($arParams['TOKEN'])
			$arMethodParams['changeToken'] = $arParams['TOKEN'];
	
		if ($arParams['NUM_ROWS'])
			$arMethodParams['rowLimit'] = intval($arParams['NUM_ROWS']);
	
		$queryOptions = new CXMLCreator('QueryOptions');
		if (isset($arParams['PAGING']))
		{
			$queryOptions->addChild(CXMLCreator::createTagAttributed('Paging ListItemCollectionPositionNext="'.htmlspecialchars($arParams['PAGING']).'"'));
		}
		
		$arMethodParams['queryOptions'] = $queryOptions;
	
		if (is_array($arParams['FIELDS']))
		{
			$viewFields = new CXMLCreator('ViewFields');
			$viewFields->setAttribute('Properties', 'TRUE');

			foreach ($arParams['FIELDS'] as $fld)
				$viewFields->addChild(CXMLCreator::createTagAttributed('FieldRef Name="'.$fld.'"'));
		
			$arMethodParams['viewFields'] = $viewFields;
		}
		
		if (
			$this->__initialize() 
			&& $this->Call('GetListItemChangesSinceToken', $arMethodParams) 
			&& ($DOM = $this->RESPONSE->DOMDocument)
		)
		{
			$RESULT = array();

			// $fp = fopen($_SERVER['DOCUMENT_ROOT'].'/sp_client1.log', 'a');
			// fwrite($fp, $this->getRawRequest());
			// fwrite($fp, $this->getRawResponse());
			// fwrite($fp, "\n==========================================\n\n");
			// fclose($fp);
			
			$CHANGES = $DOM->elementsByName('Changes');
			
			$RESULT['MORE_ROWS'] = false;
			if (is_array($CHANGES) && count($CHANGES) > 0)
			{
				$CHANGES = $CHANGES[0];
				$RESULT['TOKEN'] = $CHANGES->getAttribute('LastChangeToken');
				$RESULT['MORE_ROWS'] |= ($CHANGES->getAttribute('MoreChanges') == 'TRUE');
			}
			
			$DATA_NODE = $DOM->elementsByName('data');
			if (is_array($DATA_NODE) && count($DATA_NODE) > 0)
			{
				$DATA_NODE = $DATA_NODE[0];
				
				$RESULT['COUNT'] = $DATA_NODE->getAttribute('ItemCount');
				$RESULT['PAGING'] = $DATA_NODE->getAttribute('ListItemCollectionPositionNext');
				$RESULT['MORE_ROWS'] |= (strlen($RESULT['PAGING']) > 0);
				
				$RESULT['DATA'] = $this->ConvertRows($DATA_NODE);
				
				if (count($RESULT['DATA']) <= 0)
					$RESULT['MORE_ROWS'] = false;
			}
	
			return $this->GetListItemChangesSinceTokenProcessResult($RESULT);
		}
	
		return false;
	}
	
	public function GetAttachmentCollection($listName, $arParams)
	{
		$arMethodParams = array('listName' => $listName);
		$arMethodParams['listItemID'] = $arParams['SP_ID'];
		
		if (
			$this->__initialize() 
			&& $this->Call('GetAttachmentCollection', $arMethodParams) 
			&& ($DOM = $this->RESPONSE->DOMDocument)
		)
		{
			$RESULT = array();
			
			$ATTACHMENTS = $DOM->elementsByName('Attachment');
			
			foreach ($ATTACHMENTS as $ATTACH)
			{
				$RESULT[] = $ATTACH->textContent();
			}
			
			return $this->GetAttachmentCollectionProcessResult($RESULT);
		}
		
		return false;
	}
	
	public function UpdateListItems($listName, $arChanges)
	{
		$arMethodParams = array('listName' => $listName);
		
		$updates = CXMLCreator::createTagAttributed('Batch OnError="Continue" DateInUtc="TRUE" Properties="TRUE"');
		
		$i = 0;
		
		foreach ($arChanges as $row)
		{
			$obRow = CXMLCreator::createTagAttributed('Method ID="'.($i++).'"');
			
			if ($ID = intval($row['ID']))
			{
				$obRow->setAttribute('Cmd', 'Update');
			}
			else
			{
				$obRow->setAttribute('Cmd', 'New');
				
				unset($row['ID']);
				
				$obRow->addChild(CXMLCreator::createTagAttributed('Field Name="ID"', 'New'));
				$obRow->addChild(CXMLCreator::createTagAttributed('Field Name="MetaInfo" Property="ReplicationID"', $row['ReplicationID']));
				unset($row['ReplicationID']);
			}
			
			foreach ($row as $fld => $value)
			{
				if (substr($fld, 0, 9) == 'MetaInfo_')
				{
					$obRow->addChild(CXMLCreator::createTagAttributed('Field Name="MetaInfo" Property="'.CDataXML::xmlspecialchars(substr($fld, 9)).'"', $value));
				}
				else
				{
					if ($fld)
					{
						$obRow->addChild(CXMLCreator::createTagAttributed('Field Name="'.CDataXML::xmlspecialchars($fld).'"', $value));
					}
				}
			}
			
			$updates->addChild($obRow);
		}
		
		$arMethodParams['updates'] = $updates;
		
		$RESULT = false;
		
		if (
			$this->__initialize() 
			&& $this->Call('UpdateListItems', $arMethodParams) 
			&& ($DOM = $this->RESPONSE->DOMDocument)
		)
		{
			$RESULT = array();
		
			$arResults = $DOM->elementsByName('Result');
			
			foreach ($arResults as $resultNode)
			{
				$arRes = array(
					'ErrorCode' => $resultNode->children[0]->textContent(),
					'Row' => $this->ConvertRows($resultNode),
				);
				
				if ($arRes['Row']) $arRes['Row'] = $arRes['Row'][0];
				
				$RESULT[] = $arRes;
			}
		
		}
		
		$fp = fopen($_SERVER['DOCUMENT_ROOT'].'/sp_client5.log', 'a');
		fwrite($fp, $this->getRawRequest());
		fwrite($fp, $this->getRawResponse());
		fwrite($fp, "\n==========================================\n\n");
		fclose($fp);
	
		
		return $RESULT;
	}
	
	public function LoadFile($listName, $arParams)
	{
		if ($arParams['URL'])
		{
			// hack!
			$URL = str_replace(
				array('%3A', '%2F'),
				array(':', '/'),
				rawurlencode($GLOBALS['APPLICATION']->ConvertCharset(
					urldecode($arParams['URL']), 
					LANG_CHARSET, 
					'utf-8'
				))
			);

			$CLIENT = new CHTTP();
			$res = false;
			
			if ($this->arConnectionParams['user'])
			{
				$CLIENT->SetAuthBasic(
					$this->arConnectionParams['user'], 
					$this->arConnectionParams['pass']
				);
			}
	
			if ($file_contents = $CLIENT->Get($URL))
			{
				$point_pos = strrpos($URL, '.');
				$ext = '';
				
				$new_filename = md5($URL).($point_pos > 0 ? substr($URL, $point_pos) : '');
				
				$new_filepath = $_SERVER['DOCUMENT_ROOT'].$this->ATTACHMENTS_PATH.'/'.substr($new_filename, 0, 2).'/'.$new_filename;
				CheckDirPath($new_filepath);
				
				$fp = fopen($new_filepath, 'wb');
				fwrite($fp, $file_contents);
				fclose($fp);
				
				$res = CFile::MakeFileArray($new_filepath);
			}
		}
		
		unset($CLIENT);
	
		return $res;
	}
	
	/* getters */
	public function GetConnectionParams()
	{
		return $this->arConnectionParams;
	}
	
	public function GetResponseObject()
	{
		return $this->RESPONSE;
	}
	
	/* protected section */
	
	protected function __initialize()
	{
		global $APPLICATION;
	
		if ($this->bInit)
			return true;
	
		if (!$this->arConnectionParams['host'])
		{
			$APPLICATION->ThrowException('No SP host specified!');
			return false;
		}

		$this->CSOAPClient($this->arConnectionParams['host'], $this->arConnectionParams['path'], $this->arConnectionParams['port']);
		
		if ($this->arConnectionParams['user'])
		{
			$this->setLogin($this->arConnectionParams['user']);
			$this->setPassword($this->arConnectionParams['pass']);
		}
		
		$this->bInit = true;
		
		return true;
	}
	
	/* override these methods to add custom processing for method results */
	protected function GetListCollectionProcessResult($RESULT)
	{
		return $RESULT;
	}
	
	protected function GetListProcessResult($RESULT)
	{
		return $RESULT;
	}
	
	protected function GetListItemChangesSinceTokenProcessResult($RESULT)
	{
		return $RESULT;
	}
	
	protected function GetAttachmentCollectionProcessResult($RESULT)
	{
		foreach ($RESULT as $key => $file)
		{
			$RESULT[$key] = $this->LoadFile('', array('URL' => $file));
		}
	
		return $RESULT;
	}
	
	protected function GetByIDProcessResult($RESULT)
	{
		return $RESULT;
	}
	
	protected function ConvertRows($DATA_NODE)
	{
		$arRows = array();
	
		$DATA = $DATA_NODE->elementsByName('row');
		foreach ($DATA as $row)
		{
			$arRow = array();
			$arAttrs = $row->getAttributes();
			foreach($arAttrs as $attr)
			{
				// cut 'ows' prefix
				$name = substr($attr->name, 0, 4) == 'ows_' 
					? substr($attr->name, 4) 
					: $attr->name;
			
				$arRow[$name] = $attr->content;
			}
			
			$arRows[] = $arRow;
		}
		
		return $arRows;
	}
}
?>