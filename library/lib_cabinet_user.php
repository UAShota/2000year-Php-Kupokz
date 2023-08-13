<?
class TCabinetUser extends TInterface
{
    private $TPL;
    public $TPLLINK;
    public $TPLSUBLINK;
    public $MODE;
    private $ErrID;

    const LINK_ID_DEFAULT = 1;
    const LINK_ID_ANNOUNCE = 2;
    const LINK_ID_MESSAGE = 3;
    const LINK_ID_SETTINGS = 4;
    const LINK_ID_TICKET = 5;
    const LINK_ID_BANNER = 6;

    const E_SAVED = 1;
    const E_NOTSAVED = 2;
    const E_INVALIDMAIL = 3;
    const E_INVALIDPWD = 4;
    const E_INVALIDPWDLEN = 5;

        // Страница ошибки
    const LINK_ERROR = "&e=";

    public function __construct()
    {
        parent::__construct();
        $this->TPL = _TEMPLATE."cabinet_user/";
        $this->MODE = SafeStr($_REQUEST["cabuser"]);
        $this->ErrID = SafeInt(@$_GET["e"]);
    }

    private function RenderError($content = "")
    {
        $error_id = $this->ErrID;
        $errclass = false;
        if ($error_id == 0) return $content;

        if ($error_id == self::E_SAVED) {
            $error = "Сохранено успешно";
            $errclass = parent::E__SUCCS;
        } else
        if ($error_id == self::E_NOTSAVED) {
            $error = "Не удалось сохранить";
            $errclass = parent::E__ERROR;
        } else
        if ($error_id == self::E_INVALIDMAIL) {
            $error = "Некорректно указан почтовый ящик";
            $errclass = parent::E__ERROR;
        } else
        if ($error_id == self::E_INVALIDPWD) {
            $error = "Неверно указан старый пароль";
            $errclass = parent::E__ERROR;
        } else
        if ($error_id == self::E_INVALIDPWDLEN) {
            $error = "Слишком короткие пароли";
            $errclass = parent::E__ERROR;
        } else
        {
            $error = "^_^";
            $errclass = parent::E__ERROR;
        }

        $stream = file_get_contents(_TEMPLATE."default/default_error.html");
        $stream = str_replace("#STYLE", $errclass, $stream);
        $stream = str_replace("#TEXT", $error, $stream);
        $stream = str_replace("#CONTENT", $content, $stream);

        return $stream;
    }

    private function SetTopLink($link_id)
    {
        $stream = file_get_contents($this->TPL."default_top.html");
        $pattern = '#id="'.$link_id.'"#ms';
        $replacement = 'id="'.$link_id.'" class="selected"';

        $this->TPLLINK = preg_replace($pattern, $replacement, $stream);
    }

    private function SetTopSubLink($filename, $mode = null)
    {
        $mode = (is_null($mode) || $mode == "") ? $this->MODE : $mode;

        $stream = file_get_contents($this->TPL.$filename."_top.html");
        $pattern = '#/'.$mode.'"#ms';
        $replacement = '/'.$mode.'" class="selected"';

        $this->TPLSUBLINK = preg_replace($pattern, $replacement, $stream);
    }

