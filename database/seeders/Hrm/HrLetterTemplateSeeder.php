<?php

namespace Database\Seeders\Hrm;

use App\Models\Hrm\HrLetterTemplate;
use Illuminate\Database\Seeder;

class HrLetterTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $template) {
            HrLetterTemplate::updateOrCreate(
                ['code' => $template['code']],
                [
                    'factory_id'  => null,
                    'name'        => $template['name'],
                    'letter_type' => $template['letter_type'],
                    'body'        => $template['body'],
                    'is_active'   => true,
                ]
            );
        }

        $this->command?->info('Synced ' . count($this->templates()) . ' HR letter templates.');
    }

    /** @return list<array{code: string, name: string, letter_type: string, body: string}> */
    private function templates(): array
    {
        return [
            [
                'code'        => 'offer_of_employment',
                'name'        => 'Offer of Employment',
                'letter_type' => 'offer',
                'body'        => <<<'TXT'
Offer of Employment

Private and Confidential

Date: {{date}}

Dear {{employee_name}},

Re: Offer of Employment for the post of {{designation}}.

We are pleased to offer you the position of {{designation}} ({{department}} department) with us here at {{factory_name}} where we hope you will enjoy your role and make a significant contribution to the success of the business.

Commencement Date
Your employment will commence on or before {{joining_date}}

Location
You will be based at {{office_address}}

Position
{{designation}}
The role and responsibilities of this position will be described separately in your Appointment Letter and or in your Job Description.

Compensation
You will get consolidated salary as discussed inclusive of all allowances. In addition mobile bill, 2 festival bonuses in a year and any other benefit if any will be as per company policy.

Bring copy of your all academic credentials, NID, 2 copy recent passport size photograph and clearance from last organisation for whom you worked for.

NOTE: You will report to {{reporting_manager_name}}, {{reporting_manager_designation}}, Cell No- {{reporting_manager_phone}}

Waiting for you here at Norban.

Sincerely,
Human Resources Department
{{factory_name}}
TXT,
            ],
            [
                'code'        => 'appointment',
                'name'        => 'Appointment Letter',
                'letter_type' => 'appointment',
                'body'        => "Date: {{date}}\n\nTo,\n{{employee_name}}\nEmployee ID: {{employee_code}}\n\nSubject: Appointment Letter\n\nDear {{employee_name}},\n\nWe are pleased to appoint you as {{designation}} in the {{department}} department at {{factory_name}}, effective from {{joining_date}}.\n\nWe welcome you to Norban Group and wish you a successful career with us.\n\nSincerely,\nHuman Resources\n{{factory_name}}",
            ],
            [
                'code'        => 'confirmation',
                'name'        => 'Confirmation Letter',
                'letter_type' => 'confirmation',
                'body'        => "Date: {{date}}\n\nTo,\n{{employee_name}} ({{employee_code}})\n\nSubject: Confirmation of Employment\n\nWe are pleased to confirm your employment with {{factory_name}} as {{designation}} with effect from {{confirmation_date}}.\n\nSincerely,\nHuman Resources",
            ],
            [
                'code'        => 'warning',
                'name'        => 'Warning Letter',
                'letter_type' => 'warning',
                'body'        => "Date: {{date}}\n\nTo,\n{{employee_name}} ({{employee_code}})\nDepartment: {{department}}\n\nSubject: Warning Letter\n\nThis is to inform you that following an review of your conduct/performance, management has decided to issue this written warning. You are advised to improve immediately and comply with company rules.\n\nSincerely,\nHuman Resources\n{{factory_name}}",
            ],
            [
                'code'        => 'experience',
                'name'        => 'Experience Certificate',
                'letter_type' => 'experience',
                'body'        => "Date: {{date}}\n\nTO WHOM IT MAY CONCERN\n\nThis is to certify that {{employee_name}} (Employee ID: {{employee_code}}) was employed with {{factory_name}} as {{designation}} in the {{department}} department from {{joining_date}} to {{last_working_day}}.\n\nDuring employment, {{employee_name}} performed duties satisfactorily. We wish {{employee_name}} success in future endeavours.\n\nSincerely,\nHuman Resources\n{{factory_name}}",
            ],
            [
                'code'        => 'relieving',
                'name'        => 'Relieving Letter',
                'letter_type' => 'relieving',
                'body'        => "Date: {{date}}\n\nTo,\n{{employee_name}} ({{employee_code}})\n\nSubject: Relieving Letter\n\nThis is to confirm that you have been relieved from your duties at {{factory_name}} with effect from {{last_working_day}}. We acknowledge receipt of company assets and clearance as applicable.\n\nWe thank you for your service and wish you the best.\n\nSincerely,\nHuman Resources\n{{factory_name}}",
            ],
            [
                'code'        => 'termination',
                'name'        => 'Termination Letter',
                'letter_type' => 'termination',
                'body'        => "Date: {{date}}\n\nTo,\n{{employee_name}} ({{employee_code}})\n\nSubject: Termination of Employment\n\nWe regret to inform you that your employment with {{factory_name}} is terminated with effect from {{last_working_day}}.\n\nSincerely,\nHuman Resources\n{{factory_name}}",
            ],
        ];
    }
}
