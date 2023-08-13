<?
class TDirect extends TInterface
{
    public $MODE;
    private $TPL;
    private $URL;
    private $FAST;

    public function __construct()
    {
        parent::__construct();

        $this->TPL = _TEMPLATE."direct/";
        $this->URL = urlencode(@$_GET["direct"]);
        $this->MODE = SafeStr(@$_GET["m"]);
        $this->FAST = SafeBool("fast");
    }

    public function Redirect()
    {
        if ($this->MODE == "banner") {
            $banner_id = SafeInt(@$_GET["id"]);

            if ($banner_id > 0) {
                require_once(_LIBRARY."lib_banner.php");
                $Banner = new TBanner();
                $Banner->RegisterClick($banner_id);
            }
        }

        if (!$this->FAST) {
            $stream = file_get_contents($this->TPL."default.html");
            $stream = str_replace("#LINKURL", urldecode($this->URL), $stream);
            return $stream;
        } else {
            Redirect(urldecode($this->URL));
        }
    }
}
?>
