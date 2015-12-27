<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Sale\Location\Migration;

use Bitrix\Main;
use Bitrix\Sale\Location;

include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/lib/location/migration/migrate.php");

final class MigrationProcess extends Location\Util\Process
{
	const SESS_KEY = 	'location_migration';
	const NOTIF_TAG = 	'SALE_LOCATIONPRO_PLZ_MIGRATE';

	private $migrator = null;

	public function __construct()
	{
		parent::__construct();

		$this->addStage(array(
			'PERCENT' => 10,
			'CODE' => 'CREATE_TYPES',
			'CALLBACK' => 'stageCreateTypes'
		));

		$this->addStage(array(
			'PERCENT' => 30,
			'CODE' => 'CONVERT_TREE',
			'CALLBACK' => 'stageConvertTree'
		));

		$this->addStage(array(
			'PERCENT' => 50,
			'CODE' => 'CONVERT_ZONES',
			'CALLBACK' => 'stageConvertZones'
		));

		$this->addStage(array(
			'PERCENT' => 70,
			'CODE' => 'CONVERT_LINKS',
			'CALLBACK' => 'stageConvertLinks'
		));

		$this->addStage(array(
			'PERCENT' => 90,
			'STEP_SIZE' => 1,
			'CODE' => 'COPY_DEFAULT_LOCATIONS',
			'CALLBACK' => 'stageCopyDefaultLocations'
		));

		$this->addStage(array(
			'PERCENT' => 100,
			'STEP_SIZE' => 1,
			'CODE' => 'COPY_ZIP_CODES',
			'CALLBACK' => 'stageCopyZipCodes'
		));
	}

	public function onBeforePerformIteration()
	{
		if(\CSaleLocation::isLocationProMigrated())
			throw new Main\SystemException('Already migrated');

		if(!isset($this->data['migrator_data']))
			$this->migrator = new CUpdaterLocationPro();
		else
			$this->migrator = unserialize($this->data['migrator_data']);
	}

	public function onAfterPerformIteration()
	{
		$this->data['migrator_data'] = serialize($this->migrator);
		if($this->getPercent() == 100)
		{
			\CSaleLocation::locationProSetMigrated();
			\CSaleLocation::locationProEnable();
		}
	}

	protected function stageCreateTypes()
	{
		$this->migrator->createTypes();
		$this->nextStage();
	}

	protected function stageConvertTree()
	{
		if($this->getStep() == 0)
		{
			$this->migrator->convertTree();
			$this->nextStep();
		}
		else
		{
			$this->migrator->resetLegacyPath();
			$this->nextStage();
		}
	}

	protected function stageConvertZones()
	{
		$this->migrator->convertSalesZones();
		$this->nextStage();
	}

	protected function stageConvertLinks()
	{
		$this->migrator->convertGroupLocationLinks();
		$this->migrator->convertDeliveryLocationLinks();
		$this->migrator->convertTaxRateLocationLinks();
		$this->nextStage();
	}

	protected function stageCopyDefaultLocations()
	{
		$this->migrator->copyDefaultLocations();
		$this->nextStage();
	}

	protected function stageCopyZipCodes()
	{
		$this->migrator->copyZipCodes();
		$this->nextStage();
	}

	static public function hideNotifier()
	{
		\CAdminNotify::DeleteByTag(
			self::NOTIF_TAG
		);
	}
}