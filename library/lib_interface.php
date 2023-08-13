<?
class TInterface
{
    protected $DC;      /* Data configuration */
    protected $DL;      /* Data loader */
    public $DS;         /* Data session */
    public $AJ;         /* Ajax improved */
    public $CSS;        /* Header CSS inludes */
    public $TITLE;      /* Header Title includes */
    public $KEYWORDS;   /* Header keywords */
    public $FL_TXT;     /* Base search text */
    public $FL_CAT;     /* Base search category */
    public $FL_ERR;     /* Base error code */
    public $FL_COM;     /* Base company ID */

    /**
     * Определение элементов и атрибутов для объявления
     */
    const ATTR_FIELD_LIST     = 1; // список
    const ATTR_FIELD_LINK     = 2; // родитель
    const ATTR_FIELD_CHILD    = 3; // подчиненный
    const ATTR_FIELD_CHECKBOX = 5; // логика
    /**
     * Быстрые фильтры
     */
    const FILTER_TYPED_ALL   = 0; // все объявления
    const FILTER_TYPED_USER  = 1; // частные объявления
    const FILTER_TYPED_COMP  = 2; // объявления компании
    const FILTER_TYPED_TRADE = 1; // информационные компании
    const FILTER_TYPED_INFO  = 2; // торговые компании
    /**
     * Хранимка для объявлений
     */
    const AC_CREATE    = 1; // Добавление объявления
    const AC_DECREMENT = 2; // Декремент количества
    const AC_INCREMENT = 3; // Инкремент количества
    const AC_DELETE    = 4; // Удаление объявления
    /**
     * Типы компаний
     */
    const COMPANY_TYPE_INFO = 5; // Информационная компания
    /**
     * Идентификатор автоподсветки ошибки
     */
    const E__ERROR = "error";       // Подсветка: ошибка
    const E__SUCCS = "highlight";   // Подсветка: успешно
    const E__ERRORID = 0x129;       // Подсветка: ошибка
    const E__SUCCSID = 0x130;       // Подсветка: успешно
    const E__NOERROR = 0;           // Ошибок нет
    /**
     * Типы статусов
     */
    const STATE_ACTIVE    = 1;  // Статус активно
    const STATE_PARSER    = 3;  // Статус парсера
    const STATE_MODER     = 4;  // Статус на модерировании
    const STATE_DELETED   = 5;  // Статус удалено
    const STATE_INCORRECT = 6;  // Статус некорректное
    /**
     * Типы ролей
     */
    const ROLE_SUPPORT = 1; // Роль :: я ^_^
    const ROLE_ADMIN   = 2; // Роль :: администратор
    const ROLE_MODER   = 3; // Роль :: модератор
    const ROLE_GUEST   = 5; // Роль :: гость
    /**
     * Типы картинок с предпросмотром
     */
    const IMAGE_THUMB = 0; // Хумб изображение
    const IMAGE_PHOTO = 1; // Краткое изображение
    const IMAGE_FULHD = 3; // Полное изображение
    /**
     * Тип отправляемых писем
     */
    const MAILTYPE_SUPPORT  = 0; // Ответ саппорту
    const MAILTYPE_NOREPLY  = 1; // Ответ заглушке
    const MAILTYPE_FROMUSER = 2; // Ответ пользователю

    /*todo fixed*/
    const CURRENCY_DEFAULT = 6; // Цена: договорная
    const MEAS_DEFAULT     = 1; // Ед. измерения: уточняйте
    const BAN_LOGIN        = 0; // Блокировка учетной записи
    const REASON_ANNOUNCE  = 1; // Код жалоб на объявления

    const COOKIE_USER = "uniquepid";
    const COOKIE_GUEST = "uniquesid";


    const E_NOTPARAM = 1004; //
    const E_NOTFOUND = 1001; //

    protected function __construct()
    {
        global $_LOADER, $_CONFIG;

        $this->DC = $_CONFIG;
        if (!isset($_LOADER)) {
            $_LOADER = new TMySQL(
                $this->DC["SQL_SERVER"],
                $this->DC["SQL_PORT"],
                $this->DC["SQL_LOGIN"],
                $this->DC["SQL_PWD"],
                $this->DC["SQL_DATABASE"]);
        }
        $this->DL = $_LOADER;
        $this->AJ = isset($_REQUEST["aj"]);

        $this->FL_ERR = SafeInt(@$_REQUEST["e"]);
        $this->FL_TXT = SafeStr(@$_REQUEST["fl_text"]);
        $this->FL_CAT = SafeInt(@$_REQUEST["fl_cat"]);
    }

    public function RenderErrorTemplate($content, $state, $error)
    {
        $errclass = ($state == self::E__ERRORID) ? self::E__ERROR : self::E__SUCCS;

        $stream = file_get_contents(_TEMPLATE."default/default_error.html");
        $stream = str_replace("#STYLE", $errclass, $stream);
        $stream = str_replace("#TEXT", $error, $stream);
        $stream = str_replace("#CONTENT", $content, $stream);
        return $stream;
    }

    /*
        Блок управления контактными данными
    */
    /**
     * TInterface::ContactView()
     *
     * шаблон контактов на основе переданных сериализованных контактов
     *
     * @param TNativeContact Array $contacts
     * @param bool $useDescription
     *
     */
    protected function ContactView($contacts, $useDescription = false)
    {
        $contacts = @unserialize($contacts);
        if (!$contacts) return false;

        $stream = "";
        foreach ($contacts as $contact)
        {
            if (($useDescription) && ($contact->text != "")) {
                $stream .= "<li class='icon-default icon-des'>".$contact->text."<br>";
            }
            $stream .= "<li class='icon-default icon-".$contact->type."'>";

            if ($contact->type == "mob") {
                $stream .= $contact->phone->code."(".$contact->phone->ops.")".$contact->phone->data;
            }
            if ($contact->type == "url") {
                $stream .= "<a rel='nofollow' href='http://#SITEPATH/?direct=".SafeHttp(urlencode($contact->data))."'>Перейти на сайт</a>";
            } else
            if ($contact->type == "vk") {
                $stream .= "<a rel='nofollow' href='http://#SITEPATH/?direct=".SafeHttp(urlencode("http://vk.com/".$contact->data))."'>".$contact->data."</a>";
            } else            
            {
                $stream .= $contact->data;
            }
            $stream .= "</li><br/>";
        }
        return $stream;
    }

    /*todo*/
    protected function ContactViewNative($contacts, $count = 30, $separator = "\r\n")
    {
        $contacts = @unserialize($contacts);
        if (!$contacts) return false;

        $stream = "";
        foreach ($contacts as $contact)
        {
            if ($contact->type == "mob") {
                $stream .= $contact->phone->code."(".$contact->phone->ops.")".$contact->phone->data;
            } else
            {
                $stream .= $contact->data;
            }
            $stream .= $separator;
            $count--;
            if ($count < 0) break;
        }
        return $stream;
    }


