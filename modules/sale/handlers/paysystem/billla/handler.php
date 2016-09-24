<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Request;
use Bitrix\Sale;
use Bitrix\Sale\PaySystem;
use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('sale', array(PaySystem\Manager::getClassNameFromPath('Bill') => 'handlers/paysystem/bill/handler.php'));

class BillLaHandler extends BillHandler
{

}