    public function RenderDefault()
    {
        $this->SetTopLink(self::LINK_ID_DEFAULT);

        $user_id = isset($_SESSION["USER_ID"]) ? $_SESSION["USER_ID"] : parent::ROLE_GUEST;
        parent::SafeUserID($user_field);

        //todo replace prof

        $SQL = "select u.*, rr.CAPTION as ROLE, rc.CAPTION as CITY, cd.CAPTION, rc.LATINOS,"
            ." uncompress(u.TEXTDATA) as TEXTDATA"
            ." from USER_DATA u left join COMPANY_DATA cd on cd.ID_USER=u.ID_USER and cd.ID_TYPE<5,"
            ." REF_ROLE rr, REF_CITY rc where rr.ID_STATE=1 and rc.ID_STATE=1 and u.ID_STATE=1"
            ." and rr.ID_ROLE=u.ID_ROLE and rc.ID_CITY=u.ID_CITY and u.ID_USER=".$user_id;
        $item = $this->DL->LFetchRecord($SQL);

        $stream = file_get_contents($this->TPL."default_data.html");
        $stream = str_replace("#DATELIFE", $item["DATE_LIFE"], $stream);
        $stream = str_replace("#CAPTION", $item["LOGIN"], $stream);
        $stream = str_replace("#NAMEFIRST", $item["NAME_FIRST"], $stream);
        $stream = str_replace("#NAMELAST", $item["NAME_LAST"], $stream);
        $stream = str_replace("#NAMEMIDDLE", $item["NAME_MIDDLE"], $stream);
        $stream = str_replace("#LATIONS", $item["LATINOS"], $stream);
        $stream = str_replace("#CITY", $item["CITY"], $stream);
        $stream = str_replace("#COUNTANN", $item["COUNTANN"], $stream);
        $stream = str_replace("#COUNTFAV", $item["COUNTFAV"], $stream);
        $stream = str_replace("#COUNTMAL", $item["COUNTMAIL"], $stream);
        $stream = str_replace("#LATINOS", $item["LATINOS"], $stream);
        $stream = str_replace("#PHOTOPATH", $this->GetPhotoUser($item["ID_USER"]), $stream);
        $stream = str_replace("#TEXTVIEW", BBCodeNativeToHTML($item["TEXTDATA"]), $stream);

        $SQL = "select b.ID_ANNOUNCE, b.CAPTION, b.COST, rc.CAPTION as LITERAL, b.ID_STATE"
            ." from ANNOUNCE_DATA b, REF_CURRENCY rc"
            ." where b.ID_STATE in (1,2,3,4) and b.ID_CURRENCY=rc.ID_CURRENCY"
            ." and b.ID_GROUP=-1 and ".$user_field
            ." order by DATE_LIFE desc limit 0, 6";
        $data = $this->RenderExtendedAnnounce($SQL, $multiAnnounce, $dumpCount);
        $stream = str_replace("#SIMILATE", $data, $stream);

        return $stream;
    }

    public function NoticePost()
    {
        $m_comment = SafeInt(SafeBool("comment"));
        $m_mailbox = SafeInt(SafeBool("mailbox"));
        $o_viewother = SafeInt(SafeBool("viewother"));

        $SQL = "update USER_DATA set M_COMMENT=".$m_comment
            .", M_MAILBOX=".$m_mailbox.", O_VIEWOTHER=".$o_viewother." where ID_USER=".$_SESSION["USER_ID"];
        $this->DL->Execute($SQL);
        RedirectError(self::E_SAVED);
    }

    public function NoticeEdit()
    {
        $SQL = "select M_COMMENT, M_MAILBOX, O_VIEWOTHER from USER_DATA where ID_USER=".$_SESSION["USER_ID"];
        $item = $this->DL->LFetchRecord($SQL);

        $stream = file_get_contents($this->TPL."notice_edit.html");
        $stream = str_replace("#COMMENT", HtmlCheckbox($item["M_COMMENT"]), $stream);
        $stream = str_replace("#MAILBOX", HtmlCheckbox($item["M_MAILBOX"]), $stream);
        $stream = str_replace("#VIEWOTHER", HtmlCheckbox($item["O_VIEWOTHER"]), $stream);

        $this->SetTopLink(self::LINK_ID_SETTINGS);
        $this->SetTopSubLink("account");

        return $this->RenderError($stream);
    }

