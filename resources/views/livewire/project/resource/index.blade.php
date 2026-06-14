<div>
    <x-slot:title>
        {{ data_get_str($project, 'name')->limit(10) }} > Resources | Coolify
    </x-slot>
    <div class="flex flex-col">
        <div class="flex min-w-0 flex-nowrap items-center gap-1">
            <h1>Resources</h1>
            @if ($environment->isEmpty())
                @can('createAnyResource')
                    <a class="button" {{ wireNavigate() }}
                        href="{{ route('project.clone-me', ['project_uuid' => data_get($project, 'uuid'), 'environment_uuid' => data_get($environment, 'uuid')]) }}">
                        Clone
                    </a>
                @endcan
            @else
                @can('createAnyResource')
                    <a href="{{ route('project.resource.create', ['project_uuid' => data_get($parameters, 'project_uuid'), 'environment_uuid' => data_get($environment, 'uuid')]) }}"
                        {{ wireNavigate() }} class="button">+
                        New</a>
                @endcan
                @can('createAnyResource')
                    <a class="button" {{ wireNavigate() }}
                        href="{{ route('project.clone-me', ['project_uuid' => data_get($project, 'uuid'), 'environment_uuid' => data_get($environment, 'uuid')]) }}">
                        Clone
                    </a>
                @endcan
            @endif
            @can('delete', $environment)
                <livewire:project.delete-environment :disabled="!$environment->isEmpty()" :environment_id="$environment->id" />
            @endcan
        </div>
        <nav class="flex pt-2 pb-6">
            <ol class="flex items-center">
                <li class="inline-flex items-center" x-data="{ projectOpen: false, toggle() { this.projectOpen = !this.projectOpen }, open() { this.projectOpen = true }, close() { this.projectOpen = false } }">
                    <div class="flex items-center relative" @mouseenter="open()" @mouseleave="close()">
                        <a class="text-xs truncate lg:text-sm hover:text-warning" {{ wireNavigate() }}
                            href="{{ route('project.show', ['project_uuid' => data_get($parameters, 'project_uuid')]) }}">
                            {{ $project->name }}</a>
                        <button type="button" @click.stop="toggle()" class="px-1 text-warning">
                            <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-90': projectOpen }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M9 5l7 7-7 7">
                                </path>
                            </svg>
                        </button>

                        <div x-show="projectOpen" @click.outside="close()"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                            class="absolute z-20 top-full mt-1 w-56 -ml-2 bg-white dark:bg-coolgray-100 rounded-md shadow-lg py-1 border border-neutral-200 dark:border-coolgray-200 max-h-96 overflow-y-auto scrollbar">
                            @foreach ($allProjects as $proj)
                                <a href="{{ route('project.show', ['project_uuid' => $proj->uuid]) }}"
                                    class="block px-4 py-2 text-sm truncate hover:bg-neutral-100 dark:hover:bg-coolgray-200 {{ $proj->uuid === $project->uuid ? 'dark:text-warning font-semibold' : '' }}"
                                    title="{{ $proj->name }}">
                                    {{ $proj->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </li>
                <li class="inline-flex items-center" x-data="{ envOpen: false, activeEnv: null, envPositions: {}, closeTimeout: null, envTimeout: null, toggle() { this.envOpen = !this.envOpen; if (!this.envOpen) { this.activeEnv = null; } }, open() { clearTimeout(this.closeTimeout); this.envOpen = true }, close() { this.closeTimeout = setTimeout(() => { this.envOpen = false; this.activeEnv = null; }, 100) }, openEnv(id) { clearTimeout(this.closeTimeout); clearTimeout(this.envTimeout); this.activeEnv = id }, closeEnv() { this.envTimeout = setTimeout(() => { this.activeEnv = null; }, 100) } }">
                    <div class="flex items-center relative" @mouseenter="open()" @mouseleave="close()">
                        <a class="text-xs truncate lg:text-sm hover:text-warning" {{ wireNavigate() }}
                            href="{{ route('project.resource.index', ['project_uuid' => data_get($parameters, 'project_uuid'), 'environment_uuid' => $environment->uuid]) }}">
                            {{ $environment->name }}
                        </a>

                        <div x-show="envOpen" @click.outside="close()"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                            class="absolute z-20 top-full mt-1 left-0 sm:left-auto max-w-[calc(100vw-1rem)]"
                            x-init="$nextTick(() => { const rect = $el.getBoundingClientRect(); if (rect.right > window.innerWidth) { $el.style.left = 'auto';
                                    $el.style.right = '0'; } })">
                            <!-- Environment List -->
                            <div
                                class="relative w-48 bg-white dark:bg-coolgray-100 rounded-md shadow-lg py-1 border border-neutral-200 dark:border-coolgray-200 max-h-96 overflow-y-auto scrollbar">
                                @foreach ($allEnvironments as $env)
                                    @php
                                        $envDatabases = collect()
                                            ->merge($env->postgresqls ?? collect())
                                            ->merge($env->redis ?? collect())
                                            ->merge($env->mongodbs ?? collect())
                                            ->merge($env->mysqls ?? collect())
                                            ->merge($env->mariadbs ?? collect())
                                            ->merge($env->keydbs ?? collect())
                                            ->merge($env->dragonflies ?? collect())
                                            ->merge($env->clickhouses ?? collect());
                                        $envResources = collect()
                                            ->merge($env->applications->map(fn($app) => ['type' => 'application', 'resource' => $app]))
                                            ->merge($envDatabases->map(fn($db) => ['type' => 'database', 'resource' => $db]))
                                            ->merge($env->services->map(fn($svc) => ['type' => 'service', 'resource' => $svc]))
                                            ->sortBy(fn($item) => strtolower($item['resource']->name));
                                    @endphp
                                    <div @mouseenter="openEnv('{{ $env->uuid }}'); envPositions['{{ $env->uuid }}'] = $el.offsetTop - ($el.closest('.overflow-y-auto')?.scrollTop || 0)"
                                        @mouseleave="closeEnv()">
                                        <a href="{{ route('project.resource.index', ['project_uuid' => data_get($parameters, 'project_uuid'), 'environment_uuid' => $env->uuid]) }}"
                                            {{ wireNavigate() }}
                                            class="flex items-center justify-between gap-2 px-4 py-2 text-sm hover:bg-neutral-100 dark:hover:bg-coolgray-200 {{ $env->uuid === $environment->uuid ? 'dark:text-warning font-semibold' : '' }}"
                                            title="{{ $env->name }}">
                                            <span class="truncate">{{ $env->name }}</span>
                                            @if ($envResources->count() > 0)
                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="4" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            @endif
                                        </a>
                                    </div>
                                @endforeach
                                <div class="border-t border-neutral-200 dark:border-coolgray-200 mt-1 pt-1">
                                    <a href="{{ route('project.show', ['project_uuid' => data_get($parameters, 'project_uuid')]) }}"
                                        {{ wireNavigate() }}
                                        class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-neutral-100 dark:hover:bg-coolgray-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                        Create / Edit
                                    </a>
                                </div>
                            </div>

                            <!-- Resources Sub-dropdown (2nd level) -->
                            @foreach ($allEnvironments as $env)
                                @php
                                    $envDatabases = collect()
                                        ->merge($env->postgresqls ?? collect())
                                        ->merge($env->redis ?? collect())
                                        ->merge($env->mongodbs ?? collect())
                                        ->merge($env->mysqls ?? collect())
                                        ->merge($env->mariadbs ?? collect())
                                        ->merge($env->keydbs ?? collect())
                                        ->merge($env->dragonflies ?? collect())
                                        ->merge($env->clickhouses ?? collect());
                                    $envResources = collect()
                                        ->merge($env->applications->map(fn($app) => ['type' => 'application', 'resource' => $app]))
                                        ->merge($envDatabases->map(fn($db) => ['type' => 'database', 'resource' => $db]))
                                        ->merge($env->services->map(fn($svc) => ['type' => 'service', 'resource' => $svc]));
                                @endphp
                                @if ($envResources->count() > 0)
                                    <div x-show="activeEnv === '{{ $env->uuid }}'" x-cloak
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                        @mouseenter="openEnv('{{ $env->uuid }}')" @mouseleave="closeEnv()"
                                        :style="'position: absolute; left: 100%; top: ' + (envPositions['{{ $env->uuid }}'] || 0) + 'px; z-index: 30;'"
                                        class="flex flex-col sm:flex-row items-start pl-1">
                                        <div
                                            class="relative w-56 bg-white dark:bg-coolgray-100 rounded-md shadow-lg py-1 border border-neutral-200 dark:border-coolgray-200 max-h-96 overflow-y-auto scrollbar">
                                            @foreach ($envResources as $envResource)
                                                @php
                                                    $resType = $envResource['type'];
                                                    $res = $envResource['resource'];
                                                    $resRoute = match ($resType) {
                                                        'application' => route('project.application.configuration', [
                                                            'project_uuid' => $project->uuid,
                                                            'environment_uuid' => $env->uuid,
                                                            'application_uuid' => $res->uuid,
                                                        ]),
                                                        'service' => route('project.service.configuration', [
                                                            'project_uuid' => $project->uuid,
                                                            'environment_uuid' => $env->uuid,
                                                            'service_uuid' => $res->uuid,
                                                        ]),
                                                        'database' => route('project.database.configuration', [
                                                            'project_uuid' => $project->uuid,
                                                            'environment_uuid' => $env->uuid,
                                                            'database_uuid' => $res->uuid,
                                                        ]),
                                                    };
                                                @endphp
                                                <a href="{{ $resRoute }}" {{ wireNavigate() }}
                                                    class="block px-4 py-2 text-sm truncate hover:bg-neutral-100 dark:hover:bg-coolgray-200"
                                                    title="{{ $res->name }}">
                                                    {{ $res->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </li>
            </ol>
        </nav>
    </div>
    @php($resourceFlowDataId = 'resource-flow-data-'.data_get($environment, 'uuid'))
    @php($canAddResource = auth()->user()?->can('createAnyResource'))
    <div x-data="{ view: (localStorage.getItem('resourceView') || 'canvas'), setView(v) { this.view = v; localStorage.setItem('resourceView', v); } }">
        <div class="flex items-center gap-2 mb-3">
            <div
                class="inline-flex p-0.5 border rounded-lg border-neutral-200 dark:border-coolgray-200 bg-neutral-100 dark:bg-coolgray-100">
                <button type="button" @click="setView('canvas')"
                    :class="view === 'canvas' ? 'bg-white dark:bg-coolgray-300 text-black dark:text-white shadow-sm' :
                        'text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300'"
                    class="flex items-center gap-1.5 px-3 h-7 text-xs font-semibold rounded-md transition-colors">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7" rx="1.5" />
                        <rect x="14" y="3" width="7" height="7" rx="1.5" />
                        <rect x="14" y="14" width="7" height="7" rx="1.5" />
                        <rect x="3" y="14" width="7" height="7" rx="1.5" />
                    </svg>
                    Canvas
                </button>
                <button type="button" @click="setView('list')"
                    :class="view === 'list' ? 'bg-white dark:bg-coolgray-300 text-black dark:text-white shadow-sm' :
                        'text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300'"
                    class="flex items-center gap-1.5 px-3 h-7 text-xs font-semibold rounded-md transition-colors">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="8" y1="6" x2="21" y2="6" />
                        <line x1="8" y1="12" x2="21" y2="12" />
                        <line x1="8" y1="18" x2="21" y2="18" />
                        <line x1="3" y1="6" x2="3.01" y2="6" />
                        <line x1="3" y1="12" x2="3.01" y2="12" />
                        <line x1="3" y1="18" x2="3.01" y2="18" />
                    </svg>
                    List
                </button>
            </div>
        </div>

        <script type="application/json" id="{{ $resourceFlowDataId }}">@json($resourceFlow)</script>
        <div x-show="view === 'canvas'" wire:poll.30s.visible="pollStatuses"
            @resource-flow:sync.window="$wire.$refresh().then(() => window.mountResourceFlows && window.mountResourceFlows())">
            <div data-resource-flow-canvas data-flow-source="{{ $resourceFlowDataId }}"
                @if ($canAddResource) data-add-url="{{ route('project.resource.create', ['project_uuid' => data_get($parameters, 'project_uuid'), 'environment_uuid' => data_get($environment, 'uuid')]) }}" @endif
                data-project="{{ $project->name }}" data-environment="{{ $environment->name }}" wire:ignore
                class="w-full overflow-hidden border rounded-xl border-neutral-200 dark:border-coolgray-200 bg-base h-[calc(100vh-13rem)] min-h-[520px]">
            </div>
        </div>

        <div x-show="view === 'list'" x-cloak x-data="searchComponent()">
            <x-forms.input placeholder="Search for name, fqdn..." x-model="search" id="null" />
            <template
                x-if="filteredApplications.length === 0 && filteredDatabases.length === 0 && filteredServices.length === 0">
                <div class="flex flex-col items-center justify-center p-8 text-center">
                    <div x-show="search.length > 0">
                        <p class="text-neutral-600 dark:text-neutral-400">No resource found with the search term "<span
                                class="font-semibold" x-text="search"></span>".</p>
                        <p class="text-sm text-neutral-500 dark:text-neutral-500 mt-1">Try adjusting your search
                            criteria.</p>
                    </div>
                    <div x-show="search.length === 0">
                        <p class="text-neutral-600 dark:text-neutral-400">No resources found in this environment.</p>
                        @cannot('createAnyResource')
                            <p class="text-sm text-neutral-500 dark:text-neutral-500 mt-1">Contact your team administrator
                                to add resources.</p>
                        @endcannot
                    </div>
                </div>
            </template>

            <template x-if="filteredApplications.length > 0">
                <h2 class="pt-4">Applications</h2>
            </template>
            <div x-show="filteredApplications.length > 0"
                class="grid grid-cols-1 gap-4 pt-4 lg:grid-cols-2 xl:grid-cols-3">
                <template x-for="item in filteredApplications" :key="item.uuid">
                    <span>
                        <a class="h-24 coolbox group" :href="item.hrefLink" {{ wireNavigate() }}>
                            <div class="flex flex-col w-full">
                                <div class="flex gap-2 px-4">
                                    <div class="pb-2 truncate box-title" x-text="item.name"></div>
                                    <div class="flex-1"></div>
                                    <template x-if="item.status.startsWith('running')">
                                        <div title="running" class="bg-success badge-dashboard"></div>
                                    </template>
                                    <template x-if="item.status.startsWith('exited')">
                                        <div title="exited" class="bg-error badge-dashboard"></div>
                                    </template>
                                    <template x-if="item.status.startsWith('starting')">
                                        <div title="starting" class="bg-warning badge-dashboard"></div>
                                    </template>
                                    <template x-if="item.status.startsWith('restarting')">
                                        <div title="restarting" class="bg-warning badge-dashboard"></div>
                                    </template>
                                    <template x-if="item.status.startsWith('degraded')">
                                        <div title="degraded" class="bg-warning badge-dashboard"></div>
                                    </template>
                                </div>
                                <div class="max-w-full px-4 truncate box-description" x-text="item.description"></div>
                                <div class="max-w-full px-4 truncate box-description" x-text="item.fqdn"></div>
                                <div class="max-w-full px-4 pt-1 truncate box-description">Server: <span
                                        x-text="item.destination?.server?.name || 'Unknown'"></span></div>
                                <template x-if="item.server_status == false">
                                    <div class="px-4 text-xs font-bold text-error">Server is unreachable or
                                        misconfigured
                                    </div>
                                </template>
                            </div>
                        </a>
                        <div
                            class="flex flex-wrap gap-1 pt-1 dark:group-hover:text-white group-hover:text-black group min-h-6">
                            <template x-for="tag in item.tags">
                                <a :href="`/tags/${tag.name}`" class="tag" x-text="tag.name">
                                </a>
                            </template>
                            <a :href="`${item.hrefLink}/tags`" class="add-tag">
                                Add tag
                            </a>
                        </div>
                    </span>
                </template>
            </div>
            <template x-if="filteredDatabases.length > 0">
                <h2 class="pt-4">Databases</h2>
            </template>
            <div x-show="filteredDatabases.length > 0"
                class="grid grid-cols-1 gap-4 pt-4 lg:grid-cols-2 xl:grid-cols-3">
                <template x-for="item in filteredDatabases" :key="item.uuid">
                    <span>
                        <a class="h-24 coolbox group" :href="item.hrefLink" {{ wireNavigate() }}>
                            <div class="flex flex-col w-full">
                                <div class="flex gap-2 px-4">
                                    <div class="pb-2 truncate box-title" x-text="item.name"></div>
                                    <div class="flex-1"></div>
                                    <template x-if="item.status.startsWith('running')">
                                        <div title="running" class="bg-success badge-dashboard"></div>
                                    </template>
                                    <template x-if="item.status.startsWith('exited')">
                                        <div title="exited" class="bg-error badge-dashboard"></div>
                                    </template>
                                    <template x-if="item.status.startsWith('starting')">
                                        <div title="starting" class="bg-warning badge-dashboard"></div>
                                    </template>
                                    <template x-if="item.status.startsWith('restarting')">
                                        <div title="restarting" class="bg-warning badge-dashboard"></div>
                                    </template>
                                    <template x-if="item.status.startsWith('degraded')">
                                        <div title="degraded" class="bg-warning badge-dashboard"></div>
                                    </template>
                                </div>
                                <div class="max-w-full px-4 truncate box-description" x-text="item.description"></div>
                                <div class="max-w-full px-4 truncate box-description" x-text="item.fqdn"></div>
                                <div class="max-w-full px-4 pt-1 truncate box-description">Server: <span
                                        x-text="item.destination?.server?.name || 'Unknown'"></span></div>
                                <template x-if="item.server_status == false">
                                    <div class="px-4 text-xs font-bold text-error">Server is unreachable or
                                        misconfigured
                                    </div>
                                </template>
                            </div>
                        </a>
                        <div
                            class="flex flex-wrap gap-1 pt-1 dark:group-hover:text-white group-hover:text-black group min-h-6">
                            <template x-for="tag in item.tags">
                                <a :href="`/tags/${tag.name}`" class="tag" x-text="tag.name">
                                </a>
                            </template>
                            <a :href="`${item.hrefLink}/tags`" class="add-tag">
                                Add tag
                            </a>
                        </div>
                    </span>
                </template>
            </div>
            <template x-if="filteredServices.length > 0">
                <h2 class="pt-4">Services</h2>
            </template>
            <div x-show="filteredServices.length > 0"
                class="grid grid-cols-1 gap-4 pt-4 lg:grid-cols-2 xl:grid-cols-3">
                <template x-for="item in filteredServices" :key="item.uuid">
                    <span>
                        <a class="h-24 coolbox group" :href="item.hrefLink" {{ wireNavigate() }}>
                            <div class="flex flex-col w-full">
                                <div class="flex gap-2 px-4">
                                    <div class="pb-2 truncate box-title" x-text="item.name"></div>
                                    <div class="flex-1"></div>
                                    <template x-if="item.status.startsWith('running')">
                                        <div title="running" class="bg-success badge-dashboard"></div>
                                    </template>
                                    <template x-if="item.status.startsWith('exited')">
                                        <div title="exited" class="bg-error badge-dashboard"></div>
                                    </template>
                                    <template x-if="item.status.startsWith('starting')">
                                        <div title="starting" class="bg-warning badge-dashboard"></div>
                                    </template>
                                    <template x-if="item.status.startsWith('restarting')">
                                        <div title="restarting" class="bg-warning badge-dashboard"></div>
                                    </template>
                                    <template x-if="item.status.startsWith('degraded')">
                                        <div title="degraded" class="bg-warning badge-dashboard"></div>
                                    </template>
                                </div>
                                <div class="max-w-full px-4 truncate box-description" x-text="item.description"></div>
                                <div class="max-w-full px-4 truncate box-description" x-text="item.fqdn"></div>
                                <div class="max-w-full px-4 pt-1 truncate box-description">Server: <span
                                        x-text="item.destination?.server?.name || 'Unknown'"></span></div>
                                <template x-if="item.server_status == false">
                                    <div class="px-4 text-xs font-bold text-error">Server is unreachable or
                                        misconfigured
                                    </div>
                                </template>
                            </div>
                        </a>
                        <div
                            class="flex flex-wrap gap-1 pt-1 dark:group-hover:text-white group-hover:text-black group min-h-6">
                            <template x-for="tag in item.tags">
                                <a :href="`/tags/${tag.name}`" class="tag" x-text="tag.name">
                                </a>
                            </template>
                            <a :href="`${item.hrefLink}/tags`" class="add-tag">
                                Add tag
                            </a>
                        </div>
                    </span>
                </template>
            </div>
        </div>

        {{-- Railway-style resource detail panel, opened by clicking a canvas node --}}
        <div x-data="{ open: false, node: {}, openPanel(d) { this.node = d || {}; this.open = true }, close() { this.open = false } }"
            @resource-flow:open.window="openPanel($event.detail)" @keydown.escape.window="close()">
            <div x-show="open" x-cloak class="fixed inset-0 z-50" style="display: none">
                <div x-show="open" x-transition.opacity class="absolute inset-0 bg-black/50" @click="close()"></div>
                <div x-show="open" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
                    class="absolute top-0 right-0 w-full h-full max-w-md overflow-y-auto bg-white border-l shadow-2xl dark:bg-coolgray-100 border-neutral-200 dark:border-coolgray-200 scrollbar">
                    <div class="flex items-start gap-3 p-5 border-b border-neutral-200 dark:border-coolgray-200">
                        <img :src="node.icon" alt="" x-show="node.icon" x-on:error="$el.style.display='none'"
                            class="p-1.5 rounded-lg w-9 h-9 bg-neutral-100 dark:bg-coolgray-300" />
                        <div class="flex-1 min-w-0">
                            <div class="text-base font-bold text-black truncate dark:text-white" x-text="node.label"></div>
                            <div class="text-xs tracking-wide text-neutral-500 uppercase" x-text="node.kind"></div>
                        </div>
                        <button type="button" @click="close()"
                            class="text-neutral-400 hover:text-black dark:hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full" :style="`background:${node.statusColor}`"></span>
                            <span class="text-sm font-semibold" :style="`color:${node.statusColor}`"
                                x-text="node.statusLabel"></span>
                        </div>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between gap-3" x-show="node.fqdn">
                                <dt class="text-neutral-500">Domain</dt>
                                <dd class="truncate">
                                    <a :href="node.fqdn && (node.fqdn.startsWith('http') ? node.fqdn : 'https://' + node.fqdn)"
                                        target="_blank" rel="noopener"
                                        class="text-coollabs dark:text-warning hover:underline" x-text="node.fqdn"></a>
                                </dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-neutral-500">Server</dt>
                                <dd class="truncate" x-text="node.server"></dd>
                            </div>
                            <template x-if="node.volumes && node.volumes.length">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-neutral-500">Volumes</dt>
                                    <dd class="truncate" x-text="node.volumes.join(', ')"></dd>
                                </div>
                            </template>
                        </dl>
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <a :href="node.links?.settings" wire:navigate x-show="node.links?.settings"
                                class="justify-center button">Settings</a>
                            <a :href="node.links?.deployments" wire:navigate x-show="node.links?.deployments"
                                class="justify-center button">Deployments</a>
                            <a :href="node.links?.logs" wire:navigate x-show="node.links?.logs"
                                class="justify-center button">Logs</a>
                            <a :href="node.links?.terminal" wire:navigate x-show="node.links?.terminal"
                                class="justify-center button">Terminal</a>
                            <a :href="node.links?.variables" wire:navigate x-show="node.links?.variables"
                                class="justify-center button">Variables</a>
                        </div>
                        <a :href="node.href" wire:navigate x-show="node.href"
                            class="flex items-center justify-center w-full text-sm font-semibold text-white rounded-sm h-9 bg-coollabs hover:bg-coollabs-100">
                            Open resource →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    function sortFn(a, b) {
        return a.name.localeCompare(b.name)
    }

    function searchComponent() {
        return {
            search: '',
            applications: @js($applicationsJs),
            postgresqls: @js($postgresqlsJs),
            redis: @js($redisJs),
            mongodbs: @js($mongodbsJs),
            mysqls: @js($mysqlsJs),
            mariadbs: @js($mariadbsJs),
            keydbs: @js($keydbsJs),
            dragonflies: @js($dragonfliesJs),
            clickhouses: @js($clickhousesJs),
            services: @js($servicesJs),
            filterAndSort(items) {
                if (this.search === '') {
                    return Object.values(items).sort(sortFn);
                }
                const searchLower = this.search.toLowerCase();
                return Object.values(items).filter(item => {
                    return (item.name?.toLowerCase().includes(searchLower) ||
                        item.fqdn?.toLowerCase().includes(searchLower) ||
                        item.description?.toLowerCase().includes(searchLower) ||
                        item.tags?.some(tag => tag.name.toLowerCase().includes(searchLower)));
                }).sort(sortFn);
            },
            get filteredApplications() {
                return this.filterAndSort(this.applications)
            },
            get filteredDatabases() {
                return [
                    this.postgresqls,
                    this.redis,
                    this.mongodbs,
                    this.mysqls,
                    this.mariadbs,
                    this.keydbs,
                    this.dragonflies,
                    this.clickhouses,
                ].flatMap((items) => this.filterAndSort(items))
            },
            get filteredServices() {
                return this.filterAndSort(this.services)
            }
        };
    }
</script>
