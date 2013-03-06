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
if(Is_Null($Args)){
  #-----------------------------------------------------------------------------
  if(Is_Error(System_Load('modules/Authorisation.mod')))
    return ERROR | @Trigger_Error(500);
}
#-------------------------------------------------------------------------------
$Args = IsSet($Args)?$Args:Args();
#-------------------------------------------------------------------------------
$TaskID = (integer) @$Args['TaskID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Task = DB_Select('Tasks',Array('ID','CreateDate','UserID','TypeID','Params','Errors','Result','ExecuteDate'),Array('UNIQ','ID'=>$TaskID));
#-------------------------------------------------------------------------------
switch(ValueOf($Task)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return new gException('TASK_NOT_FOUND','Задание не найдено');
  case 'array':
    #---------------------------------------------------------------------------
    $TaskID = $Task['ID'];
    #---------------------------------------------------------------------------
    $Free = DB_Query(SPrintF("SELECT IS_FREE_LOCK('Tasks%s') as `IsFree`",$TaskID));
    if(Is_Error($Free))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Rows = MySQL::Result($Free);
    if(Is_Error($Rows))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    if(Count($Rows) < 1)
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Row = Current($Rows);
    #---------------------------------------------------------------------------
    if(!$Row['IsFree'])
      return new gException('TASK_ALREADY_EXECUTING','Задание уже выполняется');
    #---------------------------------------------------------------------------
    $Lock = DB_Query(SPrintF("SELECT GET_LOCK('Tasks%s',5) as `IsLocked`",$TaskID));
    if(Is_Error($Lock))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Rows = MySQL::Result($Lock);
    if(Is_Error($Rows))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    if(Count($Rows) < 1)
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Row = Current($Rows);
    #---------------------------------------------------------------------------
    if(!$Row['IsLocked'])
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $UTask = Array();
    #---------------------------------------------------------------------------
    $Params = (array)$Task['Params'];
    #---------------------------------------------------------------------------
    Array_UnShift($Params,$Task);
    #---------------------------------------------------------------------------
    Array_UnShift($Params,$Path = SPrintF('Tasks/%s',$Task['TypeID']));
    #---------------------------------------------------------------------------
    if(Is_Error(System_Element(SPrintF('comp/%s.comp.php',$Path))))
      return new gException('TASK_HANDLER_NOT_APPOINTED','Заданию не назначен обработчик');
    #--------------------------------TRANSACTION--------------------------------
    if(Is_Error(DB_Transaction($TransactionID = UniqID('TaskExecute'))))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $__SYSLOG = &$GLOBALS['__SYSLOG'];
    #---------------------------------------------------------------------------
    $Index = Count($__SYSLOG);
    #---------------------------------------------------------------------------
    $Result = Call_User_Func_Array('Comp_Load',$Params);
    #---------------------------------------------------------------------------
    $Log = Implode("\n",Array_Slice($__SYSLOG,$Index));
    #---------------------------------------------------------------------------
    switch(ValueOf($Result)){
      case 'error':
        #-----------------------------------------------------------------------
        if(Is_Error(DB_Roll($TransactionID)))
          return ERROR | @Trigger_Error(500);
        #-----------------------------------------------------------------------
        $UTask['Errors'] = $Task['Errors'] + 1;
        $UTask['Result'] = SPrintF("%s\n\n%s",$Task['Result'],$Log);
        #-----------------------------------------------------------------------
        $Number = Comp_Load('Formats/Task/Number',$Task['ID']);
        if(Is_Error($Number))
          return ERROR | @Trigger_Error(500);
        #-----------------------------------------------------------------------
	$Event = Array(
			'UserID'	=> $Task['UserID'],
			'PriorityID'	=> 'Error',
			'Text'		=> SPrintF('Задание №%s [' . $Task['TypeID'] . '] вернуло ошибку выполнения',$Number),
		      );
	$Event = Comp_Load('Events/EventInsert',$Event);
	if(!$Event)
	  return ERROR | @Trigger_Error(500);
      break;
      case 'exception':
        #-----------------------------------------------------------------------
        if(Is_Error(DB_Roll($TransactionID)))
          return ERROR | @Trigger_Error(500);
        #-----------------------------------------------------------------------
        $UTask['Result']   = $Log;
        $UTask['IsActive'] = FALSE;
        #-----------------------------------------------------------------------
        $Number = Comp_Load('Formats/Task/Number',$Task['ID']);
        if(Is_Error($Number))
          return ERROR | @Trigger_Error(500);
        #-----------------------------------------------------------------------
	$Event = Array(
			'UserID'	=> $Task['UserID'],
			'PriorityID'	=> 'Error',
			'Text'		=> SPrintF('Задание №%s [' . $Task['TypeID'] . '] не может быть выполнено в автоматическом режиме',$Number),
		      );
	$Event = Comp_Load('Events/EventInsert',$Event);
	if(!$Event)
	  return ERROR | @Trigger_Error(500);
	#-----------------------------------------------------------------------
      break;
      case 'true':
        #-----------------------------------------------------------------------
        $UTask['Result']     = '';
        $UTask['IsExecuted'] = TRUE;
        #-----------------------------------------------------------------------
        if(Is_Error(DB_Commit($TransactionID)))
          return ERROR | @Trigger_Error(500);
      break;
      case 'integer':
        #-----------------------------------------------------------------------
        if($Result < Time()){
          #---------------------------------------------------------------------
          $ExecuteDate = $Task['ExecuteDate'];
          #---------------------------------------------------------------------
          if($ExecuteDate < Time())
            $ExecuteDate += Round((Time() - $ExecuteDate)/$Result + 1)*$Result;
          #---------------------------------------------------------------------
          $UTask['ExecuteDate'] = $ExecuteDate;
        }else
          $UTask['ExecuteDate'] = $Result;
        #-----------------------------------------------------------------------
        if(Is_Error(DB_Commit($TransactionID)))
          return ERROR | @Trigger_Error(500);
      break;
      default:
        return ERROR | @Trigger_Error(101);
    }
    #---------------------------------------------------------------------------
    #---------------------------------------------------------------------------
    # execute additional task, if need
    if(IsSet($GLOBALS['TaskReturnArray'])){
      #---------------------------------------------------------------------------
      # выполнено или нет?
      if(IsSet($Task['Params']['AdditionalTaskExecuted']) && $Task['Params']['AdditionalTaskExecuted'] != 'yes'){
        #---------------------------------------------------------------------------
        Debug(SPrintF('[comp/www/Administrator/API/TaskExecute]: TaskReturnArray is set, need run additional task: %s',$GLOBALS['TaskReturnArray']['CompName']));
	#---------------------------------------------------------------------------
        $Comp = Comp_Load($GLOBALS['TaskReturnArray']['CompName'],$GLOBALS['TaskReturnArray']['CompParameters']);
        if(Is_Error($Comp))
          return ERROR | @Trigger_Error(500);
	#---------------------------------------------------------------------------
        # set in task parameters: additional task is executed
        $Task['Params']['AdditionalTaskExecuted'] = 'yes';
	#---------------------------------------------------------------------------
        $IsUpdate = DB_Update('Tasks',Array('Params'=>$Task['Params']),Array('ID'=>$Task['ID']));
        if(Is_Error($IsUpdate))
          return ERROR | @Trigger_Error(500);
	#---------------------------------------------------------------------------
      }
      #---------------------------------------------------------------------------
      UnSet($GLOBALS['TaskReturnArray']);
      #---------------------------------------------------------------------------
    }
    #---------------------------------------------------------------------------
    #---------------------------------------------------------------------------
    $IsUpdate = DB_Update('Tasks',$UTask,Array('ID'=>$Task['ID']));
    if(Is_Error($IsUpdate))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $FreeLock = DB_Query(SPrintF("SELECT RELEASE_LOCK('Tasks%s')",$TaskID));
    if(Is_Error($FreeLock))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    return Array('Status'=>'Ok');
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