    /**
     * TInterface::ContactRender()
     *
     * шаблон для редактирования контактов на основе переданных сериализованных контактов
     *
     * @param TNativeContact Array $contacts
     * @param bool $useDescription
     * @return
     */
    protected function ContactRender($contacts = null, $useDescription = false)
    {
        // Выборка доступных классов типов контактов
        $SQL = "select CLASS_ICON, CLASS_FIELD, false, CAPTION from REF_CONTACT where ID_STATE=1 order by ORDERBY";
        $dump = $this->DL->LFetchRows($SQL);

        // Десериализация контактов
        $contacts = @unserialize($contacts);
        // Определение класса контакта
        $rootdiv = "root";
        $stream = "";
        $tplData = _TEMPLATE."/contact/";

        // Перебор доступных классов
        for ($index = 0; $index < count($dump); $index++)
        {
            $contact_type  = $dump[$index][0];
            $contact_class = $dump[$index][1];
            $contact_text  = $dump[$index][3];
            $is_mobile = ($contact_type == "mob");

            // Загрузка темплейта вывода контактов, похерили идею на корню. Теперь ебитесь.
            if ($useDescription) {
                if ($is_mobile) $template = file_get_contents($tplData."mobile_desc.html");
                else $template = file_get_contents($tplData."simple_desc.html");
            } else {
                if ($is_mobile) $template = file_get_contents($tplData."mobile.html");
                else $template = file_get_contents($tplData."simple.html");
            }

            // Перебор имеющихся контактов
            if ($contacts) foreach ($contacts as $contact)
            {
                if ($contact->type != $contact_type) continue;
                $linkDrop = $number = "";
                // Генерирование контакт-листа
                if (!$dump[$index][2]) {
                    $number = $rootdiv;
                    $stream .= "<div class='contacts'><div class='caption'>".$contact_text.":</div>";
                } else {
                    $linkDrop = "<a href='javascript:;' onclick='return ContactDrop(this);'>Убрать</a>";
                }
                // Подготовка шаблона
                $data = str_replace("#FIELD",       $contact_class, $template);
                $data = str_replace("#DESCRIPTION", $contact->text, $data);
                $data = str_replace("#LINKDROP",    $linkDrop, $data);
                $data = str_replace("#NUMBER",      $number, $data);

                if (!$is_mobile) {
                    $data = str_replace("#VALUE",  $contact->data, $data);
                } else {
                    $data = str_replace("#MCODE",  $contact->phone->code, $data);
                    $data = str_replace("#MOPS",   $contact->phone->ops, $data);
                    $data = str_replace("#MPHONE", $contact->phone->data, $data);
                }
                $stream .= $data;
                // Признак активизированного класса контакта
                $dump[$index][2] = true;
            }
            // Если класс контакта не активизирован, вывод пустого контакта для ввода
            if (!$dump[$index][2]) {
                $stream .= "<div class='contacts'><div class='caption'>".$contact_text.":</div>";
                $data = str_replace("#FIELD",       $contact_class, $template);
                $data = str_replace("#NUMBER",      $rootdiv, $data);
                $data = str_replace("#DESCRIPTION", "", $data);
                $data = str_replace("#LINKDROP",    "", $data);

                if (!$is_mobile) {
                    $data = str_replace("#VALUE",  "", $data);
                } else {
                    $data = str_replace("#MCODE",  "+7", $data);
                    $data = str_replace("#MOPS",   "", $data);
                    $data = str_replace("#MPHONE", "", $data);
                }
                $stream .= $data;
            }
            $stream .= "</div>";
        }

        return $stream;
    }

    /**
     * TInterface::InlineContactUpload()
     *
     * Собирает присланные параметры контактов в массив контактов
     *
     * @param TNativeContact Array $vector
     * @param string $key
     * @param string $field
     * @return void
     */
    private function ContactInlineCatch(&$vector, $key, $field)
    {
        if (isset($_POST[$key]) && $key != "cmob")
        {
            for ($index = 0; $index < count($_POST[$key]); $index++)
            {
                if (@$_POST[$key][$index] == "") continue;
                // Подстановка описателя контакта
                $contact = new TNativeContact();
                $contact->type = $field;
                $contact->text = SafeStr(@$_POST[$key."desc"][$index]);
                $contact->data = SafeStr($_POST[$key][$index]);
                array_push($vector, $contact);
            }
        } else

        if (isset($_POST[$key]["code"]) && $key == "cmob")
        {
            for ($index = 0; $index < count($_POST[$key]["code"]); $index++)
            {
                if (@$_POST[$key]["phone"][$index] == "") continue;
                // Подстановка описателя контакта
                $contact = new TNativeContact();
                $contact->phone = new TNativeContactPhone();
                $contact->type = $field;
                $contact->text = SafeStr(@$_POST[$key]["desc"][$index]);
                $contact->phone->code = SafeStr(@$_POST[$key]["code"][$index]);
                $contact->phone->ops  = SafeStr(@$_POST[$key]["ops"][$index]);
                $contact->phone->data = SafeStr(@$_POST[$key]["phone"][$index]);
                array_push($vector, $contact);
            }
        }
    }

    /**
     * TInterface::InlineContactUpload()
     *
     * Сборщик контактов, на основе активных контактов
     *
     * @return
     */
    private function ContactInlineUpload()
    {
        // Выборка доступных классов типов контактов
        $SQL = "select CLASS_FIELD, CLASS_ICON from REF_CONTACT where ID_STATE=1 order by ORDERBY";
        $dump = $this->DL->LFetchRows($SQL);

        $contact = array();
        for ($index = 0; $index < count($dump); $index++) {
            $this->ContactInlineCatch($contact, $dump[$index][0], $dump[$index][1]);
        }
        return $contact;
    }

    /**
     * TInterface::ContactUpload()
     *
     * Сборщик и сериализатор контактов, для сохранения
     *
     * @return
     */
    protected function ContactUpload()
    {
        $contacts = self::ContactInlineUpload();
        return serialize($contacts);
    }



    public function CheckAuthorize()
    {
        if (!isset($_SESSION["USER_ID"]) ||($_SESSION["USER_ID"] == self::ROLE_GUEST)) {
            RedirectRegister();
        } else {
            return true;
        }
    }

    protected function SafeAgent($item)
    {
        if (isset($item["ID_ROLE"]) && ($item["ID_ROLE"] == self::ROLE_MODER)) {
            $item["ID_USER"] = self::ROLE_GUEST;
            $item["ID_GUEST"] = -1;
            $item["LOGIN"] = "Гость";
        };
        return $item;
    }

    protected function SafeCompany($item, $companyes)
    {
        foreach ($companyes as $company) {
            if (($item["ID_USER"] == $company["ID_USER"]) && ($item["ID_GROUP"] > -1)) {
                $item = array_merge($item, $company);
                break;
            }
        }
        return $item;
    }

    protected function CookieSet($key, $value)
    {
        setcookie($key, $value, time()+25920000, "/", ".".$this->DC["SITE_HOST"].$this->DC["SITE_DOMAIN"]);
    }

    protected function CookieDrop($key)
    {
        setcookie($key, "", 0, "/", ".".$this->DC["SITE_HOST"].$this->DC["SITE_DOMAIN"]);
        unset($_COOKIE[$key]);
    }

    public function SafeDomain($url)
    {
        if ($url == "") {
            return "http://".$this->DC["SITE_HOST"].$this->DC["SITE_DOMAIN"];
        }
        else
            return "http://".$url.".".$this->DC["SITE_HOST"].$this->DC["SITE_DOMAIN"];
    }

