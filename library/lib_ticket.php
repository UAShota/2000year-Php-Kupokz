<?
class TTicket extends TInterface
{
    private $TPL;
    private $ErrID;

    /*todo*/
    const LINK_MODULE = "/cabuser/ticket";
    const LINK_MODULE2 = "/cabcomp/ticket";

    const TK_STATE_DELETED = 0;
    const TK_STATE_ACTIVE = 1;
    const TK_STATE_ARCHIVE = 2;
    const TK_STATE_APPROOVE = 3;
    const TK_STATE_DEPROOVE = 4;
    const TK_STATE_OPENED = 5;

    const ML_TICKETNEW = 0;

    const E_DELETED  = 1;
    const E_ARCHIVED = 2;
    const E_DELEMPTY = 3;
    const E_ARCEMPTY = 4;
    const E_INVALIDCAPTHCA = 5;
    const E_TRADED = 6;
    const E_TICKETNOTFOUND = 7;
    const E_ITEMNOTFOUND = 8;
    const E_DEPROOVEEMPTY = 10;
    const E_APPROOVEEMPTY = 11;
    const E_APPROOVED = 12;
    const E_DEPROOVED = 13;

    public function __construct()
    {
        parent::__construct();
        $this->TPL = _TEMPLATE."ticket/";
        $this->ErrID = SafeInt(@$_GET["e"]);

        /* todo */
        include(_LIBRARY."lib_mailbox.php");
        include(_LIBRARY."lib_email.php");

        $this->mailbox = new TMailBox();
        $this->mail = new TEmail();
    }

    private function RedirectBack($error_id, $link = self::LINK_MODULE)
    {
        RedirectError($error_id, $link);
    }

    private function RenderError($content = "")
    {
        $error_id = $this->ErrID;
        $errclass = false;
        if ($error_id == 0) return $content;

        if ($error_id == self::E_DELETED) {
            $error = "Заявка удалена успешно";
            $errclass = parent::E__SUCCS;
        } else
        if ($error_id == self::E_ARCHIVED) {
            $error = "Заявка отправлена в архив успешно";
            $errclass = parent::E__SUCCS;
        } else
        if ($error_id == self::E_DELEMPTY) {
            $error = "Нет заявок для удаления. Удалять можно только заявки ожидающие рассмотрение";
            $errclass = parent::E__SUCCS;
        } else
        if ($error_id == self::E_ARCEMPTY) {
            $error = "Нет заявок для архивирования. Отправить в архив можно только заявки ожидающие рассмотрение";
            $errclass = parent::E__SUCCS;
        } else
        if ($error_id == self::E_INVALIDCAPTHCA) {
            $error = "Неверно указан код защиты";
            $errclass = parent::E__ERROR;
        } else
        if ($error_id == self::E_TRADED) {
            $error = "Заявка подана успешно";
            $errclass = parent::E__SUCCS;
        } else
        if ($error_id == self::E_TICKETNOTFOUND) {
            $error = "Указанная заявка не найдена";
            $errclass = parent::E__ERROR;
        } else
        if ($error_id == self::E_ITEMNOTFOUND) {
            $error = "Указанный товар не найден";
            $errclass = parent::E__ERROR;
        } else
        if ($error_id == self::E_DEPROOVEEMPTY) {
            $error = "Нет заявок для отказа";
            $errclass = parent::E__ERROR;
        } else
        if ($error_id == self::E_APPROOVEEMPTY) {
            $error = "Нет заявок для подтверждения";
            $errclass = parent::E__ERROR;
        } else
        if ($error_id == self::E_DEPROOVED) {
            $error = "В заявке отказано успешно";
            $errclass = parent::E__SUCCS;
        } else
        if ($error_id == self::E_APPROOVED) {
            $error = "Заявка подтверждена успешно";
            $errclass = parent::E__SUCCS;
        } else
        {
            $error = "^_^";
        }

        $stream = file_get_contents(_TEMPLATE."default/default_error.html");
        $stream = str_replace("#STYLE", $errclass, $stream);
        $stream = str_replace("#TEXT", $error, $stream);
        $stream = str_replace("#CONTENT", $content, $stream);

        return $stream;
    }

