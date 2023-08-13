<?
  $time_start = microtime(true);

  include("config.php");
  ini_set("session.cookie_domain", ".".$_CONFIG["SITE_HOST"].$_CONFIG["SITE_DOMAIN"]);
  session_start();
  header('Content-Type: text/html; charset=utf-8');

  include(_LIBRARY."lib_error.php");
  include(_LIBRARY."lib_functions.php");
  include(_LIBRARY."lib_mysql.php");
  include(_LIBRARY."lib_interface.php");
  include(_LIBRARY."lib_session.php");

  // Переменные формирования страницы
  $CONTENT = "";
  $USERDATA = "";
  $KEYWORDS = "";

  if (isset($_REQUEST["ajax"])) {
    include(_CORE."ajaxloader.php");
    die();
  } else

  if (isset($_REQUEST["admin"])) {
    include(_CORE."admin.php");
  } else

  if (isset($_REQUEST["cabuser"])) {
    include(_CORE."cabinet_user.php");
  } else

  if (isset($_SESSION["COMPANY_ID"])) {
    include(_CORE."company.php");
  } else

  if (isset($_REQUEST["company"])) {
    include(_CORE."company.php");
  } else

  if (isset($_REQUEST["com"])) {
    /* todo */
    include(_CORE."compview.php");
    $fl_cat = $ObjectX->GetFlCategory($ObjectX->CatID);
    $fl_text = ($ObjectX->TxtID);
    $KEYWORDS = $ObjectX->KEYWORDS;
    $bannerHeader = $ObjectX->GetBannerHeader($_SESSION["CITY_ID"], $ObjectX->CatID);
  } else

  if (isset($_REQUEST["tasks"])) {
    include(_CORE."tasks.php");
    die();
  } else

  if (isset($_REQUEST["ticket"])) {
    include(_CORE."ticket.php");
  } else

  if (isset($_REQUEST["cabcomp"])) {
    include(_CORE."cabinet_comp.php");
  } else

  if (isset($_REQUEST["user"])) {
    include(_CORE."user.php");
  } else

  if (isset($_REQUEST["mixed"])) {
    include(_CORE."mixed.php");
  } else

  if (isset($_REQUEST["direct"])) {
    include(_CORE."direct.php");
  } else

  if (isset($_REQUEST["error"])) {
    include(_CORE."error.php");
  } else

  if (isset($_REQUEST["report"])) {
    include(_CORE."report.php");
  } else

  if (isset($_REQUEST["info"])) {
    include(_CORE."info.php");
  } else

  if (isset($_REQUEST["payment"])) {
    include(_CORE."payment.php");
  } else

  {
    /*todo*/
    include(_CORE."announce.php");
    $KEYWORDS = $ObjectX->KEYWORDS;
    $bannerHeader = $ObjectX->GetBannerHeader($_SESSION["CITY_ID"], $ObjectX->FL_CAT);
    $fl_cat = $ObjectX->BuildSelectCash($ObjectX->CashCategory(), @$ObjectX->FL_CAT);
    $fl_text = ($ObjectX->FL_TXT);
  }
  // Подключение рендеринга пользоватльской хуйни
  //include(_CORE."user.php");
?>

