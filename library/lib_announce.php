<?
class TAnnounce extends TInterface
{
    private $TPL;
    public $MODE;

    /**
     * Определение кода ошибок при действии с объявлениями
     */
    const E_INSERTED     = 1; // Успешное добавление объявления
    const E_UPDATED      = 2; // Успешное обновление объявления
    const E_DELETED      = 5; // Успешное удаление объявления
    const E_INSERTED_REG = 7; // Успешное добавление с быстрой регистрацией
    /**
     * Константизированные ссылки
     */
    const LINK_ERROR = "/announce/&e="; // Страница ошибки
    /**
     * Дополнительное меню для объявлений
     */
    const GRANT_REPORT   = -1; // распечатка для расклейки
    const GRANT_CANVAS   = -2; // разукрашивание объявления
    const GRANT_COMPLNT  = -3; // подача жалобы
    const GRANT_CHANGE   = 1;  // Редактирование объявления
    const GRANT_MESSAGE  = 2;  // отправка сообщения
    const GRANT_DELETE   = 3;  // Удаление объявления
    const GRANT_APROOVE  = 4;  // Апрув модерируемых объявлений
    const GRANT_DEPROOVE = 5;  // Депрув модерируемых объявлений

    /**
     * Блок расширения меню для объявлений. Нужно вынести в отдельный конструктор
     */
    private $MENU_CONTEXT = array(
        self::GRANT_CANVAS   => array("", "<a href='/payment/announce/#ANNOUNCEID'><b>Раскрасить</b></a>"),
        self::GRANT_REPORT   => array("", "<a href='/report/stickera/#ANNOUNCEID' target='blank'>Для расклейки</a>"),
        self::GRANT_MESSAGE  => array("icon-mail", "<a href='/cabuser/mailbox/write&id=#USERID' onclick='return SilentDialog(this, 600, \"Cообщение\");'>Cообщение</a>"),
        self::GRANT_COMPLNT  => array("icon-complaint", "<a href='/cabuser/mailbox/complaint&id=#ANNOUNCEID' onclick='return SilentDialog(this, 400, \"Пожаловаться\");'>Пожаловаться</a>"),
        self::GRANT_CHANGE   => array("icon-edit", "<a href='/announce/edit&id=#ANNOUNCEID'>Редактировать</a>"),
        self::GRANT_DELETE   => array("icon-delete", "<a href='/announce/drop&id=#ANNOUNCEID'>Удалить</a>"),
        self::GRANT_APROOVE  => array("icon-approove", "<a href='javascript:;' onclick='return admkit.approove(\"announce\", #ANNOUNCEID, true);'>Принять</a>"),
        self::GRANT_DEPROOVE => array("icon-deproove", "<a href='javascript:;' onclick='return admkit.deproove(\"announce\", #ANNOUNCEID, true);'>Отклонить</a>")
    );

    /**
     * TKupoAnnounce::__construct()
     *
     * @param mixed $DataClass
     * @return класс связи с БД и код запрошенного действия
     */
    public function __construct()
    {
        parent::__construct();
        $this->TPL = _TEMPLATE."announce/";
        $this->MODE = SafeStr(@$_REQUEST["announce"]);

        if ($this->FL_CAT == 0) $this->FL_CAT = SafeInt(@$_REQUEST["cat"]);
        if ($this->FL_CAT > 0) $this->MODE = "category";
    }

    /**
     * TKupoAnnounce::RenderError()
     *
     * @return
     */
    public function RenderError($content = "")
    {
        if ($this->FL_ERR == parent::E__NOERROR) return $content;

        switch ($this->FL_ERR)
        {
            case (self::E_INSERTED): {
                $content = file_get_contents($this->TPL."error_insert.html");
                $content = str_replace("#ANNOUNCEID", SafeInt(@$_GET["uid"]), $content);
                return parent::RenderErrorTemplate($content, parent::E__SUCCSID,
                    "Объявление добавлено успешно");
            }
            case (self::E_INSERTED_REG): {
                $content = file_get_contents($this->TPL."error_insert_reg.html");
                $content = str_replace("#ANNOUNCEID", SafeInt(@$_GET["uid"]), $content);
                return parent::RenderErrorTemplate($content, parent::E__SUCCSID,
                    "Объявление добавлено успешно. Письмо о регистрации будет отправлено на указанный почтовый ящик");
            }
            case (self::E_UPDATED): {
                $content = file_get_contents($this->TPL."error_update.html");
                $content = str_replace("#ANNOUNCEID", SafeInt(@$_GET["uid"]), $content);
                return parent::RenderErrorTemplate($content, parent::E__SUCCSID,
                    "Объявление сохранено успешно");
            }
            case (self::E_NOTFOUND): {
                $content = file_get_contents($this->TPL."error_found.html");
                return parent::RenderErrorTemplate($content, parent::E__ERRORID,
                    "Запрошенное объявление не найдено");
            }
            case (self::E_NOTPARAM): {
                return parent::RenderErrorTemplate($content, parent::E__ERRORID,
                    "Ошибка при передаче параметров. Повторите операцию, либо сообщите в службу поддержки.");
            }
            case (self::E_DELETED): {
                $content = file_get_contents($this->TPL."error_delete.html");
                return parent::RenderErrorTemplate($content, parent::E__SUCCSID,
                    "Объявление удалено успешно");
            }
        }
        return $content;
    }

    private function CheckEmptyAnnounce($outData)
    {
        if ($outData == "") $outData = "<center><b>Нет доступных объявлений</b></center>";
        return $outData;
    }

