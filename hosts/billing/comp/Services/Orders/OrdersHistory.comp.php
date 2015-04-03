<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Params');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
#Debug(SPrintF('[comp/Services/Orders/OrdersHistory]: Params = %s',print_r($Params,true)));
#-------------------------------------------------------------------------------
$UserID = $GLOBALS['__USER']['ID'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# масив для вставки/обновления таблицы StatusesHistory
$IOrdersHistory = Array('StatusDate'=>Time());
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверяем, есть ли такой заказ в таблице с историей
$Count = DB_Count('OrdersHistory',Array('Where'=>Array(SPrintF('`OrderID` = %u',$Params['OrderID']))));
if(Is_Error($Count))
	return ERROR | Trigger_Error(500);
#-------------------------------------------------------------------------------
if($Count){
	#-------------------------------------------------------------------------------
	# это вторичная проставка статуса для заказа. просто обновляем StatusDate
	$IsUpdate = DB_Update('OrdersHistory',$IOrdersHistory,Array('Where'=>SPrintF('`OrderID` = %u',$Params['OrderID'])));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	return Array('Status'=>'Ok');
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
# бывает что заказ есть, а записи в таблице нет...
if(!IsSet($Params['ServiceID']) || !IsSet($Params['SchemeID'])){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Services/Orders/OrdersHistory]: не указаны ServiceID и SchemeID заказа #%s',$Params['OrderID']));
	#-------------------------------------------------------------------------------
	return Array('Status'=>'Ok');
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверяем, были ли такие же заказы
$Where = Array(
			SPrintF('`UserID` = %u',$UserID),
			SPrintF('`ServiceID` = %u',$Params['ServiceID']),
			SPrintF('`SchemeID` = %u',$Params['SchemeID'])
		);
#-------------------------------------------------------------------------------
$OrdersHistory = DB_Select('OrdersHistory',Array('COUNT(*) AS `Counter`','MAX(`CreateDate`) AS `LastDate`'),Array('UNIQ','Where'=>$Where));
#-------------------------------------------------------------------------------
switch(ValueOf($OrdersHistory)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	# No more...
	break;
case 'array':
	#-------------------------------------------------------------------------------
	# проверяем, как много таких заказов можно делать
	if($Params['MaxOrders'] > 0 && $OrdersHistory['Counter'] >= $Params['MaxOrders'])
		if(!$GLOBALS['__USER']['IsAdmin'])
			return new gException('TOO_MANY_ORDERS',SPrintF('Для данного тарифного плана существует ограничение на максимальное число заказов, равное %s. Ранее, вы уже делали заказы по данному тарифу%s, и больше сделать не можете. Выберите другой тарифный план.',$Params['MaxOrders'],($OrdersHistory['Counter'] > $Params['MaxOrders'])?SPrintF(' (%s)',$OrdersHistory['Counter']):''));
	#-------------------------------------------------------------------------------
	# проверяем, как часто можно делать такие заказы
	if($Params['MinOrdersPeriod'] > 0 && $Params['MinOrdersPeriod'] > (Time() - $OrdersHistory['LastDate'])/(24 * 60 * 60))
		if(!$GLOBALS['__USER']['IsAdmin'])
			return new gException('TOO_MANY_ORDER_RATE',SPrintF('Для данного тарифного плана существует ограничение на частоту заказа. Тариф можно заказывать не чаще чем раз в %s дней. До возможности сделать заказ осталось %s дней. Пока, вы можете выбрать другой тарифный план.',$Params['MinOrdersPeriod'],Ceil($Params['MinOrdersPeriod'] - (Time() - $OrdersHistory['LastDate'])/(24 * 60 * 60))));
	#-------------------------------------------------------------------------------
        break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}



#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# вносим заказ в таблицу, если его там нет
$IOrdersHistory['UserID']	= $UserID;
$IOrdersHistory['Email']	= $GLOBALS['__USER']['Email'];
$IOrdersHistory['ServiceID']	= $Params['ServiceID'];
$IOrdersHistory['ServiceName']	= $Params['ServiceName'];
$IOrdersHistory['SchemeID']	= $Params['SchemeID'];
$IOrdersHistory['SchemeName']	= $Params['SchemeName'];
$IOrdersHistory['OrderID']	= $Params['OrderID'];
$IOrdersHistory['CreateDate']	= Time();
#-------------------------------------------------------------------------------
$IsInsert = DB_Insert('OrdersHistory',$IOrdersHistory);
if(Is_Error($IsInsert))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok');
#-------------------------------------------------------------------------------

?>
