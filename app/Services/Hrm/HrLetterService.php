<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\HrLetterTemplate;
use App\Models\Hrm\IssuedLetter;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HrLetterService
{
    public function renderTemplate(HrLetterTemplate $template, Employee $employee, array $extra = []): string
    {
        $employee->loadMissing(['factory', 'department', 'designation']);

        $replacements = array_merge([
            '{{date}}'              => now()->format('d M Y'),
            '{{employee_name}}'     => $employee->name,
            '{{employee_code}}'     => $employee->employee_code,
            '{{factory_name}}'      => $employee->factory?->name ?? '',
            '{{department}}'        => $employee->department?->name ?? '',
            '{{designation}}'       => $employee->designation?->name ?? '',
            '{{joining_date}}'      => $employee->joining_date?->format('d M Y') ?? '',
            '{{confirmation_date}}' => $employee->confirmation_date?->format('d M Y') ?? '',
            '{{last_working_day}}'  => $employee->last_working_day?->format('d M Y') ?? '',
            '{{phone}}'             => $employee->phone ?? '',
        ], $extra);

        return str_replace(array_keys($replacements), array_values($replacements), $template->body);
    }

    /** @return array{date: ?string, to: array<int, string>, subject: ?string, body: array<int, string>, closing: array<int, string>, factory: ?string} */
    public function parseLetterSections(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($content)) ?: [];
        $sections = [
            'date'     => null,
            'to'       => [],
            'subject'  => null,
            'body'     => [],
            'closing'  => [],
            'factory'  => null,
        ];

        $phase = 'header';
        $bodyBuffer = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($phase === 'header' && str_starts_with($trimmed, 'Date:')) {
                $sections['date'] = trim(substr($trimmed, 5));
                continue;
            }

            if ($phase === 'header' && $trimmed === 'To,') {
                $phase = 'to';
                continue;
            }

            if ($phase === 'to') {
                if ($trimmed === '') {
                    $phase = 'meta';
                    continue;
                }
                $sections['to'][] = $trimmed;
                continue;
            }

            if (($phase === 'header' || $phase === 'meta') && str_starts_with($trimmed, 'Subject:')) {
                $sections['subject'] = trim(substr($trimmed, 8));
                $phase = 'body';
                continue;
            }

            if ($phase === 'body' && in_array($trimmed, ['Sincerely,', 'Yours sincerely,', 'Yours faithfully,'], true)) {
                if ($bodyBuffer !== []) {
                    $sections['body'][] = implode("\n", $bodyBuffer);
                    $bodyBuffer = [];
                }
                $sections['closing'][] = $trimmed;
                $phase = 'closing';
                continue;
            }

            if ($phase === 'closing') {
                if ($trimmed !== '') {
                    $sections['closing'][] = $trimmed;
                }
                continue;
            }

            if ($trimmed === '') {
                if ($bodyBuffer !== []) {
                    $sections['body'][] = implode("\n", $bodyBuffer);
                    $bodyBuffer = [];
                }
                continue;
            }

            if ($phase === 'body' || $phase === 'meta') {
                $phase = 'body';
                $bodyBuffer[] = $trimmed;
            }
        }

        if ($bodyBuffer !== []) {
            $sections['body'][] = implode("\n", $bodyBuffer);
        }

        if ($sections['closing'] !== []) {
            $sections['factory'] = end($sections['closing']) ?: null;
        }

        if ($sections['body'] === [] && $sections['to'] === [] && $sections['subject'] === null) {
            $sections['body'] = [trim($content)];
        }

        return $sections;
    }

    public function issue(
        Employee $employee,
        HrLetterTemplate $template,
        User $issuer,
        ?string $notes = null,
        array $extraPlaceholders = [],
    ): IssuedLetter {
        $content = $this->renderTemplate($template, $employee, $extraPlaceholders);

        do {
            $referenceNo = 'LTR-' . strtoupper(Str::random(8));
        } while (IssuedLetter::where('reference_no', $referenceNo)->exists());

        return IssuedLetter::create([
            'factory_id'   => $employee->factory_id,
            'employee_id'  => $employee->id,
            'template_id'  => $template->id,
            'letter_type'  => $template->letter_type,
            'reference_no' => $referenceNo,
            'content'      => $content,
            'notes'        => $notes,
            'issued_at'    => now(),
            'issued_by'    => $issuer->id,
        ]);
    }

    public function void(IssuedLetter $letter, User $issuer, ?string $reason = null): IssuedLetter
    {
        if ($letter->isVoided()) {
            throw ValidationException::withMessages(['letter' => 'This letter is already voided.']);
        }

        $letter->update([
            'voided_at'   => now(),
            'voided_by'   => $issuer->id,
            'void_reason' => $reason,
        ]);

        return $letter->fresh();
    }

    public function reissue(IssuedLetter $letter, User $issuer, ?string $notes = null): IssuedLetter
    {
        if (! $letter->isVoided()) {
            throw ValidationException::withMessages(['letter' => 'Void the letter before reissuing.']);
        }

        $letter->loadMissing(['employee', 'template']);

        if (! $letter->template) {
            throw ValidationException::withMessages(['letter' => 'Original template is unavailable for reissue.']);
        }

        $newLetter = $this->issue(
            $letter->employee,
            $letter->template,
            $issuer,
            $notes ?? $letter->notes,
        );

        $newLetter->update(['reissued_from_id' => $letter->id]);

        return $newLetter->fresh(['employee', 'template', 'issuer', 'reissuedFrom']);
    }

    public function templatesForEmployee(Employee $employee): \Illuminate\Support\Collection
    {
        return HrLetterTemplate::query()
            ->where('is_active', true)
            ->where(function ($q) use ($employee) {
                $q->whereNull('factory_id')->orWhere('factory_id', $employee->factory_id);
            })
            ->orderBy('name')
            ->get();
    }
}
