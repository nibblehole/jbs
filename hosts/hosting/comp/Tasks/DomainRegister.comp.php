<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','DomainOrderID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('classes/Registrator.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Columns = Array('ID','DomainName','UserID','IsPrivateWhoIs','PersonID','(SELECT `Name` FROM `DomainSchemes` WHERE `DomainSchemes`.`ID` = `DomainOrdersOwners`.`SchemeID`) as `DomainZone`','ProfileID','ServerID','StatusID','(SELECT SUM(`YearsRemainded`) FROM `DomainConsider` WHERE `DomainConsider`.`DomainOrderID` = `DomainOrdersOwners`.`ID`) as `YearsRemainded`','Ns1Name','Ns1IP','Ns2Name','Ns2IP','Ns3Name','Ns3IP','Ns4Name','Ns4IP','(SELECT `Params` FROM `Servers` WHERE `Servers`.`ID` = `DomainOrdersOwners`.`ServerID`) AS `Params`');
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
    $Server = new Registrator();
    #---------------------------------------------------------------------------
    $ServerID = $DomainOrder['ServerID'];
    #---------------------------------------------------------------------------
    $IsSelected = $Server->Select((integer)$DomainOrder['ServerID']);
    #---------------------------------------------------------------------------
    switch(ValueOf($IsSelected)){
      case 'error':
        return ERROR | @Trigger_Error(500);
      case 'exception':
        return new gException('CANNOT_SELECT_REGISTRATOR','Не удалось выбрать регистратора');
      case 'true':
        #-----------------------------------------------------------------------
        $GLOBALS['TaskReturnInfo'] = Array(($DomainOrder['Params']['Name'])=>SPrintF('%s.%s',$DomainOrder['DomainName'],$DomainOrder['DomainZone']));
        #-----------------------------------------------------------------------
        switch($DomainOrder['StatusID']){
          case 'ForRegister':
            #-------------------------------------------------------------------
            $PersonID = $DomainOrder['PersonID'];
            #-------------------------------------------------------------------
            if($PersonID)
              $DomainRegister = $Server->DomainRegister(Mb_StrToLower($DomainOrder['DomainName'],'UTF-8'),$DomainOrder['DomainZone'],(integer)$DomainOrder['YearsRemainded'],$DomainOrder['Ns1Name'],$DomainOrder['Ns1IP'],$DomainOrder['Ns2Name'],$DomainOrder['Ns2IP'],$DomainOrder['Ns3Name'],$DomainOrder['Ns3IP'],$DomainOrder['Ns4Name'],$DomainOrder['Ns4IP'],$DomainOrder['IsPrivateWhoIs'],$PersonID);
            else{
              #-----------------------------------------------------------------
              $ProfileID = $DomainOrder['ProfileID'];
              #-----------------------------------------------------------------
              $Profile = DB_Select('Profiles',Array('TemplateID','Attribs'),Array('UNIQ','ID'=>$ProfileID));
              #-----------------------------------------------------------------
              switch(ValueOf($Profile)){
                case 'error':
                  return ERROR | @Trigger_Error(500);
                case 'exception':
                  return ERROR | @Trigger_Error(400);
                case 'array':
                  # готовим поля профиля
                  $ProfileCompile = Comp_Load('www/Administrator/API/ProfileCompile',Array('ProfileID'=>$ProfileID));
                  #-------------------------------------------------------------
                  switch(ValueOf($ProfileCompile)){
                    case 'error':
                      return ERROR | @Trigger_Error(500);
                    case 'exception':
                      return ERROR | @Trigger_Error(400);
                    case 'array':
                      # страна должна быть кодом
                      if(IsSet($Profile['Attribs']['pCountry'])){$ProfileCompile['Attribs']['pCountry'] = $Profile['Attribs']['pCountry'];}
		      if(IsSet($Profile['Attribs']['PasportCountry'])){$ProfileCompile['Attribs']['PasportCountry'] = $Profile['Attribs']['PasportCountry'];}
		      if(IsSet($Profile['Attribs']['jCountry'])){$ProfileCompile['Attribs']['jCountry'] = $Profile['Attribs']['jCountry'];}
                      break;
                    default:
                      return ERROR | @Trigger_Error(101);
                  }
                  #-------------------------------------------------------------
                  $DomainRegister = $Server->DomainRegister(Mb_StrToLower($DomainOrder['DomainName'],'UTF-8'),$DomainOrder['DomainZone'],(integer)$DomainOrder['YearsRemainded'],$DomainOrder['Ns1Name'],$DomainOrder['Ns1IP'],$DomainOrder['Ns2Name'],$DomainOrder['Ns2IP'],$DomainOrder['Ns3Name'],$DomainOrder['Ns3IP'],$DomainOrder['Ns4Name'],$DomainOrder['Ns4IP'],$DomainOrder['IsPrivateWhoIs'],'',$Profile['TemplateID'],$ProfileCompile['Attribs']);
                break;
                default:
                  return ERROR | @Trigger_Error(101);
              }
            }
            #-------------------------------------------------------------------
            switch(ValueOf($DomainRegister)){
              case 'error':
                return ERROR | @Trigger_Error(500);
              case 'exception':
                # add ticket to user, about it's exception
                $Clause = DB_Select('Clauses','*',Array('UNIQ','Where'=>"`Partition` = 'CreateTicket/ERROR_DOMAIN_REGISTER'"));
                switch(ValueOf($Clause)){
                case 'array':
                  $CompParameters = Array('Theme'         => SPrintF('%s %s.%s',$Clause['Title'],$DomainOrder['DomainName'],$DomainOrder['DomainZone']),
                                          'TargetGroupID' => 3100000,
                                          'TargetUserID'  => 100,
                                          'PriorityID'    => 'Low',
                                          'Message'       => trim(Strip_Tags($Clause['Text'])),
                                          'UserID'        => $DomainOrder['UserID'],
                                          'Flags'         => 'CloseOnSee'
                                         );
                  # set variable, for post-executing task
                  $GLOBALS['TaskReturnArray'] = Array('CompName' => 'www/API/TicketEdit', 'CompParameters' => $CompParameters);
                }
                #-------------------------------------------------------------------
                return new gException('TRANSFER_TO_OPERATOR_1','Задание не может быть выполнено автоматически и передано оператору',$DomainRegister);
              case 'false':
                return 300;
              case 'array':
                #---------------------------------------------------------------
                if(IsSet($DomainRegister['ContractID'])){
                  #-------------------------------------------------------------
                  $IsUpdate = DB_Update('DomainOrders',Array('PersonID'=>$DomainRegister['ContractID']),Array('ID'=>$DomainOrder['ID']));
                  if(Is_Error($IsUpdate))
                    return ERROR | @Trigger_Error(500);
                }
                #---------------------------------------------------------------
                $Task['Params']['TicketID'] = $DomainRegister['TicketID'];
                #---------------------------------------------------------------
                $IsUpdate = DB_Update('Tasks',Array('Params'=>$Task['Params']),Array('ID'=>$Task['ID']));
                if(Is_Error($IsUpdate))
                  return ERROR | @Trigger_Error(500);
                #---------------------------------------------------------------
                $Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>'DomainOrders','StatusID'=>'OnRegister','RowsIDs'=>$DomainOrderID,'Comment'=>'Регистратор принял заявку на регистрацию'));
                #---------------------------------------------------------------
                switch(ValueOf($Comp)){
                  case 'error':
                    return ERROR | @Trigger_Error(500);
                  case 'exception':
                    return ERROR | @Trigger_Error(400);
                  case 'array':
                    return 300;
                  default:
                    return ERROR | @Trigger_Error(101);
                }
              default:
                return ERROR | @Trigger_Error(101);
            }
          case 'OnRegister':
            #-------------------------------------------------------------------
            $TicketID = $Task['Params']['TicketID'];
            #-------------------------------------------------------------------
            $IsDomainRegister = $Server->CheckTask($TicketID);
            #-------------------------------------------------------------------
            switch(ValueOf($IsDomainRegister)){
              case 'error':
                return ERROR | @Trigger_Error(500);
              case 'exception':
                return new gException('TRANSFER_TO_OPERATOR_2','Задание не может быть выполнено автоматически и передано оператору');
              case 'false':
                return 300;
              case 'array':
                #---------------------------------------------------------------
                $IsUpdate = DB_Update('DomainOrders',Array('ProfileID'=>NULL,'DomainID'=>$IsDomainRegister['DomainID']),Array('ID'=>$DomainOrderID));
                if(Is_Error($IsUpdate))
                  return ERROR | @Trigger_Error(500);
                #---------------------------------------------------------------
                $Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>'DomainOrders','StatusID'=>'Active','RowsIDs'=>$DomainOrderID,'Comment'=>'Доменное имя зарегистрированно'));
                #---------------------------------------------------------------
                switch(ValueOf($Comp)){
                  case 'error':
                    return ERROR | @Trigger_Error(500);
                  case 'exception':
                    return ERROR | @Trigger_Error(400);
                  case 'array':
                    return TRUE;
                  default:
                    return ERROR | @Trigger_Error(101);
                }
              default:
                return ERROR | @Trigger_Error(101);
            }
          default:
            return new gException('WRONG_STATUS','Задание не может быть в данном статусе');
        }
      default:
        return ERROR | @Trigger_Error(101);
    }
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
