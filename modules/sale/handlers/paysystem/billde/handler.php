<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Sale;
use Bitrix\Main\Loader;
use Bitrix\Sale\PaySystem;

Loader::registerAutoLoadClasses('sale', array(PaySystem\Manager::getClassNameFromPath('Bill') => 'handlers/paysystem/bill/handler.php'));

class BillDeHandler extends BillHandler
{

}