    public function Execute($mode)
    {
        if (isset($_POST["movedel"])) {
            return $this->TicketDelete();
        } else
        if (isset($_POST["movearc"])) {
            return $this->TicketArchive();
        } else
        if (isset($_POST["tradepost"])) {
            return $this->TicketTrade();
        } else
        if ($mode == "trade") {
            return $this->RenderTrade();
        } else
        if ($mode == "archive") {
            return $this->RenderTicket(false);
        } else
        if (is_numeric($mode)) {
            return $this->RenderTicketDetail(SafeInt($mode));
        } else {
            return $this->RenderTicket(true);
        }
    }

    public function ExecuteCompany($mode)
    {
        if ($mode == "archive") {
            return $this->RenderCompanyTicket(false);
        } else
        if (isset($_POST["approove"])) {
            return $this->TicketApproove();
        } else
        if (isset($_POST["deproove"])) {
            return $this->TicketDeproove();
        } else
        if (is_numeric($mode)) {
            return $this->RenderCompanyTicketDetail(SafeInt($mode));
        } else {
            return $this->RenderCompanyTicket(true);
        }
    }

    private function GetTicketEmpty()
    {
        $stream = file_get_contents($this->TPL."empty.html");
        return $stream;
    }

    private function GetTicketStatus($state_id)
    {
        if ($state_id == self::TK_STATE_ACTIVE) {
            return "Ожидает";
        } else
        if ($state_id == self::TK_STATE_APPROOVE) {
            return "Принята";
        } else
        if ($state_id == self::TK_STATE_DEPROOVE) {
            return "Отклонена";
        } else
        if ($state_id == self::TK_STATE_OPENED) {
            return "Рассматривается";
        }
    }

    private function GetTicketAction($text, $state_id)
    {
        if ($state_id == self::TK_STATE_ACTIVE) {
            $text = str_replace("#STAPR", "", $text);
            $text = str_replace("#STDEL", "", $text);
            $text = str_replace("#STARC", "none", $text);
        } else {
            $text = str_replace("#STDEL", "none", $text);
            if ($state_id == self::TK_STATE_OPENED) {
                $text = str_replace("#STAPR", "", $text);
                $text = str_replace("#STARC", "none", $text);
            } else {
                $text = str_replace("#STARC", "", $text);
                $text = str_replace("#STAPR", "none", $text);
            }
        }
        return $text;
    }

