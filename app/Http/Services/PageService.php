<?php

namespace App\Http\Services;

use App\Http\Models\Page;

class PageService
{
    public function pageList($search,$sortby,$orderby = "desc", $total_record)
    { 
        $page = Page::where(function($q) use($search){
            if($search != ''){
                $q->where('page_name','LIKE',"%{$search}%");
                $q->Orwhere('page_title','LIKE',"%{$search}%");   
            }
        })
        ->orderBy($sortby, $orderby)
        ->paginate($total_record);
        return $page;
    }

    public function storeContentPage($data){
        return Page::create($data);
    }

    public function getContentPage($where){
        return Page::where($where)->first();
    }
    
    public function updateContentPage($id,$data){
        Page::where('id',$id)->update($data);
        return Page::where('id',$id)->first();
    }

    public function deleteContentPage($id){
        return Page::where('id',$id)->delete();
    }
}