<div class="erp-live-clock hidden sm:flex"
     x-data="erpLiveClock('{{ config('app.timezone') }}')"
     aria-live="polite"
     aria-label="Current time"
     title="{{ config('app.timezone') }}">
    <span class="erp-live-clock-time">
        <span x-text="hour"></span><span class="erp-live-clock-colon">:</span><span x-text="minute"></span>
        <span class="erp-live-clock-period" x-text="period"></span>
    </span>
</div>
