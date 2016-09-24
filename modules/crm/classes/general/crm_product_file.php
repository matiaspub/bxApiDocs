<?php
IncludeModuleLangFile(__FILE__);

class CCrmProductFile
{
	private $_element_id;
	private $_field_id;
	private $_file_id;

	private $_file;
	private $_width = 0;
	private $_height = 0;

	/** @var $_counter int  */
	private static $_counter = 0;

	function __construct($element_id, $field_id, $file_id)
	{
		$this->_element_id = intval($element_id);
		$this->_field_id = $field_id;
		$this->_file_id = intval($file_id);

		$this->_file = CFile::GetFileArray($this->_file_id);
		if(is_array($this->_file))
		{
			$this->_width = intval($this->_file['WIDTH']);
			$this->_height = intval($this->_file['HEIGHT']);
		}
	}

	function GetInfoHTML($params = array())
	{
		$html = '';

		if(is_array($this->_file))
		{
			$intWidth = $this->_width;
			$intHeight = $this->_height;
			$img_src = '';
			$divId = '';
			if(isset($params['url_template']) && $intWidth > 0 && $intHeight > 0)
			{
				$img_src = $this->GetImgSrc(array('url_template' => $params['url_template']));
				if ($img_src)
				{
					CUtil::InitJSCore(array("viewer"));
					self::$_counter++;
					$divId = 'lists-image-info-'.self::$_counter;
				}
			}

			if ($divId)
			{
				$html .= '<div id="'.$divId.'">';
			}
			else
			{
				$html .= '<div>';
			}

			if (isset($params['view']) && $params['view'] == 'short')
			{
				$info = $this->_file["FILE_NAME"].' (';
				if($intWidth > 0 && $intHeight > 0)
				{
					$info .= $intWidth.'x'.$intHeight.', ';
				}
				$info .= CFile::FormatSize($this->_file['FILE_SIZE']).')';

				if ($divId)
					$html .= GetMessage('FILE_TEXT').': <span style="cursor:pointer" data-bx-viewer="image" data-bx-src="'.htmlspecialcharsbx($img_src).'">'.htmlspecialcharsex($info).'</span>';
				else
					$html .= GetMessage('FILE_TEXT').': '.htmlspecialcharsex($info);

			}
			else
			{
				if ($divId)
					$html .= GetMessage('FILE_TEXT').': <span style="cursor:pointer" data-bx-viewer="image" data-bx-src="'.htmlspecialcharsbx($img_src).'">'.htmlspecialcharsex($this->_file["FILE_NAME"]).'</span>';
				else
					$html .= GetMessage('FILE_TEXT').': '.htmlspecialcharsex($this->_file["FILE_NAME"]);

				/*if($intWidth > 0 && $intHeight > 0)
				{
					$html .= '<br>'.GetMessage('FILE_WIDTH').': '.$intWidth;
					$html .= '<br>'.GetMessage('FILE_HEIGHT').': '.$intHeight;
				}*/
				$html .= '<br>'.GetMessage('FILE_SIZE').': '.CFile::FormatSize($this->_file['FILE_SIZE']);
			}

			if ($divId)
			{
				$html .= '</div><script>BX.ready(function(){BX.viewElementBind("'.$divId.'");});</script>';
			}
			else
			{
				$html .= '</div>';
			}
		}

		return $html;
	}

	function GetInputHTML($params = array())
	{
		$input_name = $this->_field_id;
		$size = 20;
		$show_info = false;

		if(is_array($params))
		{
			if(isset($params['input_name']))
				$input_name = $params['input_name'];
			if(isset($params['size']))
				$size = intval($params['size']);
			if(isset($params['show_info']))
				$show_info = (bool)$params['show_info'];
		}

		$strReturn = ' <input name="'.htmlspecialcharsbx($input_name).'" size="'.$size.'" type="file" />';

		if(is_array($this->_file))
		{
			if($show_info)
			{
				$strReturn .= $this->GetInfoHTML(array(
						'url_template' => $params['url_template'],
						'view' => 'short',
					));
			}

			$p = strpos($input_name, "[");
			if($p > 0)
				$del_name = substr($input_name, 0, $p)."_del".substr($input_name, $p);
			else
				$del_name = $input_name."_del";

			$strReturn .= '<input type="checkbox" name="'.htmlspecialcharsbx($del_name).'" value="Y" id="'.htmlspecialcharsbx($del_name).'" />';
			$strReturn .= ' <label for="'.htmlspecialcharsbx($del_name).'">'.GetMessage('FILE_DELETE').'</label><br>';
		}

		return $strReturn;
	}

	function GetImgSrc($params = array())
	{
		if(is_array($params) && isset($params['url_template']) && (strlen($params['url_template']) > 0))
			return str_replace(
				array('#product_id#', '#field_id#', '#file_id#'),
				array($this->_element_id, $this->_field_id, $this->_file_id),
				$params['url_template']
			);
		elseif(is_array($this->_file))
			return $this->_file['SRC'];
		else
			return '';

	}
	function GetImgHtml($params = array())
	{
		$max_width = 0;
		$max_height = 0;

		if(is_array($params))
		{
			if(isset($params['max_width']))
				$max_width = intval($params['max_width']);
			if(isset($params['max_height']))
				$max_height = intval($params['max_height']);
		}

		if(is_array($this->_file))
		{
			$intWidth = $this->_width;
			$intHeight = $this->_height;
			if($intWidth > 0 && $intHeight > 0 && $max_width > 0 && $max_height > 0)
			{
				if($intWidth > $max_width || $intHeight > $max_height)
				{
					$coeff = ($intWidth/$max_width > $intHeight/$max_height? $intWidth/$max_width : $intHeight/$max_height);
					$intWidth = intval(roundEx($intWidth/$coeff));
					$intHeight= intval(roundEx($intHeight/$coeff));
				}
			}
			$file = new CFile();
			$arResizeInfo = $file->ResizeImageGet(
				$this->_file_id,
				array('width' => $intWidth, 'height' => $intHeight),
				BX_RESIZE_IMAGE_PROPORTIONAL,
				false
			);
			$src = $arResizeInfo['src'];
			$html = '<img src="'.htmlspecialcharsbx($src).'" width="'.$intWidth.'" height="'.$intHeight.'"';
			if(is_array($params) && isset($params['html_attributes']) && is_array($params['html_attributes']))
			{
				foreach($params['html_attributes'] as $name => $value)
					if(preg_match('/^[a-zA-Z-]+$/', $name))
						$html .= ' '.$name.'="'.htmlspecialcharsbx($value).'"';
			}
			$html .= '/>';
			return $html;
		}
		else
		{
			return '';
		}
	}

