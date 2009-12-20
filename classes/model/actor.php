<?php

/**
 * Using: 
 * http://svn.mysql.com/svnpublic/mysqldoc/sample-data/sakila/sakila-schema.sql
 * http://svn.mysql.com/svnpublic/mysqldoc/sample-data/sakila/sakila-data.sql
 */

class Model_Actor extends Sprig implements Sphinx_Model
{

    protected $_table = 'actor';

    protected function _init()
    {
        $this->_fields += array(
            'actor_id'        =>  new Sprig_Field_Auto,
            'first_name'  =>  new Sprig_Field_Char(array(
                'empty'         =>  FALSE,
                'max_length'    => 45,
            )),
            'last_name'  =>  new Sprig_Field_Char(array(
                'empty'         =>  FALSE,
                'mas_length'    =>  45,
            )),
        );
    }

    public function _sphinx_index()
    {
        // Create Source
        $config = new Sphinx_Source(__CLASS__);

        /*
        $config->sql_query = "
            SELECT actor_id, CONCAT(first_name, ' ', last_name) as sort_name, first_name, last_name, UNIX_TIMESTAMP(last_update) as last_update
            FROM `actor`";
        */
        $config->sql_query = "
            SELECT `actor`.`actor_id`, first_name as sort_fname, last_name as sort_lname, first_name, last_name, UNIX_TIMESTAMP(actor.last_update) as last_update, count(film_actor.film_id) as films
            FROM `actor`
            INNER JOIN `film_actor` ON `film_actor`.`actor_id` = `actor`.`actor_id`
            GROUP BY `actor`.`actor_id`";

        // Attributes
        $config->sql_attr_str2ordinal   = 'sort_fname';
        $config->sql_attr_str2ordinal   = 'sort_lname';

        $config->sql_attr_timestamp     = 'last_update';
        $config->sql_attr_uint          = 'films';

        // Create Source
        $index = new Sphinx_Index();
        $config->index($index);

        return $config; 
    }
}
