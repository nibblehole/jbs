<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
# added by lissyara 2013-08-03 in 19:28, for JBS-731
if(Is_Error(System_Load('modules/Authorisation.mod','libs/Tree.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# cache and other functions added 2011-12-30 in 19:41, by lissyara, as part of JBS-237
if(!IsSet($GLOBALS['__USER'])){
	#Debug("[comp/www/API/Events]: юзер не авторизован");
	return ERROR | @Trigger_Error(700);
}
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$OutCacheID = Md5($__FILE__ . $__USER['ID']);
$TimeCacheID = Md5($__FILE__ . $__USER['ID'] . 'time');
$LastIDCacheID = Md5($__FILE__ . $__USER['ID'] . 'ID');
#-------------------------------------------------------------------------------
$TimeResult = CacheManager::get($TimeCacheID);
#-------------------------------------------------------------------------------
if($TimeResult){
	#-------------------------------------------------------------------------------
	# проверяем не истекло ли время кэша
	if($TimeResult > Time() - 10){
		#-------------------------------------------------------------------------------
		# проверяем, есть ли выхлоп в кэше
		$Out = CacheManager::get($OutCacheID);
		#-------------------------------------------------------------------------------
		if($Out){
			#-------------------------------------------------------------------------------
			# отдаём кэш
			Debug(SPrintF('[comp/www/API/Events]: UserID = %u; результат найден в кэше',$__USER['ID']));
			#-------------------------------------------------------------------------------
			Return($Out);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($__USER['IsAdmin']){
	#-------------------------------------------------------------------------------
	$Where = Array(
			"`StatusID` = 'Working' OR `StatusID` = 'Newest'",
			"(SELECT `IsDepartment` FROM `Groups` WHERE `Groups`.`ID` = `Edesks`.`TargetGroupID`) = 'yes'",
			"(SELECT `IsDepartment` FROM `Groups` WHERE `Groups`.`ID` = (SELECT `GroupID` FROM `Users` WHERE `Users`.`ID` = `Edesks`.`UserID`)) = 'no'"
			);
	#-------------------------------------------------------------------------------
        $Session = new Session((string)@$_COOKIE['SessionID']);
	#-------------------------------------------------------------------------------
	$IsLoad = $Session->Load();
	if(Is_Error($IsLoad))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Tickets = @$Session->Data[Md5('Tickets')];
	#-------------------------------------------------------------------------------
	if(IsSet($Tickets['GroupExcludeID']) && $Tickets['GroupExcludeID'] != 'Default')
		$Where[] = SPrintF('`TargetGroupID` != %u',$Tickets['GroupExcludeID']);
	#-------------------------------------------------------------------------------
	if(IsSet($Tickets['GroupOnlyID']) && $Tickets['GroupOnlyID'] != 'Default')
		$Where[] = SPrintF('`TargetGroupID` = %u',$Tickets['GroupOnlyID']);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Where = Array(SPrintF("`StatusID` != 'Closed' AND `UserID` = %u AND (SELECT `IsDepartment` FROM `Groups` WHERE `Groups`.`ID` = `Edesks`.`TargetGroupID`) = 'yes'",$__USER['ID']));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Count = DB_Count('Edesks',Array('Where'=>$Where));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Out = Array('Messages'=>$Count);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Where = Array('UNIX_TIMESTAMP() - 10 <= `CreateDate`');
#-------------------------------------------------------------------------------
$LastID = CacheManager::get($LastIDCacheID);
#-------------------------------------------------------------------------------
if($LastID){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/API/Events]: last selected ID, from cache = %u; user = %u',$LastID,$__USER['ID']));
	#-------------------------------------------------------------------------------
	$Where[] = SPrintF('`ID` > %u',$LastID);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
if(!$__USER['IsAdmin'])
	$Where[] = SPrintF('`UserID` = %u',$__USER['ID']);
#-------------------------------------------------------------------------------
$UserInfo = "(SELECT CONCAT(FROM_UNIXTIME(`CreateDate`,'%Y-%m-%d / %H:%i:%s / '),`Email`,' / ',`Name`) FROM `Users` WHERE `Users`.`ID` = `Events`.`UserID`)";
#-------------------------------------------------------------------------------
$Events = DB_Select('Events',Array('ID','Text',SPrintF('%s AS `UserInfo`',$UserInfo),'PriorityID'),Array('SortOn'=>'ID','Where'=>$Where));
#-------------------------------------------------------------------------------
switch(ValueOf($Events)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	$Out['Status'] = 'Empty';
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'array':
	#-------------------------------------------------------------------------------
	$Result = Array();
	#-------------------------------------------------------------------------------
	foreach($Events as $Event){
		#-------------------------------------------------------------------------------
		$Event['Text'] = HtmlSpecialChars($Event['Text']);
		#-------------------------------------------------------------------------------
		$Result[] = $Event;
		#-------------------------------------------------------------------------------
		$LastID = $Event['ID'];
		#-------------------------------------------------------------------------------
		#Debug("[comp/www/API/Events]: last selected ID = " . $LastID);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	CacheManager::add($LastIDCacheID,$LastID,24 * 3600); /* на сутки в кэш */
	#-------------------------------------------------------------------------------
	$Out['Status'] = 'Ok';
	$Out['Events'] = $Result;
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
CacheManager::add($TimeCacheID,Time(),10);
CacheManager::add($OutCacheID,$Out,10);
#Debug("[comp/www/API/Events]: результат добавлен в кэш");
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#Debug(Print_r($Out,true));
return $Out;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
