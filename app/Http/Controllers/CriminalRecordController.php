<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CriminalRecord as CriminalRecord;
use App\Constituent as Constituent;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use DateTime;

class CriminalRecordController extends Controller
{

    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function create($cons_id)
    {
        return view('CriminalRecord/add_edit',[ 
            'cons_id' => $cons_id,  
            'method' => 'add'   
        ]);

    }

    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $criminal_record = new CriminalRecord;
        $input = $request->all();
        $cons_id = $input["cons_id"];
        $this->save_data($request, $criminal_record, $cons_id);

        $constituent = Constituent::find($cons_id);
        $constituent->has_record = true;
        $constituent->save();

        return redirect()->action('ConstituentController@show', [$cons_id])->with('status', 'Criminal Record Added')
            ->with('active_tab', 'criminal_record');
    }


    /**
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $criminal_record = CriminalRecord::find($id);
        $cons_id =  $criminal_record->constituent->id;
        $criminal_record->delete();

        // Checking if constituent has record remaining. If none, set the record to false.
        $count = CriminalRecord::where('constituent_id', '=', $cons_id)->count();
        if(!$count){
            $constituent = Constituent::find($cons_id);
            $constituent->has_record = false;
            $constituent->save();
        }

        return redirect()->action('ConstituentController@show', [$cons_id])->with('status', 'Criminal Record Deleted')
            ->with('active_tab', 'criminal_record');
    }


    /**
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($cons_id, $id)
    {
        $criminal_record = CriminalRecord::find($id);
        return view('CriminalRecord/add_edit',[ 
            'criminal_record' => $criminal_record, 
            'cons_id' => $cons_id, 
            'method' => 'edit'
        ]);
    }


    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $cons_id, $id)
    {
        $criminal_record = CriminalRecord::find($id);
        $input = $request->all();
        $cons_id = $input["cons_id"];
        $this->save_data($request, $criminal_record, $cons_id);
        return redirect()->action('ConstituentController@show', [$cons_id])->with('status', 'Criminal Record Updated')
            ->with('active_tab', 'criminal_record');
    }

    public function save_data($input, $criminal_record, $cons_id)
    {
        $date_time = $input["date_execution"]." ".$input["time_execution"];
        $criminal_record->constituent_id = $cons_id;
        $criminal_record->case_name = $input["case"];
        $criminal_record->details = $input["details"];
        $criminal_record->execution_date = new DateTime($date_time);
        $criminal_record->save();
    }

}