    protected function SafeUserCompany($item, $isuser = true)
    {
        if ($isuser) {
            $item["USERLINK"] = "/user/".$item["ID_USER"];
            $item["USERTYPE"] = "user-0";
            $item["USERITEM"] = "/announce";
        } else {
            $item["LOGIN"] = $item["COMCAPTION"];
            $item["USERLINK"] = $this->SafeDomain($item["DOMAIN_ACTIVE"]);
            $item["USERTYPE"] = "comp-".$item["COMTYPE"];
            $item["USERITEM"] = $item["USERLINK"]."/item";
        }

        return $item;
    }

    protected function SafeUserID(&$user_field, $suffix = "b")
    {
        if (isset($_SESSION["USER_ID"])) {
            $user_field = " ".$suffix.".ID_USER=".$_SESSION["USER_ID"];
            return true;
        }

        if (isset($_SESSION["GUEST_ID"])) {
            $user_field = " ".$suffix.".ID_GUEST=".$_SESSION["GUEST_ID"];
            return true;
        }

        $user_field = " ".$suffix.".ID_GUEST=-1";
        return false;
    }

    protected function SafeUser(&$user_field)
    {
        if (isset($_SESSION["USER_ID"])) {
            $user_field = $_SESSION["USER_ID"];
            return true;
        } else
        if (isset($_SESSION["GUEST_ID"])) {
            $user_field = $_SESSION["GUEST_ID"];
            return false;
        } else {
            $user_field = 0;
            return false;
        }
    }

    protected function SafeUserRegister(&$user_id, &$user_field)
    {
        if (isset($_SESSION["USER_ID"]))
        {
            $user_id = $_SESSION["USER_ID"];
            $user_field = " b.ID_USER=".$user_id;
            return true;
        }

        if (isset($_SESSION["GUEST_ID"]))
        {
            $user_id = $_SESSION["GUEST_ID"];
            $user_field = " b.ID_GUEST=".$user_id;
            return false;
        }

        require_once(_LIBRARY."lib_user.php");
        $User = new TUser();
        $user_id = $User->RegisterGuest();
        $user_field = " b.ID_GUEST=".$user_id;
        unset($User);
        return false;
    }

    protected function SafeUserRegisterEx(&$user_id, &$guest_id)
    {
        if (isset($_SESSION["USER_ID"]))
        {
            $user_id = $_SESSION["USER_ID"];
            $guest_id = 0;
            return false;
        }

        if (isset($_SESSION["GUEST_ID"]))
        {
            $user_id = self::ROLE_GUEST;
            $guest_id = $_SESSION["GUEST_ID"];
            return true;
        }

        require_once(_LIBRARY."lib_user.php");
        $User = new TUser();
        $guest_id = $User->RegisterGuest();
        $user_id = self::ROLE_GUEST;
        unset($User);
        return true;
    }

    protected function SafeGuestCookie($guest_id)
    {
        if (isset($_COOKIE[self::COOKIE_GUEST]))
            return $_COOKIE[self::COOKIE_GUEST];

        $SQL = "select COOKIE from USER_GUEST where ID_STATE=1 and ID_GUEST=".$guest_id;
        $item = $this->DL->LFetchRecordRow($SQL);

        if (!$item) return false; else return $item[0];
    }

    protected function CheckGrant($grant_id)
    {
        if ($grant_id < 0) return true;

        $cashGrant = self::CashGrant();
        for ($index = 0; $index < count($cashGrant); $index++) {
            // Право существует
            if (($cashGrant[$index][0] == $grant_id)
                // И включено для пользователя
                && ($cashGrant[$index][1] == 1))
            return true;
        }

        return false;
    }

    /*protected function GetPhotoMixed($item)
    {
        if (isset($item["ID_COMPANY"]) && ($item["ID_COMPANY"] > 0)) {
            return $this->GetPhotoCompany($item["ID_COMPANY"]);
        } else {
            return $this->GetPhotoUser($item["ID_USER"]);
        }
    }*/

    protected function GetPhotoCompany($company_id)
    {
        $image_user = _COMPANY."data/".$company_id."/"._COMPAVATAR;

        if (!file_exists($image_user)) {
            return _COMPANY._THUMBEMPTY;
        } else {
            return $image_user;
        }
    }

    protected function GetPhotoUser($user_id)
    {
        $image_user = _AVATAR.$user_id."/"._USERAVATAR;

        if (!file_exists($image_user)) {
            return _AVATAR._THUMBEMPTY;
        } else {
            return $image_user;
        }
    }

    protected function GetAnnounceImage($announce_id, $state_id)
    {
        $image_path = _ANNOUNCE.$announce_id."/"._THUMBPHOTO;
        if (file_exists($image_path)) {
            // Определение модерирования объявления, если изображения есть
            if ($state_id == self::STATE_MODER) return _ANNOUNCE._THUMBMODER;
            // Изображение есть (ваш КЭП)
            return $image_path;
        } else {
            // Изображений нет
            return _ANNOUNCE._THUMBEMPTY;
        }
    }

    /* todo */
    protected function GetAnnounceStyle($announce_id, $styles)
    {
        // Разбор списка стилей в массив для получения класса
        $class = " stl-0";
        $desc = "";
        // Формат стиля в CSS документе announce-<код стиля>
        foreach ($styles as $item => $style) {
            if ($style[0] == $announce_id) {
                $class .= " stl-".$style[1];
                if ($style[2] != "") {
                    $desc = $style[2];
                }
            }
        }
        return array($class, $desc);
    }

    protected function GetAnnouncePath($level, $caption, $defLink, $skiproot = false)
    {
        // Разбор пути "1.2.3." в массив
        $levelVec = explode(".", $level);
        $catLink = "";
        // Перебор пути, последний элемент является текущим
        for($index = 0; $index < count($levelVec) - 1; $index++)
        {
            $SQL = "select ID_CATEGORY, CAPTION from REF_CATEGORY"
                ." where ID_STATE=1 and (ID_CATEGORY=".$levelVec[$index]." or ID_PARENT=".$levelVec[$index].")"
                ." order by ID_PARENT, ORDERBY, CAPTION";
            $dump = $this->DL->LFetchRows($SQL);
            // Формирование выпадающего списка
            if (count($dump) > 1) {
                $out = "<div class='popup-hidden'><ul>";
                for ($sub = 1; $sub < count($dump); $sub++)
                {
                    $out .= "<li><a href='".$defLink.$dump[$sub][0]."'>".$dump[$sub][1]."</a></li>";
                    if ($sub % 14 == 0) $out .= "</ul><ul>";
                }
                $out .= "</ul></div>";
            } else {
                $out = "";
            }
            // Рутовая категория без номера
            if (!$skiproot) {
                $dump[0][0] == 1 ? $link = "" : $link = $defLink.$dump[0][0];
            } else {
                $link = $defLink.$dump[0][0];
            }
            // Формирование ссылки следования
            $catLink .= "<div class='popup-menu' onmouseover='return CtxMenuShow(this)' onmouseout='return CtxMenuHide(this)'>"
                ."<a href='".$link."'>".$dump[0][1]."</a>".$out."&nbsp;&raquo;&nbsp;</div>";
        }
        return $catLink;
    }

