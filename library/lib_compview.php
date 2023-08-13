<?
class TCompanyView extends TInterface
{
    /**
     * Переменные доступа к БД, код активной категории, ссылка на массив настроек
     */
    private $TPL;
    public $MODE;
    public $TxtID;
    public $CatID;
    public $KEYWORDS;
    public $TITLE;


    const E_COMPINFO_NOTEXIST = 1;


    /**
     * TKupoCompany::__construct()
     *
     * @param mixed $DataClass
     * @return класс связи с БД и код запрошенного действия
     */
    public function __construct()
    {
        parent::__construct();
        $this->MODE = SafeStr(@$_REQUEST["com"]);
        $this->TPL = _TEMPLATE."compview/";
        $this->TxtID = SafeStr(@$_REQUEST["fl_text"]);

        if (isset($_REQUEST["fl_cat"])) {
            $this->CatID = SafeInt(@$_REQUEST["fl_cat"]);
        } else
        if (is_numeric($this->MODE)) {
            $this->CatID = SafeInt($this->MODE);
        } else {
            $this->CatID = -1;
        }
        if ($this->CatID == 0) $this->CatID = 1;

    }

    private function RenderError($content)
    {
        if ($this->FL_ERR == parent::E__NOERROR) return $content;

        if ($this->FL_ERR == self::E_COMPINFO_NOTEXIST) {
            return parent::RenderErrorTemplate($content, parent::E__ERRORID, "Компания не найдена");
        }
    }

    public function RenderOverview()
    {
        $SQL = "select count(*) from COMPANY_CATEGORY rc where ID_PARENT=1";
        $dump = $this->DL->LFetchRecordRow($SQL);
        $catDiv = ceil($dump[0] / 3);

        $SQL = "select rc.ID_CATEGORY, rc.ID_PARENT, rc.CAPTION, cc.ITEMCOUNT"
            ." from COMPANY_CATEGORY rc, COMPANY_CATEGORY rc2, COUNT_COMPANY cc"
            ." where rc.ID_STATE=1 and rc2.ID_STATE=1 and rc2.ID_PARENT in (0,1)"
            ." and rc.ID_PARENT=rc2.ID_CATEGORY and cc.ID_CATEGORY=rc.ID_CATEGORY"
            ." and cc.ID_CITY=".$_SESSION["CITY_ID"]
            ." order by rc.ID_PARENT, rc.ORDERBY, rc.CAPTION";
        $dump = $this->DL->LFetchRows($SQL);

        $catVector[0] = $catVector[1] = $catVector[2] = "";
        $catCount = 0;
        $rootCount = 0;
        $tplBlock = file_get_contents(_TEMPLATE."default/block_category_main.html");
        $tplBlock = str_replace("#CATLINK", "com", $tplBlock);
        $divCount = count($dump);

        for ($catRoot=0; $catRoot<$divCount; $catRoot++)
        {
            if ($dump[$catRoot][1] == 1) {
                $catVector[$rootCount] .= str_replace("#GROUPID", $dump[$catRoot][0], $tplBlock);
                $catVector[$rootCount] =  str_replace("#CAPTION", $dump[$catRoot][2], $catVector[$rootCount]);
                $catVector[$rootCount] =  str_replace("#COUNT", $dump[$catRoot][3], $catVector[$rootCount]);
                $catVector[$rootCount] =  str_replace("#THEMEPIC", _THEME."yellowpic", $catVector[$rootCount]);
                $catCount++;

                $catSubVisible = $catSubHidden = "";
                $childCount = 0;
                for ($catChild = 0; $catChild < $divCount - 1; $catChild++)
                {
                    if ($dump[$catChild][1] == $dump[$catRoot][0])
                    {
                        $childCount++;
                        $item = "<a class='item' href='/com/".$dump[$catChild][0]."'>".$dump[$catChild][2]."</a>";
                        $item .= "<span class='countsub'>&nbsp;(".$dump[$catChild][3].")</span>, ";
                        if ($childCount < 6)
                            $catSubVisible .= $item;
                        else
                            $catSubHidden .= $item;
                    }
                }
                $catVector[$rootCount] = str_replace("#ITEMSHOWED", $catSubVisible, $catVector[$rootCount]);
                $catVector[$rootCount] = str_replace("#ITEMHIDDEN", $catSubHidden, $catVector[$rootCount]);

                if ($catCount >= $catDiv) {
                    $rootCount++;
                    $catCount = 0;
                }
            }
        }

        // Заполнение шаблона
        $stream = file_get_contents(_TEMPLATE."default/block_category_item.html");
        $stream = str_replace("#TRADENEWS", parent::GetNewsSimple(), $stream);
        $stream = str_replace("#TRADEITEM", "компанию", $stream);
        $stream = str_replace("#TRADELINK", "/cabcomp/", $stream);
        $stream = str_replace("#TRADETYPE", "компаний", $stream);
        $stream = str_replace("#CATEGORY1", $catVector[0], $stream);
        $stream = str_replace("#CATEGORY2", $catVector[1], $stream);
        $stream = str_replace("#CATEGORY3", $catVector[2], $stream);

        return self::RenderError($stream);
    }

