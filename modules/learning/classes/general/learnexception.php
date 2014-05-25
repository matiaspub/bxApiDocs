<?php

class LearnException extends Exception
{
	const EXC_ERR_ALL_LOGIC         = 0x0000100;	// Logic error
	const EXC_ERR_ALL_GIVEUP        = 0x0001000;	// Any fatal error, on which called method gives up
	const EXC_ERR_ALL_PARAMS        = 0x0002000;	// At least one of params is out of admitted range
	const EXC_ERR_ALL_ACCESS_DENIED = 0x0010000;	// Access denied during some operation
	const EXC_ERR_ALL_NOT_EXISTS    = 0x0020000;	// Item not exists

	// Graph relations
	const EXC_ERR_GR_LINK           = 0x0000001;	// GraphRelation error: unable to link edge
	const EXC_ERR_GR_UNLINK         = 0x0000002;	// GraphRelation error: unable to unlink edge
	const EXC_ERR_GR_SET_PROPERTY   = 0x0000004;	// GraphRelation error: unable to set property
	const EXC_ERR_GR_GET_PROPERTY   = 0x0000008;	// GraphRelation error: unable to get property value
	const EXC_ERR_GR_GET_NEIGHBOURS = 0x0000800;	// GraphRelation error: unable to list neighbours
	const EXC_ERR_GR_UPDATE         = 0x0004000;	// GraphRelation error: unable to update link parametres

	// Graph nodes
	const EXC_ERR_GN_CREATE         = 0x0000010;	// GraphNode error: unable to create graph node
	const EXC_ERR_GN_UPDATE         = 0x0000080;	// GraphNode error: unable to update graph node
	const EXC_ERR_GN_REMOVE         = 0x0000200;	// GraphNode error: unable to remove graph node
	const EXC_ERR_GN_GETBYID        = 0x0000400;	// GraphNode error: unable to get graph node by id
	const EXC_ERR_GN_CHECK_PARAMS   = 0x0000020;	// GraphNode error: failed when params checked
	const EXC_ERR_GN_FILE_UPLOAD    = 0x0000040;	// GraphNode error: file uploading failure

	// CLearnPath
	const EXC_ERR_LP_BROKEN_PATH    = 0x0008000;	// broken path

	// CLearnLesson
	const EXC_ERR_LL_UNREMOVABLE_CL = 0x0040000;	// lesson is unremovable, because linked course is unremovable
	// nextID = 0x0080000

	// Redefine the exception to log exceptions
		public function __construct($message = null, $code = 0)
		{
			//$trace = debug_backtrace();
			// $trace = $this->trace;
			//$this->learning_log_exception ($message, $code, $this->line, $this->file, $trace);
		
				// make sure everything is assigned properly
				parent::__construct($message, $code);
		}

		protected function learning_log_exception ($message, $code, $line, $file, $backtrace)
		{
				if ( ! method_exists('CDatabase', 'Query') )
					return;

				global $DB;

				if ( ! (is_object($DB) && method_exists($DB, 'Query')) )
					return;

				if ( ! $DB->TableExists('b_learn_exceptions_log') )
					return;

				$DB->Query (
					"INSERT INTO b_learn_exceptions_log
					(DATE_REGISTERED, CODE, MESSAGE, FFILE, LINE, BACKTRACE)
					VALUES (" . CDatabase::GetNowFunction() . ", " . (int) $code . ", '" 
						. CDatabase::ForSQL($message) . "', '" . CDatabase::ForSQL($file) . "', " 
						. (int) $line . ",'" 
						. CDatabase::ForSQL(base64_encode(serialize($backtrace))) // due to charsets problems do base64_encode()
						. "')
					", 
					true);
		}
}
