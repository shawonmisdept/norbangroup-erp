<?php

if (! function_exists('kb_workflow_pair')) {
    /**
     * @param list<array{step: string, who: string, action: string, result: string}> $stepsEn
     * @param list<string>                                                          $rulesEn
     * @param list<array{step: string, who: string, action: string, result: string}> $stepsBn
     * @param list<string>                                                          $rulesBn
     *
     * @return array{workflow_en: string, workflow_bn: string}
     */
    function kb_workflow_pair(array $stepsEn, array $rulesEn, array $stepsBn, array $rulesBn): array
    {
        return [
            'workflow_en' => kb_workflow_html($stepsEn, $rulesEn, 'en'),
            'workflow_bn' => kb_workflow_html($stepsBn, $rulesBn, 'bn'),
        ];
    }
}

if (! function_exists('kb_workflow_html')) {
    /**
     * @param list<array{step: string, who: string, action: string, result: string}> $steps
     * @param list<string>                                                          $rules
     */
    function kb_workflow_html(array $steps, array $rules, string $lang): string
    {
        $headers = $lang === 'bn'
            ? ['Step', 'কে', 'কাজ', 'ফলাফল']
            : ['Step', 'Who', 'Action', 'Result'];

        $html = '<h3>Step-by-step workflow</h3><table><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . $header . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($steps as $row) {
            $html .= '<tr><td>' . $row['step'] . '</td><td>' . $row['who'] . '</td><td>' . $row['action'] . '</td><td>' . $row['result'] . '</td></tr>';
        }

        $html .= '</tbody></table>';

        if ($rules !== []) {
            $heading = $lang === 'bn' ? 'গুরুত্বপূর্ণ নিয়ম' : 'Important rules';
            $html .= '<h3>' . $heading . '</h3><ul>';
            foreach ($rules as $rule) {
                $html .= '<li>' . $rule . '</li>';
            }
            $html .= '</ul>';
        }

        return $html;
    }
}

return [];
