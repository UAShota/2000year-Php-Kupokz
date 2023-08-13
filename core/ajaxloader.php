<?
    /**
    * Модуль взаимодействия с Ajax
    */
    include(_LIBRARY."lib_ajaxloader.php");
    $ObjectX = new TAjax();

    // Проверка на существование логина
    if ($ObjectX->MODE == "check_login") {
        SendJson($ObjectX->CheckLogin());
    } else

    // Проверка на существование почтового ящика
    if ($ObjectX->MODE == "check_email") {
        SendJson($ObjectX->CheckEmail());
    } else

    // Проверка на корректность капчи
    if ($ObjectX->MODE == "check_captcha") {
        SendJson($ObjectX->CheckCaptcha());
    } else

    // Генерирование капчи
    if ($ObjectX->MODE == "captcha") {
        echo $ObjectX->RenderCaptcha();
    } else

    // Генерирование автозаполнения
    if ($ObjectX->MODE == "autocomp") {
        SendJson($ObjectX->RenderAutocomp());
    } else

    // Добавление объявления в избранное
    if ($ObjectX->MODE == "favourite_announce") {
        echo $ObjectX->FavouriteAnnounce();
    } else

    // Удаление объявления из избранного
    if ($ObjectX->MODE == "favourite_delete") {
        echo $ObjectX->FavouriteDelete();
    } else

    // Генерация поля категория
    if ($ObjectX->MODE == "field_category") {
        echo $ObjectX->RenderCategory();
    } else

    // Генерация поля покупки/продажи/etc
    if ($ObjectX->MODE == "field_action") {
        echo $ObjectX->RenderAction();
    } else

    // Генерация списка быстрой фильтрации
    if ($ObjectX->MODE == "field_paramgroup") {
        echo $ObjectX->RenderParamGroup();
    } else

    // Генерация списка быстрой фильтрации
    if ($ObjectX->MODE == "uploadimg") {
        echo $ObjectX->FastLibUpload();
    } else

    // Генерация поля категория компании
    if ($ObjectX->MODE == "field_category_comp") {
        echo $ObjectX->RenderCategoryCompany();
    } else {}
?>