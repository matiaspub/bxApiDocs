<?
IncludeModuleLangFile(__FILE__);

class CListFileControl
{
	private $_ob_file;
	private $_input_name;

	public function __construct($obFile, $input_name)
	{
		$this->_ob_file = $obFile;
		$this->_input_name = $input_name;
	}

	public function GetHTML($params)
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
				'input_name' => $this->_input_name,
			));
		}
		elseif($show_info)
		{
			$html .= $this->_ob_file->GetInfoHTML();
		}

		if($this->_ob_file->IsImage() && $this->_ob_file->GetSize() < $max_size)
		{
			$html .= '<br />';

			//Popup link
			$bPopUp = ($this->_ob_file->GetWidth() > $max_width) || ($this->_ob_file->GetHeight() > $max_height);
			if($bPopUp)
			{
				$img_src = $this->_ob_file->GetImgSrc(array('url_template'=>$url_template));
				$img_onclick = "ImgShw('".CUtil::JSEscape($img_src)."', '".$this->_ob_file->GetWidth()."', '".$this->_ob_file->GetHeight()."', ''); return false;";
				$html .= '<a title="'.htmlspecialcharsbx($params['a_title']).'" onclick="'.htmlspecialcharsbx($img_onclick).'" href="'.htmlspecialcharsbx($img_src).'" target="_blank">';
				ob_start();
				CFile::OutputJSImgShw();
				$html .= ob_get_contents();
				ob_end_clean();
			}

			//img tag
			$html .= $this->_ob_file->GetImgHtml(array(
				'url_template' => $url_template,
				'max_width' => $max_width,
				'max_height' => $max_height,
				'html_attributes' => array('border' => '0'),
			));

			//Close popup link
			if($bPopUp)
				$html .= '</a>';
		}
		else
		{
			$html .= '<br />'.$this->_ob_file->GetLinkHtml(array(
				'url_template' => $url_template,
				'download_text' => $params['download_text'],
			));
		}

		return $html;
	}
}
?>