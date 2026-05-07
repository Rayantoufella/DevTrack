{{-- US2 — Dashboard : projets de l'utilisateur (lead ou developer) --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Mes projets') }}
            </h2>
            @can('create', App\Models\Project::class)
                <a href="{{ route('projects.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    + Nouveau projet
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @forelse ($projects as $project)
                @php
                    $myRole = $project->pivot->role ?? null;
                    $total = $project->tasks_count ?? 0;
                    $done  = $project->completed_tasks_count ?? 0;
                    $pct   = $total > 0 ? round($done / $total * 100) : 0;
                @endphp

                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <a href="{{ route('projects.show', $project) }}"
                               class="text-lg font-semibold text-gray-900 hover:underline">
                                {{ $project->title }}
                            </a>
                            <p class="text-sm text-gray-600 mt-1">
                                {{ Str::limit($project->description, 120) }}
                            </p>
                            <p class="text-xs text-gray-500 mt-2">
                                Deadline : {{ \Carbon\Carbon::parse($project->deadline)->format('d/m/Y') }}
                                · Mon rôle :
                                <span class="inline-block px-2 py-0.5 rounded text-xs
                                    {{ $myRole === 'lead' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $myRole ?? '—' }}
                                </span>
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-gray-800">{{ $done }} / {{ $total }}</div>
                            <div class="text-xs text-gray-500">tâches terminées</div>
                        </div>
                    </div>

                    <div class="mt-4 w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    Aucun projet pour l'instant.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
