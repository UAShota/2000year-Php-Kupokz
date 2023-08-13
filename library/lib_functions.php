<?
/**
 * Redirect functions
 */
function Redirect($url)
{
    die(header("Location: ".$url));
}
function RedirectBack()
{
    Redirect($_SERVER["HTTP_REFERER"]);
}
function RedirectRegister()
{
    Redirect("/user/5&e=3");
}
function RedirectError($error_id, $linkUrl = null)
{
    $referer = isset($linkUrl) ? $linkUrl : $_SERVER["HTTP_REFERER"];
    Redirect(SafeErrorUrl($referer)."&e=".$error_id);
}

function SendLn($value)
{
    echo $value;
    die();
}
function SendJson($data)
{
    SendLn(json_encode($data));
}

function CheckMail($email)
{
    $regexp = '/^[a-z_0-9\-\.]+@[a-z_0-9\-\.]+\.[a-z]{2,6}$/Ui';
    return preg_match($regexp, $email);
}

function CheckLogin($login)
{
    $regexp = '/^[a-z0-9-_]+$/i';
    return preg_match($regexp, $login);
}

function CheckValidDomainAuto($domain_id)
{
    $count = 0;
    for ($index = 1; $index < strlen($domain_id); $index++)
    {
        if ($domain_id[$index] != $domain_id[$index - 1]) {
            $count++;
        }
    }
    return ($count <= 1);
}

function SafeHttp($value)
{
    if (strpos($value, "http") === false) {
        $value = "http://".$value;
    }
    return $value;
}

function SafeHtml($value, $strip = true)
{
    if (get_magic_quotes_gpc() && $strip) {
        $value = stripslashes($value);
    }
    return mysql_real_escape_string(trim($value));
}

function SafeStr($value, $strip = true)
{
    return SafeHtml(htmlspecialchars(trim($value)), $strip);
}

function SafeInt($value)
{
    return abs((int)$value);
}

function SafeSign($value)
{
    return (int)$value;
}

function SafeBool($value)
{
    return isset($_REQUEST[$value]);
}

function SafeErrorUrl($url)
{
    return preg_replace("#\&e(.+)#", "", $url);
}

function SafeBR($value)
{
    $value = str_replace("\r\n", "<br/>", $value);
    $value = str_replace("\\r\\n", "<br/>", $value);

    return $value;
}

function GetLocalizeBool($value)
{
    if ($value) return "true"; else return "false";
}

function GetLocalizeDate($Date)
{
    $DateInt = strtotime($Date);
    $MonthInt = date("n", $DateInt);
    switch ($MonthInt) {
        case 1:  $Month = "января"; break;
        case 2:  $Month = "февраля"; break;
        case 3:  $Month = "марта"; break;
        case 4:  $Month = "апреля"; break;
        case 5:  $Month = "мая"; break;
        case 6:  $Month = "июня"; break;
        case 7:  $Month = "июля"; break;
        case 8:  $Month = "августа"; break;
        case 9:  $Month = "сентября"; break;
        case 10: $Month = "октября"; break;
        case 11: $Month = "ноября"; break;
        case 12: $Month = "декабря"; break;
    }
    return date("d", $DateInt)." ".$Month;
}

function GetLocalizeTime($date)
{
    $timestamp = strtotime($date);
    return date("d.m.Y", $timestamp)." в ".date("H:i", $timestamp);
}

function GetShortDate($date = null)
{
    if ($date == null) {
        return date("d.m.Y");
    } else {
        return date("d.m.Y", strtotime($date));
    }
}

function ShortString($value, $count = 74)
{
    $value = str_replace("\n", " ", $value);
    $etc = (utf8_strlen($value) > $count);
    $value = utf8_substr($value, 0, $count);
    if ($etc) $value .= "...";

    return $value;
}

function HtmlCheckbox($value)
{
    if ($value) return "checked"; else return false;
}

function MorphyText($text)
{
    require_once(_ENGINE."morphy/morphbasic.php");
    $morph = new MorphBasic();
    $result = $morph->MorphText( $text);
    unset($morph);
    return SafeStr($result);
}

function MorphyFullText($text)
{
    require_once(_ENGINE."morphy/morphbasic.php");
    $morph = new MorphBasic();
    $result = $morph->MorphFullText($text);
    unset($morph);
    return SafeStr($result);
}

function TextSwitch($text)
{
    $str_search = array(
        "й","ц","у","к","е","н","г","ш","щ","з","х","ъ",
        "ф","ы","в","а","п","р","о","л","д","ж","э",
        "я","ч","с","м","и","т","ь","б","ю"
    );
    $str_replace = array(
        "q","w","e","r","t","y","u","i","o","p","[","]",
        "a","s","d","f","g","h","j","k","l",";","'",
        "z","x","c","v","b","n","m",",","."
    );
    return str_replace($str_replace, $str_search, $text);
}