    /**
     * TAnnounce::RenderCategoryMain()
     *
     * @return Категории главной страницы
     */
    public function RenderCategoryMain()
    {
        // Выборка ключевых слов с количеством рубрик
        $SQL = "select count(*) / 3, rc.TINDEX from REF_CATEGORY rc "
            ." left outer join REF_CATEGORY rc2 on rc2.ID_PARENT=1 where rc.ID_CATEGORY=1";
        $dump = $this->DL->LFetchRecordRow($SQL);

        $catVector[0] = $catVector[1] = $catVector[2] = "";
        $catCount = $rootCount = 0;
        $catDiv = $dump[0];
        $this->KEYWORDS = $dump[1];

        $SQL = "select rc.ID_CATEGORY, rc.ID_PARENT, rc.CAPTION, ac.ITEMCOUNT"
            ." from REF_CATEGORY rc, COUNT_ANNOUNCE ac, REF_CATEGORY rc2"
            ." where rc.ID_STATE=1 and rc2.ID_STATE=1 and rc2.ID_PARENT in (0,1) and ac.ID_CITY=".$_SESSION["CITY_ID"]
            ." and rc.ID_PARENT=rc2.ID_CATEGORY and ac.ID_CATEGORY=rc.ID_CATEGORY"
            ." order by rc.ID_PARENT, rc.ORDERBY, rc.CAPTION";
        $dump = $this->DL->LFetchRows($SQL);

        $tplBlock = file_get_contents(_TEMPLATE."default/block_category_main.html");
        $tplBlock = str_replace("#CATLINK", "cat", $tplBlock);
        $divCount = count($dump);

        for ($catRoot = 0; $catRoot < $divCount; $catRoot++)
        {
            if ($dump[$catRoot][1] == 1) {
                $catVector[$rootCount] .= str_replace("#GROUPID", $dump[$catRoot][0], $tplBlock);
                $catVector[$rootCount] =  str_replace("#CAPTION", $dump[$catRoot][2], $catVector[$rootCount]);
                $catVector[$rootCount] =  str_replace("#COUNT",   $dump[$catRoot][3], $catVector[$rootCount]);
                $catVector[$rootCount] =  str_replace("#THEMEPIC", _THEME."bluepic", $catVector[$rootCount]);
                $catCount++;

                $catSubVisible = $catSubHidden = "";
                $childCount = 0;
                for ($catChild = 0; $catChild < $divCount - 1; $catChild++)
                {
                    if ($dump[$catChild][1] == $dump[$catRoot][0])
                    {
                        $childCount++;
                        $item = "<a class='item' href='/cat/".$dump[$catChild][0]."'>".$dump[$catChild][2]."</a>";
                        $item .= "<span class='countsub'>&nbsp;(".$dump[$catChild][3].")</span>, ";

                        if ($childCount < 6)
                            $catSubVisible .= $item;
                        else
                            $catSubHidden .= $item;
                    }
                }
                $catVector[$rootCount] = str_replace("#ITEMSHOWED", $catSubVisible, $catVector[$rootCount]);
                $catVector[$rootCount] = str_replace("#ITEMHIDDEN", $catSubHidden,  $catVector[$rootCount]);

                if ($catCount >= $catDiv) {
                    $rootCount++;
                    $catCount = 0;
                }
            }
        }

        // Заполнение шаблона
        $stream = file_get_contents(_TEMPLATE."default/block_category_item.html");
        $stream = str_replace("#TRADENEWS", parent::GetNewsSimple(), $stream);
        $stream = str_replace("#CATEGORY1", $catVector[0], $stream);
        $stream = str_replace("#CATEGORY2", $catVector[1], $stream);
        $stream = str_replace("#CATEGORY3", $catVector[2], $stream);

        return $stream;
    }

    public function RenderCategory()
    {
        $catAction = SafeStr(@$_GET["action"]);
        $catLink = "";
        $catGroup = "";
        $layerCount = 0;

        $SQL = "select LEVEL, CAPTION, TINDEX, ID_CATEGORY from REF_CATEGORY where ID_STATE=".parent::STATE_ACTIVE
            ." and ID_CATEGORY=".$this->FL_CAT;
        $levelStr = $this->DL->LFetchRecordRow($SQL);

        // todo replace
        $_SESSION["cat_level_".$this->FL_CAT] = $level = $levelStr[0];

        $this->TITLE = $levelStr[1];
        $this->KEYWORDS = $levelStr[2];

        $SQL = "select rc.ID_CATEGORY, rc.CAPTION, ac.ITEMCOUNT from REF_CATEGORY rc, COUNT_ANNOUNCE ac "
            ." where rc.ID_STATE=1 and rc.ID_PARENT=".$this->FL_CAT." and ac.ID_CATEGORY=rc.ID_CATEGORY"
            ." and ac.ID_CITY=".$_SESSION["CITY_ID"]." order by rc.ORDERBY, rc.CAPTION";
        $dump = $this->DL->LFetchRows($SQL);

        // По 4-ре блока на страницу
        $catCount = count($dump);
        $catDiv = ceil($catCount / 4);
        // Заполнение блоков
        foreach ($dump as $item) {
            if ($layerCount == 0) {
                $catGroup .= "<div class='catlist-block'><ul>";
            }
            $layerCount++;
            $catGroup .= "<li><a href='/cat/".$item[0]."'>".$item[1]."<div>(".$item[2].")</div></a>";
            if ($layerCount == $catDiv) {
                $catGroup .= "</ul></div>";
                $layerCount = 0;
            }
        }
        if ($layerCount > 0) {
            $catGroup .= "</ul></div>";
        }

        // Получение списка доступных действий для указанной рубрики
        $SQL = "select ra.ACTION, ra.CAPTION from REF_ACTION ra, REF_CATEGORY rc"
            ." where rc.ID_CATEGORY=".$this->FL_CAT." and rc.ACTION like concat('%', ra.ACTION, '%')"
            ." order by ra.ORDERBY desc";
        $dump = $this->DL->LFetchRows($SQL);

        array_unshift($dump, array(0 => "", 1 => "Все"));
        // формирование ссылок возможных действий
        $actions = "";
        foreach ($dump as $item) {
            // Активное действие
            $style = ($item[0] == $catAction) ? "link-hover" : $style = "link";
            // Если действие не задано, ссылка без действия
            if ($item[0] != "") $item[0] = "&action=".$item[0];
            // Генерация ссылок
            $actions .= "<a class='".$style."' href='/cat/".$this->FL_CAT.$item[0]."'>".$item[1]."</a>";
        }

        // Формирование списка типов поиска
        $action_type = array("Все", "Частные", "Компании");
        $action_ctrl = "";
        for ($index = 0; $index < count($action_type); $index++) {
            $action_ctrl .= "<a id='ta_".$index."' class='link";
            if ($index == 0) $action_ctrl .= "-hover";
            $action_ctrl .= "' href='javascript:;' onclick='return AnQueryTyped(".$index.")'>".$action_type[$index]."</a>";
        }

        // Список фильтров действия
        $filter = file_get_contents($this->TPL."category_filter.html");
        $filter = str_replace("#ACTIONLIST", $actions, $filter);
        // Список объявлений
        $announce = $this->RenderAnnounceList();

        // Заполнение шаблона вывода верхней панели
        $stream = file_get_contents($this->TPL."category_list.html");
        $stream = str_replace("#ACTIONS", $actions, $stream);
        $stream = str_replace("#BLOCKDATA", $announce[0], $stream);
        $stream = str_replace("#PAGESELECTOR", $announce[1], $stream);
        $stream = str_replace("#ITEMCOUNT", $announce[2], $stream);
        $stream = str_replace('#BLOCKACTION', $action_ctrl, $stream);
        $stream = str_replace("#CATGROUP", $catGroup, $stream);
        $stream = str_replace("#BLOCKFILTER", $filter, $stream);
        $stream = str_replace("#ITEMPERPAGE", $this->BuildSelectPerPage(), $stream);
        $stream = str_replace("#CATLINK", $this->GetAnnouncePath($levelStr[0], $levelStr[1], "/cat/", false), $stream);

        return $stream;
    }

