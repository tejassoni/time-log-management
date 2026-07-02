<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Time Logs</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- View Date filter Section Starts --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <form method="GET" action="{{ route('time-logs.index') }}">
                        <x-input-label for="filter_date" value="View Date" />
                        <x-text-input type="date" id="filter_date" name="work_date" class="mt-1 block"
                                      :value="$workDate" max="{{ now()->toDateString() }}"
                                      onchange="this.form.submit()" />
                    </form>

                    <p class="text-sm text-gray-600">
                        Time Logged
                        <span class="font-semibold text-gray-900">{{ sprintf('%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60) }}</span>
                        / 10:00 &mdash; Remaining
                        <span class="font-semibold text-gray-900">{{ sprintf('%02d:%02d', intdiv($remaining, 60), $remaining % 60) }}</span>
                    </p>
                </div>
            </div>
            {{-- View Date filter Section Ends --}}

            {{-- Add Task Section Starts --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add Task</h3>

                <form method="POST" action="{{ route('time-logs.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="work_date" value="{{ $workDate }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="project_id" value="Project" required />
                            <select id="project_id" name="project_id" required
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">-- Select Project --</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="time" value="Time Format (e.g. 2h , 2:30, 2h30m, 2.5h, 30m, 2)" required />
                            <x-text-input type="text" id="time" name="time" class="mt-1 block w-full"
                                          :value="old('time')"  placeholder="Enter time (e.g. 2h, 2:30, 2h30m, 2.5h, 30m, 2)" required />
                            <x-input-error :messages="$errors->get('time')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="description" value="Task Description" required />
                        <textarea id="description" name="description" rows="2" maxlength="1000" required
                                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Enter task description">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>Add Task</x-primary-button>
                    </div>
                </form>
            </div>
            {{-- Add Task Section Ends --}}

            {{-- Time Logs Listing Section Starts --}}
            <div id="logs" class="bg-white overflow-hidden shadow-sm sm:rounded-lg scroll-mt-4">

                {{-- Search + per-page toolbar --}}
                <div class="p-4 border-b border-gray-200">
                    <form method="GET" action="{{ route('time-logs.index') }}"
                          onsubmit="event.preventDefault(); location.href = this.action + '?' + new URLSearchParams(new FormData(this)).toString() + '#logs';"
                          class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <input type="hidden" name="work_date" value="{{ $workDate }}">
                        <input type="hidden" name="sort" value="{{ $sort }}">
                        <input type="hidden" name="direction" value="{{ $direction }}">

                        <x-text-input type="search" name="search" :value="$search"
                                      placeholder="Search project or description…"
                                      class="w-full sm:w-72" />

                        <select name="per_page" onchange="this.form.requestSubmit()"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            @foreach ([10, 25, 50] as $size)
                                <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} / page</option>
                            @endforeach
                        </select>

                        <x-primary-button>Search</x-primary-button>

                        @if ($search !== '')
                            <a href="{{ route('time-logs.index', ['work_date' => $workDate]) }}"
                               class="text-sm text-gray-500 hover:text-gray-700 underline">Clear</a>
                        @endif
                    </form>
                </div>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-sortable-th column="project" label="Project" :sort="$sort" :direction="$direction" />
                            <x-sortable-th column="description" label="Description" :sort="$sort" :direction="$direction" />
                            <x-sortable-th column="minutes" label="Time" align="right" :sort="$sort" :direction="$direction" />
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($userTimeLogs as $timeLog)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $timeLog->project->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $timeLog->description }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-700">{{ $timeLog->duration }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                                    @if ($search !== '')
                                        No tasks match "{{ $search }}" on {{ \Illuminate\Support\Carbon::parse($workDate)->format('d/m/Y') }}.
                                    @else
                                        No tasks logged for this ({{ \Illuminate\Support\Carbon::parse($workDate)->format('d/m/Y') }}) date.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($userTimeLogs->total() > 0)
                    <div class="px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <p class="text-sm text-gray-600">
                            Showing <span class="font-medium">{{ $userTimeLogs->firstItem() }}</span>
                            to <span class="font-medium">{{ $userTimeLogs->lastItem() }}</span>
                            of <span class="font-medium">{{ $userTimeLogs->total() }}</span> results
                        </p>
                        @if ($userTimeLogs->hasPages())
                            <div>{{ $userTimeLogs->links() }}</div>
                        @endif
                    </div>
                @endif
            </div>
            {{-- Time Logs Listing Section Ends --}}

        </div>
    </div>
</x-app-layout>
