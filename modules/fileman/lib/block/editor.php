<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Fileman\Block;

use Bitrix\Main\Application;
use Bitrix\Main\Web\DOM\Document;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\DOM\CssParser;
use Bitrix\Main\Text\HtmlFilter;

Loc::loadMessages(__FILE__);

class Editor
{
	CONST SLICE_SECTION_ID = 'BX_BLOCK_EDITOR_EDITABLE_SECTION';
	CONST BLOCK_PLACE_ATTR = 'data-bx-block-editor-place';
	CONST STYLIST_TAG_ATTR = 'data-bx-stylist-container';
	CONST BLOCK_PLACE_ATTR_DEF_VALUE = 'body';
	CONST BLOCK_COUNT_PER_PAGE = 16;

	public $id;
	protected $site;
	protected $url;
	protected $previewUrl;
	protected $templateType;
	protected $templateId;
	protected $charset;
	protected $isTemplateMode;
	protected $isUserHavePhpAccess;
	protected $ownResultId;

	/*
	 * block list
	*/
	public $tools = array();

	/*
	 * block list
	*/
	public $blocks = array();

	protected $componentFilter = array();

	public $componentsAsBlocks = array();

	public $previewModes = array();

	public $tabs = array();

	public $uiPatterns = array(
		'main' => <<<HTML
		#TEXTAREA#
		<div id="bx-block-editor-container-#id#" class="bx-block-editor-container">
			<div class="button-panel">
				#tabs#

				<span class="bx-editor-block-btn-close" title="#MESS_BTN_MIN#"></span>
				<span class="bx-editor-block-btn-full" title="#MESS_BTN_MAX#"></span>
			</div>
			#panels#
		</div>
HTML
		,
		'block' => <<<HTML
		<li data-bx-block-editor-block-status="blank"
			data-bx-block-editor-block-type="#code#"
			class="bx-editor-typecode-#code_class# bx-editor-type-#type_class# bx-block-editor-i-block-list-item"
			title="#desc#"
			>
			<span class="bx-block-editor-i-block-list-item-icon"></span>
			<span class="bx-block-editor-i-block-list-item-name">#name#</span>
		</li>
HTML
		,
		'block_page' => <<<HTML
		<ul class="bx-block-editor-i-block-list">
			#blocks#
		</ul>
HTML
		,
		'tool' => <<<HTML
		<div class="bx-editor-block-tools" data-bx-editor-tool="#group#:#id#">
			<div class="caption">#name#:</div>
			<div class="item">#html#</div>
		</div>
HTML
		,
		'device' => <<<HTML
		<div class="device #class#" data-bx-preview-device-class="#class#" data-bx-preview-device-width="#width#" data-bx-preview-device-height="#height#">
			<span>#MESS_NAME#</span>
		</div>
HTML
		,
		'tab' => <<<HTML
			<span class="bx-editor-block-btn bx-editor-block-btn-#code# #tab_active#">#name#</span>
HTML
		,
		'tab_active' => 'bx-editor-block-btn-active'
		,
		'panel' => <<<HTML
			<div class="bx-editor-block-panel #code#-panel" #panel_hidden#>#html#</div>
HTML
		,
		'panel_hidden' => 'style="display: none;"'
		,
		'panel-edit' => <<<HTML
			<div class="visual-part">
				<div class="shadow">
					<div class="edit-text"></div>
				</div>
				<iframe id="bx-block-editor-iframe-#id#" src="" style="border: none;" width="100%" height="100%"></iframe>
			</div>
			<div class="dialog-part">
				<div style="overflow-x: hidden;">
					<div class="block-list-cont">
						<div class="block-list-tabs">

							<div class="bx-editor-block-tabs">
								<span class="tab-list">
									<span class="tab blocks active">#MESS_BLOCKS#</span>
									<span class="tab styles">#MESS_STYLES#</span>
								</span>
							</div>

							<div class="edit-panel-tabs-style">
								<ul class="bx-block-editor-i-place-list" data-bx-place-name="item"></ul>
							</div>
							<div style="clear: both;"></div>

							<div class="edit-panel-tabs-block">

								<div>#blocks#</div>

								<div style="clear: both;"></div>
								<div class="block-pager adm-nav-pages-block">
									<span class="adm-nav-page adm-nav-page-prev"></span>
									<span class="adm-nav-page adm-nav-page-next"></span>
								</div>

							</div>


							<div style="clear: both;"></div>
						</div>
						<div>

						</div>
					</div>
				</div>
			</div>
			<div class="block-edit-cont">
				<div class="bx-editor-block-form-head">
					<div class="bx-editor-block-form-head-btn">
						<a class="bx-editor-block-tools-btn bx-editor-block-tools-close" title="#MESS_TOOL_SAVE_TITLE#">#MESS_TOOL_SAVE#</a>
						<a class="bx-editor-block-tools-btn bx-editor-block-tools-cancel" title="#MESS_TOOL_CANCEL_TITLE#">#MESS_TOOL_CANCEL#</a>
					</div>

					<div class="block-edit-tabs">
						<span data-bx-block-editor-settings-tab="cont" class="bx-editor-block-tab active">#MESS_TOOL_CONTENT#</span>
						<span data-bx-block-editor-settings-tab="style" class="bx-editor-block-tab">#MESS_TOOL_STYLES#</span>
						<span data-bx-block-editor-settings-tab="prop" class="bx-editor-block-tab">#MESS_TOOL_SETTINGS#</span>
					</div>
				</div>

				<div class="block-edit-form-empty">
					#MESS_TOOL_EMPTY#
				</div>

				<div class="block-edit-form">
					#tools#
				</div>
			</div>
HTML
		,
		'panel-preview' => <<<HTML
		<div class="bx-block-editor-preview-container">
			<div class="shadow">
				<div class="edit-text"></div>
				<div class="error-text">#MESS_ACCESS_DENIED#</div>
			</div>
			<div class="devices">
				#devices#
			</div>

			<center>
				<div class="iframe-wrapper">
					<iframe class="preview-iframe" src=""></iframe>
				</div>
			</center>
		</div>

		<div style="clear:both;"></div>
HTML
		,
		'panel-get-html' => <<<HTML
		<textarea style="width: 100%; height: 100%; min-height: 400px;" onfocus="this.select()"></textarea>
HTML
	);