    public function RenderAnnounceList()
    {
        $page = SafeInt(@$_GET["page"]);
        // Область действия объявления
        $action = SafeStr(@$_GET["action"]);
        // Определение с фотографиями
        $fl_photo = SafeStr(@$_GET["fl_photo"]);
        // Определение с типом поиска
        $fl_type = SafeInt(@$_GET["fl_type"]);
        // Количество объявлений на страницу
        $this->SetItemPerPage();
        // Количество объявлений на страницу, подготовка запроса
        $limit = $this->SelectorPrepare($page, $_SESSION["USER_PERPAGE"]);
        // Определение порядка сортировки
        $order = $this->SelectorSorter("b.POSITION");

        // Самый невьебенный запрос на данный момент
        $SQL = " b.ID_ANNOUNCE, b.CAPTION, b.ID_USER, bu.LOGIN, b.COST, r.LITERAL as CURRENCY, b.DATE_LIFE, bu.ID_TYPE,"
            ." b.VIEWS, rs.CAPTION as CITY, b.COMMENTS, ra.ID_CATEGORY as CATID, ra.CAPTION as CATEGORY, b.ID_GROUP,"
            ." b.IMAGES, b.ID_STATE, bu.ID_ROLE, rac.CAPTION as ACTION from ANNOUNCE_DATA b";
        // Продолжение запроса, фильтр вырезан
        $SQL .= ", USER_DATA bu, REF_CURRENCY r, REF_CATEGORY ra, REF_CITY rs, REF_ACTION rac"
            ." where b.ID_STATE in (1,2,3,4) and bu.ID_STATE=1 and r.ID_STATE=1 and bu.ID_USER=b.ID_USER"
            ." and ra.ID_CATEGORY=b.ID_CATEGORY and r.ID_CURRENCY=b.ID_CURRENCY and rs.ID_CITY=b.ID_CITY"
            ." and rac.ACTION=b.ID_ACTION";
        // Интервал без указания поискового запроса
        if (($this->FL_CAT <= 1) && ($this->FL_TXT == "")) {
            $SQL .= " and (b.DATE_LIFE  > now() - interval 72 hour)";
        } else
        // Поиск по уровням при поиске в не стартовой категории
        if ($this->FL_CAT > 1) {
            $catSQL = "select case when LINK_ID is not null then LINK_ID else ID_CATEGORY end"
                ." from REF_CATEGORY where LEVEL like '".(@$_SESSION["cat_level_".$this->FL_CAT])."%'";
            $SQL .= " and ra.ID_CATEGORY in (".implode(",", $this->DL->LFetchRowsField($catSQL)).")";
        }
        //Определение типа действия, если задано
        $urlPage = "/cat/".$this->FL_CAT;
        // Если указано действие
        if ($action != "") {
            $SQL .= " and b.ID_ACTION='".$action."'";
            $urlPage .= "&action=".$action;
        }
        // Определение с фотографиями
        if ($fl_photo) {
            $SQL .= " and IMAGES>0";
            $urlPage .= "&fl_photo=".$fl_photo;
        }
        // Частные объявления
        if ($fl_type == parent::FILTER_TYPED_USER) {
            $SQL .= " and ID_GROUP=-1";
            $urlPage .= "&fl_type=".$fl_type;
        }
        // Объявления компаний
        if ($fl_type == parent::FILTER_TYPED_COMP) {
            $SQL .= " and ID_GROUP>-1";
            $urlPage .= "&fl_type=".$fl_type;
        }
        // Определение с городом
        if ($_SESSION["CITY_ID"] != 88) {
            $SQL .= " and b.ID_CITY=".$_SESSION["CITY_ID"];
        }
        // Фильтр по заданному тексту
        if ($this->FL_TXT != "") {
            $MorphyText = "match(b.TEXTINDEX) against('".MorphyFullText($this->FL_TXT)."' in boolean mode)";
            $SQL = "select SQL_CALC_FOUND_ROWS ".$MorphyText." as REL,".$SQL." and ".$MorphyText;
            $order = "REL desc, ".$order;
            $urlPage .= "&fl_text=".$this->FL_TXT;
        } else {
            $SQL = "select SQL_CALC_FOUND_ROWS ".$SQL;
        }
        $SQL .= " order by ".$order.$limit;

        $dump = $this->DL->LFetch($SQL);
        // Если объявлений нет, вывод сообщения
        if (count($dump) == 0) {
            $stream = file_get_contents($this->TPL."category_empty.html");
            return array($stream, "", 0);
        }
        // Генерация страничных переходов и загрузка шаблона
        $rowfound = $this->DL->LMaxRows();
        $pageselector = $this->SelectorPage($page, $_SESSION["USER_PERPAGE"], $rowfound, $urlPage);
        $streamcomp = file_get_contents($this->TPL."category_block_comp.html");
        $streamuser = file_get_contents($this->TPL."category_block_user.html");
        // Актуальный пользователь
        $this->SafeUserID($user_field);
        $outData = "";
        // Массивы дополнительных свойств
        $data_favourite = parent::TplFavouriteLoad($dump, $user_field);
        $data_company = parent::TplCompanyLoad($dump);
        $data_style = parent::TplStyleLoad($dump);

        // Перебор собранных объявлений
        foreach($dump as $item)
        {
            $item = parent::SafeAgent($item);
            $item = parent::SafeCompany($item, $data_company);

            if ($item["ID_GROUP"] > -1) {
                $data = $streamcomp;
                $item = parent::SafeUserCompany($item, false);
            } else {
                $data = $streamuser;
                $item = parent::SafeUserCompany($item, true);
            }

            $data = str_replace('#COST', $item["COST"], $data);
            $data = str_replace('#VIEWS', $item["VIEWS"], $data);
            $data = str_replace('#CITY', $item["CITY"], $data);
            $data = str_replace('#CATID', $item["CATID"], $data);
            $data = str_replace('#ACTION', $item["ACTION"], $data);
            $data = str_replace('#CURRENCY', $item["CURRENCY"], $data);
            $data = str_replace('#COMMENTS', $item["COMMENTS"], $data);
            $data = str_replace('#TYPE', $item["USERTYPE"], $data);
            $data = str_replace('#LOGIN', $item["LOGIN"], $data);
            $data = str_replace('#CATEGORY', $item["CATEGORY"], $data);
            $data = str_replace('#ANNOUNCEID', $item["ID_ANNOUNCE"], $data);
            $data = str_replace('#USERLINK', $item["USERLINK"], $data);
            $data = str_replace('#CAPTION', $item["CAPTION"], $data);
            $data = str_replace('#DATELIFE', GetLocalizeDate($item["DATE_LIFE"]), $data);
            $data = str_replace('#IMAGEPATH', parent::GetAnnounceImage($item["ID_ANNOUNCE"], $item["ID_STATE"]), $data);
            $data = str_replace('#FAVOURITE', parent::TplFavouriteReplacement($item["ID_ANNOUNCE"], $data_favourite), $data);
            $data = parent::TplStyleReplacement($item["ID_ANNOUNCE"], $data, $data_style);

            $outData .= $data;
        }
        // Вывод блока и пейджинга на страницу
        return array($outData, $pageselector, $rowfound);
    }

