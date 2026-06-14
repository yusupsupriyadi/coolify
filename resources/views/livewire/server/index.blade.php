<div>
    <x-slot:title>
        Servers | Coolify
    </x-slot>
    <div class="flex items-center gap-2">
        <h1>Servers</h1>
        @can('createAnyResource')
            <x-modal-input buttonTitle="+ Add" title="New Server" :closeOutside="false">
                <livewire:server.create />
            </x-modal-input>
        @endcan
    </div>
    <div class="subtitle">All your servers are here.</div>
    <div class="grid grid-cols-1 gap-4 pt-2 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($servers as $server)
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
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round">
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
                        @elseif ($server->settings->force_disabled)
                            Disabled by the system
                        @elseif (!$server->settings->is_reachable)
                            Not reachable
                        @else
                            Not usable by Coolify
                        @endif
                    </span>
                </div>
            </a>
        @empty
            <div
                class="flex flex-col items-center justify-center col-span-full p-12 text-center border border-dashed rounded-xl border-neutral-300 dark:border-coolgray-300">
                <h3 class="mb-1 text-lg font-semibold text-neutral-600 dark:text-neutral-300">No servers found</h3>
                <p class="text-sm text-neutral-500">Without a server, you won't be able to do much.</p>
            </div>
        @endforelse
        @isset($error)
            <div class="text-center col-span-full text-error">
                <span>{{ $error }}</span>
            </div>
        @endisset
    </div>
</div>
