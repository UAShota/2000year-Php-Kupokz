<?
    /**
     * TMailBox
     *
     * @package TMailBox
     * @author Kupo.kz
     * @copyright clamdv
     * @version 2011
     * @access public
     */
    class TMailBox extends TInterface
    {
        private $TPL;
        private $BOX;
        public $MODE;

        /**
         * Шаблон ссылок по умолчанию
         */
        const LINK_DEFAULT = "/cabuser/mailbox/"; // Просмотр сообщения
        const LINK_ERROR = "/cabuser/mailbox/stub&e="; // Итоговые ошибки
        /**
         * Константы страниц ящика
         */
        const PAGE_INBOX = "in";   // Входящие сообщения
        const PAGE_OUTBOX = "out";  // Исходящие сообщения
        /**
         * Константы возвращаемых ошибок
         */
        const E_MAILNOTFOUND = 1;   // Письмо не найдено
        const E_USERNOTFOUND = 2;   // Пользователь не найден
        const E_MESSAGESMALL = 3;   // Письмо слишком короткое
        const E_USERNOTGUEST = 4;   // Неавторизованный получатель
        const E_NOT_FOUND = 5;      // Объявление не найдено
        const E_INVALIDPARAM = 6;   // Недопустимые параметры
        const E_MAILED_SUCCES = 7;  // Письмо саппорту отправлено
        const E_CAPTCHA = 9;        // Неверно указана капча
        const E_MAIL_SENDED = 10;   // Сообщение отправлено

        /**
         * TMailBox::__construct()
         *
         * @name __construct()
         * @param mixed $DataClass
         * @return класс связи с БД и код запрошенного действия
         */
        public function __construct()
        {
            parent::__construct();
            $this->TPL = _TEMPLATE."mailbox/";
            $this->MODE = SafeStr(@$_REQUEST["m"]);
            $this->BOX = (($this->MODE == "out") || (SafeStr(@$_REQUEST["box"]) == "out")) ? self::PAGE_OUTBOX : self::PAGE_INBOX;
        }

        public function Execute($mode)
        {
            // Генерирование формы саппорта
            if ($this->MODE == "support") {
                return $this->RenderSupport();
            } else

            // Отправка сообщения саппорту
            if ($this->MODE == "supportpost") {
               return $this->SupportPost();
            } else

            // Генерирование формы жалобы
            if ($this->MODE == "complaint") {
                return $this->RenderComplaint();
            } else

            // Отправка жалобы
            if ($this->MODE == "complaintpost") {
                return $this->ComplaintPost();
            } else

            if ($this->MODE == "stub") {
                return $this->RenderError();
            } else {

                // Для всех других действий необходима регистрация
                $this->CheckAuthorize();

                // Генерирование формы чтения сообщения
                if ($this->MODE == "read") {
                    return $this->RenderRead();
                } else

                // Генерирование формы создания сообщения
                if ($this->MODE == "write") {
                    return $this->RenderWrite();
                } else

                // Генерирование формы ответа на сообщение
                if ($this->MODE == "reply") {
                    return $this->RenderReply();
                } else

                // Обработка запроса на создание сообщения
                if ($this->MODE == "post") {
                    return $this->MailPost();
                } else

                // Обработка запроса на удаление сообщения
                if ($this->MODE == "delete") {
                    return $this->MailDelete();
                } else

                // Обработка запроса на удаления указанных сообщений
                if ($this->MODE == "crop") {
                    return $this->MailCrop();
                } else

                // Генерирование формы просмотра входящих сообщений
                {
                    return $this->RenderBox();
                }
            }
        }

        /**
         * TMailBox::RenderError()
         *
         * @return ошибку в текстовом представлении по ее номеру
         */
        public function RenderError($content = "")
        {
            $error_id = SafeInt(@$_GET["e"]);
            $errclass = false;
            if ($error_id == 0) return $content;

            if ($error_id == self::E_MAILNOTFOUND) {
                $error = "Сообщение не найдено";
                $errclass = parent::E__ERROR;
            } else
            if ($error_id == self::E_USERNOTFOUND) {
                $error = "Пользователь не найден";
                $errclass = parent::E__ERROR;
            } else
            if ($error_id == self::E_MESSAGESMALL) {
                $error = "Не указана тема, либо текст сообщения";
                $errclass = parent::E__ERROR;
            } else
            if ($error_id == self::E_USERNOTGUEST) {
                $error = "Пользователь не принимает личные сообщения";
                $errclass = parent::E__ERROR;
            } else
            if ($error_id == self::E_INVALIDPARAM) {
                $error = "Указаны некорректные параметры";
                $errclass = parent::E__ERROR;
            } else
            if ($error_id == self::E_MAILED_SUCCES) {
                $error = "Ваше сообщение отправлено и будет рассмотрено в кратчайшие сроки";
                $errclass = parent::E__SUCCS;
            } else
            if ($error_id == self::E_CAPTCHA) {
                $error = "Неверно указан код защиты";
                $errclass = parent::E__ERROR;
            } else
            if ($error_id == self::E_MAIL_SENDED) {
                $error = "Сообщение отправлено";
                $errclass = parent::E__SUCCS;
            } else {
                $error = "^_^";
                $errclass = parent::E__ERROR;
            }
            if ($this->AJ) {
                SendLn($stream);
            } else {
                $stream = file_get_contents($this->TPL."default.html");
                $stream = str_replace("#STYLE", $errclass, $stream);
                $stream = str_replace("#TEXT", $error, $stream);
                $stream = str_replace("#CONTENT", $content, $stream);
                return $stream;
            }
        }

        public function RenderSupport()
        {
            $stream = file_get_contents($this->TPL."support.html");
            $stream = str_replace("#MAILSUPPORT", $this->DC["SMTP_SUPPORT"], $stream);

            return $this->RenderError($stream);
        }

        public function SupportPost()
        {
            // Проверка капчи
            if (!GetCaptchaBoolVerify()) RedirectError(self::E_CAPTCHA);
            // Текст сообщения
            $textdata = SafeStr(@$_POST["textdata"]);
            $email = SafeStr(@$_POST["email"]);

            /* todo */
            include(_LIBRARY."lib_email.php");
            $Email = new TEmail();
            $Email->MailSupport($email, $textdata);

            Redirect(self::LINK_ERROR.self::E_MAILED_SUCCES);
        }

        public function RenderComplaint()
        {
            // Код объявления
            $announce_id = SafeInt($_GET["id"]);
            // Проверка на существование объявления
            $SQL = "select ID_ANNOUNCE from ANNOUNCE_DATA where ID_STATE in (1,2,3,4) and ID_ANNOUNCE=".$announce_id;
            if (!$this->DL->LFetchRecordRow($SQL)) {
                Redirect("/announce/&e=".self::E_NOT_FOUND);
            }

            // Выборка списка доступных жалоб
            $SQL = "select ID_COMPLAINT, CAPTION from REF_COMPLAINT where ID_STATE=1 and"
                ." ID_MODE=".parent::REASON_ANNOUNCE." order by ORDERBY desc";
            $list_reason = $this->BuildSelect($SQL);

            // Вывод форматированного шаблона с учетом кода объявления
            if ($this->AJ) {
                $stream = file_get_contents(_TEMPLATE."complaint/announce_aj.html");
            } else {
                $stream = file_get_contents(_TEMPLATE."complaint/announce.html");
            }
            $stream = str_replace("#REASONLIST", $list_reason, $stream);
            $stream = str_replace("#ANNOUNCEID", $announce_id, $stream);
            $stream = str_replace("#ANNOUNCENUM", GetStretchNumber($announce_id), $stream);

            if ($this->AJ) {
                SendLn($stream);
            } else {
                return $stream;
            }
        }

        public function ComplaintPost()
        {
            // Проверка капчи
            if (!GetCaptchaBoolVerify()) RedirectError(self::E_CAPTCHA);
            // Код объявления
            $announce_id = SafeInt($_POST["announce"]);
            // Код причины
            $reason_id = SafeInt(@$_POST["reason"]);
            // Пользовательское описание
            $textdata = SafeStr(@$_POST["textdata"]);

            // Проверка объявления на сощуствование
            $SQL = "select ID_ANNOUNCE from ANNOUNCE_DATA where ID_STATE in (1,2,3,4) and ID_ANNOUNCE=".$announce_id;
            if (!$this->DL->LFetchRows($SQL)) {
                Redirect("/announce/&e=".self::E_NOT_FOUND);
            }
            // Проверка причины на соществование
            $SQL = "select CAPTION from REF_COMPLAINT where ID_STATE=1 and ID_MODE=".self::REASON_ANNOUNCE." and ID_COMPLAINT=".$reason_id;
            $reason = $this->DL->LFetchRecordRow($SQL);
            if (!$reason) {
                RedirectError(self::E_INVALIDPARAM);
            } else {
                $reason = $reason[0];
            }

            /* todo */
            include(_LIBRARY."lib_email.php");
            $Email = new TEmail();
            $Email->MailComplaint($announce_id, $textdata, $reason);

            Redirect(self::LINK_ERROR.self::E_MAILED_SUCCES);
        }

        public function MailPost($user_id = null, $caption = null, $text = null, $referer = true)
        {
            if (!$user_id) $user_id = SafeInt(@$_POST["id"]);
            if (!$caption) $caption = SafeStr(@$_POST["mailcaption"]);
            if (!$text)    $text = SafeStr(@$_POST["mailtext"]);

            // Проверка на корректность текста
            if (!TextRange($caption, 3) || !TextRange($text, 3)) {
                RedirectError(self::E_MESSAGESMALL);
            }
            // Неавторизованный пользователь не является получателем
            if ($user_id == self::ROLE_GUEST) {
                RedirectError(self::E_USERNOTGUEST);
            }
            // Определение отправителя сообщения
            if (!isset($_SESSION["USER_ID"])) {
                $troll_id = parent::ROLE_GUEST;
            } else {
                $troll_id = $_SESSION["USER_ID"];
            }
            // Проверка пользователя на существование
            $SQL = "select ID_USER, EMAIL, LOGIN from USER_DATA where ID_USER=".$user_id;
            $item = $this->DL->LFetchRecord($SQL) or RedirectError(self::E_USERNOTFOUND);

            // Добавление нового письма в стек
            $SQL = "insert into BLOCK_MAIL (ID_USER, ID_TROLL, CAPTION, TEXTDATA) values".
                " (".$user_id.", ".$troll_id.", '".$caption."', '".$text."')";
            $this->DL->Execute($SQL);
            $message_id = $this->DL->PrimaryID();
            // Увеличение счетчика непрочитанных писем
            $this->MailInc($user_id);


            // В стэк отправку письма о комментарии
            /* todo */
            include(_LIBRARY."lib_email.php");
            $Mail = new TEmail();
            $Mail->MailMessage($item, $troll_id, $message_id, $caption, $text);

            if ($referer) Redirect(self::LINK_DEFAULT."&e=".self::E_MAIL_SENDED); else return true;
        }

        public function MailPostLocal($user_id, $caption, $text)
        {
            // Проверка пользователя на существование
            $SQL = "select ID_USER, EMAIL from USER_DATA where ID_USER=".$user_id;
            $item = $this->DL->LFetchRecord($SQL) or RedirectError(self::E_USERNOTFOUND);
            // Добавление нового письма в стек
            $SQL = "insert into BLOCK_MAIL (ID_USER, ID_TROLL, CAPTION, TEXTDATA, CHECKTROLL) values".
                " (".$user_id.", 1, '".SafeHtml($caption)."', '".SafeHtml($text)."', 0)";
            $this->DL->Execute($SQL);
            // Увеличение счетчика непрочитанных писем
            $this->MailInc($user_id);

        }

        private function MailPage($box_id, &$user, &$troll)
        {
            // Если входящее сообщение
            if ($box_id != self::PAGE_OUTBOX){
                $user = "USER";
                $troll = "TROLL";
                return "От кого";
            } else {
                $user = "TROLL";
                $troll = "USER";
                return "Кому";
            }
        }

        private function MailInc($user_id)
        {
            $SQL = "update USER_DATA set COUNTMAIL=COUNTMAIL+1 where ID_STATE=1 and ID_USER=".$user_id;
            return $this->DL->Execute($SQL);
        }

        private function MailDec()
        {
            $SQL = "update USER_DATA set COUNTMAIL=COUNTMAIL-1 where ID_STATE=1 and COUNTMAIL > 0 and ID_USER=".$_SESSION["USER_ID"];
            return $this->DL->Execute($SQL);
        }

        private function MailClean($mail_id, $box_id)
        {
            $this->MailPage($box_id, $_user, $_troll);
            $SQL = "update BLOCK_MAIL set CHECK".$_user."=0 where ID_".$_user."=".$_SESSION["USER_ID"]." and ID_MAIL=".$mail_id;

            return $this->DL->Execute($SQL);
        }

        /**
         * TMailBox::RenderBox()
         *
         * @return блок писем в почтовом ящике
         */
        public function RenderBox()
        {
            /*todo*/
            $box_id = $this->BOX;

            $page_id = SafeInt(@$_GET["page"]);
            $fromto = $this->MailPage($box_id, $_user, $_troll);

            // Вычисление количества входящих писем
            $SQL = "select count(ID_MAIL) as MAILCOUNT from BLOCK_MAIL"
                ." where CHECKUSER=1 and ID_USER=".$_SESSION["USER_ID"];
            $inbox = $this->DL->LFetchRecord($SQL);
            $inbox = $inbox["MAILCOUNT"];

            // Вычисление количества исходящих писем
            $SQL = "select count(ID_MAIL) as MAILCOUNT from BLOCK_MAIL"
                ." where CHECKTROLL=1 and ID_TROLL=".$_SESSION["USER_ID"];
            $outbox = $this->DL->LFetchRecord($SQL);
            $outbox = $outbox["MAILCOUNT"];

            // Вычисление страницы в ящике
            if ($box_id == self::PAGE_INBOX) $maxcount = $inbox; else $maxcount = $outbox;

            // Генерирование селекторов по страницам
            $limit = $this->SelectorPrepare($page_id, $this->DC["LIMIT_MAIL_PAGE"]);
            $pageselector = $this->SelectorPage($page_id, $this->DC["LIMIT_MAIL_PAGE"], $maxcount, self::LINK_DEFAULT.$box_id, false);

            // Выборка писем в зависимости от входящих или исходящих
            $SQL = "select b.ID_MAIL, b.CAPTION, b.DATE_LIFE, b.READED, bu.LOGIN, b.ID_".$_troll." as USER"
                ." from BLOCK_MAIL b, USER_DATA bu where bu.ID_STATE=1 and "
                ." b.ID_".$_troll."=bu.ID_USER and b.ID_".$_user."=".$_SESSION["USER_ID"]." and b.CHECK".$_user."=1"
                ." order by DATE_LIFE desc".$limit;
            $dump = $this->DL->LFetch($SQL);

            // Формирование итоговой таблицы
            $out = "";
            foreach ($dump as $item) {
                /*todo*/
                if ((($item["READED"] == 0) && ($box_id == self::PAGE_INBOX))
                    || (($item["READED"] == 0) && ($box_id == self::PAGE_OUTBOX)))
                {
                    $out .= "<div class='item highlite'>";
                } else {
                    $out .= "<div class='item'>";
                }

                $out .= "<div class='loginbox'><input class='checkbox-multi' type='checkbox' name='msg_".$item["ID_MAIL"]."'></div>";
                $out .= "<div class='login'><a href='/user/".$item["USER"]."'>".$item["LOGIN"]."</a></div>";
                /*$out .= GetMailTime($item["DATE_LIFE"])."</td>";*/


                $out .= "<div class='date'>".GetLocalizeTime($item["DATE_LIFE"])."</div>";

                $out .= "<a href='/cabuser/mailbox/read&box=".$box_id."&id=".$item["ID_MAIL"]."' class='body'>";

                if (($item["READED"] == 0) && ($box_id == self::PAGE_INBOX))
                    $out .= "<span>[Новое]&nbsp;</span>";
                else

                if (($item["READED"] == 0) && ($box_id == self::PAGE_OUTBOX))
                    $out .= "<span>[Не прочитано]&nbsp;</span>";
                else

                if (($item["READED"] == 1) && ($box_id == self::PAGE_OUTBOX))
                    $out .= "<span>[Прочитано]&nbsp;</span>";

                $out .= "<b>".ShortString($item["CAPTION"])."</b></a></div>";
            }

            // Загрузка шаблона почтового ящика и замена составляющих
            $stream = file_get_contents($this->TPL."mailbox.html");
            $stream = str_replace("#INBOX", $inbox, $stream);
            $stream = str_replace("#OUTBOX", $outbox, $stream);
            $stream = str_replace("#OUT", $out, $stream);
            $stream = str_replace("#PAGESELECTOR", $pageselector, $stream);
            $stream = str_replace("#BOXID", $box_id, $stream);
            $stream = str_replace("#FROMTO", $fromto, $stream);

            return $this->RenderError($stream);
        }

        /**
         * TMailBox::RenderRead()
         *
         * @return блок указанного письма
         */
        public function RenderRead()
        {
            /*todo*/
            $box_id = $this->BOX;

            $mail_id = SafeInt(@$_GET["id"]);
            $fromto = $this->MailPage($box_id, $_user, $_troll);

            // Выборка аттрибутов указанного письма
            $SQL = "select b.ID_MAIL, b.CAPTION, b.TEXTDATA, b.DATE_LIFE, b.READED, bu.ID_USER, bu.LOGIN, b.ID_TROLL"
                ." from BLOCK_MAIL b, USER_DATA bu where b.ID_".$_user."=".$_SESSION["USER_ID"]
                ." and bu.ID_STATE=1 and b.ID_MAIL=".$mail_id." and b.CHECK".$_user."=1"
                ." and bu.ID_USER=b.ID_".$_troll;
            $item = $this->DL->LFetchRecord($SQL);

            // Если письмо найдено, загрузка шаблона отображения и замена составляющих
            if ($item["ID_MAIL"] == $mail_id) {
                $stream = file_get_contents($this->TPL."mailread.html");
                $stream = str_replace("#DATELIFE", GetLocalizeTime($item["DATE_LIFE"]), $stream);
                $stream = str_replace("#MAILID", $item["ID_MAIL"], $stream);
                $stream = str_replace("#USERID", $item["ID_USER"], $stream);
                $stream = str_replace("#USERNAME", $item["LOGIN"], $stream);
                $stream = str_replace("#FROMTO", $fromto, $stream);
                $stream = str_replace("#IMAGEPATH", $this->GetPhotoUser($item["ID_TROLL"]), $stream);
                $stream = str_replace("#CAPTION", $item["CAPTION"], $stream);
                $stream = str_replace("#TEXTDATA", SafeBR($item["TEXTDATA"]), $stream);
                $stream = str_replace("#PAGE", $box_id, $stream);
            } else {
                // Если письмо не найдено, редирект на страницу ошибки
                RedirectError(self::E_MAILNOTFOUND);
            }

            // Если письмо не прочитано, обновление флага на "прочитано"
            if ($item["READED"] == 0) {
                $SQL = "update BLOCK_MAIL set READED=1 where CHECKUSER=1 and ID_USER=".$_SESSION["USER_ID"]." and ID_MAIL=".$mail_id;
                // При успешном выполнении уменьшается счетчик непрочитанных писем
                if ($this->DL->Execute($SQL)) $this->MailDec();
            }

            return $this->RenderError($stream);
        }

        /**
         * TMailBox::RenderReply()
         *
         * @return шаблон ответа на письмо
         */
        public function RenderReply()
        {
            $mail_id = SafeInt(@$_GET["id"]);

            // Выборка аттрибутов указанного письма
            $SQL = "select b.ID_TROLL, bu.LOGIN, b.CAPTION, b.ID_MAIL"
                ." from BLOCK_MAIL b, USER_DATA bu"
                ." where bu.ID_USER=b.ID_TROLL and b.CHECKUSER=1 and bu.ID_STATE=1"
                ." and b.ID_USER=".$_SESSION["USER_ID"]." and b.ID_MAIL=".$mail_id;
            $item = $this->DL->LFetchRecord($SQL);

            // Если письмо найдено, загрузка шаблона ответа и замена составляющих
            if ($item["ID_MAIL"] == $mail_id) {
                $stream = file_get_contents($this->TPL."mailreply.html");
                $stream = str_replace("#USERNAME", $item["LOGIN"], $stream);
                $stream = str_replace("#TROLLID", $item["ID_TROLL"], $stream);
                $stream = str_replace("#USERID", $item["ID_TROLL"], $stream);
                /* todo */
                $stream = str_replace("#CAPTION", str_replace("RE: ", "", $item["CAPTION"]), $stream);
                $stream = str_replace("#IMAGEPATH", $this->GetPhotoUser($item["ID_TROLL"]), $stream);
                $stream = str_replace("#REFERER", self::LINK_DEFAULT.self::PAGE_INBOX, $stream);
            } else {
                // Если письмо не найдено, редирект на страницу ошибки
                RedirectError(self::E_MAILNOTFOUND);
            }

             return $this->RenderError($stream);
        }

        /**
         * TMailBox::RenderWrite()
         *
         * @return шаблон нового письма указанному пользователю
         */
        public function RenderWrite()
        {
            $user_id = SafeInt(@$_GET["id"]);

            // Неавторизованный пользователь не является получателем
            if ($user_id == self::ROLE_GUEST) {
                // Редирект на страницу ошибки
                Redirect(self::LINK_DEFAULT."&e=".self::E_USERNOTGUEST);
            }

            // Выборка аттрибутов пользователя
            $SQL = "select ID_USER, LOGIN from USER_DATA where ID_STATE=1 and ID_USER=".$user_id;
            $item = $this->DL->LFetchRecord($SQL) or RedirectError(self::E_USERNOTFOUND);

            if ($this->AJ) {
                $stream = file_get_contents($this->TPL."mailwrite_aj.html");
            } else {
                $stream = file_get_contents($this->TPL."mailwrite.html");
            }
            $stream = str_replace("#USERNAME", $item["LOGIN"], $stream);
            $stream = str_replace("#USERID", $item["ID_USER"], $stream);
            $stream = str_replace("#IMAGEPATH", $this->GetPhotoUser($user_id), $stream);
            $stream = str_replace("#REFERER", @$_SERVER["HTTP_REFERER"], $stream);

            if ($this->AJ) {
                SendLn($stream);
            } else {
                return $this->RenderError($stream);
            }
        }

        /**
         * TMailBox::MailDelete()
         *
         * @param mixed $redirect
         *
         * @return редирект на страницу ошибки, либо страницу почтового ящика после удаления письма
         */
        public function MailDelete($redirect = true)
        {
            $mail_id = SafeInt(@$_GET["id"]);
            $box_id = SafeStr(@$_GET["box"]);
            $this->MailPage($box_id, $_user, $_troll);

            // Выборка аттрибутов указанного письма
            $SQL = "select b.ID_MAIL, b.READED from BLOCK_MAIL b where b.ID_".$_user."=".$_SESSION["USER_ID"]
                ." and b.ID_MAIL=".$mail_id." and CHECK".$_user."=1";
            $item = $this->DL->LFetchRecord($SQL);

            // Если письмо существует
            if ($item["ID_MAIL"] == $mail_id) {
                // Если оно не прочитано, уменьшения счетчика непрочитанных писем
                if ($item["READED"] == 0) $this->MailDec();
                // Выключения флага активности письма для указанной страницы почтового ящика
                $this->MailClean($mail_id, $box_id);
                // Редирект на страницу почтового ящика, с которой происходило удаление письма
                if ($redirect) Redirect(self::LINK_DEFAULT.$box_id);
            } else {
                // Если письмо не найдено, редирект на страницу ошибки
                if ($redirect) RedirectError(self::E_MAILNOTFOUND);
            }
        }

        /**
         * TMailBox::MailCrop()
         *
         * @return редирект на страницу почтового ящика
         */
        public function MailCrop()
        {
            $box_id = SafeStr(@$_POST["box"]);

            // Сброс массива на начало для парсинга ключей
            reset($_POST);
            while (list($key, $val) = each($_POST)) {
                if (preg_match('#msg_(.*)#', $key, $matches)) {
                    // Если ключ имеет признак письма, выбор аттрибута
                    $_GET["id"] = SafeInt($matches[1]);
                    $_GET["box"] = $box_id;
                    // Вызов расширенного удаления письма, без перенаправления результата операции
                    $this->MailDelete(false);
                }
            }
            // Редирект на страницу почтового ящика, с которой происходило удаление письма
            Redirect($_SERVER["HTTP_REFERER"]);
        }
    }
?>
