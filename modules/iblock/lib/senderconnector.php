<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Iblock;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class SenderEventHandler
{
	/**
	 * Return connector class description.
	 *
	 * @param array $data		Connector data.
	 * @return array
	 */
	
	/**
	* <p>Метод для сообщения модулю <b>Email-маркетинг</b> списка коннекторов. Метод статический.</p>
	*
	*
	* @param array $data  Данные коннекторов.
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/sendereventhandler/onconnectorlistiblock.php
	* @author Bitrix
	*/
	public static function onConnectorListIblock($data)
	{
		$data['CONNECTOR'] = 'Bitrix\Iblock\SenderConnectorIblock';

		return $data;
	}
}


class SenderConnectorIblock extends \Bitrix\Sender\Connector
{
	/**
	 * @return string
	 */
	static public function getName()
	{
		return Loc::getMessage('sender_connector_iblock_name');
	}

	/**
	 * @return string
	 */
	static public function getCode()
	{
		return "iblock";
	}

	/**
	 * @return bool
	 */
	static public function requireConfigure()
	{
		return true;
	}

	/** @return \CDBResult */
	public function getData()
	{
		$iblockId = $this->getFieldValue('IBLOCK', null);
		$propertyNameId = $this->getFieldValue('PROPERTY_NAME', null);
		$propertyEmailId = $this->getFieldValue('PROPERTY_EMAIL', null);

		if($iblockId && $propertyEmailId)
		{
			// if property is property with code like '123'
			$propertyNameValue = null;
			$propertyEmailValue = null;
			if($propertyEmailId)
			{
				if(is_numeric($propertyEmailId))
				{
					$propertyEmailId = "PROPERTY_" . $propertyEmailId;
					$propertyEmailValue = $propertyEmailId."_VALUE";
				}
				else
				{
					$propertyEmailValue = $propertyEmailId;
				}
			}
			$selectFields = array($propertyEmailValue);

			if($propertyNameId)
			{
				if(is_numeric($propertyNameId))
				{
					$propertyNameId = "PROPERTY_" . $propertyNameId;
					$propertyNameValue = $propertyNameId . "_VALUE";
				}
				else
				{
					$propertyNameValue = $propertyNameId;
				}

				$selectFields[] = $propertyNameValue;
			}

			$filter = array('IBLOCK_ID' => $iblockId, '!'.$propertyEmailId => false);
			$iblockElementListDb = \CIBlockElement::getList(array('id' => 'asc'), $filter, false, false, $selectFields);

			// replace property names from PROPERTY_123_VALUE to EMAIL, NAME
			$iblockElementDb = new CDBResultSenderConnector($iblockElementListDb);
			$iblockElementDb->senderConnectorFieldEmail = $propertyEmailValue;
			$iblockElementDb->senderConnectorFieldName = $propertyNameValue;
		}
		else
		{
			$iblockElementDb = new \CDBResult();
			$iblockElementDb->InitFromArray(array());
		}


		return $iblockElementDb;
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function getForm()
	{
		/*
		 * select iblock list
		*/
		$iblockList = array();
		$iblockDb = IblockTable::getList(array(
			'select' => array('ID', 'NAME'),
		));
		while($iblock = $iblockDb->fetch())
		{
			$iblockList[] = $iblock;
		}
		if(!empty($iblockList))
			$iblockList = array_merge(
				array(array('ID' => '', 'NAME' => Loc::getMessage('sender_connector_iblock_select'))),
				$iblockList
			);
		else
			$iblockList = array_merge(
				array(array('ID' => '', 'NAME' => Loc::getMessage('sender_connector_iblock_empty'))),
				$iblockList
			);

		/*
		 * select properties from all iblocks
		*/
		$propertyToIblock = array();
		$propertyList = array();
		$propertyList[''][] = array('ID' => '', 'NAME' => Loc::getMessage('sender_connector_iblock_select'));
		$propertyList['EMPTY'][] = array('ID' => '', 'NAME' => Loc::getMessage('sender_connector_iblock_prop_empty'));
		$iblockFieldsDb = PropertyTable::getList(array(
			'select' => array('ID', 'NAME', 'IBLOCK_ID'),
			'filter' => array('=PROPERTY_TYPE' => PropertyTable::TYPE_STRING)
		));
		while($iblockFields = $iblockFieldsDb->fetch())
		{
			// add property
			$propertyList[$iblockFields['IBLOCK_ID']][] = array(
				'ID' => $iblockFields['ID'],
				'NAME' => $iblockFields['NAME']
			);

			// add property link to iblock
			$propertyToIblock[$iblockFields['ID']] = $iblockFields['IBLOCK_ID'];
		}


		$fieldList = static::getIblockFieldList();
		// add default value
		$fieldList = array_merge(
			array(array('ID' => '', 'NAME' => Loc::getMessage('sender_connector_iblock_field_select'))),
			$fieldList
		);
		foreach($iblockList as $iblock)
		{
			if(!$iblock['ID'])
			{
				continue;
			}

			if(!isset($propertyList[$iblock['ID']]) || !is_array($propertyList[$iblock['ID']]))
			{
				$propertyList[$iblock['ID']] = array();
			}
			else
			{
				// add delimiter between fields and properties
				$propertyList[$iblock['ID']] = array_merge(
					array(array('ID' => '------',	'NAME' => '-----------------', 'DISABLED' => true)),
					$propertyList[$iblock['ID']]
				);
			}

			$propertyList[$iblock['ID']] = array_merge($fieldList, $propertyList[$iblock['ID']]);
		}


		/*
		 * create html-control of iblock list
		*/
		$iblockInput = '<select name="'.$this->getFieldName('IBLOCK').'" id="'.$this->getFieldId('IBLOCK').'" onChange="IblockSelect'.$this->getFieldId('IBLOCK').'()">';
		foreach($iblockList as $iblock)
		{
			$inputSelected = ($iblock['ID'] == $this->getFieldValue('IBLOCK') ? 'selected' : '');
			$iblockInput .= '<option value="'.$iblock['ID'].'" '.$inputSelected.'>';
			$iblockInput .= htmlspecialcharsbx($iblock['NAME']);
			$iblockInput .= '</option>';
		}
		$iblockInput .= '</select>';


		/*
		 * create html-control of properties list for name
		*/
		$iblockPropertyNameInput = '<select name="'.$this->getFieldName('PROPERTY_NAME').'" id="'.$this->getFieldId('PROPERTY_NAME').'">';
		if(array_key_exists($this->getFieldValue('PROPERTY_NAME', 0), $propertyToIblock))
		{
			$propSet = $propertyList[$propertyToIblock[$this->getFieldValue('PROPERTY_NAME', 0)]];
		}
		elseif(array_key_exists($this->getFieldValue('IBLOCK', 0), $propertyList))
		{
			$propSet = $propertyList[$this->getFieldValue('IBLOCK')];
		}
		else
		{
			$propSet = $propertyList[''];
		}
		foreach($propSet as $property)
		{
			$inputSelected = $property['ID'] == $this->getFieldValue('PROPERTY_NAME') ? 'selected' : '';
			$inputDisabled = (isset($property['DISABLED']) && $property['DISABLED']) ? 'disabled' : '';
			$iblockPropertyNameInput .= '<option value="'.$property['ID'].'" '.$inputSelected.' '.$inputDisabled.'>';
			$iblockPropertyNameInput .= htmlspecialcharsbx($property['NAME']);
			$iblockPropertyNameInput .= '</option>';
		}
		$iblockPropertyNameInput .= '</select>';


		/*
		 *  create html-control of properties list for email
		*/
		$iblockPropertyEmailInput = '<select name="'.$this->getFieldName('PROPERTY_EMAIL').'" id="'.$this->getFieldId('PROPERTY_EMAIL').'">';
		if(array_key_exists($this->getFieldValue('PROPERTY_EMAIL', 0), $propertyToIblock))
		{
			$propSet = $propertyList[$propertyToIblock[$this->getFieldValue('PROPERTY_EMAIL', 0)]];
		}
		elseif(array_key_exists($this->getFieldValue('IBLOCK', 0), $propertyList))
		{
			$propSet = $propertyList[$this->getFieldValue('IBLOCK')];
		}
		else
		{
			$propSet = $propertyList[''];
		}
		foreach($propSet as $property)
		{
			$inputSelected = ($property['ID'] == $this->getFieldValue('PROPERTY_EMAIL') ? 'selected' : '');
			$inputDisabled = (isset($property['DISABLED']) && $property['DISABLED']) ? 'disabled' : '';
			$iblockPropertyEmailInput .= '<option value="'.$property['ID'].'" '.$inputSelected.' '.$inputDisabled.'>';
			$iblockPropertyEmailInput .= htmlspecialcharsbx($property['NAME']);
			$iblockPropertyEmailInput .= '</option>';
		}
		$iblockPropertyEmailInput .= '</select>';


		$jsScript = "
		<script>
			function IblockSelect".$this->getFieldId('IBLOCK')."()
			{
				var iblock = BX('".$this->getFieldId('IBLOCK')."');
				IblockPropertyAdd(iblock, BX('".$this->getFieldId('PROPERTY_NAME')."'));
				IblockPropertyAdd(iblock, BX('".$this->getFieldId('PROPERTY_EMAIL')."'));
			}
			function IblockPropertyAdd(iblock, iblockProperty)
			{
				if(iblockProperty.length>0)
				{
					for (var j in iblockProperty.options)
					{
						iblockProperty.options.remove(j);
					}
				}
				var propList = {};
				if(iblockProperties[iblock.value] && iblockProperties[iblock.value].length>0)
					propList = iblockProperties[iblock.value];
				else
					propList = iblockProperties['EMPTY'];
				for(var i in propList)
				{
					var optionName = propList[i]['NAME'];
					var optionValue = propList[i]['ID'];
					var optionDisabled = propList[i]['DISABLED'];
					var newOption = new Option(optionName, optionValue);
					if(optionDisabled)
					{
						newOption.disabled = true;
					}
					iblockProperty.options.add(newOption);
				}

			}

			var iblockProperties = ".\CUtil::PhpToJSObject($propertyList).";
		</script>
		";



		return '
			'.Loc::getMessage('sender_connector_iblock_required_settings').'
			<br/><br/>
			<table>
				<tr>
					<td>'.Loc::getMessage('sender_connector_iblock_field_iblock').'</td>
					<td>'.$iblockInput.'</td>
				</tr>
				<tr>
					<td>'.Loc::getMessage('sender_connector_iblock_field_name').'</td>
					<td>'.$iblockPropertyNameInput.'</td>
				</tr>
				<tr>
					<td>'.Loc::getMessage('sender_connector_iblock_field_email').'</td>
					<td>'.$iblockPropertyEmailInput.'</td>
				</tr>
			</table>
			'.$jsScript.'
		';
	}

	protected static function getIblockFieldList()
	{
		$fieldCodeList = array('NAME', 'CODE', 'PREVIEW_TEXT', 'DETAIL_TEXT');

		$resultList = array();
		$entity = ElementTable::getEntity();
		foreach($fieldCodeList as $fieldCode)
		{
			$field = $entity->getField($fieldCode);
			$resultList[] = array(
				'ID' => $fieldCode,
				'NAME' => $field->getTitle()
			);
		}

		return $resultList;
	}
}

class CDBResultSenderConnector extends \CDBResult
{
	public $senderConnectorFieldName = null;
	public $senderConnectorFieldEmail = null;


