<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Args');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = IsSet($Args)?$Args:Args();
#-------------------------------------------------------------------------------
$AttribsName	= (string) @$Args['AttribsName'];
$Template	=  (array) @$Args['Template'];
$Table		=  (array) @$Args['Table'];
$Values		=  (array) @$Args['Values'];


$Window		=  (string) @$Args['Window'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];



#-------------------------------------------------------------------------------
$Attribs = $Template[$AttribsName];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Replace = Array_ToLine($__USER,'%');
#-------------------------------------------------------------------------------
foreach(Array_Keys($Attribs) as $AttribID){
	#-------------------------------------------------------------------------------
	$Attrib = $Attribs[$AttribID];
	#-------------------------------------------------------------------------------
	if(IsSet($Attrib['Title']))
		$Table[] = $Attrib['Title'];
	#-------------------------------------------------------------------------------
	if($Values){
		#-------------------------------------------------------------------------------
		$Value = (string)@$Values[$AttribsName][$AttribID];
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		$Value = $Attrib['Value'];
		#-------------------------------------------------------------------------------
		foreach(Array_Keys($Replace) as $Key)
			$Value = Str_Replace($Key,$Replace[$Key],$Value);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Params = &$Attrib['Attribs'];
			#-------------------------------------------------------------------------------
			$Params['name'] = $AttribID;
			#-------------------------------------------------------------------------------
			if($Attrib['IsDuty'])
				$Params['class'] = 'Duty';
			#-------------------------------------------------------------------------------
			switch($Attrib['Type']){
			case 'Input':
				#-------------------------------------------------------------------------------
				$Params['value'] = $Value;
				#-------------------------------------------------------------------------------
				# костыль для чекбоксов - у них всегда одно значение
				if(IsSet($Attrib['Attribs']['type']) && $Attrib['Attribs']['type'] == 'checkbox')
					$Params['value'] = 'yes';
				#-------------------------------------------------------------------------------
				$Comp = Comp_Load('Form/Input',$Params);
				if(Is_Error($Comp))
					return ERROR | @Trigger_Error(101);
				#-------------------------------------------------------------------------------
				# костыль для чекбоксов - у них дополнительный параметр "checked", если задано значение
				if(IsSet($Attrib['Attribs']['type']) && $Attrib['Attribs']['type'] == 'checkbox' && $Value)
					$Comp->AddAttribs(Array('checked'=>'yes'));
				#-------------------------------------------------------------------------------
				break;
				#-------------------------------------------------------------------------------
			case 'TextArea':
				#-------------------------------------------------------------------------------
				$Comp = Comp_Load('Form/TextArea',$Params,$Value);
				if(Is_Error($Comp))
					return ERROR | @Trigger_Error(101);
				#-------------------------------------------------------------------------------
				break;
				#-------------------------------------------------------------------------------
			case 'Select':
				#-------------------------------------------------------------------------------
				$Comp = Comp_Load('Form/Select',$Params,$Attrib['Options'],$Value);
				if(Is_Error($Comp))
					return ERROR | @Trigger_Error(101);
				#-------------------------------------------------------------------------------
				break;
				#-------------------------------------------------------------------------------
			case 'Hidden':
				#-------------------------------------------------------------------------------
				$Params['value'] = $Value;
				#-------------------------------------------------------------------------------
				$Params['type'] = 'hidden';
				#-------------------------------------------------------------------------------
				$Comp = Comp_Load('Form/Input',$Params);
				if(Is_Error($Comp))
					return ERROR | @Trigger_Error(101);
				#-------------------------------------------------------------------------------
				break;
				#-------------------------------------------------------------------------------

			default:
				return ERROR | @Trigger_Error(101);
			}
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			if($Attrib['Type'] == 'Hidden'){
				#-------------------------------------------------------------------------------
				$Form->AddChild($Comp);
				#-------------------------------------------------------------------------------
			}else{
				#-------------------------------------------------------------------------------
				$NoBody = new Tag('NOBODY',new Tag('SPAN',(IsSet($Attrib['CommentAttribs'])?$Attrib['CommentAttribs']:Array()),$Attrib['Comment']));
				#-------------------------------------------------------------------------------
				$NoBody->AddChild(new Tag('BR'));
				#-------------------------------------------------------------------------------
				if(IsSet($Attrib['Example']))
					$NoBody->AddChild(new Tag('SPAN',Array('class'=>'Comment'),SPrintF('Например: %s',$Attrib['Example'])));
				#-------------------------------------------------------------------------------
				$Table[] = Array($NoBody,$Comp);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table[] = 'Служба мониторинга';
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/TextArea',
			Array(
				'name'		=> 'Monitoring',
				'style'		=> 'width:100%;',
				'rows'		=> 5,
				'prompt'	=> 'Сервисы которые необходимо мониторить на данном сервере. Список, по одному значению СЕРВИС=ПОРТ на каждой строке'
				),
			Str_Replace(" ","\n",$Values['Monitoring'])
			);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Сервисы',$Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table[] = 'Заметка';
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/TextArea',
			Array(
				'name'		=> 'AdminNotice',
				'style'		=> 'width:100%;',
				'rows'		=> 5,
				'prompt'	=> 'Информация о сервере, "чисто для себя"'
				),
			$Values['AdminNotice']
			);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = $Comp;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/Input',
			Array(
				'type'    => 'button',
				'onclick' => SPrintF("FormEdit('/Administrator/API/ValuesEdit','ValuesEditForm','%s');",($ValuesID?'Сохранение настроек':'Добавление сервера')),
				'value'   => ($ValuesID?'Сохранить':'Добавить сервер')
				)
			);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = $Comp;
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Tables/Standard',$Table,Array('style'=>'width:500px;'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>