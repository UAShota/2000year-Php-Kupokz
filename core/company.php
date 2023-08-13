<?
    /**
    * Модуль управления категориями
    */

    include(_LIBRARY."lib_company.php");
    $ObjectX = new TCompany();

    if ($ObjectX->MODE == "contact") {
        $CONTENT .= $ObjectX->RenderContact();
    } else

    if ($ObjectX->MODE == "product") {
        $CONTENT .= $ObjectX->RenderProduct();
    } else

    if ($ObjectX->MODE == "item") {
        $CONTENT .= $ObjectX->RenderItem();
    } else

    if ($ObjectX->MODE == "news") {
        $CONTENT .= $ObjectX->RenderNews();
    } else

    {
        /*todo*/
        if (!isset($_SESSION["COMPANY_ID"])) {
            /*заглушки =)*/
            if (is_numeric($ObjectX->MODE)) {
                $fl_cat = $ObjectX->GetFlCategory(0);
                $fl_text = "";
                $CONTENT .= $ObjectX->RenderCompanyInfo();
            } else {
                Redirect("/com");
            }
        } else {
            $CONTENT .= $ObjectX->RenderCompany();
        }
    }
?>