    public function RenderCategory()
    {
        $catLink = "";
        $catGroup = "";
        $layerCount = 0;

        $SQL = "select CAPTION, TINDEX, ID_PARENT from COMPANY_CATEGORY where ID_STATE=1"
            ." and ID_CATEGORY=".$this->CatID;
        $levelStr = $this->DL->LFetchRecordRow($SQL);

        $this->KEYWORDS = $levelStr[1];
        $this->TITLE = $levelStr[0];

        $SQL = "select rc.ID_CATEGORY, rc.CAPTION, ac.ITEMCOUNT from COMPANY_CATEGORY rc, COUNT_COMPANY ac "
            ." where rc.ID_STATE=1 and rc.ID_PARENT=".$this->CatID
            ." and ac.ID_CATEGORY=rc.ID_CATEGORY and ac.ID_CITY=".$_SESSION["CITY_ID"]
            ." order by rc.ORDERBY, rc.CAPTION";
        $dump = $this->DL->LFetch($SQL);

        $catCount = count($dump);
        $catDiv = ceil($catCount / 4);

        foreach ($dump as $item) {
            if ($layerCount == 0) {
                $catGroup .= "<div class='catlist-block'><ul>";
            }
            $layerCount++;
            $catGroup .= "<li ";

            if ($item["ID_CATEGORY"] == $this->CatID) {
                $catGroup .= "class='catlist-hover' style='clear: both' ";
            }
            $catGroup .= "><a href='/com/".$item["ID_CATEGORY"]."'>".$item["CAPTION"]."<div>(".$item["ITEMCOUNT"].")</div></a>";

            if ($layerCount == $catDiv) {
                $catGroup .= "</ul></div>";
                $layerCount = 0;
            }
        }
        if ($layerCount > 0) {
            $catGroup .= "</ul></div>";
        }

        $announce = $this->RenderCompanyList($this->CatID);
        // Заполнение шаблона вывода верхней панели
        $stream = file_get_contents($this->TPL."company_list.html");
        $stream = str_replace("#BLOCKDATA", $announce[0], $stream);
        $stream = str_replace("#PAGESELECTOR", $announce[1], $stream);
        $stream = str_replace("#ITEMCOUNT", $announce[2], $stream);
        $stream = str_replace("#CATGROUP", $catGroup, $stream);
        $stream = str_replace("#ITEMPERPAGE", $this->BuildSelectPerPage(), $stream);
        $stream = str_replace("#CATLINK", $this->GetCompanyPath($this->CatID, "/com/"), $stream);

        return $stream;
    }

