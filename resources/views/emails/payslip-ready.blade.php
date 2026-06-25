<x-mail::message>
# Payslip — {{ $payslip->period?->periodLabel() }}

Hello {{ $payslip->employee?->name }},

Your payslip for **{{ $payslip->period?->periodLabel() }}** is ready.

| | |
|---|---:|
| Gross Pay | ৳{{ number_format((float) $payslip->gross_pay, 2) }} |
| Deductions | ৳{{ number_format($payslip->totalDeductions(), 2) }} |
| **Net Pay** | **৳{{ number_format((float) $payslip->net_pay, 2) }}** |

<x-mail::button :url="route('employee.payslips.show', $payslip)">
View Payslip
</x-mail::button>

Thanks,<br>
{{ config('portal.name', config('app.name')) }} HR
</x-mail::message>
