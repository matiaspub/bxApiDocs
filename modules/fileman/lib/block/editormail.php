<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Fileman\Block;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Application;
use Bitrix\Main\Mail\EventMessageCompiler;
use \Bitrix\Main\Web\DOM\StyleInliner;

Loc::loadMessages(__FILE__);

class EditorMail
{
	/**
	 * Create instance of editor
	 *
	 * @param array $params
	 * @return \Bitrix\Fileman\Block\Editor
	 */
	
	/**
	* <p>Статический метод создает экземпляр редактора.</p>
	*
	*
	* @param array $params  Параметры создаваемого экземпляра.
	*
	* @return \Bitrix\Fileman\Block\Editor 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editormail/createinstance.php
	* @author Bitrix
	*/
	public static function createInstance($params)
	{
		$params['componentFilter'] = array('TYPE' => 'mail');
		$params['previewUrl'] = '/bitrix/admin/fileman_block_editor.php?action=preview_mail';

		$editor = new Editor($params);

		$editor->componentsAsBlocks = array(
			'bitrix:sale.basket.basket.small.mail' => array('NAME' => Loc::getMessage('BLOCK_EDITOR_BLOCK_COMPONENT_BASKET_NAME')),
			'bitrix:sale.personal.order.detail.mail' => array('NAME' => Loc::getMessage('BLOCK_EDITOR_BLOCK_COMPONENT_ORDER_NAME')),
			'bitrix:catalog.top.mail' => array('NAME' => Loc::getMessage('BLOCK_EDITOR_BLOCK_COMPONENT_CATALOG_NAME')),
			'bitrix:sale.discount.coupon.mail' => array('NAME' => Loc::getMessage('BLOCK_EDITOR_BLOCK_COMPONENT_COUPON_NAME')),
			'bitrix:bigdata.recommends.mail' => array('NAME' => Loc::getMessage('BLOCK_EDITOR_BLOCK_COMPONENT_BIGDATA_NAME')),
		);

		$editor->setBlockList(self::getBlockList());

		return $editor;
	}

	/**
	 * Show editor
	 *
	 * @param array $params
	 * @return string
	 */
	
	/**
	* <p>Статический метод показывает редактор.</p>
	*
	*
	* @param array $params  Параметры показа.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editormail/show.php
	* @author Bitrix
	*/
	public static function show($params)
	{
		\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/fileman/block_editor/mail_handlers.js');
		return self::createInstance($params)->show();
	}

	/**
	 * Show preview of content
	 *
	 * @param array $params
	 * @return string
	 */
	
	/**
	* <p>Статический метод показывает превью контента.</p>
	*
	*
	* @param array $params  Параметры показа.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editormail/getpreview.php
	* @author Bitrix
	*/
	public static function getPreview(array $params)
	{
		$site = $params['SITE'];
		$html = $params['HTML'];

		if(isset($params['FIELDS']))
		{
			$fields = $params['FIELDS'];
		}
		else
		{
			$fields = array();
		}
		if(!isset($params['CAN_EDIT_PHP']) || !$params['CAN_EDIT_PHP'])
		{
			$html = \LPA::Process($html);
		}

		if(is_object($GLOBALS["USER"]))
		{
			/* @var $GLOBALS["USER"] \CUser */
			$fields['EMAIL_TO'] = $GLOBALS["USER"]->GetEmail();
			$fields['USER_ID'] = $GLOBALS["USER"]->GetID();
			$fields['NAME'] = $GLOBALS["USER"]->GetFullName();
		}

		$siteDb = SiteTable::getList(array(
			'select' => array('LID', 'SERVER_NAME', 'SITE_NAME', 'CULTURE_CHARSET'=>'CULTURE.CHARSET'),
			'filter' => array('LID' => $site)
		));
		if(!$siteRow = $siteDb->fetch())
		{
			$siteDb = SiteTable::getList(array(
				'select' => array('LID', 'SERVER_NAME', 'SITE_NAME', 'CULTURE_CHARSET'=>'CULTURE.CHARSET'),
				'filter' => array('DEF' => true)
			));
			$siteRow = $siteDb->fetch();
		}

		$fields['SITE_NAME'] = $siteRow['SITE_NAME'];
		$fields['SERVER_NAME'] = $siteRow['SERVER_NAME'];
		$charset = $siteRow['CULTURE_CHARSET'];

		$messageParams = array(
			'FIELDS' => $fields,
			'MESSAGE' => array(
				'BODY_TYPE' => 'html',
				'EMAIL_TO' => '#EMAIL_TO#',
				'MESSAGE' => $html,
			),
			'SITE' => $siteRow['LID'],
			'CHARSET' => $charset,
		);

		$event = new Event("main", "OnBeforeBlockEditorMailPreview", $messageParams);
		$event->send();
		foreach ($event->getResults() as $eventResult)
		{
			if($eventResult->getType() !== EventResult::ERROR)
			{
				$messageParams = array_merge($messageParams, $eventResult->getParameters());
			}
		}

		$message = EventMessageCompiler::createInstance($messageParams);
		$message->compile();
		$html = $message->getMailBody();
		$inlineHtml = StyleInliner::inlineHtml($html);

		$eventParams = array('HTML' => $html, 'INLINE_HTML' => $inlineHtml);
		$event = new Event("main", "OnAfterBlockEditorMailPreview", $eventParams);
		$event->send();
		foreach ($event->getResults() as $eventResult)
		{
			if($eventResult->getType() !== EventResult::ERROR)
			{
				$eventParams = array_merge($eventParams, $eventResult->getParameters());
			}
		}

		//return $eventParams['HTML'];
		return $eventParams['INLINE_HTML'];
	}

