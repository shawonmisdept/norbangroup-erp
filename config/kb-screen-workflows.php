<?php

/*
| Screen-specific step-by-step KB workflows (section 3).
| Keys: module code → submodule key → workflow_en / workflow_bn (HTML).
|
| Split across:
|   kb-hrm-workflows.php  — HRM submodule step tables
|   kb-tms-workflows.php  — TMS overview + all submodule step tables
|   kb-other-workflows.php — Commercial, Masters, Admin, HRM Masters + HRM module overviews
*/
return array_replace_recursive(
    require __DIR__ . '/kb-hrm-workflows.php',
    require __DIR__ . '/kb-tms-workflows.php',
    require __DIR__ . '/kb-other-workflows.php',
);
