<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('LinkID');
/******************************************************************************/
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Links = &Links();
# Коллекция ссылок
$DOM = &$Links[$LinkID];
#-------------------------------------------------------------------------------
$TitleTag = $DOM->GetByTagName('TITLE');
#-------------------------------------------------------------------------------
$Title = Current($TitleTag);
#-------------------------------------------------------------------------------
$Title->AddText(SPrintF('%s - %s',Str_Replace('→','-',$Title->Text),HOST_ID),TRUE);
#-------------------------------------------------------------------------------
$__URI = $GLOBALS['__URI'];
#-------------------------------------------------------------------------------
if(IsSet($GLOBALS['_GET']['ServiceID'])){
	$Where = SPrintF("`Partition` = 'Header:%s'",MySQL_Real_Escape_String($GLOBALS['_GET']['ServiceID']));
}else{
	$Where = SPrintF("`Partition` = 'Header:%s'",MySQL_Real_Escape_String($__URI));
}
#-------------------------------------------------------------------------------
$Clauses = DB_Select('Clauses','ID',Array('Where'=>$Where));
#-------------------------------------------------------------------------------
switch(ValueOf($Clauses)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    # No more...
  break;
  case 'array':
    #---------------------------------------------------------------------------
    $Clause = Current($Clauses);
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Clauses/Load',$Clause['ID']);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $DOM->AddChild('Into',$Comp['DOM'],TRUE);
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
