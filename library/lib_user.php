<?
class TUser extends TInterface
{
    public $MODE;
    private $TPL;
    private $IP;

    /**
     * Коды возвращаемых ошибок
     */
    const E_LOGINFAILED = 1;    // Неверный логин / пароль
    const E_LOGINBANNED = 2;    // Учетная запись заблокирована
    const E_LOGINNEEDED = 3;    // Для действия нужна авторизация
    const E_CAPTCHA = 4;        // Неверная капча
    const E_REGSUCCES = 5;      // Регистрация успешна
    const E_REGFAILED = 6;      // Некорректные данные при регистрации
    /**
     * Ссылки на части модуля
     */
    const LINK_MODULE = "/user/";

    /**
     * TUser::__construct()
     */
    public function __construct()
    {
        parent::__construct();
        $this->MODE = @$_REQUEST["user"];
        $this->IP = $_SERVER["REMOTE_ADDR"];
        $this->TPL = _TEMPLATE."user/";
    }

    public function RenderError($content)
    {
        if ($this->FL_ERR == parent::E__NOERROR) return $content;

        switch($this->FL_ERR) {
            case self::E_CAPTCHA: {
                return parent::RenderErrorTemplate($content, parent::E__ERRORID,
                "Неверно указан код защиты");
            }
            case self::E_LOGINFAILED: {
                $stream = "Неверно указан логин или пароль. При неправильном вводе ".($this->DC["LIMIT_BANIP"]-@$_SESSION["BAN_COUNT"])
                    ." раз доступ будет закрыт на ".$this->DC["LIMIT_BANTIME"]." минуты";
                return parent::RenderErrorTemplate($content, parent::E__ERRORID, $stream);
            }
            case self::E_LOGINBANNED: {
                return parent::RenderErrorTemplate($content, parent::E__ERRORID,
                    "Вы заблокированы до ".$_SESSION["BAN_DATE"]);
            }
            case self::E_LOGINNEEDED: {
                return parent::RenderErrorTemplate($content, parent::E__ERRORID,
                    "Для использования этой функции необходимо <a href='/user/register'>зарегистрироваться</a> или <a href='/user/login'>войти</a> в систему");
            }
            case self::E_REGSUCCES: {
                return parent::RenderErrorTemplate($content, parent::E__SUCCSID,
                    "Регистрация успешна, теперь вы можете войти. На указанный почтовый ящик будет отправлено письмо с данными");
            }
            case self::E_REGFAILED: {
                return parent::RenderErrorTemplate($content, parent::E__ERRORID,
                    "Указанны некорректные реквизиты, повторите еще раз");
            }
            return false;
        }
    }

    private function BanIpCheck()
    {
        $SQL = "select PARAM, DATE_LIFE from USER_BLOCK where ID_MODE=".parent::BAN_LOGIN." and IP='".$this->IP."'";
        $dump = $this->DL->LFetchRecord($SQL);

        if ($dump["PARAM"] > $this->DC["LIMIT_BANIP"]) {
            $_SESSION["BAN_DATE"] = $dump["DATE_LIFE"];
            return true;
        } else {
            $_SESSION["BAN_COUNT"] = $dump["PARAM"];
            return false;
        }
    }

    private function BanIpAdd()
    {
        $SQL = "update USER_BLOCK set PARAM=PARAM+1, DATE_LIFE=NOW() + interval ".$this->DC["LIMIT_BANTIME"]." minute"
            ." where ID_MODE=".parent::BAN_LOGIN." and IP='".$this->IP."'";
        $this->DL->Execute($SQL);

        if ($this->DL->LAffected() == 0) {
            $SQL = "insert into USER_BLOCK (IP, ID_MODE, PARAM) values('".$this->IP."', ".parent::BAN_LOGIN.", 1)";
            $this->DL->Execute($SQL);
        }
        return false;
    }

    public function BanIpDel()
    {
        $SQL = "delete from USER_BLOCK where ID_MODE=".parent::BAN_LOGIN
            ." and NOW() > DATE_LIFE + interval ".$this->DC["LIMIT_BANTIME"]." minute";
        $this->DL->Execute($SQL);

        return false;
    }

