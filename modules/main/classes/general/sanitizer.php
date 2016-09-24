<?
	IncludeModuleLangFile(__FILE__);
	/**
	* CBXSanitizer
	* Class to cut all tags and attributies from html not contained in white list
	*
	* Example to use:
	* <code>
	* $Sanitizer = new CBXSanitizer;
	*
	* $Sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
	* or
	* $Sanitizer->AddTags( array (
	* 								'a' = > array('href','id','style','alt'...),
	* 								'br' => array(),
	* 								.... ));
	*
	* $Sanitizer->SanitizeHtml($html);
	* </code>
	*
	* @version $rev 021
	*/

	/**
	* <b>CBXSanitizer</b> - класс для очистки введённого пользователем HTML - текста от тэгов и атрибутов которые не содержатся в "белом списке" разрешенных к использованию.
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxsanitizer/index.php
	* @author Bitrix
	*/
	class CBXSanitizer
	{
		/**
		 * @var All possible Sanitizer security levels
		 */
		const SECURE_LEVEL_CUSTOM	= 0;
		const SECURE_LEVEL_HIGH		= 1;
		const SECURE_LEVEL_MIDDLE	= 2;
		const SECURE_LEVEL_LOW		= 3;

		const TABLE_TOP 	= 0;
		const TABLE_CAPT 	= 1;
		const TABLE_GROUP 	= 2;
		const TABLE_ROWS 	= 3;
		const TABLE_COLS 	= 4;

		/**
		 * @deprecated For compability only will be erased next versions
		 * @var mix
		 */
		protected static $arOldTags = array();

		protected $arHtmlTags = array();
		protected $bHtmlSpecChars = true;
		protected $bDelSanitizedTags = true;
		protected $bDoubleEncode = true;
		protected $secLevel = self::SECURE_LEVEL_HIGH;
		protected $arNoClose = array(
								'br','hr','img','area','base',
								'basefont','col','frame','input',
								'isindex','link','meta','param'
								);
		protected $localAlph;

		protected $arTableTags = array(
								'table' 	=> self::TABLE_TOP,
								'caption'	=> self::TABLE_CAPT,
								'thead' 	=> self::TABLE_GROUP,
								'tfoot' 	=> self::TABLE_GROUP,
								'tbody' 	=> self::TABLE_GROUP,
								'tr'		=> self::TABLE_ROWS,
								'th'		=> self::TABLE_COLS,
								'td'		=> self::TABLE_COLS
								);

		public function __construct()
		{
			if(SITE_CHARSET == "UTF-8")
			{
				$this->localAlph="\p{L}".GetMessage("SNT_SYMB_NONE_LETTERS");
			}
			elseif(LANGUAGE_ID != "en")
			{
				$this->localAlph=GetMessage("SNT_SYMB");
			}
			else
			{
				$this->localAlph="";
			}

			$this->localAlph .= '\\x80-\\xFF';
		}

		/**
		 * Adds HTML tags and attributes to white list
		 * @param mixed $arTags array('tagName1' = > array('attribute1','attribute2',...), 'tagName2' => ........)
		 * @return int count of added tags
		 */
		
		/**
		* <p>Добавляет тэги и их атрибуты в список  разрешенных к использованию.</p> <p>Возвращает количество добавленных в список тэгов.</p> <p><b>CBXSanitizer::AddTags()</b> можно вызывать только как метод инициализированного объекта, а не как статический метод класса <b>CBXSanitizer</b>. </p>
		*
		*
		* @param array $arTags  Массив содержащий тэги и атрибуты тэгов разрешенные к
		* использованию. Имеет следующую структуру:       <ul> <li>Имя
		* разрешенного тэга 1</li>         <ul> <li>Имя атрибута 1</li>           <li>Имя
		* атрибута 2</li>           <li>...</li>         </ul> <li>Имя разрешенного тэга 2</li>        
		* <ul> <li>Имя атрибута 1</li>           <li>Имя атрибута 2</li>           <li>...</li>        
		* </ul> <li>...</li>         <ul> <li>...</li>         </ul> </ul>
		*
		* @return int 
		*
		* <h4>Example</h4> 
		* <pre bgcolor="#323232" style="padding:5px;">
		* $Sanitizer-&gt;AddTags( array (
		*                   'a' = &gt; array('href','id','style','alt'...),
		*                   'br' =&gt; array()
		*                   ));
		* </pre>
		*
		*
		* @static
		* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxsanitizer/addtags.php
		* @author Bitrix
		*/
		public function AddTags($arTags)
		{
			if(!is_array($arTags))
				return false;

			$counter = 0;
			$this->secLevel = self::SECURE_LEVEL_CUSTOM;

			foreach($arTags as $tagName => $arAttrs)
			{
				$tagName = strtolower($tagName);
				$arAttrs = array_change_key_case($arAttrs, CASE_LOWER);
				$this->arHtmlTags[$tagName] = $arAttrs;
				$counter++;
			}

			return $counter;
		}

		/**
		 * @see AddTags()
		 */
		
		/**
		* <p>Обновляет тэги и их атрибуты в списоке  разрешенных к использованию.</p> <p>Возвращает количество обновленных в списке тэгов.</p> <p><b>CBXSanitizer::UpdateTags()</b> можно вызывать только как метод инициализированного объекта, а не как статический метод класса <b>CBXSanitizer</b>. </p>
		*
		*
		* @param array $arTags  Массив содержащий тэги и атрибуты тэгов разрешенные к
		* использованию. Имеет следующую структуру:       <ul> <li>Имя
		* разрешенного тэга 1</li>         <ul> <li>Имя атрибута 1</li>           <li>Имя
		* атрибута 2</li>           <li>...</li>         </ul> <li>Имя разрешенного тэга 2</li>        
		* <ul> <li>Имя атрибута 1</li>           <li>Имя атрибута 2</li>           <li>...</li>        
		* </ul> <li>...</li>         <ul> <li>...</li>         </ul> </ul>
		*
		* @return int 
		*
		* <h4>Example</h4> 
		* <pre bgcolor="#323232" style="padding:5px;">
		* $Sanitizer-&gt;UpdateTags( array (
		*                   'a' = &gt; array('href','id','style','alt'...),
		*                   'br' =&gt; array()
		*                   ));
		* </pre>
		*
		*
		* @static
		* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxsanitizer/updatetags.php
		* @author Bitrix
		*/
		public function UpdateTags($arTags)
		{
			return $this->AddTags($arTags);
		}

		/**
		 * Deletes tags from white list
		 * @param mixed $arTagNames array('tagName1','tagname2',...)
		 * @return int count of deleted tags
		 */
		
		/**
		* <p>Удаляет тэги из списка разрешенных к использованию.</p> <p>Возвращает количество удаленных из списка тэгов.</p> <p><b>CBXSanitizer::DelTags()</b> можно вызывать только как метод инициализированного объекта, а не как статический метод класса <b>CBXSanitizer</b>. </p>
		*
		*
		* @param array $arTagsNames  Массив содержащий имена тэгов, которые необходимо удалить из
		* списка разрешенных тэгов.       <ul> <li>Имя тэга 1</li>         <li>Имя тэга 2</li>
		*         <li>...</li>       </ul>
		*
		* @return int 
		*
		* <h4>Example</h4> 
		* <pre bgcolor="#323232" style="padding:5px;">
		* $Sanitizer-&gt;DelTags( array ( 'a', 'br' ));
		* </pre>
		*
		*
		* @static
		* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxsanitizer/deltags.php
		* @author Bitrix
		*/
		public function DelTags($arTagNames)
		{
			if(!is_array($arTagNames))
				return false;

			$this->secLevel = self::SECURE_LEVEL_CUSTOM;
			$arTmp = array();
			$counter = 0;

			foreach ($this->arHtmlTags as $tagName => $arAttrs)
				foreach ($arTagNames as $delTagName)
					if(strtolower($delTagName) != $tagName)
						$arTmp[$tagName] = $arAttrs;
					else
						$counter++;

			$this->arHtmlTags = $arTmp;
			return $counter;
		}

		/**
		 * Deletes all tags from white list
		 */
		
		/**
		* <p>Удаляет все тэги из списка разрешенных к использованию.</p> <p>Метод ничего не возвращает.</p> <p><b>CBXSanitizer::DelAllTags()</b> можно вызывать только как метод инициализированного объекта, а не как статический метод класса <b>CBXSanitizer</b>.</p>
		*
		*
		* @return void 
		*
		* <h4>Example</h4> 
		* <pre bgcolor="#323232" style="padding:5px;">
		* $Sanitizer-&gt;DelAllTags();
		* </pre>
		*
		*
		* @static
		* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxsanitizer/delalltags.php
		* @author Bitrix
		*/
		public function DelAllTags()
		{
			$this->secLevel = self::SECURE_LEVEL_CUSTOM;
			$this->arHtmlTags = array();
		}

		/**
		 *  If is turned off Sanitizer will not encode existing html entities,
		 *  in text blocks.
		 *  The default is to convert everything.
		 *	http://php.net/manual/ru/function.htmlspecialchars.php (double_encode)
		 * @param bool $bApply true|false
		 */
		public function ApplyDoubleEncode($bApply=true)
		{
			if($bApply)
				$this->bDoubleEncode = true;
			else
				$this->bDoubleEncode = false;
		}

		/**
		 * Apply or not function htmlspecialchars to filtered tags and text
		 * !WARNING! if DeleteSanitizedTags = false and ApplyHtmlSpecChars = false
		 * html will not be sanitized!
		 * @param bool $bApply true|false
		 */
		
		/**
		* <p>Применяет, либо удаляет настройку класса - применять функцию htmlspecialchars к простому тексту и тэгам не входящим в список разрешенных.</p> <p>Метод ничего не возвращает.</p> <p><b>CBXSanitizer::ApplyHtmlSpecChars()</b> можно вызывать только как метод инициализированного объекта, а не как статический метод класса <b>CBXSanitizer</b>. </p>
		*
		*
		* @param bool $bApply = true Логический параметр, принимающий значения true, либо false.
		*
		* @return void 
		*
		* <h4>Example</h4> 
		* <pre bgcolor="#323232" style="padding:5px;">
		* $Sanitizer-&gt;ApplyHtmlSpecChars(true);
		* </pre>
		*
		*
		* @static
		* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxsanitizer/applyhtmlspecchars.php
		* @author Bitrix
		*/
		public function ApplyHtmlSpecChars($bApply=true)
		{
			if($bApply)
				$this->bHtmlSpecChars = true;
			else
				$this->bHtmlSpecChars = false;
		}

		/**
		 * Delete or not filtered tags
		 * !WARNING! if DeleteSanitizedTags = false and ApplyHtmlSpecChars = false
		 * html will not be sanitized!
		 * @param bool $bApply true|false
		 */
		
		/**
		* <p>Применяет, либо удаляет настройку класса - удалять тэги не входящие в список разрешенных.</p> <p>Метод ничего не возвращает.</p> <p><b>CBXSanitizer::DeleteSanitizedTags()</b> можно вызывать только как метод инициализированного объекта, а не как статический метод класса <b>CBXSanitizer</b>. </p>
		*
		*
		* @param bool $bApply = true Логический параметр, принимающий значения <i>true</i>, либо <i>false</i>.
		*
		* @return void 
		*
		* <h4>Example</h4> 
		* <pre bgcolor="#323232" style="padding:5px;">
		* $Sanitizer-&gt;DeleteSanitizedTags(true);
		* </pre>
		*
		*
		* @static
		* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxsanitizer/deletesanitizedtags.php
		* @author Bitrix
		*/
		public function DeleteSanitizedTags($bApply=true)
		{
			if($bApply)
				$this->bDelSanitizedTags = true;
			else
				$this->bDelSanitizedTags = false;
		}

		/**
		 * Sets security level from predefined
		 * @param int $secLevel { 	CBXSanitizer::SECURE_LEVEL_HIGH
		 *							| CBXSanitizer::SECURE_LEVEL_MIDDLE
		 *							| CBXSanitizer::SECURE_LEVEL_LOW }
		 */
		
		/**
		* <p>Заполняет массив тэгов, разрешенных к использованию в соответствии с выбранным уровнем.</p> <p>Метод ничего не возвращает.</p> <p><b>CBXSanitizer::SetLevel()</b> можно вызывать только как метод инициализированного объекта, а не как статический метод класса <b>CBXSanitizer</b>. </p>
		*
		*
		* @param bool $secLevel  <p>Может принимать следующие значения:       </p> <ul>
		* <li>CBXSanitizer::SECURE_LEVEL_HIGH</li>       <li>CBXSanitizer::SECURE_LEVEL_MIDDLE</li>      
		* <li>CBXSanitizer::SECURE_LEVEL_LOW</li>       </ul> <p>При этом в список разрешенных будут
		* добавлены следующие тэги и атрибуты:</p>       <pre bgcolor="#323232" style="padding:5px;">      
		* CBXSanitizer::SECURE_LEVEL_HIGH         $arTags = array(             'b'     =&gt; array(),             'br'    =&gt;
		* array(),             'big'   =&gt; array(),             'blockquote'    =&gt; array(),             'code'    =&gt;
		* array(),             'del'   =&gt; array(),             'dt'    =&gt; array(),             'dd'    =&gt; array(),       
		*      'font'    =&gt; array(),             'h1'    =&gt; array(),             'h2'    =&gt; array(),             'h3'   
		* =&gt; array(),             'h4'    =&gt; array(),             'h5'    =&gt; array(),             'h6'    =&gt; array(), 
		*            'hr'    =&gt; array(),             'i'     =&gt; array(),             'ins'   =&gt; array(),             'li'
		*    =&gt; array(),             'ol'    =&gt; array(),             'p'     =&gt; array(),             'small'   =&gt;
		* array(),             's'     =&gt; array(),             'sub'   =&gt; array(),             'sup'   =&gt; array(),       
		*      'strong'  =&gt; array(),             'pre'   =&gt; array(),             'u'     =&gt; array(),             'ul'   
		* =&gt; array()           );       </pre>             <pre bgcolor="#323232" style="padding:5px;">       CBXSanitizer::SECURE_LEVEL_MIDDLE         $arTags =
		* array(             'a'     =&gt; array('href', 'title','name','alt'),             'b'     =&gt; array(),            
		* 'br'    =&gt; array(),             'big'   =&gt; array(),             'code'    =&gt; array(),             'caption'
		* =&gt; array(),             'del'   =&gt; array('title'),             'dt'    =&gt; array(),             'dd'    =&gt;
		* array(),             'font'    =&gt; array('color','size'),             'color'   =&gt; array(),             'h1'   
		* =&gt; array(),             'h2'    =&gt; array(),             'h3'    =&gt; array(),             'h4'    =&gt; array(), 
		*            'h5'    =&gt; array(),             'h6'    =&gt; array(),             'hr'    =&gt; array(),             'i' 
		*    =&gt; array(),             'img'   =&gt; array('src','alt','height','width','title'),             'ins'   =&gt;
		* array('title'),             'li'    =&gt; array(),             'ol'    =&gt; array(),             'p'     =&gt; array(),
		*             'pre'   =&gt; array(),             's'     =&gt; array(),             'small'   =&gt; array(),            
		* 'strong'  =&gt; array(),             'sub'   =&gt; array(),             'sup'   =&gt; array(),             'table'  
		* =&gt; array('border','width'),             'tbody'   =&gt; array('align','valign'),             'td'    =&gt;
		* array('width','height','align','valign'),             'tfoot'   =&gt; array('align','valign'),             'th'    =&gt;
		* array('width','height'),             'thead'   =&gt; array('align','valign'),             'tr'    =&gt;
		* array('align','valign'),             'u'     =&gt; array(),             'ul'    =&gt; array()       </pre>       <pre bgcolor="#323232" style="padding:5px;">  
		*     CBXSanitizer::SECURE_LEVEL_LOW         $arTags = array(             'a'     =&gt; array('href',
		* 'title','name','style','id','class','shape','coords','alt','target'),             'b'     =&gt;
		* array('style','id','class'),             'br'    =&gt; array('style','id','class'),             'big'   =&gt;
		* array('style','id','class'),             'caption' =&gt; array('style','id','class'),             'code'    =&gt;
		* array('style','id','class'),             'del'   =&gt; array('title','style','id','class'),             'div'   =&gt;
		* array('title','style','id','class','align'),             'dt'    =&gt; array('style','id','class'),             'dd'   
		* =&gt; array('style','id','class'),             'font'    =&gt; array('color','size','face','style','id','class'),       
		*      'h1'    =&gt; array('style','id','class','align'),             'h2'    =&gt; array('style','id','class','align'),  
		*           'h3'    =&gt; array('style','id','class','align'),             'h4'    =&gt;
		* array('style','id','class','align'),             'h5'    =&gt; array('style','id','class','align'),             'h6'   
		* =&gt; array('style','id','class','align'),             'hr'    =&gt; array('style','id','class'),             'i'    
		* =&gt; array('style','id','class'),             'img'   =&gt; array('src','alt','height','width','title'),            
		* 'ins'   =&gt; array('title','style','id','class'),             'li'    =&gt; array('style','id','class'),            
		* 'map'   =&gt; array('shape','coords','href','alt','title','style','id','class','name'),             'ol'    =&gt;
		* array('style','id','class'),             'p'     =&gt; array('style','id','class','align'),             'pre'   =&gt;
		* array('style','id','class'),             's'     =&gt; array('style','id','class'),             'small'   =&gt;
		* array('style','id','class'),             'strong'  =&gt; array('style','id','class'),             'span'    =&gt;
		* array('title','style','id','class','align'),             'sub'   =&gt;array('style','id','class'),             'sup'  
		* =&gt;array('style','id','class'),             'table'   =&gt;
		* array('border','width','style','id','class','cellspacing','cellpadding'),             'tbody'   =&gt;
		* array('align','valign','style','id','class'),             'td'    =&gt;
		* array('width','height','style','id','class','align','valign','colspan','rowspan'),             'tfoot'   =&gt;
		* array('align','valign','style','id','class','align','valign'),             'th'    =&gt;
		* array('width','height','style','id','class','colspan','rowspan'),             'thead'   =&gt;
		* array('align','valign','style','id','class'),             'tr'    =&gt; array('align','valign','style','id','class'),   
		*          'u'     =&gt; array('style','id','class'),             'ul'    =&gt; array('style','id','class')           );  
		*     </pre>
		*
		* @return void 
		*
		* <h4>Example</h4> 
		* <pre bgcolor="#323232" style="padding:5px;">
		* $Sanitizer-&gt;SetLevel(CBXSanitizer::SECURE_LEVEL_LOW);
		* </pre>
		*
		*
		* @static
		* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxsanitizer/setlevel.php
		* @author Bitrix
		*/
		public function SetLevel($secLevel)
		{
			if($secLevel!=self::SECURE_LEVEL_HIGH && $secLevel!=self::SECURE_LEVEL_MIDDLE && $secLevel!=self::SECURE_LEVEL_LOW)
				$secLevel=self::SECURE_LEVEL_HIGH;

			switch ($secLevel)
			{
				case self::SECURE_LEVEL_HIGH:
					$arTags = array(
						'b'		=> array(),
						'br'		=> array(),
						'big'		=> array(),
						'blockquote'	=> array(),
						'code'		=> array(),
						'del'		=> array(),
						'dt'		=> array(),
						'dd'		=> array(),
						'font'		=> array(),
						'h1'		=> array(),
						'h2'		=> array(),
						'h3'		=> array(),
						'h4'		=> array(),
						'h5'		=> array(),
						'h6'		=> array(),
						'hr'		=> array(),
						'i'		=> array(),
						'ins'		=> array(),
						'li'		=> array(),
						'ol'		=> array(),
						'p'		=> array(),
						'small'		=> array(),
						's'		=> array(),
						'sub'		=> array(),
						'sup'		=> array(),
						'strong'	=> array(),
						'pre'		=> array(),
						'u'		=> array(),
						'ul'		=> array()
					);

					break;

				case self::SECURE_LEVEL_MIDDLE:
					$arTags = array(
						'a'		=> array('href', 'title','name','alt'),
						'b'		=> array(),
						'br'		=> array(),
						'big'		=> array(),
						'blockquote'	=> array('title'),
						'code'		=> array(),
						'caption'	=> array(),
						'del'		=> array('title'),
						'dt'		=> array(),
						'dd'		=> array(),
						'font'		=> array('color','size'),
						'color'		=> array(),
						'h1'		=> array(),
						'h2'		=> array(),
						'h3'		=> array(),
						'h4'		=> array(),
						'h5'		=> array(),
						'h6'		=> array(),
						'hr'		=> array(),
						'i'		=> array(),
						'img'		=> array('src','alt','height','width','title'),
						'ins'		=> array('title'),
						'li'		=> array(),
						'ol'		=> array(),
						'p'		=> array(),
						'pre'		=> array(),
						's'		=> array(),
						'small'		=> array(),
						'strong'	=> array(),
						'sub'		=> array(),
						'sup'		=> array(),
						'table'		=> array('border','width'),
						'tbody'		=> array('align','valign'),
						'td'		=> array('width','height','align','valign'),
						'tfoot'		=> array('align','valign'),
						'th'		=> array('width','height'),
						'thead'		=> array('align','valign'),
						'tr'		=> array('align','valign'),
						'u'		=> array(),
						'ul'		=> array()
					);
					break;

				case self::SECURE_LEVEL_LOW:
					$arTags = array(
						'a'		=> array('href', 'title','name','style','id','class','shape','coords','alt','target'),
						'b'		=> array('style','id','class'),
						'br'		=> array('style','id','class'),
						'big'		=> array('style','id','class'),
						'blockquote'	=> array('title','style','id','class'),
						'caption'	=> array('style','id','class'),
						'code'		=> array('style','id','class'),
						'del'		=> array('title','style','id','class'),
						'div'		=> array('title','style','id','class','align'),
						'dt'		=> array('style','id','class'),
						'dd'		=> array('style','id','class'),
						'font'		=> array('color','size','face','style','id','class'),
						'h1'		=> array('style','id','class','align'),
						'h2'		=> array('style','id','class','align'),
						'h3'		=> array('style','id','class','align'),
						'h4'		=> array('style','id','class','align'),
						'h5'		=> array('style','id','class','align'),
						'h6'		=> array('style','id','class','align'),
						'hr'		=> array('style','id','class'),
						'i'		=> array('style','id','class'),
						'img'		=> array('src','alt','height','width','title'),
						'ins'		=> array('title','style','id','class'),
						'li'		=> array('style','id','class'),
						'map'		=> array('shape','coords','href','alt','title','style','id','class','name'),
						'ol'		=> array('style','id','class'),
						'p'		=> array('style','id','class','align'),
						'pre'		=> array('style','id','class'),
						's'		=> array('style','id','class'),
						'small'		=> array('style','id','class'),
						'strong'	=> array('style','id','class'),
						'span'		=> array('title','style','id','class','align'),
						'sub'		=> array('style','id','class'),
						'sup'		=> array('style','id','class'),
						'table'		=> array('border','width','style','id','class','cellspacing','cellpadding'),
						'tbody'		=> array('align','valign','style','id','class'),
						'td'		=> array('width','height','style','id','class','align','valign','colspan','rowspan'),
						'tfoot'		=> array('align','valign','style','id','class','align','valign'),
						'th'		=> array('width','height','style','id','class','colspan','rowspan'),
						'thead'		=> array('align','valign','style','id','class'),
						'tr'		=> array('align','valign','style','id','class'),
						'u'		=> array('style','id','class'),
						'ul'		=> array('style','id','class')
					);
					break;
				default:
					$arTags = array();
					break;
			}

			$this->DelAllTags();
			$this->AddTags($arTags);
			$this->secLevel = $secLevel;
		}

		// Checks if tag's attributes are in white list ($this->arHtmlTags)
		protected function IsValidAttr(&$arAttr)
		{
			if(!isset($arAttr[1]) || !isset($arAttr[3]))
				return false;

			$attrValue = $this->Decode($arAttr[3]);

			switch (strtolower($arAttr[1]))
			{
				case 'src':
				case 'href':
					if(!preg_match("#^(http://|https://|ftp://|file://|mailto:|callto:|skype:|\\#|/)#i".BX_UTF_PCRE_MODIFIER, $attrValue))
						$arAttr[3] = "http://".$arAttr[3];

					$valid = (!preg_match("#javascript:|data:|[^\\w".$this->localAlph."a-zA-Z:/\\.=@;,!~\\*\\&\\#\\)(%\\s\\+\$\\?\\-]#i".BX_UTF_PCRE_MODIFIER, $attrValue)) ? true : false;
					break;

				case 'height':
				case 'width':
				case 'cellpadding':
				case 'cellspacing':
					$valid = !preg_match("#^[^0-9\\-]+(px|%|\\*)*#i".BX_UTF_PCRE_MODIFIER, $attrValue) ? true : false;
					break;

				case 'title':
				case 'alt':
					$valid = !preg_match("#[^\\w".$this->localAlph."\\.\\?!,:;\\s\\-]#i".BX_UTF_PCRE_MODIFIER, $attrValue) ? true : false;
					break;

				case 'style':
					$valid = !preg_match("#(behavior|expression|position|javascript)#i".BX_UTF_PCRE_MODIFIER, $attrValue) && !preg_match("#[^\\w\\s)(,:\\.;\\-\\#]#i".BX_UTF_PCRE_MODIFIER, $attrValue) ? true : false;
					break;

				case 'coords':
					$valid = !preg_match("#[^0-9\\s,\\-]#i".BX_UTF_PCRE_MODIFIER, $attrValue) ? true : false;
					break;

				default:
					$valid = !preg_match("#[^\\s\\w".$this->localAlph."\\-\\#\\.]#i".BX_UTF_PCRE_MODIFIER, $attrValue) ? true : false;
					break;
			}

			return $valid;
		}

		/**
		 * Returns allowed tags and attributies
		 * @return string
		 */
		
		/**
		* <p>Возвращает список разрешенных к использованию тэгов в виде отформатированного текста.</p> <p><b>CBXSanitizer::GetTags()</b> можно вызывать только как метод инициализированного объекта, а не как статический метод класса <b>CBXSanitizer</b>. </p>
		*
		*
		* @return void 
		*
		* <h4>Example</h4> 
		* <pre bgcolor="#323232" style="padding:5px;">
		* echo $Sanitizer-&gt;GetTags();
		* </pre>
		*
		*
		* @static
		* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxsanitizer/gettags.php
		* @author Bitrix
		*/
		public function GetTags()
		{
			if(!is_array($this->arHtmlTags))
				return false;

			$confStr="";

			foreach ($this->arHtmlTags as $tag => $arAttrs)
			{
				$confStr.=$tag." (";
				foreach ($arAttrs as $attr)
					if($attr)
						$confStr.=" ".$attr." ";
				$confStr.=")<br>";
			}

			return $confStr;
		}

		/**
		 * @deprecated For compability only will be erased next versions
		 */
		public static function SetTags($arTags)
		{
			self::$arOldTags = $arTags;

			/* for next version
			$this->DelAllTags();

			return $this->AddTags($arTags);
			*/
		}

		/**
		 * @deprecated For compability only will be erased next versions
		 */
		public static function Sanitize($html, $secLevel='HIGH', $htmlspecialchars=true, $delTags=true)
		{
			$Sanitizer = new self;

			if(empty(self::$arOldTags))
				$Sanitizer->SetLevel(self::SECURE_LEVEL_HIGH);
			else
			{
				$Sanitizer->DelAllTags();
				$Sanitizer->AddTags(self::$arOldTags);
			}

			$Sanitizer->ApplyHtmlSpecChars($htmlspecialchars);
			$Sanitizer->DeleteSanitizedTags($delTags);
			$Sanitizer->ApplyDoubleEncode();

			return $Sanitizer->SanitizeHtml($html);
		}

		/**
		 * Erases, or HtmlSpecChares Tags and attributies wich not contained in white list
		 * from inputted HTML
		 * @param string $html Dirty HTML
		 * @return string filtered HTML
		 */
		
		/**
		* <p>Очищает HTML переданный в качестве параметра от тэгов и атрибутов не содержащихся в списке разрешенных.</p> <p>Возвращает очищенный html.</p> <p><b>CBXSanitizer::SanitizeHtml()</b> можно вызывать только как метод инициализированного объекта, а не как статический метод класса <b>CBXSanitizer</b>. </p>
		*
		*
		* @param string $html  текст в формате html.
		*
		* @return string 
		*
		* <h4>Example</h4> 
		* <pre bgcolor="#323232" style="padding:5px;">
		* $filteredHtml = $Sanitizer-&gt;SanitizeHtml("Sanitize me please!");
		* </pre>
		*
		*
		* @static
		* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxsanitizer/sanitizehtml.php
		* @author Bitrix
		*/
		public function SanitizeHtml($html)
		{
			if(empty($this->arHtmlTags))
				$this->SetLevel(self::SECURE_LEVEL_HIGH);

			$openTagsStack = array();
			$isCode = false;

			//split html to tag and simple text
			$seg = array();
			$arData = preg_split('/(<[^<>]+>)/si'.BX_UTF_PCRE_MODIFIER, $html, -1, PREG_SPLIT_DELIM_CAPTURE);
			foreach($arData as $i => $chunk)
			{
				if ($i % 2)
					$seg[] = array('segType'=>'tag', 'value'=>$chunk);
				elseif ($chunk != "")
					$seg[]=array('segType'=>'text', 'value'=> $chunk);
			}

			//process segments
			$segCount = count($seg);
			for($i=0; $i<$segCount; $i++)
			{
				if($seg[$i]['segType'] == 'text' && $this->bHtmlSpecChars)
					$seg[$i]['value'] = htmlspecialchars($seg[$i]['value'], ENT_QUOTES, LANG_CHARSET, $this->bDoubleEncode);
				elseif($seg[$i]['segType'] == 'tag')
				{
					//find tag type (open/close), tag name, attributies
					preg_match('#^<\s*(/)?\s*([a-z0-9]+)(.*?)>$#si'.BX_UTF_PCRE_MODIFIER, $seg[$i]['value'], $matches);
					$seg[$i]['tagType'] = ( $matches[1] ? 'close' : 'open' );
					$seg[$i]['tagName'] = strtolower($matches[2]);

					if(($seg[$i]['tagName']=='code') && ($seg[$i]['tagType']=='close'))
						$isCode = false;

					//if tag founded inside  <code></code>  it is simple text
					if($isCode)
					{
						$seg[$i]['segType'] = 'text';
						$i--;
						continue;
					}

					if($seg[$i]['tagType'] == 'open')
					{
						// if tag unallowed screen it, or erase
						if(!array_key_exists($seg[$i]['tagName'], $this->arHtmlTags))
						{
							if($this->bDelSanitizedTags) $seg[$i]['action'] = 'del';
							else
							{
								$seg[$i]['segType'] = 'text';
								$i--;
								continue;
							}
						}
						//if allowed
						else
						{
							//Processing valid tables
							//if find 'tr','td', etc...
							if(array_key_exists($seg[$i]['tagName'], $this->arTableTags))
							{
								$this->CleanTable($seg, $openTagsStack, $i, false);

								if($seg[$i]['action'] == 'del')
									continue;
							}

							//find attributies an erase unallowed
							preg_match_all('#([a-z_]+)\s*=\s*([\'\"])\s*(.*?)\s*\2#i'.BX_UTF_PCRE_MODIFIER, $matches[3], $arTagAttrs, PREG_SET_ORDER);
							$attr = array();
							foreach($arTagAttrs as $arTagAttr)
							{
								if(in_array(strtolower($arTagAttr[1]), $this->arHtmlTags[$seg[$i]['tagName']]))
								{
									if($this->IsValidAttr($arTagAttr))
									{
										if($this->bHtmlSpecChars)
										{
											$attr[strtolower($arTagAttr[1])] = htmlspecialchars($arTagAttr[3], ENT_QUOTES, LANG_CHARSET, $this->bDoubleEncode);
										}
										else
										{
											$attr[strtolower($arTagAttr[1])] = $arTagAttr[3];
										}
									}
								}
							}

							$seg[$i]['attr'] = $attr;
							if($seg[$i]['tagName'] == 'code')
								$isCode = true;

							//if tag need close tag add it to stack opened tags
							if(!in_array($seg[$i]['tagName'], $this->arNoClose)) //!count($this->arHtmlTags[$seg[$i]['tagName']]) || fix: </br>
							{
								$openTagsStack[] = $seg[$i]['tagName'];
								$seg[$i]['closeIndex'] = count($openTagsStack)-1;
							}
						}
					}
					//if closing tag
					else
					{	//if tag allowed
						if(array_key_exists($seg[$i]['tagName'], $this->arHtmlTags) && (!count($this->arHtmlTags[$seg[$i]['tagName']]) || ($this->arHtmlTags[$seg[$i]['tagName']][count($this->arHtmlTags[$seg[$i]['tagName']])-1] != false)))
						{
							if($seg[$i]['tagName'] == 'code')
								$isCode = false;
							//if open tags stack is empty, or not include it's name lets screen/erase it
							if((count($openTagsStack) == 0) || (!in_array($seg[$i]['tagName'], $openTagsStack)))
							{
								if($this->bDelSanitizedTags || $this->arNoClose)
									$seg[$i]['action'] = 'del';
								else
								{
									$seg[$i]['segType'] = 'text';
									$i--;
									continue;
								}
							}
							else
							{
								//if this tag don't match last from open tags stack , adding right close tag
								$tagName = array_pop($openTagsStack);
								if($seg[$i]['tagName'] != $tagName)
									array_splice($seg, $i, 0, array(array('segType'=>'tag', 'tagType'=>'close', 'tagName'=>$tagName, 'action'=>'add')));
							}
						}
						//if tag unallowed erase it
						else
						{
							if($this->bDelSanitizedTags) $seg[$i]['action'] = 'del';
							else
							{
								$seg[$i]['segType'] = 'text';
								$i--;
								continue;
							}
						}
					}
				}
			}

			//close tags stayed in stack
			foreach(array_reverse($openTagsStack) as $val)
				array_push($seg, array('segType'=>'tag', 'tagType'=>'close', 'tagName'=>$val, 'action'=>'add'));

			//build filtered code and return it
			$filteredHTML = '';
			foreach($seg as $segt)
			{
				if($segt['action'] != 'del')
				{
					if($segt['segType'] == 'text')
						$filteredHTML .= $segt['value'];
					elseif($segt['segType'] == 'tag')
					{
						if($segt['tagType'] == 'open')
						{
							$filteredHTML .= '<'.$segt['tagName'];

							if(is_array($segt['attr']))
								foreach($segt['attr'] as $attr_key=>$attr_val)
									$filteredHTML .= ' '.$attr_key.'="'.$attr_val.'"';

							if (count($this->arHtmlTags[$segt['tagName']]) && ($this->arHtmlTags[$segt['tagName']][count($this->arHtmlTags[$segt['tagName']])-1] == false))
								$filteredHTML .= " /";

							$filteredHTML .= '>';
						}
						elseif($segt['tagType'] == 'close')
							$filteredHTML .= '</'.$segt['tagName'].'>';
					}
				}
			}
			return $filteredHTML;
		}

		/**
		 * function CleanTable
		 * Check if table code is valid, and corrects. If need
		 * deletes all text and tags between diferent table tags if $delTextBetweenTags=true.
		 * Checks if where are open tags from upper level if not - self-distructs.
		 */
		protected function CleanTable(&$seg, &$openTagsStack, $segIndex, $delTextBetweenTags=true)
		{
			//if we found up level or not
			$bFindUp = false;
			//count open & close tags
			$arOpenClose = array();

			for ($tElCategory=self::TABLE_COLS;$tElCategory>self::TABLE_TOP;$tElCategory--)
			{
				if($this->arTableTags[$seg[$segIndex]['tagName']] != $tElCategory)
					continue;

				//find back upper level
				for($j=$segIndex-1;$j>=0;$j--)
				{
					if ($seg[$j]['segType'] != 'tag' || !array_key_exists($seg[$j]['tagName'], $this->arTableTags))
						continue;

					if($seg[$j]['action'] == 'del')
						continue;

					if($tElCategory == self::TABLE_COLS)
					{
						if($this->arTableTags[$seg[$j]['tagName']] == self::TABLE_COLS || $this->arTableTags[$seg[$j]['tagName']] == self::TABLE_ROWS)
							$bFindUp = true;
					}
					else
						if($this->arTableTags[$seg[$j]['tagName']] <= $tElCategory)
							$bFindUp = true;

					if(!$bFindUp)
						continue;

					//count opened and closed tags
					$arOpenClose[$seg[$j]['tagName']][$seg[$j]['tagType']]++;

					//if opened tag not found yet, searching for more
					if(($arOpenClose[$seg[$j]['tagName']]['open'] <= $arOpenClose[$seg[$j]['tagName']]['close']))
					{
						$bFindUp = false;
						continue;
					}


					if(!$delTextBetweenTags)
						break;

					//if find up level let's mark all middle text and tags for del-action
					for($k=$segIndex-1;$k>$j;$k--)
					{
						//lt's save text-format
						if($seg[$k]['segType'] == 'text' && !preg_match("#[^\n\r\s]#i".BX_UTF_PCRE_MODIFIER, $seg[$k]['value']))
							continue;

						$seg[$k]['action'] = 'del';
						if(isset($seg[$k]['closeIndex']))
							unset($openTagsStack[$seg[$k]['closeIndex']]);
					}

					break;

				}
				//if we didn't find up levels,lets mark this block as del
				if(!$bFindUp)
					$seg[$segIndex]['action'] = 'del';

				break;

			}
			return $bFindUp;
		}

		/**
		 * Decodes text from codes like &#***, html-entities wich may be coded several times;
		 * @param string $str
		 * @return decoded string
		 * */
		public function Decode($str)
		{
			$str1="";

			while($str1 <> $str)
			{
				$str1 = $str;
				$str = $this->_decode($str);
				$str = str_replace("\x00", "", $str);
				$str = preg_replace("/\&\#0+(;|([^\d;]))/is", "\\2", $str);
				$str = preg_replace("/\&\#x0+(;|([^\da-f;]))/is", "\\2", $str);
			}

			return $str1;
		}

		/*
		Function is used in regular expressions in order to decode characters presented as &#123;
		*/
		protected function _decode_cb($in)
		{
			$ad = $in[2];
			if($ad == ';')
				$ad="";
			$num = intval($in[1]);
			return chr($num).$ad;
		}

		/*
		Function is used in regular expressions in order to decode characters presented as  &#xAB;
		*/
		protected function _decode_cb_hex($in)
		{
			$ad = $in[2];
			if($ad==';')
				$ad="";
			$num = intval(hexdec($in[1]));
			return chr($num).$ad;
		}

		/*
		Decodes string from html codes &#***;
		One pass!
		-- Decode only a-zA-Z:().=, because only theese are used in filters
		*/
		protected function _decode($str)
		{
			$str = preg_replace_callback("/\&\#(\d+)([^\d])/is", array("CBXSanitizer", "_decode_cb"), $str);
			$str = preg_replace_callback("/\&\#x([\da-f]+)([^\da-f])/is", array("CBXSanitizer", "_decode_cb_hex"), $str);
			return str_replace(array("&colon;","&tab;","&newline;"), array(":","\t","\n"), $str);
		}

	};
?>
