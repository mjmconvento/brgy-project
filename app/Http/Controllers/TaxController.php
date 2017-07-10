<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tax as Tax;
use App\Constituent as Constituent;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use DateTime;

class TaxController extends Controller
{

    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function create($cons_id)
    {
        $months = $this->get_months();
        return view('Tax/add_edit',[ 
            'cons_id' => $cons_id, 
            'method' => 'add',
            'months' => $months
        ]);
    }

    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $cons_id)
    {
        $input = $request->all();
        $tax = new Tax;
        $cons_id = $input["cons_id"];
        $this->save_data($input, $tax, $cons_id);
        $this->check_has_unpaid_tax($cons_id);

        return redirect()->action('ConstituentController@show', [$cons_id])->with('status', 'Tax Added')
            ->with('active_tab', 'tax');
    }


    /**
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $tax = Tax::find($id);
        $cons_id =  $tax->constituent->id;
        $tax->delete();
        $this->check_has_unpaid_tax($cons_id);

        return redirect()->action('ConstituentController@show', [$cons_id])->with('status', 'Tax Record Deleted')
            ->with('active_tab', 'tax');
    }


    /**
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($cons_id, $id)
    {
        $tax = Tax::find($id);
        $months = $this->get_months();
        return view('Tax/add_edit',[ 
            'tax' => $tax, 
            'cons_id' => $cons_id, 
            'months' => $months ,
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
        $input = $request->all();
        $tax = Tax::find($id);
        $this->save_data($input, $tax, $cons_id);
        $this->check_has_unpaid_tax($cons_id);
        return redirect()->action('ConstituentController@show', [$cons_id])->with('status', 'Tax Updated')
            ->with('active_tab', 'tax');
    }

    public function check_has_unpaid_tax($cons_id)
    {
        $constituent = Constituent::find($cons_id);
        $count = 0;

        foreach ($constituent->tax as $c)
            if($c->status == "Unpaid")
                $count++;

        if(!$count)
            $constituent->has_unpaid_tax = false;
        else
            $constituent->has_unpaid_tax = true;

        $constituent->save();
    }

    public function save_data($input, $tax, $cons_id)
    {
        $tax->constituent_id = $cons_id;
        $tax->amount = $input["amount"];
        $tax->payment_month = $input["month"];
        $tax->payment_year = $input["year"];
        $tax->status = $input["status"];
        $tax->save();
    }

    public function get_months()
    {
        return array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    }

}
