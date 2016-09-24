<?php

namespace Bitrix\Sale\Discount\Gift;


class Gift
{
	protected $productId;
	protected $isSku;

	/**
	 * Gift constructor.
	 * @param $productId
	 */
	public function __construct($productId)
	{
		$this->productId = $productId;
	}

	/**
	 * @return int|string
	 */
	public function getProductId()
	{
		return $this->productId;
	}

	static public function getProduct()
	{
		//lazy load data from CCatalog
	}
}