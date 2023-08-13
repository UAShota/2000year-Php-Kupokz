<?
class TCompany extends TInterface
{
/**
 * Переменные доступа к БД, код активной категории, ссылка на массив настроек
 */
private $TPL;
private $GROUP;
public $COMPANY;
public $MODE;
public $DESIGN = false;
/**
 * Ссылочные переходы
 */
const LINK_ID_DEFAULT = 1;
const LINK_ID_PRODUCT = 2;
const LINK_ID_CONTACT = 3;
const LINK_ID_EXTNDED = 4;

/*todo*/
const TYPE_MEDIA_TEMPLATE   = 1;
const TYPE_MEDIA_BACKGROUND = 2;
const TYPE_MEDIA_HEADER     = 3;
/**
 * Ссылки по умолчанию
 */
const LINK_ERROR_COMPINFO = "/com/";    // Каталог компаний
/**
 * Коды ошибок
 */
const E_COMPINFO_NOTEXIST = 1;  // Информационная компания не найдена
const E_ITEM_NOTEXIST = 2;      // Товар не найден
const E_NEWS_NOTEXIST = 3;      // Новость не найдена

public function __construct()
{
    parent::__construct();

    $this->COMPANY = new TCompanyData();
    $this->TPL = _TEMPLATE."company/";
    $this->DESIGN = isset($_REQUEST["design"]);
    $this->GROUP = SafeInt(@$_REQUEST["group"]);
    $this->FL_TXT = SafeStr(@$_REQUEST["fl_text"]);
    $this->MODE = isset($_REQUEST["m"]) ? SafeStr($_REQUEST["m"]) : SafeStr(@$_REQUEST["company"]);

    if ($this->DESIGN && $this->AJ) {
        $this->Design();
    }
}

public function RenderCompanyInfo()
{
    $SQL = "select c.CAPTION, c.ID_CATEGORY, rc.CAPTION as CITY, c.LOCATION_STREET,"
        ." uncompress(c.TEXTDATA) as TEXTDATA, uncompress(CONTACT) as CONTACT, REALINDEX"
        ." from COMPANY_DATA c, REF_CITY rc"
        ." where rc.ID_CITY=c.ID_CITY and rc.ID_STATE=1 and c.ID_STATE=1"
        ." and c.ID_TYPE=".parent::COMPANY_TYPE_INFO." and ID_COMPANY=".$this->MODE;
    $item = $this->DL->LFetchRecord($SQL) or RedirectError(self::E_COMPINFO_NOTEXIST, self::LINK_ERROR_COMPINFO);

    $category = $this->GetCompanyPath($item["ID_CATEGORY"], "/com/");
    $this->TITLE = $item["CAPTION"];
    $this->KEYWORDS = $item["REALINDEX"];

    $stream = file_get_contents($this->TPL."company_info.html");
    $stream = str_replace("#CAPTION", $item["CAPTION"], $stream);
    $stream = str_replace("#CITY", $item["CITY"], $stream);
    $stream = str_replace("#LOCATION_STREET", $item["LOCATION_STREET"], $stream);
    $stream = str_replace("#CONTACT", $this->ContactView($item["CONTACT"]), $stream);
    $stream = str_replace("#TEXTDATA", BBCodeToHTML($item["TEXTDATA"]), $stream);
    $stream = str_replace("#CATEGORY", $category, $stream);

    return $stream;
}

private function InitCompany()
{
    $SQL = "select cd.ID_COMPANY, cd.CAPTION, cd.ID_USER, rt.FOLDER, rc.CAPTION as CITY, rc.ID_CITY"
        .", uncompress(cd.CONTACT) as CONTACT, cd.LOCATION_STREET, cd.LOCATION_MAP, cd.REALINDEX, uncompress(cd.WORKTIME) as WORKTIME"
        ." from COMPANY_DATA cd, REF_MEDIA rt, REF_CITY rc"
        ." where rt.ID_MEDIA=cd.ID_TEMPLATE and ID_COMPANY=".$_SESSION["COMPANY_ID"]
        ." and rc.ID_CITY=cd.ID_CITY and rt.ID_TYPE=".self::TYPE_MEDIA_TEMPLATE;
    $item = $this->DL->LFetchRecord($SQL);

    $this->COMPANY->id = $item["ID_COMPANY"];
    $this->COMPANY->user_id = $item["ID_USER"];
    $this->COMPANY->template = $item["FOLDER"];
    $this->COMPANY->city = $item["CITY"];
    $this->COMPANY->city_id = $item["ID_CITY"];
    $this->COMPANY->street = ($item["LOCATION_STREET"]);
    $this->COMPANY->map = htmlspecialchars_decode($item["LOCATION_MAP"]);
    $this->COMPANY->contact = $item["CONTACT"];
    $this->COMPANY->caption = ($item["CAPTION"]);
    $this->COMPANY->worktime = $item["WORKTIME"];
    $this->KEYWORDS = $item["REALINDEX"];
}

private function RenderError($content)
{
    if ($this->FL_ERR == parent::E__NOERROR) {
        $error = "";
    } else
    if ($this->FL_ERR == self::E_NEWS_NOTEXIST) {
        $error = parent::RenderErrorTemplate("", parent::E__ERRORID, "Запрошенная новость не найдена");
    } else
    if ($this->FL_ERR == self::E_ITEM_NOTEXIST) {
        $error = parent::RenderErrorTemplate("", parent::E__ERRORID, "Запрошенный товар не найден");
    } else {
        $error = parent::RenderErrorTemplate("", parent::E__ERRORID, "^_^");
    }
    return str_replace("#ERRORS", $error, $content);
}

private function InitGroup($sideonly = true)
{
    $group = new TCompanyGroup();
    // Все группы компании в один блок
    $SQL = "select * from COMPANY_GROUP where ID_STATE=1 and ID_COMPANY=".$this->COMPANY->id
        ." order by ORDERBY, CAPTION";
    $dump = $this->DL->LFetch($SQL);

    // Поиск уровня вложения для текущей группы
    foreach ($dump as $item) {
        if ($item["ID_GROUP"] == $this->GROUP) {
            $group->level = explode(".", $item["LEVEL"]);
            break;
        }
    }
    // Формирование левой панели категорий
    if ($sideonly) {
        foreach ($dump as $item) {
            if ($item["ID_PARENT"] == 0) {
                if (in_array($item["ID_GROUP"], $group->level)) {
                    $cssclass = "selected";
                } else {
                    $cssclass = "";
                }
                $group->sidebar .= "<li class='".$cssclass."'><a href='/product/".$item["ID_GROUP"]."'>".$item["CAPTION"]."</a></li>";
            }
        }
    } else {
    // Формирование массива, для подмены кода категории на ссылку
        $relink = $group->level;
        $streamfolder = file_get_contents($this->TPL."product_folder.html");
        foreach ($dump as $item) {
            if ($item["ID_PARENT"] == $this->GROUP) {
                $folder = str_replace("#CAPTION", $item["CAPTION"], $streamfolder);
                $folder = str_replace("#IDGROUP", $item["ID_GROUP"], $folder);
                $group->folder .= $folder;
            }
            // Поиск кода группы в уровне вложений для формирования перелинковки
            $index = array_search($item["ID_GROUP"], $group->level);
            if ($index !== false) {
                $relink[$index] = " / <a href='/product/".$item["ID_GROUP"]."'>".$item["CAPTION"]."</a>";
            }
        }
        $group->relink = implode($relink, "");
    }

    return $group;
}

private function InitNews()
{
    $SQL = "select ID_NEWS, CAPTION, DATE_LIFE from COMPANY_NEWS"
        ." where ID_STATE=1 and ID_COMPANY=".$this->COMPANY->id
        ." order by DATE_LIFE desc limit 0, 3";
    $dump = $this->DL->LFetchRows($SQL);

    $stream = "";
    foreach ($dump as $item) {
        $stream .= "<li><div>".$item[2]."</div><a href='/news/".$item[0]."'>".$item[1]."</a></li>";
    }
    return $stream;
}

private function InitContactData()
{
    if (!empty($this->COMPANY->map))
    {
        $map = json_decode(str_replace("'", '"', $this->COMPANY->map));
        return $map;
    }
    return false;
}

private function InitContact()
{
    $stream = "<p><b>г. ".$this->COMPANY->city."</b> ".$this->COMPANY->street."</p>".parent::ContactView($this->COMPANY->contact);

    $map = self::InitContactData();
    if ($map) {
        $url = "http://maps.google.com/maps/api/staticmap?sensor=false&center=#x,#y&zoom=#z&size=220x220&markers=#x,#y";
        $url = str_replace("#x", $map->Xa, $url);
        $url = str_replace("#y", $map->Ya, $url);
        $url = str_replace("#z", $map->Zm, $url);
        $stream .= str_replace("#STATICMAP", $url, "<img width='220px' height='220px' src='#STATICMAP' />");
    }
    return $stream;
}

private function InitWorktime()
{
    return parent::TplWorkTime($this->COMPANY->worktime);
}

public function RenderTemplate($link_id = self::LINK_ID_DEFAULT)
{
    self::InitCompany();
    if ($this->COMPANY->template != "default")
        parent::ResourcePushCss("data/company/themes/default");
    parent::ResourcePushCss("data/company/themes/".$this->COMPANY->template);
    parent::ResourcePushCss("data/company/data/".$this->COMPANY->id);

    $designTpl = ($this->DESIGN) ? file_get_contents($this->TPL."ds_default.html"): "";
    $pattern = '#id="link_'.$link_id.'"#ms';
    $replacement = 'id="link_'.$link_id.'" class="selected"';

    $stream = file_get_contents(_COMPANY."themes/".$this->COMPANY->template."/default.html");
    $stream = preg_replace($pattern, $replacement, $stream);
    $stream = str_replace("#DESIGN", $designTpl, $stream);
    $stream = str_replace("#HEADER", $this->TplRenderHeader(), $stream);
    $stream = str_replace("#FLTXT", $this->FL_TXT, $stream);
    $stream = str_replace("#CAPTION", $this->COMPANY->caption, $stream);
    $stream = str_replace("#SIDEBARNEWS", $this->InitNews(), $stream);
    $stream = str_replace("#SIDEBARCONTACT", $this->InitContact(), $stream);
    $stream = str_replace("#SIDEBARWORKTIME", $this->InitWorktime(), $stream);
    $stream = str_replace("#SIDEBARGROUP", $this->InitGroup()->sidebar, $stream);

    return $stream;
}

private function DefaultAnnounceBlock($orderby)
{
    $SQL = "select b.ID_ANNOUNCE, b.CAPTION, b.ID_STATE, b.COST, rc.LITERAL"
        ." from ANNOUNCE_DATA b, REF_CURRENCY rc"
        ." where b.ID_STATE in (1,2,3,4) and b.ID_USER=".$this->COMPANY->user_id
        ." and b.ID_GROUP>-1 and rc.ID_CURRENCY=b.ID_CURRENCY"
        ." order by b.".$orderby." desc limit 0, 4";
    $dump = $this->DL->LFetch($SQL);

    $stream = file_get_contents($this->TPL."product_block.html");
    $outData = "";
    $styles = $this->TplStyleLoad($dump);
    foreach ($dump as $item) {
        $out = str_replace("#CAPTION", $item["CAPTION"], $stream);
        $out = str_replace("#COST", $item["COST"], $out);
        $out = str_replace("#CURRENCY", $item["LITERAL"], $out);
        $out = str_replace("#IMAGEPATH", $this->GetAnnounceImage($item["ID_ANNOUNCE"], $item["ID_STATE"]), $out);
        $out = str_replace("#ANNOUNCEID", $item["ID_ANNOUNCE"], $out);
        $outData .= $this->TplStyleReplacement($item["ID_ANNOUNCE"], $out, $styles);
    }
    return $outData;
}

public function RenderCompany()
{
    $template = $this->RenderTemplate(self::LINK_ID_DEFAULT);
    $stream = file_get_contents($this->TPL."default.html");
    $stream = str_replace("#CONTENT", $stream, $template);

    $outView = $this->DefaultAnnounceBlock("VIEWS");
    $outDate = $this->DefaultAnnounceBlock("DATE_LIFE");

    $SQL = "select uncompress(TEXTDATA) from COMPANY_DATA where ID_COMPANY=".$this->COMPANY->id;
    $textdata = $this->DL->LFetchField($SQL);

    $stream = str_replace("#ANLASTOF", $outDate, $stream);
    $stream = str_replace("#ANRATING", $outView, $stream);
    $stream = str_replace("#CONTENT", BBCodeToHTML($textdata), $stream);

    return $this->RenderError($stream);
}

public function RenderProduct()
{
    $template = $this->RenderTemplate(self::LINK_ID_PRODUCT);
    $stream = file_get_contents($this->TPL."product.html");
    $stream = str_replace("#CONTENT", $stream, $template);

    // Страница просмотра
    $page = SafeInt(@$_GET["page"]);
    // Определение с фотографиями
    $fl_photo = SafeStr(@$_GET["fl_photo"]);
    // Определение с фотографиями
    $fl_gallery = SafeInt(@$_GET["fl_gallery"]);
    // Количество объявлений на страницу
    $this->SetItemPerPage();
    // Количество объявлений на страницу, подготовка запроса
    $limit = $this->SelectorPrepare($page, $_SESSION["USER_PERPAGE"]);
    // Определение порядка сортировки
    $order = $this->SelectorSorter();
    // Определение пользователя
    $this->SafeUserID($user_field);
    // Объект групп
    $group = $this->InitGroup(false);
    // Ссылка на рубрики
    $urlPage = "/product/".$this->GROUP;

    // Выборка объявлений группы
    $SQL = " b.ID_ANNOUNCE, b.CAPTION, b.ID_STATE, b.COST, rc.LITERAL,"
        ." rm.CAPTION as MEAS, b.ITEMCOUNT, cg.ID_GROUP, cg.CAPTION as GROUPCAP"
        ." from ANNOUNCE_DATA b left join COMPANY_GROUP cg on cg.ID_GROUP=b.ID_GROUP, REF_CURRENCY rc, REF_MEAS rm"
        ." where b.ID_CURRENCY=rc.ID_CURRENCY and b.ID_MEAS=rm.ID_MEAS"
        ." and b.ID_USER=".$this->COMPANY->user_id." and b.ID_STATE in (1,2,3,4)";

    if ($this->FL_TXT != "") {
        $MorphyText = "match(b.TEXTINDEX) against('".MorphyFullText($this->FL_TXT)."' in boolean mode)";
        $SQL = "select SQL_CALC_FOUND_ROWS ".$MorphyText." as REL,".$SQL." and ".$MorphyText;
        $order = "REL desc, ".$order;
        $urlPage .= "&fl_text=".$this->FL_TXT;
    } else {
        $SQL = "select SQL_CALC_FOUND_ROWS ".$SQL;
        if ($this->GROUP > 0) {
            $SQL .= " and b.ID_GROUP=".$this->GROUP;
        } else {
            $SQL .= " and b.ID_GROUP > -1";
        }
    }
    // Определение с фотографиями
    if ($fl_photo) {
        $SQL .= " and IMAGES>0";
        $urlPage .= "&fl_photo=".$fl_photo;
    }
    $SQL .= " order by ".$order.$limit;
    // Выборка данных
    $dump = $this->DL->LFetch($SQL);
    // Количество найденных записей
    $this->ROWFOUND = $this->DL->LMaxRows();
    // Пейджинг страниц
    $pageselector = $this->SelectorPage($page, $_SESSION["USER_PERPAGE"], $this->ROWFOUND, $urlPage);
    // Блок избранного
    $favourite = $this->TplFavouriteLoad($dump, $user_field);
    // Блок стилей
    $styles = $this->TplStyleLoad($dump);
    // Определение типа представления
    if ($fl_gallery == 1) {
        $streamitem = file_get_contents($this->TPL."product_item_wide.html");
    } else {
        $streamitem = file_get_contents($this->TPL."product_item.html");
    }

    $outData = "";
    foreach($dump as $item)
    {
        $announce_id = $item["ID_ANNOUNCE"];
        if ($item["GROUPCAP"] == "") $item["GROUPCAP"] = "Главная";

        $out = str_replace("#CAPTION", $item["CAPTION"], $streamitem);
        $out = str_replace("#COST", $item["COST"], $out);
        $out = str_replace("#CURRENCY", $item["LITERAL"], $out);
        $out = str_replace("#MEAS", $item["MEAS"], $out);
        $out = str_replace("#COUNT", $item["ITEMCOUNT"], $out);
        $out = str_replace("#IDGROUP", $item["ID_GROUP"], $out);
        $out = str_replace("#GROUP", $item["GROUPCAP"], $out);
        $out = str_replace("#GROUP", $item["GROUPCAP"], $out);
        $out = str_replace("#IMAGEPATH", $this->GetAnnounceImage($announce_id, $item["ID_STATE"]), $out);
        $out = str_replace("#ANNOUNCEID", $announce_id, $out);
        $out = str_replace('#FAVOURITE', $this->TplFavouriteReplacement($announce_id, $favourite), $out);
        $out = $this->TplStyleReplacement($announce_id, $out, $styles);
        $outData .= $out;
    }
    //todo$outData = str_replace("#SITEPATH", $this->DC["SITE_HOST"].$this->DC["SITE_DOMAIN"], $outData);

    if ($this->AJ) {
        SendJson((array($outData, $pageselector, $this->ROWFOUND)));
    } else {
        $stream = str_replace("#ITEMPERPAGE", $this->BuildSelectPerPage(), $stream);
        $stream = str_replace("#ITEMCOUNT", $this->ROWFOUND, $stream);
        $stream = str_replace("#CONTENT", $outData, $stream);
        $stream = str_replace("#PAGESELECTOR", $pageselector, $stream);
        $stream = str_replace("#FOLDER", $group->folder, $stream);
        $stream = str_replace("#CATPATH", $group->relink, $stream);
        // Скрытие блока иконок рубрикатора
        if ($group->folder != "") {
            $stream = str_replace("#GROUPVISIBLE", "", $stream);
        } else {
            $stream = str_replace("#GROUPVISIBLE", "none", $stream);
        }
        return $this->RenderError($stream);
    }
}

public function RenderItem()
{
    $stream = $this->RenderTemplate(self::LINK_ID_PRODUCT);
    $this->ITEMID = SafeInt(@$_REQUEST["id"]);

    $SQL = "select ad.ID_ANNOUNCE, ad.CAPTION, uncompress(ad.TEXTDATA) as TEXTDATA, ad.ID_STATE, ad.COST,"
        ." ad.DATE_LIFE, ad.ID_USER, rc.LITERAL, cc.CAPTION as CITY, ra.CAPTION as ACTION, ad.ITEMCOUNT, rm.CAPTION as MEAS"
        ." from ANNOUNCE_DATA ad, REF_CURRENCY rc, REF_CITY cc, REF_ACTION ra, REF_MEAS rm"
        ." where ad.ID_STATE in (1,2,3,4) and ad.ID_ANNOUNCE=".$this->ITEMID." and ad.ID_USER=".$this->COMPANY->user_id
        ." and rc.ID_CURRENCY=ad.ID_CURRENCY and cc.ID_CITY=ad.ID_CITY and ra.ACTION=ad.ID_ACTION";
    $item = $this->DL->LFetchRecord($SQL) or RedirectError(self::E_ITEM_NOTEXIST, "/product/");
    // Обновление количества просмотров
    $SQL = "update ANNOUNCE_DATA set VIEWS=VIEWS+1 where ID_ANNOUNCE=".$this->ITEMID;
    $this->DL->Execute($SQL);
    // Определение пользователя
    $this->SafeUserID($user_field);
    // Блок избранного
    $favourite = $this->TplFavouriteLoad(array($item), $user_field);
    // Блок стиля
    $styles = $this->TplStyleLoad(array($item));
    // Путь к галерее
    $imagePath = _ANNOUNCE.$item["ID_ANNOUNCE"]."/";
    // Заголовок страницы
    $this->TITLE .= $item["CAPTION"];
    // Шаблон вывода
    $outData = file_get_contents($this->TPL."item.html");
    $outData = str_replace("#CAPTION", $item["CAPTION"], $outData);
    $outData = str_replace('#CITY', $item["CITY"], $outData);
    $outData = str_replace('#COST', $item["COST"], $outData);
    $outData = str_replace('#COUNT', $item["ITEMCOUNT"], $outData);
    $outData = str_replace('#MEAS', $item["MEAS"], $outData);
    $outData = str_replace('#ACTION', $item["ACTION"], $outData);
    $outData = str_replace('#ANNOUNCEID', $item["ID_ANNOUNCE"], $outData);
    $outData = str_replace('#CURRENCY', $item["LITERAL"], $outData);
    $outData = str_replace('#DATELIFE', GetLocalizeDate($item["DATE_LIFE"]), $outData);
    $outData = str_replace("#TEXTDATA", BBCodeToHTML($item["TEXTDATA"]), $outData);
    $outData = str_replace('#IMAGEPATH', $this->GetAnnounceImage($item["ID_ANNOUNCE"], $item["ID_STATE"]), $outData);
    $outData = str_replace('#USERPHOTO', $this->GetPhotoUser($item["ID_USER"]), $outData);
    $outData = str_replace('#FAVOURITE', $this->TplFavouriteReplacement($this->ITEMID, $favourite), $outData);
    $outData = str_replace('#LIGHTBOX', $this->TplImageLoad($imagePath, $item), $outData);
    $outData = $this->TplStyleReplacement($item["ID_ANNOUNCE"], $outData, $styles);
    $stream = str_replace("#CONTENT", $outData, $stream);

    return $this->RenderError($stream);
}

public function RenderContact()
{
    $template = $this->RenderTemplate(self::LINK_ID_CONTACT);
    $stream = file_get_contents($this->TPL."contact.html");
    $stream = str_replace("#CONTENT", $stream, $template);

    $SQL = "select ud.ID_USER, ud.NAME_LAST, ud.NAME_FIRST, ud.NAME_MIDDLE, LOGIN, rc.CAPTION as CITY"
        ." from USER_DATA ud, REF_CITY rc where rc.ID_CITY=".$this->COMPANY->city_id
        ." and ud.ID_USER=".$this->COMPANY->user_id;
    $item = $this->DL->LFetchRecord($SQL);

    $stream = str_replace("#CITY", $item["CITY"], $stream);
    $stream = str_replace("#LOGIN", $item["LOGIN"], $stream);
    $stream = str_replace("#NAMELAST", $item["NAME_LAST"], $stream);
    $stream = str_replace("#NAMEFIRST", $item["NAME_FIRST"], $stream);
    $stream = str_replace("#NAMEMIDDLE", $item["NAME_MIDDLE"], $stream);
    $stream = str_replace("#USERID", $item["ID_USER"], $stream);
    $stream = str_replace("#IMAGEPATH", $this->GetPhotoCompany($this->COMPANY->id), $stream);
    $stream = str_replace("#CONTACT", $this->ContactView($this->COMPANY->contact, true), $stream);
    $stream = str_replace("#LOCATION_STREET", $this->COMPANY->street, $stream);

    $map = self::InitContactData();
    $map->draggable = false;
    $stream = str_replace("#LOCATIONMAP", json_encode($map), $stream);


    return $this->RenderError($stream);
}

public function RenderNews()
{
    $newsid = SafeInt(@$_GET["id"]);
    $stream = $this->RenderTemplate();

    $SQL = "select CAPTION, uncompress(TEXTDATA) as TEXTDATA, DATE_LIFE"
        ." from COMPANY_NEWS where ID_STATE=1 and ID_NEWS=".$newsid
        ." and ID_COMPANY=".$this->COMPANY->id;
    $item = $this->DL->LFetchRecord($SQL) or RedirectError(self::E_NEWS_NOTEXIST, "/?");

    $mainstream = file_get_contents(_TEMPLATE."company/news.html");
    $mainstream = str_replace("#CONTENT", "#CONTENT", $mainstream);

    $stream = str_replace("#CONTENT", $mainstream, $stream);
    $stream = str_replace("#CAPTION", $item["CAPTION"], $stream);
    $stream = str_replace("#TEXTDATA", BBCodeToHTML($item["TEXTDATA"]), $stream);
    $stream = str_replace("#DATELIFE", $item["DATE_LIFE"], $stream);

    return $this->RenderError($stream);
}









