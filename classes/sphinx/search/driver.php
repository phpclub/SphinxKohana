<?php

abstract Class Sphinx_Search_Driver {
   
    protected $model = NULL;

    public function __construct($model)
    {
        $this->model = $model;
    }

    abstract public function in(array $docids);
}
