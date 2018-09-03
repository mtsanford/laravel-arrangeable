<?php

namespace MTSanford\LaravelArrangeable;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;

trait ArrangeableTrait
{

    /****
        These are the default configuration settings.
        They can be overidden by declaring a static property $arrangeableConfig in
        the model with settings to be overridden.  E.g.:
        
        public static $arrangeableConfig = [
            'foreign_key'  => 'parent_id',
        ];
        
        primary_key:     primary key of the model
        order_key:       the column in the model that holds the order
        foreign_key:     order will be maintained with models that have same foreign key
                         or in the entire table if NULL.
        start_order      value of order_key for the start of the list
        handle_create    automatically set order_key on new models to end of list?
        handle_delete    automatically maintain order when a model is removed?
    ****/

    protected static $arrangeableConfigDefaults = [
        'primary_key'    => 'id',
        'order_key'      => 'order',
        'foreign_key'    => NULL,
        'start_order'    => 0,
        'handle_create'  => true,
        'handle_delete'  => true,
    ];


    public static function arrangeableGetConfig($key) {
        return (isset(self::$arrangeableConfig) && array_key_exists($key, self::$arrangeableConfig))
                  ? self::$arrangeableConfig[$key]
                  : static::$arrangeableConfigDefaults[$key];
    }


    public static function bootArrangeableTrait()
    {
        static::creating(function ($model) {
            if (static::arrangeableGetConfig('handle_create')) {
                $model->arrangeableSetHighestOrderNumber();
            }
        });

        static::deleted(function ($model) {
            if (static::arrangeableGetConfig('handle_delete')) {
                static::arrangeableFixOrder($model->arrangeableGetForeignKeyValue());
            }
        });
    }

    /**
     * Move a list of models to then end of another grouping (foreign key)
     * Foreign key not required if the model does not have a foreign key,
     * if the models to be moved are all in the same group.
     *
     * If there are models already in the group, the ones being moved
     * will be appended.
     *
     * @param array|\ArrayAccess  $ids
     * @param int                 $foreignKeyValue
     */
    public static function arrangeableMove($ids, $foreignKeyValue = NULL)
    {

        $order = static::arrangeableGetConfig('start_order');
        $primaryKeyColumn = static::arrangeableGetConfig('primary_key');
        $foreignKeyColumn = static::arrangeableGetConfig('foreign_key');
        $orderColumnName = static::arrangeableGetConfig('order_key');

        if (empty($ids)) return;

        // This is a special, easier case
        if ($foreignKeyColumn === NULL) {
            $newOrder = static::orderBy($orderColumnName)
                          ->select($primaryKeyColumn)
                          ->get()
                          ->pluck($primaryKeyColumn)
                          ->diff($ids)
                          ->concat($ids);

            foreach ($newOrder as $id) {
                static::where($primaryKeyColumn, $id)->update([$orderColumnName => $order++]);
            }

            return;
        }

        // figure out which groups will need to have their order fixed.  That's
        // all the groups the models are currently in, except the target group.
        $allForeignKeys = static::whereIn($primaryKeyColumn, $ids)
                           ->select($foreignKeyColumn)
                           ->get()
                           ->pluck($foreignKeyColumn)
                           ->unique();

        // Allow no foreign key to be provided, and infer the foreign key from
        // the models to be moved, so long as they all have the same foreign key
        if ($foreignKeyValue === NULL) {
            if ($allForeignKeys->count() != 1) {
                throw new InvalidArgumentException("No foreign key provided"); 
            }
            $foreignKeyValue = $allForeignKeys->first();
        }

        // Any group that is having a model removed will need to have it's order fixed
        // The group we're moving to is already being completely reordered.
        $needsFixOrder = $allForeignKeys->diff([$foreignKeyValue]);

        $newOrder = static::where($foreignKeyColumn, $foreignKeyValue)
                      ->orderBy($orderColumnName)
                      ->select($primaryKeyColumn)
                      ->get()
                      ->pluck($primaryKeyColumn)
                      ->diff($ids)
                      ->concat($ids);

        foreach ($newOrder as $id) {
            static::where($primaryKeyColumn, $id)->update([
                $orderColumnName  => $order++,
                $foreignKeyColumn => $foreignKeyValue,
            ]);
        }

        $needsFixOrder->each(function($id) {
            static::arrangeableFixOrder($id);
        });
    }


    /**
     * Ensure that the models are in 1,2,3 order, but keeping current order
     * Useful when one or more models has been removed from the list
     *
     * @param array|\ArrayAccess $ids
     * @param int $startOrder
     */
    public static function arrangeableFixOrder($foreignKeyValue = NULL)
    {
        $order = static::arrangeableGetConfig('start_order');
        $orderColumnName = static::arrangeableGetConfig('order_key');
        $primaryKeyColumn = static::arrangeableGetConfig('primary_key');
        $foreignKeyColumn = static::arrangeableGetConfig('foreign_key');

        if ($foreignKeyColumn !== NULL && $foreignKeyValue === NULL) {
            throw new InvalidArgumentException("Foreign key value required");
        }

        $models = static::arrangeableModelQuery($foreignKeyValue)
            ->orderBy($orderColumnName)->select($primaryKeyColumn, $orderColumnName)->get();

        foreach ($models as $model) {
            $model->update([$orderColumnName => $order++]);
        }
    }


    /**
     * Modify the order column value.
     */
    public function arrangeableSetHighestOrderNumber()
    {
        $startOrder = static::arrangeableGetConfig('start_order');
        $orderColumnName = static::arrangeableGetConfig('order_key');

        $max = $this->arrangeableGetHighestOrderNumber();

        $this->$orderColumnName = ($max === null) ? $startOrder : ($max + 1);
    }
    
    /**
     * Determine the higest order value for a new record.
     *
     * CAUTION:  Will return NULL if there are no existing records
     */
    public function arrangeableGetHighestOrderNumber()
    {
        $orderColumnName = static::arrangeableGetConfig('order_key');

        return static::arrangeableModelQuery($this->arrangeableGetForeignKeyValue())
                           ->max($orderColumnName);

    }

    public function arrangeableGetForeignKeyValue() {
        $foreignKeyColumn = static::arrangeableGetConfig('foreign_key');
        return $foreignKeyColumn
                   ? $this->$foreignKeyColumn
                   : null;
    }


    /**
     * Get query builder for models with the foreign key
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function arrangeableModelQuery($foreignKeyValue = null)
    {
        $foreignKeyColumn = static::arrangeableGetConfig('foreign_key');

        return $foreignKeyColumn
                     ? static::query()->where($foreignKeyColumn, $foreignKeyValue)
                     : static::query();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeArrange(Builder $query, string $direction = 'asc')
    {
        $orderColumnName = static::arrangeableGetConfig('order_key');        
        return $query->orderBy($orderColumnName, $direction);
    }
}