    /*todo*/

public function Design()
{
    $user_id = SafeInt(@$_SESSION["USER_ID"]);
    $comp_id = SafeInt(@$_SESSION["COMPANY_ID"]);
    $SQL = "select ID_COMPANY from COMPANY_DATA where ID_USER=".$user_id." and ID_COMPANY=".$comp_id;
    $this->DL->LFetchField($SQL) or die();

    $media = new TNativeMedia();
    $media->callback = SafeStr(@$_REQUEST["call"]);
    $media->type     = SafeStr(@$_REQUEST["type"]);
    $media->folder   = SafeStr(@$_REQUEST["folder"]);
    $media->page     = SafeInt(@$_REQUEST["page"]);
    $media->ext      = "/(\.|\/)(gif|png|jpg|jpeg)$/i";
    $media->data     = array();

    if ($media->type == "body") {
        $media->path = "data/company/media/background/";
        $media->media_type = self::TYPE_MEDIA_BACKGROUND;
        $stream = $this->DesignBackImg($media);
    } else
    if ($media->type == "area") {
        $media->path = "data/company/media/background/";
        $media->media_type = self::TYPE_MEDIA_BACKGROUND;
        $stream = $this->DesignBackImg($media);
    } else
    if ($media->type == "head") {
        $media->cssClass = "head";
        $media->path = "data/company/media/header/";
        $media->pathpreview = "/preview";
        $media->perPage++;
        $media->media_type = self::TYPE_MEDIA_HEADER;
        $stream = $this->DesignBackImg($media);
    } else
    if ($media->type == "template") {
        $media->path = "data/company/themes/";
        $media->media_type = self::TYPE_MEDIA_TEMPLATE;
        $stream = $this->DesignTemplate($media);
    } else
    if ($media->type == "preview") {
        $this->test($media);
    } else
    {
        $stream = "not a function";
    }
    SendLn($stream);
}

private function test(TNativeMedia $media)
{
    $user_id = SafeInt(@$_SESSION["USER_ID"]);
    $comp_id = SafeInt(@$_SESSION["COMPANY_ID"]);
    $SQL = "select ID_COMPANY from COMPANY_DATA where ID_USER=".$user_id." and ID_COMPANY=".$comp_id;
    $this->DL->LFetchField($SQL) or die();

    $folder = _COMPANY."data/".$comp_id;
    if (!is_dir($folder) && !@mkdir($folder)) {
        echo "failed to create directory";
        trigger_error("failed to create directory ".$folder);
        return false;
    }

    $file = @fopen($folder."/style.css", "w");
    if (!$file) {
        echo "failed to create file";
        trigger_error("failed to create file ".$file);
        return false;
    }

    $vector = $_REQUEST["vector"];
    foreach($vector as $item) {
        $item[2] = str_replace("http://".$_SERVER["HTTP_HOST"], "", $item[2]);
        $style = $item[0]." {\r\n\x09".$item[1].": ".($item[2]).";\r\n}\r\n";
        fwrite($file, $style);
    }
    fclose($file);

    echo "Сохранено";
    die();
}


/***************************************/
private function DesignTemplate(TNativeMedia $media)
{
    $stream = "";

    $SQL = "select * from COMPANY_TEMPLATE where ID_STATE=1";
    $dump = $this->DL->LFetch($SQL);

    $stream .= "<div class='ri-tpl-template-block'>";
    foreach ($dump as $template) {
        $stream .= "<div class='ri-tpl-template' style='background-image: url(\"".$media->path.$template["FOLDER"]."/"._THUMBPHOTO."\");'";
        $stream .= " onclick='return DesignApply(\"".$media->type.", \"".$template["FOLDER"]."\");'></div>";
    }
    $stream .= "</div>";

    return $stream;
}
/***************************************/




public function DesignBackImg(TNativeMedia $media)
{
    $fileLow = $media->page * $media->perPage;
    $fileHight = $media->page * $media->perPage + $media->perPage;

    $SQL = "select ID_MEDIA, CAPTION from REF_MEDIA where ID_STATE=1 and ID_TYPE=".$media->media_type." order by CAPTION";
    $media->media_data = $this->DL->LFetchRowsSpare($SQL);
    $media->media_file = array();

    foreach($media->media_data as $id=>$caption)
    {
        $handle = @opendir($media->path.$id.$media->pathpreview);
        if(!$handle) continue;

        while (false !== ($file = readdir($handle)))
        {
            if (!preg_match($media->ext, $file) || ($id != $media->folder)) continue;

            if (($media->perData++ >= $fileLow) && ($media->perData <= $fileHight)) {
                array_push($media->media_file, $file);
            }
        }
        closedir($handle);
    }
    return $this->DesignRenderImg($media);
}

private function DesignGetItemUrl($media, $caption, $folder, $selected = false, $page = 0)
{
    $link = " <a class='".($selected ? "selected" : "")."' onclick='return DesignDialog(this, \"".$media->type."\");'";
    $link .= " href='/?design&aj&type=".$media->type."&page=".$page."&folder=".$folder."'>".$caption."</a>";
    return $link;
}

private function DesignRenderImg(TNativeMedia $media)
{
    $head = "<div class='ri-tpl-route head'>";
    $foot = "<div class='ri-tpl-route foot'>";
    $body = "<div class='ri-tpl-route'>";
    $pageID = 0;
    // Заголовок
    foreach($media->media_data as $folder=>$caption) {
        $head .= self::DesignGetItemUrl($media, $caption, $folder, ($folder==$media->folder));
    }
    $head .= "</div>";
    // Тело
    $body .= "<div class='ri-tpl-".$media->cssClass."-block'>";
    foreach ($media->media_file as $file) {
        $body .= " <div class='ri-tpl-".$media->cssClass."' style='background-image: url(\"";
        $body .= "/".$media->path.$media->folder.$media->pathpreview."/".$file."\");'";
        $body .= " onclick='return DesignApply(\"".$media->type."\", this);'></div>";
    }
    $body .= "</div>";
    // Пейджинг
    for ($index = 0; $index < $media->perData; $index += $media->perPage) {
        $pageID++;
        $foot .= self::DesignGetItemUrl($media, $pageID, $media->folder, ($pageID==$media->page + 1), $pageID - 1);
    }
    $foot .= "</div>";

    return $head.$body.$foot;
}


}
?>