<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

/*
//HOWTO call:

$compiler = new Compiler\Compiler(array(
	'workDir' => 'locations_data/',
	'grabbedStuffDir' => 'locations_data/grabber/output/',
	'grabber' => $grabber,
	'includeYaInfo2Name' => false,
	'fiasAddrobjFile' => 'locations_data/addrobj.xml'
));
$compiler->compile();
*/

namespace Bitrix\Sale\Location\Import\Compiler;

use Bitrix\Sale\Location\Import;

final class Compiler {

	const OUTPUT_DIR = 		'compiled/';
	const MAPS_DIR = 		'maps/';
	const INPUT_DIR = 		'output/';
	const OUTPUT_FILE = 	'output.txt';
	const CODE_LENGTH = 	10;
	const TMP_DATA_DIR = 	'tmp/';
	const STATIC_CSV_DIR = 	'static_csv/';

	const GROUP_FILE = 'typegroup.csv';

	const KAZAKHSTAN_SOURCE = 	'ukrain_kazakhstan/loc_kz.csv';
	const USA_SOURCE = 			'ukrain_kazakhstan/loc_usa.csv';
	const WORLD_SOURCE = 		'ukrain_kazakhstan/loc_cntr.csv';
	const CIS_SOURCE = 			'ukrain_kazakhstan/loc_ussr_cut.csv';

	const RUSSIA_YANDEX_CODE = 					225;
	const BELORUSSIA_YANDEX_CODE = 				149;
	const UKRAIN_YANDEX_CODE = 					187;
	const KAZAKHSTAN_YANDEX_CODE = 				159;

	const SOURCE_YANDEX = 		'Y'; // got from yandex
	const SOURCE_FIAS = 		'F'; // got from fias
	const SOURCE_UKRAIN = 		'U'; // data sent by the office in ukrain
	const SOURCE_LEGACY = 		'L'; // got from old location files

	const TMP_DATA_RUS_EXPORT_INDEX = 		'rus_exp_index';
	const TMP_DATA_RUS_GLOBAL_INDEX = 		'glob_exp_index';

	private $queue = false;

	private $typeMap = array(
		'SUBJECT_FEDERATION' => 'REGION'
	);

	private $headers = array(
		'SHORT' => array('CODE', 'PARENT_CODE', 'NAME.RU.NAME', 'NAME.EN.NAME', 'NAME.UA.NAME'),
		'LONG' => array('CODE', 'PARENT_CODE', 'TYPE_CODE', 'NAME.RU.NAME', 'NAME.EN.NAME', 'NAME.UA.NAME', 'LONGITUDE', 'LATITUDE', 'EXT.YAMARKET.0', 'EXT.ZIP.0'/*, 'EXT.ZIP.1', 'EXT.ZIP.2', 'EXT.ZIP.3'*/),
		'GROUP_FILE' => array('CODE', 'TYPES')
	);

	private $typeGroups = array(
		'LAYOUT' => array(
			'CODE' => 'LAYOUT',
			'TYPES' => array('COUNTRY', 'COUNTRY_DISTRICT', 'REGION'),
			'HEADER' => 'LONG',
			'FILE_NAME_TEMPLATE' => 'layout.csv'
		),
		/*
		'SELECTABLE' => array(
			'TYPES' => array('COUNTRY', 'COUNTRY_DISTRICT', 'REGION'),
			'HEADER' => 'LONG',
			'FILE_NAME_TEMPLATE' => 'selectable.csv'
		),
		*/
		'AREAS' => array(
			'CODE' => 'AREAS',
			'TYPES' => array('CITY', 'SUBREGION', 'VILLAGE'/*, 'CITY_DISTRICT', 'METRO_STATION', 'OTHER'*/),
			'PARENT' => 'LAYOUT',
			'HEADER' => 'LONG',
			'FILE_NAME_TEMPLATE' => '%BASE_PARENT_ITEM_CODE%_%CODE%.csv'
		),
		'STREETS' => array(
			'CODE' => 'STREETS',
			'TYPES' => array('STREET'),
			'PARENT' => 'LAYOUT',
			'HEADER' => 'LONG',
			'FILE_NAME_TEMPLATE' => '%BASE_PARENT_ITEM_CODE%_%CODE%.csv'
		)
	);

	private $fiasToBaseType = array(
		'COUNTRY' => array(),
		'COUNTRY_DISTRICT' => array(
			'РѕРєСЂСѓРі' => array('R' => 'РѕРєСЂСѓРі', 'U' => true),
		),
		'REGION' => array(
			'РђРћ' => array('R' => 'Р°РІС‚РѕРЅРѕРјРЅС‹Р№ РѕРєСЂСѓРі', 'U' => true),
			'РђРѕР±Р»' => array('R' => 'Р°РІС‚РѕРЅРѕРјРЅР°СЏ РѕР±Р»Р°СЃС‚СЊ', 'U' => true),
			'РєСЂР°Р№' => array('R' => 'РєСЂР°Р№', 'U' => true),
			'РѕР±Р»' => array('R' => 'РѕР±Р»Р°СЃС‚СЊ', 'U' => true),
			'Р РµСЃРї' => array('R' => 'СЂРµСЃРїСѓР±Р»РёРєР°', 'U' => true),
			'Р§СѓРІР°С€РёСЏ' => array('R' => 'СЂРµСЃРїСѓР±Р»РёРєР°', 'U' => true)
		),

		'SUBREGION' => array(
			'СЂ-РЅ' => array('R' => 'СЂР°Р№РѕРЅ', 'U' => true),

			'СѓР»СѓСЃ' => array('R' => 'СѓР»СѓСЃ', 'U' => true),
			'Сѓ' => array('R' => 'СѓР»СѓСЃ', 'U' => true),
		),

		'CITY' => array(
			'Рі' => array('R' => 'РіРѕСЂРѕРґ', 'U' => true),
		),
		'VILLAGE' => array(
			'РїРіС‚' => array('R' => 'РїРѕСЃС‘Р»РѕРє РіРѕСЂРѕРґСЃРєРѕРіРѕ С‚РёРїР°', 'U' => true),
			'Рї' => array('R' => 'РїРѕСЃС‘Р»РѕРє', 'U' => true),
			'РґРї' => array('R' => 'РґР°С‡РЅС‹Р№ РїРѕСЃС‘Р»РѕРє', 'U' => true),
			'СЃ/Рї' => array('R' => 'СЃРµР»СЊСЃРєРѕРµ РїРѕСЃРµР»РµРЅРёРµ', 'U' => true),
			'Р°Р°Р»' => array('R' => 'Р°Р°Р»', 'U' => true),
			'Р°СѓР»' => array('R' => 'Р°СѓР»', 'U' => true),
			'Р°СЂР±Р°РЅ' => array('R' => 'Р°СЂР±Р°РЅ', 'U' => true),
			'Рґ' => array('R' => 'РґРµСЂРµРІРЅСЏ', 'U' => true),
			'РЅРї' => array('R' => 'РЅР°СЃРµР»С‘РЅРЅС‹Р№ РїСѓРЅРєС‚', 'U' => true),
			'СЃР»' => array('R' => 'СЃР»РѕР±РѕРґР°', 'U' => true),
			'С…' => array('R' => 'С…СѓС‚РѕСЂ', 'U' => true),
			'С„РµСЂРјР°' => array('R' => 'С„РµСЂРјР°', 'U' => true),
			'СЃ' => array('R' => 'СЃРµР»Рѕ', 'U' => true),
			'СЂРї' => array('R' => 'СЂР°Р±РѕС‡РёР№ РїРѕСЃС‘Р»РѕРє', 'U' => true),
			'СЃС‚' => array('R' => 'СЃС‚Р°РЅС†РёСЏ', 'U' => true),
			'Рї/СЃС‚' => array('R' => 'РїРѕСЃС‘Р»РѕРє', 'U' => true),
			'СЃС‚-С†Р°' => array('R' => 'СЃС‚Р°РЅРёС†Р°', 'U' => true),
			'РєРї' => array('R' => 'РєСѓСЂРѕСЂС‚РЅС‹Р№ РїРѕСЃРµР»РѕРє', 'U' => true),
			'Р¶/Рґ_СЃС‚' => array('R' => 'Р¶РµР»РµР·РЅРѕРґРѕСЂРѕР¶РЅР°СЏ СЃС‚Р°РЅС†РёСЏ'),
			'С‚РµСЂ' => array('R' => 'С‚РµСЂСЂРёС‚РѕСЂРёСЏ'),
			'РѕСЃС‚СЂРѕРІ' => array('R' => 'РѕСЃС‚СЂРѕРІ'),

			'РјРєСЂ' => array('R' => 'РјРёРєСЂРѕСЂР°Р№РѕРЅ', 'U' => true),
			'СЃ/СЃ' => array('R' => 'СЃРµР»СЊСЃРѕРІРµС‚', 'U' => true),
			'Рї/Рѕ' => array('R' => 'РїРѕС‡С‚РѕРІРѕРµ РѕС‚РґРµР»РµРЅРёРµ', 'U' => true),
			'Рј' => array('R' => 'РјРµСЃС‚РµС‡РєРѕ', 'U' => true),
			'СЃ/РјРѕ' => array('R' => 'СЃРјРѕ', 'U' => true),
			'Р¶РёР»СЂР°Р№РѕРЅ' => array('R' => 'Р¶РёР»СЂР°Р№РѕРЅ', 'U' => true),
			'РјР°СЃСЃРёРІ' => array('R' => 'РјР°СЃСЃРёРІ'),
			'Р¶/Рґ_РѕРї' => array('R' => 'Р¶/Рґ РѕСЃС‚Р°РЅРѕРІРєР°'),
			'СЃ/Р°' => array('R' => 'СЃРµР»СЊСЃРєР°СЏ Р°РґРјРёРЅРёСЃС‚СЂР°С†РёСЏ', 'U' => true),
			'Рї/СЂ' => array('R' => 'РїР»Р°РЅРёСЂРѕРІРѕС‡РЅС‹Р№ СЂР°Р№РѕРЅ'),
			'Р¶/Рґ_СЂР·Рґ' => array('R' => 'Р¶/Рґ СЂР°Р·СЉРµР·Рґ'),
			'СЃРЅС‚' => array('R' => 'СЃРЅС‚', 'U' => true),
			'СЃ/Рѕ' => array('R' => 'СЃРµР»СЊСЃРєРёР№ РѕРєСЂСѓРі'),
			'Р·Р°РёРјРєР°' => array('R' => 'Р·Р°РёРјРєР°'),
			'РіРѕСЂРѕРґРѕРє' => array('R' => 'РіРѕСЂРѕРґРѕРє', 'U' => true)
		),
		
		//'CITY_DISTRICT' => array(),
		//'METRO_STATION' => array(),
		
		'STREET' => array(
			'СѓР»' => array('R' => 'СѓР»РёС†Р°', 'U' => true),
			'РєРІ-Р»' => array('R' => 'РєРІР°СЂС‚Р°Р»', 'U' => true),
			'Р°Р»Р»РµСЏ' => array('R' => 'Р°Р»Р»РµСЏ', 'U' => true),
			'РІР°Р»' => array('R' => 'РІР°Р»', 'U' => true),
			'РІСЉРµР·Рґ' => array('R' => 'РІСЉРµР·Рґ', 'U' => true),
			'РЅР°Р±' => array('R' => 'РЅР°Р±РµСЂРµР¶РЅР°СЏ', 'U' => true),
			'РїРµСЂ' => array('R' => 'РїРµСЂРµСѓР»РѕРє', 'U' => true),
			'РїР»' => array('R' => 'РїР»РѕС‰Р°РґСЊ', 'U' => true),
			'РїСЂ-РєС‚' => 		array('R' => 'РїСЂРѕСЃРїРµРєС‚', 	'U' => true),
			'РїСЂРѕРµР·Рґ' => 	array('R' => 'РїСЂРѕРµР·Рґ', 		'U' => true),
			'РїСЂРѕСѓР»РѕРє' => 	array('R' => 'РїСЂРѕСѓР»РѕРє', 	'U' => true),
			'СЂР·Рґ' => 		array('R' => 'СЂР°Р·СЉРµР·Рґ', 	'U' => true),
			'СЃР°Рґ' => 		array('R' => 'СЃР°Рґ', 		'U' => true),
			'СЃРєРІРµСЂ' => 		array('R' => 'СЃРєРІРµСЂ', 		'U' => true),
			'СЃРїСѓСЃРє' => array('R' => 'СЃРїСѓСЃРє', 'U' => true),
			'С‚РѕРЅРЅРµР»СЊ' => array('R' => 'С‚РѕРЅРЅРµР»СЊ', 'U' => true),
			'С‚СЂР°РєС‚' => array('R' => 'С‚СЂР°РєС‚', 'U' => true),
			'С‚СѓРї' => array('R' => 'С‚СѓРїРёРє', 'U' => true),
			'СЌСЃС‚Р°РєР°РґР°' => array('R' => 'СЌСЃС‚Р°РєР°РґР°', 'U' => true),
			'Р±-СЂ' => array('R' => 'Р±СѓР»СЊРІР°СЂ', 'U' => true),
			'Р±СѓРіРѕСЂ' => array('R' => 'Р±СѓРіРѕСЂ', 'U' => true),
			'Р·Р°РµР·Рґ' => array('R' => 'Р·Р°РµР·Рґ', 'U' => true),
			'РєР°РЅР°Р»' => array('R' => 'РєР°РЅР°Р»', 'U' => true),
			'РєРј' => array('R' => 'РєРёР»РѕРјРµС‚СЂ', 'U' => true),
			'РєРѕР»СЊС†Рѕ' => array('R' => 'РєРѕР»СЊС†Рѕ', 'U' => true),
			'РїР°СЂРє' => array('R' => 'РїР°СЂРє', 'U' => true),
			'РїРµСЂРµРµР·Рґ' => array('R' => 'РїРµСЂРµРµР·Рґ', 'U' => true),
			'СЃС‚СЂ' => array('R' => 'СЃС‚СЂРѕРµРЅРёРµ', 'U' => true),
			'РїСЂРѕСЃРµРє' => array('R' => 'РїСЂРѕСЃРµРє', 'U' => true),
			'С€' => array('R' => 'С€РѕСЃСЃРµ', 'U' => true),
			'Р°РІС‚РѕРґРѕСЂРѕРіР°' => array('R' => 'РґРѕСЂРѕРіР°', 'U' => true)
		)
	);

