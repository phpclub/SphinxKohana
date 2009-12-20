<?php

class Model_Film extends ORM implements Sphinx_Model
{

    protected $_table_name = 'film';
    protected $_primary_key = 'film_id';
    protected $_primary_val = 'title';

    public function _sphinx_index()
    {
        $config = new Sphinx_Conf(__CLASS__);

        $config->sql_query = "
            SELECT film_id, title, description, release_year, language_id, rental_duration, rental_rate, length, replacement_cost, rating, special_features, UNIX_TIMESTAMP(last_update) as last_update
            FROM film";

        $config->sql_attr_uint = 'release_year';
        $config->sql_attr_uint = 'rental_duration';
        $config->sql_attr_uint = 'language_id';
        $config->sql_attr_uint = 'length';

        $config->sql_attr_float = 'rental_rate';
        $config->sql_attr_float = 'replacement_cost';

        $config->sql_attr_timestamp = 'last_update';

        $config->index();
        return $config;
    }
}
