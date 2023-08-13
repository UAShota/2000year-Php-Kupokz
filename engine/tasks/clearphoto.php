<?
function ClearPhoto($loader)
{
    $SQL = "select ID_ANNOUNCE from ANNOUNCE_DATA where DATE_LIFE > DATE_ADD(now(), INTERVAL -62 DAY) and ID_STATE in (1,4);";
    $dump = $loader->RunNoAssoc($SQL);

    foreach ($dump as $item)
    {

        $photoCount = 0;
        $destImage = _ANNOUNCE.$item[0]."/";

        if ($handle = @opendir($destImage))
        {
            while (false !== ($file = readdir($handle)))
            {
                if ($file == _THUMBPHOTO) {
                    $photoCount += 0.1;
                } else {
                    if (is_file($destImage.$file)) {
                        $photoCount += 1;
                    }
                }
            }
            closedir($handle);
        }

        $SQL = "update ANNOUNCE_DATA set IMAGES=".$photoCount." where ID_ANNOUNCE=".$item[0];
        $loader->Execute($SQL);

    }
    return true;
}
ClearPhoto($_LOADER);
?>