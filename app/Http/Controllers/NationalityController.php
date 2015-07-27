<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

use App\Nationality;

class NationalityController extends Controller {

    // list all the nationality
    public function getNationality()
    {
        $data = Nationality::whereNotNull('Adjective')->orderBy('Adjective','ASC')->get();
        $allNations = array();
        $allNations[0]['id'] = ' - Select Nationality - ';
        $allNations[0]['name'] = ' - Select Nationality - ';
        foreach ($data as $nations) {
            $nation['id'] = $nations->id;
            $nation['name'] = $nations->Adjective;
            $allNations[] = $nation;
        }
        return Response::json(array('data'=>$allNations));
    }

    // list all countries
    public function getCountry()
    {
        $data = Nationality::orderBy('Country','ASC')->get();
        $allNations = array();
        $allNations[0]['id'] = ' - Select Country - ';
        $allNations[0]['name'] = ' - Select Country - ';
        foreach ($data as $nations) {
            $nation['id'] = $nations->id;
            $nation['name'] = $nations->Country;
            $allNations[] = $nation;
        }
        return Response::json(array('data'=>$allNations));
    }
}
