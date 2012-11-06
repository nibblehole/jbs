<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Args = Args();
#-------------------------------------------------------------------------------
$PoliticID = (integer) @$Args['PoliticID'];
#-------------------------------------------------------------------------------
if($PoliticID){
  #-----------------------------------------------------------------------------
  $Politic = DB_Select('Politics','*',Array('UNIQ','ID'=>$PoliticID));
  #-----------------------------------------------------------------------------
  switch(ValueOf($Politic)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      return ERROR | @Trigger_Error(400);
    case 'array':
      # No more...
    break;
    default:
      return ERROR | @Trigger_Error(101);
  }
}else{
	#-----------------------------------------------------------------------------
	$Politic = Array(
		#--------------------------------------------------------------------------
		'ExpirationDate'	=> Time() + 10 * 365 * 24 * 3600,
		'GroupID'		=> 1,
		'UserID'		=> 1,
		'FromServiceID'		=> 0,
		'FromSchemeID'		=> 0,
		'FromSchemesGroupID'	=> 0,
		'ToServiceID'		=> 0,
		'ToSchemeID'		=> 0,
		'ToSchemesGroupID'	=> 0,
		'DaysPay'		=> 363,
		'Discont'		=> 0.1,
		'Comment'		=> '10% скидки тем кто платит сразу за год'
  );
}
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
$DOM->AddAttribs('Body',Array('onload'=>SPrintF("GetSchemes(%s,'FromSchemeID','%s');GetSchemes(%s,'ToSchemeID','%s');",$Politic['FromServiceID'],$Politic['FromSchemeID'],$Politic['ToServiceID'],$Politic['ToSchemeID'])));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/GetSchemes.js}')));
#-------------------------------------------------------------------------------
$Title = ($PoliticID?'Редактирование ценовой политики':'Добавление ценовой политики');
#-------------------------------------------------------------------------------
$DOM->AddText('Title',$Title);
#-------------------------------------------------------------------------------
$Table = Array();
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Owner','Владелец политики',$Politic['GroupID'],$Politic['UserID']);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Where = Array(
		'`IsActive` = "yes"',
		'`IsHidden` != "yes"',
		);
#-------------------------------------------------------------------------------
$Services = DB_Select('ServicesOwners','*',Array('Where'=>$Where,'SortOn'=>'SortID'));
#-------------------------------------------------------------------------------
switch(ValueOf($Services)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('SERVICES_NOT_FOUND','Для создания политики необходим хотя бы один активный сервис');
	break;
case 'array':
	#---------------------------------------------------------------------------
	$ServicesOptions = Array('Любой активный сервис');
	#---------------------------------------------------------------------------
	foreach($Services as $Service)
		$ServicesOptions[$Service['ID']] = SPrintF('%s (%s)',$Service['Code'],$Service['Name']);
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'FromServiceID','onchange'=>SPrintF("GetSchemes(this.value,'FromSchemeID','%s');",$Politic['FromSchemeID'])),$ServicesOptions,$Politic['FromServiceID']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Оплачиваемый сервис',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$SchemesOptions = Array('Любой тариф');
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'FromSchemeID','id'=>'FromSchemeID','disabled'=>TRUE),$SchemesOptions,$Politic['FromSchemeID']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Оплачиваемый тариф',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$SchemesGroups = DB_Select('SchemesGroups','*');
#-------------------------------------------------------------------------------
switch(ValueOf($SchemesGroups)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	$Options = Array('Нет групп тарифов');
	break;
case 'array':
	#---------------------------------------------------------------------------
	$Options = Array('Не использовать');
	#---------------------------------------------------------------------------
	foreach($SchemesGroups as $SchemesGroup)
		$Options[$SchemesGroup['ID']] = $SchemesGroup['Name'];
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'FromSchemesGroupID'),$Options,$Politic['FromSchemesGroupID']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Оплачиваемая группа тарифов',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'ToServiceID','onchange'=>SPrintF("GetSchemes(this.value,'ToSchemeID','%s');",$Politic['ToSchemeID'])),$ServicesOptions,$Politic['ToServiceID']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Скидка на сервис',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'ToSchemeID','id'=>'ToSchemeID','disabled'=>TRUE),$SchemesOptions,$Politic['ToSchemeID']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Скидка на тариф',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$SchemesGroups = DB_Select('SchemesGroups','*');
#-------------------------------------------------------------------------------
switch(ValueOf($SchemesGroups)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	$Options = Array('Нет групп тарифов');
	break;
case 'array':
	#---------------------------------------------------------------------------
	$Options = Array('Не использовать');
	#---------------------------------------------------------------------------
	foreach($SchemesGroups as $SchemesGroup)
		$Options[$SchemesGroup['ID']] = $SchemesGroup['Name'];
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'ToSchemesGroupID'),$Options,$Politic['ToSchemesGroupID']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Скидка на группу тарифов',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('jQuery/DatePicker','ExpirationDate',$Politic['ExpirationDate']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Дата окончания',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'DaysPay',
    'value' => $Politic['DaysPay'],
    'prompt'=> 'Сколько дней надо оплатить, чтобы политика сработала'
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Дней оплаты',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'Discont',
    'value' => $Politic['Discont']*100,
    'prompt'=> 'Число от 5 до 100'
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Размер скидки в %',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/TextArea',
   Array(
	'name'  => 'Comment',
	'style' => 'width:100%;',
	'rows'  => 5,
	'prompt'=> 'Цель/причина создания этой скидки клиенту'
	),
   $Politic['Comment']
);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = 'Комментарий';
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'button', # FormEdit($URL,$FormName,$ShowProgress)
    'onclick' => SPrintF("FormEdit('/Administrator/API/PoliticEdit','PoliticEditForm','%s');",$Title),
    'value'   => ($PoliticID?'Сохранить':'Добавить')
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'PoliticEditForm','onsubmit'=>'return false;'),$Comp);
#-------------------------------------------------------------------------------
if($PoliticID){
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'name'  => 'PoliticID',
      'type'  => 'hidden',
      'value' => $PoliticID
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Form->AddChild($Comp);
}
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------

?>