	/**
	 * Return editor object
	 *
	 * @param array $params
	 * @return Editor
	 */
	
	/**
	* <p>Статический метод возвращает объект редактора.</p>
	*
	*
	* @param array $params  Параметры возвращаемого объекта.
	*
	* @return \Bitrix\Fileman\Block\Editor 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/createinstance.php
	* @author Bitrix
	*/
	public static function createInstance($params)
	{
		return new static($params);
	}

	/**
	 * Create editor object.
	 *
	 * @param array $params
	 */
	
	/**
	* <p>Нестатический метод создает объект редактора.</p>
	*
	*
	* @param array $params  Параметры создаваемого объекта.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/__construct.php
	* @author Bitrix
	*/
	public function __construct($params)
	{
		$this->id = $params['id'];
		$this->url = $params['url'];
		$this->previewUrl = isset($params['previewUrl']) ? $params['previewUrl'] : '/bitrix/admin/fileman_block_editor.php?action=preview';
		$this->templateType = $params['templateType'];
		$this->templateId = $params['templateId'];
		$this->site = $params['site'];
		$this->charset = $params['charset'];
		$this->isTemplateMode = isset($params['isTemplateMode']) ? (bool) $params['isTemplateMode'] : false;
		$this->isUserHavePhpAccess = isset($params['isUserHavePhpAccess']) ? (bool) $params['isUserHavePhpAccess'] : false;
		$this->ownResultId = isset($params['own_result_id']) ? $params['own_result_id'] : true;

		$this->componentFilter = isset($params['componentFilter']) ? $params['componentFilter'] : array();
		$this->setToolList($this->getDefaultToolList());

		$this->previewModes = array(
			array('CLASS' => 'phone', 'NAME' => Loc::getMessage('BLOCK_EDITOR_PREVIEW_MODE_PHONE'), 'WIDTH' => 320, 'HEIGHT' => 480),
			array('CLASS' => 'tablet', 'NAME' => Loc::getMessage('BLOCK_EDITOR_PREVIEW_MODE_TABLET'), 'WIDTH' => 768, 'HEIGHT' => 1024),
			array('CLASS' => 'desktop', 'NAME' => Loc::getMessage('BLOCK_EDITOR_PREVIEW_MODE_DESKTOP'), 'WIDTH' => 1024, 'HEIGHT' => 768),
		);

		$this->tabs = array(
			'edit' => array('NAME' => Loc::getMessage('BLOCK_EDITOR_TABS_EDIT'), 'ACTIVE' => true),
			'preview' => array('NAME' => Loc::getMessage('BLOCK_EDITOR_TABS_PREVIEW'), 'ACTIVE' => false),
			'get-html' => array('NAME' => Loc::getMessage('BLOCK_EDITOR_TABS_HTML'), 'ACTIVE' => false),
		);
	}


	/**
	 * Set custom blocks
	 *
	 * @param array $blocks
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает пользовательский список блоков.</p>
	*
	*
	* @param array $blocks  Список блоков.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/setblocklist.php
	* @author Bitrix
	*/
	public function setBlockList(array $blocks)
	{
		$this->blocks = $blocks;

		if(!is_array($this->blocks))
		{
			$this->blocks = array();
		}

		foreach($this->blocks as $key => $block)
		{
			if(!isset($block['TYPE']))
			{
				$block['TYPE'] = $block['CODE'];
			}

			$block['IS_COMPONENT'] = false;
			$block['CLASS'] = $block['CODE'];
			$this->blocks[$key] = $block;
		}

		$componentList = $this->getComponentList();
		$componentsNotAsBlocks = array();
		foreach($componentList as $component)
		{
			if(!isset($this->componentsAsBlocks[$component['NAME']]))
			{
				$componentsNotAsBlocks[] = array(
					'TYPE' => 'component',
					'IS_COMPONENT' => true,
					'CODE' => $component['NAME'],
					'NAME' => $component['TITLE'],
					'DESC' => $component['TITLE'] . ".\n" . $component['DESCRIPTION'],
					'HTML' => ''
				);
			}
			else
			{
				$interfaceName = $this->componentsAsBlocks[$component['NAME']]['NAME'];
				$this->blocks[] = array(
					'TYPE' => 'component',
					'IS_COMPONENT' => false,
					'CODE' => $component['NAME'],
					'NAME' => $interfaceName ? $interfaceName : $component['TITLE'],
					'DESC' => $component['DESCRIPTION'],
					'HTML' => ''
				);
			}
		}
		$this->blocks = array_merge($this->blocks, $componentsNotAsBlocks);

	}

	/**
	 * Set custom tools
	 *
	 * @param array $tools
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает пользовательский список инструментов.</p>
	*
	*
	* @param array $tools  Список инструментов.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/settoollist.php
	* @author Bitrix
	*/
	public function setToolList(array $tools)
	{
		$this->tools = $tools;
	}

	/**
	 * Return list of default blocks
	 *
	 * @return array
	 */
	
	/**
	* <p>Нестатический метод возвращает список стандартных блоков.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/getdefaultblocklist.php
	* @author Bitrix
	*/
	static public function getDefaultBlockList()
	{
		return array(
			array(
				'CODE' => 'text',
				'NAME' => Loc::getMessage('BLOCK_EDITOR_BLOCK_TEXT_NAME'),
				'DESC' => Loc::getMessage('BLOCK_EDITOR_BLOCK_TEXT_DESC'),
				'HTML' => Loc::getMessage('BLOCK_EDITOR_BLOCK_TEXT_EXAMPLE')
			),
		);
	}

	/**
	 * Return list of default tools, uses for block changing
	 *
	 * @return array
	 */
	
