<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Constituent extends Model
{
	protected $table = 'constituent';

	public function criminalRecord(){
		return $this->hasMany('App\CriminalRecord');
	}

	public function tax(){
		return $this->hasMany('App\Tax');
	}

	public function brgyCaptain(){
		return $this->belongsTo('App\BrgyCaptain');
	}

}
?>