    /*todo*/
    protected function GetCompanyPath($category, $defLink)
    {
        $catLink = "";
        // Перебор пути, последний элемент является текущим
        while ($category >= 1)
        {
            // Обработка кэшированных данных
            if (!isset($_SESSION["LIST_PATH_COM".$category])) {
                $SQL = "select ID_CATEGORY, CAPTION, ID_PARENT from COMPANY_CATEGORY"
                    ." where ID_STATE=1 and (ID_CATEGORY=".$category." or ID_PARENT=".$category.")"
                    ." order by ID_PARENT, ORDERBY, CAPTION";
                $dump = $this->DL->LFetchRows($SQL);
                if (count($dump) == 0) break;

                $_SESSION["LIST_PATH_COM".$category] = $dump;
            } else {
                $dump = $_SESSION["LIST_PATH_COM".$category];
            }

            // Формирование выпадающего списка
            if (count($dump) > 1) {
                $out = "<div class='popup-hidden'><ul>";
                for($sub = 1; $sub < count($dump); $sub++) {
                    $out .= "<li><a href='".$defLink.$dump[$sub][0]."'>".$dump[$sub][1]."</a></li>";
                    if ($sub % 15 == 0) $out .= "</ul><ul>";
                }
                $out .= "</ul></div>";
            } else {
                $out = "";
            }
            // Переход к следующей группе
            if ($dump[0][0] == $category) {
                $category = $dump[0][2];
            } else {
                $category = 0;
            }
            // Результирующий выпадающий блок
            $dump[0][0] == 1 ? $link = "" : $link = $dump[0][0];
            // Формирование ссылки следования
            $catLink = "<div class='popup-menu' onmouseover='return CtxMenuShow(this)' onmouseout='return CtxMenuHide(this)'>"
                ."<a href='".$defLink.$link."'>".$dump[0][1]."</a>".$out."&nbsp;&raquo;&nbsp;</div>".$catLink;
        }

        return $catLink;
    }

    protected function GetUploadID($uid = null)
    {
        if (isset($uid)) {
            $SQL = "select ID_UPLOAD, UID from SEQ_UPLOAD where SESSION='".SafeStr(session_id())
                ."' and UID=".$uid;
            $item = $this->DL->LFetchRecordRow($SQL);
            if ($item[1] == $uid) return $item[0];
        }

        $SQL = "insert into SEQ_UPLOAD (SESSION, UID) values ('".SafeStr(session_id())
            ."', ".(isset($uid)?$uid:"NULL").")";
        $this->DL->Execute($SQL);
        return $this->DL->PrimaryID();
    }

    protected function GetUploadState()
    {
        $SQL = "select ID_UPLOAD from SEQ_UPLOAD where ID_UPLOAD=".SafeInt(@$_REQUEST["uid"])
            ." and SESSION='".SafeStr(session_id())."'";
        $item = $this->DL->LFetchRecordRow($SQL);
        if (!$item) trigger_error("upload state corrupt"); else return $item[0];
    }

    protected function SelectorPrepare(&$page, $limit)
    {
        if ($page == 0) $page = 1;
        return " LIMIT ".(abs(($page-1)*$limit)).", ".$limit;
    }

    protected function SelectorOrder($value)
    {
        if ($value == 1) return " ASC"; else return " DESC";
    }

    protected function SelectorSorter($field = "b.DATE_LIFE")
    {
        $oby = SafeStr(@$_GET["oby"]);
        $osc = SafeStr(@$_GET["osc"]);
        if ($oby == "cost") $order = "b.COST "; else $order = $field;

        return $order.$this->SelectorOrder($osc);
    }

    protected function SelectorPage($page, $limit, $maxcount, $link, $ajax = true)
    {
        // Количество доступных элементов на странице
        $selcount = $this->DC["LIMIT_SELECTOR"];
        // Если общее количество элементов больше доступных, есть смысл реализовывать страничный режим
        if ($limit >= $maxcount) return false;
        // Добавление ссылки "Назад"
        if ($page > 1) {
            if ($ajax)
                $out = "<a href='".$link."&page=".($page-1)."' onclick='return AnQueryPager(".($page-1).")'>Назад</a>";
            else
                $out = "<a href='".$link."&page=".($page-1)."''>Назад</a>";
        } else {
            $out = "";
        }
        // Если текущая страница больше доступной, то нужно сделать ранж ссылок
        if ($page > $selcount + 1) {
            if ($ajax)
                $out .= "<a href='".$link."&page=1' onclick='return AnQueryPager(1);'>1</a> ... ";
            else
                $out .= "<a href='".$link."&page=1'>1</a> ... ";
        }
        // Цикл от точка-доступные до точка+доступные
        for ($index = $page - $selcount; $index < $page + $selcount; $index++)
        {
            // Цикл считается рабочим от первой до половины страниц
            if (($index < 1) || ($index >= $maxcount / $limit)) continue;
            // Определение активной ссылки
            if ($page == $index) $class = "selected"; else $class = "";
            // Определение ссылки
            if ($ajax)
                $clickRef = "href='".$link."&page=".$index."' onclick='return AnQueryPager(".$index.");'";
            else
                $clickRef = "href='".$link."&page=".$index."'";
            // Формирование итоговой ссылки
            $out .= "<a ".$clickRef." class='".$class."'>".$index."</a>";
        }
        // Получение последней страницы, округление в большую сторону
        $index = ceil($maxcount / $limit);
        // Определение активной ссылки
        if ($page == $index) $class = "selected"; else $class = "";
        // Формирование итоговой ссылки
        if ($ajax)
            $out .= "... <a href='".$link."&page=".$index."' class='".$class."' onclick='return AnQueryPager(".$index.");'>".$index."</a>";
        else
            $out .= "... <a href='".$link."&page=".$index."' class='".$class."'>".$index."</a>";
        // Добавление ссылки "Вперед"
        if ($page < $index) {
            if ($ajax)
                $out .= "<a href='".$link."&page=".($page+1)."' onclick='return AnQueryPager(".($page+1).")'>Вперед</a>";
            else
                $out .= "<a href='".$link."&page=".($page+1)."'>Вперед</a>";
        }

        return $out;
    }

    protected function DefinedCashGender()
    {
        $vector = array();
        array_push($vector, array(0 => "0", 1 => "Мужской"));
        array_push($vector, array(0 => "1", 1 => "Женский"));
        array_push($vector, array(0 => "2", 1 => "Не скажу"));
        return $vector;
    }

    protected function BuildSelect($SQL, $DefaultID = -1, $Empty = false)
    {
        // Получение набора элемента по указанному запросу
        $dump = $this->DL->LFetchRows($SQL);
        // Набор не содержит элементов
        if (count($dump) == 0) return false;
        // Набор содержит пустой атрибут
        if ($Empty != false) $out = "<option value=''>".$Empty."</option>"; else $out = "";
        // Перебор всех вхождений
        foreach ($dump as $item)
        {
            // Имеется элемент по умолчанию
            if ($item[0] == $DefaultID)
                $out .= "<option selected value='".$item[0]."'>".$item[1]."</option>";
            else
                $out .= "<option value='".$item[0]."'>".$item[1]."</option>";
        }

        return $out;
    }

    public function BuildSelectCash($vector, $DefaultID = -1, $Empty = false, $dropCount = -1)
    {
        if (count($vector) == 0) return false;
        // Набор содержит пустой атрибут
        if ($Empty) $out = "<option value=''>".$Empty."</option>"; else $out = "";
        // Отсев дроповых элементов
        $dropIndex = -1;
        foreach ($vector as $item)
        {
            $dropIndex++;
            if ($dropIndex <= $dropCount) continue;
            // Имеется элемент по умолчанию
            if ($item[0] == $DefaultID)
                $out .= "<option selected value='".$item[0]."'>".$item[1]."</option>";
            else
                $out .= "<option value='".$item[0]."'>".$item[1]."</option>";
        }

        return $out;
    }

