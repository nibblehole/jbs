<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Attribs');
/******************************************************************************/
Eval(COMP_INIT);
#******************************************************************************#
#******************************************************************************#
$Input = new Tag('INPUT');
#-------------------------------------------------------------------------------
if(IsSet($Attribs['prompt'])){
  #-----------------------------------------------------------------------------
  $Prompt = $Attribs['prompt'];
  #-----------------------------------------------------------------------------
  UnSet($Attribs['prompt']);
  #-----------------------------------------------------------------------------
  $LinkID = UniqID('Input');
  #-----------------------------------------------------------------------------
  $Links = &Links();
  #-----------------------------------------------------------------------------
  $Links[$LinkID] = &$Input;
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load('Form/Prompt',$LinkID,$Prompt);
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  UnSet($Links[$LinkID]);
}
#-------------------------------------------------------------------------------
$Input->AddAttribs($Attribs);
#-------------------------------------------------------------------------------
return $Input;
#-------------------------------------------------------------------------------

?>
