<?
class TBanner extends TInterface
{
    private $TPL;
    public $MODE;

    const E_CREATED  = 2;
    const E_UPDATED  = 4;

    private $costCount = array(
        /*"Не учитывать" => 0,*/
        "1 000"     => array(1000,    1),
        "10 000"    => array(10000,   3),
        "25 000"    => array(25000,   5),
        "50 000"    => array(50000,   10),
        "100 000"   => array(100000,  15)/*,
        "500 000"   => array(500000,  30),
        "1 000 000" => array(1000000, 50)*/
    );

    public function __construct()
    {
        parent::__construct();
        $this->TPL = _TEMPLATE."banner/";
        $this->MODE = SafeStr(@$_REQUEST["m"]);
    }

    public function RenderError($content = "")
    {
        if ($this->FL_ERR == parent::E__NOERROR) return $content;

        switch($this->FL_ERR)
        {
            case self::E_NOTPARAM: {
                return parent::RenderErrorTemplate($content, parent::E__ERRORID,
                    "Ошибка при передаче параметров. Повторите операцию, либо сообщите в службу поддержки");
            }
            case self::E_NOTFOUND: {
                return parent::RenderErrorTemplate($content, parent::E__ERRORID,
                    "Запрошенный баннер не найден");
            }
            case self::E_CREATED: {
                return parent::RenderErrorTemplate($content, parent::E__SUCCSID,
                    "Баннер успешно создан и готов для публикации");
            }
            case self::E_UPDATED: {
                return parent::RenderErrorTemplate($content, parent::E__SUCCSID,
                    "Изменения баннера сохранены");
            }
        }
        return $content;
    }

    public function Execute()
    {
        if ($this->MODE == "create") {
            return $this->RenderCreate();
        } else
        if ($this->MODE == "change") {
            return $this->RenderUpdate();
        } else
        if ($this->MODE == "post") {
            return $this->Post();
        } else {
            return $this->RenderBox();
        }
    }

    private function GetLimitValue($vector, $index)
    {
        foreach ($vector as $name=>$count) {
            if ($count[1] == $index) return $count[0];
        }
        return 0;
    }

    private function GetLimitCount()
    {
        $stream = "";
        foreach ($this->costCount as $name=>$count) {
            $stream .= "<option value='".$count[1]."'>".$name."</option>";
        }
        return $stream;
    }

    private function GetLimitDate()
    {
        $vector = array(
            "Не учитывать" => 0,
            "1 день"       => 1,
            "3 дня"        => 3,
            "Неделя"       => 7,
            "Две недели"   => 14,
            "1 месяц"      => 31,
            "3 месяца"     => 93,
            "6 месяцев"    => 186,
            "12 месяцев"   => 372);
        $stream = "";
        foreach ($vector as $name=>$count) {
            $stream .= "<option value='".$count."'>".$name."</option>";
        }
        return $stream;
    }

    /* todo refactor */
    public function RegisterClick($banner_id)
    {
        $SQL = "update BLOCK_BANNER set RCLICKED=RCLICKED+1 where ID_STATE=".parent::STATE_ACTIVE." and ID_BANNER=".$banner_id;
        $this->DL->Execute($SQL);
    }

    public function RenderBox()
    {
        parent::SafeUserID($user_field);

        $SQL = "select rr.CAPTION as CITY, rr.LATINOS, rc.CAPTION as CATEGORY, rs.CAPTION as STATE, b.*"
            ." from BLOCK_BANNER b, REF_CITY rr, REF_CATEGORY rc, REF_STATE rs"
            ." where rr.ID_CITY=b.ID_CITY and rc.ID_CATEGORY=b.ID_CATEGORY and rs.ID_STATE=b.ID_STATE and ".$user_field
            ." order by DATE_LIFE desc";
        $dump = $this->DL->LFetch($SQL);

        $outItem = file_get_contents($this->TPL."item.html");
        $out = "";
        $outData = "";
        foreach ($dump as $item) {
            $out = str_replace("#CAPTION", ShortString(($item["CAPTION"]), 38), $outItem);
            $out = str_replace("#LINKURL", ($item["LINKURL"]), $out);
            $out = str_replace("#LATINOS", parent::SafeDomain($item["LATINOS"]), $out);
            $out = str_replace("#BANNERID", $item["ID_BANNER"], $out);
            $out = str_replace("#CITY", $item["CITY"], $out);
            $out = str_replace("#CATEGORYID", $item["ID_CATEGORY"], $out);
            $out = str_replace("#CATEGORY", $item["CATEGORY"], $out);
            $out = str_replace("#SHOWED", $item["RSHOWED"], $out);
            $out = str_replace("#CLICKED", $item["RCLICKED"], $out);
            $out = str_replace("#LIMSHOW", $item["LIMSHOW"], $out);
            $out = str_replace("#LIMDATE", $item["LIMDATE"], $out);
            $out = str_replace("#STATUS", $item["STATE"], $out);
            $out = str_replace("#IMGBANNER", _BANNER."header/".$item["ID_BANNER"].".jpg", $out);
            $outData .= $out;
        }

        $stream = file_get_contents($this->TPL."default.html");
        $stream = str_replace("#BANNERDATA", $outData, $stream);
        return self::RenderError($stream);
    }

