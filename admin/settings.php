<?
    class TAdminSettings extends TInterface
    {
        /**
         * Переменные доступа к БД, ссылка на массив настроек
         */
        private $ERROR;
        public $MODE;
        public $DATA;
        public $HEAD;

        const E_SAVETRUE = 1;
        const E_SAVEFALSE = 2;

        public function __construct()
        {
            // Управление только администраторы
            if ($_SESSION["USER_ROLE"] > parent::ROLE_ADMIN) {
                Redirect("/");
            }

            parent::__construct();
            $this->MODE = SafeStr(@$_REQUEST["settings"]);
            $this->ERROR = SafeInt(@$_REQUEST["e"]);
            $this->HEAD = "Настройки &raquo; ";

            if ($this->MODE == "postdef") {
                $this->PostDefault();
            } else

            if ($this->MODE == "postsys") {
                $this->PostSystem();
            } else

            if ($this->MODE == "announce") {
                $this->HEAD .= "Объявления";
                $this->DATA = $this->RenderAnnounce();
            } else

            if ($this->MODE == "blockandban") {
                $this->HEAD .= "Баны";
                $this->DATA = $this->RenderBlockAndBan();
            } else

            if ($this->MODE == "photos") {
                $this->HEAD .= "Фотографии";
                $this->DATA = $this->RenderPhotos();
            } else
            if ($this->MODE == "system") {
                $this->HEAD .= "Системные";
                $this->DATA = $this->RenderSystem();
            } else {
                $this->DATA = "<h4>select option to action</h4>";
                $this->HEAD .= "Общий обзор";
            }

            if ($this->ERROR == self::E_SAVETRUE) {
                $this->HEAD .= "<font color='black'> :: сохранено</font>";
            } else
            if ($this->ERROR == self::E_SAVEFALSE) {
                $this->HEAD .= "<font color='red'> :: ошибка сохранения</font>";
            }

            $stream = file_get_contents(_TEMPLATE."admin/settings/default.html");
            $this->DATA = str_replace("#BLOCKDATA", $this->DATA, $stream);

            return $stream;
        }

        private function RenderSystem()
        {
            $stream = file_get_contents(_TEMPLATE."admin/settings/system.html");
            $stream = str_replace("#SITE_TESIS", $this->DC["SITE_TESIS"], $stream);
            $stream = str_replace("#SITE_TITLE", $this->DC["SITE_TITLE"], $stream);
            return $stream;
        }

        private function RenderAnnounce()
        {
            $stream = file_get_contents(_TEMPLATE."admin/settings/announce.html");
            $stream = str_replace("#LIMIT_PAGE", $this->DC["LIMIT_PAGE"], $stream);
            $stream = str_replace("#LIMIT_ANNOUNCEEXT", $this->DC["LIMIT_ANNOUNCEEXT"], $stream);
            $stream = str_replace("#LIMIT_SELECTOR", $this->DC["LIMIT_SELECTOR"], $stream);
            return $stream;
        }

        private function RenderBlockAndBan()
        {
            $stream = file_get_contents(_TEMPLATE."admin/settings/blockandban.html");
            $stream = str_replace("#LIMIT_BANIP", $this->DC["LIMIT_BANIP"], $stream);
            $stream = str_replace("#LIMIT_BANTIME", $this->DC["LIMIT_BANTIME"], $stream);
            return $stream;
        }

        private function RenderPhotos()
        {
            $stream = file_get_contents(_TEMPLATE."admin/settings/photos.html");
            $stream = str_replace("#LIMIT_IMAGE_GUEST", $this->DC["LIMIT_IMAGE_GUEST"], $stream);
            $stream = str_replace("#LIMIT_IMAGE_AUTH", $this->DC["LIMIT_IMAGE_AUTH"], $stream);
            $stream = str_replace("#LIMIT_IMAGE_COMP", $this->DC["LIMIT_IMAGE_COMP"], $stream);
            $stream = str_replace("#IMAGE_MAXWSIZE", $this->DC["IMAGE_MAXWSIZE"], $stream);
            $stream = str_replace("#IMAGE_MAXHSIZE", $this->DC["IMAGE_MAXHSIZE"], $stream);
            $stream = str_replace("#IMAGE_PHOTOSIZE", $this->DC["IMAGE_PHOTOSIZE"], $stream);
            $stream = str_replace("#IMAGE_THUMBSIZE", $this->DC["IMAGE_THUMBSIZE"], $stream);
            $stream = str_replace("#IMAGE_ICONSIZE", $this->DC["IMAGE_ICONSIZE"], $stream);
            return $stream;
        }

        private function PostData($filename)
        {
            $config = file_get_contents($filename);

            while (list($key, $value) = each($_REQUEST))
            {
                if (is_numeric($value)) {
                    $sourcement = '#\$_CONFIG\["'.$key.'"\].\=.(.+?);#s';
                    $replacement = '$_CONFIG["'.SafeStr($key).'"] = '.$value.";";
                } else {
                    $sourcement = '#\$_CONFIG\["'.$key.'"\].\=."(.+?)";#s';
                    $replacement = '$_CONFIG["'.SafeStr($key).'"] = "'.$value.'";';
                }
                $config = preg_replace($sourcement, $replacement, $config);
            }

            return file_put_contents($filename, $config);
        }

        private function PostSystem()
        {
            if ($this->PostData("config.inc.php")) {
                RedirectError(self::E_SAVETRUE);
            } else {
                RedirectError(self::E_SAVEFALSE);
            }
        }

        private function PostDefault()
        {
            if ($this->PostData("config.php")) {
                RedirectError(self::E_SAVETRUE);
            } else {
                RedirectError(self::E_SAVEFALSE);
            }
        }
    }
?>