	private $forbiddenPathTypes = array(
    'СѓР»' => 1,
    'РїСЂ-РєС‚' => 1,
    'РїСЂРѕРµР·Рґ' => 1,
    'С€' => 1,
    'РєРІ-Р»' => 1,
    'РєРј' => 1,
    'РїСЂРѕСЃРµРє' => 1,
    'РїРµСЂ' => 1,
    'Р±-СЂ' => 1,
    'РЅР°Р±' => 1,
    'Р¶/Рґ_Р±СѓРґРєР°' => 1,
    'С‚СЂР°РєС‚' => 1,
    'РґРѕСЂ' => 1,
    'СЂР·Рґ' => 1,
    'РїР»' => 1,
    'РІС‹СЃРµР»' => 1,
    'СЃР°Рґ' => 1,
    'СѓС‡-Рє' => 1,
    'РїСЂРѕРјР·РѕРЅР°' => 1,
    'Р°РІС‚РѕРґРѕСЂРѕРіР°' => 1,
    'Р¶/Рґ_РїР»Р°С‚С„' => 1,
	);

	private $forbiddenPathIds = array(
		'af7cdb7f-e47d-4f65-93d5-3a2b70a809ce' => true, // Р‘РѕСЂРѕРІРѕР№ РјРёРєСЂРѕСЂР°Р№РѕРЅ should be placed inside village, not street
		'762758bb-18b9-440f-bc61-8e1e77ff3fd8' => true, // РјРѕСЃРєРѕРІСЃРєРёР№ РїРѕСЃС‘Р»РѕРє cannot be inside РјРѕСЃРєРѕРІСЃРєРёР№ РіРѕСЂРѕРґ, those are the same
	);

	private $allowedFiasStats = array(
		'ACTSTATUS' => array(
			1 // Р°РєС‚СѓР°Р»СЊРЅС‹Р№
		),
		'LIVESTATUS' => array(
			1 // Р¶РёРІ!
		),
		'CURRSTATUS' => array(
			0, // Р°РєС‚СѓР°Р»СЊРЅС‹Р№
			51, // РїРµСЂРµРїРѕРґС‡РёРЅС‘РЅРЅС‹Р№
		)
	);

	private $filePools = array(

		'ukrain_kazakhstan' => array(
			'DIR' => 'ukrain_kazakhstan/'
		),

		// where we store clean and split fias data:
		'fias_tree' => array(
			'DIR' => 'fias_tree/'
		),

		// where we keep maps from yandex to fias (REGIONS, CITIES and VILLAGES)
		'fias_yamarket_links' => array(
			'DIR' => 'fias_yamarket_links/'
		),

		// where we keep result data
		'assets' => array(
			'DIR' => 'compiled/bundles/extended/'
		),
		// where we keep result data, only for russia
		'assets_standard' => array(
			'DIR' => 'compiled/bundles/standard/'
		),

		// where we keep result data
		'demo' => array(
			'DIR' => 'demo/'
		),
	);

	private $workDir = '';
	private $grabbedStuffDir = '';

	private $yaIdType = array();
	private $relations = array();

	private $options = array();

	// tree builder
	private $data = array();

	private $optionConvertNames = false;
	private $sysMaps = array();

	private $fiasCPath = array();

	private $fiasDB = null;
	private $eTreeDB = null;
	private $eTreeDBRussia = null;

public 	public function __construct($options)
	{
		$this->workDir = $options['workDir'];
		$this->grabbedStuffDir = $options['grabbedStuffDir'];

		$this->options = $options;

		//if(!file_exists($_SERVER['DOCUMENT_ROOT'].$this->workDir.self::OUTPUT_DIR))
		//	mkdir($_SERVER['DOCUMENT_ROOT'].$this->workDir.self::OUTPUT_DIR, 0700, true);

		foreach($this->typeGroups as $id => &$params)
			$params['I_TYPES'] = array_flip($params['TYPES']);

		foreach($this->fiasToBaseType as $type => $fTypes)
		{
			foreach($fTypes as $fType => $fReplace)
			{
				if(strlen($fType) && !empty($fReplace))
				{
					$this->sysMaps['FIAS2BASETYPE'][$fType] = $type;
					$this->sysMaps['FIASTYPEREPLACE'][$fType] = $fReplace['R'];
				}
			}
		}

		foreach($this->typeGroups as $groupId => $group)
		{
			//$this->output($group);

			foreach($group['TYPES'] as $type)
			{
				$this->sysMaps['BASETYPE2GROUP'][$type] = $groupId;
			}
		}

		$this->cleanOutput();
	}

public 	public function compile()
	{
		// step 1: build main tree from yandex market data
		//$this->buildMainTree();

		##########################################################
		#### MAP FIAS TO YANDEX
		##########################################################

		// step 2: processing huge fias file
		//$this->splitFiasOnRegions(); // split huge fias file onto small pieces of bundles
		/*
		Handwriting:
		move 0c5b2444-70a0-4932-980c-b4dc0d3f02b5;1;1;РњРѕСЃРєРІР°;Рі; TO 29251dcf-00a1-4e34-98d4-5c47484a36d4.csv
		move c2deb16a-0330-4f05-821f-1d09c93331e6;1;1;РЎР°РЅРєС‚-РџРµС‚РµСЂР±СѓСЂРі;Рі;190000 TO 6d1ebb35-70c6-4129-bd55-da3969658f5d.csv
		move 6fdecb78-893a-4e3f-a5ba-aa062459463b;1;1;РЎРµРІР°СЃС‚РѕРїРѕР»СЊ;Рі; TO bd8e6511-e4b9-4841-90de-6bbc231a789e.csv
		*/
		//$this->copyFias2DB(); // place fias to db

		// step 3: map fias to yandex id. There were some handwriting to map files, so do not uncomment unless you want files to be overwritten
		//$this->mapFiasRootV2(); // process fias root and find matches by regions
		// then we have a little handwriting on rootv2.csv
		//$this->mapFiasCities(); // find cities and villages matches
		// again, handwriting here

		##########################################################
		#### FIAS 2 DB
		##########################################################

		/*
		$this->copyFias2DB();

		update b_tmp_fias set PARENTGUID = '29251dcf-00a1-4e34-98d4-5c47484a36d4' where AOGUID = '0c5b2444-70a0-4932-980c-b4dc0d3f02b5' and AOID = '5c8b06f1-518e-496e-b683-7bf917e0d70b';
		update b_tmp_fias set PARENTGUID = '6d1ebb35-70c6-4129-bd55-da3969658f5d' where AOGUID = 'c2deb16a-0330-4f05-821f-1d09c93331e6' and AOID = 'aad1469e-54ff-4605-af4f-f016c75b84d2';
		update b_tmp_fias set PARENTGUID = 'bd8e6511-e4b9-4841-90de-6bbc231a789e' where AOGUID = '6fdecb78-893a-4e3f-a5ba-aa062459463b' and AOID = '6fdecb78-893a-4e3f-a5ba-aa062459463b';
		*/

		##########################################################
		#### MAKE EXPORT TABLE
		##########################################################

		/*
		$res = YandexGeoCoder::query(array(
			'query' => 'РќРµРЅРµС†РєРёР№ Р°РІС‚РѕРЅРѕРјРЅС‹Р№ РѕРєСЂСѓРі РЁРѕР№РЅР° СЃРµР»Рѕ РЁРєРѕР»СЊРЅР°СЏ СѓР»РёС†Р°',
			'kind' => YandexGeoCoder::KIND_STREET
		));
		*/

		$this->output('Build main tree');
		$this->buildMainTree();

		$this->createExportTables();

		/*
		###################################################################
		###################################################################
		###################################################################

		// making world
		$this->eTreeDB->cleanup();
		$this->eTreeDB->dropIndexes();

		$this->output('Generate export tree: Belarus');
		$this->generateExportTreeBelorussia();

		$this->output('Generate export tree: Kazakhstan');
		$this->generateExportTreeLegacy(self::KAZAKHSTAN_SOURCE);

		$this->output('Generate export tree: Ukrain');
		$this->generateExportTreeUkrain();

		$this->output('Generate export tree: USA');
		$this->generateExportTreeUSA();

		$this->output('Generate export tree: World Countries');
		$this->generateExportTreeWorld();

		$this->output('Generate export tree: EX-CIS');
		$this->generateExportTreeLegacy(self::CIS_SOURCE);

		###################################################################
		###################################################################
		###################################################################

		$this->output('Last occupied: '.$this->eTreeDB->getLastOccupiedCode());
		$this->output('Next free: '.$this->eTreeDB->getNextFreeCode());

		// making russia
		$this->eTreeDBRussia->cleanup();
		$this->eTreeDBRussia->dropIndexes();

		$this->output('Generate export tree: Russia');
		$this->generateExportTreeRussia();

		$this->output('Last occupied: '.$this->eTreeDBRussia->getLastOccupiedCode());
		$this->output('Next free: '.$this->eTreeDBRussia->getNextFreeCode());

		###################################################################
		###################################################################
		###################################################################
		*/

		$this->restoreExportTablesIndexes();

		$this->output('Build export files');

		/*
		$this->cleanPoolDir('assets');

		$this->eTreeDB->walkInDeep(array($this, 'generateExportFilesFromTableBundle')); // world
		$this->eTreeDBRussia->walkInDeep(array($this, 'generateExportFilesFromTableBundle')); // russia
		*/

		$this->cleanPoolDir('assets_standard');
		$this->eTreeDB->walkInDeep(array(
			'ITEM' => array($this, 'generateExportFilesFromTableBundle_Standard')
		)); // world
		$this->eTreeDBRussia->walkInDeep(array(
			'ITEM' => array($this, 'generateExportFilesFromTableBundle_Standard_YandexOnly')
		)); // russia

		/*
		// types by groups
		$this->makeTypeGroupFile(self::GROUP_FILE);

		$this->copyStaticCSV();
		*/

		###################################################################
		###################################################################
		###################################################################

		/*
		$this->output('Build demo files');

		$this->cleanPoolDir('demo');

		$this->eTreeDB->walkInDeep(array($this, 'generateDemoFilesWorld'), array('VILLAGE' => 1)); // world
		$this->eTreeDBRussia->walkInDeep(array($this, 'generateDemoFilesRussia'), array('VILLAGE' => 1)); // world
		*/

		/*
		РґРѕР±Р°РІРёС‚СЊ СЃСЋРґР° РіРµРЅРµСЂР°С†РёСЋ С„Р°Р№Р»Р° country_codes.php СЃ СЃРѕРґРµСЂР¶РёРјС‹Рј:

		<?
		$LOCALIZATION_COUNTRY_CODE_MAP = array(
			'ru' => '0000028023',
			'ua' => '0000000364',
			'kz' => '0000000276',
			'bl' => '0000000001'
		);

		СЌС‚РѕС‚ С„Р°Р№Р» РїРѕС‚РѕРј РёРґС‘С‚ РІ РјР°СЃС‚РµСЂ СѓСЃС‚Р°РЅРѕРІРєРё РёРЅС‚РµСЂРЅРµС‚-РјР°РіР°Р·РёРЅР°, РІРјРµСЃС‚Рµ СЃ РґРµРјРѕ-РґР°РЅРЅС‹РјРё, types.csv Рё externalservice.csv

		*/

		$this->output('DONE');
	}

	#######################################################
	### ABOUT EXPORT TABLE
	#######################################################