    public function BuildSelectCity($vector, $DropID = -1)
    {
        $stream = "";
        $CityCount = 0;
        foreach ($vector as $item)
        {
            // Имеется элемент по умолчанию
            if (($item[0] != $DropID) && ($item[3] > 0)) {
                if ($item[2] != "") {
                    $stream .= "<li><a href='http://".$item[2].".".$this->DC["SITE_HOST"].$this->DC["SITE_DOMAIN"].$_SERVER["REQUEST_URI"]."'>".$item[1]."</a></li>";
                } else {
                    $stream .= "<li><a href='http://".$this->DC["SITE_HOST"].$this->DC["SITE_DOMAIN"].$_SERVER["REQUEST_URI"]."'>".$item[1]."</a></li>";
                }
                $CityCount++;
            }
            if ($CityCount == 15) break;
        }

        return $stream;
    }

    protected function BuildSelectPerPage()
    {
        $current = SafeInt(@$_SESSION["USER_PERPAGE"]);
        $out = "";
        for ($index = 20; $index < 100; $index=$index+20) {
            $out .= "<option value=".$index;
            if ($index == $current) $out .= " selected";
            $out .= ">".$index."</option>";
        }
        return $out;
    }



    protected function BuildTPLajaxPicture()
    {
        return file_get_contents(_TEMPLATE."default/ajax_pic_tpl.html");
    }

    /**
     * GetMaxPhotoCount()
     *
     * @return доступное количество загружаемых изображений для пользователя
     */
    protected function GetPhotoMaxCount($count)
    {
        if ($_SESSION["USER_ROLE"] == 5) return $this->DC["LIMIT_IMAGE_GUEST"] - $count;
          else
        if ($_SESSION["USER_ROLE"] == 6) return $this->DC["LIMIT_IMAGE_COMP"] - $count;
          else
        return $this->DC["LIMIT_IMAGE_AUTH"] - $count;
    }

    protected function SetItemPerPage()
    {
        if (isset($_REQUEST["ppg"])) {
            $_SESSION["USER_PERPAGE"] = SafeInt($_REQUEST["ppg"]);
        } else
        if (!isset($_SESSION["USER_PERPAGE"])) {
            $_SESSION["USER_PERPAGE"] = $this->DC["LIMIT_PAGE"];
        }
    }

    protected function GetUserColor($text, $role_id)
    {
        if ($role_id == self::ROLE_SUPPORT) {
            $color = $this->DC["COLOR-THEME"][1];
        } else
        if ($role_id == self::ROLE_ADMIN) {
            $color = $this->DC["COLOR-THEME"][2];
        } else
        if ($role_id == self::ROLE_MODER) {
            $color = $this->DC["COLOR-THEME"][3];
        } else
        if ($role_id == self::ROLE_GUEST) {
            $color = $this->DC["COLOR-THEME"][5];
        } else
        {
            $color = "#000000";
        }

        return "<font color=".$color.">".$text."</font>";
    }






    /**
     * TUser::RenderBox()
     *
     * @return
     */
    public function GetUserBox()
    {
        if (self::SafeUser($user_field))
        {
            $SQL = "select LOGIN, COUNTANN, COUNTMAIL, COUNTFAV from USER_DATA where ID_USER=".$user_field;
            $item = $this->DL->LFetchRecord($SQL);

            // Дополнительное меню
            $extendMenu = "";
            if (isset($_SESSION["USER_COMPANY"])) {
                $extendMenu .= "<div class='rightlink'><a href='http://#SITEPATH/cabcomp/'>Моя компания</a></div>";
            } else {
                $extendMenu .= "<div class='rightlink'><a href='http://#SITEPATH/cabcomp/'>Создать компанию</a></div>";
            }
            if ($_SESSION["USER_ROLE"] < 4) {
                $extendMenu .= "<div class='rightlink'><a href='http://#SITEPATH/admin/'>Моя админка</a></div>";
            }

            $stream = file_get_contents(_TEMPLATE."user/online.html");
            $stream = str_replace("#LOGIN", $item["LOGIN"], $stream);
            $stream = str_replace("#CHECKANN", $item["COUNTANN"], $stream);
            $stream = str_replace("#CHECKFAV", $item["COUNTFAV"], $stream);
            $stream = str_replace("#CHECKMAIL", $item["COUNTMAIL"], $stream);
            $stream = str_replace("#EXTENDED", $extendMenu, $stream);
        } else {
            $SQL = "select COUNTANN, COUNTFAV from USER_GUEST where ID_STATE=1 and ID_GUEST=".$user_field;
            $item = $this->DL->LFetchRecord($SQL);

            $stream = file_get_contents(_TEMPLATE."user/offline.html");
            $stream = str_replace("#CHECKANN", (int)$item["COUNTANN"], $stream);
            $stream = str_replace("#CHECKFAV", (int)$item["COUNTFAV"], $stream);
        }

        return $stream;
    }

    public function TplRenderPartner()
    {
        $path = _BANNER."footer/";
        if (!$dir = @opendir($path)) return false;
        // Чтение каталога на наличие баннеров
        $outData = "";
        while(($file = readdir($dir)))
        {
            if (!is_file($path.$file)) continue;
            $outData .= "<img src='".$path.$file."'/>";
        }

        return $outData;
    }

    public function TplRenderHeader()
    {
        $stream = file_get_contents(_TEMPLATE."default/default_header.html");
        $stream = str_replace("#BASEPATH", "http://".$_SERVER["HTTP_HOST"], $stream);

        return $stream;
    }

    public function TplRenderFooter()
    {
        // Подключение футера
        $stream = file_get_contents(_TEMPLATE."default/footer.html");
        $stream = str_replace("#INFOQUERY", $this->DL->count, $stream);

        // Подключение счетчиков
        if ($this->DC["USE_COUNTERS"]) {
            $stream = str_replace("#COUNTERS", file_get_contents(_TEMPLATE."default/counters.html"), $stream);
        } else {
            $stream = str_replace("#COUNTERS", "", $stream);
        }

        return $stream;
    }









