@extends('layouts.app')

@section('title', 'Late & Weekend Work - Loops Integrated')

@push('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap">
<!-- jQuery & Select2 for multi-select dropdown -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
:root {
  --lw-bg: #F4F5F8;
  --lw-surface: #FFFFFF;
  --lw-sunk: #ECEEF3;
  --lw-line: #D9DCE5;
  --lw-ink: #1A1E2C;
  --lw-muted: #5D6375;
  --lw-accent: #B86A00;
  --lw-accent-ink: #FFFFFF;
  --lw-accent-soft: #FBEBD2;
  --lw-ok: #1F7A4D;
  --lw-ok-soft: #DDF1E6;
  --lw-bad: #B3261E;
  --lw-bad-soft: #F9DEDC;
  --lw-wait: #6B5A00;
  --lw-wait-soft: #F5EDC2;
  --lw-font-display: "Bricolage Grotesque", "Segoe UI", system-ui, sans-serif;
  --lw-font-body: "IBM Plex Sans", "Segoe UI", system-ui, sans-serif;
  --lw-font-mono: "IBM Plex Mono", ui-monospace, Menlo, monospace;
}

.lw-wrap {
  font-family: var(--lw-font-body);
  color: var(--lw-ink);
}

.lw-title {
  font-family: var(--lw-font-display);
}

.lw-mono {
  font-family: var(--lw-font-mono);
}

.lw-chip {
  font-size: 12px;
  font-weight: 600;
  border-radius: 20px;
  padding: 3px 12px;
  white-space: nowrap;
}
.lw-chip.pending { background: var(--lw-wait-soft); color: var(--lw-wait); }
.lw-chip.approved { background: var(--lw-ok-soft); color: var(--lw-ok); }
.lw-chip.declined { background: var(--lw-bad-soft); color: var(--lw-bad); }
.lw-chip.withdrawn { background: var(--lw-sunk); color: var(--lw-muted); }

.lw-tag {
  font-size: 12px;
  border: 1px solid var(--lw-line);
  border-radius: 6px;
  padding: 1px 7px;
  color: var(--lw-ink);
  background: #ffffff;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.lw-tag.wk {
  border-color: var(--lw-accent);
  color: var(--lw-accent);
  font-weight: 600;
}
.lw-tag.warn {
  border-color: var(--lw-bad);
  color: var(--lw-bad);
  background: var(--lw-bad-soft);
  font-weight: 600;
}
.lw-tag.wfh {
  border-color: #C7D2FE;
  color: #4338CA;
  background: #EEF2FF;
  font-weight: 600;
}

.lw-card {
  background: var(--lw-surface);
  border: 1px solid var(--lw-line);
  border-radius: 12px;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.lw-card:hover {
  border-color: #b0b6c6;
  box-shadow: 0 4px 12px rgba(0,0,0,0.04);
}

/* Select2 Custom Styling to match Late Work aesthetic */
.select2-container--default .select2-selection--multiple {
  background-color: #F4F5F8;
  border: 1px solid #D9DCE5;
  border-radius: 0.5rem;
  padding: 4px 8px;
  min-height: 42px;
  transition: all 0.2s ease;
}
.select2-container--default.select2-container--focus .select2-selection--multiple,
.select2-container--default.select2-container--open .select2-selection--multiple {
  border-color: #B86A00;
  box-shadow: 0 0 0 2px rgba(184, 106, 0, 0.2);
  outline: none;
  background-color: #FFFFFF;
}

/* Choice Tag (Pill) */
.select2-container--default .select2-selection--multiple .select2-selection__choice {
  position: relative !important;
  display: inline-flex !important;
  align-items: center !important;
  background-color: #FBEBD2 !important;
  border: 1px solid #E6A23C !important;
  color: #1A1E2C !important;
  border-radius: 6px !important;
  font-family: var(--lw-font-mono) !important;
  font-size: 11px !important;
  font-weight: 700 !important;
  padding: 4px 10px 4px 28px !important;
  margin-top: 4px !important;
  margin-right: 6px !important;
  line-height: 1.4 !important;
  max-width: calc(100% - 12px) !important;
  box-sizing: border-box !important;
  transition: all 0.15s ease !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice:hover {
  background-color: #F8E2BE !important;
  border-color: #B86A00 !important;
}

/* Choice Remove 'x' Button - strictly separated with left padding, no overlap */
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
  position: absolute !important;
  left: 6px !important;
  top: 50% !important;
  transform: translateY(-50%) !important;
  border: none !important;
  background: transparent !important;
  color: #B86A00 !important;
  font-size: 15px !important;
  font-weight: bold !important;
  padding: 0 !important;
  margin: 0 !important;
  cursor: pointer !important;
  line-height: 1 !important;
  width: 16px !important;
  height: 16px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  border-radius: 3px !important;
  transition: all 0.15s ease !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
  color: #FFFFFF !important;
  background-color: #B3261E !important;
}

/* Choice Text */
.select2-container--default .select2-selection--multiple .select2-selection__choice__display {
  display: inline-block !important;
  padding-left: 0 !important;
  padding-right: 0 !important;
  color: #1A1E2C !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  white-space: nowrap !important;
}

/* Hide rogue unstyled clear button */
.select2-container--default .select2-selection--multiple .select2-selection__clear {
  display: none !important;
}

/* Search input inside multiple select */
.select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field {
  margin-top: 5px !important;
  font-size: 13px !important;
  font-family: var(--lw-font-body) !important;
  color: #1A1E2C !important;
}

.select2-dropdown {
  border: 1px solid #D9DCE5;
  border-radius: 0.5rem;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  z-index: 9999;
}
.select2-container--default .select2-search--dropdown .select2-search__field {
  border: 1px solid #D9DCE5;
  border-radius: 6px;
  padding: 8px 12px;
  font-size: 13px;
  outline: none;
}
.select2-container--default .select2-search--dropdown .select2-search__field:focus {
  border-color: #B86A00;
}
.select2-container--default .select2-results__option {
  font-size: 13px;
  padding: 8px 12px;
}
.select2-container--default .select2-results__option--highlighted[aria-selected] {
  background-color: #FBEBD2;
  color: #1A1E2C;
}
.select2-container--default .select2-results__option[aria-selected=true] {
  background-color: #ECEEF3;
  color: #5D6375;
  font-weight: 600;
}
</style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto py-4 sm:py-6 px-3 sm:px-6 lg:px-8 space-y-6 lw-wrap" x-data="lateWorkApp()">

    <!-- Alerts / Flash Messages -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 px-4 py-3 rounded-xl flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600 text-lg"></i>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if(session('error') || $errors->any())
        <div class="bg-rose-50 border border-rose-300 text-rose-800 px-4 py-3 rounded-xl flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-2">
                <i class="fas fa-exclamation-circle text-rose-600 text-lg"></i>
                <span class="text-sm font-medium">
                    {{ session('error') ?: $errors->first() }}
                </span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-800">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    <!-- Header Banner -->
    <header class="bg-white border border-[#D9DCE5] rounded-2xl p-5 sm:p-7 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FBEBD2] text-[#B86A00] inline-flex items-center gap-1.5">
                    <i class="fas fa-moon text-xs"></i> After Office Hours
                </span>
                <span class="text-xs text-[#5D6375]">Policy 3.11</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-[#1A1E2C] lw-title">
                Late &amp; Weekend <span class="text-[#B86A00]">Work</span>
            </h1>
            <p class="text-xs sm:text-sm text-[#5D6375] mt-1.5 max-w-2xl leading-relaxed">
                Working past 8 PM, on a weekend or on a public holiday? Get your HOD's approval first.
                Approved requests cover the <strong>Rs.&nbsp;1,000 meal allowance</strong>, a <strong>PickMe ride home</strong> and, for weekends and holidays, <strong>lieu leave</strong>.
            </p>
        </div>

        <div class="flex items-center gap-3 bg-[#F4F5F8] p-2.5 rounded-xl border border-[#D9DCE5] self-start md:self-auto">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=B86A00&color=ffffff&size=64"
                 alt="{{ $user->name }}" class="w-10 h-10 rounded-full border border-white shadow-sm object-cover">
            <div class="min-w-0 pr-1">
                <p class="text-xs font-bold text-[#1A1E2C] truncate">{{ $user->name }}</p>
                <p class="text-[11px] text-[#5D6375] truncate">{{ $user->role }} · {{ $user->department ?: 'No Dept' }}</p>
            </div>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="flex gap-2 border-b border-[#D9DCE5] overflow-x-auto pb-1" role="tablist">
        <button type="button"
                @click="setTab('new')"
                :class="activeTab === 'new' ? 'border-[#B86A00] text-[#1A1E2C] font-bold' : 'border-transparent text-[#5D6375] hover:text-[#1A1E2C] font-medium'"
                class="px-4 py-2.5 border-b-2 text-sm transition-all whitespace-nowrap flex items-center gap-2">
            <i class="fas fa-plus-circle text-xs"></i>
            <span>New request</span>
        </button>

        <button type="button"
                @click="setTab('mine')"
                :class="activeTab === 'mine' ? 'border-[#B86A00] text-[#1A1E2C] font-bold' : 'border-transparent text-[#5D6375] hover:text-[#1A1E2C] font-medium'"
                class="px-4 py-2.5 border-b-2 text-sm transition-all whitespace-nowrap flex items-center gap-2">
            <i class="fas fa-list text-xs"></i>
            <span>My requests</span>
            @if($myRequests->count() > 0)
                <span class="bg-[#ECEEF3] text-[#1A1E2C] text-xs font-semibold px-2 py-0.5 rounded-full">
                    {{ $myRequests->count() }}
                </span>
            @endif
        </button>

        @if($canApproveAny)
        <button type="button"
                @click="setTab('approve')"
                :class="activeTab === 'approve' ? 'border-[#B86A00] text-[#1A1E2C] font-bold' : 'border-transparent text-[#5D6375] hover:text-[#1A1E2C] font-medium'"
                class="px-4 py-2.5 border-b-2 text-sm transition-all whitespace-nowrap flex items-center gap-2">
            <i class="fas fa-check-circle text-xs"></i>
            <span>Approvals</span>
            @if($pendingApprovals->count() > 0)
                <span class="bg-[#B86A00] text-white text-xs font-bold px-2 py-0.5 rounded-full animate-pulse">
                    {{ $pendingApprovals->count() }}
                </span>
            @endif
        </button>
        @endif

        @if($isHodOnly)
        <button type="button"
                @click="setTab('team')"
                :class="activeTab === 'team' ? 'border-[#B86A00] text-[#1A1E2C] font-bold' : 'border-transparent text-[#5D6375] hover:text-[#1A1E2C] font-medium'"
                class="px-4 py-2.5 border-b-2 text-sm transition-all whitespace-nowrap flex items-center gap-2">
            <i class="fas fa-users text-xs"></i>
            <span>Team's requests</span>
            @if(isset($teamRequests) && $teamRequests->count() > 0)
                <span class="bg-[#ECEEF3] text-[#1A1E2C] text-xs font-semibold px-2 py-0.5 rounded-full">
                    {{ $teamRequests->count() }}
                </span>
            @endif
        </button>
        @elseif($canViewLog || (auth()->check() && (auth()->user()->isManagement() || auth()->user()->hasRole('management'))))
        <button type="button"
                @click="setTab('log')"
                :class="activeTab === 'log' ? 'border-[#B86A00] text-[#1A1E2C] font-bold' : 'border-transparent text-[#5D6375] hover:text-[#1A1E2C] font-medium'"
                class="px-4 py-2.5 border-b-2 text-sm transition-all whitespace-nowrap flex items-center gap-2">
            <i class="fas fa-table text-xs"></i>
            <span>Logs &amp; Reports</span>
        </button>
        @endif

        @if($canManageHolidays)
        <button type="button"
                @click="setTab('calendar')"
                :class="activeTab === 'calendar' ? 'border-[#B86A00] text-[#1A1E2C] font-bold' : 'border-transparent text-[#5D6375] hover:text-[#1A1E2C] font-medium'"
                class="px-4 py-2.5 border-b-2 text-sm transition-all whitespace-nowrap flex items-center gap-2">
            <i class="fas fa-calendar-alt text-xs"></i>
            <span>Calendar Settings</span>
            <span class="bg-[#FBEBD2] text-[#B86A00] text-[10px] font-bold px-1.5 py-0.5 rounded-md border border-[#F1C888]/60">
                HR/IT
            </span>
        </button>
        @endif

        <div class="ml-auto pl-2 flex items-center shrink-0">
            <button type="button" @click="instructionsModalOpen = true"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold text-[#B86A00] bg-[#FBEBD2]/70 hover:bg-[#FBEBD2] transition flex items-center gap-1.5 whitespace-nowrap border border-[#F1C888] cursor-pointer shadow-xs">
                <i class="fas fa-circle-info"></i>
                <span>Instructions</span>
            </button>
        </div>
    </nav>

    <!-- TAB 1: NEW REQUEST -->
    <section x-show="activeTab === 'new'" x-cloak class="space-y-6">
        <form action="{{ route('late-work.store') }}" method="POST" enctype="multipart/form-data" id="newRequestForm" class="bg-white border border-[#D9DCE5] rounded-2xl p-5 sm:p-7 shadow-sm space-y-6">
            @csrf

            <!-- Form Header & Type Switcher -->
            <div>
                <h2 class="text-xl font-bold text-[#1A1E2C] lw-title" x-text="type === 'weekend' ? 'Request to work on a weekend or public holiday' : 'Request to work late'">
                    Request to work late
                </h2>
                <p class="text-xs text-[#5D6375] mt-1">Select the nature of work to apply appropriate allowance and leave policies.</p>
            </div>

            <!-- Segmented Control for Request Type -->
            <div class="bg-[#ECEEF3] p-1 rounded-xl flex flex-col sm:flex-row gap-1">
                <label class="flex-1 text-center py-2.5 px-4 rounded-lg cursor-pointer font-semibold text-xs sm:text-sm transition-all select-none"
                       :class="type === 'late' ? 'bg-white text-[#1A1E2C] shadow-sm' : 'text-[#5D6375] hover:text-[#1A1E2C]'">
                    <input type="radio" name="type" value="late" x-model="type" @change="onTypeChange()" class="sr-only">
                    <i class="fas fa-moon mr-1.5" :class="type === 'late' ? 'text-[#B86A00]' : ''"></i> Weekday, past 8 PM
                </label>
                <label class="flex-1 text-center py-2.5 px-4 rounded-lg cursor-pointer font-semibold text-xs sm:text-sm transition-all select-none"
                       :class="type === 'weekend' ? 'bg-white text-[#1A1E2C] shadow-sm' : 'text-[#5D6375] hover:text-[#1A1E2C]'">
                    <input type="radio" name="type" value="weekend" x-model="type" @change="onTypeChange()" class="sr-only">
                    <i class="fas fa-calendar-alt mr-1.5" :class="type === 'weekend' ? 'text-[#B86A00]' : ''"></i> Weekend or public holiday
                </label>
            </div>

            <!-- Department, Assigned HOD & Date Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 items-start">
                <!-- Department Selection (Read-only) -->
                <div class="space-y-1.5">
                    @php
                        $userDept = $departments->firstWhere('id', old('dept_id'))
                            ?? $departments->firstWhere('name', $user->department)
                            ?? $departments->first();
                        $userDeptId = $userDept ? $userDept->id : '';
                        $userDeptName = $userDept ? $userDept->name : ($user->department ?: 'General');
                    @endphp
                    <label for="dept_name_display" class="text-xs font-bold text-[#5D6375] uppercase tracking-wider block">Department</label>
                    <div class="relative">
                        <input type="text" id="dept_name_display" readonly
                               value="{{ $userDeptName }}"
                               class="w-full bg-[#ECEEF3] border border-[#D9DCE5] rounded-lg px-3 py-2.5 text-sm font-semibold text-[#1A1E2C] cursor-not-allowed select-all focus:outline-none h-[42px]"
                               title="Read-only: locked to your assigned department">
                        <input type="hidden" name="dept_id" id="dept_id" value="{{ $userDeptId }}" x-model="selectedDeptId">
                    </div>
                </div>

                <!-- Assigned HOD (From Users List) -->
                <div class="space-y-1.5">
                    @php
                        $resolvedHod = $userAssignedHod 
                            ?? $user->associated_hod 
                            ?? $user->supervisor 
                            ?? ($user->supervisor_id ? \App\Models\User::find($user->supervisor_id) : null)
                            ?? ($userDept && $userDept->hods->isNotEmpty() ? $userDept->hods->first() : null)
                            ?? \App\Models\User::where('role', 'HOD')->where('department', $user->department)->first();
                        $resolvedHodName = $resolvedHod 
                            ? ($resolvedHod->name . ' (' . $resolvedHod->role . ($resolvedHod->department ? ' · ' . $resolvedHod->department : '') . ')')
                            : '';
                    @endphp
                    <label for="hod_display" class="text-xs font-bold text-[#5D6375] uppercase tracking-wider block">HOD / Supervisor</label>
                    @if($resolvedHod)
                        <div class="relative">
                            <input type="text" id="hod_display" readonly
                                   value="{{ $resolvedHodName }}"
                                   class="w-full bg-[#ECEEF3] border border-[#D9DCE5] rounded-lg px-3 py-2.5 text-sm font-semibold text-[#1A1E2C] cursor-not-allowed select-all focus:outline-none h-[42px]"
                                   title="HOD assigned to your account in Users list">
                            <input type="hidden" name="hod_id" id="hod_id" value="{{ $resolvedHod->id }}">
                        </div>
                    @else
                        <div>
                            <select name="hod_id" id="hod_id" required
                                    class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-3 py-2.5 text-sm font-medium text-[#1A1E2C] focus:outline-none focus:ring-2 focus:ring-[#B86A00] h-[42px]">
                                <option value="">-- Select HOD --</option>
                                @foreach($allHods as $h)
                                    <option value="{{ $h->id }}" {{ old('hod_id') == $h->id ? 'selected' : '' }}>
                                        {{ $h->name }} ({{ $h->role }}{{ $h->department ? ' · ' . $h->department : '' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <!-- Date Selection (Custom Calendar Picker with Month-by-Month Holidays) -->
                <div class="space-y-1.5 relative" @click.outside="showDatePicker = false">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-[#5D6375] uppercase tracking-wider block">Date</label>
                        <template x-if="selectedHoliday">
                            <span class="text-[11px] font-bold text-amber-800 bg-amber-100 border border-amber-300 px-2 py-0.5 rounded-md flex items-center gap-1">
                                <i class="fas fa-umbrella-beach text-[10px]"></i>
                                <span x-text="selectedHoliday.name"></span>
                            </span>
                        </template>
                        <template x-if="!selectedHoliday && isSelectedWeekend">
                            <span class="text-[11px] font-bold text-indigo-800 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-md flex items-center gap-1">
                                <i class="fas fa-calendar-day text-[10px]"></i>
                                <span>Weekend</span>
                            </span>
                        </template>
                    </div>

                    <!-- Picker Trigger Button -->
                    <button type="button" @click="showDatePicker = !showDatePicker"
                            class="w-full bg-[#F4F5F8] border border-[#D9DCE5] hover:border-[#B86A00] rounded-lg px-3 py-2 text-sm font-medium text-[#1A1E2C] focus:outline-none focus:ring-2 focus:ring-[#B86A00] h-[42px] flex items-center justify-between transition-colors shadow-xs">
                        <span class="flex items-center gap-2 truncate">
                            <i class="fas fa-calendar-alt text-[#B86A00]"></i>
                            <span class="font-semibold text-xs sm:text-sm" x-text="formattedSelectedDate"></span>
                        </span>
                        <i class="fas fa-chevron-down text-xs text-[#5D6375] transition-transform" :class="showDatePicker ? 'rotate-180 text-[#B86A00]' : ''"></i>
                    </button>

                    <!-- Hidden Real Input for Form Submission -->
                    <input type="hidden" name="date" id="date" x-model="date" required>

                    <!-- Custom Calendar Dropdown Popover -->
                    <div x-show="showDatePicker" x-cloak
                         class="absolute z-50 left-0 sm:right-auto mt-1 w-72 sm:w-80 bg-white border border-[#D9DCE5] rounded-xl shadow-2xl p-3.5 space-y-3">
                        
                        <!-- Popover Header: Month & Year Nav -->
                        <div class="flex items-center justify-between">
                            <button type="button" @click.stop="prevPickerMonth()"
                                    class="w-7 h-7 rounded-lg hover:bg-[#ECEEF3] text-[#5D6375] hover:text-[#1A1E2C] flex items-center justify-center transition">
                                <i class="fas fa-chevron-left text-xs"></i>
                            </button>
                            <span class="text-xs font-bold text-[#1A1E2C]" x-text="pickerMonthName"></span>
                            <div class="flex items-center gap-1">
                                <button type="button" @click.stop="goPickerToday()"
                                        class="text-[10px] font-bold text-[#B86A00] hover:underline px-1.5 py-0.5">
                                    Today
                                </button>
                                <button type="button" @click.stop="nextPickerMonth()"
                                        class="w-7 h-7 rounded-lg hover:bg-[#ECEEF3] text-[#5D6375] hover:text-[#1A1E2C] flex items-center justify-center transition">
                                    <i class="fas fa-chevron-right text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Weekday labels -->
                        <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold text-[#5D6375] uppercase">
                            <span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span class="text-indigo-600">Sa</span><span class="text-rose-600">Su</span>
                        </div>

                        <!-- Day Cells Grid -->
                        <div class="grid grid-cols-7 gap-1">
                            <template x-for="item in pickerCalendarDays" :key="item.date">
                                <button type="button"
                                        @click.stop="selectPickerDate(item.date)"
                                        class="h-8 rounded-lg text-xs font-semibold flex flex-col items-center justify-center relative transition-all"
                                        :class="{
                                            'text-[#A0A4B0] opacity-35 hover:opacity-100': !item.isCurrentMonth,
                                            'bg-[#B86A00] text-white font-bold shadow-sm': item.isSelected,
                                            'bg-amber-100 text-amber-900 font-bold border border-amber-300': item.holiday && !item.isSelected,
                                            'text-indigo-700 bg-indigo-50/60 font-semibold': item.isWeekend && !item.holiday && !item.isSelected,
                                            'text-[#1A1E2C] hover:bg-[#ECEEF3]': item.isCurrentMonth && !item.isSelected && !item.holiday && !item.isWeekend,
                                            'ring-2 ring-[#B86A00]/40': item.isToday && !item.isSelected
                                        }"
                                        :title="item.holiday ? (item.holiday.name + ' (' + item.date + ')') : (item.isWeekend ? 'Weekend (' + item.date + ')' : item.date)">
                                    <span x-text="item.day"></span>
                                    <template x-if="item.holiday">
                                        <span class="w-1.5 h-1.5 rounded-full absolute bottom-1"
                                              :class="item.isSelected ? 'bg-white' : 'bg-amber-600'"></span>
                                    </template>
                                </button>
                            </template>
                        </div>

                        <!-- Legend & Help -->
                        <div class="pt-2 border-t border-[#ECEEF3] flex flex-wrap items-center justify-between text-[10px] text-[#5D6375]">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <span>Holiday</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                                <span>Weekend</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#B86A00]"></span>
                                <span>Selected</span>
                            </div>
                        </div>
                    </div>

                    <!-- Public Holiday Checkbox (Weekend Mode) -->
                    <div x-show="type === 'weekend'" x-cloak class="pt-1">
                        <label class="inline-flex items-center gap-2 p-2 bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg cursor-pointer text-xs font-semibold text-[#1A1E2C] w-full">
                            <input type="checkbox" name="is_holiday" value="1" x-model="isHoliday" @change="checkEligibility()"
                                   class="rounded text-[#B86A00] focus:ring-[#B86A00] w-4 h-4">
                            <span>This is a public holiday</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Job Number Selection (Select2 Multi-select) -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label for="job_number_select" class="text-xs font-bold text-[#5D6375] uppercase tracking-wider">Job Number &amp; Project</label>
                    <span class="text-[11px] text-[#5D6375]">Select one or multiple jobs</span>
                </div>
                <div x-ignore class="relative">
                    @php
                        $oldJobs = (array)(old('job_numbers') ?: (old('job_number') ? array_map('trim', explode(',', old('job_number'))) : []));
                        $initialSelectedJobs = [];
                        foreach ($oldJobs as $oj) {
                            $found = collect($jobs)->firstWhere('no', $oj);
                            if ($found) {
                                $initialSelectedJobs[] = ['no' => $found['no'], 'label' => $found['label']];
                            } else {
                                $initialSelectedJobs[] = ['no' => $oj, 'label' => ''];
                            }
                        }
                    @endphp
                    <select id="job_number_select" name="job_numbers[]" multiple="multiple"
                            class="w-full" style="width: 100%;">
                        @foreach($jobs as $job)
                            <option value="{{ $job['no'] }}" data-label="{{ $job['label'] }}"
                                {{ in_array($job['no'], $oldJobs) ? 'selected' : '' }}>
                                {{ $job['no'] }}{{ !empty($job['label']) ? ' · ' . $job['label'] : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Read-only auto-populated fields: one job number and description at a time, one by one down -->
                <div class="space-y-2 pt-1">
                    <template x-if="selectedJobs.length === 0">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <input type="text" readonly
                                       placeholder="Selected job number appears here"
                                       class="w-full bg-[#ECEEF3] border border-[#D9DCE5] rounded-lg px-3 py-2 text-xs font-mono font-bold text-[#5D6375] cursor-not-allowed select-all focus:outline-none">
                            </div>
                            <div>
                                <input type="text" readonly
                                       placeholder="Selected project / client description"
                                       class="w-full bg-[#ECEEF3] border border-[#D9DCE5] rounded-lg px-3 py-2 text-xs font-medium text-[#5D6375] cursor-not-allowed select-all focus:outline-none">
                            </div>
                        </div>
                    </template>

                    <template x-for="(job, index) in selectedJobs" :key="job.no">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <input type="text" readonly :value="job.no"
                                       class="w-full bg-[#ECEEF3] border border-[#D9DCE5] rounded-lg px-3 py-2 text-xs font-mono font-bold text-[#1A1E2C] cursor-not-allowed select-all focus:outline-none"
                                       :title="job.no">
                            </div>
                            <div>
                                <input type="text" readonly :value="job.label || 'No project description'"
                                       class="w-full bg-[#ECEEF3] border border-[#D9DCE5] rounded-lg px-3 py-2 text-xs font-medium text-[#1A1E2C] cursor-not-allowed select-all focus:outline-none"
                                       :title="job.label">
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Hidden inputs for backend form submission -->
                <input type="hidden" name="job_number" id="job_number" :value="jobNumber" required>
                <input type="hidden" name="job_label" id="job_label" :value="jobLabel">
            </div>

            <!-- Reason Field -->
            <div class="space-y-1.5">
                <label for="reason" class="text-xs font-bold text-[#5D6375] uppercase tracking-wider">Reason <span class="text-[11px] font-normal text-[#8A90A2] lowercase">(optional)</span></label>
                <textarea name="reason" id="reason" maxlength="600" rows="3" x-model="reason"
                          :placeholder="type === 'weekend' ? 'What needs to be done that day, and why it cannot wait until the next working day.' : 'What needs to be finished tonight, and why it cannot wait until tomorrow.'"
                          class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg p-3 text-sm text-[#1A1E2C] focus:outline-none focus:ring-2 focus:ring-[#B86A00]"></textarea>
                <div class="flex justify-between text-[11px] text-[#5D6375]">
                    <span>Clear, concise explanation for your HOD.</span>
                    <span x-text="(600 - (reason ? reason.length : 0)) + ' characters remaining'"></span>
                </div>
            </div>

            <!-- Time Fields -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 items-start">
                <!-- Start time (Weekend Only) -->
                <div x-show="type === 'weekend'" x-cloak class="space-y-1.5">
                    <div class="flex items-center justify-between min-h-[20px]">
                        <label for="start_time" class="text-xs font-bold text-[#5D6375] uppercase tracking-wider block">Start Time</label>
                    </div>
                    <input type="time" name="start_time" id="start_time" x-model="startTime"
                           @change="onStartTimeChange()"
                           @input="onStartTimeChange()"
                           :required="type === 'weekend'"
                           class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-3 py-2.5 text-sm font-medium text-[#1A1E2C] focus:outline-none focus:ring-2 focus:ring-[#B86A00] h-[42px]">
                </div>

                <!-- Finish time -->
                <div class="space-y-1.5" :class="type === 'late' ? 'sm:col-span-2' : ''">
                    <div class="flex items-center justify-between min-h-[20px]">
                        <label for="finish_time" class="text-xs font-bold text-[#5D6375] uppercase tracking-wider block"
                               x-text="type === 'weekend' ? 'End Time' : 'Expected Finish Time'"></label>
                        <template x-if="type === 'late'">
                            <span class="text-[11px] font-semibold text-[#B86A00] bg-[#FBEBD2] px-2 py-0.5 rounded-md border border-[#F1C888]">
                                Allowed: 8:00 PM – 8:00 AM
                            </span>
                        </template>
                    </div>

                    <input type="time" name="finish_time" id="finish_time" required
                           x-model="finishTime"
                           @change="onFinishTimeChange()"
                           @input="onFinishTimeChange()"
                           class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-3 py-2.5 text-sm font-medium text-[#1A1E2C] focus:outline-none focus:ring-2 focus:ring-[#B86A00] h-[42px]"
                           :class="timeWarning ? 'border-rose-500 ring-1 ring-rose-500' : ''">

                    <span class="text-xs text-[#5D6375] block" x-show="type === 'late' && !timeWarning">
                        Use the time you expect to leave the office (between 8:00 PM and 8:00 AM).
                    </span>

                    <div x-show="timeWarning" x-cloak class="p-2.5 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-700 font-semibold flex items-center gap-2">
                        <i class="fas fa-exclamation-circle text-rose-600"></i>
                        <span x-text="timeWarning"></span>
                    </div>
                </div>
            </div>

            <!-- Weekend & Holiday Work Mode: WFH Option -->
            <div x-show="type === 'weekend'" x-cloak class="p-4 bg-[#F4F5F8] border border-[#D9DCE5] rounded-xl flex items-center justify-between gap-4 transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-600 flex items-center justify-center text-lg flex-shrink-0">
                        <i class="fas fa-laptop-house"></i>
                    </div>
                    <div>
                        <b class="text-sm text-[#1A1E2C] block font-semibold">Working from Home (WFH)</b>
                        <small class="text-xs text-[#5D6375]">Check this if working remotely for this weekend or holiday shift</small>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer select-none">
                    <input type="checkbox" name="is_wfh" value="1" x-model="isWfh" @change="onWfhChange()"
                           class="w-5 h-5 rounded text-[#B86A00] focus:ring-[#B86A00] border-[#D9DCE5]">
                    <span class="ml-2 text-xs font-bold text-[#1A1E2C]">WFH</span>
                </label>
            </div>

            <!-- Allowances Checkboxes (Hidden when WFH on weekend/holiday) -->
            <div class="space-y-2" x-show="type === 'late' || !isWfh" x-cloak>
                <span class="text-xs font-bold text-[#5D6375] uppercase tracking-wider block">Allowances</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 border border-[#D9DCE5] rounded-xl p-3.5 cursor-pointer transition-all bg-[#F4F5F8]"
                           :class="{ 'opacity-50 cursor-not-allowed': !isEligible }">
                        <input type="checkbox" name="meal" value="1" x-model="meal" :disabled="!isEligible"
                               @change="onAllowanceToggle('meal')"
                               class="w-5 h-5 rounded text-[#B86A00] focus:ring-[#B86A00]">
                        <div>
                            <b class="text-sm text-[#1A1E2C] block">Meal allowance</b>
                            <small class="text-xs text-[#5D6375]">Rs. 1,000 allowance for dinner or weekend work</small>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 border border-[#D9DCE5] rounded-xl p-3.5 cursor-pointer transition-all bg-[#F4F5F8]"
                           :class="{ 'opacity-50 cursor-not-allowed': !isEligible }">
                        <input type="checkbox" name="taxi" value="1" x-model="taxi" :disabled="!isEligible"
                               @change="onAllowanceToggle('taxi')"
                               class="w-5 h-5 rounded text-[#B86A00] focus:ring-[#B86A00]">
                        <div>
                            <b class="text-sm text-[#1A1E2C] block">PickMe ride home</b>
                            <small class="text-xs text-[#5D6375]">Company-covered ride home past 8:00 PM</small>
                        </div>
                    </label>
                </div>

                <!-- Attached Petty Cash Summary Card (Shows when Meal or PickMe is selected) -->
                <div x-show="meal || taxi" x-cloak class="p-3.5 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-300 rounded-xl space-y-2.5 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-base shadow-xs flex-shrink-0">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-900">Petty Cash Request Attached</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#B86A00] text-white">Work Tracker</span>
                                </div>
                                <div class="text-[11px] text-[#5D6375] mt-0.5">
                                    Total: <b class="text-[#1A1E2C]" x-text="'Rs. ' + pettyCashTotalFormatted"></b>
                                    &bull; <span x-text="pettyCashItems.length + ' item(s)'"></span>
                                    &bull; <span x-text="proofsCountText"></span>
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="openPettyCashModal()"
                                class="px-3.5 py-2 bg-white border border-amber-300 rounded-lg text-xs font-bold text-[#B86A00] hover:bg-amber-100 transition shadow-xs flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer">
                            <i class="fas fa-edit text-xs"></i>
                            <span>Edit Petty Cash &amp; Proofs</span>
                        </button>
                    </div>
                </div>

                <p class="text-xs text-rose-600 font-medium" x-show="eligibilityWarning" x-text="eligibilityWarning"></p>
            </div>

            <!-- WFH Notice when WFH is checked on weekend/holiday -->
            <div x-show="type === 'weekend' && isWfh" x-cloak class="p-3.5 bg-indigo-50/70 border border-indigo-200 rounded-xl text-xs text-indigo-900 flex items-center gap-2.5">
                <i class="fas fa-info-circle text-indigo-600 text-sm flex-shrink-0"></i>
                <span>Working from Home (WFH): Meal allowance and PickMe ride home options are excluded for remote work.</span>
            </div>

            <!-- Weekend Only: Lieu Leave Selection -->
            <div x-show="type === 'weekend'" x-cloak class="space-y-2">
                <div class="flex items-center justify-between">
                    <label for="lieu" class="text-xs font-bold text-[#5D6375] uppercase tracking-wider">Compensatory Lieu Leave</label>
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full"
                          :class="weekendDurationHours >= 12 ? 'bg-purple-100 text-purple-800 border border-purple-200' : (weekendDurationHours >= 8 ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : (weekendDurationHours >= 4 ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-amber-100 text-amber-800 border border-amber-200'))">
                        Shift: <span x-text="weekendDurationText"></span> (<span x-text="weekendDurationHours"></span> hrs)
                    </span>
                </div>

                <!-- Warning when shift is less than 4 hours -->
                <div x-show="weekendDurationHours < 4" class="p-3 bg-amber-50 border border-amber-300 rounded-xl text-xs text-amber-900 font-medium flex items-center gap-2.5">
                    <i class="fas fa-exclamation-triangle text-amber-600 text-base flex-shrink-0"></i>
                    <div>
                        <strong class="block text-amber-950 font-bold">Need minimum 4 hours to get a half day leave.</strong>
                        <span class="text-amber-800">Current shift duration is <span class="font-bold font-mono" x-text="weekendDurationText"></span> (<span x-text="weekendDurationHours"></span> hrs). Less than 4 hours cannot be saved as leave.</span>
                    </div>
                </div>

                <!-- Entitlement badge for eligible shifts -->
                <div x-show="weekendDurationHours >= 4" class="p-3 rounded-xl text-xs font-medium flex items-center gap-2.5 border"
                     :class="weekendDurationHours >= 12 ? 'bg-purple-50 border-purple-200 text-purple-900' : (weekendDurationHours >= 8 ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-blue-50 border-blue-200 text-blue-900')">
                    <i class="fas fa-check-circle text-base flex-shrink-0"
                       :class="weekendDurationHours >= 12 ? 'text-purple-600' : (weekendDurationHours >= 8 ? 'text-emerald-600' : 'text-blue-600')"></i>
                    <div>
                        <span class="font-bold block" x-text="weekendDurationHours >= 12 ? 'Entitled: 1 Day & Half Day Leave (1.5 days)' : (weekendDurationHours >= 8 ? 'Entitled: Full Day Leave (1.0 day)' : 'Entitled: Half Day Leave (0.5 day)')"></span>
                        <span class="text-[11px] opacity-80">Automatically calculated based on your shift hours. Must be taken within 30 days.</span>
                    </div>
                </div>

                <select name="lieu" id="lieu" x-model="lieu"
                        class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-3 py-2.5 text-sm font-medium text-[#1A1E2C] focus:outline-none focus:ring-2 focus:ring-[#B86A00]"
                        :class="weekendDurationHours < 4 ? 'opacity-60 cursor-not-allowed bg-gray-100' : ''"
                        :disabled="weekendDurationHours < 4">
                    <option value="" :selected="weekendDurationHours < 4">No Leave (Minimum 4 hours required)</option>
                    <option value="half" :disabled="weekendDurationHours < 4">Half Day Leave (0.5 day - Min 4 hrs)</option>
                    <option value="full" :disabled="weekendDurationHours < 8">Full Day Leave (1.0 day - Min 8 hrs)</option>
                    <option value="one_and_half" :disabled="weekendDurationHours < 12">1 Day & Half Day Leave (1.5 days - Min 12 hrs)</option>
                </select>
                <!-- Hidden input if disabled to ensure empty string is submitted -->
                <template x-if="weekendDurationHours < 4">
                    <input type="hidden" name="lieu" value="">
                </template>
                <span class="text-xs text-[#5D6375] block">Policy: &lt;4 hrs = No leave &bull; 4+ hrs = Half day &bull; 8+ hrs = Full day &bull; 12+ hrs = 1.5 days</span>
            </div>

            <!-- Weekday Only: Expected Next Day Clock-in Readout & Policy Table -->
            <div x-show="type === 'late'" class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-[#5D6375] uppercase tracking-wider block">Expected Clock-in Next Day</span>
                    <button type="button" @click="instructionsModalOpen = true"
                            class="text-[11px] font-semibold text-[#B86A00] hover:underline flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-book-open"></i>
                        <span>Policy 3.11 Instructions</span>
                    </button>
                </div>

                <!-- Active Readout Card -->
                <div class="bg-[#ECEEF3] rounded-xl p-3.5 border border-[#D9DCE5] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-base font-bold shadow-sm transition-all"
                             :class="{
                                 'bg-[#B86A00] text-white': nextDayReportKey === '10am' || nextDayReportKey === '11am',
                                 'bg-emerald-600 text-white': nextDayReportKey === 'half' || nextDayReportKey === 'full',
                                 'bg-slate-200 text-slate-700': nextDayReportKey === 'normal'
                             }">
                            <i class="fas" :class="nextDayReportKey === 'half' || nextDayReportKey === 'full' ? 'fa-umbrella-beach' : 'fa-clock'"></i>
                        </div>
                        <div>
                            <div class="text-[11px] text-[#5D6375] font-bold uppercase tracking-wider">Time to report to work</div>
                            <div class="text-[#1A1E2C] font-extrabold text-base flex items-center gap-2">
                                <span x-text="nextDayReportText"></span>
                                <span class="text-xs font-normal text-[#5D6375]" x-show="nextDayDateText" x-text="'(' + nextDayDateText + ')'"></span>
                            </div>
                        </div>
                    </div>
                    <div class="text-left sm:text-right sm:border-l sm:border-[#D9DCE5] sm:pl-3">
                        <span class="inline-block px-2.5 py-1 rounded-md text-xs font-semibold"
                              :class="{
                                  'bg-amber-100 text-[#B86A00]': nextDayReportKey === '10am' || nextDayReportKey === '11am',
                                  'bg-emerald-100 text-emerald-800': nextDayReportKey === 'half' || nextDayReportKey === 'full',
                                  'bg-slate-200 text-slate-700': nextDayReportKey === 'normal'
                              }"
                              x-text="nextDayPolicyText">
                        </span>
                    </div>
                </div>

                <!-- Official Weekday Policy Table Card -->
                <div class="bg-white rounded-xl border border-[#D9DCE5] overflow-hidden shadow-sm">
                    <div class="bg-[#F4F5F8] px-3.5 py-2 border-b border-[#D9DCE5] flex items-center justify-between font-bold text-xs text-[#1A1E2C]">
                        <span class="flex items-center gap-1.5">
                            <i class="fas fa-table-list text-[#B86A00]"></i>
                            <span>Sign Out Time</span>
                        </span>
                        <span>Time to report to work</span>
                    </div>
                    <div class="divide-y divide-[#ECEEF3] text-xs">
                        <!-- Bracket 1: 9.00 p.m. - 10.30 p.m. -->
                        <div class="px-3.5 py-2 flex items-center justify-between transition-colors"
                             :class="nextDayReportKey === '10am' ? 'bg-amber-50/80 font-bold text-[#B86A00]' : 'text-[#5D6375]'">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full" :class="nextDayReportKey === '10am' ? 'bg-[#B86A00]' : 'bg-[#D9DCE5]'"></span>
                                <span>Between 9.00 p.m. - 10.30 p.m.</span>
                            </div>
                            <div class="flex items-center gap-2 font-semibold" :class="nextDayReportKey === '10am' ? 'text-[#B86A00]' : 'text-[#1A1E2C]'">
                                <span>10.00 a.m.</span>
                                <i class="fas fa-check-circle text-xs text-[#B86A00]" x-show="nextDayReportKey === '10am'"></i>
                            </div>
                        </div>

                        <!-- Bracket 2: 10.31 p.m. – 12.00 p.m. (Midnight) -->
                        <div class="px-3.5 py-2 flex items-center justify-between transition-colors"
                             :class="nextDayReportKey === '11am' ? 'bg-amber-50/80 font-bold text-[#B86A00]' : 'text-[#5D6375]'">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full" :class="nextDayReportKey === '11am' ? 'bg-[#B86A00]' : 'bg-[#D9DCE5]'"></span>
                                <span>Between 10.31 p.m. – 12.00 p.m.</span>
                            </div>
                            <div class="flex items-center gap-2 font-semibold" :class="nextDayReportKey === '11am' ? 'text-[#B86A00]' : 'text-[#1A1E2C]'">
                                <span>11.00 a.m.</span>
                                <i class="fas fa-check-circle text-xs text-[#B86A00]" x-show="nextDayReportKey === '11am'"></i>
                            </div>
                        </div>

                        <!-- Bracket 3: 12.01 – 3.00 a.m. -->
                        <div class="px-3.5 py-2 flex items-center justify-between transition-colors"
                             :class="nextDayReportKey === 'half' ? 'bg-emerald-50/80 font-bold text-emerald-800' : 'text-[#5D6375]'">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full" :class="nextDayReportKey === 'half' ? 'bg-emerald-600' : 'bg-[#D9DCE5]'"></span>
                                <span>Between 12.01 – 3.00 a.m.</span>
                            </div>
                            <div class="flex items-center gap-2 font-semibold" :class="nextDayReportKey === 'half' ? 'text-emerald-800' : 'text-[#1A1E2C]'">
                                <span>Half day off</span>
                                <i class="fas fa-check-circle text-xs text-emerald-700" x-show="nextDayReportKey === 'half'"></i>
                            </div>
                        </div>

                        <!-- Bracket 4: Beyond 3.00 a.m. -->
                        <div class="px-3.5 py-2 flex items-center justify-between transition-colors"
                             :class="nextDayReportKey === 'full' ? 'bg-emerald-50/80 font-bold text-emerald-800' : 'text-[#5D6375]'">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full" :class="nextDayReportKey === 'full' ? 'bg-emerald-600' : 'bg-[#D9DCE5]'"></span>
                                <span>Beyond 3.00 a.m.</span>
                            </div>
                            <div class="flex items-center gap-2 font-semibold" :class="nextDayReportKey === 'full' ? 'text-emerald-800' : 'text-[#1A1E2C]'">
                                <span>Full day off</span>
                                <i class="fas fa-check-circle text-xs text-emerald-700" x-show="nextDayReportKey === 'full'"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <span class="text-[11px] text-[#5D6375] block leading-tight">
                    * Based on your finish time under the Weekday Late Work policy. Requires HOD approval &amp; a Duty Leave entry. Both in/out fingerprint records are mandatory.
                </span>
            </div>

            <!-- Submit Section & 3 PM Cutoff Notice -->
            <div class="pt-4 border-t border-[#D9DCE5] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                    <button type="submit" id="submitBtn" :disabled="!isFormValid"
                            class="bg-[#B86A00] hover:bg-[#995800] disabled:opacity-50 text-white font-bold px-6 py-3 rounded-xl transition shadow-md flex items-center gap-2 cursor-pointer">
                        <i class="fas fa-paper-plane text-xs"></i>
                        <span>Send to HOD</span>
                    </button>
                </div>
                <div class="text-xs text-[#5D6375]" x-show="cutoffNotice" x-text="cutoffNotice" :class="isPastCutoff ? 'text-rose-600 font-semibold' : ''"></div>
            </div>

            <!-- Petty Cash Request Popup Dialog (Available when Meal or PickMe is selected) -->
            <div x-show="pettyCashModalOpen" x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-6"
                 @keydown.escape.window="closePettyCashModal()">
                <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden my-auto"
                     @click.away="closePettyCashModal()">
                    
                    <!-- Modal Header -->
                    <div class="px-6 py-4 bg-white border-b border-gray-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-orange-500 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base sm:text-lg font-bold text-gray-900">Petty Cash Request Details</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#B86A00] text-white">Work Tracker</span>
                                </div>
                                <p class="text-xs text-gray-500">Auto-filled based on your Work Tracker shift allowance selection</p>
                            </div>
                        </div>
                        <button type="button" @click="closePettyCashModal()" class="text-gray-400 hover:text-gray-600 transition p-1.5 rounded-lg hover:bg-gray-100 cursor-pointer">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>

                    <!-- Scrollable Modal Body -->
                    <div class="p-6 max-h-[calc(85vh-130px)] overflow-y-auto space-y-5">
                        
                        <!-- HOD Auto-Approval Banner -->
                        <div class="p-3.5 bg-blue-50/90 border border-blue-200 rounded-xl text-xs text-blue-900 flex items-start gap-3">
                            <i class="fas fa-shield-alt text-blue-600 text-base mt-0.5 flex-shrink-0"></i>
                            <div>
                                <span class="font-bold block text-blue-950">Automatic HOD Approval Linked</span>
                                <span>When your HOD approves your Work Tracker shift, the HOD approval stage of this Petty Cash request will automatically be approved and routed straight to Finance / Super Admin for payment.</span>
                            </div>
                        </div>

                        <!-- Read-only Metadata: HOD & Job Numbers -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-xl border border-gray-200 text-xs">
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">HOD Associated With</label>
                                <div class="flex items-center gap-2 px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-800 font-medium">
                                    <i class="fas fa-user-shield text-gray-400"></i>
                                    <span x-text="hodHint ? hodHint.replace('Request goes to HOD: ', '') : '{{ $userAssignedHod ? $userAssignedHod->name : "Department HOD" }}'"></span>
                                </div>
                            </div>
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Job Number(s)</label>
                                <div class="flex items-center gap-2 px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-800 font-medium">
                                    <i class="fas fa-briefcase text-gray-400"></i>
                                    <span x-text="selectedJobs && selectedJobs.length > 0 ? selectedJobs.map(j => j.no).join(', ') : (jobNumber || 'Shift Reference')"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Expense Line Items Section -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <label class="block text-sm font-bold text-gray-900">
                                        Expense Line Items <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="text-xs text-gray-500">Details for Meal allowance or PickMe reimbursement</span>
                                </div>
                                <button type="button" @click="addPettyCashItem()"
                                        class="text-xs bg-[#B86A00] text-white px-3 py-1.5 rounded-lg hover:bg-[#995800] transition font-semibold flex items-center gap-1 shadow-xs">
                                    <i class="fas fa-plus text-[10px]"></i>
                                    <span>Add Line Item</span>
                                </button>
                            </div>

                            <div class="space-y-3">
                                <template x-for="(item, index) in pettyCashItems" :key="index">
                                    <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200 space-y-2">
                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center">
                                            <!-- Category -->
                                            <div class="col-span-1 md:col-span-4">
                                                <label class="block text-[11px] font-semibold text-gray-600 mb-0.5 md:hidden">Category</label>
                                                <select :name="'petty_cash_items[' + index + '][expense_category_id]'"
                                                        x-model="item.expense_category_id"
                                                        @change="onCategoryChange(item)"
                                                        class="w-full rounded-lg border-gray-300 text-xs focus:ring-[#B86A00] focus:border-[#B86A00] bg-white">
                                                    <option value="">Select Category</option>
                                                    <template x-for="cat in expenseCategories" :key="cat.id">
                                                        <option :value="cat.id" :selected="String(cat.id) === String(item.expense_category_id)" x-text="cat.name"></option>
                                                    </template>
                                                </select>
                                            </div>

                                            <!-- Amount -->
                                            <div class="col-span-1 md:col-span-3">
                                                <label class="block text-[11px] font-semibold text-gray-600 mb-0.5 md:hidden">Amount (LKR)</label>
                                                <div class="relative">
                                                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-xs text-gray-400 font-semibold">Rs.</span>
                                                    <input type="number" step="0.01" min="0"
                                                           :name="'petty_cash_items[' + index + '][amount]'"
                                                           x-model="item.amount"
                                                           placeholder="Amount"
                                                           class="w-full pl-8 rounded-lg border-gray-300 text-xs focus:ring-[#B86A00] focus:border-[#B86A00] bg-white font-medium">
                                                </div>
                                            </div>

                                            <!-- Note / Details -->
                                            <div class="col-span-1 md:col-span-4">
                                                <label class="block text-[11px] font-semibold text-gray-600 mb-0.5 md:hidden">Note / Details</label>
                                                <input type="text"
                                                       :name="'petty_cash_items[' + index + '][description]'"
                                                       x-model="item.description"
                                                       placeholder="Note / Details (e.g. Dinner, PickMe Ride)"
                                                       class="w-full rounded-lg border-gray-300 text-xs focus:ring-[#B86A00] focus:border-[#B86A00] bg-white">
                                            </div>

                                            <!-- Delete button -->
                                            <div class="col-span-1 md:col-span-1 text-right">
                                                <button type="button" @click="removePettyCashItem(index)"
                                                        class="text-rose-500 hover:text-rose-700 p-1.5 rounded hover:bg-rose-50 transition"
                                                        title="Remove Item">
                                                    <i class="fas fa-trash-alt text-sm"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Meal Allowance Attendees (Matches standard Petty Cash format) -->
                                        <div x-show="isMealCategory(item.expense_category_id)" class="pt-2 mt-2 border-t border-gray-200/80">
                                            <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-lg text-xs">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="font-bold text-amber-900 flex items-center gap-1.5">
                                                        <i class="fas fa-utensils text-[#B86A00]"></i> Meal Allowance Attendees / People Names (Max 5):
                                                    </span>
                                                    <span class="text-[11px] text-amber-700">Enter names of people who attended</span>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-5 gap-2">
                                                    <input type="text" :name="'petty_cash_items[' + index + '][attendees][]'" :disabled="!isMealCategory(item.expense_category_id)" x-model="item.attendees[0]" placeholder="Person 1 Name" class="w-full rounded-md border-amber-200 text-xs focus:ring-[#B86A00] bg-white">
                                                    <input type="text" :name="'petty_cash_items[' + index + '][attendees][]'" :disabled="!isMealCategory(item.expense_category_id)" x-model="item.attendees[1]" placeholder="Person 2 Name" class="w-full rounded-md border-amber-200 text-xs focus:ring-[#B86A00] bg-white">
                                                    <input type="text" :name="'petty_cash_items[' + index + '][attendees][]'" :disabled="!isMealCategory(item.expense_category_id)" x-model="item.attendees[2]" placeholder="Person 3 Name" class="w-full rounded-md border-amber-200 text-xs focus:ring-[#B86A00] bg-white">
                                                    <input type="text" :name="'petty_cash_items[' + index + '][attendees][]'" :disabled="!isMealCategory(item.expense_category_id)" x-model="item.attendees[3]" placeholder="Person 4 Name" class="w-full rounded-md border-amber-200 text-xs focus:ring-[#B86A00] bg-white">
                                                    <input type="text" :name="'petty_cash_items[' + index + '][attendees][]'" :disabled="!isMealCategory(item.expense_category_id)" x-model="item.attendees[4]" placeholder="Person 5 Name" class="w-full rounded-md border-amber-200 text-xs focus:ring-[#B86A00] bg-white">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Extra Notes / Remarks -->
                        <div>
                            <label class="block text-sm font-bold text-gray-900 mb-1">Extra Notes / Remarks</label>
                            <textarea name="petty_cash_extra_notes" x-model="pettyCashExtraNotes" rows="2"
                                      placeholder="Optional extra remarks or justification for this Petty Cash voucher..."
                                      class="w-full rounded-xl border-gray-300 text-xs focus:border-[#B86A00] focus:ring-[#B86A00]"></textarea>
                        </div>

                        <!-- Proofs of Expenditure (Matches standard Petty Cash format) -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <label class="block text-sm font-bold text-gray-900">
                                        Proofs of Expenditure
                                    </label>
                                    <p class="text-xs text-gray-500">Attach receipts, bills, or PickMe ride screenshots</p>
                                </div>
                                <button type="button" @click="addProofInput()"
                                        class="text-xs bg-[#B86A00] text-white px-3 py-1.5 rounded-lg hover:bg-[#995800] transition font-semibold flex items-center gap-1 shadow-xs cursor-pointer">
                                    <i class="fas fa-plus text-[10px]"></i>
                                    <span>Add File</span>
                                </button>
                            </div>

                            <div class="space-y-2">
                                <template x-for="(proof, pIndex) in proofInputs" :key="proof.id">
                                    <div class="flex items-center gap-2 p-2 bg-gray-50 border border-gray-200 rounded-xl">
                                        <input type="file" name="petty_cash_proofs[]" accept="image/*,.pdf,.doc,.docx"
                                               @change="onProofFileSelected($event, pIndex)"
                                               class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-[#B86A00] hover:file:bg-amber-100">
                                        <button type="button" x-show="proofInputs.length > 1" @click="removeProofInput(pIndex)"
                                                class="text-rose-500 hover:text-rose-700 p-1.5 rounded hover:bg-rose-50 transition flex-shrink-0 cursor-pointer"
                                                title="Remove File">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <p class="text-[11px] text-gray-400 mt-1.5 flex items-center gap-1">
                                <i class="fas fa-info-circle text-[10px]"></i>
                                <span>Accepted formats: PNG, JPG, PDF, DOC, DOCX (Max 10MB each). Click "Add File" to attach multiple proofs.</span>
                            </p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="text-sm text-gray-700">
                            <span>Total Amount: </span>
                            <span class="font-extrabold text-base text-[#1A1E2C]" x-text="'Rs. ' + pettyCashTotalFormatted"></span>
                            <span class="text-xs text-gray-500 ml-2" x-text="'(' + pettyCashItems.length + ' item' + (pettyCashItems.length === 1 ? '' : 's') + ')'"></span>
                        </div>

                        <!-- Right Side: Done Button -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
                            <button type="button" @click="confirmPettyCashModal()"
                                    class="w-full sm:w-auto px-5 py-2.5 bg-[#B86A00] hover:bg-[#995800] text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center justify-center gap-2 cursor-pointer">
                                <i class="fas fa-check"></i>
                                <span>Done &amp; Keep Attached</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </section>

    <!-- TAB 2: MY REQUESTS -->
    <section x-show="activeTab === 'mine'" x-cloak class="space-y-4">
        @if($myRequests->isEmpty())
            <div class="bg-white border border-[#D9DCE5] rounded-2xl p-12 text-center text-[#5D6375]">
                <div class="w-16 h-16 rounded-full bg-[#F4F5F8] flex items-center justify-center mx-auto mb-3 text-2xl text-[#B86A00]">
                    <i class="fas fa-moon"></i>
                </div>
                <h3 class="font-bold text-base text-[#1A1E2C] mb-1">No requests yet</h3>
                <p class="text-xs max-w-sm mx-auto mb-5">You haven't sent any late or weekend work requests yet. Click below to submit your first request.</p>
                <button type="button" @click="setTab('new')" class="bg-[#B86A00] text-white text-xs font-bold px-4 py-2.5 rounded-lg shadow-sm">
                    Create New Request
                </button>
            </div>
        @else
            <div class="space-y-3">
                @foreach($myRequests as $req)
                    <article class="lw-card p-4 sm:p-5 flex flex-col gap-3">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <div class="space-y-1">
                                @foreach($req->job_items as $item)
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="lw-mono bg-[#ECEEF3] text-xs font-semibold px-2 py-0.5 rounded text-[#1A1E2C]">{{ $item['number'] }}</span>
                                        @if(!empty($item['label']))
                                            <span class="text-xs font-semibold text-[#1A1E2C]">{{ $item['label'] }}</span>
                                        @endif
                                        @if($loop->last)
                                            <span class="text-xs text-[#5D6375]">· {{ $req->department_name }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="lw-chip {{ $req->status }}">
                                    {{ ucfirst($req->status) }}
                                </span>
                                @if(auth()->user()->canEditDeleteLateWork())
                                    <button type="button"
                                            @click="openEditModal({{ json_encode($req->toEditArray()) }})"
                                            class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg transition-colors inline-flex items-center gap-1 shadow-xs cursor-pointer"
                                            title="Edit Request">
                                        <i class="fas fa-edit text-[11px]"></i> Edit
                                    </button>
                                    <form action="{{ route('late-work.destroy', $req) }}" method="POST" class="inline" onsubmit="return confirmDeleteLateWork(event, this, '{{ $req->reference_number }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg transition-colors inline-flex items-center gap-1 shadow-xs cursor-pointer"
                                                title="Delete Request">
                                            <i class="fas fa-trash-alt text-[11px]"></i> Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <!-- Meta tags -->
                        <div class="flex flex-wrap items-center gap-1.5 text-xs text-[#5D6375]">
                            @if($req->type === 'weekend')
                                <span class="lw-tag wk">
                                    <i class="fas fa-calendar-day text-[10px]"></i>
                                    {{ $req->is_holiday ? 'Public holiday' : 'Weekend' }}
                                </span>
                            @else
                                <span class="lw-tag">
                                    <i class="fas fa-moon text-[10px] text-amber-500"></i> Late night
                                </span>
                            @endif

                            <span class="lw-tag">
                                <i class="far fa-calendar-alt text-[10px]"></i>
                                {{ $req->date ? $req->date->format('D, d M') : '' }}
                            </span>

                            <span class="lw-tag">
                                <i class="far fa-clock text-[10px]"></i>
                                @if($req->type === 'weekend')
                                    {{ $req->start_time ? date('g:i A', strtotime($req->start_time)) : '' }} – {{ $req->finish_time ? date('g:i A', strtotime($req->finish_time)) : '' }}
                                @else
                                    Until {{ $req->finish_time ? date('g:i A', strtotime($req->finish_time)) : '' }}
                                @endif
                            </span>

                            @if($req->is_wfh)
                                <span class="lw-tag wfh"><i class="fas fa-laptop-house mr-1"></i>WFH</span>
                            @endif

                            @if($req->meal)
                                <span class="lw-tag">Meal</span>
                            @endif

                            @if($req->taxi)
                                <span class="lw-tag">PickMe</span>
                            @endif

                            @if($req->pettyCashRequest)
                                <a href="{{ route('petty-cash.index') }}" 
                                   class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300 hover:bg-amber-200 transition"
                                   title="Linked Petty Cash Request: {{ $req->pettyCashRequest->reference_number }}">
                                    <i class="fas fa-wallet text-[#B86A00] text-[10px]"></i>
                                    <span>Petty Cash: {{ $req->pettyCashRequest->reference_number }}</span>
                                    <span class="text-[10px] uppercase font-mono px-1 py-0.2 rounded bg-white text-gray-700">
                                        {{ str_replace('_', ' ', $req->pettyCashRequest->status) }}
                                    </span>
                                </a>
                            @endif

                            @if($req->lieu)
                                <span class="lw-tag">Lieu: {{ $req->lieu_label }}</span>
                            @endif

                            @if($req->type !== 'weekend' && $req->next_day)
                                @php
                                    $nd = \App\Models\LateWorkRequest::calculateNextDayReport($req->finish_time);
                                @endphp
                                <span class="lw-tag font-semibold text-[#B86A00]">
                                    {{ in_array($nd['key'], ['half', 'full']) ? $nd['label'] . ' next day' : 'Report by ' . $nd['label'] }}
                                </span>
                            @endif

                            @if($req->is_after_3pm)
                                <span class="lw-tag warn">After 3 PM</span>
                            @endif
                        </div>

                        <!-- Reason -->
                        @if($req->reason)
                            <p class="text-xs sm:text-sm text-[#1A1E2C] leading-relaxed break-words bg-[#F4F5F8] p-2.5 rounded-lg border border-[#D9DCE5]">
                                {{ $req->reason }}
                            </p>
                        @endif

                        <!-- Decision Stamp / Note & Re-appeal Action -->
                        @if($req->decided_at)
                            <div class="text-xs text-[#5D6375] border-l-2 {{ $req->status === 'approved' ? 'border-emerald-500' : 'border-rose-500' }} pl-3 py-1 space-y-1.5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <strong class="{{ $req->status === 'approved' ? 'text-emerald-700' : 'text-rose-700' }}">
                                            {{ $req->status === 'approved' ? 'Approved' : 'Declined' }}
                                        </strong>
                                        by {{ $req->decidedBy->name ?? 'Approver' }} · {{ $req->decided_at->format('d M, h:i A') }}
                                        @if($req->hod_note)
                                             · <span class="italic font-medium text-[#1A1E2C]">"{{ $req->hod_note }}"</span>
                                        @endif
                                    </div>

                                    @if($req->status === 'declined')
                                        <button type="button"
                                                @click="openReappealModal({{ json_encode([
                                                    'id' => $req->id,
                                                    'ref' => $req->reference_number,
                                                    'date' => $req->date ? $req->date->format('D, d M Y') : '',
                                                    'jobs' => $req->job_number,
                                                    'type' => $req->type,
                                                    'start_time' => $req->start_time,
                                                    'finish_time' => $req->finish_time,
                                                    'is_wfh' => (bool)$req->is_wfh,
                                                    'meal' => (bool)$req->meal,
                                                    'taxi' => (bool)$req->taxi,
                                                    'lieu' => $req->lieu,
                                                    'reason' => $req->reason,
                                                    'hod_note' => $req->hod_note,
                                                    'decided_by_name' => $req->decidedBy->name ?? 'Approver',
                                                ]) }})"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#1A1E2C] text-white hover:bg-black rounded-lg font-bold text-xs transition shadow-sm">
                                            <i class="fas fa-undo text-[11px]"></i> Re-appeal Request
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="text-xs text-[#5D6375] flex items-center justify-between gap-2">
                                <div>
                                    <span>Sent to <strong>{{ $req->hod->name ?? 'HOD' }}</strong> for approval · Ref: <span class="lw-mono">{{ $req->reference_number }}</span></span>
                                    @if($req->reappeal_count > 0)
                                        <span class="ml-1.5 bg-amber-100 text-amber-800 text-[10px] font-bold px-1.5 py-0.5 rounded uppercase">Re-appeal #{{ $req->reappeal_count }}</span>
                                    @endif
                                </div>

                                @if($req->canWithdraw($user))
                                    <form action="{{ route('late-work.withdraw', $req) }}" method="POST" onsubmit="return confirm('Withdraw this pending request?')">
                                        @csrf
                                        <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-semibold px-2 py-1 rounded hover:bg-rose-50 transition">
                                            Withdraw
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <!-- TAB 3: APPROVALS -->
    @if($canApproveAny)
    <section x-show="activeTab === 'approve'" x-cloak class="space-y-5">
        <div>
            <h2 class="text-lg font-bold text-[#1A1E2C] lw-title">Pending Approvals</h2>
            <p class="text-xs text-[#5D6375]">Review requests submitted by your team members for late work and weekend shifts.</p>
        </div>

        @if($pendingApprovals->isEmpty())
            <div class="bg-white border border-[#D9DCE5] rounded-2xl p-10 text-center text-[#5D6375]">
                <i class="fas fa-check-circle text-emerald-500 text-3xl mb-2"></i>
                <h3 class="font-bold text-sm text-[#1A1E2C]">All caught up</h3>
                <p class="text-xs mt-1">There are no pending requests waiting for your approval.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($pendingApprovals as $r)
                    <article class="lw-card p-5 space-y-3.5 border-l-4 border-l-[#B86A00]">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="font-bold text-sm text-[#1A1E2C]">{{ $r->user->name ?? 'Staff' }}</span>
                                    <span class="text-xs text-[#5D6375]">({{ $r->department_name }})</span>
                                </div>
                                <div class="space-y-1">
                                    @foreach($r->job_items as $item)
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="lw-mono bg-[#ECEEF3] text-xs font-semibold px-2 py-0.5 rounded text-[#1A1E2C]">{{ $item['number'] }}</span>
                                            @if(!empty($item['label']))
                                                <span class="text-xs font-medium text-[#5D6375]">{{ $item['label'] }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="lw-chip pending">Pending</span>
                                @if(auth()->user()->canEditDeleteLateWork())
                                    <button type="button"
                                            @click="openEditModal({{ json_encode($r->toEditArray()) }})"
                                            class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg transition-colors inline-flex items-center gap-1 shadow-xs cursor-pointer"
                                            title="Edit Request">
                                        <i class="fas fa-edit text-[11px]"></i> Edit
                                    </button>
                                    <form action="{{ route('late-work.destroy', $r) }}" method="POST" class="inline" onsubmit="return confirmDeleteLateWork(event, this, '{{ $r->reference_number }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg transition-colors inline-flex items-center gap-1 shadow-xs cursor-pointer"
                                                title="Delete Request">
                                            <i class="fas fa-trash-alt text-[11px]"></i> Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <!-- Meta details -->
                        <div class="flex flex-wrap gap-1.5 text-xs">
                            <span class="lw-tag wk">
                                {{ $r->type === 'weekend' ? ($r->is_holiday ? 'Public holiday' : 'Weekend') : 'Late night' }}
                            </span>
                            <span class="lw-tag">
                                {{ $r->date ? $r->date->format('D, d M Y') : '' }}
                            </span>
                            <span class="lw-tag">
                                @if($r->type === 'weekend')
                                    {{ $r->start_time ? date('g:i A', strtotime($r->start_time)) : '' }} – {{ $r->finish_time ? date('g:i A', strtotime($r->finish_time)) : '' }}
                                @else
                                    Until {{ $r->finish_time ? date('g:i A', strtotime($r->finish_time)) : '' }}
                                @endif
                            </span>

                            @if($r->is_wfh)
                                <span class="lw-tag wfh"><i class="fas fa-laptop-house mr-1"></i>WFH</span>
                            @endif

                            @if($r->meal)
                                <span class="lw-tag">Meal allowance (Rs. 1,000)</span>
                            @endif

                            @if($r->taxi)
                                <span class="lw-tag">PickMe ride</span>
                            @endif

                            @if($r->lieu)
                                <span class="lw-tag">Lieu: {{ $r->lieu_label }}</span>
                            @endif

                            @if($r->type !== 'weekend' && $r->next_day)
                                @php
                                    $nd = \App\Models\LateWorkRequest::calculateNextDayReport($r->finish_time);
                                @endphp
                                <span class="lw-tag font-bold text-[#B86A00]">
                                    {{ in_array($nd['key'], ['half', 'full']) ? $nd['label'] . ' next day' : 'Report by ' . $nd['label'] }}
                                </span>
                            @endif

                            @if($r->is_after_3pm)
                                <span class="lw-tag warn">Submitted after 3 PM</span>
                            @endif
                        </div>

                        <!-- Reason -->
                        @if($r->reason)
                            <div class="bg-[#F4F5F8] p-3 rounded-lg border border-[#D9DCE5] text-xs sm:text-sm text-[#1A1E2C] leading-relaxed">
                                <span class="text-[11px] font-bold uppercase text-[#5D6375] block mb-0.5">Reason</span>
                                {{ $r->reason }}
                            </div>
                        @endif

                        @if($r->pettyCashRequest)
                            <div class="p-3 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-300 rounded-xl text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-amber-950">Linked Petty Cash Request:</span>
                                            <a href="{{ route('petty-cash.index') }}" target="_blank" class="font-mono font-bold text-[#B86A00] hover:underline">
                                                {{ $r->pettyCashRequest->reference_number }}
                                            </a>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#B86A00] text-white">Work Tracker</span>
                                        </div>
                                        <div class="text-[11px] text-amber-900 mt-0.5">
                                            Amount: <b>Rs. {{ number_format($r->pettyCashRequest->total_amount, 2) }}</b>
                                            &bull; Status: <span class="capitalize">{{ str_replace('_', ' ', $r->pettyCashRequest->status) }}</span>
                                            &bull; <span class="font-semibold text-emerald-800"><i class="fas fa-bolt text-emerald-600"></i> Approving this shift will automatically approve the HOD stage of this petty cash voucher.</span>
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ route('petty-cash.index') }}" target="_blank"
                                   class="px-2.5 py-1.5 bg-white border border-amber-300 rounded-lg text-xs font-semibold text-amber-900 hover:bg-amber-100 transition shadow-xs flex items-center gap-1 self-start sm:self-auto flex-shrink-0">
                                    <i class="fas fa-external-link-alt text-[10px]"></i> View Voucher
                                </a>
                            </div>
                        @endif

                        @if(!empty($r->reappeal_notes))
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-2.5 text-xs text-amber-900 space-y-1">
                                <div class="font-bold flex items-center gap-1.5 text-amber-950">
                                    <i class="fas fa-history text-amber-600"></i> Re-appeal Justification (Attempt #{{ $r->reappeal_count }}):
                                </div>
                                <p class="italic text-amber-900">"{{ $r->reappeal_notes }}"</p>
                                @if(!empty($r->hod_note))
                                    <p class="text-[11px] text-amber-800/80 pt-1 border-t border-amber-200/60">Previous decline note: "{{ $r->hod_note }}"</p>
                                @endif
                            </div>
                        @endif

                        <!-- Approval Actions Form -->
                        <div class="pt-2 flex flex-col sm:flex-row items-stretch sm:items-center gap-2" x-data="{ note: '' }">
                            <input type="text" x-model="note" placeholder="Add optional note for the employee…" maxlength="200"
                                   class="flex-1 bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-3 py-2 text-xs text-[#1A1E2C] focus:outline-none focus:ring-2 focus:ring-[#B86A00]">

                            <div class="flex items-center gap-2">
                                <form action="{{ route('late-work.approve', $r) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="note" :value="note">
                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2 rounded-lg transition shadow-sm flex items-center gap-1.5">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>

                                <button type="button"
                                        @click="openDeclineModal({{ $r->id }}, '{{ $r->reference_number }}', '{{ addslashes($r->user->name ?? 'Staff') }}', note)"
                                        class="bg-transparent hover:bg-rose-50 border border-rose-600 text-rose-600 font-bold text-xs px-4 py-2 rounded-lg transition flex items-center gap-1.5">
                                    <i class="fas fa-times"></i> Decline
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        <!-- Recently Decided Section -->
        @if($recentDecided->isNotEmpty())
            <div class="pt-6 border-t border-[#D9DCE5] space-y-3">
                <h3 class="text-sm font-bold uppercase text-[#5D6375] tracking-wider">Recently Decided</h3>
                <div class="space-y-2.5">
                    @foreach($recentDecided as $r)
                        <div class="lw-card p-3.5 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="font-bold text-[#1A1E2C]">{{ $r->user->name ?? 'Staff' }}</span>
                                <span class="text-[#5D6375]">· {{ $r->department_name }}</span>
                                @if(count($r->job_numbers) > 1)
                                    @foreach($r->job_numbers as $jn)
                                        <span class="lw-mono bg-[#ECEEF3] px-1.5 py-0.5 rounded text-[11px]">{{ $jn }}</span>
                                    @endforeach
                                @else
                                    <span class="lw-mono bg-[#ECEEF3] px-1.5 py-0.5 rounded text-[11px]">{{ $r->job_number }}</span>
                                @endif
                                <span class="text-[#5D6375]">{{ $r->date ? $r->date->format('d M') : '' }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                @if($r->hod_note)
                                    <span class="text-[#5D6375] italic max-w-xs truncate">"{{ $r->hod_note }}"</span>
                                @endif
                                <span class="lw-chip {{ $r->status }}">{{ ucfirst($r->status) }}</span>
                                @if(auth()->user()->canEditDeleteLateWork())
                                    <button type="button"
                                            @click="openEditModal({{ json_encode($r->toEditArray()) }})"
                                            class="px-2 py-0.5 bg-amber-500 hover:bg-amber-600 text-white text-[11px] font-bold rounded-md transition-colors inline-flex items-center gap-1 shadow-xs cursor-pointer"
                                            title="Edit Request">
                                        <i class="fas fa-edit text-[10px]"></i> Edit
                                    </button>
                                    <form action="{{ route('late-work.destroy', $r) }}" method="POST" class="inline" onsubmit="return confirmDeleteLateWork(event, this, '{{ $r->reference_number }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2 py-0.5 bg-rose-600 hover:bg-rose-700 text-white text-[11px] font-bold rounded-md transition-colors inline-flex items-center gap-1 shadow-xs cursor-pointer"
                                                title="Delete Request">
                                            <i class="fas fa-trash-alt text-[10px]"></i> Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
    @endif

    <!-- TAB: TEAM'S REQUESTS (FOR HOD) -->
    @if($isHodOnly)
    <section x-show="activeTab === 'team'" x-cloak class="space-y-5">
        <!-- Header & Filter Bar -->
        <div class="bg-white border border-[#D9DCE5] rounded-2xl p-5 sm:p-6 shadow-sm space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-[#1A1E2C] lw-title flex items-center gap-2">
                        <i class="fas fa-users text-[#B86A00]"></i>
                        <span>Team's Requests</span>
                    </h2>
                    <p class="text-xs text-[#5D6375] mt-0.5">
                        Late night and weekend work requests submitted by members of your team ({{ $user->department ?: 'Assigned Team' }}).
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-[#5D6375] bg-[#F4F5F8] px-3 py-1.5 rounded-lg border border-[#D9DCE5]">
                        <i class="fas fa-user-shield text-[#B86A00] mr-1"></i> HOD: <strong>{{ $user->name }}</strong>
                    </span>
                </div>
            </div>

            <!-- Filter Controls -->
            <form action="{{ route('late-work.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 gap-3 items-end pt-3 border-t border-[#D9DCE5]">
                <input type="hidden" name="tab" value="team">

                <!-- Month -->
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block">Month</label>
                    <input type="month" name="month" value="{{ $teamMonth }}" onchange="this.form.submit()"
                           class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-2.5 py-2 text-xs text-[#1A1E2C]">
                </div>

                <!-- Status -->
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block">Status</label>
                    <select name="status" onchange="this.form.submit()"
                            class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-2.5 py-2 text-xs text-[#1A1E2C]">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ $teamStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ $teamStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="declined" {{ $teamStatus === 'declined' ? 'selected' : '' }}>Declined</option>
                        <option value="withdrawn" {{ $teamStatus === 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                    </select>
                </div>

                <!-- Type -->
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block">Type</label>
                    <select name="type" onchange="this.form.submit()"
                            class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-2.5 py-2 text-xs text-[#1A1E2C]">
                        <option value="">All Types</option>
                        <option value="late" {{ $teamType === 'late' ? 'selected' : '' }}>Late night (past 8 PM)</option>
                        <option value="weekend" {{ $teamType === 'weekend' ? 'selected' : '' }}>Weekend / holiday</option>
                    </select>
                </div>

                <!-- Reset Button -->
                <div>
                    <a href="{{ route('late-work.index', ['tab' => 'team']) }}"
                       class="w-full text-center bg-white border border-[#D9DCE5] hover:border-[#1A1E2C] text-[#5D6375] hover:text-[#1A1E2C] font-semibold text-xs py-2 px-3 rounded-lg transition flex items-center justify-center gap-1.5 shadow-sm">
                        <i class="fas fa-undo text-xs"></i> Reset Filters
                    </a>
                </div>
            </form>

            <!-- Metrics / Counters Bar for Team -->
            <div class="pt-3 border-t border-[#D9DCE5] grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-[#F4F5F8] p-3 rounded-xl border border-[#D9DCE5]">
                    <span class="text-[11px] font-semibold text-[#5D6375] uppercase block">Total Team Requests</span>
                    <strong class="text-xl font-bold text-[#1A1E2C] font-mono">{{ $teamStats['total'] }}</strong>
                </div>
                <div class="bg-[#FBEBD2] p-3 rounded-xl border border-[#F5EDC2]">
                    <span class="text-[11px] font-semibold text-[#B86A00] uppercase block">Pending Approval</span>
                    <strong class="text-xl font-bold text-[#B86A00] font-mono">{{ $teamStats['pending'] }}</strong>
                </div>
                <div class="bg-emerald-50 p-3 rounded-xl border border-emerald-200">
                    <span class="text-[11px] font-semibold text-emerald-800 uppercase block">Approved</span>
                    <strong class="text-xl font-bold text-emerald-800 font-mono">{{ $teamStats['approved'] }}</strong>
                </div>
                <div class="bg-rose-50 p-3 rounded-xl border border-rose-200">
                    <span class="text-[11px] font-semibold text-rose-800 uppercase block">Declined</span>
                    <strong class="text-xl font-bold text-rose-800 font-mono">{{ $teamStats['declined'] }}</strong>
                </div>
            </div>
        </div>

        <!-- Requests List -->
        @if($teamRequests->isEmpty())
            <div class="bg-white border border-[#D9DCE5] rounded-2xl p-10 text-center text-[#5D6375] space-y-2">
                <div class="w-12 h-12 mx-auto rounded-full bg-[#F4F5F8] flex items-center justify-center text-[#5D6375] text-lg mb-2">
                    <i class="fas fa-users-slash"></i>
                </div>
                <p class="text-sm font-semibold text-[#1A1E2C]">No team requests found</p>
                <p class="text-xs text-[#5D6375]">There are no late night or weekend work requests matching your filters.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($teamRequests as $r)
                    <article class="lw-card p-4 sm:p-5 flex flex-col gap-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($r->user->name ?? 'Staff') }}&background=1A1E2C&color=ffffff&size=48"
                                     alt="{{ $r->user->name ?? 'Staff' }}" class="w-8 h-8 rounded-full border border-gray-200 object-cover">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-sm text-[#1A1E2C]">{{ $r->user->name ?? 'Staff' }}</span>
                                        <span class="text-xs text-[#5D6375]">({{ $r->department_name }})</span>
                                    </div>
                                    <span class="text-[11px] text-[#5D6375]">{{ $r->user->email ?? '' }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="lw-tag wk">
                                    {{ $r->type === 'weekend' ? ($r->is_holiday ? 'Holiday' : 'Weekend') : 'Late night' }}
                                </span>
                                <span class="lw-chip {{ $r->status }}">
                                    {{ ucfirst($r->status) }}
                                </span>
                                @if(auth()->user()->canEditDeleteLateWork())
                                    <button type="button"
                                            @click="openEditModal({{ json_encode($r->toEditArray()) }})"
                                            class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg transition-colors inline-flex items-center gap-1 shadow-xs cursor-pointer"
                                            title="Edit Request">
                                        <i class="fas fa-edit text-[11px]"></i> Edit
                                    </button>
                                    <form action="{{ route('late-work.destroy', $r) }}" method="POST" class="inline" onsubmit="return confirmDeleteLateWork(event, this, '{{ $r->reference_number }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg transition-colors inline-flex items-center gap-1 shadow-xs cursor-pointer"
                                                title="Delete Request">
                                            <i class="fas fa-trash-alt text-[11px]"></i> Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <!-- Job Numbers & Description -->
                        <div class="space-y-1 pt-1">
                            @foreach($r->job_items as $item)
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="lw-mono bg-[#ECEEF3] text-xs font-semibold px-2 py-0.5 rounded text-[#1A1E2C]">{{ $item['number'] }}</span>
                                    @if(!empty($item['label']))
                                        <span class="text-xs text-[#5D6375]">· {{ $item['label'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <!-- Policy / Time Tags -->
                        <div class="flex flex-wrap items-center gap-1.5 text-xs text-[#5D6375]">
                            <span class="lw-tag"><i class="fas fa-calendar-day mr-1 text-[#5D6375]"></i>{{ $r->date ? $r->date->format('D, d M Y') : '' }}</span>
                            <span class="lw-tag">
                                <i class="fas fa-clock mr-1 text-[#5D6375]"></i>
                                @if($r->type === 'weekend')
                                    {{ $r->start_time ? date('g:i A', strtotime($r->start_time)) : '' }} – {{ $r->finish_time ? date('g:i A', strtotime($r->finish_time)) : '' }}
                                @else
                                    Until {{ $r->finish_time ? date('g:i A', strtotime($r->finish_time)) : '' }}
                                @endif
                            </span>

                            @if($r->is_wfh)
                                <span class="lw-tag wfh"><i class="fas fa-laptop-house mr-1"></i>WFH</span>
                            @endif

                            @if($r->meal)
                                <span class="lw-tag">Meal allowance (Rs. 1,000)</span>
                            @endif
                            @if($r->taxi)
                                <span class="lw-tag">PickMe ride</span>
                            @endif
                            @if($r->pettyCashRequest)
                                <a href="{{ route('petty-cash.index') }}" 
                                   class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300 hover:bg-amber-200 transition"
                                   title="Linked Petty Cash Request: {{ $r->pettyCashRequest->reference_number }}">
                                    <i class="fas fa-wallet text-[#B86A00] text-[10px]"></i>
                                    <span>Petty Cash: {{ $r->pettyCashRequest->reference_number }}</span>
                                    <span class="text-[10px] uppercase font-mono px-1 py-0.2 rounded bg-white text-gray-700">
                                        {{ str_replace('_', ' ', $r->pettyCashRequest->status) }}
                                    </span>
                                </a>
                            @endif
                            @if($r->lieu)
                                <span class="lw-tag">Lieu: {{ $r->lieu_label }}</span>
                            @endif
                            @if($r->type !== 'weekend' && $r->next_day)
                                @php
                                    $nd = \App\Models\LateWorkRequest::calculateNextDayReport($r->finish_time);
                                @endphp
                                <span class="lw-tag font-bold text-[#B86A00]">
                                    {{ in_array($nd['key'], ['half', 'full']) ? $nd['label'] . ' next day' : 'Report by ' . $nd['label'] }}
                                </span>
                            @endif
                            @if($r->is_after_3pm)
                                <span class="lw-tag warn">After 3 PM cutoff</span>
                            @endif
                        </div>

                        <!-- Reason -->
                        @if($r->reason)
                            <div class="bg-[#F4F5F8] p-3 rounded-lg border border-[#D9DCE5] text-xs sm:text-sm text-[#1A1E2C] leading-relaxed">
                                <span class="text-[11px] font-bold uppercase text-[#5D6375] block mb-0.5">Reason for work</span>
                                {{ $r->reason }}
                            </div>
                        @endif

                        <!-- Footer: Decisions or Quick Actions -->
                        @if($r->status === 'pending' && $r->canApprove($user))
                            @if(!empty($r->reappeal_notes))
                                <div class="bg-amber-50 border border-amber-200 rounded-lg p-2.5 text-xs text-amber-900 space-y-1 mb-2">
                                    <div class="font-bold flex items-center gap-1 text-amber-950">
                                        <i class="fas fa-history text-amber-600"></i> Re-appeal Justification (Attempt #{{ $r->reappeal_count }}):
                                    </div>
                                    <p class="italic text-amber-900">"{{ $r->reappeal_notes }}"</p>
                                    @if(!empty($r->hod_note))
                                        <p class="text-[11px] text-amber-800/80 pt-1 border-t border-amber-200/60">Previous decline note: "{{ $r->hod_note }}"</p>
                                    @endif
                                </div>
                            @endif

                            <div class="pt-2 border-t border-[#D9DCE5] flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5 text-xs text-[#B86A00] font-semibold">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span>Pending your approval</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <form action="{{ route('late-work.approve', $r) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-1.5 rounded-lg transition shadow-sm flex items-center gap-1">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <button type="button"
                                            @click="openDeclineModal({{ $r->id }}, '{{ $r->reference_number }}', '{{ addslashes($r->user->name ?? 'Staff') }}')"
                                            class="bg-transparent hover:bg-rose-50 border border-rose-600 text-rose-600 font-bold text-xs px-3.5 py-1.5 rounded-lg transition flex items-center gap-1">
                                        <i class="fas fa-times"></i> Decline
                                    </button>
                                </div>
                            </div>
                        @elseif($r->decided_at)
                            <div class="text-[11px] text-[#5D6375] border-t border-[#D9DCE5] pt-2 flex flex-wrap justify-between items-center gap-2">
                                <span>
                                    {{ ucfirst($r->status) }} by <strong>{{ $r->decidedBy->name ?? 'HOD' }}</strong> on {{ $r->decided_at->format('d M, h:i A') }}
                                    @if($r->hod_note) · <em class="text-[#1A1E2C]">"{{ $r->hod_note }}"</em> @endif
                                </span>
                                <span class="lw-mono text-[#5D6375]">{{ $r->reference_number }}</span>
                            </div>
                        @else
                            <div class="text-[11px] text-[#5D6375] border-t border-[#D9DCE5] pt-2 flex justify-between items-center">
                                <span>Reference: <strong class="lw-mono">{{ $r->reference_number }}</strong></span>
                                <span class="lw-chip {{ $r->status }}">{{ ucfirst($r->status) }}</span>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>
    @endif

    <!-- TAB 4: LOG & REPORTS -->
    @if(!$isHodOnly && ($canViewLog || (auth()->check() && (auth()->user()->isManagement() || auth()->user()->hasRole('management')))))
    <section x-show="activeTab === 'log'" x-cloak class="space-y-5">
        <!-- Filter Bar -->
        <div class="bg-white border border-[#D9DCE5] rounded-2xl p-5 shadow-sm space-y-4">
            <form action="{{ route('late-work.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end">
                <input type="hidden" name="tab" value="log">

                <!-- Month -->
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block">Month</label>
                    <input type="month" name="month" value="{{ $filterMonth }}" onchange="this.form.submit()"
                           class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-2.5 py-2 text-xs text-[#1A1E2C]">
                </div>

                <!-- Department -->
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block">Department</label>
                    <select name="dept" onchange="this.form.submit()"
                            class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-2.5 py-2 text-xs text-[#1A1E2C]">
                        <option value="">All Departments</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ $filterDept == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block">Status</label>
                    <select name="status" onchange="this.form.submit()"
                            class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-2.5 py-2 text-xs text-[#1A1E2C]">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ $filterStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ $filterStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="declined" {{ $filterStatus === 'declined' ? 'selected' : '' }}>Declined</option>
                        <option value="withdrawn" {{ $filterStatus === 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                    </select>
                </div>

                <!-- Type -->
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block">Type</label>
                    <select name="type" onchange="this.form.submit()"
                            class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-2.5 py-2 text-xs text-[#1A1E2C]">
                        <option value="">All Types</option>
                        <option value="late" {{ $filterType === 'late' ? 'selected' : '' }}>Late night</option>
                        <option value="weekend" {{ $filterType === 'weekend' ? 'selected' : '' }}>Weekend / holiday</option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <a href="{{ route('late-work.export-csv', ['month' => $filterMonth, 'dept' => $filterDept, 'status' => $filterStatus, 'type' => $filterType]) }}"
                       class="w-full text-center bg-white border border-[#D9DCE5] hover:border-[#1A1E2C] text-[#1A1E2C] font-semibold text-xs py-2 px-3 rounded-lg transition flex items-center justify-center gap-1.5 shadow-sm">
                        <i class="fas fa-file-csv text-emerald-600"></i> Export CSV
                    </a>
                </div>
            </form>

            <!-- Metrics / Counters Bar -->
            <div class="pt-3 border-t border-[#D9DCE5] grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                <div class="bg-[#F4F5F8] p-3 rounded-xl border border-[#D9DCE5]">
                    <span class="text-[11px] font-semibold text-[#5D6375] uppercase block">Total Requests</span>
                    <strong class="text-xl font-bold text-[#1A1E2C] font-mono">{{ $stats['total'] }}</strong>
                </div>
                <div class="bg-emerald-50 p-3 rounded-xl border border-emerald-200">
                    <span class="text-[11px] font-semibold text-emerald-800 uppercase block">Approved</span>
                    <strong class="text-xl font-bold text-emerald-800 font-mono">{{ $stats['approved'] }}</strong>
                </div>
                <div class="bg-[#FBEBD2] p-3 rounded-xl border border-[#F5EDC2]">
                    <span class="text-[11px] font-semibold text-[#B86A00] uppercase block">Meals (Count)</span>
                    <strong class="text-xl font-bold text-[#B86A00] font-mono">{{ $stats['meals'] }}</strong>
                </div>
                <div class="bg-[#FBEBD2] p-3 rounded-xl border border-[#F5EDC2]">
                    <span class="text-[11px] font-semibold text-[#B86A00] uppercase block">Meal Total</span>
                    <strong class="text-base sm:text-lg font-bold text-[#B86A00] font-mono">Rs. {{ number_format($stats['mealTotal']) }}</strong>
                </div>
                <div class="bg-cyan-50 p-3 rounded-xl border border-cyan-200">
                    <span class="text-[11px] font-semibold text-cyan-800 uppercase block">PickMe Rides</span>
                    <strong class="text-xl font-bold text-cyan-800 font-mono">{{ $stats['taxis'] }}</strong>
                </div>
                <div class="bg-purple-50 p-3 rounded-xl border border-purple-200">
                    <span class="text-[11px] font-semibold text-purple-800 uppercase block">Lieu Days</span>
                    <strong class="text-xl font-bold text-purple-800 font-mono">{{ $stats['lieuDays'] }}</strong>
                </div>
            </div>
        </div>

        <!-- Log List / Table -->
        @if($logRequests->isEmpty())
            <div class="bg-white border border-[#D9DCE5] rounded-2xl p-10 text-center text-[#5D6375]">
                <p class="text-sm">No requests match the selected filters.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($logRequests as $r)
                    <article class="lw-card p-4 flex flex-col gap-2.5">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="font-bold text-sm text-[#1A1E2C]">{{ $r->user->name ?? 'Staff' }}</span>
                                    <span class="text-xs text-[#5D6375]">({{ $r->department_name }})</span>
                                </div>
                                <div class="space-y-1">
                                    @foreach($r->job_items as $item)
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="lw-mono bg-[#ECEEF3] text-xs font-semibold px-2 py-0.5 rounded text-[#1A1E2C]">{{ $item['number'] }}</span>
                                            @if(!empty($item['label']))
                                                <span class="text-xs text-[#5D6375]">· {{ $item['label'] }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="lw-chip {{ $r->status }}">
                                    {{ ucfirst($r->status) }}
                                </span>
                                @if(auth()->user()->canEditDeleteLateWork())
                                    <button type="button"
                                            @click="openEditModal({{ json_encode($r->toEditArray()) }})"
                                            class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg transition-colors inline-flex items-center gap-1 shadow-xs cursor-pointer"
                                            title="Edit Request">
                                        <i class="fas fa-edit text-[11px]"></i> Edit
                                    </button>
                                    <form action="{{ route('late-work.destroy', $r) }}" method="POST" class="inline" onsubmit="return confirmDeleteLateWork(event, this, '{{ $r->reference_number }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg transition-colors inline-flex items-center gap-1 shadow-xs cursor-pointer"
                                                title="Delete Request">
                                            <i class="fas fa-trash-alt text-[11px]"></i> Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5 text-xs text-[#5D6375]">
                            <span class="lw-tag wk">
                                {{ $r->type === 'weekend' ? ($r->is_holiday ? 'Holiday' : 'Weekend') : 'Late night' }}
                            </span>
                            <span class="lw-tag">{{ $r->date ? $r->date->format('D, d M Y') : '' }}</span>
                            <span class="lw-tag">
                                @if($r->type === 'weekend')
                                    {{ $r->start_time ? date('g:i A', strtotime($r->start_time)) : '' }} – {{ $r->finish_time ? date('g:i A', strtotime($r->finish_time)) : '' }}
                                @else
                                    Until {{ $r->finish_time ? date('g:i A', strtotime($r->finish_time)) : '' }}
                                @endif
                            </span>

                            @if($r->is_wfh) <span class="lw-tag wfh"><i class="fas fa-laptop-house mr-1"></i>WFH</span> @endif
                            @if($r->meal) <span class="lw-tag">Meal</span> @endif
                            @if($r->taxi) <span class="lw-tag">PickMe</span> @endif
                            @if($r->pettyCashRequest)
                                <a href="{{ route('petty-cash.index') }}" 
                                   class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300 hover:bg-amber-200 transition"
                                   title="Linked Petty Cash Request: {{ $r->pettyCashRequest->reference_number }}">
                                    <i class="fas fa-wallet text-[#B86A00] text-[10px]"></i>
                                    <span>Petty Cash: {{ $r->pettyCashRequest->reference_number }}</span>
                                    <span class="text-[10px] uppercase font-mono px-1 py-0.2 rounded bg-white text-gray-700">
                                        {{ str_replace('_', ' ', $r->pettyCashRequest->status) }}
                                    </span>
                                </a>
                            @endif
                            @if($r->lieu) <span class="lw-tag">Lieu: {{ $r->lieu_label }}</span> @endif
                            @if($r->is_after_3pm) <span class="lw-tag warn">After 3 PM</span> @endif
                        </div>

                        @if($r->reason)
                            <p class="text-xs text-[#1A1E2C]">{{ $r->reason }}</p>
                        @endif

                        @if($r->status === 'pending' && $r->canApprove($user))
                            <div class="pt-2 border-t border-[#D9DCE5] flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5 text-xs text-[#B86A00] font-semibold">
                                    <i class="fas fa-clock"></i>
                                    <span>Pending approval</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <form action="{{ route('late-work.approve', $r) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-1.5 rounded-lg transition shadow-sm flex items-center gap-1 cursor-pointer">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <button type="button"
                                            @click="openDeclineModal({{ $r->id }}, '{{ $r->reference_number }}', '{{ addslashes($r->user->name ?? 'Staff') }}')"
                                            class="bg-transparent hover:bg-rose-50 border border-rose-600 text-rose-600 font-bold text-xs px-3.5 py-1.5 rounded-lg transition flex items-center gap-1 cursor-pointer">
                                        <i class="fas fa-times"></i> Decline
                                    </button>
                                </div>
                            </div>
                        @elseif($r->decided_at)
                            <div class="text-[11px] text-[#5D6375] border-t border-[#D9DCE5] pt-2 flex flex-wrap justify-between items-center gap-2">
                                <span>
                                    {{ ucfirst($r->status) }} by <strong>{{ $r->decidedBy->name ?? 'HOD' }}</strong> on {{ $r->decided_at->format('d M, h:i A') }}
                                    @if($r->hod_note) · <em class="text-[#1A1E2C]">"{{ $r->hod_note }}"</em> @endif
                                </span>
                                <span class="lw-mono text-[#5D6375]">{{ $r->reference_number }}</span>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>
    @endif

    <!-- TAB 6: CALENDAR SETTINGS (HR Admin & IT Admin) -->
    @if($canManageHolidays)
    <section x-show="activeTab === 'calendar'" x-cloak class="space-y-6">
        <!-- Calendar Header Card -->
        <div class="bg-white border border-[#D9DCE5] rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-amber-100 text-[#B86A00] flex items-center justify-center text-sm font-bold">
                        <i class="fas fa-calendar-alt"></i>
                    </span>
                    <h2 class="text-lg font-bold text-[#1A1E2C]">Holiday Calendar Settings</h2>
                </div>
                <p class="text-xs text-[#5D6375] mt-1 max-w-xl">
                    Configure official public, mercantile, and company holidays month by month. Holidays configured here automatically highlight on the staff Late Work calendar picker.
                </p>
            </div>
            
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="openHolidayTypesModal()"
                        class="px-3.5 py-2.5 rounded-xl border border-[#D9DCE5] bg-[#F4F5F8] hover:bg-[#ECEEF3] text-xs font-bold text-[#1A1E2C] transition flex items-center gap-2 cursor-pointer shadow-xs">
                    <i class="fas fa-tags text-[#B86A00]"></i>
                    <span>Holiday Types</span>
                    <span class="bg-[#ECEEF3] border border-[#D9DCE5] text-[#1A1E2C] text-[10px] font-extrabold px-1.5 py-0.2 rounded-full"
                          x-text="holidayTypes.length"></span>
                </button>

                <button type="button" @click="openAddHolidayModal()"
                        class="bg-[#B86A00] hover:bg-[#995800] text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-plus"></i>
                    <span>Add Holiday</span>
                </button>
            </div>
        </div>

        <!-- Month Navigator & Interactive Calendar Grid -->
        <div class="bg-white border border-[#D9DCE5] rounded-2xl p-5 sm:p-6 shadow-sm space-y-5">
            <!-- Month Bar -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pb-4 border-b border-[#ECEEF3]">
                <div class="flex items-center gap-2">
                    <button type="button" @click="prevCalMonth()"
                            class="px-3 py-2 rounded-lg bg-[#F4F5F8] hover:bg-[#ECEEF3] text-xs font-bold text-[#1A1E2C] border border-[#D9DCE5] transition flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-chevron-left text-[10px]"></i>
                        <span>Previous</span>
                    </button>
                    <div class="px-4 py-1.5 rounded-lg bg-[#ECEEF3] font-extrabold text-sm sm:text-base text-[#1A1E2C] min-w-[170px] text-center"
                         x-text="calMonthName"></div>
                    <button type="button" @click="nextCalMonth()"
                            class="px-3 py-2 rounded-lg bg-[#F4F5F8] hover:bg-[#ECEEF3] text-xs font-bold text-[#1A1E2C] border border-[#D9DCE5] transition flex items-center gap-1 cursor-pointer">
                        <span>Next</span>
                        <i class="fas fa-chevron-right text-[10px]"></i>
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="goCalToday()"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold text-[#B86A00] bg-[#FBEBD2]/60 hover:bg-[#FBEBD2] border border-[#F1C888] transition cursor-pointer">
                        This Month
                    </button>
                    <span class="text-xs text-[#5D6375]">
                        <span class="font-bold text-[#1A1E2C]" x-text="monthHolidaysList.length"></span> holidays in this month
                    </span>
                </div>
            </div>

            <!-- Calendar Days Grid -->
            <div>
                <!-- Day-of-week headers -->
                <div class="grid grid-cols-7 gap-2 mb-2 text-center text-xs font-bold text-[#5D6375] uppercase tracking-wider">
                    <div class="py-1">Mon</div>
                    <div class="py-1">Tue</div>
                    <div class="py-1">Wed</div>
                    <div class="py-1">Thu</div>
                    <div class="py-1">Fri</div>
                    <div class="py-1 text-indigo-600">Sat</div>
                    <div class="py-1 text-rose-600">Sun</div>
                </div>

                <!-- Days of month -->
                <div class="grid grid-cols-7 gap-2">
                    <template x-for="item in calCalendarDays" :key="item.date">
                        <div class="min-h-[75px] sm:min-h-[85px] rounded-xl p-2 border transition-all flex flex-col justify-between relative group cursor-pointer"
                             :class="{
                                 'bg-white border-[#D9DCE5] hover:border-[#B86A00] hover:shadow-xs': item.isCurrentMonth && !item.holiday,
                                 'bg-[#FAFBFD] border-[#ECEEF3] opacity-40 hover:opacity-80': !item.isCurrentMonth,
                                 'bg-amber-50/90 border-amber-300 ring-1 ring-amber-300/60 shadow-xs': item.holiday,
                                 'bg-indigo-50/30': item.isWeekend && !item.holiday && item.isCurrentMonth
                             }"
                             @click="openAddHolidayModal(item.date)">
                            
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold"
                                      :class="{
                                          'text-[#B86A00]': item.holiday,
                                          'text-indigo-600': item.isWeekend && !item.holiday,
                                          'text-[#1A1E2C]': item.isCurrentMonth && !item.isWeekend && !item.holiday,
                                          'text-[#A0A4B0]': !item.isCurrentMonth
                                      }"
                                      x-text="item.day"></span>

                                <template x-if="item.holiday">
                                    <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-amber-200/90 text-amber-900 border border-amber-300">
                                        Holiday
                                    </span>
                                </template>
                            </div>

                            <!-- Holiday Label / Quick Info -->
                            <div class="mt-1 flex-1 flex flex-col justify-end">
                                <template x-if="item.holiday">
                                    <div class="text-[11px] font-extrabold text-amber-900 truncate leading-tight"
                                         x-text="item.holiday.name"
                                         :title="item.holiday.name"></div>
                                </template>
                                <template x-if="!item.holiday && item.isCurrentMonth">
                                    <span class="text-[10px] opacity-0 group-hover:opacity-100 transition flex items-center gap-1 text-[#B86A00] font-semibold">
                                        <i class="fas fa-plus text-[8px]"></i> Set Holiday
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Month Holidays List Card -->
        <div class="bg-white border border-[#D9DCE5] rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-sm text-[#1A1E2C] flex items-center gap-2">
                    <i class="fas fa-list-check text-[#B86A00]"></i>
                    <span>Configured Holidays for <span x-text="calMonthName"></span></span>
                    <span class="bg-[#ECEEF3] text-[#1A1E2C] text-xs font-semibold px-2 py-0.5 rounded-full"
                          x-text="monthHolidaysList.length"></span>
                </h3>
            </div>

            <template x-if="monthHolidaysList.length === 0">
                <div class="p-8 text-center text-[#5D6375] bg-[#F4F5F8] rounded-xl border border-dashed border-[#D9DCE5]">
                    <i class="fas fa-calendar-xmark text-2xl text-[#A0A4B0] mb-2 block"></i>
                    <p class="text-xs font-semibold text-[#1A1E2C]">No holidays set for this month yet.</p>
                    <p class="text-[11px] text-[#5D6375] mt-0.5">Click any day on the calendar grid above or use the "Add Holiday" button to configure holidays.</p>
                </div>
            </template>

            <template x-if="monthHolidaysList.length > 0">
                <div class="divide-y divide-[#ECEEF3] border border-[#ECEEF3] rounded-xl overflow-hidden">
                    <template x-for="hol in monthHolidaysList" :key="hol.date">
                        <div class="p-3.5 flex items-center justify-between gap-3 hover:bg-[#FAFBFD] transition">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-900 font-extrabold flex flex-col items-center justify-center leading-none border border-amber-300/80">
                                    <span class="text-xs" x-text="hol.dayNum"></span>
                                    <span class="text-[9px] uppercase font-bold text-amber-700" x-text="hol.dayName"></span>
                                </div>
                                <div>
                                    <div class="font-bold text-xs sm:text-sm text-[#1A1E2C]" x-text="hol.name"></div>
                                    <div class="text-[11px] text-[#5D6375] flex items-center gap-2 mt-0.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold border"
                                              :class="getColorBadgeClass(hol.type_color || 'amber')"
                                              x-text="hol.type_name || (hol.type + ' Holiday')"></span>
                                        <span>·</span>
                                        <span x-text="hol.formattedDate"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="openAddHolidayModal(hol.date)"
                                        class="px-2.5 py-1.5 text-xs text-[#5D6375] hover:text-[#B86A00] hover:bg-[#ECEEF3] rounded-lg transition" title="Edit Holiday">
                                    <i class="fas fa-pencil"></i>
                                </button>
                                <button type="button" @click="removeHoliday(hol.id, hol.date)"
                                        class="px-2.5 py-1.5 text-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition" title="Remove Holiday">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Add/Edit Holiday Modal -->
        <div x-show="holidayModalOpen" x-cloak
             class="fixed inset-0 bg-[#0F121C]/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-[#D9DCE5] overflow-hidden"
                 @click.away="closeHolidayModal()">
                <div class="px-5 py-4 border-b border-[#D9DCE5] flex items-center justify-between bg-[#F4F5F8]">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-full bg-amber-100 text-[#B86A00] flex items-center justify-center text-xs">
                            <i class="fas fa-calendar-plus"></i>
                        </span>
                        <h3 class="font-bold text-sm text-[#1A1E2C]">Configure Holiday</h3>
                    </div>
                    <button type="button" @click="closeHolidayModal()" class="text-[#5D6375] hover:text-[#1A1E2C] text-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="saveHolidayForm()" class="p-5 space-y-4">
                    <div class="space-y-1.5">
                        <label for="hol_date" class="text-xs font-bold text-[#5D6375] uppercase tracking-wider block">
                            Date <span class="text-rose-600">*</span>
                        </label>
                        <input type="date" id="hol_date" required x-model="holidayForm.date"
                               class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-3 py-2.5 text-sm font-medium text-[#1A1E2C] focus:outline-none focus:ring-2 focus:ring-[#B86A00]">
                    </div>

                    <div class="space-y-1.5">
                        <label for="hol_name" class="text-xs font-bold text-[#5D6375] uppercase tracking-wider block">
                            Holiday Name <span class="text-rose-600">*</span>
                        </label>
                        <input type="text" id="hol_name" required x-model="holidayForm.name"
                               placeholder="e.g. Poya Day, Diwali, Christmas Day, National Holiday..."
                               class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-3 py-2.5 text-sm font-medium text-[#1A1E2C] focus:outline-none focus:ring-2 focus:ring-[#B86A00]">
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="hol_type" class="text-xs font-bold text-[#5D6375] uppercase tracking-wider block">
                                Holiday Type <span class="text-rose-600">*</span>
                            </label>
                            <button type="button" @click="openHolidayTypesModal()" class="text-[11px] text-[#B86A00] hover:underline font-bold flex items-center gap-1 cursor-pointer">
                                <i class="fas fa-plus text-[9px]"></i> Manage Types
                            </button>
                        </div>
                        <select id="hol_type" x-model="holidayForm.type" required
                                class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-3 py-2.5 text-sm font-medium text-[#1A1E2C] focus:outline-none focus:ring-2 focus:ring-[#B86A00]">
                            <template x-for="ht in holidayTypes" :key="ht.code">
                                <option :value="ht.code" x-text="ht.name" :selected="holidayForm.type === ht.code"></option>
                            </template>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#D9DCE5]">
                        <button type="button" @click="closeHolidayModal()"
                                class="px-4 py-2 text-xs font-semibold text-[#5D6375] hover:text-[#1A1E2C] rounded-lg hover:bg-[#ECEEF3] transition">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSavingHoliday"
                                class="px-4 py-2 text-xs font-bold text-white bg-[#B86A00] hover:bg-[#995800] rounded-lg transition shadow-sm flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                            <i class="fas fa-check"></i>
                            <span x-text="isSavingHoliday ? 'Saving...' : 'Save Holiday'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Manage Holiday Types Modal -->
        <div x-show="holidayTypesModalOpen" x-cloak
             class="fixed inset-0 bg-[#0F121C]/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-[#D9DCE5] overflow-hidden"
                 @click.away="closeHolidayTypesModal()">
                <!-- Modal Header -->
                <div class="px-5 py-4 border-b border-[#D9DCE5] flex items-center justify-between bg-[#F4F5F8]">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-full bg-amber-100 text-[#B86A00] flex items-center justify-center text-xs">
                            <i class="fas fa-tags"></i>
                        </span>
                        <div>
                            <h3 class="font-bold text-sm text-[#1A1E2C]">Holiday Types &amp; Categories</h3>
                            <p class="text-[11px] text-[#5D6375]">Manage holiday classifications for staff Late Work scheduling</p>
                        </div>
                    </div>
                    <button type="button" @click="closeHolidayTypesModal()" class="text-[#5D6375] hover:text-[#1A1E2C] text-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-5 space-y-5">
                    <!-- Existing Holiday Types List -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-[#5D6375] uppercase tracking-wider">Configured Types</span>
                            <span class="text-xs text-[#5D6375]" x-text="holidayTypes.length + ' types'"></span>
                        </div>
                        <div class="max-h-52 overflow-y-auto divide-y divide-[#ECEEF3] border border-[#ECEEF3] rounded-xl bg-white">
                            <template x-for="ht in holidayTypes" :key="ht.id || ht.code">
                                <div class="p-3 flex items-center justify-between gap-3 hover:bg-[#FAFBFD] transition">
                                    <div class="flex items-center gap-2.5">
                                        <span class="px-2.5 py-1 rounded-md text-xs font-bold border"
                                              :class="getColorBadgeClass(ht.color)"
                                              x-text="ht.name"></span>
                                        <span class="text-[10px] text-[#A0A4B0] font-mono hidden sm:inline" x-text="'code: ' + ht.code"></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <template x-if="ht.is_system">
                                            <span class="text-[10px] font-semibold text-[#5D6375] bg-[#ECEEF3] px-2 py-0.5 rounded-full">
                                                Default
                                            </span>
                                        </template>
                                        <template x-if="!ht.is_system">
                                            <button type="button" @click="removeHolidayType(ht.id, ht.name)"
                                                    class="px-2 py-1 text-xs text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg transition cursor-pointer"
                                                    title="Delete holiday type">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Add New Holiday Type Form -->
                    <form @submit.prevent="saveHolidayTypeForm()" class="bg-[#F4F5F8] border border-[#D9DCE5] rounded-xl p-4 space-y-3">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-[#1A1E2C]">
                            <i class="fas fa-plus text-[#B86A00]"></i>
                            <span>Add New Holiday Type</span>
                        </div>

                        <div class="space-y-1">
                            <label for="new_type_name" class="text-[11px] font-bold text-[#5D6375] uppercase block">
                                Type Name <span class="text-rose-600">*</span>
                            </label>
                            <input type="text" id="new_type_name" required x-model="newTypeForm.name"
                                   placeholder="e.g. Poya Day, Special Statutory, Cultural Day..."
                                   class="w-full bg-white border border-[#D9DCE5] rounded-lg px-3 py-2 text-xs font-medium text-[#1A1E2C] focus:outline-none focus:ring-2 focus:ring-[#B86A00]">
                        </div>

                        <!-- Color Selection Chips -->
                        <div class="space-y-1">
                            <label class="text-[11px] font-bold text-[#5D6375] uppercase block">Badge Theme Color</label>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <template x-for="c in ['amber', 'indigo', 'emerald', 'purple', 'rose', 'sky', 'orange', 'teal']" :key="c">
                                    <button type="button" @click="newTypeForm.color = c"
                                            class="px-2.5 py-1 rounded-lg text-xs font-bold border transition flex items-center gap-1 cursor-pointer"
                                            :class="getColorBadgeClass(c) + (newTypeForm.color === c ? ' ring-2 ring-offset-1 ring-[#1A1E2C]' : ' opacity-70 hover:opacity-100')">
                                        <span class="capitalize" x-text="c"></span>
                                        <i x-show="newTypeForm.color === c" class="fas fa-check text-[9px]"></i>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="pt-2 flex justify-end">
                            <button type="submit" :disabled="isSavingType || !newTypeForm.name.trim()"
                                    class="bg-[#B86A00] hover:bg-[#995800] text-white text-xs font-bold px-4 py-2 rounded-lg transition shadow-xs flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                                <i class="fas fa-plus"></i>
                                <span x-text="isSavingType ? 'Adding...' : 'Add Type'"></span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="px-5 py-3 border-t border-[#D9DCE5] bg-[#F4F5F8] flex justify-end">
                    <button type="button" @click="closeHolidayTypesModal()"
                            class="px-4 py-1.5 text-xs font-bold text-[#1A1E2C] hover:bg-[#ECEEF3] rounded-lg transition cursor-pointer">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Decline Modal -->
    <div x-show="declineModalOpen" x-cloak
         class="fixed inset-0 bg-[#0F121C]/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-[#D9DCE5] overflow-hidden"
             @click.away="closeDeclineModal()">
            <div class="px-5 py-4 border-b border-[#D9DCE5] flex items-center justify-between bg-[#F4F5F8]">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center text-xs">
                        <i class="fas fa-times"></i>
                    </span>
                    <h3 class="font-bold text-sm text-[#1A1E2C]">Decline Request: <span class="font-mono text-rose-700" x-text="declineRef"></span></h3>
                </div>
                <button type="button" @click="closeDeclineModal()" class="text-[#5D6375] hover:text-[#1A1E2C] text-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form :action="'{{ url('late-work') }}/' + declineId + '/decline'" method="POST" class="p-5 space-y-4">
                @csrf
                <p class="text-xs text-[#5D6375]">
                    Requester: <strong class="text-[#1A1E2C]" x-text="declineStaff"></strong>. Please provide a reason for declining this request. This note is required and will be sent directly to the staff member by email.
                </p>

                <div class="space-y-1.5">
                    <label for="decline_note" class="text-xs font-bold text-[#5D6375] uppercase tracking-wider block">
                        Reason for Rejection <span class="text-rose-600">*</span>
                    </label>
                    <textarea name="note" id="decline_note" rows="3" required x-model="declineNote"
                              placeholder="e.g. Budget reached for this sprint, or task can be completed tomorrow morning during standard working hours..."
                              class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg p-3 text-xs sm:text-sm text-[#1A1E2C] focus:outline-none focus:ring-2 focus:ring-rose-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#D9DCE5]">
                    <button type="button" @click="closeDeclineModal()"
                            class="px-4 py-2 text-xs font-semibold text-[#5D6375] hover:text-[#1A1E2C] rounded-lg hover:bg-[#ECEEF3] transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg transition shadow-sm flex items-center gap-1.5">
                        <i class="fas fa-times"></i> Confirm Decline
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Re-appeal Modal -->
    <div x-show="reappealModalOpen" x-cloak
         class="fixed inset-0 bg-[#0F121C]/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-[#D9DCE5] overflow-hidden"
             @click.away="closeReappealModal()">
            <div class="px-5 py-4 border-b border-[#D9DCE5] flex items-center justify-between bg-[#F4F5F8]">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center text-xs">
                        <i class="fas fa-undo"></i>
                    </span>
                    <h3 class="font-bold text-sm text-[#1A1E2C]">Re-appeal Request: <span class="font-mono text-[#B86A00]" x-text="reappealData.ref"></span></h3>
                </div>
                <button type="button" @click="closeReappealModal()" class="text-[#5D6375] hover:text-[#1A1E2C] text-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form :action="'{{ url('late-work') }}/' + reappealData.id + '/reappeal'" method="POST" class="p-5 space-y-4">
                @csrf

                <!-- Previous Rejection Note -->
                <div x-show="reappealData.hod_note" class="bg-rose-50 border border-rose-200 rounded-lg p-3 text-xs text-rose-900 space-y-1">
                    <span class="text-[10px] font-bold text-rose-700 uppercase block tracking-wider">
                        Decline Note from <span x-text="reappealData.decided_by_name || 'Approver'"></span>
                    </span>
                    <p class="italic text-rose-800" x-text="'&ldquo;' + reappealData.hod_note + '&rdquo;'"></p>
                </div>

                <!-- Re-appeal Explanation (Required) -->
                <div class="space-y-1.5">
                    <label for="reappeal_notes" class="text-xs font-bold text-[#5D6375] uppercase tracking-wider block">
                        Re-appeal Justification / Explanation <span class="text-rose-600">*</span>
                    </label>
                    <textarea name="reappeal_notes" id="reappeal_notes" rows="3" required x-model="reappealNotes"
                              placeholder="Explain what has changed, why this work is critical tonight, or provide the justification requested by your HOD..."
                              class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg p-3 text-xs sm:text-sm text-[#1A1E2C] focus:outline-none focus:ring-2 focus:ring-[#B86A00]"></textarea>
                </div>

                <!-- Optional Adjustments -->
                <div class="bg-[#F4F5F8] border border-[#D9DCE5] rounded-xl p-3.5 space-y-3">
                    <span class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block">Adjust Working Details (Optional)</span>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="font-semibold text-[#5D6375] block mb-1">Expected Finish Time</label>
                            <input type="time" name="finish_time" x-model="reappealData.finish_time"
                                   class="w-full bg-white border border-[#D9DCE5] rounded-lg px-2.5 py-1.5 text-xs text-[#1A1E2C]">
                        </div>
                        <div x-show="reappealData.type === 'weekend'">
                            <label class="font-semibold text-[#5D6375] block mb-1">Start Time</label>
                            <input type="time" name="start_time" x-model="reappealData.start_time"
                                   class="w-full bg-white border border-[#D9DCE5] rounded-lg px-2.5 py-1.5 text-xs text-[#1A1E2C]">
                        </div>
                    </div>

                    <!-- WFH Toggle for Weekend in Reappeal -->
                    <div x-show="reappealData.type === 'weekend'" class="pt-1">
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-[#1A1E2C] cursor-pointer">
                            <input type="checkbox" name="is_wfh" value="1" x-model="reappealData.is_wfh"
                                   @change="if(reappealData.is_wfh){ reappealData.meal=false; reappealData.taxi=false; }"
                                   class="rounded text-[#B86A00] focus:ring-[#B86A00] w-4 h-4">
                            <span><i class="fas fa-laptop-house text-indigo-600 mr-1"></i>Working from Home (WFH)</span>
                        </label>
                    </div>

                    <!-- Allowances Checkboxes (Hidden when WFH on weekend) -->
                    <div class="flex flex-wrap items-center gap-4 pt-1" x-show="reappealData.type === 'late' || !reappealData.is_wfh">
                        <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1A1E2C] cursor-pointer">
                            <input type="checkbox" name="meal" value="1" x-model="reappealData.meal"
                                   class="rounded text-[#B86A00] focus:ring-[#B86A00] w-3.5 h-3.5">
                            <span>Claim Meal (Rs. 1,000)</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1A1E2C] cursor-pointer">
                            <input type="checkbox" name="taxi" value="1" x-model="reappealData.taxi"
                                   class="rounded text-[#B86A00] focus:ring-[#B86A00] w-3.5 h-3.5">
                            <span>PickMe Ride Home</span>
                        </label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Late Work Request Modal (IT Admin & Finance Admin) -->
    @if(auth()->user()->canEditDeleteLateWork())
    <div x-show="editModalOpen" x-cloak
         class="fixed inset-0 bg-[#0F121C]/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 z-50 overflow-y-auto"
         @keydown.escape.window="closeEditModal()">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-[#D9DCE5] overflow-hidden my-auto animate-fade-in"
             @click.away="closeEditModal()">
            <!-- Modal Header -->
            <div class="px-5 sm:px-6 py-4 border-b border-[#D9DCE5] flex items-center justify-between bg-[#F4F5F8]">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-amber-100 text-[#B86A00] flex items-center justify-center text-base font-bold shadow-xs">
                        <i class="fas fa-edit"></i>
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-[#B86A00] uppercase tracking-wider">Admin Override</span>
                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded bg-amber-200 text-amber-900" x-text="editData.status"></span>
                        </div>
                        <h3 class="font-bold text-base sm:text-lg text-[#1A1E2C]">
                            Edit Request: <span class="font-mono text-[#B86A00]" x-text="editData.ref"></span>
                        </h3>
                    </div>
                </div>
                <button type="button" @click="closeEditModal()"
                        class="text-[#5D6375] hover:text-[#1A1E2C] w-8 h-8 rounded-lg hover:bg-[#ECEEF3] flex items-center justify-center transition cursor-pointer">
                    <i class="fas fa-times text-base"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form :action="'{{ url('late-work') }}/' + editData.id" method="POST">
                @csrf
                @method('PUT')

                <div class="p-5 sm:px-6 space-y-4 max-h-[72vh] overflow-y-auto text-xs sm:text-sm text-[#1A1E2C]">
                    <!-- Row 1: Requester & Department & HOD -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block mb-1">
                                Requester (Staff)
                            </label>
                            <select name="user_id" x-model="editData.user_id"
                                    class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-2.5 py-2 text-xs text-[#1A1E2C] font-medium focus:ring-2 focus:ring-[#B86A00]">
                                @foreach($allUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->department ?: 'No Dept' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block mb-1">
                                Department
                            </label>
                            <select name="department_id" x-model="editData.department_id"
                                    class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-2.5 py-2 text-xs text-[#1A1E2C] font-medium focus:ring-2 focus:ring-[#B86A00]">
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block mb-1">
                                Assigned HOD
                            </label>
                            <select name="hod_id" x-model="editData.hod_id"
                                    class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-2.5 py-2 text-xs text-[#1A1E2C] font-medium focus:ring-2 focus:ring-[#B86A00]">
                                <option value="">-- No HOD --</option>
                                @foreach($allHods as $h)
                                    <option value="{{ $h->id }}">{{ $h->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Row 2: Type, Date, Holiday & Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block mb-1">
                                Shift Type <span class="text-rose-600">*</span>
                            </label>
                            <select name="type" x-model="editData.type" @change="onEditTypeChange()"
                                    class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-2.5 py-2 text-xs text-[#1A1E2C] font-medium focus:ring-2 focus:ring-[#B86A00]">
                                <option value="late">Late Night (Weekday)</option>
                                <option value="weekend">Weekend / Holiday</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block mb-1">
                                Date <span class="text-rose-600">*</span>
                            </label>
                            <input type="date" name="date" x-model="editData.date" required
                                   class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-2.5 py-1.5 text-xs text-[#1A1E2C] font-medium focus:ring-2 focus:ring-[#B86A00]">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block mb-1">
                                Holiday Status
                            </label>
                            <label class="h-[34px] flex items-center gap-2 bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-3 text-xs font-semibold cursor-pointer">
                                <input type="checkbox" name="is_holiday" value="1" x-model="editData.is_holiday"
                                       class="rounded text-[#B86A00] focus:ring-[#B86A00] w-4 h-4">
                                <span>Public Holiday</span>
                            </label>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block mb-1">
                                Request Status <span class="text-rose-600">*</span>
                            </label>
                            <select name="status" x-model="editData.status"
                                    class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-2.5 py-2 text-xs font-bold focus:ring-2 focus:ring-[#B86A00]"
                                    :class="{
                                        'text-emerald-700': editData.status === 'approved',
                                        'text-amber-700': editData.status === 'pending',
                                        'text-rose-700': editData.status === 'declined',
                                        'text-gray-600': editData.status === 'withdrawn'
                                    }">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="declined">Declined</option>
                                <option value="withdrawn">Withdrawn</option>
                            </select>
                        </div>
                    </div>

                    <!-- Row 3: Job Numbers & Job Label -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block mb-1">
                                Job Number(s) <span class="text-rose-600">*</span>
                            </label>
                            <input type="text" name="job_number" x-model="editData.job_number" required
                                   placeholder="e.g. LOOPS/2026/0469, LOOPS/2026/0466"
                                   class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-3 py-2 text-xs font-mono text-[#1A1E2C] focus:ring-2 focus:ring-[#B86A00]">
                            <span class="text-[10px] text-[#5D6375]">Separate multiple jobs with a comma</span>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block mb-1">
                                Job / Client Label (Optional)
                            </label>
                            <input type="text" name="job_label" x-model="editData.job_label"
                                   placeholder="e.g. Client or Campaign name"
                                   class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-3 py-2 text-xs text-[#1A1E2C] focus:ring-2 focus:ring-[#B86A00]">
                        </div>
                    </div>

                    <!-- Row 4: Times & WFH -->
                    <div class="bg-[#F8F9FA] border border-[#D9DCE5] rounded-xl p-3.5 space-y-3">
                        <span class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block">Working Schedule & Hours</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div x-show="editData.type === 'weekend'">
                                <label class="text-[11px] font-semibold text-[#5D6375] block mb-1">Start Time</label>
                                <input type="time" name="start_time" x-model="editData.start_time"
                                       class="w-full bg-white border border-[#D9DCE5] rounded-lg px-2.5 py-1.5 text-xs text-[#1A1E2C]">
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold text-[#5D6375] block mb-1">Finish Time <span class="text-rose-600">*</span></label>
                                <input type="time" name="finish_time" x-model="editData.finish_time" required
                                       class="w-full bg-white border border-[#D9DCE5] rounded-lg px-2.5 py-1.5 text-xs text-[#1A1E2C]">
                            </div>
                            <div x-show="editData.type === 'weekend'" class="flex items-end">
                                <label class="h-[34px] w-full flex items-center gap-2 bg-white border border-[#D9DCE5] rounded-lg px-3 text-xs font-semibold cursor-pointer">
                                    <input type="checkbox" name="is_wfh" value="1" x-model="editData.is_wfh"
                                           @change="if(editData.is_wfh){ editData.meal=false; editData.taxi=false; }"
                                           class="rounded text-[#B86A00] focus:ring-[#B86A00] w-4 h-4">
                                    <span><i class="fas fa-laptop-house text-indigo-600 mr-1"></i>Working From Home</span>
                                </label>
                            </div>
                        </div>

                        <!-- Allowances & Concessions -->
                        <div class="pt-2 border-t border-[#D9DCE5] space-y-2">
                            <span class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block">Allowances &amp; Concessions</span>
                            <div class="flex flex-wrap items-center gap-4">
                                <label class="inline-flex items-center gap-2 text-xs font-semibold text-[#1A1E2C] cursor-pointer"
                                       :class="{ 'opacity-50 pointer-events-none': editData.type === 'weekend' && editData.is_wfh }">
                                    <input type="checkbox" name="meal" value="1" x-model="editData.meal"
                                           :disabled="editData.type === 'weekend' && editData.is_wfh"
                                           class="rounded text-[#B86A00] focus:ring-[#B86A00] w-4 h-4">
                                    <span><i class="fas fa-utensils text-amber-600 mr-1"></i>Meal Allowance (Rs. 1,000)</span>
                                </label>

                                <label class="inline-flex items-center gap-2 text-xs font-semibold text-[#1A1E2C] cursor-pointer"
                                       :class="{ 'opacity-50 pointer-events-none': editData.type === 'weekend' && editData.is_wfh }">
                                    <input type="checkbox" name="taxi" value="1" x-model="editData.taxi"
                                           :disabled="editData.type === 'weekend' && editData.is_wfh"
                                           class="rounded text-[#B86A00] focus:ring-[#B86A00] w-4 h-4">
                                    <span><i class="fas fa-car text-indigo-600 mr-1"></i>PickMe Ride Home</span>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                                <div x-show="editData.type === 'weekend'">
                                    <label class="text-[11px] font-semibold text-[#5D6375] block mb-1">Lieu Leave Entitlement</label>
                                    <select name="lieu" x-model="editData.lieu"
                                            class="w-full bg-white border border-[#D9DCE5] rounded-lg px-2.5 py-1.5 text-xs text-[#1A1E2C]">
                                        <option value="">None (No Leave)</option>
                                        <option value="half">Half day off (0.5)</option>
                                        <option value="full">Full day off (1.0)</option>
                                        <option value="one_and_half">1.5 days off (1 day &amp; half)</option>
                                    </select>
                                </div>
                                <div x-show="editData.type === 'late'">
                                    <label class="text-[11px] font-semibold text-[#5D6375] block mb-1">Next Day Reporting Time</label>
                                    <select name="next_day" x-model="editData.next_day"
                                            class="w-full bg-white border border-[#D9DCE5] rounded-lg px-2.5 py-1.5 text-xs text-[#1A1E2C]">
                                        <option value="normal">9:00 AM (Standard)</option>
                                        <option value="10am">10:00 AM</option>
                                        <option value="11am">11:00 AM</option>
                                        <option value="half">Half day off</option>
                                        <option value="full">Full day off</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 5: Work Reason / Description -->
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block">
                            Reason / Description of Work <span class="text-[10px] font-normal text-[#8A90A2] lowercase">(optional)</span>
                        </label>
                        <textarea name="reason" rows="2" x-model="editData.reason"
                                  class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg p-3 text-xs sm:text-sm text-[#1A1E2C] focus:ring-2 focus:ring-[#B86A00]"></textarea>
                    </div>

                    <!-- Row 6: Approver / Admin Note -->
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-[#5D6375] uppercase tracking-wider block">
                            HOD / Admin Note
                        </label>
                        <input type="text" name="hod_note" x-model="editData.hod_note"
                               placeholder="Optional approval or decline notes..."
                               class="w-full bg-[#F4F5F8] border border-[#D9DCE5] rounded-lg px-3 py-2 text-xs text-[#1A1E2C] focus:ring-2 focus:ring-[#B86A00]">
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-5 sm:px-6 py-4 border-t border-[#D9DCE5] bg-[#F4F5F8] flex items-center justify-between">
                    <span class="text-xs text-[#5D6375]">
                        <i class="fas fa-info-circle text-[#B86A00] mr-1"></i> Changes will sync with linked Petty Cash records.
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="closeEditModal()"
                                class="px-4 py-2 text-xs font-semibold text-[#5D6375] hover:text-[#1A1E2C] rounded-lg hover:bg-[#ECEEF3] transition">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition shadow-sm flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-save text-[11px]"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Policy & Instructions Modal (3.11 After Office Hours) -->
    <div x-show="instructionsModalOpen" x-cloak
         class="fixed inset-0 bg-[#0F121C]/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 z-50 overflow-y-auto"
         @keydown.escape.window="instructionsModalOpen = false">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-[#D9DCE5] overflow-hidden my-auto animate-fade-in"
             @click.away="instructionsModalOpen = false">
            <!-- Modal Header -->
            <div class="px-5 sm:px-6 py-4 border-b border-[#D9DCE5] flex items-center justify-between bg-[#F4F5F8]">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-[#FBEBD2] text-[#B86A00] flex items-center justify-center text-base font-bold shadow-xs">
                        <i class="fas fa-book-open"></i>
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-[#995800] uppercase tracking-wider">Company Guidelines</span>
                            <span class="text-[11px] px-2 py-0.5 rounded-md bg-[#ECEEF3] text-[#5D6375] font-semibold">Attendance &amp; Leave</span>
                        </div>
                        <h3 class="font-bold text-base sm:text-lg text-[#1A1E2C] lw-title">3.11 After Office Hours</h3>
                    </div>
                </div>
                <button type="button" @click="instructionsModalOpen = false"
                        class="text-[#5D6375] hover:text-[#1A1E2C] w-8 h-8 rounded-lg hover:bg-[#ECEEF3] flex items-center justify-center transition cursor-pointer">
                    <i class="fas fa-times text-base"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-5 sm:p-6 space-y-4 max-h-[75vh] overflow-y-auto text-xs sm:text-sm text-[#1A1E2C] leading-relaxed">
                <!-- Preamble -->
                <div class="bg-[#F8F9FA] p-3.5 rounded-xl border border-[#D9DCE5] text-[#5D6375] leading-relaxed">
                    Working late through the night, weekends/ public holidays to complete work assignments are part of agency life. However, this is something we would like to discourage unless absolutely necessary.
                </div>

                <!-- Section: Please note following -->
                <div class="space-y-2">
                    <h4 class="font-bold text-[#1A1E2C] text-xs uppercase tracking-wider text-[#5D6375]">Please note following:</h4>
                    <div class="space-y-2.5">
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-[#ECEEF3] text-[#1A1E2C] font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">a</span>
                            <div>
                                <strong>Prior authorization by your department head is required</strong> for working after office hours or during weekends and holidays. In the absence of this authorization, we discourage staff from remaining in the office after 8pm for official work unless they want to relax and socialize with other staff.
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-[#ECEEF3] text-[#1A1E2C] font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">b</span>
                            <div>
                                <strong>Meal requests and transport requests</strong> are administered after 8pm (Pls refer company meal and transportation policy for more details).
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Concessions Table -->
                <div class="pt-1 space-y-2">
                    <p class="font-medium text-[#1A1E2C]">
                        Please note the following concessions should you work late, subject to prior approval from the Head of Department (HOD):
                    </p>

                    <div class="rounded-xl border border-[#D9DCE5] overflow-hidden shadow-xs">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-[#F4F5F8] border-b border-[#D9DCE5] font-bold text-[#1A1E2C]">
                                    <th class="p-3">Sign Out Time</th>
                                    <th class="p-3">Time to report to work</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#ECEEF3]">
                                <tr class="hover:bg-amber-50/50 transition">
                                    <td class="p-3 font-medium text-[#1A1E2C]">Between 9.00 p.m. - 10.30 p.m.</td>
                                    <td class="p-3 font-bold text-[#B86A00]">10.00 a.m.</td>
                                </tr>
                                <tr class="hover:bg-amber-50/50 transition">
                                    <td class="p-3 font-medium text-[#1A1E2C]">Between 10.31 p.m. – 12.00 p.m.</td>
                                    <td class="p-3 font-bold text-[#B86A00]">11.00 a.m.</td>
                                </tr>
                                <tr class="hover:bg-emerald-50/50 transition">
                                    <td class="p-3 font-medium text-[#1A1E2C]">Between 12.01 – 3.00 a.m.</td>
                                    <td class="p-3 font-bold text-emerald-700">Half day off</td>
                                </tr>
                                <tr class="hover:bg-emerald-50/50 transition">
                                    <td class="p-3 font-medium text-[#1A1E2C]">Beyond 3.00 a.m.</td>
                                    <td class="p-3 font-bold text-emerald-700">Full day off</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Section: Note (c, d, e, f) -->
                <div class="pt-1 space-y-2">
                    <h4 class="font-bold text-[#1A1E2C] text-xs uppercase tracking-wider text-[#5D6375]">Note:</h4>
                    <div class="space-y-2.5">
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-[#ECEEF3] text-[#1A1E2C] font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">c</span>
                            <div>
                                Both <strong>In and Out times must be recorded in the system</strong>, and a <strong>Duty leave approved by HOD is mandatory</strong> for any request to report late the following day in order for it to be considered.
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-[#ECEEF3] text-[#1A1E2C] font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">d</span>
                            <div>
                                This benefit is limited to staff who work outside of office hours. It <strong>cannot be used by staff who leave the office in the evening and return to work later</strong>.
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-[#ECEEF3] text-[#1A1E2C] font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">e</span>
                            <div>
                                Benefit is limited to staff who arrive very late to office regularly and work until late.
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-[#ECEEF3] text-[#1A1E2C] font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">f</span>
                            <div>
                                If you work on a weekend or a public holiday, you are <strong>eligible for compensatory lieu leave</strong>: minimum 4 hours earns a half day leave, 8 hours earns a full day leave, and 12 hours earns 1.5 days leave (1 day &amp; half day). Shifts under 4 hours are not saved as leave. For remote duties, Work from Home (WFH) is available (excluding meal and ride allowances).
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-5 sm:px-6 py-3.5 border-t border-[#D9DCE5] bg-[#F4F5F8] flex items-center justify-between">
                <span class="text-xs text-[#5D6375]">Loops Integrated · Human Resources Policy</span>
                <button type="button" @click="instructionsModalOpen = false"
                        class="px-4 py-2 text-xs font-bold text-white bg-[#1A1E2C] hover:bg-[#2d334a] rounded-lg transition shadow-sm flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-check"></i>
                    <span>Got it</span>
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function lateWorkApp() {
    return {
        activeTab: '{{ $tab }}',
        type: 'late',
        date: '{{ date("Y-m-d") }}',
        isHoliday: false,
        startTime: '09:00',
        finishTime: '21:00',
        isWfh: {{ old('is_wfh') ? 'true' : 'false' }},
        meal: {{ old('meal') ? 'true' : 'false' }},
        taxi: {{ old('taxi') ? 'true' : 'false' }},
        lieu: '{{ old('lieu', 'full') }}',
        reason: '{{ addslashes(old('reason', '')) }}',
        jobNumber: '{{ addslashes(old('job_number', '')) }}',
        jobLabel: '{{ addslashes(old('job_label', '')) }}',
        selectedJobs: @json($initialSelectedJobs),
        selectedDeptId: '{{ old("dept_id") ?: ($userDeptId ?? ($departments->firstWhere("name", $user->department)->id ?? "")) }}',
        hodHint: '',
        eligibilityWarning: '',
        cutoffNotice: '',
        isPastCutoff: false,
        isEligible: false,
        nextDayReportKey: '10am',
        nextDayReportText: '10.00 a.m.',
        nextDayPolicyText: 'Between 9.00 p.m. - 10.30 p.m.',
        nextDayDateText: '',
        timeWarning: '',

        // Holidays and Calendar State
        holidaysMap: @json($holidaysMap ?? []),
        canManageHolidays: {{ $canManageHolidays ? 'true' : 'false' }},

        // Custom Calendar Picker for Request Form
        showDatePicker: false,
        pickerYear: new Date().getFullYear(),
        pickerMonth: new Date().getMonth(),

        // Holiday Configuration Tab (HR & IT)
        calYear: new Date().getFullYear(),
        calMonth: new Date().getMonth(),
        holidayModalOpen: false,
        holidayForm: {
            date: '',
            name: '',
            type: 'public',
            description: '',
        },
        isSavingHoliday: false,

        // Holiday Types State (HR & IT Admin)
        holidayTypes: @json($holidayTypes ?? []),
        holidayTypesModalOpen: false,
        newTypeForm: {
            name: '',
            color: 'amber',
        },
        isSavingType: false,

        // Pre-loaded departments with HOD names
        deptsData: @json($departmentsWithHods),

        // Instructions Modal State
        instructionsModalOpen: false,

        // Decline Modal State
        declineModalOpen: false,
        declineId: null,
        declineRef: '',
        declineStaff: '',
        declineNote: '',

        openDeclineModal(id, ref, staffName, prefillNote = '') {
            this.declineId = id;
            this.declineRef = ref;
            this.declineStaff = staffName;
            this.declineNote = prefillNote || '';
            this.declineModalOpen = true;
        },

        closeDeclineModal() {
            this.declineModalOpen = false;
            this.declineId = null;
            this.declineRef = '';
            this.declineStaff = '';
            this.declineNote = '';
        },

        // Re-appeal Modal State
        reappealModalOpen: false,
        reappealData: {
            id: null,
            ref: '',
            date: '',
            jobs: '',
            type: 'late',
            start_time: '',
            finish_time: '',
            is_wfh: false,
            meal: false,
            taxi: false,
            lieu: 'full',
            reason: '',
            hod_note: '',
            decided_by_name: '',
        },
        reappealNotes: '',

        openReappealModal(data) {
            this.reappealData = Object.assign({}, data);
            this.reappealNotes = '';
            this.reappealModalOpen = true;
        },

        closeReappealModal() {
            this.reappealModalOpen = false;
            this.reappealNotes = '';
        },

        // Edit Modal State (IT Admin & Finance Admin)
        editModalOpen: false,
        editData: {
            id: null,
            ref: '',
            user_id: '',
            department_id: '',
            hod_id: '',
            type: 'late',
            date: '',
            is_holiday: false,
            is_wfh: false,
            job_numbers: [],
            job_number: '',
            job_label: '',
            start_time: '',
            finish_time: '',
            meal: false,
            taxi: false,
            lieu: '',
            next_day: '',
            reason: '',
            status: 'pending',
            hod_note: '',
        },

        openEditModal(data) {
            this.editData = Object.assign({
                id: null,
                ref: '',
                user_id: '',
                department_id: '',
                hod_id: '',
                type: 'late',
                date: '',
                is_holiday: false,
                is_wfh: false,
                job_numbers: [],
                job_number: '',
                job_label: '',
                start_time: '',
                finish_time: '',
                meal: false,
                taxi: false,
                lieu: '',
                next_day: '',
                reason: '',
                status: 'pending',
                hod_note: '',
            }, data);
            this.editModalOpen = true;
        },

        closeEditModal() {
            this.editModalOpen = false;
        },

        onEditTypeChange() {
            if (this.editData.type === 'late') {
                this.editData.is_wfh = false;
                this.editData.lieu = '';
            }
        },

        // Petty Cash Popup Dialog & Allowance State
        pettyCashModalOpen: false,
        mealCategoryId: {{ $mealCategory ? $mealCategory->id : 'null' }},
        travelCategoryId: {{ $travelCategory ? $travelCategory->id : 'null' }},
        expenseCategories: @json($expenseCategories ?? []),
        pettyCashItems: [],
        proofInputs: [{ id: 1, name: '' }],
        nextProofId: 2,
        pettyCashExtraNotes: '{{ addslashes(old('petty_cash_extra_notes', '')) }}',

        onAllowanceToggle(type) {
            if (type === 'meal') {
                if (this.meal) {
                    this.syncAllowanceItems('meal', true);
                    this.pettyCashModalOpen = true;
                } else {
                    this.syncAllowanceItems('meal', false);
                }
            } else if (type === 'taxi') {
                if (this.taxi) {
                    this.syncAllowanceItems('taxi', true);
                    this.pettyCashModalOpen = true;
                } else {
                    this.syncAllowanceItems('taxi', false);
                }
            }
        },

        syncAllowanceItems(type, add) {
            if (type === 'meal') {
                if (add) {
                    const exists = this.pettyCashItems.some(it => String(it.expense_category_id) === String(this.mealCategoryId));
                    if (!exists) {
                        this.pettyCashItems.push({
                            expense_category_id: this.mealCategoryId ? String(this.mealCategoryId) : '',
                            amount: 1000.00,
                            description: 'Meal allowance',
                            attendees: ['', '', '', '', '']
                        });
                    }
                } else {
                    this.pettyCashItems = this.pettyCashItems.filter(it => String(it.expense_category_id) !== String(this.mealCategoryId));
                }
            } else if (type === 'taxi') {
                if (add) {
                    const exists = this.pettyCashItems.some(it => String(it.expense_category_id) === String(this.travelCategoryId));
                    if (!exists) {
                        this.pettyCashItems.push({
                            expense_category_id: this.travelCategoryId ? String(this.travelCategoryId) : '',
                            amount: '',
                            description: 'PickMe ride home',
                            attendees: []
                        });
                    }
                } else {
                    this.pettyCashItems = this.pettyCashItems.filter(it => String(it.expense_category_id) !== String(this.travelCategoryId));
                }
            }
        },

        openPettyCashModal() {
            if (this.pettyCashItems.length === 0) {
                if (this.meal) this.syncAllowanceItems('meal', true);
                if (this.taxi) this.syncAllowanceItems('taxi', true);
                if (this.pettyCashItems.length === 0) {
                    this.addPettyCashItem();
                }
            }
            this.pettyCashModalOpen = true;
        },

        addPettyCashItem() {
            this.pettyCashItems.push({
                expense_category_id: '',
                amount: '',
                description: '',
                attendees: []
            });
        },

        removePettyCashItem(index) {
            this.pettyCashItems.splice(index, 1);
        },

        isMealCategory(catId) {
            if (!catId) return false;
            if (this.mealCategoryId && String(catId) === String(this.mealCategoryId)) return true;
            const cat = this.expenseCategories.find(c => String(c.id) === String(catId));
            return cat ? /meal/i.test(cat.name) : false;
        },

        onCategoryChange(item) {
            if (this.isMealCategory(item.expense_category_id)) {
                if (!item.attendees || item.attendees.length === 0) {
                    item.attendees = ['', '', '', '', ''];
                }
                if (!item.amount || parseFloat(item.amount) === 0) {
                    item.amount = 1000.00;
                }
                if (!item.description) {
                    item.description = 'Meal allowance';
                }
            } else if (this.isTravelCategory(item.expense_category_id)) {
                if (!item.description) {
                    item.description = 'PickMe ride home';
                }
            }
        },

        isTravelCategory(catId) {
            if (!catId) return false;
            if (this.travelCategoryId && String(catId) === String(this.travelCategoryId)) return true;
            const cat = this.expenseCategories.find(c => String(c.id) === String(catId));
            return cat ? /(travel|taxi|pickme|ride)/i.test(cat.name) : false;
        },

        get hasTravelItem() {
            return this.pettyCashItems.some(it => this.isTravelCategory(it.expense_category_id));
        },

        get hasValidTravelAmount() {
            if (!this.hasTravelItem && !this.taxi) return true;
            const travelItems = this.pettyCashItems.filter(it => this.isTravelCategory(it.expense_category_id));
            if (travelItems.length === 0) {
                return false;
            }
            return travelItems.every(it => it.amount !== '' && it.amount !== null && !isNaN(parseFloat(it.amount)) && parseFloat(it.amount) > 0);
        },

        get hasProofAttached() {
            return this.proofInputs.some(p => !!p.name && p.name.trim() !== '');
        },

        get canDonePettyCash() {
            return true;
        },

        get travelValidationHint() {
            return '';
        },

        confirmPettyCashModal() {
            this.pettyCashModalOpen = false;
        },

        closePettyCashModal() {
            this.pettyCashModalOpen = false;
        },

        addProofInput() {
            this.proofInputs.push({ id: this.nextProofId++, name: '' });
        },

        removeProofInput(index) {
            if (this.proofInputs.length > 1) {
                this.proofInputs.splice(index, 1);
            }
        },

        onProofFileSelected(event, index) {
            const file = event.target.files && event.target.files[0];
            if (this.proofInputs[index]) {
                this.proofInputs[index].name = file ? file.name : '';
            }
        },

        get pettyCashTotal() {
            if (!this.pettyCashItems || this.pettyCashItems.length === 0) return 0;
            return this.pettyCashItems.reduce((sum, it) => sum + (parseFloat(it.amount) || 0), 0);
        },

        get pettyCashTotalFormatted() {
            return this.pettyCashTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        get proofsCountText() {
            const count = this.proofInputs.filter(p => !!p.name).length;
            if (count === 0) return 'No proofs attached yet';
            return `${count} proof file${count === 1 ? '' : 's'} attached`;
        },

        init() {
            if (this.date) {
                const parts = this.date.split('-').map(Number);
                if (parts.length === 3 && !isNaN(parts[0]) && !isNaN(parts[1])) {
                    this.pickerYear = parts[0];
                    this.pickerMonth = parts[1] - 1;
                    const dt = new Date(parts[0], parts[1] - 1, parts[2]);
                    const dayOfWeek = dt.getDay();
                    const isWk = (dayOfWeek === 0 || dayOfWeek === 6);
                    const hol = this.holidaysMap ? this.holidaysMap[this.date] : null;
                    if (hol) {
                        this.type = 'weekend';
                        this.isHoliday = true;
                    } else if (isWk) {
                        this.type = 'weekend';
                        this.isHoliday = false;
                    }
                }
            }

            this.updateHodHint();
            this.checkEligibility();
            this.updateWeekendLieu();

            if (this.meal) {
                this.syncAllowanceItems('meal', true);
            }
            if (this.taxi) {
                this.syncAllowanceItems('taxi', true);
            }

            @if(request('reappeal_id'))
                @php
                    $reappealTarget = $myRequests->firstWhere('id', (int)request('reappeal_id'));
                @endphp
                @if($reappealTarget && $reappealTarget->status === 'declined')
                    this.$nextTick(() => {
                        this.openReappealModal({
                            id: {{ $reappealTarget->id }},
                            ref: '{{ $reappealTarget->reference_number }}',
                            date: '{{ $reappealTarget->date ? $reappealTarget->date->format('D, d M Y') : '' }}',
                            jobs: '{{ addslashes($reappealTarget->job_number) }}',
                            type: '{{ $reappealTarget->type }}',
                            start_time: '{{ $reappealTarget->start_time }}',
                            finish_time: '{{ $reappealTarget->finish_time }}',
                            is_wfh: {{ $reappealTarget->is_wfh ? 'true' : 'false' }},
                            meal: {{ $reappealTarget->meal ? 'true' : 'false' }},
                            taxi: {{ $reappealTarget->taxi ? 'true' : 'false' }},
                            lieu: '{{ $reappealTarget->lieu }}',
                            reason: '{{ addslashes($reappealTarget->reason) }}',
                            hod_note: '{{ addslashes($reappealTarget->hod_note ?? '') }}',
                            decided_by_name: '{{ addslashes($reappealTarget->decidedBy->name ?? 'Approver') }}',
                        });
                    });
                @endif
            @endif

            this.$nextTick(() => {
                if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.select2 !== 'undefined') {
                    const self = this;
                    const $jobSelect = window.jQuery('#job_number_select');

                    $jobSelect.select2({
                        placeholder: 'Search and select job number(s)…',
                        width: '100%',
                        closeOnSelect: false,
                        templateSelection: function (item) {
                            if (!item.id) return item.text;
                            return item.id;
                        },
                        templateResult: function (item) {
                            if (!item.id) return item.text;
                            return item.text;
                        }
                    });

                    const syncSelect2ToFields = () => {
                        const selectedData = $jobSelect.select2('data') || [];
                        const list = [];
                        const numbers = [];
                        const labels = [];

                        selectedData.forEach(item => {
                            if (item.id) {
                                numbers.push(item.id);
                                const opt = item.element || $jobSelect.find('option[value="' + item.id + '"]')[0];
                                const lbl = opt ? (opt.getAttribute('data-label') || window.jQuery(opt).data('label') || '') : '';
                                list.push({ no: item.id, label: lbl });
                                if (lbl) {
                                    labels.push(lbl);
                                }
                            }
                        });

                        self.selectedJobs = list;
                        self.jobNumber = numbers.join(', ');
                        self.jobLabel = labels.join('; ');

                        // Synchronize DOM elements directly as well
                        window.jQuery('#job_number').val(self.jobNumber);
                        window.jQuery('#job_label').val(self.jobLabel);
                    };

                    $jobSelect.on('change', syncSelect2ToFields);

                    // Sync initial selection if already selected
                    syncSelect2ToFields();
                }
            });
        },

        setTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url.toString());
        },

        onTypeChange() {
            if (this.type === 'weekend') {
                this.finishTime = '17:00';
                this.startTime = '09:00';
                // Pick next Saturday if current date is weekday
                const today = new Date();
                const day = today.getDay();
                if (day !== 0 && day !== 6 && !this.isHoliday) {
                    const daysUntilSat = (6 - day + 7) % 7 || 7;
                    const sat = new Date();
                    sat.setDate(today.getDate() + daysUntilSat);
                    this.date = sat.toISOString().slice(0, 10);
                }
            } else {
                this.finishTime = '21:00';
                this.startTime = '';
                this.isHoliday = false;
                this.isWfh = false;
                const d = new Date();
                this.date = d.toISOString().slice(0, 10);
            }
            this.checkEligibility();
            this.updateWeekendLieu();
        },

        onWfhChange() {
            if (this.isWfh) {
                this.meal = false;
                this.taxi = false;
                this.syncAllowanceItems('meal', false);
                this.syncAllowanceItems('taxi', false);
            }
        },

        onStartTimeChange() {
            this.checkEligibility();
            this.updateWeekendLieu();
        },

        get weekendDurationMinutes() {
            if (this.type !== 'weekend' || !this.startTime || !this.finishTime) return 0;
            const sParts = this.startTime.split(':');
            const fParts = this.finishTime.split(':');
            const sM = parseInt(sParts[0] || 0, 10) * 60 + parseInt(sParts[1] || 0, 10);
            const fM = parseInt(fParts[0] || 0, 10) * 60 + parseInt(fParts[1] || 0, 10);
            let diff = fM - sM;
            if (diff < 0) diff += 24 * 60;
            return diff;
        },

        get weekendDurationHours() {
            return Math.round((this.weekendDurationMinutes / 60) * 10) / 10;
        },

        get weekendDurationText() {
            const m = this.weekendDurationMinutes;
            const hrs = Math.floor(m / 60);
            const mins = m % 60;
            if (hrs === 0) return `${mins}m`;
            if (mins === 0) return `${hrs}h`;
            return `${hrs}h ${mins}m`;
        },

        updateWeekendLieu() {
            if (this.type !== 'weekend') return;
            const hrs = this.weekendDurationHours;
            if (hrs < 4) {
                this.lieu = '';
            } else if (hrs < 8) {
                this.lieu = 'half';
            } else if (hrs < 12) {
                this.lieu = 'full';
            } else {
                this.lieu = 'one_and_half';
            }
        },

        userAssignedHodName: '{{ $userAssignedHod ? addslashes($userAssignedHod->name) : "" }}',
        updateHodHint() {
            if (this.userAssignedHodName) {
                this.hodHint = 'Request goes to HOD: ' + this.userAssignedHodName;
                return;
            }
            if (!this.selectedDeptId) {
                this.hodHint = '';
                return;
            }
            const dept = this.deptsData.find(d => String(d.id) === String(this.selectedDeptId));
            if (!dept) {
                this.hodHint = '';
                return;
            }
            if (dept.hods && dept.hods.length > 0) {
                const names = dept.hods.map(h => h.name).join(' / ');
                this.hodHint = 'Request goes to HOD: ' + names;
            } else {
                this.hodHint = 'No HOD configured for this department. Admin will approve.';
            }
        },

        onFinishTimeChange() {
            if (!this.finishTime) {
                this.timeWarning = '';
                this.checkEligibility();
                return;
            }

            if (this.type === 'late') {
                const parts = this.finishTime.split(':');
                let h = parseInt(parts[0] || 0, 10);
                const m = parseInt(parts[1] || 0, 10);

                // If user entered 12-hour format without PM (09:00, 10:00, 11:00):
                // e.g. user entered "11:00" expecting 11:00 PM:
                // Auto-convert to 23:00 so the picker smoothly adopts 11:00 PM!
                if (h >= 9 && h <= 11) {
                    h += 12;
                    this.finishTime = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                }

                // Strictly validate 8:00 PM (20:00) to 8:00 AM (08:00):
                const isWithinWindow = (h >= 20 || h < 8 || (h === 8 && m === 0));

                if (!isWithinWindow) {
                    this.timeWarning = 'Expected time must be between 8:00 PM and 8:00 AM for weekday late work.';
                } else {
                    this.timeWarning = '';
                }
            } else {
                this.timeWarning = '';
            }

            this.checkEligibility();
            this.updateWeekendLieu();
        },

        checkEligibility() {
            let finish = (this.finishTime || '').trim();
            const parts = finish.split(':');
            let h = parseInt(parts[0] || 0, 10);
            const m = parseInt(parts[1] || 0, 10);

            if (this.type === 'late') {
                if (h >= 9 && h <= 11) {
                    h += 12;
                }
                const isWithinWindow = (h >= 20 || h < 8 || (h === 8 && m === 0));
                this.isEligible = isWithinWindow;
                if (!isWithinWindow && finish) {
                    this.timeWarning = 'Expected time must be between 8:00 PM and 8:00 AM for weekday late work.';
                } else {
                    this.timeWarning = '';
                }
            } else {
                this.isEligible = true;
                this.timeWarning = '';
            }

            if (!this.isEligible || (this.type === 'weekend' && this.isWfh)) {
                this.meal = false;
                this.taxi = false;
                this.syncAllowanceItems('meal', false);
                this.syncAllowanceItems('taxi', false);
                this.eligibilityWarning = (this.type === 'weekend' && this.isWfh)
                    ? 'Meal allowance and PickMe are excluded for Work from Home (WFH).'
                    : 'Meal allowance and PickMe apply only when you work past 8:00 PM.';
            } else {
                this.eligibilityWarning = '';
            }

            // Next day report calculation
            this.calculateNextDayReport(h, m);

            // Cutoff calculation (3:00 PM deadline)
            this.calculateCutoff();
        },

        calculateNextDayReport(h, m) {
            if (!this.finishTime) {
                this.nextDayReportKey = 'normal';
                this.nextDayReportText = '9.00 a.m. (usual start time)';
                this.nextDayPolicyText = 'Prior to 9.00 p.m.';
                return;
            }
            if (this.type === 'late' && h >= 9 && h <= 11) {
                h += 12;
            }
            const totalM = h * 60 + m;

            // Morning hours after midnight up to 8:00 AM (00:00 to 08:00)
            if (h <= 8) {
                if (totalM === 0) {
                    this.nextDayReportKey = '11am';
                    this.nextDayReportText = '11.00 a.m.';
                    this.nextDayPolicyText = 'Between 10.31 p.m. – 12.00 p.m.';
                } else if (totalM <= 180) {
                    this.nextDayReportKey = 'half';
                    this.nextDayReportText = 'Half day off';
                    this.nextDayPolicyText = 'Between 12.01 – 3.00 a.m.';
                } else {
                    this.nextDayReportKey = 'full';
                    this.nextDayReportText = 'Full day off';
                    this.nextDayPolicyText = 'Beyond 3.00 a.m.';
                }
            } else if (totalM < 21 * 60) {
                this.nextDayReportKey = 'normal';
                this.nextDayReportText = '9.00 a.m. (usual start time)';
                this.nextDayPolicyText = 'Prior to 9.00 p.m.';
            } else if (totalM <= 22 * 60 + 30) {
                this.nextDayReportKey = '10am';
                this.nextDayReportText = '10.00 a.m.';
                this.nextDayPolicyText = 'Between 9.00 p.m. - 10.30 p.m.';
            } else {
                this.nextDayReportKey = '11am';
                this.nextDayReportText = '11.00 a.m.';
                this.nextDayPolicyText = 'Between 10.31 p.m. – 12.00 p.m.';
            }

            if (this.date) {
                const parts = this.date.split('-');
                const d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                d.setDate(d.getDate() + 1);
                // skip weekend
                while (d.getDay() === 0 || d.getDay() === 6) {
                    d.setDate(d.getDate() + 1);
                }
                this.nextDayDateText = d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });
            }
        },

        calculateCutoff() {
            if (!this.date) return;
            const now = new Date();
            const dateParts = this.date.split('-');
            const targetDate = new Date(parseInt(dateParts[0]), parseInt(dateParts[1]) - 1, parseInt(dateParts[2]));

            let deadline = new Date(targetDate);
            if (this.type === 'weekend') {
                // previous working day 3 PM
                do {
                    deadline.setDate(deadline.getDate() - 1);
                } while (deadline.getDay() === 0 || deadline.getDay() === 6);
            }
            deadline.setHours(15, 0, 0, 0);

            this.isPastCutoff = (now.getTime() > deadline.getTime() && targetDate >= new Date(now.getFullYear(), now.getMonth(), now.getDate()));

            if (this.isPastCutoff) {
                this.cutoffNotice = (this.type === 'weekend')
                    ? 'Meal requests were due by 3:00 PM on ' + deadline.toLocaleDateString('en-GB', { weekday: 'long' }) + '. This one will be marked late.'
                    : 'It is past 3:00 PM. Meal requests will be marked late for Finance.';
            } else {
                this.cutoffNotice = (this.type === 'weekend')
                    ? 'Send by 3:00 PM on ' + deadline.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'short' }) + ' for meal allowance.'
                    : '';
            }
        },

        // ==========================================
        // Custom Calendar Picker for Late Work Form
        // ==========================================
        get formattedSelectedDate() {
            if (!this.date) return 'Select date';
            const parts = this.date.split('-');
            if (parts.length !== 3) return this.date;
            const dt = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
            return dt.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
        },

        get selectedHoliday() {
            if (!this.date) return null;
            return this.holidaysMap ? this.holidaysMap[this.date] || null : null;
        },

        get isSelectedWeekend() {
            if (!this.date) return false;
            const parts = this.date.split('-');
            if (parts.length !== 3) return false;
            const dt = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
            const day = dt.getDay();
            return day === 0 || day === 6;
        },

        get pickerMonthName() {
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            return months[this.pickerMonth] + ' ' + this.pickerYear;
        },

        formatYmd(y, m, d) {
            return `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        },

        prevPickerMonth() {
            if (this.pickerMonth === 0) {
                this.pickerMonth = 11;
                this.pickerYear--;
            } else {
                this.pickerMonth--;
            }
        },

        nextPickerMonth() {
            if (this.pickerMonth === 11) {
                this.pickerMonth = 0;
                this.pickerYear++;
            } else {
                this.pickerMonth++;
            }
        },

        goPickerToday() {
            const today = new Date();
            this.pickerYear = today.getFullYear();
            this.pickerMonth = today.getMonth();
            this.selectPickerDate(this.formatYmd(today.getFullYear(), today.getMonth() + 1, today.getDate()));
        },

        get pickerCalendarDays() {
            const year = this.pickerYear;
            const month = this.pickerMonth;
            const firstDay = new Date(year, month, 1).getDay();
            const startOffset = (firstDay === 0 ? 6 : firstDay - 1); // Monday start
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            const days = [];
            for (let i = startOffset - 1; i >= 0; i--) {
                const d = daysInPrevMonth - i;
                const prevMonth = month === 0 ? 11 : month - 1;
                const prevYear = month === 0 ? year - 1 : year;
                const dateStr = this.formatYmd(prevYear, prevMonth + 1, d);
                const dayOfWeek = new Date(prevYear, prevMonth, d).getDay();
                days.push({
                    day: d,
                    date: dateStr,
                    isCurrentMonth: false,
                    isWeekend: (dayOfWeek === 0 || dayOfWeek === 6),
                    holiday: this.holidaysMap ? this.holidaysMap[dateStr] || null : null,
                });
            }

            for (let d = 1; d <= daysInMonth; d++) {
                const dateStr = this.formatYmd(year, month + 1, d);
                const dayOfWeek = new Date(year, month, d).getDay();
                const now = new Date();
                const todayStr = this.formatYmd(now.getFullYear(), now.getMonth() + 1, now.getDate());
                days.push({
                    day: d,
                    date: dateStr,
                    isCurrentMonth: true,
                    isToday: dateStr === todayStr,
                    isSelected: dateStr === this.date,
                    isWeekend: (dayOfWeek === 0 || dayOfWeek === 6),
                    holiday: this.holidaysMap ? this.holidaysMap[dateStr] || null : null,
                });
            }

            const remaining = (7 - (days.length % 7)) % 7;
            for (let d = 1; d <= remaining; d++) {
                const nextMonth = month === 11 ? 0 : month + 1;
                const nextYear = month === 11 ? year + 1 : year;
                const dateStr = this.formatYmd(nextYear, nextMonth + 1, d);
                const dayOfWeek = new Date(nextYear, nextMonth, d).getDay();
                days.push({
                    day: d,
                    date: dateStr,
                    isCurrentMonth: false,
                    isWeekend: (dayOfWeek === 0 || dayOfWeek === 6),
                    holiday: this.holidaysMap ? this.holidaysMap[dateStr] || null : null,
                });
            }

            return days;
        },

        selectPickerDate(dateStr) {
            this.date = dateStr;
            this.showDatePicker = false;
            const parts = dateStr.split('-').map(Number);
            const dt = new Date(parts[0], parts[1] - 1, parts[2]);
            const dayOfWeek = dt.getDay();
            const isWk = (dayOfWeek === 0 || dayOfWeek === 6);
            const hol = this.holidaysMap ? this.holidaysMap[dateStr] : null;

            if (hol) {
                this.type = 'weekend';
                this.isHoliday = true;
            } else if (isWk) {
                this.type = 'weekend';
                this.isHoliday = false;
            } else {
                this.type = 'late';
                this.isHoliday = false;
            }

            this.checkEligibility();
        },

        // ==========================================
        // Holiday Settings Tab (HR & IT Admin)
        // ==========================================
        get calMonthName() {
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            return months[this.calMonth] + ' ' + this.calYear;
        },

        get calMonthKey() {
            return `${this.calYear}-${String(this.calMonth + 1).padStart(2, '0')}`;
        },

        get monthHolidaysList() {
            const prefix = this.calMonthKey;
            const list = [];
            for (const [dateStr, h] of Object.entries(this.holidaysMap || {})) {
                if (dateStr.startsWith(prefix)) {
                    const parts = dateStr.split('-').map(Number);
                    const dt = new Date(parts[0], parts[1] - 1, parts[2]);
                    list.push({
                        ...h,
                        dayNum: parts[2],
                        dayName: dt.toLocaleDateString('en-GB', { weekday: 'short' }),
                        formattedDate: dt.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }),
                    });
                }
            }
            list.sort((a, b) => a.dayNum - b.dayNum);
            return list;
        },

        get calCalendarDays() {
            const year = this.calYear;
            const month = this.calMonth;
            const firstDay = new Date(year, month, 1).getDay();
            const startOffset = (firstDay === 0 ? 6 : firstDay - 1);
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            const days = [];
            for (let i = startOffset - 1; i >= 0; i--) {
                const d = daysInPrevMonth - i;
                const prevMonth = month === 0 ? 11 : month - 1;
                const prevYear = month === 0 ? year - 1 : year;
                const dateStr = this.formatYmd(prevYear, prevMonth + 1, d);
                const dayOfWeek = new Date(prevYear, prevMonth, d).getDay();
                days.push({
                    day: d,
                    date: dateStr,
                    isCurrentMonth: false,
                    isWeekend: (dayOfWeek === 0 || dayOfWeek === 6),
                    holiday: this.holidaysMap ? this.holidaysMap[dateStr] || null : null,
                });
            }

            for (let d = 1; d <= daysInMonth; d++) {
                const dateStr = this.formatYmd(year, month + 1, d);
                const dayOfWeek = new Date(year, month, d).getDay();
                days.push({
                    day: d,
                    date: dateStr,
                    isCurrentMonth: true,
                    isWeekend: (dayOfWeek === 0 || dayOfWeek === 6),
                    holiday: this.holidaysMap ? this.holidaysMap[dateStr] || null : null,
                });
            }

            const remaining = (7 - (days.length % 7)) % 7;
            for (let d = 1; d <= remaining; d++) {
                const nextMonth = month === 11 ? 0 : month + 1;
                const nextYear = month === 11 ? year + 1 : year;
                const dateStr = this.formatYmd(nextYear, nextMonth + 1, d);
                const dayOfWeek = new Date(nextYear, nextMonth, d).getDay();
                days.push({
                    day: d,
                    date: dateStr,
                    isCurrentMonth: false,
                    isWeekend: (dayOfWeek === 0 || dayOfWeek === 6),
                    holiday: this.holidaysMap ? this.holidaysMap[dateStr] || null : null,
                });
            }

            return days;
        },

        prevCalMonth() {
            if (this.calMonth === 0) {
                this.calMonth = 11;
                this.calYear--;
            } else {
                this.calMonth--;
            }
        },

        nextCalMonth() {
            if (this.calMonth === 11) {
                this.calMonth = 0;
                this.calYear++;
            } else {
                this.calMonth++;
            }
        },

        goCalToday() {
            const today = new Date();
            this.calYear = today.getFullYear();
            this.calMonth = today.getMonth();
        },

        openAddHolidayModal(dateStr = '') {
            const initialDate = dateStr || this.formatYmd(this.calYear, this.calMonth + 1, 1);
            const existing = this.holidaysMap ? this.holidaysMap[initialDate] : null;
            this.holidayForm = {
                date: initialDate,
                name: existing ? existing.name : '',
                type: existing ? existing.type : 'public',
                description: existing ? (existing.description || '') : '',
            };
            this.holidayModalOpen = true;
        },

        closeHolidayModal() {
            this.holidayModalOpen = false;
        },

        async saveHolidayForm() {
            if (!this.holidayForm.date) return;
            this.isSavingHoliday = true;
            try {
                const response = await fetch('{{ route("late-work.holidays.save") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.holidayForm),
                });
                const data = await response.json();
                if (data.success) {
                    this.holidaysMap = data.holidaysMap;
                    this.holidayModalOpen = false;
                } else {
                    alert(data.message || 'Error saving holiday.');
                }
            } catch (e) {
                alert('Network error while saving holiday.');
            } finally {
                this.isSavingHoliday = false;
            }
        },

        async removeHoliday(holidayId, dateStr) {
            if (!confirm(`Are you sure you want to remove the holiday on ${dateStr}?`)) return;
            try {
                const response = await fetch(`/late-work/holidays/${holidayId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();
                if (data.success) {
                    this.holidaysMap = data.holidaysMap;
                }
            } catch (e) {
                console.error(e);
            }
        },

        getColorBadgeClass(color) {
            const map = {
                amber: 'bg-amber-100 text-amber-900 border-amber-300',
                indigo: 'bg-indigo-100 text-indigo-900 border-indigo-300',
                emerald: 'bg-emerald-100 text-emerald-900 border-emerald-300',
                purple: 'bg-purple-100 text-purple-900 border-purple-300',
                rose: 'bg-rose-100 text-rose-900 border-rose-300',
                sky: 'bg-sky-100 text-sky-900 border-sky-300',
                orange: 'bg-orange-100 text-orange-900 border-orange-300',
                teal: 'bg-teal-100 text-teal-900 border-teal-300',
            };
            return map[color] || map.amber;
        },

        openHolidayTypesModal() {
            this.newTypeForm = { name: '', color: 'amber' };
            this.holidayTypesModalOpen = true;
        },

        closeHolidayTypesModal() {
            this.holidayTypesModalOpen = false;
        },

        async saveHolidayTypeForm() {
            if (!this.newTypeForm.name.trim()) return;
            this.isSavingType = true;
            try {
                const response = await fetch('{{ route("late-work.holiday-types.save") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.newTypeForm),
                });
                const data = await response.json();
                if (data.success) {
                    this.holidayTypes = data.holidayTypes;
                    if (data.holidayType && this.holidayModalOpen) {
                        this.holidayForm.type = data.holidayType.code;
                    }
                    this.newTypeForm = { name: '', color: 'amber' };
                } else {
                    alert(data.message || 'Error saving holiday type.');
                }
            } catch (e) {
                alert('Network error while saving holiday type.');
            } finally {
                this.isSavingType = false;
            }
        },

        async removeHolidayType(typeId, typeName) {
            if (!confirm(`Are you sure you want to remove the holiday type "${typeName}"? Any existing holidays using this type will be reset to Public Holiday.`)) return;
            try {
                const response = await fetch(`/late-work/holiday-types/${typeId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();
                if (data.success) {
                    this.holidayTypes = data.holidayTypes;
                    if (data.holidaysMap) {
                        this.holidaysMap = data.holidaysMap;
                    }
                } else {
                    alert(data.message || 'Error deleting holiday type.');
                }
            } catch (e) {
                console.error(e);
            }
        },

        get isFormValid() {
            if (!this.selectedDeptId || !this.date || !this.jobNumber || !this.finishTime) {
                return false;
            }
            if (this.type === 'weekend' && !this.startTime) {
                return false;
            }
            if (this.type === 'late') {
                const parts = this.finishTime.split(':');
                let h = parseInt(parts[0] || 0, 10);
                const m = parseInt(parts[1] || 0, 10);
                if (h >= 9 && h <= 11) h += 12;
                const isWithinWindow = (h >= 20 || h < 8 || (h === 8 && m === 0));
                if (!isWithinWindow) return false;
            }
            return true;
        }
    };
}

function confirmDeleteLateWork(event, formElement, refNumber) {
    event.preventDefault();
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Delete Request?',
            html: `Are you sure you want to delete Late & Weekend Work request <strong class="font-mono text-gray-900 whitespace-nowrap">${refNumber}</strong>?<br><span class="text-xs text-red-600 font-medium mt-1.5 block">This will permanently delete this request and any linked Petty Cash allowance records.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#4b5563',
            confirmButtonText: '<i class="fas fa-trash-alt mr-1.5"></i> Yes, Delete Request',
            cancelButtonText: 'Cancel',
            buttonsStyling: true,
            width: '420px',
            customClass: {
                popup: 'rounded-2xl shadow-2xl border border-gray-100 p-5',
                title: 'text-base font-bold text-gray-800 mt-2',
                htmlContainer: 'text-xs text-gray-600 mt-1',
                confirmButton: 'px-4 py-2 rounded-lg font-bold text-xs text-white shadow-md transition-all mr-2 cursor-pointer',
                cancelButton: 'px-4 py-2 rounded-lg font-semibold text-xs text-gray-700 bg-gray-200 hover:bg-gray-300 transition-all cursor-pointer'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                formElement.submit();
            }
        });
        return false;
    }
    if (confirm(`Are you sure you want to delete Late Work request ${refNumber}? This action cannot be undone.`)) {
        formElement.submit();
    }
    return false;
}
</script>
@endpush
@endsection