    public function RenderCompanyList()
    {
        // Страница просмотра
        $page = SafeInt(@$_GET["page"]);
        // Определение с типом поиска
        $fl_type = SafeInt(@$_GET["fl_type"]);
        // Количество объявлений на страницу
        $this->SetItemPerPage();
        // Количество объявлений на страницу, подготовка запроса
        $limit = $this->SelectorPrepare($page, $_SESSION["USER_PERPAGE"]);
        // Ссылка по умолчанию
        $urlPage = "/com/".$this->CatID;

        // Выборка компаний в категории
        $SQL = " cd.ID_COMPANY, cd.ID_TYPE as COMTYPE, cd.CAPTION as COMCAPTION, cd.TEXTVIEW, cd.RATING,"
            ." rc.CAPTION as CATCAPTION, rc.ID_CATEGORY, cc.CAPTION as CITY, cd.LOCATION_STREET, cd.DOMAIN_ACTIVE"
            ." from COMPANY_DATA cd, COMPANY_CATEGORY rc, REF_CITY cc"
            ." where rc.ID_CATEGORY=cd.ID_CATEGORY"
            ." and cc.ID_CITY=cd.ID_CITY and cd.ID_STATE in (1,4)";
        /*заебали!!!!!!!*/
        /*if ($fl_type == parent::FILTER_TYPED_TRADE) {
            $SQL .= " and cd.ID_TYPE<>".parent::COMPANY_TYPE_INFO;
            $urlPage .= "&fl_type=".$fl_type;
        }
        if ($fl_type == parent::FILTER_TYPED_INFO) {
            $SQL .= " and cd.ID_TYPE=".parent::COMPANY_TYPE_INFO;
            $urlPage .= "&fl_type=".$fl_type;
        }*/
        // Если указан город, кроме "все города = 88"
        if ($_SESSION["CITY_ID"] != 88) {
            $SQL .= " and cd.ID_CITY=".$_SESSION["CITY_ID"];
        }
        // Поиск по всем категориям
        if (($this->CatID <= 1) && ($this->TxtID == "")) {
            $SQL .= " and (cd.DATE_LIFE > now() - interval 10 day)";
        };
        // Поиск по указанной категории
        if ($this->CatID > 1) {
            $SQL .= " and (rc.ID_PARENT=".$this->CatID." or rc.ID_CATEGORY=".$this->CatID.")";
        }
        // Фильтр по заданному тексту
        if ($this->TxtID != "") {
            $MorphyText = "match(cd.REALINDEX) against('".MorphyFullText($this->TxtID)."' in boolean mode)";
            $SQL = $MorphyText." as REL,".$SQL." and ".$MorphyText;
            $order = " order by cd.ID_TYPE, REL desc";
            $urlPage .= "&fl_text=".$this->TxtID;
        } else {
            $order = " order by cd.ID_TYPE, cd.DATE_LIFE desc";
        }
        // Итоговый запрос
        $SQL = "select SQL_CALC_FOUND_ROWS ".$SQL.$order.$limit;

        $dump = $this->DL->LFetch($SQL);
        $pagecount = $this->DL->LMaxRows();
        $pageselector = $this->SelectorPage($page, $_SESSION["USER_PERPAGE"], $pagecount, $urlPage);

        $streamtrade = file_get_contents($this->TPL."company_block_trade.html");
        $streaminfo = file_get_contents($this->TPL."company_block_info.html");

        $outData = "";
        foreach ($dump as $item) {
            if ($item["COMTYPE"] == 1) {
                $data = $streamtrade;
            } else {
                $data = $streaminfo;
            }
            $item = $this->SafeUserCompany($item, false);

            $data = str_replace("#COMPANYID", $item["ID_COMPANY"], $data);
            $data = str_replace("#CATID", $item["ID_CATEGORY"], $data);
            $data = str_replace("#CAPTION", $item["COMCAPTION"], $data);
            $data = str_replace("#USERLINK", $item["USERLINK"], $data);
            $data = str_replace("#CITY", $item["CITY"], $data);
            $data = str_replace("#LOCATION_STREET", $item["LOCATION_STREET"], $data);
            $data = str_replace("#CATEGORY", $item["CATCAPTION"], $data);
            $data = str_replace("#TEXTDATA", $item["TEXTVIEW"], $data);
            $data = str_replace("#IMAGEPATH", $this->GetPhotoCompany($item["ID_COMPANY"]), $data);
            $outData .= $data;
        }

        return array($outData, $pageselector, $pagecount);
    }
}
?>
