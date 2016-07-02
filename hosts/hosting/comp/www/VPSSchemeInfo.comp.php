<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$VPSSchemeID = (string) @$Args['VPSSchemeID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$VPSScheme = DB_Select('VPSSchemes','*',Array('UNIQ','ID'=>$VPSSchemeID));
#-------------------------------------------------------------------------------
switch(ValueOf($VPSScheme)){
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
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Window')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Тариф виртуального сервера');
#-------------------------------------------------------------------------------
$Table = Array('Общая информация');
#-------------------------------------------------------------------------------
$Table[] = Array('Название тарифа',$VPSScheme['Name']);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Currency',$VPSScheme['CostDay']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Цена 1 дн.',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Currency',$VPSScheme['CostInstall']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Цена за установку',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$ServersGroup = DB_Select('ServersGroups','*',Array('UNIQ','ID'=>$VPSScheme['ServersGroupID']));
if(!Is_Array($ServersGroup))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Группа серверов',$ServersGroup['Name']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$VPSScheme['IsReselling']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Права реселлера',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$VPSScheme['IsActive']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Тариф активен',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$VPSScheme['IsProlong']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Возможность продления',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$VPSScheme['IsSchemeChange']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Возможность смены тарифа',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($VPSScheme['MaxOrders'] > 0)
	$Table[] = Array('Максимальное число заказов',$VPSScheme['MaxOrders']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Общие ограничения';
#-------------------------------------------------------------------------------
$Table[] = Array('Число VDS (для реселлеров)',$VPSScheme['vdslimit']);
#-------------------------------------------------------------------------------
$Table[] = Array('Число пользователей (для реселлеров)',$VPSScheme['QuotaUsers']);
#-------------------------------------------------------------------------------
$Table[] = Array('Дисковое пространство',SPrintF("%u Мб.",$VPSScheme['disklimit']));
#-------------------------------------------------------------------------------
$Table[] = Array('Количество процессоров',$VPSScheme['ncpu']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Частота (приоритет, для KVM) процессора',$VPSScheme['cpu']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Память',$VPSScheme['mem']);
#-------------------------------------------------------------------------------
$Table[] = Array('Скорость канала',SPrintF('%u MBit/s',$VPSScheme['chrate']));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$SystemsIDs = Array();
#-------------------------------------------------------------------------------
$Servers = DB_Select('Servers','Params',Array('Where'=>SPrintF('`ServersGroupID` = %u',$VPSScheme['ServersGroupID'])));
#-------------------------------------------------------------------------------
switch(ValueOf($Servers)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	# No more...
	break;
case 'array':
	#-------------------------------------------------------------------------------
	foreach($Servers as $Server)
		$SystemsIDs[] = $Server['Params']['SystemID'];
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
if(In_Array('VdsManager4',$SystemsIDs)){
	#-------------------------------------------------------------------------------
	$Table[] = 'Ограничения для VdsManager4';
	#-------------------------------------------------------------------------------
	$Table[] = Array('Burstable RAM',$VPSScheme['bmem']);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Свап',$VPSScheme['maxswap']);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Открытых файлов',$VPSScheme['maxdesc']);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Месячный трафик',SPrintF('%u Мб.',$VPSScheme['traf']));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$dns = "не используются";
	#-------------------------------------------------------------------------------
	if($VPSScheme['extns'] == 'dnsprovider'){$dns = "провайдера";}
	#-------------------------------------------------------------------------------
	if($VPSScheme['extns'] == 'dnsprivate'){$dns = "собственные";}
	#-------------------------------------------------------------------------------
	$Table[] = Array('DNS сервера',$dns);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table[] = Array('Ограничение собственных DNS',$VPSScheme['limitpvtdns']);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Ограничение DNS провайдера',$VPSScheme['limitpubdns']);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$backup = "Не делается";
	#-------------------------------------------------------------------------------
	if($VPSScheme['backup'] == 'bday'){$dns = "Ежедневно";}
	#-------------------------------------------------------------------------------
	if($VPSScheme['backup'] == 'bweek'){$dns = "Еженедельно";}
	#-------------------------------------------------------------------------------
	if($VPSScheme['backup'] == 'bmonth'){$dns = "Ежемесячно";}
	#-------------------------------------------------------------------------------
	$Table[] = Array('Резервное копирование',$backup);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
if(In_Array('VmManager5_KVM',$SystemsIDs)){
	#-------------------------------------------------------------------------------
	$Table[] = 'Ограничения для VmManager5_KVM';
	#-------------------------------------------------------------------------------
	$Table[] = Array('Вес использования дискового I/O',$VPSScheme['blkiotune']);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Количество загружаемых образов',$VPSScheme['isolimitnum']);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Размер загруженных образов',$VPSScheme['isolimitsize']);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Comp);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
