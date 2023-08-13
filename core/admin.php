<?
    /**
    * Модуль администрирования
    */

    if (!(($_SESSION["USER_ROLE"] < 4))) {
        Redirect("/");
    }

    if (isset($_REQUEST["moder"])) {
        include("admin/moderate.php");
        $Admin = new TAdminModerate();
    } else

    if (isset($_REQUEST["banner"])) {
        include("admin/banner.php");
        $Admin = new TAdminBanner();
    } else

    if (isset($_REQUEST["company"])) {
        include("admin/company.php");
        $Admin = new TAdminCompany();
    } else

    if (isset($_REQUEST["category"])) {
        include("admin/category.php");
        $Admin = new TAdminCategory();
    } else

    if (isset($_REQUEST["parser"])) {
        include("admin/parser.php");
        $Admin = new TAdminParser();
    } else

    if (isset($_REQUEST["settings"])) {
        include("admin/settings.php");
        $Admin = new TAdminSettings();
    } else

    if (isset($_REQUEST["stats"])) {
        include("admin/stats.php");
        $Admin = new TAdminStats();
    } else

    if (isset($_REQUEST["errors"])) {
        include("admin/errors.php");
        $Admin = new TAdminErrors();
    } else

    {
        include("admin/stub.php");
        $Admin = new TAdminStub();
        $stream = "";
    }

    // Подключение рендеринга пользоватльской хуйни
    include(_CORE."user.php");

    $stream = file_get_contents(_TEMPLATE."admin/default.html");
    $stream = str_replace("#HEADER", $Admin->TplRenderHeader(), $stream);
    $stream = str_replace("#USERDATA", $Admin->GetUserBox(), $stream);

    if (isset($Admin)) {
        $stream = str_replace("#BLOCKDATA", $Admin->DATA, $stream);
        $stream = str_replace("#BLOCKMODE", $Admin->HEAD, $stream);
        $stream = str_replace("#SITE_TITLE", $Admin->HEAD, $stream);
    } else {
        $stream = str_replace("#BLOCKDATA", "<p><center>Системы в норме, к полету готов!</center></p>", $stream);
        $stream = str_replace("#BLOCKMODE", "Тест системы", $stream);
        $stream = str_replace("#SITE_TITLE", "Админ панель", $stream);
    }
    $stream = str_replace("#SITEPATH", $_CONFIG["SITE_HOST"].$_CONFIG["SITE_DOMAIN"], $stream);
    $stream = str_replace("#SITECOMP", $_CONFIG["SITE_HOST"].$_CONFIG["SITE_DOMAIN"], $stream);
    $stream = str_replace("#SITE_CSS", "", $stream);

    SendLn($stream);
?>
