<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Sale\Location\Search;

use Bitrix\Main;

use Bitrix\Sale\Location;

final class ReindexProcess extends Location\Util\Process
{
	const DEBUG_MODE = 						true;

	protected $sessionKey = 				'location_reindex';

	protected $wordInstance = 				null;
	protected $chainInstance = 				null;

	public function __construct($options)
	{
		$this->addStage(array(
			'PERCENT' => 5,
			'CODE' => 'CLEANUP',
			'CALLBACK' => 'stageCleanup'
		));

		$this->addStage(array(
			'PERCENT' => 10,
			'CODE' => 'CREATE_DICTIONARY',
			'CALLBACK' => 'stageCreateDictionary',
			'SUBPERCENT_CALLBACK' => 'getSubpercentForStageCreateDictionary',
			'ON_BEFORE_CALLBACK' => 'stageCreateDictionaryBefore',
			'ON_AFTER_CALLBACK' => 'stageCreateDictionaryAfter',
			'TYPE' => static::CALLBACK_TYPE_QUOTA
		));

		$this->addStage(array(
			'PERCENT' => 20,
			'CODE' => 'RESORT_DICTIONARY',
			'CALLBACK' => 'stageResortDictionary',
			'SUBPERCENT_CALLBACK' => 'getSubpercentForStageResortDictionary',
			'ON_BEFORE_CALLBACK' => 'stageResortDictionaryBefore',
			'ON_AFTER_CALLBACK' => 'stageResortDictionaryAfter',
			'TYPE' => static::CALLBACK_TYPE_QUOTA
		));

		$this->addStage(array(
			'PERCENT' => 80,
			'CODE' => 'CREATE_SEARCH_INDEX',
			'CALLBACK' => 'stageCreateSearchIndex',
			'SUBPERCENT_CALLBACK' => 'getSubpercentForStageCreateSearchIndex',
			'ON_BEFORE_CALLBACK' => 'stageCreateSearchIndexBefore',
			'ON_AFTER_CALLBACK' => 'stageCreateSearchIndexAfter',
			'TYPE' => static::CALLBACK_TYPE_QUOTA
		));

		$this->addStage(array(
			'PERCENT' => 90,
			'CODE' => 'CREATE_SITE2LOCATION_INDEX',
			'CALLBACK' => 'stageCreateSite2LocationIndex',
			'SUBPERCENT_CALLBACK' => 'getSubpercentForCreateSite2LocationIndex'
		));

		$this->addStage(array(
			'PERCENT' => 100,
			'CODE' => 'RESTORE_DB_INDEXES',
			'CALLBACK' => 'stageRestoreDBIndexes',
			'SUBPERCENT_CALLBACK' => 'getSubpercentForRestoreDBIndexes'
		));

		parent::__construct($options);
	}

	public function onAfterPerformIteration()
	{
		if($this->getPercent() == 100)
			Finder::setIndexValid();
	}

	/////////////////////////////////////
	// STAGES

	protected function stageCleanup()
	{
		Finder::setIndexInvalid();

		WordTable::cleanUpData();
		ChainTable::cleanUpData();
		SiteLinkTable::cleanUpData();

		$this->nextStage();
	}

	///////////////////////////////////////////////

	protected function stageCreateDictionaryBefore()
	{
		if(!isset($this->data['WORD_TABLE_INSTANCE_SERIALIZED']))
		{
			$instance = new WordTable(array(
				'TYPES' => Finder::getIndexedTypes(),
				'LANGS' => Finder::getIndexedLanguages()
			));
		}
		else
		{
			$instance = unserialize($this->data['WORD_TABLE_INSTANCE_SERIALIZED']);
		}

		$this->wordInstance = $instance;
	}

	protected function stageCreateDictionary()
	{
		if($this->getStep() == 0)
		{
			$this->wordInstance->setOffset(0);
			$this->wordInstance->setPosition(0);
		}

		return $this->wordInstance->initializeData();
	}

	protected function stageCreateDictionaryAfter()
	{
		$this->data['WORD_TABLE_INSTANCE_SERIALIZED'] = serialize($this->wordInstance);
		$this->data['OFFSET'] = $this->wordInstance->getOffset();
	}

