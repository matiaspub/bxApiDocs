<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Mail\Internal;

use Bitrix\Main\Entity;
use Bitrix\Main\Type as Type;

class EventMessageTable extends Entity\DataManager
{

	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_event_message';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => new Type\DateTime(),
			),
			'EVENT_NAME' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'LID' => array(
				'data_type' => 'string',
			),
			'ACTIVE' => array(
				'data_type' => 'string',
				'required' => true,
				'default_value' => 'Y'
			),
			'EMAIL_FROM' => array(
				'data_type' => 'string',
				'required' => true,
				'default_value' => '#EMAIL_FROM#'
			),
			'EMAIL_TO' => array(
				'data_type' => 'string',
				'required' => true,
				'default_value' => '#EMAIL_TO#'
			),
			'SUBJECT' => array(
				'data_type' => 'string',
			),
			'MESSAGE' => array(
				'data_type' => 'string',
			),
			'MESSAGE_PHP' => array(
				'data_type' => 'string',
			),
			'BODY_TYPE' => array(
				'data_type' => 'string',
				'required' => true,
				'default_value' => 'text'
			),
			'BCC' => array(
				'data_type' => 'string',
			),
			'REPLY_TO' => array(
				'data_type' => 'string',
			),
			'CC' => array(
				'data_type' => 'string',
			),
			'IN_REPLY_TO' => array(
				'data_type' => 'string',
			),
			'PRIORITY' => array(
				'data_type' => 'string',
			),
			'FIELD1_NAME' => array(
				'data_type' => 'string',
			),
			'FIELD1_VALUE' => array(
				'data_type' => 'string',
			),
			'FIELD2_NAME' => array(
				'data_type' => 'string',
			),
			'FIELD2_VALUE' => array(
				'data_type' => 'string',
			),
			'SITE_TEMPLATE_ID' => array(
				'data_type' => 'string',
			),
			'ADDITIONAL_FIELD' => array(
				'data_type' => 'string',
				'serialized' => true,
			),
			'EVENT_MESSAGE_SITE' => array(
				'data_type' => 'Bitrix\Main\Mail\Internal\EventMessageSite',
				'reference' => array('=this.ID' => 'ref.EVENT_MESSAGE_ID'),
			),
		);
	}

	public static function replaceTemplateToPhp($str, $fromTemplateToPhp=true)
	{
		preg_match_all("/#([0-9a-zA-Z_.]+?)#/", $str, $matchesFindPlaceHolders);
		$matchesFindPlaceHoldersCount = count($matchesFindPlaceHolders[1]);
		for($i=0; $i<$matchesFindPlaceHoldersCount; $i++)
			if(strlen($matchesFindPlaceHolders[1][$i]) > 200)
				unset($matchesFindPlaceHolders[1][$i]);

		if(empty($matchesFindPlaceHolders[1]))
			return $str;
		$ar = $matchesFindPlaceHolders[1];

		$strResult = $str;
		$arReplaceTagsOne = array();

		if(!$fromTemplateToPhp)
		{
			foreach($ar as $k)
			{
				$replaceTo = '#'.$k.'#';

				$replaceFrom = '$arParams["'.$k.'"]';
				$replaceFromQuote = '$arParams[\''.$k.'\']';
				$replaceFromPhp = '<?='.$replaceFrom.';?>';

				$arReplaceTagsOne[$replaceFromPhp] = $replaceTo;
				$arReplaceTagsOne[$replaceFrom] = $replaceTo;
				$arReplaceTagsOne[$replaceFromQuote] = $replaceTo;
			}
		}
		else
		{
			$replaceTemplateString = '';
			foreach($ar as $k) $replaceTemplateString .= '|#'.$k.'#';

			$arReplaceTags = array();
			$bOpenPhpTag = false;
			preg_match_all('/(<\?|\?>'.$replaceTemplateString.')/', $str, $matchesTag, PREG_OFFSET_CAPTURE);
			foreach($matchesTag[0] as $tag)
			{
				$placeHolder = $tag[0];
				$placeHolderPosition = $tag[1];
				$ch1 = substr($placeHolder, 0, 1);
				$ch2 = substr($placeHolder, 0, 2);

				if($ch2 == "<?")
					$bOpenPhpTag = true;
				elseif($ch2 == "?>")
					$bOpenPhpTag = false;
				elseif($ch1 == "#")
				{
					$placeHolderClear = substr($placeHolder, 1, strlen($placeHolder)-2);

					$bOpenQuote = (substr($str, $placeHolderPosition-2, 2) == '"{');
					$bCloseQuote = (substr($str, $placeHolderPosition+strlen($placeHolder), 2) == '}"');
					if($bOpenPhpTag && $bOpenQuote && $bCloseQuote)
						$replaceTo = '$arParams[\''.$placeHolderClear.'\']';
					else
						$replaceTo = '$arParams["'.$placeHolderClear.'"]';

					if(!$bOpenPhpTag) $replaceTo = '<?=' . $replaceTo . ';?>';
					$arReplaceTags[$tag[0]][] = $replaceTo;
				}
			}

			foreach($arReplaceTags as $k => $v)
			{
				if(count($v)>1)
				{
					foreach($v as $replaceTo)
					{
						$resultReplace = preg_replace('/'.$k.'/', $replaceTo, $strResult, 1);
						if($resultReplace !== null)
							$strResult = $resultReplace;
					}
				}
				else
				{
					$arReplaceTagsOne[$k] = $v[0];
				}
			}
		}

		if(count($arReplaceTagsOne)>0)
			$strResult = str_replace(array_keys($arReplaceTagsOne), array_values($arReplaceTagsOne), $strResult);

		// php parser delete newline folowing the closing tag in string passed to eval
		$strResult = str_replace(array("?>\n", "?>\r\n"), array("?>\n\n", "?>\r\n\r\n"), $strResult);

		return $strResult;
	}

	/**
	 * @param Entity\Event $event
	 * @return Entity\EventResult
	 */
	public static function onBeforeUpdate(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameters();

		if(array_key_exists('MESSAGE', $data['fields']))
		{
			$data['fields']['MESSAGE_PHP'] = static::replaceTemplateToPhp($data['fields']['MESSAGE']);
			$result->modifyFields($data['fields']);
		}

		return $result;
	}

	/**
	 * @param Entity\Event $event
	 * @return Entity\EventResult
	 */
	public static function onBeforeAdd(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameters();

		if(array_key_exists('MESSAGE', $data['fields']))
		{
			$data['fields']['MESSAGE_PHP'] = static::replaceTemplateToPhp($data['fields']['MESSAGE']);
			$result->modifyFields($data['fields']);
		}

		return $result;
	}
}
