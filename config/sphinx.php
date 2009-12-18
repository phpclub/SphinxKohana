<?php

return array(
    'default' => array
    (
        'bin'       =>  '/usr/lib/sphinx/bin',
        'data_folder'   =>  'application/cache/sphinx',
        'core_file'     =>  'application/core_sphinx.conf',
        'conf'        =>  DOCROOT.'modules/sphinx/classes/sphinx.php',
        'database'  =>  array(
            'sql_host'  =>  Kohana::config('database.default.connection.hostname'),
            'sql_user'  =>  Kohana::config('database.default.connection.username'),
            'sql_pass'  =>  Kohana::config('database.default.connection.password'),
            'sql_db'    =>  Kohana::config('database.default.connection.database'),
            'sql_port'  =>  '3306',
        ),
    ),
);
