<?
//todo
    include(_ENGINE."morphy/common.php");

    class MorphBasic
    {
        private $morph;
        private $words;

        public function __construct()
        {
            $this->morph = new phpMorphy(
                new phpMorphy_FilesBundle(_ENGINE."morphy/dicts", "ru_ru"),
                    array('storage' => PHPMORPHY_STORAGE_FILE,
                        'with_gramtab' => false,
                        'predict_by_suffix' => true,
                        'predict_by_db' => true
                    )
            );
        }

        private function MorphBase($text)
        {
            $text = iconv("utf-8", "windows-1251", mb_strtoupper($text, "utf-8"));
            $words = preg_replace('#\[.*\]#isU', '', $text);
            $words = preg_split('#\s|[,.:;!?"\'()]#', $words, -1, PREG_SPLIT_NO_EMPTY);

            $bulk_words = array();
            foreach ($words as $v)
                if (strlen($v) > 3) $bulk_words[] = $v;

            return $this->morph->getBaseForm($bulk_words);
        }

        public function MorphText($text)
        {
            $base_form = self::MorphBase($text);

            $fullList = array();
            if (is_array($base_form) && count($base_form))
            {
                foreach ($base_form as $k => $v)
                {
                    if (is_array($v))
                        foreach ($v as $v1)
                            $fullList[$v1] = 1;
                    else {
                        $fullList[$k] = 1;
                    }
                }
            }

            return iconv("windows-1251", "utf-8", join(' ', array_keys($fullList)));
        }

        public function MorphFullText($text)
        {
            $base_form = self::MorphBase($text);

            $fullText = "";
            if (is_array($base_form) && count($base_form))
            {
                foreach ($base_form as $k => $v)
                {
                    if (is_array($v))
                    {
                        if (count($v) > 1) {
                            $fullText .= "<(";
                                foreach ($v as $v1) $fullText .= $v1." ";
                            $fullText .= ")";
                        } else {
                            $fullText .= " ".$v[0];
                        }
                    }
                    else {
                        $fullText .= " ".$k;
                    }
                }
            }

            return iconv("windows-1251", "utf-8", $fullText);
        }
    }