<?php

namespace Tests\Feature;

use Tests\TestCase;

class MobileAppLandingTest extends TestCase
{
    public function test_app_landing_page_loads(): void
    {
        $this->get(route('mobile.landing'))
            ->assertOk()
            ->assertSee('Employee Portal')
            ->assertSee('Rental Driver')
            ->assertSee('Choose how you want to sign in');
    }

    public function test_app_portal_query_redirects_to_employee_login(): void
    {
        $this->get(route('mobile.landing', ['portal' => 'employee']))
            ->assertRedirect(route('employee.login', ['source' => 'app']));
    }

    public function test_app_portal_query_redirects_to_rental_login(): void
    {
        $this->get(route('mobile.landing', ['portal' => 'rental']))
            ->assertRedirect(route('rental.login', ['source' => 'app']));
    }

    public function test_app_manifest_is_public(): void
    {
        $this->get('/app-manifest.webmanifest')
            ->assertOk()
            ->assertSee('Norban Group Portal');
    }

    public function test_assetlinks_json_is_public(): void
    {
        $this->get('/.well-known/assetlinks.json')
            ->assertOk()
            ->assertSee('com.norbangroup.portal');
    }
}
