<?
    $ModeID = SafeStr($_GET["tasks"]);

    // Отправка отложенных писем
    if ($ModeID == "minute") {
        include(_ENGINE."tasks/minute.php");
    }
    // Обновление рейтинга городов по объявлениям
    if ($ModeID == "daily") {
        include(_ENGINE."tasks/daily.php");
    }

    if ($ModeID == "morphy") {
        include(_ENGINE."tasks/morphy.php");
    }
?>