    public function RenderCreate()
    {
        //$list_city = $this->BuildSelectCash(parent::CashCity(), -1, false, 0);
        //$list_caty = $this->BuildSelectCash(parent::CashCategory(), -1, false, 0);
        $list_show = self::GetLimitCount();
        //$list_date = self::GetLimitDate();

        $stream = file_get_contents($this->TPL."create.html");
        //$stream = str_replace("#CITY", $list_city, $stream);
        //$stream = str_replace("#CATEGORY", $list_caty, $stream);
        $stream = str_replace("#LIMSHOW", $list_show, $stream);
        //$stream = str_replace("#LIMDATE", $list_date, $stream);

        return self::RenderError($stream);
    }

    public function RenderUpdate()
    {
        $banner_id = SafeInt(@$_REQUEST["id"]);
        parent::SafeUserID($user_field);

        $SQL = "select ID_BANNER, CAPTION, LINKURL from BLOCK_BANNER b"
            ." where ID_BANNER=".$banner_id." and ".$user_field;
        $item = $this->DL->LFetchRecord($SQL) or RedirectError(self::E_NOTFOUND, "/cabuser/banner");

        $stream = file_get_contents($this->TPL."update.html");
        $stream = str_replace("#BANNERID", $banner_id, $stream);
        $stream = str_replace("#CAPTION", ($item["CAPTION"]), $stream);
        $stream = str_replace("#LINKURL", ($item["LINKURL"]), $stream);

        return self::RenderError($stream);
    }

    private function PostCreate($caption, $linkurl, $limshow)
    {
        // Проверка капчи
        if (!GetCaptchaBoolVerify()) RedirectError(self::E_NOTPARAM);
        parent::SafeUserRegisterEx($user_id, $guest_id);

        $SQL = "insert into BLOCK_BANNER (ID_USER, ID_GUEST, CAPTION, LINKURL, LIMSHOW, ID_CATEGORY, ID_CITY) values"
            ."(".$user_id.", ".$guest_id.", '".$caption."', '".$linkurl."', ".$limshow.", 1, 88)";
        $this->DL->Execute($SQL);

        return $this->DL->PrimaryID();
    }

    private function PostUpdate($banner_id, $caption, $linkurl)
    {
        parent::SafeUserID($user_field);

        $SQL = "select ID_BANNER from BLOCK_BANNER b where ID_BANNER=".$banner_id." and ".$user_field;
        $this->DL->LFetchField($SQL) or RedirectError(self::E_NOTFOUND, "/cabuser/banner");

        $SQL = "update BLOCK_BANNER b set CAPTION='".$caption."', LINKURL='".$linkurl."', ID_STATE=".parent::STATE_MODER
            ." where ID_BANNER=".$banner_id." and ".$user_field;
        $this->DL->Execute($SQL);

        return $banner_id;
    }

    private function Post()
    {
        $banner_id = SafeInt(@$_REQUEST["id"]);
        $caption = SafeStr(@$_REQUEST["caption"]);
        $linkurl = SafeStr(@$_REQUEST["linkurl"]);
        $limshow = self::GetLimitValue($this->costCount, SafeInt(@$_REQUEST["limshow"]));

        if ($banner_id > 0) {
            $banner_id = self::PostUpdate($banner_id, $caption, $linkurl);
        } else {
            $banner_id = self::PostCreate($caption, $linkurl, $limshow);
        }

        if (is_array($_FILES["file"])) {
            if (($_FILES["file"]["error"] == 0) && ($_FILES["file"]["size"] <= 153600)) {
                move_uploaded_file($_FILES["file"]["tmp_name"], _BANNER."header/".$banner_id.".jpg");
            }
        }

        if ($banner_id > 0) {
            RedirectError(self::E_UPDATED, "/cabuser/banner");
        } else {
            RedirectError(self::E_CREATED, "/cabuser/banner");
        }
    }
}
?>