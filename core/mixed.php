<?
    /**
    * Модуль различных пользовательских представлений
    */
    include(_LIBRARY."lib_mixed.php");
    $ObjectX = new TMixed();

    if ($ObjectX->MODE == "passlost") {
        $CONTENT .= $ObjectX->PasswordLostRender();
    } else

    if ($ObjectX->MODE == "passlostpost") {
        $ObjectX->PasswordLostPost();
    } else

    if ($ObjectX->MODE == "passkey") {
        $CONTENT .= $ObjectX->PasswordKeyRender();
    } else

    if ($ObjectX->MODE == "passkeypost") {
        $ObjectX->PasswordKeyPost();
    } else

    if ($ObjectX->MODE == "fastreg") {
        $CONTENT .= $ObjectX->FastRegRender();
    } else

    if ($ObjectX->MODE == "fastregpost") {
        $ObjectX->FastRegPost();
    } else

    if ($ObjectX->MODE == "citylist") {
        $CONTENT .= $ObjectX->RenderCityList();
    } else

    $CONTENT = $ObjectX->RenderError("");
?>