    public function Login($pid = false)
    {
        if ($this->BanIpCheck()) {
            RedirectError(self::E_LOGINBANNED, self::LINK_MODULE);
        }

        // Пользовательский ID при автологоне
        if (!$pid) {
            $login = SafeStr(@$_POST["login"]);
            $pwd = SafeStr(@$_POST["pwd"]);
            $remember = SafeBool("remember");
        }

        $SQL = "select b.ID_USER, b.ID_ROLE, ry.ID_COMPANY"
            ." from USER_DATA b left join COMPANY_DATA ry on ry.ID_USER=b.ID_USER and ry.ID_TYPE<5 and ry.ID_STATE in (1, 4)"
            ." where b.ID_STATE=1 and ";
        if (!$pid) {
            $SQL .= "(b.LOGIN='".$login."' or b.EMAIL='".$login."') and b.PWD=MD5('".$pwd."')";
        } else {
            $SQL .= "b.COOKIE='".$pid."'";
            $remember = true;
        }
        $item = $this->DL->LFetchRecord($SQL);

        // Пользователь не найден
        if ($item["ID_USER"] == "") {
            if (!$pid) {
                $this->BanIpAdd();
                RedirectError(self::E_LOGINFAILED, self::LINK_MODULE);
            } else {
                $this->CookieDrop(parent::COOKIE_USER);
            }
            return false;
        }

        $_SESSION["USER_ID"] = $item["ID_USER"];
        $_SESSION["USER_ROLE"] = $item["ID_ROLE"];
        $_SESSION["USER_COMPANY"] = $item["ID_COMPANY"];

        unset($_SESSION["LIST_GRANT"]);

        if ($remember) {
            $cookie = md5(microtime(false));
            $this->CookieSet(parent::COOKIE_USER, $cookie);
            $cookie = ", COOKIE='".$cookie."'";
        } else {
            $cookie = ", COOKIE=NULL";
            $this->CookieDrop(parent::COOKIE_USER);
        }

        $SQL = "update USER_DATA set DATE_LOGON=NOW() ".$cookie." where ID_USER=".$item["ID_USER"];
        $this->DL->Execute($SQL);

        if (!$pid && !$this->AJ) Redirect("/");
    }

    public function LoginGuest($sid)
    {
        // Поиск гостя по кукам
        $SQL = "select ID_GUEST from USER_GUEST where ID_STATE=1 and COOKIE='".$sid."'";
        $item = $this->DL->LFetchRecord($SQL);
        // Гость существует на доске
        if ($item["ID_GUEST"] > 0) {
            $_SESSION["GUEST_ID"] = $item["ID_GUEST"];
            $_SESSION["USER_ROLE"] = parent::ROLE_GUEST;
        }
    }

    public function Logout()
    {
        if (!isset($_SESSION["USER_ID"])) return false;
        $SQL = "update USER_DATA set DATE_LOGOFF=NOW(), COOKIE=NULL where ID_USER=".$_SESSION["USER_ID"];
        $this->DL->Execute($SQL);
        $this->CookieDrop(parent::COOKIE_USER);
        session_destroy();

        Redirect("/");
    }

    public function RenderLogin()
    {
        if ($this->AJ) {
            $stream = file_get_contents($this->TPL."login_aj.html");
            SendLn($stream);
        } else {
            $stream = file_get_contents($this->TPL."login.html");
            return $this->RenderError($stream);
        }
    }

    public function RenderRegister()
    {
        $stream = file_get_contents($this->TPL."register.html");
        return $this->RenderError($stream);
    }

    public function RegisterInline($login, $pwd, $email)
    {
        // Создание нового пользователя
        $SQL = "insert into USER_DATA (LOGIN, PWD, EMAIL) values ('".$login."', MD5('".$pwd."'), '".$email."')";
        $this->DL->Execute($SQL);

        return $this->DL->PrimaryID();
    }

