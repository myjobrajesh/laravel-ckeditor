<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use UniSharp\LaravelFilemanager\Events\ImageIsUploading;
use UniSharp\LaravelFilemanager\Events\ImageWasUploaded;


use UniSharp\LaravelFilemanager\Events\ImageIsRenaming;
use UniSharp\LaravelFilemanager\Events\ImageWasRenamed;
use UniSharp\LaravelFilemanager\Events\FolderIsRenaming;
use UniSharp\LaravelFilemanager\Events\FolderWasRenamed;

use Illuminate\Http\File as FileObj;

use Storage;

use App\Http\Traits\CkEditorHelpers;

/**
 * Class UploadController.
 */
class CkEditorUploadController extends \UniSharp\LaravelFilemanager\Controllers\LfmController
{
    use CkEditorHelpers;

    protected $errors;

    public function __construct()
    {
        parent::__construct();
        $this->errors = [];
    }

    /**
     * Upload files
     *
     * @param void
     * @return string
     */
    public function upload()
    {
        $files = request()->file('upload');

        // single file
        if (!is_array($files)) {
            $file = $files;

            if (!$this->fileIsValid($file)) {
                return $this->errors;
            }
            $filename = $this->proceedSingleUpload($file);

            if ($filename === false) {
                return $this->errors;
            }

            // upload via ckeditor 'Upload' tab
            return $this->useFile($filename);
        }


        // Multiple files
        foreach ($files as $file) {
            if (!$this->fileIsValid($file)) {
                continue;
            }

            $this->proceedSingleUpload($file);
        }

        return count($this->errors) > 0 ? $this->errors : parent::$success_response;
    }

    private function proceedSingleUpload($file)
    {
        $new_filename = $this->getNewName($file);
        $new_file_path = $this->getCurrentPath();

        event(new ImageIsUploading($new_file_path));
        try {
            $disk = $this->getDiskToStore();

            if ($this->fileIsImage($file) && !in_array($file->getMimeType(), ['image/gif', 'image/svg+xml'])) {

                $disk->putFileAs($new_file_path, $file, $new_filename, 'public');
                // Generate a thumbnail
                if (config('lfm.should_create_thumbnails', true)) {
                    $this->makeThumb($new_filename, $file->getRealPath());
                }
            } else {
                // Create (move) the file
                $disk->move($file->getRealPath(), $new_file_path);
            }
        } catch (\Exception $e) {
          //  echo $e->getMessage();die;
            array_push($this->errors, parent::error('invalid'));
            Log::error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }

        event(new ImageWasUploaded(realpath($new_file_path)));

        return $new_filename;
    }

    private function fileIsValid($file)
    {
        $disk = $this->getDiskToStore();
        if (empty($file)) {
            array_push($this->errors, parent::error('file-empty'));
            return false;
        }

        if (! $file instanceof UploadedFile) {
            array_push($this->errors, parent::error('instance'));
            return false;
        }

        if ($file->getError() == UPLOAD_ERR_INI_SIZE) {
            $max_size = ini_get('upload_max_filesize');
            array_push($this->errors, parent::error('file-size', ['max' => $max_size]));
            return false;
        }

        if ($file->getError() != UPLOAD_ERR_OK) {
            $msg = 'File failed to upload. Error code: ' . $file->getError();
            array_push($this->errors, $msg);
            return false;
        }

        $new_filename = $this->getNewName($file);

        if ($disk->exists($this->getCurrentPath($new_filename))) {
            array_push($this->errors, parent::error('file-exist'));
            return false;
        }
        $mimetype = $file->getMimeType();

        // Bytes to KB
        $file_size = $file->getSize() / 1024;
        $type_key = parent::currentLfmType();

        if (config('lfm.should_validate_mime', false)) {
            $mine_config = 'lfm.valid_' . $type_key . '_mimetypes';
            $valid_mimetypes = config($mine_config, []);
            if (false === in_array($mimetype, $valid_mimetypes)) {
                array_push($this->errors, parent::error('mime') . $mimetype);
                return false;
            }
        }

        if (config('lfm.should_validate_size', false)) {
            $max_size = config('lfm.max_' . $type_key . '_size', 0);
            if ($file_size > $max_size) {
                array_push($this->errors, parent::error('size'));
                return false;
            }
        }

        return true;
    }

    protected function replaceInsecureSuffix($name)
    {
        return preg_replace("/\.php$/i", '', $name);
    }

