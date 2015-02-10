<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = IsSet($Args)?$Args:Args();
#-------------------------------------------------------------------------------
$DomainName = (string) @$Args['DomainName'];
$DomainZone = (string) @$Args['DomainZone'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('libs/WhoIs.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$IsCheck = WhoIs_Check($DomainName,$DomainZone);
#-------------------------------------------------------------------------------
switch(ValueOf($IsCheck)){
case 'error':
	return Array('Status'=>'Fail');
case 'exception':
	return $IsCheck;
case 'false':
	return new gException('DOMAIN_ZONE_NOT_SUPPORTED','Доменная зона не поддерживается');
case 'array':
	#-------------------------------------------------------------------------------
	$IsCheck['Status'] = 'Borrowed';
	#-------------------------------------------------------------------------------
	return $IsCheck;
	#-------------------------------------------------------------------------------
case 'true':
	#-------------------------------------------------------------------------------
	return Array('Status'=>'Free');
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
