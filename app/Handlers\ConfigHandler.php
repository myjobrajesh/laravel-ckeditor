<?php
/*This class is overwritten for Unisharp\Laravelfilemanager\Handlers\ConfigHandler
 */
namespace App\Handlers;

// we assume Customer Contorller exists
use App\User; 

class ConfigHandler
{
    public function userField()
    {
        $user_createdAt = auth()->user()->created_at;
        $user_id = auth()->user()->id;
        
        $folderName = $user_id.$user_createdAt;
        return $folderName;
    }
       
}
