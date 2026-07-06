<?php

$content = require __DIR__ . '/demo_job_posting_content.php';

/**
 * Demo open job postings — Head Office roles only.
 */
return [
    array_merge($content['software_developer'], [
        'factory'           => 'Head Office',
        'title'             => 'Full Stack Software Developer',
        'content_profile'   => 'software_developer',
        'slots'             => 2,
        'department'        => 'I T',
        'designation'       => 'IT Officer',
        'worker_category'   => null,
        'shift_type'        => 'day',
        'salary_text'       => 'Tk. 45,000 – 60,000 (Monthly)',
        'salary_negotiable' => true,
    ]),
    array_merge($content['mto_mis'], [
        'factory'           => 'Head Office',
        'title'             => 'MTO — MIS',
        'content_profile'   => 'mto_mis',
        'slots'             => 2,
        'department'        => 'I T',
        'designation'       => 'IT Assistant',
        'worker_category'   => null,
        'shift_type'        => 'day',
        'salary_text'       => 'Tk. 22,000 – 28,000 (Monthly)',
        'salary_negotiable' => false,
    ]),
    array_merge($content['senior_merchandiser'], [
        'factory'           => 'Head Office',
        'title'             => 'Senior Merchandiser',
        'content_profile'   => 'senior_merchandiser',
        'slots'             => 3,
        'department'        => 'Merchandising',
        'designation'       => 'Sr. Merchandiser',
        'worker_category'   => null,
        'shift_type'        => 'day',
        'salary_text'       => 'Negotiable',
        'salary_negotiable' => true,
    ]),
];