	private $allowedForDemo = array('COUNTRY' => 1, 'COUNTRY_DISTRICT' => 1, 'REGION' => 1, 'SUBREGION' => 1, 'CITY' => 1);
	private $demoCategory = false;

public 	public function generateDemoFilesWorld($item, $table)
	{
		if(!isset($this->allowedForDemo[$item['TYPE_CODE']]))
			return;

		if($item['TYPE_CODE'] == 'COUNTRY')
		{
			$this->addItemToCSV('world', 'demo', $item);

			// this part must not depend on codes, which may flow left and right. in future we may add 
			// some markers like "is_ukrain" or "is_russia" etc to database instead of relying on names
			if($item['NAME'] == 'РЈРєСЂР°С—РЅР°')
				$this->demoCategory = 'ukrain';
			elseif($item['NAME'] == 'РљР°Р·Р°С…СЃС‚Р°РЅ')
				$this->demoCategory = 'kazakhstan';
			elseif($item['NAME'] == 'Р‘РµР»Р°СЂСѓСЃСЊ')
				$this->demoCategory = 'belarus';
			elseif($item['NAME'] == 'РЎРЁРђ')
				$this->demoCategory = 'usa';
			else
				$this->demoCategory = false;
		}

		//$this->output($table->getWalkPathString());
		//$this->output($this->demoCategory);

		if($this->demoCategory !== false)
			$this->addItemToCSV($this->demoCategory, 'demo', $item);
	}

public 	public function generateDemoFilesRussia($item, $table)
	{
		if(!isset($this->allowedForDemo[$item['TYPE_CODE']]))
			return;

		if($item['TYPE_CODE'] == 'COUNTRY')
			$this->addItemToCSV('world', 'demo', $item);

		$this->addItemToCSV('russia', 'demo', $item);//done
	}

public 	private function createExportTables()
	{
		$this->eTreeDB = new Db\ExportTreeTable();
		$this->eTreeDB->create();

		$this->eTreeDBRussia = new Db\ExportTreeRussiaTable();
		$this->eTreeDBRussia->create();
	}

public static 	private function cleanUpExportTables()
	{
		$this->eTreeDB->cleanup();
		$this->eTreeDB->dropIndexes();

		$this->eTreeDBRussia->cleanup();
		$this->eTreeDBRussia->dropIndexes();
	}

public 	private function restoreExportTablesIndexes()
	{
		$this->eTreeDB->restoreIndexes();
		$this->eTreeDBRussia->restoreIndexes();
	}

public 	private function copyStaticCSV()
	{
		$workDir = $_SERVER['DOCUMENT_ROOT'].'/'.$this->workDir;

		system('cp '.$workDir.self::STATIC_CSV_DIR.'externalservice.csv '.$workDir.'/'.self::OUTPUT_DIR);
		system('cp '.$workDir.self::STATIC_CSV_DIR.'type.csv '.$workDir.'/'.self::OUTPUT_DIR);
	}

	private $currentParentGroup = '';

public 	private function addItemToCSV($fName, $group, $item)
	{
		$data = array(
			'CODE' => 			$item['CODE'],
			'PARENT_CODE' => 	$item['PARENT_CODE'],
			'TYPE_CODE' => 		$item['TYPE_CODE']
		);

		$data['NAME.RU.NAME'] = '';
		$data['NAME.EN.NAME'] = '';
		$data['NAME.UA.NAME'] = '';

		$name = unserialize($item['LANGNAMES']);
		foreach($name as $lid => $values)
		{
			foreach($values as $i => $val)
				$data['NAME.'.$lid.'.'.$i] = $val;
		}

		$data['EXT.YAMARKET.0'] = '';
		$data['EXT.ZIP.0'] = '';

		$externals = unserialize($item['EXTERNALS']);
		if(!empty($externals))
		{
			foreach($externals as $type => $values)
			{
				if(is_array($values))
				{
					foreach($values as $i => $val)
						$data['EXT.'.$type.'.'.$i] = $val;
				}
			}
		}

		$data['LONGITUDE'] = $item['LONGITUDE'];
		$data['LATITUDE'] = $item['LATITUDE'];

		/*
		$this->output($data);
		$this->output($group);
		$this->output($fName);
		*/

		$this->putToFile2(
			$data,
			$group,
			$fName,
			true
		);
	}

public 	public function generateExportFilesFromTableBundle($item, $table)
	{
		if(in_array($item['TYPE_CODE'], $this->typeGroups['LAYOUT']['TYPES']))
			$this->currentParentGroup = $item['CODE'];

		########################################################
		########################################################
		########################################################

		$cat = $this->sysMaps['BASETYPE2GROUP'][$item['TYPE_CODE']];
		$fName = $this->typeGroups[$cat]['FILE_NAME_TEMPLATE'];

		$fName = str_replace(array(
			'%BASE_PARENT_ITEM_CODE%',
			'%CODE%',
			'.csv'
		), array(
			$cat == 'LAYOUT' ? '' : $this->currentParentGroup,
			ToLower($cat),
			''
		), $fName);

		$this->addItemToCSV($fName, 'assets', $item);

		########################################################
		########################################################
		########################################################
	}

	public function generateExportFilesFromTableBundle_Standard($item, $table)
	{
		if(in_array($item['TYPE_CODE'], $this->typeGroups['LAYOUT']['TYPES']))
			$this->currentParentGroup = $item['CODE'];

		########################################################
		########################################################
		########################################################

		$cat = $this->sysMaps['BASETYPE2GROUP'][$item['TYPE_CODE']];
		$fName = $this->typeGroups[$cat]['FILE_NAME_TEMPLATE'];

		$fName = str_replace(array(
			'%BASE_PARENT_ITEM_CODE%',
			'%CODE%',
			'.csv'
		), array(
			$cat == 'LAYOUT' ? '' : $this->currentParentGroup,
			ToLower($cat),
			''
		), $fName);

		$this->addItemToCSV($fName, 'assets_standard', $item);

		########################################################
		########################################################
		########################################################
	}

public 	public function generateExportFilesFromTableBundle_Standard_YandexOnly($item, $table)
	{
		if(in_array($item['TYPE_CODE'], $this->typeGroups['LAYOUT']['TYPES']))
			$this->currentParentGroup = $item['CODE'];

		########################################################
		########################################################
		########################################################

		if($item['TYPE_CODE'] == 'VILLAGE' && strpos($item['EXTERNALS'], 'YAMARKET') === false/*not from yandex database*/)
		{
			//$this->output($item['NAME'].' skipped');
			return false;
		}

		$cat = $this->sysMaps['BASETYPE2GROUP'][$item['TYPE_CODE']];
		$fName = $this->typeGroups[$cat]['FILE_NAME_TEMPLATE'];

		$fName = str_replace(array(
			'%BASE_PARENT_ITEM_CODE%',
			'%CODE%',
			'.csv'
		), array(
			$cat == 'LAYOUT' ? '' : $this->currentParentGroup,
			ToLower($cat),
			''
		), $fName);

		$this->addItemToCSV($fName, 'assets_standard', $item);

		########################################################
		########################################################
		########################################################

		return true;
	}

	public private function generateExportTreeRussia()
	{
		$this->fiasDB = new Db\FiasTable();

		$this->eTreeDBRussia->dropCodeIndex();
		$this->eTreeDBRussia->setExportOffset(intval($this->eTreeDB->getNextFreeCode())); // start where the previous table ended

		// get yandex regions
		$regions = $this->readFiasRootMapV2();

		// add Russia (country), districts and regions, that are taken from yandex
		$this->generateExportTreePutRussiaBundle(array(self::RUSSIA_YANDEX_CODE), $regions, 0);

		// add all precious content from fias: subregions, cities, villages, streets
		$this->generateExportTreePutRussiaInner();

		$this->eTreeDBRussia->doneInsert();
		$this->eTreeDBRussia->switchIndexes(true);
	}

public 	private function getYandexToFiasCityMap($yandexRegionId)
	{
		$result = array();
		$fias2yandex = $this->getDataFromCSV('fias_yamarket_links', 'region_'.$yandexRegionId);
		foreach($fias2yandex as $map)
		{
			unset($map['HZ']); // csv viewer would crash without this key (wonder why)
			$result[$map['AOGUID']] = $map;
		}
		
		return $result;
	}

	prpublic ivate function generateExportTreePutRussiaInner()
	{
		// get yandex-to-fias region code map
		$links = $this->getFias2YamarketRootLinks(false, true);

		$i = -1;
		// for each region we must dump its content
		// $yRId is a yandex id for the target region
		// $fiasRegions is one (mostly) or several corresponding "regions" from fias
		foreach($links as $yRId => $fiasRegions)
		{
			$i++;

			// get yandex-to-fias city code map for the current region
			$this->fias2yandexCityMap = $this->getYandexToFiasCityMap($yRId);
			$this->currentRegion = $this->mapETCodeAsYandex($yRId);

			$this->eTreeDBRussia->dropCodeIndex(); // drop previous region index

			foreach($fiasRegions as $regionGuid)
			{
				$this->fiasPath = array();
				$this->generateExportTreePutRussiaInnerBundle($regionGuid);
			}

			//break;
		}
	}

public 	private function checkIsAllowedCityVillage($item)
	{
		$baseType = $this->sysMaps['FIAS2BASETYPE'][$item['SHORTNAME']];

		//if($baseType == 'CITY' || $baseType == 'VILLAGE')
		//	$this->output('Doubtfull city\village: '.$item['FORMALNAME'].' ('.$item['SHORTNAME'].')');

		$skip = array('5544bf6a-0ec1-4b5f-bbc5-49294f71de16'/*С‚СЏРіР»РѕРІР°СЏ РїРѕРґСЃС‚Р°РЅС†РёСЏ*/, '22a77f13-3764-41dc-aa23-db680b03ef5d'/*6 РєРј РђР—РЎ*/, '3f2ab130-274e-4fd6-b611-a94467b04f57'/*Р’РµР»С‚РѕРЅ РџР°СЂРє duplicate*/, '762758bb-18b9-440f-bc61-8e1e77ff3fd8', /*РњРѕСЃРєРѕРІСЃРєРёР№ РїРѕСЃС‘Р»РѕРє, not exists*/);

		return 	($baseType == 'CITY' || $baseType == 'VILLAGE') && 
				(
					$this->fiasToBaseType[$baseType][$item['SHORTNAME']]['U'] /*code is in a list of allowed types for export*/
					||
					isset($this->fias2yandexCityMap[$item['AOGUID']] /*code is present in yandex2fias city map*/
				) &&
				!in_array($item['AOGUID'], $skip) // not one of those forbidden broken items
				);
	}

public 	private function checkIsAllowedStreet($item)
	{
		$baseType = $this->sysMaps['FIAS2BASETYPE'][$item['SHORTNAME']];

		return ($baseType == 'STREET' && $this->fiasToBaseType[$baseType][$item['SHORTNAME']]['U']); /*street code is in a list of allowed types for export*/
	}

public 	public function generateExportTreePutRussiaFiasPathCutForbidden($path)
	{
		/*
		$object = $path[count($path) - 1];
		if($object['AOGUID'] == 'da99f366-1a88-43b1-9aa8-c1b66334c97f')
		{
			$found = true;

			\_print_r('Wow: da99f366-1a88-43b1-9aa8-c1b66334c97f');
			\_print_r($object);
		}
		*/

		//$path = array_reverse($path);

		$newPath = array();
		$lastValidId = false;
		$neepPasteLastValid = false;
		foreach($path as $item)
		{
			if(isset($this->forbiddenPathTypes[$item['SHORTNAME']]) || isset($this->forbiddenPathIds[$item['AOGUID']]) || $this->checkIsAllowedStreet($item['SHORTNAME']))
			{
				$neepPasteLastValid = true;
				continue;
			}
			else
			{
				if($lastValidId !== false && $neepPasteLastValid)
				{
					$item['PARENTGUID'] = $lastValidId;
					$neepPasteLastValid = false;
				}
				
				$lastValidId = $item['AOGUID'];
			}

			$newPath[] = $item;
		}

		/*
		if($found)
		{
			\_print_r('Path is now:');
			\_print_r($newPath);
			die();
		}
		*/

		return $newPath;
	}

public 	private function generateExportTreePutRussiaFiasPath($targetItem)
	{
		// pre-process, cut off unwanted types (actually, streets)
		$newPath = $this->generateExportTreePutRussiaFiasPathCutForbidden($this->fiasPath);

		$i = -1;
		foreach($newPath as $item)
		{
			$i++;

			// external data
			$externals = array();
			if(strlen($item['POSTALCODE']))
				$externals['ZIP'][] = $item['POSTALCODE'];
			if(isset($this->fias2yandexCityMap[$item['AOGUID']]))
				$externals['YAMARKET'][] = $this->fias2yandexCityMap[$item['AOGUID']]['ID'];

			// type and name
			$itemType = $item['SHORTNAME'];
			$baseType = $this->sysMaps['FIAS2BASETYPE'][$itemType];
			$typeNameReplace = $baseType != 'CITY' ? $this->sysMaps['FIASTYPEREPLACE'][$itemType] : ''; // replace base type (e.g. "Рї" => "РїРѕСЃС‘Р»РѕРє", "Рґ" => "РґРµСЂРµРІРЅСЏ", ...)
			$name = trim($item['FORMALNAME']).(strlen($typeNameReplace) ? ' '.$typeNameReplace : '');

			$this->eTreeDBRussia->insert(array(
				'TYPE_CODE' => 			$baseType,
				'FIAS_TYPE' => 			$itemType,
				'NAME' => 				$name,
				'LANGNAMES' => 			array('RU' => array('NAME' => $name)),
				'EXTERNALS' =>			$externals,
				'SOURCE' => 			self::SOURCE_FIAS,

				'SYS_CODE' => 			$this->mapETCodeAsFias($item['AOGUID']),
				'PARENT_SYS_CODE' => 	$i ? $this->mapETCodeAsFias($item['PARENTGUID']) : $this->currentRegion
			));
		}
	}

public 	private function generateExportTreePutRussiaStreets($parentGuid)
	{
		if(!strlen($parentGuid))
			return;

		$res = $this->fiasDB->getActualChildren($parentGuid);
		while($item = $res->fetch())
		{
			$itemType = $item['SHORTNAME'];
			if($this->checkIsAllowedStreet($item))
			{
				$externals = array();
				if(strlen($item['POSTALCODE']))
					$externals['ZIP'][] = $item['POSTALCODE'];

				$baseType = $this->sysMaps['FIAS2BASETYPE'][$itemType];
				$name = trim($item['FORMALNAME']).' '.$this->sysMaps['FIASTYPEREPLACE'][$itemType];

				$this->eTreeDBRussia->insert(array(
					'TYPE_CODE' => 			$baseType,
					'FIAS_TYPE' => 			$itemType,
					'NAME' => 				$name,
					'LANGNAMES' => 			array('RU' => array('NAME' => $name)),
					'EXTERNALS' =>			$externals,
					'SOURCE' => 			self::SOURCE_FIAS,

					'SYS_CODE' => 			$this->mapETCodeAsFias($item['AOGUID']),
					'PARENT_SYS_CODE' => 	$this->mapETCodeAsFias($parentGuid),
				));
			}
		}
	}

public 	private function generateExportTreePutRussiaInnerBundle($parentGuid)
	{
		if(!strlen($parentGuid))
			return;

		$res = $this->fiasDB->getActualChildren($parentGuid);
		while($item = $res->fetch())
		{
			$item['PARENTGUID'] = $parentGuid;
			array_push($this->fiasPath, $item);

			if($this->checkIsAllowedCityVillage($item)) // check if this is a village or city or what else we should add to export
			{
				//$this->output('Allowed city\village '.$item['FORMALNAME']);

				$this->generateExportTreePutRussiaFiasPath($item); // ALSO store intermediate locations from current region-to-city(village, etc) being stored
				$this->generateExportTreePutRussiaStreets($item['AOGUID']); // store all streets of current village\city\...
			}

			$this->generateExportTreePutRussiaInnerBundle($item['AOGUID']);

			array_pop($this->fiasPath);
		}
		
	}

