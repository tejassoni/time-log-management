@php
    $success = session('success') ?? session('status');
    $error   = session('error');
@endphp

@if ($success)
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         class="rounded-md bg-green-50 border border-green-200 p-4 flex items-start justify-between">
        <p class="text-sm font-medium text-green-800">{{ $success }}</p>
        <button type="button" @click="show = false" class="text-green-600 hover:text-green-800">&times;</button>
    </div>
@endif

@if ($error)
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
         class="rounded-md bg-red-50 border border-red-200 p-4 flex items-start justify-between">
        <p class="text-sm font-medium text-red-800">{{ $error }}</p>
        <button type="button" @click="show = false" class="text-red-600 hover:text-red-800">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div class="rounded-md bg-red-50 border border-red-200 p-4">
        <p class="text-sm font-medium text-red-800 mb-1">Please fix the following:</p>
        <ul class="list-disc list-inside text-sm text-red-700 space-y-0.5">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
