<?
    class TAdminBanner extends TInterface
    {
        private $ID;
        private $ERROR;
        public $MODE;
        public $DATA;
        public $HEAD;

        const E_UPLOAD_OK = 0; // Успешная загрузка файла в /tmp
        const E_CREATE_OK = 1; // Успешное создание баннера
        const E_UPDATE_OK = 2; // Успешное обновление баннера
        const LINK_DEFAULT = "/admin/banner&id="; // Страница ошибки

        /**
         * TKupoAnnounce::__construct()
         *
         * @param mixed $DataClass
         * @return класс связи с БД и код запрошенного действия
         */
        public function __construct()
        {
            return false;
            // Управление только администраторы
            if ($_SESSION["USER_ROLE"] > parent::ROLE_ADMIN) {
                Redirect("/");
            }

            parent::__construct();
            // Код баннера
            $this->ID = SafeInt(@$_REQUEST["id"]);
            $this->MODE = SafeStr(@$_REQUEST["banner"]);
            $this->ERROR = SafeInt(@$_REQUEST["e"]);

            // Форма создания баннера
            if ($this->MODE == "create") {
                $this->HEAD = "Создание баннера";
                $this->DATA = self::RenderCreate();
            } else
            // Форма обновления баннера
            if ($this->MODE == "edit") {
                $this->DATA = self::RenderEdit();
                $this->HEAD = "Обновление баннера";
            } else
            // Создание баннера
            if ($this->MODE == "postcreate") {
                $this->Create();
            } else
            // Обновление баннера
            if ($this->MODE == "postupdate") {
                $this->Update();
            } else {
            // Вывод доступных баннеров
                $this->HEAD = "Управление баннерами";
                $this->DATA = self::RenderView();
            }
            // Обработка ошибок
            if ($this->ERROR == self::E_CREATE_OK) {
                $this->HEAD .= "<font color='black'> :: баннер создан</font>";
            } else
            if ($this->ERROR == self::E_UPDATE_OK) {
                $this->HEAD .= "<font color='black'> :: баннер обновлен</font>";
            }
        }

        /**
         * TAdminBanner::UploadBanner()
         *
         * @param mixed $bannerID
         * @return Загрузка баннера на сервер
         */
        private function UploadBanner($bannerID)
        {
            // Каталог баннеров
            $path = _BANNER."header/";
            // Проверка на передачу файла
            if (isset($_FILES["file"])) $files = $_FILES["file"]; else return false;
            // Проверка на успешную загрузку файла
            if (($files['error'] == self::E_UPLOAD_OK) && (is_dir($path) || @mkdir($path))) {
                return move_uploaded_file($files['tmp_name'], $path.$bannerID.".jpg");
            }

            return false;
        }

        /**
         * TAdminBanner::RenderView()
         *
         * @return Вывод доступных баннеров
         */
        private function RenderView()
        {
            // Выборка и отключенных и вкюченных баннеров
            $SQL = "select rb.ID_BANNER, concat(rc.CAPTION, ', ', rb.CAPTION) as CAPTION, rb.ID_STATE"
                ." from REF_BANNER rb, REF_CITY rc where rb.ID_CITY=rc.ID_CITY order by rb.ID_BANNER desc";
            $dump = $this->DL->LFetch($SQL);

            // Формирование вывода
            $out = "";
            foreach ($dump as $item) {
                $out .= "<div class='state-container'>".GetStateContainer($item["ID_STATE"],
                    "<a href='/admin/banner=edit&id=".$item["ID_BANNER"]."'>".($item["CAPTION"])
                    ."<br/><img width='761px' height='67px' src='"._BANNER."header/".$item["ID_BANNER"].".jpg'></a></div>"
                );
            }
            // Подключение итогового шаблона
            $stream = file_get_contents(_TEMPLATE."admin/banner/default.html");
            $stream = str_replace("#BLOCKDATA", $out, $stream);

            return $stream;
        }

        /**
         * TAdminBanner::RenderCreate()
         *
         * @return Форма создания баннера
         */
        private function RenderCreate()
        {
            $stream = file_get_contents(_TEMPLATE."admin/banner/create.html");
            $stream = str_replace("#CITYLIST", $this->BuildSelectCash(parent::CashCity()), $stream);

            return $stream;
        }

        /**
         * TAdminBanner::RenderEdit()
         *
         * @return Форма редактирования баннера
         */
        private function RenderEdit()
        {
            // Поиск баннера
            $SQL = "select * from REF_BANNER where ID_BANNER=".$this->ID;
            $item = $this->DL->LFetchRecord($SQL);
            // Выборка доступных состояний в радио группу
            $SQL = "select ID_STATE, CAPTION from REF_STATE where ID_STATE in (1, 2)";
            $catState = GetRadioOption($SQL, $item["ID_STATE"], "states");
            // Подключение итогового шаблона
            $stream = file_get_contents(_TEMPLATE."admin/banner/update.html");
            $stream = str_replace("#BANNERLINK", _BANNER."header/", $stream);
            $stream = str_replace("#BANNERID", $item["ID_BANNER"], $stream);
            $stream = str_replace("#CAPTION", $item["CAPTION"], $stream);
            $stream = str_replace("#STATES", $catState, $stream);
            $stream = str_replace("#CITYLIST", $this->BuildSelectCash(parent::CashCity(), $item["ID_CITY"]), $stream);

            return $stream;
        }

        /**
         * TAdminBanner::Create()
         *
         * @return void Создание баннера
         */
        private function Create()
        {
            // Баннер привязан к городу
            $caption = SafeStr(@$_POST["caption"]);
            $city = SafeInt(@$_POST["city"]);
            // Добавление баннера
            $SQL = "insert into REF_BANNER (CAPTION, ID_CITY) values('".$caption."', ".$city.")";
            $this->DL->Execute($SQL);
            // Загрузка картинки баннера
            $this->UploadBanner($this->DL->PrimaryID());

            Redirect(self::LINK_DEFAULT.$this->ID."&e=".self::E_CREATE_OK);
        }

        /**
         * TAdminBanner::Update()
         *
         * @return void Обновление баннера
         */
        private function Update()
        {
            // Баннер привязан к городу
            $caption = SafeStr(@$_POST["caption"]);
            $city = SafeInt(@$_POST["city"]);
            // Массив статусов дял обработки
            $states = @$_POST["states"];
            // Выбор состояния записи из массива
            if (list($key, $val) = each($states)) $state = SafeInt($val); else $state = 2;
            // Обновление баннера
            $SQL = "update REF_BANNER set CAPTION='".$caption."', ID_CITY=".$city.", ID_STATE=".$state." where ID_BANNER=".$this->ID;
            $this->DL->Execute($SQL);
            // Загрузка картинки баннера
            $this->UploadBanner($this->ID);

            Redirect(self::LINK_DEFAULT.$this->ID."&e=".self::E_UPDATE_OK);
        }
    }
?>