# Arrangeable behaviour for Eloquent models

This package provides a trait that adds an arrangeable behaviour to an Eloquent model.  Instances of arrangeable models have an order column with values 0,1,2..., and can be optionally be grouped by a foreign key.

## Usage

To add arrangeable behavior to your model, use the trait ```MTSanford\LaravelArrangeable\ArrangeableTrait```

### Example

```php
use MTSanford\LaravelArrangeable\ArrangeableTrait;

class MyModel extends Model
{

    use ArrangeableTrait;

    public $arrangeableConfig = [
        'foreign_key' => 'parent_id',
        'order'
        'handle_delete' => false,
    ];
    
    ...
}
```

You can set a new order for all the records using the `arrangeableNewOrder`-method

```php
/**
 * model id 3 will have order_column value 1
 * model id 2 will have order_column value 2
 * model id 1 will have order_column value 3
 */
MyModel::arrangeableNewOrder([3,2,1]);
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

