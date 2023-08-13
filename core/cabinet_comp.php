<?
    include(_CORE."user.php");
    include(_LIBRARY."lib_cabinet_comp.php");

    $ObjectX = new TCabinetComp();
    $ObjectX->CheckAuthorize();

    if ($ObjectX->MODE == "registerpost") {
       $ObjectX->PostRegister();
    } else
    if ($_SESSION["USER_COMPANY"] == 0) {
        $stream = $ObjectX->TplRegister();
    } else

    if ($ObjectX->MODE == "worktime") {
        $ObjectX->TITLE .= "График работы";
       $stream = $ObjectX->WorkTime();
    } else

    if ($ObjectX->MODE == "newspost") {
       $ObjectX->PostNews();
    } else
    if ($ObjectX->MODE == "news") {
        $ObjectX->TITLE .= "Новости";
        $stream = $ObjectX->TplNews();
    } else

    if ($ObjectX->MODE == "domainpost") {
       $ObjectX->PostDomain();
    } else
    if ($ObjectX->MODE == "domain") {
        $ObjectX->TITLE .= "Домен компании";
        $stream = $ObjectX->TplDomain();
    } else

    if ($ObjectX->MODE == "settingspost") {
       $ObjectX->PostSettings();
    } else
    if ($ObjectX->MODE == "settings") {
        $ObjectX->TITLE .= "Настройки";
        $stream = $ObjectX->TplSettings();
    } else

    if ($ObjectX->MODE == "contactpost") {
        $ObjectX->PostContact();
    } else
    if ($ObjectX->MODE == "contact") {
        $ObjectX->TITLE .= "Контакты";
        $stream = $ObjectX->TplContact();
    } else

    if ($ObjectX->MODE == "postnewgroup") {
        $ObjectX->PostCategoryCreate();
    } else

    if ($ObjectX->MODE == "newgroup") {
        $ObjectX->TITLE .= "Добавить группу";
        $stream = $ObjectX->TplCategoryCreate();
    } else

    if ($ObjectX->MODE == "posteditgroup") {
        $ObjectX->PostCategoryEdit();
    } else

    if ($ObjectX->MODE == "editgroup") {
        $ObjectX->TITLE .= "Свойства группы";
        $stream = $ObjectX->TplCategoryEdit();
    } else

    if ($ObjectX->MODE == "postedititem") {
        $ObjectX->PostAnnounceEdit();
    } else

    if ($ObjectX->MODE == "edititem") {
        $ObjectX->TITLE .= "Редактирование товара";
        $stream = $ObjectX->TplAnnounceEdit();
    } else

    if ($ObjectX->MODE == "postcreateitem") {
        $ObjectX->PostAnnounceCreate();
    } else

    if ($ObjectX->MODE == "dropitem") {
        $ObjectX->TITLE .= "Удаление товара";
        $stream = $ObjectX->TplAnnounceDrop();
    } else

    if ($ObjectX->MODE == "postdropitem") {
        $ObjectX->PostAnnounceDrop();
    } else

    if ($ObjectX->MODE == "newitem") {
        $ObjectX->TITLE .= "Добавить товар";
        $stream = $ObjectX->TplAnnounceCreate();
    } else

    if ($ObjectX->MODE == "cat") {
        $ObjectX->TITLE .= "Товары и услуги";
        $stream = $ObjectX->TplCategory();
    } else

    if ($ObjectX->MODE == "ticket") {
        $ObjectX->TITLE = "Заявки на покупку";
        $stream = $ObjectX->ExecuteTicket();
    } else

    {
         $stream = $ObjectX->TplDefault();
         $ObjectX->TITLE = "Кабинет компании";
    }

  $CONTENT = file_get_contents(_TEMPLATE."cabinet_comp/default.html");
  $CONTENT = str_replace("#HEADER", $ObjectX->TplRenderHeader(), $CONTENT);
  $CONTENT = str_replace("#SITE_TITLE", $ObjectX->TITLE, $CONTENT);
  $CONTENT = str_replace("#USERDATA", $ObjectX->GetUserBox(), $CONTENT);
  $CONTENT = str_replace("#TPLLINK", $ObjectX->TPLLINK, $CONTENT);
  $CONTENT = str_replace("#TPLSUBLINK", $ObjectX->TPLSUBLINK, $CONTENT);
  $CONTENT = str_replace("#CONTENT", $stream, $CONTENT);
  $CONTENT = str_replace("#FOOTER", $ObjectX->TplRenderFooter(), $CONTENT);
  $CONTENT = str_replace("#SITEPATH", $_CONFIG["SITE_HOST"].$_CONFIG["SITE_DOMAIN"], $CONTENT);
  $CONTENT = str_replace("#SITECOMP", $_CONFIG["SITE_HOST"].$_CONFIG["SITE_DOMAIN"]."/com", $CONTENT);
  $CONTENT = str_replace("#SITELOCAL", $ObjectX->SafeDomain($ObjectX->Company["DOMAIN_ACTIVE"]), $CONTENT);
  $CONTENT = str_replace("#SITE_CSS", "", $CONTENT);
  $CONTENT = str_replace("#INFOTIME", round(microtime(true) - $time_start, 3), $CONTENT);

  SendLn($CONTENT);
?>
