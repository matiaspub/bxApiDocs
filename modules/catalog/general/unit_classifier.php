<?
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class CCatalogMeasureClassifier
 */
class CCatalogMeasureClassifier
{
	protected static $unitsClassifier = null;

	private function initMeasureClassifier()
	{
		if (null !== self::$unitsClassifier)
			return;

		self::$unitsClassifier = array(
			0 =>
			array(
				'TITLE' => Loc::getMessage('CAT_UC_TITLE1'),
				0 =>
				array(
					'TITLE' => Loc::getMessage('CAT_UC_LENGTH_UNITS'),
					3 =>
					array(
						'CODE' => '003',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLIMETR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MM"),
						'SYMBOL_INTL' => 'mm',
						'SYMBOL_LETTER_INTL' => 'MMT',
					),
					4 =>
					array(
						'CODE' => '004',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SANTIMETR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_SM"),
						'SYMBOL_INTL' => 'cm',
						'SYMBOL_LETTER_INTL' => 'CMT',
					),
					5 =>
					array(
						'CODE' => '005',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DETCIMETR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DM"),
						'SYMBOL_INTL' => 'dm',
						'SYMBOL_LETTER_INTL' => 'DMT',
					),
					6 =>
					array(
						'CODE' => '006',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_METR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_M"),
						'SYMBOL_INTL' => 'm',
						'SYMBOL_LETTER_INTL' => 'MTR',
					),
					8 =>
					array(
						'CODE' => '008',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOMETR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KM"),
						'SYMBOL_INTL' => 'km',
						'SYMBOL_LETTER_INTL' => 'KMT',
					),
					9 =>
					array(
						'CODE' => '009',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MEGAMETR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MEGAM"),
						'SYMBOL_INTL' => 'Mm',
						'SYMBOL_LETTER_INTL' => 'MAM',
					),
					39 =>
					array(
						'CODE' => '039',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DYUJM_25_4_MM"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DYUJM"),
						'SYMBOL_INTL' => 'in',
						'SYMBOL_LETTER_INTL' => 'INH',
					),
					41 =>
					array(
						'CODE' => '041',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_FUT_0_3048_M"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_FUT"),
						'SYMBOL_INTL' => 'ft',
						'SYMBOL_LETTER_INTL' => 'FOT',
					),
					43 =>
					array(
						'CODE' => '043',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_YARD_0_9144_M"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_YARD"),
						'SYMBOL_INTL' => 'yd',
						'SYMBOL_LETTER_INTL' => 'YRD',
					),
					47 =>
					array(
						'CODE' => '047',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MORSKAYA_MILYA_1852_M"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MILYA"),
						'SYMBOL_INTL' => 'n mile',
						'SYMBOL_LETTER_INTL' => 'NMI',
					),
				),
				1 =>
				array(
					'TITLE' => Loc::getMessage('CAT_UC_AREA_UNITS'),
					50 =>
					array(
						'CODE' => '050',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVADRATNIJ_MILLIMETR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MM2"),
						'SYMBOL_INTL' => 'mm2',
						'SYMBOL_LETTER_INTL' => 'MMK',
					),
					51 =>
					array(
						'CODE' => '051',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVADRATNIJ_SANTIMETR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_SM2"),
						'SYMBOL_INTL' => 'cm2',
						'SYMBOL_LETTER_INTL' => 'CMK',
					),
					53 =>
					array(
						'CODE' => '053',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVADRATNIJ_DETCIMETR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DM2"),
						'SYMBOL_INTL' => 'dm2',
						'SYMBOL_LETTER_INTL' => 'DMK',
					),
					55 =>
					array(
						'CODE' => '055',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVADRATNIJ_METR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_M2"),
						'SYMBOL_INTL' => 'm2',
						'SYMBOL_LETTER_INTL' => 'MTK',
					),
					58 =>
					array(
						'CODE' => '058',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_KVADRATNIH_METROV"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_M2"),
						'SYMBOL_INTL' => 'daa',
						'SYMBOL_LETTER_INTL' => 'DAA',
					),
					59 =>
					array(
						'CODE' => '059',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GEKTAR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_GA"),
						'SYMBOL_INTL' => 'ha',
						'SYMBOL_LETTER_INTL' => 'HAR',
					),
					61 =>
					array(
						'CODE' => '061',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVADRATNIJ_KILOMETR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KM2"),
						'SYMBOL_INTL' => 'km2',
						'SYMBOL_LETTER_INTL' => 'KMK',
					),
					71 =>
					array(
						'CODE' => '071',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVADRATNIJ_DYUJM_645_16_MM2"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DYUJM2"),
						'SYMBOL_INTL' => 'in2',
						'SYMBOL_LETTER_INTL' => 'INK',
					),
					73 =>
					array(
						'CODE' => '073',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVADRATNIJ_FUT_0_092903_M2"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_FUT2"),
						'SYMBOL_INTL' => 'ft2',
						'SYMBOL_LETTER_INTL' => 'FTK',
					),
					75 =>
					array(
						'CODE' => '075',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVADRATNIJ_YARD_0_8361274_M2"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_YARD2"),
						'SYMBOL_INTL' => 'yd2',
						'SYMBOL_LETTER_INTL' => 'YDK',
					),
					109 =>
					array(
						'CODE' => '109',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_AR_100_M2"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_A"),
						'SYMBOL_INTL' => 'a',
						'SYMBOL_LETTER_INTL' => 'ARE',
					),
				),
				2 =>
				array(
					'TITLE' => Loc::getMessage('CAT_UC_VOLUME_UNITS'),
					110 =>
					array(
						'CODE' => '110',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KUBICHESKIJ_MILLIMETR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MM3"),
						'SYMBOL_INTL' => 'mm3',
						'SYMBOL_LETTER_INTL' => 'MMQ',
					),
					111 =>
					array(
						'CODE' => '111',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KUBICHESKIJ_SANTIMETR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_SM3"),
						'SYMBOL_INTL' => 'cm3',
						'SYMBOL_LETTER_INTL' => 'CMQ',
					),
					112 =>
					array(
						'CODE' => '112',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_LITR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_L"),
						'SYMBOL_INTL' => 'l',
						'SYMBOL_LETTER_INTL' => 'LTR',
					),
					113 =>
					array(
						'CODE' => '113',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KUBICHESKIJ_METR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_M3"),
						'SYMBOL_INTL' => 'm3',
						'SYMBOL_LETTER_INTL' => 'MTQ',
					),
					118 =>
					array(
						'CODE' => '118',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DETCILITR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DL"),
						'SYMBOL_INTL' => 'dl',
						'SYMBOL_LETTER_INTL' => 'DLT',
					),
					122 =>
					array(
						'CODE' => '122',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GEKTOLITR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_GL"),
						'SYMBOL_INTL' => 'hl',
						'SYMBOL_LETTER_INTL' => 'HLT',
					),
					126 =>
					array(
						'CODE' => '126',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MEGALITR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_ML"),
						'SYMBOL_INTL' => 'Ml',
						'SYMBOL_LETTER_INTL' => 'MAL',
					),
					131 =>
					array(
						'CODE' => '131',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KUBICHESKIJ_DYUJM_16387_1_MM3"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DYUJM3"),
						'SYMBOL_INTL' => 'in3',
						'SYMBOL_LETTER_INTL' => 'INQ',
					),
					132 =>
					array(
						'CODE' => '132',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KUBICHESKIJ_FUT_0_02831685_M3"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_FUT3"),
						'SYMBOL_INTL' => 'ft3',
						'SYMBOL_LETTER_INTL' => 'FTQ',
					),
					133 =>
					array(
						'CODE' => '133',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KUBICHESKIJ_YARD_0_764555_M3"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_YARD3"),
						'SYMBOL_INTL' => 'yd3',
						'SYMBOL_LETTER_INTL' => 'YDQ',
					),
					159 =>
					array(
						'CODE' => '159',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_KUBICHESKIH_METROV"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_M3"),
						'SYMBOL_INTL' => '10^6 m3',
						'SYMBOL_LETTER_INTL' => 'HMQ',
					),
				),
				3 =>
				array(
					'TITLE' => Loc::getMessage('CAT_UC_MASS_UNITS'),
					160 =>
					array(
						'CODE' => '160',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GEKTOGRAMM"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_GG"),
						'SYMBOL_INTL' => 'hg',
						'SYMBOL_LETTER_INTL' => 'HGM',
					),
					161 =>
					array(
						'CODE' => '161',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLIGRAMM"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MG"),
						'SYMBOL_INTL' => 'mg',
						'SYMBOL_LETTER_INTL' => 'MGM',
					),
					162 =>
					array(
						'CODE' => '162',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_METRICHESKIJ_KARAT_1_KARAT_=_200_MG_=_2*0_0001_KG"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KAR"),
						'SYMBOL_INTL' => 'CTM',
						'SYMBOL_LETTER_INTL' => 'CTM',
					),
					163 =>
					array(
						'CODE' => '163',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GRAMM"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_G"),
						'SYMBOL_INTL' => 'g',
						'SYMBOL_LETTER_INTL' => 'GRM',
					),
					166 =>
					array(
						'CODE' => '166',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOGRAMM"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KG"),
						'SYMBOL_INTL' => 'kg',
						'SYMBOL_LETTER_INTL' => 'KGM',
					),
					168 =>
					array(
						'CODE' => '168',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TONNA_METRICHESKAYA_TONNA_1000_KG"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_T"),
						'SYMBOL_INTL' => 't',
						'SYMBOL_LETTER_INTL' => 'TNE',
					),
					170 =>
					array(
						'CODE' => '170',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOTONNA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_10_T3"),
						'SYMBOL_INTL' => 'kt',
						'SYMBOL_LETTER_INTL' => 'KTN',
					),
					173 =>
					array(
						'CODE' => '173',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SANTIGRAMM"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_SG"),
						'SYMBOL_INTL' => 'cg',
						'SYMBOL_LETTER_INTL' => 'CGM',
					),
					181 =>
					array(
						'CODE' => '181',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_BRUTTO_-_REGISTROVAYA_TONNA_2_8316_M3"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_BRT"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'GRT',
					),
					185 =>
					array(
						'CODE' => '185',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GRUZOPOD_EMNOST_V_METRICHESKIH_TONNAH"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_T_GRP"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'CCT',
					),
					206 =>
					array(
						'CODE' => '206',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TCENTNER_METRICHESKIJ_100_KG"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_TC"),
						'SYMBOL_INTL' => 'q',
						'SYMBOL_LETTER_INTL' => 'DTN',
					),
				),
				4 =>
				array(
					'TITLE' => Loc::getMessage('CAT_UC_ENGINEERING_UNITS'),
					212 =>
					array(
						'CODE' => '212',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_VATT"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_VT"),
						'SYMBOL_INTL' => 'W',
						'SYMBOL_LETTER_INTL' => 'WTT',
					),
					214 =>
					array(
						'CODE' => '214',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOVATT"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KVT"),
						'SYMBOL_INTL' => 'kW',
						'SYMBOL_LETTER_INTL' => 'KWT',
					),
					215 =>
					array(
						'CODE' => '215',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MEGAVATT_TISYACHA_KILOVATT"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MVT_1000_KVT"),
						'SYMBOL_INTL' => 'MW',
						'SYMBOL_LETTER_INTL' => 'MAW',
					),
					222 =>
					array(
						'CODE' => '222',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_VOL_T"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_V"),
						'SYMBOL_INTL' => 'V',
						'SYMBOL_LETTER_INTL' => 'VLT',
					),
					223 =>
					array(
						'CODE' => '223',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOVOL_T"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KV"),
						'SYMBOL_INTL' => 'kV',
						'SYMBOL_LETTER_INTL' => 'KVT',
					),
					227 =>
					array(
						'CODE' => '227',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOVOL_T_-_AMPER"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KV_A"),
						'SYMBOL_INTL' => 'kV.A',
						'SYMBOL_LETTER_INTL' => 'KVA',
					),
					228 =>
					array(
						'CODE' => '228',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MEGAVOL_T_-_AMPER_TISYACHA_KILOVOL_T_-_AMPER"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MV_A"),
						'SYMBOL_INTL' => 'MV.A',
						'SYMBOL_LETTER_INTL' => 'MVA',
					),
					230 =>
					array(
						'CODE' => '230',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOVAR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KVAR"),
						'SYMBOL_INTL' => 'kVAR',
						'SYMBOL_LETTER_INTL' => 'KVR',
					),
					243 =>
					array(
						'CODE' => '243',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_VATT_-_CHAS"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_VT_CH"),
						'SYMBOL_INTL' => 'W.h',
						'SYMBOL_LETTER_INTL' => 'WHR',
					),
					245 =>
					array(
						'CODE' => '245',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOVATT_-_CHAS"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KVT_CH"),
						'SYMBOL_INTL' => 'kW.h',
						'SYMBOL_LETTER_INTL' => 'KWH',
					),
					246 =>
					array(
						'CODE' => '246',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MEGAVATT_-_CHAS"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MVT_CH"),
						'SYMBOL_INTL' => 'MW.h',
						'SYMBOL_LETTER_INTL' => 'MWH',
					),
					247 =>
					array(
						'CODE' => '247',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GIGAVATT_-_CHAS_MILLION_KILOVATT_-_CHASOV"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_GVT_CH"),
						'SYMBOL_INTL' => 'GW.h',
						'SYMBOL_LETTER_INTL' => 'GWH',
					),
					260 =>
					array(
						'CODE' => '260',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_AMPER"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_A"),
						'SYMBOL_INTL' => 'A',
						'SYMBOL_LETTER_INTL' => 'AMP',
					),
					263 =>
					array(
						'CODE' => '263',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_AMPER_-_CHAS_3_6_KKL"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_A_CH"),
						'SYMBOL_INTL' => 'A.h',
						'SYMBOL_LETTER_INTL' => 'AMH',
					),
					264 =>
					array(
						'CODE' => '264',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_AMPER_-_CHASOV"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_A_CH"),
						'SYMBOL_INTL' => '1000  A.h',
						'SYMBOL_LETTER_INTL' => 'TAH',
					),
					270 =>
					array(
						'CODE' => '270',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KULON"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KL"),
						'SYMBOL_INTL' => 'C',
						'SYMBOL_LETTER_INTL' => 'COU',
					),
					271 =>
					array(
						'CODE' => '271',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DZHOUL"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DZH"),
						'SYMBOL_INTL' => 'J',
						'SYMBOL_LETTER_INTL' => 'JOU',
					),
					273 =>
					array(
						'CODE' => '273',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILODZHOUL"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KDZH"),
						'SYMBOL_INTL' => 'kJ',
						'SYMBOL_LETTER_INTL' => 'KJO',
					),
					274 =>
					array(
						'CODE' => '274',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_OM"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_OM"),
						'SYMBOL_INTL' => 'OHM',
						'SYMBOL_LETTER_INTL' => 'OHM',
					),
					280 =>
					array(
						'CODE' => '280',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GRADUS_TCEL_SIYA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_S"),
						'SYMBOL_INTL' => Loc::getMessage("CAT_UC_GRADUS_SYMBOL").'C',
						'SYMBOL_LETTER_INTL' => 'CEL',
					),
					281 =>
					array(
						'CODE' => '281',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GRADUS_FARENGEJTA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_F"),
						'SYMBOL_INTL' => Loc::getMessage("CAT_UC_GRADUS_SYMBOL").'F',
						'SYMBOL_LETTER_INTL' => 'FAN',
					),
					282 =>
					array(
						'CODE' => '282',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KANDELA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KD"),
						'SYMBOL_INTL' => 'cd',
						'SYMBOL_LETTER_INTL' => 'CDL',
					),
					283 =>
					array(
						'CODE' => '283',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_LYUKS"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_LK"),
						'SYMBOL_INTL' => 'lx',
						'SYMBOL_LETTER_INTL' => 'LUX',
					),
					284 =>
					array(
						'CODE' => '284',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_LYUMEN"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_LM"),
						'SYMBOL_INTL' => 'lm',
						'SYMBOL_LETTER_INTL' => 'LUM',
					),
					288 =>
					array(
						'CODE' => '288',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KEL_VIN"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_K"),
						'SYMBOL_INTL' => 'K',
						'SYMBOL_LETTER_INTL' => 'KEL',
					),
					289 =>
					array(
						'CODE' => '289',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_N_YUTON"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_N"),
						'SYMBOL_INTL' => 'N',
						'SYMBOL_LETTER_INTL' => 'NEW',
					),
					290 =>
					array(
						'CODE' => '290',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GERTC"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_GTC"),
						'SYMBOL_INTL' => 'Hz',
						'SYMBOL_LETTER_INTL' => 'HTZ',
					),
					291 =>
					array(
						'CODE' => '291',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOGERTC"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KGTC"),
						'SYMBOL_INTL' => 'kHz',
						'SYMBOL_LETTER_INTL' => 'KHZ',
					),
					292 =>
					array(
						'CODE' => '292',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MEGAGERTC"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MGTC"),
						'SYMBOL_INTL' => 'MHz',
						'SYMBOL_LETTER_INTL' => 'MHZ',
					),
					294 =>
					array(
						'CODE' => '294',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_PASKAL"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_PA"),
						'SYMBOL_INTL' => 'Pa',
						'SYMBOL_LETTER_INTL' => 'PAL',
					),
					296 =>
					array(
						'CODE' => '296',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SIMENS"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_SM"),
						'SYMBOL_INTL' => 'S',
						'SYMBOL_LETTER_INTL' => 'SIE',
					),
					297 =>
					array(
						'CODE' => '297',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOPASKAL"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KPA"),
						'SYMBOL_INTL' => 'kPa',
						'SYMBOL_LETTER_INTL' => 'KPA',
					),
					298 =>
					array(
						'CODE' => '298',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MEGAPASKAL"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MPA"),
						'SYMBOL_INTL' => 'MPa',
						'SYMBOL_LETTER_INTL' => 'MPA',
					),
					300 =>
					array(
						'CODE' => '300',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_FIZICHESKAYA_ATMOSFERA_101325_PA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_ATM"),
						'SYMBOL_INTL' => 'atm',
						'SYMBOL_LETTER_INTL' => 'ATM',
					),
					301 =>
					array(
						'CODE' => '301',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TEHNICHESKAYA_ATMOSFERA_98066_5_PA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_AT"),
						'SYMBOL_INTL' => 'at',
						'SYMBOL_LETTER_INTL' => 'ATT',
					),
					302 =>
					array(
						'CODE' => '302',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GIGABEKKEREL"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_GBK"),
						'SYMBOL_INTL' => 'GBq',
						'SYMBOL_LETTER_INTL' => 'GBQ',
					),
					304 =>
					array(
						'CODE' => '304',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLIKYURI"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MKI"),
						'SYMBOL_INTL' => 'mCi',
						'SYMBOL_LETTER_INTL' => 'MCU',
					),
					305 =>
					array(
						'CODE' => '305',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KYURI"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KI"),
						'SYMBOL_INTL' => 'Ci',
						'SYMBOL_LETTER_INTL' => 'CUR',
					),
					306 =>
					array(
						'CODE' => '306',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GRAMM_DELYASHIHSYA_IZOTOPOV"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_G_DI"),
						'SYMBOL_INTL' => 'g fissile isotopes',
						'SYMBOL_LETTER_INTL' => 'GFI',
					),
					308 =>
					array(
						'CODE' => '308',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLIBAR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MB"),
						'SYMBOL_INTL' => 'mbar',
						'SYMBOL_LETTER_INTL' => 'MBR',
					),
					309 =>
					array(
						'CODE' => '309',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_BAR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_BAR"),
						'SYMBOL_INTL' => 'bar',
						'SYMBOL_LETTER_INTL' => 'BAR',
					),
					310 =>
					array(
						'CODE' => '310',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GEKTOBAR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_GB"),
						'SYMBOL_INTL' => 'hbar',
						'SYMBOL_LETTER_INTL' => 'HBA',
					),
					312 =>
					array(
						'CODE' => '312',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOBAR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KB"),
						'SYMBOL_INTL' => 'kbar',
						'SYMBOL_LETTER_INTL' => 'KBA',
					),
					314 =>
					array(
						'CODE' => '314',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_FARAD"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_F"),
						'SYMBOL_INTL' => 'F',
						'SYMBOL_LETTER_INTL' => 'FAR',
					),
					316 =>
					array(
						'CODE' => '316',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOGRAMM_NA_KUBICHESKIJ_METR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KGM3"),
						'SYMBOL_INTL' => 'kg/m3',
						'SYMBOL_LETTER_INTL' => 'KMQ',
					),
					323 =>
					array(
						'CODE' => '323',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_BEKKEREL"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_BK"),
						'SYMBOL_INTL' => 'Bq',
						'SYMBOL_LETTER_INTL' => 'BQL',
					),
					324 =>
					array(
						'CODE' => '324',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_VEBER"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_VB"),
						'SYMBOL_INTL' => 'Wb',
						'SYMBOL_LETTER_INTL' => 'WEB',
					),
					327 =>
					array(
						'CODE' => '327',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_UZEL_MILYACH"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_UZ"),
						'SYMBOL_INTL' => 'kn',
						'SYMBOL_LETTER_INTL' => 'KNT',
					),
					328 =>
					array(
						'CODE' => '328',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_METR_V_SEKUNDU"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MS"),
						'SYMBOL_INTL' => 'm/s',
						'SYMBOL_LETTER_INTL' => 'MTS',
					),
					330 =>
					array(
						'CODE' => '330',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_OBOROT_V_SEKUNDU"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_OBS"),
						'SYMBOL_INTL' => 'r/s',
						'SYMBOL_LETTER_INTL' => 'RPS',
					),
					331 =>
					array(
						'CODE' => '331',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_OBOROT_V_MINUTU"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_OBMIN"),
						'SYMBOL_INTL' => 'r/min',
						'SYMBOL_LETTER_INTL' => 'RPM',
					),
					333 =>
					array(
						'CODE' => '333',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOMETR_V_CHAS"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KMCH"),
						'SYMBOL_INTL' => 'km/h',
						'SYMBOL_LETTER_INTL' => 'KMH',
					),
					335 =>
					array(
						'CODE' => '335',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_METR_NA_SEKUNDU_V_KVADRATE"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MS2"),
						'SYMBOL_INTL' => 'm/s2',
						'SYMBOL_LETTER_INTL' => 'MSK',
					),
					349 =>
					array(
						'CODE' => '349',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KULON_NA_KILOGRAMM"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KLKG"),
						'SYMBOL_INTL' => 'C/kg',
						'SYMBOL_LETTER_INTL' => 'CKG',
					),
				),
				5 =>
				array(
					'TITLE' => Loc::getMessage('CAT_UC_TIME_UNITS'),
					354 =>
					array(
						'CODE' => '354',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SEKUNDA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_S"),
						'SYMBOL_INTL' => 's',
						'SYMBOL_LETTER_INTL' => 'SEC',
					),
					355 =>
					array(
						'CODE' => '355',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MINUTA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MIN"),
						'SYMBOL_INTL' => 'min',
						'SYMBOL_LETTER_INTL' => 'MIN',
					),
					356 =>
					array(
						'CODE' => '356',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_CHAS"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_CH"),
						'SYMBOL_INTL' => 'h',
						'SYMBOL_LETTER_INTL' => 'HUR',
					),
					359 =>
					array(
						'CODE' => '359',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SUTKI"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_SUT"),
						'SYMBOL_INTL' => 'd',
						'SYMBOL_LETTER_INTL' => 'DAY',
					),
					360 =>
					array(
						'CODE' => '360',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_NEDELYA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_NED"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'WEE',
					),
					361 =>
					array(
						'CODE' => '361',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DEKADA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DEK"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'DAD',
					),
					362 =>
					array(
						'CODE' => '362',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MESYATC"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MES"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'MON',
					),
					364 =>
					array(
						'CODE' => '364',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVARTAL"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KVART"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'QAN',
					),
					365 =>
					array(
						'CODE' => '365',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_POLUGODIE"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_POLGODA"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'SAN',
					),
					366 =>
					array(
						'CODE' => '366',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GOD"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_G_LET"),
						'SYMBOL_INTL' => 'a',
						'SYMBOL_LETTER_INTL' => 'ANN',
					),
					368 =>
					array(
						'CODE' => '368',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DESYATILETIE"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DESLET"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'DEC',
					),
				),
				6 =>
				array(
					'TITLE' => Loc::getMessage('CAT_UC_ECONOMIC_UNITS'),
					499 =>
					array(
						'CODE' => '499',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOGRAMM_V_SEKUNDU"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KGS"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'KGS',
					),
					533 =>
					array(
						'CODE' => '533',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TONNA_PARA_V_CHAS"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_T_PARCH"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'TSH',
					),
					596 =>
					array(
						'CODE' => '596',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KUBICHESKIJ_METR_V_SEKUNDU"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_M3S"),
						'SYMBOL_INTL' => 'm3/s',
						'SYMBOL_LETTER_INTL' => 'MQS',
					),
					598 =>
					array(
						'CODE' => '598',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KUBICHESKIJ_METR_V_CHAS"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_M3CH"),
						'SYMBOL_INTL' => 'm3/h',
						'SYMBOL_LETTER_INTL' => 'MQH',
					),
					599 =>
					array(
						'CODE' => '599',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_KUBICHESKIH_METROV_V_SUTKI"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_M3SUT"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'TQD',
					),
					616 =>
					array(
						'CODE' => '616',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_BOBINA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_BOB"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'NBB',
					),
					625 =>
					array(
						'CODE' => '625',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_LIST"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_L"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'LEF',
					),
					626 =>
					array(
						'CODE' => '626',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_STO_LISTOV"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_100_L"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'CLF',
					),
					630 =>
					array(
						'CODE' => '630',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_STANDARTNIH_USLOVNIH_KIRPICHEJ"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_TIS_STAND_USL_KIRP"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'MBE',
					),
					641 =>
					array(
						'CODE' => '641',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DYUZHINA_12_SHT"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DYUZHINA"),
						'SYMBOL_INTL' => 'Doz. 12',
						'SYMBOL_LETTER_INTL' => 'DZN',
					),
					657 =>
					array(
						'CODE' => '657',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_IZDELIE"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_IZD"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'NAR',
					),
					683 =>
					array(
						'CODE' => '683',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_STO_YASHIKOV"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_100_YASH"),
						'SYMBOL_INTL' => 'Hbx',
						'SYMBOL_LETTER_INTL' => 'HBX',
					),
					704 =>
					array(
						'CODE' => '704',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_NABOR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_NABOR"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'SET',
					),
					715 =>
					array(
						'CODE' => '715',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_PARA_2_SHT"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_PAR"),
						'SYMBOL_INTL' => 'pr. 2',
						'SYMBOL_LETTER_INTL' => 'NPR',
					),
					730 =>
					array(
						'CODE' => '730',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DVA_DESYATKA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_20"),
						'SYMBOL_INTL' => '20',
						'SYMBOL_LETTER_INTL' => 'SCO',
					),
					732 =>
					array(
						'CODE' => '732',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DESYAT_PAR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_10_PAR"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'TPR',
					),
					733 =>
					array(
						'CODE' => '733',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DYUZHINA_PAR"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DYUZHINA_PAR"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'DPR',
					),
					734 =>
					array(
						'CODE' => '734',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_POSILKA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_POSIL"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'NPL',
					),
					735 =>
					array(
						'CODE' => '735',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_CHAST"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_CHAST"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'NPT',
					),
					736 =>
					array(
						'CODE' => '736',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_RULON"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_RUL"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'NPL',
					),
					737 =>
					array(
						'CODE' => '737',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DYUZHINA_RULONOV"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DYUZHINA_RUL"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'DRL',
					),
					740 =>
					array(
						'CODE' => '740',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DYUZHINA_SHTUK"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DYUZHINA_SHT"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'DPC',
					),
					745 =>
					array(
						'CODE' => '745',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_ELEMENT"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_ELEM"),
						'SYMBOL_INTL' => 'CI',
						'SYMBOL_LETTER_INTL' => 'NCL',
					),
					778 =>
					array(
						'CODE' => '778',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_UPAKOVKA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_UPAK"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'NMP',
					),
					780 =>
					array(
						'CODE' => '780',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DYUZHINA_UPAKOVOK"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DYUZHINA_UPAK"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'DZP',
					),
					781 =>
					array(
						'CODE' => '781',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_STO_UPAKOVOK"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_100_UPAK"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'CNP',
					),
					796 =>
					array(
						'CODE' => '796',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SHTUKA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_SHT"),
						'SYMBOL_INTL' => 'pc. 1',
						'SYMBOL_LETTER_INTL' => 'PCE. NMB',
					),
					797 =>
					array(
						'CODE' => '797',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_STO_SHTUK"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_100_SHT"),
						'SYMBOL_INTL' => '100',
						'SYMBOL_LETTER_INTL' => 'CEN',
					),
					798 =>
					array(
						'CODE' => '798',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_SHTUK"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_TIS_SHT_1000_SHT"),
						'SYMBOL_INTL' => '1000',
						'SYMBOL_LETTER_INTL' => 'MIL',
					),
					799 =>
					array(
						'CODE' => '799',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_SHTUK"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_SHT"),
						'SYMBOL_INTL' => '10^6',
						'SYMBOL_LETTER_INTL' => 'MIO',
					),
					800 =>
					array(
						'CODE' => '800',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLIARD_SHTUK"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_109_SHT"),
						'SYMBOL_INTL' => '10^9',
						'SYMBOL_LETTER_INTL' => 'MLD',
					),
					801 =>
					array(
						'CODE' => '801',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_BILLION_SHTUK_EVROPA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1012_SHT"),
						'SYMBOL_INTL' => '10^12',
						'SYMBOL_LETTER_INTL' => 'BIL',
					),
					802 =>
					array(
						'CODE' => '802',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVINTIL_ON_SHTUK_EVROPA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1018_SHT"),
						'SYMBOL_INTL' => '10^18',
						'SYMBOL_LETTER_INTL' => 'TRL',
					),
					820 =>
					array(
						'CODE' => '820',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KREPOST_SPIRTA_PO_MASSE"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KREP_SPIRTA_PO_MASSE"),
						'SYMBOL_INTL' => '% mds',
						'SYMBOL_LETTER_INTL' => 'ASM',
					),
					821 =>
					array(
						'CODE' => '821',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KREPOST_SPIRTA_PO_OB_EMU"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KREP_SPIRTA_PO_OB_EMU"),
						'SYMBOL_INTL' => '% vol',
						'SYMBOL_LETTER_INTL' => 'ASV',
					),
					831 =>
					array(
						'CODE' => '831',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_LITR_CHISTOGO_100_SPIRTA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_L_100_SPIRTA"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'LPA',
					),
					833 =>
					array(
						'CODE' => '833',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GEKTOLITR_CHISTOGO_100_SPIRTA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_GL_100_SPIRTA"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'HPA',
					),
					841 =>
					array(
						'CODE' => '841',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOGRAMM_PEROKSIDA_VODORODA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KG_H_2_0_2"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => NULL,
					),
					845 =>
					array(
						'CODE' => '845',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOGRAMM_90-GO_SUHOGO_VESHESTVA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KG_90_SV"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'KSD',
					),
					847 =>
					array(
						'CODE' => '847',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TONNA_90-GO_SUHOGO_VESHESTVA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_T_90_SV"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'TSD',
					),
					852 =>
					array(
						'CODE' => '852',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOGRAMM_OKSIDA_KALIYA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KG_K_2_O"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'KPO',
					),
					859 =>
					array(
						'CODE' => '859',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOGRAMM_GIDROKSIDA_KALIYA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KG_KON"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'KPH',
					),
					861 =>
					array(
						'CODE' => '861',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOGRAMM_AZOTA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KG_N"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'KNI',
					),
					863 =>
					array(
						'CODE' => '863',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOGRAMM_GIDROKSIDA_NATRIYA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KG_NAOH"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'KSH',
					),
					865 =>
					array(
						'CODE' => '865',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOGRAMM_PYATIOKISI_FOSFORA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KG_R_2_O_5"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'KPP',
					),
					867 =>
					array(
						'CODE' => '867',
						'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOGRAMM_URANA"),
						'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KG_U"),
						'SYMBOL_INTL' => NULL,
						'SYMBOL_LETTER_INTL' => 'KUR',
					),
				),
			),
		);

		if(LANGUAGE_ID === 'ru' || LANGUAGE_ID === 'uk' || LANGUAGE_ID === 'by')
		{
			self::$unitsClassifier[] =
				array(
					'TITLE' => Loc::getMessage('CAT_UC_TITLE2'),
					0 =>
					array(
						'TITLE' => Loc::getMessage('CAT_UC_LENGTH_UNITS'),
						18 =>
						array(
							'CODE' => '018',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_POGONNIJ_METR"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_POG_M"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						19 =>
						array(
							'CODE' => '019',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_POGONNIH_METROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_POG_M"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						20 =>
						array(
							'CODE' => '020',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_USLOVNIJ_METR"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_USL_M"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						48 =>
						array(
							'CODE' => '048',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_USLOVNIH_METROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_USL_M"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						49 =>
						array(
							'CODE' => '049',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOMETR_USLOVNIH_TRUB"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KM_USL_TRUB"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
					),
					1 =>
					array(
						'TITLE' => Loc::getMessage('CAT_UC_AREA_UNITS'),
						54 =>
						array(
							'CODE' => '054',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_KVADRATNIH_DETCIMETROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_DM2"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						56 =>
						array(
							'CODE' => '056',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_KVADRATNIH_DETCIMETROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_DM2"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						57 =>
						array(
							'CODE' => '057',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_KVADRATNIH_METROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_M2"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						60 =>
						array(
							'CODE' => '060',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_GEKTAROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_GA"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						62 =>
						array(
							'CODE' => '062',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_USLOVNIJ_KVADRATNIJ_METR"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_USL_M2"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						63 =>
						array(
							'CODE' => '063',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_USLOVNIH_KVADRATNIH_METROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_USL_M2"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						64 =>
						array(
							'CODE' => '064',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_USLOVNIH_KVADRATNIH_METROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_USL_M2"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						81 =>
						array(
							'CODE' => '081',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVADRATNIJ_METR_OBSHEJ_PLOSHADI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_M2_OBSH_PL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						82 =>
						array(
							'CODE' => '082',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_KVADRATNIH_METROV_OBSHEJ_PLOSHADI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_M2_OBSH_PL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						83 =>
						array(
							'CODE' => '083',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_KVADRATNIH_METROV_OBSHEJ_PLOSHADI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_M2_OBSH_PL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						84 =>
						array(
							'CODE' => '084',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVADRATNIJ_METR_ZHILOJ_PLOSHADI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_M2_ZHIL_PL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						85 =>
						array(
							'CODE' => '085',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_KVADRATNIH_METROV_ZHILOJ_PLOSHADI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_M2_ZHIL_PL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						86 =>
						array(
							'CODE' => '086',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_KVADRATNIH_METROV_ZHILOJ_PLOSHADI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_M2_ZHIL_PL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						87 =>
						array(
							'CODE' => '087',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVADRATNIJ_METR_UCHEBNO_-_LABORATORNIH_ZDANIJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_M2_UCH_LAB_ZDAN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						88 =>
						array(
							'CODE' => '088',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_KVADRATNIH_METROV_UCHEBNO_-_LABORATORNIH_ZDANIJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_M2_UCH_LAB_ZDAN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						89 =>
						array(
							'CODE' => '089',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_KVADRATNIH_METROV_V_DVUHMILLIMETROVOM_ISCHISLENII"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_M2_2_MM_ISCH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
					),
					2 =>
					array(
						'TITLE' => Loc::getMessage('CAT_UC_VOLUME_UNITS'),
						114 =>
						array(
							'CODE' => '114',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_KUBICHESKIH_METROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_M3"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						115 =>
						array(
							'CODE' => '115',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLIARD_KUBICHESKIH_METROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_109_M3"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						116 =>
						array(
							'CODE' => '116',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DEKALITR"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DKL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						119 =>
						array(
							'CODE' => '119',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_DEKALITROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_DKL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						120 =>
						array(
							'CODE' => '120',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_DEKALITROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_DKL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						121 =>
						array(
							'CODE' => '121',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_PLOTNIJ_KUBICHESKIJ_METR"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_PLOTN_M3"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						123 =>
						array(
							'CODE' => '123',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_USLOVNIJ_KUBICHESKIJ_METR"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_USL_M3"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						124 =>
						array(
							'CODE' => '124',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_USLOVNIH_KUBICHESKIH_METROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_USL_M3"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						125 =>
						array(
							'CODE' => '125',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_KUBICHESKIH_METROV_PERERABOTKI_GAZA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_M3_PERERAB_GAZA"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						127 =>
						array(
							'CODE' => '127',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_PLOTNIH_KUBICHESKIH_METROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_PLOTN_M3"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						128 =>
						array(
							'CODE' => '128',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_POLULITROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_POL_L"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						129 =>
						array(
							'CODE' => '129',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_POLULITROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_POL_L"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						130 =>
						array(
							'CODE' => '130',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_LITROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_L"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
					),
					3 =>
					array(
						'TITLE' => Loc::getMessage('CAT_UC_MASS_UNITS'),
						165 =>
						array(
							'CODE' => '165',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_KARATOV_METRICHESKIH"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_KAR"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						167 =>
						array(
							'CODE' => '167',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_KARATOV_METRICHESKIH"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_KAR"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						169 =>
						array(
							'CODE' => '169',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TONN"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_T"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						171 =>
						array(
							'CODE' => '171',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_TONN"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_T"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						172 =>
						array(
							'CODE' => '172',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TONNA_USLOVNOGO_TOPLIVA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_T_USL_TOPL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						175 =>
						array(
							'CODE' => '175',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TONN_USLOVNOGO_TOPLIVA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_T_USL_TOPL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						176 =>
						array(
							'CODE' => '176',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_TONN_USLOVNOGO_TOPLIVA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_T_USL_TOPL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						177 =>
						array(
							'CODE' => '177',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TONN_EDINOVREMENNOGO_HRANENIYA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_T_EDINOVR_HRAN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						178 =>
						array(
							'CODE' => '178',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TONN_PERERABOTKI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_T_PERERAB"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						179 =>
						array(
							'CODE' => '179',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_USLOVNAYA_TONNA_T"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_USL_T"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						207 =>
						array(
							'CODE' => '207',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TCENTNEROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_TC"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
					),
					4 =>
					array(
						'TITLE' => Loc::getMessage('CAT_UC_ENGINEERING_UNITS'),
						226 =>
						array(
							'CODE' => '226',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_VOL_T_-_AMPER"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_V_A"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						231 =>
						array(
							'CODE' => '231',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_METR_V_CHAS"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MCH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						232 =>
						array(
							'CODE' => '232',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOKALORIYA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KKAL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						233 =>
						array(
							'CODE' => '233',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GIGAKALORIYA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_GKAL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						234 =>
						array(
							'CODE' => '234',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_GIGAKALORIJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_GKAL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						235 =>
						array(
							'CODE' => '235',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_GIGAKALORIJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_GKAL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						236 =>
						array(
							'CODE' => '236',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KALORIYA_V_CHAS"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KALCH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						237 =>
						array(
							'CODE' => '237',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOKALORIYA_V_CHAS"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KKALCH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						238 =>
						array(
							'CODE' => '238',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GIGAKALORIYA_V_CHAS"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_GKALCH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						239 =>
						array(
							'CODE' => '239',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_GIGAKALORIJ_V_CHAS"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_GKALCH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						241 =>
						array(
							'CODE' => '241',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_AMPER_-_CHASOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_A_CH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						242 =>
						array(
							'CODE' => '242',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_KILOVOL_T_-_AMPER"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_KV_A"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						248 =>
						array(
							'CODE' => '248',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOVOL_T_-_AMPER_REAKTIVNIJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KV_A_R"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						249 =>
						array(
							'CODE' => '249',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLIARD_KILOVATT_-_CHASOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_109_KVT_CH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						250 =>
						array(
							'CODE' => '250',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_KILOVOL_T_-_AMPER_REAKTIVNIH"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_KV_A_R"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						251 =>
						array(
							'CODE' => '251',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_LOSHADINAYA_SILA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_L_S"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						252 =>
						array(
							'CODE' => '252',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_LOSHADINIH_SIL"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_L_S"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						253 =>
						array(
							'CODE' => '253',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_LOSHADINIH_SIL"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_L_S"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						254 =>
						array(
							'CODE' => '254',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_BIT"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_BIT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						255 =>
						array(
							'CODE' => '255',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_BAJT"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_BAJT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						256 =>
						array(
							'CODE' => '256',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOBAJT"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KBAJT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						257 =>
						array(
							'CODE' => '257',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MEGABAJT"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MBAJT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						258 =>
						array(
							'CODE' => '258',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_BOD"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_BOD"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						287 =>
						array(
							'CODE' => '287',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GENRI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_GN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						313 =>
						array(
							'CODE' => '313',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TESLA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_TL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						317 =>
						array(
							'CODE' => '317',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOGRAMM_NA_KVADRATNIJ_SANTIMETR"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KGSM2"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						337 =>
						array(
							'CODE' => '337',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLIMETR_VODYANOGO_STOLBA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MM_VOD_ST"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						338 =>
						array(
							'CODE' => '338',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLIMETR_RTUTNOGO_STOLBA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MM_RT_ST"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						339 =>
						array(
							'CODE' => '339',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SANTIMETR_VODYANOGO_STOLBA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_SM_VOD_ST"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
					),
					5 =>
					array(
						'TITLE' => Loc::getMessage('CAT_UC_TIME_UNITS'),
						352 =>
						array(
							'CODE' => '352',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MIKROSEKUNDA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MKS"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						353 =>
						array(
							'CODE' => '353',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLISEKUNDA_EK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MLS"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
					),
					6 =>
					array(
						'TITLE' => Loc::getMessage('CAT_UC_ECONOMIC_UNITS'),
						383 =>
						array(
							'CODE' => '383',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_RUBL"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_RUB"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						384 =>
						array(
							'CODE' => '384',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_RUBLEJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_RUB"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						385 =>
						array(
							'CODE' => '385',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_RUBLEJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_RUB"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						386 =>
						array(
							'CODE' => '386',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLIARD_RUBLEJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_109_RUB"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						387 =>
						array(
							'CODE' => '387',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TRILLION_RUBLEJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1012_RUB"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						388 =>
						array(
							'CODE' => '388',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVADRIL_ON_RUBLEJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1015_RUB"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						414 =>
						array(
							'CODE' => '414',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_PASSAZHIRO_-_KILOMETR"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_PASS_KM"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						421 =>
						array(
							'CODE' => '421',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_PASSAZHIRSKOE_MESTO_PASSAZHIRSKIH_MEST"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_PASS_MEST"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						423 =>
						array(
							'CODE' => '423',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_PASSAZHIRO_-_KILOMETROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_PASS_KM"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						424 =>
						array(
							'CODE' => '424',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_PASSAZHIRO_-_KILOMETROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_PASS_KM"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						427 =>
						array(
							'CODE' => '427',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_PASSAZHIROPOTOK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_PASS_POTOK"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						449 =>
						array(
							'CODE' => '449',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TONNO_-_KILOMETR"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_T_KM"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						450 =>
						array(
							'CODE' => '450',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TONNO_-_KILOMETROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_T_KM"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						451 =>
						array(
							'CODE' => '451',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_TONNO_-_KILOMETROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_T_KM"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						479 =>
						array(
							'CODE' => '479',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_NABOROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_NABOR"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						510 =>
						array(
							'CODE' => '510',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GRAMM_NA_KILOVATT_-_CHAS"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_GKVT_CH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						511 =>
						array(
							'CODE' => '511',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KILOGRAMM_NA_GIGAKALORIYU"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KGGKAL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						512 =>
						array(
							'CODE' => '512',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TONNO_-_NOMER"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_T_NOM"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						513 =>
						array(
							'CODE' => '513',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_AVTOTONNA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_AVTO_T"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						514 =>
						array(
							'CODE' => '514',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TONNA_TYAGI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_T_TYAGI"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						515 =>
						array(
							'CODE' => '515',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DEDVEJT-TONNA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DEDVEJT_T"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						516 =>
						array(
							'CODE' => '516',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TONNO-TANID"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_T_TANID"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						521 =>
						array(
							'CODE' => '521',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_CHELOVEK_NA_KVADRATNIJ_METR"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_CHELM2"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						522 =>
						array(
							'CODE' => '522',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_CHELOVEK_NA_KVADRATNIJ_KILOMETR"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_CHELKM2"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						534 =>
						array(
							'CODE' => '534',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TONNA_V_CHAS"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_TCH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						535 =>
						array(
							'CODE' => '535',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TONNA_V_SUTKI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_TSUT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						536 =>
						array(
							'CODE' => '536',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TONNA_V_SMENU"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_TSMEN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						537 =>
						array(
							'CODE' => '537',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TONN_V_SEZON"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_TSEZ"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						538 =>
						array(
							'CODE' => '538',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TONN_V_GOD"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_TGOD"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						539 =>
						array(
							'CODE' => '539',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_CHELOVEKO_-_CHAS"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_CHEL_CH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						540 =>
						array(
							'CODE' => '540',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_CHELOVEKO_-_DEN"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_CHEL_DN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						541 =>
						array(
							'CODE' => '541',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_CHELOVEKO_-_DNEJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_CHEL_DN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						542 =>
						array(
							'CODE' => '542',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_CHELOVEKO_-_CHASOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_CHEL_CH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						543 =>
						array(
							'CODE' => '543',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_USLOVNIH_BANOK_V_SMENU"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_USL_BANKSMEN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						544 =>
						array(
							'CODE' => '544',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_EDINITC_V_GOD"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_EDGOD"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						545 =>
						array(
							'CODE' => '545',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_POSESHENIE_V_SMENU"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_POSESHSMEN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						546 =>
						array(
							'CODE' => '546',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_POSESHENIJ_V_SMENU"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_POSESHSMEN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						547 =>
						array(
							'CODE' => '547',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_PARA_V_SMENU"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_PARSMEN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						548 =>
						array(
							'CODE' => '548',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_PAR_V_SMENU"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_PARSMEN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						550 =>
						array(
							'CODE' => '550',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_TONN_V_GOD"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_TGOD"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						552 =>
						array(
							'CODE' => '552',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TONNA_PERERABOTKI_V_SUTKI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_T_PERERABSUT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						553 =>
						array(
							'CODE' => '553',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TONN_PERERABOTKI_V_SUTKI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_T_PERERABSUT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						554 =>
						array(
							'CODE' => '554',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TCENTNER_PERERABOTKI_V_SUTKI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_TC_PERERABSUT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						555 =>
						array(
							'CODE' => '555',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TCENTNEROV_PERERABOTKI_V_SUTKI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_TC_PERERABSUT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						556 =>
						array(
							'CODE' => '556',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_GOLOV_V_GOD"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_GOLGOD"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						557 =>
						array(
							'CODE' => '557',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_GOLOV_V_GOD"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_GOLGOD"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						558 =>
						array(
							'CODE' => '558',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_PTITCEMEST"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_PTITCEMEST"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						559 =>
						array(
							'CODE' => '559',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_KUR_-_NESUSHEK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_KUR_NESUSH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						560 =>
						array(
							'CODE' => '560',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MINIMAL_NAYA_ZARABOTNAYA_PLATA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MIN_ZARABOTN_PLAT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						561 =>
						array(
							'CODE' => '561',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TONN_PARA_V_CHAS"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_T_PARCH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						562 =>
						array(
							'CODE' => '562',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_PRYADIL_NIH_VERETEN"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_PRYAD_VERET"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						563 =>
						array(
							'CODE' => '563',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_PRYADIL_NIH_MEST"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_PRYAD_MEST"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						639 =>
						array(
							'CODE' => '639',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DOZA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DOZ"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						640 =>
						array(
							'CODE' => '640',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_DOZ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_DOZ"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						642 =>
						array(
							'CODE' => '642',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_EDINITCA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_ED"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						643 =>
						array(
							'CODE' => '643',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_EDINITC"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_ED"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						644 =>
						array(
							'CODE' => '644',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_EDINITC"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_ED"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						661 =>
						array(
							'CODE' => '661',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KANAL"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KANAL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						673 =>
						array(
							'CODE' => '673',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_KOMPLEKTOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_KOMPL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						698 =>
						array(
							'CODE' => '698',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MESTO"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_MEST"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						699 =>
						array(
							'CODE' => '699',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_MEST"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_MEST"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						709 =>
						array(
							'CODE' => '709',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_NOMEROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_NOM"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						724 =>
						array(
							'CODE' => '724',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_GEKTAROV_PORTCIJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_GA_PORTC"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						729 =>
						array(
							'CODE' => '729',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_PACHEK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_PACH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						744 =>
						array(
							'CODE' => '744',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_PROTCENT"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_PROC"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						746 =>
						array(
							'CODE' => '746',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_PROMILLE_0_1_PROTCENTA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_PROMILLE"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						751 =>
						array(
							'CODE' => '751',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_RULONOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_RUL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						761 =>
						array(
							'CODE' => '761',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_STANOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_STAN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						762 =>
						array(
							'CODE' => '762',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_STANTCIYA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_STANTC"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						775 =>
						array(
							'CODE' => '775',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TYUBIKOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_TYUBIK"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						776 =>
						array(
							'CODE' => '776',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_USLOVNIH_TUBOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_USL_TUB"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						779 =>
						array(
							'CODE' => '779',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_UPAKOVOK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_UPAK"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						782 =>
						array(
							'CODE' => '782',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_UPAKOVOK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_UPAK"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						792 =>
						array(
							'CODE' => '792',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_CHELOVEK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_CHEL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						793 =>
						array(
							'CODE' => '793',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_CHELOVEK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_CHEL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						794 =>
						array(
							'CODE' => '794',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_CHELOVEK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_CHEL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						808 =>
						array(
							'CODE' => '808',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_EKZEMPLYAROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_EKZ"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						810 =>
						array(
							'CODE' => '810',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_YACHEJKA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_YACH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						812 =>
						array(
							'CODE' => '812',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_YASHIK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_YASH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						836 =>
						array(
							'CODE' => '836',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GOLOVA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_GOL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						837 =>
						array(
							'CODE' => '837',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_PAR"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_PAR"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						838 =>
						array(
							'CODE' => '838',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_PAR"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_PAR"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						839 =>
						array(
							'CODE' => '839',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KOMPLEKT"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KOMPL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						840 =>
						array(
							'CODE' => '840',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SEKTCIYA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_SEKTC"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						868 =>
						array(
							'CODE' => '868',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_BUTILKA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_BUT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						869 =>
						array(
							'CODE' => '869',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_BUTILOK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_BUT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						870 =>
						array(
							'CODE' => '870',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_AMPULA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_AMPUL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						871 =>
						array(
							'CODE' => '871',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_AMPUL"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_AMPUL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						872 =>
						array(
							'CODE' => '872',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_FLAKON"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_FLAK"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						873 =>
						array(
							'CODE' => '873',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_FLAKONOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_FLAK"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						874 =>
						array(
							'CODE' => '874',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TUBOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_TUB"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						875 =>
						array(
							'CODE' => '875',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_KOROBOK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_KOR"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						876 =>
						array(
							'CODE' => '876',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_USLOVNAYA_EDINITCA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_USL_ED"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						877 =>
						array(
							'CODE' => '877',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_USLOVNIH_EDINITC"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_USL_ED"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						878 =>
						array(
							'CODE' => '878',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_USLOVNIH_EDINITC"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_USL_ED"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						879 =>
						array(
							'CODE' => '879',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_USLOVNAYA_SHTUKA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_USL_SHT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						880 =>
						array(
							'CODE' => '880',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_USLOVNIH_SHTUK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_USL_SHT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						881 =>
						array(
							'CODE' => '881',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_USLOVNAYA_BANKA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_USL_BANK"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						882 =>
						array(
							'CODE' => '882',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_USLOVNIH_BANOK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_USL_BANK"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						883 =>
						array(
							'CODE' => '883',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_USLOVNIH_BANOK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_USL_BANK"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						884 =>
						array(
							'CODE' => '884',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_USLOVNIJ_KUSOK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_USL_KUS"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						885 =>
						array(
							'CODE' => '885',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_USLOVNIH_KUSKOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_USL_KUS"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						886 =>
						array(
							'CODE' => '886',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_USLOVNIH_KUSKOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_USL_KUS"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						887 =>
						array(
							'CODE' => '887',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_USLOVNIJ_YASHIK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_USL_YASH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						888 =>
						array(
							'CODE' => '888',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_USLOVNIH_YASHIKOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_USL_YASH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						889 =>
						array(
							'CODE' => '889',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_USLOVNAYA_KATUSHKA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_USL_KAT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						890 =>
						array(
							'CODE' => '890',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_USLOVNIH_KATUSHEK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_USL_KAT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						891 =>
						array(
							'CODE' => '891',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_USLOVNAYA_PLITKA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_USL_PLIT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						892 =>
						array(
							'CODE' => '892',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_USLOVNIH_PLITOK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_USL_PLIT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						893 =>
						array(
							'CODE' => '893',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_USLOVNIJ_KIRPICH"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_USL_KIRP"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						894 =>
						array(
							'CODE' => '894',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_USLOVNIH_KIRPICHEJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_USL_KIRP"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						895 =>
						array(
							'CODE' => '895',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_USLOVNIH_KIRPICHEJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_USL_KIRP"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						896 =>
						array(
							'CODE' => '896',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SEM_YA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_SEMEJ"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						897 =>
						array(
							'CODE' => '897',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_SEMEJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_SEMEJ"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						898 =>
						array(
							'CODE' => '898',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_SEMEJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_SEMEJ"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						899 =>
						array(
							'CODE' => '899',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DOMOHOZYAJSTVO"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_DOMHOZ"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						900 =>
						array(
							'CODE' => '900',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_DOMOHOZYAJSTV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_DOMHOZ"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						901 =>
						array(
							'CODE' => '901',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_DOMOHOZYAJSTV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_DOMHOZ"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						902 =>
						array(
							'CODE' => '902',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_UCHENICHESKOE_MESTO"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_UCHEN_MEST"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						903 =>
						array(
							'CODE' => '903',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_UCHENICHESKIH_MEST"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_UCHEN_MEST"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						904 =>
						array(
							'CODE' => '904',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_RABOCHEE_MESTO"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_RAB_MEST"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						905 =>
						array(
							'CODE' => '905',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_RABOCHIH_MEST"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_RAB_MEST"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						906 =>
						array(
							'CODE' => '906',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_POSADOCHNOE_MESTO"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_POSAD_MEST"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						907 =>
						array(
							'CODE' => '907',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_POSADOCHNIH_MEST"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_POSAD_MEST"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						908 =>
						array(
							'CODE' => '908',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_NOMER"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_NOM"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						909 =>
						array(
							'CODE' => '909',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVARTIRA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KVART"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						910 =>
						array(
							'CODE' => '910',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_KVARTIR"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_KVART"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						911 =>
						array(
							'CODE' => '911',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KOJKA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KOEK"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						912 =>
						array(
							'CODE' => '912',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_KOEK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_KOEK"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						913 =>
						array(
							'CODE' => '913',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TOM_KNIZHNOGO_FONDA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_TOM_KNIZHN_FOND"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						914 =>
						array(
							'CODE' => '914',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TOMOV_KNIZHNOGO_FONDA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_1000_TOM_KNIZHN_FOND"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						915 =>
						array(
							'CODE' => '915',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_USLOVNIJ_REMONT"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_USL_REM"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						916 =>
						array(
							'CODE' => '916',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_USLOVNIJ_REMONT_V_GOD"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_USL_REMGOD"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						917 =>
						array(
							'CODE' => '917',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SMENA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_SMEN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						918 =>
						array(
							'CODE' => '918',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_LIST_AVTORSKIJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_L_AVT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						920 =>
						array(
							'CODE' => '920',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_LIST_PECHATNIJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_L_PECH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						921 =>
						array(
							'CODE' => '921',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_LIST_UCHETNO_-_IZDATEL_SKIJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_L_UCH_-IZD"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						922 =>
						array(
							'CODE' => '922',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_ZNAK"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_ZNAK"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						923 =>
						array(
							'CODE' => '923',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SLOVO"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_SLOVO"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						924 =>
						array(
							'CODE' => '924',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SIMVOL"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_SIMVOL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						925 =>
						array(
							'CODE' => '925',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_USLOVNAYA_TRUBA"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_USL_TRUB"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						930 =>
						array(
							'CODE' => '930',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_PLASTIN"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_PLAST"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						937 =>
						array(
							'CODE' => '937',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_DOZ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_DOZ"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						949 =>
						array(
							'CODE' => '949',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_LISTOV-OTTISKOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_LIST_OTTISK"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						950 =>
						array(
							'CODE' => '950',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_VAGONO_MASHINO_-DEN"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_VAG_MASH_DN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						951 =>
						array(
							'CODE' => '951',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_VAGONO-_MASHINO_-CHASOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_VAG_MASH_CH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						952 =>
						array(
							'CODE' => '952',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_VAGONO-_MASHINO_-KILOMETROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_VAG_MASH_KM"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						953 =>
						array(
							'CODE' => '953',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_MESTO-KILOMETROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_MEST_KM"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						954 =>
						array(
							'CODE' => '954',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_VAGONO-SUTKI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_VAG_SUT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						955 =>
						array(
							'CODE' => '955',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_POEZDO-CHASOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_POEZD_CH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						956 =>
						array(
							'CODE' => '956',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_POEZDO-KILOMETROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_POEZD_KM"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						957 =>
						array(
							'CODE' => '957',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TONNO-MIL"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_T_MIL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						958 =>
						array(
							'CODE' => '958',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_PASSAZHIRO-MIL"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_PASS_MIL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						959 =>
						array(
							'CODE' => '959',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_AVTOMOBILE-DEN"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_AVTOMOB_DN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						960 =>
						array(
							'CODE' => '960',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_AVTOMOBILE-TONNO-DNEJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_AVTOMOB_T_DN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						961 =>
						array(
							'CODE' => '961',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_AVTOMOBILE-CHASOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_AVTOMOB_CH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						962 =>
						array(
							'CODE' => '962',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_AVTOMOBILE-MESTO-DNEJ"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_AVTOMOB_MEST_DN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						963 =>
						array(
							'CODE' => '963',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_PRIVEDENNIJ_CHAS"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_PRIVED_CH"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						964 =>
						array(
							'CODE' => '964',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SAMOLETO-KILOMETR"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_SAMOLET_KM"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						965 =>
						array(
							'CODE' => '965',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_KILOMETROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_KM"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						966 =>
						array(
							'CODE' => '966',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TONNAZHE-REJSOV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_TONNAZH_REJS"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						967 =>
						array(
							'CODE' => '967',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_TONNO-MIL"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_T_MIL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						968 =>
						array(
							'CODE' => '968',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_PASSAZHIRO-MIL"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_PASS_MIL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						969 =>
						array(
							'CODE' => '969',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_TONNAZHE-MIL"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_TONNAZH_MIL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						970 =>
						array(
							'CODE' => '970',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_PASSAZHIRO-MESTO-MIL"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_PASS_MEST_MIL"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						971 =>
						array(
							'CODE' => '971',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KORMO-DEN"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KORM_DN"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						972 =>
						array(
							'CODE' => '972',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TCENTNER_KORMOVIH_EDINITC"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_TC_KORM_ED"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						973 =>
						array(
							'CODE' => '973',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_AVTOMOBILE-KILOMETROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_AVTOMOB_KM"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						974 =>
						array(
							'CODE' => '974',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TONNAZHE-SUT"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_TONNAZH_SUT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						975 =>
						array(
							'CODE' => '975',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SUGO-SUTKI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_SUGO_SUT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						976 =>
						array(
							'CODE' => '976',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SHTUK_V_20-FUTOVOM_EKVIVALENTE_DFE"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_SHTUK_V_20-FUTOVOM_EKVIVALENTE"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						977 =>
						array(
							'CODE' => '977',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KANALO-KILOMETR"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KANAL_KM"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						978 =>
						array(
							'CODE' => '978',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KANALO-KONTCI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_KANAL_KONTC"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						979 =>
						array(
							'CODE' => '979',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_EKZEMPLYAROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_EKZ"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						980 =>
						array(
							'CODE' => '980',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_DOLLAROV"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_DOLLAR"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						981 =>
						array(
							'CODE' => '981',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHA_TONN_KORMOVIH_EDINITC"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_103_KORM_ED"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						982 =>
						array(
							'CODE' => '982',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILLION_TONN_KORMOVIH_EDINITC"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_106_KORM_ED"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
						983 =>
						array(
							'CODE' => '983',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SUDO-SUTKI"),
							'SYMBOL_RUS' => Loc::getMessage("CAT_UC_SUD_SUT"),
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => NULL,
						),
					),
				);
			self::$unitsClassifier[] =
				array(
					'TITLE' => Loc::getMessage('CAT_UC_TITLE3'),
					0 =>
					array(
						'TITLE' => Loc::getMessage('CAT_UC_LENGTH_UNITS'),
						17 =>
						array(
							'CODE' => '017',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GEKTOMETR"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'hm',
							'SYMBOL_LETTER_INTL' => 'HMT',
						),
						45 =>
						array(
							'CODE' => '045',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MILYA_USTAVNAYA_1609_344_M"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'mile',
							'SYMBOL_LETTER_INTL' => 'SMI',
						),
					),
					1 =>
					array(
						'TITLE' => Loc::getMessage('CAT_UC_AREA_UNITS'),
						77 =>
						array(
							'CODE' => '077',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_AKR_4840_KVADRATNIH_YARDOV"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'acre',
							'SYMBOL_LETTER_INTL' => 'ACR',
						),
						79 =>
						array(
							'CODE' => '079',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVADRATNAYA_MILYA"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'mile2',
							'SYMBOL_LETTER_INTL' => 'MIK',
						),
					),
					2 =>
					array(
						'TITLE' => Loc::getMessage('CAT_UC_VOLUME_UNITS'),
						135 =>
						array(
							'CODE' => '135',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_ZHIDKOSTNAYA_UNTCIYA_SK_3_28_413_SM3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'fl oz (UK)',
							'SYMBOL_LETTER_INTL' => 'OZI',
						),
						136 =>
						array(
							'CODE' => '136',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DZHILL_SK_0_142065_DM3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'gill (UK)',
							'SYMBOL_LETTER_INTL' => 'GII',
						),
						137 =>
						array(
							'CODE' => '137',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_PINTA_SK_0_568262_DM3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'pt (UK)',
							'SYMBOL_LETTER_INTL' => 'PTI',
						),
						138 =>
						array(
							'CODE' => '138',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVARTA_SK_1_136523_DM3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'qt (UK)',
							'SYMBOL_LETTER_INTL' => 'QTI',
						),
						139 =>
						array(
							'CODE' => '139',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GALLON_SK_4_546092_DM3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'gal (UK)',
							'SYMBOL_LETTER_INTL' => 'GLI',
						),
						140 =>
						array(
							'CODE' => '140',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_BUSHEL_SK_36_36874_DM3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'bu (UK)',
							'SYMBOL_LETTER_INTL' => 'BUI',
						),
						141 =>
						array(
							'CODE' => '141',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_ZHIDKOSTNAYA_UNTCIYA_SSHA_29_5735_SM3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'fl oz (US)',
							'SYMBOL_LETTER_INTL' => 'OZA',
						),
						142 =>
						array(
							'CODE' => '142',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DZHILL_SSHA_11_8294_SM3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'gill  (US)',
							'SYMBOL_LETTER_INTL' => 'GIA',
						),
						143 =>
						array(
							'CODE' => '143',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_ZHIDKOSTNAYA_PINTA_SSHA_0_473176_DM3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'liq pt (US)',
							'SYMBOL_LETTER_INTL' => 'PTL',
						),
						144 =>
						array(
							'CODE' => '144',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_ZHIDKOSTNAYA_KVARTA_SSHA_0_946353_DM3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'liq qt (US)',
							'SYMBOL_LETTER_INTL' => 'QTL',
						),
						145 =>
						array(
							'CODE' => '145',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_ZHIDKOSTNIJ_GALLON_SSHA_3_78541_DM3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'gal (US)',
							'SYMBOL_LETTER_INTL' => 'GLL',
						),
						146 =>
						array(
							'CODE' => '146',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_BARREL_NEFTYANOJ_SSHA_158_987_DM3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'barrel (US)',
							'SYMBOL_LETTER_INTL' => 'BLL',
						),
						147 =>
						array(
							'CODE' => '147',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SUHAYA_PINTA_SSHA_0_55061_DM3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'dry pt (US)',
							'SYMBOL_LETTER_INTL' => 'PTD',
						),
						148 =>
						array(
							'CODE' => '148',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SUHAYA_KVARTA_SSHA_1_101221_DM3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'dry qt (US)',
							'SYMBOL_LETTER_INTL' => 'QTD',
						),
						149 =>
						array(
							'CODE' => '149',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SUHOJ_GALLON_SSHA_4_404884_DM3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'dry gal (US)',
							'SYMBOL_LETTER_INTL' => 'GLD',
						),
						150 =>
						array(
							'CODE' => '150',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_BUSHEL_SSHA_35_2391_DM3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'bu (US)',
							'SYMBOL_LETTER_INTL' => 'BUA',
						),
						151 =>
						array(
							'CODE' => '151',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SUHOJ_BARREL_SSHA_115_627_DM3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'bbl (US)',
							'SYMBOL_LETTER_INTL' => 'BLD',
						),
						152 =>
						array(
							'CODE' => '152',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_STANDART"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => 'WSD',
						),
						153 =>
						array(
							'CODE' => '153',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KORD_3_63_M3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => 'WCD',
						),
						154 =>
						array(
							'CODE' => '154',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TISYACHI_BORDFUTOV_2_36_M3"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => 'MBF',
						),
					),
					3 =>
					array(
						'TITLE' => Loc::getMessage('CAT_UC_MASS_UNITS'),
						182 =>
						array(
							'CODE' => '182',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_NETTO_-_REGISTROVAYA_TONNA"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => 'NTT',
						),
						183 =>
						array(
							'CODE' => '183',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_OBMERNAYA_FRAHTOVAYA_TONNA"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => 'SHT',
						),
						184 =>
						array(
							'CODE' => '184',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_VODOIZMESHENIE"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => 'DPT',
						),
						186 =>
						array(
							'CODE' => '186',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_FUNT_SK_SSHA_0_45359237_KG"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'lb',
							'SYMBOL_LETTER_INTL' => 'LBR',
						),
						187 =>
						array(
							'CODE' => '187',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_UNTCIYA_SK_SSHA_28_349523_G"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'oz',
							'SYMBOL_LETTER_INTL' => 'ONZ',
						),
						188 =>
						array(
							'CODE' => '188',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DRAHMA_SK_1_771745_G"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'dr',
							'SYMBOL_LETTER_INTL' => 'DRI',
						),
						189 =>
						array(
							'CODE' => '189',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GRAN_SK_SSHA_64_798910_MG"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'gn',
							'SYMBOL_LETTER_INTL' => 'GRN',
						),
						190 =>
						array(
							'CODE' => '190',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_STOUN_SK_6_350293_KG"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'st',
							'SYMBOL_LETTER_INTL' => 'STI',
						),
						191 =>
						array(
							'CODE' => '191',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KVARTER_SK_12_700586_KG"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'qtr',
							'SYMBOL_LETTER_INTL' => 'QTR',
						),
						192 =>
						array(
							'CODE' => '192',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TCENTAL_SK_45_359237_KG"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => 'CNT',
						),
						193 =>
						array(
							'CODE' => '193',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TCENTNER_SSHA_45_3592_KG"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'cwt',
							'SYMBOL_LETTER_INTL' => 'CWA',
						),
						194 =>
						array(
							'CODE' => '194',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DLINNIJ_TCENTNER_SK_50_802345_KG"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'cwt (UK)',
							'SYMBOL_LETTER_INTL' => 'CWI',
						),
						195 =>
						array(
							'CODE' => '195',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KOROTKAYA_TONNA_SK_SSHA_0_90718474_T"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'sht',
							'SYMBOL_LETTER_INTL' => 'STN',
						),
						196 =>
						array(
							'CODE' => '196',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DLINNAYA_TONNA_SK_SSHA_1_0160469_T"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'lt',
							'SYMBOL_LETTER_INTL' => 'LTN',
						),
						197 =>
						array(
							'CODE' => '197',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_SKRUPUL_SK_SSHA_1_295982_G"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'scr',
							'SYMBOL_LETTER_INTL' => 'SCR',
						),
						198 =>
						array(
							'CODE' => '198',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_PENNIVEJT_SK_SSHA_1_555174_G"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'dwt',
							'SYMBOL_LETTER_INTL' => 'DWT',
						),
						199 =>
						array(
							'CODE' => '199',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DRAHMA_SK_3_887935_G"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'drm',
							'SYMBOL_LETTER_INTL' => 'DRM',
						),
						200 =>
						array(
							'CODE' => '200',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_DRAHMA_SSHA_3_887935_G"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => 'DRA',
						),
						201 =>
						array(
							'CODE' => '201',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_UNTCIYA_SK_SSHA_31_10348_G_TROJSKAYA_UNTCIYA"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'apoz',
							'SYMBOL_LETTER_INTL' => 'APZ',
						),
						202 =>
						array(
							'CODE' => '202',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_TROJSKIJ_FUNT_SSHA_373_242_G"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => 'LBT',
						),
					),
					4 =>
					array(
						'TITLE' => Loc::getMessage('CAT_UC_ENGINEERING_UNITS'),
						213 =>
						array(
							'CODE' => '213',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_EFFEKTIVNAYA_MOSHNOST_245_7_VATT"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'B.h.p.',
							'SYMBOL_LETTER_INTL' => 'BHP',
						),
						275 =>
						array(
							'CODE' => '275',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_BRITANSKAYA_TEPLOVAYA_EDINITCA_1_055_KDZH"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'Btu',
							'SYMBOL_LETTER_INTL' => 'BTU',
						),
					),
					5 =>
					array(
						'TITLE' => Loc::getMessage('CAT_UC_TIME_UNITS'),
					),
					6 =>
					array(
						'TITLE' => Loc::getMessage('CAT_UC_ECONOMIC_UNITS'),
						638 =>
						array(
							'CODE' => '638',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GROSS_144_SHT"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => 'gr',
							'SYMBOL_LETTER_INTL' => 'GRO',
						),
						731 =>
						array(
							'CODE' => '731',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_BOL_SHOJ_GROSS_12_GROSSOV"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => '1728',
							'SYMBOL_LETTER_INTL' => 'GGR',
						),
						738 =>
						array(
							'CODE' => '738',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_KOROTKIJ_STANDART_7200_EDINITC"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => 'SST',
						),
						835 =>
						array(
							'CODE' => '835',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_GALLON_SPIRTA_USTANOVLENNOJ_KREPOSTI"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => 'PGL',
						),
						851 =>
						array(
							'CODE' => '851',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_MEZHDUNARODNAYA_EDINITCA"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => 'NIU',
						),
						853 =>
						array(
							'CODE' => '853',
							'MEASURE_TITLE' => Loc::getMessage("CAT_UC_STO_MEZHDUNARODNIH_EDINITC"),
							'SYMBOL_RUS' => NULL,
							'SYMBOL_INTL' => NULL,
							'SYMBOL_LETTER_INTL' => 'HIU',
						),
					),
				);
		}
	}

	/**
	 * @return array
	 */
	public static function getMeasureClassifier()
	{
		if (null === self::$unitsClassifier)
		{
			self::initMeasureClassifier();
		}
		return self::$unitsClassifier;
	}

	/**
	 * @deprecated deprecated since catalog 14.5.0
	 * @return array
	 */
	protected function measureStore()
	{
		if (null === self::$unitsClassifier)
		{
			self::initMeasureClassifier();
		}
		return self::$unitsClassifier;
	}
	/**
	 * @param $findId
	 * @param string $findValue
	 * @return string
	 */
	public static function getMeasureTitle($findId, $findValue = 'MEASURE_TITLE')
	{
		$findId = (int)$findId;
		$findValue = (string)$findValue;
		if ($findValue === '')
			$findValue = 'MEASURE_TITLE';
		if (0 < $findId)
		{
			self::initMeasureClassifier();
			foreach (self::$unitsClassifier as $subSection)
			{
				foreach ($subSection as $measureList)
				{
					if (!is_array($measureList))
						continue;
					if (
						isset($measureList[$findId])
						&& isset($measureList[$findId]['CODE'])
						&& (int)$measureList[$findId]['CODE'] === $findId
						&& isset($measureList[$findId][$findValue])
					)
					{
						return $measureList[$findId][$findValue];
					}
				}
			}
		}
		return '';
	}

	public static function getMeasureInfoByCode($findCode)
	{
		$result = null;
		$findCode = (int)$findCode;
		if (0 < $findCode)
		{
			self::initMeasureClassifier();
			foreach (self::$unitsClassifier as $subSection)
			{
				foreach ($subSection as $measureList)
				{
					if (!is_array($measureList))
						continue;
					if (isset($measureList[$findCode]) && isset($measureList[$findCode]['CODE']) && (int)$measureList[$findCode]['CODE'] === $findCode)
					{
						$result = $measureList[$findCode];
						break 2;
					}
				}
			}
		}
		return $result;
	}
}
?>