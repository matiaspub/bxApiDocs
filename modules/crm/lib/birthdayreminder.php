<?php
namespace Bitrix\Crm;
use Bitrix\Main;
class BirthdayReminder
{
	public static function prepareSorting($date)
	{
		$site = new \CSite();
		$time = $date !== '' ? MakeTimeStamp($date, $site->GetDateFormat('SHORT')) : false;
		if($time === false)
		{
			return 1024;
		}

		return self::internalPrepareSorting($time);
	}
	private static function internalPrepareSorting($time)
	{
		$day = (int)date('d', $time);
		$month = (int)date('n', $time);
		return (($month - 1) << 5) + $day;
	}

	public static function getNearestEntities($entityID, $currentDate, $startDate = '', $intervalInDays = 7, $checkPermissions = true, $limit = 5)
	{
		if(!is_string($startDate) || $startDate === '')
		{
			$startDate = $currentDate;
		}

		$site = new \CSite();
		$dateFormat = $site->GetDateFormat('SHORT');
		$curretTime = $currentDate !== '' ? MakeTimeStamp($currentDate, $dateFormat) : false;
		$startTime = $startDate !== '' ? MakeTimeStamp($startDate, $dateFormat) : false;

		if($startTime === false)
		{
			return array();
		}

		$dt = new \DateTime();
		$dt->setTimestamp($startTime);
		$dt->add(new \DateInterval("P{$intervalInDays}D"));
		$endTime = $dt->getTimeStamp();

		$currentSorting = self::internalPrepareSorting($curretTime);
		$startSorting = self::internalPrepareSorting($startTime);
		$endSorting = self::internalPrepareSorting($endTime);

		$result = array();
		if($entityID === \CCrmOwnerType::Lead)
		{
			$dbResult = \CCrmLead::GetListEx(
				array(),
				array(
					'>=BIRTHDAY_SORT' => $startSorting,
					'<=BIRTHDAY_SORT' => $endSorting,
					'CHECK_PERMISSIONS'=> $checkPermissions ? 'Y' : 'N'
				),
				false,
				array('nTopCount' => $limit),
				array('ID', 'BIRTHDATE', 'BIRTHDAY_SORT', 'NAME', 'SECOND_NAME', 'LAST_NAME')
			);

			while($fields = $dbResult->Fetch())
			{
				$fields['ENTITY_TYPE_ID'] = \CCrmOwnerType::Lead;
				$fields['IMAGE_ID'] = 0;

				$sorting = isset($fields['BIRTHDAY_SORT']) ? (int)$fields['BIRTHDAY_SORT'] : 512;
				$fields['IS_BIRTHDAY'] = $sorting === $currentSorting;
				$result[] = $fields;
			}
		}
		elseif($entityID === \CCrmOwnerType::Contact)
		{
			$dbResult = \CCrmContact::GetListEx(
				array(),
				array(
					'>=BIRTHDAY_SORT' => $startSorting,
					'<=BIRTHDAY_SORT' => $endSorting,
					'CHECK_PERMISSIONS'=> $checkPermissions ? 'Y' : 'N'
				),
				false,
				array('nTopCount' => $limit),
				array('ID', 'BIRTHDATE', 'BIRTHDAY_SORT', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'PHOTO')
			);

			while($fields = $dbResult->Fetch())
			{
				$fields['ENTITY_TYPE_ID'] = \CCrmOwnerType::Contact;
				$fields['IMAGE_ID'] = isset($fields['PHOTO']) ? (int)$fields['PHOTO'] : 0;

				$sorting = isset($fields['BIRTHDAY_SORT']) ? (int)$fields['BIRTHDAY_SORT'] : 512;
				$fields['IS_BIRTHDAY'] = $sorting === $currentSorting;
				$result[] = $fields;
			}
		}

		return $result;
	}
}