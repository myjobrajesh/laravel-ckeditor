# laravel-ckeditor
By default ckeditor uses local file system to store images , but what happen if we want to store images on cloud server or other server?. this is the code that unables to store images on aws or other system using FileStorage. 

Steps
- Install Filemanager : https://github.com/UniSharp/laravel-filemanager
- Do below changes :
- .env : add below lines

FILESYSTEM=ftp

CKEDITOR_S3_STORAGE=false

AWS_CKEDITOR_BUCKET=aws-ckeditor (for aws we need to create s3 bucket named aws-ckeditor, or you can name whatever)


- Don't override any files. just get changes and add to original files

- config/lfm.php : update file.

- app/Handlers\ConfigHandler.php : create new file

- routes/lfmroutes.php : create new file

- config/filesystems.php : update file

- app/Http/Controllers/CkEditorController.php : create new file

- app/Http/Controllers/CkeditorUploadController.php : create new file

- app/Http/Traits/CkEditorHelpers.php : create new file

- clear cache and route.

