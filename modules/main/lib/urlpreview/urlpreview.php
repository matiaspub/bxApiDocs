<?php

namespace Bitrix\Main\UrlPreview;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Loader;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\HttpHeaders;
use Bitrix\Main\Web\Uri;

class UrlPreview
{
	const SIGN_SALT = 'url_preview';
	const USER_AGENT = 'Bitrix link preview';
	/** @var int Maximum allowed length of the description. */
	const MAX_DESCRIPTION = 500;

	/**
	 * Returns associated metadata for the specified URL
	 *
	 * @param string $url URL.
	 * @param bool $addIfNew Should metadata be fetched and saved, if not found in database.
	 * @return array|false Metadata for the URL if found, or false otherwise.
	 */
	
	/**
	* <p>Статический метод возвращает метаданные, связанные с указанным URL.</p>
	*
	*
	* @param string $url  URL.
	*
	* @param boolean $addIfNew = true Указание должны ли метаданные быть выданы и сохранены, если их
	* нет в БД.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/urlpreview/getmetadatabyurl.php
	* @author Bitrix
	*/
	public static function getMetadataByUrl($url, $addIfNew = true)
	{
		if(!static::isEnabled())
			return false;

		$url = static::normalizeUrl($url);
		if($url == '')
			return false;

		if($metadata = UrlMetadataTable::getByUrl($url))
		{
			if($metadata['TYPE'] == UrlMetadataTable::TYPE_TEMPORARY && $addIfNew)
			{
				$metadata = static::resolveTemporaryMetadata($metadata['ID']);
			}
			return $metadata;
		}

		if(!$addIfNew)
			return false;

		$metadataId = static::reserveIdForUrl($url);
		$metadata = static::fetchUrlMetadata($url);
		if(is_array($metadata) && count($metadata) > 0)
		{
			$result = UrlMetadataTable::update($metadataId, $metadata);
			$metadata['ID'] = $result->getId();
			return $metadata;
		}

		return false;
	}

	/**
	 * Returns html code for url preview
	 *
	 * @param array $userField Userfield's value.
	 * @param array $userFieldParams Userfield's parameters.
	 * @param string $cacheTag Cache tag for returned preview (out param).
	 * @param bool $edit Show method build preview for editing the userfield.
	 * @return string HTML code for the preview.
	 */
	
