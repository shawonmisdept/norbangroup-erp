@extends('layouts.admin')

@section('title', 'App Settings — ' . config('app.name'))

@section('breadcrumbs')
    <span class="text-gray-600 font-medium">Administration</span>
    <span>/</span>
    <span class="text-gray-800 font-medium">App Settings</span>
@endsection

@section('admin-content')

@include('partials.erp.page-header', [
    'title' => 'App Settings',
    'subtitle' => 'Configure branding, timezone, currency, mail and notification preferences',
])

<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="max-w-4xl space-y-5"
      x-data="{ tab: 'general', mailer: '{{ old('mail_mailer', $settings->mail_mailer) }}' }">
    @csrf @method('PUT')

    {{-- Tabs --}}
    <div class="erp-panel overflow-hidden">
        <div class="flex border-b border-erp-border bg-gray-50/80 overflow-x-auto">
            @foreach(['general' => 'General', 'mail' => 'Mail', 'notifications' => 'Notifications'] as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'"
                        class="px-4 py-3 text-xs font-semibold uppercase tracking-wide border-b-2 transition whitespace-nowrap"
                        :class="tab === '{{ $key }}' ? 'border-gold text-brand bg-white' : 'border-transparent text-gray-400 hover:text-gray-600'">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="erp-panel-body space-y-4">

            {{-- General --}}
            <div x-show="tab === 'general'" x-cloak class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="erp-form-label">Application Name</label>
                    <input type="text" name="app_name" value="{{ old('app_name', $settings->app_name) }}" required class="erp-input">
                </div>
                <div class="sm:col-span-2">
                    <label class="erp-form-label">Tagline</label>
                    <input type="text" name="app_tagline" value="{{ old('app_tagline', $settings->app_tagline) }}" class="erp-input" placeholder="Manufacturer">
                </div>
                <div>
                    <label class="erp-form-label">Timezone</label>
                    <select name="timezone" required class="erp-input">
                        @foreach($timezones as $tz)
                            <option value="{{ $tz }}" {{ old('timezone', $settings->timezone) === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Currency</label>
                    <select name="currency_code" required class="erp-input">
                        @foreach($currencies as $code => $meta)
                            <option value="{{ $code }}" data-symbol="{{ $meta['symbol'] }}" {{ old('currency_code', $settings->currency_code) === $code ? 'selected' : '' }}>
                                {{ $meta['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Currency Symbol</label>
                    <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings->currency_symbol) }}" required class="erp-input">
                </div>

                <div class="sm:col-span-2 border-t border-erp-border pt-4">
                    <p class="erp-form-label mb-3">Branding Logos</p>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="border border-erp-border rounded-sm p-3 bg-gray-50/50">
                            <label class="erp-form-label">Navbar Logo (ERP Admin)</label>
                            <p class="text-[11px] text-gray-400 mb-2">Shown in the admin sidebar. PNG, JPG, WebP or SVG. Max 2MB.</p>
                            @if($settings->navbarLogoUrl())
                                <div class="flex items-center gap-3 mb-3 p-2 bg-white border border-erp-border rounded-sm">
                                    <img src="{{ $settings->navbarLogoUrl() }}" alt="Navbar logo" class="h-10 w-auto max-w-[140px] object-contain">
                                    <label class="flex items-center gap-2 text-xs text-red-600 cursor-pointer">
                                        <input type="checkbox" name="remove_navbar_logo" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                        Remove
                                    </label>
                                </div>
                            @endif
                            <input type="file" name="navbar_logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="erp-input !py-1.5 !text-xs">
                            @error('navbar_logo')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="border border-erp-border rounded-sm p-3 bg-gray-50/50">
                            <label class="erp-form-label">Frontend Logo (Public Site)</label>
                            <p class="text-[11px] text-gray-400 mb-2">Shown on the public navbar and login page. PNG, JPG, WebP or SVG. Max 2MB.</p>
                            @if($settings->frontendLogoUrl())
                                <div class="flex items-center gap-3 mb-3 p-2 bg-white border border-erp-border rounded-sm">
                                    <img src="{{ $settings->frontendLogoUrl() }}" alt="Frontend logo" class="h-10 w-auto max-w-[140px] object-contain">
                                    <label class="flex items-center gap-2 text-xs text-red-600 cursor-pointer">
                                        <input type="checkbox" name="remove_frontend_logo" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                        Remove
                                    </label>
                                </div>
                            @endif
                            <input type="file" name="frontend_logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="erp-input !py-1.5 !text-xs">
                            @error('frontend_logo')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mail --}}
            <div x-show="tab === 'mail'" x-cloak class="space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="erp-form-label">Mail Driver</label>
                        <select name="mail_mailer" x-model="mailer" required class="erp-input">
                            <option value="log">Log (development)</option>
                            <option value="gmail">Gmail</option>
                            <option value="smtp">SMTP (custom)</option>
                            <option value="sendmail">Sendmail</option>
                            <option value="array">Array (testing)</option>
                        </select>
                    </div>
                    <div>
                        <label class="erp-form-label">Admin Notification Email</label>
                        <input type="email" name="mail_admin_address" value="{{ old('mail_admin_address', $settings->mail_admin_address) }}" class="erp-input" placeholder="your@gmail.com">
                        <p class="text-[11px] text-gray-400 mt-1">New requirement alerts will be sent here.</p>
                    </div>
                </div>

                {{-- Gmail --}}
                <div x-show="mailer === 'gmail'" class="space-y-4 border-t border-erp-border pt-4">
                    <div class="rounded-sm border border-blue-100 bg-blue-50/70 px-4 py-3 text-xs text-blue-900 leading-relaxed">
                        <p class="font-semibold mb-1">Gmail setup</p>
                        <ol class="list-decimal ml-4 space-y-1">
                            <li>Google Account → Security → turn on <strong>2-Step Verification</strong></li>
                            <li>Search <strong>App Passwords</strong> → create one for "Mail"</li>
                            <li>Paste the 16-character App Password below (not your regular Gmail password)</li>
                        </ol>
                        <p class="mt-2">All notification emails (client confirmation, status update, admin alerts) will be sent from your Gmail address.</p>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="erp-form-label">Gmail Address</label>
                            <input type="email" name="mail_username"
                                   value="{{ old('mail_username', $settings->mail_username) }}"
                                   class="erp-input" placeholder="yourname@gmail.com"
                                   @input="if (mailer === 'gmail' && !$refs.fromAddress.value) { $refs.fromAddress.value = $event.target.value }">
                        </div>
                        <div>
                            <label class="erp-form-label">Gmail App Password</label>
                            <input type="password" name="mail_password" class="erp-input"
                                   placeholder="16-character app password" autocomplete="new-password">
                            <p class="text-[11px] text-gray-400 mt-1">Leave blank to keep the saved password.</p>
                        </div>
                    </div>

                    <input type="hidden" name="mail_host" value="smtp.gmail.com">
                    <input type="hidden" name="mail_port" value="587">
                    <input type="hidden" name="mail_encryption" value="tls">
                </div>

                <div x-show="mailer === 'smtp'" class="grid sm:grid-cols-2 gap-4 border-t border-erp-border pt-4">
                    <div>
                        <label class="erp-form-label">SMTP Host</label>
                        <input type="text" name="mail_host" value="{{ old('mail_host', $settings->mail_host) }}" class="erp-input" placeholder="smtp.mailtrap.io">
                    </div>
                    <div>
                        <label class="erp-form-label">SMTP Port</label>
                        <input type="number" name="mail_port" value="{{ old('mail_port', $settings->mail_port) }}" class="erp-input" placeholder="2525">
                    </div>
                    <div>
                        <label class="erp-form-label">Username</label>
                        <input type="text" name="mail_username" value="{{ old('mail_username', $settings->mail_username) }}" class="erp-input" autocomplete="off">
                    </div>
                    <div>
                        <label class="erp-form-label">Password</label>
                        <input type="password" name="mail_password" class="erp-input" placeholder="Leave blank to keep current" autocomplete="new-password">
                    </div>
                    <div>
                        <label class="erp-form-label">Encryption</label>
                        <select name="mail_encryption" class="erp-input">
                            <option value="" {{ old('mail_encryption', $settings->mail_encryption) === null ? 'selected' : '' }}>None</option>
                            <option value="tls" {{ old('mail_encryption', $settings->mail_encryption) === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ old('mail_encryption', $settings->mail_encryption) === 'ssl' ? 'selected' : '' }}>SSL</option>
                        </select>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4 border-t border-erp-border pt-4">
                    <div>
                        <label class="erp-form-label">From Email</label>
                        <input type="email" name="mail_from_address" x-ref="fromAddress"
                               value="{{ old('mail_from_address', $settings->mail_from_address) }}" class="erp-input"
                               placeholder="{{ $settings->mail_username ?: 'yourname@gmail.com' }}">
                        <p class="text-[11px] text-gray-400 mt-1" x-show="mailer === 'gmail'">Recipients will see this as the sender address.</p>
                    </div>
                    <div>
                        <label class="erp-form-label">From Name</label>
                        <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $settings->mail_from_name) }}" class="erp-input" placeholder="{{ config('portal.name') }}">
                    </div>
                </div>

                <div x-show="mailer === 'gmail' || mailer === 'smtp'" class="border-t border-erp-border pt-4">
                    <p class="text-[11px] text-gray-400">Save settings first, then use "Send Test Email" below to verify your mail configuration.</p>
                </div>
            </div>

            {{-- Notifications --}}
            <div x-show="tab === 'notifications'" x-cloak class="space-y-4">
                <p class="text-sm text-gray-500">Control popup (in-app) and email notifications for requirement events.</p>
                <p class="text-xs text-gray-400 rounded-sm border border-erp-border bg-gray-50 px-3 py-2">
                    Email notifications use the mail driver configured in the <button type="button" @click="tab = 'mail'" class="text-brand font-semibold hover:underline">Mail</button> tab
                    (Gmail, SMTP, etc.). Enable the toggles below to send emails on each event.
                </p>

                <div class="divide-y divide-erp-border border border-erp-border rounded-sm">
                    @foreach([
                        ['notify_popup_enabled', 'Enable popup notifications', 'Show toast alerts in the admin panel'],
                        ['notify_popup_admin_on_order', 'Popup — new requirement', 'Notify admin users when a client submits a requirement'],
                        ['notify_popup_admin_on_status', 'Popup — status change', 'Notify admin users when requirement status is updated'],
                        ['notify_mail_client_on_order', 'Email — client confirmation', 'Send confirmation email to client on submission'],
                        ['notify_mail_admin_on_order', 'Email — admin alert', 'Send email to admin when a new requirement arrives'],
                        ['notify_mail_client_on_status', 'Email — client status update', 'Send email to client when status changes'],
                    ] as [$field, $title, $desc])
                        <label class="flex items-start gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50/80">
                            <input type="hidden" name="{{ $field }}" value="0">
                            <input type="checkbox" name="{{ $field }}" value="1"
                                   {{ old($field, $settings->{$field}) ? 'checked' : '' }}
                                   class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                            <span>
                                <span class="block text-sm font-medium text-gray-800">{{ $title }}</span>
                                <span class="block text-xs text-gray-400 mt-0.5">{{ $desc }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="erp-btn-primary !px-6 !py-2.5">Save Settings</button>
    </div>
</form>

@if($settings->usesSmtpTransport())
    <form method="POST" action="{{ route('admin.settings.test-mail') }}" class="max-w-4xl mt-3 flex flex-wrap items-center gap-3">
        @csrf
        <button type="submit" class="erp-btn-secondary !text-xs">Send Test Email to Me</button>
        <p class="text-[11px] text-gray-400">Sends a test message to {{ auth()->user()->email }} using saved Gmail/SMTP settings.</p>
    </form>
@endif
@endsection
