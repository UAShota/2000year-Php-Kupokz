<?
  include(_LIBRARY."lib_email.php");
  $Email = new TEmail();
  $Email->Delivery();
  
  $User = new TUser();
  $User->BanIpDel();  
?>