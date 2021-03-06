<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('LinkID','ModeID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Links = &Links();
# Коллекция ссылок
$Template = &$Links[$LinkID];
/******************************************************************************/
/******************************************************************************/
if($Template['Source']['Count'] < 1)
	return FALSE;
#-------------------------------------------------------------------------------
$Links['DOM']->AddChild('Head',new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/StatusSet.js}')));
#-------------------------------------------------------------------------------
$Table = Array();
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Statuses = $Config['Statuses'][$ModeID];
#-------------------------------------------------------------------------------
$Options = Array();
#-------------------------------------------------------------------------------
foreach(Array_Keys($Statuses) as $StatusID)
	$Options[$StatusID] = $Statuses[$StatusID]['Name'];
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'StatusID','style'=>'width: 100%;'),$Options);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Статус',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$OnKeyPress = SPrintF("ctrlEnterEvent(event) && ShowConfirm('Вы подтверждаете установку статуса?',\"StatusSet('%s');\");",$ModeID);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('name'=>'Comment','type'=>'text','OnKeyPress'=>$OnKeyPress));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Комментарий',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('onclick'=>SPrintF("ShowConfirm('Вы подтверждаете установку статуса?',\"StatusSet('%s');\");",$ModeID),'type'=>'button','value'=>'Установить'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Div = new Tag('DIV',Array('id'=>'AfterSuperTableSetStatus'),$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('name'=>'ModeID','type'=>'hidden','value'=>$ModeID));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Div->AddChild($Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Div;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