	public private function generateExportTreePutRussiaBundle($bundle, $regions, $dl = 0)
	{
		foreach($bundle as $id)
		{
			$node = $this->data['TREES']['MAIN']['NODES'][$id];

			$edges = array();
			if(isset($this->data['TREES']['MAIN']['EDGES'][$id]))
				$edges = $this->data['TREES']['MAIN']['EDGES'][$id];
			
			if(in_array($node['TYPE_CODE'], array('COUNTRY', 'COUNTRY_DISTRICT', 'REGION')))
			{
				if($node['TYPE_CODE'] == 'REGION')
				{
					// get fias code & postal code, if any
					$fNode = $this->fiasDB->getByAOGUID($regions[$node['ID']]['AOGUID']);
					if(strlen($fNode['POSTALCODE']))
						$node['EXT']['ZIP'][] = $fNode['POSTALCODE'];

					$edges = array(); // no way farther
				}

				// set english name for Russia
				if($node['TYPE_CODE'] == 'COUNTRY')
					$node['NAME']['EN']['NAME'] = 'Russian Federation';

				if($node['NAME']['RU']['NAME'] == 'РњРѕСЃРєРІР° Рё РњРѕСЃРєРѕРІСЃРєР°СЏ РѕР±Р»Р°СЃС‚СЊ')
					$node['NAME']['RU']['NAME'] = 'РњРѕСЃРєРѕРІСЃРєР°СЏ РѕР±Р»Р°СЃС‚СЊ';

				if($node['NAME']['RU']['NAME'] == 'РЎР°РЅРєС‚-РџРµС‚РµСЂР±СѓСЂРі Рё Р›РµРЅРёРЅРіСЂР°РґСЃРєР°СЏ РѕР±Р»Р°СЃС‚СЊ')
					$node['NAME']['RU']['NAME'] = 'Р›РµРЅРёРЅРіСЂР°РґСЃРєР°СЏ РѕР±Р»Р°СЃС‚СЊ';

				$this->eTreeDBRussia->insert(array(
					'TYPE_CODE' => 		$node['TYPE_CODE'],
					'NAME' => 			$node['NAME']['RU']['NAME'],
					'LANGNAMES' => 		$node['NAME'],
					'EXTERNALS' =>		$node['EXT'],
					'SOURCE' => 		self::SOURCE_YANDEX,

					'SYS_CODE' => $this->mapETCodeAsYandex($node['ID']),
					'PARENT_SYS_CODE' => strlen($node['PARENT_ID']) && $dl > 0 ? $this->mapETCodeAsYandex($node['PARENT_ID']) : ''
				));

				if(!empty($edges))
					$this->generateExportTreePutRussiaBundle($edges, $regions, $dl+1);
			}
		}
	}

	#########################

public 	private function generateExportTreeWorld()
	{
		$this->eTreeDB->dropCodeIndex();
		$this->eTreeDB->restoreExportOffset();

		$this->eTreeDB->switchIndexes(false);

		$csv = new Import\CSVReader();
		$csv->loadFile($_SERVER['DOCUMENT_ROOT'].'/locations_data/'.self::WORLD_SOURCE);

		$countries = array();

		while($item = $csv->Fetch())
		{
			$item = explode(',', $item[0]);

			if(!isset($item[1])) // its a language marker
				continue;

			// exclude the following countries, kz we got an extended file for them
			if(in_array($item[2], array('USA', 'Kazakhstan', 'Ukraine', 'Byelorussia', 'Russian Federation', 'Azerbaijan', 'Estonia', 'Georgia', 'Latvia', 'Lithuania', 'Moldavia', 'Turkmenistan', 'Armenia', 'Tadjikistan', 'Uzbekistan')))
				continue;

			$id = implode(':', $item);

			$countries[] = $item[2].' - '.$item[4];

			$this->eTreeDB->insert(array(
				'TYPE_CODE' => 		'COUNTRY',
				'NAME' => 			$item[4],
				'LANGNAMES' => 		serialize(array(
					'RU' => array('NAME' => $item[4]),
					'EN' => array('NAME' => $item[2])
				)),
				'EXTERNALS' =>		'',
				'SOURCE' => 		self::SOURCE_LEGACY,

				'SYS_CODE' => $this->mapETCodeAsLegacy($id),
				'PARENT_SYS_CODE' => ''
			));
		}

		$this->eTreeDB->doneInsert();
		$this->eTreeDB->switchIndexes(true);
	}

	#########################

public 	private function generateExportTreeLegacy($source)
	{
		$this->eTreeDB->dropCodeIndex();
		$this->eTreeDB->restoreExportOffset();

		$this->eTreeDB->switchIndexes(false);

		$csv = new Import\CSVReader();
		$csv->loadFile($_SERVER['DOCUMENT_ROOT'].'/locations_data/'.$source);
		
		$lastOnes = array();

		while($item = $csv->Fetch())
		{
			$item = explode(',', $item[0]);

			if(!isset($item[1])) // its a language marker
				continue;

			if($item[0] == 'R')
			{
				$item[2] = preg_replace('# obl$#', ' region', $item[2]);
				$item[4] = preg_replace('# РѕР±Р»$#', ' РѕР±Р»Р°СЃС‚СЊ', $item[4]);
			}

			$parentId = '';

			if($item[0] == 'S')
				$type = 'COUNTRY';
			if($item[0] == 'R')
				$type = 'REGION';
			if($item[0] == 'T')
				$type = 'CITY';

			$id = implode(':', $item);

			if($type == 'REGION')
				$parentId = $lastOnes['COUNTRY'];
			elseif($type == 'CITY')
				$parentId = $lastOnes['PARENT'];
			else
				$parentId = '';

			if($type != 'CITY')
			{
				$lastOnes[$type] = $id;
				$lastOnes['PARENT'] = $id;
			}

			$this->eTreeDB->insert(array(
				'TYPE_CODE' => 		$type,
				'NAME' => 			$item[4],
				'LANGNAMES' => 		serialize(array(
					'RU' => array('NAME' => $item[4]),
					'EN' => array('NAME' => $item[2])
				)),
				'EXTERNALS' =>		'',
				'SOURCE' => 		self::SOURCE_LEGACY,

				'SYS_CODE' => $this->mapETCodeAsLegacy($id),
				'PARENT_SYS_CODE' => strlen($parentId) ? $this->mapETCodeAsLegacy($parentId) : ''
			));
		}

		$this->eTreeDB->doneInsert();
		$this->eTreeDB->switchIndexes(true);
	}

	#########################

	public private function generateExportTreeUSA()
	{
		$this->eTreeDB->dropCodeIndex();
		$this->eTreeDB->restoreExportOffset();

		$this->eTreeDB->switchIndexes(false);

		$csv = new Import\CSVReader();
		$csv->loadFile($_SERVER['DOCUMENT_ROOT'].'/locations_data/'.self::USA_SOURCE);
		
		$lastOnes = array();

		while($item = $csv->Fetch())
		{
			$item = explode(',', $item[0]);

			if(!isset($item[1])) // its a language marker
				continue;

			$parentId = '';

			if($item[0] == 'S')
				$type = 'COUNTRY';
			if($item[0] == 'R')
				$type = 'REGION';
			if($item[0] == 'T')
				$type = 'CITY';

			$id = implode(':', $item);

			if($type == 'REGION')
				$parentId = $lastOnes['COUNTRY'];
			elseif($type == 'CITY')
				$parentId = $lastOnes['PARENT'];
			else
				$parentId = '';

			if($type != 'CITY')
			{
				$lastOnes[$type] = $id;
				$lastOnes['PARENT'] = $id;
			}

			if($item['2'] == 'USA')
				$item['4'] = 'РЎРЁРђ';

			$this->eTreeDB->insert(array(
				'TYPE_CODE' => 		$type,
				'NAME' => 			$item[4],
				'LANGNAMES' => 		serialize(array(
					'RU' => array('NAME' => $item[4]),
					'EN' => array('NAME' => $item[2])
				)),
				'EXTERNALS' =>		'',
				'SOURCE' => 		self::SOURCE_LEGACY,

				'SYS_CODE' => $this->mapETCodeAsLegacy($id),
				'PARENT_SYS_CODE' => strlen($parentId) ? $this->mapETCodeAsLegacy($parentId) : ''
			));
		}

		$this->eTreeDB->doneInsert();
		$this->eTreeDB->switchIndexes(true);
	}

	#########################

public static 	private function generateExportTreeBelorussia()
	{
		$this->eTreeDB->dropCodeIndex();
		$this->eTreeDB->restoreExportOffset();

		$this->eTreeDB->switchIndexes(false);

		$this->generateExportTreePutBelorussiaBundle(array(self::BELORUSSIA_YANDEX_CODE));
		
		$this->eTreeDB->doneInsert();
		$this->eTreeDB->switchIndexes(true);
	}

public 	private function generateExportTreePutBelorussiaBundle($bundle)
	{
		foreach($bundle as $id)
		{
			$node = $this->data['TREES']['MAIN']['NODES'][$id];

			$edges = array();
			if(isset($this->data['TREES']['MAIN']['EDGES'][$id]))
				$edges = $this->data['TREES']['MAIN']['EDGES'][$id];

			// attach to belorussia its en-name
			if($node['TYPE_CODE'] == 'COUNTRY')
				$node['NAME']['EN']['NAME'] = 'Belarus';

			// these two types are not allowed currently, because we do not have the corresponding types in fias
			if($node['TYPE_CODE'] == 'METRO_STATION' || $node['TYPE_CODE'] == 'CITY_DISTRICT')
				continue;

			$this->eTreeDB->insert(array(
				'TYPE_CODE' => 		$node['TYPE_CODE'],
				'NAME' => 			$node['NAME']['RU']['NAME'],
				'LANGNAMES' => 		serialize($node['NAME']),
				'EXTERNALS' =>		serialize($node['EXT']),
				'SOURCE' => 		self::SOURCE_YANDEX,

				'SYS_CODE' => $this->mapETCodeAsYandex($node['ID']),
				'PARENT_SYS_CODE' => strlen($node['PARENT_ID']) ? $this->mapETCodeAsYandex($node['PARENT_ID']) : ''
			));

			if(!empty($edges))
				$this->generateExportTreePutBelorussiaBundle($edges);
		}
	}

