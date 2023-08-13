<?
function ClearStyles($loader)
{
    $SQL = "delete from BLOCK_STYLES where DATE_LIFE<now()";
    $loader->Execute($SQL);
}
function ClearBanner($loader)
{
    $SQL = "update BLOCK_BANNER set ID_STATE=6 where RSHOWED >= LIMSHOW";
    $loader->Execute($SQL);
}
function ClearBan()
{
    require_once(_LIBRARY."lib_user.php");
    $User = new TUser();
    $User->BanIpDel();
    unset($User);
}
function ClearDelivery()
{
    require_once(_LIBRARY."lib_email.php");
    $Email = new TEmail();
    $Email->SendMailing();
    unset($Email);
}

ClearStyles($_LOADER);
ClearBanner($_LOADER);
ClearBan();
ClearDelivery();
?>
