<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('ClauseID','Prefix','Width','Groups');
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('libs/Image.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!Comp_IsLoaded('Clauses/ImagesGallery')){
  #-----------------------------------------------------------------------------
  $Links = &Links();
  # Коллекция ссылок
  $DOM = &$Links['DOM'];
  #-----------------------------------------------------------------------------
  $Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/ClauseImage.js}'));
  #-----------------------------------------------------------------------------
  $DOM->AddChild('Head',$Script);
}
#-------------------------------------------------------------------------------
$Images = DB_Select('ClausesFiles',Array('ID','Comment','FileData'),Array('Where'=>SPrintF("`ClauseID` = %u AND `FileName` LIKE '%s%%'",$ClauseID,MySQL_Real_Escape_String($Prefix))));
#-------------------------------------------------------------------------------
switch(ValueOf($Images)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return new Tag('SPAN','Изображений не найдено');
  case 'array':
    #---------------------------------------------------------------------------
    $Table = new Tag('TABLE',Array('cellspacing'=>10));
    #---------------------------------------------------------------------------
    $Tr = new Tag('TR');
    #---------------------------------------------------------------------------
    foreach($Images as $Image){
      #-------------------------------------------------------------------------
      if(Count($Tr->Childs)%$Groups == 0){
        #-----------------------------------------------------------------------
        $Table->AddChild($Tr);
        #-----------------------------------------------------------------------
        $Tr = new Tag('TR');
      }
      #-------------------------------------------------------------------------
      $Size = Image_Get_Size($Image['FileData']);
      if(Is_Error($Size))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $Index = $Size['Height']/$Size['Width'];
      #-------------------------------------------------------------------------
      $Height = $Width*$Index;
      #-------------------------------------------------------------------------
      $Img = new Tag('IMG',Array('border'=>0,'width'=>$Width,'height'=>$Height,'style'=>'border:1px solid #DCDCDC;cursor:pointer;','title'=>$Image['Comment'],'src'=>SPrintF('/ClauseImage?ImageID=%u&Width=%u',$Image['ID'],$Width)));
      #-------------------------------------------------------------------------
      $Img->AddAttribs(Array('onclick'=>SPrintF('ClauseImageShow(%u,this);',$Image['ID'])));
      #-------------------------------------------------------------------------
      $Tr->AddChild(new Tag('TD',$Img,new Tag('DIV',Array('align'=>'center','style'=>'font-size:11px;color:#969696;'),$Image['Comment'])));
    }
    #---------------------------------------------------------------------------
    if(Count($Tr->Childs))
      $Table->AddChild($Tr);
    #---------------------------------------------------------------------------
    return $Table;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
