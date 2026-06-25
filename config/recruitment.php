<?php

return [

    'sms' => [
        'driver' => env('RECRUITMENT_SMS_DRIVER', 'log'),

        'http' => [
            'url'     => env('RECRUITMENT_SMS_URL'),
            'api_key' => env('RECRUITMENT_SMS_API_KEY'),
            'sender'  => env('RECRUITMENT_SMS_SENDER', 'NORBAN'),
        ],
    ],

    'messages' => [
        'otp' => 'Your :app verification code is :otp. Valid for 10 minutes.',
        'application_received' => 'Application :no received for :job. Track: :url',
        'status_updated' => 'Application :no status updated to :status.',
        'interview_scheduled' => 'Interview scheduled on :date for :job. Location: :location. Ref: :no',
        'interview_reminder' => 'Reminder: Interview on :date for :job. Location: :location. Ref: :no',
    ],

];
