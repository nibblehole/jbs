<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Comp = Comp_Load('Tables/Widget','HostingOrders[User]');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!$Comp->Attribs['count'])
	return FALSE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Title'=>'Последние заказы на хостинг','DOM'=>$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
