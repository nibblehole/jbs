<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Order');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
#Debug(SPrintF('[comp/Triggers/GLOBAL]: ModeID = %s; StatusID = %s; Order = %s',$Order['ModeID'],$Order['StatusID'],print_r($Order['Row'],true)));
#-------------------------------------------------------------------------------
if(!IsSet($Order['Row']['ServerID'])){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Triggers/GLOBAL]: Для (%s->%s) не задан сервер',$Order['ModeID'],$Order['StatusID']));
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Null($Order['Row']['ServerID'])){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Triggers/GLOBAL]: Для (%s->%s) сервер = NULL',$Order['ModeID'],$Order['StatusID']));
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($Order['Row']['ServerID'] < 1){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Triggers/GLOBAL]: Для (%s->%s) ServerID = 0',$Order['ModeID'],$Order['StatusID']));
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# пробуем определить сервис, к которому относится услуга
$ServersGroup = DB_Select('ServersGroups','*',Array('UNIQ','Where'=>SPrintF('`ID` = (SELECT `ServersGroupID` FROM `Servers` WHERE `ID` = %u)',$Order['Row']['ServerID'])));
#-------------------------------------------------------------------------------
switch(ValueOf($ServersGroup)){
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
#Debug(SPrintF('[comp/Triggers/GLOBAL]: Params = %s',print_r($ServersGroup['Params'],true)));
#-------------------------------------------------------------------------------
if(!$ServersGroup['Params']['Count']){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Triggers/GLOBAL]: Для (%s->%s) не задано дополнительных сервисов, Count = 0 или не задан',$Order['ModeID'],$Order['StatusID']));
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Triggers/GLOBAL]: Для (%s->%s) необходимо активировать дополнительных услуг: %u',$Order['ModeID'],$Order['StatusID'],$ServersGroup['Params']['Count']));
#-------------------------------------------------------------------------------
for($i = 1; $i <= $ServersGroup['Params']['Count']; $i++){
	#-------------------------------------------------------------------------------
	$ServiceID	= (integer) @$ServersGroup['Params'][(SPrintF('Service%u',$i))];
	$SchemeID	= (integer) @$ServersGroup['Params'][(SPrintF('Scheme%u',$i))];
	$StatusID	=  (string) @$ServersGroup['Params'][(SPrintF('Status%u',$i))];
	$IsNoDuplicate	= (boolean) @$ServersGroup['Params'][(SPrintF('IsNoDuplicate%u',$i))];
	#-------------------------------------------------------------------------------
	if($StatusID != $Order['StatusID']){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Triggers/GLOBAL]: Услуга пропущена, не соответствует статус %s != %s',$StatusID,$Order['StatusID']));
		#-------------------------------------------------------------------------------
		continue;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Triggers/GLOBAL]: Создание заказа на услугу #%u; сервис = %u; тариф = %u; не дублировать = %s',$i,$ServiceID,$SchemeID,(($IsNoDuplicate)?'TRUE':'FALSE')));
	#-------------------------------------------------------------------------------
	$Service = DB_Select('Services',Array('*'),Array('UNIQ','ID'=>$ServiceID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($ServersGroup)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		#-------------------------------------------------------------------------------
		# такого сервиса нет. видимо, его уже удалили...
		Debug(SPrintF('[comp/Triggers/GLOBAL]: не найден сервис ServiceID = %u',$ServiceID));
		#-------------------------------------------------------------------------------
		continue 2;
		#-------------------------------------------------------------------------------
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if($Service['Code'] == 'Default'){
		#-------------------------------------------------------------------------------
		# TODO: надо сделать реализацию для услуг настраиваемых вручную....
		continue;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# проверяем наличие такой услуги у юзера
	$Where = Array(SprintF('`UserID` = %u',$Order['Row']['UserID']),SPrintF('`SchemeID` = %u',$SchemeID),'`StatusID` = "Active"');
	#-------------------------------------------------------------------------------
	$Count = DB_Count(SPrintF('%sOrdersOwners',$Service['Code']),Array('Where'=>$Where));
	if(Is_Error($Count))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	if($Count && $IsNoDuplicate){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Triggers/GLOBAL]: у клиента уже есть заказ на ServiceID = %u, SchemeID = %u',$ServiceID,$SchemeID));
		#-------------------------------------------------------------------------------
		continue;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# проверяем наличие такого тарифа для такой услуги, и его активность
	$Scheme = DB_Select(SPrintF('%sSchemes',$Service['Code']),'*',Array('UNIQ','ID'=>$SchemeID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Scheme)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		#-------------------------------------------------------------------------------
		# нет такого тарифа, видимо, удалён
		Debug(SPrintF('[comp/Triggers/GLOBAL]: у сервиса ServiceID = %u не найден тариф SchemeID = %u',$ServiceID,$SchemeID));
		#-------------------------------------------------------------------------------
		continue 2;
		#-------------------------------------------------------------------------------
	case 'array':
		#-------------------------------------------------------------------------------
		if(!$Scheme['IsActive']){
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/Triggers/GLOBAL]: у сервиса ServiceID = %u тариф SchemeID = %u неактивен, нельзя заказать',$ServiceID,$SchemeID));
			#-------------------------------------------------------------------------------
			continue 2;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# заказываем услугу
	$Path = SPrintF('www/API/%sOrder',$Service['Code']);
	#-------------------------------------------------------------------------------
	if(Is_Error(System_Element(SPrintF('comp/%s.comp.php',$Path)))){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Triggers/GLOBAL]: API для заказа сервиса не найдено: %s',$Path));
		#-------------------------------------------------------------------------------
		continue;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Array = Array('ContractID'=>$Order['Row']['ContractID'],SPrintF('%sSchemeID',$Service['Code'])=>$SchemeID,'Comment'=>SPrintF('Автоматическое создание услуги, группа серверов #%u, "%s"',$ServersGroup['ID'],$ServersGroup['Name']));
	#-------------------------------------------------------------------------------
	$AddOrder = Comp_Load($Path,$Array);
	#-------------------------------------------------------------------------------
	switch(ValueOf($AddOrder)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		# No more...
		continue 2;
	case 'array':
		#-------------------------------------------------------------------------------
		# оплачиваем услугу на минимальное число дней (или юзать параметры? или юзать срок оплаты основной услуги?)
		$Path = SPrintF('www/API/%sOrderPay',$Service['Code']);
		#-------------------------------------------------------------------------------
		if(Is_Error(System_Element(SPrintF('comp/%s.comp.php',$Path)))){
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/Triggers/GLOBAL]: API для заказа сервиса не найдено: %s',$Path));
			#-------------------------------------------------------------------------------
			continue 2;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$OrderID = SPrintF('%sOrderID',$Service['Code']);
		#-------------------------------------------------------------------------------
		$Array = Array($OrderID=>$AddOrder[$OrderID],'DaysPay'=>$Scheme['MinDaysPay'],'IsNoBasket'=>TRUE,'PayMessage'=>SPrintF('Автоматическая оплата зависимой услуги, группа серверов #%u, "%s"',$ServersGroup['ID'],$ServersGroup['Name']));
		#-------------------------------------------------------------------------------
		$OrderPay = Comp_Load($Path,$Array);
		#-------------------------------------------------------------------------------
		switch(ValueOf($OrderPay)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			# No more...
			continue 3;
		case 'array':
			break;
		default:
			return ERROR | @Trigger_Error(101);
		}
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
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------

?>