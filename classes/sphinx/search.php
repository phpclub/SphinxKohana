<?php

class Sphinx_Search implements Iterator, Countable
{

    protected $search = NULL;
    protected $model = NULL;
    protected $result = NULL;
    protected $driver = NULL;
    protected $query = NULL;
    protected $index_config = NULL;
    protected $config = NULL;

    public $return_model = FALSE;
    public $sc = NULL;
    public $limit = 100;
    public $offset = 0;
    public $last_error = NULL;
    public $last_warning = NULL;

    static public function factory($model, $config = 'default')
    {
        return new Sphinx($model, $config);
    }

    /**
     * Initialize the index/model config
     *
     * @throws Sphinx_Exception on invalid $index
     * @return void
    */
    public function __construct($index, $config = 'default')
    {
        $this->config = Kohana::config('sphinx.'.$config);

        if (!is_object($index))
        {
            $this->index_config = new stdClass();

            $this->index_config->index = $index;
        }
        else
        {
            if ($index instanceof Sphinx_Conf)
            {
                $this->index_config = $index; 
            }
            elseif ($index instanceof Sphinx_Model)
            {
                $this->model = $index;

                $this->index_config = $this->model->_sphinx_index();

                // Load the Model Driver
                $driver = 'Sphinx_Driver_'.ucfirst($this->config['driver']);

                $this->driver = new $driver($this->model);

                // We have a model, so return it in the Iterator
                $this->return_model = TRUE;
            }
            else
            {
                throw new Sphinx_Exception('Index must be instanceof Sphinx_Model || Sphinx_Conf');
            }
        }

        // Load SphinxClient with default values
        $this->_init();
    }

    protected function _init()
    {
        // Load SphinxClient Class
        $this->sc = new SphinxClient();

        // Set Default Sort Relevance
        $this->sort_relevance();

        // Set Server and port if they differ from default
        if (isset($this->config['server']) && isset($this->config['port']))
        {
            $this->sc->SetServer($this->config['server'], $this->config['port']);
        }
        
        // Set Server connect timeout setting
        if (isset($this->config['timeout']))
        {
            $this->sc->SetConnectTimeout($this->config['timeout']);
        }
    }

    /**
     * Runs the indexer
     *
     * @param   bool    tells the indexer to push/fork the task
     * @return  string  returns the output of the index command
     */
    public function run_index($push = FALSE)
    {
        if ($this->index_config instanceof Sphinx_Conf)
        {
            $this->mk_index();
        }

        return Sphinx::index($this->index_config->index, $push);
    }

