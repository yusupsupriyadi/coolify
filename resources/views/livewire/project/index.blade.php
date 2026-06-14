<div>
    <x-slot:title>
        Projects | Coolify
    </x-slot>
    <div class="flex gap-2 items-center">
        <h1>Projects</h1>
        @can('createAnyResource')
            <x-modal-input buttonTitle="+ Add" title="New Project">
                <livewire:project.add-empty />
            </x-modal-input>
        @endcan
    </div>
    <div class="subtitle">All your projects are here.</div>

    @if ($projects->isEmpty())
        <div
            class="flex flex-col items-center justify-center p-12 text-center border border-dashed rounded-xl border-neutral-300 dark:border-coolgray-300">
            <h3 class="mb-1 text-lg font-semibold text-neutral-600 dark:text-neutral-300">No projects yet</h3>
            <p class="text-sm text-neutral-500">Create your first project to start deploying resources.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 pt-2 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($projects as $project)
                @php($environmentCount = $project->environments->count())
                <div
                    class="relative flex flex-col p-5 transition-all bg-white border group rounded-xl border-neutral-200 dark:bg-coolgray-100 dark:border-coolgray-200 hover:border-coollabs dark:hover:border-coollabs hover:shadow-lg hover:-translate-y-0.5">
                    <a href="{{ $project->navigateTo() }}" {{ wireNavigate() }} class="absolute inset-0 z-0"
                        aria-label="Open {{ $project->name }}"></a>
                    <div class="flex items-start gap-3">
                        <div
                            class="flex items-center justify-center w-10 h-10 rounded-lg shrink-0 bg-coollabs/10 text-coollabs dark:bg-coolgray-200 dark:text-warning">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 4l-8 4l8 4l8 -4l-8 -4" />
                                <path d="M4 12l8 4l8 -4" />
                                <path d="M4 16l8 4l8 -4" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-black truncate dark:text-white dark:group-hover:text-white">
                                {{ $project->name }}</div>
                            <div class="text-xs line-clamp-2 text-neutral-500 dark:text-neutral-400">
                                {{ $project->description ?: 'No description' }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mt-4">
                        <span
                            class="px-2 py-0.5 text-xs rounded-full bg-neutral-100 dark:bg-coolgray-200 text-neutral-600 dark:text-neutral-300">
                            {{ $environmentCount }} {{ $environmentCount === 1 ? 'environment' : 'environments' }}
                        </span>
                    </div>
                    <div
                        class="relative z-10 flex items-center gap-4 pt-3 mt-4 text-xs font-bold border-t border-neutral-100 dark:border-coolgray-200">
                        @if ($project->environments->first())
                            @can('createAnyResource')
                                <a class="text-neutral-500 hover:text-coollabs dark:hover:text-warning" {{ wireNavigate() }}
                                    href="{{ route('project.resource.create', [
                                        'project_uuid' => $project->uuid,
                                        'environment_uuid' => $project->environments->first()->uuid,
                                    ]) }}">
                                    + Add Resource
                                </a>
                            @endcan
                        @endif
                        @can('update', $project)
                            <a class="text-neutral-500 hover:text-coollabs dark:hover:text-warning" {{ wireNavigate() }}
                                href="{{ route('project.edit', ['project_uuid' => $project->uuid]) }}">
                                Settings
                            </a>
                        @endcan
                        <span
                            class="ml-auto text-neutral-400 transition-transform group-hover:translate-x-0.5 group-hover:text-coollabs dark:group-hover:text-warning">→</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
