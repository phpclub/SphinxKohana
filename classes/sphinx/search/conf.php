<?php

class Sphinx_Search_Conf
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

    public function index(Sphinx_Index $index = NULL)
    {
        if ($index === NULL)
        {
            $this->index_conf = new Sphinx_Index;
        }
        else
        {
            $this->index_conf = $index;
        }
    }
}
