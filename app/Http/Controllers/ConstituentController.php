<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Constituent as Constituent;
use App\BrgyCaptain as BrgyCaptain;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ConstituentController extends Controller
{
    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $user = Auth::user();
        // dd($user->name);
        $constituents = Constituent::all();
        return view('Constituents/index', ['constituents' => $constituents ]);
    }

    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $brgy_captains = BrgyCaptain::all();
        return view('Constituents/add_edit', [
            'method' => 'add', 
            'brgy_captains' => $brgy_captains
        ]);
    }

    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $constituent = new Constituent;
        $this->save_data($input, $constituent);
        return redirect()->action('ConstituentController@index')->with('status', 'Record Added');
    }

    /**
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $constituent = Constituent::find($id);
        return view('Constituents/show', [ 
            'constituent' => $constituent, 
        ]);
    }

    /**
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $constituent = Constituent::find($id);
        $brgy_captains = BrgyCaptain::all();

        return view('Constituents/add_edit', [ 
            'constituent' => $constituent, 
            'method' => 'edit', 
            'brgy_captains' => $brgy_captains 
        ]);
    }

    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $input = $request->all();
        $constituent = Constituent::find($id);
        $this->save_data($input, $constituent);
        return redirect()->action('ConstituentController@index')->with('status', 'Record Updated');

    }

    /**
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $constituent = Constituent::find($id);
        $constituent->delete();

        return redirect()->action('ConstituentController@index')->with('status', 'Record Deleted');
    }


    public function save_data($input, $constituent)
    {
        $constituent->first_name = $input['first_name'];
        $constituent->middle_name = $input['middle_name'];
        $constituent->last_name = $input['last_name'];
        $constituent->address = $input['address'];
        $constituent->brgy_captain_id = $input['brgy_captain'];
        $constituent->save();
    }
}
