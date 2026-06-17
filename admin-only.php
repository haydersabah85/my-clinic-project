<?php
include_once 'clinic_helpers.php';

$requiredPermissions = $requiredPermissions ?? ['admin'];
clinic_require_permissions($requiredPermissions);
