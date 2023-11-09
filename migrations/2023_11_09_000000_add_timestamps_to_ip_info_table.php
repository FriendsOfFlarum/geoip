<?php

use Flarum\Database\Migration;

return Migration::addColumns('ip_info', [
    'created_at' => ['timestamp', 'nullable' => false, 'useCurrent' => true],
    'updated_at' => ['datetime', 'nullable' => true],
]);
