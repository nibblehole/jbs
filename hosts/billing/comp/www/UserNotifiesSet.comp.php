<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php','libs/Tree.php')))
  return ERROR | @Trigger_Error(500);
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
$DOM->AddText('Title','Настройка уведомлений');
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/UserNotifiesSet.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Notifies = $Config['Notifies'];
#-------------------------------------------------------------------------------
$Methods = $Notifies['Methods'];
#-------------------------------------------------------------------------------
if ($Methods['SMS']['IsActive']) {
    if ($__USER['MobileConfirmed'] == 0) {
	$Row2 = Array(new Tag('TD', Array('colspan' => 3, 'class' => 'Standard', 'style' => 'background-color:#FDF6D3;'), 'Для настройки SMS уведомлений, подтвердите свой номер телефона и пополните баланс.'));
    }
    else {
	$Row2 = Array(new Tag('TD', Array('colspan' => 3, 'class' => 'Standard', 'style' => 'background-color:#FDF6D3;'), 'SMS уведомления платные, рекомендуем включать только --> \'Уведомления о блокировках заказа\'.'));
    }
}
#-------------------------------------------------------------------------------
$Row = Array(new Tag('TD',Array('class'=>'Head'),'Тип сообщения'));
#-------------------------------------------------------------------------------
$uNotifies = Array();
#-------------------------------------------------------------------------------
foreach(Array_Keys($Methods) as $MethodID){
  #-----------------------------------------------------------------------------
  $Method = $Methods[$MethodID];
  #-----------------------------------------------------------------------------
  if(!$Method['IsActive'])
    continue;
  #-----------------------------------------------------------------------------
  $uNotifies[$MethodID] = Array();
  #-----------------------------------------------------------------------------
  $Row[] = new Tag('TD',Array('class'=>'Head'),$Method['Name']);
}
#-------------------------------------------------------------------------------
if ($Methods['SMS']['IsActive']) {
    $Table = Array($Row2, $Row);
}
#-------------------------------------------------------------------------------
$Rows = DB_Select('Notifies','*',Array('Where'=>SPrintF('`UserID` = %u',$GLOBALS['__USER']['ID'])));
#-------------------------------------------------------------------------------
switch(ValueOf($Rows)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    # No more...
  break;
  case 'array':
    #---------------------------------------------------------------------------
    foreach($Rows as $Row)
      $uNotifies[$Row['MethodID']][] = $Row['TypeID'];
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Types = $Notifies['Types'];
#-------------------------------------------------------------------------------
foreach(Array_Keys($Types) as $TypeID){
  #-----------------------------------------------------------------------------
  $Type = $Types[$TypeID];
  #-----------------------------------------------------------------------------
  $Entrance = Tree_Entrance('Groups',(integer)$Type['GroupID']);
  #-----------------------------------------------------------------------------
  switch(ValueOf($Entrance)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      return ERROR | @Trigger_Error(400);
    case 'array':
      #-------------------------------------------------------------------------
      if(!In_Array($GLOBALS['__USER']['GroupID'],$Entrance))
        continue 2;
      #-------------------------------------------------------------------------
    break;
    default:
      return ERROR | @Trigger_Error(101);
  }
  #-----------------------------------------------------------------------------
  if(IsSet($Type['Title']))
    $Table[] = Array(new Tag('TD',Array('colspan'=>5,'class'=>'Separator'),$Type['Title']));
  #-----------------------------------------------------------------------------
  $Row = Array(new Tag('TD',Array('class'=>'Comment'),$Type['Name']));
  #-----------------------------------------------------------------------------
  foreach(Array_Keys($Methods) as $MethodID){
    #---------------------------------------------------------------------------
    $Method = $Methods[$MethodID];
    #---------------------------------------------------------------------------
    if(!$Method['IsActive'])
      continue;
    #---------------------------------------------------------------------------
    $Comp = Comp_Load(
      'Form/Input',
      Array(
        #-----------------------------------------------------------------------
        'name'  => SPrintF('%s[]',$MethodID),
        'type'  => 'checkbox',
        'value' => $TypeID
      )
    );
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
	#---------------------------------------------------------------------------
	// Если телефон не подтвержден то не выводить активными галочки для смс.
	if ($MethodID == 'SMS' && $__USER['MobileConfirmed'] == 0) {
	    $Comp->AddAttribs(Array('disabled' => 'true'));
	}
	else {
	    if (!In_Array($TypeID, $uNotifies[$MethodID]))
		$Comp->AddAttribs(Array('checked' => 'true'));
	}
	#---------------------------------------------------------------------------
    $Row[] = new Tag('TD',Array('align'=>'center'),$Comp);
  }
  #-----------------------------------------------------------------------------
  $Table[] = $Row;
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'button',
    'onclick' => 'UserNotifiesSet();',
    'value'   => 'Сохранить'
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('TD',Array('colspan'=>6,'align'=>'right'),$Comp));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Extended',$Table);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tab','User/Settings',new Tag('FORM',Array('name'=>'UserNotifiesSetForm','onsubmit'=>'return false;'),$Comp));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Comp);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------

?>