    /**
     * TKupoAnnounce::RenderAnnounce()
     *
     * @return
     */
    public function RenderAnnounce()
    {
        // Код объявления
        $announce_id = SafeInt(@$_GET["announce"]);
        // Определение прав на редактирование документа
        $this->SafeUserID($user_field, "a");
        // Запрос на выборку указанного объявления
        $SQL = "select b.ID_ANNOUNCE, b.ID_USER, b.ID_GUEST, b.CAPTION, b.COST, b.DATE_LIFE, bu.O_VIEWOTHER,"
            ." b.ID_STATE, r.CAPTION as CITY, ra.CAPTION as ACTION, ru.LITERAL as CURRENCY, rc.CAPTION as CATEGORY,"
            ." bu.LOGIN, bu.ID_ROLE, rc.LEVEL, a.ID_ANNOUNCE as FAVOURITE, r.ID_CITY, b.ID_CATEGORY,"
            ." uncompress(b.TEXTDATA) as TEXTDATA, uncompress(b.CONTACT) as CONTACT"
            ." from ANNOUNCE_DATA b left join ANNOUNCE_FAVOURITE a ON b.ID_ANNOUNCE=a.ID_ANNOUNCE and ".$user_field.","
            ." REF_CITY r, REF_ACTION ra, REF_CURRENCY ru, USER_DATA bu, REF_CATEGORY rc"
            ." where r.ID_STATE=1 and ra.ID_STATE=1 and ru.ID_STATE=1 and bu.ID_STATE=1 and b.ID_STATE in (1,2,3,4)"
            ." and r.ID_CITY=b.ID_CITY and ra.ACTION=b.ID_ACTION and ru.ID_CURRENCY=b.ID_CURRENCY and b.ID_GROUP=-1"
            ." and rc.ID_CATEGORY=b.ID_CATEGORY and bu.ID_USER=b.ID_USER and b.ID_ANNOUNCE=".$announce_id;
        $item = $this->DL->LFetchRecord($SQL) or Redirect(self::LINK_ERROR.self::E_NOTFOUND);
        // Обновление количества просмотров
        $SQL = "update ANNOUNCE_DATA set VIEWS=VIEWS+1 where ID_ANNOUNCE=".$announce_id;
        $this->DL->Execute($SQL);
        // Определение модератора
        $item = $this->SafeAgent($item);
        // В заголовок страницы - название объявления
        $this->TITLE = $item["CAPTION"];
        // Текущая категория объявления
        $this->FL_CAT = $item["ID_CATEGORY"];
        // Определение дополнительных пунктов меню
        $menu_context = "";
        // Определение прав над объявлением
        while (list($grant_id, list($icon, $url)) = each($this->MENU_CONTEXT))
        {
            // Гостю нельзя писать сообщение
            if (($grant_id == self::GRANT_MESSAGE)
                && (($item["ID_USER"] == parent::ROLE_GUEST) || ($_SESSION["USER_ROLE"] == parent::ROLE_GUEST))
            ) continue;
            // Апрув / депрув для немодерированных
            if ((($grant_id == self::GRANT_APROOVE) || ($grant_id == self::GRANT_DEPROOVE))
                && $item["ID_STATE"] != parent::STATE_MODER
            ) continue;
            // Проверка прав на действие
            if ($this->CheckGrant($grant_id)) $menu_context .= "<li class='icon-default ".$icon."'>".$url."</li><br />";
        }

        // Включение комментариев для зарегистрированных
        if ($_SESSION["USER_ROLE"] != self::ROLE_GUEST) {
            $comment_dialog = file_get_contents(_TEMPLATE."/announce/viewcomment.html");
        } else {
            $comment_dialog = "";
        }
        // Подгрузка стиля объявления, ради ценника
        $list_style = parent::TplStyleLoad(array($item));

        // Форматирование выходного шаблона
        $stream = file_get_contents($this->TPL."viewdetail.html");
        $stream = str_replace('#USERNAME', $item["LOGIN"], $stream);
        $stream = str_replace('#COST', $item["COST"], $stream);
        $stream = str_replace('#CURRENCY', $item["CURRENCY"], $stream);
        $stream = str_replace('#ACTION', $item["ACTION"], $stream);
        $stream = str_replace('#CITY', $item["CITY"], $stream);
        $stream = str_replace('#COMMENTDIALOG', $comment_dialog, $stream);
        $stream = str_replace('#MENUCONTEXT', $menu_context, $stream);
        $stream = str_replace('#DATELIFE', GetStringTime($item["DATE_LIFE"]), $stream);
        $stream = str_replace('#CAPTION', $item["CAPTION"], $stream);
        $stream = str_replace('#TEXTDATA', BBCodeNativeToHTML($item["TEXTDATA"]), $stream);
        $stream = str_replace('#CONTACT', $this->ContactView($item["CONTACT"]), $stream);
        $stream = str_replace('#IMAGEPATH', $this->GetAnnounceImage($item["ID_ANNOUNCE"], $item["ID_STATE"]), $stream);
        $stream = str_replace('#USERPHOTO', $this->GetPhotoUser($item["ID_USER"]), $stream);
        $stream = str_replace('#CATEGORY', $this->GetAnnouncePath($item["LEVEL"], $item["CATEGORY"], "/cat/", true), $stream);
        $stream = str_replace('#ANNOUNCEID', $item["ID_ANNOUNCE"], $stream);
        $stream = str_replace('#USERID', $item["ID_USER"], $stream);
        $stream = str_replace('#FAVOURITE', parent::TplFavouriteReplacement($announce_id, array($item["FAVOURITE"])), $stream);
        $stream = parent::TplStyleReplacement($announce_id, $stream, $list_style);

        // Выборка комментариев для объявления
        $SQL = "select SQL_CALC_FOUND_ROWS c.TEXTDATA, b.ID_USER, b.LOGIN, c.DATE_LIFE, c.ID_COMMENT"
            ." from ANNOUNCE_COMMENT c, USER_DATA b"
            ." where b.ID_USER=c.ID_TROLL AND c.ID_STATE=1 and b.ID_STATE=1 and c.ID_ANNOUNCE=".$announce_id;
        $dump = $this->DL->LFetch($SQL);
        // Форматирование комментариев
        $stream_comment = file_get_contents(_TEMPLATE."default/comment.html");
        $comments = "";
        foreach ($dump as $comment)
        {
            $data = str_replace("#DATELIFE", GetStringTime($comment["DATE_LIFE"]), $stream_comment);
            $data = str_replace("#TEXTDATA", $comment["TEXTDATA"], $data);
            $data = str_replace("#COMMENTID", $comment["ID_COMMENT"], $data);
            $data = str_replace("#USERID", $comment["ID_USER"], $data);
            $data = str_replace("#LOGIN", $comment["LOGIN"], $data);
            $comments .= $data;
        }
        $stream = str_replace('#COMMENTS', $comments, $stream);
        $stream = str_replace('#COMMENTCOUNT', $this->DL->LMaxRows(), $stream);

        // Подготовка галереи изображений
        $lightbox = "";
        // По две картинки в блок
        $ispare = 0;
        // Объявление прошло модерацию и имеет фотографии
        $imagePath = _ANNOUNCE.$announce_id."/";
        if (($item["ID_STATE"] != self::STATE_MODER) && ($handle = @opendir($imagePath."thumb/")))
        {
            while (false !== ($file = readdir($handle)))
            {
                // Пропуск каталогов
                if (!is_file($imagePath.$file)) continue;
                // Поиск уменьшенных фотографий, на основе которых делается ссылка на большие
                $filethumb = $imagePath."thumb/".$file;
                $filepath = $imagePath.$file;
                // Новый блок
                $lightbox .= "<li><a rel='prettyPhoto[mixed]' href='/".$filepath."'><img src='/".$filethumb."'"
                    ." width='".$this->DC["IMAGE_ICONSIZE"]."' height='".$this->DC["IMAGE_ICONSIZE"]."' /></a></li>";
            }
        }
        $stream = str_replace('#LIGHTBOX', $lightbox, $stream);

        // Выборка дополнительных объявлений
        $multiAnnounce = array();
        $limitMax = $this->DC["LIMIT_ANNOUNCEEXT"];
        // Определение роли пользователя
        if ($item["ID_USER"] != self::ROLE_GUEST) {
            $SQLUser = "ID_USER=".$item["ID_USER"];
        } else {
            $SQLUser = "ID_GUEST=".$item["ID_GUEST"];
        }
        // Исключение текущего объявления из поиска
        array_push($multiAnnounce, $announce_id);

        $dumpCount = 0;
        $outData = "";
        // Разрешил ли пользователь показывать свои объявления
        if ($item["O_VIEWOTHER"] == 0) {
            // выборка объявлений пользователя, с полным учетом
            if ($dumpCount < $limitMax)
            {
                $limitCount = $limitMax - $dumpCount;
                $SQL = "select ID_ANNOUNCE, ad.CAPTION, COST, LITERAL, ad.ID_STATE from ANNOUNCE_DATA ad, REF_CURRENCY rc"
                    ." where ad.ID_STATE in (1,2,4) and rc.ID_CURRENCY=ad.ID_CURRENCY and rc.ID_STATE=1 and ad.ID_GROUP=-1 and"
                    ." ID_CATEGORY=".$item["ID_CATEGORY"]." and ID_ANNOUNCE not in (".implode(",", $multiAnnounce).") and"
                    ." ID_CITY=".$item["ID_CITY"]." and ".$SQLUser." limit 0, ".$limitCount;
                $outData .= $this->RenderExtendedAnnounce($SQL, $multiAnnounce, $dumpCount);
            }
            // выборка объявлений пользователя, если не хватает, без учета группы
            if ($dumpCount < $limitMax)
            {
                $limitCount = $limitMax - $dumpCount;
                $SQL = "select ID_ANNOUNCE, ad.CAPTION, COST, LITERAL, ad.ID_STATE from ANNOUNCE_DATA ad, REF_CURRENCY rc"
                    ." where ad.ID_STATE in (1,2,4) and rc.ID_CURRENCY=ad.ID_CURRENCY and rc.ID_STATE=1 and ad.ID_GROUP=-1 and"
                    ." ID_ANNOUNCE not in (".implode(",", $multiAnnounce).") and ID_CITY=".$item["ID_CITY"]." and ".$SQLUser
                    ." limit 0, ".$limitCount;
                $outData .= $this->RenderExtendedAnnounce($SQL, $multiAnnounce, $dumpCount);
            }
            // выборка объявлений пользователя, если не хватает, без учета группы и города
            if ($dumpCount < $limitMax)
            {
                $limitCount = $limitMax - $dumpCount;
                $SQL = "select ID_ANNOUNCE, ad.CAPTION, COST, LITERAL, ad.ID_STATE from ANNOUNCE_DATA ad, REF_CURRENCY rc"
                    ." where ad.ID_STATE in (1,2,4) and rc.ID_CURRENCY=ad.ID_CURRENCY and rc.ID_STATE=1 and ad.ID_GROUP=-1 and"
                    ." ID_ANNOUNCE not in (".implode(",", $multiAnnounce).") and ".$SQLUser
                    ." limit 0, ".$limitCount;
                $outData .= $this->RenderExtendedAnnounce($SQL, $multiAnnounce, $dumpCount);
            }
        }
        $stream = str_replace('#SIMILATE', self::CheckEmptyAnnounce($outData), $stream);

        $dumpCount = 0;
        $outData = "";
        // выборка похожих объявлений
        if ($dumpCount < $limitMax)
        {
            $limitCount = $limitMax - $dumpCount;
            $SQL =  "select ID_ANNOUNCE, ad.CAPTION, COST, LITERAL, ad.ID_STATE from ANNOUNCE_DATA ad, REF_CURRENCY rc"
                ." where ad.ID_STATE in (1,2,3,4) and rc.ID_CURRENCY=ad.ID_CURRENCY and rc.ID_STATE=1 and ad.ID_GROUP=-1 and"
                ." ID_CATEGORY=".$item["ID_CATEGORY"]." and ID_ANNOUNCE not in (".implode(",", $multiAnnounce).")"
                ." and ID_CITY=".$item["ID_CITY"]." and match (ad.TEXTINDEX) against ('".MorphyText($item["CAPTION"])."' IN BOOLEAN MODE)"
                ." limit 0, ".$this->DC["LIMIT_ANNOUNCEEXT"];
            $outData .= $this->RenderExtendedAnnounce($SQL, $multiAnnounce, $dumpCount);
        }

        // выборка похожих объявлений, если не хватает, без учета группы и города
        if ($dumpCount < $limitMax)
        {
            $limitCount = $limitMax - $dumpCount;
            $SQL =  "select ID_ANNOUNCE, ad.CAPTION, COST, LITERAL, ad.ID_STATE from ANNOUNCE_DATA ad, REF_CURRENCY rc"
                ." where ad.ID_STATE in (1,2,3,4) and rc.ID_CURRENCY=ad.ID_CURRENCY and rc.ID_STATE=1 and ad.ID_GROUP=-1 and"
                ." ID_ANNOUNCE not in (".implode(",", $multiAnnounce).")"
                ." and ID_CITY=".$item["ID_CITY"]." and match (ad.TEXTINDEX) against ('".MorphyText($item["CAPTION"])."' IN BOOLEAN MODE)"
                ." limit 0, ".$limitCount;
            $outData .= $this->RenderExtendedAnnounce($SQL, $multiAnnounce, $dumpCount);
        }
        // выборка похожих объявлений, если не хватает, без учета группы и города
        if ($dumpCount < $limitMax)
        {
            $limitCount = $limitMax - $dumpCount;
            $SQL =  "select ID_ANNOUNCE, ad.CAPTION, COST, LITERAL, ad.ID_STATE from ANNOUNCE_DATA ad, REF_CURRENCY rc"
                ." where ad.ID_STATE in (1,2,3,4) and rc.ID_CURRENCY=ad.ID_CURRENCY and rc.ID_STATE=1 and ad.ID_GROUP=-1 and"
                ." ID_ANNOUNCE not in (".implode(",", $multiAnnounce).")"
                ." and match (ad.TEXTINDEX) against ('".MorphyText($item["CAPTION"])."' IN BOOLEAN MODE)"
                ." limit 0, ".$limitCount;
            $outData .= $this->RenderExtendedAnnounce($SQL, $multiAnnounce, $dumpCount);
        }
        $stream = str_replace('#MATCHED', self::CheckEmptyAnnounce($outData), $stream);

        return $stream;
    }