    public function __get($var)
    {
        switch($var)
        {
            case 'results':
                return $this->result();
            break;

            case 'search':
                return $this->search();
            break;

            case 'total':
                return isset($this->result['total'])? $this->result['total'] : FALSE;
            break;

            case 'total_found':
                return isset($this->result['total_found'])? $this->result['total_found'] : FALSE;
            break;

            case 'time':
                return isset($this->result['time'])? $this->result['time'] : FALSE;
            break;

            default:
                return NULL;
        }
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

    /**
     * Get the current result matche @count value
     * 
     * @param   int     current iterator's key
     * @return  numeric
     */
    public function counts($match)
    {
        return $this->attr($match, '@count');
    }

    /**
     * Get the current result match @group value
     * 
     * @param   int     current iterator's key
     * @return numeric
     */
    public function groups($match)
    {
        return $this->attr($match, '@group');
    }

    /**
     * Get Dynamic Attributes, mostly used when in Model mode, and the model doesn't have this informaion
     *
     * @param   int     current iterator's key
     * @param   string  attribute to retreive
     * @return mixed
     */
    public function attr($match, $attribute)
    {
        if (isset($this->result['matches'][$match]) && isset($this->result['matches'][$match]['attrs'][$attribute]))
        {
            return $this->result['matches'][$match]['attrs'][$attribute];
        }
        return NULL;
    }

    /**
     * Sets Limit
     *
     * @param   int     limit
     * @return  $this
     */
    public function limit($limit)
    {
        $this->limit = (int)$limit;

        return $this;
    }

    /**
     * Sets Offset
     *
     * @param   int     offset
     * @return  $this
     */
    public function offset($offset)
    {
        $this->offset = (int)$offset;

        return $this;
    }

    /**
     * Allows sorting by desc/asc or with sort functions
     *
     * @param   string  field name
     * @param   mixed   order/string || const of sort function
     * @return  $this
     */
    public function order_by($field, $order = 'asc')
    {
        // convert common strings to const version
        switch($order)
        {
            case 'asc':
                $order = SPH_SORT_ATTR_ASC;
            break;

            case 'desc':
                $order = SPH_SORT_ATTR_DESC;
            break;

            case 'exp':
                $order = SPH_SORT_EXPR;
            break;

            case 'ext':
                $order = SPH_SORT_EXTENDED;
            break;

            case 'time':
                $order = SPH_SORT_TIME_SEGMENTS;
            break;

            case 'rel':
                $order = SPH_SORT_RELEVANCE;
            break;

            default:
                // do nothing
        }

        $this->sc->SetSortMode($order, $field);

        return $this;
    }

    /**
     * Set Sorting to Relevance
     *
     * @return  $this
     */
    public function sort_relevance()
    {
        $this->sc->SetSortMode(SPH_SORT_RELEVANCE);

        return $this;
    }

    /**
     * Alias of order_by
     * 
     * @param   string  sort field or expression
     * @param   int     const of sorting mode
     * @return  $this
     */
    public function sort_by($sort_by, $mode)
    {
        $this->order_by($sort_by, $mode);

        return $this;
    }

    /**
     * Filter given attribute by given ids
     *
     * @param   string  attribute
     * @param   array   ids of attribute
     * @param   bool    exclude given values or not
     * @return  $this
     */
    public function filter($attribute, array $values, $exclude = FALSE)
    {
        $this->sc->SetFilter($attribute, $values, $exclude);

        return $this;
    }

    /**
     * Filter a given range, this method determines if range is int or float
     *
     * @param   string      attribute
     * @param   numeric     min range
     * @param   numeric     max range
     * @param   bool        exclude given range or not
     * @return $this
     */
    public function filter_range($attribute, $min, $max, $exclude = FALSE)
    {
        if (is_int($min) || is_int($max))
        {
            $this->sc->SetFilterRange($attribute, $min, $max, $exclude);
        }
        else
        {
            $this->sc->SetFilterFloatRange($attribute, $min, $max, $exclude);
        }
        return $this;
    }

    /**
     * Sets the match mode
     *
     * @param   int     constant of match mode  
     * @return  $this
     */
    public function match_mode($mode)
    {
        $this->sc->SetMatchMode($mode);

        return $this;
    }

    /**
     * Groups the search by given attribute
     * 
     * @param   string  attribute
     * @param   mixed   if asc || desc sets the direction, otherwise sets groupby Function
     * @param   string  sets group by direction
     * @return $this
     */
    public function group_by($attribute, $func = NULL, $dir = 'desc')
    {
        if ($func == 'desc' || $func == 'asc')
        {
            // set direction
            $dir = $func;

            // make func null to make it default
            $func = NULL;
        }

        if (is_null($func))
        {
            // default grouping to Attribute
            $func = SPH_GROUPBY_ATTR;
        }

        $this->sc->SetGroupBy($attribute, $func, "@group {$dir}");

        return $this;
    }

    /**
     * Set Ranking Mode.
     *
     * @param   int     const of Ranking mode
     * @retun   $this
     */
    public function ranking_mode($ranker)
    {
        $this->sc->SetRankingMode($ranker);

        return $this;
    }

    /**
     * Sets the iterator to return the model or not
     * 
     * @param   bool
     * @return  $this
     */
    public function return_model($value = TRUE)
    {
        $this->return_model = (bool)$value;

        return $this;
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

                    $this->search = $this->driver->in($docids);
                }
            }
            else
            {
                $this->search = array();
            }
        }
        return $this->search;
    }

    protected function do_search()
    {
        $this->sc->SetLimits($this->offset, $this->limit);

        $this->result = $this->sc->Query($this->query, $this->index_config->index);
        $this->last_error = $this->sc->GetLastError();
        if ($this->last_error !== '' && $this->config['debug'])
        {
            throw new Sphinx_Exception($this->last_error);
        }
        $this->last_warning = $this->sc->GetLastWarning();
        if ($this->last_warning !== '' && $this->config['debug'])
        {
            throw new Sphinx_Exception($this->last_warning);
        }
    }

    /**
     * Makes the conf file for passed in Sphinx_Conf class
     * Saves this file in the data folder
     *
     * @return void
     */
    protected function mk_index()
    {
        $file = "source {$this->index_config->index}_src".PHP_EOL."{".PHP_EOL."\t"."type = mysql".PHP_EOL;
        foreach($this->config['database'] as $key => $variable)
        {
            $file.="\t"."{$key} = ".$variable.PHP_EOL;
        }
        $file.="\t"."sql_query = ".str_replace(PHP_EOL, " \\".PHP_EOL, $this->index_config->sql_query).PHP_EOL.PHP_EOL;
        foreach($this->index_config->attributes as $attribute)
        {
            $file.="\t"."{$attribute[0]} = ".$attribute[1].PHP_EOL;
        }
        $file.="}".PHP_EOL.PHP_EOL."index {$this->index_config->index}".PHP_EOL."{"
            .PHP_EOL."\t"."source = {$this->index_config->index}_src"
            .PHP_EOL."\t"."path = ".DOCROOT."{$this->config['data_folder']}/{$this->index_config->index}".PHP_EOL;
        foreach ($this->index_config->index_conf->values as $var => $value)
        {
            $file.="\t"."{$var} = ".$value.PHP_EOL;
        }
        $file.="}".PHP_EOL;
        $filename = DOCROOT.$this->config['data_folder'].'/sphinx_'.$this->index_config->index.'.conf';
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
        return is_object($this->search())? $this->search->rewind() : reset($this->search);
    }

    public function current()
    {
        return is_object($this->search())? $this->search->current() : current($this->search);
    }

    public function key()
    {
        if (is_object($this->search()))
        {
            // Return Model Primary Key if set
            if (isset($this->index_config->pk))
            {
                return $this->search->current()->{$this->index_config->pk};
            }
            return $this->search->key();
        }
        return key($this->search);
    }

    public function next()
    {
        return is_object($this->search())? $this->search->next() : next($this->search);
    }

    public function valid()
    {
        return is_object($this->search())? $this->search->valid() : is_array($this->search)? current($this->search)!==FALSE : FALSE;
    }

    /**
     * Static Methods 
     */
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

    static protected function run($cmd, $push = FALSE)
    {
        return $push? `{$cmd} > /dev/null 2>&1` : `{$cmd}`;
    }

}
