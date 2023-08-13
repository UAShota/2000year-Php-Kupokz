<?
class TAdminCompany extends TInterface
{
    /**
     * Переменные доступа к БД, ссылка на массив настроек
     */
    private $ErrID;
    public $MODE;
    public $DATA;
    public $HEAD;

    const E_INFO_CREATED = 1; // инфо компания создана
    const E_INFO_SAVED = 2; // инфо компания обновлена

    const LINK_INFO_ERROR = "/admin/company=info&e=";

    public function __construct()
    {
        // Управление только администраторы
        if ($_SESSION["USER_ROLE"] > parent::ROLE_MODER) {
            Redirect("/");
        }
        parent::__construct();

        $this->MODE = SafeStr(@$_REQUEST["company"]);
        $this->ErrID = SafeInt(@$_GET["e"]);
        $this->HEAD = "Компании &raquo; ";

        if ($this->MODE == "info") {
            $this->DATA = $this->RenderInfoList();
            $this->HEAD .= "Информационные";
        } else

        if ($this->MODE == "infocreate") {
            $this->DATA = $this->RenderInfoCreate();
            $this->HEAD .= "Добавление компании";
        } else

        if ($this->MODE == "infopostcreate") {
            $this->InfoCreate();
        } else

        if ($this->MODE == "infoedit") {
            $this->DATA = $this->RenderInfoEdit();
            $this->HEAD .= "Редактирование компании";
        } else

        if ($this->MODE == "infopostedit") {
            $this->InfoUpdate();
        } else
        {
            $this->DATA = "<h4>select option to action</h4>";
            $this->HEAD .= "Общий обзор";
        }

        $stream = file_get_contents(_TEMPLATE."admin/company/default.html");
        $this->DATA = str_replace("#BLOCKDATA", $this->DATA, $stream);

        return $stream;
    }

    public function RenderError($content = "")
    {
        $error_id = $this->ErrID;
        $errclass = false;
        if ($error_id == 0) return $content;
        // Код объявления
        $announce_id = SafeInt(@$_GET["uid"]);

        if ($error_id == self::E_INFO_CREATED) {
            $error = "Информационная компания добавлена успешно";
            $errclass = parent::E__SUCCS;
        } else
        if ($error_id == self::E_INFO_SAVED) {
            $error = "Информационная компания обновлена";
            $errclass = parent::E__SUCCS;
        } else {
            $error = "^_^";
            $errclass = parent::E__ERROR;
        }

        $stream = file_get_contents(_TEMPLATE."default/default_error.html");
        $stream = str_replace("#STYLE", $errclass, $stream);
        $stream = str_replace("#TEXT", $error, $stream);
        $stream = str_replace("#CONTENT", $content, $stream);

        return $stream;
    }

    public function RenderInfoList()
    {
        // Страница просмотра
        $page = SafeInt(@$_GET["page"]);
        // Количество объявлений на страницу, подготовка запроса
        $limit = $this->SelectorPrepare($page, 50);

        $SQL = "select SQL_CALC_FOUND_ROWS ID_COMPANY, CAPTION, DATE_LIFE from COMPANY_DATA"
            ." where ID_STATE=1 and ID_TYPE=".parent::COMPANY_TYPE_INFO
            ." order by DATE_LIFE desc".$limit;
        $dump = $this->DL->LFetch($SQL);

        $urlPage = "/admin/company=info";
        $pageselector = $this->SelectorPage($page, 50, $this->DL->LMaxRows(), $urlPage, false);
        $outData = "";
        foreach ($dump as $item) {
            $outData .= "<tr><td>".$item["DATE_LIFE"]."</td>"
                ."<td><a href='/company/".$item["ID_COMPANY"]."'>".($item["CAPTION"])."</a></td>"
                ."<td><a href='/admin/company=infoedit&id=".$item["ID_COMPANY"]."'>Править</a></td></tr>";
        }

        $stream = file_get_contents(_TEMPLATE."admin/company/info.html");
        $stream = str_replace("#BLOCKDATA", $outData, $stream);
        $stream = str_replace("#PAGESELECTOR", $pageselector, $stream);

        return $this->RenderError($stream);
    }