    /**
     * TKupoAnnounce::RenderCreate()
     *
     * @return шаблон создания объявления
     */
    public function RenderCreate()
    {
        if ($this->SafeUser($user_id)) {
            $SQL = "select uncompress(CONTACT) as CONTACT, ID_CITY from USER_DATA"
                ." where ID_STATE=1 and ID_USER=".$user_id;
            $item = $this->DL->LFetchRecordRow($SQL);
            $contacts = $item[0];
            // Установка города, закрепленного под пользователем
            if ($_SESSION["CITY_ID"] != 88) {
                $city_id = $_SESSION["CITY_ID"];
            } else {
                $city_id = $item[1];
            }
            $fastreg = "none";
        } else {
            $contacts = null;
            $city_id = $_SESSION["CITY_ID"];
            $fastreg = "block";
        }
        // Заполняемые списки
        $list_curr = $this->BuildSelectCash(parent::CashCurrency(), 1);
        $list_city = $this->BuildSelectCash(parent::CashCity(), $city_id, false, 0);
        $list_caty = $this->BuildSelectCash(parent::CashCategory(), -1, false, 0);

        // Вывод шаблона добавления объявления
        $stream = file_get_contents($this->TPL."edit_create.html");
        $stream = str_replace("#CITY", $list_city, $stream);
        $stream = str_replace("#FASTREG", $fastreg, $stream);
        $stream = str_replace("#DIVISION", $list_caty, $stream);
        $stream = str_replace("#CURRENCY", $list_curr, $stream);
        $stream = str_replace("#JSONPHOTO", json_encode(array()), $stream);
        $stream = str_replace("#JSONTHUMB", json_encode(array()), $stream);
        $stream = str_replace("#UPLOADID", $this->GetUploadID(), $stream);
        $stream = str_replace("#CONTACT", $this->ContactRender($contacts), $stream);
        $stream = str_replace("#THUMBSIZE", $this->DC["IMAGE_THUMBSIZE"], $stream);
        $stream = str_replace("#PHOTOSIZE", $this->DC["IMAGE_PHOTOSIZE"], $stream);
        $stream = str_replace("#PHOTOCOUNT", $this->GetPhotoMaxCount(0), $stream);
        $stream = str_replace("#AJAXPICTPL", $this->BuildTPLajaxPicture(), $stream);

        return $this->RenderError($stream);
    }

