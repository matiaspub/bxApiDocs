<?php

namespace Bitrix\Mail;

use Bitrix\Main;

abstract class BaseException extends Main\SystemException
{
}

class ReceiverException extends BaseException
{
}
