<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = IsSet($Args)?$Args:Args();
#-------------------------------------------------------------------------------
$Secret		=  (string) @$Args['Secret'];	// секретный ключ
// эти данные используются при отправке
$UserID		= (integer) @$Args['UserID'];	// идентификатор юзера в телеграмме
$Text		=  (string) @$Args['Text'];	// текст сообщения
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('libs/HTTP.php','libs/Server.php','libs/Telegram.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = SelectServerSettingsByTemplate('Telegram');
#-------------------------------------------------------------------------------
switch(ValueOf($Settings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('NO_TELEGRAM_SERVERS','Отсуствует настроенный сервер Telegramm');
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// проверяем секретный ключ, убеждаемся что сообщение пришло от телеграмма
if(!$Secret || $Secret != $Settings['Params']['Secret'])
	return new gException('SECRET_KEY_NOT_MATCH','Секретный ключ не совпадает с ключом из настроек');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// телега на вебхук шлёт данные постом, json. читаем их.
$Data = Json_Decode(File_Get_Contents('php://input'));
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/www/API/Telegramm]: Data = %s',print_r($Data,true)));
#-------------------------------------------------------------------------------
$Data = $Data->{'message'};
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// достаём сообщение или примечание, если оно есть
if(IsSet($Data->{'text'})){
	#-------------------------------------------------------------------------------
	$Message = $Data->{'text'};
	#-------------------------------------------------------------------------------
}elseif(IsSet($Data->{'caption'})){
	#-------------------------------------------------------------------------------
	$Message = $Data->{'caption'};
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Message = 'сообщение без текста';
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$ChatID = IntVal($Data->{'chat'}->{'id'}); // вернет ID отправителя
#-------------------------------------------------------------------------------
if(!$ChatID)
	return new gException('NO_CHAT_ID','Не удалось определить отправителя сообщения');
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/www/API/Telegramm]: входящее сообщение от ChatID = %u',$ChatID));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// если в сообщении написано /start - выводим подсказку
if($Message == '/start'){
	#-------------------------------------------------------------------------------
	if(!TgSendMessage($Settings,$ChatID,SPrintF($Settings['Params']['StartMessage'],$Settings['Params']['BotName'])))
		return new gException('ERROR_SEND_START_MESSAGE','Ошибка отправки стартового сообщения на сервер Telegramm');
	#-------------------------------------------------------------------------------
	return Array('Status'=>'Ok');
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// возможно это - ответ на сообщение. тогда проверяем идентфикатор на который ответ
if(IsSet($Data->{'reply_to_message'}->{'message_id'})){
	#-------------------------------------------------------------------------------
	//$Message = SPrintF("%s\n\n[hidden]Data = %s[/hidden]",Trim($Message),print_r($Data,true));
	#-------------------------------------------------------------------------------
	$Message = SPrintF("%s\n\n[hidden]posted via Telegramm, chat_id = %s[/hidden]",Trim($Message),$ChatID);
	#-------------------------------------------------------------------------------
	$ReplyToID = $Data->{'reply_to_message'}->{'message_id'};
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/API/Telegramm]: ответ на сообщение = %u',$ReplyToID));
	#-------------------------------------------------------------------------------
	// проверяем соответствие телеграммовского идентфикатора и нашего
	if($MessageID = TgFindThreadID($ReplyToID)){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/www/API/Telegramm]: reply_to_message->message_id = %u; MessageID = %u',$ReplyToID,$MessageID));
		#-------------------------------------------------------------------------------
		// проверяем наличие такого тикета
		$Columns = Array('*','(SELECT `UserID` FROM `Edesks` WHERE `EdesksMessagesOwners`.`EdeskID` = `Edesks`.`ID`) AS `EdeskUserID`');
		#-------------------------------------------------------------------------------
		$Edesk = DB_Select('EdesksMessagesOwners',$Columns,Array('UNIQ','ID'=>$MessageID));
		switch(ValueOf($Edesk)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/www/API/Telegramm]: НЕ найдено: EdeskID = %s',$Edesk['EdeskID']));
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		case 'array':
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/www/API/Telegramm]: найдено: EdeskID = %s',$Edesk['EdeskID']));
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			// ищем отправителя по идентфикатору отправителя
			$Count = DB_Count('Contacts',Array('Where'=>SPrintF('`ExternalID` = %u',$ChatID)));
			if(Is_Error($Count))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			// возможны варианты. идеальный - такой отправитель один, не идеальный - таких несколько, и странный - такого нет
			if($Count == 1){
				#-------------------------------------------------------------------------------
				Debug(SPrintF('[comp/www/API/Telegramm]: найден один пользователь для ChatID = %s',$ChatID));
				#-------------------------------------------------------------------------------
				// просто выбираем данные отправителя
				$Contact = DB_Select('Contacts',Array('UserID'),Array('UNIQ','Where'=>SPrintF('`ExternalID` = %u',$ChatID)));
				#-------------------------------------------------------------------------------
				switch(ValueOf($Contact)){
				case 'error':
					return ERROR | @Trigger_Error(500);
				case 'exception':
					return ERROR | @Trigger_Error(400);
				case 'array':
					#-------------------------------------------------------------------------------
					$UserID = $Contact['UserID'];
					#-------------------------------------------------------------------------------
					break;
					#-------------------------------------------------------------------------------
				default:
					return ERROR | @Trigger_Error(101);
				}
				#-------------------------------------------------------------------------------
			}elseif($Count > 1){
				#-------------------------------------------------------------------------------
				// достаём всех, смотрим кто из них участвовал в треде. возможно: один, много, никто
				Debug(SPrintF('[comp/www/API/Telegramm]: найдено %u пользователей для ChatID = %s',$Count,$ChatID));
				#-------------------------------------------------------------------------------
				$Contacts = DB_Select('Contacts',Array('UserID'),Array('Where'=>SPrintF('`ExternalID` = %u',$ChatID)));
				#-------------------------------------------------------------------------------
				switch(ValueOf($Contacts)){
				case 'error':
					return ERROR | @Trigger_Error(500);
				case 'exception':
					return ERROR | @Trigger_Error(400);
				case 'array':
					#-------------------------------------------------------------------------------
					// исключаем идентфикатор того кто запостил сообщение на которое ответ - сами себе отвечать вроде не могут
					#Debug(print_r($Contacts,true));
					$UserIDs = Array();
					#-------------------------------------------------------------------------------
					foreach($Contacts as $Contact)
						if($Contact['UserID'] != $Edesk['UserID'])
							$UserIDs[] = $Contact['UserID'];
					#-------------------------------------------------------------------------------
					#-------------------------------------------------------------------------------
					Debug(SPrintF('[comp/www/API/Telegramm]: найдены контакты (%s) c ChatID = %s',Implode(',',$UserIDs),$ChatID));
					#-------------------------------------------------------------------------------
					// если один элемент в массиве - юзер найден
					if(SizeOf($UserIDs) == 1){
						#-------------------------------------------------------------------------------
						$UserID = $UserIDs[0];
						#-------------------------------------------------------------------------------
						Debug(SPrintF('[comp/www/API/Telegramm]: найден контакт %s, ChatID = %s',$UserID,$ChatID));
						#-------------------------------------------------------------------------------
					}else{
						#-------------------------------------------------------------------------------
						// более одного юзера. проверяем какой из владеющих этой телегой участвует в треде
						// SELECT * , (SELECT `UserID` FROM `Edesks` WHERE `EdesksMessagesOwners`.`EdeskID` = `Edesks`.`ID`) AS `EdeskUserID` FROM EdesksMessagesOwners WHERE EdeskID = (SELECT EdeskID FROM EdesksMessagesOwners WHERE ID = 347401)
						$Owners = DB_Select('EdesksMessagesOwners',$Columns,Array('Where'=>SPrintF('`EdeskID` = (SELECT `EdeskID` FROM `EdesksMessagesOwners` WHERE `ID` = %u)',$MessageID)));
						#-------------------------------------------------------------------------------
						switch(ValueOf($Owners)){
						case 'error':
							return ERROR | @Trigger_Error(500);
						case 'exception':
							return ERROR | @Trigger_Error(400);
						case 'array':
							#-------------------------------------------------------------------------------
							foreach($Owners as $Owner){
								#-------------------------------------------------------------------------------
								if(In_Array($Owner['UserID'],$UserIDs)){
									#-------------------------------------------------------------------------------
									Debug(SPrintF('[comp/www/API/Telegramm]: перебором отвечавших в тред найден контакт (%s) c ChatID = %s',$UserID,$ChatID));
									#-------------------------------------------------------------------------------
									$UserID = $Owner['UserID'];
									#-------------------------------------------------------------------------------
									break;
									#-------------------------------------------------------------------------------
								}
								#-------------------------------------------------------------------------------
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
					break;
					#-------------------------------------------------------------------------------
				default:
					return ERROR | @Trigger_Error(101);
				}
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			// тут либо юзер найден, либо нет. если нет - назначаем его гостем
			$UserID = IsSet($UserID)?$UserID:10;
			#-------------------------------------------------------------------------------
			// инициализируюем юзера, иначе статус не проставится
			$Init = Comp_Load('Users/Init',IsSet($UserID)?$UserID:100);
			if(Is_Error($Init))
                                return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			// проверяем приложенные файлы и фотки $Data->{'text'}
			if(IsSet($Data->{'photo'})){
				#-------------------------------------------------------------------------------
				$Count = SizeOf($Data->{'photo'});
				#-------------------------------------------------------------------------------
				$FileID = $Data->{'photo'}[($Count - 1)]->{'file_id'};
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			if(IsSet($Data->{'document'})){
				$FileID   = $Data->{'document'}->{'file_id'};
				$FileName = $Data->{'document'}->{'file_name'};
			}
			#-------------------------------------------------------------------------------
			if(IsSet($FileID)){
				#-------------------------------------------------------------------------------
				// какой-то файл есть
				if($FileData = TgGetFile($Settings,$FileID)){
					#-------------------------------------------------------------------------------
					// говорим что не интерактивно, чтоб размер не проверяло
					$GLOBALS['IsCron'] = TRUE;
					#-------------------------------------------------------------------------------
					// прописываем имя, если оно есть
					if(IsSet($FileName))
						$FileData['name'] = $FileName;
					#-------------------------------------------------------------------------------
					$_FILES = Array('Upload'=>$FileData);
					#-------------------------------------------------------------------------------
					global $_FILES;
					#-------------------------------------------------------------------------------
					$Hash = Comp_Load('www/API/Upload');
					if(Is_Error($Hash))
						return ERROR | @Trigger_Error(500);
					#-------------------------------------------------------------------------------
				}
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			// постим сообщение в существующий тред
			#-------------------------------------------------------------------------------
			// снимаем флаг у треда
			$IsUpdate = DB_Update('Edesks',Array('Flags'=>'No'),Array('ID'=>$Edesk['EdeskID']));
			if(Is_Error($IsUpdate))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			// постим от админа, т.к. пост может идти от другого юзера в ответ на...
			$GLOBALS['__USER']['ID']	= 100;
			$GLOBALS['__USER']['IsAdmin']	= TRUE;
			#-------------------------------------------------------------------------------
			$Params = Array('Message'=>($Message)?$Message:'текст сообщения отсуствует','TicketID'=>$Edesk['EdeskID'],'UserID'=>$UserID,'IsInternal'=>TRUE);
			#-------------------------------------------------------------------------------
			if(IsSet($Hash))
				$Params['TicketMessageFile'] = $Hash;
			#-------------------------------------------------------------------------------
			$IsAdd = Comp_Load('www/API/TicketMessageEdit',$Params);
			if(Is_Error($IsAdd))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$GLOBALS['__USER']['ID']        = 100;
			$GLOBALS['__USER']['IsAdmin']   = FALSE;
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			return Array('Status'=>'Ok');
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		// не найдено соответсвтие идентификатора в телеграмме и номера сообщения в тикетнцие
		Debug(SPrintF('[comp/www/API/Telegramm]: НЕ найдено соответствие сообщения в телеграмм и тикета: Data->reply_to_message->message_id = %s',$Data->{'reply_to_message'}->{'message_id'}));
		#-------------------------------------------------------------------------------
		if(!TgSendMessage($Settings,$ChatID,$Settings['Params']['EdeskNotFound']))
			return new gException('ERROR_SEND_EdeskNotFound_MESSAGE','Ошибка отправки сообщения о не найденном тикете');
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		return Array('Status'=>'Ok');
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	// это не ответ на сообщение, возможно это код подтверждения
	#-------------------------------------------------------------------------------
	// разбираем текст на слова, слова проверяем как код подтверждения
	$Words = Explode(" ",$Message);
	$Count = 0;
	#-------------------------------------------------------------------------------
	foreach($Words as $Word){
		#-------------------------------------------------------------------------------
		// удаляем дефисы
		$Word = str_replace ('-','',$Word) ;
		#-------------------------------------------------------------------------------
		#Debug(SPrintF('[comp/www/API/Telegramm]: Word = %s',$Word));
		// если это число - ищем по базе
		if(IntVal($Word) != 0){
			#-------------------------------------------------------------------------------
			// могут быть ведущие нули в коде
			$Count = DB_Count('Contacts',Array('Where'=>SPrintF('`Confirmation` = "%s"',$Word)));
			if(Is_Error($Count))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			if($Count){
				#-------------------------------------------------------------------------------
				$Contact = DB_Select('Contacts',Array('UserID','Address','ExternalID'),Array('UNIQ','Where'=>SPrintF('`Confirmation` = "%s"',$Word)));
				if(!Is_Array($Contact))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
				$Event = Array('UserID'=>$Contact['UserID'],'PriorityID'=>'Billing','Text'=>SPrintF('Контактный адрес (%s/%s) подтверждён через "%s"',$Contact['Address'],$ChatID,$Config['Notifies']['Methods']['Telegram']['Name']));
				#-------------------------------------------------------------------------------
				$Event = Comp_Load('Events/EventInsert',$Event);
				if(!$Event)
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
				// код найден в базе, проставляем что контакт подтверждён
				$IsUpdated = DB_Update('Contacts',Array('Confirmed'=>Time(),'Confirmation'=>'','ExternalID'=>$ChatID,'IsActive'=>TRUE),Array('Where'=>SPrintF('`Confirmation` = "%s"',$Word)));
				if(Is_Error($IsUpdated))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
				#-------------------------------------------------------------------------------
				// шлём сообщение о успешной активации
				if(!TgSendMessage($Settings,$ChatID,$Settings['Params']['ConfirmSuccess']))
					return new gException('ERROR_SEND_SUCCESS_ACTIVATE_MESSAGE','Ошибка отправки сообщения о успешной активации на сервер Telegramm');
				#-------------------------------------------------------------------------------
				// шлём сообщение со справочной информацией
				if(!TgSendMessage($Settings,$ChatID,$Settings['Params']['StubMessage']))
					return new gException('ERROR_SEND_START_MESSAGE','Ошибка отправки сообщения-затычки на сервер Telegramm');
				#-------------------------------------------------------------------------------
				// вываливаемся из скрипта, вообще
				return Array('Status'=>'Ok');
				#-------------------------------------------------------------------------------
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		// всё перебирать бессмысленно, достаточно первого десятка слов
		if($Count > 9)
			break;
		#-------------------------------------------------------------------------------
		$Count++;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// сюда мы попали если ничего не найдено
if(!TgSendMessage($Settings,$ChatID,SPrintF($Settings['Params']['StartMessage'],$Settings['Params']['BotName'])))
	return new gException('ERROR_SEND_START_MESSAGE','Ошибка отправки стартового сообщения на сервер Telegramm');
#-------------------------------------------------------------------------------
if(!TgSendMessage($Settings,$ChatID,$Settings['Params']['StubMessage']))
	return new gException('ERROR_SEND_START_MESSAGE','Ошибка отправки сообщения-затычки на сервер Telegramm');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------


?>
