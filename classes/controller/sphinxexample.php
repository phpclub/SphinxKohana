<?php defined('SYSPATH') or die('No direct script access.');

class Controller_SphinxExample extends Controller {

	public function action_index()
	{
        $actor_search = Sphinx::factory(Sprig::Factory('actor'));

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

    public function action_test3($query = null)
    {
        $config = new Sphinx_Conf('film_text');
        $config->sql_query = "
            SELECT film_id, title, title as sort_title, description
            FROM `film_text`";

        $config->sql_attr_str2ordinal = 'sort_title';

        // Adds Default Index Conf
        $config->index();

        $search = Sphinx::factory($config);

        if (is_null($query))
        {
            echo '<pre>';
            echo $search->run_index();
            echo '</pre>';
        }
        else
        {
            $search->query = $query;
            $search->limit = 20;

            echo Kohana::debug($search->get_results);
        }
    }

    public function action_test2($query = null)
    {
        $search = Sphinx::factory(Sprig::factory('actor'));
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

    public function action_string()
    {
        // You can also give the Index Name for non-model indexes.
        // But for this example im using the name i gave my model index (__CLASS__)
        $search = Sphinx::factory('Model_Actor');
        $search->limit = 10;
        $search->order_by('films');

        ?>
        <h3><?php echo count($search);?> results.</h3>
        <table width="500"> 
        <?php foreach($search as $actor): ?>
        <tr>
            <td>
            <?php echo $actor['attrs']['films']; ?>
            </td>
            <td>
            <?php echo Kohana::debug($actor); ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </table>
        <?php
    }

    public function action_test($page = 1)
    {
        $search = Sphinx::factory(Sprig::factory('actor'));
        $search->limit = 10;
        $search->offset = $search->limit * (($page<=0? 1 : $page)-1);
        $search->order_by('sort_fname');

        $result = $search->get_results;
        ?>
        <h3><?php echo count($search);?> results. Of <?php echo $search->total;?></h3>
        <table width="500"> 
        <?php foreach($search as $actor): ?>
        <tr>
            <td>
            <?php echo $search->attr($actor->actor_id, 'films');?>
            </td>
            <td>
            <?php echo $actor->first_name; ?>
            </td>
            <td>
            <?php echo $actor->last_name; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </table>
        <?php

        if (!$result)
        {
            echo Kohana::debug($search->last_error);
        }
        else
        {
            echo Kohana::debug($result);
        }
    }

} // End Welcome
