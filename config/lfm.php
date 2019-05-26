return [
    // Include to pre-defined routes from package or not. Middlewares
    //'use_package_routes' => true,
    'use_package_routes' => false,

    // The url to this package. Change it if necessary.
    'prefix' => 'laravel-filemanager',

    // The prefix of urls to non-public files, for exmaple if: base_directory !== 'public'
    // Without slashes
    'urls_prefix' => '',
    
   // 'user_field' => Unisharp\Laravelfilemanager\Handlers\ConfigHandler::class,
    'user_field'   =>   App\Handlers\ConfigHandler::class,
    
    // Which folder to store files in project, fill in 'public', 'resources', 'storage' and so on.
    // You should create routes to serve images if it is not set to public.
   // 'base_directory' => 'public/ckeditor',
    'base_directory' => '',

    'images_folder_name' => 'photos',
    'files_folder_name'  => 'files',

];
