<?php

namespace Tests\Unit;

use App\Support\TimeInput;
use Tests\TestCase;

class TimeInputTest extends TestCase
{
    public function test_format_for_input_converts_twelve_hour_values(): void
    {
        $this->assertSame('09:45', TimeInput::formatForInput('09:45:00'));
        $this->assertSame('19:00', TimeInput::formatForInput('07:00:00 PM'));
        $this->assertSame('13:00', TimeInput::formatForInput('01:00 PM'));
    }

    public function test_normalize_accepts_twelve_and_twenty_four_hour_values(): void
    {
        $this->assertSame('09:45', TimeInput::normalize('09:45:00 AM'));
        $this->assertSame('19:00', TimeInput::normalize('07:00:00 PM'));
        $this->assertSame('13:00', TimeInput::normalize('13:00'));
        $this->assertSame('14:00', TimeInput::normalize('14:00:00'));
    }

    public function test_format_for_display_uses_twelve_hour_clock(): void
    {
        $this->assertSame('7:00 PM', TimeInput::formatForDisplay('19:00:00'));
        $this->assertSame('9:45 AM', TimeInput::formatForDisplay('09:45:00'));
    }
}
