<?
    class TAdminCategory extends TInterface
    {
        /**
         * Переменные доступа к БД, ссылка на массив настроек
         */
        private $ID;
        public $ERROR;
        public $MODE;
        public $DATA;
        public $HEAD;

        /**
         * Константизированные ссылки
         */
        const LINK_DEFAULT_ANN = "/admin/category=announce&id=";
        const LINK_DEFAULT_COM = "/admin/category=company&id=";
        // Успешное обновление рубрики
        const E_UPDATE_OK = 2;

        public function __construct()
        {
            // Управление только администраторы
            if ($_SESSION["USER_ROLE"] > parent::ROLE_ADMIN) {
                Redirect("/");
            }


            parent::__construct();
            $this->ID = SafeInt(@$_REQUEST["id"]);
            $this->MODE = SafeStr(@$_REQUEST["category"]);
            $this->ERROR = SafeInt(@$_REQUEST["e"]);
            $this->HEAD = "Рубрикатор ";

            if ($this->MODE == "announce") {
                $this->HEAD .= "объявлений";
                $this->DATA = $this->RenderCategoryAnnounce();
            } else
            if ($this->MODE == "company") {
                $this->HEAD .= "компаний";
                $this->DATA = $this->RenderCategoryCompany();
            } else
            if ($this->MODE == "postann") {
                $this->PostAnnounce();
            } else
            if ($this->MODE == "postcom") {
                $this->PostCompany();
            } else {
                $this->DATA = "<h4>select option to action</h4>";
                $this->HEAD .= "Общий обзор";
            }

            /* todo */
            if ($this->ERROR == self::E_UPDATE_OK) {
                $this->HEAD .= " :: Изменения сохранены";
            }

            $stream = file_get_contents(_TEMPLATE."admin/category/default.html");
            $this->DATA = str_replace("#BLOCKDATA", $this->DATA, $stream);
        }

        private function RenderCategoryAnnounce()
        {
            if ($this->ID == 0) $this->ID++;
            $catList = "";

            // Блок подрубрик
            $SQL = "select ID_CATEGORY, CAPTION from REF_CATEGORY where ID_PARENT=".$this->ID." order by ID_PARENT, ORDERBY, CAPTION";
            $dump = $this->DL->LFetchRows($SQL);
            foreach ($dump as $item) {
                $catList .= "<li><a href='".self::LINK_DEFAULT_ANN.$item[0]."'>".$item[1]."</a></li>";
            }
            // Параметры категории
            $SQL = "select * from REF_CATEGORY where ID_CATEGORY=".$this->ID;
            $catVal = $this->DL->LFetchRecord($SQL);
            // Блок действий
            $SQL = "select ACTION, CAPTION from REF_ACTION R where ID_STATE=1 order by ORDERBY desc";
            $catAction = GetCheckboxOption($SQL, $catVal["ACTION"], "actions");
            // Блок статусов
            $SQL = "select ID_STATE, CAPTION from REF_STATE where ID_STATE in (1, 2)";
            $catState = GetRadioOption($SQL, $catVal["ID_STATE"], "states");
            // Блок перелинковки
            $catPath = $this->GetAnnouncePath($catVal["LEVEL"], $catVal["CAPTION"], self::LINK_DEFAULT_ANN, true);

            /* todo template */
            // Формирвоание выходного шаблона
            $stream = file_get_contents(_TEMPLATE."admin/category/block_announce.html");
            $stream = str_replace("#CATID",    $this->ID, $stream);
            $stream = str_replace("#CAPTION",  $catVal["CAPTION"], $stream);
            $stream = str_replace("#KEYWORDS", $catVal["TINDEX"], $stream);
            $stream = str_replace("#CATLIST",  $catList, $stream);
            $stream = str_replace("#CATPATH",  $catPath, $stream);
            $stream = str_replace("#ACTIONS",  $catAction, $stream);
            $stream = str_replace("#STATES",   $catState, $stream);

            return $stream;
        }

        private function RenderCategoryCompany()
        {
            if ($this->ID == 0) $this->ID++;
            $catList = "";

            // Блок подрубрик
            $SQL = "select ID_CATEGORY, CAPTION from COMPANY_CATEGORY where ID_PARENT=".$this->ID." order by ID_PARENT, ORDERBY, CAPTION";
            $dump = $this->DL->LFetchRows($SQL);
            foreach ($dump as $item) {
                $catList .= "<li><a href='".self::LINK_DEFAULT_COM.$item[0]."'>".$item[1]."</a></li>";
            }
            // Параметры категории
            $SQL = "select * from COMPANY_CATEGORY where ID_CATEGORY=".$this->ID;
            $catVal = $this->DL->LFetchRecord($SQL);
            // Блок статусов
            $SQL = "select ID_STATE, CAPTION from REF_STATE where ID_STATE in (1, 2)";
            $catState = GetRadioOption($SQL, $catVal["ID_STATE"], "states");
            // Блок перелинковки
            $catPath = $this->GetCompanyPath($this->ID, self::LINK_DEFAULT_COM);

            /* todo */
            // Формирвоание выходного шаблона
            $stream = file_get_contents(_TEMPLATE."admin/category/block_company.html");
            $stream = str_replace("#CATID",    $this->ID, $stream);
            $stream = str_replace("#CAPTION",  $catVal["CAPTION"], $stream);
            $stream = str_replace("#KEYWORDS", $catVal["TINDEX"], $stream);
            $stream = str_replace("#CATLIST",  $catList, $stream);
            $stream = str_replace("#CATPATH",  $catPath, $stream);
            $stream = str_replace("#STATES",   $catState, $stream);

            return $stream;
        }

        public function PostAnnounce()
        {
            $caption  = SafeStr(@$_POST["caption"]);
            $keywords = SafeStr(@$_POST["keywords"]);
            $actions  = @$_POST["actions"];
            $states   = @$_POST["states"];
            $inheritAction = SafeBool("inheritaction");

            // Сбор доступных действий
            $action = "";
            while (list($key, $val) = each($actions)) {
                $action .= SafeStr($key);
            }
            // Сбор доступных состояний
            $state = "";
            while (list($key, $val) = each($states)) {
                $state .= SafeInt($val);
            }
            // Обновление параметров рубрики
            $SQL = "update REF_CATEGORY set CAPTION='".$caption."', TINDEX='".$keywords."', ID_STATE=".$state
                ." where ID_CATEGORY=".$this->ID;
            $this->DL->Execute($SQL);

            // Параметры рубрики
            if ($inheritAction) {
                $SQL = "select LEVEL from REF_CATEGORY where ID_CATEGORY=".$this->ID;
                $level = $this->DL->LFetchRecordRow($SQL);
            }

            // Каскадное обновление действий
            $path = $inheritAction ? "LEVEL like '".$level[0]."%'" : "ID_CATEGORY=".$this->ID;
            $SQL = "update REF_CATEGORY set ACTION='".$action."' where ".$path;
            $this->DL->Execute($SQL);

            RedirectError(self::E_UPDATE_OK);
        }

        public function PostCompany()
        {
            $caption  = SafeStr(@$_POST["caption"]);
            $keywords = SafeStr(@$_POST["keywords"]);
            $states   = @$_POST["states"];

            // Сбор доступных состояний
            $state = "";
            while (list($key, $val) = each($states)) {
                $state .= SafeInt($val);
            }
            // Обновление параметров рубрики
            $SQL = "update COMPANY_CATEGORY set CAPTION='".$caption."', TINDEX='".$keywords."', ID_STATE=".$state
                ." where ID_CATEGORY=".$this->ID;
            $this->DL->Execute($SQL);

            RedirectError(self::E_UPDATE_OK);
        }
    }
?>
