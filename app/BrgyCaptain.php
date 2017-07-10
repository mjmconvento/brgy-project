<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BrgyCaptain extends Model
{
	protected $table = 'brgy_captain_candidate';

	public function constituents(){
		return $this->hasMany('App\Constituent');
	}
}
?>