<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('IsOK','ID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Image = 'No.gif';
#-------------------------------------------------------------------------------
if(!Is_Null($IsOK) && $IsOK){
	#-------------------------------------------------------------------------------
	if($IsOK > 98)
		$Image = 'Yes.gif';
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$IsOK = NULL;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Message = SPrintF('%s',(Is_Null($IsOK) )?'не мониторится':SPrintF('%s%%',$IsOK));
#-------------------------------------------------------------------------------
$Out = new Tag(
		'IMG',
		Array(
			'alt'		=> '+',
			'class'		=> 'Button',
			'onmouseover'	=> SPrintF("PromptShow(event,'%s',this);",$Message),
			'onclick'	=> SPrintF("ShowWindow('/Administrator/ServerUpTimeInfo',{ServerID:%u});",$ID),
			'width'		=> 16,
			'height'	=> 16,
			'src'		=> SPrintF('SRC:{/Images/Icons/%s}',$Image)
			)
		);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Out;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------


?>