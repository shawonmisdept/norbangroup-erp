<?php

namespace App\Support;

use App\Models\KbModule;

class KbArticleSeedBuilder
{
    /** @var array<string, string> */
    private const WORKFLOW_FILES = [
        'commercial'      => '01-commercial-requirements.md',
        'masters'         => '02-masters-erp.md',
        'hrm-employee'    => '05-hrm-employee.md',
        'hrm-recruitment' => '06-hrm-recruitment.md',
        'hrm-attendance'  => '07-hrm-attendance.md',
        'hrm-leave'       => '08-hrm-leave.md',
        'hrm-performance' => '09-hrm-performance.md',
        'hrm-salary'      => '10-hrm-salary.md',
        'hrm-compliance'  => '11-hrm-compliance.md',
        'hrm-finance'     => '12-hrm-finance.md',
        'hrm-rmg'         => '13-hrm-rmg.md',
        'hrm-masters'     => '04-hrm-masters.md',
        'tms'             => '14-tms.md',
        'admin-system'    => '15-admin-system.md',
    ];

    public function overviewArticle(KbModule $module): array
    {
        $markdown = $this->workflowMarkdown($module->code);
        $submodules = $module->submoduleDefinitions();

        return [
            'submodule_key' => null,
            'title_en'      => $module->label_en . ' — Overview',
            'title_bn'      => $module->label_bn . ' — সারাংশ',
            'summary_en'    => 'End-to-end workflow for ' . $module->label_en . '.',
            'summary_bn'    => $module->label_bn . ' মডিউলের সম্পূর্ণ workflow গাইড।',
            'body_en'       => $this->overviewBodyEn($module, $submodules, $markdown),
            'body_bn'       => $markdown ? KbMarkdown::toHtml($markdown) : $this->overviewBodyBn($module, $submodules),
            'is_published'  => true,
        ];
    }

    /** @param array<string, array<string, mixed>> $subConfig */
    public function submoduleArticle(KbModule $module, string $key, array $subConfig): array
    {
        $label = $subConfig['label'] ?? $key;
        $description = $subConfig['description'] ?? '';
        $permission = $subConfig['permission'] ?? null;
        $manage = $subConfig['manage'] ?? null;
        $route = $subConfig['route'] ?? null;

        return [
            'submodule_key' => $key,
            'title_en'      => $label . ' — ' . $module->label_en,
            'title_bn'      => $label . ' — ' . $module->label_bn,
            'summary_en'    => $description,
            'summary_bn'    => $description,
            'body_en'       => $this->submoduleBodyEn($module, $label, $description, $permission, $manage, $route),
            'body_bn'       => $this->submoduleBodyBn($module, $label, $description, $permission, $manage, $route),
            'is_published'  => true,
        ];
    }

    private function workflowMarkdown(string $code): ?string
    {
        $file = self::WORKFLOW_FILES[$code] ?? null;

        if (! $file) {
            return null;
        }

        $path = base_path('docs/workflows/' . $file);

        return is_file($path) ? (string) file_get_contents($path) : null;
    }

    /** @param array<string, array<string, mixed>> $submodules */
    private function overviewBodyEn(KbModule $module, array $submodules, ?string $markdown): string
    {
        $parts = [
            '<h2>' . e($module->label_en) . '</h2>',
            '<p>This module is part of the Norbangroup admin portal. Use the screens below in order for day-to-day operations.</p>',
        ];

        if ($module->view_permission) {
            $parts[] = '<p><strong>Access:</strong> requires permission <code>' . e($module->view_permission) . '</code> (or broader module access).</p>';
        }

        if ($submodules !== []) {
            $parts[] = '<h3>Sub-modules</h3><table><tbody><tr><td><strong>Screen</strong></td><td><strong>Description</strong></td></tr>';
            foreach ($submodules as $sub) {
                if (($sub['status'] ?? 'active') === 'planned') {
                    continue;
                }
                $parts[] = '<tr><td>' . e($sub['label'] ?? '') . '</td><td>' . e($sub['description'] ?? '') . '</td></tr>';
            }
            $parts[] = '</tbody></table>';
        }

        $parts[] = '<h3>Typical flow</h3><ol>';
        $parts[] = '<li>Open the module hub from the sidebar.</li>';
        $parts[] = '<li>Complete setup / master data first if this is a new factory.</li>';
        $parts[] = '<li>Run daily transactions, then review reports and approvals.</li>';
        $parts[] = '<li>Close the period (month/week) before the next module (e.g. payroll) consumes the data.</li>';
        $parts[] = '</ol>';

        if ($markdown) {
            $parts[] = '<p><em>See the Bengali tab for the detailed step-by-step guide imported from operations documentation.</em></p>';
        }

        return implode("\n", $parts);
    }