    public function MailPost()
    {
        $pwd_old = SafeStr($_POST["password"]);
        $email = SafeStr($_POST["email"]);

        if (!CheckMail($email)) {
            RedirectError(self::E_INVALIDMAIL);
        }
        if (!TextRange($pwd_old, 4)) {
            RedirectError(self::E_INVALIDPWDLEN);
        }

        $SQL = "select ID_USER from USER_DATA where PWD=md5('".$pwd_old."') and ID_USER=".$_SESSION["USER_ID"];
        if (!$this->DL->LFetchRows($SQL)) {
            RedirectError(self::E_INVALIDPWD);
        }

        $SQL = "update USER_DATA set EMAIL='".$email."'"
            ." where ID_USER=".$_SESSION["USER_ID"]." and PWD=md5('".$pwd_old."')";
        $this->DL->Execute($SQL);
        RedirectError(self::E_SAVED);
    }

    public function MailEdit()
    {
        $SQL = "select EMAIL from USER_DATA where ID_USER=".$_SESSION["USER_ID"];
        $item = $this->DL->LFetchRecord($SQL);

        $stream = file_get_contents($this->TPL."email_edit.html");
        $stream = str_replace("#EMAIL", $item["EMAIL"], $stream);

        $this->SetTopLink(self::LINK_ID_SETTINGS);
        $this->SetTopSubLink("account");

        return $this->RenderError($stream);
    }

    public function PasswordPost()
    {
        $pwd_old = SafeStr($_POST["pwd_old"]);
        $pwd_new = SafeStr($_POST["pwd_new"]);

        if (!TextRange($pwd_new, 4) || !TextRange($pwd_old, 4)) {
            RedirectError(self::E_INVALIDPWDLEN);
        }

        $SQL = "select ID_USER from USER_DATA where PWD=md5('".$pwd_old."') and ID_USER=".$_SESSION["USER_ID"];
        if (!$this->DL->LFetchRows($SQL)) {
            RedirectError(self::E_INVALIDPWD);
        }

        $SQL = "update USER_DATA set PWD=md5('".$pwd_new."')"
            ." where ID_USER=".$_SESSION["USER_ID"]." and PWD=md5('".$pwd_old."')";
        $this->DL->Execute($SQL);
        RedirectError(self::E_SAVED);
    }

    public function PasswordEdit()
    {
        $SQL = "select EMAIL from USER_DATA where ID_USER=".$_SESSION["USER_ID"];
        $item = $this->DL->LFetchRecord($SQL);

        $stream = file_get_contents($this->TPL."password_edit.html");

        $this->SetTopLink(self::LINK_ID_SETTINGS);
        $this->SetTopSubLink("account");

        return $this->RenderError($stream);
    }

    private function DownloadAvatar($user_id)
    {
        // Код каталога загрузки
        $upload_id = $this->GetUploadState();
        // Каталог источник аватарки
        $destImage = _AVATAR.$user_id."/";
        $destFile = $destImage._USERAVATAR;
        // Каталог загрузки
        $srcImage = _UPLOAD.$upload_id."/";
        $srcFile = $srcImage._USERAVATAR;
        // Переброс выбранного аватара, если нет - удаление текущего
        if (is_file($srcFile)) {
            if (!is_dir($destImage) && !mkdir($destImage)) trigger_error("download avatar mkdir");
            if (!rename($srcFile, $destFile)) trigger_error("download avatar rename");
        } else
        if (is_file($destFile)) {
            if (!unlink($destFile)) trigger_error("download avatar unlink");
        }
    }

    private function UploadAvatar($user_id, $uid)
    {
        // Каталог загрузки
        $destImage = _UPLOAD.$uid."/";
        $destFile = $destImage._USERAVATAR;
        // Каталог источник аватарки
        $srcImage = _AVATAR.$user_id."/";
        $srcFile = $srcImage._USERAVATAR;
        // Попытка создать каталог назначения с рекурсивным обходом
        if (file_exists($srcFile) && !is_dir($destImage) && !mkdir($destImage)) trigger_error("upload avatar mkdir");
        // Проверка аватара на существование
        if (file_exists($srcFile) && copy($srcFile, $destFile)) {
            $item = new stdClass();
            $item->name = _USERAVATAR;
            $item->url = "/".$srcFile;
            $item->thumbnail_url = "/".$destFile;
            $item->delete_type = "POST";
            $item->delete_url = "http://".$_SERVER['HTTP_HOST']."/ajax/uploadimg&uid=".$uid."&file=".rawurlencode($item->name);
            return array($item);
        }
        return array();
    }

