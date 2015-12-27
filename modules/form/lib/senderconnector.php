<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage form
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Form;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class SenderEventHandler
{
	/**
	 * @param $data
	 * @return mixed
	 */
	public static function onConnectorListForm($data)
	{
		$data['CONNECTOR'] = 'Bitrix\Form\SenderConnectorForm';

		return $data;
	}
}


class SenderConnectorForm extends \Bitrix\Sender\Connector
{
	/**
	 * @return string
	 */
	static public function getName()
	{
		return Loc::getMessage('sender_connector_form_name');
	}

	/**
	 * @return string
	 */
	static public function getCode()
	{
		return "form";
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
		$formId = $this->getFieldValue('FORM', null);
		$propertyNameId = $this->getFieldValue('PROPERTY_NAME', null);
		$propertyEmailId = $this->getFieldValue('PROPERTY_EMAIL', null);

		$formResultsDb = new \CDBResult();
		$dataResult = array();

		if($formId && $propertyEmailId)
		{
			$filter = array();
			$formResultDb = \CFormResult::GetList($formId, ($by="s_timestamp"),($order="asc"), $filter, $filtered, "N");
			while ($formResult = $formResultDb->Fetch())
			{
				$answerList = \CFormResult::GetDataByID(
					$formResult['ID'],
					array(),
					$formResult,
					$answerList2
				);

				$data = array();
				foreach($answerList as $fieldCode => $arFieldsAnswer)
				{
					if($arFieldsAnswer[0]['TITLE_TYPE'] == 'text')
					{
						if($arFieldsAnswer[0]['FIELD_ID'] == $propertyNameId)
							$data['NAME'] = $arFieldsAnswer[0]['USER_TEXT'];

						if($arFieldsAnswer[0]['FIELD_ID'] == $propertyEmailId)
							$data['EMAIL'] = $arFieldsAnswer[0]['USER_TEXT'];
					}

				}

				if(!empty($data['EMAIL']))
				{
					if(intval($formResult['USER_ID']) > 0)
						$data['USER_ID'] = intval($formResult['USER_ID']);

					$dataResult[] = $data;
				}
			}
		}

		$formResultsDb->InitFromArray($dataResult);
		return $formResultsDb;
	}