function TextRange($value, $min, $max = false)
{
    if ($max) {
        return (utf8_strlen($value) >= $min) && (utf8_strlen($value) <= $max);
    } else {
        return (utf8_strlen($value) >= $min);
    }
}

function utf_convert($value)
{
    return iconv("utf-8", "cp1251//IGNORE", $value);
}

function utf8_strlen($value)
{
    return mb_strlen($value, "utf-8");
}

function utf8_substr($value, $start, $length)
{
    return mb_substr($value, $start, $length, "utf-8");
}

function utf8_strtoupper($value)
{
    return mb_strtoupper($value, "utf-8");
}

function GetCaptchaDefence()
{
    // Набор доступных символов
    $char = strtoupper(substr(str_shuffle('abcdefghjkmnpqrstuvwxyz'), 0, 4));
    // Два случайных числа и строка из 4-х символов
    $captcha = rand(1, 7).rand(1, 7).$char;
    // Установка капчи в сессию
    $_SESSION["captcha"] = $captcha;

    return $captcha;
}

function GetCaptchaBoolVerify()
{
    // Регистр не имеет значения
    $captcha = strtoupper(SafeStr(@$_REQUEST["captcha"]));
    // Возврат в понятном для валидатора формате
    if (isset($_SESSION["captcha"]) && ($captcha == $_SESSION["captcha"]) || ($_SESSION["USER_ROLE"] < 4))
    {
        GetCaptchaDefence();
        return true;
    } else {
        GetCaptchaDefence();
        return false;
    }
}

function GetStringTime($Date)
{
    $DateInt = strtotime($Date);
    return GetLocalizeDate($Date).", ".date("H:i", $DateInt);
}

function GetMailTime($Date)
{
    $DateInt = strtotime($Date);
    return GetLocalizeDate($Date).date(" H:i", $DateInt);
}

function GetStretchNumber($value, $count = 7)
{
    while (strlen($value) < $count) $value = "0".$value;
    return $value;
}

function BBCodeNativeToHTML($value)
{
    // BBCode to find...
    $in = array('/\[b\](.*?)\[\/b\]/ms',
                '/\[i\](.*?)\[\/i\]/ms',
                '/\[u\](.*?)\[\/u\]/ms',
                '/\[s\](.*?)\[\/s\]/ms',
                '/\[center\](.*?)\[\/center\]/ms',
                '/\[left\](.*?)\[\/left\]/ms',
                '/\[right\](.*?)\[\/right\]/ms',
                '/\[justify\](.*?)\[\/justify\]/ms',
                '/\[ol\](.*?)\[\/ol\]/ms',
                '/\[ul\](.*?)\[\/ul\]/ms',
                '/\[li\](.*?)\[\/li\]/ms',
                '/\[hr\]/ms',
                '/\[table\](.*?)\[\/table\]/ms',
                '/\[tr\](.*?)\[\/tr\]/ms',
                '/\[td\](.*?)\[\/td\]/ms',
                '/\n/ms'
    );
    // And replace them by...
    $out = array('<b>\1</b>',
                 '<i>\1</i>',
                 '<u>\1</u>',
                 '<s>\1</s>',
                 '<div style="text-align: center">\1</div>',
                 '<div style="text-align: left">\1</div>',
                 '<div style="text-align: right">\1</div>',
                 '<div style="text-align: justify">\1</div>',
                 '<ol>\1</ol>',
                 '<ul>\1</ul>',
                 '<li>\1</li>',
                 '<hr>',
                 '<table border="1" cellspacing="0" cellpadding="3">\1</table>',
                 '<tr>\1</tr>',
                 '<td>\1</td>',
                 '<br/>'
    );
    $value = preg_replace($in, $out, $value);
    $value = preg_replace('/\[(.*?)\](.*?)\[(.*?)\]/ms', '\2', $value);
    return $value;
}

function BBCodeToHTML($value)
{
    // BBCode to find...
    $in = array('/\[youtube\](.*?)\[\/youtube\]/ms',
                '/\[size\=(.*?)\](.*?)\[\/size\]/ms',
                '/\[color\=(.*?)\](.*?)\[\/color\]/ms',
                '/\[url\=(.*?)\](.*?)\[\/url\]/ms',
                '/\[font\=(.*?)\](.*?)\[\/font\]/ms',
                '/\[img\=([0-9]+)x([0-9]+)\](.*?)\[\/img\]/ms',
                '/\[quote](.*?)\[\/quote\]/ms'
    );
    // And replace them by...
    $out = array('<iframe width="560" height="315" src="http://www.youtube.com/embed/\1" data-youtube-id="\1" frameborder="0" allowfullscreen=""></iframe>',
                 '<span style="font-size: 1\1px">\2</span>',
                 '<span style="color: \1">\2</span>',
                 '<a rel="nofollow" href="\1"><b>\2</b></a>',
                 '<font face="\1">\2</font>',
                 '<img width="\1" height="\2" src="\3"/>',
                 '<blockquote>\1</blockquote>'
    );
    $value = preg_replace($in, $out, $value);
    $value = BBCodeNativeToHTML($value);
    return $value;
}

