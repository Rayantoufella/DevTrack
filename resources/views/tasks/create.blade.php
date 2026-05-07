{{-- US9 — Créer une tâche (lead) --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nouvelle tâche — {{ $project->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('projects.tasks.store', $project) }}">
                    @csrf

                    <div>
                        <x-input-label for="title" :value="__('Titre')" />
                        <x-text-input id="title" name="title" type="text"
                                      class="mt-1 block w-full" :value="old('title')" required autofocus />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="4"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                  required>{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="deadline" :value="__('Deadline')" />
                            <x-text-input id="deadline" name="deadline" type="datetime-local"
                                          class="mt-1 block w-full" :value="old('deadline')" required />
                            <x-input-error :messages="$errors->get('deadline')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="priority" :value="__('Priorité')" />
                            <select id="priority" name="priority"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                <option value="low"    {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high"   {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                            </select>
                            <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <x-input-label for="user_id" :value="__('Assigner à')" />
                        <select id="user_id" name="user_id"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">— Choisir un membre —</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}" {{ old('user_id') == $member->id ? 'selected' : '' }}>
                                    {{ $member->name }} ({{ $member->pivot->role }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-6 gap-3">
                        <a href="{{ route('projects.show', $project) }}"
                           class="text-sm text-gray-600 hover:text-gray-900">Annuler</a>
                        <x-primary-button>Créer</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