    private function RenderInfoCreate()
    {
        $SQL = "select ID_CATEGORY, CAPTION from COMPANY_CATEGORY where ID_PARENT=1 order by ORDERBY, CAPTION";
        $list_caty = $this->BuildSelect($SQL);
        $list_city = $this->BuildSelectCash(parent::CashCity(), -1, false, 0);

        $stream = file_get_contents(_TEMPLATE."admin/company/info_create.html");
        $stream = str_replace("#ACTION", $list_city, $stream);
        $stream = str_replace("#CITY", $list_city, $stream);
        $stream = str_replace("#CATEGORY", $list_caty, $stream);
        $stream = str_replace("#CONTACT", $this->ContactRender(null, true), $stream);

        return $this->RenderError($stream);
    }

    private function InfoCreate()
    {
        $company_id = SafeInt(@$_POST["id"]);
        $caption = SafeStr(@$_POST["caption"]);
        $textdata = SafeStr(@$_POST["textdata"]);
        $category = SafeStr(@$_POST["category"]);
        $textview = SafeStr(@$_POST["textview"]);
        $textindex = SafeStr(@$_POST["textindex"]);
        $city_id = SafeInt(@$_POST["id_city"]);
        $location_street = SafeStr(@$_POST["location_street"]);

        if (!TextRange($caption, 3, 60)) {
            RedirectError(self::E_INVALIDTEXTLEN);
        }

        // Создание компании
        $SQL = "insert into COMPANY_DATA (CAPTION, ID_USER, ID_CITY, LOCATION_STREET, ID_CATEGORY,"
            ." ID_TYPE, ID_STATE, TEXTVIEW, TEXTINDEX, REALINDEX, TEXTDATA, CONTACT)"
            ." values('".$caption."', ".$_SESSION["USER_ID"].", ".$city_id
            .", '".$location_street."', ".$category.", ".parent::COMPANY_TYPE_INFO.", 1, '".$textview."', '".$textindex."'"
            .", '".MorphyText($caption." ".$textview." ".$textindex)."', compress('".$textdata."'), compress('".$this->ContactUpload()."'));";
        $this->DL->Execute($SQL);
        $company_id = $this->DL->PrimaryID();

        // Обновление количество компаний в категории
        $SQL = "update COUNT_COMPANY set ITEMCOUNT=ITEMCOUNT+1 where"
            ." (ID_CATEGORY = (select ID_PARENT from COMPANY_CATEGORY where ID_CATEGORY=".$category.")"
            ." or ID_CATEGORY=".$category.") and ID_CITY in (88, ".$city_id.")";
        $this->DL->Execute($SQL);

        Redirect(self::LINK_INFO_ERROR.self::E_INFO_CREATED);
    }

