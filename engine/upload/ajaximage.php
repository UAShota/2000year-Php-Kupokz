<?php
/*
 * jQuery File Upload Plugin PHP Example 5.5
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

class UploadHandler extends TInterface
{
    private $options;
    private $uid;

    function __construct($options = null)
    {
        parent::__construct();
        $this->uid = $this->GetUploadState();
        $upload = 'data/upload/'.$this->uid."/";

        $this->options = array(
            'script_type' => "thumb",
            'script_url' => "http://".$_SERVER['HTTP_HOST']."/ajax/uploadimg",
            'upload_dir' => $upload,
            'thumb_dir' => $upload."thumb/",
            'accept_file_types' => '/(\.|\/)(gif|png|jpg|jpeg)$/i',
            'discard_aborted_uploads' => true,
            'max_file_size' => 3145728,
            'min_file_size' => 1,
            'max_number_of_files' => $this->GetPhotoMaxCount(-1),
        );
        if ($options) {
            $this->options = array_replace_recursive($this->options, $options);
        }
        if (!is_dir($this->options['thumb_dir'])) {
            if (!mkdir($this->options['thumb_dir'], 0755, true)) trigger_error("mkdir thumb dir");
        }
    }

    private function trim_file_name($name, $type)
    {
        $file_ext = pathinfo($name, PATHINFO_EXTENSION);
        $file_name = md5(microtime(true));

        if (($file_ext == "") && preg_match('/^image\/(gif|jpe?g|png)/', $type, $matches)) {
            $file_ext = $matches[1];
        }
        if ($file_ext == "") {
            $file_ext = "jpg";
        }
        return strtolower($file_name.".".$file_ext);
    }

    private function get_file_object($file_name)
    {
        $file_path = $this->options['upload_dir'].$file_name;
        if (is_file($file_path) && $file_name[0] !== '.')
        {
            $file = new stdClass();
            $file->name = $file_name;
            $file->size = filesize($file_path);
            $file->url = $this->options['upload_dir'].rawurlencode($file->name);
            $file->thumbnail_url = $this->options['thumb_dir'].rawurlencode($file->name);
            $file->delete_url = $this->options['script_url'].'&uid='.$this->uid.'&file='.rawurlencode($file->name);
            $file->delete_type = 'POST';
            return $file;
        }
        return null;
    }

    private function get_file_objects() {
        return array_values(array_filter(array_map(
            array($this, 'get_file_object'),
            scandir($this->options['upload_dir'])
        )));
    }

    private function has_error($uploaded_file, $file, $error) {
        if ($error) {
            return $error;
        }
        if (!preg_match($this->options['accept_file_types'], $file->name)) {
            return 'acceptFileTypes';
        }
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            $file_size = filesize($uploaded_file);
        } else {
            $file_size = $_SERVER['CONTENT_LENGTH'];
        }
        if ($this->options['max_file_size'] && (
                $file_size > $this->options['max_file_size'] ||
                $file->size > $this->options['max_file_size'])
            ) {
            return 'maxFileSize';
        }
        if ($this->options['min_file_size'] &&
            $file_size < $this->options['min_file_size']) {
            return 'minFileSize';
        }
        if (is_int($this->options['max_number_of_files'])
            && ($this->options['script_type'] == "thumb")
            && (count($this->get_file_objects()) >= $this->options['max_number_of_files'])
        ) {
            return 'maxNumberOfFiles';
        }
        return $error;
    }

    private function handle_file_upload($uploaded_file, $name, $size, $type, $error)
    {
        $file = new stdClass();
        $file->name = $this->trim_file_name($name, $type);
        $file->size = intval($size);
        $file->type = $type;
        $error = $this->has_error($uploaded_file, $file, $error);

        if (!$error && $file->name)
        {
            $file_path = $this->options['upload_dir'].$file->name;
            $append_file = !$this->options['discard_aborted_uploads']
                && is_file($file_path)
                && $file->size > filesize($file_path);
            clearstatcache();
            if ($uploaded_file && is_uploaded_file($uploaded_file))
            {
                // multipart/formdata uploads (POST method uploads)
                if ($append_file) {
                    file_put_contents($file_path, fopen($uploaded_file, 'r'), FILE_APPEND);
                } else {
                    move_uploaded_file($uploaded_file, $file_path);
                }
            } else {
                // Non-multipart uploads (PUT method support)
                file_put_contents($file_path, fopen('php://input', 'r'), $append_file ? FILE_APPEND : 0);
            }
            $file_size = filesize($file_path);

            $file_thumb = "";
            // Создание картинок
            if ($file_size === $file->size)
            {
                include(_LIBRARY."lib_picture.php");
                $Picture = new TPicture();

                if ($this->options["script_type"] == "logotype")
                {
                    $file->name = _COMPAVATAR;
                    if (!$Picture->Thumb($file_path, $this->options['upload_dir'].$file->name, parent::IMAGE_PHOTO)) {
                        unlink($file_path);
                        $file->error = 'abort';
                        return $file;
                    } else {
                        unlink($file_path);
                        $file->thumbnail_url = $this->options['upload_dir'].rawurlencode($file->name)."?".time();
                    }
                }

                // Аватарка
                if ($this->options["script_type"] == "avatar")
                {
                    $file->name = _USERAVATAR;
                    if (!$Picture->Thumb($file_path, $this->options['upload_dir'].$file->name, parent::IMAGE_PHOTO)) {
                        unlink($file_path);
                        $file->error = 'abort';
                        return $file;
                    } else {
                        unlink($file_path);
                        $file->thumbnail_url = $this->options['upload_dir'].rawurlencode($file->name)."?".time();
                    }
                }

                // Главное фото объявления
                if ($this->options["script_type"] == "photo")
                {
                    if (!$Picture->Thumb($file_path, $this->options['upload_dir']._THUMBPHOTO, parent::IMAGE_PHOTO)) {
                        unlink($file_path);
                        $file->error = 'abort';
                        return $file;
                    } else {
                        $Picture->Thumb($file_path, $file_path, parent::IMAGE_FULHD);
                    }
                    $file->thumbnail_url = $this->options['upload_dir'].rawurlencode(_THUMBPHOTO);
                    $file_thumb = "&thumb";
                    // Хумб для фото
                    if (!$Picture->Thumb($file_path, $this->options['thumb_dir'].$file->name, parent::IMAGE_THUMB)) {
                        unlink($file_path);
                        $file->error = 'abort';
                        return $file;
                    }
                }

                // Хумб с картинкой
                if ($this->options["script_type"] == "thumb")
                {
                    if (!$Picture->Thumb($file_path, $this->options['thumb_dir'].$file->name, parent::IMAGE_THUMB)) {
                        unlink($file_path);
                        $file->error = 'abort';
                        return $file;
                    }
                    $Picture->Thumb($file_path, $file_path, parent::IMAGE_FULHD);
                    $file->thumbnail_url = $this->options['thumb_dir'].rawurlencode($file->name);
                }

                $file->url = "/".$this->options['upload_dir'].rawurlencode($file->name);
                $file->thumbnail_url = "/".$file->thumbnail_url;
            } else
            if ($this->options['discard_aborted_uploads']) {
                unlink($file_path);
                $file->error = 'abort';
            }
            $file->size = $file_size;
            $file->delete_url = $this->options['script_url'].'&uid='.$this->uid.'&file='.rawurlencode($file->name).$file_thumb;
            $file->delete_type = 'POST';
        } else {
            $file->error = $error;
        }
        return $file;
    }

    public function get()
    {
        $file_name = isset($_REQUEST['file']) ? basename(stripslashes($_REQUEST['file'])) : null;
        if ($file_name) {
            $info = $this->get_file_object($file_name);
        } else {
            $info = $this->get_file_objects();
        }
        header('Content-type: application/json');
        echo json_encode($info);
    }

    public function post()
    {
        if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
            return $this->delete();
        }

        $upload = isset($_FILES[$this->options["script_type"]]) ? $_FILES[$this->options["script_type"]] : null;
        $info = array();

        if ($upload && is_array($upload['tmp_name']))
        {
            foreach ($upload['tmp_name'] as $index => $value)
            {
                $info[] = $this->handle_file_upload(
                    $upload['tmp_name'][$index],
                    isset($_SERVER['HTTP_X_FILE_NAME']) ?
                        $_SERVER['HTTP_X_FILE_NAME'] : $upload['name'][$index],
                    isset($_SERVER['HTTP_X_FILE_SIZE']) ?
                        $_SERVER['HTTP_X_FILE_SIZE'] : $upload['size'][$index],
                    isset($_SERVER['HTTP_X_FILE_TYPE']) ?
                        $_SERVER['HTTP_X_FILE_TYPE'] : $upload['type'][$index],
                    $upload['error'][$index]
                );
            }
        } elseif ($upload || isset($_SERVER['HTTP_X_FILE_NAME'])) {
            $info[] = $this->handle_file_upload(
                isset($upload['tmp_name']) ? $upload['tmp_name'] : null,
                isset($_SERVER['HTTP_X_FILE_NAME']) ?
                    $_SERVER['HTTP_X_FILE_NAME'] : (isset($upload['name']) ?
                        isset($upload['name']) : null),
                isset($_SERVER['HTTP_X_FILE_SIZE']) ?
                    $_SERVER['HTTP_X_FILE_SIZE'] : (isset($upload['size']) ?
                        isset($upload['size']) : null),
                isset($_SERVER['HTTP_X_FILE_TYPE']) ?
                    $_SERVER['HTTP_X_FILE_TYPE'] : (isset($upload['type']) ?
                        isset($upload['type']) : null),
                isset($upload['error']) ? $upload['error'] : null
            );
        }
        header('Vary: Accept');
        $json = json_encode($info);
        $redirect = isset($_REQUEST['redirect']) ? stripslashes($_REQUEST['redirect']) : null;
        if ($redirect) {
            header('Location: '.sprintf($redirect, rawurlencode($json)));
            return;
        }
        if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-type: application/json');
        } else {
            header('Content-type: text/plain');
        }
        echo $json;
    }

    public function delete()
    {
        $file_name = isset($_REQUEST['file']) ? basename(stripslashes($_REQUEST['file'])) : null;
        $file_path = $this->options['upload_dir'].$file_name;
        $success = is_file($file_path) && $file_name[0] !== '.' && unlink($file_path);
        // Удаление основного фото
        if ($success)
        {
            // Удаление хумба
            $file = $this->options['thumb_dir'].$file_name;
            if (is_file($file)) unlink($file);
        }
        // Заказано удаление главной фотки
        if (isset($_REQUEST["thumb"])) {
            $filepath = $this->options['upload_dir']._THUMBPHOTO;
            if (is_file($filepath)) unlink($filepath);
        }

        header('Content-type: application/json');
        echo json_encode($success);
    }

}

if (isset($_FILES["avatar"]) && is_array($_FILES["avatar"])) {
    $scrypt_type = "avatar";
    $max_files = 1;
} else
if (isset($_FILES["logotype"]) && is_array($_FILES["logotype"])) {
    $scrypt_type = "logotype";
    $max_files = 1;
} else
if (isset($_FILES["photo"]) && is_array($_FILES["photo"])) {
    $scrypt_type = "photo";
} else
if (isset($_FILES["thumb"]) && is_array($_FILES["thumb"])) {
    $scrypt_type = "thumb";
}

$options = array();
    if (isset($scrypt_type)) $options["script_type"] = $scrypt_type;
    if (isset($max_files)) $options["max_number_of_files"] = $max_files;
$upload_handler = new UploadHandler($options);


header('Pragma: no-cache');
header('Cache-Control: private, no-cache');
header('Content-Disposition: inline; filename="files.json"');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'OPTIONS':
        break;
    case 'HEAD':
    case 'GET':
        $upload_handler->get();
        break;
    case 'POST':
        $upload_handler->post();
        break;
    case 'DELETE':
        $upload_handler->delete();
        break;
    default:
        header('HTTP/1.1 405 Method Not Allowed');
}
?>
