<?php

namespace Tests\Unit;

use App\Support\NotificationUrl;
use Tests\TestCase;

class NotificationUrlTest extends TestCase
{
    public function test_resolve_strips_host_from_absolute_url(): void
    {
        $this->assertSame(
            '/admin/tms/requests/2',
            NotificationUrl::resolve('http://127.0.0.1:8000/admin/tms/requests/2')
        );
    }

    public function test_resolve_preserves_query_string(): void
    {
        $this->assertSame(
            '/admin/hrm/compliance/working-hours?factory_id=1&year=2026',
            NotificationUrl::resolve('http://localhost:8000/admin/hrm/compliance/working-hours?factory_id=1&year=2026')
        );
    }

    public function test_resolve_keeps_relative_path(): void
    {
        $this->assertSame('/employee/transport/trips', NotificationUrl::resolve('/employee/transport/trips'));
    }

    public function test_route_generates_relative_path(): void
    {
        $path = NotificationUrl::route('admin.tms.requests.index');

        $this->assertStringStartsWith('/admin/tms/requests', $path);
        $this->assertFalse(str_starts_with($path, 'http'));
    }
}
