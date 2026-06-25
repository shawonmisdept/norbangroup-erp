@php
    $isPublic = $isPublic ?? false;
    $postings = $postings ?? null;
    $selectedPosting = $selectedPosting ?? ($posting->id ?? null);
    $educationRows = old('education_history', [['degree' => '', 'institution' => '', 'board_or_university' => '', 'passing_year' => '', 'result' => '']]);
    $employmentRows = old('employment_history', [['company_name' => '', 'designation' => '', 'department' => '', 'joining_date' => '', 'leaving_date' => '', 'reason_for_leaving' => '']]);
    $inputClass = $isPublic ? 'careers-input' : 'erp-input !text-xs';
    $sectionClass = $isPublic ? 'careers-form-section' : 'erp-panel';
    $sectionHead = $isPublic ? 'careers-form-section-head' : 'erp-panel-head';
    $sectionBody = $isPublic ? 'careers-form-section-body' : 'erp-panel-body';
    $labelWrap = $isPublic ? 'careers-field' : '';
    $labelTag = $isPublic ? 'span' : null;
@endphp

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="space-y-4" id="recruitment-form">
    @csrf
    @if($formMethod ?? false) @method($formMethod) @endif

    @if(! $isPublic && $postings)
        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">Application Source</h2></div>
            <div class="erp-panel-body grid grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Job Posting *</label>
                    <select name="job_posting_id" required class="erp-input !text-xs">
                        <option value="">Select posting…</option>
                        @foreach($postings as $id => $label)
                            <option value="{{ $id }}" {{ (int) old('job_posting_id', $selectedPosting) === (int) $id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Source</label>
                    <select name="source" class="erp-input !text-xs">
                        @foreach(config('hrm.recruitment_sources', []) as $val => $label)
                            @if($val !== 'online')
                                <option value="{{ $val }}" {{ old('source', $application->source ?? 'hr_manual') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    @else
        <input type="hidden" name="job_posting_id" value="{{ $posting->id }}">
    @endif

    <div class="{{ $sectionClass }}">
        <div class="{{ $sectionHead }}">Personal Information</div>
        <div class="{{ $sectionBody }} grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                @if($isPublic)<label class="careers-field"><span>Full Name *</span></label>@else<label class="erp-form-label">Full Name *</label>@endif
                <input type="text" name="name" required value="{{ old('name') }}" class="{{ $inputClass }}">
            </div>
            <div>
                @if($isPublic)<label class="careers-field"><span>Phone *</span></label>@else<label class="erp-form-label">Phone *</label>@endif
                <input type="text" name="phone" id="recruitment-phone" required value="{{ old('phone') }}" class="{{ $inputClass }}" placeholder="01XXXXXXXXX">
            </div>
            @if($isPublic && ($otpSendUrl ?? null))
                <div>
                    <label class="careers-field"><span>Verify Phone (OTP) *</span></label>
                    <div class="careers-otp-row">
                        <input type="text" name="otp" id="recruitment-otp" required maxlength="6" pattern="[0-9]{6}" value="{{ old('otp') }}" placeholder="6-digit code" class="careers-input">
                        <button type="button" id="send-otp-btn" class="careers-btn careers-btn-secondary shrink-0 !px-3">Send OTP</button>
                    </div>
                    <p id="otp-message" class="text-xs mt-1 text-[var(--careers-muted)]"></p>
                    @error('otp')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            @endif
            <div>
                @if($isPublic)<label class="careers-field"><span>Email</span></label>@else<label class="erp-form-label">Email</label>@endif
                <input type="email" name="email" value="{{ old('email') }}" class="{{ $inputClass }}">
            </div>
            <div>
                @if($isPublic)<label class="careers-field"><span>Gender</span></label>@else<label class="erp-form-label">Gender</label>@endif
                <select name="gender" class="{{ $inputClass }}">
                    <option value="">Select…</option>
                    @foreach($genders as $val => $label)
                        <option value="{{ $val }}" {{ old('gender') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                @if($isPublic)<label class="careers-field"><span>Date of Birth</span></label>@else<label class="erp-form-label">Date of Birth</label>@endif
                <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="{{ $inputClass }}">
            </div>
            <div>
                @if($isPublic)<label class="careers-field"><span>NID Number</span></label>@else<label class="erp-form-label">NID Number</label>@endif
                <input type="text" name="nid_number" value="{{ old('nid_number') }}" class="{{ $inputClass }}">
            </div>
            <div class="md:col-span-2">
                @if($isPublic)<label class="careers-field"><span>Present Address</span></label>@else<label class="erp-form-label">Present Address</label>@endif
                <textarea name="present_address" rows="2" class="{{ $inputClass }}">{{ old('present_address') }}</textarea>
            </div>
            <div class="md:col-span-2">
                @if($isPublic)<label class="careers-field"><span>Permanent Address</span></label>@else<label class="erp-form-label">Permanent Address</label>@endif
                <textarea name="permanent_address" rows="2" class="{{ $inputClass }}">{{ old('permanent_address') }}</textarea>
            </div>
            <div>
                @if($isPublic)<label class="careers-field"><span>Photo</span></label>@else<label class="erp-form-label">Photo</label>@endif
                <input type="file" name="photo" accept="image/*" class="{{ $inputClass }}">
            </div>
            <div>
                @if($isPublic)<label class="careers-field"><span>NID Document</span></label>@else<label class="erp-form-label">NID Document</label>@endif
                <input type="file" name="nid_document" accept=".jpg,.jpeg,.png,.pdf" class="{{ $inputClass }}">
            </div>
        </div>
    </div>

    <div class="{{ $sectionClass }}" x-data="{
        education: @js($educationRows),
        addEdu() { this.education.push({degree:'',institution:'',board_or_university:'',passing_year:'',result:''}); }
    }">
        <div class="{{ $sectionHead }} flex justify-between items-center">
            <span>Education History</span>
            @if($isPublic)<button type="button" @click="addEdu()" class="text-xs text-[var(--careers-blue)] hover:underline font-medium">+ Add row</button>
            @else<button type="button" @click="addEdu()" class="text-xs text-brand hover:underline">+ Add row</button>@endif
        </div>
        <div class="{{ $sectionBody }} space-y-3">
            <template x-for="(row, index) in education" :key="'edu-'+index">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-2 border border-gray-200 rounded-lg p-3 bg-gray-50/50">
                    <input type="text" :name="'education_history['+index+'][degree]'" x-model="row.degree" placeholder="Degree" class="{{ $inputClass }}">
                    <input type="text" :name="'education_history['+index+'][institution]'" x-model="row.institution" placeholder="Institution" class="{{ $inputClass }}">
                    <input type="text" :name="'education_history['+index+'][board_or_university]'" x-model="row.board_or_university" placeholder="Board/University" class="{{ $inputClass }}">
                    <input type="text" :name="'education_history['+index+'][passing_year]'" x-model="row.passing_year" placeholder="Year" class="{{ $inputClass }}">
                    <input type="text" :name="'education_history['+index+'][result]'" x-model="row.result" placeholder="Result" class="{{ $inputClass }}">
                </div>
            </template>
        </div>
    </div>

    <div class="{{ $sectionClass }}" x-data="{
        employment: @js($employmentRows),
        addEmp() { this.employment.push({company_name:'',designation:'',department:'',joining_date:'',leaving_date:'',reason_for_leaving:''}); }
    }">
        <div class="{{ $sectionHead }} flex justify-between items-center">
            <span>Employment History (Optional)</span>
            @if($isPublic)<button type="button" @click="addEmp()" class="text-xs text-[var(--careers-blue)] hover:underline font-medium">+ Add row</button>
            @else<button type="button" @click="addEmp()" class="text-xs text-brand hover:underline">+ Add row</button>@endif
        </div>
        <div class="{{ $sectionBody }} space-y-3">
            <template x-for="(row, index) in employment" :key="'emp-'+index">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2 border border-gray-200 rounded-lg p-3 bg-gray-50/50">
                    <input type="text" :name="'employment_history['+index+'][company_name]'" x-model="row.company_name" placeholder="Company" class="{{ $inputClass }}">
                    <input type="text" :name="'employment_history['+index+'][designation]'" x-model="row.designation" placeholder="Designation" class="{{ $inputClass }}">
                    <input type="text" :name="'employment_history['+index+'][department]'" x-model="row.department" placeholder="Department" class="{{ $inputClass }}">
                    <input type="date" :name="'employment_history['+index+'][joining_date]'" x-model="row.joining_date" class="{{ $inputClass }}">
                    <input type="date" :name="'employment_history['+index+'][leaving_date]'" x-model="row.leaving_date" class="{{ $inputClass }}">
                    <input type="text" :name="'employment_history['+index+'][reason_for_leaving]'" x-model="row.reason_for_leaving" placeholder="Reason for leaving" class="{{ $inputClass }}">
                </div>
            </template>
        </div>
    </div>

    <div class="{{ $sectionClass }}">
        <div class="{{ $sectionHead }}">Additional Information</div>
        <div class="{{ $sectionBody }} grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                @if($isPublic)<label class="careers-field"><span>Expected Salary (BDT)</span></label>@else<label class="erp-form-label">Expected Salary (BDT)</label>@endif
                <input type="number" name="expected_salary" min="0" step="0.01" value="{{ old('expected_salary') }}" class="{{ $inputClass }}">
            </div>
            <div>
                @if($isPublic)<label class="careers-field"><span>How did you hear about us?</span></label>@else<label class="erp-form-label">How did you hear about us?</label>@endif
                <select name="referral_source" class="{{ $inputClass }}">
                    <option value="">Select…</option>
                    @foreach($referralSources as $val => $label)
                        <option value="{{ $val }}" {{ old('referral_source') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if(! $isPublic)
                <div class="md:col-span-2">
                    <label class="erp-form-label">HR Notes (internal)</label>
                    <textarea name="notes" rows="2" class="erp-input !text-xs">{{ old('notes') }}</textarea>
                </div>
            @endif
        </div>
    </div>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="{{ $isPublic ? 'careers-btn careers-btn-primary !px-8 !py-3' : 'erp-btn-primary' }}">
            {{ $submitLabel ?? 'Submit Application' }}
        </button>
        @if($isPublic)
            <a href="{{ route('careers.show', $posting) }}" class="careers-btn careers-btn-secondary">Cancel</a>
        @endif
    </div>
</form>

@if($isPublic && ($otpSendUrl ?? null))
    @push('scripts')
    <script>
        document.getElementById('send-otp-btn')?.addEventListener('click', async function () {
            const phone = document.getElementById('recruitment-phone')?.value;
            const msg = document.getElementById('otp-message');
            const btn = this;
            if (!phone) { msg.textContent = 'Enter phone number first.'; msg.style.color = '#991b1b'; return; }
            btn.disabled = true;
            msg.textContent = 'Sending…';
            msg.style.color = '';
            try {
                const res = await fetch('{{ $otpSendUrl }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ phone }),
                });
                const data = await res.json();
                if (res.ok) {
                    msg.textContent = data.message || 'OTP sent to your phone.';
                    msg.style.color = '#166534';
                } else {
                    msg.textContent = data.message || 'Could not send OTP.';
                    msg.style.color = '#991b1b';
                }
            } catch (e) {
                msg.textContent = 'Network error. Try again.';
                msg.style.color = '#991b1b';
            }
            setTimeout(() => { btn.disabled = false; }, 60000);
        });
    </script>
    @endpush
@endif
