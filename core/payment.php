<?
    /**
    * Модуль наложени ябонусов на объявления
    */
    include(_LIBRARY."lib_payment.php");
    $ObjectX = new TPayment();

    if ($ObjectX->MODE == "announce") {
        $CONTENT .= $ObjectX->RenderAnnounce();
    } else

    if ($ObjectX->MODE == "apply") {
        $CONTENT .= $ObjectX->Transform();
    } else

    die();
?>
