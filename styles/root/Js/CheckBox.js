//------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
function ChangeCheckBox($CheckBox){
  //----------------------------------------------------------------------------
  if(document.getElementsByName($CheckBox)[0].checked){
    //------------------------------------------------------------------------
    document.getElementsByName($CheckBox)[0].checked = false;
  }else{
    //------------------------------------------------------------------------
    document.getElementsByName($CheckBox)[0].checked = true;
  }
}
//------------------------------------------------------------------------------