    /**
     * TTicket::RenderTicket()
     *
     * @return Список заявлк пользователя
     */
    private function RenderTicket($active)
    {
        $this->SafeUserID($user_field, "bt");
        $state = !$active ? self::TK_STATE_ARCHIVE : self::TK_STATE_ACTIVE;

        $SQL = "select bt.ID_TICKET, ad.ID_ANNOUNCE, ad.CAPTION, rm.CAPTION as MEAS, rc.LITERAL,"
            ." bt.ITEMCOUNT, (bt.ITEMCOUNT*ad.COST) as SUMM, bt.DATE_LIFE, bt.ID_STATE as STATUS,"
            ." cd.ID_COMPANY, cd.ID_TYPE as COMTYPE, cd.CAPTION as COMCAPTION, cd.DOMAIN_ACTIVE"
            ." from BLOCK_TICKET bt, ANNOUNCE_DATA ad, REF_MEAS rm, REF_CURRENCY rc, COMPANY_DATA cd"
            ." where bt.ID_ANNOUNCE=ad.ID_ANNOUNCE and ad.ID_MEAS=rm.ID_MEAS and ad.ID_CURRENCY=rc.ID_CURRENCY"
            ." and cd.ID_USER=ad.ID_USER and cd.ID_TYPE<>".self::COMPANY_TYPE_INFO." and ".$user_field
            ." and bt.ID_STATE_USER=".$state." and bt.ID_STATE<>".self::TK_STATE_DELETED
            ." order by bt.DATE_LIFE desc";
        $dump = $this->DL->LFetch($SQL);

        // Обработка пустого списка заявок
        if (count($dump) == 0) {
            return $this->RenderError($this->GetTicketEmpty());
        }

        $streamblock = file_get_contents($this->TPL."block.html");
        $streamout = "";
        foreach ($dump as $item) {
            $block = str_replace("#LITERAL", $item["LITERAL"], $streamblock);
            $block = str_replace("#COUNT", $item["ITEMCOUNT"], $block);
            $block = str_replace("#SUMM", $item["SUMM"], $block);
            $block = str_replace("#MEAS", $item["MEAS"], $block);
            $block = str_replace("#TICKETID", $item["ID_TICKET"], $block);
            $block = str_replace("#COMPANY", $item["COMCAPTION"], $block);
            $block = str_replace("#CAPTION", $item["CAPTION"], $block);
            $block = str_replace("#ANNOUNCEID", $item["ID_ANNOUNCE"], $block);
            $block = str_replace("#STATUS", $this->GetTicketStatus($item["STATUS"]), $block);
            $block = str_replace("#DATELIFE", GetLocalizeTime($item["DATE_LIFE"]), $block);
            $block = str_replace("#COMPATH", $this->SafeDomain($item["DOMAIN_ACTIVE"]), $block);
            $streamout .= $block;
        }

        $stream = file_get_contents($this->TPL."view.html");
        $stream = str_replace("#CONTENT", $streamout, $stream);
        if (!$active) $stream = $this->GetTicketAction($stream, self::TK_STATE_OPENED);

        return $this->RenderError($stream);
    }

    private function RenderTicketDetail($ticket_id)
    {
        $this->SafeUserID($user_field, "bt");

        $SQL = "select bt.ID_TICKET, ad.ID_ANNOUNCE, ad.CAPTION, rm.CAPTION as MEAS, rc.LITERAL, ad.COST, bt.USERTEXT,"
            ." bt.ITEMCOUNT, (bt.ITEMCOUNT*ad.COST) as SUMM, bt.DATE_LIFE, uncompress(cd.CONTACT) as CONTACT, ad.ID_STATE,"
            ." cd.ID_COMPANY, cd.ID_TYPE as COMTYPE, cd.CAPTION as COMCAPTION, cd.DOMAIN_ACTIVE, bt.ID_STATE as STATUS"
            ." from BLOCK_TICKET bt, ANNOUNCE_DATA ad, REF_MEAS rm, REF_CURRENCY rc, COMPANY_DATA cd"
            ." where bt.ID_ANNOUNCE=ad.ID_ANNOUNCE and ad.ID_MEAS=rm.ID_MEAS and ad.ID_CURRENCY=rc.ID_CURRENCY"
            ." and cd.ID_USER=ad.ID_USER and cd.ID_TYPE<>".self::COMPANY_TYPE_INFO." and ".$user_field
            ." and bt.ID_TICKET=".$ticket_id." and bt.ID_STATE<>".self::TK_STATE_DELETED;
        $item = $this->DL->LFetchRecord($SQL) or RedirectError(self::E_TICKETNOTFOUND);

        $stream = file_get_contents($this->TPL."detail.html");
        $stream = str_replace("#CAPTION", $item["CAPTION"], $stream);
        $stream = str_replace("#COUNT", $item["ITEMCOUNT"], $stream);
        $stream = str_replace("#COST", $item["COST"], $stream);
        $stream = str_replace("#LITERAL", $item["LITERAL"], $stream);
        $stream = str_replace("#SUMM", $item["SUMM"], $stream);
        $stream = str_replace("#MEAS", $item["MEAS"], $stream);
        $stream = str_replace("#TICKETID", $item["ID_TICKET"], $stream);
        $stream = str_replace("#ANNOUNCEID", $item["ID_ANNOUNCE"], $stream);
        $stream = str_replace("#COMPANY", $item["COMCAPTION"], $stream);
        $stream = str_replace("#USERTEXT", ($item["USERTEXT"]), $stream);
        $stream = str_replace("#DATELIFE", GetLocalizeTime($item["DATE_LIFE"]), $stream);
        $stream = str_replace("#STATUS", $this->GetTicketStatus($item["STATUS"]), $stream);
        $stream = str_replace("#COMPATH", $this->SafeDomain($item["DOMAIN_ACTIVE"]), $stream);
        $stream = str_replace("#CONTACT", $this->ContactView(null, false), $stream);
        $stream = str_replace('#IMAGEPATH', $this->GetAnnounceImage($item["ID_ANNOUNCE"], $item["ID_STATE"]), $stream);
        $stream = $this->GetTicketAction($stream, $item["STATUS"]);

        return $stream;
    }

