<?php
// php seed-users.php <staging.sqlite> — adds loadtest1..200@example.test (password loadtest-pass-123) and clears old sessions/cache rows.
$pdo = new PDO('sqlite:'.$argv[1]);
$hash = password_hash('loadtest-pass-123', PASSWORD_BCRYPT, ['cost' => 12]);
$now = date('Y-m-d H:i:s');
$pdo->beginTransaction();
$insert = $pdo->prepare('insert or ignore into users (name, email, email_verified_at, password, created_at, updated_at, is_admin) values (?, ?, ?, ?, ?, ?, 0)');
for ($i = 1; $i <= 200; $i++) {
    $insert->execute(["قارئ تجريبي $i", "loadtest$i@example.test", $now, $hash, $now, $now]);
}
$pdo->exec('delete from sessions');
$pdo->exec('delete from cache');
$pdo->exec('delete from cache_locks');
$pdo->commit();
echo $pdo->query("select count(*) from users where email like 'loadtest%'")->fetchColumn()." load-test users\n";
