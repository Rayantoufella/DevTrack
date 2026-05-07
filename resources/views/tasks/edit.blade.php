{{-- US10 — Modifier une tâche (lead) --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Modifier la tâche
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('projects.tasks.update', [$project, $task]) }}">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="title" :value="__('Titre')" />
                        <x-text-input id="title" name="title" type="text"
                                      class="mt-1 block w-full"
                                      :value="old('title', $task->title)" required autofocus />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="4"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                  required>{{ old('description', $task->description) }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="deadline" :value="__('Deadline')" />
                            <x-text-input id="deadline" name="deadline" type="datetime-local"
                                          class="mt-1 block w-full"
                                          :value="old('deadline', \Carbon\Carbon::parse($task->deadline)->format('Y-m-d\TH:i'))"
                                          required />
                            <x-input-error :messages="$errors->get('deadline')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="priority" :value="__('Priorité')" />
                            <select id="priority" name="priority"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                @foreach (['low','medium','high'] as $p)
                                    <option value="{{ $p }}" {{ old('priority', $task->priority) === $p ? 'selected' : '' }}>
                                        {{ ucfirst($p) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="status" :value="__('Statut')" />
                            <select id="status" name="status"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                @php
                                    $statuses = [
                                        'todo' => 'À faire',
                                        'in_progress' => 'En cours',
                                        'done' => 'Terminé',
                                    ];
                                @endphp
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" {{ old('status', $task->status) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <x-input-label for="user_id" :value="__('Assigner à')" />
                        <select id="user_id" name="user_id"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}" {{ old('user_id', $task->user_id) == $member->id ? 'selected' : '' }}>
                                    {{ $member->name }} ({{ $member->pivot->role }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-6 gap-3">
                        <a href="{{ route('projects.tasks.show', [$project, $task]) }}"
                           class="text-sm text-gray-600 hover:text-gray-900">Annuler</a>
                        <x-primary-button>Enregistrer</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
