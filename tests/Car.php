<?php

namespace MTSanford\LaravelArrangeable\Test;

use Illuminate\Database\Eloquent\Model;
use MTSanford\LaravelArrangeable\ArrangeableTrait;

class Car extends Model
{
    use ArrangeableTrait;

    public static $arrangeableConfig = [];

    protected $table = 'cars';
    protected $guarded = [];
    public $timestamps = false;

    public function passengers() {
    	return $this->hasMany('MTSanford\LaravelArrangeable\Test\Passenger');
    }
}
