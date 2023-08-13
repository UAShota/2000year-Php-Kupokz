<?
    class TAdminParser extends TInterface
    {
        /**
         * Переменные доступа к БД, ссылка на массив настроек
         */
        public $MODE;
        public $DATA;
        public $HEAD;

        const CURRENCY_DEFAULT = 6;
        const CURRENCY_TENGE = 1;
        const CURRENCY_USA = 2;

        public function __construct()
        {
            // Управление только администраторы
            if ($_SESSION["USER_ROLE"] != parent::ROLE_MODER) {
                Redirect("/");
            }

            parent::__construct();
            $this->HEAD = "Парсер";
            $this->DATA = $this->Render();
        }

        private function cnv($value)
        {
            return iconv("windows-1251", "utf-8", $value);
        }

        private function ucnv($value)
        {
            return iconv("utf-8", "windows-1251", $value);
        }

        public function Render()
        {
            mb_internal_encoding("UTF-8");
            // Подключение модуля управления объявлениями
            include(_LIBRARY."lib_announce.php");
            $Announce = new TAnnounce();
            // Количество обработанных объявлений
            $Affected = 0;
            // Попытка открыть переданный файл
            if ($file = @fopen($_FILES["doc"]["tmp_name"], "r"))
            {
                // Разбор принятых параметров
                $category = trim(fgets($file));
                $city = trim(fgets($file));
                $action = trim(fgets($file));

                $SQL = "select ID_PARENT from REF_CATEGORY where ID_CATEGORY=".$category;
                $division = $this->DL->RunOne($SQL);
                $division = $division["ID_PARENT"];

                $_SESSION["REGION_ID"] = $city;
                $_POST["city"] = $city;
                $_POST["action"] = $action;
                $_POST["division"] = $division;

                // Выборка группы фильтров для категории
                $SQL = "select ID_PARAM_GROUP from REF_PARAM_GROUP where ID_CATEGORY=".$category;
                $paramGroup = $this->DL->RunOne($SQL);
                $paramGroup = $paramGroup["ID_PARAM_GROUP"];

                // Если фильтров для категории нет
                if ($paramGroup == "") $paramGroup = -1;
                // Выборка доступных фильтров
                $SQL = "select ID_PARAM, CAPTION from REF_PARAM where ID_PARAM_GROUP=".$paramGroup;
                $filter = $this->DL->Run($SQL);

                $SQL = "select ID_CATEGORY, CAPTION from REF_CATEGORY where ID_STATE=1 and ID_PARENT=".$category;
                $filcat = $this->DL->Run($SQL);

                // Разбор принятого файла
                while (!feof($file))
                {
                    // Обработка строки из файла
                    $str = trim(fgets($file));
                    if ($str == "") continue;
                    $str = str_replace("  ", " ", $str);
                    $str = str_replace("«", "", $str);
                    $str = str_replace("«", "", $str);
                    $str = str_replace("¬", "", $str);
                    $packStr = mb_strtolower($this->cnv(str_replace(" ", "", $str)), "utf-8");
                    $contact = "";
                    $_POST["cphonemob"] = array();
                    $_POST["cphonehom"] = array();
                    $_POST["cphoneho3"] = array();


                    if (preg_match("#\@\@\@(\d+)\#\#\#(\d+)#Ui", $str, $matches)) {
                        // Цена по умолчанию
                        $cost = $matches[1];
                        // Валюта по умолчанию
                        $currency = $matches[2];

                        $str = preg_replace("#\@\@\@(\d+)\#\#\#(\d+)#Ui", "", $str);
                    } else {
                        // Цена по умолчанию
                        $cost = "NULL";
                        // Валюта по умолчанию
                        $currency = self::CURRENCY_DEFAULT;

                        $str = preg_replace("#\@\@\@NULL\#\#\#6#Ui", "", $str);
                    }

                    if (preg_match("#\%\%\%(.+?)#Ui", $str, $matches)) {
                        $str = preg_replace("#\%\%\%(.+?)#Ui", "", $str).$matches[0];
                    }

                    if (preg_match_all("/\[mob]\s?(.*?);/ms", $str, $matches)) {
                        for ($i=0; $i < count($matches[0]); $i++) {
                            array_push($_POST["cphonemob"], $matches[1][$i]);
                            $str = str_replace($matches[0][$i], "", $str);
                        }
                    }

                    if (preg_match_all("/\[hom]\s?(.*?);/ms", $str, $matches)) {
                        for ($i=0; $i < count($matches[0]); $i++) {
                            array_push($_POST["cphonehom"], $matches[1][$i]);
                            $str = str_replace($matches[0][$i], "", $str);
                        }
                    }

                    if (preg_match_all("/\[ho3]\s?(.*?);/ms", $str, $matches)) {
                        for ($i=0; $i < count($matches[0]); $i++) {
                            array_push($_POST["cphoneho3"], $matches[1][$i]);
                            $str = str_replace($matches[0][$i], "", $str);
                        }
                    }

                    // Обнуление фильтра
                    $_POST["p_".$paramGroup] = "";
                    // Обнуление позиции вхождения фильтра
                    $minpos = 800;
                    // Перебор фильтров
                    foreach ($filter as $item)
                    {
                        // Поиск вхождения фильтра в текст
                        $smFilter = mb_substr($item["CAPTION"], 0, 5);
                        $pos = mb_strpos($packStr, mb_strtolower($smFilter));
                        // Фильтр найден
                        if ($pos !== false)
                        {
                            // Если фильтр актуальнее ранее найденного
                            if ($pos < $minpos)
                            {
                                $_POST["p_".$paramGroup] = $item["ID_PARAM"];
                                $minpos = $pos;
                            }
                        }
                    }

                    $minpos = 800;
                    // Перебор подрубрик
                    $newcategory = 0;
                    foreach ($filcat as $item)
                    {
                        // Поиск вхождения фильтра в текст
                        $smFilter = mb_substr($item["CAPTION"], 0, 5);
                        $pos = mb_strpos($packStr, mb_strtolower($smFilter));

                        // Фильтр найден
                        if ($pos !== false)
                        {
                            // Если фильтр актуальнее ранее найденного
                            if ($pos < $minpos)
                            {
                                $newcategory = $item["ID_CATEGORY"];
                                $minpos = $pos;
                            }
                        }
                    }
                    // если подрубрика не прошла - засовываем в прочее
                    if (is_array($filcat) && ($newcategory == 0)) {
                        foreach ($filcat as $item) {
                            if ("прочее" == mb_strtolower($item["CAPTION"])) {
                                $newcategory = $item["ID_CATEGORY"];
                            }
                        }
                    }
                    if ($newcategory != 0) {
                        $_POST["category"] = $newcategory;
                    } else {
                        $_POST["category"] = $category;
                    }

                     // Включение 15-слов текста в заголовок
                    $caption = explode(" ", trim($str));
                    $caption = array_slice($caption, 0, 6);
                    $caption = implode(" ", $caption);
                    // Обрезание текста
                    $str = str_replace("¬", "", str_replace("  ", " ", $str));
                    // На всякий случай проверка на длину заголовка и текста
                    if ((strlen($caption) < 5) || (strlen($str) < 10)) continue;

                    // Включение заголовка и текста
                    $_POST["caption"] = $this->cnv(str_replace(".,", ",", $caption));
                    $_POST["textdata"] = $this->cnv(str_replace(".,", ",", $str));
                    // Включение цены и валюты
                    $_POST["cost"] = $cost;
                    $_POST["currency"] = $currency;

                    // Добавление нового объявления
                    $Announce->Insert(false);
                    // Инкремент количества обработанных объявлений]
                    $Affected++;
                }
            }
            // Вывод итогового шаблона
            $stream = file_get_contents(_TEMPLATE."admin/parser.html");
            $stream = str_replace("#AFFECTED", $Affected, $stream);

            return $stream;
        }
    }
?>