    public function AccountEdit()
    {
        $SQL = "select ID_USER, NAME_FIRST, NAME_LAST, NAME_MIDDLE, NAME_GENDER, ID_CITY, LOCATION_STREET,"
            ." uncompress(TEXTDATA) as TEXTDATA, uncompress(CONTACT) as CONTACT"
            ." from USER_DATA where ID_USER=".$_SESSION["USER_ID"];
        $item = $this->DL->LFetchRecord($SQL);

        $uid = $this->GetUploadID($item["ID_USER"]);
        $json = $this->UploadAvatar($item["ID_USER"], $uid);

        $stream = file_get_contents($this->TPL."account_edit.html");
        $stream = str_replace("#UPLOADID", $uid, $stream);
        $stream = str_replace("#PHOTOSIZE", $this->DC["IMAGE_PHOTOSIZE"], $stream);
        $stream = str_replace("#JSONPHOTO", json_encode($json), $stream);
        $stream = str_replace("#NAMEFIRST", $item["NAME_FIRST"], $stream);
        $stream = str_replace("#NAMELAST", $item["NAME_LAST"], $stream);
        $stream = str_replace("#NAMEMIDDLE", $item["NAME_MIDDLE"], $stream);
        $stream = str_replace("#LOCATIONSTREET", $item["LOCATION_STREET"], $stream);
        $stream = str_replace("#TEXTDATA", ($item["TEXTDATA"]), $stream);
        $stream = str_replace("#CONTACT", $this->ContactRender($item["CONTACT"]), $stream);
        $stream = str_replace("#CITY", $this->BuildSelectCash(parent::CashCity(), $item["ID_CITY"]), $stream);
        $stream = str_replace("#NAMEGENDER", $this->BuildSelectCash($this->DefinedCashGender(), $item["NAME_GENDER"]), $stream);
        $stream = str_replace("#AJAXPICTPL", $this->BuildTPLajaxPicture(), $stream);

        $this->SetTopLink(self::LINK_ID_SETTINGS);
        $this->SetTopSubLink("account");

        return $this->RenderError($stream);
    }

    public function AccountPost()
    {
        $nameFirst = SafeStr($_POST["name_first"]);
        $nameLast = SafeStr($_POST["name_last"]);
        $nameMiddle = SafeStr($_POST["name_middle"]);
        $nameGender = SafeInt($_POST["name_gender"]);
        $textdata = SafeStr($_POST["textdata"]);
        $locationStreet = SafeStr($_POST["location_street"]);
        $city_id = SafeInt($_POST["id_city"]);

        $SQL = "update USER_DATA set NAME_FIRST='".$nameFirst."', NAME_LAST='".$nameLast."', NAME_MIDDLE='".$nameMiddle."',"
            ." NAME_GENDER=".$nameGender.", LOCATION_STREET='".$locationStreet."', ID_CITY=".$city_id.","
            ." TEXTDATA=compress('".$textdata."'), CONTACT=compress('".$this->ContactUpload()."')"
            ." where ID_USER=".$_SESSION["USER_ID"];
        $this->DL->Execute($SQL);
        $this->DownloadAvatar($_SESSION["USER_ID"]);

        RedirectError(self::E_SAVED);
    }

