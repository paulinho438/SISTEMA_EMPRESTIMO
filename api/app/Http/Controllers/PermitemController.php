<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Permitem;

class PermitemController extends Controller
{
    public function index(Request $r){
        return Permitem::all()->groupBy('group')->toArray();
    }

    public function id(Request $r, $id){
        $res = Permitem::find($id);
        $res->groups;
        return $res;
    }
}
