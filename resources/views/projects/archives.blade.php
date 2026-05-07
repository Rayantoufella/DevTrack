{{-- US5/US6 + bonus forceDelete — Projets archivés du lead courant --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Archives') }}
            </h2>
            <a href="{{ route('projects.index') }}"
               class="text-sm text-gray-500 hover:underline">← Retour au dashboard</a>
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
                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-4 opacity-90">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">{{ $project->title }}</h3>
                            <p class="text-sm text-gray-600 mt-1">{{ Str::limit($project->description, 120) }}</p>
                            <p class="text-xs text-gray-500 mt-2">
                                Archivé le : {{ \Carbon\Carbon::parse($project->deleted_at)->format('d/m/Y H:i') }}
                                · Tâches : {{ $project->tasks_count }}
                                · Deadline : {{ \Carbon\Carbon::parse($project->deadline)->format('d/m/Y') }}
                            </p>
                        </div>

                        <div class="flex gap-2">
                            @can('restore', $project)
                                <form method="POST" action="{{ route('projects.restore', $project) }}">
                                    @csrf
                                    <button class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                                        Restaurer
                                    </button>
                                </form>
                            @endcan

                            @can('forceDelete', $project)
                                <form method="POST" action="{{ route('projects.forceDelete', $project) }}"
                                      onsubmit="return confirm('Supprimer définitivement ce projet ? Cette action est irréversible.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="inline-flex items-center px-3 py-1.5 bg-red-700 text-white rounded text-sm hover:bg-red-800">
                                        Supprimer définitivement
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    Aucun projet archivé.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
