<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
#Debug('[comp/Tasks/Dispatch]: ' . print_r($Task,true));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# смотрим количество сообщений в очереди
$Config = &Config();
#-------------------------------------------------------------------------------
$Notifies = $Config['Notifies'];
#-------------------------------------------------------------------------------
$iWhere = Array();
#-------------------------------------------------------------------------------
foreach(Array_Keys($Notifies['Methods']) as $MethodID){
	if ($Notifies['Methods'][$MethodID]['IsActive'])
		$iWhere[] = SPrintF("`TypeID` = '%s'",$MethodID);
}
#-------------------------------------------------------------------------------
$Where = SPrintF("(%s) AND `IsExecuted` = 'no'",Implode(' OR ',$iWhere));
$Count = DB_Count('TasksOwners',Array('Where'=>$Where));
#-------------------------------------------------------------------------------
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($Count > 9)
	return (Time() + 60);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$SendToIDs = Explode(',',$Task['Params']['SendToIDs']);
$SendedIDs = IsSet($Task['Params']['SendedIDs'])?Explode(',',$Task['Params']['SendedIDs']):Array();
#-------------------------------------------------------------------------------
$Count = 0;
$Replace = Array('Theme'=>$Task['Params']['Theme'],'Message'=>$Task['Params']['Message']);
#-------------------------------------------------------------------------------
foreach($SendToIDs as $User){
	# пропускаем циклы, если счётчик уже больше 10
	if($Count > 9)
		continue;
	#-------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/Dispatch]: send message to UserID = %s;',$User));
	#-------------------------------------------------------------------------
	$msg = new DispatchMsg($Replace, (integer)$User, $Task['Params']['FromID']);
	$IsSend = NotificationManager::sendMsg($msg);
	#-------------------------------------------------------------------------
	switch(ValueOf($IsSend)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		# Исключение - системные юзеры, например...
		$SendedIDs[] = $User;
		Array_Shift($SendToIDs);
		break;
	case 'true':
		#-------------------------------------------------------------------------
		$Count++;
		$SendedIDs[] = $User;
		Array_Shift($SendToIDs);
		#-------------------------------------------------------------------------
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#Debug(SPrintF('[comp/Tasks/Dispatch]: SendToIDs = %s; SendedIDs = %s;',Implode(',',$SendToIDs),Implode(',',$SendedIDs)));
#-------------------------------------------------------------------------------
# сохраняем параметры задачи
$Task['Params']['SendToIDs'] = Implode(',',$SendToIDs);
$Task['Params']['SendedIDs'] = Implode(',',$SendedIDs);
$UTasks = Array('Params'=>$Task['Params']);
$IsUpdate = DB_Update('Tasks',$UTasks,Array('ID'=>$Task['ID']));
#-------------------------------------------------------------------------------
if(Is_Error($IsUpdate))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = Array(SPrintF('Sended: %u, estimated: %u, new: %u messages',SizeOf($SendedIDs),SizeOf($SendToIDs),$Count));
#-------------------------------------------------------------------------------
if(SizeOf($SendToIDs) > 0)
	return (Time() + 60);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------

?>
