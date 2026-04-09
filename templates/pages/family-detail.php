<?php
$db = getDb();
$familyId = (int) ($_GET['id'] ?? 0);

$family = $db->prepare('SELECT * FROM families WHERE id = ?');
$family->execute([$familyId]);
$family = $family->fetch();

if (!$family) {
    flash('error', 'Family not found.');
    redirect('families');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'update_family') {
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            $stmt = $db->prepare('UPDATE families SET name = ? WHERE id = ?');
            $stmt->execute([$name, $familyId]);
            flash('success', 'Family name updated.');
            redirect('family-detail&id=' . $familyId);
        }
    }

    if ($action === 'delete_family') {
        $db->prepare('DELETE FROM families WHERE id = ?')->execute([$familyId]);
        flash('success', "Family \"{$family['name']}\" deleted.");
        redirect('families');
    }

    if ($action === 'add_member') {
        $email = trim($_POST['email'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        if ($email !== '' && $displayName !== '') {
            $token = bin2hex(random_bytes(16));
            $stmt = $db->prepare('INSERT INTO members (family_id, email, display_name, unsubscribe_token) VALUES (?, ?, ?, ?)');
            $stmt->execute([$familyId, $email, $displayName, $token]);
            flash('success', "Member \"{$displayName}\" added.");
            redirect('family-detail&id=' . $familyId);
        }
    }

    if ($action === 'toggle_member') {
        $memberId = (int) ($_POST['member_id'] ?? 0);
        $db->prepare('UPDATE members SET active = NOT active WHERE id = ? AND family_id = ?')->execute([$memberId, $familyId]);
        redirect('family-detail&id=' . $familyId);
    }

    if ($action === 'delete_member') {
        $memberId = (int) ($_POST['member_id'] ?? 0);
        $db->prepare('DELETE FROM members WHERE id = ? AND family_id = ?')->execute([$memberId, $familyId]);
        flash('success', 'Member removed.');
        redirect('family-detail&id=' . $familyId);
    }

    if ($action === 'add_account') {
        $label = trim($_POST['label'] ?? '');
        if ($label !== '') {
            $stmt = $db->prepare('INSERT INTO scout_accounts (family_id, label) VALUES (?, ?)');
            $stmt->execute([$familyId, $label]);
            flash('success', "Scout account \"{$label}\" created.");
            redirect('family-detail&id=' . $familyId);
        }
    }

    if ($action === 'update_balance') {
        $accountId = (int) ($_POST['account_id'] ?? 0);
        $balance = round((float) ($_POST['balance'] ?? 0), 2);
        $db->prepare('UPDATE scout_accounts SET balance = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND family_id = ?')
            ->execute([$balance, $accountId, $familyId]);
        flash('success', 'Balance updated.');
        redirect('family-detail&id=' . $familyId);
    }

    if ($action === 'delete_account') {
        $accountId = (int) ($_POST['account_id'] ?? 0);
        $db->prepare('DELETE FROM scout_accounts WHERE id = ? AND family_id = ?')->execute([$accountId, $familyId]);
        flash('success', 'Scout account removed.');
        redirect('family-detail&id=' . $familyId);
    }
}

$members = $db->prepare('SELECT * FROM members WHERE family_id = ? ORDER BY display_name');
$members->execute([$familyId]);
$members = $members->fetchAll();

$accounts = $db->prepare('SELECT * FROM scout_accounts WHERE family_id = ? ORDER BY label');
$accounts->execute([$familyId]);
$accounts = $accounts->fetchAll();

$pageTitle = $family['name'];
?>

<div class="action-bar">
    <h2><?= htmlspecialchars($family['name']) ?></h2>
    <a href="?page=families" class="btn btn-secondary btn-small">Back to Families</a>
</div>

<!-- Family name -->
<div class="card">
    <h3>Family Name</h3>
    <form method="post" class="inline-form">
            <?= csrfField() ?>
        <input type="hidden" name="action" value="update_family">
        <div class="form-group">
            <input type="text" name="name" value="<?= htmlspecialchars($family['name']) ?>" required>
        </div>
        <button type="submit" class="btn btn-secondary">Update</button>
    </form>
</div>

<!-- Members -->
<div class="card">
    <h3>Members</h3>
    <?php if (!empty($members)): ?>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $member): ?>
            <tr>
                <td><?= htmlspecialchars($member['display_name']) ?></td>
                <td><?= htmlspecialchars($member['email']) ?></td>
                <td>
                    <span class="badge <?= $member['active'] ? 'badge-active' : 'badge-inactive' ?>">
                        <?= $member['active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </td>
                <td style="white-space: nowrap;">
                    <form method="post" style="display:inline;">
            <?= csrfField() ?>
                        <input type="hidden" name="action" value="toggle_member">
                        <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                        <button type="submit" class="btn btn-secondary btn-small">
                            <?= $member['active'] ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </form>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Remove this member?')">
            <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_member">
                        <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-small">Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <?php endif; ?>
    <form method="post" class="inline-form">
            <?= csrfField() ?>
        <input type="hidden" name="action" value="add_member">
        <div class="form-group">
            <label for="display_name">Name</label>
            <input type="text" id="display_name" name="display_name" placeholder="First name" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="email@example.com" required>
        </div>
        <button type="submit" class="btn btn-primary">Add Member</button>
    </form>
</div>

<!-- Scout Accounts -->
<div class="card">
    <h3>Scout Accounts</h3>
    <?php if (!empty($accounts)): ?>
    <table>
        <thead>
            <tr>
                <th>Label</th>
                <th>Balance</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accounts as $account): ?>
            <tr>
                <td><?= htmlspecialchars($account['label']) ?></td>
                <td>
                    <form method="post" class="inline-form">
            <?= csrfField() ?>
                        <input type="hidden" name="action" value="update_balance">
                        <input type="hidden" name="account_id" value="<?= $account['id'] ?>">
                        <div class="form-group">
                            <input type="number" name="balance" value="<?= number_format($account['balance'], 2, '.', '') ?>" step="0.01" style="width: 120px;">
                        </div>
                        <button type="submit" class="btn btn-secondary btn-small">Save</button>
                    </form>
                </td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this scout account?')">
            <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_account">
                        <input type="hidden" name="account_id" value="<?= $account['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-small">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <?php endif; ?>
    <form method="post" class="inline-form">
            <?= csrfField() ?>
        <input type="hidden" name="action" value="add_account">
        <div class="form-group">
            <label for="label">Add scout account</label>
            <input type="text" id="label" name="label" placeholder="Scout's first name" required>
        </div>
        <button type="submit" class="btn btn-primary">Add</button>
    </form>
</div>

<!-- Delete family -->
<div class="card" style="border-color: var(--color-error);">
    <h3>Delete Family</h3>
    <p>This will permanently remove this family, all its members, and all scout accounts.</p>
    <br>
    <form method="post" onsubmit="return confirm('Are you sure? This cannot be undone.')">
            <?= csrfField() ?>
        <input type="hidden" name="action" value="delete_family">
        <button type="submit" class="btn btn-danger">Delete Family</button>
    </form>
</div>
