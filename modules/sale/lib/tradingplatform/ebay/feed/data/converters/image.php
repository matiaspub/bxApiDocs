<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Converters;

use Bitrix\Main\ArgumentNullException;

class Image extends DataConverter
{
	protected $siteId;	
	protected $morePhotoProp;
	protected $domainName;

	public function __construct($params)
	{
		if(!isset($params["SITE_ID"]) || strlen($params["SITE_ID"]) <= 0)
			throw new ArgumentNullException("SITE_ID");

		$this->siteId = $params["SITE_ID"];
	}

	public function convert($data)
	{
		$result = "";
		$ebay = \Bitrix\Sale\TradingPlatform\Ebay\Ebay::getInstance();
		$ebaySettings = $ebay->getSettings();
		$this->morePhotoProp = isset($ebaySettings[$this->siteId]["MORE_PHOTO_PROP"][$data["IBLOCK_ID"]]) ? $ebaySettings[$this->siteId]["MORE_PHOTO_PROP"][$data["IBLOCK_ID"]] : null;

		if(!$this->morePhotoProp)
			return "";

		$this->domainName = isset($ebaySettings[$this->siteId]["DOMAIN_NAME"]) ? $ebaySettings[$this->siteId]["DOMAIN_NAME"] : null;

		if(!empty($data["OFFERS"]) && is_array($data["OFFERS"]))
		{
			foreach($data["OFFERS"] as $offer)
				$result .= $this->getItemData($offer, $data["IBLOCK_ID"]."_".$data["ID"]."_".$offer["ID"]);
		}
		else
		{
			$result = $this->getItemData($data, $data["IBLOCK_ID"]."_".$data["ID"]);
		}

		return $result;
	}

	protected function getItemData($data, $sku)
	{
		$morePhotoValue = array();

		if(empty($data["PROPERTIES"]) || !is_array($data["PROPERTIES"]))
			return "";

		foreach($data["PROPERTIES"] as $propCode => $propParams)
		{
			if($propParams["ID"] == $this->morePhotoProp)
			{
				if(!empty($propParams["VALUE"]) && is_array($propParams["VALUE"]))
				{
					$morePhotoValue = $propParams["VALUE"];
					break;
				}
				else
				{
					return "";
				}
			}
		}

		$pictureUrls = "";

		foreach($morePhotoValue as $value)
		{
			$pictureUrl = $this->getPictureUrl($value);

			if(strlen($pictureUrl) > 0)
				$pictureUrls .= "\t\t<URL>".$pictureUrl."</URL>\n";
		}

		if(strlen($pictureUrls) <= 0)
			return "";

		$result = "\t<Image>\n".
			"\t\t<SKU>".$sku."</SKU>\n".
			$pictureUrls.
			"\t</Image>\n";

		return $result;
	}

	protected function getPictureUrl($pictNo)
	{
		$strFile = "";

		if ($file = \CFile::GetFileArray($pictNo))
		{
			if(substr($file["SRC"], 0, 1) == "/")
				$strFile = "http://".$this->domainName.implode("/", array_map("rawurlencode", explode("/", $file["SRC"])));
			elseif(preg_match("/^(http|https):\\/\\/(.*?)\\/(.*)\$/", $file["SRC"], $match))
				$strFile = "http://".$match[2].'/'.implode("/", array_map("rawurlencode", explode("/", $match[3])));
			else
				$strFile = $file["SRC"];
		}

		return $strFile;
	}

}