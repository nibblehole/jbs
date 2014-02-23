<?php

#-------------------------------------------------------------------------------
/** @author Rootden for Lowhosting.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task', 'Mobile', 'Message', 'UserID', 'ChargeFree', 'IsImmediately');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('libs/Server.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$ServerSettings = SelectServerSettingsByTemplate('SMS');
#-------------------------------------------------------------------------------
switch(ValueOf($ServerSettings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'] = 'server with template: SMS, params: IsActive, IsDefault not found';
	#-------------------------------------------------------------------------------
	if(IsSet($GLOBALS['IsCron']))
		return 3600;
	#-------------------------------------------------------------------------------
	return $ServerSettings;
	#-------------------------------------------------------------------------------
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверяем, можно ли отправлять в заданное время
$User = DB_Select('Users', Array('MobileConfirmed','GroupID','Params'), Array('UNIQ', 'ID' => $UserID));
if(!Is_Array($User))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$IsImmediately = (IsSet($IsImmediately)?$IsImmediately:FALSE);
#-------------------------------------------------------------------------------
$TransferTime = FALSE;
#-------------------------------------------------------------------------------
# возможно, параметры не заданы/требуется немедленная отправка - время не опредлеяем
if(IsSet($User['Params']['SMSTime']) && !$IsImmediately){
	#-------------------------------------------------------------------------------
	$SMSTime = $User['Params']['SMSTime'];
	#-------------------------------------------------------------------------------
	# время окончания, если оно 0:00 - это больше чем 23:00, например... надо 0->24
	$SMSTime['SMSEndTime'] = (($SMSTime['SMSEndTime'] == 0)?24:$SMSTime['SMSEndTime']);
	#-------------------------------------------------------------------------------
	if(IsSet($SMSTime['SMSBeginTime']) && IsSet($SMSTime['SMSEndTime']) && $SMSTime['SMSBeginTime'] != $SMSTime['SMSEndTime']){
		#-------------------------------------------------------------------------------
		# если обычный период, например 9:00-18:00
		if($SMSTime['SMSBeginTime'] < $SMSTime['SMSEndTime']){
			#-------------------------------------------------------------------------------
			if(Date('G') >= $SMSTime['SMSBeginTime'] && Date('G') < $SMSTime['SMSEndTime']){
				# OK
			}else{
				#-------------------------------------------------------------------------------
				if(Date('G') < $SMSTime['SMSBeginTime']){
					#-------------------------------------------------------------------------------
					# сегодня попзже
					$TransferTime = MkTime($SMSTime['SMSBeginTime'],0,0,Date('n'),Date('j'),Date('Y'));
					Debug(SPrintF('[comp/Tasks/SMS]: Перенос отправки сообщения (%u) на %s',$Mobile,Date('Y-m-d/H:i:s',$TransferTime)));
					#-------------------------------------------------------------------------------
				}else{
					#-------------------------------------------------------------------------------
					# завтра пораньше
					$TransferTime = MkTime($SMSTime['SMSBeginTime'],0,0,Date('n'),Date('j')+1,Date('Y'));
					Debug(SPrintF('[comp/Tasks/SMS]: Перенос отправки сообщения (%u) на завтра, %s',$Mobile,Date('Y-m-d/H:i:s',$TransferTime)));
					#-------------------------------------------------------------------------------
				}
			}
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			# период типа 21:00-8:00
			if(Date('G') < $SMSTime['SMSBeginTime'] && Date('G') >= $SMSTime['SMSEndTime']){
				#-------------------------------------------------------------------------------
				# время типа 12:00 - требуется перенос на SMSBeginTime, сегодня
				$TransferTime = MkTime($SMSTime['SMSBeginTime'],0,0,Date('n'),Date('j'),Date('Y'));
				Debug(SPrintF('[comp/Tasks/SMS]: Перенос отправки сообщения (%u) на %s', $Mobile,Date('Y-m-d/H:i:s',$TransferTime)));
				#-------------------------------------------------------------------------------
			}else{
				# OK
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
if($TransferTime){
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'] = SPrintF("transfer send to %s",Date('Y-m-d/H:i:s',$TransferTime));
	#-------------------------------------------------------------------------------
	$Event = Array('UserID' => $UserID, 'PriorityID' => 'Billing', 'Text' => SPrintF('Отправка SMS сообщения для номера (%s) перенесена на (%s), согласно клиентским настройкам',$Mobile,Date('Y-m-d/H:i:s',$TransferTime)));
	$Event = Comp_Load('Events/EventInsert', $Event);
	if(!$Event)
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	return $TransferTime;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Tasks/SMS]: отправка SMS сообщения для (%u)', $Mobile));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Message = Trim($Message);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = $Mobile;
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Notifies']['Settings']['SMSGateway'];
#-------------------------------------------------------------------------------
if(!IsSet($ServerSettings['Params']['Provider']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($ServerSettings['Params']['Provider'] == 'SMSpilot' && !IsSet($ServerSettings['Params']['ApiKey']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!IsSet($Settings['SMSLogin']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!IsSet($Settings['SMSPassword']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!IsSet($ServerSettings['Params']['Sender']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!IsSet($Settings['SMSExceptions']['SMSExceptionsPaidInvoices']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!IsSet($Settings['SMSExceptions']['SMSExceptionsSchemeID']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// Если пользователь относится к группе 'Сотрудники' то плату не взымаем...
# TODO: однако, надо через Tree_Entrance('Groups',3000000) искать
#-------------------------------------------------------------------------------
if($User['GroupID'] == '3000000')
	$ChargeFree = TRUE;
#-------------------------------------------------------------------------------
// Проверяем пользователя на исключения оплаты, сумма оплаченных счетов.
#-------------------------------------------------------------------------------
if($Settings['SMSExceptions']['SMSExceptionsPaidInvoices'] >= 0){
	#-------------------------------------------------------------------------------
	$IsSelect = DB_Select('InvoicesOwners','SUM(`Summ`) AS `Summ`',Array('UNIQ','Where'=>SPrintF('`UserID` = %u AND `IsPosted` = "yes"',$UserID)));
	switch(ValueOf($IsSelect)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		#-------------------------------------------------------------------------------
		if($IsSelect['Summ'] >= $Settings['SMSExceptions']['SMSExceptionsPaidInvoices'])
			$ChargeFree = true;
			//Debug(SPrintF('[comp/Tasks/SMS]: Оплаченных счетов (%s)', $IsSelect['Summ']));
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(100);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// Проверяем пользователя на исключения оплаты, активные заказы хостинга.
// мегакостыль =) // commented by lissyara, 2013-06-01 in 15:47 MSK
#-------------------------------------------------------------------------------
if($Settings['SMSExceptions']['SMSExceptionsSchemeID'] != 0){
	#-------------------------------------------------------------------------------
	$OrderHostings = DB_Select('HostingOrdersOwners', 'SchemeID', Array('Where' => SPrintF('`UserID` = %u AND `StatusID` = "Active"', $UserID)));
	if (Is_Error($OrderHostings))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$LimitSchemeID = Explode(',',$Settings['SMSExceptions']['SMSExceptionsSchemeID']);
	foreach($OrderHostings as $OrderHosting){
		if(In_Array((integer) $OrderHosting['SchemeID'], $LimitSchemeID)){
			$ChargeFree = true;
			break;
		}
	}
	#-------------------------------------------------------------------------------
	//Debug(print_r($LimitSchemeID, true));
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$MessageLength = MB_StrLen($Message);
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Tasks/SMS]: длинна: %s, сообщение (%s)',$MessageLength,$Message));
Debug(SPrintF('[comp/Tasks/SMS]: SMS шлюз (%s)', $ServerSettings['Params']['Provider']));
#Debug(SPrintF('[comp/Tasks/SMS]: API ключ (%s)', $ServerSettings['Params']['ApiKey']));
Debug(SPrintF('[comp/Tasks/SMS]: Отправитель (%s)', $ServerSettings['Params']['Sender']));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if (Is_Error(System_Load(SPrintF('classes/%s.class.php', $ServerSettings['Params']['Provider']))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Regulars = Regulars();
$MobileCountry = 'SMSPriceDefault';
$RegCountrys = array('SMSPriceRu' => $Regulars['SMSPriceRu'], 'SMSPriceUa' => $Regulars['SMSPriceUa'], 'SMSPriceSng' => $Regulars['SMSPriceSng'], 'SMSPriceZone1' => $Regulars['SMSPriceZone1'], 'SMSPriceZone2' => $Regulars['SMSPriceZone2']);
#-------------------------------------------------------------------------------
foreach ($RegCountrys as $RegCountryKey => $RegCountry)
	if (Preg_Match($RegCountry, $Mobile))
		$MobileCountry = $RegCountryKey;
Debug(SPrintF('[comp/Tasks/SMS]: Страна определена (%s)', $MobileCountry));
#-------------------------------------------------------------------------------
if (!IsSet($Settings['SMSPrice'][$MobileCountry]))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($MessageLength <= 70){
	#-------------------------------------------------------------------------------
	$SMSCost = Str_Replace(',', '.', $Settings['SMSPrice'][$MobileCountry]);
	$SMSCount = 1;
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$SMSCount = Ceil($MessageLength / 67);
	#-------------------------------------------------------------------------------
	# сообщение не может быть больше 10 частей... на самом деле, например у меня 
	# телефон поддерживает максимум 6 частей...
	if($SMSCount > 10){
		Debug(SPrintF('[comp/Tasks/SMS]: Слишком длинное сообщеие (%s частей), не отправлено', $SMSCount));
		return TRUE;
	}
	#-------------------------------------------------------------------------------
	$SMSCost = $SMSCount * Str_Replace(',', '.', $Settings['SMSPrice'][$MobileCountry]);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($ChargeFree)
	$SMSCost = 0;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Currency',$SMSCost);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Tasks/SMS]: Стоимость сообщения (%s) всего частей (%s)', $Comp, $SMSCount));
#-------------------------------------------------------------------------------
if (!Is_Numeric($SMSCost))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------
if ($SMSCost > 0){
	#-------------------------------------------------------------------------------
	$Where = Array(
			SPrintF('`UserID` = %u', $UserID),
			SPrintF('`Balance` >= %s', $SMSCost),
			'`TypeID` != "NaturalPartner"',
			);
	#-------------------------------------------------------------------------------
	$Contract = DB_Select('Contracts', Array('TypeID', 'ID', 'Balance'), Array('UNIQ','Where'=>$Where,'Limits'=>Array('Start'=>0,'Length'=>1)));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Contract)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		#-------------------------------------------------------------------------------
		# нет денег
		break;
		#-------------------------------------------------------------------------------
	case 'array':
		#-------------------------------------------------------------------------------
		$ContractID = $Contract['ID'];
		(integer) $After = $Contract['Balance'] - $SMSCost;
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(100);
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(!IsSet($ContractID) && !IsSet($After)){
		#-------------------------------------------------------------------------------
		Debug("[comp/Tasks/SMS]: Недостаточно денежных средств на любом договоре клиента");
		if($Config['Notifies']['Methods']['SMS']['IsEvent']){
			#-------------------------------------------------------------------------------
			$Event = Array('UserID' => $UserID, 'PriorityID' => 'Error', 'Text' => SPrintF('Не удалось отправить SMS сообщение для (%s), недостаточно денежных средств на любом договоре клиента', $Mobile));
			$Event = Comp_Load('Events/EventInsert', $Event);
			if(!$Event)
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		if(Is_Null($Task))
			return SPrintF('Недостаточно денежных средств на вашем балансе. Стоимость сообщения: %s',$SMSCost);
		#-------------------------------------------------------------------------------
		return TRUE;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Links = &Links();
#-------------------------------------------------------------------------------
$LinkID = Md5($ServerSettings['Params']['Provider']);
#-------------------------------------------------------------------------------
if(!IsSet($Links[$LinkID])){
	#-------------------------------------------------------------------------------
	$Links[$LinkID] = NULL;
	#-------------------------------------------------------------------------------
	$SMS = &$Links[$LinkID];
	#-------------------------------------------------------------------------------
	$SMS = new $ServerSettings['Params']['Provider']($ServerSettings['Login'],$ServerSettings['Password'],$ServerSettings['Params']['ApiKey'],$ServerSettings['Params']['Sender']);
	if (Is_Error($SMS))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$IsAuth = $SMS->balance();
	switch (ValueOf($IsAuth)) {
	case 'false':
		#-------------------------------------------------------------------------------
		Debug("[comp/Tasks/SMS]: Подключаемся и получаем баланс -> Error:'".$SMS->error."'");
		if($Config['Notifies']['Methods']['SMS']['IsEvent']){
			#-------------------------------------------------------------------------------
			$Event = Array('UserID' => $UserID, 'PriorityID' => 'Error', 'Text' => SPrintF('Не удалось отправить SMS сообщение для (%s), %s', $Mobile, 'шлюз временно недоступен.'));
			$Event = Comp_Load('Events/EventInsert', $Event);
			if(!$Event)
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		UnSet($Links[$LinkID]);
		#-------------------------------------------------------------------------------
		if(Is_Null($Task))
			return "Пожалуйста, попробуйте повторить попытку позже";
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		return TRUE;
		#-------------------------------------------------------------------------------
	case 'true':
		#-------------------------------------------------------------------------------
		Debug("[comp/Tasks/SMS]: Подключаемся и получаем баланс: '".$SMS->balance."'");
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	// Проверим баланс и отложим задачу в случае нехватки кредитов
	#-------------------------------------------------------------------------------
	$SMSBalanse = (integer) $SMS->balance;
	if ($SMSBalanse == 0 || $SMSBalanse < $SMSCost) {
		#-------------------------------------------------------------------------------
		if ($Config['Notifies']['Methods']['SMS']['IsEvent']) {
			#-------------------------------------------------------------------------------
			$Event = Array('UserID' => $UserID, 'PriorityID' => 'Error', 'Text' => SPrintF('Не удалось отправить SMS сообщение для (%s), %s', $Mobile, 'временно нет средств на шлюзе.'));
			$Event = Comp_Load('Events/EventInsert', $Event);
			if (!$Event)
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		if(Is_Null($Task))
			return "Пожалуйста, попробуйте повторить попытку позже";
		#-------------------------------------------------------------------------------
		UnSet($Links[$LinkID]);
		return 3600;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$SMS = &$Links[$LinkID];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IsMessage = $SMS->send((integer) $Mobile, $Message,$ServerSettings['Params']['Sender']);
switch (ValueOf($IsMessage)) {
case 'false':
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/SMS]: Неудачно, ошибка: "%s"',$SMS->error));
	#-------------------------------------------------------------------------------
	if ($Config['Notifies']['Methods']['SMS']['IsEvent']) {
		#-------------------------------------------------------------------------------
		$Event = Array('UserID' => $UserID,'PriorityID' => 'Error','Text' => SPrintF('Не удалось отправить SMS сообщение для (%s), %s', $Mobile, 'шлюз временно недоступен.'));
		$Event = Comp_Load('Events/EventInsert', $Event);
		if (!$Event)
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	if(Is_Null($Task))
		return 'Пожалуйста, попробуйте повторить попытку позже';
	#-------------------------------------------------------------------------------
	UnSet($Links[$LinkID]);
	return 3600;
	#-------------------------------------------------------------------------------
case 'true':
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/SMS]: Отправка успешна, ответ шлюза: %s',$SMS->success));
	#-------------------------------------------------------------------------------
	if(!$ChargeFree && IsSet($After)){
		#------------------------------TRANSACTION--------------------------------------
		if (Is_Error(DB_Transaction($TransactionID = UniqID('PostingSMS'))))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$IsUpdated = DB_Update('Contracts', Array('Balance' => $After), Array('ID' => $ContractID));
		if (Is_Error($IsUpdated))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$IPosting = Array(
					'ContractID'	=> $ContractID,
					'ServiceID'	=> '2000',
					'Comment'	=> "SMS уведомление ($SMSCount шт)",
					'Before'	=> $Contract['Balance'],
					'After'		=> $After
				);
		#-------------------------------------------------------------------------------
		$PostingID = DB_Insert('Postings', $IPosting);
		if (Is_Error($PostingID))
		return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		if (Is_Error(DB_Commit($TransactionID)))
			return ERROR | @Trigger_Error(500);
		#-------------------------END TRANSACTION---------------------------------------
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Formats/Currency',$Contract['Balance']);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Comp1 = Comp_Load('Formats/Currency',$After);
		if(Is_Error($Comp1))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Tasks/SMS]: Договор (%s) баланс до оплаты (%s) после оплаты (%s)', $ContractID, $Comp, $Comp1));
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
if (!$Config['Notifies']['Methods']['SMS']['IsEvent'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Event = Array('UserID'=>$UserID,'Text'=>SPrintF('SMS сообщение для (%s) успешно отправлено', $Mobile));
#-------------------------------------------------------------------------------
$Event = Comp_Load('Events/EventInsert', $Event);
#-------------------------------------------------------------------------------
if (!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
?>
