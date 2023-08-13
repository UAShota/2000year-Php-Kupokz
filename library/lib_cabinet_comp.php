<?
class TCabinetComp extends TInterface
{
    private $TPL;
    private $ErrID;
    public $TPLLINK;
    public $TPLSUBLINK;
    public $MODE;
    private $ACTION;
    public $Company;
    /*todo
    public $aitem;*/

    const LINK_ERROR = "/cabcomp/&e="; // для итоговых ошибок

    const LINK_ID_DEFAULT = 1;
    const LINK_ID_SETTINGS = 2;
    const LINK_ID_GROUP = 4;
    const LINK_ID_CONTACT = 5;
    const LINK_ID_TICKET = 6;

    const CATPATH_SIMPLE = 0;
    const CATPATH_GENERAL = 1;
    const CATPATH_PARENT = 2;


    const E_COMPANYEXISTS = 1;  // компания уже существует у пользователя
    const E_INVALIDTEXTLEN = 2; // некорректная длина передаваемых параметров
    const E_SAVED = 3;          // сохранено успешно
    const E_NOTSAVED = 4;       // не сохранено
    const E_INVALIDGROUP = 5;   // указанная группа не найдена
    const E_NOTFOUND = 6;       // объявление не найдено
    const E_NEWSNOTFOUND = 7;   // новость не найдена
    const E_NEWSDELETED = 8;    // новость удалена
    const E_DELETED = 9;        // Товар удален

