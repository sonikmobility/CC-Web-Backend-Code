<?php

namespace App\Http\Services;
use App\Http\Models\Version;

class VersionService
{
    public function getVersion($where)
    {
        return Version::where($where)->get();
    }

    public function storeVersion($data){
        return Version::create($data);
    }
    
    public function updateVersion($id,$data){
        Version::where('id',$id)->update($data);
        return Version::where('id',$id)->first();
    }

    public function deleteVersion($id){
        return Version::where('id',$id)->delete();
    }

    public function versionList($search,$sortby,$orderby = "desc", $total_record)
    { 
        $version = Version::where(function($q) use($search){
            if($search != ''){
                $q->where('ios_version','LIKE',"%{$search}%");
                $q->Orwhere('android_version','LIKE',"%{$search}%");   
            }
        })
        ->orderBy($sortby, $orderby)
        ->paginate($total_record);
        return $version;
    }
}