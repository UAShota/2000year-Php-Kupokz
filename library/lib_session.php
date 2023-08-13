<?
class TSession extends TInterface
{
    public function __construct()
    {
        parent::__construct();
        // Отладка компаний на денвере
        if ($_SERVER["REMOTE_ADDR"] == "1127.0.0.1") {
            $server = "аксу.kupo.su";
        } else {
            $server = $_SERVER["HTTP_HOST"];
        }
        // Инициализация пользователя
        self::InitSets();
        self::InitUser();
        // Поиск компании или города
        if (self::InitDomain($server, $hostname)) {
            if (!self::InitCity($hostname)) self::InitCompany($hostname);
        }
    }

    private function InitSets()
    {
        $_SESSION["CITY_ID"] = 88;
        unset($_SESSION["COMPANY_ID"]);
    }

    private function InitDomain($host, &$hostname)
    {
        require_once(_ENGINE."idna/idna_convert.class.php");
        $idna = new idna_convert();
        $domain = $idna->decode($host);

        if (preg_match("#([a-zа-я0-9-_]+)\.([a-zа-я0-9]+)\.([a-zа-я0-9]+)#ui", $domain, $matches)) {
            $hostname = $matches[1];
            return true;
        } else {
            return false;
        }
    }

    private function InitCity($hostname)
    {
        $list = $this->CashCity();
        foreach ($list as $item) {
            if ($item[2] == $hostname) {
                $_SESSION["CITY_ID"] = $item[0];
                return true;
            }
        }
        return false;
    }

    private function InitCompany($hostname)
    {
        if ($hostname === "") return false;

        $SQL = "select ID_COMPANY from COMPANY_DATA where ID_STATE in (".TInterface::STATE_ACTIVE.",".TInterface::STATE_MODER.")"
            ." and (DOMAIN_ACTIVE='".$hostname."' or DOMAIN_AUTO='".$hostname."')";
        $_SESSION["COMPANY_ID"] = $this->DL->LFetchField($SQL) or Redirect("/errors/901.php");
    }

    private function InitUser()
    {
        if (isset($_SESSION["USER_ID"]) || isset($_SESSION["GUEST_ID"])) return false;

        if (!isset($_SESSION["USER_ID"]) && isset($_COOKIE[TInterface::COOKIE_USER])) {
            require_once(_LIBRARY."lib_user.php");
            $User = new TUser();
            $User->Login(SafeStr($_COOKIE[TInterface::COOKIE_USER]));
            unset($User);
        } else

        if (!isset($_SESSION["GUEST_ID"]) && isset($_COOKIE[TInterface::COOKIE_GUEST])) {
            require_once(_LIBRARY."lib_user.php");
            $User = new TUser();
            $User->LoginGuest(SafeStr($_COOKIE[TInterface::COOKIE_GUEST]));
            unset($User);
        }

        if (!isset($_SESSION["USER_ID"]) && !isset($_SESSION["GUEST_ID"])) {
            $_SESSION["USER_ROLE"] = TInterface::ROLE_GUEST;
        }
    }
}
$_SESSIONX = new TSession();
unset($_SESSIONX);
?>