    /**
     * TKupoAnnounce::RenderEdit()
     *
     * @return шаблон редактирования объявления
     */
    public function RenderEdit()
    {
        // Код редактируемого объявления
        $announce_id = SafeInt(@$_GET["id"]);
        // Запрос потверждающий права на управление
        $SQL = "select b.ID_ANNOUNCE, b.ID_CATEGORY, b.ID_ACTION, b.CAPTION, b.ID_CURRENCY, "
            ." b.COST, b.IMAGES, rr.ID_CITY, rc.LEVEL,"
            ." uncompress(b.TEXTDATA) as TEXTDATA, uncompress(b.CONTACT) as CONTACT"
            ." from ANNOUNCE_DATA b, REF_CATEGORY rc, REF_CITY rr"
            ." where b.ID_STATE in (1,2,3,4) and rc.ID_CATEGORY=b.ID_CATEGORY and rc.ID_STATE=1"
            ." and b.ID_CITY=rr.ID_CITY and b.ID_ANNOUNCE=".$announce_id;
        // Определение прав на редактирование документа
        if (!$this->CheckGrant(self::GRANT_CHANGE)) {
            $this->SafeUserID($user_field);
            $SQL .= " and ".$user_field;
        }
        $item = $this->DL->LFetchRecord($SQL) or Redirect(self::LINK_ERROR.self::E_NOTFOUND);

        // Вычисление доступных действий
        $SQL_Actn = "select ra.ACTION, ra.CAPTION from REF_ACTION ra, REF_CATEGORY rc"
        ." where rc.ID_CATEGORY=".$item["ID_CATEGORY"]." and rc.ACTION like concat('%', ra.ACTION, '%')"
        ." order by ra.ORDERBY asc";
        $list_actn = $this->BuildSelect($SQL_Actn, $item["ID_ACTION"]);
        $list_curr = $this->BuildSelectCash(parent::CashCurrency(), $item["ID_CURRENCY"]);
        $list_city = $this->BuildSelectCash(parent::CashCity(), $item["ID_CITY"], false, 0);
        $list_cat = $this->TplCategoryLoad($item["LEVEL"]);
        // Инициализация аплоада
        $uid = $this->GetUploadID($announce_id);
        $json = $this->UploadFiles($announce_id, $uid);

        // Вывод шаблона редактирования документа
        $stream = file_get_contents($this->TPL."edit_update.html");
        $stream = str_replace("#COST", $item["COST"], $stream);
        $stream = str_replace("#CITY", $list_city, $stream);
        $stream = str_replace("#DIVISION", $list_cat, $stream);
        $stream = str_replace("#TEXTDATA", ($item["TEXTDATA"]), $stream);
        $stream = str_replace("#CAPTION", ($item["CAPTION"]), $stream);
        $stream = str_replace("#ACTION", $list_actn, $stream);
        $stream = str_replace("#CURRENCY", $list_curr, $stream);
        $stream = str_replace("#ANNOUNCEID", $announce_id, $stream);
        $stream = str_replace("#JSONPHOTO", json_encode($json[0]), $stream);
        $stream = str_replace("#JSONTHUMB", json_encode($json[1]), $stream);
        $stream = str_replace("#THUMBSIZE", $this->DC["IMAGE_THUMBSIZE"], $stream);
        $stream = str_replace("#PHOTOSIZE", $this->DC["IMAGE_PHOTOSIZE"], $stream);
        $stream = str_replace("#UPLOADID", $uid, $stream);
        $stream = str_replace("#PHOTOCOUNT", $this->GetPhotoMaxCount($item["IMAGES"]), $stream);
        $stream = str_replace("#CONTACT", $this->ContactRender($item["CONTACT"]), $stream);
        $stream = str_replace("#AJAXPICTPL", $this->BuildTPLajaxPicture(), $stream);

        return $this->RenderError($stream);
    }