    public function __construct()
    {
        parent::__construct();
        $this->TPL = _TEMPLATE."cabinet_comp/";
        $this->MODE = SafeStr($_REQUEST["cabcomp"]);
        $this->ACTION = SafeStr(@$_REQUEST["m"]);







        $this->ErrID = SafeInt(@$_GET["e"]);
        $this->CompID = SafeInt(@$_SESSION["USER_COMPANY"]);

        if ($this->CompID > 0) {
            $SQL = "select *, uncompress(WORKTIME) as WORKTIME from COMPANY_DATA where ID_STATE in (1, 4) and ID_COMPANY=".$this->CompID;
            $this->Company = $this->DL->LFetchRecord($SQL);
        }
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
        if ($error_id == self::E_COMPANYEXISTS) {
            $error = "Компания уже существует";
            $errclass = parent::E__ERROR;
        } else
        if ($error_id == self::E_INVALIDTEXTLEN) {
            $error = "Некорректные текстовые значения";
            $errclass = parent::E__ERROR;
        } else
        if ($error_id == self::E_INVALIDGROUP) {
            $error = "Указанной группы не существует";
            $errclass = parent::E__ERROR;
        } else
        if ($error_id == self::E_NOTFOUND) {
            $error = "Объявление не найдено";
            $errclass = parent::E__ERROR;
        } else
        if ($error_id == self::E_NEWSNOTFOUND) {
            $error = "Новость не найдена";
            $errclass = parent::E__ERROR;
        } else
        if ($error_id == self::E_NEWSDELETED) {
            $error = "Новость удалена";
            $errclass = parent::E__SUCCS;
        } else
        if ($error_id == self::E_DELETED) {
            $error = "Товар удален";
            $errclass = parent::E__SUCCS;
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


    private function SetLinkPanel($link_id, $filename = "", $mode = null)
    {
        $mode = is_null($mode) ? $this->MODE : $mode;

        $stream = file_get_contents($this->TPL."default_top.html");
        $pattern = '#id="'.$link_id.'"#ms';
        $replacement = 'id="'.$link_id.'" class="selected"';
        $this->TPLLINK = preg_replace($pattern, $replacement, $stream);

        if ($filename != "") {
            $stream = file_get_contents($this->TPL.$filename."_top.html");
            $pattern = '#/'.$mode.'"#ms';
            $replacement = '/'.$mode.'" class="selected"';
            $this->TPLSUBLINK = preg_replace($pattern, $replacement, $stream);
        }
    }

    /*todo effective perf*/
    public function TplDefault()
    {
        $this->SetLinkPanel(self::LINK_ID_DEFAULT);

        $SQL = "select cd.ID_USER, cc.ID_CATEGORY, cc.CAPTION as CATEGORY, cd.DATE_LIFE, cd.CAPTION as COMCAPTION, cd.TEXTVIEW,"
            ." cd.DOMAIN_AUTO, cd.DOMAIN_MODERATE, cd.DOMAIN_ACTIVE, cd.DOMAIN_DATE, cd.ID_STATE, cd.ID_COMPANY, cd.LOCATION_STREET,"
            ." cd.RATING, rc.ID_CITY, rc.CAPTION as CITY, rc.LATINOS, rt.ID_TYPE, rt.CAPTION as COMTYPE, TEXTINDEX, uncompress(CONTACT) as CONTACT"
            ." from COMPANY_DATA cd, COMPANY_CATEGORY cc, REF_CITY rc, COMPANY_TYPE rt"
            ." where cd.ID_TYPE<>".self::COMPANY_TYPE_INFO." and cd.ID_STATE in (1, 4) and cc.ID_STATE=1 and rc.ID_STATE=1"
            ." and rt.ID_STATE=1 and cc.ID_CATEGORY=cd.ID_CATEGORY and rc.ID_CITY=cd.ID_CITY and rt.ID_TYPE=cd.ID_TYPE"
            ." and ID_COMPANY=".$this->CompID." and ID_USER=".$_SESSION["USER_ID"];
        $item = $this->DL->LFetchRecord($SQL);

        $stream = file_get_contents($this->TPL."default_data.html");
        $stream = str_replace("#CAPTION", $item["COMCAPTION"], $stream);
        $stream = str_replace("#DOMAINAUTO", $item["DOMAIN_AUTO"], $stream);
        $stream = str_replace("#COMTYPE", $item["COMTYPE"], $stream);
        $stream = str_replace("#COMIDTYPE", $item["ID_TYPE"], $stream);
        $stream = str_replace("#CATID", $item["ID_CATEGORY"], $stream);
        $stream = str_replace("#CATEGORY", $item["CATEGORY"], $stream);
        $stream = str_replace("#RATING", $item["RATING"], $stream);
        $stream = str_replace("#LATINOS", $item["LATINOS"], $stream);
        $stream = str_replace("#CITY", $item["CITY"], $stream);
        $stream = str_replace("#TEXTVIEW", ($item["TEXTVIEW"]), $stream);
        $stream = str_replace("#DATELIFE", GetLocalizeTime($item["DATE_LIFE"]), $stream);

        $photopath = $this->GetPhotoCompany($item["ID_COMPANY"]);
        $stream = str_replace("#PHOTOPATH", $photopath, $stream);

        if ($item["DOMAIN_ACTIVE"] != $item["DOMAIN_AUTO"]) {
            $siteURL = $item["DOMAIN_ACTIVE"].".".$this->DC["SITE_HOST"].$this->DC["SITE_DOMAIN"];
            $stream = str_replace("#DOMAINSYMBOL", "<a href='http://".$siteURL."'>".$siteURL."</a>", $stream);
        } else {
            $stream = str_replace("#DOMAINSYMBOL", "<span class='span-nolink'>Отсутствует</span>, но можно <a href='/cabcomp/domain'>Заказать</a>", $stream);
        }

        if ($item["DOMAIN_MODERATE"] != "") {
            $stream = str_replace("#DOMAINMODERATE", "<p>Домены на модерации: <b>".$item["DOMAIN_MODERATE"]."</b></p>", $stream);
        } else {
            $stream = str_replace("#DOMAINMODERATE", "", $stream);
        }

        if ($item["ID_STATE"] == parent::STATE_MODER) {
            $stream = str_replace("#COMSTATE", "<span class='span-nolink'>(на модерации)</span>", $stream);
        } else {
            $stream = str_replace("#COMSTATE", "", $stream);
        }

        $item = $this->SafeUserCompany($item, false);

        $SQL = "select b.ID_ANNOUNCE, b.CAPTION, b.COST, rc.CAPTION as LITERAL, b.ID_STATE"
            ." from ANNOUNCE_DATA b, REF_CURRENCY rc"
            ." where b.ID_STATE in (1,2,3,4) and b.ID_CURRENCY=rc.ID_CURRENCY"
            ." and ID_GROUP>-1 and ID_USER=".$_SESSION["USER_ID"]
            ." order by DATE_LIFE desc limit 0, 6";
        $data = $this->RenderExtendedAnnounce($SQL, $multiAnnounce, $dumpCount, $item["USERLINK"]."/item");
        $stream = str_replace("#SIMILATE", $data, $stream);

        // Вычиление эффективности компании крайне черновой вариант
        // +5 за название
        $progress = 5;
        // +10 за один контакт и +15 за более
        if ($item["CONTACT"] == ";") $item["CONTACT"] = "";
        $contactCount = substr_count($item["CONTACT"], ";");
        if ($contactCount == 1) $progress += 10;
        if ($contactCount > 1) $progress += 15;
        // +5 за улицу
        if (utf8_strlen($item["LOCATION_STREET"]) >= 5) $progress += 5;
        // +1..+10 за текст в 800 символов
        $progress += (100 * utf8_strlen(utf8_substr($item["TEXTVIEW"], 0, 800)) / 8000);
        // +20 за фото
        if ($photopath != _COMPANY._THUMBEMPTY) $progress += 20;
        // +1..10 за ключевые слова
        $indexcount = mb_substr_count($item["TEXTINDEX"], " ", "utf-8");
        if ($indexcount > 10) $indexcount = 10;
        $progress += $indexcount;
        // +20 за домен
        if ($item["DOMAIN_AUTO"] != $item["DOMAIN_ACTIVE"] ) $progress += 20;
        // +5 за базовый пакет
        if ($item["ID_TYPE"] == 1) $progress += 5;

        $stream = str_replace("#PROGRESSWIDTH", round(310 / 100 * $progress), $stream);
        $stream = str_replace("#PROGRESS", round($progress), $stream);

        return $this->RenderError($stream);
    }

    private function DownloadAvatar($company_id)
    {
        // Код каталога загрузки
        $upload_id = $this->GetUploadState();
        // Каталог источник аватарки
        $destImage = _COMPANY.$company_id."/";
        $destFile = $destImage._COMPAVATAR;
        // Каталог загрузки
        $srcImage = _UPLOAD.$upload_id."/";
        $srcFile = $srcImage._COMPAVATAR;

        // Переброс выбранного аватара, если нет - удаление текущего
        if (is_file($srcFile)) {
            if (!is_dir($destImage) && !mkdir($destImage)) trigger_error("download avatar mkdir");
            if (!rename($srcFile, $destFile)) trigger_error("download avatar renaime");
        } else
        if (is_file($destFile)) {
            if (!unlink($destFile)) trigger_error("download avatar unlink");
        }
    }

    private function UploadAvatar($company_id, $uid)
    {
        // Каталог загрузки
        $destImage = _UPLOAD.$uid."/";
        $destFile = $destImage._COMPAVATAR;
        // Каталог источник аватарки
        $srcImage = _COMPANY.$company_id."/";
        $srcFile = $srcImage._COMPAVATAR;
        // Попытка создать каталог назначения с рекурсивным обходом
        if (file_exists($srcFile) && !is_dir($destImage) && !mkdir($destImage)) trigger_error("upload avatar mkdir");
        // Проверка аватара на существование
        if (file_exists($srcFile) && copy($srcFile, $destFile)) {
            $item = new stdClass();
            $item->name = _COMPAVATAR;
            $item->url = "/".$srcFile;
            $item->thumbnail_url = "/".$destFile;
            $item->delete_type = "POST";
            $item->delete_url = "http://".$_SERVER['HTTP_HOST']."/ajax/uploadimg&uid=".$uid."&file=".rawurlencode($item->name);
            return array($item);
        }
        return array();
    }

    public function TplRegister()
    {
        $this->SetLinkPanel(self::LINK_ID_DEFAULT);

        $SQL = "select u.CONTACT, cd.ID_COMPANY, u.ID_CITY, u.LOCATION_STREET"
            ." from USER_DATA u left outer join COMPANY_DATA cd on cd.ID_USER=u.ID_USER"
            ." where cd.ID_TYPE<>5 and u.ID_USER=".$_SESSION["USER_ID"];
        $item = $this->DL->LFetchRecord($SQL);

        if (is_numeric($item["ID_COMPANY"])) {
            Redirect(self::LINK_ERROR.self::E_COMPANYEXISTS);
        }
        $uid = $this->GetUploadID($_SESSION["USER_ID"]);

        $SQL = "select ID_CATEGORY, CAPTION from COMPANY_CATEGORY where ID_PARENT=1 order by ORDERBY, CAPTION";
        $list_caty = $this->BuildSelect($SQL);

        $stream = file_get_contents($this->TPL."comp_register.html");
        $stream = str_replace("#CATEGORY", $list_caty, $stream);
        $stream = str_replace("#LOCATIONSTREET", $item["LOCATION_STREET"], $stream);
        $stream = str_replace("#CITY", $this->BuildSelectCash(parent::CashCity(), $item["ID_CITY"], false, 0), $stream);
        $stream = str_replace("#CONTACT", $this->ContactRender($item["CONTACT"], true), $stream);
        $stream = str_replace("#UPLOADID", $uid, $stream);
        $stream = str_replace("#PHOTOSIZE", $this->DC["IMAGE_PHOTOSIZE"], $stream);
        $stream = str_replace("#JSONPHOTO", json_encode(array()), $stream);
        $stream = str_replace("#AJAXPICTPL", $this->BuildTPLajaxPicture(), $stream);

        return $this->RenderError($stream);
    }

    public function PostRegister()
    {
        $caption = SafeStr(@$_POST["caption"]);
        $subdomain = SafeStr(@$_POST["subdomain"]);
        $textdata = SafeStr(@$_POST["textdata"]);
        $category = SafeStr(@$_POST["category"]);
        $city_id = SafeInt(@$_POST["id_city"]);
        $location_street = SafeStr(@$_POST["location_street"]);

        if (!TextRange($caption, 3, 60)) {
            RedirectError(self::E_INVALIDTEXTLEN);
        }
        // Дубликат компании
        $SQL = "select ID_COMPANY from COMPANY_DATA where ID_TYPE<>5 and ID_USER=".$_SESSION["USER_ID"];
        if ($this->DL->LFetchRecordRow($SQL)) {
            Redirect(self::LINK_ERROR.self::E_COMPANYEXISTS);
        }

        // Автоподомен компании
        do {
            $counter = $this->NextCounter("CNT_COMPANY");
            $domain_auto = GetStretchNumber($counter + 9, 6);
        } while (CheckValidDomainAuto($domain_auto));

        // Создание компании
        $SQL = "insert into COMPANY_DATA (CAPTION, ID_USER, DOMAIN_MODERATE, DOMAIN_AUTO, DOMAIN_ACTIVE, ID_CITY, LOCATION_STREET, ID_CATEGORY, TEXTDATA, CONTACT)"
            ." values('".$caption."', ".$_SESSION["USER_ID"].", '".$subdomain."', '".$domain_auto."', '".$domain_auto."'"
            .", ".$city_id.", '".$location_street."', ".$category.", compress('".$textdata."'), compress('".$this->ContactUpload()."'))";
        $this->DL->Execute($SQL);
        $company_id = $this->DL->PrimaryID();
        $this->CompID = $company_id;

        $_SESSION["USER_COMPANY"] = $company_id;

        // Обновление количество компаний в категории
        $SQL = "update COUNT_COMPANY set ITEMCOUNT=ITEMCOUNT+1 where"
            ." (ID_CATEGORY = (select ID_PARENT from COMPANY_CATEGORY where ID_CATEGORY=".$category.")"
            ." or ID_CATEGORY=".$category.") and ID_CITY in (88, ".$city_id.")";
        $this->DL->Execute($SQL);

        // Аватарка
        $this->DownloadAvatar($company_id);
        RedirectBack();
    }

    public function TplDomain()
    {
        $this->SetLinkPanel(self::LINK_ID_SETTINGS, "comp");

        $SQL = "select DOMAIN_AUTO, DOMAIN_MODERATE from COMPANY_DATA"
            ." where ID_USER=".$_SESSION["USER_ID"]." and ID_COMPANY=".$this->CompID;
        $item = $this->DL->LFetchRecord($SQL);

        $stream = file_get_contents($this->TPL."domain_edit.html");
        $stream = str_replace("#DOMAIN_MODERATE", $item["DOMAIN_MODERATE"], $stream);
        $stream = str_replace("#DOMAIN", $item["DOMAIN_AUTO"], $stream);

        return $this->RenderError($stream);
    }

    public function PostDomain()
    {
        $subdomain = SafeStr(@$_POST["subdomain"]);
        $company_id = $this->CompID;

        $moder = ($subdomain == "") ? "DOMAIN_MODERATE=null" : "ID_STATE=4, DOMAIN_MODERATE='".$subdomain."'";

        $SQL = "update COMPANY_DATA set CAPTION=CAPTION, ".$moder
            ." where ID_USER=".$_SESSION["USER_ID"]." and ID_COMPANY=".$company_id;
        $this->DL->Execute($SQL);

        RedirectError(self::E_SAVED);
    }

    public function TplSettings()
    {
        $this->SetLinkPanel(self::LINK_ID_SETTINGS, "comp");

        $SQL = "select *, uncompress(TEXTDATA) as TEXTDATA from COMPANY_DATA"
            ." where ID_USER=".$_SESSION["USER_ID"]." and ID_COMPANY=".$this->CompID;
        $item = $this->DL->LFetchRecord($SQL);

        $uid = $this->GetUploadID($item["ID_COMPANY"]);
        $json = $this->UploadAvatar($item["ID_COMPANY"], $uid);

        $stream = file_get_contents($this->TPL."comp_edit.html");
        $stream = str_replace("#CAPTION", ($item["CAPTION"]), $stream);
        $stream = str_replace("#TEXTVIEW", ($item["TEXTVIEW"]), $stream);
        $stream = str_replace("#TEXTINDEX", ($item["TEXTINDEX"]), $stream);
        $stream = str_replace("#TEXTDATA", ($item["TEXTDATA"]), $stream);
        $stream = str_replace("#UPLOADID", $uid, $stream);
        $stream = str_replace("#PHOTOSIZE", $this->DC["IMAGE_PHOTOSIZE"], $stream);
        $stream = str_replace("#JSONPHOTO", json_encode($json), $stream);
        $stream = str_replace("#AJAXPICTPL", $this->BuildTPLajaxPicture(), $stream);

        return $this->RenderError($stream);
    }

    public function PostSettings()
    {
        $caption = SafeStr(@$_POST["caption"]);
        $textdata = SafeStr(@$_POST["textdata"]);
        $textview = SafeStr(@$_POST["textview"]);
        $textindex = SafeStr(@$_POST["textindex"]);
        $company_id = $this->CompID;

        if (!TextRange($caption, 3, 60)) {
            RedirectError(self::E_INVALIDTEXTLEN);
        }

        $SQL = "update COMPANY_DATA set CAPTION='".$caption."', REALINDEX='".MorphyText($caption." ".$textindex." ".$textview)."'"
            .", TEXTVIEW='".$textview."', TEXTINDEX='".$textindex."', TEXTDATA=compress('".$textdata."')"
            ." where ID_USER=".$_SESSION["USER_ID"]." and ID_COMPANY=".$company_id;
        $this->DL->Execute($SQL);
        $this->DownloadAvatar($company_id);
        RedirectError(self::E_SAVED);
    }

    public function TplContact()
    {
        $this->SetLinkPanel(self::LINK_ID_CONTACT);

        $SQL = "select uncompress(CONTACT) as CONTACT, ID_CITY, LOCATION_STREET, LOCATION_MAP from COMPANY_DATA"
            ." where ID_USER=".$_SESSION["USER_ID"]." and ID_COMPANY=".$this->CompID;
        $item = $this->DL->LFetchRecord($SQL);

        $stream = file_get_contents($this->TPL."contact_edit.html");
        $stream = str_replace("#CONTACT", $this->ContactRender($item["CONTACT"], true), $stream);
        $stream = str_replace("#CITY", $this->BuildSelectCash(parent::CashCity(), $item["ID_CITY"], false, 0), $stream);
        $stream = str_replace("#LOCATIONSTREET", ($item["LOCATION_STREET"]), $stream);
        $stream = str_replace("#LOCATIONMAP", htmlspecialchars_decode($item["LOCATION_MAP"]), $stream);

        return $this->RenderError($stream);
    }

    public function PostContact()
    {
        $location_street = SafeStr(@$_POST["location_street"]);
        $location_map = SafeStr(@$_POST["gmap_mark_value"]);
        $city_id = SafeInt(@$_POST["id_city"]);

        $SQL = "update COMPANY_DATA set ID_CITY=".$city_id.", CONTACT=compress('".$this->ContactUpload()."'),"
            ." LOCATION_STREET='".$location_street."', LOCATION_MAP='".$location_map."'"
            ." where ID_USER=".$_SESSION["USER_ID"]." and ID_COMPANY=".$this->CompID;
        $this->DL->Execute($SQL);

        RedirectError(self::E_SAVED);
    }

    public function TplNews()
    {
        $this->SetLinkPanel(self::LINK_ID_SETTINGS, "comp");

        $newsid = SafeInt(@$_GET["id"]);

        // todo - маразм архитектора
        $drop = SafeBool("drop");
        if ($drop) {
            $SQL = "update COMPANY_NEWS set ID_STATE=".parent::STATE_DELETED
                ." where ID_NEWS=".$newsid." and ID_COMPANY=".$this->CompID;
            $this->DL->Execute($SQL);
            Redirect("/cabcomp/news&e=".self::E_NEWSDELETED);
        }

        if ($newsid != 0) {
            $SQL = "select CAPTION, DATE_LIFE, uncompress(TEXTDATA) as TEXTDATA"
                ." from COMPANY_NEWS where ID_COMPANY=".$this->CompID
                ." and ID_NEWS=".$newsid;
            $item = $this->DL->LFetchRecord($SQL) or Redirect("/cabcomp/news&e=".self::E_NEWSNOTFOUND);
        }

        $stream = file_get_contents($this->TPL."news_view.html");
        $stream = str_replace("#IDNEWS", $newsid, $stream);
        $stream = str_replace("#CAPTION", (@$item["CAPTION"]), $stream);
        $stream = str_replace("#TEXTDATA", (@$item["TEXTDATA"]), $stream);
        $stream = str_replace("#DATELIFE", GetShortDate(@$item["DATE_LIFE"]), $stream);

        $SQL = "select ID_NEWS, CAPTION, DATE_LIFE"
            ." from COMPANY_NEWS where ID_COMPANY=".$this->CompID
            ." and ID_STATE=1 order by DATE_LIFE desc";
        $dump = $this->DL->LFetch($SQL);

        $newstream = file_get_contents($this->TPL."news_item.html");
        $outData = "";
        foreach ($dump as $item) {
            $out = str_replace("#IDNEWS", $item["ID_NEWS"], $newstream);
            $out = str_replace("#CAPTION", ($item["CAPTION"]), $out);
            $out = str_replace("#DATELIFE", $item["DATE_LIFE"], $out);
            $outData .= $out;
        }
        $stream = str_replace("#CONTENT", $outData, $stream);

        return $this->RenderError($stream);
    }

    public function PostNews()
    {
        $id = SafeInt(@$_POST["id"]);
        $caption = SafeStr(@$_POST["caption"]);
        $textdata = SafeStr(@$_POST["textdata"]);
        $datelife = Date("Y-m-d", @strtotime(SafeStr(@$_POST["datelife"])));

        if ($id == 0) {
            $SQL = "insert into COMPANY_NEWS (CAPTION, TEXTDATA, DATE_LIFE, ID_COMPANY) values("
                ."'".$caption."', compress('".$textdata."'), '".$datelife."', ".$this->CompID.")";
        } else {
            $SQL = "update COMPANY_NEWS set CAPTION='".$caption."', TEXTDATA=compress('".$textdata."'),"
                ."DATE_LIFE='".$datelife."' where ID_COMPANY=".$this->CompID." and ID_NEWS=".$id;
        }
        $this->DL->Execute($SQL);

        Redirect("/cabcomp/news&e=".self::E_SAVED);
    }

    private function InlineTplCategoryTree($parent, &$dump, &$tree, $selected, $mode, $group)
    {
        for ($index = 0; $index < count($dump); $index++) {
            if ($dump[$index]["ID_PARENT"] == $parent)
            {
                // Иерархия групп
                if ($mode == self::CATPATH_GENERAL)
                {
                    if ($dump[$index]["ID_GROUP"] == $selected) {
                        $caption = "<b>".$dump[$index]["CAPTION"]."</b>";
                    } else {
                        $caption = $dump[$index]["CAPTION"];
                    }
                     $tree .= "<li><span class='folder'><a href='/cabcomp/cat&id=".$dump[$index]["ID_GROUP"]."'>".$caption."</a></span>";
                // Список групп
                } else {
                    $tree .= "<option value=".$dump[$index]["ID_GROUP"];
                    // Родитель для редактирования
                    if ($mode == self::CATPATH_PARENT) {
                        // Выделенный
                        if ($dump[$index]["ID_GROUP"] == $group->item["ID_PARENT"]) $tree .= " selected";
                        // Потомок зацикленности
                        if (strpos($dump[$index]["LEVEL"], $group->item["LEVEL"]) !== false) $tree .= " disabled";
                    } else {
                    // Текущий для добавления
                        if ($dump[$index]["ID_GROUP"] == $group->item["ID_GROUP"]) $tree .= " selected";
                    }
                    $tree .= ">".GroupLevelIndent($dump[$index]["LEVEL"], $dump[$index]["CAPTION"])."</option>";
                }

                $newparent = $dump[$index]["ID_GROUP"];
                array_splice($dump, $index, 1);
                $index = -1;
                $stream = "";

                $this->InlineTplCategoryTree($newparent, $dump, $stream, $selected, $mode, $group);

                if ($mode == self::CATPATH_GENERAL) {
                    if ($stream != "") {
                        $tree .= "<ul>".$stream."</ul>";
                    }
                    $tree .= "</li>";
                } else {
                    $tree .= $stream;
                }
            }
        }
    }

    private function InlineTplCategoryPath($group_id, $mode)
    {
        $SQL = "select * from COMPANY_GROUP where ID_COMPANY=".$this->CompID
            ." order by ID_PARENT, ORDERBY";
        $dump = $this->DL->LFetch($SQL);

        $group = new StdClass();
        // Поиск текущей группы
        foreach ($dump as $item) {
            if ($item["ID_GROUP"] == $group_id) {
                $group->item = $item;
                break;
            }
        }
        if (!isset($group->item)) {
            $group->item["ID_GROUP"] = 0;
            $group->item["LEVEL"] = "";
        }

        /* todo refactoring */
        // Уровни категории
        $group->level = explode(".", $group->item["LEVEL"]);
        $group->link = "";
        $group->tree = "";
        $group->dump = $dump;
        foreach ($group->dump as $item)
        {
            // Перелинковка категорий
            if (in_array($item["ID_GROUP"], $group->level)) {
                $group->link .= " &raquo; <a href='/cabcomp/cat&id=".$item["ID_GROUP"]."'>"
                    .$item["CAPTION"]."</a>";
            }
        }
        $this->InlineTplCategoryTree(0, $dump, $group->tree, $group_id, $mode, $group);

        return $group;;
    }

    public function TplCategory()
    {
        $this->SetLinkPanel(self::LINK_ID_GROUP, "group");

        $group_id = SafeInt(@$_REQUEST["id"]);

        $group_block = file_get_contents($this->TPL."group_element_a.html");
        $group = $this->InlineTplCategoryPath($group_id, self::CATPATH_GENERAL);

        $outData = "";
        $outBlock = "";
        foreach ($group->dump as $item)
        {
            if ($item["ID_PARENT"] == $group_id) {
                $outBlock = str_replace("#GROUPID", $item["ID_GROUP"], $group_block);
                $outBlock = str_replace("#CAPTION", $item["CAPTION"], $outBlock);
                $outData .= $outBlock;
            }
        }

        $SQL = "select b.ID_ANNOUNCE, b.CAPTION, b.ID_STATE, b.ITEMCOUNT, b.COST,"
            ." rc.LITERAL, rm.CAPTION as MEAS"
            ." from ANNOUNCE_DATA b, REF_CURRENCY rc, REF_MEAS rm"
            ." where b.ID_STATE in (1,2,3,4) and rc.ID_CURRENCY=b.ID_CURRENCY and rm.ID_MEAS=b.ID_MEAS"
            ." and ID_GROUP=".$group_id." and ID_USER=".$_SESSION["USER_ID"]." order by b.POSITION desc";
        $dump = $this->DL->LFetch($SQL);

        $group_block = file_get_contents($this->TPL."group_element_b.html");
        $outBlock = "";
        foreach ($dump as $item)
        {
            $outBlock = str_replace("#ITEMID", $item["ID_ANNOUNCE"], $group_block);
            $outBlock = str_replace("#CAPTION", $item["CAPTION"], $outBlock);
            $outBlock = str_replace("#ITEMCOUNT", $item["ITEMCOUNT"], $outBlock);
            $outBlock = str_replace("#COST", $item["COST"], $outBlock);
            $outBlock = str_replace("#MEAS", $item["MEAS"], $outBlock);
            $outBlock = str_replace("#LITERAL", $item["LITERAL"], $outBlock);
            $outBlock = str_replace("#IMAGEPATH", $this->GetAnnounceImage($item["ID_ANNOUNCE"], $item["ID_STATE"]), $outBlock);
            $outData .= $outBlock;
        }

        $stream = file_get_contents($this->TPL."group_view.html");
        $stream = str_replace("#GROUPTREE", $group->tree, $stream);
        $stream = str_replace("#GROUPID", $group_id, $stream);
        $stream = str_replace("#GROUPLIST", $outData, $stream);
        $stream = str_replace("#GROUPLINK", $group->link, $stream);

        return $this->RenderError($stream);
    }

    function PostCategoryCreate()
    {
        $parent_id = SafeInt(@$_REQUEST["group"]);
        $caption = SafeStr(@$_REQUEST["caption"]);

        if ($parent_id != 0) {
            $SQL = "select ID_GROUP, LEVEL from COMPANY_GROUP where ID_COMPANY=".$this->CompID
                ." and ID_GROUP=".$parent_id;
            $item = $this->DL->LFetchRecordRow($SQL);
            if (!$item) {
                RedirectError(self::E_INVALIDGROUP);
            }
        } else $item[1] = "";

        $SQL = "insert into COMPANY_GROUP (ID_COMPANY, ID_PARENT, CAPTION) values "
            ."(".$this->CompID.", ".$parent_id.", '".$caption."')";
        $this->DL->Execute($SQL);
        $group_id = $this->DL->PrimaryID();

        $SQL = "update COMPANY_GROUP set LEVEL='".$item[1].$group_id.".'"
            ." where ID_COMPANY=".$this->CompID." and ID_GROUP=".$group_id;
        $this->DL->Execute($SQL);

        Redirect("/cabcomp/cat&id=".$parent_id);
    }

    function TplCategoryCreate()
    {
        $group_id = SafeInt(@$_REQUEST["parent"]);

        $this->SetLinkPanel(self::LINK_ID_GROUP, "group");
        $group = $this->InlineTplCategoryPath($group_id, self::CATPATH_SIMPLE);

        $stream = file_get_contents($this->TPL."group_create.html");
        $stream = str_replace("#GROUPTREE", $group->tree, $stream);
        $stream = str_replace("#GROUPLINK", $group->link, $stream);
        $stream = str_replace("#GROUPID", $group_id, $stream);

        return $this->RenderError($stream);
    }

    function PostCategoryEdit()
    {
        $group_id = SafeInt(@$_REQUEST["group"]);
        $parent_id = SafeInt(@$_REQUEST["parent"]);
        $caption = SafeStr(@$_REQUEST["caption"]);

        if ($parent_id != 0) {
            $SQL = "select ID_GROUP, LEVEL from COMPANY_GROUP where ID_COMPANY=".$this->CompID
                ." and ID_GROUP=".$parent_id;
            $item = $this->DL->LFetchRecordRow($SQL);
            if (!$item) {
                RedirectError(self::E_INVALIDGROUP);
            }
        } else $item[1] = "";

        $SQL = "update COMPANY_GROUP set CAPTION='".$caption."', ID_PARENT=".$parent_id.", LEVEL='".$item[1].$group_id.".'"
            ." where ID_COMPANY=".$this->CompID." and ID_GROUP=".$group_id;
        $this->DL->Execute($SQL);

        Redirect("/cabcomp/cat&id=".$parent_id);
    }

    function TplCategoryEdit()
    {
        $group_id = SafeInt(@$_REQUEST["id"]);

        $this->SetLinkPanel(self::LINK_ID_GROUP, "group");
        $group = $this->InlineTplCategoryPath($group_id, self::CATPATH_PARENT);

        $stream = file_get_contents($this->TPL."group_edit.html");
        $stream = str_replace("#GROUPID", $group_id, $stream);
        $stream = str_replace("#GROUPTREE", $group->tree, $stream);
        $stream = str_replace("#GROUPLINK", $group->link, $stream);
        $stream = str_replace("#CAPTION", $group->item["CAPTION"], $stream);

        return $this->RenderError($stream);
    }

    function TplAnnounceEdit()
    {
        // Код редактируемого объявления
        $announce_id = SafeInt(@$_REQUEST["id"]);
        $this->SetLinkPanel(self::LINK_ID_GROUP, "group");

        // Запрос потверждающий права на управление
        $SQL = "select b.ID_ANNOUNCE, b.ID_CATEGORY, b.ID_ACTION, b.CAPTION, uncompress(b.TEXTDATA) as TEXTDATA,"
        ." b.ID_CURRENCY, b.COST, b.IMAGES, rc.LEVEL as CATLEVEL, cg.ID_GROUP, cg.LEVEL, b.ITEMCOUNT, b.ID_MEAS"
        ." from ANNOUNCE_DATA b left join COMPANY_GROUP cg on cg.ID_GROUP=b.ID_GROUP"
        ." and cg.ID_COMPANY=".$this->CompID.", REF_CATEGORY rc"
        ." where b.ID_STATE in (1,2,3,4) and rc.ID_CATEGORY=b.ID_CATEGORY and b.ID_ANNOUNCE=".$announce_id;
        // Определение прав на редактирование документа
        //todo
        $item = $this->DL->LFetchRecord($SQL) or Redirect(self::LINK_ERROR.self::E_NOTFOUND);
        $group = $this->InlineTplCategoryPath($item["ID_GROUP"], self::CATPATH_SIMPLE);




        // Вычисление доступных действий
        $SQL_Actn = "select ra.ACTION, ra.CAPTION from REF_ACTION ra, REF_CATEGORY rc"
        ." where rc.ID_CATEGORY=".$item["ID_CATEGORY"]." and rc.ACTION like concat('%', ra.ACTION, '%')"
        ." order by ra.ORDERBY asc";
        $actn = $this->BuildSelect($SQL_Actn, $item["ID_ACTION"]);
        // Вычисление единиц измерения
        $SQL_Meas = "select ID_MEAS, CAPTION from REF_MEAS where ID_STATE=1 order by CAPTION";
        $meas = $this->BuildSelect($SQL_Meas, $item["ID_MEAS"]);
        // Вычисление доступных единиц оплаты
        $list_curr = $this->BuildSelectCash(parent::CashCurrency(), $item["ID_CURRENCY"]);
        // Список начальных категорий
        $list_caty = parent::TplCategoryLoad($item["CATLEVEL"]);
        // Зашружаемые картинки
        $uid = $this->GetUploadID($announce_id);
        $json = $this->UploadFiles($announce_id, $uid);

        // Вывод шаблона редактирования документа
        $stream = file_get_contents($this->TPL."announce_edit.html");
        $stream = str_replace("#COST", $item["COST"], $stream);
        $stream = str_replace("#COUNT", $item["ITEMCOUNT"], $stream);
        $stream = str_replace("#TEXTDATA", ($item["TEXTDATA"]), $stream);
        $stream = str_replace("#CAPTION", ($item["CAPTION"]), $stream);
        $stream = str_replace("#ACTION", $actn, $stream);
        $stream = str_replace("#MEAS", $meas, $stream);
        $stream = str_replace("#EXTPARAM", $ajcat, $stream);
        $stream = str_replace("#GROUPTREE", $group->tree, $stream);
        $stream = str_replace("#GROUPLINK", $group->link, $stream);
        $stream = str_replace("#DIVISION", $list_caty, $stream);
        $stream = str_replace("#CURRENCY", $list_curr, $stream);
        $stream = str_replace("#ANNOUNCEID", $announce_id, $stream);
        $stream = str_replace("#JSONPHOTO", json_encode($json[0]), $stream);
        $stream = str_replace("#JSONTHUMB", json_encode($json[1]), $stream);
        $stream = str_replace("#THUMBSIZE", $this->DC["IMAGE_THUMBSIZE"], $stream);
        $stream = str_replace("#PHOTOSIZE", $this->DC["IMAGE_PHOTOSIZE"], $stream);
        $stream = str_replace("#UPLOADID", $uid, $stream);
        $stream = str_replace("#PHOTOCOUNT", $this->GetPhotoMaxCount($item["IMAGES"]), $stream);
        $stream = str_replace("#AJAXPICTPL", $this->BuildTPLajaxPicture(), $stream);

        return $this->RenderError($stream);
    }

    function PostAnnounceEdit()
    {
        // Принимаемые параметры, список дополняется
        $announce_id = SafeInt(@$_POST["id"]);
        $currency = SafeInt(@$_POST["currency"]);
        $action = SafeStr(@$_POST["action"]);
        $cost = SafeInt(@$_POST["cost"]);
        $caption = SafeStr(@$_POST["caption"]);
        $textdata = SafeStr(@$_POST["textdata"]);
        $category = SafeInt(@$_POST["category"]);
        $group = SafeInt(@$_POST["group"]);
        $meas = SafeInt(@$_POST["meas"]);
        $itemcount = SafeInt(@$_POST["itemcount"]);

        // Определение прав на обновление объявления
        $this->SafeUserID($SQLuser);
        // Договорная цена при неуказании стоимости
        if (($cost == 0) || ($currency == parent::CURRENCY_DEFAULT)) {
            $currency = parent::CURRENCY_DEFAULT;
            $cost = "NULL";
        }
        // Единица измерения уточнения при неуказании
        if (($itemcount == 0) || ($meas == parent::MEAS_DEFAULT)) {
            $meas = parent::MEAS_DEFAULT;
            $itemcount = "NULL";
        }

        // Запрос потверждающий права на управление
        $SQL = "select b.ID_ANNOUNCE, b.IMAGES, b.ID_CATEGORY, b.ID_CITY from ANNOUNCE_DATA b"
            ." where b.ID_STATE in (1,2,3,4) and b.ID_GROUP>-1 and b.ID_ANNOUNCE=".$announce_id." and ".$SQLuser;
        $item = $this->DL->LFetchRecord($SQL) or Redirect(self::LINK_ERROR.self::E_NOTFOUND);

        // Очистка аттрибутов и удаление изображений
        $this->ClearParam($announce_id);
        $this->ClearFiles(_ANNOUNCE.$announce_id);
        // Загрузка новых параметров и изображений
        $photoCount = $this->DownloadFiles($announce_id);
        $this->UploadParam($announce_id);

        // Обновление объявления
        $SQL = "update ANNOUNCE_DATA b set ID_ACTION='".$action."', CAPTION='".$caption."', TEXTDATA=compress('".$textdata."'),"
            ." COST=".$cost.", ID_CURRENCY=".$currency.", IMAGES=".$photoCount.", ID_GROUP=".$group.","
            ." TEXTINDEX='".MorphyText($caption." ".$textdata)."', CONTACT=null, ID_STATE=".parent::STATE_ACTIVE.","
            ." ID_MEAS=".$meas.", ITEMCOUNT=".$itemcount.","
            ." ID_CATEGORY=".$category." where ID_STATE in (1,2,3,4) and ID_ANNOUNCE=".$announce_id." and ".$SQLuser;
        $this->DL->Execute($SQL);

        if ($category != $item["ID_CATEGORY"]) {
            // Инкремент количества объявлений старой категории
            $this->ToggleAnnounceCount(parent::AC_DECREMENT, $item["ID_CATEGORY"], $item["ID_CITY"]);
            // Декремент количества объявлений новой категории
            $this->ToggleAnnounceCount(parent::AC_INCREMENT, $category, $item["ID_CITY"]);
        }

        Redirect("/cabcomp/cat&id=".$group);
    }

    function TplAnnounceCreate()
    {
        $parent_id = SafeStr(@$_GET["parent"]);
        $this->SetLinkPanel(self::LINK_ID_GROUP, "group");

        $group = $this->InlineTplCategoryPath($parent_id, self::CATPATH_SIMPLE);
        $list_curr = $this->BuildSelectCash(parent::CashCurrency(), 1);
        $list_caty = $this->BuildSelectCash(parent::CashCategory(), -1, false, 0);

        // Вычисление единиц измерения
        $SQL_Meas = "select ID_MEAS, CAPTION from REF_MEAS where ID_STATE=1 order by CAPTION";
        $list_meas = $this->BuildSelect($SQL_Meas);

        // Вывод шаблона добавления документа
        $stream = file_get_contents($this->TPL."announce_create.html");
        $stream = str_replace("#DIVISION", $list_caty, $stream);
        $stream = str_replace("#CURRENCY", $list_curr, $stream);
        $stream = str_replace("#MEAS", $list_meas, $stream);

        $stream = str_replace("#GROUPTREE", $group->tree, $stream);
        $stream = str_replace("#GROUPLINK", $group->link, $stream);

        $stream = str_replace("#JSONPHOTO", json_encode(array()), $stream);
        $stream = str_replace("#JSONTHUMB", json_encode(array()), $stream);
        $stream = str_replace("#UPLOADID", $this->GetUploadID(), $stream);
        $stream = str_replace("#THUMBSIZE", $this->DC["IMAGE_THUMBSIZE"], $stream);
        $stream = str_replace("#PHOTOSIZE", $this->DC["IMAGE_PHOTOSIZE"], $stream);
        $stream = str_replace("#PHOTOCOUNT", $this->GetPhotoMaxCount(0), $stream);
        $stream = str_replace("#AJAXPICTPL", $this->BuildTPLajaxPicture(), $stream);

        return $this->RenderError($stream);
    }

    function PostAnnounceCreate()
    {
        // Принимаемые параметры, список дополняется
        $currency = SafeInt(@$_POST["currency"]);
        $action = SafeStr(@$_POST["action"]);
        $cost = SafeInt(@$_POST["cost"]);
        $caption = SafeStr(@$_POST["caption"]);
        $textdata = SafeStr(@$_POST["textdata"]);
        $category = SafeInt(@$_POST["category"]);
        $group = SafeInt(@$_POST["group"]);
        $meas = SafeInt(@$_POST["meas"]);
        $itemcount = SafeInt(@$_POST["itemcount"]);

        // Договорная цена при неуказании стоимости
        if (($cost == 0) || ($currency == parent::CURRENCY_DEFAULT)) {
            $currency = parent::CURRENCY_DEFAULT;
            $cost = "NULL";
        }
        // Единица измерения уточнения при неуказании
        if (($itemcount == 0) || ($meas == parent::MEAS_DEFAULT)) {
            $meas = parent::MEAS_DEFAULT;
            $itemcount = "NULL";
        }

        // Выборка конечной категории и пути следования
        $SQL = "select count(*) as VALID from REF_CATEGORY rc left outer join REF_CATEGORY rc2"
            ." on rc2.LEVEL like concat(rc.LEVEL, '%') where rc.ID_CATEGORY=".$category;
        $valid = $this->DL->LFetchRecord($SQL);
        // Проверка на актуальность категории
        if ($valid["VALID"] != 1) {
           RedirectError(self::E_NOTPARAM);
        }

        $SQL = "select ID_CITY, ID_USER from COMPANY_DATA where ID_COMPANY=".$this->CompID;
        $cmp = $this->DL->LFetchRecord($SQL);

        // Обновление объявления
        $SQL = "insert into ANNOUNCE_DATA (ID_ACTION, CAPTION, TEXTDATA, COST, ID_CURRENCY,"
            ." ID_GROUP, TEXTINDEX, ID_CATEGORY, ID_CITY, ID_USER, ID_MEAS, ITEMCOUNT) values "
            ."('".$action."', '".$caption."', compress('".$textdata."'), ".$cost
            .", ".$currency.", ".$group.", '".MorphyText($caption." ".$textdata)
            ."', ".$category.", ".$cmp["ID_CITY"].", ".$cmp["ID_USER"].", ".$meas
            .", ".$itemcount.")";
        $this->DL->Execute($SQL);
        $announce_id = $this->DL->PrimaryID();

        // Инкремент количества объявлений категории
        $this->ToggleAnnounceCount(parent::AC_INCREMENT, $category, $cmp["ID_CITY"]);

        // Загрузка файлов
        $photoCount = self::DownloadFiles($announce_id);
        // Загрузка параметров
        self::UploadParam($announce_id);
        // Обновление информации о количестве изображений в базе
        $SQL = "update ANNOUNCE_DATA set IMAGES=".$photoCount.", POSITION=".$this->NextCounter("CNT_POSITION_ANN", $announce_id)
            ." where ID_ANNOUNCE=".$announce_id;
        $this->DL->Execute($SQL);

        Redirect("/cabcomp/cat&id=".$group);
    }

    public function TplAnnounceDrop()
    {
        $this->SetLinkPanel(self::LINK_ID_GROUP, "group");

        $announce_id = SafeInt($_REQUEST["id"]);
        $this->SafeUserID($SQLuser);

        $SQL = "select ID_ANNOUNCE, CAPTION from ANNOUNCE_DATA b"
            ." where ID_GROUP>-1 and ID_ANNOUNCE=".$announce_id." and ".$SQLuser;
        $item = $this->DL->LFetchRecord($SQL) or Redirect(self::LINK_ERROR.self::E_NOTFOUND);

        $stream = file_get_contents($this->TPL."announce_drop.html");
        $stream = str_replace("#ANNOUNCEUID", GetStretchNumber($announce_id), $stream);
        $stream = str_replace("#ANNOUNCEID", $announce_id, $stream);
        $stream = str_replace("#CAPTION", $item["CAPTION"], $stream);
        return $stream;
    }

    public function PostAnnounceDrop()
    {
        // Принимаемые параметры, список дополняется
        $announce_id = SafeInt(@$_POST["id"]);

            $this->SafeUserID($SQLuser);
            $SQLuser = " and ".$SQLuser;

        // Запрос потверждающий права на управление
        $SQL = "select b.ID_ANNOUNCE, b.ID_CITY, b.ID_USER, b.ID_GUEST, b.ID_CATEGORY"
            ." from ANNOUNCE_DATA b, REF_CATEGORY rc"
            ." where b.ID_STATE in (1,2,3,4) and rc.ID_STATE=1 and rc.ID_CATEGORY=b.ID_CATEGORY"
            ." and b.ID_GROUP>-1 and b.ID_ANNOUNCE=".$announce_id.$SQLuser;
          $item = $this->DL->LFetchRecord($SQL) or Redirect(self::LINK_ERROR.self::E_NOTFOUND);

        $SQL = "update ANNOUNCE_DATA b set ID_STATE=".parent::STATE_DELETED." where ID_ANNOUNCE=".$announce_id.$SQLuser;
        $this->DL->Execute($SQL);

        // Декремент количества объявлений категории
        $this->ToggleAnnounceCount(parent::AC_DECREMENT, $item["ID_CATEGORY"], $item["ID_CITY"]);
        // Удаление ссылок на объявление
        $this->ToggleAnnounceUser(parent::AC_DELETE, $item["ID_USER"], $item["ID_GUEST"], $announce_id);
        // Удаление параметров
        $this->ClearParam($announce_id);
        // Удаление фотографий
        $this->ClearFiles(_ANNOUNCE.$announce_id);

        Redirect(self::LINK_ERROR.self::E_DELETED);
    }

    public function ExecuteTicket()
    {
        $mode = @$_REQUEST["m"];
        $this->SetLinkPanel(self::LINK_ID_TICKET, "ticket", $mode);

        include(_LIBRARY."lib_ticket.php");
        $Ticket = new TTicket();
        return $Ticket->ExecuteCompany($mode);
    }

    private function WorkTime_Render()
    {
        /* todo*/
        global $days;

        $item = file_get_contents($this->TPL."worktime/item.html");
        $outData = "";
        $worktime = @json_decode($this->Company["WORKTIME"], true);

        for ($index = 0; $index < count($days); $index++)
        {
            $out = str_replace("#DAYID", $index, $item);
            $out = str_replace("#DAY", $days[$index], $out);

            if (!isset($worktime[$index])) {
                $timeWork = "Выходной";
                $timeBreak = "";
            } else {
                parent::WorkTime_WorkToText($worktime[$index], $timeWork, $timeBreak);
            }

            $out = str_replace("#TIMEWORK", $timeWork, $out);
            $out = str_replace("#TIMEBREAK", $timeBreak, $out);
            $outData .= $out;
        }
        $stream = file_get_contents($this->TPL."worktime/default.html");
        $stream = str_replace("#CONTENT", $outData, $stream);

        return $stream;
    }

    private function WorkTime_TimeGet()
    {
        $dayNumber = SafeInt(@$_REQUEST["day"]);

        $item = @json_decode($this->Company["WORKTIME"], true);

        $stream = file_get_contents($this->TPL."worktime/timeset.html");
        $stream = str_replace("#DAYNUMBER", $dayNumber, $stream);
        $stream = str_replace("#WORKSTARTHOUR", SetSelectOption(0, 23, 1, @$item[$dayNumber][0]), $stream);
        $stream = str_replace("#WORKSTARTMIN", SetSelectOption(0, 45, 15, @$item[$dayNumber][1]), $stream);
        $stream = str_replace("#WORKENDHOUR", SetSelectOption(0, 23, 1, @$item[$dayNumber][2]), $stream);
        $stream = str_replace("#WORKENDMIN", SetSelectOption(0, 45, 15, @$item[$dayNumber][3]), $stream);
        $stream = str_replace("#BREAKSTARTHOUR", SetSelectOption(0, 23, 1, @$item[$dayNumber][4]), $stream);
        $stream = str_replace("#BREAKSTARTMIN", SetSelectOption(0, 45, 15, @$item[$dayNumber][5]), $stream);
        $stream = str_replace("#BREAKENDHOUR", SetSelectOption(0, 23, 1, @$item[$dayNumber][6]), $stream);
        $stream = str_replace("#BREAKENDMIN", SetSelectOption(0, 45, 15, @$item[$dayNumber][7]), $stream);
        $stream = str_replace("#CHECKED", @$item[$dayNumber]["break"] ? "checked" : "", $stream);
        $stream = str_replace("#DISPLAY", @$item[$dayNumber]["break"] ? "block" : "none", $stream);


        SendLn($stream);
    }

    private function WorkTime_TimeSet()
    {
        $worktime = @json_decode($this->Company["WORKTIME"], true);

        $dayNumber = SafeInt(@$_REQUEST["day"]);
        $dayBreak = SafeBool("break");
        $dayTime = array();

        for ($index = 0; $index < 8; $index++) {
            $dayTime[$index] = SafeInt(@$_REQUEST["tset"][$index]);
        }
        $dayTime["break"] = $dayBreak;
        $worktime[$dayNumber] = $dayTime;

        $SQL = "update COMPANY_DATA set WORKTIME=compress('".json_encode($worktime)."')"
            ." where ID_USER=".$_SESSION["USER_ID"]." and ID_COMPANY=".$this->CompID;
        $this->DL->Execute($SQL);

        self::WorkTime_WorkToText($worktime[$dayNumber], $timeWork, $timeBreak);
        SendLn($timeWork."<br/>".$timeBreak);
    }

    private function WorkTime_TimeClear()
    {
        $worktime = @json_decode($this->Company["WORKTIME"], true);

        $dayNumber = SafeInt(@$_REQUEST["day"]);
        $dayTime = array();
        $dayTime["break"] = false;
        $worktime[$dayNumber] = $dayTime;

        $SQL = "update COMPANY_DATA set WORKTIME=compress('".json_encode($worktime)."')"
            ." where ID_USER=".$_SESSION["USER_ID"]." and ID_COMPANY=".$this->CompID;
        $this->DL->Execute($SQL);

        self::WorkTime_WorkToText($worktime[$dayNumber], $timeWork, $timeBreak);
        SendLn($timeWork."<br/>".$timeBreak);
    }

    private function WorkTime_TimeCopy()
    {
        $worktime = @json_decode($this->Company["WORKTIME"], true);

        $dayNumber = SafeInt(@$_REQUEST["day"]);
        $dayTime = $worktime[$dayNumber];

        for ($index = 0; $index < 7; $index++) {
            $worktime[$index] = $dayTime;
        }

        $SQL = "update COMPANY_DATA set WORKTIME=compress('".json_encode($worktime)."')"
            ." where ID_USER=".$_SESSION["USER_ID"]." and ID_COMPANY=".$this->CompID;
        SendLn($this->DL->Execute($SQL));
    }

    public function WorkTime()
    {
        $this->SetLinkPanel(self::LINK_ID_SETTINGS, "comp");

        if (empty($this->ACTION)) {
            return self::WorkTime_Render();
        } else
        if ($this->ACTION == "timeget") {
            return self::WorkTime_TimeGet();
        } else
        if ($this->ACTION == "timeset") {
            return self::WorkTime_TimeSet();
        } else
        if ($this->ACTION == "timeclear") {
            return self::WorkTime_TimeClear();
        } else
        if ($this->ACTION == "timecopy") {
            return self::WorkTime_TimeCopy();
        }
    }

}
?>