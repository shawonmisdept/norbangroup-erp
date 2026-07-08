<?php

namespace App\Support;

use App\Models\KbModule;

class KbArticleSeedBuilder
{
    public function overviewArticle(KbModule $module): array
    {
        $profile = config('kb-seed-profiles.modules.' . $module->code, []);
        $submodules = $module->submoduleDefinitions();

        $basePurposeEn = $profile['purpose_en'] ?? $this->fallbackPurposeEn($module);
        $basePurposeBn = $profile['purpose_bn'] ?? $this->fallbackPurposeBn($module);

        return $this->articlePayload(
            submoduleKey: null,
            titleEn: $module->label_en . ' — Module Guide',
            titleBn: $module->label_bn . ' — মডিউল গাইড',
            summaryEn: $profile['summary_en'] ?? ('Guide for ' . $module->label_en),
            summaryBn: $profile['summary_bn'] ?? ($module->label_bn . ' মডিউল গাইড'),
            purposeEn: $this->expandModulePurposeEn($basePurposeEn, $module, $submodules),
            purposeBn: $this->expandModulePurposeBn($basePurposeBn, $module, $submodules),
            audienceEn: $profile['audience_en'] ?? $this->fallbackAudienceEn($module),
            audienceBn: $profile['audience_bn'] ?? $this->fallbackAudienceBn($module),
            usageRulesEn: $this->expandModuleWorkflowEn($profile, $module, $submodules),
            usageRulesBn: $this->expandModuleWorkflowBn($profile, $module, $submodules),
        );
    }

    /** @param array<string, mixed> $subConfig */
    public function submoduleArticle(KbModule $module, string $key, array $subConfig): array
    {
        $label = $subConfig['label'] ?? $key;
        $description = $subConfig['description'] ?? '';
        $permission = $subConfig['permission'] ?? null;
        $manage = $subConfig['manage'] ?? null;
        $profile = config('kb-seed-profiles.modules.' . $module->code, []);
        $customSummary = config('kb-submodule-summaries.' . $module->code . '.' . $key, []);

        return $this->articlePayload(
            submoduleKey: $key,
            titleEn: $label . ' — ' . $module->label_en,
            titleBn: $label . ' — ' . $module->label_bn,
            summaryEn: $customSummary['summary_en'] ?? $description,
            summaryBn: $customSummary['summary_bn'] ?? $description,
            purposeEn: $this->submodulePurposeEn($module, $key, $label, $description, $subConfig),
            purposeBn: $this->submodulePurposeBn($module, $key, $label, $description, $subConfig),
            audienceEn: $this->submoduleAudienceEn($module, $key, $label, $permission, $manage, $profile),
            audienceBn: $this->submoduleAudienceBn($module, $key, $label, $permission, $manage, $profile),
            usageRulesEn: $this->submoduleWorkflowEn($module, $key, $label, $description, $permission, $manage, $subConfig, $profile),
            usageRulesBn: $this->submoduleWorkflowBn($module, $key, $label, $description, $permission, $manage, $subConfig, $profile),
        );
    }

    /** @return array<string, mixed> */
    private function articlePayload(
        ?string $submoduleKey,
        string $titleEn,
        string $titleBn,
        string $summaryEn,
        string $summaryBn,
        string $purposeEn,
        string $purposeBn,
        string $audienceEn,
        string $audienceBn,
        string $usageRulesEn,
        string $usageRulesBn,
    ): array {
        return [
            'submodule_key'  => $submoduleKey,
            'title_en'       => $titleEn,
            'title_bn'       => $titleBn,
            'summary_en'     => $summaryEn,
            'summary_bn'     => $summaryBn,
            'purpose_en'     => $purposeEn,
            'purpose_bn'     => $purposeBn,
            'audience_en'    => $audienceEn,
            'audience_bn'    => $audienceBn,
            'usage_rules_en' => $usageRulesEn,
            'usage_rules_bn' => $usageRulesBn,
            'body_en'        => null,
            'body_bn'        => null,
            'is_published'   => true,
        ];
    }

    /** @param array<string, array<string, mixed>> $submodules */
    private function expandModulePurposeEn(string $base, KbModule $module, array $submodules): string
    {
        $parts = [$base];

        $parts[] = '<h3>Main objectives</h3><ul>';
        $parts[] = '<li>Centralize all <strong>' . e($module->label_en) . '</strong> operations in one portal module.</li>';
        $parts[] = '<li>Ensure every transaction is factory-scoped, permission-controlled, and auditable.</li>';
        $parts[] = '<li>Provide consistent data for reports, compliance, and downstream modules (payroll, finance, TMS).</li>';
        $parts[] = '</ul>';

        if ($submodules !== []) {
            $parts[] = '<h3>Included screens &amp; functions</h3><ul>';
            foreach ($submodules as $sub) {
                if (($sub['status'] ?? 'active') === 'planned') {
                    continue;
                }
                $parts[] = '<li><strong>' . e($sub['label'] ?? '') . ':</strong> ' . e($sub['description'] ?? '') . '</li>';
            }
            $parts[] = '</ul>';
        }

        $parts[] = '<h3>Typical business cycle</h3><ol>';
        $parts[] = '<li><strong>Setup</strong> — masters, policy, and configuration (one-time or yearly).</li>';
        $parts[] = '<li><strong>Daily operation</strong> — data entry, monitoring, and first-level approval.</li>';
        $parts[] = '<li><strong>Period close</strong> — freeze, reconcile, and hand off to payroll/compliance/finance.</li>';
        $parts[] = '<li><strong>Review</strong> — management reports and audit trail verification.</li>';
        $parts[] = '</ol>';

        return implode("\n", $parts);
    }