	/**
	 * @return string
	 */
	public function getForm()
	{
		/*
		 * select form list
		*/
		$formList = array();
		$formDb = \CForm::GetList($by = "s_sort", $order = "asc", array(), $filtered);
		while($form = $formDb->Fetch())
		{
			$formList[] = array('ID' => $form['ID'], 'NAME' => $form['NAME']);
		}
		if(!empty($formList))
			$formList = array_merge(
				array(array('ID' => '', 'NAME' => Loc::getMessage('sender_connector_form_select'))),
				$formList
			);
		else
			$formList = array_merge(
				array(array('ID' => '', 'NAME' => Loc::getMessage('sender_connector_form_empty'))),
				$formList
			);

		/*
		 * select properties from all forms
		*/
		$propertyToForm = array();
		$propertyList = array();
		$propertyList[''][] = array('ID' => '', 'NAME' => Loc::getMessage('sender_connector_form_select'));
		$propertyList['EMPTY'][] = array('ID' => '', 'NAME' => Loc::getMessage('sender_connector_form_prop_empty'));
		foreach($formList as $form)
		{
			if(empty($form['ID'])) continue;

			$formFieldsDb = \CFormField::GetList($form['ID'], 'N', $by = "s_sort", $order = "asc", array(), $filtered);
			while ($formFields = $formFieldsDb->Fetch())
			{
				if($formFields['TITLE_TYPE'] != 'text') continue;
				// add default value
				if (!array_key_exists($formFields['FORM_ID'], $propertyList))
				{
					$propertyList[$formFields['FORM_ID']][] = array(
						'ID' => '',
						'NAME' => Loc::getMessage('sender_connector_form_field_select')
					);
				}

				// add property
				$propertyList[$formFields['FORM_ID']][] = array(
					'ID' => $formFields['ID'],
					'NAME' => $formFields['TITLE']
				);

				// add property link to iblock
				$propertyToForm[$formFields['ID']] = $formFields['FORM_ID'];
			}
		}


		/*
		 * create html-control of form list
		*/
		$formInput = '<select name="'.$this->getFieldName('FORM').'" id="'.$this->getFieldId('FORM').'" onChange="FormSelect'.$this->getFieldId('FORM').'()">';
		foreach($formList as $form)
		{
			$inputSelected = ($form['ID'] == $this->getFieldValue('FORM') ? 'selected' : '');
			$formInput .= '<option value="'.$form['ID'].'" '.$inputSelected.'>';
			$formInput .= htmlspecialcharsbx($form['NAME']);
			$formInput .= '</option>';
		}
		$formInput .= '</select>';


		/*
		 * create html-control of properties list for name
		*/
		$formPropertyNameInput = '<select name="'.$this->getFieldName('PROPERTY_NAME').'" id="'.$this->getFieldId('PROPERTY_NAME').'">';
		if(array_key_exists($this->getFieldValue('PROPERTY_NAME', 0), $propertyToForm))
		{
			$arProp = $propertyList[$propertyToForm[$this->getFieldValue('PROPERTY_NAME', 0)]];
		}
		else
		{
			$arProp = $propertyList[''];
		}
		foreach($arProp as $property)
		{
			$inputSelected = ($property['ID'] == $this->getFieldValue('PROPERTY_NAME') ? 'selected' : '');
			$formPropertyNameInput .= '<option value="'.$property['ID'].'" '.$inputSelected.'>';
			$formPropertyNameInput .= htmlspecialcharsbx($property['NAME']);
			$formPropertyNameInput .= '</option>';
		}
		$formPropertyNameInput .= '</select>';


		/*
		 *  create html-control of properties list for email
		*/
		$formPropertyEmailInput = '<select name="'.$this->getFieldName('PROPERTY_EMAIL').'" id="'.$this->getFieldId('PROPERTY_EMAIL').'">';
		if(array_key_exists($this->getFieldValue('PROPERTY_EMAIL', 0), $propertyToForm))
		{
			$arProp = $propertyList[$propertyToForm[$this->getFieldValue('PROPERTY_EMAIL', 0)]];
		}
		else
		{
			$arProp = $propertyList[''];
		}
		foreach($arProp as $property)
		{
			$inputSelected = ($property['ID'] == $this->getFieldValue('PROPERTY_EMAIL') ? 'selected' : '');
			$formPropertyEmailInput .= '<option value="'.$property['ID'].'" '.$inputSelected.'>';
			$formPropertyEmailInput .= htmlspecialcharsbx($property['NAME']);
			$formPropertyEmailInput .= '</option>';
		}
		$formPropertyEmailInput .= '</select>';


		$jsScript = "
		<script>
			function FormSelect".$this->getFieldId('FORM')."()
			{
				var iblock = BX('".$this->getFieldId('FORM')."');
				FormPropertyAdd(iblock, BX('".$this->getFieldId('PROPERTY_NAME')."'));
				FormPropertyAdd(iblock, BX('".$this->getFieldId('PROPERTY_EMAIL')."'));
			}
			function FormPropertyAdd(iblock, iblockProperty)
			{
				if(iblockProperty.length>0)
				{
					for (var j in iblockProperty.options)
					{
						iblockProperty.options.remove(j);
					}
				}
				var propList = {};
				if(formProperties[iblock.value] && formProperties[iblock.value].length>0)
					propList = formProperties[iblock.value];
				else
					propList = formProperties['EMPTY'];
				for(var i in propList)
				{
					var optionName = propList[i]['NAME'];
					var optionValue = propList[i]['ID'];
					iblockProperty.options.add(new Option(optionName, optionValue));
				}

			}

			var formProperties = ".\CUtil::PhpToJSObject($propertyList).";
		</script>
		";



		return '
			'.Loc::getMessage('sender_connector_form_required_settings').'
			<br/><br/>
			<table>
				<tr>
					<td>'.Loc::getMessage('sender_connector_form_field_form').'</td>
					<td>'.$formInput.'</td>
				</tr>
				<tr>
					<td>'.Loc::getMessage('sender_connector_form_field_name').'</td>
					<td>'.$formPropertyNameInput.'</td>
				</tr>
				<tr>
					<td>'.Loc::getMessage('sender_connector_form_field_email').'</td>
					<td>'.$formPropertyEmailInput.'</td>
				</tr>
			</table>
			'.$jsScript.'
		';
	}
}
