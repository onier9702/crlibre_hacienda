<?php
/*
 * Copyright (C) 2017-2025 CRLibre <https://crlibre.org>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/** @file module.php
 * Main Files module, this is where it all begins...
 * Not much more to say
 * @todo this may become a library, not a module
 */
#####################################################################
#
# Files (Upload and such)
#
#####################################################################

/** \addtogroup Modules
 *  @{
 */
/**
 * \defgroup Files
 * @{
 */
//! File size is too big
define('ERROR_FILES_TOO_BIG', '-400');

//! File extention not allowed
define('ERROR_FILES_NOT_ALLOWED', '-401');

//! Some error while uploading
define('ERROR_FILES_UPLOAD_ERROR', '-402');

//! Some error while downloading
define('ERROR_FILES_DOWNLOAD_ERROR', '-403');

//! File type not allowed
define('ERROR_FILES_EXT_NOT_ALLOWED', '-404');

//! The requested file was not found
define('ERROR_FILES_NOT_FOUND', '-405');

/**
 * Boot up procedure
 */
function files_bootMeUp()
{
    //
}

/**
 * Init function
 */
function files_init()
{
    $paths = array(
        array(
            'r'         => 'filesGetUrl',
            'action'    => 'filesGetUrl',
            'access'    => "users_openAccess",
            'params'    => array(
                array("key" => "downloadCode", "def" => "", "req" => true)
            )
        ),
        array(
            'r'         => 'files_view_file',
            'action'    => 'files_viewPublic',
            'access'    => "users_openAccess"
        ),
        array(
            'r'         => 'upload',
            'action'    => 'files_upload',
            'access'    => "users_openAccess"
        )
    );

    return $paths;
}

/**
 * @brief Creates a file path
 *
 * @param idUser The id of the user
 * @param type The type of file, this will define part of the path
 *
 * @return 
 * */
function filesGetUrl($codigo = '')
{
    /**
     * Esta funcion se puede llamar desde GET POST si se envian los siguientes parametros
     * w=files
     * r=filesGetUrl
     * downloadCode=codigo de descarga del file
     * Tambien se puede llamar desde un metodo de la siguiente manera:
     * modules_loader("files");       <-- Esta funcion importa el modulo
     * filesGetUrl('codigo');  <------------ esta funcion retorna el URL del file codigo es el downloadCode de la db
     * */
    if ($codigo == '')
        $codigo = params_get('downloadCode', '');

    $q = sprintf("SELECT * FROM files WHERE downloadCode = '%s'", db_escape($codigo));
    $file = db_query($q, 1);
    if ($file != ERROR_DB_NO_RESULTS_FOUND)
    {
        $filePath = files_createPath($file->idUser, $file->type) . $file->name;
        return $filePath;
    }

    return false;
}

function files_createPath($idUser, $type)
{
    return sprintf('%s%s/%s/', conf_get('basePath', 'files', '/'), $idUser, $type);
}

/**
 * @brief Creates a download link for each file
 *
 * @param name The name of the file, just the actual name is good
 * @param idUser The id of the user owner of the file
 *
 * @return 
 * */
function files_createDownloadCode($name, $idUser)
{
    return md5($name . "//" . time() . $idUser);
}

/**
 * @brief Upload files in the system and stores them in the db
 *
 * @param type The type of file to upload, this will define part of the path to be stored in
 * @param finalName The final name that you want to use for it
 * @param ext Allowed extentions to be accepted
 * @param maxSize The maximum file size in Mb to be accepted
 * @param del Should it be deleted if it already exists?
 *
 * @return 
 * */
