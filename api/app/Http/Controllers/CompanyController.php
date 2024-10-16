<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;

use App\Models\Company;
use App\Http\Resources\EmpresaResource;

class CompanyController extends Controller
{
    public function index(Request $r){
        $companies = Company::all();
        return $companies;
    }
    public function get(Request $request) {
        $companies = Company::find($request->header('company-id'));
        return $companies;
    }

    public function getAll(Request $request) {
        $companies = EmpresaResource::collection(Company::all());
        return $companies;
    }
}
