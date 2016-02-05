<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Constituents as Constituents;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class ConstituentController extends Controller
{
    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $constituents = Constituents::all();
        return view('Constituents/index', ['constituents' => $constituents ]);
    }

    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('Constituents/add_edit', ['method' => 'add']);
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

    /**
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $constituent = Constituents::find($id);
        return view('Constituents/show', [ 'constituent' => $constituent ]);
    }

    /**
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $constituent = Constituents::find($id);
        return view('Constituents/add_edit', [ 'constituent' => $constituent, 'method' => 'edit' ]);
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
        $constituent = Constituents::find($id);
        $this->save_data($input, $constituent);
        return redirect()->action('ConstituentController@index');

    }

    /**
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $constituent = Constituents::find($id);
        $constituent->delete();

        return redirect()->action('ConstituentController@index');
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