	#########################

public 	private function generateExportTreeUkrain()
	{
		$this->eTreeDB->dropCodeIndex();
		$this->eTreeDB->restoreExportOffset();

		$cd2r = $this->getDataFromCSV('ukrain_kazakhstan', 'ukrain_district2region');

		$tree = array();

		$tree['NODES'][self::UKRAIN_YANDEX_CODE] = array(
			'NAME' => array(
				'RU' => array('NAME' => 'РЈРєСЂР°РёРЅР°'),
				'UA' => array('NAME' => 'РЈРєСЂР°С—РЅР°'),
				'EN' => array('NAME' => 'Ukraine')
			),
			'TYPE_CODE' => 'COUNTRY',
			'SOURCE' => self::SOURCE_YANDEX,
			'ID' => self::UKRAIN_YANDEX_CODE,
			'EXT' => array(
				'YAMARKET' => array(
					self::UKRAIN_YANDEX_CODE
				)
			)
		);

		foreach($cd2r as $line)
		{
			// add country district
			if(!isset($tree['NODES'][$line['CDID']]))
			{
				$tree['NODES'][$line['CDID']] = array(
					'NAME' => $line['CDNAME'],
					'TYPE_CODE' => 'COUNTRY_DISTRICT',
					'SOURCE' => self::SOURCE_YANDEX,
					'ID' => $line['CDID'],
					'PARENT_ID' => self::UKRAIN_YANDEX_CODE,
					'EXT' => array(
						'YAMARKET' => array(
							$line['CDID'] // country district id in file
						),
					)
				);
			}

			$tree['EDGES'][self::UKRAIN_YANDEX_CODE][$line['CDID']] = true;

			$regionId = 'r'.md5($line['RNAME']['UA']['NAME']);

			if($line['RNAME']['UA']['NAME'] == 'РЎРµРІР°СЃС‚РѕРїРѕР»СЊ, РњС–СЃС‚Рѕ' || $line['RNAME']['UA']['NAME'] == 'РђРІС‚РѕРЅРѕРјРЅР° Р РµСЃРїСѓР±Р»С–РєР° РљСЂРёРј')
				$source = self::SOURCE_UKRAIN;
			else
				$source = self::SOURCE_YANDEX;

			if($line['RNAME']['UA']['NAME'] == 'РЎРµРІР°СЃС‚РѕРїРѕР»СЊ, РњС–СЃС‚Рѕ')
			{
				$typeCode = 'SUBREGION';
			}
			else
			{
				$typeCode = 'REGION';
			}

			// add region
			if(!isset($tree['NODES'][$regionId]))
			{
				$tree['NODES'][$regionId] = array(
					'NAME' => $line['RNAME'],
					'TYPE_CODE' => $typeCode,
					'SOURCE' => $source,
					'ID' => $regionId,
					'PARENT_ID' => $line['CDID'],
				);

				if($source == self::SOURCE_YANDEX)
					$tree['NODES'][$regionId]['EXT']['YAMARKET'][] = $line['RID'];
			}

			$tree['EDGES'][$line['CDID']][$regionId] = true;
		}

		$res = $this->getDataFromCSV('ukrain_kazakhstan', 'ukrain');

		foreach($res as $line)
		{
			if($line['REGION'] == 'РђРІС‚РѕРЅРѕРјРЅР° СЂРµСЃРїСѓР±Р»С–РєР° РљСЂРёРј')
				$line['REGION'] = 'РђРІС‚РѕРЅРѕРјРЅР° Р РµСЃРїСѓР±Р»С–РєР° РљСЂРёРј';

			$line['REGION'] = $this->mb_str_replace('РњС–СЃС‚Рѕ', 'РјС–СЃС‚Рѕ', $line['REGION']);
			$line['SUBREGION'] = $this->mb_str_replace('РњС–СЃС‚Рѕ', 'РјС–СЃС‚Рѕ', $line['SUBREGION']);
			$line['CITY'] = $this->mb_str_replace('РњС–СЃС‚Рѕ', 'РјС–СЃС‚Рѕ', $line['CITY']);

			$regionId = 'r'.md5($line['REGION']);
			$subRegionId = 'sr'.md5($line['SUBREGION']);
			$cityId = 'c'.md5($line['CITY']);

			if($line['REGION'] != 'РЎРµРІР°СЃС‚РѕРїРѕР»СЊ, РњС–СЃС‚Рѕ')
			{
				if(!isset($tree['NODES'][$subRegionId]))
				{
					$tree['NODES'][$subRegionId] = array(
						'NAME' => array('UA' => array('NAME' => $line['SUBREGION'])),
						'TYPE_CODE' => 'SUBREGION',
						'SOURCE' => self::SOURCE_UKRAIN,
						'ID' => $subRegionId,
						'PARENT_ID' => $regionId,
						'EXT' => array()
					);
					if(!isset($tree['EDGES'][$regionId][$subRegionId]))
						$tree['EDGES'][$regionId][$subRegionId] = true;
				}
			}

			if(!isset($tree['NODES'][$cityId]))
			{
				$tree['NODES'][$cityId] = array(
					'NAME' => array('UA' => array('NAME' => $line['CITY'])),
					'TYPE_CODE' => 'CITY',
					'SOURCE' => self::SOURCE_UKRAIN,
					'ID' => $cityId,
					'PARENT_ID' => $subRegionId,
					'EXT' => array()
				);
				if(!isset($tree['EDGES'][$subRegionId][$cityId]))
					$tree['EDGES'][$subRegionId][$cityId] = true;
			}
		}

		foreach($tree['EDGES'] as $k => $edges)
			$tree['EDGES'][$k] = array_keys($edges);

		$this->data['TREES']['UKRAIN'] = $tree;

		$this->generateExportTreePutUkrainBundle(array(self::UKRAIN_YANDEX_CODE));

		unset($this->data['TREES']['UKRAIN']);

		$this->eTreeDB->doneInsert();
	}

public 	private function generateExportTreePutUkrainBundle($bundle)
	{
		foreach($bundle as $id)
		{
			$node = $this->data['TREES']['UKRAIN']['NODES'][$id];

			$edges = array();
			if(isset($this->data['TREES']['UKRAIN']['EDGES'][$id]))
				$edges = $this->data['TREES']['UKRAIN']['EDGES'][$id];

			$this->eTreeDB->insert(array(
				'TYPE_CODE' => 		$node['TYPE_CODE'],
				'NAME' => 			$node['NAME']['UA']['NAME'],
				'LANGNAMES' => 		serialize($node['NAME']),
				'EXTERNALS' =>		serialize($node['EXT']),
				'SOURCE' => 		$node['SOURCE'],

				'SYS_CODE' => $this->mapETCodeAsUkrainian($node['ID']),
				'PARENT_SYS_CODE' => strlen($node['PARENT_ID']) ? $this->mapETCodeAsUkrainian($node['PARENT_ID']) : ''
			));

			if(!empty($edges))
				$this->generateExportTreePutUkrainBundle($edges);
		}
	}













public static 	private function mapETCodeAsYandex($code)
	{
		return 'Y_'.$code;
	}

public 	private function mapETCodeAsFias($code)
	{
		return 'F_'.$code;
	}

public 	private function mapETCodeAsUkrainian($name)
	{
		return 'U_'.md5($name);
	}

public 	private function mapETCodeAsLegacy($name)
	{
		return 'L_'.md5($name);
	}

	#######################################################
	### ABOUT EXPORT TREE GENERATION
	#######################################################

public 	private function mapETCodeBySource($value, $source)
	{
		if($source == self::SOURCE_YANDEX)
			return 'Y_'.$value;
		if($source == self::SOURCE_FIAS)
			return 'F_'.$value;
		if($source == self::SOURCE_UKRAIN)
			return 'U_'.md5($name);
		if($source == self::SOURCE_KAZAKHSTAN)
			return 'K_'.md5($name);
	}

	ppublic rivate function startExportFromScratch()
	{
		$this->cleanTemporalData(self::TMP_DATA_RUS_EXPORT_INDEX);
		$this->cleanTemporalData(self::TMP_DATA_RUS_GLOBAL_INDEX);

		$this->cleanPoolDir('assets');
	}

public 	private function generateExportTreeRussiaRoot()
	{
		$this->restoreTDRusExpIndex();

		if(!empty($this->alreadyDumped))
			return;

		$regions = $this->readFiasRootMapV2();
		$this->generateExportTreePutRussiaBundleOld(array(self::RUSSIA_YANDEX_CODE), $regions, 0);

		$this->storeTemporalData(self::TMP_DATA_RUS_EXPORT_INDEX, $this->alreadyDumped);
	}

public 	private function restoreTDRusExpIndex()
	{
		if(!empty($this->alreadyDumped))
			return;

		$this->alreadyDumped = $this->getStoredTemporalData(self::TMP_DATA_RUS_EXPORT_INDEX);
	}

public 	private function storeTDGlobalExpIndex()
	{
		$this->storeTemporalData(self::TMP_DATA_RUS_GLOBAL_INDEX, array('I' => $this->exportOffset));
	}

public 	private function restoreTDGlobalExpIndex()
	{
		if($this->exportOffset == 0)
		{
			$data = $this->getStoredTemporalData(self::TMP_DATA_RUS_GLOBAL_INDEX);
			$this->exportOffset = intval($data['I']);
		}
	}

public 	private function generateExportTreePutRussiaBundleOld($bundle, $regions, $dl = 0)
	{
		foreach($bundle as $id)
		{
			$node = $this->data['TREES']['MAIN']['NODES'][$id];

			$edges = array();
			if(isset($this->data['TREES']['MAIN']['EDGES'][$id]))
				$edges = $this->data['TREES']['MAIN']['EDGES'][$id];
			
			if(in_array($node['TYPE_CODE'], array('COUNTRY', 'COUNTRY_DISTRICT', 'REGION')))
			{
				if($node['TYPE_CODE'] == 'REGION')
				{
					// get fias code & postal code, if any
					//$node['EXT']['FIAS'][] = $regions[$node['ID']]['AOGUID'];

					$fNode = $this->fiasGetByAOGUID($regions[$node['ID']]['AOGUID']);
					if(strlen($fNode['POSTALCODE']))
						$node['EXT']['ZIP'][] = $fNode['POSTALCODE'];

					$edges = array(); // no way farther
				}

				$this->addItemToExportTree(array(
					'ID' => 			$this->mapETCodeAsYandex($node['ID']),
					'PARENT_ID' => 		strlen($node['PARENT_ID']) && $dl > 0 ? $this->mapETCodeAsYandex($node['PARENT_ID']) : '',
					'TYPE' => 			$node['TYPE_CODE'],
					'NAME' => 			$node['NAME'],
					'EXTERNALS' =>		$node['EXT'],
					'SOURCE' => 		self::SOURCE_YANDEX,
				));

				if(!empty($edges))
					$this->generateExportTreePutRussiaBundle($edges, $regions, $dl+1);
			}
		}
	}

	private $fiasPath = array();
	private $alreadyStoredPathItems = array();
	private $fias2yandexMap = array();

	private $alreadyDumped = array();

