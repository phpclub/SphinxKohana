<?php defined('SYSPATH') or die('No direct script access.');

class Controller_SphinxExample extends Controller {

    public function before()
    {
        echo '
        <a href="'.URL::site('sphinxexample').'">Examples Home</a> | 
        <a href="'.URL::site('sphinxexample/runindex').'">Run Index</a> |
        <a href="'.URL::site('sphinxexample/run/start').'">Start Daemon</a> |
        <a href="'.URL::site('sphinxexample/run/stop').'">Stop Daemon</a> |
        <a href="'.URL::site('sphinxexample/run/restart').'">Restart Daemon</a>
        <br/>';
        echo '
        <a href="'.URL::site('sphinxexample/test').'">Example 1</a> | 
        <a href="'.URL::site('sphinxexample/test2').'">Example 2</a> | 
        <a href="'.URL::site('sphinxexample/test3').'">Example 3</a> | 
        <a href="'.URL::site('sphinxexample/string').'">Normal Index(non model)</a> | 
        <a href="'.URL::site('sphinxexample/orm').'">ORM Model</a> | 
        <br/>';
    }

    public function action_index()
    {
    }

	public function action_runindex()
	{
        echo '<pre>';
        echo Sphinx::index();
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

    public function action_orm($query = NULL)
    {
        // Passes in ORM config, which has driver set to orm
        $search = Sphinx::factory(ORM::factory('film'), 'orm');

        echo '<br/><form method="post"><input name="query" value="'.(isset($_POST['query'])? $_POST['query'] : 'goldfinger').'"><input type="submit"/></form>';

        if (!isset($_POST['query']))
        {
            echo '<pre>';
            echo $search->run_index();
            echo '</pre>';
        }
        else
        {
            $search->query = $_POST['query'];
            $search->limit = 20;

            ?>
            <table>
            <?php foreach($search as $film): ?>
            <tr>
                <td><?php echo $film->film_id;?></td>
                <td><?php echo $film->title;?></td>
                <td><?php echo $film->description;?></td>
            </tr>
            <?php endforeach;?>
            </table>
            <?php
        }
    }

    public function action_test3()
    {
        $config = new Sphinx_Conf('film_text');
        $config->sql_query = "
            SELECT film_id, title, title as sort_title, description
            FROM `film_text`";

        $config->sql_attr_str2ordinal = 'sort_title';

        // Adds Default Index Conf
        $config->index();

        $search = Sphinx::factory($config);

        echo '<h3>Passing Sphinx_Conf object instead of normal model</h3>
            <br/><form method="post"><input name="query" value="'.(isset($_POST['query'])? $_POST['query'] : 'goldfinger').'"><input type="submit"/></form>';

        if (!isset($_POST['query']))
        {
            echo '<pre>';
            echo $search->run_index();
            echo '</pre>';
        }
        else
        {
            $search->query = $_POST['query'];
            $search->limit = 20;

            echo Kohana::debug($search->results);
        }
    }

    public function action_test2($query = null)
    {
        $search = Sphinx::factory(Sprig::factory('actor'));
        $search->query = $query;
        //$search->limit = 10;
        $search->order_by('films', 'desc');
        $search->group_by('films');
        // Result matches key is no longer docid
        $search->sc->SetArrayResult(TRUE);

        $result = $search->results;

        echo '<h3>Grouping, Ordering, and Using Array result instead of Hash</h3>';
        echo '<pre>';
        echo '# Films', "\t\t", '# of actors with', '<br/>';
        foreach($search as $key => $actor)
        {
            echo $result['matches'][$key]['attrs']['films'], "\t", ' - ', "\t", $search->counts($key), '<br/>';
        }
        echo Kohana::debug($result);
        echo '</pre>';
    }

    public function action_string()
    {
        // You can also give the Index Name for non-model indexes.
        // But for this example im using the name i gave my model index (__CLASS__)
        $search = Sphinx::factory('Model_Actor');
        $search->limit = 10;
        $search->order_by('films', 'desc');

        ?>
        <h3>This is just passing in a Index Name to Sphinx::Factory</h3>
        <h3><?php echo count($search);?> results.</h3>
        <table width="500"> 
        <?php foreach($search as $i => $actor): ?>
        <tr>
            <td>
            <?php echo $i; //echo $actor['attrs']['films']; ?>
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
        $page = $page<=0? 1 : $page;
        $search = Sphinx::factory(Sprig::factory('actor'));
        $search->limit = 10;
        $search->offset = $search->limit * ($page-1);
        $search->order_by('sort_fname');

        $result = $search->results;
        ?>
        <h2>Sorted by first name</h2>
        <h3><?php echo $search->offset+1, ' - ', $search->offset+10;?> results. Of <?php echo $search->total;?></h3>
        <a href="<?php echo URL::site('sphinxexample/test/'.($page-1));?>">Prev</a> | <a href="<?php echo URL::site('sphinxexample/test/'.($page+1));?>">Next</a>
        <br/>
        <table width="500"> 
        <tr>
            <th>docid</th>
            <th>Films</th>
            <th>First Name</th>
            <th>Last Name</th>
        </tr>
        <?php foreach($search as $i => $actor): ?>
        <tr>
            <td><?php echo $actor->actor_id; ?></td>
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
