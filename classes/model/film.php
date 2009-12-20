<?php

class Model_Film extends Sprig
{
    protected $_table = 'film';

    protected function _init()
    {
        $this->_fields += array(
            'film_id'       =>  new Sprig_Field_Auto,
            'title'         =>  new Sprig_Field_Char(array(
                'empty'         =>  FALSE,
                'max_length'    =>  255,
            )),
            'description'   =>  new Sprig_Field_Text(array(
            )),
            'release_year'  =>  new Sprig_Field_Integer(array(
            )),
            'language_id'   =>  new Sprig_Field_HasOne(array(
                'model'     =>  'language'
            )),
        ); 
    }
}
