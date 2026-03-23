@extends('components.manager.layout.layout-manager')

@section('header', 'Edit Leave Request')
@section('subtitle', 'Modify your leave request details')

@section('content')
<div class="max-w-3xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('manager.dashboard') }}"
                    class="inline-flex items-center text-sm font-medium text-neutral-700 hover:text-primary-600">
                    <i class="mr-2 fas fa-home"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <a href="{{ route(name: 'manager.leaves.index') }}"
                        class="text-sm font-medium text-neutral-700 hover:text-primary-600">Leave Requests</a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <a href="{{ route('manager.leaves.show', $leave->id) }}"
                        class="text-sm font-medium text-neutral-700 hover:text-primary-600">Request #{{ $leave->id
                        }}</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <span class="text-sm font-medium text-neutral-500">Edit</span>
                </div>
            </li>
        </ol>
    </nav>

    @include('components.alert-errors')

    <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
        <div class="px-6 py-4 border-b border-neutral-200">
            <h2 class="text-lg font-bold text-neutral-900">Edit Leave Request #{{ $leave->id }}</h2>
            <p class="text-sm text-neutral-600">Update your leave request information</p>
        </div>

        

        <form action="{{ route('manager.leaves.updateSelf', $leave->id) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label for="date_start" class="block mb-2 text-sm font-semibold text-neutral-700">
                        <i class="mr-2 fas fa-calendar-alt text-primary-600"></i>
                        Start Date
                    </label>
                    <input type="date" id="date_start" name="date_start" class="form-input"
                        value="{{ \Carbon\Carbon::parse($leave->date_start)->format('Y-m-d') }}" required>
                </div>

                <div>
                    <label for="date_end" class="block mb-2 text-sm font-semibold text-neutral-700">
                        <i class="mr-2 fas fa-calendar-alt text-primary-600"></i>
                        End Date
                    </label>
                    <input type="date" id="date_end" name="date_end" class="form-input"
                        value="{{ \Carbon\Carbon::parse($leave->date_end)->format('Y-m-d') }}" required>
                </div>
            </div>

            <!-- Duration Display -->
            <div class="p-4 border rounded-lg bg-neutral-50 border-neutral-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="mr-2 fas fa-clock text-secondary-600"></i>
                        <span class="text-sm font-medium text-neutral-700">Duration:</span>
                    </div>
                    <span id="duration-display" class="text-sm font-bold text-primary-600">
                        {{
                        \Carbon\Carbon::parse($leave->date_start)->diffInDays(\Carbon\Carbon::parse($leave->date_end)) +
                        1 }} days
                    </span>
                </div>
                <div class="mt-2 text-xs text-neutral-500">
                    <span id="working-days-display">
                        {{
                        \Carbon\Carbon::parse($leave->date_start)->diffInWeekdays(\Carbon\Carbon::parse($leave->date_end))
                        + 1 }} working days
                    </span>
                </div>
            </div>

            <div>
                <label for="reason" class="block mb-2 text-sm font-semibold text-neutral-700">
                    <i class="mr-2 fas fa-comment-alt text-primary-600"></i>
                    Reason for Leave
                </label>
                <textarea id="reason" name="reason" rows="4" class="form-textarea"
                    placeholder="Please provide a detailed reason for your leave request..."
                    required>{{ $leave->reason }}</textarea>
            </div>

            <!-- Warning Notice -->
            <div class="p-4 border rounded-lg bg-warning-50 border-warning-200">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-warning-600 mr-3 mt-0.5"></i>
                    <div>
                        <h4 class="mb-1 text-sm font-semibold text-warning-800">Important Notice</h4>
                        <p class="text-xs text-warning-700">
                            Editing this request will reset its status to pending and require re-approval from your team
                            lead.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-6 space-x-4 border-t border-neutral-200">
                <a href="{{ url()->previous() }}"
                    class="px-6 py-2 text-sm font-medium transition-colors duration-200 rounded-lg text-neutral-700 bg-neutral-100 hover:bg-neutral-200">
                    <i class="mr-2 fas fa-times"></i>
                    Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="mr-2 fas fa-save"></i>
                    Update Request
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function calculateDuration() {
    const startDate = document.getElementById('date_start').value;
    const endDate = document.getElementById('date_end').value;

    if (startDate && endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);

    if (end >= start) {
    const timeDiff = end.getTime() - start.getTime();
    const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;

    let workingDays = 0;
    let currentDate = new Date(start);

    while (currentDate <= end) { const dayOfWeek=currentDate.getDay(); if (dayOfWeek !==0 && dayOfWeek !==6) { // Sunday (0)
        | Saturday (6) workingDays++; } currentDate.setDate(currentDate.getDate() + 1); }
        document.getElementById('duration-display').textContent=daysDiff + ' days' ;
        document.getElementById('working-days-display').textContent=workingDays + ' working days' ; } else {
        document.getElementById('duration-display').textContent='0 days' ;
        document.getElementById('working-days-display').textContent='0 working days' ; } } }
        document.getElementById('date_start').addEventListener('change', calculateDuration);
        document.getElementById('date_end').addEventListener('change', calculateDuration);
        document.getElementById('date_start').addEventListener('change', function() {
        document.getElementById('date_end').min=this.value;
    });

</script>
@endpush
@endsection
