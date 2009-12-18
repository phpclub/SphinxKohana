<?php

class Sphinx_Search_Sphinx implements Iterator, Countable
{

    protected $model = NULL;
    public $query = NULL;

    protected $search = NULL;

    static public function factory($model)
    {
        return new self($model);
    }

    static protected function run($cmd, $push = FALSE)
    {
        return $push? `{$cmd} > /dev/null 2>&1` : `{$cmd}`;
    }

    static public function restart()
    {
        $cmd1 = Sphinx_Search::stop();
        $cmd2 = Sphinx_Search::start();
        return $cmd1.PHP_EOL.$cmd2;
    }

    static public function stop()
    {
        $bin = Kohana::config('sphinx.default.bin'); 
        $config = Kohana::config('sphinx.default.conf');
        return Sphinx_Search::run($bin.'/searchd --config '.$config.' --stop');
    }

    static public function start()
    {
        $bin = Kohana::config('sphinx.default.bin'); 
        $config = Kohana::config('sphinx.default.conf');
        return Sphinx_Search::run($bin.'/searchd --config '.$config);
    }

    static public function stats()
    {
        $bin = Kohana::config('sphinx.default.bin'); 
        $config = Kohana::config('sphinx.default.conf');
        return Sphinx_Search::run($bin.'/searchd --config '.$config.' --status');
    }

    static public function index($index = null, $push = FALSE)
    {
        $bin = Kohana::config('sphinx.default.bin'); 
        $config = Kohana::config('sphinx.default.conf');
        $index = is_null($index)? '--all' : $index;
        return Sphinx_Search::run($bin.'/indexer '.$index.' --config '.$config.' --rotate', $push);
    }

    public function __construct($model, $config = 'default')
    {
        $this->model = is_string($model)? Sprig::factory($model) : $model;

        // Currently only support for Sprig
        if (!($this->model instanceof Sphinx_Search_Model && $this->model instanceof Sprig))
        {
            throw new Kohana_Exception('Model must be interface of Sphinx_Model && instance of Sprig');
        }

        $this->config = Kohana::config('sphinx.'.$config);

        $this->load_model_config();

    }

    protected function load_model_config()
    {
        $this->model_config = $this->model->_sphinx_index();
    }

    public function count()
    {
        return count($this->search());
    }
    public function reset()
    {
        $this->search = NULL;
    }
    public function rewind()
    {
        return $this->search()? $this->search()->rewind() : FALSE;
    }
    public function current()
    {
        return $this->search()->current();
    }
    public function key()
    {
        return $this->search()->key();
    }
    public function next()
    {
        return $this->search()->next();
    }
    public function valid()
    {
        return $this->search()? $this->search()->valid() : FALSE;
    }

    public function search()
    {
        if (is_null($this->search))
        {
            $sl = new SphinxClient(); 
            $sl->SetMatchMode(SPH_MATCH_EXTENDED2);
            $sl->SetLimits(0, 100);
            $sl->SetSortMode(SPH_SORT_EXTENDED, '@relevance DESC');
            
            $result = $sl->Query($this->query, $this->model_config->index);
            
            if ($result && isset($result['matches']))
            {
                $docids = array_keys($result['matches']);

                $query = DB::select()
                    ->where($this->model->pk(), 'IN', $docids)
                    ->order_by(new Database_Expression('FIELD(`'.$this->model->pk().'`, '.implode(',', $docids).')'));

                $this->search = $this->model->load($query, null);
            }
            else
            {
                var_dump($result);
                $this->search = array();
            }
        }
        return $this->search;
    }

    public function run_index($push = FALSE)
    {
        $this->mk_index();

        return Sphinx_Search::index($this->model_config->index, $push);
    }

    public function mk_index()
    {
        $docroot = DOCROOT;

        $file = "
        source {$this->model_config->index}_src
        {
            type    = mysql
        ";


        foreach($this->config['database'] as $key => $variable)
        {
            $file.="    {$key} = ".$variable."\n";
        }

        $file.="    sql_query = ".str_replace("\n", " \\"."\n", $this->model_config->sql_query)."\n\n";
        
        foreach($this->model_config->attributes as $alias => $obj)
        {
            $file.="    {$obj->var} = ".$alias."\n";
        }

        $file.="
        }

        index {$this->model_config->index}
        {
            source = {$this->model_config->index}_src

            path = {$docroot}{$this->config['data_folder']}/data/{$this->model_config->index}

            docinfo = extern

            mlock = 0

            min_stemming_len = 4

            min_word_len = 1
        ";


        $file.="
        }
        ";

        $filename = DOCROOT.$this->config['data_folder'].'/sphinx_'.$this->model_config->index.'.conf';
        file_put_contents($filename, $file);
    }
}
