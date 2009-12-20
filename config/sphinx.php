<?php

return array(
    'default' => array
    (
        'server'    =>  'localhost',
        'port'      =>  9312, 
        // Location of Sphinx install bin folder
        'bin'       =>  '/usr/lib/sphinx/bin',
        // Folder to save the index files
        'data_folder'   =>  'application/sphinx_data',
        'core_file'     =>  'core_sphinx.conf',
        'conf'        =>  DOCROOT.'modules/sphinx/conf.php',
        'debug'     =>  TRUE,
        'database'  =>  array(
            'sql_host'  =>  Kohana::config('database.default.connection.hostname'),
            'sql_user'  =>  Kohana::config('database.default.connection.username'),
            'sql_pass'  =>  Kohana::config('database.default.connection.password'),
            'sql_db'    =>  Kohana::config('database.default.connection.database'),
            'sql_port'  =>  '3306',
        ),
    ),
);
