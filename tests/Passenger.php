<?php

namespace MTSanford\LaravelArrangeable\Test;

use Illuminate\Database\Eloquent\Model;
use MTSanford\LaravelArrangeable\ArrangeableTrait;

class Passenger extends Model
{
    use ArrangeableTrait;

    protected static $arrangeableConfig = [
        'foreign_key'    => 'car_id',
    ];

    protected $table = 'passengers';
    protected $guarded = [];
    public $timestamps = false;

    public function car() {
    	return $this->belongsTo('MTSanford\LaravelArrangeable\Test\Car');
    }
}
