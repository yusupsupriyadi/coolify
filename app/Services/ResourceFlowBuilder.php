<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ResourceFlowBuilder
{
    private const CARD_WIDTH = 280;

    private const CARD_HEIGHT = 132;

    private const CARD_GAP_X = 24;

    private const CARD_GAP_Y = 20;

    private const GROUP_PAD_X = 20;

    private const GROUP_PAD_TOP = 56;

    private const GROUP_PAD_BOTTOM = 20;

    private const GROUP_GAP_X = 96;

    private const MAX_COLUMNS = 2;

    /**
     * Build a Railway-style, resource-centric canvas: every resource is a card,
     * grouped into a container per server.
     *
     * @param  Collection<int, array<string, mixed>>  $resources
     * @param  array<int, array{from: string, fromType: string, to: string, toType: string}>  $connections
     * @param  array<string, array{x: int|float, y: int|float}>  $savedPositions
     * @return array{
     *     nodes: array<int, array<string, mixed>>,
     *     edges: array<int, array<string, mixed>>,
     *     summary: array{total: int, applications: int, databases: int, services: int, servers: int}
     * }
     */
    public static function build(string $projectName, string $environmentName, Collection $resources, array $connections = [], array $savedPositions = []): array
    {
        $resources = $resources
            ->sortBy([
                fn (array $left, array $right): int => self::serverName($left) <=> self::serverName($right),
                fn (array $left, array $right): int => ((string) $left['type']) <=> ((string) $right['type']),
                fn (array $left, array $right): int => ((string) $left['name']) <=> ((string) $right['name']),
            ])
            ->values();

        $nodes = [];
        $nodeIdByUuid = [];
        $resourcesByServer = $resources->groupBy(fn (array $resource): string => self::serverName($resource));

        $groupX = 0;

        foreach ($resourcesByServer as $serverName => $serverResources) {
            $serverResources = $serverResources->values();
            $count = $serverResources->count();
            $columns = (int) min(self::MAX_COLUMNS, max(1, $count));
            $rows = (int) ceil($count / $columns);

            $innerWidth = ($columns * self::CARD_WIDTH) + (($columns - 1) * self::CARD_GAP_X);
            $innerHeight = ($rows * self::CARD_HEIGHT) + (max(0, $rows - 1) * self::CARD_GAP_Y);
            $groupWidth = $innerWidth + (self::GROUP_PAD_X * 2);
            $groupHeight = $innerHeight + self::GROUP_PAD_TOP + self::GROUP_PAD_BOTTOM;

            $groupId = self::nodeId('group-server', (string) $serverName);
            $groupPos = self::positionFor($savedPositions, $groupId, $groupX, 0);
            $nodes[] = self::groupNode($groupId, (string) $serverName, $count, $groupPos['x'], $groupPos['y'], $groupWidth, $groupHeight);

            foreach ($serverResources as $index => $resource) {
                $column = $index % $columns;
                $row = intdiv($index, $columns);
                $x = self::GROUP_PAD_X + ($column * (self::CARD_WIDTH + self::CARD_GAP_X));
                $y = self::GROUP_PAD_TOP + ($row * (self::CARD_HEIGHT + self::CARD_GAP_Y));

                $resourceId = self::nodeId('resource-'.$resource['type'], (string) $resource['uuid']);
                $nodeIdByUuid[(string) $resource['uuid']] = $resourceId;
                $pos = self::positionFor($savedPositions, $resourceId, $x, $y);
                $nodes[] = self::resourceNode($resourceId, $groupId, $resource, $pos['x'], $pos['y']);
            }

            $groupX += $groupWidth + self::GROUP_GAP_X;
        }

        return [
            'nodes' => $nodes,
            'edges' => self::buildEdges($connections, $nodeIdByUuid),
            'summary' => [
                'total' => $resources->count(),
                'applications' => $resources->where('type', 'application')->count(),
                'databases' => $resources->where('type', 'database')->count(),
                'services' => $resources->where('type', 'service')->count(),
                'servers' => $resourcesByServer->count(),
            ],
        ];
    }

    /**
     * @param  array<int, array{from: string, fromType: string, to: string, toType: string}>  $connections
     * @param  array<string, string>  $nodeIdByUuid
     * @return array<int, array<string, mixed>>
     */
    private static function buildEdges(array $connections, array $nodeIdByUuid): array
    {
        $edges = [];
        $seen = [];

        foreach ($connections as $connection) {
            $from = (string) ($connection['from'] ?? '');
            $to = (string) ($connection['to'] ?? '');

            if (! isset($nodeIdByUuid[$from], $nodeIdByUuid[$to]) || $from === $to) {
                continue;
            }

            $source = $nodeIdByUuid[$from];
            $target = $nodeIdByUuid[$to];
            $id = 'edge-'.$source.'-to-'.$target;

            if (isset($seen[$id])) {
                continue;
            }

            $seen[$id] = true;
            $edges[] = [
                'id' => $id,
                'source' => $source,
                'target' => $target,
                'sourceHandle' => 'out',
                'targetHandle' => 'in',
            ];
        }

        return $edges;
    }

    /**
     * @param  array<string, array{x: int|float, y: int|float}>  $saved
     * @return array{x: int|float, y: int|float}
     */
    private static function positionFor(array $saved, string $id, int|float $defaultX, int|float $defaultY): array
    {
        $pos = $saved[$id] ?? null;

        if (is_array($pos) && isset($pos['x'], $pos['y']) && is_numeric($pos['x']) && is_numeric($pos['y'])) {
            return ['x' => (float) $pos['x'], 'y' => (float) $pos['y']];
        }

        return ['x' => $defaultX, 'y' => $defaultY];
    }

    /**
     * @return array<string, mixed>
     */
    private static function groupNode(string $id, string $serverName, int $resourceCount, int|float $x, int|float $y, int $width, int $height): array
    {
        return [
            'id' => $id,
            'type' => 'group',
            'position' => ['x' => $x, 'y' => $y],
            'draggable' => true,
            'selectable' => false,
            'style' => ['width' => $width, 'height' => $height],
            'data' => [
                'kind' => 'server',
                'label' => $serverName,
                'resourceCount' => $resourceCount,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return array<string, mixed>
     */
    private static function resourceNode(string $id, string $parentId, array $resource, int|float $x, int|float $y): array
    {
        $status = (string) ($resource['status'] ?? '');
        [$statusLabel, $statusColor] = self::statusMeta($status);
        $type = (string) $resource['type'];
        $subtype = (string) ($resource['subtype'] ?? $type);
        $description = $resource['description'] ?? null;
        $fqdn = $resource['fqdn'] ?? null;

        return [
            'id' => $id,
            'type' => 'resource',
            'position' => ['x' => $x, 'y' => $y],
            'parentId' => $parentId,
            'extent' => 'parent',
            'data' => [
                'kind' => $type,
                'subtype' => $subtype,
                'label' => (string) $resource['name'],
                'subtitle' => self::subtitle($fqdn, $description),
                'description' => $description,
                'fqdn' => $fqdn,
                'status' => $status,
                'statusLabel' => $statusLabel,
                'statusColor' => $statusColor,
                'href' => (string) ($resource['hrefLink'] ?? ''),
                'icon' => self::iconFor($type, $subtype),
                'server' => self::serverName($resource),
                'volumes' => array_values(array_filter((array) ($resource['volumes'] ?? []))),
                'links' => is_array($resource['links'] ?? null) ? $resource['links'] : [],
            ],
        ];
    }

    private static function subtitle(?string $fqdn, ?string $description): ?string
    {
        if (is_string($fqdn) && $fqdn !== '') {
            return str($fqdn)->after('://')->before('/')->value();
        }

        if (is_string($description) && $description !== '') {
            return $description;
        }

        return null;
    }

    public static function resourceNodeId(string $type, string $uuid): string
    {
        return self::nodeId('resource-'.$type, $uuid);
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function statusMeta(string $status): array
    {
        $status = strtolower($status);

        if (str_starts_with($status, 'running')) {
            if (str_contains($status, 'unhealthy')) {
                return ['Degraded', '#fcd452'];
            }

            return ['Online', '#22c55e'];
        }

        if (str_starts_with($status, 'starting') || str_starts_with($status, 'restarting')) {
            return ['Deploying', '#fcd452'];
        }

        if (str_starts_with($status, 'degraded')) {
            return ['Degraded', '#fcd452'];
        }

        if (str_starts_with($status, 'exited') || str_starts_with($status, 'dead') || str_starts_with($status, 'stopped')) {
            return ['Offline', '#ef4444'];
        }

        return ['Unknown', '#737373'];
    }

    private static function iconFor(string $type, string $subtype): string
    {
        $map = [
            'git' => 'github',
            'docker' => 'docker',
            'service' => 'docker',
            'standalone-postgresql' => 'postgresql',
            'standalone-redis' => 'redis',
            'standalone-keydb' => 'redis',
            'standalone-dragonfly' => 'redis',
            'standalone-mysql' => 'mysql',
            'standalone-mariadb' => 'mariadb',
            'standalone-mongodb' => 'mongodb',
            'standalone-clickhouse' => 'clickhouse',
        ];

        $icon = $map[$subtype] ?? 'docker';

        return '/svgs/'.$icon.'.svg';
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private static function serverName(array $resource): string
    {
        $name = $resource['server'] ?? data_get($resource, 'destination.server.name');

        if (is_string($name) && $name !== '') {
            return $name;
        }

        return 'Unknown';
    }

    private static function nodeId(string $type, string $value): string
    {
        return $type.'-'.Str::slug($value);
    }
}