	private $currentRegion = false;

public 	private function generateExportTreeRussiaInner()
	{
		$links = $this->getFias2YamarketRootLinks(false, true);

		//$this->output($links);

		$i = -1;
		foreach($links as $yRId => $regions)
		{
			$i++;

			$this->restoreTDGlobalExpIndex();
			$this->alreadyDumped = array();
			$this->restoreTDRusExpIndex();

			$this->fias2yandexMap = array();
			$fias2yandex = $this->getDataFromCSV('fias_yamarket_links', 'region_'.$yRId);
			foreach($fias2yandex as $map)
			{
				$this->fias2yandexMap[$map['AOGUID']] = $map;
			}
			unset($fias2yandex);

			$this->currentRegion = $this->mapETCodeBySource($yRId, self::SOURCE_YANDEX);

			foreach($regions as $regionId)
			{
				$this->fiasPath = array();

				$this->output('CCCurrent REGION is '.$this->currentRegion);
				//$this->output($this->alreadyDumped);

				$this->generateExportTreeRussiaInnerBundle($regionId);
			}

			$this->storeTDGlobalExpIndex();

			//break; // tmp
			//if($i == 1)break;
		}
	}

public 	private function generateExportTreeRussiaInnerBundle($parentGuid)
	{
		if(!strlen($parentGuid))
			return;

		$res = $this->getDataFromCSV('fias_tree', $parentGuid);
		foreach($res as $item)
		{
			if($item['LIVESTATUS'] != '1' || $item['ACTSTATUS'] != '1')
				continue;

			$item['PARENT_ID'] = $parentGuid;
			array_push($this->fiasPath, $item);

			if($this->checkIsAllowedCityVillage($item['ID'], $item['TYPE']))
			{
				// ADD!!!
				$this->storeCurrentFiasPath2();
				$this->generateExportTreeRussiaInnerStreets($item['ID']);
			}

			$this->generateExportTreeRussiaInnerBundle($item['ID']);

			array_pop($this->fiasPath);
		}
		
	}


public 	private function addItemToExportTree($item)
	{
		$this->exportOffset++;

		$this->output('ADD: '.$item['NAME']['RU']['NAME'].' '.$item['TYPE']);
		//$this->output($item);

		$cat = $this->sysMaps['BASETYPE2GROUP'][$item['TYPE']];
		$fName = $this->typeGroups[$cat]['FILE_NAME_TEMPLATE'];

		$header = $this->typeGroups[$cat];
		$this->alreadyDumped[$item['ID']] = $this->exportOffset;

		$parentCode = strlen($item['PARENT_ID']) ? $this->addLeadingZero($this->alreadyDumped[$item['PARENT_ID']], self::CODE_LENGTH) : '';

		$data = array(
			'CODE' => 			$this->addLeadingZero($this->exportOffset, self::CODE_LENGTH),
			'PARENT_CODE' => 	$parentCode,
			'TYPE_CODE' => 		$item['TYPE']
		);

		foreach($item['NAME'] as $lid => $values)
		{
			foreach($values as $i => $val)
				$data['NAME.'.$lid.'.'.$i] = $val;
		}

		$data['EXT.YAMARKET.0'] = '';
		$data['EXT.ZIP.0'] = '';
		if(!empty($item['EXTERNALS']))
		{
			foreach($item['EXTERNALS'] as $type => $values)
			{
				foreach($values as $i => $val)
					$data['EXT.'.$type.'.'.$i] = $val;
			}
		}

		$parentGroupId = strlen($item['PARENT_GROUP_ID']) ? $this->addLeadingZero($this->alreadyDumped[$item['PARENT_GROUP_ID']], self::CODE_LENGTH) : '';

		$data['LONGITUDE'] = '';
		$data['LATITUDE'] = '';
		//$this->output('PGID is set to '.$parentGroupId);

		$fName = str_replace(array(
			'%BASE_PARENT_ITEM_CODE%',
			'%CODE%',
			'.csv'
		), array(
			$parentGroupId,
			ToLower($cat),
			''
		), $fName);

		$this->output('PUT:');
		$this->output($data);

		$this->output('TO:');
		$this->output($fName);

		$this->putToFile2(
			$data,
			'assets',
			$fName,
			true
		);
	}

	#######################################################
	### ABOUT MAIN TREE
	#######################################################

	// map yandex cities to fias cities
static 	public function buildMainTree()
	{
		$this->queue = false;
		$this->data['TREES']['MAIN'] = array();
		$this->data['MAPS'] = array();
		$this->data['INDEXES'] = array();

		$done = false;
		while(!$done)
			$done = $this->buildMainTreeNext();

		// build name-path index for russia
		$this->buildRussiaPathIndex($this->data['TREES']['MAIN']['EDGES'][self::RUSSIA_YANDEX_CODE], array());
	}

public static 	private function buildMainTreeNext()
	{
		$next = $this->queueShift();
		$bundle = $this->getBundleFromFile($next);

		if(!empty($bundle))
		{
			foreach($bundle as $item)
			{
				$this->data['TREES']['MAIN']['NODES'][$item['ID']] = $item;
				$parent = isset($item['PARENT_ID']) ? $item['PARENT_ID'] : 'ROOT';
				$this->data['TREES']['MAIN']['EDGES'][$parent][] = $item['ID'];

				if($item['CHILDREN_COUNT'] > 0)
					$this->queue[] = $item['ID'];
			}
		}

		return empty($this->queue);
	}

	#######################################################
	### ABOUT FIAS PROCESS
	#######################################################

	// temporal function
public 	private function checkFiasMaps()
	{
		$links = $res = $this->getDataFromCSV('fias_yamarket_links', 'rootv2');
		$uTotal = 0;
		foreach($links as $reg)
		{
			if($reg['CITIES_MAPPED'] != '1')
			{
				//$this->output($reg);
				$rMap = $this->getDataFromCSV('fias_yamarket_links', 'region_'.$reg['YAMARKET']);
				
				$this->output('============================ For: '.$reg['YAMARKET_NAME'].' '.$reg['YAMARKET']);

				$unmapped = 0;
				foreach($rMap as $map)
				{
					if(!strlen($map['AOGUID']))
					{
						$unmapped++;

						$this->output($map);

						$res = DB\FiasTable::getList(array('filter' => array(
							'FORMALNAME' => $map['NAME'],
							'!SHORTNAME' => array('СѓР»', 'РїРµСЂ'),
							'ACTSTATUS' => '1',
							'LIVESTATUS' => '1'
						)));
						while($item = $res->fetch())
						{
							$this->output($item);
						}
					}
				}

				$this->output('Unmapped: '.$unmapped);
				$uTotal += $unmapped;
			}
		}
		$this->output('TOTAL: '.$uTotal);
	}

public 	private function showChildren($pId)
	{
		$res = DB\FiasTable::getList(array('filter' => array(
			'PARENTGUID' => $pId,
			'ACTSTATUS' => '1',
			'LIVESTATUS' => '1'
		)));
		while($item = $res->fetch())
		{
			$this->output($item);
		}
	}

public 	private function findPathes()
	{
		$pathes = array(
			'aea7bac4-f9b4-4160-95f2-3d667b4d3f92', // РѕС‚СЂР°РґРЅРѕРµ, РєР°Р»РёРЅРёРіСЂР°РґСЃРєР°СЏ РѕР±Р»Р°СЃС‚СЊ, 10857
			'bda061ac-cbd0-4db8-8d18-69db43e76c2d', // РѕС‚СЂР°РґРЅРѕРµ, РєР°Р»РёРЅРёРіСЂР°РґСЃРєР°СЏ РѕР±Р»Р°СЃС‚СЊ, 10857
			'436b841d-a44f-431e-b1ec-7d76456d4a11', // РѕС‚СЂР°РґРЅРѕРµ, РєР°Р»РёРЅРёРіСЂР°РґСЃРєР°СЏ РѕР±Р»Р°СЃС‚СЊ, 10857
			'807943cc-31c0-4a86-a3fe-46d9262101e9', // РґР°РіРѕРјС‹СЃ, РєСЂР°СЃРЅРѕРґР°СЂСЃРєРёР№ РєСЂР°Р№, 10995
		);

		foreach($pathes as $id)
		{
			$this->fiasFindPath($id);
		}
	}

public 	private function fiasFindPath($aoguid)
	{
		$pId = $aoguid;

		while($pId && $res = DB\FiasTable::getList(array('filter' => array(
			'AOGUID' => $pId,
			'ACTSTATUS' => '1',
			'LIVESTATUS' => '1'
		)))->fetch())
		{
			$this->output($res);
			if($res['PARENTGUID'])
				$pId = $res['PARENTGUID'];
			else
				$pId = false;
		}
	}

	prpublic ivate function fiasFind2()
	{
		$res = DB\FiasTable::getList(array('filter' => array(
			'FORMALNAME' => array(


				),
			//'ACTSTATUS' => '1',
			//'LIVESTATUS' => '1'
		)));
		while($item = $res->fetch())
		{
			$this->output($item);
		}
	}

public 	private function fiasFind()
	{
		$findInFias = array(
			//РЎРЎ Рђ Р Р Р  Р“Р“Р“ Р’Р’Р’ РџРџРџ РЈРЈРЈРЈ Р­Р­Р­Р­ Р¦Р¦Р¦

			'11 0 007 000 000 009 0000 0000 000', // РєР°Р¶С‹Рј, РєРѕРјРё, 10939
			'35 0 003 000 000 001 0000 0000 000', // РёРј Р±Р°Р±СѓС€РєРёРЅР°, РІРѕР»РѕРі. РѕР±Р», 10853
			'05 0 017 000 000 019 0000 0000 000', // Р°С‡Рё-СЃСѓ, РґР°РіРµСЃС‚Р°РЅ, 11010
			'20 0 028 000 000 001 0000 0000 000', // РёС‚СѓРј-РєР°Р»Рё, С‡РµС‡РЅСЏ, 11024
			'16 0 022 000 000 023 0000 0000 000', // РєСѓР»Р°РЅРіР°, С‚Р°С‚Р°СЂСЃС‚Р°РЅ, 11119
			'59 0 020 000 000 003 0000 0000 000', // РіР°РјРѕРІРѕ, РїРµСЂРјСЃРєРёР№ РєСЂР°Р№, 11108
			'56 0 000 000 000 002 0000 0000 000', // Р·Р°С‚Рѕ РєРѕРјР°СЂРѕРІСЃРєРёР№, РѕСЂРµРЅР±СѓСЂРіСЃРєР°СЏ РѕР±Р»Р°СЃС‚СЊ, 11084
			'86 0 003 000 000 031 0000 0000 000', // СѓР·СЋРј-СЋСЂРіР°РЅСЃРєР°СЏ РіРєСЃ, С…Р°РЅС‚С‹-РјР°РЅСЃРёР№СЃРєРёРёР№ Р°Рѕ, 11193
			'70 0 007 000 000 000 0000 0047 000', // РёРіРѕР», С‚РѕРјСЃРєР°СЏ РѕР±Р»Р°СЃС‚СЊ, 11353
			'27 0 009 000 000 001 0000 0000 000', // РёРј РїРѕР»РёРЅС‹ РѕСЃРёРїРµРЅРєРѕ, С…Р°Р±Р°СЂРѕРІСЃРєР№Рё РєСЂР°Р№, 11457
			'28 0 014 000 000 045 0000 0000 000', // СЃРІРѕР±РѕРґРЅС‹Р№-21, Р°РјСѓСЂСЃРєР°СЏ РѕР±Р»Р°СЃС‚СЊ, 11375
			'14 0 010 000 000 001 0000 0000 000', // Р±Р°РіР°С‚Р°Р№, СЃР°С…Р° СЏРєСѓС‚РёСЏ, 11443
			
		);

		$res = DB\FiasTable::getList(array('filter' => array(
			'CODE' => array_values($findInFias),
			'ACTSTATUS' => '1',
			'LIVESTATUS' => '1'
		)));
		while($item = $res->fetch())
		{
			$this->output($item);
		}
	}

public 	private function fiasGetByAOGUID($fiasId)
	{
		return DB\FiasTable::getList(array('filter' => array(
			'=AOGUID' => $fiasId,
		)))->fetch();
	}