	/**
	 * Get block list
	 *
	 * @return array
	 */
	
	/**
	* <p>Статический метод возвращает список блоков.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editormail/getblocklist.php
	* @author Bitrix
	*/
	public static function getBlockList()
	{
		return array(
			array(
				'CODE' => 'text', 'NAME' => Loc::getMessage('BLOCK_EDITOR_BLOCK_TEXT_NAME'),
				'DESC' => Loc::getMessage('BLOCK_EDITOR_BLOCK_TEXT_DESC'),
				'HTML' => '
				<table border="0" cellpadding="0" cellspacing="0" width="100%" class="bxBlockText">
				<tbody class="bxBlockOut">
					<tr>
						<td valign="top" class="bxBlockInn bxBlockInnText">
						<table align="left" border="0" cellpadding="0" cellspacing="0" width="100%">
						<tbody>
							<tr>
								<td valign="top" class="bxBlockPadding bxBlockContentText">
									' . Loc::getMessage('BLOCK_EDITOR_BLOCK_TEXT_EXAMPLE') . '
								</td>
							</tr>
						</tbody>
						</table>
						</td>
					</tr>
				</tbody>
				</table>
			'
			),
			array(
				'CODE' => 'boxedtext', 'NAME' => Loc::getMessage('BLOCK_EDITOR_BLOCK_BOXEDTEXT_NAME'),
				'DESC' => Loc::getMessage('BLOCK_EDITOR_BLOCK_BOXEDTEXT_DESC'),
				'HTML' => '
				<table border="0" cellpadding="0" cellspacing="0" width="100%" class="bxBlockBoxedText">
				<tbody class="bxBlockOut">
					<tr>
						<td valign="top" class="bxBlockInn bxBlockInnBoxedText" >
						<table align="left" border="0" cellpadding="0" cellspacing="0" width="100%">
						<tbody>
							<tr>
								<td valign="top" class="bxBlockPadding">
									<table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" class="bxBlockContentEdge" style="border: 1px solid rgb(153, 153, 153); background-color: rgb(235, 235, 235);">
									<tbody>
										<tr>
											<td valign="top" class="bxBlockPadding bxBlockContentText">
												' . Loc::getMessage('BLOCK_EDITOR_BLOCK_BOXEDTEXT_EXAMPLE') . '
											</td>
										</tr>
									</tbody>
									</table>
								</td>
							</tr>
						</tbody>
						</table>
						</td>
					</tr>
				</tbody>
				</table>
			'
			),
			array(
				'CODE' => 'line', 'NAME' => Loc::getMessage('BLOCK_EDITOR_BLOCK_LINE_NAME'),
				'DESC' => Loc::getMessage('BLOCK_EDITOR_BLOCK_LINE_DESC'),
				'HTML' => '
				<table border="0" cellpadding="0" cellspacing="0" width="100%" class="bxBlockLine">
				<tbody class="bxBlockOut">
					<tr>
						<td valign="top" class="bxBlockInn bxBlockInnLine">
						<table align="left" border="0" cellpadding="0" cellspacing="0" width="100%">
						<tbody>
							<tr>
								<td valign="top" class="bxBlockPadding">
									<span class="bxBlockContentLine" style="height: 2px; background-color: #EBEBEB; display: block;"></span>
								</td>
							</tr>
						</tbody>
						</table>
						</td>
					</tr>
				</tbody>
				</table>
			'
			),
			array(
				'CODE' => 'image', 'NAME' => Loc::getMessage('BLOCK_EDITOR_BLOCK_IMAGE_NAME'),
				'DESC' => Loc::getMessage('BLOCK_EDITOR_BLOCK_IMAGE_DESC'),
				'HTML' => '
				<table border="0" cellpadding="0" cellspacing="0" width="100%" class="bxBlockImage">
				<tbody class="bxBlockOut">
					<tr>
						<td valign="top" class="bxBlockInn bxBlockInnImage">
						<table align="left" border="0" cellpadding="0" cellspacing="0" width="100%">
						<tbody>
							<tr>
								<td valign="top" class="bxBlockPadding bxBlockContentImage" style="text-align: center">
									<a href="#">
										<img align="center" data-bx-editor-def-image="1" src="/bitrix/images/fileman/block_editor/photo-default.png" class="bxImage">
									</a>
								</td>
							</tr>
						</tbody>
						</table>
						</td>
					</tr>
				</tbody>
				</table>
			'
			),
			array(
				'CODE' => 'imagegroup', 'NAME' => Loc::getMessage('BLOCK_EDITOR_BLOCK_IMAGEGROUP_NAME'),
				'DESC' => Loc::getMessage('BLOCK_EDITOR_BLOCK_IMAGEGROUP_DESC'),
				'HTML' => '
				<table border="0" cellpadding="0" cellspacing="0" width="100%" class="bxBlockImageGroup">
				<tbody class="bxBlockOut">
					<tr>
						<td valign="top" class="bxBlockInn bxBlockInnImageGroup">
						<table align="left" border="0" cellpadding="0" cellspacing="0">
						<tbody>
							<tr>
								<td valign="top" class="bxBlockContentImageGroup">
									<table align="left" border="0" cellpadding="0" cellspacing="0" width="260" >
									<tbody>
										<tr>
											<td valign="top" class="bxBlockPadding bxBlockContentImage">
												<a href="#">
													<img align="left" data-bx-editor-def-image="1" src="/bitrix/images/fileman/block_editor/photo-default.png" class="bxImage">
												</a>
											</td>
										</tr>
									</tbody>
									</table>

									<table align="left" border="0" cellpadding="0" cellspacing="0" width="260">
									<tbody>
										<tr>
											<td valign="top" class="bxBlockPadding bxBlockContentImage">
												<a href="#">
													<img align="left" data-bx-editor-def-image="1" src="/bitrix/images/fileman/block_editor/photo-default.png" class="bxImage">
												</a>
											</td>
										</tr>
									</tbody>
									</table>
								</td>
							</tr>
						</tbody>
						</table>
						</td>
					</tr>
				</tbody>
				</table>
			'
			),
			array(
				'CODE' => 'boxedimage', 'NAME' => Loc::getMessage('BLOCK_EDITOR_BLOCK_BOXEDIMAGE_NAME'),
				'DESC' => Loc::getMessage('BLOCK_EDITOR_BLOCK_BOXEDIMAGE_DESC'),
				'HTML' => '
				<table border="0" cellpadding="0" cellspacing="0" width="100%" class="bxBlockBoxedImage">
				<tbody class="bxBlockOut">
					<tr>
						<td valign="top" class="bxBlockInn bxBlockInnBoxedImage">
						<table align="left" border="0" cellpadding="0" cellspacing="0" width="100%">
						<tbody>
							<tr>
								<td valign="top" class="bxBlockPadding">
									<table width="100%" align="left" border="0" cellpadding="0" cellspacing="0" class="bxBlockContentEdge" style="border: 1px solid rgb(153, 153, 153); background-color: rgb(235, 235, 235);">
									<tbody>
										<tr>
											<td valign="top" class="bxBlockPadding bxBlockContentImage" style="text-align: center;">
												<a href="#">
													<img align="center" data-bx-editor-def-image="1" src="/bitrix/images/fileman/block_editor/photo-default.png" class="bxImage">
												</a>
											</td>
										</tr>
										<tr>
											<td valign="top" class="bxBlockPadding bxBlockContentText" style="text-align: center;">
												' . Loc::getMessage('BLOCK_EDITOR_BLOCK_TEXT_EXAMPLE') . '
											</td>
										</tr>
									</tbody>
									</table>
								</td>
							</tr>
						</tbody>
						</table>
						</td>
					</tr>
				</tbody>
				</table>
			'
			),
			array(
				'CODE' => 'imagetext', 'NAME' => Loc::getMessage('BLOCK_EDITOR_BLOCK_IMAGETEXT_NAME'),
				'DESC' => Loc::getMessage('BLOCK_EDITOR_BLOCK_IMAGETEXT_DESC'),
				'HTML' => '
				<table border="0" cellpadding="0" cellspacing="0" width="100%" class="bxBlockImageText">
				<tbody class="bxBlockOut">
					<tr>
						<td valign="top" class="bxBlockInn bxBlockInnImageText">
							<table align="left" border="0" cellpadding="0" cellspacing="0" class="bxBlockContentItemImageText" width="290">
							<tbody>
								<tr>
									<td valign="top" class="bxBlockPadding bxBlockContentImage">
										<a href="#">
											<img data-bx-editor-def-image="1" src="/bitrix/images/fileman/block_editor/photo-default.png" class="bxImage">
										</a>
									</td>
								</tr>
							</tbody>
							</table>

							<table align="left" border="0" cellpadding="0" cellspacing="0" class="bxBlockContentItemImageText" width="290">
							<tbody>
								<tr>
									<td valign="top" class="bxBlockPadding bxBlockContentText">
										' . Loc::getMessage('BLOCK_EDITOR_BLOCK_TEXT_EXAMPLE') . '
									</td>
								</tr>
							</tbody>
							</table>
						</td>
					</tr>
				</tbody>
				</table>
			'
			),
			array(
				'CODE' => 'button', 'NAME' => Loc::getMessage('BLOCK_EDITOR_BLOCK_BUTTON_NAME'),
				'DESC' => Loc::getMessage('BLOCK_EDITOR_BLOCK_BUTTON_DESC'),
				'HTML' => '
				<table border="0" cellpadding="0" cellspacing="0" width="100%" class="bxBlockButton">
				<tbody class="bxBlockOut">
					<tr>
						<td valign="top" class="bxBlockPadding bxBlockInn bxBlockInnButton">
						<table align="center" border="0" cellpadding="0" cellspacing="0" class="bxBlockContentButtonEdge">
						<tbody>
							<tr>
								<td valign="top">
									<a
										class="bxBlockContentButton"
										title="' . Loc::getMessage('BLOCK_EDITOR_BLOCK_BUTTON_EXAMPLE') . '"
										href="/"
										target="_blank"
									>
										' . Loc::getMessage('BLOCK_EDITOR_BLOCK_BUTTON_EXAMPLE') . '
									</a>
								</td>
							</tr>
						</tbody>
						</table>
						</td>
					</tr>
				</tbody>
				</table>
			'
			),
			array(
				'CODE' => 'code', 'NAME' => Loc::getMessage('BLOCK_EDITOR_BLOCK_CODE_NAME'),
				'DESC' => Loc::getMessage('BLOCK_EDITOR_BLOCK_CODE_DESC'),
				'HTML' => '
				<table border="0" cellpadding="0" cellspacing="0" width="100%" class="bxBlockCode">
				<tbody class="bxBlockOut">
					<tr>
						<td valign="top" class="bxBlockPadding bxBlockInn bxBlockInnCode">
							' . Loc::getMessage('BLOCK_EDITOR_BLOCK_CODE_EXAMPLE') . '
						</td>
					</tr>
				</tbody>
				</table>'
			),
			array(
				'CODE' => 'footer', 'TYPE' => 'text', 'NAME' => Loc::getMessage('BLOCK_EDITOR_BLOCK_FOOTER_NAME'),
				'DESC' => Loc::getMessage('BLOCK_EDITOR_BLOCK_FOOTER_DESC'),
				'HTML' => '
				<table border="0" cellpadding="0" cellspacing="0" width="100%" class="bxBlockText">
				<tbody class="bxBlockOut">
					<tr>
						<td valign="top" class="bxBlockInn bxBlockInnText" >
						<table align="left" border="0" cellpadding="0" cellspacing="0" width="100%">
						<tbody>
							<tr>
								<td valign="top" class="bxBlockPadding bxBlockContentText" style="text-align: center;">
									<br><a href="#UNSUBSCRIBE_LINK#">' . Loc::getMessage('BLOCK_EDITOR_BLOCK_FOOTER_EXAMPLE') . '</a>
								</td>
							</tr>
						</tbody>
						</table>
						</td>
					</tr>
				</tbody>
				</table>
			'
			),
			array(
				'CODE' => 'social', 'NAME' => Loc::getMessage('BLOCK_EDITOR_BLOCK_SOCIAL_NAME'),
				'DESC' => Loc::getMessage('BLOCK_EDITOR_BLOCK_SOCIAL_DESC'),
				'HTML' => '
				<table border="0" cellpadding="0" cellspacing="0" width="100%" class="bxBlockSocial">
				<tbody class="bxBlockOut">
					<tr>
						<td valign="top" class="bxBlockInn bxBlockInnSocial" >

							<table align="center" border="0" cellpadding="0" cellspacing="0" class="bxBlockContentEdgeSocial">
							<tbody>
								<tr>
									<td valign="top" class="bxBlockPadding">

										<table align="left" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate !important; margin-right: 10px;">
										<tbody>
											<tr>
												<td valign="top" class="" style="padding-top: 5px; padding-right: 10px; padding-bottom: 5px; padding-left: 10px;">
													<a
														class="bxBlockContentSocial"
														href="http://vk.com/"
														target="_blank"
														style="font-weight: bold; color: #626262; letter-spacing: normal;line-height: 100%;text-align: center; text-decoration: underline; font-size: 12px;"
													>' . Loc::getMessage('BLOCK_EDITOR_BLOCK_SOCIAL_VK') . '</a>
												</td>
											</tr>
										</tbody>
										</table>

										<table align="left" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate !important; margin-right: 10px;">
										<tbody>
											<tr>
												<td valign="top" class="" style="padding-top: 5px; padding-right: 10px; padding-bottom: 5px; padding-left: 10px; font-size: 12px;">
													<a
														class="bxBlockContentSocial"
														href="http://facebook.com/"
														target="_blank"
														style="font-weight: bold; color: #626262; letter-spacing: normal;line-height: 100%;text-align: center; text-decoration: underline; font-size: 12px;"
													>' . Loc::getMessage('BLOCK_EDITOR_BLOCK_SOCIAL_FACEBOOK') . '</a>
												</td>
											</tr>
										</tbody>
										</table>

										<table align="left" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate !important; margin-right: 10px;">
										<tbody>
											<tr>
												<td valign="top" class="" style="padding-top: 5px; padding-right: 10px; padding-bottom: 5px; padding-left: 10px; font-size: 12px;">
													<a
														class="bxBlockContentSocial"
														href="http://twitter.com/"
														target="_blank"
														style="font-weight: bold; color: #626262; letter-spacing: normal;line-height: 100%;text-align: center; text-decoration: underline; font-size: 12px;"
													>' . Loc::getMessage('BLOCK_EDITOR_BLOCK_SOCIAL_TWITTER') . '</a>
												</td>
											</tr>
										</tbody>
										</table>
									</td>
								</tr>
							</tbody>
							</table>

						</td>
					</tr>
				</tbody>
				</table>
			'
			),
		);
	}
}
