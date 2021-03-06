<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CriminalRecord extends Model
{
	protected $table = 'criminal_record';

	public function constituent(){
		return $this->belongsTo('App\Constituent');
	}
}
?>