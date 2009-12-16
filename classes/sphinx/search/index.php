<?php

class Sphinx_Search_Index
{
    public $doc_id = NULL;
    public $index = NULL;
    public $sql_query = NULL;
    public $attributes = array();
    public $indexes = array();

    public function  __construct($index)
    {
        $this->index = $index;
    }

    public function doc_id($doc_id)
    {
        $this->doc_id = $doc_id;
    }

    public function index($field)
    {
        $name = (string)$field;
        if (is_array($field)) 
        {
            $name = $field[0];
            $field = $field[1];
        }
        $this->indexes[$name] = $field;
    }

    public function has($attribute)
    {
        $name = (string)$attribute;
        if (is_array($attribute))
        {
            $name = $attribute[0];
            $attribute = $attribute[1];
        }
        $this->attributes[$name] = $attribute;
    }
}

class Attr_Sortable extends Attr
{
    public $var = 'sql_attr_str2ordinal';
}
class Attr_Timestamp extends Attr
{
    public $var = 'sql_attr_timestamp';
}
class Attr_Multi extends Attr
{
    public $var = 'sql_attr_multi';
}

class Attr_Uint extends Attr
{
    public $var = 'sql_attr_uint';
}

class Attr
{
    public $var = NULL;
    public $field = NULL;
    public $alias = NULL;

    public function __construct($field, $alias = NULL)
    {
        $this->field = $field;
        $this->alias = $alias;
    }

    public function __toString()
    {
        return $this->alias? $this->alias : $this->field;
    }
}
