<?php

namespace Bitrix\Sale\Delivery;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Menu
{
	protected $currentDeliveryId;

	public function __construct($currentDeliveryId = 0)
	{
		$this->currentDeliveryId = $currentDeliveryId;
	}

	public function getItems()
	{
		$result = 	array(
			"text" => Loc::getMessage("SALE_DELIVERY"),
			"title" => Loc::getMessage("SALE_DELIVERY_DESCR"),
			"url" => "sale_delivery_service_list.php?lang=".LANGUAGE_ID."&filter_group=0",
			"page_icon" => "sale_page_icon",
			//"icon" => "sale_section_icon",
			"items_id" => "menu_sale_delivery_tree",
			"more_url" => array(
				"sale_delivery_service_edit.php?lang=".LANGUAGE_ID."&PARENT_ID=0",
				"sale_delivery_service_edit.php?lang=".LANGUAGE_ID,
				"sale_delivery_service_list.php?lang=".LANGUAGE_ID,
				"sale_delivery_eservice_edit.php?lang=".LANGUAGE_ID,
				"sale_delivery_eservice_list.php?lang=".LANGUAGE_ID
			),
		);

		$children = $this->getChildren();

		foreach($children as $key => $child)
			if(!$child["can_has_children"])
				unset($children[$key]);

		if(!empty($children))
		{
			$result["items"] = $children;
			$result["dynamic"] = true;
		}

		return $result;
	}

	protected function getChildren(array $parentIds = array(0))
	{
		if(empty($parentIds))
			return array();

		$result = array();

		$dbRes = \Bitrix\Sale\Delivery\Services\Table::getList(array(
			"filter" => array(
				"=PARENT_ID" => $parentIds
			),
			"select" => array(
				"ID", "NAME", "DESCRIPTION", "CLASS_NAME"
			),
			"order" => array(
				"SORT" =>"ASC",
				"NAME" => "ASC"
			)
		));

		$services = array();
		$parents = array();

		while($service = $dbRes->fetch())
		{
			$services[$service["ID"]] = $service;
			$result[$service["ID"]] = array();

			if(is_callable($service["CLASS_NAME"].'::canHasChildren') &&  $service["CLASS_NAME"]::canHasChildren())
				$parents[] =  $service["ID"];
		}

		if(!empty($parents))
			$childrenList = $this->getChildren($parents);

		foreach($services as $serviceId => $service)
		{
			$canHasChildren = in_array($serviceId, $parents);

			if($canHasChildren && !empty($childrenList[$serviceId]))
				$children = $childrenList[$serviceId];
			else
				$children =array();

			foreach($children as $key => $child)
				if(!$child["can_has_children"])
					unset($children[$key]);

			$item = array(
				"text" => htmlspecialcharsbx($service["NAME"]),
				"title" => htmlspecialcharsbx($service["DESCRIPTION"]),
				"url" => "sale_delivery_service_list.php?lang=".LANGUAGE_ID."&filter_group=".$serviceId.'&set_filter=y',
				"page_icon" => "sale_page_icon",
				//"icon" => $canHasChildren ? "sale_section_icon" : "",
				"can_has_children" => $canHasChildren,
				"more_url" => array(
					"sale_delivery_service_edit.php?lang=".LANGUAGE_ID."&PARENT_ID=".$serviceId
				)
			);

			if($canHasChildren && !empty($children))
			{
				$item["items"] = $children;
				$item["dynamic"] = true;
				$item["items_id"] = "menu_sale_delivery_".$serviceId;
			}

			$result[$serviceId] = $item;
		}

		return $result;
	}
} 