    public function Register()
    {
        // Проверка капчи
        if (!GetCaptchaBoolVerify()) RedirectError(self::E_CAPTCHA);
        // Параметры пользователя
        $pwd = SafeStr(@$_POST["pwd"]);
        $login = SafeStr(@$_POST["login"]);
        $email = SafeStr(@$_POST["mail"]);

        // Проверка существования почтового ящика
        $SQL = "select ID_USER from USER_DATA where EMAIL='".$email."' or LOGIN='".$login."'";
        $grant = !$this->DL->LFetchRecordRow($SQL);

        // Проверка корректности почтового ящика
        if (!CheckLogin($login)
            || !CheckMail($email)
            || !TextRange($login, 3)
            || !TextRange($email, 4)
            || !TextRange($pwd, 4)
            || !$grant
        ) RedirectError(self::E_REGFAILED);

        $this->RegisterInline($login, $pwd, $email);

        /* todo */
        include(_LIBRARY."lib_email.php");
        $Mail = new TEmail();
        $Mail->MailRegister($login, $email, $pwd);

        Redirect("/user/login&e=".self::E_REGSUCCES);
    }

    public function RenderUser()
    {
        $user_id = SafeInt(@$_GET["user"]);

        $SQL = "select b.ID_USER, LOGIN, NAME_FIRST, NAME_LAST, NAME_MIDDLE, NAME_GENDER, b.ID_ROLE, rc.CAPTION as CITY,"
            ." b.LOCATION_STREET, b.LOCATION_MAP, b.DATE_LIFE, DATE_LOGON, DATE_LOGOFF, rr.CAPTION as ROLE,"
            ." uncompress(b.TEXTDATA) as TEXTDATA, uncompress(b.CONTACT) as CONTACT"
            /*." ry.ID_COMPANY, ry.ID_TYPE as COMTYPE, ry.CAPTION as COMCAPTION, ry.DOM1AIN_SYM1BOL as COMLAT"*/
            /* b left join COMPANY_DATA ry on b.ID_USER=ry.ID_USER and ry.ID_TYPE<5*/
            ." from USER_DATA b, "
            ." REF_CITY rc, REF_ROLE rr where b.ID_STATE=1 and rc.ID_STATE=1 and rr.ID_STATE=1"
            ." and rc.ID_CITY=b.ID_CITY and rr.ID_ROLE=b.ID_ROLE and b.ID_USER=".$user_id;
        $item = $this->DL->LFetchRecord($SQL);

        if ($item["ID_ROLE"] == parent::ROLE_GUEST) {
            $item["CITY"] = "Не определен";
        }

        $stream = file_get_contents(_TEMPLATE."user/about.html");
        $stream = str_replace("#ROLE", $item["ROLE"], $stream);
        $stream = str_replace("#CITY", $item["CITY"], $stream);
        $stream = str_replace("#DATELOGON", $item["DATE_LOGON"], $stream);
        $stream = str_replace("#DATELOGOFF", $item["DATE_LOGOFF"], $stream);
        $stream = str_replace("#DATELIFE", $item["DATE_LIFE"], $stream);
        $stream = str_replace("#NAMEFIRST", $item["NAME_FIRST"], $stream);
        $stream = str_replace("#NAMELAST", $item["NAME_LAST"], $stream);
        $stream = str_replace("#NAMEMIDDLE", $item["NAME_MIDDLE"], $stream);
        $stream = str_replace("#TEXTDATA", BBCodeNativeToHTML($item["TEXTDATA"]), $stream);
        $stream = str_replace("#LOGIN", $this->GetUserColor($item["LOGIN"], $item["ID_ROLE"]), $stream);
        $stream = str_replace("#PHOTOPATH", $this->GetPhotoUser($item["ID_USER"]), $stream);
        $stream = str_replace("#CONTACT", $this->ContactView($item["CONTACT"]), $stream);
        $stream = str_replace("#USERID", $item["ID_USER"], $stream);
        $this->TITLE .= "Профиль ".$item["LOGIN"];

        return $this->RenderError($stream);
    }

    public function RegisterGuest()
    {
        // Кука по текущему таймстампу
        $cookie = md5(microtime(false));
        // Добавление гостя в базу
        $SQL = "insert into USER_GUEST (COOKIE) values ('".$cookie."')";
        $this->DL->Execute($SQL);

        // Кукисы на год
        $this->CookieSet(parent::COOKIE_GUEST, $cookie);
        $_SESSION["GUEST_ID"] = $this->DL->PrimaryID();

        return $_SESSION["GUEST_ID"];
    }
}
?>
