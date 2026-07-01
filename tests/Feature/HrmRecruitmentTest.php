<?php

namespace Tests\Feature;

use App\Mail\RecruitmentApplicationReceivedMail;
use App\Mail\RecruitmentInterviewScheduledMail;
use App\Models\AppSetting;
use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\JobPosting;
use App\Models\Hrm\RecruitmentApplication;
use App\Models\Hrm\RecruitmentInterview;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class HrmRecruitmentTest extends TestCase
{
    use RefreshDatabase;

    private function hrAdmin(): User
    {
        $role = Role::create([
            'name'        => 'HR Recruitment Admin',
            'permissions' => [
                'hrm.employees.view',
                'hrm.employees.manage',
                'hrm.recruitment.postings.view',
                'hrm.recruitment.postings.manage',
                'hrm.recruitment.postings.approve',
                'hrm.recruitment.applications.view',
                'hrm.recruitment.applications.manage',
                'hrm.recruitment.applications.convert',
            ],
        ]);

        return User::create([
            'name'     => 'Recruitment HR',
            'email'    => 'hr-recruit@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);
    }

    private function openPosting(Factory $factory): JobPosting
    {
        return JobPosting::create([
            'factory_id'   => $factory->id,
            'title'        => 'Sewing Operator',
            'requirements' => 'Experienced operator needed.',
            'slots'        => 5,
            'status'       => 'open',
            'published_at' => now(),
        ]);
    }

    private function seedOtp(string $phone, string $otp = '123456'): void
    {
        $normalized = preg_replace('/\s+/', '', $phone);
        Cache::put('recruitment_otp:' . $normalized, $otp, 600);
    }

    public function test_careers_index_shows_open_postings(): void
    {
        $factory = Factory::create(['name' => 'Career Factory', 'is_active' => true]);
        $this->openPosting($factory);

        $this->get(route('careers.index'))
            ->assertOk()
            ->assertSee('Sewing Operator')
            ->assertSee('Build Your')
            ->assertSee('With Norban Group');
    }

    public function test_candidate_can_apply_online_with_otp(): void
    {
        $factory = Factory::create(['name' => 'Apply Factory', 'is_active' => true]);
        $posting = $this->openPosting($factory);
        $phone = '01711111111';
        $this->seedOtp($phone);

        $this->post(route('careers.apply.store', $posting), [
            'name'  => 'Karim Ahmed',
            'phone' => $phone,
            'otp'   => '123456',
            'email' => 'karim@test.com',
        ])->assertRedirect();

        $application = RecruitmentApplication::first();
        $this->assertSame('online', $application->source);
        $this->assertSame('Karim Ahmed', $application->name);
        $this->assertNotNull($application->phone_verified_at);
        $this->assertStringStartsWith('APP-', $application->application_no);
    }

    public function test_online_application_notifies_hr(): void
    {
        $factory = Factory::create(['name' => 'Notify Factory', 'is_active' => true]);
        $admin = $this->hrAdmin();
        $posting = $this->openPosting($factory);
        $phone = '01799999999';
        $this->seedOtp($phone);

        $this->post(route('careers.apply.store', $posting), [
            'name'  => 'Notify Candidate',
            'phone' => $phone,
            'otp'   => '123456',
        ]);

        $this->assertTrue(
            $admin->fresh()->notifications()->where('type', \App\Notifications\RecruitmentApplicationSubmittedNotification::class)->exists()
        );
    }

    public function test_candidate_can_track_application(): void
    {
        $factory = Factory::create(['name' => 'Track Factory', 'is_active' => true]);
        $posting = $this->openPosting($factory);
        $phone = '01722222222';
        $this->seedOtp($phone);

        $this->post(route('careers.apply.store', $posting), [
            'name'  => 'Track User',
            'phone' => $phone,
            'otp'   => '123456',
        ]);

        $application = RecruitmentApplication::first();

        $this->post(route('careers.track.submit'), [
            'application_no' => $application->application_no,
            'phone'          => '01722222222',
        ])->assertOk()
            ->assertSee($application->application_no)
            ->assertSee('Applied');
    }

    public function test_hr_can_schedule_interview(): void
    {
        $factory = Factory::create(['name' => 'Interview Factory', 'is_active' => true]);
        $admin = $this->hrAdmin();
        $posting = $this->openPosting($factory);

        $application = RecruitmentApplication::create([
            'application_no' => 'APP-2026-00099',
            'job_posting_id' => $posting->id,
            'factory_id'     => $factory->id,
            'source'         => 'online',
            'status'         => 'screening',
            'name'           => 'Interview Candidate',
            'phone'          => '01788888888',
            'applied_at'     => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.recruitment.applications.interviews.store', $application), [
            'scheduled_at'   => now()->addDays(2)->format('Y-m-d\TH:i'),
            'interview_type' => 'in_person',
            'location'       => 'HR Office',
        ])->assertRedirect();

        $application->refresh();
        $this->assertSame('interview', $application->status);
        $this->assertCount(1, $application->interviews);
    }

    public function test_hr_can_create_posting_and_manual_application(): void
    {
        $factory = Factory::create(['name' => 'HR Factory', 'is_active' => true]);
        $admin = $this->hrAdmin();
        $posting = $this->openPosting($factory);

        $this->actingAs($admin)->get(route('admin.hrm.recruitment.postings.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.hrm.recruitment.applications.index'))->assertOk();

        $this->actingAs($admin)->post(route('admin.hrm.recruitment.applications.store'), [
            'job_posting_id' => $posting->id,
            'source'         => 'walk_in',
            'name'           => 'Walk In Candidate',
            'phone'          => '01733333333',
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_recruitment_applications', [
            'name'   => 'Walk In Candidate',
            'source' => 'walk_in',
        ]);
    }

    public function test_convert_redirects_to_employee_create_with_prefill(): void
    {
        $factory = Factory::create(['name' => 'Convert Factory', 'is_active' => true]);
        $admin = $this->hrAdmin();
        $posting = $this->openPosting($factory);

        $application = RecruitmentApplication::create([
            'application_no' => 'APP-2026-00001',
            'job_posting_id' => $posting->id,
            'factory_id'     => $factory->id,
            'source'         => 'online',
            'status'         => 'selected',
            'name'           => 'Future Employee',
            'phone'          => '01744444444',
            'nid_number'     => '1234567890',
            'applied_at'     => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.recruitment.applications.convert', $application))
            ->assertRedirect(route('admin.hrm.employees.create'));

        $this->actingAs($admin)->get(route('admin.hrm.employees.create'))
            ->assertOk()
            ->assertSee('Pre-filled from recruitment application');
    }

    public function test_former_employee_can_apply_again(): void
    {
        $factory = Factory::create(['name' => 'Rehire Factory', 'is_active' => true]);
        $posting = $this->openPosting($factory);
        $phone = '01755555555';
        $this->seedOtp($phone);

        Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'REH-1',
            'name'          => 'Old Worker',
            'phone'         => $phone,
            'nid_number'    => '9876543210',
            'status'        => 'resigned',
            'joining_date'  => now()->subYears(2)->toDateString(),
        ]);

        $this->post(route('careers.apply.store', $posting), [
            'name'       => 'Old Worker',
            'phone'      => $phone,
            'otp'        => '123456',
            'nid_number' => '9876543210',
        ])->assertRedirect();

        $this->assertSame(1, RecruitmentApplication::count());
    }

    public function test_online_application_sends_confirmation_email(): void
    {
        Mail::fake();

        $factory = Factory::create(['name' => 'Mail Factory', 'is_active' => true]);
        $posting = $this->openPosting($factory);
        $phone = '01766666666';
        $this->seedOtp($phone);

        $this->post(route('careers.apply.store', $posting), [
            'name'  => 'Mail Candidate',
            'phone' => $phone,
            'otp'   => '123456',
            'email' => 'candidate@test.com',
        ])->assertRedirect();

        Mail::assertSent(RecruitmentApplicationReceivedMail::class, function ($mail) {
            return $mail->hasTo('candidate@test.com');
        });
    }

    public function test_interview_schedule_sends_candidate_email(): void
    {
        Mail::fake();

        $factory = Factory::create(['name' => 'Schedule Mail Factory', 'is_active' => true]);
        $admin = $this->hrAdmin();
        $posting = $this->openPosting($factory);

        $application = RecruitmentApplication::create([
            'application_no' => 'APP-2026-00100',
            'job_posting_id' => $posting->id,
            'factory_id'     => $factory->id,
            'source'         => 'online',
            'status'         => 'screening',
            'name'           => 'Scheduled Candidate',
            'phone'          => '01777777777',
            'email'          => 'scheduled@test.com',
            'applied_at'     => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.recruitment.applications.interviews.store', $application), [
            'scheduled_at'   => now()->addDays(2)->format('Y-m-d\TH:i'),
            'interview_type' => 'in_person',
            'location'       => 'Main Gate',
        ])->assertRedirect();

        Mail::assertSent(RecruitmentInterviewScheduledMail::class, function ($mail) {
            return $mail->hasTo('scheduled@test.com');
        });
    }

    public function test_interview_reminder_command_marks_reminder_sent(): void
    {
        Mail::fake();

        $factory = Factory::create(['name' => 'Reminder Factory', 'is_active' => true]);
        $posting = $this->openPosting($factory);

        $application = RecruitmentApplication::create([
            'application_no' => 'APP-2026-00101',
            'job_posting_id' => $posting->id,
            'factory_id'     => $factory->id,
            'source'         => 'online',
            'status'         => 'interview',
            'name'           => 'Reminder Candidate',
            'phone'          => '01712121212',
            'email'          => 'reminder@test.com',
            'applied_at'     => now(),
        ]);

        $interview = RecruitmentInterview::create([
            'application_id' => $application->id,
            'scheduled_at'   => now()->addHours(12),
            'location'       => 'HR Room',
            'interview_type' => 'in_person',
            'result'         => 'pending',
        ]);

        Artisan::call('hrm:notify-recruitment-interviews');

        $this->assertNotNull($interview->fresh()->reminder_sent_at);
        Mail::assertSent(\App\Mail\RecruitmentInterviewReminderMail::class);
    }

    public function test_posting_auto_closes_when_slots_filled(): void
    {
        $factory = Factory::create(['name' => 'Close Factory', 'is_active' => true]);
        $admin = $this->hrAdmin();
        $posting = JobPosting::create([
            'factory_id'   => $factory->id,
            'title'        => 'One Slot Job',
            'slots'        => 1,
            'status'       => 'open',
            'published_at' => now(),
        ]);

        $application = RecruitmentApplication::create([
            'application_no' => 'APP-2026-00200',
            'job_posting_id' => $posting->id,
            'factory_id'     => $factory->id,
            'source'         => 'online',
            'status'         => 'selected',
            'name'           => 'Hire Candidate',
            'phone'          => '01710101010',
            'applied_at'     => now(),
        ]);

        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'NEW-1',
            'name'          => 'Hire Candidate',
            'phone'         => '01710101010',
            'status'        => 'probation',
            'joining_date'  => now()->toDateString(),
        ]);

        app(\App\Services\Hrm\RecruitmentService::class)->markConverted($application, $employee, $admin);

        $this->assertSame('closed', $posting->fresh()->status);
        $this->assertSame(1, $posting->fresh()->openings_filled);
    }

    public function test_hr_can_issue_offer_letter(): void
    {
        Mail::fake();

        $factory = Factory::create(['name' => 'Offer Factory', 'is_active' => true]);
        $admin = $this->hrAdmin();
        $posting = $this->openPosting($factory);

        $application = RecruitmentApplication::create([
            'application_no' => 'APP-2026-00201',
            'job_posting_id' => $posting->id,
            'factory_id'     => $factory->id,
            'source'         => 'online',
            'status'         => 'selected',
            'name'           => 'Offer Candidate',
            'phone'          => '01720202020',
            'email'          => 'offer@test.com',
            'applied_at'     => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.recruitment.applications.offer-letter.store', $application), [
            'offered_salary' => 25000,
            'joining_date'   => now()->addDays(5)->toDateString(),
        ])->assertRedirect();

        $application->refresh();
        $this->assertSame('offered', $application->status);
        $this->assertDatabaseHas('hrm_recruitment_offer_letters', [
            'application_id' => $application->id,
        ]);
    }

    public function test_candidate_can_accept_offer_on_careers_portal(): void
    {
        $factory = Factory::create(['name' => 'Offer Portal Factory', 'is_active' => true]);
        $admin = $this->hrAdmin();
        $posting = $this->openPosting($factory);

        $application = RecruitmentApplication::create([
            'application_no' => 'APP-2026-00999',
            'job_posting_id' => $posting->id,
            'factory_id'     => $factory->id,
            'source'         => 'online',
            'status'         => 'selected',
            'name'           => 'Portal Offer Candidate',
            'phone'          => '01790909090',
            'applied_at'     => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.recruitment.applications.offer-letter.store', $application), [
            'offered_salary' => 22000,
            'joining_date'   => now()->addDays(7)->toDateString(),
        ])->assertRedirect();

        $application->refresh();
        $offer = $application->latestOfferLetter();
        $this->assertNotNull($offer);

        $this->post(route('careers.offer.respond'), [
            'application_no' => $application->application_no,
            'phone'          => $application->phone,
            'response'       => 'accepted',
        ])->assertRedirect(route('careers.track'));

        $this->assertSame('accepted', $offer->fresh()->response);
        $this->assertSame('offered', $application->fresh()->status);
    }

    public function test_recruitment_dashboard_loads(): void
    {
        $admin = $this->hrAdmin();

        $this->actingAs($admin)->get(route('admin.hrm.recruitment.dashboard'))
            ->assertOk()
            ->assertSee('Recruitment Dashboard');
    }

    public function test_applications_can_be_exported_as_csv(): void
    {
        $factory = Factory::create(['name' => 'Export Factory', 'is_active' => true]);
        $admin = $this->hrAdmin();
        $posting = $this->openPosting($factory);

        RecruitmentApplication::create([
            'application_no' => 'APP-2026-00202',
            'job_posting_id' => $posting->id,
            'factory_id'     => $factory->id,
            'source'         => 'walk_in',
            'status'         => 'applied',
            'name'           => 'Export Candidate',
            'phone'          => '01730303030',
            'applied_at'     => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.hrm.recruitment.applications.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_posting_requires_salary_when_not_negotiable(): void
    {
        $factory = Factory::create(['name' => 'Salary Factory', 'is_active' => true]);
        $admin = $this->hrAdmin();

        $this->actingAs($admin)->post(route('admin.hrm.recruitment.postings.store'), [
            'factory_id'        => $factory->id,
            'title'             => 'Salary Test Job',
            'slots'             => 1,
            'status'            => 'draft',
            'salary_negotiable' => 0,
        ])->assertSessionHasErrors('salary_text');
    }

    public function test_posting_allows_blank_salary_when_negotiable(): void
    {
        $factory = Factory::create(['name' => 'Negotiable Factory', 'is_active' => true]);
        $admin = $this->hrAdmin();

        $this->actingAs($admin)->post(route('admin.hrm.recruitment.postings.store'), [
            'factory_id'        => $factory->id,
            'title'             => 'Negotiable Job',
            'slots'             => 1,
            'status'            => 'draft',
            'salary_negotiable' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_job_postings', [
            'title'             => 'Negotiable Job',
            'salary_negotiable' => 1,
            'salary_text'       => null,
        ]);
    }

    public function test_candidate_can_apply_without_otp_when_disabled(): void
    {
        $factory = Factory::create(['name' => 'No OTP Factory', 'is_active' => true]);
        $posting = $this->openPosting($factory);

        AppSetting::current()->update(['recruitment_otp_enabled' => false]);
        AppSetting::clearCache();

        $this->post(route('careers.apply.store', $posting), [
            'name'  => 'No OTP Candidate',
            'phone' => '01714141414',
            'email' => 'nootp@test.com',
        ])->assertRedirect();

        $application = RecruitmentApplication::first();
        $this->assertSame('No OTP Candidate', $application->name);
        $this->assertNull($application->phone_verified_at);
    }

    public function test_send_otp_rejected_when_otp_disabled(): void
    {
        $factory = Factory::create(['name' => 'OTP Off Factory', 'is_active' => true]);
        $posting = $this->openPosting($factory);

        AppSetting::current()->update(['recruitment_otp_enabled' => false]);
        AppSetting::clearCache();

        $this->postJson(route('careers.otp.send', $posting), [
            'phone' => '01715151515',
        ])->assertStatus(422)
            ->assertJson(['message' => 'Phone verification is currently disabled.']);
    }

    public function test_internal_posting_hidden_from_careers_portal(): void
    {
        $factory = Factory::create(['name' => 'Internal Factory', 'is_active' => true]);
        JobPosting::create([
            'factory_id'   => $factory->id,
            'title'        => 'Internal Only Role',
            'slots'        => 2,
            'status'       => 'open',
            'is_internal'  => true,
            'published_at' => now(),
        ]);

        $this->get(route('careers.index'))
            ->assertOk()
            ->assertDontSee('Internal Only Role');
    }

    public function test_job_posting_records_page_views(): void
    {
        $factory = Factory::create(['name' => 'Views Factory', 'is_active' => true]);
        $posting = $this->openPosting($factory);

        $this->get(route('careers.show', $posting))->assertOk();
        $this->get(route('careers.show', $posting))->assertOk();

        $this->assertSame(2, $posting->fresh()->page_views);
    }

    public function test_manual_application_blocked_on_closed_posting(): void
    {
        $factory = Factory::create(['name' => 'Closed Factory', 'is_active' => true]);
        $admin = $this->hrAdmin();
        $posting = JobPosting::create([
            'factory_id'   => $factory->id,
            'title'        => 'Closed Role',
            'slots'        => 1,
            'status'       => 'closed',
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.recruitment.applications.store'), [
            'job_posting_id' => $posting->id,
            'source'         => 'hr_manual',
            'name'           => 'Manual Try',
            'phone'          => '01716161616',
        ])->assertSessionHasErrors('job_posting_id');
    }

    public function test_hr_can_publish_close_and_duplicate_posting(): void
    {
        $factory = Factory::create(['name' => 'Actions Factory', 'is_active' => true]);
        $admin = $this->hrAdmin();
        $posting = JobPosting::create([
            'factory_id' => $factory->id,
            'title'      => 'Draft Role',
            'slots'      => 3,
            'status'     => 'draft',
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.recruitment.postings.publish', $posting))
            ->assertRedirect();
        $this->assertSame('open', $posting->fresh()->status);

        $this->actingAs($admin)->post(route('admin.hrm.recruitment.postings.close', $posting))
            ->assertRedirect();
        $this->assertSame('closed', $posting->fresh()->status);

        $this->actingAs($admin)->post(route('admin.hrm.recruitment.postings.duplicate', $posting))
            ->assertRedirect();

        $this->assertDatabaseHas('hrm_job_postings', [
            'title'  => 'Draft Role (Copy)',
            'status' => 'draft',
        ]);
    }

    public function test_close_expired_job_postings_command(): void
    {
        $factory = Factory::create(['name' => 'Expire Factory', 'is_active' => true]);
        $posting = JobPosting::create([
            'factory_id' => $factory->id,
            'title'      => 'Expired Role',
            'slots'      => 2,
            'status'     => 'open',
            'closes_at'  => now()->subDay(),
            'published_at' => now()->subWeek(),
        ]);

        Artisan::call('hrm:close-expired-job-postings');

        $this->assertSame('closed', $posting->fresh()->status);
        $this->assertDatabaseHas('hrm_job_posting_logs', [
            'job_posting_id' => $posting->id,
            'action'         => 'auto_closed',
        ]);
    }

    public function test_posting_index_filters_by_department(): void
    {
        $factory = Factory::create(['name' => 'Filter Factory', 'is_active' => true]);
        $admin = $this->hrAdmin();
        $department = \App\Models\Department::create([
            'factory_id' => $factory->id,
            'name'       => 'Production',
            'is_active'  => true,
        ]);

        JobPosting::create([
            'factory_id'    => $factory->id,
            'department_id' => $department->id,
            'title'         => 'Dept Filter Job',
            'slots'         => 1,
            'status'        => 'draft',
        ]);
        JobPosting::create([
            'factory_id' => $factory->id,
            'title'      => 'Other Job',
            'slots'      => 1,
            'status'     => 'draft',
        ]);

        $this->actingAs($admin)->get(route('admin.hrm.recruitment.postings.index', [
            'department_id' => $department->id,
        ]))
            ->assertOk()
            ->assertSee('Dept Filter Job')
            ->assertDontSee('Other Job');
    }

    public function test_demo_job_posting_seeder_creates_expected_openings(): void
    {
        Factory::create(['name' => 'Head Office', 'is_active' => true]);
        Factory::create(['name' => 'Norban Comtex Limited', 'is_active' => true]);
        Factory::create(['name' => 'Hornbill Apparal Limited', 'is_active' => true]);

        $this->seed(\Database\Seeders\Hrm\DemoJobPostingSeeder::class);

        $this->assertDatabaseHas('hrm_job_postings', [
            'title'  => 'Full Stack Software Developer',
            'slots'  => 2,
            'status' => 'open',
        ]);

        $operatorSlots = JobPosting::query()
            ->where('title', 'Sewing Machine Operator')
            ->sum('slots');

        $supervisorSlots = JobPosting::query()
            ->where('title', 'Line Supervisor')
            ->sum('slots');

        $this->assertSame(10, (int) $operatorSlots);
        $this->assertSame(2, (int) $supervisorSlots);

        $this->get(route('careers.index'))
            ->assertOk()
            ->assertSee('Full Stack Software Developer')
            ->assertSee('Sewing Machine Operator');

        $developer = JobPosting::where('title', 'Full Stack Software Developer')->first();
        $this->assertNotNull($developer->description);
        $this->assertNotNull($developer->benefits);
        $this->assertNotNull($developer->skills_expertise);

        $this->get(route('careers.show', $developer))
            ->assertOk()
            ->assertSee('Overview')
            ->assertSee('Requirements')
            ->assertSee('Benefits & Perks');
    }

    public function test_candidate_can_apply_with_cv_attachment(): void
    {
        $factory = Factory::create(['name' => 'CV Factory', 'is_active' => true]);
        $posting = $this->openPosting($factory);
        $phone = '01717171717';
        $this->seedOtp($phone);

        $file = \Illuminate\Http\UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

        $this->post(route('careers.apply.store', $posting), [
            'name'  => 'CV Candidate',
            'phone' => $phone,
            'otp'   => '123456',
            'cv'    => $file,
        ])->assertRedirect();

        $application = RecruitmentApplication::first();
        $this->assertNotNull($application->cv_path);
    }
}
