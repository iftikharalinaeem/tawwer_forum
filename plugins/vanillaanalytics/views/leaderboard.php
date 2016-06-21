<?php if (!defined('APPLICATION')) exit(); ?>
<h3><?php echo $this->title(); ?></h3>
<table>
    <thead>
        <tr>
            <th></th>
            <th></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($this->data('Leaderboard') as $currentRow): ?>
        <?php $leaderRecord = $currentRow['LeaderRecord']; ?>
        <tr>
            <td><?php echo $leaderRecord['Position']; ?></td>
            <td><?php echo htmlspecialchars($leaderRecord['Title']); ?></td>
            <td class="LeaderPosition<?php echo $leaderRecord['PositionChange']?>">
                <?php echo $leaderRecord['Previous'] ?: ''; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
