<div style="height:200px;width:400px;overflow:auto;">
<h3>Actors</h3>
<table>
    <tr>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Count</th>
    </tr>
<?php foreach($actors as $actor): ?>
    <tr>
        <td><?php echo $actor->first_name;?></td>
        <td><?php echo $actor->last_name;?></td>
        <td>(<?php //echo $count;?>)</td>
    </tr>
<? endforeach; ?>
</table>
</div>

