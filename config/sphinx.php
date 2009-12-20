<?php

$config = array(
    'default' => array
    (
        /**
         * Name of the driver to use, usually refers the type of model
         */
        'driver'    =>  'sprig',
        /**
         * Location of Sphinx Search daemon
         */
        'server'    =>  'localhost',
        /**
         * Port of Sphinx Search daemon
         * Default: 9312
        */
        'port'      =>  9312, 
        /**
         *  Location of Sphinx bin folder
         */
        'bin'       =>  '/usr/lib/sphinx/bin',
        /**
         * Folder to save the index files
         * Must be writable by apache
         */
        'data_folder'   =>  'application/sphinx_data',
        /**
         * Core .conf file to include (located in config folder)
         * Default: core_sphinx.conf
        */
        'core_file'     =>  'core_sphinx.conf',
        /**
         * Location of the Conf generator script.
         * Default: DOCROOT.modules/sphinx/conf.php
         * Needs to be executable, this is a bash script executing php
         */
        'conf'        =>  DOCROOT.'modules/sphinx/conf.php',
        /**
         * When set to TRUE throws exceptions on Errors and Warnings
         */
        'debug'     =>  TRUE,
        /**
         * MySQL Database Information, Used for Indexing By Sphinx.
        */
        'database'  =>  array(
            'sql_host'  =>  Kohana::config('database.default.connection.hostname'),
            'sql_user'  =>  Kohana::config('database.default.connection.username'),
            'sql_pass'  =>  Kohana::config('database.default.connection.password'),
            'sql_db'    =>  Kohana::config('database.default.connection.database'),
            'sql_port'  =>  '3306',
        ),
    ),
);

// Copy defaults values
$config['orm'] = $config['default'];
$config['orm']['driver'] = 'orm';

return $config;