    public function RenderFavourite()
    {
        $this->SetTopLink(self::LINK_ID_ANNOUNCE);
        $this->SetTopSubLink("announce");

        // Страница просмотра
        $page = SafeInt(@$_GET["page"]);
        // Количество объявлений на страницу, подготовка запроса
        $limit = $this->SelectorPrepare($page, $this->DC["LIMIT_PAGE"]);
        // Определение порядка сортировки
        $order = $this->SelectorSorter();
        // Определение с фотографиями
        $fl_photo = SafeBool("fl_photo");
        // Определение пользователя
        $this->SafeUserID($user_field, "af");

        $SQL = "SELECT SQL_CALC_FOUND_ROWS b.ID_ANNOUNCE, b.CAPTION, b.COST, rc.LITERAL, b.DATE_LIFE,"
            ." b.VIEWS, b.COMMENTS, cc.CAPTION as CITY, ud.ID_USER, ud.LOGIN, b.ID_STATE, ud.ID_TYPE, ud.ID_ROLE,"
            ." ry.ID_COMPANY, ry.ID_TYPE as COMTYPE, ry.CAPTION as COMCAPTION, ry.DOMAIN_ACTIVE, b.ID_GROUP"
            ." FROM ANNOUNCE_DATA b left join COMPANY_DATA ry on ry.ID_USER=b.ID_USER and ry.ID_STATE in (1,4) and ry.ID_TYPE<5,"
            ." ANNOUNCE_FAVOURITE af, REF_CURRENCY rc, REF_CITY cc, USER_DATA ud"
            ." where b.ID_ANNOUNCE=af.ID_ANNOUNCE and b.ID_CURRENCY=rc.ID_CURRENCY and cc.ID_CITY=b.ID_CITY"
            ." and b.ID_USER=ud.ID_USER and b.ID_STATE in (1,2,3,4) and rc.ID_STATE=1 and cc.ID_STATE=1 and ud.ID_STATE=1"
            ." and ".$user_field;
        // Определение с фотографиями
        if ($fl_photo) {
            $SQL .= " and IMAGES>0";
        }
        // Определение сортировки и страницы поиска
        $SQL .= " ORDER BY ".$order.$limit;

        $dump = $this->DL->LFetch($SQL);
        $pagecount = $this->DL->LMaxRows();

        // Если избранных объявлений нет, вывод сообщения
        if (count($dump) == 0) {
            $stream = file_get_contents($this->TPL."favourite_empty.html");
            return $stream;
        }

        /*todo*/
        $announces = array();
        foreach ($dump as $item) {
            array_push($announces, $item["ID_ANNOUNCE"]);
        }
        if (count($announces) > 0) {
            /*todo*/
            $SQL = "select bs.ID_ANNOUNCE, rf.TAGCLASS, rf.TAGNAME from BLOCK_STYLES bs, REF_STYLE rf"
                ." where bs.ID_ANNOUNCE in (".implode(",", $announces).") and rf.ID_STYLE=bs.ID_STYLE";
            $styles = $this->DL->LFetchRows($SQL);
        } else $styles = array();

        // Генерация страничных переходов и загрузка шаблона. Стоит тут, потому что использует MAX_ROWS
        $streamsimple = file_get_contents($this->TPL."favourite_block.html");
        $pageselector = $this->SelectorPage($page, $this->DC["LIMIT_PAGE"], $pagecount, "/cabuser/favourite");
        $outData = "";

        // Перебор собранных объявлений
        foreach($dump as $item) {
            // Определение модератора
            $item = $this->SafeAgent($item);
            $item = $this->SafeUserCompany($item, $item["ID_GROUP"] == -1);

            // Заполнение шаблона объявление
            $data = str_replace('#USERLINK', $item["USERLINK"], $streamsimple);
            $data = str_replace('#USERTYPE', $item["USERTYPE"], $data);
            $data = str_replace('#USERITEM', $item["USERITEM"], $data);
            $data = str_replace('#LOGIN', $item["LOGIN"], $data);
            $data = str_replace('#CITY', $item["CITY"], $data);
            $data = str_replace('#VIEWS', $item["VIEWS"], $data);
            $data = str_replace('#COMMENTS', $item["COMMENTS"], $data);
            $data = str_replace('#COST', $item["COST"], $data);
            $data = str_replace('#CURRENCY', $item["LITERAL"], $data);
            $data = str_replace('#ANNOUNCEID', $item["ID_ANNOUNCE"], $data);
            $data = str_replace('#CAPTION', ($item["CAPTION"]), $data);
            $data = str_replace('#IMAGEPATH', $this->GetAnnounceImage($item["ID_ANNOUNCE"], $item["ID_STATE"]), $data);
            $data = str_replace('#DATELIFE', GetLocalizeDate($item["DATE_LIFE"]), $data);

            /* todo */
            $style = $this->GetAnnounceStyle($item["ID_ANNOUNCE"], $styles);
            $data = str_replace('#CLASS', $style[0], $data);
            $data = str_replace('#TAGDESC', $style[1], $data);

            $outData .= $data;
        }
        if ($this->AJ) {
            // Вывод реквеста
            SendJson((array($outData, $pageselector, $pagecount)));
        } else {
            // Вывод основного блока на страницу
            $stream = file_get_contents($this->TPL."favourite_view.html");
            $stream = str_replace("#BLOCKDATA", $outData, $stream);
            $stream = str_replace("#PAGESELECTOR", $pageselector, $stream);
            $stream = str_replace("#ITEMCOUNT", $pagecount, $stream);
            return $stream;
        }
    }

