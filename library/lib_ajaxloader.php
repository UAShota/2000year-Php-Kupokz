<?
class TAjax extends TInterface
{
    public $MODE;

    public function __construct()
    {
        parent::__construct();
        $this->MODE = SafeStr($_REQUEST["ajax"]);
    }

    /**
     *
     * TAjax::CheckLogin()
     *
     * @return Проверка логина на уникальность
     */
    public function CheckLogin()
    {
        // Запрошенный логин
        $loginField = SafeStr(@$_GET["fieldId"]);
        $loginValue = SafeStr(@$_GET["fieldValue"]);
        // Поиск логина
        $SQL = "select ID_USER from USER_DATA where LOGIN='".$loginValue."'";
        $item = $this->DL->LFetchField($SQL);
        // Возврат в понятном для валидатора формате
        if (!$item)
            return array($loginField, true, "Указанный логин свободен");
        else
            return array($loginField, false, "Указанный логин уже занят");
    }

    /**
     * TAjax::CheckEmail()
     *
     * @return Проверка почтового ящика на уникальность
     */
    public function CheckEmail()
    {
        // Запрошенный почтовый ящик
        $emailField = SafeStr(@$_REQUEST["fieldId"]);
        $emailValue = SafeStr(@$_REQUEST["fieldValue"]);
        // Поиск почтовых ящиков
        $SQL = "select EMAIL from USER_DATA where EMAIL='".$emailValue."'";
        $item = $this->DL->LFetchField($SQL);

        // Возврат в понятном для валидатора формате
        if (!$item)
            return array($emailField, true, "Указанный почтовый ящик свободен");
        else
            return array($emailField, false, "Указанный почтовый ящик занят");
    }

    /**
     * TAjax::CheckCaptcha()
     *
     * @return Проверка капчи на валидность
     */
    public function CheckCaptcha()
    {
        // Регистр не имеет значения
        $captchaField = SafeStr(@$_GET["fieldId"]);
        $captchaValue = strtoupper(SafeStr(@$_GET["fieldValue"]));
        // Возврат в понятном для валидатора формате
        if (isset($_SESSION["captcha"]) && ($captchaValue == $_SESSION["captcha"]) || ($_SESSION["USER_ROLE"] < 4))
        {
            return array($captchaField, true);
        } else {
            return array($captchaField, false);
        }
    }

    /**
     * TAjax::RenderCaptcha()
     *
     * @return Генерирование капча-картинки
     */
    public function RenderCaptcha()
    {
        // Подключение графической библиотеки
        include(_LIBRARY."lib_picture.php");
        $picture = new TPicture();
        // Вывод капчи в изображении
        return $picture->TextToCaptcha(GetCaptchaDefence(), $_SESSION["USER_ROLE"] < 4);
    }

    /**
     * TAjax::FavouriteAnnounce()
     *
     * @return Добавление объявления в "Избранное"
     * todo Убрать в библиотеку объявлений
     */
    public function FavouriteAnnounce()
    {
        // Код объявления
        $announce_id = SafeInt(@$_GET["id"]);
        // Код пользователя
        $isUser = $this->SafeUserRegister($user_id, $user_field);

        // Поиск наличия в избранном
        $SQL = "select ID_ANNOUNCE from ANNOUNCE_FAVOURITE b where ID_ANNOUNCE=".$announce_id." and ".$user_field;
        // Если объявление есть в избранном, то выход
        if ($this->DL->LFetchField($SQL)) return false;

        if ($isUser) {
            // Добавление объявления в список избранных
            $SQL = "insert into ANNOUNCE_FAVOURITE (ID_ANNOUNCE, ID_USER) values (".$announce_id.", ".$user_id.")";
            $this->DL->Execute($SQL);
            // Обновление количества избранных объявлений
            $SQL = "update USER_DATA set COUNTFAV=COUNTFAV+1 where ID_USER=".$user_id;
            $this->DL->Execute($SQL);
        } else {
            // Добавление объявления в список избранных
            $SQL = "insert into ANNOUNCE_FAVOURITE (ID_ANNOUNCE, ID_GUEST) values (".$announce_id.", ".$user_id.")";
            $this->DL->Execute($SQL);
            // Обновление количества избранных объявлений
            $SQL = "update USER_GUEST set COUNTFAV=COUNTFAV+1 where ID_GUEST=".$user_id;
            $this->DL->Execute($SQL);
        }
        // Получение количества в избранном
        if ($isUser) {
            $SQL = "select COUNTFAV from USER_DATA where ID_USER=".$user_id;
        } else {
            $SQL = "select COUNTFAV from USER_GUEST where ID_GUEST=".$user_id;
        }
        $item = $this->DL->LFetchField($SQL);

        return $item;
    }

