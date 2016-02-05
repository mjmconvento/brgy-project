<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CriminalRecord as CriminalRecord;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class CriminalRecordController extends Controller
{

    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('CriminalRecord/add');
    }

    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $constituent = new Constituents;
        $this->save_data($input, $constituent);
        return redirect()->action('ConstituentController@index');
    }

}
