{{-- Détail projet : infos + membres (US7) + tâches (US8) --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $project->title }}
            </h2>

            <div class="flex gap-2">
                @can('update', $project)
                    <a href="{{ route('projects.edit', $project) }}"
                       class="inline-flex items-center px-3 py-1.5 bg-gray-200 rounded text-sm hover:bg-gray-300">
                        Modifier
                    </a>
                @endcan
                @can('delete', $project)
                    <form method="POST" action="{{ route('projects.destroy', $project) }}"
                          onsubmit="return confirm('Archiver ce projet ?')">
                        @csrf
                        @method('DELETE')
                        <button class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white rounded text-sm hover:bg-red-700">
                            Archiver
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Infos projet --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-gray-700">{{ $project->description }}</p>
                <p class="text-sm text-gray-500 mt-3">
                    Deadline : {{ \Carbon\Carbon::parse($project->deadline)->format('d/m/Y') }}
                </p>
            </div>

            {{-- Membres + add (US7) --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Membres</h3>

                <ul class="divide-y divide-gray-200 mb-4">
                    @foreach ($project->users as $member)
                        <li class="py-2 flex items-center justify-between">
                            <div>
                                <span class="font-medium">{{ $member->name }}</span>
                                <span class="text-sm text-gray-500">— {{ $member->email }}</span>
                                <span class="ml-2 inline-block px-2 py-0.5 rounded text-xs
                                    {{ $member->pivot->role === 'lead' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $member->pivot->role }}
                                </span>
                            </div>
                            @can('manageMembers', $project)
                                @if ($member->pivot->role !== 'lead')
                                    <form method="POST"
                                          action="{{ route('projects.members.destroy', [$project, $member]) }}"
                                          onsubmit="return confirm('Retirer ce membre ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-sm text-red-600 hover:underline">Retirer</button>
                                    </form>
                                @endif
                            @endcan
                        </li>
                    @endforeach
                </ul>

                @can('manageMembers', $project)
                    <form method="POST" action="{{ route('projects.members.store', $project) }}"
                          class="flex items-end gap-2">
                        @csrf
                        <div class="flex-1">
                            <x-input-label for="email" :value="__('Email du developer à ajouter')" />
                            <x-text-input id="email" name="email" type="email"
                                          class="mt-1 block w-full" :value="old('email')" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <x-primary-button>Ajouter</x-primary-button>
                    </form>
                @endcan
            </div>

            {{-- Tâches (US8) --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Tâches</h3>
                    @can('createTask', $project)
                        <a href="{{ route('projects.tasks.create', $project) }}"
                           class="inline-flex items-center px-3 py-1.5 bg-gray-800 text-white rounded text-sm hover:bg-gray-700">
                            + Nouvelle tâche
                        </a>
                    @endcan
                </div>

                @forelse ($project->tasks as $task)
                    @php
                        $deadline = \Carbon\Carbon::parse($task->deadline);
                        $diff = now()->diffInHours($deadline, false);
                        if ($task->status === 'done') {
                            $urgencyClass = 'bg-green-100 text-green-800';
                            $urgencyLabel = 'Terminé';
                        } elseif ($diff < 0) {
                            $urgencyClass = 'bg-red-200 text-red-900';
                            $urgencyLabel = 'En retard';
                        } elseif ($diff < 48) {
                            $urgencyClass = 'bg-orange-100 text-orange-800';
                            $urgencyLabel = 'Urgent';
                        } else {
                            $urgencyClass = 'bg-gray-100 text-gray-700';
                            $urgencyLabel = 'À temps';
                        }

                        $statusLabels = [
                            'todo'        => 'À faire',
                            'in_progress' => 'En cours',
                            'done'        => 'Terminé',
                        ];
                    @endphp

                    <div class="border border-gray-200 rounded p-4 mb-2 flex items-start justify-between">
                        <div>
                            <a href="{{ route('projects.tasks.show', [$project, $task]) }}"
                               class="font-medium text-gray-900 hover:underline">
                                {{ $task->title }}
                            </a>
                            <div class="text-sm text-gray-500 mt-1">
                                Statut : <span class="font-medium">{{ $statusLabels[$task->status] ?? $task->status }}</span>
                                · Priorité : {{ $task->priority }}
                                · Assigné : {{ $task->user?->name ?? '—' }}
                                · Deadline : {{ $deadline->format('d/m/Y H:i') }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 rounded text-xs {{ $urgencyClass }}">{{ $urgencyLabel }}</span>
                            @can('update', $task)
                                <a href="{{ route('projects.tasks.edit', [$project, $task]) }}"
                                   class="text-sm text-blue-600 hover:underline">Éditer</a>
                            @endcan
                            @can('delete', $task)
                                <form method="POST" action="{{ route('projects.tasks.destroy', [$project, $task]) }}"
                                      onsubmit="return confirm('Supprimer cette tâche ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-sm text-red-600 hover:underline">Suppr</button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">Aucune tâche.</p>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
