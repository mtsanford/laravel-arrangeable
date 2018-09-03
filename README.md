# Arrangeable behaviour for Eloquent models

This package provides a trait that adds an arrangeable behaviour to an Eloquent model.  Instances of arrangeable models have an order column with values 0,1,2...  Arrangeable models by default do not use a foreign key for grouping, but a foreign key can be specified in configuration.

## Usage

To add arrangeable behavior to your model, use the trait ```MTSanford\LaravelArrangeable\ArrangeableTrait```

### Example

```php
use MTSanford\LaravelArrangeable\ArrangeableTrait;

class MyModel extends Model
{

    use ArrangeableTrait;

    public $arrangeableConfig = [
        'foreign_key' => 'foreign_id',
    ];
    
}
```
### Methods

#### ArrangeableTrait::arrangeableMove(array, int = null)

This will move a list of models specified by id to a target group defined by a foreign key, appending them to the end of the target group in the order specified.  Models from the target group can be in the source list.  Groups with removed items will have their orders adjusted appropriately.

```php
MyModel::arrangeableMove([4,5,2],1);

// | id | foreign_id | order |                | id | foreign_id | order |
// | -- | ---------- | ----- |                | -- | ---------- | ----- |
// | 1  | 1          | 0     |    BECOMES     | 1  | 1          | 0     |
// | 2  | 1          | 1     |   =========>   | 2  | 1          | 4     |
// | 3  | 1          | 2     |                | 3  | 1          | 1     |
// | 4  | 2          | 0     |                | 4  | 1          | 2     |
// | 5  | 2          | 1     |                | 5  | 1          | 3     |
// | 6  | 2          | 2     |                | 6  | 2          | 0     |
```

If all the models are in the same foreign key group and will remain there, there is no need to specify it.  Reordering an entire group is just a special case of this.

```php
// reverse the order of the models with foreign key = 2
MyModel::arrangeableMove([6,5,4]);

// | id | foreign_id | order |                | id | foreign_id | order |
// | -- | ---------- | ----- |                | -- | ---------- | ----- |
// | 1  | 1          | 0     |    BECOMES     | 1  | 1          | 0     |
// | 2  | 1          | 1     |   =========>   | 2  | 1          | 1     |
// | 3  | 1          | 2     |                | 3  | 1          | 2     |
// | 4  | 2          | 0     |                | 4  | 1          | 2     |
// | 5  | 2          | 1     |                | 5  | 1          | 1     |
// | 6  | 2          | 2     |                | 6  | 2          | 0     |
```

Also if the model has no foreign key, there is no need to specify it.

```php
// ( array of primary keys, foreign key if used)
MyModel::arrangeableMove([2,1]);

// | id | order |                | id | order |
// | -- | ----- |                | -- | ----- |
// | 1  | 0     |    BECOMES     | 1  | 3     |
// | 2  | 1     |   =========>   | 2  | 2     |
// | 3  | 2     |                | 3  | 0     |
// | 4  | 3     |                | 4  | 1     |
```

#### ArrangeableTrait::arrangeableFixOrder(int = null)

A convenient utility should your operations cause the ordering to become irregular.  Again, the foreign key parameter is only needed if there is a foreign key specified in $arrangeableCongig.

```php
MyModel::arrangeableFixOrder(1);

// | id | foreign_id | order |                | id | foreign_id | order |
// | -- | ---------- | ----- |                | -- | ---------- | ----- |
// | 1  | 1          | 0     |    BECOMES     | 1  | 1          | 0     |
// | 2  | 1          | 5     |   =========>   | 2  | 1          | 1     |
// | 3  | 1          | 8     |                | 3  | 1          | 2     |
// | 4  | 2          | 0     |                | 4  | 2          | 0     |
```
### Create and Delete

By default, creating and deleted model events are listened to, and the orders within the group are kept up to date.

```php
new MyModel(['foreign_id' => 1]);

// | id | foreign_id | order |                | id | foreign_id | order |
// | -- | ---------- | ----- |                | -- | ---------- | ----- |
// | 1  | 1          | 0     |    BECOMES     | 1  | 1          | 0     |
// | 2  | 1          | 1     |   =========>   | 2  | 1          | 1     |
// | 3  | 1          | 2     |                | 3  | 1          | 2     |
//                                            | 4  | 1          | 3     |

MyModel::find(2)->delete();

// | id | foreign_id | order |                | id | foreign_id | order |
// | -- | ---------- | ----- |                | -- | ---------- | ----- |
// | 1  | 1          | 0     |    BECOMES     | 1  | 1          | 0     |
// | 2  | 1          | 1     |   =========>   | 3  | 1          | 1     |
// | 3  | 1          | 2     |
```

### Configuration

These are the default configuration settings:

```php
    protected static $arrangeableConfigDefaults = [
        'primary_key'    => 'id',
        'order_key'      => 'order',
        'foreign_key'    => NULL,
        'start_order'    => 0,
        'handle_create'  => true,
        'handle_delete'  => true,
    ];

//        primary_key:     primary key of the model
//        order_key:       the column in the model that holds the order
//        foreign_key:     order will be maintained with models that have same foreign key
//                         or in the entire table if NULL.
//        start_order      value of order_key for the start of the list
//        handle_create    automatically set order_key on new models to end of list?
//        handle_delete    automatically maintain order when a model is removed?
```

To override any of them, define a static property $arrangeableConfig in your model.

```php
class MyModel extends Model
{
    use ArrangeableTrait;

    public static $arrangeableConfig = [
        'foreign_key'  => 'parent_id',
        'start_order'  => 1,
    ];
}
```

## Tests

The package contains some phpunit tests, using Orchestra.

```
$ vendor/bin/phpunit
```

## About

Mark Sanford is a developer in San Francisco.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