    /** @param array<string, array<string, mixed>> $submodules */
    private function expandModulePurposeBn(string $base, KbModule $module, array $submodules): string
    {
        $parts = [$base];

        $parts[] = '<h3>মূল উদ্দেশ্য</h3><ul>';
        $parts[] = '<li><strong>' . e($module->label_bn) . '</strong> সংক্রান্ত সব কাজ এক portal module-এ কেন্দ্রীভূত করা।</li>';
        $parts[] = '<li>প্রতিটি transaction factory-wise, permission-controlled ও auditable রাখা।</li>';
        $parts[] = '<li>Report, compliance ও downstream module (payroll, finance, TMS)-এ consistent data নিশ্চিত করা।</li>';
        $parts[] = '</ul>';

        if ($submodules !== []) {
            $parts[] = '<h3>অন্তর্ভুক্ত স্ক্রিন ও কাজ</h3><ul>';
            foreach ($submodules as $sub) {
                if (($sub['status'] ?? 'active') === 'planned') {
                    continue;
                }
                $parts[] = '<li><strong>' . e($sub['label'] ?? '') . ':</strong> ' . e($sub['description'] ?? '') . '</li>';
            }
            $parts[] = '</ul>';
        }

        $parts[] = '<h3>সাধারণ business cycle</h3><ol>';
        $parts[] = '<li><strong>Setup</strong> — master, policy ও configuration (one-time বা yearly)।</li>';
        $parts[] = '<li><strong>Daily operation</strong> — data entry, monitoring ও first-level approval।</li>';
        $parts[] = '<li><strong>Period close</strong> — freeze, reconcile, payroll/compliance/finance-এ hand off।</li>';
        $parts[] = '<li><strong>Review</strong> — management report ও audit trail verification।</li>';
        $parts[] = '</ol>';

        return implode("\n", $parts);
    }

    /** @param array<string, mixed> $subConfig */
    private function submodulePurposeEn(KbModule $module, string $key, string $label, string $description, array $subConfig): string
    {
        $route = $subConfig['route'] ?? null;
        $frequency = $this->screenFrequency($key, $label);

        $parts = [
            '<p>The <strong>' . e($label) . '</strong> screen is part of <strong>' . e($module->label_en) . '</strong>. '
            . e($description) . '</p>',
            '<h3>What this screen does</h3>',
            '<ul>',
            '<li>Provides a dedicated workspace for <strong>' . e(strtolower($label)) . '</strong> records for your assigned factory/unit.</li>',
            '<li>Stores official data used by HR, management, compliance, and audit teams.</li>',
            '<li>Supports search, filter, export, and (where permitted) create/edit/approve actions.</li>',
            '</ul>',
            '<h3>When to use</h3>',
            '<p>Use this screen <strong>' . e($frequency['en']) . '</strong> as part of the standard '
            . e($module->label_en) . ' operating procedure.</p>',
            '<h3>Inputs &amp; outputs</h3>',
            '<ul>',
            '<li><strong>Input:</strong> Employee/factory context, dates, amounts, documents, and approval decisions entered by authorized staff.</li>',
            '<li><strong>Output:</strong> Updated records, notifications to next approver, and reports consumed by payroll/compliance modules.</li>',
            '</ul>',
        ];

        if ($route) {
            $parts[] = '<p><strong>System route:</strong> <code>' . e($route) . '</code></p>';
        }

        return implode("\n", $parts);
    }

