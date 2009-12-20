<?php

$start = microtime(TRUE);
$sl->SetMatchMode(SPH_MATCH_EXTENDED2);
$sl->SetLimits(0,100);
$sl->SetSortMode(SPH_SORT_EXTENDED, '@relevance DESC');

$q = isset($_GET['q'])? $_GET['q'] : '';

$qu = '';
if ( !empty($q) )
{
    $qu .= $q;
}

$actors = $db->query(Database::SELECT, 'Select `actor_id` as `id`, `first_name`, `last_name` FROM `actor`', false);
$actors_array = array();
if ( isset($_GET['actor']) && !empty($_GET['actor']) )
{
    $actors_array = explode(',', $_GET['actor']);
    foreach($actors_array as $a_actor)
    {
        $sl->SetFilter('actor_id', (array)$a_actor);
    }
}
$rating = '';
if ( isset($_GET['rating']) && !empty($_GET['rating']) )
{
    $rating = $_GET['rating'];
    $qu .= ' @rating "'.$_GET['rating'].'"';
}
$res = $sl->Query($qu, 'film');
?>
<?php if ($res && !empty($res['matches'])): ?>
<div style="height:200px;width:400px;overflow:auto;">
<h3>Actors</h3>
<table>
    <tr>
        <th>ID</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Count</th>
    </tr>
    <?php 
    $num = ceil(count($actors) / 8);
    $i = 1;
    $total = 0;
    foreach($actors as $actor):?>
    <?php
            $sl->ResetFilters();
            /*
            $sl->SetMatchMode(SPH_MATCH_ALL);
            $new_actors_array = $actors_array;
            $new_actors_array[] = $actor['id'];
            */
            if ( !empty($actors_array) )
            {
                foreach($actors_array as $a_actor)
                {
                    $sl->SetFilter('actor_id', (array)$a_actor);
                }
            }
            $sl->SetFilter('actor_id', array($actor['id']));
            $sl->SetLimits(0,1);
            $a_res = $sl->Query($qu, 'film');
            $count = $a_res['total'];
            $total += $a_res['time'];
            if ( $count==0 ) continue;
    ?>
        <tr>
            <td><a href="?rating=<?php echo $rating;?>&q=<?php echo $q?>&actor=<?php echo (!empty($actors_array)? implode(',',$actors_array).',' : '').$actor['id'];?>"><?php echo $actor['id'];?></a></td>
            <td><?php echo $actor['first_name'];?></td>
            <td><?php echo $actor['last_name'];?></td>
            <td>(<?php echo $count;?>)</td>
        </tr>
    <?php $i++; endforeach;?>
</table>
</div>
<?php var_dump($total); ?>
<?php $ids = implode(', ', array_keys($res['matches']));

    $query = $db->query(Database::SELECT, 'Select `film_id` as id, title, description, release_year, rental_duration, length, replacement_cost, rating, special_features
        FROM `film` WHERE `film_id` IN('.$ids.') ORDER BY FIELD(`film_id`, '.$ids.')', false);

    echo 'Total: ', $res['total'], '<br/>';
    echo 'Total Found: ', $res['total_found'], '<br/>';
    echo 'Time: ', $res['time'];


    $merged = array();
    foreach($query as $qu)
    {
        $merged[$qu['id']] = $qu['title'].', '.$qu['description'].', '.$qu['special_features'];
    }

    $exc = $sl->BuildExcerpts($merged, 'film', $q, array(
        'limit' => 35,
    ));
    
    if ( isset($res['words']) )
    {
        var_dump($res['words']);
    }
    $query->seek(0);
    ?>
    <table>
    <tr>
    <?php foreach($query->current() as $key => $value): ?>
        <th><?php echo $key; ?></th>
    <?php endforeach; ?>
        <th>Match</th>
    </tr>

    <?php $i=0; foreach($query as $result): ?>
        <tr>
        <?php foreach ($result as $key => $value): ?>
            <td><?php echo $value; ?></td>
        <?php endforeach; ?>
        <td><?php echo !empty($q)? $exc[$i] : '';?></td>
        </tr>
    <?php $i++; endforeach; ?>
    </table>
    <pre>
    <?php //print_r($res); ?>
    </pre>
    <?php 
else: ?>

<?php var_dump($res); ?>

<?php var_dump($sl->GetLastError()); ?>
<?php var_dump($sl->GetLastWarning()); ?>

<?php endif; ?>

<?
var_dump(round(microtime(TRUE) - $start, 4));
?>