    protected function RenderTypedField($category_id, $announce_id)
    {
        // Выбор доступных группы параметров и их зависимостей
        $SQL = "select ID_PARAM_GROUP, ID_CHILD, CAPTION, ID_FIELD_TYPE from REF_PARAM_GROUP"
            ." where ID_CATEGORY=".$category_id." order by ID_FIELD_TYPE";
        $dump = $this->DL->LFetch($SQL);

        // Выбор заданных групп параметров и их зависимостей
        $SQL = "select b.ID_PARAM_GROUP, rp.ID_PARAM from BLOCK_PARAM b, REF_PARAM rp"
            ." where b.VALUE=rp.ID_PARAM and b.ID_ANNOUNCE=".$announce_id;
        $param = $this->DL->LFetch($SQL);

        $stream = "";
        foreach ($dump as $item) {
            $field_id = $item["ID_FIELD_TYPE"];
            $group_id = $item["ID_PARAM_GROUP"];
            $caption = $item["CAPTION"];
            $default = -1;

            // Перебор элементов для индекса по умолчанию
            for ($index=0; $index < count($param); $index++) {
                if ($param[$index]["ID_PARAM_GROUP"] == $group_id) {
                    $default = $param[$index]["ID_PARAM"];
                    break;
                }
            }
            // Общий запрос на выбор набора атрибутов
            $SQL = "select ID_PARAM, CAPTION from REF_PARAM where ID_PARAM_GROUP=".$group_id;
            // Тип поля - список с дочерними объектами
            if ($field_id == self::FIELD_LINK) {
                $stream .= $caption." <select name='p_".$group_id."' onchange='LoadExtField(this, \"".$item["ID_CHILD"]."\")'>";
                $stream .= GetSelectOption($SQL, $default, true);
                $stream .= "</select>";
            } else
            // Тип поля - перечисление элементов
            if ($field_id == self::FIELD_LIST) {
                $stream .= $caption." <select name='p_".$group_id."'>";
                $stream .= GetSelectOption($SQL, $default, true);
                $stream .= "</select>";
            } else
            // Тип поля - дочерний объект
            if ($field_id == self::FIELD_CHILD) {
                $stream .= $caption." <select id='p_".$group_id."' name='p_".$group_id."'>";
                if ($default != -1) {
                    $stream .= GetSelectOption($SQL, $default, true);
                }
                $stream .= "</select>";
            } else
            // Тип поля - логический аттрибут
            if ($field_id == self::FIELD_CHECKBOX) {
                if ($default != -1) $checked = "checked "; else $checked = "";
                $stream .= "<input ".$checked."type='checkbox' name='p_".$group_id."'> ".$caption;
            }
        }

        return $stream;
    }

    protected function DownloadFiles($announce_id)
    {
        // Тест на передачу идентификатора аплоадера
        $upload_id = $this->GetUploadState();
        // Каталог объявления
        $destImage = _ANNOUNCE.$announce_id."/";
        // Каталог превью
        $destThumb = _ANNOUNCE.$announce_id."/thumb/";
        // Каталог источник картинок
        $srcImage = _UPLOAD.$upload_id."/";
        // Каталог источник хумбов
        $srcThumb = _UPLOAD.$upload_id."/thumb/";
        // Количество картинок для объявления
        $photoCount = 0;
        // Попытка создать каталог назначения с рекурсивным обходом
        if (!is_dir($destThumb) && !mkdir($destThumb, 0755, true)) trigger_error("download files mkdir");
        // Перебор файло на предмет картинок
        if ($handle = @opendir($srcImage))
        {
            while (false !== ($file = readdir($handle)))
            {
                if (preg_match('/\.(gif|jpe?g|png)/', $file, $matches))
                {
                    // Переброс стандартных фото
                    if (!rename($srcImage.$file, $destImage.$file)) trigger_error("download files rename");
                    // Переброс обычных фото, исключения хумб фото
                    if (is_file($srcThumb.$file)) {
                        if (!rename($srcThumb.$file, $destThumb.$file)) trigger_error("download files thumb rename");
                    }
                }
            }
            closedir($handle);
            RemoveDirectory($srcImage);
        }

        /* todo comment */
        if ($handle = @opendir($destImage))
        {
            while (false !== ($file = readdir($handle)))
            {
                if ($file == _THUMBPHOTO) {
                    $photoCount += 0.1;
                } else {
                    if (is_file($destImage.$file)) {
                        $photoCount += 1;
                    }
                }
            }
            closedir($handle);
        }

        return $photoCount;
    }

    protected function UploadFiles($announce_id, $uid)
    {
        // Каталог загрузки
        $destImage = _UPLOAD.$uid."/";
        // Каталог превью
        $destThumb = _UPLOAD.$uid."/thumb/";
        // Каталог источник картинок
        $srcImage = _ANNOUNCE.$announce_id."/";
        // Каталог источник хумбов
        $srcThumb = _ANNOUNCE.$announce_id."/thumb/";
        // Попытка создать каталог назначения с рекурсивным обходом
        if (!is_dir($destThumb) && !mkdir($destThumb, 0755, true)) trigger_error("upload files mkdir");
        // Массив главного фото
        $filePhoto = array();
        // Массив хумбов
        $fileThumb = array();
        // Перебор файло на предмет картинок
        if ($handle = @opendir($srcThumb))
        {
            while (false !== ($file = readdir($handle)))
            {
                if (preg_match('/\.(gif|jpe?g|png)/', $file, $matches))
                {
                    // Переброс стандартных фото
                    if (!copy($srcImage.$file, $destImage.$file)) trigger_error("upload files copy");
                    // Переброс обычных фото, исключения хумб фото
                    if (is_file($srcThumb.$file) && !copy($srcThumb.$file, $destThumb.$file)) trigger_error("upload files copy thumb");
                    // Формирование JSON класса
                    $item = new stdClass();
                    $item->name = $file;
                    $item->url = "/".$destImage.$file;
                    $item->thumbnail_url = "/".$destThumb.$file;
                    $item->delete_type = "POST";
                    $item->delete_url = "http://".$_SERVER['HTTP_HOST']."/ajax/uploadimg&uid=".$uid."&file=".rawurlencode($item->name);
                    array_push($fileThumb, $item);
                }
            }
            if (file_exists($srcImage._THUMBPHOTO)) {
                if (!copy($srcImage._THUMBPHOTO, $destImage._THUMBPHOTO)) trigger_error("upload files copy");
                $item = new stdClass();
                $item->name = _THUMBPHOTO;
                $item->url = "/".$destImage._THUMBPHOTO;
                $item->thumbnail_url = "/".$destImage._THUMBPHOTO;
                $item->delete_type = "POST";
                $item->delete_url = "http://".$_SERVER['HTTP_HOST']."/ajax/uploadimg&uid=".$uid."&file=".rawurlencode($item->name);
                array_push($filePhoto, $item);
            }
            closedir($handle);
        }

        return array($filePhoto, $fileThumb);
    }

    /**
     * TKupoAnnounce::UploadParam()
     *
     * @param mixed $announce_id
     * @return загрузку элементов и аттрибутов объявления
     */
    protected function UploadParam($announce_id)
    {
        reset($_POST);
        while (list($key, $val) = each($_POST))
        {
            // Элемент должен иметь вид p_<код элемента>
            if (!preg_match('#p_(.*)#', $key, $matches)) continue;

            $key = $matches[1];
            if ($val == "on") $val = true;
            // Тест на некорректный параметр
            $key = SafeInt($key);
            $val = SafeInt($val);
            if (($key == 0) || ($val == 0)) continue;

            // Добавление нового элемента для объявления
            $SQL = "insert into BLOCK_PARAM (ID_ANNOUNCE, ID_PARAM_GROUP, VALUE) values ("
                .$announce_id.", ".$key.", ".$val.")";
            $this->DL->Execute($SQL);
        }

        return true;
    }

    /**
     * TKupoAnnounce::ClearParam()
     *
     * @param mixed $announce_id
     * @return очищение элементов и атрибутов объявления
     */
    protected function ClearParam($announce_id)
    {
        // Запрос на удаление всех параметров для объявления
        $SQL = "delete from BLOCK_PARAM where ID_ANNOUNCE=".$announce_id;
        $this->DL->Execute($SQL);

        return true;
    }