	/*
public 	private function parseFiasCode($code)
	{
		//РЎРЎ(0) Рђ(1) Р Р Р (2) Р“Р“Р“(3) Р’Р’Р’(4) РџРџРџ(5) РЈРЈРЈРЈ(6) Р­Р­Р­Р­(7) Р¦Р¦Р¦(8)
		$code = explode(' ', $code);
		return array(
			'REGIONCODE' => $code[0],
			'AREACODE' => $code[2],
			'AUTOCODE' => $code[1],
			'CITYCODE' => $code[3],
			'CTARCODE' => $code[4],
			'PLACECODE' => $code[5],
			'STREETCODE' => $code[6],
			'EXTRCODE' => $code[7],
			'SEXTCODE' => $code[8]
		);
	}

	public private function checkFitItemByCode($code, $item)
	{
		$code = $this->parseFiasCode($code);

		return (
			$item['REGIONCODE'] == $code['REGIONCODE']
			&&
			$item['AREACODE'] == $code['AREACODE']
			&&
			$item['AUTOCODE'] == $code['AUTOCODE']
			&&
			$item['CITYCODE'] == $code['CITYCODE']
			&&
			$item['CTARCODE'] == $code['CTARCODE']
			&&
			$item['PLACECODE'] == $code['PLACECODE']
			&&
			$item['STREETCODE'] == $code['STREETCODE']
			&&
			$item['EXTRCODE'] == $code['EXTRCODE']
			&&
			$item['SEXTCODE'] == $code['SEXTCODE']
		);
	}
	*/

public 	private function mapFiasCities()
	{
		//$this->output($this->data['MAPS']['REGIONS']);
		$links = $this->getFias2YamarketRootLinks();

		//$this->output($links);

		$typesToSearch = array('CITY', 'VILLAGE');

		// for each region and city we choose the correpongind ones from fias, saving routes
		foreach($links as $id => $fiasSource)
		{
			$region = $this->data['MAPS']['REGIONS'][$id];

			$this->output('FOR region: '.$region);
			$this->output($region);

			$toBeFound = array(); // among all nodes get only ones with following types
			$this->getMainTreeNodesOfType(array($id), $typesToSearch, $toBeFound);

			//$this->output(count($toBeFound));

			if(!empty($toBeFound)) // search them in fias prepared tree
			{
				foreach($fiasSource as $fiasId)
				{
					$this->output('In: '.$fiasId);
					$this->walkFiasTreeAndKeepFollowing($fiasId, $toBeFound, $typesToSearch);
				}
			}

			$this->cleanUpFile('fias_yamarket_links', 'region_'.$region['ID']);
			foreach($toBeFound as $node)
			{
				$exactId = '';
				$exactName = '';
				$exactType = '';
				$exactCode = '';

				if(!empty($node['MATCH']))
				{
					$match = array_shift($node['MATCH']);

					$exactId = $match['ID'];
					$exactName = $match['NAME'];
					$exactType = $match['TYPE'];

					$item = $this->fiasGetByAOGUID($exactId);
					if($item)
					{
						$exactCode = $item['CODE'];
					}
					else
						$this->output('no record in fias for: '.$exactId);
				}

				$data = array(
					'HZ' => 'libre',
					'ID' => $node['ID'],
					'NAME' => $node['NAME']['RU']['NAME'],
					'AOGUID' => $exactId,
					'FNAME' => $exactName,
					'FTYPE' => $exactType,
					'CODE' => $exactCode
				);

				for($i = 0; $i < 3; $i++)
				{
					$data['VAR_AOGUID_'.$i] = '';
					$data['VAR_NAME_'.$i] = '';
					$data['VAR_TYPE_'.$i] = '';
				}

				$i = 0;
				if(!empty($node['MATCH']))
				{
					foreach($node['MATCH'] as $item)
					{
						$data['VAR_AOGUID_'.$i] = $item['ID'];
						$data['VAR_NAME_'.$i] = $item['NAME'];
						$data['VAR_TYPE_'.$i] = $item['TYPE'];

						$i++;
					}
				}

				if(!empty($node['POSSIBLE']))
				{
					foreach($node['POSSIBLE'] as $item)
					{
						$data['VAR_AOGUID_'.$i] = $item['ID'];
						$data['VAR_NAME_'.$i] = $item['NAME'];
						$data['VAR_TYPE_'.$i] = $item['TYPE'];

						$i++;
					}
				}

				$this->putToFile2(
					$data,
					'fias_yamarket_links',
					'region_'.$region['ID'],
					true
				);
			}

			//break;//tmp
		}
	}

public static 	private function checkAllowedState($node)
	{
		return $node['ACTSTATUS'] == '1' && $node['LIVESTATUS'] == '1';
	}

public 	private function mb_str_replace($needle, $replace_text, $haystack)
	{
		return implode($replace_text, mb_split($needle, $haystack));
	}

public 	private function checkNamesEqual($one, $two)
	{
		// try trim-lc
		$one = $this->makeNameIndexKey($one);
		$two = $this->makeNameIndexKey($two);

		if($one == $two)
			return true;

		// try С‘ => e

		$one = $this->mb_str_replace('С‘', 'Рµ', $one);
		$two = $this->mb_str_replace('С‘', 'Рµ', $two);

		if($one == $two)
			return true;

		// try Р№ => Рё

		$one = $this->mb_str_replace('Р№', 'Рё', $one);
		$two = $this->mb_str_replace('Р№', 'Рё', $two);

		if($one == $two)
			return true;

		// there could be also multiple spaces between

		//if(!($keptNode['NAME_I'] == $name || strpos($keptNode['NAME_I'], $name) !== false || strpos($name, $keptNode['NAME_I']) !== false))
		//	continue;

		return false;
	}

public 	private function checkNamesAlmostEqual($one, $two)
	{
		$one = $this->makeNameIndexKey($one);
		$two = $this->makeNameIndexKey($two);

		if(strpos($one, $two) !== false || strpos($two, $one) !== false)
			return true;

		$one = $this->mb_str_replace('С‘', 'Рµ', $one);
		$two = $this->mb_str_replace('С‘', 'Рµ', $two);

		if(strpos($one, $two) !== false || strpos($two, $one) !== false)
			return true;

		$one = $this->mb_str_replace('Р№', 'Рё', $one);
		$two = $this->mb_str_replace('Р№', 'Рё', $two);

		if(strpos($one, $two) !== false || strpos($two, $one) !== false)
			return true;


		$one = preg_replace('#\s+-\s+#', '-', $one);
		$two = preg_replace('#\s+-\s+#', '-', $two);

		if(strpos($one, $two) !== false || strpos($two, $one) !== false)
			return true;

		return false;
	}

public 	private function walkFiasTreeAndKeepFollowing($node, &$toBeFound, $typesToSearch)
	{
		$bundle = $this->getDataFromCSV('fias_tree', $node);

		if(is_array($bundle) && !empty($bundle))
		{
			//$this->output($bundle);
			foreach($bundle as $node)
			{
				$name = $this->makeNameIndexKey($node['NAME']);

				//$this->output($node);

				// check if we need this
				if(strlen($node['TYPE']) && isset($this->sysMaps['FIAS2BASETYPE'][$node['TYPE']]))
				{
					$type = $this->sysMaps['FIAS2BASETYPE'][$node['TYPE']];
					
					if(in_array($type, $typesToSearch)) // type fits
					{
						// check if name fits too
						foreach($toBeFound as &$keptNode)
						{
							if($keptNode['TYPE_CODE'] != $type)
								continue;

							if(!$this->checkAllowedState($node))
								continue;

							if($this->checkNamesEqual($name, $keptNode['NAME']['RU']['NAME']))
							{
								$keptNode['MATCH'][$node['AOGUID']] = $node;
							}
							elseif($this->checkNamesAlmostEqual($name, $keptNode['NAME']['RU']['NAME']))
							{
								$keptNode['POSSIBLE'][$node['AOGUID']] = $node;
							}
						}
					}
				}

				$this->walkFiasTreeAndKeepFollowing($node['ID'], $toBeFound, $typesToSearch);
			}
		}
	}

public 	private function getFias2YamarketRootLinks($skipMapped = true, $skipCompiled = false)
	{
		$res = $this->getDataFromCSV('fias_yamarket_links', 'rootv2');

		$result = array();
		foreach($res as $reg)
		{
			if($skipMapped && $reg['CITIES_MAPPED'] == '1')
				continue;

			if($skipCompiled && $reg['COMPILED'] == '1')
				continue;

			$fias = array($reg['AOGUID']);

			if(strlen($reg['ADDITIONAL']))
			{
				$variants = explode(', ', $reg['ADDITIONAL']);
				foreach($variants as $var)
				{
					$id = explode(':', $var);
					$fias[] = $id[0];
				}
			}

			$result[$reg['YAMARKET']] = $fias;
		}

		return $result;
	}

public static 	public function splitFiasOnRegions()
	{
		$this->cleanPoolDir('fias_tree');
		$this->walkFias('fiasGotOneSplit');
	}

public 	public function copyFias2DB()
	{
		$this->fiasDB = new Db\FiasTable();
		$this->fiasDB->create();

		$this->fiasDB->switchIndexes(false);
		$this->fiasDB->deleteAll();
		$this->walkFias('fiasGotOneAdd2DB');
		$this->fiasDB->doneInsert();
		$this->fiasDB->switchIndexes(true);
	}

	/*
public 	public function dropFiasTreeDuplicates()
	{
		foreach(new \DirectoryIterator($this->getPoolDirName('fias_tree')) as $file)
		{
			if($file->isDot() || $file->isDir())
				continue;

			$csv = $this->getDataFromCSV('fias_tree', str_replace('.csv', '', $file->getFilename()));

			$index = array();
			foreach($csv as $id => $line)
			{
				if(isset($index[$line['ID']]))
				{
					unset($csv[$id]);
					continue;
				}

				$index[$line['ID']] = true;
			}

			$this->putDataToCSV($csv, 'fias_tree', str_replace('.csv', '', $file->getFilename()));

			unset($index);
			unset($csv);
		}
	}
	*/

public 	public function fiasGotOneSplit($data)
	{
		$item = $data['__ATTR'];

		//$this->manageFiasPath($item);

		//$this->output(str_repeat('-', intval($item['AOLEVEL']) - 1).$item['FORMALNAME']);
		//$this->output($this->printCurrentFiasPath());

		//$this->test[$item['PARENTGUID']][] = $item['AOGUID'];

		$this->putToFile2(
			array(
				'ID' => $item['AOGUID'],
				'ACTSTATUS' => $item['ACTSTATUS'],
				'LIVESTATUS' => $item['LIVESTATUS'],
				'NAME' => $item['FORMALNAME'],
				'TYPE' => $item['SHORTNAME'],
				'POSTALCODE' => $item['POSTALCODE']
			),
			'fias_tree',
			strlen($item['PARENTGUID']) ? $item['PARENTGUID'] : 'root',
			true
		);
	}

public 	public function fiasGotOneAdd2DB($data)
	{
		$item = $data['__ATTR'];

		$code = implode(' ', array(
			$item['REGIONCODE'],
			$item['AUTOCODE'],
			$item['AREACODE'],
			$item['CITYCODE'],
			$item['CTARCODE'],
			$item['PLACECODE'],
			$item['STREETCODE'],
			$item['EXTRCODE'],
			$item['SEXTCODE'],
		));

		// if db works in cp1251
		/*
		$formalName = \CharsetConverter::ConvertCharset($item['FORMALNAME'], 'UTF-8', SITE_CHARSET);
		$nameLC = \CharsetConverter::ConvertCharset($this->makeNameIndexKey($item['FORMALNAME']), 'UTF-8', SITE_CHARSET);
		$shortName = \CharsetConverter::ConvertCharset($item['SHORTNAME'], 'UTF-8', SITE_CHARSET);
		*/

		$formalName = $item['FORMALNAME'];
		$nameLC = $this->makeNameIndexKey($item['FORMALNAME']);
		$shortName = $item['SHORTNAME'];

		$this->fiasDB->insert(array(
			'AOGUID' => $item['AOGUID'],
			'PARENTGUID' => $item['PARENTGUID'],

			'AOID' => $item['AOID'],
			'NEXTID' => $item['NEXTID'],

			'FORMALNAME' => $formalName,
			'SHORTNAME' => $shortName,
			'POSTALCODE' => $item['POSTALCODE'],
			
			'ACTSTATUS' => $item['ACTSTATUS'],
			'LIVESTATUS' => $item['LIVESTATUS'],

			'NAME_LC' => $nameLC,
			'CODE' => $code
		));
	}

static 	private function getByAOID($aoid)
	{
		$id = explode('-', $aoid);
	}

public 	private function printCurrentFiasPath()
	{
		$path = array();
		foreach($this->fiasCPath as $item)
			$path[] = $item['NAME'];

		return implode('>', $path);
	}

public 	private function manageFiasPath($item)
	{
		$guid = $item['AOGUID'];
		$parentGUID = $item['PARENTGUID'];

		//$this->truncateCurrentFiasPath($parentGUID);
		//$this->fiasCPath[] = array('GUID' => $guid, 'NAME' => $item['FORMALNAME']);
	}

public 	private function truncateCurrentFiasPath($guid)
	{
		foreach($this->fiasCPath as $i => $node)
		{
			if($node['GUID'] == $guid)
			{
				array_splice($this->fiasCPath, $i + 1);
				return;
			}
		}
	}

	#######################################################
	### ABOUT FIAS PROCESS ROOT v2
	#######################################################

public 	public function mapFiasRootV2()
	{
		$this->cleanUpFile('fias_yamarket_links', 'rootv2');
		$this->walkFias('fiasGotOneMapRootV2');

		foreach($this->data['MAPS']['REGIONS'] as $id => $reg)
		{
			$foundId = '';
			$foundName = '';
			$additResults = '';

			if(count($reg['MATCH']))
			{
				$foundId = $reg['MATCH'][0]['ID'];
				$foundName = $reg['MATCH'][0]['NAME'];

				array_shift($reg['MATCH']);

				if(count($reg['MATCH']))
				{
					$additResults = array();
					foreach($reg['MATCH'] as $additRes)
						$additResults[] = $additRes['ID'].':"'.$additRes['NAME'].'"';

					$additResults = implode(', ', $additResults);
				}
			}

			$this->putToFile(
				array(
					'YAMARKET' => $id,
					'YAMARKET_NAME' => $reg['NAME']['RU']['NAME'],
					'AOGUID' => $foundId,
					'FIAS_NAME' => $foundName,
					'ADDITIONAL' => $additResults
				),
				'fias_yamarket_links',
				'rootv2'
			);
		}
	}

public 	public function fiasGotOneMapRootV2($data)
	{
		$item = $data['__ATTR'];

		$type = $item['SHORTNAME'];
		$name = $this->makeNameIndexKey($item['FORMALNAME']);

		if($this->sysMaps['FIAS2BASETYPE'][$type] == 'REGION') // this is fias-region
		{
			$result = array();
			foreach($this->data['MAPS']['REGIONS'] as $id => &$node)
			{
				$rName = $this->makeNameIndexKey($node['NAME']['RU']['NAME']);

				if($rName == $name || strpos($rName, $name) !== false)
				{
					$node['MATCH'][] = array(
						'ID' => $item['AOGUID'],
						'NAME' => $item['FORMALNAME'].' '.$item['SHORTNAME']
					);
				}
			}
		}
	}

public static 	private function readFiasRootMapV2()
	{
		try
		{
			$csv = new Import\CSVReader('R', false);
			$result = array();
			$data = $csv->ReadBlock($this->getPoolFileName('fias_yamarket_links', 'rootv2', false));
			foreach($data as $region)
				$result[$region['YAMARKET']] = $region;

			return $result;
		}
		catch(\Exception $e)
		{
			return array();
		}
	}

public 	private function walkFias($callback, $limit = -1)
	{
		$sax = new SAXParser(array(
			'watch4Tag' => 'Object',
			'onEachParseResult' => array($this, $callback),
			'limit' => $limit,
			'collapseAttr' => true
		));

		$fd = fopen($_SERVER['DOCUMENT_ROOT'].$this->options['fiasAddrobjFile'], 'r');
		while($block = fread($fd, 1024))
		{
			if(!$sax->putToParser($block))
				break;
		}

		unset($sax);
	}

