<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Leaves</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Apply Leave Section Starts --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Apply Leave</h3>

                <form method="POST" action="{{ route('leaves.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="start_date" value="Start Date" required />
                            <x-text-input type="date" id="start_date" name="start_date" class="mt-1 block w-full"
                                          :value="old('start_date')"
                                          min="{{ now()->startOfYear()->toDateString() }}"
                                          max="{{ now()->endOfYear()->toDateString() }}"
                                          onchange="syncEndMin()"
                                          required />
                            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="end_date" value="End Date" required />
                            <x-text-input type="date" id="end_date" name="end_date" class="mt-1 block w-full"
                                          :value="old('end_date')"
                                          min="{{ old('start_date', now()->startOfYear()->toDateString()) }}"
                                          max="{{ now()->endOfYear()->toDateString() }}"
                                          required />
                            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                        </div>
                    </div>

                    <script>
                        function syncEndMin() {
                            var start = document.getElementById('start_date');
                            var end   = document.getElementById('end_date');
                            end.min = start.value || '{{ now()->startOfYear()->toDateString() }}';
                            if (end.value && end.value < start.value) {
                                end.value = start.value;
                            }
                        }
                    </script>

                    <div class="flex justify-end">
                        <x-primary-button>Apply Leave</x-primary-button>
                    </div>
                </form>
            </div>
            {{-- Apply Leave Section Ends --}}

            {{-- Leaves Listing Section Starts --}}
            <div id="leaves" class="bg-white overflow-hidden shadow-sm sm:rounded-lg scroll-mt-4">

                {{-- Date-range filter + per-page toolbar --}}
                <div class="p-4 border-b border-gray-200">
                    <form method="GET" action="{{ route('leaves.index') }}"
                          onsubmit="event.preventDefault(); location.href = this.action + '?' + new URLSearchParams(new FormData(this)).toString() + '#leaves';"
                          class="flex flex-col sm:flex-row sm:items-end gap-3">
                        <input type="hidden" name="sort" value="{{ $sort }}">
                        <input type="hidden" name="direction" value="{{ $direction }}">

                        <div>
                            <x-input-label for="from" value="From" />
                            <x-text-input type="date" id="from" name="from" :value="$from" class="mt-1 block" />
                        </div>
                        <div>
                            <x-input-label for="to" value="To" />
                            <x-text-input type="date" id="to" name="to" :value="$to" class="mt-1 block" />
                        </div>

                        <select name="per_page" onchange="this.form.requestSubmit()"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            @foreach ([10, 25, 50] as $size)
                                <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} / page</option>
                            @endforeach
                        </select>

                        <x-primary-button>Filter</x-primary-button>

                        @if ($from !== '' || $to !== '')
                            <a href="{{ route('leaves.index') }}"
                               class="text-sm text-gray-500 hover:text-gray-700 underline pb-2">Clear</a>
                        @endif
                    </form>
                </div>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-sortable-th column="start_date" label="Start" fragment="leaves" :sort="$sort" :direction="$direction" />
                            <x-sortable-th column="end_date" label="End" fragment="leaves" :sort="$sort" :direction="$direction" />
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($leaves as $leave)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $leave->start_date->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $leave->end_date->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">No leaves found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($leaves->total() > 0)
                    <div class="px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <p class="text-sm text-gray-600">
                            Showing <span class="font-medium">{{ $leaves->firstItem() }}</span>
                            to <span class="font-medium">{{ $leaves->lastItem() }}</span>
                            of <span class="font-medium">{{ $leaves->total() }}</span> results
                        </p>
                        @if ($leaves->hasPages())
                            <div>{{ $leaves->links() }}</div>
                        @endif
                    </div>
                @endif
            </div>
            {{-- Leaves Listing Section Ends --}}

        </div>
    </div>
</x-app-layout>