    protected function ClearFiles($path)
    {
        RemoveDirectory($path."/");
    }

    /*todo*/
    public function GetFlCategory($id_category = 0)
    {
        $SQL = "select ID_CATEGORY, CAPTION from COMPANY_CATEGORY where ID_PARENT in (0, 1)"
            ." and ID_STATE=1 order by ID_PARENT, CAPTION";
        return $this->BuildSelect($SQL, $id_category);
    }

    /*todo*/
    protected function ToggleAnnounceCount($action, $cat_id, $city_id)
    {
        $SQL = "select LEVEL from REF_CATEGORY where LINK_ID=".$cat_id." or ID_CATEGORY=".$cat_id;
        $levels = $this->DL->LFetchRows($SQL);

        $link = "";
        foreach($levels as $level) {
            $link .= $level[0];
        }
        $link = substr(str_replace(".", ",", $link), 0, strlen($link) - 1);

        $inc = ($action == self::AC_INCREMENT) ? $inc = 1 : $inc = -1;

        $SQL = "update COUNT_ANNOUNCE set ITEMCOUNT=ITEMCOUNT+".$inc
            ." where ID_CATEGORY in (".$link.") and ID_CITY in (88, ".$city_id.")"
            ." and (ITEMCOUNT>0 or ".$inc."=1)";
        $this->DL->Execute($SQL);
    }

    /*todo description */
    protected function ToggleAnnounceUser($type, $user_id, $guest_id, $announce_id = "null")
    {
        $SQL = "SELECT F_ANNOUNCE_COUNT(".$type.", '".$user_id."', '".$guest_id."', ".$announce_id.");";
        $this->DL->Execute($SQL);
    }

    /**
     * TKupoAnnounce::RenderExtendedAnnounce()
     *
     * @param mixed $SQL
     * @param mixed $multiAnnounce
     * @param mixed $dumpCount
     * @return форматированный набор дополнительный объявлений
     */
    //TODO Шаблон дополнительных объявлений
    protected function RenderExtendedAnnounce($SQL, &$multiAnnounce, &$dumpCount, $path = "/announce")
    {
        if (!isset($multiAnnounce)) $multiAnnounce = array();
        // Выполнение указанного запроса
        $dump = $this->DL->LFetch($SQL);
        // Увеличения счетчика обработанных объявлений
        $dumpCount += count($dump);

        $stream = file_get_contents(_TEMPLATE."announce/viewthumb.html");
        $outData = "";

        foreach($dump as $item) {
            // Добавление в блок обработанных объявлений
            array_push($multiAnnounce, $item["ID_ANNOUNCE"]);
            // форматирование вывода
            $data = $stream;
            $data = str_replace("#ANNOUNCEID", $item["ID_ANNOUNCE"], $data);
            $data = str_replace("#ANNOUNCEPATH", $path, $data);
            $data = str_replace("#COST", $item["COST"], $data);
            $data = str_replace("#CURRENCY", $item["LITERAL"], $data);
            $data = str_replace("#CAPTION", ($item["CAPTION"]), $data);
            $data = str_replace("#IMGPATH", $this->GetAnnounceImage($item["ID_ANNOUNCE"], $item["ID_STATE"]), $data);
            $outData .= $data;
        }

        return $outData;
    }

    protected function NextCounter($key, $value = false)
    {
        $SQL = "update SEQ_COUNTERS set ".$key."=".$key." + 1";
        if ($value) $SQL .= ",".$key."ID=".$value." where ".$key."ID<>".$value;
        $this->DL->Execute($SQL);

        $SQL = "select ".$key." from SEQ_COUNTERS";
        $item = $this->DL->LFetchRecordRow($SQL);

        return $item[0];
    }

    /* todo */
    public function GetBannerHeader($city_id, $category_id)
    {
        $SQL = "select ID_BANNER, CAPTION, LINKURL from BLOCK_BANNER"
            ." where ID_STATE=1 and RSHOWED < LIMSHOW and ID_CATEGORY > 1 and ID_CITY=".$city_id." and ID_CATEGORY=".$category_id;
        $banner = $this->DL->LFetchRecordRow($SQL);
        // Если баннера на категорию не существует, выбор любого доступного баннера
        if (!$banner) {
            $SQL = "select ID_BANNER, CAPTION, LINKURL from BLOCK_BANNER"
                ." where ID_CATEGORY=1 and ID_STATE=1 and RSHOWED < LIMSHOW order by DATE_SHOW";
            $banner = $this->DL->LFetchRecordRow($SQL);
        }
        // Баннер по умолчанию
        if (!$banner) {
            $banner[0] = 0;
            $banner[1] = "Занять это место!";
            $banner[2] = "/cabuser/banner&m=create";
        } else {
            $SQL = "update BLOCK_BANNER set RSHOWED=RSHOWED+1, DATE_SHOW=NOW() where ID_STATE=1 and ID_BANNER=".$banner[0];
            $this->DL->Execute($SQL);
        }
        // Чтение каталога на наличие баннера
        $imgPath = _BANNER."header/".$banner[0].".jpg";
        if (!is_file($imgPath) || ($category_id == 0)) return false;

        // Формирование итогового представления
        $stream = file_get_contents(_TEMPLATE."default/banner_header.html");
        $stream = str_replace("#BANNERID", $banner[0], $stream);
        $stream = str_replace("#CAPTION", $banner[1], $stream);
        $stream = str_replace("#IMGPATH", $imgPath, $stream);
        $stream = str_replace("#LINKURL", urlencode($banner[2]), $stream);

        return $stream;
    }

    public function GetNewsSimple()
    {
        require_once(_LIBRARY."lib_info.php");
        $Info = new TInfo();
        return $Info->RenderNews();
    }

    public function TplFavouriteLoad($dump, $user_field)
    {
        // Блок определения избранных объявлений
        $favourite = array();
        foreach ($dump as $item) {
            array_push($favourite, $item["ID_ANNOUNCE"]);
        }
        // При наличии объявлений, поиск доступных для сравнения
        if (count($favourite) > 0) {
            $SQL = "select ID_ANNOUNCE from ANNOUNCE_FAVOURITE b where ID_ANNOUNCE in (".implode(",", $favourite).") and ".$user_field;
            // Получение данных
            $favourite = $this->DL->LFetchRowsField($SQL);
        }
        return $favourite;
    }

    public function TplFavouriteReplacement($announce_id, $favourite)
    {
        // Проверка на присутствии в избранном
        if (in_array($announce_id, $favourite))
            return "fav-on";
        else
            return "fav-off";
    }

    public function TplStyleLoad($dump)
    {
        $announces = array();
        foreach ($dump as $item) {
            array_push($announces, $item["ID_ANNOUNCE"]);
        }
        if (count($announces) > 0) {
            $SQL = "select bs.ID_ANNOUNCE, rf.TAGCLASS, rf.TAGNAME from BLOCK_STYLES bs, REF_STYLE rf"
                ." where bs.ID_ANNOUNCE in (".implode(",", $announces).") and rf.ID_STYLE=bs.ID_STYLE";
            $styles = $this->DL->LFetchRows($SQL);
        } else $styles = array();

        return $styles;
    }

    public function TplStyleReplacement($announce_id, $stream, $styles)
    {
        $style = $this->GetAnnounceStyle($announce_id, $styles);
        $stream = str_replace('#CLASS', $style[0], $stream);
        $stream = str_replace('#TAGDESC', $style[1], $stream);

        return $stream;
    }

