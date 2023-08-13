<?
    require_once(_LIBRARY."lib_user.php");
    $ObjectX = new TUser();

    if (isset($_REQUEST["user"]))
    {
        if ($ObjectX->MODE == "login") {
            $CONTENT .= $ObjectX->RenderLogin();
        } else

        if ($ObjectX->MODE == "register") {
            $CONTENT .= $ObjectX->RenderRegister();
        } else

        // Обработка запроса на регистрацию пользователя
        if ($ObjectX->MODE == "postreg") {
            $ObjectX->Register();
        } else

        // Обработка запроса на выход
        if ($ObjectX->MODE == "postlogin") {
            $ObjectX->Login();
        } else

        // Обработка запроса на выход
        if ($ObjectX->MODE == "logout") {
            $ObjectX->Logout();
        } else

        {
            $CONTENT .= $ObjectX->RenderUser();
        }
    }

    // Генерирование формы действий пользователя в область пользователя
    $ObjectX->TITLE .= $ObjectX->TITLE;
?>
