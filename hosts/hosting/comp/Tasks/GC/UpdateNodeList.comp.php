<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('classes/VPSServer.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Tasks']['Types']['GC']['UpdateNodeListSettings'];
#-------------------------------------------------------------------------------
if(!$Settings['IsActive'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Servers = DB_Select('Servers',Array('ID','Address','Params'),Array('Where'=>'(SELECT `ServiceID` FROM `ServersGroups` WHERE `ServersGroups`.`ID` = `Servers`.`ServersGroupID`) = 30000','SortOn'=>'Address'));
#-------------------------------------------------------------------------------
switch(ValueOf($Servers)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return TRUE;
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
foreach($Servers as $iServer){
	#-------------------------------------------------------------------------------
	if(!$iServer['Params']['IsUpdateNodeList'])
		continue;
	#-------------------------------------------------------------------------------
	$VPSServer = new VPSServer();
	#-------------------------------------------------------------------------------
	$IsSelected = $VPSServer->Select((integer)$iServer['ID']);
	#-------------------------------------------------------------------------------
	switch(ValueOf($IsSelected)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'true':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$NodeList = $VPSServer->GetNodeList();
	#-------------------------------------------------------------------------------
	switch(ValueOf($NodeList)){
	case 'error':
		# No more...
		break;
	case 'exception':
		# No more...
		break;
	case 'array':
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Tasks/GC/UpdateNodeList]: NodeList = %s',print_r($NodeList,true)));
		#-------------------------------------------------------------------------------
		if(SizeOf($NodeList) < 1)
			continue 2;
		#-------------------------------------------------------------------------------
		$iServer['Params']['NodeList'] = Implode("\n",Array_Keys($NodeList));
		#-------------------------------------------------------------------------------
		$IsUpdate = DB_Update('Servers',Array('Params'=>$iServer['Params']),Array('ID'=>$iServer['ID']));
		if(Is_Error($IsUpdate))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>