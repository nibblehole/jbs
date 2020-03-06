<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('IsCreate','Folder');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('libs/Artichow.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Result = Array('Title'=>'Распределение доходов по серверам');
#-------------------------------------------------------------------------------
if(!$IsCreate)
  return $Result;
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY');
#-------------------------------------------------------------------------------
$NoBody->AddChild(new Tag('P','Данный вид статистики содержит информацию о доходности каждого из имеющихся серверов за 1 месяц (30 дней)'));
$NoBody->AddChild(new Tag('P','Суммируются цены за месяц тарифов всех активных заказов размещенных на сервере.'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Graphs = Array();	# для построения графиков на выхлопе
#-------------------------------------------------------------------------------
# перебираем группы серверов
$ServersGroups = DB_Select('ServersGroups',Array('*'),Array('SortOn'=>'SortID'));
switch(ValueOf($ServersGroups)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	Debug("[comp/Statistics/ServersIncome]: no groups found");
	break;
case 'array':
	# All OK, Servers Groups found
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// таблица для данных, одна на всё
$Table = Array();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach($ServersGroups as $ServersGroup){
	#-------------------------------------------------------------------------------
	#if($ServersGroup['ServiceID'] != 20000)
	#	continue;
	#-------------------------------------------------------------------------------
	# выбираем сервера группы
	$Servers = DB_Select('Servers',Array('*'),Array('Where'=>SPrintF('`ServersGroupID` = %u',$ServersGroup['ID']),'SortOn'=>'Address'));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Servers)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Statistics/ServersIncome]: no servers for group %s',$ServersGroup['ID']));
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
	$Balance = $Accounts = $NumPaid = 0;
	#-------------------------------------------------------------------------------
	$Params = $Labels = Array();
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table[] = SPrintF('Группа серверов: %s',$ServersGroup['Name']);
	#-------------------------------------------------------------------------------
	$Table[] = Array(new Tag('TD',Array('class'=>'Head'),'Адрес сервера'),new Tag('TD',Array('class'=>'Head'),'Аккаунтов (всего/платно)'),new Tag('TD',Array('class'=>'Head'),'Доход сервера'),new Tag('TD',Array('class'=>'Head'),'Доход аккаунта')/*,new Tag('TD',Array('class'=>'Head'),'Диск, Gb'),new Tag('TD',Array('class'=>'Head'),'Память, Mb')*/);
	#-------------------------------------------------------------------------------
	foreach($Servers as $Server){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Statistics/ServersIncome]: Address = %s',$Server['Address']));
		#-------------------------------------------------------------------------------
		# достаём все активные аккаунты сервера
		$ServerAccounts = DB_Select('Orders',Array('ID'),Array('Where'=>SPrintF('`ServerID` = %u AND `StatusID` = "Active" AND `ServiceID` = %u',$Server['ID'],$ServersGroup['ServiceID'])));
		#-------------------------------------------------------------------------------
		switch(ValueOf($ServerAccounts)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			Debug(SPrintF('[comp/Statistics/ServersIncome]: no accounts for server %s',$Server['Address']));
			continue 2;
		case 'array':
			# All OK, accounts found
			Debug(SPrintF('[comp/Statistics/ServersIncome]: server %s, found %u accounts',$Server['Address'],SizeOf($ServerAccounts)));
			break;
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		$Array = Array();
		#-------------------------------------------------------------------------------
		foreach($ServerAccounts as $Account)
			$Array[] = $Account['ID'];
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
                if($ServersGroup['ServiceID'] == 20000){
			#-------------------------------------------------------------------------------
			# домены обсчитываем отдельно.
			# выбираем
			$Incomes = DB_Select('DomainOrders',Array('SUM((SELECT `CostOrder` FROM `DomainSchemes` WHERE `DomainSchemes`.`ID` = `DomainOrders`.`SchemeID`)) AS `CostOrders`'),Array('UNIQ','Where'=>SPrintF('`OrderID` IN (%s)',Implode(',',$Array))));
			#-------------------------------------------------------------------------------
			switch(ValueOf($Incomes)){
			case 'error':
				return ERROR | @Trigger_Error(500);
			case 'exception':
				Debug(SPrintF('[comp/Statistics/ServersIncome]: no summ for registrator %s',$Server['Address']));
				continue 2;
			case 'array':
				break;
			default:
				return ERROR | @Trigger_Error(101);
			}
			#-------------------------------------------------------------------------------
			$PaidAccounts = SizeOf($Array);
			#-------------------------------------------------------------------------------
			$ServerIncome	= $Incomes['CostOrders'] / 12;	# в месяц
			$AccountIncome	= $ServerIncome / $PaidAccounts;
			$Income['DaysRemainded'] = $PaidAccounts * 365;
			#Debug(SPrintF('[comp/Statistics/ServersIncome]: Income = %s',print_r($Income,true)));
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			# считаем стоимость одного дня для каждого аккаунта сервера
			$Where = Array('`DaysRemainded` > 0',SPrintF('`OrderID` IN (%s)',Implode(',',$Array)));
			#-------------------------------------------------------------------------------
			$Incomes = DB_Select('OrdersConsider',Array('SUM(`DaysRemainded`*`Cost`*(1-`Discont`))/SUM(`DaysRemainded`) as `CostDay`'),Array('Where'=>$Where,'GroupBy'=>'OrderID'));
			#-------------------------------------------------------------------------------
			switch(ValueOf($Incomes)){
			case 'error':
				return ERROR | @Trigger_Error(500);
			case 'exception':
				#-------------------------------------------------------------------------------
				Debug(SPrintF('[comp/Statistics/ServersIncome]: no summ for server %s',$Server['Address']));
				#-------------------------------------------------------------------------------
				continue 2;
				#-------------------------------------------------------------------------------
			case 'array':
				#-------------------------------------------------------------------------------
				// изначально, всё по нулям
				$PaidAccounts = $ServerIncome = $AccountIncome = 0;
				#-------------------------------------------------------------------------------
				// перебираем аккаунты, считаем сумму дохода всего сервера в ДЕНЬ, стоимость одного аккаунта, количество платных аккаунтов
				foreach($Incomes as $Income){
					#-------------------------------------------------------------------------------
					// если стомость аккаунта равна нулю, пропускаем его
					if($Income['CostDay'] == 0)
						continue;
					#-------------------------------------------------------------------------------
					$PaidAccounts++;
					#-------------------------------------------------------------------------------
					$ServerIncome = $ServerIncome + $Income['CostDay'];
					#-------------------------------------------------------------------------------
				}
				#-------------------------------------------------------------------------------
				// если платных аккаунтов нет - пропускаем
				#Debug(SPrintF('[comp/Statistics/ServersIncome]: ServerIncome = %s; PaidAccounts = %s',$ServerIncome,$PaidAccounts));
				if($PaidAccounts == 0)
					continue 2;
				#-------------------------------------------------------------------------------
				// доход сервера
				$ServerIncome = $ServerIncome * 30;		# 30 дней в месяце
				// доход одного аккаунта
				$AccountIncome = $ServerIncome / $PaidAccounts;	# только по платным аккаунтам
				#-------------------------------------------------------------------------------
				break;
				#-------------------------------------------------------------------------------
			default:
				return ERROR | @Trigger_Error(101);
			}
			#-------------------------------------------------------------------------------
			#Debug(SPrintF('[comp/Statistics/ServersIncome]: Incomes = %s',print_r($Incomes,true)));
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		#Debug("[comp/Statistics/ServersIncome]: before calculate");
		$NumAccounts = SizeOf($Array);
		#-------------------------------------------------------------------------------
		$AccountIncome = Comp_Load('Formats/Currency',$AccountIncome);
		if(Is_Error($AccountIncome))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		#Debug("[comp/Statistics/ServersIncome]: debug - 1");
		$Comp = Comp_Load('Formats/Currency',$ServerIncome);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		#Debug("[comp/Statistics/ServersIncome]: debug - 2");
		$Table[] = Array($Server['Address'],SPrintF('%s / %s',$NumAccounts,$PaidAccounts),$Comp,$AccountIncome/*,$Usage['tdisk'],$Usage['tmem']*/);
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		#Debug("[comp/Statistics/ServersIncome]: debug - 3");
		$Params[] = $ServerIncome;
		$Labels[] = $Server['Address'];
		#-------------------------------------------------------------------------------
		#Debug("[comp/Statistics/ServersIncome]: debug - 4");
		#Debug(SPrintF('Balance = %s',print_r($Balance,true)));
		#Debug(SPrintF('ServerIncome = %s',print_r($ServerIncome,true)));
		#Debug(SPrintF('NumAccounts = %s',print_r($NumAccounts,true)));
		#Debug(SPrintF('PaidAccounts = %s',print_r($PaidAccounts,true)));
		$Balance += $ServerIncome;
		$Accounts+= $NumAccounts;
		$NumPaid += $PaidAccounts;
		#-------------------------------------------------------------------------------
		#Debug("[comp/Statistics/ServersIncome]: cycle complete");
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(SizeOf($Servers) > 1){
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Formats/Currency',$Balance);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Table[] = Array(new Tag('TD',Array('colspan'=>5,'class'=>'Standard'),SPrintF('Общий доход от серверов группы: %s',$Comp)));
		#-------------------------------------------------------------------------------
		$Table[] = Array(new Tag('TD',Array('colspan'=>5,'class'=>'Standard'),SPrintF('Число аккаунтов в группе: %s / %s',$Accounts,$NumPaid)));
		#-------------------------------------------------------------------------------
		# средняя стоимость аккаунта
		$Comp = Comp_Load('Formats/Currency',($NumPaid > 0)?($Balance / $NumPaid):0);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Table[] = Array(new Tag('TD',Array('colspan'=>5,'class'=>'Standard'),SPrintF('Средняя цена аккаунта в группе: %s',$Comp)));
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table[] = Array(new Tag('TD',Array('colspan'=>5,'class'=>'Standard','style'=>'color:white;'),'конец группы серверов'));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Graphs[$ServersGroup['ID']] = Array('Name'=>$ServersGroup['Name'],'Balance'=>$Balance,'NumPaid'=>$NumPaid,'Accounts'=>$Accounts,'Params'=>$Params,'Labels'=>$Labels);
	#----------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// рисуем таблицу
$Comp = Comp_Load('Tables/Extended',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody->AddChild($Comp);
#-------------------------------------------------------------------------------
$NoBody->AddChild(new Tag('BR'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# строим графики, считаем суммы
$Balance = 0;
$Accounts = 0;
$NumPaid = 0;
#-------------------------------------------------------------------------------
foreach($ServersGroups as $ServersGroup){
	#-------------------------------------------------------------------------------
	if(IsSet($Graphs[$ServersGroup['ID']])){
		#-------------------------------------------------------------------------------
		$Balance += $Graphs[$ServersGroup['ID']]['Balance'];
		#-------------------------------------------------------------------------------
		$Accounts+= $Graphs[$ServersGroup['ID']]['Accounts'];
		#-------------------------------------------------------------------------------
		$NumPaid += $Graphs[$ServersGroup['ID']]['NumPaid'];
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Currency',$Balance);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody->AddChild(new Tag('SPAN',SPrintF('Доход от всех серверов: %s',$Comp)));
$NoBody->AddChild(new Tag('BR'));
#-------------------------------------------------------------------------------
$NoBody->AddChild(new Tag('SPAN',SPrintF('Число активных аккаунтов: %s',$Accounts)));
$NoBody->AddChild(new Tag('BR'));
#-------------------------------------------------------------------------------
$NoBody->AddChild(new Tag('SPAN',SPrintF('Число активных платных аккаунтов: %s',$NumPaid)));
$NoBody->AddChild(new Tag('BR'));
#-------------------------------------------------------------------------------
$NoBody->AddChild(new Tag('BR'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// для общей статистики, по группам
$Params = $Labels = Array();
#-------------------------------------------------------------------------------
foreach($ServersGroups as $ServersGroup){
	#-------------------------------------------------------------------------------
	if(IsSet($Graphs[$ServersGroup['ID']])){
		#-------------------------------------------------------------------------------
		$iParam = 0;
		#-------------------------------------------------------------------------------
		#Debug(print_r($Graphs[$ServersGroup['ID']],true));
		#-------------------------------------------------------------------------------
		if(Count($Graphs[$ServersGroup['ID']]['Params']) > 1){
			#-------------------------------------------------------------------------------
			$File = SPrintF('%s.jpg',UniqID(SPrintF('ServersIncome_%s_',$ServersGroup['ID'])));
			#-------------------------------------------------------------------------------
			Artichow_Pie(SPrintF('Доходы группы %s',$Graphs[$ServersGroup['ID']]['Name']),SPrintF('%s/%s',$Folder,$File),$Graphs[$ServersGroup['ID']]['Params'],$Graphs[$ServersGroup['ID']]['Labels']);
			#-------------------------------------------------------------------------------
			$NoBody->AddChild(new Tag('IMG',Array('src'=>$File)));
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		foreach(Array_Keys($Graphs[$ServersGroup['ID']]['Params']) as $Key)
			$iParam = $iParam + $Graphs[$ServersGroup['ID']]['Params'][$Key];
		#-------------------------------------------------------------------------------
		if($iParam > 0){
			#-------------------------------------------------------------------------------
			$Params[] = $iParam;
			$Labels[] = $ServersGroup['Name'];
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// генерим общую картинку по группам
$File = SPrintF('%s.jpg',UniqID('ServersIncomeTotal_'));
#-------------------------------------------------------------------------------
Artichow_Pie('Доходы всех групп',SPrintF('%s/%s',$Folder,$File),$Params,$Labels);
#-------------------------------------------------------------------------------
$NoBody->AddChild(new Tag('IMG',Array('src'=>$File)));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Result['DOM'] = $NoBody;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Result;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
