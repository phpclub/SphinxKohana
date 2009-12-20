<?php

class Sphinx_Search implements Iterator, Countable
{

    protected $model = NULL;
    protected $result = NULL;
    protected $return_model = TRUE;
    public $sc = NULL;

    public $query = NULL;
    public $limit = 100;
    public $offset = 0;
    public $last_error = NULL;
    public $last_warning = NULL;

    protected $search = NULL;

    public function __construct($model, $config = 'default')
    {
        $this->model = is_string($model)? Sprig::factory($model) : $model;

        // Currently only support for Sprig
        if (!($this->model instanceof Sphinx_Model && $this->model instanceof Sprig))
        {
            throw new Kohana_Exception('Model must be interface of Sphinx_Model && instance of Sprig');
        }

        $this->config = Kohana::config('sphinx.'.$config);
        $this->sc = new SphinxClient();
        // Default Sorting Relevance
        $this->sc->SetSortMode(SPH_SORT_RELEVANCE);

        $this->model_config = $this->model->_sphinx_index();

        $this->sc->SetServer($this->config['server'], $this->config['port']);
        if (isset($this->config['timeout']))
        {
            $this->sc->SetConnectTimeout($this->config['timeout']);
        }
    }

    public function __get($var)
    {
        if ($var == 'get_results')
        {
            return $this->result();
        }
        elseif ($var == 'search')
        {
            return $this->search();
        }
    }

    /**
     * Forward SphinxClient methods
     */
    public function order_by($field, $order = 'asc')
    {
        $order = strtolower($order)=='asc'? SPH_SORT_ATTR_ASC : SPH_SORT_ATTR_DESC;
        $this->sc->SetSortMode($order, $field);
    }

    public function filter($attribute, array $values, $exclude = FALSE)
    {
        return $this->sc->SetFilter($attribute, $values, $exclude);
    }

    public function filter_range($attribute, $min, $max, $exclude = FALSE)
    {
        if (is_float($min) || is_float($max))
        {
            return $this->sc->SetFilterFloatRange($attribute, $min, $max, $exclude);
        }
        return $this->sc->SetFilterRange($attribute, $min, $max, $exclude);
    }
    public function match_mode($mode)
    {
        return $this->sc->SetMatchMode($mode);
    }

    public function group_by($attribute, $func = NULL, $dir = 'desc')
    {
        if ($func == 'desc' || $func == 'asc')
        {
            $dir = $func;
            $func = NULL;
        }
        if (is_null($func))
        {
            $func = SPH_GROUPBY_ATTR;
        }
        return $this->sc->SetGroupBy($attribute, $func, "@group {$dir}");
    }

    public function ranking_mode($ranker)
    {
        return $this->sc->SetRankingMode($ranker);
    }

    /**
     * Calling this function requires search to be pushed to sphinx
    */
    public function result()
    {
        if (!$this->search)
        {
            $this->search();
        }
        return $this->result;
    }

    public function return_model($value = TRUE)
    {
        $this->return_model = (bool)$value;
    }

    public function search()
    {
        if (is_null($this->search))
        {
            $this->do_search();

            if ($this->result && isset($this->result['matches']))
            {
                if (!$this->return_model)
                {
                    $this->search = (array)$this->result['matches'];
                }
                else
                {
                    if ($this->sc->_arrayresult)
                    {
                        $docids = array();
                        foreach($this->result['matches'] as $match)
                        {
                            $docids[] = $match['id'];
                        }
                    }
                    else
                    {
                        $docids = array_keys($this->result['matches']);
                    }

                    $query = DB::select()
                        ->where($this->model->pk(), 'IN', $docids)
                        ->order_by(new Database_Expression('FIELD(`'.$this->model->pk().'`, '.implode(',', $docids).')'));

                    $this->search = $this->model->load($query, null);
                }
            }
            else
            {
                $this->search = array();
            }
        }
        return $this->search;
    }

    public function run_index($push = FALSE)
    {
        $this->mk_index();

        return Sphinx::index($this->model_config->index, $push);
    }

    protected function do_search()
    {
        $this->sc->SetLimits($this->offset, $this->limit);

        $this->result = $result = $this->sc->Query($this->query, $this->model_config->index);
        $this->last_error = $this->sc->GetLastError();
        $this->last_warning = $this->sc->GetLastWarning();
    }

    protected function mk_index()
    {
        $file = "source {$this->model_config->index}_src".PHP_EOL."{".PHP_EOL."\t"."type = mysql".PHP_EOL;
        foreach($this->config['database'] as $key => $variable)
        {
            $file.="\t"."{$key} = ".$variable.PHP_EOL;
        }
        $file.="\t"."sql_query = ".str_replace(PHP_EOL, " \\".PHP_EOL, $this->model_config->sql_query).PHP_EOL.PHP_EOL;
        foreach($this->model_config->attributes as $attribute)
        {
            $file.="\t"."{$attribute[0]} = ".$attribute[1].PHP_EOL;
        }
        $file.="}".PHP_EOL.PHP_EOL."index {$this->model_config->index}".PHP_EOL."{"
            .PHP_EOL."\t"."source = {$this->model_config->index}_src"
            .PHP_EOL."\t"."path = ".DOCROOT."{$this->config['data_folder']}/{$this->model_config->index}".PHP_EOL;
        foreach ($this->model_config->index_conf->values as $var => $value)
        {
            $file.="\t"."{$var} = ".$value.PHP_EOL;
        }
        $file.="}".PHP_EOL;
        $filename = DOCROOT.$this->config['data_folder'].'/sphinx_'.$this->model_config->index.'.conf';
        if (!file_exists(dirname($filename)))
        {
            mkdir(dirname($filename), 0755, true);
        }
        file_put_contents($filename, $file);
    }

    /**
     * Iterator and Countable implementation
     */
    public function count()
    {
        return count($this->search());
    }

    public function rewind()
    {
        return is_object($this->search)? $this->search->rewind() : reset($this->search);
    }

    public function current()
    {
        return is_object($this->search)? $this->search->current() : current($this->search);
    }

    public function key()
    {
        return is_object($this->search)? $this->search->key() : key($this->search);
    }

    public function next()
    {
        return is_object($this->search)? $this->search->next() : next($this->search);
    }

    public function valid()
    {
        return is_object($this->search)? $this->search->valid() : is_array($this->search)? current($this->search)!==FALSE : FALSE;
    }

    /**
     * Static Methods 
     */
    static protected function run($cmd, $push = FALSE)
    {
        return $push? `{$cmd} > /dev/null 2>&1` : `{$cmd}`;
    }

    static public function restart()
    {
        $cmd1 = Sphinx::stop();
        $cmd2 = Sphinx::start();
        return $cmd1.PHP_EOL.$cmd2;
    }

    static public function stop()
    {
        $bin = Kohana::config('sphinx.default.bin'); 
        $config = Kohana::config('sphinx.default.conf');
        return Sphinx::run($bin.'/searchd --config '.$config.' --stop');
    }

    static public function start()
    {
        $bin = Kohana::config('sphinx.default.bin'); 
        $config = Kohana::config('sphinx.default.conf');
        return Sphinx::run($bin.'/searchd --config '.$config);
    }

    static public function index($index = null, $push = FALSE)
    {
        $bin = Kohana::config('sphinx.default.bin'); 
        $config = Kohana::config('sphinx.default.conf');
        $index = is_null($index)? '--all' : $index;
        return Sphinx::run($bin.'/indexer '.$index.' --config '.$config.' --rotate', $push);
    }

    static public function factory($model)
    {
        return new self($model);
    }

}