    private function getNewName($file)
    {
        $new_filename = parent::translateFromUtf8(trim($this->pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)));
        if (config('lfm.rename_file') === true) {
            $new_filename = uniqid();
        } elseif (config('lfm.alphanumeric_filename') === true) {
            $new_filename = preg_replace('/[^A-Za-z0-9\-\']/', '_', $new_filename);
        }

        return $new_filename . $this->replaceInsecureSuffix('.' . $file->getClientOriginalExtension());
    }


    private function makeThumb($new_filename, $file = null)
    {
        // create thumb folder
        $this->createFolderByPath($this->getThumbPath());

        //create thumb image in local and then move that file to file storage and then delete local file.
        $temp_path =  $this->translateToOsPath($this->getLocalUploadPath()."/".time().$new_filename);
        // create thumb image
         Image::make($file)
            ->fit(config('lfm.thumb_img_width', 200), config('lfm.thumb_img_height', 200))
            ->save($temp_path);//->save(parent::getThumbPath($new_filename));

        //move to storage
        $disk = $this->getDiskToStore();
        $disk->putFileAs($this->getThumbPath(), new FileObj($temp_path), $new_filename, 'public');
        //delete local temp path
        File::delete($temp_path);

    }

    private function useFile($new_filename)
    {
        $file = $this->getFileUrl($new_filename);

        $responseType = request()->input('responseType');
        if ($responseType && $responseType == 'json') {
            return [
                "uploaded" => 1,
                "fileName" => $new_filename,
                "url" => $file,
            ];
        }

        return "<script type='text/javascript'>

        function getUrlParam(paramName) {
            var reParam = new RegExp('(?:[\?&]|&)' + paramName + '=([^&]+)', 'i');
            var match = window.location.search.match(reParam);
            return ( match && match.length > 1 ) ? match[1] : null;
        }

        var funcNum = getUrlParam('CKEditorFuncNum');

        var par = window.parent,
            op = window.opener,
            o = (par && par.CKEDITOR) ? par : ((op && op.CKEDITOR) ? op : false);

        if (op) window.close();
        if (o !== false) o.CKEDITOR.tools.callFunction(funcNum, '$file');
        </script>";
    }

    private function pathinfo($path, $options = null)
    {
        $path = urlencode($path);
        $parts = is_null($options) ? pathinfo($path) : pathinfo($path, $options);
        if (is_array($parts)) {
            foreach ($parts as $field => $value) {
                $parts[$field] = urldecode($value);
            }
        } else {
            $parts = urldecode($parts);
        }

        return $parts;
    }

    /* upload end */


    /* renameing */
    /**
     * @return string
     */
    public function getRename()
    {

        $old_name = parent::translateFromUtf8(request('file'));
        $new_name = parent::translateFromUtf8(trim(request('new_name')));

        $extension = strtolower(File::extension($old_name));

        if (!$extension) {
            return $this->renameDirectory($old_name, $new_name);
        } else {
            return $this->renameFile($old_name, $new_name);
        }
    }

    protected function renameDirectory($old_name, $new_name)
    {
        if (empty($new_name)) {
            return parent::error('folder-name');
        }

        $old_file = $this->getCurrentPath($old_name);
        $new_file = $this->getCurrentPath($new_name);

        event(new FolderIsRenaming($old_file, $new_file));

        if (config('lfm.alphanumeric_directory') && preg_match('/[^\w-]/i', $new_name)) {
            return parent::error('folder-alnum');
        }
        $disk = $this->getDiskToStore();
        if ($disk->exists($new_file)) {
            return parent::error('rename');
        }

        $disk->move($old_file, $new_file);
        event(new FolderWasRenamed($old_file, $new_file));

        return parent::$success_response;
    }

    protected function renameFile($old_name, $new_name)
    {
        if (empty($new_name)) {
            return parent::error('file-name');
        }

        $old_file = $this->getCurrentPath($old_name);
        $extension = File::extension($old_file);
        $new_file = $this->getCurrentPath(basename($new_name, ".$extension") . ".$extension");

        if (config('lfm.alphanumeric_filename') && preg_match('/[^\w-.]/i', $new_name)) {
            return parent::error('file-alnum');
        }

        // TODO Should be "FileIsRenaming"
        event(new ImageIsRenaming($old_file, $new_file));
        $disk = $this->getDiskToStore();

        if ($disk->exists($new_file)) {
            return parent::error('rename');
        }

        if ($disk->exists($this->getThumbPath($old_name))) {
            $disk->move($this->getThumbPath($old_name), $this->getThumbPath($new_name));
        }

        $disk->move($old_file, $new_file);

        event(new ImageWasRenamed($old_file, $new_file));

        return parent::$success_response;
    }
    /* end renaming */
}
