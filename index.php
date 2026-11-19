<?php
require __DIR__ . '/app/bootstrap.php';
(new App\Core\Router())->dispatch();