    private function RenderCompanyTicket($active)
    {
        $state = !$active ? self::TK_STATE_ARCHIVE : self::TK_STATE_ACTIVE;

        $SQL = "select bt.ID_TICKET, ad.ID_ANNOUNCE, ad.CAPTION, rm.CAPTION as MEAS, rc.LITERAL,"
            ." bt.ITEMCOUNT, (bt.ITEMCOUNT*ad.COST) as SUMM, bt.DATE_LIFE, bt.USERNAME, ud.LOGIN, ud.ID_USER,"
            ." cd.ID_COMPANY, cd.ID_TYPE as COMTYPE, cd.CAPTION as COMCAPTION, cd.DOMAIN_ACTIVE, bt.ID_STATE as STATUS"
            ." from BLOCK_TICKET bt left join USER_DATA ud on ud.ID_USER=bt.ID_USER,"
            ." ANNOUNCE_DATA ad, REF_MEAS rm, REF_CURRENCY rc, COMPANY_DATA cd"
            ." where bt.ID_ANNOUNCE=ad.ID_ANNOUNCE and ad.ID_MEAS=rm.ID_MEAS and ad.ID_CURRENCY=rc.ID_CURRENCY"
            ." and cd.ID_USER=ad.ID_USER and cd.ID_TYPE<>".self::COMPANY_TYPE_INFO." and bt.ID_STATE_COMP=".$state
            ." and bt.ID_COMPANY=".$_SESSION["USER_COMPANY"]." and bt.ID_STATE<>".self::TK_STATE_DELETED
            ." order by bt.DATE_LIFE desc";
        $dump = $this->DL->LFetch($SQL);

        // Обработка пустого списка заявок
        if (count($dump) == 0) {
            return $this->GetTicketEmpty();
        }

        $streamblock = file_get_contents($this->TPL."block_cmp.html");
        $streamout = "";
        foreach ($dump as $item)
        {
            $block = str_replace("#LITERAL", $item["LITERAL"], $streamblock);
            $block = str_replace("#COUNT", $item["ITEMCOUNT"], $block);
            $block = str_replace("#SUMM", $item["SUMM"], $block);
            $block = str_replace("#CAPTION", $item["CAPTION"], $block);
            $block = str_replace("#MEAS", $item["MEAS"], $block);
            $block = str_replace("#TICKETID", $item["ID_TICKET"], $block);
            $block = str_replace("#ANNOUNCEID", $item["ID_ANNOUNCE"], $block);
            $block = str_replace("#STATUS", $this->GetTicketStatus($item["STATUS"]), $block);
            $block = str_replace("#COMPATH", $this->SafeDomain($item["DOMAIN_ACTIVE"]), $block);
            $block = str_replace("#DATELIFE", GetLocalizeTime($item["DATE_LIFE"]), $block);

            if ($item["ID_USER"] <> self::ROLE_GUEST) {
                $block = str_replace("#USERNAME", "<a href='/user/".$item["ID_USER"]."'>".$item["LOGIN"]."</a>", $block);
            } else {
                $block = str_replace("#USERNAME", $item["USERNAME"], $block);
            }
            $streamout .= $block;
        }

        $stream = file_get_contents($this->TPL."view_cmp.html");
        $stream = str_replace("#CONTENT", $streamout, $stream);
        if (!$active) $stream = $this->GetTicketAction($stream, self::TK_STATE_OPENED);

        return$this->RenderError($stream);
    }

