<?php

class Sphinx_Driver_Orm extends Sphinx_Driver {

    public function in(array $docids)
    {
        return $this->model
            ->where($this->model->primary_key(), 'IN', $docids)
            ->order_by(new Database_Expression('FIELD(`'.$this->model->primary_key().'`, '.implode(',', $docids).')'))
            ->find_all();
    }

}
