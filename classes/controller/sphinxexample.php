<?php defined('SYSPATH') or die('No direct script access.');

class Controller_SphinxExample extends Controller {

	public function action_index()
	{
        $actor_search = Sphinx::factory('actor');

        echo '<pre>';
        echo $actor_search->run_index();
        echo '</pre>';
	}

    public function action_run($cmd = null)
    {
        echo '<pre>';
        if ($cmd == 'start')
        {
            echo Sphinx::start();
        }
        elseif ($cmd == 'restart')
        {
            echo Sphinx::restart();
        }
        elseif ($cmd == 'stop')
        {
            echo Sphinx::stop();
        }
        elseif ($cmd == 'index')
        {
            echo Sphinx::index();
        }
        echo '</pre>';
    }

    public function action_test2($query = null)
    {
        $search = Sphinx::factory('actor');
        $search->query = $query;
        $search->limit = 10;
        $search->order_by('films', 'desc');
        $search->group_by('films');
        $search->sc->SetArrayResult(TRUE);

        $result = $search->get_results;

        foreach($search as $key => $actor)
        {
            echo $actor->actor_id, ' ',$actor->first_name, ' ', $actor->last_name, '<br>';
            echo $result['matches'][$key]['attrs']['films'], '<br/>';
        }

        echo Kohana::debug($result);
    }

    public function action_test($page = 1)
    {
        $search = Sphinx::factory('actor');
        $search->limit = 10;
        $search->offset = $search->limit * (($page<=0? 1 : $page)-1);

        $result = $search->get_results;

        echo Kohana::debug($result);

        foreach($search as $actor)
        {
            echo $actor->actor_id, ' ',$actor->first_name, ' ', $actor->last_name, '<br>';
        }
    }

} // End Welcome