    private function RenderInfoEdit()
    {
        $company_id = SafeInt(@$_GET["id"]);

        $SQL = "select cd.*, uncompress(cd.TEXTDATA) as TEXTDATA, uncompress(cd.CONTACT) as CONTACT, rc.ID_PARENT"
            ." from COMPANY_DATA cd, COMPANY_CATEGORY rc"
            ." where rc.ID_CATEGORY=cd.ID_CATEGORY and cd.ID_TYPE=".parent::COMPANY_TYPE_INFO
            ." and cd.ID_COMPANY=".$company_id;
        $item = $this->DL->LFetchRecord($SQL);

        $list_city = $this->BuildSelectCash(parent::CashCity(), $item["ID_CITY"], false, 0);
        $list_caty = "";
        $cat_tree = explode(".", "1.".$item["ID_PARENT"].".".$item["ID_CATEGORY"].".");
        for ($index = 0; $index < count($cat_tree) - 1; $index++)
        {
            $SQL = "select ID_CATEGORY, CAPTION from COMPANY_CATEGORY where ID_STATE=1 and ID_PARENT=".$cat_tree[$index]
                ." order by ORDERBY, CAPTION";
            $cat_item = $this->BuildSelect($SQL, $cat_tree[$index + 1]);
            if ($cat_item != false) {
                $list_caty .= '<select id="category" name="category" onchange="return annkit.listcatcom(this);">'.$cat_item."</select>";
            }
        }

        $stream = file_get_contents(_TEMPLATE."admin/company/info_update.html");
        $stream = str_replace("#CITY", $list_city, $stream);
        $stream = str_replace("#CATEGORY", $list_caty, $stream);
        $stream = str_replace("#IDCOMPANY", $item["ID_COMPANY"], $stream);
        $stream = str_replace("#LOCATION_STREET", ($item["LOCATION_STREET"]), $stream);
        $stream = str_replace("#CAPTION", ($item["CAPTION"]), $stream);
        $stream = str_replace("#TEXTVIEW", ($item["TEXTVIEW"]), $stream);
        $stream = str_replace("#TEXTINDEX", ($item["TEXTINDEX"]), $stream);
        $stream = str_replace("#TEXTDATA", ($item["TEXTDATA"]), $stream);
        $stream = str_replace("#CONTACT", $this->ContactRender($item["CONTACT"], true), $stream);

        return $this->RenderError($stream);
    }

    private function InfoUpdate()
    {
        $company_id = SafeInt(@$_POST["id"]);
        $caption = SafeStr(@$_POST["caption"]);
        $textdata = SafeStr(@$_POST["textdata"]);
        $category = SafeStr(@$_POST["category"]);
        $textview = SafeStr(@$_POST["textview"]);
        $textindex = SafeStr(@$_POST["textindex"]);
        $city_id = SafeInt(@$_POST["id_city"]);
        $location_street = SafeStr(@$_POST["location_street"]);

        if (!TextRange($caption, 3, 60)) {
            RedirectError(self::E_INVALIDTEXTLEN);
        }

        $SQL = "select ID_CATEGORY from COMPANY_DATA where ID_TYPE=".parent::COMPANY_TYPE_INFO." and ID_COMPANY=".$company_id;
        $item = $this->DL->LFetchRecordRow($SQL);

        // Создание компании
        $SQL = "update COMPANY_DATA set CAPTION='".$caption."', ID_CITY=".$city_id
            .", LOCATION_STREET='".$location_street."', ID_CATEGORY=".$category.", TEXTVIEW='".$textview."', TEXTINDEX='".$textindex."'"
            .", REALINDEX='".MorphyText($caption." ".$textview." ".$textindex)."', TEXTDATA=compress('".$textdata."'), CONTACT=compress('".$this->ContactUpload()."')"
            ." where ID_TYPE=".parent::COMPANY_TYPE_INFO." and ID_COMPANY=".$company_id;
        $this->DL->Execute($SQL);

        // Обновление количество компаний в категории
        $SQL = "update COUNT_COMPANY set ITEMCOUNT=ITEMCOUNT-1 where ITEMCOUNT > 0 and "
            ." (ID_CATEGORY = (select ID_PARENT from COMPANY_CATEGORY where ID_CATEGORY=".$item[0].")"
            ." or ID_CATEGORY=".$item[0].") and ID_CITY in (88, ".$city_id.")";
        $this->DL->Execute($SQL);

        // Обновление количество компаний в категории
        $SQL = "update COUNT_COMPANY set ITEMCOUNT=ITEMCOUNT+1 where"
            ." (ID_CATEGORY = (select ID_PARENT from COMPANY_CATEGORY where ID_CATEGORY=".$category.")"
            ." or ID_CATEGORY=".$category.") and ID_CITY in (88, ".$city_id.")";
        $this->DL->Execute($SQL);

        Redirect(self::LINK_INFO_ERROR.self::E_INFO_SAVED);
    }
}
?>