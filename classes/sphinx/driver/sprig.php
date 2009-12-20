<?php

class Sphinx_Driver_Sprig extends Sphinx_Driver {

    public function in(array $docids)
    {
        $query = DB::select()
            ->where($this->model->pk(), 'IN', $docids)
            ->order_by(new Database_Expression('FIELD(`'.$this->model->pk().'`, '.implode(',', $docids).')'));
        return $this->model->load($query, NULL); 
    } 

}
