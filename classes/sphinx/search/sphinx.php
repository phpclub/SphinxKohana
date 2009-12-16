<?php

class Sphinx_Search_Sphinx
{

    protected $model = NULL;

    public $query = NULL;

    static public function factory($model)
    {
        return new self($model);
    }

    public function __construct($model)
    {
        if (is_string($model))
        {
            $this->model = Sprig::factory($model); 
        }
        if (!$this->model instanceof Sphinx_Search_Model)
        {
            throw new Kohana_Exception('Model must be instanceof Sphinx_Model');
        }
        $this->load_config();
    }

    public function search()
    {
        
        $sl = new SphinxClient(); 
        $sl->SetMatchMode(SPH_MATCH_EXTENDED2);
        $sl->SetLimits(0, 100);
        $sl->SetSortMode(SPH_SORT_EXTENDED, '@relevance DESC');
        
        $result = $sl->Query($this->query, $this->config->index);
        
        if ($result && isset($result['matches']))
        {
            $docids = array_keys($result['matches']);
        } else {
            var_dump($sl->GetLastError());
            var_dump($sl->GetLastWarning());
            return array();
        }

        $query = DB::select()
            ->where($this->model->pk(), 'IN', $docids)
            ->order_by(new Database_Expression('FIELD('.$this->model->pk().', '.implode(',', $docids).')'));

        return $this->model->load($query, null);
    }

    protected function load_config()
    {
        $this->config = $this->model->_sphinx_index();

        $path = '/usr/lib/sphinx/';

        $this->confg_file = $filename = $path.'auto/'.$this->config->index.'.conf';

        //file_put_contents($filename, $file);
    }

    public function index()
    {
        $config = $this->config;
        $file = "
source {$config->index}_src
{
    type    = mysql

    sql_host    = localhost
    sql_user    = root
    sql_pass    = klownz
    sql_db      = sakila
    sql_port    = 3306

";

        $file.="    sql_query = ".str_replace("\n", " \\"."\n", $config->sql_query)."\n\n";
        
        foreach($config->attributes as $alias => $obj)
        {
            $file.="    ".$obj->var." = ".$alias."\n";
        }

        $file.="
}

index {$config->index}
{
    source = {$config->index}_src

    path = /usr/lib/sphinx/var/data/{$config->index}

    docinfo = extern

    mlock = 0

    min_stemming_len = 4

    min_word_len = 1
";


        $file.="
}

indexer
{
    mem_limit   = 32M
}

searchd
{
	# hostname, port, or hostname:port, or /unix/socket/path to listen on
	# multi-value, multiple listen points are allowed
	# optional, default is 0.0.0.0:9312 (listen on all interfaces, port 9312)
	#
	# listen				= 127.0.0.1
	# listen				= 192.168.0.1:9312
	# listen				= 9312
	# listen				= /var/run/searchd.sock


	# log file, searchd run info is logged here
	# optional, default is 'searchd.log'
	log					= /usr/lib/sphinx/var/log/searchd.log

	# query log file, all search queries are logged here
	# optional, default is empty (do not log queries)
	query_log			= /usr/lib/sphinx/var/log/query.log

	# client read timeout, seconds
	# optional, default is 5
	read_timeout		= 5

	# request timeout, seconds
	# optional, default is 5 minutes
	client_timeout		= 300

	# maximum amount of children to fork (concurrent searches to run)
	# optional, default is 0 (unlimited)
	max_children		= 30

	# PID file, searchd process ID file name
	# mandatory
	pid_file			= /usr/lib/sphinx/var/log/searchd.pid

	# max amount of matches the daemon ever keeps in RAM, per-index
	# WARNING, THERE'S ALSO PER-QUERY LIMIT, SEE SetLimits() API CALL
	# default is 1000 (just like Google)
	max_matches			= 1000

	# seamless rotate, prevents rotate stalls if precaching huge datasets
	# optional, default is 1
	seamless_rotate		= 1

	# whether to forcibly preopen all indexes on startup
	# optional, default is 0 (do not preopen)
	preopen_indexes		= 0

	# whether to unlink .old index copies on succesful rotation.
	# optional, default is 1 (do unlink)
	unlink_old			= 1

	# attribute updates periodic flush timeout, seconds
	# updates will be automatically dumped to disk this frequently
	# optional, default is 0 (disable periodic flush)
	#
	# attr_flush_period	= 900


	# instance-wide ondisk_dict defaults (per-index value take precedence)
	# optional, default is 0 (precache all dictionaries in RAM)
	#
	# ondisk_dict_default	= 1


	# MVA updates pool size
	# shared between all instances of searchd, disables attr flushes!
	# optional, default size is 1M
	mva_updates_pool	= 1M

	# max allowed network packet size
	# limits both query packets from clients, and responses from agents
	# optional, default size is 8M
	max_packet_size		= 8M

	# crash log path
	# searchd will (try to) log crashed query to 'crash_log_path.PID' file
	# optional, default is empty (do not create crash logs)
	#
	# crash_log_path		= /usr/lib/sphinx/var/log/crash


	# max allowed per-query filter count
	# optional, default is 256
	max_filters			= 256

	# max allowed per-filter values count
	# optional, default is 4096
	max_filter_values	= 4096


	# socket listen queue length
	# optional, default is 5
	#
	# listen_backlog		= 5


	# per-keyword read buffer size
	# optional, default is 256K
	#
	# read_buffer			= 256K


	# unhinted read size (currently used when reading hits)
	# optional, default is 32K
	#
	# read_unhinted		= 32K
}
";
        $path = '/usr/lib/sphinx/';

        $filename = $path.'auto/'.$config->index.'.conf';

        file_put_contents($filename, $file);

        `{$path}bin/indexer {$config->index} --config {$filename} --rotate`;

    }
}
