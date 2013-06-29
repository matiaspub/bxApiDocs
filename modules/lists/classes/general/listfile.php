<?
IncludeModuleLangFile(__FILE__);

class CListFile
{
	private $_list_id;
	private $_section_id;
	private $_element_id;
	private $_field_id;
	private $_file_id;
	private $_socnet_group_id;

	private $_file;
	private $_width = 0;
	private $_height = 0;

	public function __construct($list_id, $section_id, $element_id, $field_id, $file_id)
	{
		$this->_list_id = intval($list_id);
		$this->_section_id = intval($section_id);
		$this->_element_id = intval($element_id);
		$this->_field_id = $field_id;
		$this->_file_id = intval($file_id);
		$this->_socnet_group_id = "";

		$this->_file = CFile::GetFileArray($this->_file_id);
		if(is_array($this->_file))
		{
			$this->_width = intval($this->_file['WIDTH']);
			$this->_height = intval($this->_file['HEIGHT']);
		}
	}

	public function SetSocnetGroup($socnet_group_id)
	{
		if($socnet_group_id > 0)
			$this->_socnet_group_id = intval($socnet_group_id);
	}

	public function GetInfoHTML()
	{
		$html = '';

		if(is_array($this->_file))
		{
			$html .= GetMessage('FILE_TEXT').': '.htmlspecialcharsex($this->_file["FILE_NAME"]);

			$intWidth = $this->_width;
			$intHeight = $this->_height;
			if($intWidth > 0 && $intHeight > 0)
			{
				$html .= '<br>'.GetMessage('FILE_WIDTH').': '.$intWidth;
				$html .= '<br>'.GetMessage('FILE_HEIGHT').': '.$intHeight;
			}
			$html .= '<br>'.GetMessage('FILE_SIZE').': '.CFile::FormatSize($this->_file['FILE_SIZE']);
		}

		return $html;
	}

	public function GetInputHTML($params = array())
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
				$strReturn .= '<br>'.$this->GetInfoHTML();

			$p = strpos($input_name, "[");
			if($p > 0)
				$del_name = substr($input_name, 0, $p)."_del".substr($input_name, $p);
			else
				$del_name = $input_name."_del";

			$strReturn .= '<br><input type="checkbox" name="'.htmlspecialcharsbx($del_name).'" value="Y" id="'.htmlspecialcharsbx($del_name).'" />';
			$strReturn .= ' <label for="'.htmlspecialcharsbx($del_name).'">'.GetMessage('FILE_DELETE').'</label>';
		}

		return $strReturn;
	}

	public function GetImgSrc($params = array())
	{
		if(is_array($params) && isset($params['url_template']) && (strlen($params['url_template']) > 0))
			return str_replace(
				array('#list_id#', '#section_id#', '#element_id#', '#field_id#', '#file_id#', '#group_id#'),
				array($this->_list_id, $this->_section_id, $this->_element_id, $this->_field_id, $this->_file_id, $this->_socnet_group_id),
				$params['url_template']
			);
		elseif(is_array($this->_file))
			return $this->_file['SRC'];
		else
			return '';

	}
	public function GetImgHtml($params = array())
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

			$src = $this->GetImgSrc($params);
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

	public function GetLinkHtml($params = array())
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

	public function GetWidth()
	{
		return $this->_width;
	}

	public function GetHeight()
	{
		return $this->_height;
	}

	public function GetSize()
	{
		if(is_array($this->_file))
			return $this->_file["FILE_SIZE"];
		else
			return 0;
	}

	public function IsImage()
	{
		return is_array($this->_file) && ($this->_width > 0) && ($this->_height > 0);
	}
}
?>