	/**
	* <p>Нестатический метод возвращает html код для "богатой ссылки".</p>
	*
	*
	* @param array $userField  Значение пользовательского поля.
	*
	* @param array $userFieldParams  Параметры пользовательского поля.
	*
	* @param string $cacheTag  Тег кеша для кэширования внутренней богатой ссылки (без
	* параметров).
	*
	* @param boolean $edit = false Указание метода как выводить ссылку. Если <i>false</i> - вернется html код
	* для вывода богатой ссылки в режиме просмотра, если <i>true</i> -
	* вернется html код для вывода в режиме редактирования.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/urlpreview/showview.php
	* @author Bitrix
	*/
	public static function showView($userField, $userFieldParams, &$cacheTag, $edit = false)
	{
		global $APPLICATION;
		$edit = !!$edit;
		$cacheTag = '';

		if(!static::isEnabled())
			return null;

		$metadataId = (int)$userField['VALUE'][0];
		$metadata = false;
		if($metadataId > 0)
		{
			$metadata = UrlMetadataTable::getById($metadataId)->fetch();
			if(isset($metadata['TYPE']) && $metadata['TYPE'] == UrlMetadataTable::TYPE_TEMPORARY)
				$metadata = static::resolveTemporaryMetadata($metadata['ID']);
		}

		if(is_array($metadata))
		{
			if($metadata['TYPE'] == UrlMetadataTable::TYPE_DYNAMIC)
			{
				$routeRecord = Router::dispatch(new Uri(static::unfoldShortLink($metadata['URL'])));

				if(isset($routeRecord['MODULE']) && Loader::includeModule($routeRecord['MODULE']))
				{
					$className = $routeRecord['CLASS'];
					$routeRecord['PARAMETERS']['URL'] = $metadata['URL'];
					$parameters = $routeRecord['PARAMETERS'];

					if($edit && (!method_exists($className, 'checkUserReadAccess') || !$className::checkUserReadAccess($parameters, static::getCurrentUserId())))
						return null;

					if(method_exists($className, 'buildPreview'))
					{
						$metadata['HANDLER'] = $routeRecord;
						$metadata['HANDLER']['BUILD_METHOD'] = 'buildPreview';
					}

					if(method_exists($className, 'getCacheTag'))
						$cacheTag = $className::getCacheTag();
				}
				else if(!$edit)
				{
					return null;
				}
			}
		}
		else if(!$edit)
		{
			return null;
		}

		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:main.urlpreview',
			'',
			array(
				'USER_FIELD' => $userField,
				'METADATA' => $metadata,
				'PARAMS' => $userFieldParams,
				'EDIT' => ($edit ? 'Y' : 'N'),
				'CHECK_ACCESS' => ($edit ? 'Y' : 'N'),
			)
		);
		return ob_get_clean();
	}

	/**
	 * Returns html code for url preview edit form
	 *
	 * @param array $userField Userfield's value.
	 * @param array $userFieldParams Userfield's parameters.
	 * @return string HTML code for the preview.
	 */
	
	/**
	* <p>Статический метод возвращает html код "богатой ссылки" для вывода в режиме редактирования.</p>
	*
	*
	* @param array $userField  Значение пользовательского поля.
	*
	* @param array $userFieldParams  Параметры юзерского поля.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/urlpreview/showedit.php
	* @author Bitrix
	*/
	public static function showEdit($userField, $userFieldParams)
	{
		return static::showView($userField, $userFieldParams, $cacheTag, true);
	}

	/**
	 * Checks if metadata for the provided url is already fetched and cached.
	 *
	 * @param string $url Document's URL.
	 * @return bool True if metadata for the url is located in database, false otherwise.
	 */
	
	/**
	* <p>Статический метод проверяет, найдены ли закэшированные метаданные для указанного URL.</p>
	*
	*
	* @param string $url  URL документа.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/urlpreview/isurlcached.php
	* @author Bitrix
	*/
	public static function isUrlCached($url)
	{
		$url = static::normalizeUrl($url);
		if($url == '')
			return false;

		return (static::isUrlLocal(new Uri($url)) || !!UrlMetadataTable::getByUrl($url));
	}

	/**
	 * If url is remote - returns metadata for this url. If url is local - checks current user access to the entity
	 * behind the url, and returns html preview for this entity.
	 *
	 * @param string $url Document's URL.
	 * @param bool $addIfNew Should method fetch and store metadata for the document, if it is not found in database.
	 * @return array|false Metadata for the document, or false if metadata could not be fetched/parsed.
	 */
	
	/**
	* <p>Статический метод. Если URL - удалённый, то возвращает метаданные для него. Если URL - локальный, проверяет пользователя на доступ к сущности и возвращает "богатую ссылку".</p>
	*
	*
	* @param string $url  URL документа.
	*
	* @param boolean $addIfNew = true Указание следует ли методу выбрать и сохранить метаданные для
	* документа если их нет в БД.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/urlpreview/getmetadataandhtmlbyurl.php
	* @author Bitrix
	*/
	public static function getMetadataAndHtmlByUrl($url, $addIfNew = true)
	{
		$metadata = static::getMetadataByUrl($url, $addIfNew);
		if($metadata === false)
			return false;

		if($metadata['TYPE'] == UrlMetadataTable::TYPE_STATIC || $metadata['TYPE'] == UrlMetadataTable::TYPE_FILE)
		{
			return $metadata;
		}
		else if($metadata['TYPE'] == UrlMetadataTable::TYPE_DYNAMIC)
		{
			if($preview = static::getDynamicPreview($url))
			{
				$metadata['HTML'] = $preview;
				return $metadata;
			}

		}

		return false;
	}

	/**
	 * Returns stored metadata for array of IDs
	 *
	 * @param array $ids Array of record's IDs.
	 * @param bool $checkAccess Should method check current user's access to the internal entities, or not.
	 * @return array Array with provided IDs as the keys.
	 */
	
	/**
	* <p>Статический метод возвращает сохранённые метаданные для массива ID.</p>
	*
	*
	* @param array $ids  Массив ID записей.
	*
	* @param boolean $checkAccess = true Указание методу следует ли проверять или нет права доступа
	* пользователя к внутренним сущностям.
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/urlpreview/getmetadataandhtmlbyids.php
	* @author Bitrix
	*/
	public static function getMetadataAndHtmlByIds(array $ids, $checkAccess = true)
	{
		if(!static::isEnabled())
			return false;

		$result = array();

		$queryResult = UrlMetadataTable::getList(array(
			'filter' => array(
				'ID' => $ids,
				'!=TYPE' => UrlMetadataTable::TYPE_TEMPORARY
			)
		));

		while($metadata = $queryResult->fetch())
		{
			if($metadata['TYPE'] == UrlMetadataTable::TYPE_DYNAMIC)
			{
				$metadata['HTML'] = static::getDynamicPreview($metadata['URL'], $checkAccess);
				if($metadata['HTML'] === false)
					continue;
			}
			$result[$metadata['ID']] = $metadata;
		}

		return $result;
	}

	/**
	 * Creates temporary record for url
	 *
	 * @param string $url URL for which temporary record should be created.
	 * @return int Temporary record's id.
	 */
	
	/**
	* <p>Статический метод создаёт временную запись для URL.</p>
	*
	*
	* @param string $url  URL для которой должна быть создана временная запись.
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/urlpreview/reserveidforurl.php
	* @author Bitrix
	*/
	public static function reserveIdForUrl($url)
	{
		if($metadata = UrlMetadataTable::getByUrl($url))
		{
			$id = $metadata['ID'];
		}
		else
		{
			$result = UrlMetadataTable::add(array(
					'URL' => $url,
					'TYPE' => UrlMetadataTable::TYPE_TEMPORARY
			));
			$id = $result->getId();
		}

		return $id;
	}

	/**
	 * Fetches and stores metadata for temporary record, created by UrlPreview::reserveIdForUrl. If metadata could
	 * not be fetched, deletes record.
	 * @param int $id Metadata record's id.
	 * @param bool $checkAccess Should method check current user's access to the entity, or not.
	 * @return array|false Metadata if fetched, false otherwise.
	 */
	
	/**
	* <p>Статический метод возвращает и хранит метаданные для временной записи созданной методом <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/urlpreview/reserveidforurl.php">reserveIdForUrl</a>. Если метаданные не могут быть выданы, запись удаляется.</p>
	*
	*
	* @param integer $id  ID записи метаданных
	*
	* @param boolean $checkAccess = true Проверять или нет права доступа пользователя к сущности.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/urlpreview/resolvetemporarymetadata.php
	* @author Bitrix
	*/
	public static function resolveTemporaryMetadata($id, $checkAccess = true)
	{
		$metadata = UrlMetadataTable::getById($id)->fetch();
		if(!is_array($metadata))
			return false;

		if($metadata['TYPE'] == UrlMetadataTable::TYPE_TEMPORARY)
		{
			$metadata['URL'] = static::normalizeUrl($metadata['URL']);
			$metadata = static::fetchUrlMetadata($metadata['URL']);
			if($metadata === false)
			{
				UrlMetadataTable::delete($id);
				return false;
			}

			UrlMetadataTable::update($id, $metadata);
			return $metadata;
		}
		else if($metadata['TYPE'] == UrlMetadataTable::TYPE_STATIC || $metadata['TYPE'] == UrlMetadataTable::TYPE_FILE)
		{
			return $metadata;
		}
		else if($metadata['TYPE'] == UrlMetadataTable::TYPE_DYNAMIC)
		{
			if($preview = static::getDynamicPreview($metadata['URL'], $checkAccess))
			{
				$metadata['HTML'] = $preview;
				return $metadata;
			}
		}

		return false;
	}

	/**
	 * Returns HTML code for the dynamic (internal url) preview.
	 * @param string $url URL of the internal document.
	 * @param bool $checkAccess Should method check current user's access to the entity, or not.
	 * @return string|false HTML code of the preview, or false if case of any errors (including access denied)/
	 */
	
	/**
	* <p>Статический метод возвращает HTML код для динамической (внутренний URL) "богатой ссылки".</p>
	*
	*
	* @param string $url  URL внутреннего документа.
	*
	* @param boolean $checkAccess = true Указание методу проверять или нет права доступа пользователя к
	* сущности.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/urlpreview/getdynamicpreview.php
	* @author Bitrix
	*/
	public static function getDynamicPreview($url, $checkAccess = true)
	{
		$routeRecord = Router::dispatch(new Uri(static::unfoldShortLink($url)));
		if($routeRecord === false)
			return false;

		if(isset($routeRecord['MODULE']) && Loader::includeModule($routeRecord['MODULE']))
		{
			$className = $routeRecord['CLASS'];
			$parameters = $routeRecord['PARAMETERS'];
			$parameters['URL'] = $url;

			if ($checkAccess && (!method_exists($className, 'checkUserReadAccess') || !$className::checkUserReadAccess($parameters, static::getCurrentUserId())))
				return false;

			if (method_exists($className, 'buildPreview'))
			{
				$preview = $className::buildPreview($parameters);
				return (strlen($preview) > 0 ? $preview : false);
			}
		}
		return false;
	}

	/**
	 * Returns true if current user has read access to the content behind internal url.
	 * @param string $url URL of the internal document.
	 * @return bool True if current user has read access to the main entity of the document, or false otherwise.
	 */
	
	/**
	* <p>Статический Метод возвращает <i>true</i> если у текущего пользователя есть права на чтение данных сущности за внутренней ссылкой.</p>
	*
	*
	* @param string $url  URL внутреннего документа.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/urlpreview/checkdynamicpreviewaccess.php
	* @author Bitrix
	*/
	public static function checkDynamicPreviewAccess($url)
	{
		$routeRecord = Router::dispatch(new Uri(static::unfoldShortLink($url)));
		if($routeRecord === false)
			return false;

		if(isset($routeRecord['MODULE']) && Loader::includeModule($routeRecord['MODULE']))
		{
			$className = $routeRecord['CLASS'];
			$parameters = $routeRecord['PARAMETERS'];

			return (method_exists($className, 'checkUserReadAccess') && $className::checkUserReadAccess($parameters, static::getCurrentUserId()));
		}
		return false;
	}

	/**
	 * Sets main image url for the metadata with given id.
	 * @param int $id Id of the metadata to set image url.
	 * @param string $imageUrl Url of the image.
	 * @return bool Returns true in case of successful update, or false otherwise.
	 * @throws ArgumentException
	 */
	
	/**
	* <p>Статический метод устанавливает картинку с указанным ID, отображающуюся для богатой ссылки.</p>
	*
	*
	* @param integer $id  ID картинки, отображаемой для богатой ссылки.
	*
	* @param string $imageUrl  Url картинки.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/urlpreview/setmetadataimage.php
	* @author Bitrix
	*/
	public static function setMetadataImage($id, $imageUrl)
	{
		if(!is_int($id))
			throw new ArgumentException("Id of the metadata must be an integer", "id");
		if(!is_string($imageUrl) && !is_null($imageUrl))
			throw new ArgumentException("Url of the image must be a string", "imageUrl");

		$metadata = UrlMetadataTable::getList(array(
			'select' => array('IMAGE', 'IMAGE_ID', 'EXTRA'),
			'filter' => array('=ID' => $id)
		))->fetch();

		if(isset($metadata['EXTRA']['IMAGES']))
		{
			$imageIndex = array_search($imageUrl, $metadata['EXTRA']['IMAGES']);
			if($imageIndex === false)
				unset($metadata['EXTRA']['SELECTED_IMAGE']);
			else
				$metadata['EXTRA']['SELECTED_IMAGE'] = $imageIndex;
		}

		if(static::getOptionSaveImages())
		{
			$metadata['IMAGE_ID'] = static::saveImage($imageUrl);
			$metadata['IMAGE'] = null;
		}
		else
		{
			$metadata['IMAGE'] = $imageUrl;
			$metadata['IMAGE_ID'] = null;
		}

		return UrlMetadataTable::update($id, $metadata)->isSuccess();
	}

	/**
	 * Checks if UrlPreview is enabled in module option
	 * @return bool True if UrlPreview is enabled in module options.
	 */
	
	/**
	* <p>Статический метод проверяет разрешено ли использование "богатых ссылок" (UrlPreview) в <a href="http://dev.1c-bitrix.ru/user_help/settings/settings/settings.php#settings" >настройках</a> главного модуля.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/urlpreview/isenabled.php
	* @author Bitrix
	*/
	public static function isEnabled()
	{
		static $result = null;
		if(is_null($result))
		{
			$result = Option::get('main', 'url_preview_enable', 'N') === 'Y';
		}
		return $result;
	}

	/**
	 * Signs value using UrlPreview salt
	 * @param string $id Unsigned value.
	 * @return string Signed value.
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	
	/**
	* <p>Статический метод подписывает значение с использованием соли <code>\UrlPreview</code>.</p>
	*
	*
	* @param string $id  Неподписанное значение.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/urlpreview/sign.php
	* @author Bitrix
	*/
	public static function sign($id)
	{
		$signer = new Signer();
		return $signer->sign((string)$id, static::SIGN_SALT);
	}

	/**
	 * @param string $url URL of the document.
	 * @return array|false Fetched metadata or false if metadata was not found, or was invalid.
	 */
	protected static function fetchUrlMetadata($url)
	{
		$uriParser = new Uri($url);
		if(static::isUrlLocal($uriParser))
		{
			if($routeRecord = Router::dispatch(new Uri(static::unfoldShortLink($url))))
			{
				$metadata = array(
					'URL' => $url,
					'TYPE' => UrlMetadataTable::TYPE_DYNAMIC,
				);
			}
		}
		else
		{
			$metadataRemote = static::getRemoteUrlMetadata($uriParser);
			if(is_array($metadataRemote) && count($metadataRemote) > 0)
			{
				$metadata = array(
					'URL' => $url,
					'TYPE' => $metadataRemote['TYPE'] ?: UrlMetadataTable::TYPE_STATIC,
					'TITLE' => $metadataRemote['TITLE'],
					'DESCRIPTION' => $metadataRemote['DESCRIPTION'],
					'IMAGE_ID' => $metadataRemote['IMAGE_ID'],
					'IMAGE' => $metadataRemote['IMAGE'],
					'EMBED' => $metadataRemote['EMBED'],
					'EXTRA' => $metadataRemote['EXTRA']
				);
			}
		}

		if(isset($metadata['TYPE']))
		{
			return $metadata;
		}
		return false;
	}

	/**
	 * Returns true if given URL is local
	 *
	 * @param Uri $uri Absolute URL to be checked.
	 * @return bool
	 */
	protected static function isUrlLocal(Uri $uri)
	{
		$host = \Bitrix\Main\Context::getCurrent()->getRequest()->getHttpHost();

		return $uri->getHost() === $host;
	}

	/**
	 * @param Uri $uri Absolute URL to get metadata for.
	 * @return array|false
	 */
	protected static function getRemoteUrlMetadata(Uri $uri)
	{
		$httpClient = new HttpClient();
		$httpClient->setTimeout(5);
		$httpClient->setStreamTimeout(5);
		$httpClient->setHeader('User-Agent', self::USER_AGENT, true);
		if(!$httpClient->query('GET', $uri->getUri()))
			return false;

		if($httpClient->getStatus() !== 200)
			return false;

		$htmlContentType = strtolower($httpClient->getHeaders()->getContentType());
		if($htmlContentType !== 'text/html')
			return static::getFileMetadata($httpClient->getEffectiveUrl(), $httpClient->getHeaders());

		$html = $httpClient->getResult();
		$htmlDocument = new HtmlDocument($html, $uri);
		$htmlDocument->setEncoding($httpClient->getCharset());
		ParserChain::extractMetadata($htmlDocument);
		$metadata = $htmlDocument->getMetadata();

		if(is_array($metadata) && static::validateRemoteMetadata($metadata))
		{
			if(isset($metadata['IMAGE']) && static::getOptionSaveImages())
			{
				$metadata['IMAGE_ID'] = static::saveImage($metadata['IMAGE']);
				unset($metadata['IMAGE']);
			}

			if(isset($metadata['DESCRIPTION']) && strlen($metadata['DESCRIPTION']) > static::MAX_DESCRIPTION)
			{
				$metadata['DESCRIPTION'] = substr(
						$metadata['DESCRIPTION'],
						0,
						static::MAX_DESCRIPTION
				);
			}

			return $metadata;
		}

		return false;
	}

	/**
	 * @param string $url Image's URL.
	 * @return integer Saved file identifier
	 */
	protected static function saveImage($url)
	{
		$fileId = false;
		$file = new \CFile();
		$httpClient = new HttpClient();
		$httpClient->setTimeout(5);
		$httpClient->setStreamTimeout(5);

		$urlComponents = parse_url($url);
		if ($urlComponents && strlen($urlComponents["path"]) > 0)
			$tempPath = $file->GetTempName('', bx_basename($urlComponents["path"]));
		else
			$tempPath = $file->GetTempName('', bx_basename($url));

		$httpClient->download($url, $tempPath);
		$fileName = $httpClient->getHeaders()->getFilename();
		$localFile = \CFile::MakeFileArray($tempPath);
		$localFile['MODULE_ID'] = 'main';

		if(is_array($localFile))
		{
			if(strlen($fileName) > 0)
			{
				$localFile['name'] = $fileName;
			}
			if(\CFile::CheckImageFile($localFile, 0, 0, 0, array("IMAGE")) === null)
			{
				$fileId = $file->SaveFile($localFile, 'urlpreview', true);
			}
		}

		return ($fileId === false ? null : $fileId);
	}

	/**
	 * If provided url does not contain scheme part, tries to add it
	 *
	 * @param string $url URL to be fixed.
	 * @return string Fixed URL.
	 */
	protected static function normalizeUrl($url)
	{
		if(!preg_match('#^https?://#i', $url))
		{
			$url = 'http://'.$url;
		}

		$parsedUrl = new Uri($url);
		$parsedUrl->setHost(ToLower($parsedUrl->getHost()));

		return $parsedUrl->getUri();
	}

	/**
	 * Returns value of the option for saving images locally.
	 * @return bool True if images should be saved locally.
	 */
	protected static function getOptionSaveImages()
	{
		static $result = null;
		if(is_null($result))
		{
			$result = Option::get('main', 'url_preview_save_images', 'N') === 'Y';
		}
		return $result;
	}

	/**
	 * Checks if metadata is complete.
	 * @param array $metadata HTML document metadata.
	 * @return bool True if metadata is complete, false otherwise.
	 */
	protected static function validateRemoteMetadata(array $metadata)
	{
		$result = ((isset($metadata['TITLE']) && isset($metadata['IMAGE'])) || (isset($metadata['TITLE']) && isset($metadata['DESCRIPTION'])) || isset($metadata['EMBED']));
		return $result;
	}

	/**
	 * Returns id of currently logged user.
	 * @return int User's id.
	 */
	protected static function getCurrentUserId()
	{
		global $USER;
		return $USER->getId();
	}

	/**
	 * Unfolds internal short url. If url is not classified as a short link, returns input $url.
	 * @param string $shortUrl Short URL.
	 * @return string Full URL.
	 */
	protected static function unfoldShortLink($shortUrl)
	{
		$result = $shortUrl;
		if($shortUri = \CBXShortUri::GetUri($shortUrl))
		{
			$result = $shortUri['URI'];
		}
		return $result;
	}

	/**
	 * Returns metadata for downloadable file.
	 * @param string $path Path part of the URL.
	 * @param HttpHeaders $httpHeaders Server's response headers.
	 * @return array|bool Metadata record if mime type and filename were detected, or false otherwise.
	 */
	protected static function getFileMetadata($path, HttpHeaders $httpHeaders)
	{
		$mimeType = $httpHeaders->getContentType();
		$filename = $httpHeaders->getFilename() ?: bx_basename($path);
		$result = false;
		if($mimeType && $filename)
		{
			$result = array(
					'TYPE' => UrlMetadataTable::TYPE_FILE,
					'EXTRA' => array(
							'ATTACHMENT' => strtolower($httpHeaders->getContentDisposition()) === 'attachment' ? 'Y' : 'N',
							'MIME_TYPE' => $mimeType,
							'FILENAME' => $filename,
							'SIZE' => $httpHeaders->get('Content-Length')
					)
			);
		}
		return $result;
	}
}