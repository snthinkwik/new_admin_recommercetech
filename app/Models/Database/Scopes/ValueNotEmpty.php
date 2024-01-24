<?php
namespace App\Models\Database\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ScopeInterface;

class ValueNotEmpty implements ScopeInterface{

    protected $fieldName;

    public function __construct($fieldName)
    {
        $this->fieldName = $fieldName;
    }

    public function apply(Builder $builder, Model $model)
    {
        $builder->where($this->fieldName, '<>', '');
    }

    public function remove(Builder $builder, Model $model)
    {
        throw new Exception('Remove not implemented for this scope yet, sorry.');
    }


}
