<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Tasks']['Types']['CheckEmail'];
#-------------------------------------------------------------------------------
# проверяем, есть ли функции для работы с IMAP
if(!Function_Exists('imap_open'))
	return 24*3600;
#-------------------------------------------------------------------------------
if(!$Settings['IsActive'])
	return 3600;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('classes/ImapMailbox.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Server = SPrintF("{%s/%s/%s}INBOX",$Settings['CheckEmailServer'],$Settings['CheckEmailProtocol'],$Settings['UseSSL']?'ssl/novalidate-cert':'notls');
#-------------------------------------------------------------------------------
$attachmentsDir = SPrintF('%s/hosts/%s/tmp/imap',SYSTEM_PATH,HOST_ID);
#-------------------------------------------------------------------------------
@mkdir($attachmentsDir, 0700, true);
#-------------------------------------------------------------------------------
$mailbox = new ImapMailbox($Server, $Settings['CheckEmailLogin'], $Settings['CheckEmailPassword'],$attachmentsDir);
#-------------------------------------------------------------------------------
$mails = array();
#-------------------------------------------------------------------------------
foreach($mailbox->searchMailbox() as $mailId){
	#-------------------------------------------------------------------------------
	$mail = $mailbox->getMail($mailId);
	#-------------------------------------------------------------------------------
	$mails[] = $mail;
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/Tasks/CheckEmail]: attachments = %s',print_r($mail->attachments,true)));
	#Debug(SPrintF('[comp/Tasks/CheckEmail]: attachmentsIds = %s',print_r($mail->attachmentsIds,true)));
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(SizeOf($mails) < 1){
	#-------------------------------------------------------------------------------
	$mailbox->disconnect();
	#-------------------------------------------------------------------------------
	return $Settings['RequestPeriod'] * 60;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Tasks/CheckEmail]: сообщений = %s',SizeOf($mails)));
$GLOBALS['TaskReturnInfo'] = Array();
$GLOBALS['TaskReturnInfo'][] = SPrintF('%s messages',SizeOf($mails));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach ($mails as $mail){
	#-------------------------------------------------------------------------------
	$Subject = $mail->subject;
	$fromAddress = $mail->fromAddress;
	$textPlain = $mail->textPlain;
	#-------------------------------------------------------------------------------
	# перебираем аттачменты
	UnSet($_FILES);
	#-------------------------------------------------------------------------------
	$Files = $mail->attachments;
	foreach(Array_Keys($Files) as $FileName){
		#---------------------------------------------------------------------
		Debug(SPrintF('[comp/Tasks/CheckEmail]: name = "%s"; path = "%s"',$FileName,$Files[$FileName]));
		$FileData = Array(
					'size'		=> FileSize($Files[$FileName]),
					'error'		=> 0,
					'tmp_name'	=> $Files[$FileName],
					'name'		=> $FileName
				);
		#---------------------------------------------------------------------
		$_FILES = Array('Upload'=>$FileData);
		#---------------------------------------------------------------------
		global $_FILES;
		#---------------------------------------------------------------------
		$Comp = Comp_Load('www/API/Upload');
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#---------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	# надо ли вырезать цитаты из текста
	if($Settings['CutQuotes']){
		$textPlain = Trim(Preg_Replace('#^>(.*)$#m', '',$textPlain));
		$textPlain = preg_replace("/\r/", "\n",$textPlain);
		$textPlain = trim(preg_replace('/[\n]+/m',"\n",$textPlain)); 
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# надо ли отпиливать подпись из сообщения
	if($Settings['CutSign']){
		#-------------------------------------------------------------------------------
		$Texts = Explode("\n",$textPlain);
		#-------------------------------------------------------------------------------
		$textPlain = Array();
		#-------------------------------------------------------------------------------
		foreach($Texts as $Text){
			#-------------------------------------------------------------------------------
			$textPlain[] = Trim($Text);
			#-------------------------------------------------------------------------------
			if(Trim($Text) == '--')
				$SignPos = SizeOf($textPlain) - 1;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$Length = (IsSet($SignPos))?$SignPos:SizeOf($textPlain);
		#-------------------------------------------------------------------------------
		$textPlain = Implode("\n",Array_Slice($textPlain,0,$Length));
		#-------------------------------------------------------------------------------
		UnSet($SignPos);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# достаём все заголовки
	$References = FALSE;
	#-------------------------------------------------------------------------------
	$Headers = Explode("\n", Trim($mailbox->fetchHeader($mail->mId)));
	#-------------------------------------------------------------------------------
	if(Is_Array($Headers) && Count($Headers)){
		foreach($Headers as $Line){
			#-------------------------------------------------------------------------------
			$HeaderLine = Explode(" ",Trim($Line));
			#-------------------------------------------------------------------------------
			if(StrToLower($HeaderLine[0]) == 'in-reply-to:')
				$References = $HeaderLine[1];
			#-------------------------------------------------------------------------------
			if(StrToLower($HeaderLine[0]) == 'references:')
				$References = $HeaderLine[1];
			#-------------------------------------------------------------------------------
			if(StrToLower($HeaderLine[0]) == 'x-autoreply:')
				$AutoReply = TRUE;
			#-------------------------------------------------------------------------------
		}
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(IsSet($AutoReply)){
		# это автоответ. удаляем сообщение и продолжаем
		Debug(SPrintF('[comp/Tasks/CheckEmail]: AutoReply from %s',$fromAddress));
		$mailbox->deleteMessage($mail->mId, TRUE);
		UnSet($AutoReply);
		continue;
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(StrLen($textPlain) < 2){
		# пустое сообщение, или вместе с подписью текст выпилился
		Debug(SPrintF('[comp/Tasks/CheckEmail]: Пустое сообщение с адреса %s',$fromAddress));
		$mailbox->deleteMessage($mail->mId, TRUE);
		continue;
	}
	#-------------------------------------------------------------------------------
	# проверяем наличие ссылки на тикет
	if($References){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Tasks/CheckEmail]: References %s',$References));
		#-------------------------------------------------------------------------------
		$Address = MailParse_RFC822_Parse_Addresses($References);
		$Address = Explode("@",$Address[0]['address']);
		#-------------------------------------------------------------------------------
		if(IsSet($Address[1]) && $Address[1] == HOST_ID && IntVal($Address[0]) == $Address[0]){
			#-------------------------------------------------------------------------------
			# проверяем наличие такого тикета
			$Columns = Array('*','(SELECT `UserID` FROM `Edesks` WHERE `EdesksMessagesOwners`.`EdeskID` = `Edesks`.`ID`) AS `EdeskUserID`');
			$Edesk = DB_Select('EdesksMessagesOwners',$Columns,Array('UNIQ','ID'=>$Address[0]));
			switch(ValueOf($Edesk)){
			case 'error':
				return ERROR | @Trigger_Error(500);
			case 'exception':
				break;
			case 'array':
				#-------------------------------------------------------------------------------
				$MessageID = $Address[0];
				#-------------------------------------------------------------------------------
				break;
			default:
				return ERROR | @Trigger_Error(101);
			}
		}
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if($Settings['SaveHeaders'])
		$SaveHeaders = SPrintF("[hidden]\n%s[/hidden]\n",$mailbox->fetchHeader($mail->mId));
	#-------------------------------------------------------------------------------
	$Message = SPrintF("%s\n\n%s[size:10][color:gray]posted via email, from: %s[/color][/size]",$textPlain,(IsSet($SaveHeaders))?$SaveHeaders:'',$fromAddress);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# имеем 2 ситуации, задан или не задан $MessageID - соответственно, добавление в тикет или создание тикета
	if(IsSet($MessageID)){
		#-------------------------------------------------------------------------------
		# либо от существующего юзера, либо от гостя - определяемся по владельцу треда
		$GLOBALS['__USER']['ID'] = $Edesk['EdeskUserID'];
		#-------------------------------------------------------------------------------
		$IsAdd = Comp_Load('www/API/TicketMessageEdit',Array('Message'=>$Message,'TicketID'=>$Edesk['EdeskID']));
		if(Is_Error($IsAdd))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$GLOBALS['__USER']['ID'] = 100;
		#-------------------------------------------------------------------------------
		$mailbox->deleteMessage($mail->mId, TRUE);
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		# ищщем в юзерах этого пользователя
		$User = DB_Select('Users',Array('ID','GroupID'),Array('UNIQ','Where'=>SPrintF('`Email` = "%s"',$fromAddress)));
		#-------------------------------------------------------------------------------
		switch(ValueOf($User)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			#-------------------------------------------------------------------------------
			# сообщение на www/API/TicketEdit, от юзера "Гость" (проверить его существование)
			$Count = DB_Count('Users',Array('ID'=>10));
			if(Is_Error($Count))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			if(!$Count){
				#-------------------------------------------------------------------------------
				Debug('[comp/Tasks/CheckEmail]: пользователь "Гость", идентификатор 10 не найден %s');
				$mailbox->deleteMessage($mail->mId, TRUE);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			$Params = Array(
					'Theme'		=> $Subject,
					'PriorityID'	=> 'Low',
					'Message'	=> $Message,
					'Flags'		=> 'No',
					'TargetGroupID'	=> 3100000,
					'NotifyEmail'	=> $fromAddress
					);
			#-------------------------------------------------------------------------------
			$GLOBALS['__USER']['ID'] = 10;
			#-------------------------------------------------------------------------------
			$IsAdd = Comp_Load('www/API/TicketEdit',$Params);
			if(Is_Error($IsAdd))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$GLOBALS['__USER']['ID'] = 100;
			#-------------------------------------------------------------------------------
			$mailbox->deleteMessage($mail->mId, TRUE);
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		case 'array':
			#-------------------------------------------------------------------------------
			# сообщение на www/API/TicketEdit, от найденного юзера
			$Params = Array(
					'Theme'		=> $Subject,
					'PriorityID'	=> 'Low',
					'Message'	=> $Message,
					'Flags'		=> 'No',
					'TargetGroupID'	=> 3100000
					);
			#-------------------------------------------------------------------------------
			$GLOBALS['__USER']['ID'] = $User['ID'];
			#-------------------------------------------------------------------------------
			$IsAdd = Comp_Load('www/API/TicketEdit',$Params);
			if(Is_Error($IsAdd))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$GLOBALS['__USER']['ID'] = 100;
			#-------------------------------------------------------------------------------
			$mailbox->deleteMessage($mail->mId, TRUE);
			#-------------------------------------------------------------------------------
			break;
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	# ампутируем переменную, чтоб в один тикет не напостило все письма
	UnSet($MessageID);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$mailbox->disconnect();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Settings['RequestPeriod'] * 60;
#-------------------------------------------------------------------------------

?>
