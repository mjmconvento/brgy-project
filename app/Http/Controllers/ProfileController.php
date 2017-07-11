<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\User;
use DateTime;

class ProfileController extends Controller
{


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

        $user = User::find($id);
        return view('Profile/edit', [ 
            'user' => $user
        ]);
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


        $user = User::find($id);
        $input = $request->all();
        $user->first_name = $input["first_name"];
        $user->middle_name = $input["middle_name"];
        $user->last_name = $input["last_name"];
        $user->updated_at = new DateTime();
        $user->save();

        return redirect()->action('ConstituentController@index')->with('status', 'Profile Record Updated');
    }

}
