

    /* todo Quick Filters
    /*todo// Генерация поля аттрибутов категории
    if (isset($_GET["field_extfield"])) {
        echo $ObjectX->RenderExtField();
    } else

    // Генерация поля свойств аттрибута
    if (isset($_GET["field_extfilter"])) {
        echo $ObjectX->RenderExtFilter();
    } else*/


        // Инициализация типизированных полей, параметры объявления
        $ajcat = $this->RenderTypedField($item["ID_CATEGORY"], $announce_id);


        // Инициализация типизированных полей, параметры объявления
        /*$ajcat = $this->RenderTypedField($item["ID_CATEGORY"], $announce_id);
        $stream = str_replace("#EXTPARAM", $ajcat, $stream);
        */

 else {
            //$('#extfields').load('/ajax/field_paramgroup&id=' + Object.value);
        }


        // Обработка параметров расширенного фильтра
        /*$filter = array();
        reset($_REQUEST);
        while (list($key, $val) = each($_REQUEST)) {
            // Чекбоксы
            if (preg_match('#c_(.*)#', $key, $matches)) {
                if ($matches[1] != "") {
                    if (!isset($filter[$val])) $filter[$val] = array();
                    array_push($filter[$val], $matches[1]);
                }
            }
            // Списки
            if (preg_match('#p_(.*)#', $key, $matches)) {
                if ($val != "") {
                    if (!isset($filter[$matches[1]])) $filter[$matches[1]] = array();
                    array_push($filter[$matches[1]], $val);
                }
            }
        }
        // Формирование запроса на выборку с расширенным фильтром, модель ENV
        if (count($filter) > 0) {
            $SQL .= " join (SELECT bp.ID_ANNOUNCE FROM BLOCK_PARAM bp WHERE 1<>1";
            $paramCount = 0;
            reset($filter);
            while (list($key, $vector) = each($filter)) {
                $SQL .= " or (bp.ID_PARAM_GROUP=".$key." AND bp.VALUE IN (-1";
                $paramCount++;

                while (list($subkey, $value) = each($vector)) {
                    if ($value == "on") $value = 1;
                    $SQL .= ",".$value;
                }
                $SQL .= "))";
            }
            $SQL .= " group by bp.ID_ANNOUNCE having count(bp.ID_ANNOUNCE)=".$paramCount.") OPTIONS on b.ID_ANNOUNCE=OPTIONS.ID_ANNOUNCE";
        }*/


    /* todo фильтры
    public function RenderExt1Field()
    {
        // Код категории
        $group_id = SafeInt(@$_GET["id"]);

        $SQL = "select ID_PARAM, CAPTION from REF_PARAM where ID_PARENT=".$group_id;
        // Получение набора опций для указанного запроса с добавление пустой пары опций
        $stream = GetSelectOption($SQL, -1, true);

        return $stream;
    }
    public function RenderExt1Filter()
    {
        $parent_id = SafeInt(@$_GET["id"]);
        $group_id = SafeInt(@$_GET["group"]);

        $SQL = "select ID_PARAM, CAPTION from REF_PARAM where ID_PARENT=".$parent_id;
        $dump = $this->DL->LFetch($SQL);

        $stream = "";
        foreach ($dump as $item) {
            $checkID = "c_".$item["ID_PARAM"];
            $stream .= "<input type='checkbox' id='".$checkID."' name='".$checkID."' "
                ."value=".$group_id." onchange='return SetPage(0);'>"
                ."<label for='".$checkID."'>".$item["CAPTION"]."</label><br />";
        }

        return $stream;
    }
    public function RenderParamGroup()
    {
        $category_id = SafeInt(@$_GET["id"]);
        $stream = "";

        // Выбор группы параметров и их зависимостей
        $SQL = "select ID_PARAM_GROUP, CAPTION, ID_TYPE from REF_PARAM_GROUP"
            ." where ID_CATEGORY=".$category_id." order by ID_TYPE";
        $dump = $this->DL->LFetch($SQL);

        foreach ($dump as $item)
        {
            $fieldType = $item["ID_FIELD_TYPE"];
            $paramGroup = $item["ID_PARAM_GROUP"];
            $caption = $item["CAPTION"];
            $SQL = "select ID_PARAM, CAPTION from REF_PARAM where ID_PARAM_GROUP=".$paramGroup;
            // Тип поля - список с дочерними объектами
            if ($fieldType == parent::ATTR_FIELD_LINK) {
                $stream .= $caption." <select name='p_".$paramGroup."' onchange='LoadExtField(this, \"".$item["ID_CHILD"]."\")'>";
                $stream .= $this->BuildSelect($SQL, -1, " ");
                $stream .= "</select>";
            } else
            // Тип поля - перечисление элементов
            if ($fieldType == parent::ATTR_FIELD_LIST) {
                $stream .= $caption." <select name='p_".$paramGroup."'>";
                $stream .= $this->BuildSelect($SQL, -1, " ");
                $stream .= "</select>";
            } else
            // Тип поля - дочерний объект
            if ($fieldType == parent::ATTR_FIELD_CHILD) {
                $stream .= $caption." <select name='p_".$paramGroup."' id='p_".$paramGroup."' ></select>";
            } else
            // Тип поля - логический аттрибут
            if ($fieldType == parent::ATTR_FIELD_CHECKBOX) {
                $stream .= "<input type='checkbox' name='p_".$paramGroup."'> ".$caption;
            }
        }

        return $stream;
    }*/


        // TODO Фильтры выборка доступных для объявления
        /*$SQL = "select b.ID_PARAM_GROUP, r.CAPTION, r.ID_FIELD_TYPE, rp.ID_PARAM, rp.CAPTION as VALUE"
            ." from BLOCK_PARAM b, REF_PARAM_GROUP r, REF_PARAM rp"
            ." where r.ID_PARAM_GROUP=b.ID_PARAM_GROUP and b.VALUE=rp.ID_PARAM"
            ." and r.ID_STATE=1 and b.ID_ANNOUNCE=".$announce_id
            ." ORDER BY r.ID_FIELD_TYPE";
        $dump = $this->DL->LFetch($SQL);
        // TODO Фильтры форматирование
        $fieldparam = "";
        foreach ($dump as $field) {
            if ($field["ID_FIELD_TYPE"] != self::ATTR_FIELD_CHECKBOX)
              $fieldparam .= "<li>".$field["CAPTION"].":&nbsp;".$field["VALUE"]."</li>";

            if ($field["ID_FIELD_TYPE"] == self::ATTR_FIELD_CHECKBOX)
              $fieldparam .= "<li>".$field["CAPTION"]."</li>";
        }
        $stream = str_replace('#FIELDPARAM', $fieldparam, $stream);*/
