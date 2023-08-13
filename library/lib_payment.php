<?
class TPayment extends TInterface
{
    public $MODE;
    private $ITEM;
    private $TPL;
    private $ErrID;

    const TRF_BORDER = 1;
    const TRF_COLOR  = 2;
    const TRF_TAG_3  = 3;
    const TRF_TAG_4  = 4;
    const TRF_TAG_5  = 5;
    const TRF_TAG_7  = 7;
    const TRF_TOPPOS = 101;

    const IDT_UNKNOWN   = 0;
    const IDT_TAG       = 1;
    const IDT_BORDER    = 2;

    const E_ACTIVATED   = 1;
    const E_NOTFOUND    = 2;

    public function __construct()
    {
        parent::__construct();
        $this->MODE  = SafeStr($_REQUEST["payment"]);
        $this->ITEM  = SafeInt($_REQUEST["id"]);
        $this->ErrID = SafeInt(@$_GET["e"]);
        $this->TPL   = _TEMPLATE."payment/";
    }

    public function RenderError($content = "")
    {
        $error_id = $this->ErrID;
        $errclass = false;
        if ($error_id == 0) return $content;
        // Код объявления
        $announce_id = SafeInt(@$_GET["uid"]);

        if ($error_id == self::E_ACTIVATED) {
            $error = "Услуга активирована";
            $errclass = parent::E__SUCCS;
        } else
        if ($error_id == self::E_NOTFOUND) {
            $error = "Объявление или сервис не существует";
            $errclass = parent::E__ERROR;
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


    public function RenderAnnounce()
    {
        $announce_id = SafeInt(@$_REQUEST["id"]);

        $SQL = "select ID_STYLE, CAPTION, DESCRIPTION, TAGNAME, TAGLINK, TAGCLASS, TIMELEFT"
            ." from REF_STYLE where ID_STATE=1 order by ORDERBY desc";
        $dump = $this->DL->LFetch($SQL);

        $SQL = "select ID_STYLE, DATE_LIFE from BLOCK_STYLES where ID_ANNOUNCE=".$this->ITEM;
        $styles = $this->DL->LFetchRows($SQL);

        $template = file_get_contents($this->TPL."block.html");
        $data = "";
        foreach ($dump as $item) {
            $block = str_replace("#ANNOUNCEID", $this->ITEM, $template);
            $block = str_replace("#CAPTION", $item["CAPTION"], $block);
            $block = str_replace("#DESCRIPTION", $item["DESCRIPTION"], $block);
            $block = str_replace("#TAGCLASS", $item["TAGCLASS"], $block);
            $block = str_replace("#TAGNAME", $item["TAGNAME"], $block);
            $block = str_replace("#TAGLINK", $item["TAGLINK"], $block);
            $block = str_replace("#TIMELEFT", $item["TIMELEFT"], $block);

            /* todo */
            $timelife = "";
            $active = "deactive";
            for ($index = 0; $index < count($styles); $index++) {
                if ($styles[$index][0] == $item["ID_STYLE"]) {
                    $active = "active";
                    $timelife = $styles[$index][1];
                    break;
                }
            }
            $block = str_replace("#TIMELIFE", $timelife, $block);
            $block = str_replace("#ACTIVE", $active, $block);
            $data .= $block;
        }

        $stream = file_get_contents($this->TPL."announce.html");
        $stream = str_replace("#CONTENT", $data, $stream);
        $stream = str_replace("#ANNOUNCEID", GetStretchNumber($this->ITEM), $stream);

        return $this->RenderError($stream);
    }

    public function Transform()
    {
        $transform = SafeStr(@$_POST["transform"]);

        if ($transform == "toppos") {
            $this->ApplyPosition(self::TRF_TOPPOS);
        } else

        if ($transform == "border") {
            $this->ApplyTransform(self::TRF_BORDER, self::IDT_BORDER);
        } else

        if ($transform == "color") {
            $this->ApplyTransform(self::TRF_COLOR, self::IDT_BORDER);
        } else

        if ($transform == "tag-3") {
            $this->ApplyTransform(self::TRF_TAG_3);
        } else

        if ($transform == "tag-4") {
            $this->ApplyTransform(self::TRF_TAG_4);
        } else

        if ($transform == "tag-5") {
            $this->ApplyTransform(self::TRF_TAG_5);
        } else

        if ($transform == "tag-7") {
            $this->ApplyTransform(self::TRF_TAG_7);
        } else

        die();

        /*todo*/
        RedirectError(self::E_ACTIVATED);
    }

    private function ApplyTransform($code, $type = self::IDT_TAG)
    {
        $SQL = "select UNIX_TIMESTAMP(DATE_LIFE) - UNIX_TIMESTAMP(now()) from BLOCK_STYLES where ID_ANNOUNCE=".$this->ITEM
            ." and ID_TYPE=".$type;
        $timeleft = $this->DL->LFetchRecordRow($SQL);

        /*todo*/
        $timeleft = 0;

        $SQL = "select rs.TIMELEFT from REF_STYLE rs, ANNOUNCE_DATA ad where rs.ID_STYLE=".$code." and ID_ANNOUNCE=".$this->ITEM;
        $interval = $this->DL->LFetchRecordRow($SQL);
        if (!$interval) RedirectError(self::E_NOTFOUND);

        $datelife = "now() + interval ".(int)$timeleft[0]." second + interval ".(int)$interval[0]." hour";

        $SQL = "delete from BLOCK_STYLES where ID_ANNOUNCE=".$this->ITEM." and ID_TYPE=".$type;
        $this->DL->Execute($SQL);

        $SQL = "insert into BLOCK_STYLES values (".$this->ITEM.", ".$code.", ".$type.",".$datelife.")";
        $this->DL->Execute($SQL);
    }

    private function ApplyPosition($code)
    {
        if ($code == self::TRF_TOPPOS) {
            $counter = $this->NextCounter("CNT_POSITION_ANN", $this->ITEM);

            $SQL = "update ANNOUNCE_DATA set DATE_LIFE=now(), POSITION=".$counter." where ID_ANNOUNCE=".$this->ITEM;
            $this->DL->Execute($SQL);
        }

        if ($this->DL->LAffected() > 0) {
            RedirectError(self::E_ACTIVATED);
        } else {
            RedirectError(self::E_NOTFOUND);
        }
    }
}
?>
