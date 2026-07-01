@extends('layouts.app')

@section('title', 'Time Logs')

@section('content')
    <h1>Daily Time Log</h1>

    <form method="GET" action="{{ route('time-logs.index') }}">
        <label for="filter_date">View date</label>
        <input type="date" id="filter_date" name="work_date" value="{{ $workDate }}"
               max="{{ now()->toDateString() }}" onchange="this.form.submit()">
    </form>

    <p class="muted">
        Logged: {{ sprintf('%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60) }}
        / 10:00 &mdash; remaining {{ sprintf('%02d:%02d', intdiv($remaining, 60), $remaining % 60) }}
    </p>

    <form method="POST" action="{{ route('time-logs.store') }}">
        @csrf
        <input type="hidden" name="work_date" value="{{ $workDate }}">

        <label for="project_id">Project</label>
        <select id="project_id" name="project_id" required>
            <option value="">-- Select --</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>

        <label for="description">Task Description</label>
        <textarea id="description" name="description" rows="2" maxlength="1000" required>{{ old('description') }}</textarea>

        <label for="time">Time (e.g. 2:30, 2h30m, 2.5h)</label>
        <input type="text" id="time" name="time" value="{{ old('time') }}" required>

        <button type="submit">Add Task</button>
    </form>

    <table>
        <thead>
            <tr><th>Project</th><th>Description</th><th>Time</th></tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
                <tr>
                    <td>{{ $entry->project->name }}</td>
                    <td>{{ $entry->description }}</td>
                    <td>{{ $entry->duration }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">No tasks logged for this date.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