    /** @param array<string, mixed> $subConfig */
    private function submodulePurposeBn(KbModule $module, string $key, string $label, string $description, array $subConfig): string
    {
        $route = $subConfig['route'] ?? null;
        $frequency = $this->screenFrequency($key, $label);

        $parts = [
            '<p><strong>' . e($module->label_bn) . '</strong> মডিউলের <strong>' . e($label) . '</strong> স্ক্রিনের মূল কাজ হলো: '
            . e($description) . '</p>',
            '<h3>এই স্ক্রিনে যা করা হয়</h3>',
            '<ul>',
            '<li>আপনার assigned factory/unit-এর <strong>' . e($label) . '</strong> সংক্রান্ত record দেখা, entry ও maintain করা।</li>',
            '<li>HR, management, compliance ও audit team-এর জন্য official data সংরক্ষণ।</li>',
            '<li>Search, filter, export এবং permission অনুযায়ী create/edit/approve করা।</li>',
            '</ul>',
            '<h3>কখন ব্যবহার করবেন</h3>',
            '<p><strong>' . e($module->label_bn) . '</strong> module-এর standard operating procedure অনুযায়ী এই স্ক্রিন '
            . e($frequency['bn']) . ' ব্যবহার করুন।</p>',
            '<h3>Input ও Output</h3>',
            '<ul>',
            '<li><strong>Input:</strong> Employee/factory context, date, amount, document ও authorized staff-এর approval decision।</li>',
            '<li><strong>Output:</strong> Updated record, next approver-কে notification, payroll/compliance module-এ report।</li>',
            '</ul>',
        ];

        if ($route) {
            $parts[] = '<p><strong>System route:</strong> <code>' . e($route) . '</code></p>';
        }

        return implode("\n", $parts);
    }

    /** @return array{en: string, bn: string} */
    private function screenFrequency(string $key, string $label): array
    {
        $haystack = strtolower($key . ' ' . $label);

        if (str_contains($haystack, 'dashboard')) {
            return ['en' => 'every working day (morning review)', 'bn' => 'প্রতিদিন (সকালে review)'];
        }

        if (str_contains($haystack, 'close') || str_contains($haystack, 'process') || str_contains($haystack, 'allocation')) {
            return ['en' => 'at month-end or period-end', 'bn' => 'মাস শেষে বা period close-এ'];
        }

        if (str_contains($haystack, 'report') || str_contains($haystack, 'register')) {
            return ['en' => 'weekly or monthly for management review', 'bn' => 'management review-এর জন্য weekly বা monthly'];
        }

        if (str_contains($haystack, 'policy') || str_contains($haystack, 'rule') || str_contains($haystack, 'setting')) {
            return ['en' => 'when policy changes or at fiscal year start', 'bn' => 'policy change হলে বা fiscal year start-এ'];
        }

        return ['en' => 'as required during daily HR/operations work', 'bn' => 'daily HR/operations কাজে প্রয়োজন অনুযায়ী'];
    }

    /** @param array<string, mixed> $profile */
    private function submoduleAudienceEn(
        KbModule $module,
        string $key,
        string $label,
        ?string $permission,
        ?string $manage,
        array $profile,
    ): string {
        $rows = $this->audienceRowsForScreen($module, $key, $label, $permission, $manage);

        return $this->audienceTable($rows, 'en', $profile['audience_en'] ?? null);
    }

    /** @param array<string, mixed> $profile */
    private function submoduleAudienceBn(
        KbModule $module,
        string $key,
        string $label,
        ?string $permission,
        ?string $manage,
        array $profile,
    ): string {
        $rows = $this->audienceRowsForScreen($module, $key, $label, $permission, $manage);

        return $this->audienceTable($rows, 'bn', $profile['audience_bn'] ?? null);
    }

    /**
     * @return list<array{role: string, dept: string, task_en: string, task_bn: string}>
     */
    private function audienceRowsForScreen(
        KbModule $module,
        string $key,
        string $label,
        ?string $permission,
        ?string $manage,
    ): array {
        $isManage = $manage !== null;
        $moduleCode = $module->code;

        $primaryRole = match (true) {
            str_starts_with($moduleCode, 'hrm-') => ['HR Officer', 'HR / Admin'],
            $moduleCode === 'tms'                => ['Transport Officer', 'Admin / Transport'],
            $moduleCode === 'commercial'         => ['Commercial Executive', 'Commercial'],
            $moduleCode === 'masters'            => ['Master Data Officer', 'IT / Admin'],
            $moduleCode === 'admin-system'       => ['System Administrator', 'IT / Management'],
            default                              => ['Module User', 'Operations'],
        };

        $approverRole = match (true) {
            str_starts_with($moduleCode, 'hrm-') => ['HR Manager', 'HR'],
            $moduleCode === 'tms'                => ['Transport Authority', 'Admin / Transport'],
            default                              => ['Department Manager', 'Management'],
        };

        $viewTaskEn = 'View ' . $label . ' data and reports';
        $viewTaskBn = $label . ' data ও report দেখা';
        $manageTaskEn = 'Create, edit, approve ' . $label . ' records';
        $manageTaskBn = $label . ' record create, edit, approve';

        $rows = [
            [
                'role'     => $primaryRole[0],
                'dept'     => $primaryRole[1],
                'task_en'  => $isManage ? $manageTaskEn : $viewTaskEn,
                'task_bn'  => $isManage ? $manageTaskBn : $viewTaskBn,
            ],
        ];

        if ($isManage || str_contains($label, 'Approve') || str_contains($key, 'approve')) {
            $rows[] = [
                'role'     => $approverRole[0],
                'dept'     => $approverRole[1],
                'task_en'  => 'Final approval and policy decisions for ' . $label,
                'task_bn'  => $label . '-এ final approval ও policy decision',
            ];
        }

        if (str_contains(strtolower($label), 'dashboard')) {
            $rows[] = [
                'role'     => 'Factory Manager',
                'dept'     => 'Management',
                'task_en'  => 'Review KPI summary (view only)',
                'task_bn'  => 'KPI summary review (view only)',
            ];
        }

        if ($permission) {
            $rows[] = [
                'role'     => 'Assigned portal user',
                'dept'     => 'As per role permission',
                'task_en'  => 'Requires <code>' . e($permission) . '</code>' . ($manage ? ' / <code>' . e($manage) . '</code>' : ''),
                'task_bn'  => '<code>' . e($permission) . '</code> permission প্রয়োজন' . ($manage ? ' (manage: <code>' . e($manage) . '</code>)' : ''),
            ];
        }

        return $rows;
    }

