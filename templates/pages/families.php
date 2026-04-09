<?php
$db = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_family') {
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            $stmt = $db->prepare('INSERT INTO families (name) VALUES (?)');
            $stmt->execute([$name]);
            flash('success', "Family \"{$name}\" created.");
            redirect('family-detail&id=' . $db->lastInsertId());
        }
    }
}

$families = $db->query('
    SELECT f.*,
        COUNT(DISTINCT m.id) AS member_count,
        COUNT(DISTINCT sa.id) AS account_count
    FROM families f
    LEFT JOIN members m ON m.family_id = f.id AND m.active = 1
    LEFT JOIN scout_accounts sa ON sa.family_id = f.id
    GROUP BY f.id
    ORDER BY f.name
')->fetchAll();
?>

<div class="action-bar">
    <h2>Families</h2>
</div>

<form method="post" class="inline-form" style="margin-bottom: 1.5rem;">
            <?= csrfField() ?>
    <input type="hidden" name="action" value="add_family">
    <div class="form-group">
        <label for="name">Add a family</label>
        <input type="text" id="name" name="name" placeholder="Family name (e.g. The Andersons)" required>
    </div>
    <button type="submit" class="btn btn-primary">Add</button>
</form>

<?php if (empty($families)): ?>
    <div class="card">
        <p>No families yet. Add one above to get started.</p>
    </div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Family</th>
                <th>Members</th>
                <th>Scout Accounts</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($families as $family): ?>
            <tr>
                <td><a href="?page=family-detail&id=<?= $family['id'] ?>"><?= htmlspecialchars($family['name']) ?></a></td>
                <td><?= $family['member_count'] ?></td>
                <td><?= $family['account_count'] ?></td>
                <td><a href="?page=family-detail&id=<?= $family['id'] ?>" class="btn btn-secondary btn-small">Manage</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
