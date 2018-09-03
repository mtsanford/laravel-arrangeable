<?php

namespace MTSanford\LaravelArrangeable\Test;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

use MTSanford\LaravelArrangeable\Test\Car;
use MTSanford\LaravelArrangeable\Test\Passenger;

class ArrangeableTest extends TestCase
{
    use DatabaseMigrations;
    
    /** @test */
    public function testCreateNoForeignKey()
    {
        $car1 = factory(Car::class)->create();
        $car2 = factory(Car::class)->create();

        $this->assertEquals($car1->order, 0);
        $this->assertEquals($car2->order, 1);
    }


    /** @test */
    public function testCreateWithForeignKey()
    {
        $car1 = factory(Car::class)->create();
        $car2 = factory(Car::class)->create();
        $passengers1 = factory(Passenger::class, 3)->create(['car_id' => $car1->id]);
        $passengers2 = factory(Passenger::class, 3)->create(['car_id' => $car2->id]);

        $o = Passenger::where('car_id', $car1->id)->arrange()->get()->pluck('order')->all();
        $this->assertEquals($o, [0,1,2]);

        $o = Passenger::where('car_id', $car2->id)->arrange()->get()->pluck('order')->all();
        $this->assertEquals($o, [0,1,2]);
    }

    /** @test */
    public function testCustomStartOrder()
    {

        Car::$arrangeableConfig['start_order'] = 1;
        Passenger::$arrangeableConfig['start_order'] = 2;

        $cars = factory(Car::class, 3)->create();
        $passengers1 = factory(Passenger::class, 3)->create(['car_id' => $cars[1]->id]);

        $o = Car::arrange()->get()->pluck('order')->all();
        $this->assertEquals($o, [1,2,3]);

        $o = Passenger::where('car_id', $cars[1]->id)->arrange()->get()->pluck('order')->all();
        $this->assertEquals($o, [2,3,4]);

        unset(Car::$arrangeableConfig['start_order']);
        unset(Passenger::$arrangeableConfig['start_order']);
    }

    /** @test */
    public function testDeleteNoForeignKeys()
    {
    	$cars = factory(Car::class, 5)->create();

    	$last = $cars->last()->fresh();
   	   	$this->assertEquals($last->order, 4);

    	Car::where('order', 2)->first()->delete();

    	// deleted one, should now be at 3
    	$last = $cars->last()->fresh();
   	   	$this->assertEquals($last->order, 3);

        // and they should be in 0,1,2.. order
        $o = Car::query()->arrange()->get()->pluck('order')->all();
        $this->assertEquals($o, [0,1,2,3]);
    }

    /** @test */
    public function testDeleteWithDifferentForeignKeys()
    {
    	$car1 = factory(Car::class)->create();
    	$car2 = factory(Car::class)->create();
    	$passengers1 = factory(Passenger::class, 5)->create(['car_id' => $car1->id]);
    	$passengers2 = factory(Passenger::class, 5)->create(['car_id' => $car2->id]);

    	$passengers1->where('order', 0)->first()->delete();
    	$passengers1->where('order', 2)->first()->delete();
    	$passengers2->where('order', 4)->first()->delete();

        $o = Passenger::where('car_id', $car1->id)->arrange()->get()->pluck('order')->all();
        $this->assertEquals($o, [0,1,2]);

        $o = Passenger::where('car_id', $car2->id)->arrange()->get()->pluck('order')->all();
        $this->assertEquals($o, [0,1,2,3]);
    }

    /** @test */
    public function testMoveNoForeignKeyProvided()
    {
        $car = factory(Car::class)->create();
        $lessons = factory(Passenger::class, 5)->create(['car_id' => $car->id]);

        $reverse_ids = array_reverse(Passenger::query()->arrange()->get()->pluck('id')->toArray());

        Passenger::arrangeableMove($reverse_ids);

        $o = Passenger::query()->arrange()->get()->pluck('id')->toArray();
        $this->assertEquals($o, $reverse_ids);
    }

    /** @test */
    /* Move from two different groups, including the target group */
    public function testMove()
    {
        $car1 = factory(Car::class)->create();
        $passenger1 = factory(Passenger::class, 5)->create(['car_id' => $car1->id]);

        $car2 = factory(Car::class)->create();
        $passenger2 = factory(Passenger::class, 5)->create(['car_id' => $car2->id]);

        $passenger1_ids = $passenger1->pluck('id');
        $passenger2_ids = $passenger2->pluck('id');

        // move the first from $car1 and the second from $car2 to $car2
        $pulled = [$passenger1_ids->pull(0), $passenger2_ids->pull(1)];
        $passenger2_ids = $passenger2_ids->concat($pulled);  // <-- we expect this

        Passenger::arrangeableMove($pulled, $car2->id);

        $o = Passenger::where('car_id', $car1->id)->arrange()->get()->pluck('id')->toArray();
        $this->assertEquals($o, array_values($passenger1_ids->all()));

        $o = Passenger::where('car_id', $car2->id)->arrange()->get()->pluck('id')->toArray();
        $this->assertEquals($o, array_values($passenger2_ids->all()));
    }

    /** @test */
    public function testMoveNoForeignKey()
    {
        $cars = factory(Car::class, 5)->create();

        $ids = $cars->pluck('id');

        Car::arrangeableMove([2,1]);

        $o = Car::arrange()->get()->pluck('id')->toArray();
        $this->assertEquals($o, [3,4,5,2,1]);
    }

    /** @test */
    public function testFixOrderNoForeignKey()
    {
        $cars = factory(Car::class, 5)->create();

        $cars[3]->update(['order' => 10]);
        $cars[4]->update(['order' => 20]);

        Car::arrangeableFixOrder();

        $o = Car::arrange()->get()->pluck('order')->toArray();
        $this->assertEquals($o, [0,1,2,3,4]);
    }

    /** @test */
    public function testFixOrderWithForeignKey()
    {
        $car = factory(Car::class)->create();
        $passengers = factory(Passenger::class, 5)->create(['car_id' => $car->id]);

        $passengers[3]->update(['order' => 10]);
        $passengers[4]->update(['order' => 20]);

        Passenger::arrangeableFixOrder($car->id);

        $o = Passenger::where('car_id', $car->id)->arrange()->get()->pluck('order')->toArray();
        $this->assertEquals($o, [0,1,2,3,4]);
    }

    /** @test
     *  @expectedException InvalidArgumentException
     */
    public function testBadMove() {
        // requires second parameter foreign key
        Passenger::arrangeableMove([1,2]);        
    }

}
