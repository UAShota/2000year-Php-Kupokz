<?
    /**
    * Модуль управления новостями
    */
    include(_LIBRARY."lib_news.php");
    $ObjectX = new TNews();
    $CONTENT .= $ObjectX->RenderNews();
?>
