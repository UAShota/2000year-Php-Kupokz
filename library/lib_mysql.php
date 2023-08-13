<?
class TMySQL
{
    private $handle;
    private $link;
    public $count;

    function __construct($server, $port, $login, $pwd, $database)
    {
        $this->link = @mysql_connect($server.":".$port, $login, $pwd) or
            mysql_error();

        if (!mysql_select_db($database, $this->link))
            trigger_error(mysql_error());

        if (!mysql_set_charset("utf8", $this->link))
            trigger_error(mysql_error());

        $this->count = 0;
    }

    private function GetHandle($SQL)
    {
        $this->count++;
        return $this->handle = @mysql_query($SQL.";") or trigger_error(mysql_error());
    }

    public function LFetch($SQL)
    {
        self::GetHandle($SQL);
        $vector = array();
        while ($row = mysql_fetch_assoc($this->handle)) {
            array_push($vector, $row);
        }
        return $vector;
    }

    public function LFetchRows($SQL)
    {
        self::GetHandle($SQL);
        $vector = array();
        while ($row = mysql_fetch_row($this->handle)) {
            array_push($vector, $row);
        }
        return $vector;
    }

    public function LFetchRowsField($SQL)
    {
        self::GetHandle($SQL);
        $vector = array();
        while ($row = mysql_fetch_row($this->handle)) {
            array_push($vector, $row[0]);
        }
        return $vector;
    }

    public function LFetchField($SQL)
    {
        self::GetHandle($SQL." limit 1");
        $row = mysql_fetch_row($this->handle);
        if ($row) return $row[0]; else return false;
    }

    public function LFetchRecord($SQL)
    {
        self::GetHandle($SQL." limit 1");
        return mysql_fetch_assoc($this->handle);
    }

    public function LFetchRecordRow($SQL)
    {
        self::GetHandle($SQL." limit 1");
        return mysql_fetch_row($this->handle);
    }

    public function LFetchRowsSpare($SQL)
    {
        self::GetHandle($SQL);
        $vector = array();
        while ($row = mysql_fetch_row($this->handle)) {
            $vector[$row[0]] = $row[1];
        }
        return $vector;
    }

    public function Execute($SQL)
    {
        return self::GetHandle($SQL);
    }

    public function ExecuteSafe($SQL)
    {
        return $this->handle = @mysql_query($SQL.";");
    }

    public function PrimaryID()
    {
      return mysql_insert_id($this->link);
    }

    public function LAffected()
    {
       return mysql_affected_rows($this->link);
    }

    public function LMaxRows()
    {
        $this->handle = @mysql_query("select FOUND_ROWS() as maxcount");
        $row = mysql_fetch_row($this->handle);
        return $row[0];
    }
}
?>