    /** @param array<string, array<string, mixed>> $submodules */
    private function overviewBodyBn(KbModule $module, array $submodules): string
    {
        $parts = [
            '<h2>' . e($module->label_bn) . '</h2>',
            '<p>এটি Norbangroup admin portal-এর একটি মডিউল। দৈনন্দিন কাজের জন্য নিচের স্ক্রিনগুলো অনুসরণ করুন।</p>',
        ];

        if ($submodules !== []) {
            $parts[] = '<h3>সাব-মডিউল</h3><ul>';
            foreach ($submodules as $sub) {
                if (($sub['status'] ?? 'active') === 'planned') {
                    continue;
                }
                $parts[] = '<li><strong>' . e($sub['label'] ?? '') . ':</strong> ' . e($sub['description'] ?? '') . '</li>';
            }
            $parts[] = '</ul>';
        }

        return implode("\n", $parts);
    }

    private function submoduleBodyEn(
        KbModule $module,
        string $label,
        string $description,
        ?string $permission,
        ?string $manage,
        ?string $route,
    ): string {
        $parts = [
            '<h2>' . e($label) . '</h2>',
            '<p>' . e($description) . '</p>',
            '<h3>When to use</h3>',
            '<p>Open this screen from <strong>' . e($module->label_en) . '</strong> when you need to work on ' . e(strtolower($label)) . ' records for your factory.</p>',
            '<h3>Steps</h3>',
            '<ol>',
            '<li>Go to the module hub and open <strong>' . e($label) . '</strong>.</li>',
            '<li>Filter by factory / date / employee as needed.</li>',
            '<li>Create, edit, approve, or export data according to company policy.</li>',
            '<li>Confirm notifications were sent to the next approver if the workflow requires it.</li>',
            '</ol>',
        ];

        if ($permission || $manage) {
            $parts[] = '<h3>Permissions</h3><ul>';
            if ($permission) {
                $parts[] = '<li>View: <code>' . e($permission) . '</code></li>';
            }
            if ($manage) {
                $parts[] = '<li>Manage: <code>' . e($manage) . '</code></li>';
            }
            $parts[] = '</ul>';
        }

        if ($route) {
            $parts[] = '<p><strong>Route name:</strong> <code>' . e($route) . '</code></p>';
        }

        return implode("\n", $parts);
    }

    private function submoduleBodyBn(
        KbModule $module,
        string $label,
        string $description,
        ?string $permission,
        ?string $manage,
        ?string $route,
    ): string {
        $parts = [
            '<h2>' . e($label) . '</h2>',
            '<p>' . e($description) . '</p>',
            '<h3>কখন ব্যবহার করবেন</h3>',
            '<p><strong>' . e($module->label_bn) . '</strong> মডিউল থেকে <strong>' . e($label) . '</strong> স্ক্রিনটি তখনই খুলুন যখন এই বিষয়ে তথ্য দেখতে, এন্ট্রি করতে বা অনুমোদন দিতে হবে।</p>',
            '<h3>ধাপ</h3>',
            '<ol>',
            '<li>Sidebar থেকে মডিউল hub-এ যান এবং <strong>' . e($label) . '</strong> খুলুন।</li>',
            '<li>Factory, তারিখ বা কর্মী দিয়ে filter করুন।</li>',
            '<li>কোম্পানির নীতি অনুযায়ী তথ্য entry, edit, approve বা export করুন।</li>',
            '<li>Workflow-এ approver থাকলে notification গেছে কিনা যাচাই করুন।</li>',
            '</ol>',
        ];

        if ($permission || $manage) {
            $parts[] = '<h3>Permission</h3><ul>';
            if ($permission) {
                $parts[] = '<li>View: <code>' . e($permission) . '</code></li>';
            }
            if ($manage) {
                $parts[] = '<li>Manage: <code>' . e($manage) . '</code></li>';
            }
            $parts[] = '</ul>';
        }

        if ($route) {
            $parts[] = '<p><strong>Route:</strong> <code>' . e($route) . '</code></p>';
        }

        return implode("\n", $parts);
    }
}
