<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
//$Where = "`StatusID` = 'Suspended' AND ROUND((`StatusDate` + 1296000 - UNIX_TIMESTAMP())/86400) IN (1,5,10)";
$Where = "`StatusID` = 'Suspended' AND ROUND((UNIX_TIMESTAMP() - `StatusDate`)/86400) IN (2,3,6,11,16,21,31,41,51,61,71,101)";
#-------------------------------------------------------------------------------
$ISPswOrders = DB_Select('ISPswOrdersOwners',Array('*'),Array('Where'=>$Where));
#-------------------------------------------------------------------------------
switch(ValueOf($ISPswOrders)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    # No more...
  break;
  case 'array':
    #---------------------------------------------------------------------------
    $GLOBALS['TaskReturnInfo'] = SPrintF('Notified %u accounts',SizeOf($ISPswOrders));
    #---------------------------------------------------------------------------
    foreach($ISPswOrders as $ISPswOrder){
      #-------------------------------------------------------------------------
      $IsSend = NotificationManager::sendMsg(new Message('ISPswNoticeDelete',(integer)$ISPswOrder['UserID'],Array('ISPswOrder'=>$ISPswOrder)));
      #-------------------------------------------------------------------------
      switch(ValueOf($IsSend)){
        case 'error':
          return ERROR | @Trigger_Error(500);
        case 'exception':
          # No more...
        case 'true':
          # No more...
        break;
        default:
          return ERROR | @Trigger_Error(101);
      }
    }
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
return MkTime(4,25,0,Date('n'),Date('j')+1,Date('Y'));
#-------------------------------------------------------------------------------

?>
