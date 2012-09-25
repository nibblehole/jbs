<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('IsCreate','Folder','StartDate','FinishDate','Details');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Result = Array('Title'=>'Распределение доходов/заказов на VPS по тарифам');
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY');
#-------------------------------------------------------------------------------
if(!$IsCreate)
  return $Result;
#-------------------------------------------------------------------------------
$VPSOrders = DB_Select('VPSSchemes',Array('Name','ServersGroupID','(SELECT COUNT(*) FROM `VPSOrders` WHERE `SchemeID` = `VPSSchemes`.`ID` AND `StatusID`="Active") as `Count`','(SELECT `Name` FROM `VPSServersGroups` WHERE `VPSServersGroups`.`ID`=`VPSSchemes`.`ServersGroupID`) as `ServersGroupName`','SUM(`CostDay`*`MinDaysPay`)*(SELECT COUNT(*) FROM `VPSOrders` WHERE `SchemeID` = `VPSSchemes`.`ID` AND `StatusID`="Active") as `Income`'),Array('SortOn'=>Array('ServersGroupID','SortID'),'GroupBy'=>'ID'));
#-------------------------------------------------------------------------------
switch(ValueOf($VPSOrders)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return $Result;
  case 'array':
    #---------------------------------------------------------------------------
    $NoBody->AddChild(new Tag('P','Данный вид статистики дает детальную информацию о количестве активных заказов и доходов на каждом из тарифов.'));
    #---------------------------------------------------------------------------
    $Table = Array(Array(new Tag('TD',Array('class'=>'Head'),'Наименование тарифа'),new Tag('TD',Array('class'=>'Head'),'Кол-во заказов'),new Tag('TD',Array('class'=>'Head'),'Доход')));
    #---------------------------------------------------------------------------
    $ServersGroupName = UniqID();
    #---------------------------------------------------------------------------
    foreach($VPSOrders as $VPSOrder){
      #-------------------------------------------------------------------------
      if($ServersGroupName != $VPSOrder['ServersGroupName']){
        #-----------------------------------------------------------------------
        $ServersGroupName = $VPSOrder['ServersGroupName'];
        #-----------------------------------------------------------------------
        $Table[] = $ServersGroupName;
      }
      #-------------------------------------------------------------------------
      $Table[] = Array($VPSOrder['Name'],(integer)$VPSOrder['Count'],$VPSOrder['Income']);
    }
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Tables/Extended',$Table);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $NoBody->AddChild($Comp);
    #---------------------------------------------------------------------------
    $Result['DOM'] = $NoBody;
    #---------------------------------------------------------------------------
    return $Result;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
