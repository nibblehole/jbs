<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru  */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('LinkID');
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(!IsSet($GLOBALS['_GET']['TicketID']))
	return FALSE;
#-------------------------------------------------------------------------------
if(!$GLOBALS['__USER']['IsAdmin'])
	return FALSE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#$Links['DOM'] = &$DOM;
$Links = &Links();
# Коллекция ссылок
$Template = &$Links[$LinkID];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$CacheID = 'TableSuper[AdministratorMenu]';
#-------------------------------------------------------------------------------
if(!$NoBody = Cache_Get($CacheID)){
	Debug(SPrintF("[comp/Tables/Forms/AdministratorMenu]: EdeskID = %s",print_r($GLOBALS['_GET']['TicketID'],true)));
	#-------------------------------------------------------------------------------
	$NoBody = new Tag('NOBODY');
	#-------------------------------------------------------------------------------
	$Menus = Styles_XML('Menus/Administrator/ListMenu/Tickets.xml');
	#Debug(SPrintF("[comp/Tables/Forms/AdministratorMenu]: Menus = %s",print_r($Menus,true)));
	#-------------------------------------------------------------------------------
	foreach($Menus['Items'] as $Menu){
		#-------------------------------------------------------------------------------
		$Href = Str_Replace('"',"'",$Menu['Href']);
		$Href = Str_Replace('%Replace%',$GLOBALS['_GET']['TicketID'],$Href);
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Buttons/Standard',Array('onclick'=>$Href,'style'=>'cursor: pointer;'),$Menu['Text'],$Menu['Icon']);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$NoBody->AddChild($Comp);
		#-------------------------------------------------------------------------------
	}
	#---------------------------------------------------------------------------
	Cache_Add($CacheID,$NoBody);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $NoBody;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>