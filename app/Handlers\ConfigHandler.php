<?php
/*This class is overwritten for Unisharp\Laravelfilemanager\Handlers\ConfigHandler
 */
namespace App\Handlers;

// we assume Customer Contorller exists
use App\Customer; 

class ConfigHandler
{
    public function userField()
    {
        $user_createdAt = auth()->user()->created_at;
        $folderName = $customer_id.$user_createdAt;
        return $folderName;
    }
       
}
