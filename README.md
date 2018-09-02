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

#### ArrangeableTrait::arrangeableNewOrder(array, int = null)

Set a completely new order for a group of models.

```php
// ( array of primary keys, foreign key if used)
MyModel::arrangeableNewOrder([3,2,1],1);

// | id | foreign_id | order |                | id | foreign_id | order |
// | -- | ---------- | ----- |                | -- | ---------- | ----- |
// | 1  | 1          | 0     |    BECOMES     | 1  | 1          | 2     |
// | 2  | 1          | 1     |   =========>   | 2  | 1          | 1     |
// | 3  | 1          | 2     |                | 3  | 1          | 0     |
// | 4  | 2          | 0     |                | 4  | 2          | 0     |
// | 5  | 2          | 1     |                | 5  | 2          | 1     |
// | 6  | 2          | 2     |                | 6  | 2          | 2     |
```

#### ArrangeableTrait::arrangeableMoveGroup(array, int = null)

This will move models with an list of ids to a different group defined by a foreign key, appending to the end of the list.  Groups with removed items will have their orders adjusted appropriately.

```php
// ( array of primary keys, foreign key if used)
MyModel::arrangeableMoveGroup([4,5,2],1);

// | id | foreign_id | order |                | id | foreign_id | order |
// | -- | ---------- | ----- |                | -- | ---------- | ----- |
// | 1  | 1          | 0     |    BECOMES     | 1  | 1          | 0     |
// | 2  | 1          | 1     |   =========>   | 2  | 1          | 4     |
// | 3  | 1          | 2     |                | 3  | 1          | 1     |
// | 4  | 2          | 0     |                | 4  | 1          | 2     |
// | 5  | 2          | 1     |                | 5  | 1          | 3     |
// | 6  | 2          | 2     |                | 6  | 2          | 0     |
```
#### ArrangeableTrait::arrangeableFixOrder(int = null)

If through other operations the ordering is out of whack, fix it.

```php
// (foreign key if used)
MyModel::arrangeableFixOrder(2);

// | id | foreign_id | order |                | id | foreign_id | order |
// | -- | ---------- | ----- |                | -- | ---------- | ----- |
// | 1  | 1          | 0     |    BECOMES     | 1  | 1          | 0     |
// | 2  | 1          | 1     |   =========>   | 2  | 1          | 1     |
// | 3  | 1          | 2     |                | 3  | 1          | 2     |
// | 4  | 2          | 5     |                | 4  | 2          | 0     |
// | 5  | 2          | 6     |                | 5  | 2          | 1     |
// | 6  | 2          | 10    |                | 6  | 2          | 2     |
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

