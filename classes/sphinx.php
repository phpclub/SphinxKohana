#!/usr/bin/php
<?php

// Back up to Root
$dir = dirname(dirname(dirname(dirname(__FILE__))));

include($dir.'/index.php');

class Controller_1 {

    public function __construct() {
        $config = Kohana::config('sphinx.default');

        // Conf Files
        $files = glob(DOCROOT.'/'.$config['data_folder'].'/*.conf');

        foreach ($files as $file)
        {
            echo file_get_contents($file), PHP_EOL;
        }
        //echo file_get_contents(DOCROOT.'/'.$config['core_file']);
        $files = Kohana::find_file('config', str_replace('.conf', '', $config['core_file']), 'conf');
        echo file_get_contents(max($files));
        exit();
    }

}