    public function TplCompanyLoad($dump)
    {
        $announces = array();
        foreach ($dump as $item) {
            if ($item["ID_GROUP"] > -1)
                array_push($announces, $item["ID_USER"]);
        }
        if (count($announces) > 0) {
            $SQL = "select ry.ID_USER, ry.ID_COMPANY, ry.ID_TYPE as COMTYPE, ry.CAPTION as COMCAPTION, ry.DOMAIN_ACTIVE, ry.RATING as COMRATING"
                ." from COMPANY_DATA ry where ry.ID_USER in (".implode(",", $announces).") and ry.ID_TYPE < 5 and ry.ID_STATE in (1, 4)";
            return $this->DL->LFetch($SQL);
        } else return array();
    }

    public function TplImageLoad($path, $item)
    {
        // Подготовка галереи изображений
        $lightbox = "";
        // Открытие галереи для промодерированного объявления
        if (($item["ID_STATE"] != self::STATE_MODER) && ($handle = @opendir($path."thumb/")))
        {
            while (false !== ($file = readdir($handle)))
            {
                // Пропуск каталогов
                if (!is_file($path.$file)) continue;
                // Поиск уменьшенных фотографий, на основе которых делается ссылка на большие
                $filethumb = $path."thumb/".$file;
                $filepath = $path.$file;
                // Новый блок
                $lightbox .= "<li><a rel='prettyPhoto[mixed]' href='/".$filepath."'><img src='/".$filethumb."'"
                    ." width='".$this->DC["IMAGE_ICONSIZE"]."' height='".$this->DC["IMAGE_ICONSIZE"]."' /></a></li>";
            }
        }
        return $lightbox;
    }

    public function TplCategoryLoad($level)
    {
        $cat_tree = explode(".", $level);
        $cat_list = "";
        for ($index = 0; $index < count($cat_tree) - 1; $index++)
        {
            $SQL = "select ID_CATEGORY, CAPTION from REF_CATEGORY where ID_STATE=1 and ID_PARENT=".$cat_tree[$index]
                ." order by ORDERBY, CAPTION";
            $cat_item = self::BuildSelect($SQL, $cat_tree[$index + 1]);
            if ($cat_item != false) {
                $cat_list .= '<select id="category" name="category" onchange="return annkit.listcat(this);">'.$cat_item."</select>";
            }
        }
        return $cat_list;
    }



    public function ResourcePushCss($filename)
    {
        $filename = $filename."/style.css";
        if (file_exists($filename)) {
            $this->CSS .= "<link href='/".$filename."' rel='stylesheet' type='text/css' />\r\n";
        }
    }

    public function WorkTime_WorkToText($worktime, &$timeWork, &$timeBreak)
    {
        $timeWork = "Выходной";
        $timeBreak = "";
        if (count($worktime) == 1) return false;

        if ($worktime["break"]) {
            $timeBreak = sprintf("Обед %02d:%02d - %02d:%02d",
                $worktime[4], $worktime[5], $worktime[6], $worktime[7]);
        }
        $timeWork = sprintf("%02d:%02d - %02d:%02d", $worktime[0], $worktime[1], $worktime[2], $worktime[3]);
    }


    public function TplWorkTime($worktime)
    {
        global $days;

        $worktime = @json_decode($worktime, true);
        if (count($worktime) == 0) {
            return "Не указано";
        }

        $stream = "";
        for ($index = 0; $index < count($worktime); $index++) {
            self::WorkTime_WorkToText($worktime[$index], $timeWork, $timeBreak);
            $stream .= "<li><b>".$days[$index]."</b><br/>".$timeWork."<br/>".$timeBreak."</li>";
        }
        return $stream;
    }

    protected function FastLibMail()
    {
        include(_LIBRARY."lib_email.php");
        return new TEmail();
    }

    protected function FastLibMsg()
    {
        include(_LIBRARY."lib_mailbox.php");
        return new TMailBox();
    }

    public function FastLibUpload()
    {
        include _ENGINE."upload/ajaximage.php";
    }



    public function CashCategory()
    {
        if (!isset($_SESSION["LIST_CATEGORY"])) {
            $SQL = "select ID_CATEGORY, CAPTION from REF_CATEGORY where ID_STATE=1"
                ." and ID_PARENT in (0, 1) order by ID_PARENT, ORDERBY, CAPTION";
            $_SESSION["LIST_CATEGORY"] = $this->DL->LFetchRows($SQL);
        }
        return $_SESSION["LIST_CATEGORY"];
    }

    public function CashCity()
    {
        if (!isset($_SESSION["LIST_CITY"])) {
            $SQL = "select ID_CITY, CAPTION, LATINOS, IMPORTANT from REF_CITY where ID_STATE=1"
                ." order by CAPTION";
            $_SESSION["LIST_CITY"] = $this->DL->LFetchRows($SQL);
        }
        return $_SESSION["LIST_CITY"];
    }

    public function CashGrant()
    {
        if (!isset($_SESSION["LIST_GRANT"])) {
            $SQL = "select ID_RIGHT, GRANTED from BLOCK_ROLE where ID_ROLE=".$_SESSION["USER_ROLE"];
            $_SESSION["LIST_GRANT"] = $this->DL->LFetchRows($SQL);
        }
        return $_SESSION["LIST_GRANT"];
    }

    public function CashCurrency()
    {
        if (!isset($_SESSION["LIST_CURRENCY"])) {
            $SQL = "select ID_CURRENCY, CAPTION from REF_CURRENCY where ID_STATE=1"
                ." order by ORDERBY, CAPTION";
            $_SESSION["LIST_CURRENCY"] = $this->DL->LFetchRows($SQL);
        }
        return $_SESSION["LIST_CURRENCY"];
    }

    public function AnnounceCheckPath($category_id)
    {
        $SQL = "select count(*) as VALID from REF_CATEGORY rc left outer join REF_CATEGORY rc2"
            ." on rc2.LEVEL like concat(rc.LEVEL, '%') where rc.ID_CATEGORY=".$category_id;
        if ($this->DL->LFetchField($SQL) != 1) RedirectError(self::E_NOTPARAM);
    }

    public function AnnounceCheckCost(&$cost, &$currency)
    {
        if (($cost == 0) || ($currency == self::CURRENCY_DEFAULT)) {
            $currency = self::CURRENCY_DEFAULT;
            $cost = "NULL";
        }
    }






}
class TCompanyData
{
    public $id;
    public $user_id;
    public $template;
    public $city;
    public $city_id;
    public $street;
    public $map;
    public $caption;
    public $contact;
    public $worktime;
}
class TCompanyGroup
{
    public $level = array();
    public $sidebar = "";
    public $relink = "";
    public $folder = "";
}
class TNativeContact
{
    public $type;
    public $class;
    public $text;
    public $data;
    public $phone;
}
class TNativeContactPhone
{
    public $code;
    public $ops;
    public $data;
}
class TNativeMedia
{
    const CALLBACK_BACKGROUND = "DesignBackground";

    public $type;
    public $folder;
    public $page;
    public $path;
    public $media_type;
    public $media_data;
    public $media_file;
    public $pathpreview = "";
    public $ext;
    public $cssClass = "image";
    public $perPage = 9;
    public $perData = 0;
}