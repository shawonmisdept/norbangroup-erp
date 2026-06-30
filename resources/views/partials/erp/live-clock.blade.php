<div class="erp-live-clock hidden sm:flex"
     x-data="erpLiveClock('{{ config('app.timezone') }}')"
     aria-live="polite"
     aria-label="Current date and time"
     title="{{ config('app.timezone') }}">
    <div class="erp-live-clock-face">
        <span class="erp-live-clock-time">
            <span x-text="hour"></span><span class="erp-live-clock-colon">:</span><span x-text="minute"></span><span class="erp-live-clock-colon">:</span><span class="erp-live-clock-sec" x-text="second"></span>
            <span class="erp-live-clock-period" x-text="period"></span>
        </span>
        <span class="erp-live-clock-date" x-text="date"></span>
    </div>
</div>
