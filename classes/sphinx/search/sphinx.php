<?php

class Sphinx_Search_Sphinx
{

    protected $model = NULL;

    public $query = NULL;

    static public function factory($model)
    {
        return new self($model);
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

    public function search()
    {
        $sl = new SphinxClient(); 
        $sl->SetMatchMode(SPH_MATCH_EXTENDED2);
        $sl->SetLimits(0, 100);
        $sl->SetSortMode(SPH_SORT_EXTENDED, '@relevance DESC');
        
        $result = $sl->Query($this->query, $this->model_config->index);
        
        if ($result && isset($result['matches']))
        {
            $docids = array_keys($result['matches']);
        }
        else
        {
            var_dump($sl->GetLastError());
            var_dump($sl->GetLastWarning());
            return array();
        }

        $query = DB::select()
            ->where($this->model->pk(), 'IN', $docids)
            ->order_by(new Database_Expression('FIELD('.$this->model->pk().', '.implode(',', $docids).')'));

        return $this->model->load($query, null);
    }

    public function index($verbose = FALSE)
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

        $report = `{$this->config['bin']}/indexer {$this->model_config->index} --config {$docroot}modules/sphinx/classes/sphinx.php --rotate`;

        if ( $verbose ) {
            echo '<pre>';
            echo $report;
            echo '</pre>';
        }
    }
}
