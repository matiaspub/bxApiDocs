<?php
namespace Bitrix\Security\Mfa;

use Bitrix\Main\Type;

class OtpEvents
{
	/**
	 * Agent for activate temporary disabled users OTP
	 *
	 * @return string
	 */
	public static function onRecheckDeactivate()
	{
		$users = UserTable::query()
			->addFilter('<DEACTIVATE_UNTIL', new Type\DateTime)
			->addFilter('=ACTIVE', 'N')
			->addSelect('USER_ID')
			->addSelect('SKIP_MANDATORY')
			->addSelect('SECRET')
			->setLimit(100)
			->exec()
			->fetchAll();

		foreach($users as $user)
		{
			if ($user['SKIP_MANDATORY'] === 'Y' && !$user['SECRET'])
				UserTable::update($user['USER_ID'], array('SKIP_MANDATORY' => 'N', 'DEACTIVATE_UNTIL' => null));
			else
				UserTable::update($user['USER_ID'], array('ACTIVE' => 'Y', 'SKIP_MANDATORY' => 'N', 'DEACTIVATE_UNTIL' => null));
		}

		return sprintf('%s();',  __METHOD__);
	}
}