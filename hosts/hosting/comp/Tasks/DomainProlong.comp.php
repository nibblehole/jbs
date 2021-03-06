<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','DomainOrderID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('classes/DomainServer.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Columns = Array(
		'DomainName','PersonID','DomainID','StatusID','ServerID',
		'(SELECT `Name` FROM `DomainSchemes` WHERE `DomainSchemes`.`ID` = `DomainOrdersOwners`.`SchemeID`) as `DomainZone`',
		'(SELECT SUM(`YearsRemainded`) FROM `DomainConsider` WHERE `DomainConsider`.`DomainOrderID` = `DomainOrdersOwners`.`ID`) as `YearsRemainded`',
		'(SELECT `Params` FROM `Servers` WHERE `Servers`.`ID` = `DomainOrdersOwners`.`ServerID`) AS `Params`'
		);
#-------------------------------------------------------------------------------
$DomainOrder = DB_Select('DomainOrdersOwners',$Columns,Array('UNIQ','ID'=>$DomainOrderID));
#-------------------------------------------------------------------------------
switch(ValueOf($DomainOrder)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Server = new DomainServer();
#-------------------------------------------------------------------------------
$IsSelected = $Server->Select((integer)$DomainOrder['ServerID']);
#-------------------------------------------------------------------------------
switch(ValueOf($IsSelected)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('TRANSFER_TO_OPERATOR','Задание не может быть выполнено автоматически и передано оператору');
case 'true':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = Array(($DomainOrder['Params']['Name'])=>Array(SPrintF('%s.%s',$DomainOrder['DomainName'],$DomainOrder['DomainZone'])));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
switch($DomainOrder['StatusID']){
case 'ForProlong':
	#-------------------------------------------------------------------------------
	$IsProlong = $Server->DomainProlong($DomainOrder['DomainName'],$DomainOrder['DomainZone'],(integer)$DomainOrder['YearsRemainded'],$DomainOrder['PersonID'],$DomainOrder['DomainID']);
	#-------------------------------------------------------------------------------
	switch(ValueOf($IsProlong)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return new gException('TRANSFER_TO_OPERATOR','Задание не может быть выполнено автоматически и передано оператору');
	case 'false':
		return 300;
	case 'array':
		#-------------------------------------------------------------------------------
		$Task['Params']['TicketID'] = $IsProlong['TicketID'];
		#-------------------------------------------------------------------------------
		$IsUpdate = DB_Update('Tasks',Array('Params'=>$Task['Params']),Array('ID'=>$Task['ID']));
		if(Is_Error($IsUpdate))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>'DomainOrders','StatusID'=>'OnProlong','RowsIDs'=>$DomainOrderID,'Comment'=>'Регистратор принял заявку на продление доменного имени'));
		#-------------------------------------------------------------------------------
		switch(ValueOf($Comp)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			return ERROR | @Trigger_Error(400);
		case 'array':
			return 300;
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
case 'OnProlong':
	#-------------------------------------------------------------------------------
	$TicketID = $Task['Params']['TicketID'];
	#-------------------------------------------------------------------------------
	$IsDomainProlong = $Server->CheckTask($TicketID);
	#-------------------------------------------------------------------------------
	switch(ValueOf($IsDomainProlong)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return new gException('TRANSFER_TO_OPERATOR','Задание не может быть выполнено автоматически и передано оператору');
	case 'false':
		return 300;
	case 'array':
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>'DomainOrders','StatusID'=>'Active','RowsIDs'=>$DomainOrderID,'Comment'=>'Доменное имя продлено'));
		#-------------------------------------------------------------------------------
		switch(ValueOf($Comp)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			return ERROR | @Trigger_Error(400);
		case 'array':
			return TRUE;
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
default:
	return new gException('WRONG_STATUS','Задание не может быть в данном статусе');

}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
