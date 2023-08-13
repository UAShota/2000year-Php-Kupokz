<?
function UploadClear($path)
{
    if ($handle = opendir($path))
    {
        while (false !== ($file = readdir($handle)))
        {
            $fileName = $path."/".$file;
            if (is_dir($fileName) && ($file != ".") && ($file != "..")) {
                UploadClear($fileName);
            } else
            if (is_file($fileName)) {
                // 1440 = 24*60 = 1 day
                if (filemtime($fileName) < time() - 1440) {
                    unlink($fileName);
                }
            }
        }
        closedir($handle);
    }
    if ($path != _UPLOAD) @rmdir($path);
}

function UpdateCity($loader)
{
    $SQL = "select ID_CITY, sum(ITEMCOUNT) as ITEMCOUNT FROM COUNT_ANNOUNCE A "
        ." group by ID_CITY order by ITEMCOUNT desc limit 15";
    $dump = $loader->LFetchRows($SQL);

    $SQL = "update REF_CITY set IMPORTANT=0 where ID_STATE=1";
    $loader->Execute($SQL);

    for($index = 0; $index < count($dump); $index++)
    {
        $SQL = "update REF_CITY set IMPORTANT=1 where ID_CITY=".$dump[$index][0];
        $loader->Execute($SQL);
    }

    return true;
}

UploadClear(_UPLOAD);
UpdateCity($_LOADER);
?>
