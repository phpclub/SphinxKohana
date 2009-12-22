# SphinxKohana

A [SphinxSearch](http://www.sphinxsearch.com) interface for the [Kohana framework](http://kohanaphp.com/) (v3.0+).

## Quick Start

Currently Supports [ORM](http://github.com/kohana/orm) and [Sprig](http://github.com/shadowhand/sprig) models. With the ability to easily add a new Driver for other types.

### Setup

* Install/Already have SphinxSearch
* Make sure modules/sphinx/conf.php is executable (this is a bash/php script)
* Configure config/sphinx_core.conf && config/sphinx.php (copy from modules/sphinx/config to application/config)
* chown/chgrp directories application/sphinx_data to allow apache to write access. (or where you specify in config/sphinx.php)
* Start Sphinx daemon with Sphinx::start() or command line with /pathtobin/searchd --config /pathto/modules/sphinx/conf.php

### Each model must

* implement `Sphinx_Model`
* define a public `_sphinx_index()` method that must return a `Sphinx_Conf` object

Example of the method:

    class Model_Film extends Sprig implements Sphinx_Model
    {
        
        /* ...  */

        public function _sphinx_index()
        {
            /**
             * Uses Model Name as Index name
             * Can be anything as long as its unique
             */
            $config = new Sphinx_Conf(__CLASS__);

            $config->sql_query = "
                SELECT film_id, title, title as sort_title, description, release_year, language_id, rental_duration, rental_rate, length, replacement_cost, rating, special_features, UNIX_TIMESTAMP(last_update) as last_update
                FROM film";

            $config->sql_attr_uint = 'release_year';
            $config->sql_attr_uint = 'rental_duration';
            $config->sql_attr_uint = 'language_id';
            $config->sql_attr_uint = 'length';

            $config->sql_attr_str2ordinal = 'sort_title';

            $config->sql_attr_float = 'rental_rate';
            $config->sql_attr_float = 'replacement_cost';

            $config->sql_attr_timestamp = 'last_update';

            /**
             * Sets a default Sphinx_Index object.
             * With more advanced configurations you can pass in Sphinx_Index with other settings
             */
            $config->index();
            return $config;
        }

        /* ...  */

    }

##Using

Loading is done with `Sphinx::factory($obj_or_index)`:

    $search = Sphinx::factory(Sprig::factory('film'));

Index:

    $search->run_index(); // Pass TRUE to push/fork to /dev/null

###Searching:

Currently the following is possible:

    $search->limit($limit)
    //
    ->offset($search->limit * ($page-1))
    //
    ->order_by($fieldname, $order_or_func)
    //
    ->group_by($fieldname)
    //
    ->sort_relevance()
    //
    ->match_mode($mode)
    //
    ->ranking_mode($ranker)
    //
    ->filter($attribute, array(5, 1), $exclude = FALSE)
    //
    ->filter_range($attribute, $min, $max, $exclude = FALSE)
    //
    ->return_model(bool);

#More docs/examples soon!
