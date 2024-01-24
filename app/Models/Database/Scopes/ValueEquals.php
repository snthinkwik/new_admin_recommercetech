<?php
namespace App\Models\Database\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ValueEquals implements Scope{

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var mixed
     */
    protected $value;

    public function __construct($fieldName, $value)
    {
        $this->fieldName = $fieldName;
        $this->value = $value;
    }

    public function apply(Builder $builder, Model $model)
    {
        $builder->where($this->fieldName, $this->value);
    }

    public function remove(Builder $builder, Model $model)
    {
        $query = $builder->getQuery();
        $bindings = $builder->getBindings();

        foreach ((array) $query->wheres as $key => $where)
        {
            if ($where['column'] === $this->fieldName && $where['value'] === $this->value) {
                unset($query->wheres[$key]);
                unset($bindings[$key]);
            }
        }

        $query->wheres = array_values($query->wheres);
        $builder->setBindings($bindings);
    }

}


?>
