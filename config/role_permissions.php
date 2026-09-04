<?php

// Kept for older config('role_permissions') callers. Canonical map is config/rbac.php.
$rbac = require __DIR__.'/rbac.php';

return $rbac['role_permissions'];
