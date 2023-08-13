<?
    class TAdminStats extends TInterface
    {
        /**
         * Переменные доступа к БД, ссылка на массив настроек
         */
        private $TPL;
        private $ERROR;
        public $MODE;
        public $DATA;
        public $HEAD;

        public function __construct()
        {
            // Управление только администраторы
            if ($_SESSION["USER_ROLE"] > parent::ROLE_ADMIN) {
                Redirect("/");
            }

            parent::__construct();
            $this->TPL = _TEMPLATE."admin/stats/";
            $this->MODE = SafeStr(@$_REQUEST["stats"]);

            if ($this->MODE == "statann") {
                die($this->PostAnnounceStatistic());
            } else {
                $this->DATA = $this->RenderDefault();
                $this->HEAD = "Статистика";
            }
        }

        private function PostAnnounceStatistic()
        {
            $value = SafeStr(@$_REQUEST["value"]);
            $vector = explode(";", $value);

            $SQL = "insert into STAT_ANNOUNCE values(NULL, '"
                .$vector[0]."', " // date
                .$vector[5].", "  // user
                .$vector[4].", "  // guest
                .$vector[3].", "  // agent
                .$vector[2].", "  // parser
                .$vector[1].", "  // deleted
                .$vector[6].")";  // company
            $this->DL->Execute($SQL);
            SendLn(GetLocalizeBool($this->DL->PrimaryID() > 0));
        }

        private function RenderDefault()
        {
            $SQL = "select max(DATE_LIFE) from STAT_ANNOUNCE";
            $date = $this->DL->LFetchRecordRow($SQL);
            $date = $date[0] == "" ? $date = "DATE_ADD(now(), interval -36 month)" : $date = "DATE('".$date[0]."')";

            $SQL = "select count(ID_ANNOUNCE), DATE(DATE_ADD(a.DATE_LIFE, interval 1 day)) dl, ud.ID_ROLE, a.ID_STATE, a.ID_GROUP"
                ." from ANNOUNCE_DATA a, USER_DATA ud"
                ." where a.DATE_LIFE > ".$date." and ud.ID_USER=a.ID_USER and ud.ID_STATE=1"
                ." group by dl, ud.ID_ROLE, a.ID_STATE, a.ID_GROUP";
            $dump = $this->DL->LFetchRows($SQL);

            foreach($dump as $item)
            {
                if (!isset($vector[$item[1]])) $vector[$item[1]] = array(0, 0, 0, 0, 0, 0);
                // group > -1
                if ($item[4] > -1) $vector[$item[1]][5] = $item[0]; // 6 company
                    else
                // state = 5
                if ($item[3] == 5) $vector[$item[1]][0] = $item[0]; // 1 deleted
                    else
                // state = 3
                if ($item[3] == 3) $vector[$item[1]][1] = $item[0]; // 2 parser
                    else
                // role = 3
                if ($item[2] == 3) $vector[$item[1]][2] = $item[0]; // 3 agent
                    else
                // role = 5
                if ($item[2] == 5) $vector[$item[1]][3] = $item[0]; // 4 guest
                    else
                // state = 1
                if ($item[3] == 1) $vector[$item[1]][4] = $item[0]; // 5 user
            }
            $content = "";

            if (isset($vector)) {
                // Data Action
                $blockData = file_get_contents($this->TPL."element.html");

                $index = 0;
                foreach($vector as $date=>$value)
                {
                    $index++;
                    $item = $value;
                    $json = $date.";".implode(";", $item);

                    $block = str_replace("#CNTDELETED", $item[0], $blockData);
                    $block = str_replace("#CNTPARSER", $item[1], $block);
                    $block = str_replace("#CNTAGENT", $item[2], $block);
                    $block = str_replace("#CNTGUEST", $item[3], $block);
                    $block = str_replace("#CNTUSER", $item[4], $block);
                    $block = str_replace("#CNTCOMPANY", $item[5], $block);
                    $block = str_replace("#DATELIFE", $date, $block);
                    $block = str_replace("#CNTALL", array_sum($item), $block);
                    $block = str_replace("#INDEX", $index, $block);
                    $block = str_replace("#APPROOVE", $json, $block);
                    $content .= $block;
                }
            }

            // Data Stored
            $SQL = "select * from STAT_ANNOUNCE order by DATE_LIFE desc";
            $dump = $this->DL->LFetchRows($SQL);
            $stored = "";
            foreach ($dump as $item) {
                $stored .= "<tr>";
                for ($index = 1; $index < count($item); $index++) {
                    $stored .= "<td>".$item[$index]."</td>";
                }
                $stored .= "</tr>";
            }

            $stream = file_get_contents($this->TPL."default.html");
            $stream = str_replace("#DATAACTION", $content, $stream);
            $stream = str_replace("#DATASTORED", $stored, $stream);

            return $stream;
        }
    }
?>