    /**
     * TAjax::FavouriteDelete()
     *
     * @return Удаление объявления из "Избранного"
     * todo Убрать в библиотеку объявлений
     */
    public function FavouriteDelete()
    {
        // Код объявления
        $announce_id = SafeInt(@$_GET["id"]);
        // Код пользователя
        $isUser = $this->SafeUserRegister($user_id, $user_field);

        // Поиск наличия в избранном
        $SQL = "select ID_ANNOUNCE from ANNOUNCE_FAVOURITE b where ID_ANNOUNCE=".$announce_id." and ".$user_field;
        // Если объявления нет в избранном, то выход
        if (!$this->DL->LFetchField($SQL)) return false;

        if ($isUser) {
            // Добавление объявления в список избранных
            $SQL = "delete from ANNOUNCE_FAVOURITE where ID_ANNOUNCE=".$announce_id." and ID_USER=".$user_id;
            $this->DL->Execute($SQL);
            // Обновление количества избранных объявлений
            $SQL = "update USER_DATA set COUNTFAV=COUNTFAV-1 where COUNTFAV > 0 and ID_USER=".$user_id;
            $this->DL->Execute($SQL);
        } else {
            // Добавление объявления в список избранных
            $SQL = "delete from ANNOUNCE_FAVOURITE where ID_ANNOUNCE=".$announce_id." and ID_GUEST=".$user_id;
            $this->DL->Execute($SQL);
            // Обновление количества избранных объявлений
            $SQL = "update USER_GUEST set COUNTFAV=COUNTFAV-1 where COUNTFAV > 0 and ID_GUEST=".$user_id;
            $this->DL->Execute($SQL);
        }
        // Получение количества в избранном
        if ($isUser) {
            $SQL = "select COUNTFAV from USER_DATA where ID_USER=".$user_id;
        } else {
            $SQL = "select COUNTFAV from USER_GUEST where ID_GUEST=".$user_id;
        }
        $item = $this->DL->LFetchField($SQL);

        return $item;
    }

    /**
     * TAjax::RenderAutocomp()
     *
     * @return Поиск слов для автозавершения
     */
    public function RenderAutocomp()
    {
        $text = SafeStr(@$_GET["term"]);
        if (!TextRange($text, 2)) return false;
        // Смена раскладки
        $punto = TextSwitch($text);
        $result = array();
        // Выборка доступных имен категорий, в будущем заменить на облако тегов
        $SQL = "select CAPTION from REF_CATEGORY where CAPTION like '%".$text."%' or CAPTION like '%".$punto."%'"
            ." group by CAPTION order by CAPTION limit 15";
        $dump = $this->DL->LFetchRowsField($SQL);

        // Добавление текста раскладки
        if ($punto != "") array_push($result, $punto);
        //foreach ($dump as $item) {
            // todo jquery autocomplete array_push($result, str_ireplace($text, "<b>".$text."</b>", $item));
        //}
        return array_merge($result, $dump);
    }

    /**
     * TAjax::RenderAction()
     *
     * @return Группа действий для категории при действиях с объявлением
     */
    public function RenderAction()
    {
        // Код рубрики
        $division_id = SafeInt(@$_GET["id"]);

        $SQL = "select ra.ACTION, ra.CAPTION from REF_ACTION ra, REF_CATEGORY rc"
        ." where rc.ID_CATEGORY=".$division_id." and rc.ACTION like concat('%', ra.ACTION, '%')"
        ." order by ra.ORDERBY desc";
        // Получение набора опций для указанного запроса
        $stream = parent::BuildSelect($SQL, -1, "Выберите рубрику");

        return $stream;
    }

    /**
     * TAjax::RenderCategory()
     *
     * @return Группа категорий для рубрики при действиях с объявлением
     */
    public function RenderCategory()
    {
        // Код рубрики
        $catID = SafeInt(@$_GET["id"]);
        // Получение набора опций для указанного запроса с добавление пустой пары опций
        $SQL = "select ID_CATEGORY, CAPTION from REF_CATEGORY where ID_PARENT=".$catID
            ." order by ORDERBY, CAPTION";
        $stream = parent::BuildSelect($SQL, -1, "Выберите рубрику");

        // При пустом наборе, отказ от дополнительных уровней
        if ($stream) {
            $stream = "<select name='category' onchange='return annkit.listcat(this);'"
                ."id='rc_".$catID."' class='validate[required]'>".$stream."</select>";
        } else {
            return false;
        }
        return $stream;
    }

    /**
     * TAjax::RenderCategoryCompany()
     *
     * @return Группа категорий для рубрики при действиях с объявлением
     */
    public function RenderCategoryCompany()
    {
        // Код рубрики
        $catID = SafeInt(@$_GET["id"]);
        // Получение набора опций для указанного запроса с добавление пустой пары опций
        $SQL = "select ID_CATEGORY, CAPTION from COMPANY_CATEGORY where ID_PARENT=".$catID
            ." order by ORDERBY, CAPTION";
        $stream = parent::BuildSelect($SQL, -1, "Выберите рубрику");

        // При пустом наборе, отказ от дополнительных уровней
        if ($stream) {
            $stream = "<select name='category' id='rc_".$catID."' class='validate[required]'>".$stream."</select>";
        } else {
            return false;
        }
        return $stream;
    }
}
?>