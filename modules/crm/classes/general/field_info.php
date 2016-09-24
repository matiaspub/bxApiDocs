<?php
class CCrmFieldInfoAttr
{
	const Undefined = '';
	const Hidden = 'HID';
	const ReadOnly = 'R-O';
	const Immutable = 'IM'; //User can define field value only on create
	const UserPKey = 'UPK'; //User defined primary key (currency alpha code for example)
	const Required = 'REQ';
	const Multiple = 'MUL';
	const Dynamic = 'DYN';
	const Deprecated = 'DEP';
}