function files_upload($type = 'attach', $finalName = false, $ext = false, $maxSize = 0, $del = true)
{
    grace_debug("========== UPLOAD DEBUG START ==========");

    if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') === false) {
        file_put_contents('/tmp/raw_after.txt', file_get_contents("php://input"));
    }

    grace_debug("post_max_size: " . ini_get('post_max_size'));
    grace_debug("upload_max_filesize: " . ini_get('upload_max_filesize'));
    grace_debug("memory_limit: " . ini_get('memory_limit'));

    grace_debug("CONTENT_LENGTH: " . ($_SERVER['CONTENT_LENGTH'] ?? 'N/A'));
    grace_debug("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A'));

    if (function_exists('getallheaders')) {
        grace_debug("HEADERS:");
        grace_debug(print_r(getallheaders(), true));
    }

    $raw = file_get_contents("php://input");
    grace_debug("RAW INPUT SIZE: " . strlen($raw));

    grace_debug("RAW _FILES:");
    grace_debug(print_r($_FILES, true));

    grace_debug("RAW _POST:");
    grace_debug(print_r($_POST, true));

    if (empty($_FILES)) {
        grace_debug("ERROR: _FILES is empty → REQUEST BODY NOT PARSED");
        grace_debug("========== UPLOAD DEBUG END ==========");
        return ERROR_FILES_UPLOAD_ERROR;
    }

    global $user;

    grace_debug("========== UPLOAD DEBUG START ==========");

    // 🔥 0. SERVER / PHP LIMITS (CRITICAL)
    grace_debug("post_max_size: " . ini_get('post_max_size'));
    grace_debug("upload_max_filesize: " . ini_get('upload_max_filesize'));
    grace_debug("memory_limit: " . ini_get('memory_limit'));

    // 🔥 1. REQUEST INFO
    grace_debug("CONTENT_LENGTH: " . ($_SERVER['CONTENT_LENGTH'] ?? 'NOT SET'));
    grace_debug("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);

    // 🔥 2. HEADERS (THIS WILL EXPOSE POSTMAN ISSUES)
    if (function_exists('getallheaders')) {
        grace_debug("HEADERS:");
        grace_debug(print_r(getallheaders(), true));
    }

    // 🔥 3. RAW INPUT SIZE
    $rawInput = file_get_contents("php://input");
    grace_debug("RAW INPUT SIZE: " . strlen($rawInput));

    // 🔥 4. FILES + POST
    grace_debug("RAW _FILES:");
    grace_debug(print_r($_FILES, true));

    grace_debug("RAW _POST:");
    grace_debug(print_r($_POST, true));

    // 🚨 If empty → we already know it's infra problem
    if (empty($_FILES)) {
        grace_debug("ERROR: _FILES is empty → REQUEST BODY NOT PARSED");

        grace_debug("========== UPLOAD DEBUG END ==========");
        return ERROR_FILES_UPLOAD_ERROR;
    }

    $key = array_key_first($_FILES);
    grace_debug("Detected file key: " . $key);

    $file = $_FILES[$key];

    grace_debug("FILE STRUCT:");
    grace_debug(print_r($file, true));

    // 🔥 5. PHP upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        grace_debug("UPLOAD ERROR CODE: " . $file['error']);

        $errors = [
            UPLOAD_ERR_INI_SIZE   => "Exceeded upload_max_filesize",
            UPLOAD_ERR_FORM_SIZE  => "Exceeded MAX_FILE_SIZE",
            UPLOAD_ERR_PARTIAL    => "Partial upload",
            UPLOAD_ERR_NO_FILE    => "No file sent",
            UPLOAD_ERR_NO_TMP_DIR => "Missing tmp dir",
            UPLOAD_ERR_CANT_WRITE => "Disk write failed",
            UPLOAD_ERR_EXTENSION  => "Stopped by PHP extension",
        ];

        grace_debug("UPLOAD ERROR MSG: " . ($errors[$file['error']] ?? "Unknown"));

        grace_debug("========== UPLOAD DEBUG END ==========");
        return ERROR_FILES_UPLOAD_ERROR;
    }

    // 🔥 6. TMP FILE CHECK
    if (!file_exists($file['tmp_name'])) {
        grace_debug("ERROR: tmp file does not exist: " . $file['tmp_name']);
        grace_debug("========== UPLOAD DEBUG END ==========");
        return ERROR_FILES_UPLOAD_ERROR;
    }

    grace_debug("TMP FILE EXISTS: " . $file['tmp_name']);

    // 🔥 7. NAME + EXT
    $originalName = $file['name'];

    if (empty($originalName)) {
        grace_debug("ERROR: filename is empty");
        return ERROR_FILES_UPLOAD_ERROR;
    }

    if ($finalName === false) {
        $finalName = basename($originalName);
    } else {
        $finalName = $finalName . "." . pathinfo($originalName, PATHINFO_EXTENSION);
    }

    grace_debug("FINAL NAME: " . $finalName);

    $extension = pathinfo($finalName, PATHINFO_EXTENSION);
    grace_debug("EXTENSION DETECTED: " . $extension);

    if ($ext == false) {
        $ext = conf_get("allowedExt", "files", "jpg,png,p12,xml");
    }

    if ($ext != "*") {
        $allowed = explode(",", $ext);
        grace_debug("ALLOWED EXT: " . implode(",", $allowed));

        if (!in_array($extension, $allowed)) {
            grace_debug("ERROR: extension not allowed");
            return ERROR_FILES_EXT_NOT_ALLOWED;
        }
    }

    // 🔥 8. SIZE VALIDATION
    if ($maxSize == false) {
        $maxSize = conf_get("maxUploadSize", "files", "2");
    }

    if ($file['size'] > $maxSize * 1000000) {
        grace_debug("ERROR: file too big");
        return ERROR_FILES_TOO_BIG;
    }

    grace_debug("FILE SIZE OK: " . $file['size']);

    // 🔥 9. PATH
    $targetDir = files_createPath($user->idUser, $type);
    grace_debug("TARGET DIR: " . $targetDir);

    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
        grace_debug("Created directory");
    }

    $targetFile = $targetDir . $finalName;
    grace_debug("TARGET FILE: " . $targetFile);

    // 🔥 10. MOVE
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        grace_debug("SUCCESS: file moved");

        $downloadCode = files_createDownloadCode($finalName, $user->idUser);

        $idFile = files_save([
            'md5'         => md5_file($targetFile),
            'name'        => $finalName,
            'timestamp'   => time(),
            'size'        => $file['size'],
            'idUser'      => $user->idUser,
            'downloadCode'=> $downloadCode,
            'fileType'    => "",
            'type'        => $type
        ]);

        grace_debug("========== UPLOAD DEBUG END ==========");

        return [
            'idFile' => $idFile,
            'name' => $finalName,
            'downloadCode' => $downloadCode
        ];
    } else {
        grace_debug("ERROR: move_uploaded_file FAILED");
        grace_debug("Check permissions or open_basedir");

        grace_debug("========== UPLOAD DEBUG END ==========");
        return ERROR_FILES_UPLOAD_ERROR;
    }
}

