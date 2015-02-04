<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Args');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Null($Args))
	if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
		return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Args = IsSet($Args)?$Args:Args();
#-------------------------------------------------------------------------------
$DomainOrderID = (integer) @$Args['DomainOrderID'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Columns = Array('ID','UserID','OrderID','OrderDate','ContractID','DomainName','ProfileID','PersonID','IsPrivateWhoIs','WhoIs','UpdateDate','Ns1Name','Ns1IP','Ns2Name','Ns2IP','Ns3Name','Ns3IP','Ns4Name','Ns4IP','StatusID','StatusDate','(SELECT `Name` FROM `DomainSchemes` WHERE `DomainSchemes`.`ID` = `DomainOrdersOwners`.`SchemeID`) as `DomainZone`','(SELECT `Params` FROM `Servers` WHERE `Servers`.`ID` = (SELECT `ServerID` FROM `DomainSchemes` WHERE `DomainSchemes`.`ID` = `DomainOrdersOwners`.`SchemeID`)) as `Params`','(SELECT `IsAutoProlong` FROM `Orders` WHERE `DomainOrdersOwners`.`OrderID`=`Orders`.`ID`) AS `IsAutoProlong`');
#-------------------------------------------------------------------------------
$DomainOrder = DB_Select('DomainOrdersOwners',$Columns,Array('UNIQ','ID'=>$DomainOrderID));
#-------------------------------------------------------------------------------
switch(ValueOf($DomainOrder)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return ERROR | @Trigger_Error(400);
  case 'array':
    #---------------------------------------------------------------------------
    $__USER = $GLOBALS['__USER'];
    #---------------------------------------------------------------------------
    $IsPermission = Permission_Check('DomainOrdersRead',(integer)$__USER['ID'],(integer)$DomainOrder['UserID']);
    #---------------------------------------------------------------------------
    switch(ValueOf($IsPermission)){
      case 'error':
        return ERROR | @Trigger_Error(500);
      case 'exception':
        return ERROR | @Trigger_Error(400);
      case 'false':
        return ERROR | @Trigger_Error(700);
      case 'true':
        #-----------------------------------------------------------------------
        $DOM = new DOM();
        #-----------------------------------------------------------------------
        $Links = &Links();
        # Коллекция ссылок
        $Links['DOM'] = &$DOM;
        #-----------------------------------------------------------------------
        if(Is_Error($DOM->Load('Window')))
          return ERROR | @Trigger_Error(500);
        #-----------------------------------------------------------------------
        $DOM->AddText('Title',SPrintF('Заказ домена %s.%s',$DomainOrder['DomainName'],$DomainOrder['DomainZone']));
        #-----------------------------------------------------------------------
        $Table = Array('Общая информация');
        #-----------------------------------------------------------------------
        $Comp = Comp_Load('Formats/Order/Number',$DomainOrder['OrderID']);
        if(Is_Error($Comp))
          return ERROR | @Trigger_Error(500);
        #-----------------------------------------------------------------------
        $Table[] = Array('Номер',$Comp);
        #-----------------------------------------------------------------------
        $Comp = Comp_Load('Formats/Date/Extended',$DomainOrder['OrderDate']);
        if(Is_Error($Comp))
          return ERROR | @Trigger_Error(500);
        #-----------------------------------------------------------------------
        $Table[] = Array('Дата заказа',$Comp);
        #-----------------------------------------------------------------------
        $Comp = Comp_Load('Formats/Contract/Number',$DomainOrder['ContractID']);
        if(Is_Error($Comp))
          return ERROR | @Trigger_Error(500);
        #-----------------------------------------------------------------------
        $Table[] = Array('Договор №',$Comp);
        #-----------------------------------------------------------------------
        $Table[] = Array('Доменное имя',SPrintF('%s.%s',$DomainOrder['DomainName'],$DomainOrder['DomainZone']));
        #-----------------------------------------------------------------------
        $DomainConsider = DB_Select('DomainConsider','*',Array('Where'=>SPrintF('`DomainOrderID` = %u',$DomainOrder['ID'])));
        #-----------------------------------------------------------------------
        switch(ValueOf($DomainConsider)){
          case 'error':
            return ERROR | @Trigger_Error(500);
          case 'exception':
            # No more...
          break;
          case 'array':
            #-------------------------------------------------------------------
            $Tr = new Tag('TR');
            #-------------------------------------------------------------------
            foreach(Array('Лет зарез.','Лет ост.','Цена','Скидка') as $Text)
              $Tr->AddChild(new Tag('TD',Array('class'=>'Head'),$Text));
            #-------------------------------------------------------------------
            $Array = Array($Tr);
            #-------------------------------------------------------------------
            $RemainderSumm = 0.00;
            #-------------------------------------------------------------------
            foreach($DomainConsider as $ConsiderItem){
              #-----------------------------------------------------------------
              $Comp = Comp_Load('Formats/Percent',$ConsiderItem['Discont']);
              if(Is_Error($Comp))
                return ERROR | @Trigger_Error(500);
              #-----------------------------------------------------------------
              $Tr = new Tag('TR');
              #-----------------------------------------------------------------
              foreach(Array($ConsiderItem['YearsReserved'],$ConsiderItem['YearsRemainded'],$ConsiderItem['Cost'],$Comp) as $Text)
                $Tr->AddChild(new Tag('TD',Array('class'=>'Standard','align'=>'right'),$Text));
              #-----------------------------------------------------------------
              $Array[] = $Tr;
              #-----------------------------------------------------------------
              $RemainderSumm += (float)$ConsiderItem['Cost']*(integer)$ConsiderItem['YearsRemainded']*(1 - (float)$ConsiderItem['Discont']);
            }
            #-------------------------------------------------------------------
            $Comp = Comp_Load('Tables/Extended',$Array,'Способ учета');
            if(Is_Error($Comp))
              return ERROR | @Trigger_Error(500);
            #-------------------------------------------------------------------
            $Table[] = new Tag('DIV',Array('align'=>'center'),$Comp);
            #-------------------------------------------------------------------
            $IsPermission = Permission_Check('DomainOrdersConsider',(integer)$__USER['ID'],(integer)$DomainOrder['UserID']);
            #-------------------------------------------------------------------
            switch(ValueOf($IsPermission)){
              case 'error':
                return ERROR | @Trigger_Error(500);
              case 'exception':
                return ERROR | @Trigger_Error(400);
              case 'false':
                # No more...
              break;
              case 'true':
                #---------------------------------------------------------------
                if($RemainderSumm){
                  #-------------------------------------------------------------
                  $Comp = Comp_Load('Formats/Currency',$RemainderSumm);
                  if(Is_Error($Comp))
                    return ERROR | @Trigger_Error(500);
                  #-------------------------------------------------------------
                  $Comp = Comp_Load(
                    'Form/Input',
                    Array(
                      'type'    => 'button',
                      'onclick' => SPrintF("AjaxCall('/Administrator/API/DomainOrderRestore',{DomainOrderID:%u},'Отмена транзакций','GetURL(document.location);');",$DomainOrder['ID']),
                      'value'   => SPrintF('Вернуть %s',$Comp)
                    )
                  );
                  if(Is_Error($Comp))
                    return ERROR | @Trigger_Error(500);
                  #-------------------------------------------------------------
                  $Table[] = $Comp;
                }
              break;
              default:
                return ERROR | @Trigger_Error(101);
            }
          break;
          default:
            return ERROR | @Trigger_Error(101);
        }
        #-----------------------------------------------------------------------
        $Table[] = 'Информация владельца';
        #-----------------------------------------------------------------------
        $ProfileID = $DomainOrder['ProfileID'];
        #-----------------------------------------------------------------------
        if($ProfileID){
          #---------------------------------------------------------------------
          $Comp = Comp_Load(
            'Form/Input',
            Array(
              'type'    => 'button',
              'onclick' => SPrintF("ShowWindow('/ProfileInfo',{ProfileID:%u});",$ProfileID),
              'value'   => 'Просмотреть'
            )
          );
          if(Is_Error($Comp))
            return ERROR | @Trigger_Error(500);
          #---------------------------------------------------------------------
          $NoBody = new Tag('NOBODY',$Comp,new Tag('SPAN','|'));
          #---------------------------------------------------------------------
          $Comp = Comp_Load(
            'Form/Input',
            Array(
              'type'    => 'button',
              'onclick' => SPrintF("ShowWindow('/ProfileEdit',{ProfileID:%u});",$ProfileID),
              'value'   => 'Редактировать'
            )
          );
          if(Is_Error($Comp))
            return ERROR | @Trigger_Error(500);
          #---------------------------------------------------------------------
          $NoBody->AddChild($Comp);
          #---------------------------------------------------------------------
          $Table[] = Array('Данные профиля',$NoBody);
        }
        #-----------------------------------------------------------------------
        if($PersonID = $DomainOrder['PersonID'])
          $Table[] = Array('Договор регистратора',$PersonID);
        #-----------------------------------------------------------------------
        $Comp = Comp_Load('Formats/Logic',$DomainOrder['IsPrivateWhoIs']);
        if(Is_Error($Comp))
          return ERROR | @Trigger_Error(500);
        #-----------------------------------------------------------------------
        $Table[] = Array('Скрыть данные в WhoIs',$Comp);
        #-----------------------------------------------------------------------
        $WhoIs = Trim($DomainOrder['WhoIs']);
        #-----------------------------------------------------------------------
        if($WhoIs){
          #---------------------------------------------------------------------
          $Table[] = 'Данные службы WhoIs';
          #---------------------------------------------------------------------
          $Table[] = new Tag('TD',Array('class'=>'Standard','colspan'=>2),new Tag('PRE',Array('style'=>'font-size:10px;'),$WhoIs));
          #---------------------------------------------------------------------
          $Comp = Comp_Load('Formats/Date/Extended',$DomainOrder['UpdateDate']);
          if(Is_Error($Comp))
            return ERROR | @Trigger_Error(500);
          #---------------------------------------------------------------------
          $Table[] = Array('Последнее обновление',$Comp);
        }
        #-----------------------------------------------------------------------
        if($DomainOrder['Ns1Name'] || $DomainOrder['Ns1IP']){
          #---------------------------------------------------------------------
          $Table[] = 'Первичный сервер имен';
          #---------------------------------------------------------------------
          $Table[] = Array('Доменный адрес',$DomainOrder['Ns1Name']);
          #---------------------------------------------------------------------
	  if($DomainOrder['Ns1IP'])
            $Table[] = Array('IP адрес',$DomainOrder['Ns1IP']);
        }
        #-----------------------------------------------------------------------
        if($DomainOrder['Ns2Name'] || $DomainOrder['Ns2IP']){
          #---------------------------------------------------------------------
          $Table[] = 'Вторичный сервер имен';
          #---------------------------------------------------------------------
          $Table[] = Array('Доменный адрес',$DomainOrder['Ns2Name']);
          #---------------------------------------------------------------------
	  if($DomainOrder['Ns2IP'])
            $Table[] = Array('IP адрес',$DomainOrder['Ns2IP']);
        }
        #-----------------------------------------------------------------------
        if($DomainOrder['Ns3Name'] || $DomainOrder['Ns3IP']){
          #---------------------------------------------------------------------
          $Table[] = 'Дополнительный сервер имен';
          #---------------------------------------------------------------------
          $Table[] = Array('Доменный адрес',$DomainOrder['Ns3Name']);
          #---------------------------------------------------------------------
	  if($DomainOrder['Ns3IP'])
            $Table[] = Array('IP адрес',$DomainOrder['Ns3IP']);
        }
        #-----------------------------------------------------------------------
        if($DomainOrder['Ns4Name'] || $DomainOrder['Ns4IP']){
          #---------------------------------------------------------------------
          $Table[] = 'Расширенный сервер имен';
          #---------------------------------------------------------------------
          $Table[] = Array('Доменный адрес',$DomainOrder['Ns4Name']);
          #---------------------------------------------------------------------
	  if($DomainOrder['Ns4IP'])
            $Table[] = Array('IP адрес',$DomainOrder['Ns4IP']);
        }
	#-----------------------------------------------------------------------
	#-----------------------------------------------------------------------
	$Table[] = 'Прочее';
	#-----------------------------------------------------------------------
	$Comp = Comp_Load('Formats/Logic',$DomainOrder['IsAutoProlong']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-----------------------------------------------------------------------
	$Table[] = Array('Автопродление',$Comp);
	#-----------------------------------------------------------------------
        #-----------------------------------------------------------------------
        $Comp = Comp_Load('Statuses/State','DomainOrders',$DomainOrder);
        if(Is_Error($Comp))
          return ERROR | @Trigger_Error(500);
        #-----------------------------------------------------------------------
        $Table = Array_Merge($Table,$Comp);
        #-----------------------------------------------------------------------
        $Comp = Comp_Load('Tables/Standard',$Table);
        if(Is_Error($Comp))
          return ERROR | @Trigger_Error(500);
        #-----------------------------------------------------------------------
	#-----------------------------------------------------------------------
	$Form = new Tag('FORM',Array('method'=>'POST','name'=>'OrderInfo'),$Comp);
	#-----------------------------------------------------------------------
	#-----------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/Input',
			Array(
				'type'  => 'hidden',
				'name'  => 'OrderID',
				'value' => $DomainOrder['OrderID']
				)
			);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-----------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-----------------------------------------------------------------------
	#-----------------------------------------------------------------------
        $DOM->AddChild('Into',$Form);
        #-----------------------------------------------------------------------
        if(Is_Error($DOM->Build(FALSE)))
          return ERROR | @Trigger_Error(500);
        #-----------------------------------------------------------------------
        return Array('Status'=>'Ok','DOM'=>$DOM->Object);
      default:
        return ERROR | @Trigger_Error(101);
    }
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
