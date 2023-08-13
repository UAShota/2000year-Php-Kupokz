<?
    function UploadClear($path)
    {
        if ($handle = opendir($path))
        {
            while (false !== ($file = readdir($handle)))
            {
                $fileName = $path."/".$file;
                if (is_dir($fileName) && ($file != ".") && ($file != "..")) {
                    UploadClear($fileName);
                } else
                if (is_file($fileName)) {
                    // 1440 = 24*60 = 1 day
                    if (filemtime($fileName) < time() - 1440) {
                        unlink($fileName);
                    }
                }
            }
            closedir($handle);
        }
        if ($path != _UPLOAD) @rmdir($path);
    }
    UploadClear(_UPLOAD);
?>