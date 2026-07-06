<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
    @php
        $failedCount = $devices->where('last_sync_status', 'failed')->count();
        $staleMinutes = config('hrm.sync.stale_push_minutes', 60);
        $staleCutoff = now()->subMinutes($staleMinutes);
        $staleCount = $devices->filter(function ($device) use ($staleCutoff) {
            if ($device->last_sync_status === 'failed') {
                return false;
            }
            if ($device->last_seen_at) {
                return $device->last_seen_at->lt($staleCutoff);
            }
            if ($device->hasAdmsEndpoint() && $device->last_synced_at) {
                return $device->last_synced_at->lt($staleCutoff);
            }
            return false;
        })->count();
    @endphp
    <div class="erp-panel">
        <div class="erp-panel-body text-center">
            <p class="text-2xl font-bold text-brand">{{ $stats['devices'] }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Active Devices</p>
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-body text-center">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['punches_today'] }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Punches Today</p>
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-body text-center">
            <p class="text-2xl font-bold {{ $stats['unprocessed'] ? 'text-amber-600' : 'text-green-700' }}">{{ $stats['unprocessed'] }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Unprocessed</p>
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-body text-center">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['logs_today'] }}</p>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Logs Today</p>
        </div>
    </div>
</div>

@if($failedCount > 0 || $staleCount > 0)
    <div class="erp-panel mb-4 border-red-200 bg-red-50/30">
        <div class="erp-panel-body flex flex-wrap items-center justify-between gap-3 text-xs">
            <div>
                @if($failedCount > 0)
                    <p class="font-semibold text-red-700">{{ $failedCount }} device(s) with failed sync</p>
                @endif
                @if($staleCount > 0)
                    <p class="font-semibold text-amber-700">{{ $staleCount }} device(s) stale (no activity &gt; {{ $staleMinutes }}m)</p>
                @endif
            </div>
            <a href="{{ route('admin.hrm.attendance.sync.failures') }}" class="erp-btn-secondary !text-xs">View Sync Failures →</a>
        </div>
    </div>
@endif

<div class="erp-panel mb-4 border border-brand/20 bg-brand/5">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-brand uppercase tracking-wide">SpeedFace V5L Setup (Cloud Server)</h2>
    </div>
    <div class="erp-panel-body text-xs text-gray-700 space-y-2">
        @php
            $host = parse_url(config('app.url'), PHP_URL_HOST) ?: request()->getHost();
            $isHttps = str_starts_with(config('app.url'), 'https://');
            $cloudPort = $isHttps ? '443' : '80';
        @endphp
        <p>On the device: <strong>Menu → COMM. → Cloud Server Setting</strong></p>
        <ol class="list-decimal list-inside space-y-1 text-gray-600">
            <li><strong>Server Mode:</strong> ADMS</li>
            <li><strong>Enable Domain Name:</strong> ON (recommended)</li>
            <li><strong>Server Address:</strong> <code class="bg-white px-1.5 py-0.5 rounded border">{{ $host }}</code> — not <code class="bg-white px-1.5 py-0.5 rounded border">0.0.0.0</code></li>
            <li><strong>Server Port:</strong> <code class="bg-white px-1.5 py-0.5 rounded border">{{ $cloudPort }}</code> @if($isHttps)(HTTPS)@else(HTTP — use 443 on production with SSL)@endif</li>
            <li>Register device serial in <a href="{{ route('admin.hrm.masters.index', ['module' => 'hrm-biometric-devices']) }}" class="text-brand font-semibold">Biometric Devices</a> (must match device SN)</li>
            <li>Map employee <strong>Biometric ID</strong> = device PIN (face enrolled user ID)</li>
        </ol>
        <p class="pt-1 text-gray-500">Device pushes to <code class="bg-white px-1.5 py-0.5 rounded border">{{ url('/iclock/cdata') }}</code> — attendance updates <strong>instantly</strong> (no manual Process needed).</p>
        <p class="text-emerald-700 font-medium">✓ When connected, a cloud icon appears on the device standby screen.</p>
    </div>
</div>

<div class="erp-panel mb-4 border border-amber-200 bg-amber-50/40">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-amber-800 uppercase tracking-wide">Ethernet — fix before Cloud Server</h2>
    </div>
    <div class="erp-panel-body text-xs text-gray-700 space-y-2">
        <p>On the device: <strong>Menu → COMM. → Ethernet</strong></p>
        <ul class="list-disc list-inside space-y-1 text-gray-600">
            <li><strong>Gateway</strong> must be your router IP (e.g. <code class="bg-white px-1 rounded border">192.168.1.1</code>) — not <code class="bg-white px-1 rounded border">0.0.0.0</code></li>
            <li><strong>DNS</strong> — use <code class="bg-white px-1 rounded border">8.8.8.8</code> or your office DNS — not <code class="bg-white px-1 rounded border">0.0.0.0</code></li>
            <li>Static IP (e.g. <code class="bg-white px-1 rounded border">192.168.1.201</code>) is fine if it does not conflict with other devices</li>
            <li>Or enable <strong>DHCP</strong> if your network assigns IPs automatically</li>
        </ul>
        <p class="text-amber-800">Without gateway/DNS the device cannot reach the internet or <code class="bg-white px-1 rounded border">{{ $host }}</code>.</p>
        <p class="text-gray-500"><strong>PC Connection</strong> (port 4370) is for ZKTeco desktop software on LAN — separate from ADMS cloud push.</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    <div class="erp-panel overflow-hidden">
        <div class="erp-panel-head">
            <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Biometric Devices</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Device</th>
                        <th>Factory</th>
                        <th>Last Activity</th>
                        @if(auth()->user()->hasPermission('hrm.attendance.sync'))
                            <th class="text-right">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($devices as $device)
                        <tr>
                            <td>
                                <p class="font-medium text-sm">{{ $device->name }}</p>
                                <p class="text-[11px] text-gray-400 font-mono">{{ $device->device_serial ?: 'No serial' }}</p>
                                @if($device->device_model)
                                    <p class="text-[10px] text-gray-400">{{ $device->device_model }}</p>
                                @endif
                            </td>
                            <td class="text-xs">{{ $device->factory?->name }}</td>
                            <td class="text-xs">
                                @if($device->last_seen_at)
                                    <span class="erp-badge {{ $device->last_sync_status === 'success' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                        Online push
                                    </span>
                                    <p class="text-gray-400 mt-0.5 tabular-nums">{{ $device->last_seen_at->diffForHumans() }}</p>
                                @elseif($device->last_synced_at)
                                    <span class="erp-badge {{ $device->last_sync_status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($device->last_sync_status ?? 'unknown') }}
                                    </span>
                                    <p class="text-gray-400 mt-0.5 tabular-nums">@portalDateTimeShort($device->last_synced_at)</p>
                                @else
                                    <span class="text-gray-400">Waiting for device…</span>
                                @endif
                            </td>
                            @if(auth()->user()->hasPermission('hrm.attendance.sync'))
                                <td class="text-right">
                                    @if($device->hasAdmsEndpoint())
                                        <form method="POST" action="{{ route('admin.hrm.attendance.devices.sync', $device) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="erp-btn-secondary !py-1 !px-2 text-xs">Pull Sync</button>
                                        </form>
                                    @else
                                        <span class="text-[11px] text-emerald-600">Push mode</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-8 text-gray-400">No biometric devices configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-4">
        <div class="erp-panel overflow-hidden">
            <div class="erp-panel-head flex items-center justify-between">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Recent Punches</h2>
                <a href="{{ route('admin.hrm.attendance.punches') }}" class="erp-btn-sm-secondary">View all</a>
            </div>
            <div class="divide-y divide-erp-border">
                @forelse($recentPunches as $punch)
                    <div class="px-4 py-3 text-xs flex items-center justify-between gap-3">
                        <div>
                            <p class="font-medium text-gray-900">{{ $punch->employee?->name ?? 'Unmapped' }}</p>
                            <p class="text-gray-400 font-mono">{{ $punch->biometric_user_id }} · {{ $punch->punchTypeLabel() }} · {{ $punch->sourceLabel() }}</p>
                        </div>
                        <p class="text-gray-500 tabular-nums shrink-0">@portalDateTimeShort($punch->punched_at)</p>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-gray-400">No punches yet. Configure SpeedFace V5L cloud server.</p>
                @endforelse
            </div>
        </div>

        <div class="erp-panel overflow-hidden">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Sync History</h2>
            </div>
            <div class="divide-y divide-erp-border max-h-64 overflow-y-auto">
                @forelse($syncLogs as $log)
                    <div class="px-4 py-3 text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-medium">{{ $log->biometricDevice?->name }}</p>
                            <span class="erp-badge {{ $log->status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ ucfirst($log->status) }}</span>
                        </div>
                        <p class="text-gray-500 mt-1">{{ $log->message }}</p>
                        <p class="text-gray-400 mt-0.5 tabular-nums">@portalDateTime($log->started_at)</p>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-gray-400">No sync runs yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="erp-panel mt-4">
    <div class="erp-panel-body text-xs text-gray-500 space-y-1">
        <p><strong>SpeedFace V5L (recommended):</strong> Cloud push to <code class="bg-gray-100 px-1 rounded">{{ url('/iclock/cdata') }}</code> — real-time attendance.</p>
        <p><strong>Mobile / QR:</strong> Employees use <code class="bg-gray-100 px-1 rounded">/employee/attendance/check-in</code> or scan gate QR from admin Gate Points.</p>
        <p><strong>Legacy pull sync:</strong> Optional ADMS URL + <code class="bg-gray-100 px-1 rounded">php artisan hrm:sync-adms</code>.</p>
    </div>
</div>
