<?
IncludeModuleLangFile(__FILE__);

class CListFileControl
{
	/** @var $_ob_file CListFile */
	private $_ob_file = null;
	/** @var $_input_name string */
	private $_input_name = null;
	/** @var $_counter int  */
	private static $_counter = 0;

	/**
	 * @param $obFile CListFile File to display.
	 * @param $input_name string Input control name.
	 */
	public function __construct($obFile, $input_name)
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

		if($this->_ob_file->IsImage() && $this->_ob_file->GetSize() < $max_size)
		{
			$img_src = $this->_ob_file->GetImgSrc(array('url_template'=>$url_template));
			CUtil::InitJSCore(array("viewer"));
			self::$_counter++;
			$divId = 'lists-image-' . self::$_counter;

			$html .= '<div id="'.$divId.'">';
			$html .= $this->_ob_file->GetImgHtml(array(
				'url_template' => $url_template,
				'max_width' => $max_width,
				'max_height' => $max_height,
				'html_attributes' => array(
					'border' => '0',
					'data-bx-image' => $img_src,
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
?>