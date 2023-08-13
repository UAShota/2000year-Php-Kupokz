<?
    include(_CORE."user.php");
    include(_LIBRARY."lib_cabinet_user.php");
    $ObjectX = new TCabinetUser();

    if ($ObjectX->MODE == "favourite") {
        $ObjectX->TITLE = "Избранное";
        $stream = $ObjectX->RenderFavourite();
    } else

    if ($ObjectX->MODE == "announce") {
        $ObjectX->TITLE = "Мои объявления";
        $stream = $ObjectX->RenderAnnounce();
    } else

    if ($ObjectX->MODE == "archive") {
        $ObjectX->TITLE = "Архивные объявления";
        $stream = $ObjectX->RenderArchive();
    } else

    if ($ObjectX->MODE == "ticket") {
        $ObjectX->TITLE = "Заявки на покупку";
        $stream = $ObjectX->ExecuteTicket();
    } else

    if ($ObjectX->MODE == "mailbox") {
        $ObjectX->TITLE = "Личные сообщения";
        $stream = $ObjectX->ExecuteMailbox();
    } else

    if ($ObjectX->MODE == "banner") {
        $ObjectX->TITLE = "Рекламные блоки";
        $stream = $ObjectX->ExecuteBanner();
    } else

    {
        // Для всех других действий необходима регистрация
        if ($ObjectX->MODE != "") {
            $ObjectX->CheckAuthorize();
        }

        if ($ObjectX->MODE == "notice_change") {
            $ObjectX->TITLE = "Уведомления";
            $stream = $ObjectX->NoticeEdit();
        } else
        if ($ObjectX->MODE == "notice_post") {
            $stream = $ObjectX->NoticePost();
        } else

        if ($ObjectX->MODE == "mail_change") {
            $ObjectX->TITLE = "Смена E-Mail";
            $stream = $ObjectX->MailEdit();
        } else
        if ($ObjectX->MODE == "mail_post") {
            $stream = $ObjectX->MailPost();
        } else

        if ($ObjectX->MODE == "password_change") {
            $ObjectX->TITLE = "Смена пароля";
            $stream = $ObjectX->PasswordEdit();
        } else
        if ($ObjectX->MODE == "password_post") {
            $stream = $ObjectX->PasswordPost();
        } else

        if ($ObjectX->MODE == "account_change") {
            $ObjectX->TITLE = "Персональные даныне";
            $stream = $ObjectX->AccountEdit();
        } else
        if ($ObjectX->MODE == "account_post") {
            $stream = $ObjectX->AccountPost();
        } else

        {
            $ObjectX->TITLE = "Кабинет пользователя";
            $stream = $ObjectX->RenderDefault();
        }
    }

  $CONTENT = file_get_contents(_TEMPLATE."cabinet_user/default.html");
  $CONTENT = str_replace("#HEADER", $ObjectX->TplRenderHeader(), $CONTENT);
  $CONTENT = str_replace("#USERDATA", $ObjectX->GetUserBox(), $CONTENT);
  $CONTENT = str_replace("#FOOTER", $ObjectX->TplRenderFooter(), $CONTENT);
  $CONTENT = str_replace("#SITE_TITLE", $ObjectX->TITLE, $CONTENT);
  $CONTENT = str_replace("#TPLLINK", $ObjectX->TPLLINK, $CONTENT);
  $CONTENT = str_replace("#TPLSUBLINK", $ObjectX->TPLSUBLINK, $CONTENT);
  $CONTENT = str_replace("#CONTENT", $stream, $CONTENT);
  $CONTENT = str_replace("#SITE_CSS", "", $CONTENT);
  $CONTENT = str_replace("#SITEPATH", $_CONFIG["SITE_HOST"].$_CONFIG["SITE_DOMAIN"], $CONTENT);
  $CONTENT = str_replace("#SITECOMP", $_CONFIG["SITE_HOST"].$_CONFIG["SITE_DOMAIN"], $CONTENT);
  $CONTENT = str_replace("#INFOTIME", round(microtime(true) - $time_start, 3), $CONTENT);

  SendLn($CONTENT);
?>
