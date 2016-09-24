<?php

namespace Bitrix\Mail;

use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class Message
{

	//const QUOTE_START_MARKER = '-- Bitrix24 Mail begin ---';
	//const QUOTE_END_MARKER   = '-- Bitrix24 Mail end ---';

	const QUOTE_START_MARKER_HTML = '<div id="srvb24mqsm" style="font-family: \'srvb24mqsm\', serif;">&nbsp;</div>';
	const QUOTE_END_MARKER_HTML   = '<div id="qemb24msrv" style="font-family: \'qemb24msrv\', serif;">&nbsp;</div>';

	const QUOTE_HTML_REGEX = '/<div\s[^>]+srvb24mqsm[^>]+>.*?<\/div>(.*)<div\s[^>]+qemb24msrv[^>]+>.*?<\/div>/is';

	const QUOTE_PLACEHOLDER = '__QUOTE_PLACEHOLDER__';

	protected $type;
	protected $headers, $subject, $from, $to;
	protected $html, $text, $attachments;
	protected $secret;

	public function __construct(array &$message, $type)
	{
		$this->type = $type;

		$properties = array(
			'headers', 'subject', 'from', 'to',
			'text', 'html', 'attachments',
			'secret'
		);

		foreach ($properties as $property)
		{
			if (isset($message[$property]))
				$this->$property = $message[$property];
		}
	}

	/**
	 * Returns quote start marker
	 *
	 * @param bool $html Html/text switch.
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает маркер начала цитаты. Метод статический.</p>
	*
	*
	* @param boolean $html = false Html/text переключатель.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mail/message/getquotestartmarker.php
	* @author Bitrix
	*/
	final public static function getQuoteStartMarker($html = false)
	{
		return $html ? static::QUOTE_START_MARKER_HTML : static::QUOTE_START_MARKER;
	}

	/**
	 * Returns quote end marker
	 *
	 * @param bool $html Html/text switch.
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает маркер конца цитаты. Метод статический.</p>
	*
	*
	* @param boolean $html = false Html/text переключатель.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mail/message/getquoteendmarker.php
	* @author Bitrix
	*/
	final public static function getQuoteEndMarker($html = false)
	{
		return $html ? static::QUOTE_END_MARKER_HTML : static::QUOTE_END_MARKER;
	}

	/**
	 * Returns message attachments count
	 *
	 * @return int
	 */
	
	/**
	* <p>Метод возвращает количество вложений в сообщение. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mail/message/attachmentscount.php
	* @author Bitrix
	*/
	public function attachmentsCount()
	{
		return is_array($this->attachments) ? count($this->attachments) : 0;
	}

	/**
	 * Returns parsed message text
	 *
	 * @return string
	 */
	protected function parse()
	{
		if (isset($this->html))
		{
			$html = $this->html;

			$html = str_replace(array("\r", "\n"), '', $html);
			$html = preg_replace('/<br\s*\/?>/is', "\n", $html);

			$html = str_ireplace('</div>', "</div>\n", $html);
			$html = str_ireplace('</p>', "</p>\n", $html);
			$html = preg_replace('/<\/h([1-6])>/i', "</h\\1>\n", $html);
			$html = str_ireplace('</table>', "</table>\n", $html);
			$html = str_ireplace('</tr>', "</tr>\n", $html);
			$html = str_ireplace('</pre>', "</pre>\n", $html);

			$html = preg_replace('/(\n\s*)?<div/i', "\n<div", $html);
			$html = preg_replace('/(\n\s*)?<p(?=\s|>)/i', "\n<p", $html);
			$html = preg_replace('/(\n\s*)?<h([1-6])/i', "\n<h\\2", $html);
			$html = preg_replace('/(\n\s*)?<table/i', "\n<table", $html);
			$html = preg_replace('/(\n\s*)?<tr/i', "\n<tr", $html);
			$html = preg_replace('/(\n\s*)?<pre/i', "\n<pre", $html);

			$html = preg_replace('/(\n\s*)?<hr[^>]*>(\s*\n)?/i', "\n<hr>\n", $html);

			if ($this->type == 'reply' and $parts = $this->splitHtml($html))
			{
				list($before, $quote, $after) = $parts;
				$html = sprintf('%s%s%s', $before, static::QUOTE_PLACEHOLDER, $after);
			}

			if ($this->attachmentsCount())
			{
				foreach ($this->attachments as $item)
				{
					$html = preg_replace(
						sprintf('/<img[^>]+src\s*=\s*(\'|\")?\s*(cid:%s)\s*\1[^>]*>/is', preg_quote($item['contentId'], '/')),
						sprintf('[ATTACHMENT=%s]', $item['uniqueId']),
						$html
					);
				}
			}

			// TODO: Sanitizer
			$html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
			$html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
			$html = preg_replace('/<title[^>]*>.*?<\/title>/is', '', $html);
			$html = preg_replace('/<caption[^>]*>.*?<\/caption>/is', '', $html);

			// TODO: Sanitizer
			$html = preg_replace('/<a\s[^>]*href\s*=\s*([^\'\"\s>]+)\s*[^>]*>/is', '<a href="\1">', $html);

			// TODO: TextParser
			$html = preg_replace('/<strong[^>]*>(.*?)<\/strong>/is', '<b>\1</b>', $html);
			$html = preg_replace('/<em[^>]*>(.*?)<\/em>/is', '<i>\1</i>', $html);
			$html = preg_replace('/<blockquote[^>]*>(.*?)<\/blockquote>/is', '<quote>\1</quote>', $html);
			$html = preg_replace('/<hr[^>]*>/is', '________________________________________', $html);
			$html = preg_replace('/<del[^>]*>(.*?)<\/del>/is', '<s>\1</s>', $html);
			$html = preg_replace('/<ins[^>]*>(.*?)<\/ins>/is', '<u>\1</u>', $html);
			$html = preg_replace('/<h([1-6])[^>]*>(.*?)<\/h\1>/is', '<b>\2</b>', $html);
			$html = preg_replace('/<dl[^>]*>(.*?)<\/dl>/is', '<ul>\1</ul>', $html);
			$html = preg_replace('/<dt[^>]*>/is', '<li>', $html);
			$html = preg_replace('/<dd[^>]*>/is', ' - ', $html);
			$html = preg_replace('/<(sub|sup)[^>]*>(.*?)<\/\1>/is', '(\2)', $html);

			// TODO: TextParser
			$html = preg_replace('/<th[^>]*>(.*?)<\/th>/is', '<td>\1</td>', $html);

			$sanitizer = new \CBXSanitizer();
			//$sanitizer->setLevel(\CBXSanitizer::SECURE_LEVEL_MIDDLE);
			$sanitizer->addTags(array(
				'a'     => array('href'),
				'b'     => array(),
				'u'     => array(),
				's'     => array(),
				'i'     => array(),
				'img'   => array('src'),
				'font'  => array('color', 'size', 'face'),
				'ul'    => array(),
				'ol'    => array(),
				'li'    => array(),
				'table' => array(),
				'tr'    => array(),
				'td'    => array(),
				'th'    => array(),
				'quote' => array(),
				'br'    => array(),
				//'big'   => array(),
				//'small' => array(),
			));
			$sanitizer->applyHtmlSpecChars(false);
			$html = $sanitizer->sanitizeHtml($html);

			$parser = new \CTextParser();
			$text = $parser->convertHtmlToBB($html);

			$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML401, LANG_CHARSET);

			// TODO: TextParser
			$text = preg_replace('/<\/?([abuis]|img|font|ul|ol|li|table|tr|td|th|quote|br)(?=\s|>)[^>]*>/i', '', $text);

			$text = preg_replace('/[\t\x20]+/', "\x20", $text);
		}
		else
		{
			$text = $this->text;

			$text = str_replace("\r\n", "\n", $text);
			$text = str_replace("\r", "\n", $text);

			if ($this->type == 'reply' and $parts = $this->splitText($text))
			{
				list($before, $quote, $after) = $parts;
				$text = sprintf('%s%s%s', $before, static::QUOTE_PLACEHOLDER, $after);
			}

			if ($this->attachmentsCount())
			{
				foreach ($this->attachments as $item)
				{
					$text = str_replace(
						sprintf('[cid:%s]', $item['contentId']),
						sprintf('[ATTACHMENT=%s]', $item['uniqueId']),
						$text
					);
				}
			}
		}

		if ($this->type == 'reply' && strpos($text, static::QUOTE_PLACEHOLDER))
		{
			$text = $this->removeReplyHead($text);
			$text = preg_replace(sprintf('/\s*%s\s*/', preg_quote(static::QUOTE_PLACEHOLDER, '/')), "\n\n", $text);
		}

		if ($this->type == 'forward')
			$text = $this->removeForwardHead($text);

		// TODO: TextParser
		$text = preg_replace('/\[tr\]\s*\[\/tr\]/is', '', $text);
		$text = preg_replace('/\[table\]\s*\[\/table\]/is', '', $text);

		$text = trim($text);
		$text = preg_replace('/(\s*\n){2,}/', "\n\n", $text);

		if (empty($text) && $this->attachmentsCount() == 1)
			$text = sprintf('[ATTACHMENT=%s]', $item['uniqueId']);

		if (!empty($this->secret))
			$text = str_replace($this->secret, 'xxxxxxxx', $text);

		return $text;
	}

	public static function parseMessage(array &$message)
	{
		$message = new static($message, null);

		return $message->parse();
	}

	/**
	 * Returns parsed reply text
	 *
	 * @param array &$message Message.
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает автоматически обработанный текст ответа. Метод статический.</p>
	*
	*
	* @param array $array  Сообщение.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mail/message/parsereply.php
	* @author Bitrix
	*/
	public static function parseReply(array &$message)
	{
		$reply = new static($message, 'reply');

		return $reply->parse();
	}

	/**
	 * Returns parsed forward text
	 *
	 * @param array &$message Message.
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает автоматически обработанный текст посылаемого сообщения. Метод статический.</p>
	*
	*
	* @param array $array  Сообщение.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mail/message/parseforward.php
	* @author Bitrix
	*/
	public static function parseForward(array &$message)
	{
		$forward = new static($message, 'forward');

		return $forward->parse();
	}

	/**
	 * Extracts quote from message html
	 *
	 * @param string &$html Message html.
	 * @return string
	 */
	protected function splitHtml(&$html)
	{
		$parts = preg_split('/(<blockquote.+?<\/blockquote>)/is', $html, null, PREG_SPLIT_DELIM_CAPTURE);

		if (count($parts) > 3)
		{
			$parts = array_merge(
				array(join(array_slice($parts, 0, -2))),
				array_slice($parts, -2)
			);
		}
		else
		{
			if (count($parts) == 3)
				$parts = preg_split('/(<blockquote.+<\/blockquote>)/is', $html, null, PREG_SPLIT_DELIM_CAPTURE);
		}

		if (count($parts) < 3)
			$parts = preg_split(static::QUOTE_HTML_REGEX, $html, null, PREG_SPLIT_DELIM_CAPTURE);

		if (count($parts) == 3)
			return $parts;

		return false;
	}

	/**
	 * Extracts quote from message text
	 *
	 * @param string &$text Message text.
	 * @return string
	 */
	protected function splitText(&$text)
	{
		$parts = preg_split('/((?:^>.*$\n?){2,})/m', $text, null, PREG_SPLIT_DELIM_CAPTURE);

		if (count($parts) < 3)
			$parts = preg_split('/((?:^\|.*$\n?){2,})/m', $text, null, PREG_SPLIT_DELIM_CAPTURE);

		if (count($parts) < 3)
		{
			$outlookRegex = '/(
				(?:^_{20,}\n(?:[\t\x20]*\n)?)?
				(?:^(?:from|to|subject|sent|date):\x20[^\n]+$\n?){2,8}.*
			)/ismx';
			$parts = preg_split($outlookRegex, $text, null, PREG_SPLIT_DELIM_CAPTURE);
		}

		if (count($parts) == 3)
			return $parts;

		return false;
	}

	/**
	 * Returns full reply/forward head score
	 *
	 * @param string &$head Full reply/forward head.
	 * @return int
	 */
	protected function scoreFullHead(&$head)
	{
		$score = 0;

		if (preg_match_all('/^([^\:\n]{1,20}):[\t\x20]+(.+)$/m'.BX_UTF_PCRE_MODIFIER, $head, $matches, PREG_SET_ORDER))
		{
			$subject = array(
				'value'  => $this->subject,
				'strlen' => strlen($this->subject),
				'sgnlen' => strlen(trim($this->subject))
			);

			$isHeader = function($key, $value) use (&$subject)
			{
				if (strlen(trim($value)) >= 10 && $subject['sgnlen'] >= 10)
				{
					$dist = $subject['strlen']-strlen($value);
					if ($dist < 10)
					{
						if ($dist >= 0 && strpos($subject['value'], $value) === $dist)
							return true;
						else if (levenshtein($subject['value'], $value) < 10)
							return true;
					}
				}

				$date = preg_replace('/(?<=[\s\d])UT$/i', '+0000', trim($value));
				if (strtotime($date) !== false)
					return true;

				if (preg_match('/([a-z\d_](\.?[a-z\d_-]+)*)?[a-z\d_]@(([a-z\d][a-z\d-]*)?[a-z\d]\.?)+/i', $value))
					return true;

				return false;
			};

			foreach ($matches as $item)
				$score += (int) $isHeader($item[1], $item[2]);
		}

		return $score;
	}

	/**
	 * Returns short reply/forward head score
	 *
	 * @param string &$head Short reply/forward head.
	 * @return int
	 */
	protected function scoreShortHead(&$head)
	{
		$score = 0;

		$regex = '/(?:^|\n)
			(?<date>.{5,50}\d),?\x20
			[^\d\n]{0,20}(?<time>\d{1,2}\:\d{2}(?:\:\d{2})?\x20?(?:am|pm)?),?\x20
			(?<from>.+):\s*$
		/ix'.BX_UTF_PCRE_MODIFIER;
		if (preg_match($regex, $head, $matches))
		{
			$matches['date'] = trim($matches['date']);
			if (strtotime($matches['date']) !== false)
			{
				$score++;
			}
			else if (preg_match('/^[^\x20]+\x20+((?:[^\x20]+\x20+)?(.+))$/', $matches['date'], $date))
			{
				if (strtotime($date[1]) !== false || strtotime($date[2]) !== false)
					$score++;
			}

			if (preg_match('/([a-z\d_](\.?[a-z\d_-]+)*)?[a-z\d_]@(([a-z\d][a-z\d-]*)?[a-z\d]\.?)+/i', $matches['from']))
				$score++;
		}

		return $score;
	}

	/**
	 * Returns significant reply text
	 *
	 * @param array &$text Reply text.
	 * @return string
	 */
	protected function removeReplyHead(&$text)
	{
		list($before, $after) = explode(static::QUOTE_PLACEHOLDER, $text, 2);

		if (!trim($before))
			return $text;

		$data = static::reduceTags($before);

		/**
		 * Outlook
		 *
		 * ________________________________________
		 * From: <from>
		 * Sent: <datetime>
		 * To: <to>
		 * Subject: <subject>
		 */
		$fullHeadRegex = '/(?:^|\n)
			(?<hr>_{20,}\n(?:[\t\x20]*\n)?)?
			(?<head>(?:[^\:\n]{1,20}:[\t\x20]+.+(?:\n|$)){2,8})\s*$
		/x'.BX_UTF_PCRE_MODIFIER;
		if (preg_match($fullHeadRegex, $data, $matches))
		{
			$score  = (int) !empty($matches['hr']);
			$score += $this->scoreFullHead($matches['head']);

			if ($score > 1)
			{
				$pattern = preg_replace(array('/.+/', '/\n/'), array('.+', '\n'), $matches[0]);
				$before  = preg_replace_callback(
					sprintf('/%s$/', $pattern),
					function($matches)
					{
						return Message::reduceHead($matches[0]);
					},
					$before
				);

				return sprintf('%s%s%s', $before, static::QUOTE_PLACEHOLDER, $after);
			}
		}

		/**
		 * Gmail, Yandex, Thunderbird
		 *
		 * <date>, <time>, <from>:
		 */
		$shortHeadRegex = '/(?:^|\n)
			(?<date>.{5,50}\d),?\x20
			[^\d\n]{0,20}(?<time>\d{1,2}\:\d{2}(?:\:\d{2})?\x20?(?:am|pm)?),?\x20
			(?<from>.+):\s*$
		/ix'.BX_UTF_PCRE_MODIFIER;
		if (preg_match($shortHeadRegex, $data, $matches))
		{
			$score = 0;
			$score += $this->scoreShortHead($matches[0]);

			if ($score > 0)
			{
				$pattern = preg_replace(array('/.+/', '/\n/'), array('.+', '\n'), $matches[0]);
				$before  = preg_replace_callback(
					sprintf('/%s$/', $pattern),
					function($matches)
					{
						return Message::reduceHead($matches[0]);
					},
					$before
				);

				return sprintf('%s%s%s', $before, static::QUOTE_PLACEHOLDER, $after);
			}
		}

		return $text;
	}

	/**
	 * Returns significant forward text
	 *
	 * @param array &$text Forward text.
	 * @return string
	 */
	protected function removeForwardHead(&$text)
	{
		if (!trim($text))
			return $text;

		$data = static::reduceTags($text);

		$fullHeadRegex = '/(?:^|\n)\s*
			(?<marker>-{3,}.{4,40}?-{3,}[\t\x20]*\n)?
			(?<head>(?:[\t\x20]*\n)?
			(?<lines>(?:[^\:\n]{1,20}:[\t\x20]+.+(?:\n|$)){2,8})(?:\s*\n)?)
		/x'.BX_UTF_PCRE_MODIFIER;
		if (preg_match($fullHeadRegex, $data, $matches, PREG_OFFSET_CAPTURE))
		{
			$score  = (int) !empty($matches['marker'][0]);
			$score += $this->scoreFullHead($matches['lines'][0]);

			if ($score > 1)
			{
				// @TODO: Main\Text\BinaryString::getSubstring()
				$pattern = preg_replace(
					array('/.+/', '/\n/'), array('.+', '\n'),
					array(\CUtil::binSubstr($data, 0, $matches['head'][1]), $matches['head'][0])
				);

				return preg_replace_callback(
					sprintf('/^(%s)(%s)/', $pattern[0], $pattern[1]),
					function($matches)
					{
						return sprintf("%s\n\n%s", $matches[1], Message::reduceHead($matches[2]));
					},
					$text
				);
			}
		}

		$shortHeadRegex = '/(?:^|\n)\s*
			-{3,}.{4,40}?-{3,}[\t\x20]*\n
			(?<head>(?:[\t\x20]*\n)?
			(?<date>.{5,50}\d),?\x20
			[^\d\n]{0,20}(?<time>\d{1,2}\:\d{2}(?:\:\d{2})?\x20?(?:am|pm)?),?\x20
			(?<from>.+):(?:\s*\n)?)
		/ix'.BX_UTF_PCRE_MODIFIER;
		if (preg_match($shortHeadRegex, $data, $matches, PREG_OFFSET_CAPTURE))
		{
			$score  = 0;
			$score += $this->scoreShortHead($matches['head'][0]);

			if ($score > 0)
			{
				// @TODO: Main\Text\BinaryString::getSubstring()
				$pattern = preg_replace(
					array('/.+/', '/\n/'), array('.+', '\n'),
					array(\CUtil::binSubstr($data, 0, $matches['head'][1]), $matches['head'][0])
				);

				return preg_replace_callback(
					sprintf('/^(%s)(%s)/', $pattern[0], $pattern[1]),
					function($matches)
					{
						return sprintf("%s\n\n%s", $matches[1], Message::reduceHead($matches[2]));
					},
					$text
				);
			}
		}

		return $text;
	}

	/**
	 * Returns text without bb-codes
	 *
	 * @param array &$text Text.
	 * @return string
	 */
	protected static function reduceTags(&$text)
	{
		$data = $text;

		$data = preg_replace('/^(\[\/?(\*|[busi]|img|table|tr|td|th|quote|(url|size|color|font|list)(=.+?)?)\])+$/im', "\t", $data);
		$data = preg_replace('/\[\/?(\*|[busi]|img|table|tr|td|th|quote|(url|size|color|font|list)(=.+?)?)\]/i', '', $data);

		return $data;
	}

	/**
	 * Returns non-paired bb-codes only
	 *
	 * @param array &$text Text.
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает только непарные bb-коды. Метод статический.</p>
	*
	*
	* @param array $array  Текст.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mail/message/reducehead.php
	* @author Bitrix
	*/
	public static function reduceHead(&$text)
	{
		preg_match_all('/\[\/?([busi]|img|table|tr|td|th|quote|(url|size|color|font|list)(=.+?)?)\]/is', $text, $tags);

		$result = join($tags[0]);
		unset($tags);

		do
		{
			$result = preg_replace('/\[([busi]|img|table|tr|td|th|quote|url|size|color|font|list)(=.+?)?\]\[\/\1\]/is', '', $result, -1, $n2);
		}
		while ($n1+$n2 > 0);

		return $result;
	}

}
