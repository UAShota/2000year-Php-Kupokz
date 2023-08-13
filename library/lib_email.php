<?
    class TEmail extends TInterface
    {
        /**
         * Переменные доступа к БД, ссылка на массив настроек
         */
        private $TPL;
        private $SMTP;

        /**
         * TEmail::__construct()
         *
         * @param
         * @return класс связи с БД и код запрошенного действия
         */
        public function __construct()
        {
            parent::__construct();

            include(_ENGINE."smtp/smtp.php");
            include(_ENGINE."smtp/sasl.php");

            $this->TPL = _TEMPLATE."email/";
            $this->SMTP = new smtp_class();
            $this->SMTP->host_name = $this->DC["SMTP_SERVER"];
            $this->SMTP->user = $this->DC["SMTP_LOGIN"];
            $this->SMTP->password = $this->DC["SMTP_PASSWORD"];
        }

        /**
         * TEmail::SendSimple()
         *
         * @param mixed $login
         * @param mixed $email
         * @param mixed $subject
         * @param mixed $body
         * @param mixed $reply
         * @return Отправляет письмо указанному пользователю
         */
        public function SendSimple($login, $email, $subject, $body, $reply = null)
        {
            if ($reply == null) {
                $reply = $this->DC["SMTP_SUPPORT"];
            }
            $boundary = md5(uniqid(time()));

            $stream = file_get_contents($this->TPL."default.html");
            $stream = str_replace("#TEXTDATA", ($body), $stream);
            $stream = str_replace("#SITEPATH", $_SERVER["HTTP_HOST"], $stream);
            $stream = str_replace("#SITETITLE", $this->DC["SITE_TITLE"], $stream);

            $headers  = array();
            array_push($headers, "Subject: ".$subject);
            array_push($headers, "To: ".$login." <".$email.">");
            array_push($headers, "From: ".$this->DC["TEXT_NAME_SUPPORT"]." ".$this->DC["TEXT_NAME_PROJECT"]." <".$reply.">");
            array_push($headers, "Reply-To: ".$reply);
            array_push($headers, "Mime-Version: 1.0");

            array_push($headers, "Content-Type: multipart/alternative; boundary=\"$boundary\"");
            array_push($headers, "");
            array_push($headers, "--$boundary");
            array_push($headers, "Content-Type: text/plain; charset=utf-8");
            array_push($headers, "Content-Transfer-Encoding: base64");
            array_push($headers, "");
            array_push($headers, chunk_split(base64_encode(strip_tags($stream))));

            array_push($headers, "--$boundary");
                array_push($headers, "Content-Type: multipart/related; boundary=\"$boundary-media\"");
                array_push($headers, "");

                array_push($headers, "--$boundary-media");
                array_push($headers, "Content-Type: text/html; charset=utf-8");
                array_push($headers, "Content-Transfer-Encoding: base64");
                array_push($headers, "");
                array_push($headers, chunk_split(base64_encode($stream)));
                array_push($headers, "");

                if (preg_match_all("#\"cid:(.+?)\"#ms", $stream, $matches))
                {
                    for ($index = 0; $index < count($matches); $index++)
                    {
                        $filename = "./images/".$matches[1][$index].".png";
                        if (!file_exists($filename)) continue;

                        array_push($headers, "--$boundary-media");
                        array_push($headers, "Content-Type: image/png");
                        array_push($headers, "Content-Transfer-Encoding: base64");
                        array_push($headers, "Content-Disposition: inline");
                        array_push($headers, "Content-ID: <".$matches[1][$index].">");
                        array_push($headers, "");
                        $filedump = fopen($filename, "rb");
                            array_push($headers, chunk_split(base64_encode(fread($filedump, filesize($filename)))));
                        fclose($filedump);
                        array_push($headers, "");
                    }
                }
                array_push($headers, "--$boundary-media");
            array_push($headers, "--$boundary");

            return ($this->SMTP->SendMessage($reply, array($email), $headers, "") != 0);
        }

        public function SendMailing()
        {
            $SQL = "select *, uncompress(BODY) as BODY from BLOCK_DELIVERY order by DATE_LIFE desc limit 0,10";
            $dump = $this->DL->LFetch($SQL);

            foreach ($dump as $item)
            {
                switch ($item["ID_TYPE"]) {
                    case parent::MAILTYPE_SUPPORT:
                        $replyto = $this->DC["SMTP_SUPPORT"];
                        break;
                    case parent::MAILTYPE_FROMUSER:
                        $replyto = $item["REPLYTO"];
                        break;
                    default:
                        $replyto = $this->DC["SMTP_NOREPLY"];
                        break;
                }

                if (self::SendSimple($item["USERTO"], $item["MAILTO"], $item["SUBJECT"], $item["BODY"], $replyto))
                {
                    $SQL = "delete from BLOCK_DELIVERY where ID_DELIVERY=".$item["ID_DELIVERY"];
                    $this->DL->Execute($SQL);
                }
            }
        }

        private function UserNotify($type, $user_id)
        {
            $SQL = "select ".$type.", EMAIL from USER_DATA where ID_USER=".$user_id;
            $item = $this->DL->LFetchRecordRow($SQL);

            return ($item[0] == 1);
        }

        private function MailAssign($mailto, $userto, $subject, $body, $type = self::MAILTYPE_NOREPLY, $replyto = "")
        {
            $SQL = "insert into BLOCK_DELIVERY (MAILTO, USERTO, SUBJECT, BODY, ID_TYPE, REPLYTO) values ("
                ."'".SafeHtml($mailto)."', '".SafeHtml($userto)."', '".SafeHtml($subject)
                ."', compress('".SafeHtml($body)."'), ".$type.", '".SafeHtml($replyto)."')";
            $this->DL->Execute($SQL);
        }

        public function MailPush($mailto, $userto, $subject, $body, $type = self::MAILTYPE_NOREPLY, $replyto = "")
        {
            $this->MailAssign($mailto, $userto, ($subject), ($body), $type, $replyto);
        }

        public function MailRegister($login, $email, $pwd)
        {
            $stream = file_get_contents($this->TPL."register.html");
            $stream = str_replace("#LOGIN", $login, $stream);
            $stream = str_replace("#PWD", $pwd, $stream);
            $stream = str_replace("#SITEPATH", $_SERVER["HTTP_HOST"], $stream);
            $this->MailAssign($email, $login, $this->DC["TEXT_NEWREGISTER"], $stream, self::MAILTYPE_SUPPORT);
        }

        public function MailComment($self, $troll_id, $comment_id, $comment)
        {
            if (!self::UserNotify("M_COMMENT", $self["ID_USER"])) return true;

            $SQL = "select LOGIN from USER_DATA where ID_USER=".$troll_id;
            $login = $this->DL->LFetchRecordRow($SQL);

            $stream = file_get_contents($this->TPL."comment.html");
            $stream = str_replace("#CAPTION",   SafeHtml($self["CAPTION"], false), $stream);
            $stream = str_replace("#ANNID",     $self["ID_ANNOUNCE"], $stream);
            $stream = str_replace("#TROLLID",   $troll_id, $stream);
            $stream = str_replace("#TROLL",     $login[0], $stream);
            $stream = str_replace("#COMMENTID", $comment_id, $stream);
            $stream = str_replace("#COMMENT",   SafeBR($comment), $stream);
            $this->MailAssign($self["EMAIL"], $self["LOGIN"], $this->DC["TEXT_NEWCOMMENT"], $stream);

            return $stream;
        }

        public function MailMessage($self, $troll_id, $message_id, $subject, $message)
        {
            if (!self::UserNotify("M_MAILBOX", $self["ID_USER"])) return true;

            $SQL = "select LOGIN from USER_DATA where ID_USER=".$troll_id;
            $login = $this->DL->LFetchRecordRow($SQL);

            $stream = file_get_contents($this->TPL."mailbox.html");
            $stream = str_replace("#MAILID",   $message_id, $stream);
            $stream = str_replace("#CAPTION",  $subject, $stream);
            $stream = str_replace("#TEXTDATA", SafeBR($message), $stream);
            $stream = str_replace("#TROLLID",  $troll_id, $stream);
            $stream = str_replace("#TROLL",    $login[0], $stream);
            $this->MailAssign($self["EMAIL"], $self["LOGIN"], $this->DC["TEXT_NEWMESSAGE"], $stream);
        }

        public function MailFastRegister($email, $passkey)
        {
            $stream = file_get_contents($this->TPL."fastreg.html");
            $stream = str_replace("#PASSKEY", $passkey, $stream);
            $this->MailAssign($email, $this->DC["TEXT_NAME_GUEST"], $this->DC["TEXT_NEWREGISTER"], $stream);
        }

        public function MailSupport($email, $body)
        {
            $stream = file_get_contents($this->TPL."support.html");
            $stream = str_replace("#TEXTDATA", SafeBR($body), $stream);
            $this->MailAssign($this->DC["SMTP_SUPPORT"], $this->DC["TEXT_NAME_PROJECT"],
                $this->DC["TEXT_NEWSUPPORT"], $stream, parent::MAILTYPE_FROMUSER, $email);
        }

        public function MailComplaint($announce_id, $textdata, $reason)
        {
            $stream = file_get_contents($this->TPL."complaint.html");
            $stream = str_replace("#ANNOUNCEID", $announce_id, $stream);
            $stream = str_replace("#TEXTDATA", SafeBR($textdata), $stream);
            $stream = str_replace("#ANNOUNCENUM", GetStretchNumber($announce_id), $stream);

            $this->MailAssign($this->DC["SMTP_SUPPORT"], $this->DC["TEXT_NAME_PROJECT"], $reason, $stream);
        }
    }
?>