/**
 * @bried Saves a file in the db
 *
 * @param The dets of the file, an array with all of them
 */
function files_save($dets)
{
    $q = sprintf("INSERT INTO files (md5, name, timestamp, size, idUser, downloadCode, fileType, type)
        VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')", db_escape($dets['md5']), db_escape($dets['name']), db_escape($dets['timestamp']), db_escape($dets['size']), db_escape($dets['idUser']), db_escape($dets['downloadCode']), db_escape($dets['fileType']), db_escape($dets['type'])
    );

    db_query($q, 0);

    # Lets find out which file it was
    $q      = sprintf("SELECT idFile FROM files WHERE downloadCode = '%s'", db_escape($dets['downloadCode']));
    $idFile = db_query($q, 1);

    return $idFile->idFile;
}

/**
 *  @brief Gets a file from the db
 *
 *  @param idFile The id of the file
 */
function files_load($idFile)
{
    $q = sprintf("SELECT * FROM files WHERE idFile = '%s'", db_escape($idFile));
    $file = db_query($q, 1);
    if ($file != ERROR_DB_NO_RESULTS_FOUND)
    {
        $file->path = files_createPath($file->idUser, $file->type) . $file->name;
        return $file;
    }

    return false;
}

/**
 * @brief Present private files to other people
 *
 * @param file The full path to the file
 * @param internal If this is an internal file
 *
 * @return 
 * */
function files_presentFile($file, $internal = true)
{
    if ($internal && !file_exists($file))
        $file = conf_get('resourcesPath', 'core', '') . "404FileNotFound.svg";

    $type = files_getMimeTypeFromExtention(basename($file));
    $thisFileName = time() . basename($file);
    header('Content-Type: ' . $type);
    header('Content-Disposition: filename=' . $thisFileName);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    if ($internal == false)
    {
        echo file_get_contents($file);
        exit;
    }
    else
    {
        ob_clean();
        flush();
        readfile($file);
    }

    exit;
}

/**
 * @brief Gets the mime type according to the extension
 * Thanks to: http://www.thecave.info/php-get-mime-type-from-file-extension/
 *
 * @param file The file's name 
 *
 * @return The Mime Type for said file
 * */
