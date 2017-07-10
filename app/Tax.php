<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
	protected $table = 'tax';

	public function constituent(){
		return $this->belongsTo('App\Constituent');
	}
}
?>