    private function RenderCompanyTicketDetail($ticket_id)
    {
        $SQL = "select bt.ID_TICKET, ad.ID_ANNOUNCE, ad.CAPTION, rm.CAPTION as MEAS, rc.LITERAL, ad.COST,"
            ." bt.ITEMCOUNT, (bt.ITEMCOUNT*ad.COST) as SUMM, bt.DATE_LIFE, uncompress(cd.CONTACT) as CONTACT, ad.ID_STATE,"
            ." cd.ID_COMPANY, cd.ID_TYPE as COMTYPE, cd.CAPTION as COMCAPTION, cd.DOMAIN_ACTIVE, bt.ID_STATE as STATUS,"
            ." uncompress(ud.CONTACT) as CONTACT, ud.ID_USER, ud.LOGIN, bt.USERMAIL, bt.USERPHONE, bt.USERNAME, bt.USERTEXT"
            ." from BLOCK_TICKET bt left join USER_DATA ud on ud.ID_USER=bt.ID_USER, ANNOUNCE_DATA ad,"
            ." REF_MEAS rm, REF_CURRENCY rc, COMPANY_DATA cd"
            ." where bt.ID_ANNOUNCE=ad.ID_ANNOUNCE and ad.ID_MEAS=rm.ID_MEAS and ad.ID_CURRENCY=rc.ID_CURRENCY"
            ." and cd.ID_USER=ad.ID_USER and cd.ID_TYPE<>".self::COMPANY_TYPE_INFO." and bt.ID_COMPANY=".$_SESSION["USER_COMPANY"]
            ." and bt.ID_TICKET=".$ticket_id." and bt.ID_STATE<>".self::TK_STATE_DELETED;
        $item = $this->DL->LFetchRecord($SQL) or RedirectError(self::E_TICKETNOTFOUND);

        if ($item["STATUS"] == self::TK_STATE_ACTIVE) {
            $SQL = "update BLOCK_TICKET set ID_STATE=".self::TK_STATE_OPENED." where ID_TICKET=".$ticket_id;
            $this->DL->Execute($SQL);
        }

        $stream = file_get_contents($this->TPL."detail_cmp.html");
        $stream = str_replace("#CAPTION", $item["CAPTION"], $stream);
        $stream = str_replace("#COUNT", $item["ITEMCOUNT"], $stream);
        $stream = str_replace("#COST", $item["COST"], $stream);
        $stream = str_replace("#LITERAL", $item["LITERAL"], $stream);
        $stream = str_replace("#SUMM", $item["SUMM"], $stream);
        $stream = str_replace("#MEAS", $item["MEAS"], $stream);
        $stream = str_replace("#USERTEXT", ($item["USERTEXT"]), $stream);
        $stream = str_replace("#TICKETID", $item["ID_TICKET"], $stream);
        $stream = str_replace("#ANNOUNCEID", $item["ID_ANNOUNCE"], $stream);
        $stream = str_replace("#COMPANY", $item["COMCAPTION"], $stream);
        $stream = str_replace("#DATELIFE", GetLocalizeTime($item["DATE_LIFE"]), $stream);
        $stream = str_replace("#STATUS", $this->GetTicketStatus($item["STATUS"]), $stream);
        $stream = str_replace("#COMPATH", $this->SafeDomain($item["DOMAIN_ACTIVE"]), $stream);
        //$stream = str_replace("#STYLES", $this->GetAnnounceStyle(null, false), $stream);
        $stream = str_replace("#IMAGEPATH", $this->GetAnnounceImage($item["ID_ANNOUNCE"], $item["ID_STATE"]), $stream);

        if ($item["ID_USER"] <> self::ROLE_GUEST) {
            $contact = $this->ContactView($item["CONTACT"]);
            $username = "<a href='/user/".$item["ID_USER"]."'>".$item["LOGIN"]."</a>";
        } else {
            $contact = "<b>E-mail: </b>".$item["USERMAIL"]."<br /><b>Телефон: </b>".$item["USERPHONE"];
            $username = $item["USERNAME"];
        }
        $stream = str_replace("#CONTACTS", $contact, $stream);
        $stream = str_replace("#USERNAME", $username, $stream);

        $stream = $this->GetTicketAction($stream, $item["STATUS"]);

        return $stream;
    }

