<?php
// Phase 2 blocker reproduction. No application boot or HTTP requests; read-only transaction.
require dirname(__DIR__, 4).'/vendor/autoload.php';
$root = dirname(__DIR__, 4);
Dotenv\Dotenv::createImmutable($root)->safeLoad();
$p = new PDO('mysql:host='.$_ENV['DB_HOST'].';port='.($_ENV['DB_PORT']??3306).';dbname='.$_ENV['DB_DATABASE'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$p->exec('START TRANSACTION READ ONLY');
try {
    $out = ['database'=>$p->query('SELECT DATABASE()')->fetchColumn(), 'mode'=>'START TRANSACTION READ ONLY', 'tests'=>[]];
    if ($out['database'] !== 'leader') throw new RuntimeException('Unexpected database');
    $out['missing_tables'] = [];
    foreach (['yards','users','vaults','vault_transactions'] as $table) {
        $s=$p->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?'); $s->execute([$table]);
        if (!$s->fetchColumn()) $out['missing_tables'][]=$table;
    }
    $conn = new Illuminate\Database\MySqlConnection($p, 'leader');
    foreach (['companies','private_companies'] as $table) {
        foreach ([['id','desc'],['phase2_unknown_sort','desc'],['id','phase2_invalid_direction']] as [$column,$direction]) {
            $case=['table'=>$table,'sort_by'=>$column,'sort_dir'=>$direction];
            try { $q=$conn->table($table)->orderBy($column,$direction)->limit(20); $case['sql']=$q->toSql(); $rows=$q->get(); $case['outcome']='success'; $case['count']=$rows->count(); }
            catch (Throwable $e) { $case['outcome']='exception'; $case['exception']=get_class($e); $case['message']=$e->getMessage(); }
            $out['tests'][]=$case;
        }
    }
    echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
} finally { $p->exec('ROLLBACK'); }
