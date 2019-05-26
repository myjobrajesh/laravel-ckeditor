<?php

return [

    'default' => env('FILESYSTEM', 'ftp'), //for AWS we use s3

    'ckeditor_s3_storage' =>  env('CKEDITOR_S3_STORAGE', false), //for s3 true else false.

    'cloud' => 's3',


    'disks' => [

        'ckeditor_s3' => [
            'driver' => 's3',
            'key' => env('AWS_KEY', ''),
            'secret' => env('AWS_SECRET', ''),
            'region' => env('AWS_REGION', ''),
            'bucket' => env('AWS_CKEDITOR_BUCKET', ''),
        ],
        'ckeditor_public' => [
            'driver' => 'local',
            'root' => public_path('ckeditor'),
            'visibility' => 'public',
        ],
    ],

];
