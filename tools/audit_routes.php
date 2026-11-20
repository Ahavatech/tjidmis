<?php
// Developer tool: lists every routable controller method as "page|op|area|class|params".
// Run inside the installed app: php tools/audit_routes.php
require __DIR__ . '/../app/bootstrap.php';
$r = new ReflectionClass(App\Core\Router::class);
$p = $r->getProperty('routes');
$p->setAccessible(true);
$routes = $p->getValue(new App\Core\Router());
foreach ($routes as $page => $def) {
    $cls = $def[0];
    foreach ((new ReflectionClass($cls))->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
        if ($m->isStatic() || $m->isConstructor() || $m->class !== $cls) continue;
        $area = strpos($cls, '\\Backend\\') !== false ? 'backend' : 'public';
        echo $page . '|' . $m->name . '|' . $area . '|' . substr(strrchr($cls, '\\'), 1) . '|' . $m->getNumberOfParameters() . "\n";
    }
}
