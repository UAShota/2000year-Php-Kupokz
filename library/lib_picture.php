<?
    class TPicture extends TInterface
    {
        /**
         * TKupoImage::__construct()
         *
         * @return указатель на конфигурацию
         */
        function __construct()
        {
            parent::__construct();
        }

        /**
         * TKupoImage::TextToPicture()
         *
         * @param mixed $caption
         * @return Конвертация текста в изображение с фоном (капча)
         */
        public function TextToCaptcha($caption, $skip = false)
        {
            // Управляющие не используют капчу
            if (!$skip) {
                $thumb = imagecreatefrompng(_THEME."images/captcha.png");
            } else {
                $thumb = imagecreatefrompng(_THEME."images/captchaskip.png");
                $caption = "";
            }
            for ($index = 0; $index < strlen($caption); $index++) {
                // Текст по случайным ушлом -10..10 градусов
                $radian = rand(-5, 10);
                // Цвет и текст с тенью
                $colour = imagecolorallocate($thumb, rand(0, 200), rand(0, 200), rand(0, 200));
                imagettftext($thumb, 22, $radian, 3 + 21 * $index, 40, $colour,
                    _FONTS.$this->DC["IMAGE_FONTNAME"], $caption[$index]);
                imagettftext($thumb, 22, $radian, 3 + 21 * $index, 43, $colour,
                    _FONTS.$this->DC["IMAGE_FONTNAME"], $caption[$index]);
            }
            // Дефолтовый заголовок
            header('Cache-control: no-cache');
            header("Content-type: image/png");
            imagepng($thumb);
            imagedestroy($thumb);
        }

        /**
         * TKupoImage::DrawText()
         *
         * @param mixed $link
         * @param mixed $width
         * @param mixed $height
         * @param mixed $caption
         * @return Рисование текста на указанном ресурсе
         */
        public function DrawText($link, $width, $height, $caption)
        {
            if ($width > $height)
                $fontSize = round($width / $this->DC["IMAGE_MAXWSIZE"] * 7.5);
            else
                $fontSize = round($height / $this->DC["IMAGE_MAXHSIZE"] * 7.5);
            // Минимальный размер шрифта: 10
            if ($fontSize < 6) $fontSize = 6;

            ImageTTFtext($link, $fontSize, 0, 0, $fontSize,
                $this->DC["IMAGE_FONTCOLOR"], _FONTS.$this->DC["IMAGE_FONTNAME"], $caption);
        }

        /**
         * TKupoImage::Thumb()
         *
         * @param mixed $filename
         * @param mixed $thumbtype
         * @return созданный thumb элемент заданного файла указанного размера
         */
        public function Thumb($filesrc, $filedest, $thumbtype = parent::IMAGE_THUMB)
        {
            list($img_width, $img_height) = @getimagesize($filesrc);
            if (!$img_width || !$img_height) {
                return false;
            }
            if ($thumbtype == parent::IMAGE_THUMB) {
                $scale = min($this->DC["IMAGE_THUMBSIZE"] / $img_width, $this->DC["IMAGE_THUMBSIZE"] / $img_height);
            } else
            if ($thumbtype == parent::IMAGE_PHOTO) {
                $scale = min($this->DC["IMAGE_PHOTOSIZE"] / $img_width, $this->DC["IMAGE_PHOTOSIZE"] / $img_height);
            } else {
                $scale = min($this->DC["IMAGE_MAXWSIZE"] / $img_width, $this->DC["IMAGE_MAXHSIZE"] / $img_height);
            }
            if ($scale > 1) $scale = 1;
            $new_width = $img_width * $scale;
            $new_height = $img_height * $scale;

            if ($thumbtype == parent::IMAGE_THUMB) {
                $boxH = $boxW = $this->DC["IMAGE_THUMBSIZE"];
                $offsetH = $boxH / 2 - $new_height / 2;
                $offsetW = $boxW / 2 - $new_width / 2;
            } else
            if ($thumbtype == parent::IMAGE_PHOTO) {
                $boxH = $boxW = $this->DC["IMAGE_PHOTOSIZE"];
                $offsetH = $boxH / 2 - $new_height / 2;
                $offsetW = $boxW / 2 - $new_width / 2;
            } else {
                $boxH = $new_height;
                $boxW = $new_width;
                $offsetH = 0;
                $offsetW = 0;
            }

            $new_img = @imagecreatetruecolor($boxW, $boxH);
            @imagefill($new_img, 0, 0, @imagecolorallocate($new_img, 255, 255, 255));
            switch (strtolower(substr(strrchr($filesrc, '.'), 1))) {
                case 'jpg':
                case 'jpeg':
                    $src_img = @imagecreatefromjpeg($filesrc);
                    $write_image = 'imagejpeg';
                    break;
                case 'gif':
                    $src_img = @imagecreatefromgif($filesrc);
                    $write_image = 'imagegif';
                    break;
                case 'png':
                    $src_img = @imagecreatefrompng($filesrc);
                    $write_image = 'imagepng';
                    break;
                default:
                    $src_img = $image_method = null;
            }
            $success = $src_img && @imagecopyresampled(
                $new_img,
                $src_img,
                $offsetW, $offsetH, 0, 0,
                $new_width,
                $new_height,
                $img_width,
                $img_height
            ) && $write_image($new_img, $filedest);
            // Free up memory (imagedestroy does not delete files):
            @imagedestroy($src_img);
            @imagedestroy($new_img);

            return $success;
        }
    }
?>