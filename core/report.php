<?
    include(_LIBRARY."lib_report.php");
    $ObjectX = new TReport();

    if ($ObjectX->MODE == "stickera") {
        $CONTENT .= $ObjectX->StickerAnnounce();
    } else

    if ($ObjectX->MODE == "stickerc") {
        $CONTENT .= $ObjectX->StickerCompany();
    } else

    die();
?>
