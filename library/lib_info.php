<?
// todo навскидку за 1.5 часа )
class TInfo extends TInterface
{
    public $MODE;
    private $TPL;
    public $TITLE;
    public $KEYWORDS;

    const TYPE_NEWS = 0;

    public function __construct()
    {
        parent::__construct();
        $this->TPL = _TEMPLATE."info/";
        $this->MODE = SafeStr(@$_GET["info"]);
    }

    public function RenderNews()
    {
        $SQL = "select ID_INFO, DATE_LIFE, CAPTION from BLOCK_INFO where ID_PARENT=1 and ID_STATE=1 order by DATE_LIFE desc limit 3";
        $dump = $this->DL->LFetchRows($SQL);

        $block = file_get_contents($this->TPL."block_news.html");
        $stream = "";
        foreach ($dump as $item) {
            $out = str_replace("#INFOID", $item[0], $block);
            $out = str_replace("#DATELIFE", GetLocalizeTime($item[1]), $out);
            $out = str_replace("#CAPTION", $item[2], $out);
            $stream .= $out;
        }
        return $stream;
    }

    public function RenderInfo()
    {
        $info_id = SafeInt($this->MODE);

        /*$SQL = "select ID_INFO, DATE_LIFE, INFONAME from BLOCK_INFO where ID_STATE=".parent::STATE_ACTIVE
            ." and ID_PARENT=".self::TYPE_NEWS." order by DATE_LIFE desc";
        $dump = $this->DL->LFetchRows($SQL);

        $out = "";
        foreach ($dump as $item) {
            $out .= "<li><b>".GetShortDate($item[1])."</b> <a href='/info/".$item[0]."'>".$item[2]."</a></li>";
        }*/

        $SQL = "select ID_INFO, DATE_LIFE, INFONAME, ID_PARENT from BLOCK_INFO where ID_STATE=".parent::STATE_ACTIVE
            ."  order by ID_PARENT, DATE_LIFE desc, INFONAME";
        $dump = $this->DL->LFetchRows($SQL);



        $out2 = "";
        for ($index = 0; $index < count($dump); $index++) {
            if ($dump[$index][3] > 0) continue;
            $out2 .= "<li><span class='folder'>".$dump[$index][2]."</span><ul>";
            for ($sub = $index + 1; $sub < count($dump); $sub++) {
                if ($dump[$sub][3] != $dump[$index][0]) continue;
                $out2 .= "<li>";
                if ($info_id == $dump[$sub][0]) $out2 .= "<b>";
                $out2 .= "<a href='/info/".$dump[$sub][0]."'>".$dump[$sub][2]."</a></li>";
                if ($info_id == $dump[$sub][0]) $out2 .= "</b>";
            }
            $out2 .= "</ul></li>";
        }


        $SQL = "select INFONAME, CAPTION, uncompress(TEXTDATA), DATE_LIFE from BLOCK_INFO where ID_STATE=".parent::STATE_ACTIVE
            ." and ID_INFO=".$info_id;
        $info = $this->DL->LFetchRecordRow($SQL);

        $stream = file_get_contents($this->TPL."default.html");
        $stream = str_replace("#CAPTION", ($info[1]), $stream);
        $stream = str_replace("#CONTENT", BBCodeToHTML($info[2]), $stream);
        $stream = str_replace("#DATELIFE", $info[3], $stream);
        $stream = str_replace("#TREELIST", $out2, $stream);

        $this->TITLE = ($info[0]);
        $this->KEYWORDS = ($info[0]);


        return $stream;
    }
}
?>