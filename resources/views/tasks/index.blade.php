{{-- US8 — Liste des tâches d'un projet --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Tâches — {{ $project->title }}
                </h2>
                <a href="{{ route('projects.show', $project) }}"
                   class="text-sm text-gray-500 hover:underline">← Retour au projet</a>
            </div>

            @can('createTask', $project)
                <a href="{{ route('projects.tasks.create', $project) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    + Nouvelle tâche
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

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Titre</th>
                            <th class="px-6 py-3">Statut</th>
                            <th class="px-6 py-3">Priorité</th>
                            <th class="px-6 py-3">Assigné</th>
                            <th class="px-6 py-3">Deadline</th>
                            <th class="px-6 py-3">Urgence</th>
                            <th class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($tasks as $task)
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

                                $priorityClass = match ($task->priority) {
                                    'high'   => 'bg-red-100 text-red-800',
                                    'medium' => 'bg-yellow-100 text-yellow-800',
                                    default  => 'bg-blue-100 text-blue-800',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('projects.tasks.show', [$project, $task]) }}"
                                       class="font-medium text-gray-900 hover:underline">
                                        {{ $task->title }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ $statusLabels[$task->status] ?? $task->status }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-xs {{ $priorityClass }}">
                                        {{ ucfirst($task->priority) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ $task->user?->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $deadline->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 rounded text-xs {{ $urgencyClass }}">
                                        {{ $urgencyLabel }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm flex gap-2">
                                    @can('update', $task)
                                        <a href="{{ route('projects.tasks.edit', [$project, $task]) }}"
                                           class="text-blue-600 hover:underline">Éditer</a>
                                    @endcan
                                    @can('delete', $task)
                                        <form method="POST"
                                              action="{{ route('projects.tasks.destroy', [$project, $task]) }}"
                                              onsubmit="return confirm('Supprimer cette tâche ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-600 hover:underline">Suppr</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    Aucune tâche dans ce projet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
