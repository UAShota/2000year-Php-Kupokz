<?

function gl($id)
{
    global $_LOADER;

    $parent = 0;
    $tree = $id.".";

    while ($id != 1) {
        $SQL = "select id_parent from REF_CATEGORY where id_category=".$id;
        $a = $_LOADER->RunOneNoAssocEx($SQL);

        $tree = $a[0].".".$tree;
        $id = $a[0];
    }

    return $tree;
}

function UpdateLevels($loader)
{
    $SQL = "select id_category, caption from REF_CATEGORY where id_state=1";
    $dump = $loader->Run($SQL);

    foreach ($dump as $item) {
        $SQL = "update REF_CATEGORY set level='".gl($item["id_category"])."' where id_category=".$item["id_category"];
        $loader->Execute($SQL);
    }
    return true;
}
UpdateLevels($_LOADER);
?>