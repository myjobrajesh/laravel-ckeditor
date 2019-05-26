<?php
/**
 * Created by PhpStorm.
 * User: Arbox
 * Date: 08-04-2019
 * Time: 10:58 PM
 */

namespace App\Http\Controllers;

use Storage;
use File;
use App\Http\Traits\CkEditorHelpers;


use UniSharp\LaravelFilemanager\Events\ImageIsDeleting;
use UniSharp\LaravelFilemanager\Events\ImageWasDeleted;


class CkEditorController extends \UniSharp\LaravelFilemanager\Controllers\FolderController
{

    use CkEditorHelpers;
    /**
     * Get real path of root working directory on the operating system.
     * override
     * @param  string|null  $type  User or share
     * @return string|null
     */
    public function getRootFolderPath($type)
    {
        return $this->base_path($this->getPathPrefix('dir') . $this->rootFolder($type));
    }

    /**
     * Override fun.
     * Get list of folders as json to populate treeview.
     *
     * @return mixed
     */
    public function getFolders()
    {
        $folder_types = [];
        $root_folders = [];

        if (parent::allowMultiUser()) {
            $folder_types['user'] = 'root';
        }

        if (parent::allowShareFolder()) {
            $folder_types['share'] = 'shares';
        }

        foreach ($folder_types as $folder_type => $lang_key) {
            $root_folder_path = $this->getRootFolderPath($folder_type);
            $children = $this->getDirectories($root_folder_path);
            usort($children, function ($a, $b) {
                return strcmp($a->name, $b->name);
            });

            array_push($root_folders, (object) [
                'name' => trans('laravel-filemanager::lfm.title-' . $lang_key),
                'path' => $this->getInternalPath($root_folder_path),
                'children' => $children,
                'has_next' => ! ($lang_key == end($folder_types)),
            ]);
        }

        return view('laravel-filemanager::tree')
            ->with(compact('root_folders'));
    }


    /* end overrider folderController
    */

    /* override code from ItemsController
    */
    /**
     * Get the images to load for a selected folder.
     *
     * @return mixed
     */
    public function getItems()
    {

        $path = $this->getCurrentPath();
        $sort_type = request('sort_type');

        $files = $this->sortFilesAndDirectories($this->getFilesWithInfo($path), $sort_type);

        $directories = $this->sortFilesAndDirectories($this->getDirectories($path), $sort_type);

        return [
            'html' => (string) view($this->getView())->with([
                'files'       => $files,
                'directories' => $directories,
                'items'       => array_merge($directories, $files),
            ]),
            'working_dir' => $this->getInternalPath($path),
        ];
    }

    private function getView()
    {
        $view_type = request('show_list');

        if (null === $view_type) {
            return $this->composeViewName($this->getStartupViewFromConfig());
        }

        $view_mapping = [
            '0' => 'grid',
            '1' => 'list'
        ];

        return $this->composeViewName($view_mapping[$view_type]);
    }

    private function composeViewName($view_type = 'grid')
    {
        return "laravel-filemanager::$view_type-view";
    }

    private function getStartupViewFromConfig($default = 'grid')
    {
        $type_key = parent::currentLfmType();
        $startup_view = config('lfm.' . $type_key . 's_startup_view', $default);
        return $startup_view;
    }
    /* end overriden code from ItemsController */

    /* delete controller code overridden */
    /**
     * Delete image and associated thumbnail.
     *
     * @return mixed
     */
    public function getDelete()
    {
        $name_to_delete = request('items');

        $file_to_delete = $this->getCurrentPath($name_to_delete);
        $thumb_to_delete = $this->getThumbPath($name_to_delete);

        event(new ImageIsDeleting($file_to_delete));

        $disk = $this->getDiskToStore();

        if (is_null($name_to_delete)) {
            return parent::error('folder-name');
        }

        if (! $disk->exists($file_to_delete)) {
            return parent::error('folder-not-found', ['folder' => $file_to_delete]);
        }

       $extension = strtolower(File::extension($file_to_delete));

        //if no extension then assume dir
        if (!$extension) {
            if (! $this->directoryIsEmpty($file_to_delete)) {
                return parent::error('delete-folder');
            }
            $disk->deleteDirectory($file_to_delete);
            return parent::$success_response;
        } else {
            //if fle then delete
            echo $thumb_to_delete;
            $disk->delete($thumb_to_delete);
            $disk->delete($file_to_delete);
        }

        event(new ImageWasDeleted($file_to_delete));

        return parent::$success_response;
    }

    /* end delete overriden */

    /* download file */
    /**
     * Download a file.
     *
     * @return mixed
     */
    public function getDownload()
    {
        $disk = $this->getDiskToStore();
        $filename = request('file');
        $file = $this->getCurrentPath($filename);

        try {
            $file = $disk->get($file);
        } catch (\Exception $e) {
            return trans('general.file_not_found : '.$filename);
        }

        ob_end_clean();
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=".$filename);
        return $file;
    }
    /* end dowload file */

    //add new folder
    /**
     * Add a new folder.
     *
     * @return mixed
     */
    public function getAddfolder()
    {
        $folder_name = parent::translateFromUtf8(trim(request('name')));
        $path = $this->getCurrentPath($folder_name);
        $disk = $this->getDiskToStore();

        if (empty($folder_name)) {
            return parent::error('folder-name');
        }

        if ($disk->exists($path)) {
            return parent::error('folder-exist');
        }

        if (config('lfm.alphanumeric_directory') && preg_match('/[^\w-]/i', $folder_name)) {
            return parent::error('folder-alnum');
        }

        $this->createFolderByPath($path);
        return parent::$success_response;
    }

}