    public function RenderAnnounce()
    {
        $this->SetTopLink(self::LINK_ID_ANNOUNCE);
        $this->SetTopSubLink("announce");

        // Страница просмотра
        $page = SafeInt(@$_GET["page"]);
        // Количество объявлений на страницу, подготовка запроса
        $limit = $this->SelectorPrepare($page, $this->DC["LIMIT_PAGE"]);
        // Определение порядка сортировки
        $order = $this->SelectorSorter();
        // Определение с фотографиями
        $fl_photo = SafeBool("fl_photo");
        // Определение пользователя
        $this->SafeUserID($user_field);

        // Второй самый невьебенный запрос
        $SQL = "select SQL_CALC_FOUND_ROWS b.ID_ANNOUNCE, b.CAPTION, b.COST, rr.LITERAL as CURRENCY, b.DATE_LIFE, b.VIEWS, b.ID_STATE,"
            ." b.COMMENTS, rc.CAPTION as CITY from ANNOUNCE_DATA b, REF_CURRENCY rr, REF_CITY rc"
            ." where rc.ID_STATE=1 and b.ID_STATE in (1,2,3,4) and rr.ID_STATE=1 and b.ID_GROUP=-1"
            ." and rc.ID_CITY=b.ID_CITY and rr.ID_CURRENCY=b.ID_CURRENCY and ".$user_field;
        // Определение с фотографиями
        if ($fl_photo) {
            $SQL .= " and IMAGES>0";
        }
        // Определение сортировки и страницы поиска
        $SQL .= " ORDER BY ".$order.$limit;
        $dump = $this->DL->LFetch($SQL);
        $pagecount = $this->DL->LMaxRows();

        // Если избранных объявлений нет, вывод сообщения
        if (count($dump) == 0) {
            $stream = file_get_contents($this->TPL."announce_empty.html");
            return $stream;
        }

        /*todo*/
        $announces = array();
        foreach ($dump as $item) {
            array_push($announces, $item["ID_ANNOUNCE"]);
        }
        if (count($announces) > 0) {
            /*todo*/
            $SQL = "select bs.ID_ANNOUNCE, rf.TAGCLASS, rf.TAGNAME from BLOCK_STYLES bs, REF_STYLE rf"
                ." where bs.ID_ANNOUNCE in (".implode(",", $announces).") and rf.ID_STYLE=bs.ID_STYLE";
            $styles = $this->DL->LFetchRows($SQL);
        } else $styles = array();

        // Генерация страничных переходов и загрузка шаблона. Стоит тут, потому что использует MAX_ROWS
        $pageselector = $this->SelectorPage($page, $this->DC["LIMIT_PAGE"], $pagecount, "/cabuser/announce");
        $streamsimple = file_get_contents($this->TPL."announce_block.html");
        $outData = "";

        // Блок определения избранных объявлений
        $favourite = array();
        foreach ($dump as $item) {
            array_push($favourite, $item["ID_ANNOUNCE"]);
        }
        // При наличии объявлений, поиск доступных для сравнения
        if (count($favourite) > 0) {
            $SQL = "select ID_ANNOUNCE from ANNOUNCE_FAVOURITE b where ID_ANNOUNCE in (".implode(",", $favourite).") and ".$user_field;
            // Получение данных
            $favourite = $this->DL->LFetchRows($SQL);
        }

        // Перебор собранных объявлений
        foreach($dump as $item) {
            $announce_id = $item["ID_ANNOUNCE"];
            // Заполнение шаблона объявление
            $data = str_replace('#ANNOUNCEID', $announce_id, $streamsimple);
            $data = str_replace('#COST', $item["COST"], $data);
            $data = str_replace('#CURRENCY', $item["CURRENCY"], $data);
            $data = str_replace('#CITY', $item["CITY"], $data);
            $data = str_replace('#VIEWS', $item["VIEWS"], $data);
            $data = str_replace('#COMMENTS', $item["COMMENTS"], $data);
            $data = str_replace('#CAPTION', ($item["CAPTION"]), $data);
            $data = str_replace('#IMAGEPATH', $this->GetAnnounceImage($announce_id, $item["ID_STATE"]), $data);
            $data = str_replace('#DATELIFE', GetLocalizeDate($item["DATE_LIFE"]), $data);

            /* todo */
            $style = $this->GetAnnounceStyle($item["ID_ANNOUNCE"], $styles);
            $data = str_replace('#CLASS', $style[0], $data);
            $data = str_replace('#TAGDESC', $style[1], $data);

            // Проверка на присутствии в избранном
            if (in_array($announce_id, $favourite)) {
                $data = str_replace('#FAVOURITE', "fav-on", $data);
            } else {
                $data = str_replace('#FAVOURITE', "fav-off", $data);
            }
            $outData .= $data;
        }
        if ($this->AJ) {
            // Вывод реквеста
            SendJson((array($outData, $pageselector, $pagecount)));
        } else {
            // Вывод основного блока на страницу
            $stream = file_get_contents($this->TPL."favourite_view.html");
            $stream = str_replace("#BLOCKDATA", $outData, $stream);
            $stream = str_replace("#PAGESELECTOR", $pageselector, $stream);
            $stream = str_replace("#ITEMCOUNT", $pagecount, $stream);
            return $stream;
        }
    }

    public function RenderArchive()
    {
        $this->SetTopLink(self::LINK_ID_ANNOUNCE);
        $this->SetTopSubLink("announce");

        return file_get_contents($this->TPL."announce_empty.html");
    }

    public function ExecutePlugin($content, $toplink, $sublink)
    {

    }

    public function ExecuteTicket()
    {
        $mode = @$_REQUEST["m"];

        $this->SetTopLink(self::LINK_ID_TICKET);
        $this->SetTopSubLink("ticket", $mode);

        include(_LIBRARY."lib_ticket.php");
        $Ticket = new TTicket();
        return $Ticket->Execute($mode);
    }

    public function ExecuteMailbox()
    {
        $mode = @$_REQUEST["m"];

        $this->SetTopLink(self::LINK_ID_MESSAGE);
        $this->SetTopSubLink("mailbox", $mode);

        include(_LIBRARY."lib_mailbox.php");
        $Mailbox = new TMailbox();
        return $Mailbox->Execute($mode);
    }

    public function ExecuteBanner()
    {
        $mode = @$_REQUEST["m"];

        $this->SetTopLink(self::LINK_ID_BANNER);

        include(_LIBRARY."lib_banner.php");
        $Banner = new TBanner();
        return $Banner->Execute($mode);
    }
}