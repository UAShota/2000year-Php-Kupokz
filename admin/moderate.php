<?
    class TAdminModerate extends TInterface
    {
        /**
         * Переменные доступа к БД, ссылка на массив настроек
         */
        private $TPL;
        public $MODE;
        public $DATA;
        public $HEAD;

        public function __construct()
        {
            parent::__construct();

            $this->MODE = SafeStr(@$_REQUEST["moder"]);
            $this->TPL = _TEMPLATE."admin/moderate/";
            $this->HEAD = "Модерирование &raquo; ";

            if ($this->MODE == "company") {
                if (SafeBool("roove")) {
                    SendLn(self::RooveCompany());
                }
                $this->HEAD .= "Компании";
                $this->DATA = self::CompanyList();
            } else
            if ($this->MODE == "announce") {
                if (SafeBool("roove")) {
                    SendLn(self::RooveAnnounce());
                }
                $this->HEAD .= "Объявления";
                $this->DATA = self::AnnounceList();
            } else
            if ($this->MODE == "banner") {
                if (SafeBool("roove")) {
                    SendLn(self::RooveBanner());
                }
                $this->HEAD .= "Баннеры";
                $this->DATA = self::BannerList();
            } else {
                $this->DATA = "<h4>select option to action</h4>";
                $this->HEAD .= "Общий обзор";
            }

            $stream = file_get_contents(_TEMPLATE."admin/moderate/default.html");
            $this->DATA = str_replace("#BLOCKDATA", $this->DATA, $stream);
        }

        private function AnnounceList()
        {
            $SQL = "select ad.ID_ANNOUNCE, ad.CAPTION, ad.ID_STATE, rc.CAPTION as CATCAP, rac.CAPTION as CITY,"
                ." uncompress(ad.TEXTDATA) as TEXTDATA, uncompress(CONTACT) as CONTACT"
                ." from ANNOUNCE_DATA ad, REF_CATEGORY rc, REF_CITY rac where ad.ID_STATE=4 and rc.ID_STATE=1"
                ." and rc.ID_CATEGORY=ad.ID_CATEGORY and rac.ID_CITY=ad.ID_CITY order by ad.ID_ANNOUNCE desc";
            $dump = $this->DL->LFetch($SQL);

            $blockData = file_get_contents($this->TPL."block_announce.html");
            $outData = "";
            foreach ($dump as $item)
            {
                // Подготовка галереи изображений
                $image_path = _ANNOUNCE.$item["ID_ANNOUNCE"]."/";
                $picdata = "";
                if ($handle = @opendir($image_path."thumb/"))
                {
                    while (false !== ($file = readdir($handle)))
                    {
                        // Пропуск каталогов
                        if (!is_file($image_path.$file)) continue;

                        $filethumb = $image_path.$file;
                        $picdata .= "<a href='".$image_path.$file."'><img class='photo lazy' data-original='".$image_path."thumb/".$file."'"
                            ." src='/images/ajax-loader.gif' width='60px' height='60px' /></a>&nbsp;";
                    }
                }

                $block = str_replace("#ANNOUNCEID", $item["ID_ANNOUNCE"], $blockData);
                $block = str_replace("#CATEGORY", $item["CATCAP"], $block);
                $block = str_replace("#CITY", $item["CITY"], $block);
                $block = str_replace("#PICDATA", $picdata, $block);
                $block = str_replace("#CAPTION", ($item["CAPTION"]), $block);
                $block = str_replace("#TEXTDATA", BBCodeNativeToHTML($item["TEXTDATA"]), $block);
                $block = str_replace("#CONTACT", $this->ContactView($item["CONTACT"]), $block);
                $outData .= $block;
            }
            if ($outData == "") $outData = "<h4>Нет объявлений для модерирования</h4>";

            return $outData;
        }

        private function CompanyList()
        {
            $SQL = "select cd.*, ud.LOGIN from COMPANY_DATA cd, USER_DATA ud"
                ." where cd.ID_STATE=4 and cd.ID_USER=ud.ID_USER";
            $dump = $this->DL->LFetch($SQL);

            $blockData = file_get_contents($this->TPL."block_company.html");
            $outData = "";
            foreach ($dump as $item)
            {
                $domain_list = explode("\r\n", $item["DOMAIN_MODERATE"]);
                $domain = "";
                for ($index = 0; $index < count($domain_list); $index++)
                {
                    $domain_name = SafeStr($domain_list[$index]);
                    if ($domain_name === "") continue;
                    $domain .= "<option value='".$domain_name."'>".$domain_name."</option>";
                }

                $block = str_replace("#CAPTION", $item["CAPTION"], $blockData);
                $block = str_replace("#COMPANYID", $item["ID_COMPANY"], $block);
                $block = str_replace("#USERID", $item["ID_USER"], $block);
                $block = str_replace("#USERLOGIN", $item["LOGIN"], $block);
                $block = str_replace("#AUTODOMAIN", $item["DOMAIN_AUTO"], $block);
                $block = str_replace("#DOMAINLIST", $domain, $block);
                $outData .= $block;
            }
            if ($outData == "") $outData = "<h4>Нет компаний для модерирования</h4>";

            return $outData;
        }

        private function BannerList()
        {
            $SQL = "select bb.ID_BANNER, bb.CAPTION, bb.LINKURL, ud.ID_USER, ud.LOGIN"
                ." from BLOCK_BANNER bb, USER_DATA ud where ud.ID_USER=bb.ID_USER and bb.ID_STATE=".parent::STATE_MODER;
            $dump = $this->DL->LFetch($SQL);

            $blockData = file_get_contents($this->TPL."block_banner.html");
            $outData = "";
            foreach ($dump as $item)
            {
                $picFile = _BANNER."header/".$item["ID_BANNER"].".jpg";
                $picSize = @getimagesize($picFile);
                $block = str_replace("#PICSIZE", json_encode($picSize), $blockData);
                $block = str_replace("#CAPTION", ($item["CAPTION"]), $block);
                $block = str_replace("#LINKURL", ($item["LINKURL"]), $block);
                $block = str_replace("#LOGIN", ($item["LOGIN"]), $block);
                $block = str_replace("#USERID", $item["ID_USER"], $block);
                $block = str_replace("#BANNERID", $item["ID_BANNER"], $block);
                $block = str_replace("#PICDATA", $picFile, $block);
                $outData .= $block;
            }
            if ($outData == "") $outData = "<h4>Нет баннеров для модерирования</h4>";

            return $outData;
        }

        private function RooveAnnounce()
        {
            $id = SafeInt(@$_REQUEST["roove"]);
            $state = SafeBool("approove") ? parent::STATE_ACTIVE : parent::STATE_DELETED;

            $SQL = "update ANNOUNCE_DATA set ID_STATE=".$state." where ID_ANNOUNCE=".$id;
            return $this->DL->Execute($SQL);
        }

        private function RooveCompany()
        {
            $id = SafeInt(@$_REQUEST["roove"]);
            $domain = SafeStr(@$_REQUEST["subdomain"]);

            if ($domain == -1) $state = parent::STATE_DELETED;
            if ($domain == -2) $state = parent::STATE_ACTIVE;

            if (isset($state)) {
                $SQL = "update COMPANY_DATA set DOMAIN_MODERATE=null, ID_STATE=".$state.","
                    ." DOMAIN_DATE=null";
            } else {
                $SQL = "update COMPANY_DATA set DOMAIN_MODERATE=null, ID_STATE=".parent::STATE_ACTIVE.","
                    ." DOMAIN_ACTIVE='".$domain."', DOMAIN_DATE=now()";
            }
            $SQL .= " where ID_COMPANY=".$id;
            $this->DL->Execute($SQL);

            RedirectBack();
        }

        private function RooveBanner()
        {
            $id = SafeInt(@$_REQUEST["roove"]);
            $state = SafeBool("approove") ? parent::STATE_ACTIVE : parent::STATE_INCORRECT;

            $SQL = "update BLOCK_BANNER set ID_STATE=".$state." where ID_BANNER=".$id;
            return $this->DL->Execute($SQL);
        }
    }
?>