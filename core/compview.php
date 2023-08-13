<?
    include(_LIBRARY."lib_compview.php");
    $ObjectX = new TCompanyView();

    // Генерирование блока объявлений для Ajax запроса
    if ($ObjectX->AJ) {
        SendJson(($ObjectX->RenderCompanyList()));
    } else

    if ($ObjectX->CatID >= 0) {
        $CONTENT .= $ObjectX->RenderCategory();
    } else {
        $CONTENT .= $ObjectX->RenderOverview();
    }
?>
