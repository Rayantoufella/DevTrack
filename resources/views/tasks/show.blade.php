{{-- US11 — Détail tâche + changement de statut par le developer assigné --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $task->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-gray-700 whitespace-pre-line">{{ $task->description }}</p>

                <dl class="mt-4 grid grid-cols-2 gap-y-2 text-sm">
                    <dt class="text-gray-500">Projet</dt>
                    <dd>
                        <a href="{{ route('projects.show', $project) }}" class="text-blue-600 hover:underline">
                            {{ $project->title }}
                        </a>
                    </dd>

                    <dt class="text-gray-500">Assigné à</dt>
                    <dd>{{ $task->user?->name ?? '—' }}</dd>

                    <dt class="text-gray-500">Priorité</dt>
                    <dd>{{ ucfirst($task->priority) }}</dd>

                    <dt class="text-gray-500">Statut</dt>
                    <dd>
                        @php
                            $statusLabels = [
                                'todo' => 'À faire',
                                'in_progress' => 'En cours',
                                'done' => 'Terminé',
                            ];
                        @endphp
                        {{ $statusLabels[$task->status] ?? $task->status }}
                    </dd>

                    <dt class="text-gray-500">Deadline</dt>
                    <dd>{{ \Carbon\Carbon::parse($task->deadline)->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>

            {{-- US11 — Le developer assigné peut changer uniquement le statut --}}
            @can('updateStatus', $task)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Changer le statut</h3>
                    <form method="POST" action="{{ route('projects.tasks.updateStatus', [$project, $task]) }}"
                          class="flex items-end gap-2">
                        @csrf
                        @method('PATCH')
                        <div class="flex-1">
                            <select name="status" class="block w-full border-gray-300 rounded-md shadow-sm">
                                @foreach ($statusLabels as $value => $label)
                                    <option value="{{ $value }}" {{ $task->status === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <x-primary-button>Mettre à jour</x-primary-button>
                    </form>
                </div>
            @endcan

        </div>
    </div>
</x-app-layout>
