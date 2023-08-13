<?
class TAdminErrors extends TInterface
{
    private $TPL;
    private $DOC;
    public $DATA;
    public $HEAD;

    public function __construct()
    {
        // Управление только администраторы
        if ($_SESSION["USER_ROLE"] > parent::ROLE_ADMIN) {
            Redirect("/");
        }
        parent::__construct();

        $this->HEAD = "Лог ошибок";
        $this->TPL = _TEMPLATE."admin/errors/";
        $this->DOC = SafeStr(@$_REQUEST["doc"]);

        if (empty($this->DOC)) $this->DATA = $this->LogDir(); else $this->DATA = $this->LogFile($this->DOC);
    }

    private function LogDir()
    {
        $handle = @opendir("logs");
        $out = "";
        $stream = file_get_contents($this->TPL."default.html");

        $files = array();
        while (false !== ($file = readdir($handle)))
        {
            if (!is_file("logs/".$file)) continue;
            array_push($files, $file);
        }
        array_multisort($files, SORT_DESC);

        foreach($files as $filename) {
            $out .= "<li><a href='/admin/errors&doc=".$filename."'>".$filename."</a></li>";
        }
        $stream = str_replace("#CONTENT", $out, $stream);

        return $stream;
    }

    private function LogFile($id)
    {
        $stream = file_get_contents($this->TPL."default.html");
        $stream = str_replace("#CONTENT", BBCodeNativeToHTML(addslashes(@file_get_contents("logs/".$id))), $stream);
        return $stream;
    }
}
?>