	/**
	* <p>Нестатический метод возвращает список стандартных инструментов, используемых для изменения блоков.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/getdefaulttoollist.php
	* @author Bitrix
	*/
	public function getDefaultToolList()
	{
		$isUserHavePhpAccess = $this->isUserHavePhpAccess;


		$resultList = array();

		$resultList[] = array(
			'GROUP' => 'cont',
			'ID' => 'html-raw',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_HTML_RAW'),
			'HTML' => '<textarea style="width:600px; height: 400px;" data-bx-editor-tool-input="item"></textarea>',
		);

		$resultList[] = array(
			'GROUP' => 'cont',
			'ID' => 'src',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_SRC'),
			'HTML' => '<input type="hidden" data-bx-editor-tool-input="item" value="">'
				. \Bitrix\Main\UI\FileInput::createInstance((array(
					"id" => "BX_BLOCK_EDITOR_SRC_" . $this->id,
					"name" => "NEW_FILE_EDITOR[n#IND#]",
					"upload" => true,
					"medialib" => true,
					"fileDialog" => true,
					"cloud" => true
				)))->show()
		);

		$resultList[] = array(
			'GROUP' => 'cont',
			'ID' => 'title',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_TITLE'),
			'HTML' => Tools::getControlInput(),
		);

		$resultList[] = array(
			'GROUP' => 'cont',
			'ID' => 'href',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_HREF'),
			'HTML' => Tools::getControlInput(),
		);

		\Bitrix\Main\Loader::includeModule('fileman');
		ob_start();
		?>
		<div class="column" data-bx-editor-column="item">
			<span data-bx-editor-column-number="1"><?=Loc::getMessage('BLOCK_EDITOR_TOOL_COLUMN')?> 1</span>
			<span data-bx-editor-column-number="2"><?=Loc::getMessage('BLOCK_EDITOR_TOOL_COLUMN')?> 2</span>
			<span data-bx-editor-column-number="3"><?=Loc::getMessage('BLOCK_EDITOR_TOOL_COLUMN')?> 3</span>
			<span data-bx-editor-column-number="4"><?=Loc::getMessage('BLOCK_EDITOR_TOOL_COLUMN')?> 4</span>
		</div>
		<?
		\CFileMan::AddHTMLEditorFrame(
			'BX_BLOCK_EDITOR_CONTENT_' . $this->id,
			'',
			false,
			"html",
			array(
				'height' => '200',
				'width' => '100%'
			),
			"N",
			0,
			"",
			'',//'data-bx-editor-tool-input="content"',
			false,
			!$isUserHavePhpAccess,
			false,
			array(
				//'templateID' => $str_SITE_TEMPLATE_ID,
				'componentFilter' => $this->componentFilter,
				'limit_php_access' => !$isUserHavePhpAccess,
				'hideTypeSelector' => true,
				'minBodyWidth' => '420',
				'normalBodyWidth' => '420',
			)
		);

		$resultList[] = array(
			'GROUP' => 'cont',
			'ID' => 'content',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_CONTENT'),
			'HTML' => '<input type="hidden" data-bx-editor-tool-input="item" value="">' . ob_get_clean()
		);

		ob_start();
		?>
		<script type="text/template" id="template-social-item">
			<table style="background-color: #E9E9E9;">
				<tr>
					<td><?=Loc::getMessage('BLOCK_EDITOR_TOOL_SOCIAL_CONTENT_ADDRESS')?></td>
					<td>
						<input class="href" type="text" value="#href#">
						<select class="preset">
							<option value=""><?=Loc::getMessage('BLOCK_EDITOR_TOOL_SOCIAL_CONTENT_SELECT')?></option>
							<option value="http://#SERVER_NAME#/"><?=Loc::getMessage('BLOCK_EDITOR_TOOL_SOCIAL_CONTENT_OURSITE')?></option>
							<option value="http://vk.com/"><?=Loc::getMessage('BLOCK_EDITOR_TOOL_SOCIAL_CONTENT_VK')?></option>
							<option value="http://ok.ru/"><?=Loc::getMessage('BLOCK_EDITOR_TOOL_SOCIAL_CONTENT_OK')?></option>
							<option value="http://facebook.com/"><?=Loc::getMessage('BLOCK_EDITOR_TOOL_SOCIAL_CONTENT_FACEBOOK')?></option>
							<option value="http://twitter.com/"><?=Loc::getMessage('BLOCK_EDITOR_TOOL_SOCIAL_CONTENT_TWITTER')?></option>
							<option value="http://"><?=Loc::getMessage('BLOCK_EDITOR_TOOL_SOCIAL_CONTENT_SITE')?></option>
							<option value="mailto:"><?=Loc::getMessage('BLOCK_EDITOR_TOOL_SOCIAL_CONTENT_EMAIL')?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td><?=Loc::getMessage('BLOCK_EDITOR_TOOL_SOCIAL_CONTENT_NAME')?></td>
					<td>
						<input class="name" type="text" value="#name#">
						<input class="delete" type="button" value="<?=Loc::getMessage('BLOCK_EDITOR_TOOL_SOCIAL_CONTENT_BTN_DELETE')?>">
					</td>
				</tr>
			</table>
			<br/>
		</script>
		<div class="container"></div>
		<input class="add" type="button" value="<?=Loc::getMessage('BLOCK_EDITOR_TOOL_SOCIAL_CONTENT_BTN_ADD')?>">
		<?
		$resultList[] = array(
			'GROUP' => 'cont',
			'ID' => 'social_content',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_SOCIAL_CONTENT'),
			'HTML' => '<input type="hidden" data-bx-editor-tool-input="item" value="">' . ob_get_clean()
		);

		$resultList[] = array(
			'GROUP' => 'cont',
			'ID' => 'button_caption',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_BUTTON_CAPTION'),
			'HTML' => Tools::getControlInput(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'font-size',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_FONT_SIZE'),
			'HTML' => Tools::getControlFontSize(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'text-align',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_TEXT_ALIGN'),
			'HTML' => Tools::getControlTextAlign(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'border',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_BORDER'),
			'HTML' => '<input type="hidden" data-bx-editor-tool-input="item" id="block_editor_style_border">
				<select id="block_editor_style_border_style">
					<option value="">' . Loc::getMessage('BLOCK_EDITOR_COMMON_NO') . '</option>
					<option value="solid">' . Loc::getMessage('BLOCK_EDITOR_TOOL_BORDER_SOLID') . '</option>
					<option value="dashed">' . Loc::getMessage('BLOCK_EDITOR_TOOL_BORDER_DASHED') . '</option>
					<option value="dotted">' . Loc::getMessage('BLOCK_EDITOR_TOOL_BORDER_DOTTED') . '</option>
				</select>
				<input id="block_editor_style_border_width" type="text">
				<input id="block_editor_style_border_color" type="text" class="bx-editor-color-picker">
				<span class="bx-editor-color-picker-view"></span>',
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'background-color',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_BACKGROUND_COLOR'),
			'HTML' => Tools::getControlColor(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'border-radius',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_BORDER_RADIUS'),
			'HTML' => Tools::getControlBorderRadius(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'color',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_COLOR'),
			'HTML' => Tools::getControlColor(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'font-family',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_FONT_FAMILY'),
			'HTML' => Tools::getControlFontFamily(),
		);

		$resultList[] = array(
			'GROUP' => 'prop',
			'ID' => 'align',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_ALIGN'),
			'HTML' => Tools::getControlTextAlign(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'text-decoration',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_TEXT_DECORATION'),
			'HTML' => Tools::getControlTextDecoration(),
		);

		$resultList[] = array(
			'GROUP' => 'prop',
			'ID' => 'align',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_ALIGN'),
			'HTML' => Tools::getControlTextAlign(),
		);

		$resultList[] = array(
			'GROUP' => 'prop',
			'ID' => 'imagetextalign',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_IMAGETEXTALIGN'),
			'HTML' => Tools::getControlSelect(array(
				'left' => Loc::getMessage('BLOCK_EDITOR_CTRL_ALIGN_LEFT'),
				'right' => Loc::getMessage('BLOCK_EDITOR_CTRL_ALIGN_RIGHT')
			), false)
		);

		$resultList[] = array(
			'GROUP' => 'prop',
			'ID' => 'imagetextpart',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_IMAGETEXTPART'),
			'HTML' => Tools::getControlSelect(
				array(
					'1/4' => Loc::getMessage('BLOCK_EDITOR_TOOL_IMAGETEXTPART14'),
					'1/3' => Loc::getMessage('BLOCK_EDITOR_TOOL_IMAGETEXTPART13'),
					'1/2' => Loc::getMessage('BLOCK_EDITOR_TOOL_IMAGETEXTPART12'),
					'2/3' => Loc::getMessage('BLOCK_EDITOR_TOOL_IMAGETEXTPART23')
				),
				false)
		);

		$resultList[] = array(
			'GROUP' => 'prop',
			'ID' => 'height',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_HEIGHT'),
			'HTML' => Tools::getControlInput(),
		);

		$resultList[] = array(
			'GROUP' => 'prop',
			'ID' => 'margin-top',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_MARGIN_TOP'),
			'HTML' => Tools::getControlPaddingBottoms(),
		);

		$resultList[] = array(
			'GROUP' => 'prop',
			'ID' => 'margin-bottom',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_MARGIN_BOTTOM'),
			'HTML' => Tools::getControlPaddingBottoms(),
		);

		$resultList[] = array(
			'GROUP' => 'prop',
			'ID' => 'groupimage-view',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_GROUPIMAGE_VIEW'),
			'HTML' => Tools::getControlSelect(
				array(
					'' => Loc::getMessage('BLOCK_EDITOR_TOOL_GROUPIMAGE_VIEW_2COL'),
					'1' => Loc::getMessage('BLOCK_EDITOR_TOOL_GROUPIMAGE_VIEW_1COL')
				),
				false
			),
		);

		$resultList[] = array(
			'GROUP' => 'prop',
			'ID' => 'column-count',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_COLUMN_COUNT'),
			'HTML' => Tools::getControlSelect(array('1' => '1', '2' => '2', '3' => '3'), false),
		);

		$resultList[] = array(
			'GROUP' => 'prop',
			'ID' => 'paddings',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_PADDINGS'),
			'HTML' => Tools::getControlSelect(
				array(
					'Y' => Loc::getMessage('BLOCK_EDITOR_TOOL_PADDINGS_STANDARD'),
					'N' => Loc::getMessage('BLOCK_EDITOR_TOOL_PADDINGS_WITHOUT')
				),
				false
			),
		);

		$resultList[] = array(
			'GROUP' => 'prop',
			'ID' => 'wide',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_WIDE'),
			'HTML' => Tools::getControlSelect(
				array(
					'N' => Loc::getMessage('BLOCK_EDITOR_TOOL_WIDE_N'),
					'Y' => Loc::getMessage('BLOCK_EDITOR_TOOL_WIDE_Y')
				)
				, false
			),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'bx-stylist-bgcolor',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_BACKGROUND_COLOR'),
			'HTML' => Tools::getControlColor(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'bx-stylist-padding-top',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_MARGIN_TOP'),
			'HTML' => Tools::getControlPaddingBottoms(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'bx-stylist-padding-bottom',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_MARGIN_BOTTOM'),
			'HTML' => Tools::getControlPaddingBottoms(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'bx-stylist-text-color',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_STYLIST_TEXT') . ': ' . Loc::getMessage('BLOCK_EDITOR_TOOL_COLOR'),
			'HTML' => Tools::getControlColor(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'bx-stylist-text-font-family',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_STYLIST_TEXT') . ': ' . Loc::getMessage('BLOCK_EDITOR_TOOL_FONT_FAMILY'),
			'HTML' => Tools::getControlFontFamily(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'bx-stylist-text-font-size',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_STYLIST_TEXT') . ': ' . Loc::getMessage('BLOCK_EDITOR_TOOL_FONT_SIZE'),
			'HTML' => Tools::getControlFontSize(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'bx-stylist-text-font-weight',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_STYLIST_TEXT') . ': ' . Loc::getMessage('BLOCK_EDITOR_TOOL_FONT_WEIGHT'),
			'HTML' => Tools::getControlFontWeight(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'bx-stylist-text-line-height',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_STYLIST_TEXT') . ': ' . Loc::getMessage('BLOCK_EDITOR_TOOL_LINE_HEIGHT'),
			'HTML' => Tools::getControlLineHeight(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'bx-stylist-text-text-align',
			'NAME' =>  Loc::getMessage('BLOCK_EDITOR_TOOL_STYLIST_TEXT') . ': ' . Loc::getMessage('BLOCK_EDITOR_TOOL_TEXT_ALIGN'),
			'HTML' => Tools::getControlTextAlign(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'bx-stylist-a-color',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_STYLIST_LINK') . ': ' . Loc::getMessage('BLOCK_EDITOR_TOOL_COLOR'),
			'HTML' => Tools::getControlColor(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'bx-stylist-a-font-weight',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_STYLIST_LINK') . ': ' . Loc::getMessage('BLOCK_EDITOR_TOOL_FONT_WEIGHT'),
			'HTML' => Tools::getControlFontWeight(),
		);

		$resultList[] = array(
			'GROUP' => 'style',
			'ID' => 'bx-stylist-a-text-decoration',
			'NAME' => Loc::getMessage('BLOCK_EDITOR_TOOL_STYLIST_LINK') . ': ' . Loc::getMessage('BLOCK_EDITOR_TOOL_TEXT_DECORATION'),
			'HTML' => Tools::getControlTextDecoration(),
		);

		for($i = 1; $i <= 4; $i++)
		{
			$resultList[] = array(
				'GROUP' => 'style',
				'ID' => 'bx-stylist-h' . $i . '-color',
				'NAME' => 'H' . $i . ': ' . Loc::getMessage('BLOCK_EDITOR_TOOL_COLOR'),
				'HTML' => Tools::getControlColor(),
			);

			$resultList[] = array(
				'GROUP' => 'style',
				'ID' => 'bx-stylist-h' . $i . '-font-size',
				'NAME' => 'H' . $i . ': ' . Loc::getMessage('BLOCK_EDITOR_TOOL_FONT_SIZE'),
				'HTML' => Tools::getControlFontSize(),
			);

			$resultList[] = array(
				'GROUP' => 'style',
				'ID' => 'bx-stylist-h' . $i . '-font-weight',
				'NAME' => 'H' . $i . ': ' . Loc::getMessage('BLOCK_EDITOR_TOOL_FONT_WEIGHT'),
				'HTML' => Tools::getControlFontWeight(),
			);

			$resultList[] = array(
				'GROUP' => 'style',
				'ID' => 'bx-stylist-h' . $i . '-line-height',
				'NAME' => 'H' . $i . ': ' . Loc::getMessage('BLOCK_EDITOR_TOOL_LINE_HEIGHT'),
				'HTML' => Tools::getControlLineHeight(),
			);

			$resultList[] = array(
				'GROUP' => 'style',
				'ID' => 'bx-stylist-h' . $i . '-text-align',
				'NAME' => 'H' . $i . ': ' . Loc::getMessage('BLOCK_EDITOR_TOOL_TEXT_ALIGN'),
				'HTML' => Tools::getControlTextAlign(),
			);
		}

		return $resultList;
	}

	/**
	 * Return html of interface part.
	 *
	 * @param string $id
	 * @param array $values
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает HTML части интерфейса.</p>
	*
	*
	* @param string $id  Код шаблона.
	*
	* @param array $values  Массив заменяемых полей.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/getui.php
	* @author Bitrix
	*/
	public function getUI($id, array $values)
	{
		if(!array_key_exists($id, $this->uiPatterns) || strlen(trim($this->uiPatterns[$id])) === 0)
		{
			return '';
		}

		$placeholders = array_keys($values);
		$placeholders = '#' . implode('#,#', $placeholders) . '#';
		$placeholders = explode(',', $placeholders);

		return str_replace($placeholders, array_values($values), $this->uiPatterns[$id]);
	}

	/**
	 * Return html of editor interface without resources.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает HTML интерфейса редактора без css и js.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/showeditor.php
	* @author Bitrix
	*/
	public function showEditor()
	{
		$textArea = '';
		$panels = '';
		$tabs = '';
		$blocks = '';
		$tools = '';
		$devices = '';


		foreach(array_chunk($this->blocks, static::BLOCK_COUNT_PER_PAGE) as $blocksPerPage)
		{
			$blocksForPage = '';
			foreach($blocksPerPage as $block)
			{
				$blocksForPage .= $this->getUI('block', array(
					'type_class' => htmlspecialcharsbx($block['IS_COMPONENT'] ? 'component' : 'blockcomponent'),
					'code_class' => htmlspecialcharsbx(str_replace(array(':', '.'), array('-', '-'), $block['CODE'])),
					'type' => htmlspecialcharsbx($block['TYPE']),
					'code' => htmlspecialcharsbx($block['CODE']),
					'name' => htmlspecialcharsbx($block['NAME']),
					'desc' => htmlspecialcharsbx($block['DESC']),
				));
			}

			$blocks .= $this->getUI('block_page', array('blocks' => $blocksForPage));
		}

		foreach($this->tools as $tool)
		{
			$tools .= $this->getUI('tool', array(
				'group' => htmlspecialcharsbx($tool['GROUP']),
				'id' => htmlspecialcharsbx($tool['ID']),
				'name' => htmlspecialcharsbx($tool['NAME']),
				'html' => $tool['HTML'],
			));
		}

		foreach($this->previewModes as $mode)
		{
			$devices .= $this->getUI('device', array(
				'MESS_NAME' => strtoupper(htmlspecialcharsbx($mode['NAME'])),
				'class' => htmlspecialcharsbx($mode['CLASS']),
				'width' => htmlspecialcharsbx($mode['WIDTH']),
				'height' => htmlspecialcharsbx($mode['HEIGHT']),
			));
		}


		if(!$this->ownResultId)
		{
			$this->ownResultId = 'bx-block-editor-result-' . htmlspecialcharsbx($this->id);
			$textArea = '<textarea name="' . htmlspecialcharsbx($this->id) . '" id="' . htmlspecialcharsbx($this->ownResultId)
				.'" style="width:800px;height:900px; display: none;"></textarea>';
		}

		foreach($this->tabs as $tabCode => $tab)
		{
			if(!isset($this->uiPatterns['panel-' . $tabCode]))
			{
				continue;
			}

			$tabs .= $this->getUI('tab', array(
				'code' => htmlspecialcharsbx($tabCode),
				'name' => htmlspecialcharsbx($tab['NAME']),
				'tab_active' => ($tab['ACTIVE'] ? $this->getUI('tab_active', array()) : '')
			));

			$panel = $this->getUI('panel-' . $tabCode, array(
				'id' => htmlspecialcharsbx($this->id),
				'blocks' => $blocks,
				'tools' => $tools,
				'devices' => $devices,
				'MESS_ACCESS_DENIED' => Loc::getMessage('ACCESS_DENIED'),
				'MESS_STYLES' => Loc::getMessage('BLOCK_EDITOR_UI_STYLES'),
				'MESS_BLOCKS' => Loc::getMessage('BLOCK_EDITOR_UI_BLOCKS'),
				'MESS_TOOL_CONTENT' => Loc::getMessage('BLOCK_EDITOR_UI_TOOL_CONTENT'),
				'MESS_TOOL_STYLES' => Loc::getMessage('BLOCK_EDITOR_UI_TOOL_STYLES'),
				'MESS_TOOL_SETTINGS' => Loc::getMessage('BLOCK_EDITOR_UI_TOOL_SETTINGS'),
				'MESS_TOOL_EMPTY' => Loc::getMessage('BLOCK_EDITOR_UI_TOOL_EMPTY'),
				'MESS_TOOL_SAVE' => Loc::getMessage('BLOCK_EDITOR_UI_TOOL_SAVE'),
				'MESS_TOOL_SAVE_TITLE' => Loc::getMessage('BLOCK_EDITOR_UI_TOOL_SAVE_TITLE'),
				'MESS_TOOL_CANCEL' => Loc::getMessage('BLOCK_EDITOR_UI_TOOL_CANCEL'),
				'MESS_TOOL_CANCEL_TITLE' => Loc::getMessage('BLOCK_EDITOR_UI_TOOL_CANCEL_TITLE'),
			));

			$panels .= $this->getUI('panel', array(
				'code' => htmlspecialcharsbx($tabCode),
				'panel_hidden' => (!$tab['ACTIVE'] ? $this->getUI('panel_hidden', array()) : ''),
				'html' => $panel
			));
		}

		return $this->getUI('main', array(
			'TEXTAREA' => $textArea,
			'id' => htmlspecialcharsbx($this->id),
			'tabs' => $tabs,
			'panels' => $panels,
			'MESS_BTN_MAX' => Loc::getMessage('BLOCK_EDITOR_UI_BTN_MAX'),
			'MESS_BTN_MIN' => Loc::getMessage('BLOCK_EDITOR_UI_BTN_MIN'),
		));
	}

	/**
	 * Return html for showing editor and include all resources
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает HTML-код для показа в редакторе, включая css и js.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/show.php
	* @author Bitrix
	*/
	public function show()
	{
		\CJSCore::RegisterExt('block_editor', array(
			'js' => array(
				'/bitrix/js/main/core/core_dragdrop.js',
				'/bitrix/js/fileman/block_editor/dialog.js',
				'/bitrix/js/fileman/block_editor/helper.js',
				'/bitrix/js/fileman/block_editor/editor.js',
			),
			'css' => '/bitrix/js/fileman/block_editor/dialog.css',
			'lang' => '/bitrix/modules/fileman/lang/' . LANGUAGE_ID . '/js_block_editor.php',
		));
		\CJSCore::Init(array("block_editor"));

		static $isBlockEditorManagerInited = false;
		$editorBlockTypeListByCode = array();
		if(!$isBlockEditorManagerInited)
		{
			foreach($this->blocks as $block)
			{
				$editorBlockTypeListByCode[$block['CODE']] = $block;
			}
		}

		$jsCreateParams = array(
			'id' => $this->id,
			'url' => $this->url,
			'previewUrl' => $this->previewUrl,
			'templateType' => $this->templateType,
			'templateId' => $this->templateId,
			'isTemplateMode' => $this->isTemplateMode,
			'site' => $this->site,
			'charset' => $this->charset
		);


		$result = '';
		if(!$isBlockEditorManagerInited)
		{
			$result .= 'BX.BlockEditorManager.setBlockList(' . \CUtil::PhpToJSObject($editorBlockTypeListByCode) . ");\n";
		}

		$result .= "var blockEditorParams = " . \CUtil::PhpToJSObject($jsCreateParams) . ";\n";
		$result .= "blockEditorParams['context'] = BX('bx-block-editor-container-" . htmlspecialcharsbx($this->id) . "');\n";
		$result .= "blockEditorParams['iframe'] = BX('bx-block-editor-iframe-" . htmlspecialcharsbx($this->id) . "');\n";
		$result .= "blockEditorParams['resultNode'] = BX('" . htmlspecialcharsbx($this->ownResultId) . "');\n";
		$result .= "BX.BlockEditorManager.create(blockEditorParams);\n";

		$result = "\n" . '<script type="text/javascript">BX.ready(function(){' . "\n" . $result . '})</script>' . "\n";
		$result = $this->showEditor() . $result;


		$isBlockEditorManagerInited = true;

		return $result;
	}

	/**
	 * Return received string, that php changed in special format for block editor.
	 *
	 * @param string $html
	 * @param string $charset
	 * @return string $html
	 */
	
	/**
	* <p>Статический метод возвращает принятую строку, преобразованную php в специальный формат для работы с блочным редактором.</p>
	*
	*
	* @param string $html  Принятая HTML-строка.
	*
	* @param string $charset = null Кодировка.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/gethtmlforeditor.php
	* @author Bitrix
	*/
	public static function getHtmlForEditor($html, $charset = null)
	{
		$phpList = \PHPParser::ParseFile($html);
		foreach($phpList as $php)
		{
			$id = 'bx_block_php_' . mt_rand();
			$surrogate = '<span id="' . $id . '" data-bx-editor-php-slice="' . htmlspecialcharsbx($php[2]) . '" class="bxhtmled-surrogate" title=""></span>';
			$html = str_replace($php[2], $surrogate, $html);
		}

		if(!$charset)
		{
			$charset = Application::getInstance()->getContext()->getCulture()->getCharset();
			$charset = 'UTF-8';
		}

		$charsetPlaceholder = '#CHARSET#';
		$html = static::replaceCharset($html, $charsetPlaceholder);
		$html = str_replace($charsetPlaceholder, HtmlFilter::encode($charset), $html);

		return $html;
	}

	/**
	 * Replace charset in HTML string.
	 *
	 * @param string $html
	 * @param string $charset
	 * @param bool $add
	 * @return string $html
	 */
	
	/**
	* <p>Статический метод меняет кодировку в HTML-строке.</p>
	*
	*
	* @param string $html  HTML-строка.
	*
	* @param string $charset = '#CHARSET#' Кодировка.
	*
	* @param boolean $add = false Добавлять ли кодировку, если она не указана в HTML.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/replacecharset.php
	* @author Bitrix
	*/
	public static function replaceCharset($html, $charset = '#CHARSET#', $add = false)
	{
		$html = preg_replace(
			'/(<meta .*?charset=["\']+?)([^"\']+?)(["\']+?.*?>)/i',
			'$1' . $charset . '$3',
			$html
		);

		$html = preg_replace(
			'/(<meta .*?content=["\']+?[^;]+?;[ ]*?charset=)([^"\']*?)(["\']+?.*?>)/i',
			'$1' . $charset . '$3', $html, 1, $replaceCount
		);
		if($replaceCount === 0 && $add)
		{
			$html = preg_replace(
				'/(<head.*?>)/i',
				'$1<meta http-equiv="Content-Type" content="text/html; charset=' . $charset . '">',
				$html
			);
		}

		return $html;
	}

	/**
	 * Fill template(as a HTML) by slice content.
	 * Result is string.
	 *
	 * @param string $template
	 * @param string $sliceContent
	 * @param string $encoding
	 * @return string
	 */
	
	/**
	* <p>Статический метод заполняет шаблон (как HTML) кусочным контентом. Результатом исполнения является строка.</p>
	*
	*
	* @param string $template  Целевой шаблон.
	*
	* @param string $sliceContent  Кусочный контент, которым будет заполнен шаблон.
	*
	* @param string $encoding = null Кодировка.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/filltemplatebyslicecontent.php
	* @author Bitrix
	*/
	public static function fillTemplateBySliceContent($template, $sliceContent, $encoding = null)
	{
		if(!static::isSliceContent($sliceContent))
		{
			return $template;
		}

		// create DomDocument from template
		$document = new Document;
		$document->loadHTML($template);

		$fillResult = static::fillDocumentBySliceContent($document, $sliceContent, $encoding);
		if($fillResult)
		{
			$result = $document->saveHTML();
			return $result ? $result : $template;
		}
		else
		{
			return $template;
		}
	}

	/**
	 * Fill template(as a DOM Document) by slice content.
	 * Result is DOM Document.
	 *
	 * @param \Bitrix\Main\Web\DOM\Document $document
	 * @param string $sliceContent
	 * @param string $encoding
	 * @return boolean
	 */
	
	/**
	* <p>Статический метод заполняет шаблон (как DOM-документ) кусочным контентом. Результатом исполнения является DOM-документ.</p>
	*
	*
	* @param mixed $Bitrix  Целевой документ.
	*
	* @param Bitri $Main  Кусочный контент, которым будет заполнен шаблон.
	*
	* @param Mai $Web  Кодировка.
	*
	* @param We $DOM  
	*
	* @param Document $document  
	*
	* @param string $sliceContent  
	*
	* @param string $encoding = null 
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/filldocumentbyslicecontent.php
	* @author Bitrix
	*/
	public static function fillDocumentBySliceContent(Document $document, $sliceContent, $encoding = null)
	{
		$blocks = array();
		$styles = '';

		// parse content to slices of blocks and styles
		$sliceList = static::parseSliceContent($sliceContent);
		// group blocks by places
		if(array_key_exists('BLOCKS', $sliceList))
		{
			$groupedSliceList = array();
			foreach($sliceList['BLOCKS'] as $slice)
			{
				if($slice['VALUE'])
				{
					$groupedSliceList[$slice['ITEM']][] = $slice['VALUE'];
				}
			}

			foreach($groupedSliceList as $item => $valueList)
			{
				$blocks[$item] = "\n" . implode("\n", $valueList) . "\n";
			}

			unset($groupedSliceList);
		}

		// unite styles to one string
		if(array_key_exists('STYLES', $sliceList))
		{
			foreach($sliceList['STYLES'] as $slice)
			{
				if($slice['VALUE'])
				{
					$styles .= "\n" . $slice['VALUE'];
				}
			}

			if($styles && preg_match_all("#<style[\\s\\S]*?>([\\s\\S]*?)</style>#i", $styles, $matchesStyles))
			{
				$styles = '';
				$matchesStylesCount = count($matchesStyles);
				for($i = 0; $i < $matchesStylesCount; $i++)
				{
					$styles .= "\n" . $matchesStyles[1][$i];
				}
			}
		}

		// if nothing to replace, return content
		if(!$styles && count($blocks) ===  0)
		{
			return false;
		}

		// add styles block to head of document
		if($styles)
		{
			$headDomElement = $document->getHead();
			if($headDomElement)
			{
				$styleNode = end($headDomElement->querySelectorAll('style[' . self::STYLIST_TAG_ATTR . ']'));
				if(!$styleNode)
				{
					$styleNode = $document->createElement('style');
					$styleNode->setAttribute('type', 'text/css');
					$styleNode->setAttribute(self::STYLIST_TAG_ATTR, 'item');
					$headDomElement->appendChild($styleNode);
					$styleNode->appendChild($document->createTextNode($styles));
				}
				else
				{
					$styleList1 = CssParser::parseCss($styleNode->getTextContent());
					$styleList2 = CssParser::parseCss($styles);
					$styleList = array_merge($styleList1, $styleList2);

					$styleListByKey = array();
					foreach($styleList as $styleItem)
					{
						if(!is_array($styleListByKey[$styleItem['SELECTOR']]))
						{
							$styleListByKey[$styleItem['SELECTOR']] = array();
						}

						$styleListByKey[$styleItem['SELECTOR']] = array_merge(
							$styleListByKey[$styleItem['SELECTOR']],
							$styleItem['STYLE']
						);
					}

					$stylesString = '';
					foreach($styleListByKey as $selector => $declarationList)
					{
						$stylesString .= $selector . '{' . CssParser::getDeclarationString($declarationList) . "}\n";
					}

					if($stylesString)
					{
						$styleNode->setInnerHTML('');
						$styleNode->appendChild($document->createTextNode($stylesString));
					}
				}
			}
		}

		// fill places by blocks
		if($blocks)
		{
			$placeList = $document->querySelectorAll('[' . static::BLOCK_PLACE_ATTR . ']');
			if(!empty($placeList))
			{
				// find available places
				$firstPlaceCode = null;
				$bodyPlaceCode = null;
				$placeListByCode = array();
				foreach($placeList as $place)
				{
					/* @var $place \Bitrix\Main\Web\DOM\Element */
					if (!$place || !$place->getAttributeNode(static::BLOCK_PLACE_ATTR))
					{
						continue;
					}

					/*
					// remove child nodes
					foreach($place->getChildNodesArray() as $child)
					{
						$place->removeChild($child);
					}
					*/

					$placeCode = $place->getAttribute(static::BLOCK_PLACE_ATTR);
					$placeListByCode[$placeCode] = $place;
					if(!$firstPlaceCode)
					{
						$firstPlaceCode = $placeCode;
					}

					if(!$bodyPlaceCode && $placeCode == static::BLOCK_PLACE_ATTR_DEF_VALUE)
					{
						$bodyPlaceCode = $placeCode;
					}
				}

				// group block list by existed places
				$blocksByExistType = array();
				foreach($blocks as $placeCode => $blockHtml)
				{
					// if there is no place, find body-place or first place or skip filling place
					if(!array_key_exists($placeCode, $placeListByCode))
					{
						if($bodyPlaceCode)
						{
							$placeCode = $bodyPlaceCode;
						}
						elseif($firstPlaceCode)
						{
							$placeCode = $firstPlaceCode;
						}
						else
						{
							continue;
						}
					}

					$blocksByExistType[$placeCode][] = $blockHtml;
				}

				//fill existed places by blocks
				foreach($blocksByExistType as $placeCode => $blockHtmlList)
				{
					if(!array_key_exists($placeCode, $placeListByCode))
					{
						continue;
					}

					$place = $placeListByCode[$placeCode];
					$place->setInnerHTML(implode("\n", $blockHtmlList));
				}
			}
		}

		return true;
	}

	/**
	 * Check string for the presence of tag attributes that indicates supporting of block editor
	 *
	 * @param string $content
	 * @return boolean
	 */
	
	/**
	* <p>Статический метод проверяет строку на наличие атрибутов тегов, указывающих на поддержку блочного редактора.</p>
	*
	*
	* @param string $content  Проверяемый контент.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/iscontentsupported.php
	* @author Bitrix
	*/
	public static function isContentSupported($content)
	{
		if(!$content || strpos($content, self::BLOCK_PLACE_ATTR) === false)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Check string for the presence of html
	 *
	 * @param string $content
	 * @return bool
	 */
	
	/**
	* <p>Статический метод проверяет строку на наличие HTML.</p>
	*
	*
	* @param string $content  Проверяемый контент.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/ishtmldocument.php
	* @author Bitrix
	*/
	public static function isHtmlDocument($content)
	{
		$result = true;
		$content = strtoupper($content);
		if(strpos($content, '<HTML') === false)
		{
			$result = false;
		}
		if(strpos($content, '</HTML') === false)
		{
			$result = false;
		}
		if(strpos($content, '<BODY') === false)
		{
			$result = false;
		}

		return $result;
	}

	/**
	 * Check string for the presence of slices
	 *
	 * @param string $content
	 * @return bool
	 */
	
	/**
	* <p>Статический метод проверяет строку на наличие кусочного контента.</p>
	*
	*
	* @param string $content  Проверяемый контент.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/isslicecontent.php
	* @author Bitrix
	*/
	public static function isSliceContent($content)
	{
		$result = true;
		$content = strtoupper($content);
		if(strpos($content, '<!--START ' . static::SLICE_SECTION_ID . '/') === false)
		{
			$result = false;
		}
		if(strpos($content, '<!--END ' . static::SLICE_SECTION_ID . '/') === false)
		{
			$result = false;
		}

		return $result;
	}

	/**
	 * Parse string of sliced content to array of block content
	 *
	 * @param string $content
	 * @return array
	 */
	
	/**
	* <p>Статический метод преобразует строку кусочного контента в массив элементов блочного контента.</p>
	*
	*
	* @param string $content  Преобразуемый контент.
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/parseslicecontent.php
	* @author Bitrix
	*/
	public static function parseSliceContent($content)
	{
		$result = array();
		$pattern = '#<!--START '
			. static::SLICE_SECTION_ID . '/([\w]+?)/([\w]+?)/-->'
			. '([\s\S,\n]*?)'
			. '<!--END ' . static::SLICE_SECTION_ID . '[/\w]+?-->#';

		$matches = array();
		if(preg_match_all($pattern, $content, $matches))
		{
			$matchesCount = count($matches[0]);
			for($i = 0; $i < $matchesCount; $i++)
			{
				$section = trim($matches[1][$i]);
				$result[$section][] = array(
					'SECTION' => $section,
					'ITEM' => trim($matches[2][$i]),
					'VALUE' => trim($matches[3][$i]),
				);
			}
		}

		return $result;
	}

	/**
	 * Set components filter
	 *
	 * @param array $componentFilter
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает фильтр компонентов.</p>
	*
	*
	* @param array $componentFilter = null Устанавливаемый фильтр.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/fileman/block/editor/setcomponentfilter.php
	* @author Bitrix
	*/
	public function setComponentFilter(array $componentFilter = null)
	{
		$this->componentFilter = $componentFilter;
	}

	protected function getComponentList()
	{
		return static::getComponentListPlain(static::getComponentTree());
	}

	protected function getComponentTree()
	{
		$util = new \CComponentUtil;

		return $util->GetComponentsTree(false, false, $this->componentFilter);
	}

	protected function getComponentListPlain($list)
	{
		$result = array();
		$path = null;

		if(!is_array($list))
		{
			return $result;
		}

		if(isset($list['@']))
		{
			$path = $list['@'];
		}

		if(isset($list['*']))
		{
			$componentList = array();
			foreach($list['*'] as $componentName => $componentData)
			{
				$componentData['TREE_PATH'] = array($path);
				$componentList[$componentName] = $componentData;
			}
			return $componentList;
		}

		if(isset($list['#']))
		{
			foreach($list['#'] as $key => $item)
			{
				$resultItem = static::getComponentListPlain($item);
				if(is_array($resultItem) && is_array($path))
				{
					foreach($resultItem as $componentName => $componentData)
					{
						if(!isset($componentData['TREE_PATH']))
						{
							$componentData['TREE_PATH'] = array();
						}
						$resultItem[$componentName]['TREE_PATH'] = array_merge(array($path), $componentData['TREE_PATH']);
					}
				}

				$result = array_merge($result, $resultItem);
			}
		}

		return $result;
	}

}