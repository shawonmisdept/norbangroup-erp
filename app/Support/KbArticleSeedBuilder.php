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
            usageRulesEn: $profile['usage_rules_en'] ?? $this->fallbackRulesEn($module),
            usageRulesBn: $profile['usage_rules_bn'] ?? $this->fallbackRulesBn($module),
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

        return $this->articlePayload(
            submoduleKey: $key,
            titleEn: $label . ' — ' . $module->label_en,
            titleBn: $label . ' — ' . $module->label_bn,
            summaryEn: $description,
            summaryBn: $description,
            purposeEn: $this->submodulePurposeEn($module, $key, $label, $description, $subConfig),
            purposeBn: $this->submodulePurposeBn($module, $key, $label, $description, $subConfig),
            audienceEn: $this->submoduleAudienceEn($module, $key, $label, $permission, $manage, $profile),
            audienceBn: $this->submoduleAudienceBn($module, $key, $label, $permission, $manage, $profile),
            usageRulesEn: $this->submoduleRulesEn($key, $label, $permission, $manage, $profile),
            usageRulesBn: $this->submoduleRulesBn($key, $label, $permission, $manage, $profile),
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
    private function submoduleRulesEn(string $key, string $label, ?string $permission, ?string $manage, array $profile): string
    {
        return $this->submoduleRules($key, $label, $permission, $manage, $profile, 'en');
    }

    /** @param array<string, mixed> $profile */
    private function submoduleRulesBn(string $key, string $label, ?string $permission, ?string $manage, array $profile): string
    {
        return $this->submoduleRules($key, $label, $permission, $manage, $profile, 'bn');
    }

    /** @param array<string, mixed> $profile */
    private function submoduleRules(
        string $key,
        string $label,
        ?string $permission,
        ?string $manage,
        array $profile,
        string $lang,
    ): string {
        $rules = [];
        $suffix = $lang === 'bn' ? '_bn' : '_en';

        if ($manage) {
            $rules[] = $lang === 'bn'
                ? 'এই স্ক্রিনে entry/edit-এর জন্য <code>' . e($manage) . '</code> permission mandatory।'
                : 'Permission <code>' . e($manage) . '</code> required for entry/edit on this screen.';
        } elseif ($permission) {
            $rules[] = $lang === 'bn'
                ? 'শুধু view/access-এর জন্য <code>' . e($permission) . '</code> permission যথেষ্ট।'
                : 'Permission <code>' . e($permission) . '</code> sufficient for view/access.';
        }

        foreach ($this->hintKeysForSubmodule($key, $label) as $hintKey) {
            $hint = config('kb-seed-profiles.screen_hints.' . $hintKey);
            if ($hint) {
                $rules[] = $hint[$lang] ?? $hint['en'];
            }
        }

        if (! empty($profile['usage_rules' . $suffix])) {
            $rules[] = $lang === 'bn'
                ? 'মডিউল-প্রয় general rule: module overview-এর section ৩ দেখুন।'
                : 'Module-level rules apply — see section 3 in module overview.';
        }

        $rules[] = $lang === 'bn'
            ? 'সব transaction factory/unit scope অনুযায়ী restricted (user factory_id)।'
            : 'All transactions are restricted to the user\'s assigned factory/unit scope.';

        $rules[] = $lang === 'bn'
            ? 'সন্দেহজনক বা duplicate entry HR/Admin-কে same day report করতে হবে।'
            : 'Report suspicious or duplicate entries to HR/Admin on the same day.';

        return '<ul>' . implode('', array_map(fn (string $r) => '<li>' . $r . '</li>', $rules)) . '</ul>';
    }

    /** @return list<string> */
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