    /**
     * TKupoAnnounce::RenderDelete()
     *
     * @return шаблон удаления объявления
     */
    public function RenderDelete()
    {
        // Код удаляемого объявления
        $announce_id = SafeInt(@$_GET["id"]);
        // Запрос потверждающий права на управление
        $SQL = "select ID_ANNOUNCE, CAPTION, uncompress(TEXTDATA) as TEXTDATA, COST from ANNOUNCE_DATA b"
            ." where ID_STATE in (1,2,3,4) and ID_ANNOUNCE=".$announce_id;
        // Определение прав на удаление объявления
        if (!$this->CheckGrant(self::GRANT_DELETE)) {
            $this->SafeUserID($user_field);
            $SQL .= " and ".$user_field;
        }
        $item = $this->DL->LFetchRecord($SQL) or Redirect(self::LINK_ERROR.self::E_NOTFOUND);

        $stream = file_get_contents($this->TPL."edit_delete.html");
        $stream = str_replace("#TEXTDATA", BBCodeToHTML($item["TEXTDATA"]), $stream);
        $stream = str_replace("#CAPTION", $item["CAPTION"], $stream);
        $stream = str_replace("#COST", $item["COST"], $stream);
        $stream = str_replace("#ANNOUNCEID", $item["ID_ANNOUNCE"], $stream);

        return $this->RenderError($stream);
    }

    /**
     * TKupoAnnounce::Insert()
     *
     * @return добавление объявления
     */
    public function Insert($directed = true)
    {
        // Проверка капчи
        if (!GetCaptchaBoolVerify()) RedirectError(self::E_NOTPARAM);

        // Принимаемые параметры, список дополняется
        $category = SafeInt(@$_POST["category"]);
        $currency = SafeInt(@$_POST["currency"]);
        $action = SafeStr(@$_POST["action"]);
        $cost = SafeInt(@$_POST["cost"]);
        $city = SafeInt(@$_POST["city"]);
        $caption = SafeStr(@$_POST["caption"]);
        $textdata = SafeStr(@$_POST["textdata"]);
        $mail = SafeStr(@$_POST["mail"]);

        // Проверка на конечный каталог
        parent::AnnounceCheckPath($category);
        // Договорная цена при неуказании стоимости
        parent::AnnounceCheckCost($cost, $currency);
        // Загрузка учетной записи
        $isGuest = $this->SafeUserRegisterEx($user_id, $guest_id);
        // Определение модерирования объявления
        $id_state = !$directed ? parent::STATE_PARSER : parent::STATE_ACTIVE; //todo parent::STATE_MODER;

        // Добавление объявления
        $SQL = "insert into ANNOUNCE_DATA (ID_USER, ID_GUEST, ID_CATEGORY, ID_CITY, ID_ACTION, CAPTION,"
            ." COST, ID_CURRENCY, ID_STATE, TEXTINDEX, TEXTDATA, CONTACT) values"
            ."(".$user_id.", ".$guest_id.", ".$category.", ".$city.", '".$action."', '".$caption
            ."', ".$cost.", ".$currency.", ".$id_state.", '".MorphyText($caption." ".$textdata)."', compress('".$textdata."'),"
            ." compress('".$this->ContactUpload()."'));";
        $this->DL->Execute($SQL);
        $announce_id = $this->DL->PrimaryID();

        // Инкремент количества объявлений категории
        $this->ToggleAnnounceCount(parent::AC_INCREMENT, $category, $city);
        // Инкремент количества объявлений пользователя
        $this->ToggleAnnounceUser(parent::AC_CREATE, $user_id, $guest_id, $announce_id);
        // Загрузка файлов
        $photoCount = self::DownloadFiles($announce_id);
        // Загрузка параметров
        self::UploadParam($announce_id);
        // Обновление информации о количестве изображений в базе
        $SQL = "update ANNOUNCE_DATA set IMAGES=".$photoCount.", POSITION=".$this->NextCounter("CNT_POSITION_ANN", $announce_id)
            ." where ID_ANNOUNCE=".$announce_id;
        $this->DL->Execute($SQL);

        // Быстрая регистрация пользователя
        if ($directed && CheckMail($mail) && ($_SESSION["USER_ROLE"] == parent::ROLE_GUEST))
        {
            $passkey = $this->SafeGuestCookie($guest_id);
            if ($passkey) {
                $link = $_SERVER["HTTP_HOST"]."/mixed/fastreg&pk=".$passkey;

                /* todo refactor */
                include(_LIBRARY."lib_email.php");
                $Email = new TEmail();
                $Email->MailFastRegister($mail, $link);

                $SQL = "update USER_GUEST set EMAIL='".$mail."', PWD='".$passkey."' where ID_GUEST=".$guest_id;
                $this->DL->Execute($SQL);

                Redirect(self::LINK_ERROR.self::E_INSERTED_REG."&uid=".$announce_id);
            }
        }

        if ($directed)
            Redirect(self::LINK_ERROR.self::E_INSERTED."&uid=".$announce_id);
    }

