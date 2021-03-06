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
$Search  = (string) @$Args['Search'];
#-------------------------------------------------------------------------------
$Search  = "case\s'\exception\'\:\n\s+return\s\$";
#-------------------------------------------------------------------------------
Header('Content-type: text/plain; charset=utf-8');
#-------------------------------------------------------------------------------
$Files = IO_Files(SYSTEM_PATH);
if(Is_Error($Files))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Count = Count($Files);
#-------------------------------------------------------------------------------
echo SPrintF("Finded %s files\n",$Count);
#-------------------------------------------------------------------------------
echo SPrintF("Search (%s)\n",$Search);
#-------------------------------------------------------------------------------
foreach($Files as $File){
  #-----------------------------------------------------------------------------
  $Source = IO_Read($File);
  if(Is_Error($Source))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  #if(!Preg_Match('/\.comp.php/',$File))
  #  continue;
  #-----------------------------------------------------------------------------
  if(Preg_Match(SPrintF('/%s/sU',$Search),$Source))
    echo SPrintF("%s\n",$File);
}
#-------------------------------------------------------------------------------


?>
