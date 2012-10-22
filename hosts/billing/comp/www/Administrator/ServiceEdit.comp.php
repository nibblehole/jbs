<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$ServiceID = (integer) @$Args['ServiceID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($ServiceID){
  #-----------------------------------------------------------------------------
  $Service = DB_Select('Services','*',Array('UNIQ','ID'=>$ServiceID));
  #-----------------------------------------------------------------------------
  switch(ValueOf($Service)){
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
  $Service = Array(
    #---------------------------------------------------------------------------
    'GroupID'         => 20,
    'UserID'          => 1,
    'ServicesGroupID' => 1000,
    'Name'            => 'Новая услуга',
    'Item'            => 'Услуга',
    'Emblem'          => '',
    'Measure'         => 'шт.',
    'ConsiderTypeID'  => 'Upon',
    'CostOn'          => 10,
    'Cost'            => 10,
    'IsProtected'     => FALSE,
    'IsActive'        => TRUE,
    'IsProlong'       => TRUE,
    'IsConditionally' => FALSE,
    'SortID'          => 10
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
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/Administrator/ServiceEdit.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/CheckBox.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
$Title = ($ServiceID?'Редактирование услуги':'Добавление новой услуги');
#-------------------------------------------------------------------------------
$DOM->AddText('Title',$Title);
#-------------------------------------------------------------------------------
$Table = Array();
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Owner','Владелец услуги',$Service['GroupID'],$Service['UserID']);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
$ServicesGroups = DB_Select('ServicesGroups',Array('ID','Name'));
#-------------------------------------------------------------------------------
switch(ValueOf($ServicesGroups)){
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
#-------------------------------------------------------------------------------
$Options = Array();
#-------------------------------------------------------------------------------
foreach($ServicesGroups as $ServiceGroup)
  $Options[$ServiceGroup['ID']] = $ServiceGroup['Name'];
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'ServicesGroupID'),$Options,$Service['ServicesGroupID']);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Группа услуг',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'Name',
    'size'  => 30,
    'value' => $Service['Name']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Название услуги',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'Item',
    'size'  => 20,
    'value' => $Service['Item']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Раздел меню',$Comp);
#-------------------------------------------------------------------------------
$Emblem = MB_StrLen($Service['Emblem'],'ASCII');
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Upload','Emblem',$Emblem?SPrintF('%01.2f Кб.',$Emblem/1024):'не загружена');
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Эмблема (72x72, *.jpg)',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'Measure',
    'size'  => 10,
    'value' => $Service['Measure']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$IsProtected = $Service['IsProtected'];
#-------------------------------------------------------------------------------
if($IsProtected)
  $Comp->AddAttribs(Array('disabled'=>TRUE));
#-------------------------------------------------------------------------------
$Table[] = Array('Ед. измерения',$Comp);
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Types = $Config['Services']['Consider']['Types'];
#-------------------------------------------------------------------------------
$Options = Array();
#-------------------------------------------------------------------------------
foreach($Types as $TypeID=>$Type)
  $Options[$TypeID] = $Type['Name'];
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'ConsiderTypeID'),$Options,$Service['ConsiderTypeID']);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($IsProtected)
  $Comp->AddAttribs(Array('disabled'=>TRUE));
#-------------------------------------------------------------------------------
$Table[] = Array('Способ учета',$Comp);
#-------------------------------------------------------------------------------
$Attribs = Array('name'=>'CostOn','value'=>SPrintF('%01.2f',$Service['CostOn']));
#-------------------------------------------------------------------------------
if($IsProtected)
  $Attribs['disabled'] = TRUE;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Summ',$Attribs);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Стоимость подключения',$Comp);
#-------------------------------------------------------------------------------
$Attribs = Array('name'=>'Cost','value'=>SPrintF('%01.2f',$Service['Cost']));
#-------------------------------------------------------------------------------
if($IsProtected)
  $Attribs['disabled'] = TRUE;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Summ',$Attribs);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Цена единицы',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'IsActive','value'=>'yes'));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($Service['IsActive'])
  $Comp->AddAttribs(Array('checked'=>'yes'));
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsActive\'); return false;'),'Услуга активна'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'IsProlong','value'=>'yes'));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($Service['IsProlong'])
  $Comp->AddAttribs(Array('checked'=>'yes'));
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsProlong\'); return false;'),'Возможность продления'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'IsConditionally','value'=>'yes','prompt'=>'Пользователь может продлевать эту услугу условным счётом, при условии что он её ранее оплачивал'));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($Service['IsConditionally'])
  $Comp->AddAttribs(Array('checked'=>'yes'));
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsConditionally\'); return false;'),'Может быть оплачена условно'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'SortID',
    'size'  => 5,
    'value' => $Service['SortID']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Порядок сортировки',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'button',
    'onclick' => 'ServiceEdit();',
    'value'   => ($ServiceID?'Сохранить':'Добавить')
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
$Form = new Tag('FORM',Array('name'=>'ServiceEditForm','onsubmit'=>'return false;'),$Comp);
#-------------------------------------------------------------------------------
if($ServiceID){
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'name'  => 'ServiceID',
      'type'  => 'hidden',
      'value' => $ServiceID
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Form->AddChild($Comp);
}
#-------------------------------------------------------------------------------
if($ServiceID){
  #-----------------------------------------------------------------------------
  if(!$IsProtected){
    #---------------------------------------------------------------------------
    $Iframe = new Tag('IFRAME',Array('name'=>'ServiceFields','src'=>SPrintF('/Administrator/ServiceFields?ServiceID=%s',$ServiceID),'width'=>'450px','height'=>'450px'),'Загрузка...');
    #---------------------------------------------------------------------------
    $Form = new Tag('TABLE',Array('cellspacing'=>5),new Tag('TR',new Tag('TD',$Form),new Tag('TD',$Iframe)));
  }
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
