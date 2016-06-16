<?php if (!defined('APPLICATION')) exit(); ?>
<?php $position = 1; ?>
<h3><?php echo $this->title(); ?></h3>
<table>
    <thead>
        <tr>
            <th></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($this->data('Leaderboard') as $currentRow): ?>
        <tr>
            <td><?php echo $position++; ?></td>
            <td><?php echo htmlspecialchars($currentRow['LeaderRecord']['Title']); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
