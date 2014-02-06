<?php
#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
class VPSServer{
# Тип системы сервера
public $SystemID = 'Default';
# Параметры связи с сервером
public $Settings = Array();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
public function Select($ServerID){
  /****************************************************************************/
  $__args_types = Array('integer');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Settings = DB_Select('VPSServers','*',Array('UNIQ','ID'=>$ServerID));
  #-----------------------------------------------------------------------------
  switch(ValueOf($Settings)){
    case 'error':
      return ERROR | @Trigger_Error('[Server->Select]: не удалось выбрать сервер');
    case 'exception':
      return new gException('SERVER_NOT_FOUND','Указаный сервер не найден');
    case 'array':
      #-------------------------------------------------------------------------
      $this->SystemID = $Settings['SystemID'];
      #-------------------------------------------------------------------------
      $this->Settings = $Settings;
      #-------------------------------------------------------------------------
      if(Is_Error(System_Load(SPrintF('libs/%s.php',$this->SystemID))))
        @Trigger_Error('[Server->Select]: не удалось загрузить целевую библиотеку');
      #-------------------------------------------------------------------------
      return TRUE;
    default:
      return ERROR | @Trigger_Error(101);
  }
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
public function Logon(){
  /****************************************************************************/
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  Array_UnShift($__args__,$this->Settings);
  #-----------------------------------------------------------------------------
  $Function = SPrintF('%s_Logon',$this->SystemID);
  #-----------------------------------------------------------------------------
  if(!Function_Exists($Function))
    return new gException('FUNCTION_NOT_SUPPORTED','Функция не поддерживается API модулем');
  #-----------------------------------------------------------------------------
  $Result = Call_User_Func_Array($Function,$__args__);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[Server->UserLogin]: не удалось вызвать целевую функцию');
  #-----------------------------------------------------------------------------
  return $Result;
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
public function GetUsers(){
  /****************************************************************************/
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  Array_UnShift($__args__,$this->Settings);
  #-----------------------------------------------------------------------------
  $Function = SPrintF('%s_Get_Users',$this->SystemID);
  #-----------------------------------------------------------------------------
  if(!Function_Exists($Function))
    return new gException('FUNCTION_NOT_SUPPORTED','Функция не поддерживается API модулем');
  #-----------------------------------------------------------------------------
  $Result = Call_User_Func_Array($Function,$__args__);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[Server->GetUsers]: не удалось вызвать целевую функцию');
  #-----------------------------------------------------------------------------
  return $Result;
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
public function Create(){
  /****************************************************************************/
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  Array_UnShift($__args__,$this->Settings);
  #-----------------------------------------------------------------------------
  $Function = SPrintF('%s_Create',$this->SystemID);
  #-----------------------------------------------------------------------------
  if(!Function_Exists($Function))
    return new gException('FUNCTION_NOT_SUPPORTED','Функция не поддерживается API модулем');
  #-----------------------------------------------------------------------------
  $Result = Call_User_Func_Array($Function,$__args__);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[Server->Create]: не удалось вызвать целевую функцию');
  #-----------------------------------------------------------------------------
  return $Result;
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
public function Active(){
  /****************************************************************************/
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  Array_UnShift($__args__,$this->Settings);
  #-----------------------------------------------------------------------------
  $Function = SPrintF('%s_Active',$this->SystemID);
  #-----------------------------------------------------------------------------
  if(!Function_Exists($Function))
    return new gException('FUNCTION_NOT_SUPPORTED','Функция не поддерживается API модулем');
  #-----------------------------------------------------------------------------
  $Result = Call_User_Func_Array($Function,$__args__);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[Server->Active]: не удалось вызвать целевую функцию');
  #-----------------------------------------------------------------------------
  return $Result;
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
public function Suspend(){
  /****************************************************************************/
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  Array_UnShift($__args__,$this->Settings);
  #-----------------------------------------------------------------------------
  $Function = SPrintF('%s_Suspend',$this->SystemID);
  #-----------------------------------------------------------------------------
  if(!Function_Exists($Function))
    return new gException('FUNCTION_NOT_SUPPORTED','Функция не поддерживается API модулем');
  #-----------------------------------------------------------------------------
  $Result = Call_User_Func_Array($Function,$__args__);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[Server->Suspend]: не удалось вызвать целевую функцию');
  #-----------------------------------------------------------------------------
  return $Result;
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
public function Delete(){
  /****************************************************************************/
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  Array_UnShift($__args__,$this->Settings);
  #-----------------------------------------------------------------------------
  $Function = SPrintF('%s_Delete',$this->SystemID);
  #-----------------------------------------------------------------------------
  if(!Function_Exists($Function))
    return new gException('FUNCTION_NOT_SUPPORTED','Функция не поддерживается API модулем');
  #-----------------------------------------------------------------------------
  $Result = Call_User_Func_Array($Function,$__args__);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[Server->Delete]: не удалось вызвать целевую функцию');
  #-----------------------------------------------------------------------------
  return $Result;
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
public function SchemeChange(){
  /****************************************************************************/
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  Array_UnShift($__args__,$this->Settings);
  #-----------------------------------------------------------------------------
  $Function = SPrintF('%s_Scheme_Change',$this->SystemID);
  #-----------------------------------------------------------------------------
  if(!Function_Exists($Function))
    return new gException('FUNCTION_NOT_SUPPORTED','Функция не поддерживается API модулем');
  #-----------------------------------------------------------------------------
  $Result = Call_User_Func_Array($Function,$__args__);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[Server->SchemeChange]: не удалось вызвать целевую функцию');
  #-----------------------------------------------------------------------------
  return $Result;
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
public function PasswordChange(){
  /****************************************************************************/
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  Array_UnShift($__args__,$this->Settings);
  #-----------------------------------------------------------------------------
  $Function = SPrintF('%s_Password_Change',$this->SystemID);
  #-----------------------------------------------------------------------------
  if(!Function_Exists($Function))
    return new gException('FUNCTION_NOT_SUPPORTED','Функция не поддерживается API модулем');
  #-----------------------------------------------------------------------------
  $Result = Call_User_Func_Array($Function,$__args__);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[Server->PasswordChange]: не удалось вызвать целевую функцию');
  #-----------------------------------------------------------------------------
  return $Result;
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
public function MainUsage(){
  /****************************************************************************/
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  Array_UnShift($__args__,$this->Settings);
  #-----------------------------------------------------------------------------
  $Function = SPrintF('%s_MainUsage',$this->SystemID);
  #-----------------------------------------------------------------------------
  if(!Function_Exists($Function))
    return new gException('FUNCTION_NOT_SUPPORTED','Функция не поддерживается API модулем');
  #-----------------------------------------------------------------------------
  $Result = Call_User_Func_Array($Function,$__args__);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[Server->MainUsage]: не удалось вызвать целевую функцию');
  #-----------------------------------------------------------------------------
  return $Result;
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
public function CheckIsActive(){
  /****************************************************************************/
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  Array_UnShift($__args__,$this->Settings);
  #-----------------------------------------------------------------------------
  $Function = SPrintF('%s_CheckIsActive',$this->SystemID);
  #-----------------------------------------------------------------------------
  if(!Function_Exists($Function))
    return new gException('FUNCTION_NOT_SUPPORTED','Функция не поддерживается API модулем');
  #-----------------------------------------------------------------------------
  $Result = Call_User_Func_Array($Function,$__args__);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[Server->CheckIsActive]: не удалось вызвать целевую функцию');
  #-----------------------------------------------------------------------------
  return $Result;
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
public function Reboot(){
  /****************************************************************************/
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  Array_UnShift($__args__,$this->Settings);
  #-----------------------------------------------------------------------------
  $Function = SPrintF('%s_Reboot',$this->SystemID);
  #-----------------------------------------------------------------------------
  if(!Function_Exists($Function))
    return new gException('FUNCTION_NOT_SUPPORTED','Функция не поддерживается API модулем');
  #-----------------------------------------------------------------------------
  $Result = Call_User_Func_Array($Function,$__args__);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[Server->CheckIsActive]: не удалось вызвать целевую функцию');
  #-----------------------------------------------------------------------------
  return $Result;
}
#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
?>