function files_getMimeTypeFromExtention($file)
{
    # Internal non-complete list of mime types, but the ones we need at least
    $mimeTypes = array(
        "pdf"     => "application/pdf",
        "exe"     => "application/octet-stream",
        "zip"     => "application/zip",
        "docx"    => "application/msword",
        "doc"     => "application/msword",
        "xls"     => "application/vnd.ms-excel",
        "ppt"     => "application/vnd.ms-powerpoint",
        "gif"     => "image/gif",
        "png"     => "image/png",
        "jpeg"    => "image/jpg",
        "jpg"     => "image/jpg",
        "mp3"     => "audio/mpeg",
        "wav"     => "audio/x-wav",
        "mpeg"    => "video/mpeg",
        "mpg"     => "video/mpeg",
        "mpe"     => "video/mpeg",
        "mov"     => "video/quicktime",
        "avi"     => "video/x-msvideo",
        "3gp"     => "video/3gpp",
        "css"     => "text/css",
        "jsc"     => "application/javascript",
        "js"      => "application/javascript",
        "php"     => "text/html",
        "htm"     => "text/html",
        "html"    => "text/html",
        "svg"     => "image/svg+xml",
    );

    $extension = explode('.', $file);
    $extension = end($extension);
    $extension = strtolower($extension);

    if (array_key_exists($extension, $mimeTypes))
        return $mimeTypes[$extension];
    else
        return "application/octet-stream";
}

/**
 * Resizes images to specified dimensions, currently I work with 'max' dimension either height or width
 * I have to give it more options.
 * Each file will have a name exactly like the parent one, but with an appended 'v_size_'
 * before it, or at the end, I don't know.
 * @param filename The full path to the file
 * @param sizes An array with all the sizes that you want
 */
function files_resizeImg($fileName, $sizes = array())
{
    # Base name without extention
    $fileParts = pathinfo($fileName);
    $baseName = $fileParts['filename'];
    # Create the new image
    $newImage = new \Eventviva\ImageResize($fileName);

    if (is_array($sizes))
    {
        foreach ($sizes as $size)
        {
            grace_debug("Creating a new version of the image: " . $size);
            $newImage->resizeToMax($size);
            $newImage->save(str_replace($baseName, $baseName . "_" . $size, $fileName));
        }
    }
    else
    {
        grace_debug("Resizing image and keeping the same name");
        $newImage->resizeToMax($sizes);
        $newImage->save($fileName);
    }

    return true;
}

/**
 * @brief Gets the public path for a file
 *
 * @param idFile The id of the file
 * @param size The size of the image you are looking for, applies for images only
 *
 * @return The public path for said file
 *
 * */
function files_getPublicPath($idFile, $size = false)
{
    # Get the details about the file
    $file = files_load($idFile);

    if ($file != false) 
        return "w=files&r=files_view_file&code=" . $file->downloadCode . "&size=" . $size;
    else
        return ERROR_FILES_DOWNLOAD_ERROR;
}

/**
 * @brief View files, given a code I will present them to everyone 
 *
 * @return 'Presents' a file in the browser, an image will be displayed, a zip will be prompted for dowload probably. Or an eror not found.
 * */
function files_viewPublic()
{
    $file = files_loadByCode(params_get("code", ""), params_get("size", 0));
    grace_debug("found file in path: " . $file->path);
    if ($file != ERROR_DB_NO_RESULTS_FOUND)
        files_presentFile($file->path);

    return ERROR_FILES_NOT_FOUND;
}

/**
 * @brief Loads a file by its code and size
 *
 * @param code The unique download code of the file 
 * @param size The size of the file, only valid for images
 *
 * @return The information about the file including its path.
 * */
function files_loadByCode($code, $size = false)
{
    $q = sprintf("SELECT * FROM files WHERE downloadCode = '%s'", db_escape($code));
    $file = db_query($q, 1);
    if ($file != ERROR_DB_NO_RESULTS_FOUND)
    {
        $file->path = files_createPath($file->idUser, $file->type) . $file->name;
        if ($size)
        {
            $file->path = files_renameImgWithSize($file->path, $size);
            /*
              $fileParts  = pathinfo($file->path);
              $baseName   = $fileParts['filename'];
              $file->path = str_replace($baseName, $baseName . "_" . $size, $file->path);
             */
        }

        return $file;
    }

    return false;
}

/**
 * @brief Renames an image with its size included
 *
 * @param fullName The fullname of the image, may include the path
 * @param size The size like 50, 100, or 250, all in px
 *
 * @return 
 * */
function files_renameImgWithSize($fullName, $size)
{
    # Get the parts of this file
    $fileParts = pathinfo($fullName);
    $baseName = $fileParts['filename'];
    return str_replace($baseName, $baseName . "_" . $size, $fullName);
}

/**@}*/
/** @}*/
