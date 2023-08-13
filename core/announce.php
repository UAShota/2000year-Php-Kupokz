<?
    /**
    * РњРѕРґСѓР»СЊ РѕР±СЂР°Р±РѕС‚РєРё РѕР±СЉСЏРІР»РµРЅРёР№
    */
    include(_LIBRARY."lib_announce.php");
    $ObjectX = new TAnnounce();

    // Р“РµРЅРµСЂРёСЂРѕРІР°РЅРёРµ Р±Р»РѕРєР° РѕР±СЉСЏРІР»РµРЅРёР№ РґР»СЏ Ajax Р·Р°РїСЂРѕСЃР°
    if ($ObjectX->AJ) {
        SendJson($ObjectX->RenderAnnounceList());
    } else

    if ($ObjectX->MODE == "category") {
        $CONTENT .= $ObjectX->RenderCategory();
    } else

    // Р“РµРЅРµСЂРёСЂРѕРІР°РЅРёРµ С„РѕСЂРјС‹ РїРѕРґР°С‡Рё РѕР±СЉСЏРІР»РµРЅРёСЏ
    if ($ObjectX->MODE == "create") {
        $CONTENT .= $ObjectX->RenderCreate();
    } else

    // РћР±СЂР°Р±РѕС‚РєР° Р·Р°РїСЂРѕСЃР° РЅР° СЃРѕР·РґР°РЅРёРµ РѕР±СЉСЏРІР»РµРЅРёСЏ
    if ($ObjectX->MODE == "insert") {
        $CONTENT .= $ObjectX->Insert();
    } else

    // Р“РµРЅРµСЂРёСЂРѕРІР°РЅРёРµ С„РѕСЂРјС‹ СЂРµРґР°РєС‚РёСЂРѕРІР°РЅРёСЏ РѕР±СЉСЏРІР»РµРЅРёСЏ
    if ($ObjectX->MODE == "edit") {
        $CONTENT .= $ObjectX->RenderEdit();
    } else

    // РћР±СЂР°Р±РѕС‚РєР° Р·Р°РїСЂРѕСЃР° РЅР° РѕР±РЅРѕРІР»РµРЅРёРµ РѕР±СЉСЏРІР»РµРЅРёСЏ
    if ($ObjectX->MODE == "update") {
        $CONTENT .= $ObjectX->Update();
    } else

    // Р“РµРЅРµСЂРёСЂРѕРІР°РЅРёРµ С„РѕСЂРјС‹ СѓРґР°Р»РµРЅРёСЏ РѕР±СЉСЏРІР»РµРЅРёСЏ
    if ($ObjectX->MODE == "drop") {
        $CONTENT .= $ObjectX->RenderDelete();
    } else

    // РћР±СЂР°Р±РѕС‚РєР° Р·Р°РїСЂРѕСЃР° РЅР° СѓРґР°Р»РµРЅРёРµ РѕР±СЉСЏРІР»РµРЅРёСЏ
    if ($ObjectX->MODE == "delete") {
        $CONTENT .= $ObjectX->Delete();
    } else

    // РћР±СЂР°Р±РѕС‚РєР° Р·Р°РїСЂРѕСЃР° РЅР° РєРѕРјРјРµРЅС‚РёСЂРѕРІР°РЅРёРµ
    if ($ObjectX->MODE == "comment") {
        $CONTENT .= $ObjectX->Comment();
    } else

    // РџСЂРѕРІРµСЂРєР° РЅР° РЅР°Р»РёС‡РёРµ РѕР±СЉСЏРІР»РµРЅРёСЏ РІ Р·Р°РїСЂРѕСЃРµ
    if (is_numeric($ObjectX->MODE)) {
        $CONTENT .= $ObjectX->RenderAnnounce();
    } else

    // РџСЂРѕРІРµСЂРєР° РЅР° РЅР°Р»РёС‡РёРµ РїСЂСЏРјРѕР№ СЃСЃС‹Р»РєРё РЅР° РѕС€РёР±РєСѓ
    if ($ObjectX->FL_ERR > 0) {
        $CONTENT .= $ObjectX->RenderError();
    } else

    // Р“РµРЅРµСЂРёСЂРѕРІР°РЅРёРµ С„РѕСЂРјС‹ РїСЂРѕСЃРјРѕС‚СЂР° РѕР±СЉСЏРІР»РµРЅРёСЏ
    {
        $CONTENT .= $ObjectX->RenderCategoryMain();
    }
?>
