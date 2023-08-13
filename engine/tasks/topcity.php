<?
function UpdateCity($loader)
{
    $SQL = "select ID_CITY, sum(ITEMCOUNT) as ITEMCOUNT FROM COUNT_ANNOUNCE A "
        ." group by ID_CITY order by ITEMCOUNT desc limit 15";
    $dump = $loader->RunNoAssoc($SQL);

    $SQL = "update REF_CITY set IMPORTANT=0 where ID_STATE=1";
    $loader->Execute($SQL);

    for($index = 0; $index < count($dump); $index++)
    {
        $SQL = "update REF_CITY set IMPORTANT=1 where ID_CITY=".$dump[$index][0];
        $loader->Execute($SQL);
    }

    return true;
}
UpdateCity($_LOADER);
?>