	/**
	 * @return array|null
	 */
	public function Fetch()
	{
		$fields = parent::Fetch();
		if($fields)
		{
			$keysForUnset = array();
			if ($this->senderConnectorFieldName)
			{
				if(isset($fields[$this->senderConnectorFieldName."_VALUE"]))
				{
					$fields['NAME'] = $fields[$this->senderConnectorFieldName."_VALUE"];
					$keysForUnset[] = $this->senderConnectorFieldName."_VALUE";
					$keysForUnset[] = $this->senderConnectorFieldName."_VALUE"."_ID";
				}
				elseif(isset($fields[$this->senderConnectorFieldName]))
				{
					$fields['NAME'] = $fields[$this->senderConnectorFieldName];
					if($this->senderConnectorFieldName != 'NAME')
						$keysForUnset[] = $this->senderConnectorFieldName;
				}
			}

			if ($this->senderConnectorFieldEmail)
			{
				if(isset($fields[$this->senderConnectorFieldEmail."_VALUE"]))
				{
					$fields['EMAIL'] = $fields[$this->senderConnectorFieldEmail."_VALUE"];
					$keysForUnset[] = $this->senderConnectorFieldEmail."_VALUE";
					$keysForUnset[] = $this->senderConnectorFieldEmail."_VALUE"."_ID";
				}
				elseif(isset($fields[$this->senderConnectorFieldEmail]))
				{
					$fields['EMAIL'] = $fields[$this->senderConnectorFieldEmail];
					$keysForUnset[] = $this->senderConnectorFieldEmail;
				}
			}

			if (count($keysForUnset)>0)
			{
				$keysForUnset = array_unique($keysForUnset);
				foreach($keysForUnset as $key) unset($fields[$key]);
			}
		}

		return $fields;
	}
}
