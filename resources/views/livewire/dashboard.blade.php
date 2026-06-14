<div>
    <x-slot:title>
        Dashboard | Coolify
    </x-slot>
    @if (session('error'))
        <span x-data x-init="$wire.emit('error', '{{ session('error') }}')" />
    @endif
    <h1>Dashboard</h1>
    <div class="subtitle">Your self-hosted infrastructure.</div>

    <section class="-mt-2">
        <div class="flex items-center gap-2 pb-2">
            <h3>Projects</h3>
            @if ($projects->count() > 0)
                <x-modal-input buttonTitle="Add" title="New Project">
                    <x-slot:content>
                        <button
                            class="flex items-center justify-center size-4 text-black dark:text-white rounded hover:bg-coolgray-400 dark:hover:bg-coolgray-300 cursor-pointer">
                            <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </button>
                    </x-slot:content>
                    <livewire:project.add-empty />
                </x-modal-input>
            @endif
        </div>
        @if ($projects->count() > 0)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
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
        @else
            <div class="flex flex-col gap-1">
                <div class='font-bold dark:text-warning'>No projects found.</div>
                <div class="flex items-center gap-1">
                    <x-modal-input buttonTitle="Add" title="New Project">
                        <livewire:project.add-empty />
                    </x-modal-input> your first project or
                    go to the <a class="underline dark:text-white" href="{{ route('onboarding') }}" {{ wireNavigate() }}>onboarding</a> page.
                </div>
            </div>
        @endif
    </section>

    <section>
        <div class="flex items-center gap-2 pb-2">
            <h3>Servers</h3>
            @if ($servers->count() > 0 && $privateKeys->count() > 0)
                <x-modal-input buttonTitle="Add" title="New Server" :closeOutside="false">
                    <x-slot:content>
                        <button
                            class="flex items-center justify-center size-4 text-black dark:text-white rounded hover:bg-coolgray-400 dark:hover:bg-coolgray-300 cursor-pointer">
                            <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </button>
                    </x-slot:content>
                    <livewire:server.create />
                </x-modal-input>
            @endif
        </div>
        @if ($servers->count() > 0)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($servers as $server)
                    @php($serverReachable = $server->settings->is_reachable && $server->settings->is_usable && !$server->settings->force_disabled)
                    <a href="{{ route('server.show', ['server_uuid' => data_get($server, 'uuid')]) }}" {{ wireNavigate() }}
                        @class([
                            'relative flex flex-col p-5 transition-all bg-white border group rounded-xl border-neutral-200 dark:bg-coolgray-100 dark:border-coolgray-200 hover:shadow-lg hover:-translate-y-0.5',
                            'hover:border-coollabs dark:hover:border-coollabs' => $serverReachable,
                            'border-error/60 dark:border-error/50' => !$serverReachable,
                        ])>
                        <div class="flex items-start gap-3">
                            <div
                                class="flex items-center justify-center w-10 h-10 rounded-lg shrink-0 bg-coollabs/10 text-coollabs dark:bg-coolgray-200 dark:text-warning">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 4m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" />
                                    <path d="M15 20h-9a3 3 0 0 1 -3 -3v-2a3 3 0 0 1 3 -3h12" />
                                    <path d="M7 8v.01" />
                                    <path d="M7 16v.01" />
                                    <path d="M20 15l-2 3h3l-2 3" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-black truncate dark:text-white dark:group-hover:text-white">
                                    {{ $server->name }}</div>
                                <div class="text-xs line-clamp-2 text-neutral-500 dark:text-neutral-400">
                                    {{ $server->description ?: 'No description' }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mt-4 text-xs font-medium">
                            <span class="w-2 h-2 rounded-full {{ $serverReachable ? 'bg-success' : 'bg-error' }}"></span>
                            <span class="{{ $serverReachable ? 'text-success' : 'text-error' }}">
                                @if ($serverReachable)
                                    Reachable
                                @elseif (!$server->settings->is_reachable)
                                    Not reachable
                                @else
                                    Not usable by Coolify
                                @endif
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            @if ($privateKeys->count() === 0)
                <div class="flex flex-col gap-1">
                    <div class='font-bold dark:text-warning'>No private keys found.</div>
                    <div class="flex items-center gap-1">Before you can add your server, first <x-modal-input
                            buttonTitle="add" title="New Private Key">
                            <livewire:security.private-key.create from="server" />
                        </x-modal-input> a private key
                        or
                        go to the <a class="underline dark:text-white" href="{{ route('onboarding') }}" {{ wireNavigate() }}>onboarding</a>
                        page.
                    </div>
                </div>
            @else
                <div class="flex flex-col gap-1">
                    <div class='font-bold dark:text-warning'>No servers found.</div>
                    <div class="flex items-center gap-1">
                        <x-modal-input buttonTitle="Add" title="New Server" :closeOutside="false">
                            <livewire:server.create />
                        </x-modal-input> your first server
                        or
                        go to the <a class="underline dark:text-white" href="{{ route('onboarding') }}" {{ wireNavigate() }}>onboarding</a>
                        page.
                    </div>
                </div>
            @endif
        @endif
    </section>
</div>
