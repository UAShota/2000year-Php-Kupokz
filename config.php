<?
  require_once("config.inc.php");

  $_CONFIG["LANGUAGE"] = "ru";

  // Количество объявлений на страницу
  $_CONFIG["LIMIT_PAGE"] = 20;
  $_CONFIG["LIMIT_MAIL_PAGE"] = 15;
  // Количество цифр для пейджинга
  $_CONFIG["LIMIT_SELECTOR"] = 4;
  // Количество дополнительных объявлений
  $_CONFIG["LIMIT_ANNOUNCEEXT"] = 4;

  // Количество неверных входов перед баном
  $_CONFIG["LIMIT_BANIP"] = 5;
  // Время бана
  $_CONFIG["LIMIT_BANTIME"] = 3;

  // Количество фоток для гостя
  $_CONFIG["LIMIT_IMAGE_GUEST"] = 4;
  $_CONFIG["LIMIT_IMAGE_AUTH"] = 10;
  $_CONFIG["LIMIT_IMAGE_COMP"] = 30;

  // Размер фотографий по ширине
  $_CONFIG["IMAGE_MAXWSIZE"] = 800;
  // Размер фотографий по высоте
  $_CONFIG["IMAGE_MAXHSIZE"] = 800;
  // Размер фотографии объявления
  $_CONFIG["IMAGE_PHOTOSIZE"] = 190;
  // Размер эскиза фотографии
  $_CONFIG["IMAGE_THUMBSIZE"] = 110;
  // Размер иконки пользователя
  $_CONFIG["IMAGE_ICONSIZE"] = 60;
  // Цвет шрифта для копирайта
  $_CONFIG["IMAGE_FONTCOLOR"] = 0x000000;
  // Шрифт для капчи / копирайта
  $_CONFIG["IMAGE_FONTNAME"]  = "Anorexia2.ttf";

  $_CONFIG["TEXT_NEWMESSAGE"]  = "Новое личное сообщение";
  $_CONFIG["TEXT_NEWCOMMENT"]  = "Новый комментарий";
  $_CONFIG["TEXT_NEWREGISTER"] = "Регистрация на торговой площадке";
  $_CONFIG["TEXT_NEWSUPPORT"]  = "Обращение пользователя";
  $_CONFIG["TEXT_NEWPASWORD"]  = "Восстановление пароля";
  $_CONFIG["TEXT_ANNOUNCEREG"]  = "Управление объявлением";
  $_CONFIG["TEXT_NAME_SUPPORT"]  = "Служба поддержки";
  $_CONFIG["TEXT_NAME_PROJECT"]  = "Купо";
  $_CONFIG["TEXT_NAME_GUEST"]  = "Гость";

  $_CONFIG["COLOR-THEME"][1] = "#800000";
  $_CONFIG["COLOR-THEME"][2] = "#8000FF";
  $_CONFIG["COLOR-THEME"][3] = "#CC0080";
  $_CONFIG["COLOR-THEME"][5] = "#808080";

  // Системные директории
  if (!defined("_TEMPLATE"))   define("_TEMPLATE", "template/");
  if (!defined("_THEME"))      define("_THEME",    "theme/default/");
  if (!defined("_LIBRARY"))    define("_LIBRARY",  "library/");
  if (!defined("_ENGINE"))     define("_ENGINE",   "engine/");
  if (!defined("_FONTS"))      define("_FONTS",    "engine/fonts/");
  if (!defined("_CORE"))       define("_CORE",     "core/");

  // Директории с данными
  if (!defined("_UPLOAD"))     define("_UPLOAD",   "data/upload/");
  if (!defined("_BANNER"))     define("_BANNER",   "data/banner/");
  if (!defined("_ANNOUNCE"))   define("_ANNOUNCE", "data/announce/");
  if (!defined("_USER"))       define("_AVATAR",   "data/user/");
  if (!defined("_COMPANY"))    define("_COMPANY",  "data/company/");
  if (!defined("_STATIC"))     define("_STATIC",   "data/static/");

  // Системные изображения
  if (!defined("_COMPAVATAR")) define("_COMPAVATAR", "compavatar.png");
  if (!defined("_USERAVATAR")) define("_USERAVATAR", "useravatar.png");
  if (!defined("_THUMBPHOTO")) define("_THUMBPHOTO", "thumbphoto.png");
  if (!defined("_THUMBEMPTY")) define("_THUMBEMPTY", "thumbempty.png");
  if (!defined("_THUMBMODER")) define("_THUMBMODER", "thumbmoder.png");
?>