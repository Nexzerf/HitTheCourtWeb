<?php
// admin/members.php
// รายชื่อผู้ใช้ทั้งหมด พร้อมสถานะสมาชิก วันหมดอายุ แต้มสะสม และประวัติการสมัครสมาชิก
require_once '../config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $uid = intval($_POST['user_id'] ?? 0);
    if (($_POST['action'] ?? '') === 'set_expiry') {
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['member_expire'] ?? '') ? $_POST['member_expire'] : null;
        $pdo->prepare("UPDATE users SET is_member = ?, member_expire = ? WHERE user_id = ?")->execute([$date ? 1 : 0, $date, $uid]);
        $_SESSION['flash_success'] = t('admin.member_updated');
    }
    redirect('/admin/members.php?' . http_build_query(array_intersect_key($_GET, array_flip(['q', 'type']))));
}

$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? '';
$today = date('Y-m-d');
$where = ['1=1']; $params = [];
if ($q !== '') { $where[] = '(username LIKE ? OR email LIKE ? OR phone LIKE ? OR first_name LIKE ? OR last_name LIKE ?)'; array_push($params, ...array_fill(0, 5, "%$q%")); }
if ($type === 'member') $where[] = "is_member = 1 AND member_expire >= '$today'";
if ($type === 'regular') $where[] = "(is_member = 0 OR member_expire IS NULL OR member_expire < '$today')";
$stmt = $pdo->prepare("SELECT * FROM users WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

$adminPage = 'members';
$pageTitle = t('admin.nav_members');
include '../includes/admin_layout_top.php';
?>
<form class="filters card card-pad" method="GET">
    <div class="field"><label class="label" for="q"><?= e('admin.search') ?></label><input class="input" id="q" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="<?= e('admin.search_user_ph') ?>"></div>
    <div class="field"><label class="label" for="type"><?= e('admin.type') ?></label>
        <select class="select" id="type" name="type"><option value=""><?= e('admin.all') ?></option>
            <option value="member" <?= $type === 'member' ? 'selected' : '' ?>><?= e('profile.member') ?></option>
            <option value="regular" <?= $type === 'regular' ? 'selected' : '' ?>><?= e('member.regular') ?></option></select></div>
    <button class="btn btn-primary" type="submit"><?= e('admin.filter') ?></button>
</form>
<p class="muted small"><?= e('admin.users_count', ['n' => count($users)]) ?></p>
<div class="card table-wrap">
    <table class="table">
        <thead><tr><th><?= e('auth.username') ?></th><th><?= e('admin.contact') ?></th><th><?= e('admin.type') ?></th><th><?= e('profile.points') ?></th><th><?= e('profile.total_bookings') ?></th><th><?= e('admin.member_until') ?></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): $m = isActiveMember($u); ?>
            <tr>
                <td><strong><?= htmlspecialchars($u['username']) ?></strong><div class="adm-sub"><?= htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?: '–' ?></div></td>
                <td><?= htmlspecialchars($u['email']) ?><div class="adm-sub"><?= htmlspecialchars($u['phone'] ?? '') ?></div></td>
                <td><span class="badge <?= $m ? 'badge-gold' : '' ?>"><?= $m ? icon('crown', 12) . ' ' . e('profile.member') : e('member.regular') ?></span></td>
                <td><?= (int) $u['points'] ?></td>
                <td><?= (int) $u['total_bookings'] ?></td>
                <td>
                    <form method="POST" class="row" style="flex-wrap:nowrap">
                        <?= csrfField() ?><input type="hidden" name="action" value="set_expiry"><input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                        <input class="input input-sm" type="date" name="member_expire" value="<?= htmlspecialchars($u['member_expire'] ?? '') ?>" style="width:150px;min-height:36px">
                        <button class="btn btn-outline btn-sm" type="submit" title="<?= e('admin.save') ?>"><?= icon('check', 16) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="hint mt-1"><?= e('admin.member_hint') ?></p>
<?php include '../includes/admin_layout_bottom.php'; ?>