	protected function getSubpercentForStageCreateDictionary()
	{
		if(!isset($this->data['LOC_NAMES_2_INDEX_COUNT']))
		{
			$item = Location\Name\LocationTable::getList(array(
				'select' => array('CNT'),
				'filter' => WordTable::getFilterForInitData(array(
					'TYPES' => Finder::getIndexedTypes(),
					'LANGS' => Finder::getIndexedLanguages()
				))
			))->fetch();

			$this->data['LOC_NAMES_2_INDEX_COUNT'] = intval($item['CNT']);
		}

		return $this->getSubPercentByTotalAndDone($this->data['LOC_NAMES_2_INDEX_COUNT'], $this->data['OFFSET']);
	}

	///////////////////////////////////////////////

	protected function stageResortDictionaryBefore()
	{
		if(!isset($this->data['WORD_TABLE_INSTANCE_SERIALIZED']))
		{
			$instance = new WordTable(array(
				'TYPES' => Finder::getIndexedTypes(),
				'LANGS' => Finder::getIndexedLanguages()
			));
		}
		else
			$instance = unserialize($this->data['WORD_TABLE_INSTANCE_SERIALIZED']);

		$this->wordInstance = $instance;
	}

	protected function stageResortDictionary()
	{
		if($this->getStep() == 0)
		{
			$this->wordInstance->setOffset(0);
			$this->wordInstance->setPosition(0);
		}

		$allDone = $this->wordInstance->resort();

		if($allDone)
			$this->wordInstance->mergeResort();

		return $allDone;
	}

	protected function stageResortDictionaryAfter()
	{
		$this->data['WORD_TABLE_INSTANCE_SERIALIZED'] = serialize($this->wordInstance);
		$this->data['OFFSET'] = $this->wordInstance->getOffset();
	}

	protected function getSubpercentForStageResortDictionary()
	{
		if($this->getStep() == 0)
			$this->data['OFFSET'] = 0;

		if(!isset($this->data['DICTIONARY_SIZE']))
		{
			$item = WordTable::getList(array(
				'select' => array('CNT')
			))->fetch();

			$this->data['DICTIONARY_SIZE'] = intval($item['CNT']);
		}

		return $this->getSubPercentByTotalAndDone($this->data['DICTIONARY_SIZE'], $this->data['OFFSET']);
	}

	///////////////////////////////////////////////

	protected function stageCreateSearchIndexBefore()
	{
		if(!isset($this->data['CHAIN_TABLE_INSTANCE_SERIALIZED']))
		{
			$instance = new ChainTable(array(
				'TYPES' => Finder::getIndexedTypes()
			));
		}
		else
			$instance = unserialize($this->data['CHAIN_TABLE_INSTANCE_SERIALIZED']);

		$this->chainInstance = $instance;
	}

	protected function stageCreateSearchIndex()
	{
		return $this->chainInstance->initializeData();
	}

	protected function stageCreateSearchIndexAfter()
	{
		$this->data['CHAIN_TABLE_INSTANCE_SERIALIZED'] = serialize($this->chainInstance);
		$this->data['OFFSET'] = $this->chainInstance->getOffset();
	}

	protected function getSubpercentForStageCreateSearchIndex()
	{
		if($this->getStep() == 0)
			$this->data['OFFSET'] = 0;

		if(!isset($this->data['INDEX_LOCATION_COUNT']))
		{
			$item = Location\LocationTable::getList(array(
				'select' => array(
					'CNT'
				),
				'filter' => ChainTable::getFilterForInitData(array('TYPES' => Finder::getIndexedTypes()))
			))->fetch();

			$this->data['INDEX_LOCATION_COUNT'] = intval($item['CNT']);
		}

		return $this->getSubPercentByTotalAndDone($this->data['INDEX_LOCATION_COUNT'], $this->data['OFFSET']);
	}

	///////////////////////////////////////////////

	protected function stageCreateSite2LocationIndex()
	{
		SiteLinkTable::initializeData();

		$this->nextStage();
	}
	protected function getSubpercentForCreateSite2LocationIndex()
	{
		return 0;
	}

	protected function stageRestoreDBIndexes()
	{
		$step = $this->getStep();

		if($step == 0)
			WordTable::createIndex();
		elseif($step == 1)
			ChainTable::createIndex();
		elseif($step == 2)
			SiteLinkTable::createIndex();

		if($step >= 2)
			$this->nextStage();
		else
			$this->nextStep();
	}
	protected function getSubpercentForRestoreDBIndexes()
	{
		$pRange = $this->getCurrentPercentRange();
		$step = $this->getStep();

		$indexCount = 3;

		if($step >= $indexCount)
			return $pRange;
		else
		{
			return round($pRange * ($step / $indexCount));
		}
	}
}