	function GetLinkHtml($params = array())
	{
		if(is_array($this->_file))
		{
			$src = CHTTP::urlAddParams($this->GetImgSrc($params), array("download" => "y"));
			return ' [ <a href="'.htmlspecialcharsbx($src).'" target="_self">'.$params['download_text'].'</a> ] ';
		}
		else
		{
			return '';
		}
	}

	function GetWidth()
	{
		return $this->_width;
	}

	function GetHeight()
	{
		return $this->_height;
	}

	function GetSize()
	{
		if(is_array($this->_file))
			return $this->_file["FILE_SIZE"];
		else
			return 0;
	}

	function IsImage()
	{
		return is_array($this->_file) && ($this->_width > 0) && ($this->_height > 0);
	}

	public static function CheckFieldId($catalogID, $fieldID)
	{
		if ($fieldID === "DETAIL_PICTURE")
			return true;
		elseif ($fieldID === "PREVIEW_PICTURE")
			return true;
		elseif ($fieldID === "PICTURE")
			return true;
		elseif ($catalogID <= 0 || $catalogID !== CCrmCatalog::EnsureDefaultExists())
			return false;
		elseif (!preg_match("/^PROPERTY_(.+)\$/", $fieldID, $match))
			return false;
		else
		{
			$db_prop = CIBlockProperty::GetPropertyArray($match[1], $catalogID);
			if(is_array($db_prop) && $db_prop["PROPERTY_TYPE"] === "F")
				return true;
		}
		return false;
	}

}

class CCrmProductFileControl
{
	/** @var $_ob_file CCrmProductFile */
	private $_ob_file = null;
	/** @var $_input_name string */
	private $_input_name = null;
	/** @var $_counter int  */
	private static $_counter = 0;

	/**
	 * @param $obFile CCrmProductFile File to display.
	 * @param $input_name string Input control name.
	 */
	function __construct($obFile, $input_name)
	{
		$this->_ob_file = $obFile;
		$this->_input_name = $input_name;
	}

	/**
	 * @param $params array Display parameters.
	 * 	<ul>
	 * 	<li>max_size - maximum file size to display IMG tag (default 100K).
	 * 	<li>max_width - width to scale image to (default 150).
	 * 	<li>max_height - height to scale image to (default 150).
	 * 	<li>url_template - template for image path builder (default '').
	 * 	<li>show_input - if set to true file control will be displayed.
	 * 	<li>show_info - if set to true file information will be displayed.
	 * 	<li>download_text - text to be shown on download link.
	 * 	</ul>
	 * @return string Html to display.
	 */
	function GetHTML($params)
	{
		$html = '';

		$max_size = 102400;
		$max_width = 150;
		$max_height = 150;
		$url_template = '';
		$show_input = true;
		$show_info = true;

		if(is_array($params))
		{
			if(isset($params['max_size']))
				$max_size = intval($params['max_size']);
			if(isset($params['max_width']))
				$max_width = intval($params['max_width']);
			if(isset($params['max_height']))
				$max_height = intval($params['max_height']);
			if(isset($params['url_template']))
				$url_template = $params['url_template'];
			if(isset($params['show_input']))
				$show_input = (bool)$params['show_input'];
			if(isset($params['show_info']))
				$show_info = (bool)$params['show_info'];
		}

		if($show_input)
		{
			$html .= $this->_ob_file->GetInputHTML(array(
				'show_info' => true,
				'url_template' => $url_template,
				'input_name' => $this->_input_name,
			));
		}
		elseif($show_info)
		{
			$html .= $this->_ob_file->GetInfoHTML(array(
				'url_template' => $url_template,
			));
		}

		if($this->_ob_file->IsImage() && $this->_ob_file->GetSize()/* < $max_size*/)
		{
			$img_src = $this->_ob_file->GetImgSrc(array('url_template'=>$url_template));
			CUtil::InitJSCore(array("viewer"));
			self::$_counter++;
			$divId = 'lists-image-' . self::$_counter;

			$html .= '<div id="'.$divId.'" style="cursor: pointer;">';
			$html .= $this->_ob_file->GetImgHtml(array(
				'url_template' => $url_template,
				'max_width' => $max_width,
				'max_height' => $max_height,
				'html_attributes' => array(
					'border' => '0',
					'data-bx-image' => $img_src
				),
			));
			$html .= '</div><script>BX.ready(function(){BX.viewElementBind("'.$divId.'");});</script>';
		}

		$html .= $this->_ob_file->GetLinkHtml(array(
			'url_template' => $url_template,
			'download_text' => $params['download_text'],
		));

		return $html;
	}
}
