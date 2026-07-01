<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Time Logs</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Date filter --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <form method="GET" action="{{ route('time-logs.index') }}">
                        <x-input-label for="filter_date" value="View date" />
                        <x-text-input type="date" id="filter_date" name="work_date" class="mt-1 block"
                                      :value="$workDate" max="{{ now()->toDateString() }}"
                                      onchange="this.form.submit()" />
                    </form>

                    <p class="text-sm text-gray-600">
                        Logged
                        <span class="font-semibold text-gray-900">{{ sprintf('%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60) }}</span>
                        / 10:00 &mdash; remaining
                        <span class="font-semibold text-gray-900">{{ sprintf('%02d:%02d', intdiv($remaining, 60), $remaining % 60) }}</span>
                    </p>
                </div>
            </div>

            {{-- Add task --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add Task</h3>

                <form method="POST" action="{{ route('time-logs.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="work_date" value="{{ $workDate }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="project_id" value="Project" />
                            <select id="project_id" name="project_id" required
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">-- Select --</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="time" value="Time (e.g. 2:30, 2h30m, 2.5h)" />
                            <x-text-input type="text" id="time" name="time" class="mt-1 block w-full"
                                          :value="old('time')" required />
                            <x-input-error :messages="$errors->get('time')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="description" value="Task Description" />
                        <textarea id="description" name="description" rows="2" maxlength="1000" required
                                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>Add Task</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Entries --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($entries as $entry)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $entry->project->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $entry->description }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-700">{{ $entry->duration }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No tasks logged for this date.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