    private function TicketDelete()
    {
        $tickets = @$_POST["tickets"];
        if (!is_array($tickets)) $this->RedirectBack(self::E_DELEMPTY);

        $count = 0;
        $this->SafeUserID($user_field, "bt");

        /*todo explode */
        foreach ($tickets as $ticket => $state) {
            $SQL = "update BLOCK_TICKET bt set ID_STATE=".self::TK_STATE_DELETED
                ." where ID_TICKET=".SafeInt($ticket)." and ".$user_field." and ID_STATE=".self::TK_STATE_ACTIVE;
            $this->DL->Execute($SQL);
            if ($this->DL->LAffected() > 0) $count++;
        }

        if ($count == 0)
            $this->RedirectBack(self::E_DELEMPTY);
        else
            $this->RedirectBack(self::E_DELETED);
    }

    private function TicketArchive()
    {
        $tickets = @$_POST["tickets"];
        if (!is_array($tickets)) $this->RedirectBack(self::E_ARCEMPTY);

        $count = 0;
        $this->SafeUserID($user_field, "bt");

        foreach ($tickets as $ticket => $state) {
            $SQL = "update BLOCK_TICKET bt set ID_STATE_USER=".self::TK_STATE_ARCHIVE
                ." where ID_TICKET=".SafeInt($ticket)." and ".$user_field." and ID_STATE<>".self::TK_STATE_ACTIVE;
            $this->DL->Execute($SQL);
            if ($this->DL->LAffected() > 0) $count++;
        }
        if ($count == 0)
            $this->RedirectBack(self::E_ARCEMPTY);
        else
            $this->RedirectBack(self::E_ARCHIVED);
    }

    public function RenderTrade()
    {
        $announce_id = SafeInt(@$_REQUEST["id"]);

        $SQL = "select ad.ID_ANNOUNCE, ad.CAPTION, rm.CAPTION as MEAS, rc.LITERAL, ad.COST,"
            ." uncompress(cd.CONTACT) as CONTACT, ad.ID_STATE, rr.CAPTION as CITY,"
            ." cd.ID_COMPANY, cd.ID_TYPE as COMTYPE, cd.CAPTION as COMCAPTION, cd.DOMAIN_ACTIVE, cd.LOCATION_STREET"
            ." from ANNOUNCE_DATA ad, REF_MEAS rm, REF_CURRENCY rc, COMPANY_DATA cd, REF_CITY rr"
            ." where ad.ID_MEAS=rm.ID_MEAS and ad.ID_CURRENCY=rc.ID_CURRENCY and rr.ID_CITY=ad.ID_CITY and cd.ID_STATE in (1,4)"
            ." and cd.ID_USER=ad.ID_USER and cd.ID_TYPE<>".parent::COMPANY_TYPE_INFO." and ad.ID_ANNOUNCE=".$announce_id;
        $item = $this->DL->LFetchRecord($SQL);

        /*todo*/
        if ($item["ID_ANNOUNCE"] != $announce_id) {
            if (!$this->AJ) {
                RedirectError(self::E_ITEMNOTFOUND, self::LINK_MODULE);
            } else {
                $this->ErrID = self::E_ITEMNOTFOUND;
                SendLn($this->RenderError());
            }
        }

        /*todo*/
        $SQL = "select bs.ID_ANNOUNCE, rs.TAGCLASS, rs.TAGNAME from BLOCK_STYLES bs, REF_STYLE rs where rs.ID_STYLE=bs.ID_STYLE and bs.ID_ANNOUNCE=".$announce_id;
        $styles = $this->DL->LFetchRows($SQL);

        if ($_SESSION["USER_ROLE"] == parent::ROLE_GUEST) {
            $stream = file_get_contents($this->TPL."trade_guest.html");
        } else {
            $stream = file_get_contents($this->TPL."trade_user.html");
        }

        $stream = str_replace("#ANNOUNCEID", $item["ID_ANNOUNCE"], $stream);
        $stream = str_replace("#CAPTION", $item["CAPTION"], $stream);
        $stream = str_replace("#COST", $item["COST"], $stream);
        $stream = str_replace("#CURRENCY", $item["LITERAL"], $stream);
        $stream = str_replace("#MEAS", $item["MEAS"], $stream);
        $stream = str_replace("#CITY", $item["CITY"], $stream);
        $stream = str_replace("#LOCATION_STREET", $item["LOCATION_STREET"], $stream);
        $stream = str_replace("#COMPANY", $item["COMCAPTION"], $stream);
        $stream = str_replace("#CONTACT", "", $stream);
        $stream = str_replace('#IMAGEPATH', $this->GetAnnounceImage($item["ID_ANNOUNCE"], $item["ID_STATE"]), $stream);
        $stream = str_replace("#COMPATH", $this->SafeDomain($item["DOMAIN_ACTIVE"]), $stream);


        /* todo */
            $style = $this->GetAnnounceStyle($item["ID_ANNOUNCE"], $styles);
            $stream = str_replace('#CLASS', $style[0], $stream);
            $stream = str_replace('#TAGDESC', $style[1], $stream);

        if ($this->AJ) {
            SendLn($stream);
        } else {
            $block = file_get_contents($this->TPL."trade_block.html");
            $stream = str_replace("#CONTENT", $stream, $block);
            return $stream;
        }
    }

