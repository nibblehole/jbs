<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = @Array('ReturnType','ISPtype');
# $__args_list = @Array('ReturnType','pricelist_id','period','addon');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/

# format is price:period
#-------------------------------------------------------------------------------
Debug("[comp/Formats/ISPswOrder/SoftWareList]: ReturnType is '" . $ReturnType . "',ISPtype is '" . $ISPtype . "'");
# Debug(SPrintF('[comp/Formats/ISPswOrder/SoftWareList]: ReturnType = %s; pricelist_id = %s; period = %s; addon = %s',$ReturnType,$pricelist_id,$period,$addon));
#-------------------------------------------------------------------------------
$SW = Array(
		#-------------------------------------------------------------------------------
		# Типы заказываемого ПО
		'pricelist_id'	=> Array(
						0	=> 'не определено',
						7	=> 'ISPmanager Lite (без поддержки)',
						11	=> 'ISPmanager Pro (без поддержки)',
						5	=> 'ISPmanager Lite',
						9	=> 'ISPmanager Pro',
						112	=> 'ISPmanager Cluster',
						3541	=> 'ISPmanager 5 Lite',
						4601	=> 'ISPmanager 5 Business',
						432	=> 'BILLmanager Standard',
						434	=> 'BILLmanager Advanced',
						435	=> 'BILLmanager Corporate',
						2891	=> 'IPmanager 5',
						3045	=> 'VMmanager 5 Basic',
						3887	=> 'VMmanager 5 Cloud',
						3651	=> 'VEmanager 5',
						3932	=> 'DCImanager 5 Essential',
						3934	=> 'DCImanager 5 Progressive',
						3936	=> 'DCImanager 5 Enterprise',
						3136	=> 'DNSmanager 5'
					),
		#-------------------------------------------------------------------------------
		# Периоды заказа ПО
		'period'	=> Array(
						'-100'	=> 'trial',
						1	=> 1,
						3	=> 3,
						6	=> 6,
						12	=> 12,
						0	=> 'lifetime'
						),
		#-------------------------------------------------------------------------------
		# дополнительные параметры - число узлов кластера
		'addon'		=> Array(
						4601	=> 'addon_4602',
						3045	=> 'addon_3049',
						3887	=> 'addon_3889',
						3651	=> 'addon_3698',
					)
		#-------------------------------------------------------------------------------
	);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------


$ISPswList =  Array(
			'7:7'		=> 'ISPmanager Lite 4 [без поддержки] / триал',
			'7:8'		=> 'ISPmanager Lite 4 [без поддержки] / 1 месяц',
			'7:9'		=> 'ISPmanager Lite 4 [без поддержки] / вечная',
			'11:15'		=> 'ISPmanager Pro 4 [без поддержки] / триал',
			'11:16'		=> 'ISPmanager Pro 4 [без поддержки] / 1 месяц',
			'11:17'		=> 'ISPmanager Pro 4 [без поддержки] / вечная',
			'15:24'		=> 'VDSmanager-Linux / триал',
			'15:25'		=> 'VDSmanager-Linux / 1 месяц',
			'15:26'		=> 'VDSmanager-Linux / вечная',
			'884:534'	=> 'VDSmanager-FreeBSD / триал',
			'884:535'	=> 'VDSmanager-FreeBSD / 1 месяц',
			'884:536'	=> 'VDSmanager-FreeBSD / вечная',
			'432:246'	=> 'BILLmanager Standard / триал',
			'432:380'	=> 'BILLmanager Standard / 1 месяц',
			'432:381'	=> 'BILLmanager Standard / 1 год',
			'434:247'	=> 'BILLmanager Advanced / триал',
			'434:382'	=> 'BILLmanager Advanced / 1 месяц',
			'434:383'	=> 'BILLmanager Advanced / 1 год',
			'435:248'	=> 'BILLmanager Corporate / триал',
			'435:384'	=> 'BILLmanager Corporate / 1 месяц',
			'435:385'	=> 'BILLmanager Corporate / 1 год',
			'16:27'		=> 'DSmanager / триал',
			'16:28'		=> 'DSmanager / 1 месяц',
			'16:29'		=> 'DSmanager / 1 год',
			'16:661'	=> 'DSmanager / вечная',
			'17:30'		=> 'DNSmanager 4 / триал',
			'17:31'		=> 'DNSmanager 4 / вечная',
			'18:32'		=> 'IPmanager 4 / триал',
			'18:33'		=> 'IPmanager 4 / вечная',
			// added by lissyara, for JBS-753
			'3541:2307'	=> 'ISPmanager Lite 5 / триал',
			'3541:2308'	=> 'ISPmanager Lite 5 / 1 месяц',
			'3541:2309'	=> 'ISPmanager Lite 5 / 1 год',
			'3541:2310'	=> 'ISPmanager Lite 5 / вечная',
			// added by lissyara, 2014-03-22 in 19:23 MSK
			'3045:1898'	=> 'VMmanager 5 Basic / триал',
			'3045:1899'	=> 'VMmanager 5 Basic / 1 месяц',
			'3045:1900'	=> 'VMmanager 5 Basic / 1 год',
			'3045:1901'	=> 'VMmanager 5 Basic / вечная',
			'2891:1814'	=> 'IPmanager 5 / триал',
			'2891:1815'	=> 'IPmanager 5 / 1 месяц',
			'2891:1816'	=> 'IPmanager 5 / 1 год',
			'2891:1817'	=> 'IPmanager 5 / вечная',
	);


if($ReturnType){
	if($ISPtype){
		return $ISPswList[$ISPtype];
	}else{
		return "не задано/отсутствует";
	}
}
return $ISPswList;

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$ReturnType)
	return $SW;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($pricelist_id){
	#-------------------------------------------------------------------------------
	if($SW['pricelist_id'][$pricelist_id]){
		#-------------------------------------------------------------------------------
		return $SW['pricelist_id'][$pricelist_id];
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		return 'Неизвестная услуга';
		#-------------------------------------------------------------------------------
	}
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# период может быть задан и равен ноль... поэтому првоеряем длинну строки, как показатель наличия значения переменной
if(StrLen($period) > 0){
	#-------------------------------------------------------------------------------
	if($SW['period'][$period]){
		#-------------------------------------------------------------------------------
		return $SW['period'][$period];
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		return 'Неизвестный период';
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
