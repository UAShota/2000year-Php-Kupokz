<?
    function nt_error_log($error)
    {
        $handle = fopen("logs/".date("Y-m-d").".log", "a");
        fwrite($handle, $error);
        fclose($handle);
    }

    function nt_error_message()
    {
        if ($_SERVER["REMOTE_ADDR"] == "127.0.0.1") return false;

        // todo Add template
        SendLn("На торговой площадке произошел системный сбой, данные записаны и
            будут исправленны в кратчайшие сроки. Приносим извинения за доставленные неудобства
            <a href='/'>Вернуться на сайт</a>");
        die();
    }

    function nt_error_handler($errno, $errstr, $errfile, $errline)
    {
        // todo Add bbcode prop
        if (($errno != E_ERROR) && ($errno != E_USER_NOTICE)) return;

        $trace = debug_backtrace();
        $format = (true) ? "%s %s [b]%s[/b] (%s)\r\n" : "%s %s %s (%s)\r\n";
        $error = sprintf($format, date("Y-m-d H:i:s"), $errfile, $errstr, $errline);

        $format = (true) ? "%s [b](%s)[/b]\r\n" : "%s (%s)\r\n";
        for ($i = 0; $i < count($trace); $i++)
        {
            if (isset($trace[$i]["file"])) {
                $error .= sprintf($format, $trace[$i]["file"], $trace[$i]["line"]);
            }
        }
        if (true) $error .= "[hr]";
        nt_error_log($error);

        if (true) nt_error_message();
    }

    function nt_error_trap($errstr, $errfile, $errline)
    {
        $format = (true) ? "%s %s [b]%s[/b] (%s)[hr]\r\n" : "%s %s %s (%s)\r\n";
        $error = sprintf($format, date("Y-m-d H:i:s"), $errstr, $errfile, $errline);
        nt_error_log($error);
    }

    if ($_SERVER["REMOTE_ADDR"] == "127.0.0.1") {
        error_reporting(E_ALL);
    } else {
        set_error_handler("nt_error_handler");
    }
?>