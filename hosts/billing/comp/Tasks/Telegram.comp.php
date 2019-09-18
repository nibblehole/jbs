<?php
#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','Address','Message','Attribs');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
// возможно, параметры не заданы/требуется немедленная отправка - время не опредлеяем
if(!IsSet($Attribs['IsImmediately']) || !$Attribs['IsImmediately']){
	#-------------------------------------------------------------------------------
	// проверяем, можно ли отправлять в заданное время
	$TransferTime = Comp_Load('Formats/Task/TransferTime',$Attribs['UserID'],$Address,'Telegram',$Attribs['TimeBegin'],$Attribs['TimeEnd']);
	#-------------------------------------------------------------------------------
	switch(ValueOf($TransferTime)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'integer':
		return $TransferTime;
	case 'false':
		break;
	default:
		return ERROR | @Trigger_Error(100);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Tasks/Telegram]: отправка Telegram сообщения для (%s)', $Address));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('libs/HTTP.php','libs/Telegram.php','libs/Server.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Settings = SelectServerSettingsByTemplate('Telegram');
#-------------------------------------------------------------------------------
switch(ValueOf($Settings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'] = 'server with template: Telegram, params: IsActive, IsDefault not found';
	#-------------------------------------------------------------------------------
	if(IsSet($GLOBALS['IsCron']))
		return 3600;
	#-------------------------------------------------------------------------------
	return $Settings;
	#-------------------------------------------------------------------------------
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($TgMessageIDs = TgSendMessage($Settings,$Attribs['ExternalID'],$Message,(IsSet($Attribs['MessageID'])?TRUE:FALSE))){
	#-------------------------------------------------------------------------------
	// если есть идентфикатор сообщения - сохраняем сооветствие
	if(IsSet($Attribs['MessageID']))
		foreach($TgMessageIDs as $TgMessageID)
			if(!TgSaveThreadID($Attribs['MessageID'],$TgMessageID))
				return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	// если не отправилось, ждём час и пробуем снова
	return 3600;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# достаём данные юзера которому идёт письмо
$User = DB_Select('Users',Array('ID','Params','Email'),Array('UNIQ','ID'=>$Attribs['UserID']));
if(!Is_Array($User))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($User['Params']['Settings']['SendEdeskFilesToTelegram'] == "Yes"){
	#-------------------------------------------------------------------------------
	if($TgMessageIDs = TgSendFile($Settings,$Attribs['ExternalID'],Is_Array($Attribs['Attachments'])?$Attribs['Attachments']:Array(),(IsSet($Attribs['MessageID'])?TRUE:FALSE))){
		#-------------------------------------------------------------------------------
		// если есть идентфикатор сообщения - сохраняем сооветствие
		if(IsSet($Attribs['MessageID']))
			foreach($TgMessageIDs as $TgMessageID)
				if(!TgSaveThreadID($Attribs['MessageID'],$TgMessageID))
					return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Tasks/Telegram]: не удалось отправить файл в Telegram'));
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
if(!$Config['Notifies']['Methods']['Telegram']['IsEvent'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Event = Comp_Load('Events/EventInsert', Array('UserID'=>$Attribs['UserID'],'Text'=>SPrintF('Сообщение для (%s) через службу Telegram отправлено', $Address)));
if(!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'][$User['Email']]	= Array($Address,$Attribs['ExternalID']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