    private function TicketTrade()
    {
        $id = SafeInt(@$_POST["id"]);
        $name = SafeStr(@$_POST["name"]);
        $phone = SafeStr(@$_POST["phone"]);
        $email = SafeStr(@$_POST["email"]);
        $count = SafeInt(@$_POST["count"]);

        // Проверка капчи
        if (!GetCaptchaBoolVerify()) RedirectError(self::E_INVALIDCAPTHCA, self::LINK_MODULE);

        $SQL = "select cd.ID_COMPANY, cd.ID_USER, ad.CAPTION, ud.EMAIL, cd.CAPTION as COMPANY"
            ." from ANNOUNCE_DATA ad, COMPANY_DATA cd, USER_DATA ud"
            ." where ad.ID_ANNOUNCE=".$id." and ad.ID_GROUP>-1 and ad.ID_STATE in (1,2,3,4)"
            ." and cd.ID_USER=ad.ID_USER and ud.ID_USER=cd.ID_USER";
        $item = $this->DL->LFetchRecord($SQL) or RedirectError(self::E_ITEMNOTFOUND, self::LINK_MODULE);

        $this->SafeUserRegisterEx($user_id, $guest_id);

        if ($user_id == parent::ROLE_GUEST) {
            $SQL = "insert into BLOCK_TICKET (ID_USER, ID_GUEST, ID_ANNOUNCE, ID_COMPANY, ITEMCOUNT, USERNAME, USERPHONE, USERMAIL)"
                ." values (".$user_id.", ".$guest_id.", ".$id.", ".$item["ID_COMPANY"].", ".$count.", '".$name."',"." '".$phone."', '".$email."')";
            $this->DL->Execute($SQL);
        } else {
            $SQL = "insert into BLOCK_TICKET (ID_USER, ID_GUEST, ID_ANNOUNCE, ID_COMPANY, ITEMCOUNT)"
                ." values (".$user_id.", ".$guest_id.", ".$id.", ".$item["ID_COMPANY"].", ".$count.")";
            $this->DL->Execute($SQL);
        }

        /* todo */
        $stream = file_get_contents($this->TPL."mail_new.html");
        $stream = str_replace("#TICKETID", $this->DL->PrimaryID(), $stream);
        $stream = str_replace("#CAPTION", $item["CAPTION"], $stream);
        $stream = str_replace("#COUNT", $count, $stream);

        $this->mailbox->MailPostLocal($item["ID_USER"], "Новая заявка на товар", $stream);
        $this->mail->MailAssign($item["EMAIL"], $item["COMPANY"], "Новая заявка на товар", $stream);

        RedirectError(self::E_TRADED, self::LINK_MODULE);
    }