	#######################################################
	### ABOUT MAIN DATA
	#######################################################

public 	private function buildRussiaPathIndex($bundle, $parentPath = array())
	{
		foreach($bundle as $id)
		{
			$node = $this->data['TREES']['MAIN']['NODES'][$id];
			$name = $this->makeNameIndexKey($node['NAME']['RU']['NAME']);

			//regions:
			if($node['TYPE_CODE'] == 'REGION')
				$this->data['MAPS']['REGIONS'][$id] = $node;

			if(isset($this->data['TREES']['MAIN']['EDGES'][$id]))
				$this->buildRussiaPathIndex($this->data['TREES']['MAIN']['EDGES'][$id], $ppp);
		}
	}

public 	private function getMainTreeNodesOfType($bundle, $types = array(), &$buffer)
	{
		foreach($bundle as $id)
		{
			$node = $this->data['TREES']['MAIN']['NODES'][$id];

			if(in_array($node['TYPE_CODE'], $types))
			{
				//$node['NAME_I'] = $this->makeNameIndexKey($node['NAME']['RU']['NAME']);
				$buffer[$id] = $node;
			}

			if(isset($this->data['TREES']['MAIN']['EDGES'][$id]))
				$this->getMainTreeNodesOfType($this->data['TREES']['MAIN']['EDGES'][$id], $types, $buffer);
		}
	}

	#######################################################
	### ABOUT FILE POOL
	#######################################################

public 	private function putToFile($data, $poolName, $fileSubname)
	{
		$dir = $_SERVER['DOCUMENT_ROOT'].$this->workDir.$this->filePools[$poolName]['DIR'];
		if(!file_exists($dir))
			mkdir($dir, 0755, true);

		if(!isset($this->filePoolsp[$poolName][$fileSubname]))
		{
			$fd = $this->filePoolsp[$poolName][$fileSubname] = fopen($dir.$fileSubname.'.csv', 'w');
			$head = implode(';', array_keys($data));
			fputs($fd, $head.PHP_EOL);

			$this->filePoolsp[$poolName][$fileSubname] = $fd;
		}

		fputs($this->filePoolsp[$poolName][$fileSubname], implode(';', $data).PHP_EOL);
	}

public 	private function putToFile2($data, $poolName, $fileSubname, $checkDir = false)
	{
		$dir = $_SERVER['DOCUMENT_ROOT'].$this->workDir.$this->filePools[$poolName]['DIR'];
		$fName = $dir.$fileSubname.'.csv';

		if($checkDir && !file_exists($dir))
			mkdir($dir, 0755, true);

		if(!file_exists($fName))
			file_put_contents($fName, implode(';', array_keys($data)).PHP_EOL, FILE_APPEND);

		file_put_contents($fName, implode(';', $data).PHP_EOL, FILE_APPEND);
	}

public static 	private function cleanUpFile($poolName, $fileSubname)
	{
		$name = $_SERVER['DOCUMENT_ROOT'].$this->workDir.$this->filePools[$poolName]['DIR'].$fileSubname.'.csv';

		if(file_exists($name))
			unlink($name);
	}

public static 	private function getPoolFileName($poolName, $fileSubname, $docRoot = true)
	{
		return ($docRoot ? $_SERVER['DOCUMENT_ROOT'] : '/').$this->workDir.$this->filePools[$poolName]['DIR'].$fileSubname.'.csv';
	}

public 	private function getPoolDirName($poolName, $docRoot = true)
	{
		return ($docRoot ? $_SERVER['DOCUMENT_ROOT'] : '/').$this->workDir.$this->filePools[$poolName]['DIR'];
	}

public static 	private function cleanPoolDir($poolName)
	{
		$dir = $_SERVER['DOCUMENT_ROOT'].$this->workDir.$this->filePools[$poolName]['DIR'];
		if(file_exists($dir))
			system('rm -rf '.$dir);

		mkdir($dir, 0755, true);
	}

public 	private function getDataFromCSV($poolName, $fileSubname)
	{
		try
		{
			$csv = new Import\CSVReader('R', false);
			return $csv->ReadBlock($this->getPoolFileName($poolName, $fileSubname, false));
		}
		catch(\Exception $e)
		{
			return array();
		}
	}

public 	private function putDataToCSV($data, $poolName, $fileSubname)
	{
		$fName = $this->getPoolFileName($poolName, $fileSubname);
		if(file_exists($fName))
		{
			$header = implode(';', array_keys($data[0])).PHP_EOL;
			file_put_contents($fName, $header);
			foreach($data as $line)
				file_put_contents($fName, implode(';', $line).PHP_EOL, FILE_APPEND);
		}
	}

	#######################################################
	### ABOUT COMPILER
	#######################################################

public 	private function mapFiasTypeToMain($fiasType)
	{
		return isset($this->sysMaps['FIAS2BASETYPE'][$fiasType]) ? $this->sysMaps['FIAS2BASETYPE'][$fiasType] : false;
	}

public static 	private function makeNameIndexKey($name)
	{
		return trim(mb_strtolower($name, 'UTF-8'));
	}

public 	private function makeTypeGroupFile($file = '')
	{
		$fd = $this->fileOpen(strlen($file) ? $file : self::GROUP_FILE);

		fputs($fd, implode(';', $this->headers['GROUP_FILE']).PHP_EOL);
		foreach($this->typeGroups as $code => $group)
		{
			$line = array();
			foreach($this->headers['GROUP_FILE'] as $colCode)
				$line[] = is_array($group[$colCode]) ? implode(':', $group[$colCode]) : $group[$colCode];

			fputs($fd, implode(';', $line).PHP_EOL);
		}

		fclose($fd);
	}

	private function makeNext()
	{
		$next = $this->queueShift();
		$bundle = $this->getBundleFromFile($next);

		if(!empty($bundle))
		{
			foreach($bundle as $item)
			{
				$this->putToGroups($item);

				if($item['CHILDREN_COUNT'] > 0)
					$this->queue[] = $item['ID'];
			}
		}

		return empty($this->queue);
	}

	public private function queueShift()
	{
		if($this->queue !== false)
			return array_shift($this->queue);

		return 'root';
	}

	private function getBundleFromFile($id)
	{
		$data = unserialize(file_get_contents($_SERVER['DOCUMENT_ROOT'].$this->grabbedStuffDir.$id));

		foreach($data as $k => &$item)
		{
			if(in_array($item['NAME'], array('РџСЂРѕС‡РµРµ', 'РћР±С‰РµСЂРѕСЃСЃРёР№СЃРєРёРµ', 'РЈРЅРёРІРµСЂСЃР°Р»СЊРЅРѕРµ', 'Р”СЂСѓРіРёРµ РіРѕСЂРѕРґР° СЂРµРіРёРѕРЅР°')))
			{
				unset($data[$k]);
				continue;
			}

			//$item['NAME'] = \CharsetConverter::ConvertCharset($item['NAME'], 'UTF-8', SITE_CHARSET); // temp
			if(isset($this->typeMap[$item['TYPE_CODE']]))
				$item['TYPE_CODE'] = $this->typeMap[$item['TYPE_CODE']];

			// type "OTHER" which is a child of type "REGION" is actually a "SUBREGION"
			if($item['TYPE_CODE'] == 'OTHER')
			{
				$parentType = $this->yaIdType[$item['PARENT_ID']];

				if($parentType == 'REGION')
					$item['TYPE_CODE'] = 'SUBREGION';
			}

			$code = $this->addLeadingZero($this->codeOffset, $this->leading);
			$this->yaIdType[$item['ID']] = $item['TYPE_CODE'];

			$this->relations[$item['CODE']] = $item['PARENT_CODE'];
			$this->code2type[$item['CODE']] = $item['TYPE_CODE'];

			$ruName = $item['NAME'];

			//if($this->optionConvertNames)
			//	$ruName = \CharsetConverter::ConvertCharset($ruName, 'UTF-8', SITE_CHARSET);

			$item['NAME'] = array();
			$item['NAME']['RU']['NAME'] = $ruName.($this->options['includeYaInfo2Name'] ? ' ('.$item['TYPE_CODE'].', '.$item['ID'].')' : '');
			//$item['NAME.EN.NAME'] = '[no-translation]'; // attach translations from old import files
			//$item['NAME.UA.NAME'] = '[no-translation]'; // attach translations from old import files

			$item['EXT']['YAMARKET'][] = $item['ID'];

			//unset($item['ID']);
			//unset($item['PARENT_ID']);
		}

		return $data;
	}

public static 	private function getParentOfType($code, $types)
	{
		if(empty($types))
			return '';

		$nextCode = $code;
		$i = -1;
		while($nextCode)
		{
			$i++;

			if($i > 50)
				throw new Main\SystemException('Recursion gone too deep when trying to find parent of type');

			if(isset($types[$this->code2type[$nextCode]]))
				return $nextCode;

			$nextCode = $this->relations[$nextCode];
		}

		return '';
	}

public static 	private function putToGroups($item)
	{
		foreach($this->typeGroups as $gCode => &$group)
		{
			//_dump_r('Item '.$item['NAME.RU.NAME'].' ('.$item['TYPE_CODE'].')');

			if(!isset($group['I_TYPES'][$item['TYPE_CODE']]))
				continue;

			//_dump_r('Goes to group: '.$gCode);

			$baseParent = $this->getParentOfType($item['CODE'], $this->typeGroups[$group['PARENT']]['I_TYPES']);

			//_dump_r('Base parent is: '.$baseParent);

			if(!$group['FD'][$baseParent])
			{
				$fName = str_replace(array(
					'%BASE_PARENT_ITEM_CODE%',
					'%CODE%'
				), array(
					$baseParent,
					ToLower($gCode)
				), $group['FILE_NAME_TEMPLATE']);

				$group['FD'][$baseParent] = $this->fileOpen($fName);
				fputs($group['FD'][$baseParent], implode(';', $this->headers[$group['HEADER']]).PHP_EOL);
			}

			$header = $this->headers[$group['HEADER']];
			$line = array();
			foreach($header as $code)
				$line[] = isset($item[$code]) ? $item[$code] : '';

			fputs($group['FD'][$baseParent], implode(';', $line).PHP_EOL);
		}
	}

public static 	private function fileOpen($name)
	{
		return fopen($_SERVER['DOCUMENT_ROOT'].$this->workDir.self::OUTPUT_DIR.$name, 'w');
	}

public static 	private static function addLeadingZero($value, $length)
	{
		if(strlen($value) >= $length)
			return $value;

		$diff = abs($length - strlen($value));

		for($i = 0; $i < $diff; $i++)
			$value = '0'.$value;

		return $value;
	}

	#######################################################
	### ABOUT DATA
	#######################################################

public static 	private function storeTemporalData($dataCode, $data)
	{
		$dir = $_SERVER['DOCUMENT_ROOT'].$this->workDir.self::TMP_DATA_DIR;
		if(!file_exists($dir))
			mkdir($dir, 0755, true);

		file_put_contents($dir.$dataCode, serialize($data));
	}

public static 	private function getStoredTemporalData($dataCode)
	{
		$file = $_SERVER['DOCUMENT_ROOT'].$this->workDir.self::TMP_DATA_DIR.$dataCode;

		if(is_readable($file))
			return unserialize(file_get_contents($file));
		else
			return array();
	}

public static 	private function cleanTemporalData($dataCode)
	{
		$file = $_SERVER['DOCUMENT_ROOT'].$this->workDir.self::TMP_DATA_DIR.$dataCode;

		if(is_readable($file))
			unlink($file);
	}

	/*
public static 	private function dropTemporalFile()
	{
		$file = $_SERVER['DOCUMENT_ROOT'].$this->workDir.self::TMP_DATA_FILE;

		if(is_readable($file))
			unlink($file);
	}
	*/

public static 	public function cleanOutput()
	{
		$file = $_SERVER['DOCUMENT_ROOT'].$this->workDir.self::OUTPUT_FILE;

		if(is_readable($file))
			unlink($file);
	}

public static 	public function output($data, $important = true)
	{
		if(!$important)
			return false;

		ob_start();
		print_r($data);
		$data = ob_get_contents();
		ob_end_clean();

		file_put_contents($_SERVER['DOCUMENT_ROOT'].$this->workDir.self::OUTPUT_FILE, $data.PHP_EOL, FILE_APPEND);
	}
}