    /**
     * TKupoAnnounce::Update()
     *
     * @return обновление объявления
     */
    public function Update()
    {
        // Проверка капчи
        if (!GetCaptchaBoolVerify()) RedirectError(self::E_NOTPARAM);

        // Принимаемые параметры, список дополняется
        $announce_id = SafeInt(@$_POST["id"]);
        $currency = SafeInt(@$_POST["currency"]);
        $action = SafeStr(@$_POST["action"]);
        $category = SafeInt(@$_POST["category"]);
        $city = SafeInt(@$_POST["city"]);
        $cost = SafeInt(@$_POST["cost"]);
        $caption = SafeStr(@$_POST["caption"]);
        $textdata = SafeStr(@$_POST["textdata"]);

        // Проверка на конечный каталог
        parent::AnnounceCheckPath($category);
        // Договорная цена при неуказании стоимости
        parent::AnnounceCheckCost($cost, $currency);
        // Определение прав на обновление объявления
        if (!$this->CheckGrant(self::GRANT_CHANGE)) {
            $this->SafeUserID($SQLuser);
            $SQLuser = " and ".$SQLuser;
        } else {
            $SQLuser = "";
        }

        // Запрос потверждающий права на управление
        $SQL = "select b.ID_ANNOUNCE, b.IMAGES, b.ID_CATEGORY, b.ID_CITY, b.ID_USER, b.ID_GUEST"
            ." from ANNOUNCE_DATA b, REF_CATEGORY rc"
            ." where b.ID_STATE in (1,2,3,4) and rc.ID_STATE=1 and b.ID_GROUP=-1"
            ." and rc.ID_CATEGORY=b.ID_CATEGORY and b.ID_ANNOUNCE=".$announce_id.$SQLuser;
        $item = $this->DL->LFetchRecord($SQL) or Redirect(self::LINK_ERROR.self::E_NOTFOUND);

        // Очистка аттрибутов
        $this->ClearParam($announce_id);
        // Очистка изображений
        $this->ClearFiles(_ANNOUNCE.$announce_id);
        // Загрузка новых изображений
        $photoCount = $this->DownloadFiles($announce_id);
        // Загрузка новых параметров
        $this->UploadParam($announce_id);

        // Обновление объявления
        $SQL = "update ANNOUNCE_DATA b set ID_ACTION='".$action."', CAPTION='".$caption."',"
            ." COST=".$cost.", ID_CURRENCY=".$currency.", IMAGES=".$photoCount.","
            ." TEXTINDEX='".MorphyText($caption." ".$textdata)."', ID_CATEGORY=".$category.", ID_CITY=".$city.","
            ." TEXTDATA=compress('".$textdata."'), CONTACT=compress('".$this->ContactUpload()."')"
            ." where ID_STATE in (1,2,3,4) and ID_ANNOUNCE=".$announce_id.$SQLuser;
        $this->DL->Execute($SQL);
        // Инкремент количества объявлений старой категории
        $this->ToggleAnnounceCount(parent::AC_DECREMENT, $item["ID_CATEGORY"], $item["ID_CITY"]);
        // Декремент количества объявлений новой категории
        $this->ToggleAnnounceCount(parent::AC_INCREMENT, $category, $city);

        Redirect(self::LINK_ERROR.self::E_UPDATED."&uid=".$announce_id);
    }

    public function Delete()
    {
        // Проверка капчи
        if (!GetCaptchaBoolVerify()) RedirectError(self::E_NOTPARAM);

        // Принимаемые параметры, список дополняется
        $announce_id = SafeInt(@$_POST["id"]);

        // Определение прав на удаление объявления
        if (!$this->CheckGrant(self::GRANT_DELETE)) {
            $this->SafeUserID($SQLuser);
            $SQLuser = " and ".$SQLuser;
        } else {
            $SQLuser = "";
        }

        // Запрос потверждающий права на управление
        $SQL = "select b.ID_ANNOUNCE, b.ID_CITY, b.ID_USER, b.ID_GUEST, b.ID_CATEGORY"
            ." from ANNOUNCE_DATA b, REF_CATEGORY rc"
            ." where b.ID_STATE in (1,2,3,4) and rc.ID_STATE=1 and rc.ID_CATEGORY=b.ID_CATEGORY"
            ." and b.ID_GROUP=-1 and b.ID_ANNOUNCE=".$announce_id.$SQLuser;
        $item = $this->DL->LFetchRecord($SQL) or Redirect(self::LINK_ERROR.self::E_NOTFOUND);

        $SQL = "update ANNOUNCE_DATA b set b.ID_STATE=".parent::STATE_DELETED." where b.ID_ANNOUNCE=".$announce_id.$SQLuser;
        $this->DL->Execute($SQL);

        // Декремент количества объявлений категории
        $this->ToggleAnnounceCount(parent::AC_DECREMENT, $item["ID_CATEGORY"], $item["ID_CITY"]);
        // Удаление ссылок на объявление
        $this->ToggleAnnounceUser(parent::AC_DELETE, $item["ID_USER"], $item["ID_GUEST"], $announce_id);
        // Удаление параметров
        $this->ClearParam($announce_id);
        // Удаление фотографий
        $this->ClearFiles(_ANNOUNCE.$announce_id);

        Redirect(self::LINK_ERROR.self::E_DELETED);
    }

    public function Comment()
    {
        // Гости не имеют права комментировать
        if (!$this->SafeUser($user_id)) RedirectRegister();

        // Параметры комментария
        $announce_id = SafeInt(@$_POST["id"]);
        $comment = SafeStr(@$_POST["textdata"]);
        if (!TextRange($comment, 10)) return false;

        // Поиск объявления и данных пользователя
        $SQL = "select ad.ID_ANNOUNCE, ad.CAPTION, ad.ID_USER, ud.EMAIL, ud.LOGIN from ANNOUNCE_DATA ad, USER_DATA ud"
            ." where ad.ID_STATE in (1,2,3,4) and ud.ID_USER=ad.ID_USER and ad.ID_ANNOUNCE=".$announce_id;
        $item = $this->DL->LFetchRecord($SQL) or RedirectBack();
        // Добавление комментария к объявлению
        $SQL = "insert into ANNOUNCE_COMMENT (ID_ANNOUNCE, ID_TROLL, TEXTDATA) values (".$announce_id.", ".$user_id.", '".$comment."')";
        $this->DL->Execute($SQL);
        $comment_id = $this->DL->PrimaryID();
        // Увеличение количества комментариев для объявления
        $SQL = "update ANNOUNCE_DATA set COMMENTS = COMMENTS + 1 where ID_STATE in (1,2,3,4) and ID_ANNOUNCE=".$announce_id;
        $this->DL->Execute($SQL);
        // В стэк отправку письма о комментарии, пересмотреть механизм в сторону подписки
        // todo refactor
        parent::FastLibMsg()->MailPostLocal($user_id, $this->DC["TEXT_NEWCOMMENT"],
            parent::FastLibMail()->MailComment($item, $user_id, $comment_id, $comment));

        return RedirectBack();
    }
}
?>