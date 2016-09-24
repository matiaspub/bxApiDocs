<?php

namespace Bitrix\Sale;

interface IBusinessValueProvider
{
	public function getPersonTypeId();
	static public function getBusinessValueProviderInstance($mapping);
}
