<?php
// CLI-only evidence capture. No HTTP entrypoint, no persisted authentication.
if (PHP_SAPI !== 'cli') { exit(1); }
require __DIR__.'/../../../../vendor/autoload.php';
$app = require __DIR__.'/../../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver'=>'array','cache.default'=>'array','telescope.enabled'=>false,'app.debug'=>false,'app.url'=>'http://127.0.0.1:8765']);
if (class_exists(\Laravel\Telescope\Telescope::class)) { \Laravel\Telescope\Telescope::stopRecording(); }
$db = \Illuminate\Support\Facades\DB::connection();
if ($db->getDatabaseName() !== 'leader') { throw new RuntimeException('Unexpected database'); }
$db->statement('SET SESSION TRANSACTION READ ONLY');
$db->beginTransaction();
$queries=[];
\Illuminate\Support\Facades\DB::listen(function($q) use (&$queries) { $queries[]=$q->sql; });
// Existing role assignment id=1, in-memory only; users table is absent locally.
$actor = new \App\Models\User();
$actor->setRawAttributes(['id'=>1,'name'=>'مراجعة واجهة الإدارة']);
auth()->guard('web')->setUser($actor);
$uri=$argv[1] ?? '/dashboard/drivers';
$request=\Illuminate\Http\Request::create('http://127.0.0.1:8765'.$uri,'GET');
$kernel=$app->make(\Illuminate\Contracts\Http\Kernel::class);
$response=$kernel->handle($request);
if (isset($argv[2])) { file_put_contents($argv[2],$response->getContent()); }
$exception=$response->exception ?? null;
echo json_encode(['uri'=>$uri,'status'=>$response->getStatusCode(),'bytes'=>strlen($response->getContent()),'queries'=>count($queries),'sql'=>$queries,'error'=>$exception ? $exception->getMessage():null], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$db->rollBack();
