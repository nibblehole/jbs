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
$ContractID	=  (string) @$Args['ContractID'];
$DSSchemeID	= (integer) @$Args['DSSchemeID'];
$StepID		= (integer) @$Args['StepID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php','libs/WhoIs.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Base')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddAttribs('MenuLeft',Array('args'=>'User/Services'));
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Аренда сервера');
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/DSOrder.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'DSOrderForm','onsubmit'=>'return false;'));
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
if($StepID){
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'name'  => 'ContractID',
      'type'  => 'hidden',
      'value' => $ContractID
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Form->AddChild($Comp);
  #-----------------------------------------------------------------------------
  $Regulars = Regulars();
  #-----------------------------------------------------------------------------
  if(!$DSSchemeID)
    return new gException('DS_SCHEME_NOT_DEFINED','Сервер для аренды не выбран');
  #-----------------------------------------------------------------------------
  $DSScheme = DB_Select('DSSchemes',Array('ID','Name','IsActive'),Array('UNIQ','ID'=>$DSSchemeID));
  #-----------------------------------------------------------------------------
  switch(ValueOf($DSScheme)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      return ERROR | @Trigger_Error(400);
    case 'array':
      #-------------------------------------------------------------------------
      if(!$DSScheme['IsActive'])
        return new gException('SCHEME_NOT_ACTIVE','Выбранный тарифный план заказа DS не активен');
      #-------------------------------------------------------------------------
      $Table = Array(Array('Тарифный план',$DSScheme['Name']));
      #-------------------------------------------------------------------------
      $Comp = Comp_Load(
        'Form/Input',
        Array(
          'name'  => 'DSSchemeID',
          'type'  => 'hidden',
          'value' => $DSScheme['ID']
        )
      );
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $Form->AddChild($Comp);
      #-------------------------------------------------------------------------
      $Rows = Array();
      #-------------------------------------------------------------------------
#      $Comp = Comp_Load(
 #       'Form/Input',
 #       Array(
 #         'type'    => 'button',
 #         'onclick' => SPrintF("ShowWindow('/DSOrder',{DSSchemeID:%u,Domain:'%s'});",$DSScheme['ID'],$Domain),
 #         'value'   => 'Изменить домен'
 #       )
 #     );
 #     if(Is_Error($Comp))
 #       return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $Div = new Tag('DIV',Array('align'=>'right'),'');
      #-------------------------------------------------------------------------
      $Comp = Comp_Load(
        'Form/Input',
        Array(
          'type'    => 'button',
          'onclick' => 'DSOrder();',
          'value'   => 'Продолжить'
        )
      );
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $Div->AddChild($Comp);
      #-------------------------------------------------------------------------
      $Table[] = $Div;
      #-------------------------------------------------------------------------
      $Comp = Comp_Load('Tables/Standard',$Table,Array('width'=>400));
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $Form->AddChild($Comp);
      #-------------------------------------------------------------------------
      $DOM->AddChild('Into',$Form);
    break;
    default:
      return ERROR | @Trigger_Error(101);
  }
}else{
  #-----------------------------------------------------------------------------
  $__USER = $GLOBALS['__USER'];
  #-----------------------------------------------------------------------------
  $Contracts = DB_Select('Contracts',Array('ID','Customer'),Array('Where'=>SPrintF("`UserID` = %u AND `TypeID` != 'NaturalPartner'",$__USER['ID'])));
  #-----------------------------------------------------------------------------
  switch(ValueOf($Contracts)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      return new gException('CONTRACTS_NOT_FOUND','Система не обнаружила у Вас ни одного договора. Пожалуйста, перейдите в раздел [Мой офис - Договоры] и сформируйте хотя бы 1 договор.');
    case 'array':
      #-------------------------------------------------------------------------
      $Options = Array();
      #-------------------------------------------------------------------------
      foreach($Contracts as $Contract){
        #-----------------------------------------------------------------------
        $Customer = $Contract['Customer'];
	#-------------------------------------------------------------------------------
	$Number = Comp_Load('Formats/Contract/Number',$Contract['ID']);
	if(Is_Error($Number))
		return ERROR | @Trigger_Error(500);
        #-----------------------------------------------------------------------
        if(Mb_StrLen($Customer) > 20)
          $Customer = SPrintF('%s...',Mb_SubStr($Customer,0,20));
        #-----------------------------------------------------------------------
	$Options[$Contract['ID']] = SPrintF('#%s / %s',$Number,$Customer);
	#-------------------------------------------------------------------------------
      }
      #-------------------------------------------------------------------------
      $Comp = Comp_Load('Form/Select',Array('name'=>'ContractID'),$Options,$ContractID);
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $NoBody = new Tag('NOBODY',$Comp);
      #-------------------------------------------------------------------------
      $Window = JSON_Encode(Array('Url'=>'/DSOrder','Args'=>Array()));
      #-------------------------------------------------------------------------
      $A = new Tag('A',Array('href'=>SPrintF("javascript:ShowWindow('/ContractMake',{Window:'%s'});",Base64_Encode($Window))),'[новый]');
      #-------------------------------------------------------------------------
      $NoBody->AddChild($A);
      #-------------------------------------------------------------------------
      $Table = Array(Array('Базовый договор',$NoBody));
      #-------------------------------------------------------------------------
      $UniqID = UniqID('DSSchemes');
      #-------------------------------------------------------------------------
      $Comp = Comp_Load('Services/Schemes','DSSchemes',$__USER['ID'],Array('Name','ServersGroupID'),$UniqID);
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $Columns = Array('ID','Name','ServersGroupID','UserComment','CostMonth','CostInstall','cputype', 'cpuarch', 'numcpu', 'numcores', 'cpufreq', 'ram', 'raid', 'disk1', 'disk2','(SELECT `Name` FROM `DSServersGroups` WHERE `DSServersGroups`.`ID` = `ServersGroupID`) as `ServersGroupName`','(SELECT `Comment` FROM `DSServersGroups` WHERE `DSServersGroups`.`ID` = `ServersGroupID`) as `ServersGroupComment`','(SELECT `SortID` FROM `DSServersGroups` WHERE `DSServersGroups`.`ID` = `ServersGroupID`) as `ServersGroupSortID`');
      #-------------------------------------------------------------------------
      $DSSchemes = DB_Select($UniqID,$Columns,Array('SortOn'=>Array('ServersGroupSortID','SortID'),'Where'=>"`IsActive` = 'yes' AND `RemainServers` > 0"));
      #-------------------------------------------------------------------------
      switch(ValueOf($DSSchemes)){
        case 'error':
          return ERROR | @Trigger_Error(500);
        case 'exception':
          return new gException('DS_SCHEMES_NOT_FOUND','Нет свободных серверов');
        case 'array':
	  # массив с именами

          #---------------------------------------------------------------------
          $NoBody = new Tag('NOBODY');
          #---------------------------------------------------------------------
          $Tr = new Tag('TR');
          #---------------------------------------------------------------------
          $Tr->AddChild(new Tag('TD',Array('class'=>'Head','colspan'=>2),'Сервер'));
          $Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center'),'Цена в мес.'));
	  #---------------------------------------------------------------------
	  $Td = new Tag('TD',Array('class'=>'Head','align'=>'center'),new Tag('SPAN','Процессор'),new Tag('SPAN',Array('style'=>'font-weight:bold;font-size:14px;'),'?'));
	  $LinkID = UniqID('Prompt');
	  $Links[$LinkID] = &$Td;
	  $Comp = Comp_Load('Form/Prompt',$LinkID,'Информация о процессоре(-ах) усновленном в сервере');
	  if(Is_Error($Comp))
	    return ERROR | @Trigger_Error(500);
	  $Tr->AddChild($Td);
	  #---------------------------------------------------------------------
          #---------------------------------------------------------------------
	  $Td = new Tag('TD',Array('class'=>'Head','align'=>'center'),new Tag('SPAN','Числ. проц.'),new Tag('SPAN',Array('style'=>'font-weight:bold;font-size:14px;'),'?'));
	  $LinkID = UniqID('Prompt');
	  $Links[$LinkID] = &$Td;
	  $Comp = Comp_Load('Form/Prompt',$LinkID,'Число процессоров (сокетов)');
	  if(Is_Error($Comp))
	    return ERROR | @Trigger_Error(500);
	  $Tr->AddChild($Td);
          #---------------------------------------------------------------------
	  #---------------------------------------------------------------------
          $Td = new Tag('TD',Array('class'=>'Head','align'=>'center'),new Tag('SPAN','Ядер'),new Tag('SPAN',Array('style'=>'font-weight:bold;font-size:14px;'),'?'));
          $Links[$LinkID] = &$Td;
          $Comp = Comp_Load('Form/Prompt',$LinkID,'Число ядер в каждом процессоре');
          if(Is_Error($Comp))
            return ERROR | @Trigger_Error(500);
          $Tr->AddChild($Td);
	  #---------------------------------------------------------------------
          #---------------------------------------------------------------------
          $Td = new Tag('TD',Array('class'=>'Head','align'=>'center'),new Tag('SPAN','MHz'),new Tag('SPAN',Array('style'=>'font-weight:bold;font-size:14px;'),'?'));
          $Links[$LinkID] = &$Td;
          $Comp = Comp_Load('Form/Prompt',$LinkID,'Частота работы, каждого ядра');
          if(Is_Error($Comp))
            return ERROR | @Trigger_Error(500);
          $Tr->AddChild($Td);
	  #---------------------------------------------------------------------
          #---------------------------------------------------------------------
	  $Td = new Tag('TD',Array('class'=>'Head','align'=>'center'),new Tag('SPAN','RAM'),new Tag('SPAN',Array('style'=>'font-weight:bold;font-size:14px;'),'?'));
	  $Links[$LinkID] = &$Td;
	  $Comp = Comp_Load('Form/Prompt',$LinkID,'Количество установленной оперативной памяти, МегаБайт');
	  if(Is_Error($Comp))
	    return ERROR | @Trigger_Error(500);
	  $Tr->AddChild($Td);
	  #---------------------------------------------------------------------
	  #---------------------------------------------------------------------
	  $Td = new Tag('TD',Array('class'=>'Head','align'=>'center'),new Tag('SPAN','RAID'),new Tag('SPAN',Array('style'=>'font-weight:bold;font-size:14px;'),'?'));
	  $Links[$LinkID] = &$Td;
	  $Comp = Comp_Load('Form/Prompt',$LinkID,'Тип установленного RAID контроллера, его характеристики');
	  if(Is_Error($Comp))
	    return ERROR | @Trigger_Error(500);
	  $Tr->AddChild($Td);
	  #---------------------------------------------------------------------
	  #---------------------------------------------------------------------
	  $Td = new Tag('TD',Array('class'=>'Head','align'=>'center'),new Tag('SPAN','disk1'),new Tag('SPAN',Array('style'=>'font-weight:bold;font-size:14px;'),'?'));
	  $Links[$LinkID] = &$Td;
	  $Comp = Comp_Load('Form/Prompt',$LinkID,'Характеристики первого диска установленного в сервер');
	  if(Is_Error($Comp))
	    return ERROR | @Trigger_Error(500);
	  $Tr->AddChild($Td);
	  #---------------------------------------------------------------------
	  #---------------------------------------------------------------------
	  $Td = new Tag('TD',Array('class'=>'Head','align'=>'center'),new Tag('SPAN','disk2'),new Tag('SPAN',Array('style'=>'font-weight:bold;font-size:14px;'),'?'));
	  $Links[$LinkID] = &$Td;
	  $Comp = Comp_Load('Form/Prompt',$LinkID,'Характеристики второго диска установленного в сервер');
	  if(Is_Error($Comp))
	    return ERROR | @Trigger_Error(500);
	  $Tr->AddChild($Td);


	  #---------------------------------------------------------------------
          UnSet($Links[$LinkID]);
          #---------------------------------------------------------------------
          $Rows = Array($Tr);
          #---------------------------------------------------------------------
          $ServersGroupName = UniqID();
          #---------------------------------------------------------------------
          foreach($DSSchemes as $DSScheme){
            #-------------------------------------------------------------------
            if($ServersGroupName != $DSScheme['ServersGroupName']){
              #-----------------------------------------------------------------
              $ServersGroupName = $DSScheme['ServersGroupName'];
              #-----------------------------------------------------------------
              $Comp = Comp_Load('Formats/String',$DSScheme['ServersGroupComment'],75);
              if(Is_Error($Comp))
                return ERROR | @Trigger_Error(500);
              #-----------------------------------------------------------------
              $Rows[] = new Tag('TR',new Tag('TD',Array('colspan'=>11,'class'=>'Separator'),new Tag('SPAN',Array('style'=>'font-size:16px;'),SPrintF('%s |',$ServersGroupName)),new Tag('SPAN',Array('style'=>'font-size:11px;'),$Comp)));
            }
            #-------------------------------------------------------------------
            $Comp = Comp_Load(
              'Form/Input',
              Array(
                'name'  => 'DSSchemeID',
                'type'  => 'radio',
                'value' => $DSScheme['ID']
              )
            );
            if(Is_Error($Comp))
              return ERROR | @Trigger_Error(500);
            #-------------------------------------------------------------------
            if($DSScheme['ID'] == $DSSchemeID)
              $Comp->AddAttribs(Array('checked'=>'true'));
            #-------------------------------------------------------------------
            $Comment = $DSScheme['UserComment'];
            #-------------------------------------------------------------------
            if($Comment)
              $Rows[] = new Tag('TR',new Tag('TD',Array('colspan'=>2)),new Tag('TD',Array('colspan'=>9,'class'=>'Standard','style'=>'background-color:#FDF6D3;'),$Comment));
            #-------------------------------------------------------------------
            $CostMonth = Comp_Load('Formats/Currency',$DSScheme['CostMonth']);
            if(Is_Error($CostMonth))
              return ERROR | @Trigger_Error(500);
	    #-------------------------------------------------------------------
	    $cpu = Comp_Load('Formats/DSOrder/CPUType', $DSScheme['cputype'], 12);
	    if(Is_Error($cpu))
	      return ERROR | @Trigger_Error(500);
	    #-------------------------------------------------------------------
	    $raid = Comp_Load('Formats/String',$DSScheme['raid'],9);
	    if(Is_Error($raid))
	      return ERROR | @Trigger_Error(500);
            #-------------------------------------------------------------------
            $Rows[] = new Tag('TR',
	    			new Tag('TD',Array('width'=>20),$Comp),
				new Tag('TD',Array('class'=>'Comment'),$DSScheme['Name']),
				new Tag('TD',Array('class'=>'Standard','align'=>'right'),$CostMonth),
				new Tag('TD',Array('class'=>'Standard','align'=>'left'),$cpu),
				new Tag('TD',Array('class'=>'Standard','align'=>'right'),$DSScheme['numcpu']),
				new Tag('TD',Array('class'=>'Standard','align'=>'right'),$DSScheme['numcores']),
				new Tag('TD',Array('class'=>'Standard','align'=>'right'),$DSScheme['cpufreq']),
				new Tag('TD',Array('class'=>'Standard','align'=>'right'),$DSScheme['ram']),
				new Tag('TD',Array('class'=>'Standard','align'=>'right'),$raid),
				new Tag('TD',Array('class'=>'Standard','align'=>'right'),$DSScheme['disk1']),
				new Tag('TD',Array('class'=>'Standard','align'=>'right'),$DSScheme['disk2'])
			);
          }
          #---------------------------------------------------------------------
          $Comp = Comp_Load('Tables/Extended',$Rows,Array('align'=>'center'));
          if(Is_Error($Comp))
            return ERROR | @Trigger_Error(500);
          #---------------------------------------------------------------------
          $Table[] = $Comp;
          #---------------------------------------------------------------------
#          $Comp = Comp_Load(
#            'Form/Input',
#            Array(
#              'type'    => 'button',
#              'name'    => 'Submit',
#              'onclick' => "ShowWindow('/DSOrder',FormGet(form));",
#              'value'   => 'Продолжить'
#            )
#          );
	   $Comp = Comp_Load(
	     'Form/Input',
	     Array(
	       'type'    => 'button',
	       'onclick' => 'DSOrder();',
	       'value'   => 'Продолжить'
	     )
	  );

          if(Is_Error($Comp))
            return ERROR | @Trigger_Error(500);
          #---------------------------------------------------------------------
          $Table[] = $Comp;
          #---------------------------------------------------------------------
          $Comp = Comp_Load('Tables/Standard',$Table);
          if(Is_Error($Comp))
            return ERROR | @Trigger_Error(500);
          #---------------------------------------------------------------------
          $Form->AddChild($Comp);
          #---------------------------------------------------------------------
          $Comp = Comp_Load(
            'Form/Input',
            Array(
              'name'  => 'StepID',
              'value' => 1,
              'type'  => 'hidden',
            )
          );
          if(Is_Error($Comp))
            return ERROR | @Trigger_Error(500);
          #---------------------------------------------------------------------
          $Form->AddChild($Comp);
          #---------------------------------------------------------------------
          #---------------------------------------------------------------------
          $DOM->AddChild('Into',$Form);
        break 2;
        default:
          return ERROR | @Trigger_Error(101);
      }
    default:
      return ERROR | @Trigger_Error(101);
  }
}
#-------------------------------------------------------------------------------
$Out = $DOM->Build(FALSE);
#-------------------------------------------------------------------------------
if(Is_Error($Out))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------

?>
