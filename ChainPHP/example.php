<?php
	require('ChainPHP.class.php');
	require('ExtendedChainPHP.class.php');

	//using passthru to pass the previous call's result to the next call
	$o = new ChainPHP();
	print $o->_append(array('hello', 'i', 'i', 'like', 'pie'))->_passthru(0, true)->array_unique()->implode(' ')->ucwords()->_result()->current();
	
	//manually passing the previous call's result to the next call
	$o = new ChainPHP();
	$o->_append(array('hello', 'i', 'i', 'like', 'pie'))
	    ->array_unique($o->_result()->current())
	    ->implode(' ', $o->_result()->current())
	    ->ucwords($o->_result()->current())
	;
	print $o->_result()->current();
	
	//extended chaining using the array and echo functions
	$o = new ExtendedChainPHP();
	$o->_array(1,2,3,4,5)->_passthru(0, true)->array_reverse()->array_slice(2)->print_r();
	$o->_clear()->_append('hello world')->_passthru(0, true)->strrev()->substr(2)->_echo(); 
	$o->md5()->_echo();
	
	//a single passthru call that passes the value as the second argument
	$o->_clear();
	print $o->_append(time())->_passthru(1)->date('Y-m-d H:i:s')->_result()->current();
?>