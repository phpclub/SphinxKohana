<?php

class Sphinx_Search_Source
{
    public $index = NULL;
    public $sql_query = NULL;
    public $attributes = array();
    public $index_conf = NULL;

    public function  __construct($index)
    {
        $this->index = $index;
    }

    public function __set($type, $variable)
    {
        $this->attributes[] = array($type, $variable);
    }

    public function index(Sphinx_Search_Index $index)
    {
        $this->index_conf = $index;
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