    /**
     * @param list<array{role: string, dept: string, task_en: string, task_bn: string}> $rows
     */
    private function audienceTable(array $rows, string $lang, ?string $moduleTableFallback): string
    {
        $taskKey = $lang === 'bn' ? 'task_bn' : 'task_en';
        $headers = $lang === 'bn'
            ? ['Role', 'Department', 'দায়িত্ব']
            : ['Role', 'Department', 'Responsibility'];

        $html = '<table><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . e($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr><td>' . e($row['role']) . '</td><td>' . e($row['dept']) . '</td><td>' . $row[$taskKey] . '</td></tr>';
        }

        $html .= '</tbody></table>';

        if ($moduleTableFallback && count($rows) <= 2) {
            $html .= $lang === 'bn'
                ? '<p><em>মডিউল-প্রয় level department তালিকার জন্য module overview দেখুন।</em></p>'
                : '<p><em>See module overview for full department list at module level.</em></p>';
        }

        return $html;
    }

    /** @param array<string, mixed> $profile */
    /** @param array<string, array<string, mixed>> $submodules */
    private function expandModuleWorkflowEn(array $profile, KbModule $module, array $submodules): string
    {
        $custom = config('kb-screen-workflows.' . $module->code . '.overview.workflow_en');
        if ($custom) {
            return trim($custom);
        }

        $parts = [];
        if (! empty($profile['usage_rules_en'])) {
            $parts[] = $profile['usage_rules_en'];
        }

        $parts[] = $this->defaultModuleWorkflowEn($module, $submodules);

        return implode("\n", $parts);
    }

    /** @param array<string, mixed> $profile */
    /** @param array<string, array<string, mixed>> $submodules */
    private function expandModuleWorkflowBn(array $profile, KbModule $module, array $submodules): string
    {
        $custom = config('kb-screen-workflows.' . $module->code . '.overview.workflow_bn');
        if ($custom) {
            return trim($custom);
        }

        $parts = [];
        if (! empty($profile['usage_rules_bn'])) {
            $parts[] = $profile['usage_rules_bn'];
        }

        $parts[] = $this->defaultModuleWorkflowBn($module, $submodules);

        return implode("\n", $parts);
    }

    /** @param array<string, array<string, mixed>> $submodules */
    private function defaultModuleWorkflowEn(KbModule $module, array $submodules): string
    {
        $parts = ['<h3>End-to-end module workflow</h3><ol>'];
        $step = 1;

        foreach ($submodules as $key => $sub) {
            if (($sub['status'] ?? 'active') === 'planned') {
                continue;
            }
            $label = $sub['label'] ?? $key;
            $parts[] = '<li><strong>Step ' . $step . ' — ' . e($label) . ':</strong> '
                . e($sub['description'] ?? 'Complete tasks on this screen') . '</li>';
            $step++;
        }

        $parts[] = '</ol>';
        $parts[] = '<p><em>Open each sub-module article below for step-by-step instructions (who does what, and what happens after each action).</em></p>';

        return implode("\n", $parts);
    }

    /** @param array<string, array<string, mixed>> $submodules */
    private function defaultModuleWorkflowBn(KbModule $module, array $submodules): string
    {
        $parts = ['<h3>End-to-end module workflow</h3><ol>'];
        $step = 1;

        foreach ($submodules as $key => $sub) {
            if (($sub['status'] ?? 'active') === 'planned') {
                continue;
            }
            $label = $sub['label'] ?? $key;
            $parts[] = '<li><strong>Step ' . $step . ' — ' . e($label) . ':</strong> '
                . e($sub['description'] ?? 'এই screen-এ কাজ complete করুন') . '</li>';
            $step++;
        }

        $parts[] = '</ol>';
        $parts[] = '<p><em>নিচের প্রতিটি sub-module article-এ step-by-step নির্দেশনা আছে (কে কী করবেন, প্রতিটি action-এর পর কী হবে)।</em></p>';

        return implode("\n", $parts);
    }

    /** @param array<string, mixed> $subConfig */
    /** @param array<string, mixed> $profile */
    private function submoduleWorkflowEn(
        KbModule $module,
        string $key,
        string $label,
        string $description,
        ?string $permission,
        ?string $manage,
        array $subConfig,
        array $profile,
    ): string {
        return $this->submoduleWorkflow($module, $key, $label, $description, $permission, $manage, $subConfig, $profile, 'en');
    }

    /** @param array<string, mixed> $subConfig */
    /** @param array<string, mixed> $profile */
    private function submoduleWorkflowBn(
        KbModule $module,
        string $key,
        string $label,
        string $description,
        ?string $permission,
        ?string $manage,
        array $subConfig,
        array $profile,
    ): string {
        return $this->submoduleWorkflow($module, $key, $label, $description, $permission, $manage, $subConfig, $profile, 'bn');
    }

    /** @param array<string, mixed> $subConfig */
    /** @param array<string, mixed> $profile */
    private function submoduleWorkflow(
        KbModule $module,
        string $key,
        string $label,
        string $description,
        ?string $permission,
        ?string $manage,
        array $subConfig,
        array $profile,
        string $lang,
    ): string {
        $customKey = 'kb-screen-workflows.' . $module->code . '.' . $key . '.workflow_' . $lang;
        $custom = config($customKey);
        if ($custom) {
            return trim($custom);
        }

        $route = $subConfig['route'] ?? null;
        $steps = $this->defaultWorkflowSteps($module, $key, $label, $description, $route, $manage, $lang);
        $parts = [$this->workflowTable($steps, $lang)];

        $parts[] = $this->workflowRulesBlock($key, $label, $permission, $manage, $profile, $lang);

        return implode("\n", $parts);
    }

    /**
     * @return list<array{step: string, who: string, action: string, result: string}>
     */
    private function defaultWorkflowSteps(
        KbModule $module,
        string $key,
        string $label,
        string $description,
        ?string $route,
        ?string $manage,
        string $lang,
    ): array {
        $menuPath = $this->menuPathForModule($module, $lang);
        $screenPath = $menuPath . ' → <strong>' . e($label) . '</strong>';
        $officer = $lang === 'bn' ? 'Module Officer' : 'Module Officer';
        $manager = $lang === 'bn' ? 'Manager' : 'Manager';
        $haystack = strtolower($key . ' ' . $label);

        if (str_contains($haystack, 'dashboard')) {
            return $lang === 'bn' ? [
                ['step' => '১', 'who' => 'HR Officer / Manager', 'action' => $screenPath . ' open করুন', 'result' => 'আজকের KPI, pending item, open period summary'],
                ['step' => '২', 'who' => 'HR Officer', 'action' => 'Red flag (high late, open approval) item identify', 'result' => 'Same day action list তৈরি'],
                ['step' => '৩', 'who' => 'HR Officer', 'action' => 'Drill-down link দিয়ে সংশ্লিষ্ট screen-এ যান', 'result' => 'Exception fix — data entry dashboard-এ নয়'],
            ] : [
                ['step' => '1', 'who' => 'HR Officer / Manager', 'action' => 'Open ' . $screenPath, 'result' => 'Today\'s KPIs, pending items, open periods summary'],
                ['step' => '2', 'who' => 'HR Officer', 'action' => 'Identify red flags (high late count, open approvals)', 'result' => 'Same-day action list created'],
                ['step' => '3', 'who' => 'HR Officer', 'action' => 'Use drill-down links to open the relevant screen', 'result' => 'Fix exceptions — no data entry on dashboard'],
            ];
        }

        if (str_contains($haystack, 'sync')) {
            return $lang === 'bn' ? [
                ['step' => '১', 'who' => 'IT / HR Officer', 'action' => $screenPath . ' → device online verify', 'result' => 'Offline device skip'],
                ['step' => '২', 'who' => 'IT / HR Officer', 'action' => 'Sync Now / Sync All run', 'result' => 'Raw punch import queue'],
                ['step' => '৩', 'who' => 'HR Officer', 'action' => 'Punch Logs / Daily Summary check', 'result' => 'Processed attendance update'],
            ] : [
                ['step' => '1', 'who' => 'IT / HR Officer', 'action' => $screenPath . ' → verify devices online', 'result' => 'Offline devices skipped'],
                ['step' => '2', 'who' => 'IT / HR Officer', 'action' => 'Run Sync Now / Sync All', 'result' => 'Raw punches queued for import'],
                ['step' => '3', 'who' => 'HR Officer', 'action' => 'Check Punch Logs / Daily Summary', 'result' => 'Processed attendance updated'],
            ];
        }

        if ($key === 'punches' || str_contains($haystack, 'punch log')) {
            return $lang === 'bn' ? [
                ['step' => '১', 'who' => 'HR Officer', 'action' => $screenPath . ' → আজকের date filter', 'result' => 'Raw IN/OUT punch list'],
                ['step' => '২', 'who' => 'HR Officer', 'action' => 'Unmapped / duplicate punch identify', 'result' => 'Employee mapping বা device issue flag'],
                ['step' => '৩', 'who' => 'HR Officer', 'action' => 'Fix mapping অথবা Manual Punch screen-এ escalate', 'result' => 'Daily Summary process unblock'],
            ] : [
                ['step' => '1', 'who' => 'HR Officer', 'action' => $screenPath . ' → filter by today\'s date', 'result' => 'Raw IN/OUT punch list'],
                ['step' => '2', 'who' => 'HR Officer', 'action' => 'Identify unmapped or duplicate punches', 'result' => 'Employee mapping or device issue flagged'],
                ['step' => '3', 'who' => 'HR Officer', 'action' => 'Fix mapping or escalate via Manual Punch screen', 'result' => 'Daily Summary processing unblocked'],
            ];
        }

        if (str_contains($key, 'manual') || str_contains($haystack, 'manual punch')) {
            return $lang === 'bn' ? [
                ['step' => '১', 'who' => 'HR Officer', 'action' => $screenPath . ' → New entry', 'result' => 'Form open'],
                ['step' => '২', 'who' => 'HR Officer', 'action' => 'Employee, date, IN/OUT, reason fill → Submit', 'result' => 'Pending approval; Manager notified'],
                ['step' => '৩', 'who' => 'HR Manager', 'action' => 'Approve বা Reject (comment সহ)', 'result' => 'Approve → daily log update; Reject → officer re-submit'],
            ] : [
                ['step' => '1', 'who' => 'HR Officer', 'action' => $screenPath . ' → New entry', 'result' => 'Form opens'],
                ['step' => '2', 'who' => 'HR Officer', 'action' => 'Fill employee, date, IN/OUT, reason → Submit', 'result' => 'Pending approval; Manager notified'],
                ['step' => '3', 'who' => 'HR Manager', 'action' => 'Approve or Reject with comment', 'result' => 'Approved → daily log updated; Rejected → officer re-submits'],
            ];
        }

        if (str_contains($haystack, 'approve') || str_contains($haystack, 'acceptance')) {
            return $lang === 'bn' ? [
                ['step' => '১', 'who' => 'Employee', 'action' => 'Portal থেকে application submit', 'result' => 'Status Pending'],
                ['step' => '২', 'who' => 'HR Officer', 'action' => $screenPath . ' → queue review, policy limit check', 'result' => 'Valid/invalid flag'],
                ['step' => '৩', 'who' => 'HR Manager', 'action' => 'Approve/Reject written reason সহ', 'result' => 'Attendance recalc; employee notified'],
            ] : [
                ['step' => '1', 'who' => 'Employee', 'action' => 'Submit application via portal', 'result' => 'Status Pending'],
                ['step' => '2', 'who' => 'HR Officer', 'action' => $screenPath . ' → review queue, check policy limits', 'result' => 'Valid/invalid flagged'],
                ['step' => '3', 'who' => 'HR Manager', 'action' => 'Approve/Reject with written reason', 'result' => 'Attendance recalculated; employee notified'],
            ];
        }

        if (str_contains($haystack, 'period') || str_contains($haystack, 'close') || str_contains($haystack, 'process')) {
            return $lang === 'bn' ? [
                ['step' => '১', 'who' => 'HR Officer', 'action' => 'Daily exception সব close confirm', 'result' => 'Process unblock'],
                ['step' => '২', 'who' => 'HR Officer', 'action' => $screenPath . ' → period select → Process run', 'result' => 'Monthly totals calculate'],
                ['step' => '৩', 'who' => 'HR Manager', 'action' => 'Summary review → Freeze/Close', 'result' => 'Period lock; downstream module use'],
            ] : [
                ['step' => '1', 'who' => 'HR Officer', 'action' => 'Confirm all daily exceptions closed', 'result' => 'Process unblocked'],
                ['step' => '2', 'who' => 'HR Officer', 'action' => $screenPath . ' → select period → Run process', 'result' => 'Monthly totals calculated'],
                ['step' => '3', 'who' => 'HR Manager', 'action' => 'Review summary → Freeze/Close', 'result' => 'Period locked; downstream modules can use data'],
            ];
        }

        if (str_contains($haystack, 'report') || str_contains($haystack, 'register')) {
            return $lang === 'bn' ? [
                ['step' => '১', 'who' => 'HR Officer', 'action' => $screenPath . ' → factory, period filter set', 'result' => 'Filtered dataset'],
                ['step' => '২', 'who' => 'HR Officer', 'action' => 'Report generate / preview', 'result' => 'On-screen summary'],
                ['step' => '৩', 'who' => 'HR Manager', 'action' => 'Review → Export PDF/Excel archive', 'result' => 'Audit record saved'],
            ] : [
                ['step' => '1', 'who' => 'HR Officer', 'action' => $screenPath . ' → set factory and period filters', 'result' => 'Filtered dataset'],
                ['step' => '2', 'who' => 'HR Officer', 'action' => 'Generate / preview report', 'result' => 'On-screen summary'],
                ['step' => '3', 'who' => 'HR Manager', 'action' => 'Review → Export PDF/Excel for archive', 'result' => 'Audit record saved'],
            ];
        }

        if (str_contains($haystack, 'policy') || str_contains($haystack, 'rule')) {
            return $lang === 'bn' ? [
                ['step' => '১', 'who' => 'HR Manager', 'action' => $screenPath . ' → current policy review', 'result' => 'Baseline documented'],
                ['step' => '২', 'who' => 'HR Manager', 'action' => 'Grace minutes, deduction rule, limit update → Save', 'result' => 'New policy saved (usually next period effective)'],
                ['step' => '৩', 'who' => 'HR Officer', 'action' => 'Staff-কে change communicate; Daily Summary monitor', 'result' => 'Consistent application from effective date'],
            ] : [
                ['step' => '1', 'who' => 'HR Manager', 'action' => $screenPath . ' → review current policy', 'result' => 'Baseline documented'],
                ['step' => '2', 'who' => 'HR Manager', 'action' => 'Update grace minutes, deduction rules, limits → Save', 'result' => 'New policy saved (usually effective next period)'],
                ['step' => '3', 'who' => 'HR Officer', 'action' => 'Communicate change to staff; monitor Daily Summary', 'result' => 'Consistent application from effective date'],
            ];
        }

        if (str_contains($haystack, 'bulk') || str_contains($haystack, 'upload')) {
            return $lang === 'bn' ? [
                ['step' => '১', 'who' => 'HR Officer', 'action' => 'Template download → ৫ row test upload', 'result' => 'Validation error preview'],
                ['step' => '২', 'who' => 'HR Officer', 'action' => 'Full file upload → preview grid check', 'result' => 'Draft import ready'],
                ['step' => '৩', 'who' => 'HR Manager', 'action' => 'Dual verify → Confirm save', 'result' => 'Records created/updated in bulk'],
            ] : [
                ['step' => '1', 'who' => 'HR Officer', 'action' => 'Download template → test upload with 5 rows', 'result' => 'Validation errors previewed'],
                ['step' => '2', 'who' => 'HR Officer', 'action' => 'Upload full file → review preview grid', 'result' => 'Draft import ready'],
                ['step' => '3', 'who' => 'HR Manager', 'action' => 'Dual verify → Confirm save', 'result' => 'Records created/updated in bulk'],
            ];
        }

        if ($manage) {
            return $lang === 'bn' ? [
                ['step' => '১', 'who' => $officer, 'action' => $screenPath . ' open → New/Edit', 'result' => 'Form with factory-scoped data'],
                ['step' => '২', 'who' => $officer, 'action' => e($description) . ' — required field fill → Save', 'result' => 'Record saved; audit log entry'],
                ['step' => '৩', 'who' => $manager, 'action' => 'Approval queue review (যদি apply)', 'result' => 'Approved → active; Rejected → correction'],
            ] : [
                ['step' => '1', 'who' => $officer, 'action' => 'Open ' . $screenPath . ' → New/Edit', 'result' => 'Form with factory-scoped data'],
                ['step' => '2', 'who' => $officer, 'action' => e($description) . ' — fill required fields → Save', 'result' => 'Record saved; audit log entry'],
                ['step' => '3', 'who' => $manager, 'action' => 'Review approval queue (if applicable)', 'result' => 'Approved → active; Rejected → correction required'],
            ];
        }

        return $lang === 'bn' ? [
            ['step' => '১', 'who' => 'Authorized user', 'action' => $screenPath . ' open', 'result' => 'Screen data load (view-only বা filter)'],
            ['step' => '২', 'who' => 'Authorized user', 'action' => 'Filter/search দিয়ে প্রয়োজনীয় record find', 'result' => 'Target record displayed'],
            ['step' => '৩', 'who' => 'Authorized user', 'action' => 'Export/print (যদি permission থাকে)', 'result' => 'Offline copy for review'],
        ] : [
            ['step' => '1', 'who' => 'Authorized user', 'action' => 'Open ' . $screenPath, 'result' => 'Screen data loaded (view or filter)'],
            ['step' => '2', 'who' => 'Authorized user', 'action' => 'Use filter/search to find required records', 'result' => 'Target records displayed'],
            ['step' => '3', 'who' => 'Authorized user', 'action' => 'Export/print if permitted', 'result' => 'Offline copy for review'],
        ];
    }

    /**
     * @param list<array{step: string, who: string, action: string, result: string}> $steps
     */
    private function workflowTable(array $steps, string $lang): string
    {
        $headers = $lang === 'bn'
            ? ['Step', 'কে', 'কী করবেন', 'ফলাফল']
            : ['Step', 'Who', 'Action', 'Result'];

        $html = '<h3>Step-by-step workflow</h3><table><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . e($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($steps as $row) {
            $html .= '<tr><td>' . e($row['step']) . '</td><td>' . e($row['who']) . '</td><td>' . $row['action'] . '</td><td>' . e($row['result']) . '</td></tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /** @param array<string, mixed> $profile */
    private function workflowRulesBlock(
        string $key,
        string $label,
        ?string $permission,
        ?string $manage,
        array $profile,
        string $lang,
    ): string {
        $rules = [];
        $suffix = $lang === 'bn' ? '_bn' : '_en';
        $heading = $lang === 'bn' ? 'গুরুত্বপূর্ণ নিয়ম ও exception' : 'Important rules & exceptions';

        if ($manage) {
            $rules[] = $lang === 'bn'
                ? 'Entry/edit-এর জন্য <code>' . e($manage) . '</code> permission mandatory।'
                : 'Permission <code>' . e($manage) . '</code> required for entry/edit.';
        } elseif ($permission) {
            $rules[] = $lang === 'bn'
                ? 'View/access-এর জন্য <code>' . e($permission) . '</code> permission যথেষ্ট।'
                : 'Permission <code>' . e($permission) . '</code> sufficient for view/access.';
        }

        foreach ($this->hintKeysForSubmodule($key, $label) as $hintKey) {
            $hint = config('kb-seed-profiles.screen_hints.' . $hintKey);
            if ($hint) {
                $rules[] = $hint[$lang] ?? $hint['en'];
            }
        }

        $rules[] = $lang === 'bn'
            ? 'সব transaction user-এর assigned factory/unit scope-এ restricted।'
            : 'All transactions restricted to the user\'s assigned factory/unit scope.';

        $rules[] = $lang === 'bn'
            ? 'Error বা duplicate same day HR/Admin-কে report করুন — period close-এর আগে resolve।'
            : 'Report errors or duplicates to HR/Admin same day — resolve before period close.';

        if (! empty($profile['usage_rules' . $suffix])) {
            $rules[] = $lang === 'bn'
                ? 'Module-level policy: module overview section ৩ দেখুন।'
                : 'Module-level policy: see module overview section 3.';
        }

        return '<h3>' . e($heading) . '</h3><ul>'
            . implode('', array_map(fn (string $r) => '<li>' . $r . '</li>', $rules))
            . '</ul>';
    }

    private function menuPathForModule(KbModule $module, string $lang): string
    {
        $code = $module->code;

        return match (true) {
            str_starts_with($code, 'hrm-') => $lang === 'bn'
                ? 'Menu: HRM → ' . e(str_replace('HRM — ', '', $module->label_en))
                : 'Menu: HRM → ' . e(str_replace('HRM — ', '', $module->label_en)),
            $code === 'tms' => 'Menu: Transport (TMS)',
            $code === 'commercial' => 'Menu: Commercial',
            $code === 'masters' => 'Menu: Masters',
            $code === 'admin-system' => 'Menu: Administration',
            default => 'Menu: ' . e($module->label_en),
        };
    }

    private function hintKeysForSubmodule(string $key, string $label): array
    {
        $keys = [];
        $haystack = strtolower($key . ' ' . $label);

        foreach (['dashboard', 'sync', 'reports', 'bulk', 'upload', 'close', 'policy'] as $hint) {
            if (str_contains($haystack, $hint)) {
                $keys[] = $hint;
            }
        }

        if (str_contains($haystack, 'approve') || str_contains($haystack, 'acceptance')) {
            $keys[] = 'approve';
        }

        return array_values(array_unique($keys));
    }

    private function fallbackPurposeEn(KbModule $module): string
    {
        return '<p>Manage <strong>' . e($module->label_en) . '</strong> operations within the Norbangroup admin portal.</p>';
    }

    private function fallbackPurposeBn(KbModule $module): string
    {
        return '<p>Norbangroup admin portal-এ <strong>' . e($module->label_bn) . '</strong> সংক্রান্ত কাজ পরিচালনা।</p>';
    }

    private function fallbackAudienceEn(KbModule $module): string
    {
        return '<table><thead><tr><th>Role</th><th>Department</th><th>Responsibility</th></tr></thead><tbody>'
            . '<tr><td>Module User</td><td>Assigned department</td><td>Daily operations</td></tr>'
            . '<tr><td>Manager</td><td>Management</td><td>Approve and review</td></tr>'
            . '</tbody></table>';
    }

    private function fallbackAudienceBn(KbModule $module): string
    {
        return '<table><thead><tr><th>Role</th><th>Department</th><th>দায়িত্ব</th></tr></thead><tbody>'
            . '<tr><td>Module User</td><td>Assigned department</td><td>Daily operation</td></tr>'
            . '<tr><td>Manager</td><td>Management</td><td>Approve ও review</td></tr>'
            . '</tbody></table>';
    }

    private function fallbackRulesEn(KbModule $module): string
    {
        return '<ul><li>Use only assigned portal permissions.</li><li>Do not share login credentials.</li><li>Escalate errors to system administrator.</li></ul>';
    }

    private function fallbackRulesBn(KbModule $module): string
    {
        return '<ul><li>শুধু assigned portal permission ব্যবহার করুন।</li><li>Login credential share নয়।</li><li>Error system administrator-কে escalate করুন।</li></ul>';
    }
}
