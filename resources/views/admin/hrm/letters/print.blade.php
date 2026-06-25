<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $letter->reference_no }} — {{ $letter->typeLabel() }}</title>
    @include('partials.hrm.letter-document-styles')
    <style>
        body { background: #f3f4f6; margin: 0; padding: 32px 16px; }
        .hr-letter-print-wrap { max-width: 820px; margin: 0 auto; }
        @media print {
            body { background: #fff; padding: 0; }
            .hr-letter-print-wrap { max-width: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="hr-letter-print-wrap">
        @include('partials.hrm.letter-document', [
            'content'      => $letter->content,
            'title'        => $letter->typeLabel(),
            'factoryName'  => $letter->employee->factory?->name,
            'referenceNo'  => $letter->reference_no,
            'issuedAt'     => $letter->issued_at,
        ])
    </div>
</body>
</html>
