<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BrgyCaptain as BrgyCaptain;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class BrgyCaptainController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $brgy_captains = BrgyCaptain::all();
        return view('BrgyCaptain/index', ['brgy_captains' => $brgy_captains ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('BrgyCaptain/add_edit', ['method' => 'add']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $constituent = new BrgyCaptain;
        $this->save_data($input, $constituent);
        return redirect()->action('BrgyCaptainController@index')->with('status', 'Record Added');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $brgy_captain = BrgyCaptain::find($id);
        return view('BrgyCaptain/show', [ 'brgy_captain' => $brgy_captain ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $brgy_captain = BrgyCaptain::find($id);
        return view('BrgyCaptain/add_edit', [ 'brgy_captain' => $brgy_captain, 'method' => 'edit' ]);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $input = $request->all();
        $brgy_captain = BrgyCaptain::find($id);
        $this->save_data($input, $brgy_captain);
        return redirect()->action('BrgyCaptainController@index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $brgy_captain = BrgyCaptain::find($id);
        $brgy_captain->delete();

        return redirect()->action('BrgyCaptainController@index')->with('status', 'Record Deleted');
    }

    public function save_data($input, $constituent)
    {
        $constituent->first_name = $input['first_name'];
        $constituent->middle_name = $input['middle_name'];
        $constituent->last_name = $input['last_name'];
        $constituent->address = $input['address'];
        $constituent->save();
    }

}
