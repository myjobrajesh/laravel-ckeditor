# laravel-ckeditor
By default ckeditor uses local file system to store images , but what happen if we want to store images on cloud server or other server?. this is the code that unables to store images on aws or other system using FileStorage. 

Steps
- Install Filemanager : https://github.com/UniSharp/laravel-filemanager
- Do below changes :
- .env file

FILESYSTEM=ftp
#for ckeditor file storage
CKEDITOR_S3_STORAGE=false
AWS_CKEDITOR_BUCKET=aws-ckeditor (for aws we need to create s3 bucket named aws-ckeditor, or you can name whatever)
#end ckeditor filestorage

- config/lfm.php

return [
    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    */

    // Include to pre-defined routes from package or not. Middlewares
    //'use_package_routes' => true,
    'use_package_routes' => false,

    // Middlewares which should be applied to all package routes.
    // For laravel 5.1 and before, remove 'web' from the array.
    'middlewares' => ['web','auth','CkeditorPermission'],

    // The url to this package. Change it if necessary.
    'prefix' => 'laravel-filemanager',

    // The prefix of urls to non-public files, for exmaple if: base_directory !== 'public'
    // Without slashes
    'urls_prefix' => '',

    /*
    |--------------------------------------------------------------------------
    | Multi-User Mode
    |--------------------------------------------------------------------------
    */

    // If true, private folders will be created for each signed-in user.
    'allow_multi_user' => true,
    // If true, share folder will be created when allow_multi_user is true.
    'allow_share_folder' => false,

    // Flexible way to customize client folders accessibility
    // Ex: The private folder of user will be named as the user id.
    // You cant use a closure when using the optimized config file (in Laravel 5.2 anyway)
    /*'user_field' => function() {
        $user_id = auth()->user()->id;
        $customer_id = auth()->user()->user_customer_id;
        $company_createdAt = strtotime(App\Customer::where('customer_id','=',$customer_id)->value('created_at'));
        $folderName = $customer_id.$company_createdAt;
        return $folderName;
    },*/
   // 'user_field' => Unisharp\Laravelfilemanager\Handlers\ConfigHandler::class,
    'user_field'   =>   App\Handlers\ConfigHandler::class,
    /*
    |--------------------------------------------------------------------------
    | Working Directory
    |--------------------------------------------------------------------------
    */

    // Which folder to store files in project, fill in 'public', 'resources', 'storage' and so on.
    // You should create routes to serve images if it is not set to public.
   // 'base_directory' => 'public/ckeditor',
   // 'base_directory' => env('CKEDITOR_S3_STORAGE', false) ? 'https://s3.amazonaws.com/'.env('AWS_CKEDITOR_BUCKET', '') : 'public/ckeditor',//s3
    'base_directory' => '',//env('CKEDITOR_S3_STORAGE', false) ? '' : 'public/ckeditor',//s3


    'images_folder_name' => 'photos',
    'files_folder_name'  => 'files',

    'shared_folder_name' => 'shares',
    'thumb_folder_name'  => 'thumbs',

 
];
