<?php
    set_time_limit(0);
    $time_start = microtime(true);

    include(_ENGINE."morphy/morphbasic.php");
    $morph = new MorphBasic();

    $SQL = "select SQL_CALC_FOUND_ROWS ID_ANNOUNCE from ANNOUNCE_DATA where ID_STATE in (1, 2, 3, 4)";
    $_LOADER->RunOneNoAssocEx($SQL);
    $count = $_LOADER->MaxRows();

    for ($index = 0; $index < $count - 1; $index = $index + 100)
    {
        $SQL = "select ID_ANNOUNCE, concat(CAPTION, ' ', uncompress(TEXTDATA)) from ANNOUNCE_DATA"
            ." where ID_STATE in (1, 2, 3, 4) limit ".$index.", 101";
        $dump = $_LOADER->RunNoAssoc($SQL);

        while (list($key, $value) = each($dump))
        {
            $value[1] = $morph->MorphText(UnSafeStr($value[1]));

            $SQL = "update ANNOUNCE_DATA set TEXTINDEX='".SafeStr($value[1])."' where ID_ANNOUNCE=".$value[0];
            $_LOADER->Execute($SQL);
        }
    }

    $time = microtime(true);
    $time = $time - $time_start;

    echo "<hr>".$time;
?>