function BBCodeToPlain($value)
{
    $pattern = array(
        '/\[(.+?)\]/',
        '/\[/',
        '/\]/',
        '/=/',
        '/\//'
    );
    return preg_replace($pattern, '', $value);
}

function BBCodeToText($value)
{
    return htmlspecialchars($value);
}

function RemoveDirectory($path)
{
    if (is_dir($path) && ($dir = @opendir($path)))
    {
        while(($file = readdir($dir))) {
            if (is_file($path.$file)) {
                unlink($path.$file);
            } else
            if (is_dir($path.$file) && ($file != ".") && ($file != "..")) {
                RemoveDirectory($path.$file."/");
            }
        }
        closedir($dir);
        return rmdir($path);
    }
}

function GetCashValue($vector, $DefaultID)
{
    foreach ($vector as $item) {
        // Имеется элемент по умолчанию
        if ($item[0] == $DefaultID) return $item[1];
    }

    return false;
}

function GetCheckboxOption($SQL, $matches, $controlName)
{
    global $_LOADER;

    // Получение набора элемента по указанному запросу
    $dump = $_LOADER->LFetchRows($SQL);
    // Набор не содержит элементов
    if (count($dump) == 0) return false;

    $out = "";
    foreach ($dump as $item) {
        if (strpos($matches, $item[0]) !== false)
            $out .= "<input type='checkbox' name='".$controlName."[".$item[0]."]' id='".$controlName.$item[0]."' checked>";
        else
            $out .= "<input type='checkbox' name='".$controlName."[".$item[0]."]' id='".$controlName.$item[0]."'>";
        $out .= "<label for='".$controlName.$item[0]."'>".$item[1]."</label><br/>";
    }

    return $out;
}

function GetRadioOption($SQL, $matches, $controlName)
{
    global $_LOADER;

    // Получение набора элемента по указанному запросу
    $dump = $_LOADER->LFetchRows($SQL);
    // Набор не содержит элементов
    if (count($dump) == 0) return false;

    $out = "";
    foreach ($dump as $item) {
        if ($matches == $item[0])
            $out .= "<input type='radio' name='".$controlName."[]' value='".$item[0]."' id='".$controlName.$item[0]."' checked>";
        else
            $out .= "<input type='radio' name='".$controlName."[]' value='".$item[0]."' id='".$controlName.$item[0]."'>";
        $out .= "<label for='".$controlName.$item[0]."'>".$item[1]."</label><br/>";
    }

    return $out;
}

function GetStateContainer($value, $data)
{
    if ($value == 1) $out = "<div class='on'>"; else $out = "<div class='off'>";
    return $out.$data."</div>";
}

function GroupLevelIndent($level, $caption, $skip = 0)
{
    $count = substr_count($level, ".");
    for ($i = $skip; $i < $count; $i++)
        $caption = "&nbsp;&nbsp;&nbsp;&nbsp;".$caption;
    return $caption;
}

function GroupLevelToSQL($level)
{
    $level = substr($level, 0, strlen($level) - 1);
    $level = str_replace(".", ",", $level);
    return $level;
}

function SetSelectOption($from, $to, $interval, $default = -1)
{
    $out = "";
    for ($index = $from; $index <= $to; $index = $index + $interval)
    {
        $out .= "<option ".($index == $default ? "selected" : "")." value='".$index."'>".GetStretchNumber($index, 2)."</option>";
    }
    return $out;
}

if (!function_exists('array_replace_recursive'))
{
    function array_replace_recursive($array, $array1)
    {
        function recurse($array, $array1)
        {
            foreach ($array1 as $key => $value) {
                // create new key in $array, if it is empty or not an array
                if (!isset($array[$key]) || (isset($array[$key]) && !is_array($array[$key]))) {
                    $array[$key] = array();
                }
                // overwrite the value in the base array
                if (is_array($value)) {
                    $value = recurse($array[$key], $value);
                }
                $array[$key] = $value;
            }
            return $array;
        }

        // handle the arguments, merge one by one
        $args = func_get_args();
        $array = $args[0];
        if (!is_array($array)) {
            return $array;
        }
        for ($i = 1; $i < count($args); $i++)
        {
            if (is_array($args[$i])) {
                $array = recurse($array, $args[$i]);
            }
        }
        return $array;
    }
}

        $days = array(
            "Понедельник",
            "Вторник",
            "Среда",
            "Четверг",
            "Пятница",
            "<b>Суббота</b>",
            "<b>Воскресенье</b>
        ");
?>