<?
    $USERDATA = $ObjectX->GetUserBox();

        /*todo mega pizdos =) */
        if (isset($_REQUEST["company"]) || strpos($_SERVER["REQUEST_URI"], "com") !== false) {
            $_LOADER->DS->TRADE->link = "/cabcomp/";
            $_LOADER->DS->TRADE->back = "/";
            $_LOADER->DS->TRADE->item = "компанию";
            $_LOADER->DS->TRADE->type = "компаний";
            $_LOADER->DS->TRADE->color = "yellow";
            $_LOADER->DS->TRADE->part = "com";
        } else {
            $_LOADER->DS->TRADE->link = "/announce/create";
            $_LOADER->DS->TRADE->back = "/com";
            $_LOADER->DS->TRADE->item = "объявление";
            $_LOADER->DS->TRADE->type = "объявлений";
            $_LOADER->DS->TRADE->color = "blue";
            $_LOADER->DS->TRADE->part = "";
       }

        /*todo*/
        if (($_SERVER["REQUEST_URI"] == "/") || ($_SERVER["REQUEST_URI"] == "/com") || ($_SERVER["REQUEST_URI"] == "/com/")) {
            $_LOADER->DS->TRADE->side = "outside";
        } else {
            $_LOADER->DS->TRADE->side = "inside";
        }

    if (!isset($_SESSION["COMPANY_ID"])) {
        if (!isset($fl_text)) {
            $fl_cat = $ObjectX->BuildSelectCash($ObjectX->CashCategory());
            $fl_text = "";
        }
        $stream = file_get_contents(_TEMPLATE."default/default.html");
        // Инфо - параметры поиска
        $stream = str_replace("#FLCAT", $fl_cat, $stream);
        $stream = str_replace("#FLTXT", $fl_text, $stream);
        // Инфо - пользователь и города
        $stream = str_replace("#CITYLIST", $ObjectX->BuildSelectCity($ObjectX->CashCity(), $_SESSION["CITY_ID"], 0), $stream);
        $stream = str_replace("#CITYNAME", GetCashValue($ObjectX->CashCity(), $_SESSION["CITY_ID"], 0), $stream);
        // Контент сайта
        $stream = str_replace("#HEADER", $ObjectX->TplRenderHeader(), $stream);
        $stream = str_replace("#CONTENT", $CONTENT, $stream);
        $stream = str_replace("#PARTNER", $ObjectX->TplRenderPartner(), $stream);
        $stream = str_replace("#FOOTER", $ObjectX->TplRenderFooter(), $stream);
        // Реклама нижняя todo GetBannerFooter(@$contentFooter)
        $stream = str_replace("#BANNERHEADER", @$bannerHeader, $stream);

        $stream = str_replace("#BANNERFOOTER", "", $stream);
        $stream = str_replace("#SITE_CSS", "", $stream);


    } else {
        $stream = $CONTENT;
        $stream = str_replace("#SITE_CSS", $ObjectX->CSS, $stream);
    }
    $stream = str_replace("#USERDATA", $USERDATA, $stream);

        // СЕО - наполнение
        $stream = str_replace("#SITE_TITLE", $ObjectX->TITLE.$_CONFIG["SITE_TITLE"], $stream);
        $stream = str_replace("#SITE_TESIS", $_CONFIG["SITE_TESIS"], $stream);
        $stream = str_replace("#SITE_KEYS", $KEYWORDS, $stream);
        //$stream = str_replace("#CITYNAME", GetCashValue($ObjectX->CashCity(), $_SESSION["CITY_ID"]), $stream);

        // Раздел сайта
        $stream = str_replace("#TRADESIDE",  $_LOADER->DS->TRADE->side,  $stream);
        $stream = str_replace("#TRADECOLOR", $_LOADER->DS->TRADE->color, $stream);
        $stream = str_replace("#TRADELINK",  $_LOADER->DS->TRADE->link,  $stream);
        $stream = str_replace("#TRADEITEM",  $_LOADER->DS->TRADE->item,  $stream);
        $stream = str_replace("#TRADEPART",  $_LOADER->DS->TRADE->part,  $stream);
        $stream = str_replace("#TRADETYPE",  $_LOADER->DS->TRADE->type,  $stream);

    if (isset($_SESSION["COMPANY_ID"]))
        $stream = str_replace("#FOOTER", "", $stream);
    else {
        $stream = str_replace("#FOOTER", $ObjectX->TplRenderFooter(), $stream);
    }

    if (isset($_SESSION["COMPANY_ID"]) || isset($_REQUEST["company"]) || (strpos($_SERVER["REQUEST_URI"], "com") !== false)) {
        $stream = str_replace("#SITECOMP", $_CONFIG["SITE_HOST"].$_CONFIG["SITE_DOMAIN"]."/com", $stream);
    } else {
        $stream = str_replace("#SITECOMP", $_CONFIG["SITE_HOST"].$_CONFIG["SITE_DOMAIN"], $stream);
    }
    $stream = str_replace("#SITEPATH", $_CONFIG["SITE_HOST"].$_CONFIG["SITE_DOMAIN"], $stream);

    $stream = str_replace("#INFOTIME", round(microtime(true) - $time_start, 3), $stream);

    echo $stream;
?>