    /* todo */
    private function TicketAppDeppMail($app, $ticket_id)
    {
        /*todo*/
        $SQL = "select bt.ID_USER, ud.EMAIL, ad.CAPTION, cd.CAPTION as COMPANY"
            ." from BLOCK_TICKET bt, USER_DATA ud, ANNOUNCE_DATA ad, COMPANY_DATA cd"
            ." where ud.ID_USER=bt.ID_USER and ad.ID_ANNOUNCE=bt.ID_ANNOUNCE"
            ." and cd.ID_COMPANY=bt.ID_COMPANY and bt.ID_TICKET=".$ticket_id;
        $item = $this->DL->LFetchRecord($SQL);

        if ($app) {
            $stream = file_get_contents($this->TPL."mail_approove.html");
        } else {
            $stream = file_get_contents($this->TPL."mail_deproove.html");
        }
        $stream = str_replace("#CAPTION", $item["CAPTION"], $stream);
        $stream = str_replace("#COMPANY", $item["COMPANY"], $stream);
        $stream = str_replace("#TICKETID", $ticket_id, $stream);

        $this->mailbox->MailPostLocal($item["ID_USER"], "Заявка на товар ".$item["CAPTION"], $stream);
        $this->mail->MailPush($item["EMAIL"], $item["COMPANY"], "Заявка на товар ".$item["CAPTION"], $stream);
    }

    private function TicketApproove()
    {
        $tickets = @$_POST["tickets"];
        $count = 0;
        if (!is_array($tickets)) $this->RedirectBack(self::E_APPROOVEEMPTY, self::LINK_MODULE2);

        /*todo explode */
        foreach ($tickets as $ticket => $state) {
            $SQL = "update BLOCK_TICKET set ID_STATE=".self::TK_STATE_APPROOVE.", ID_STATE_COMP=".self::TK_STATE_ARCHIVE
                ." where ID_TICKET=".SafeInt($ticket)." and ID_STATE in (".self::TK_STATE_ACTIVE.",".self::TK_STATE_OPENED.")"
                ." and ID_COMPANY=".$_SESSION["USER_COMPANY"];
            $this->DL->Execute($SQL);

            if ($this->DL->LAffected() > 0) {
                $this->TicketAppDeppMail(true, SafeInt($ticket));
                $count++;
            }
        }

        if ($count == 0)
            $this->RedirectBack(self::E_APPROOVEEMPTY, self::LINK_MODULE2);
        else
            $this->RedirectBack(self::E_APPROOVED, self::LINK_MODULE2);
    }

    private function TicketDeproove()
    {
        $count = 0;
        $tickets = @$_POST["tickets"];
        if (!is_array($tickets)) $this->RedirectBack(self::E_DEPROOVEEMPTY, self::LINK_MODULE2);

        /*todo explode */
        foreach ($tickets as $ticket => $state) {
            $SQL = "update BLOCK_TICKET set ID_STATE=".self::TK_STATE_DEPROOVE.", ID_STATE_COMP=".self::TK_STATE_ARCHIVE
                ." where ID_TICKET=".SafeInt($ticket)." and ID_STATE in (".self::TK_STATE_ACTIVE.",".self::TK_STATE_OPENED.")"
                ." and ID_COMPANY=".$_SESSION["USER_COMPANY"];
            $this->DL->Execute($SQL);

            if ($this->DL->LAffected() > 0) {
                $this->TicketAppDeppMail(false, SafeInt($ticket));
                $count++;
            }
        }

        if ($count == 0)
            $this->RedirectBack(self::E_DEPROOVEEMPTY, self::LINK_MODULE2);
        else
            $this->RedirectBack(self::E_DEPROOVED, self::LINK_MODULE2);
    }
}