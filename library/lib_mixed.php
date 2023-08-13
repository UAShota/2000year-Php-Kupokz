<?
    class TMixed extends TInterface
    {
        private $TPL;
        public $MODE;

        const E_CAPTCHA = 1; // неверно указана капча
        const E_NOUSERMAIL = 2; // неверно указаны логин / мыло
        const E_NOMAILSENDED = 3; // невозможно отправить почту
        const E_PASSKEYSENDED = 4; // пасскей отправлен
        const E_NOPASSKEY = 5; // пасскей не найден
        const E_PASSCHANGED = 6; // пароль изменен
        const E_PWDCOMPARE = 7; // пароли не совпадают
        const E_PWDLOGINSMALL = 8; // короткий пароль или логин
        const E_FASTKEYEMPTY = 9; // несуществующий ключ
        const E_FASTALREADY = 10; // существует пользователь с мылом

        const LINK_ERROR = "/mixed/&e="; // для итоговых ошибок


        public function __construct()
        {
            parent::__construct();
            $this->TPL = _TEMPLATE."mixed/";
            $this->MODE = SafeStr($_REQUEST["mixed"]);
        }

        public function RenderError($content)
        {
            $error_id = SafeInt(@$_GET["e"]);
            $errclass = false;
            if ($error_id == 0) return $content;

            if ($error_id == self::E_CAPTCHA) {
                $error = "Неверно указан код защиты";
                $errclass = parent::E__ERROR;
            } else
            if ($error_id == self::E_NOMAILSENDED) {
                $error = "Не удалось отправить письмо на почтовый ящик. Попробуйте позднее.";
                $errclass = parent::E__ERROR;
            } else
            if ($error_id == self::E_NOPASSKEY) {
                $error = "Указанный ключ просрочен. Повторите операцию восстановления пароля.";
                $errclass = parent::E__ERROR;
            } else
            if ($error_id == self::E_NOUSERMAIL) {
                $error = "Учетная запись для восстановления не найдена.";
                $errclass = parent::E__ERROR;
            } else
            if ($error_id == self::E_PASSCHANGED) {
                $error = "Пароль успешно изменен. <a href='/user/login'>Войти на сайт</a>";
                $errclass = parent::E__SUCCS;
            } else
            if ($error_id == self::E_PASSKEYSENDED) {
                $error = "На указанный почтовый ящик отправлен ключ для сброса пароля.";
                $errclass = parent::E__SUCCS;
            } else
            if ($error_id == self::E_PWDCOMPARE) {
                $error = "Заданные пароли не совпадают. Повторите попытку.";
                $errclass = parent::E__ERROR;
            } else
            if ($error_id == self::E_PWDLOGINSMALL) {
                $error = "Логин или пароль слишком короткие. Повторите попытку.";
                $errclass = parent::E__ERROR;
            } else
            if ($error_id == self::E_FASTKEYEMPTY) {
                $error = "Указанный ключ не найден для активации.";
                $errclass = parent::E__ERROR;
            } else
            if ($error_id == self::E_FASTALREADY) {
                $error = "Указанный почтовый ящик уже существует. Войдите в учетную запись, либо задайте другой почтовый ящик.";
                $errclass = parent::E__ERROR;
            } else {
                $error = "^_^";
                $errclass = parent::E__ERROR;
            }

            $stream = file_get_contents($this->TPL."default.html");
            $stream = str_replace("#STYLE", $errclass, $stream);
            $stream = str_replace("#TEXT", $error, $stream);
            $stream = str_replace("#CONTENT", $content, $stream);

            return $stream;
        }

        public function PasswordLostRender()
        {
            $stream = file_get_contents($this->TPL."passlost.html");
            return $this->RenderError($stream);
        }

        public function PasswordLostPost()
        {
            if (!GetCaptchaBoolVerify()) {
                RedirectError(self::E_CAPTCHA);
            }

            $login = SafeStr(@$_POST["login"]);
            $email = SafeStr(@$_POST["mail"]);
            if ($login == "") $login = $email;

            $SQL = "select ID_USER, LOGIN, EMAIL, PWD from USER_DATA where ID_STATE=1"
                ." and EMAIL='".$email."' and LOGIN='".$login."'";
            $item = $this->DL->LFetchRecord($SQL);

            if ($item["LOGIN"] == "") {
                RedirectError(self::E_NOUSERMAIL);
            }

            $passkey = substr(md5($item["ID_USER"].$item["LOGIN"].$item["PWD"]), 1);
            $passLink = $_SERVER["HTTP_HOST"]."/mixed/passkey&pk=".$passkey;

            $stream = file_get_contents(_TEMPLATE."email/passlost.html");
            $stream = str_replace("#EMAIL", $item["EMAIL"], $stream);
            $stream = str_replace("#LOGIN", $item["LOGIN"], $stream);
            $stream = str_replace("#LINK", $passLink, $stream);

            include(_LIBRARY."lib_email.php");
            $Mail = new TEmail();
            if ($Mail->SendSimple($item["LOGIN"], $item["EMAIL"], $this->DC["TEXT_NEWPASWORD"], $stream))
            {
                $SQL = "update USER_DATA set COOKIE='-".$passkey."' where ID_USER=".$item["ID_USER"];
                $this->DL->Execute($SQL);
                Redirect(self::LINK_ERROR.self::E_PASSKEYSENDED);
            } else {
                RedirectError(self::E_NOMAILSENDED);
            }
        }

        public function PasswordKeyRender()
        {
            $passkey = SafeStr(@$_GET["pk"]);

            $SQL = "select ID_USER from USER_DATA where COOKIE='-".$passkey."' and ID_STATE=1";
            $item = $this->DL->LFetchRecordRow($SQL);

            if (!$item) {
                Redirect(self::LINK_ERROR.self::E_NOPASSKEY);
            }

            $stream = file_get_contents($this->TPL."passkey.html");
            $stream = str_replace("#PASSKEY", $passkey, $stream);

            return $this->RenderError($stream);
        }

        public function PasswordKeyPost()
        {
            $passkey = SafeStr(@$_POST["pk"]);
            $pwd_new = SafeStr(@$_POST["pwd_new"]);
            $pwd_dup = SafeStr(@$_POST["pwd_dup"]);

            if (($pwd_dup == $pwd_new) && TextRange($pwd_dup, 4)) {
                $SQL = "select ID_USER, LOGIN, EMAIL from USER_DATA where COOKIE='-".$passkey."' and ID_STATE=1";
                $item = $this->DL->LFetchRecord($SQL);

                if ($item["ID_USER"] <= 0) {
                    RedirectError(self::E_NOPASSKEY);
                }

                include(_LIBRARY."lib_email.php");
                $Mail = new TEmail();
                $stream = file_get_contents(_TEMPLATE."email/passkey.html");
                $stream = str_replace("#PASSWORD", $pwd_new, $stream);
                $stream = str_replace("#LOGIN", $item["LOGIN"], $stream);

                if ($Mail->SendSimple($item["LOGIN"], $item["EMAIL"], $this->DC["TEXT_NEWPASWORD"], $stream))
                {
                    $SQL = "update USER_DATA set PWD=md5('".$pwd_new."'), COOKIE=NULL where ID_USER=".$item["ID_USER"];
                    $this->DL->Execute($SQL);
                    Redirect(self::LINK_ERROR.self::E_PASSCHANGED);
                } else {
                    RedirectError(self::E_NOMAILSENDED);
                }
            } else {
                RedirectError(self::E_PWDCOMPARE);
            }
        }

        public function FastRegRender()
        {
            $passkey = SafeStr(@$_GET["pk"]);

            $SQL = "select ID_GUEST, EMAIL from USER_GUEST where PWD='".$passkey."' and ID_STATE=1";
            $guest = $this->DL->LFetchRecordRow($SQL);
            if (!$guest) {
                Redirect(self::LINK_ERROR.self::E_FASTKEYEMPTY);
            }

            $SQL = "select ID_USER from USER_DATA where EMAIL='".$guest[1]."' and ID_STATE=1";
            $user = $this->DL->LFetchRecordRow($SQL);
            if ($user) {
                Redirect(self::LINK_ERROR.self::E_FASTALREADY);
            }

            $stream = file_get_contents($this->TPL."fastreg.html");
            $stream = str_replace("#PASSKEY", $passkey, $stream);
            $stream = str_replace("#EMAIL", $guest[1], $stream);

            return $this->RenderError($stream);
        }

        public function FastRegPost()
        {
            $passkey = SafeStr(@$_POST["pk"]);
            $login = SafeStr(@$_POST["login"]);
            $pwd = SafeStr(@$_POST["pwd"]);

            if (!TextRange($pwd, 4) || !TextRange($login, 3)) {
                RedirectError(self::E_PWDLOGINSMALL);
            }

            $SQL = "select ID_GUEST, EMAIL from USER_GUEST where PWD='".$passkey."'"
                ." and COOKIE='".$passkey."' and ID_STATE=1";
            $guest = $this->DL->LFetchRecordRow($SQL);
            if (!$guest) {
                Redirect(self::LINK_ERROR.self::E_FASTKEYEMPTY);
            }

            $SQL = "select ID_USER from USER_DATA where (EMAIL='".$guest[1]."' or LOGIN='".$login."') and ID_STATE=1";
            $item = $this->DL->LFetchRecordRow($SQL);
            if ($item) {
                Redirect(self::LINK_ERROR.self::E_FASTALREADY);
            }

            require_once(_LIBRARY."lib_user.php");
            $User = new TUser();
            $user_id = $User->RegisterInline($login, $pwd, $guest[1]);

            $SQL = "update ANNOUNCE_DATA set ID_USER=".$user_id.", ID_GUEST=0"
                ." where ID_GUEST=".$guest[0]." and ID_USER=".parent::ROLE_GUEST;
            $this->DL->Execute($SQL);

            $SQL = "update ANNOUNCE_FAVOURITE set ID_GUEST=0, ID_USER=".$user_id
                ." where ID_USER=".parent::ROLE_GUEST." and ID_GUEST=".$guest[0];
            $this->DL->Execute($SQL);

            $SQL = "update USER_DATA u, USER_GUEST a set u.COUNTANN=a.COUNTANN, u.COUNTFAV=a.COUNTFAV,"
                ." a.COUNTANN=0, a.COUNTFAV=0, a.PWD=null, a.EMAIL=null"
                ." where u.ID_USER=".$user_id." and a.ID_GUEST=".$guest[0];
            $this->DL->Execute($SQL);

            include(_LIBRARY."lib_email.php");
            $Mail = new TEmail();
            $Mail->MailRegister($login, $guest[1], $pwd);
            unset($Mail);

            $User->Login();
       }

       public function RenderCityList()
       {
            $cityCount = 0;
            $cityList = parent::CashCity();
            $out = "<div><ul>";
            for ($index=0; $index < count(parent::CashCity()); $index++)
            {
                $city = $cityList[$index];

                if ($city[2] != "") {
                    $out .= "<li><a href='http://".$city[2].".".$this->DC["SITE_HOST"].$this->DC["SITE_DOMAIN"]."'>".$city[1]."</a></li>";
                } else {
                    $out .= "<li><a href='http://".$this->DC["SITE_HOST"].$this->DC["SITE_DOMAIN"]."'>".$city[1]."</a></li>";
                }

                $cityCount++;
                if ($cityCount == 15) {
                    $out .= "</ul></div><div><ul>";
                    $cityCount = 0;
                }
            }
            $out .= "</ul></div>";
            $stream = file_get_contents(_TEMPLATE."mixed/citylist.html");
            $stream = str_replace("#BLOCKDATA", $out, $stream);

            return $stream;
        }
    }
?>
