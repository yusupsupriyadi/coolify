import React, { useMemo } from 'react';
import { createRoot } from 'react-dom/client';
import {
    Background,
    Controls,
    Handle,
    MarkerType,
    MiniMap,
    Position,
    ReactFlow,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';

const roots = new WeakMap();

const colors = {
    application: '#60a5fa',
    database: '#22c55e',
    service: '#a78bfa',
    running: '#22c55e',
    warning: '#fcd452',
    error: '#dc2626',
    idle: '#737373',
};

function statusColor(status = '') {
    if (status.startsWith('running')) {
        return colors.running;
    }

    if (status.startsWith('starting') || status.startsWith('restarting') || status.startsWith('degraded')) {
        return colors.warning;
    }

    if (status.startsWith('exited') || status.startsWith('dead')) {
        return colors.error;
    }

    return colors.idle;
}

function cardStyle(accent) {
    return {
        width: 260,
        border: '1px solid #323232',
        borderLeft: `4px solid ${accent}`,
        borderRadius: 14,
        background: 'linear-gradient(135deg, rgba(32,32,32,.98), rgba(16,16,16,.98))',
        color: '#f5f5f5',
        boxShadow: '0 18px 45px rgba(0,0,0,.28)',
        padding: 14,
    };
}

function EnvironmentNode({ data }) {
    return (
        <div style={cardStyle('#fcd452')}>
            <Handle type="source" position={Position.Right} />
            <div style={{ fontSize: 11, letterSpacing: '.12em', textTransform: 'uppercase', color: '#a3a3a3' }}>
                Environment
            </div>
            <div style={{ marginTop: 4, fontSize: 20, fontWeight: 800 }}>{data.label}</div>
            <div style={{ marginTop: 2, color: '#d4d4d4' }}>{data.project}</div>
        </div>
    );
}

function ServerNode({ data }) {
    return (
        <div style={cardStyle('#f97316')}>
            <Handle type="target" position={Position.Left} />
            <Handle type="source" position={Position.Right} />
            <div style={{ fontSize: 11, letterSpacing: '.12em', textTransform: 'uppercase', color: '#a3a3a3' }}>
                Server
            </div>
            <div style={{ marginTop: 4, fontSize: 18, fontWeight: 800 }}>{data.label}</div>
            <div style={{ marginTop: 8, color: '#d4d4d4' }}>{data.resourceCount} resources</div>
        </div>
    );
}

function ResourceNode({ data }) {
    const accent = colors[data.kind] || '#7317ff';
    const indicator = statusColor(data.status);

    return (
        <a href={data.href || '#'} style={{ textDecoration: 'none' }}>
            <div style={cardStyle(accent)}>
                <Handle type="target" position={Position.Left} />
                <div style={{ display: 'flex', gap: 10, alignItems: 'center' }}>
                    <div
                        title={data.status || 'unknown'}
                        style={{
                            width: 10,
                            height: 10,
                            borderRadius: 999,
                            background: indicator,
                            boxShadow: `0 0 18px ${indicator}`,
                            flex: '0 0 auto',
                        }}
                    />
                    <div style={{ minWidth: 0 }}>
                        <div style={{ fontSize: 11, letterSpacing: '.12em', textTransform: 'uppercase', color: '#a3a3a3' }}>
                            {data.kind}
                        </div>
                        <div style={{ marginTop: 2, fontSize: 18, fontWeight: 800, color: '#fff', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                            {data.label}
                        </div>
                    </div>
                </div>
                {data.description ? (
                    <div style={{ marginTop: 10, color: '#d4d4d4', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                        {data.description}
                    </div>
                ) : null}
                {data.fqdn ? (
                    <div style={{ marginTop: 8, color: '#a3a3a3', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                        {data.fqdn}
                    </div>
                ) : null}
                <div style={{ marginTop: 10, fontSize: 12, color: '#a3a3a3' }}>Server: {data.server}</div>
            </div>
        </a>
    );
}

const nodeTypes = {
    environment: EnvironmentNode,
    server: ServerNode,
    resource: ResourceNode,
};

function ResourceFlowCanvas({ flow }) {
    const nodes = useMemo(() => flow.nodes || [], [flow]);
    const edges = useMemo(() => (flow.edges || []).map((edge) => ({
        ...edge,
        type: 'smoothstep',
        markerEnd: { type: MarkerType.ArrowClosed, color: '#525252' },
        style: { stroke: '#525252', strokeWidth: 2 },
    })), [flow]);

    return (
        <div style={{ width: '100%', height: '100%', position: 'relative' }}>
            <ReactFlow
                nodes={nodes}
                edges={edges}
                nodeTypes={nodeTypes}
                fitView
                fitViewOptions={{ padding: 0.22 }}
                minZoom={0.25}
                maxZoom={1.4}
                proOptions={{ hideAttribution: true }}
                nodesDraggable
                nodesConnectable={false}
                elementsSelectable
            >
                <Background color="#323232" gap={24} />
                <MiniMap
                    pannable
                    zoomable
                    nodeColor={(node) => {
                        if (node.type === 'environment') {
                            return '#fcd452';
                        }
                        if (node.type === 'server') {
                            return '#f97316';
                        }
                        return colors[node.data?.kind] || '#7317ff';
                    }}
                    maskColor="rgba(16,16,16,.72)"
                    style={{ background: '#181818', border: '1px solid #323232' }}
                />
                <Controls />
            </ReactFlow>
            {(flow.summary?.total || 0) === 0 ? (
                <div style={{ position: 'absolute', inset: 0, display: 'grid', placeItems: 'center', pointerEvents: 'none' }}>
                    <div style={{ border: '1px dashed #525252', borderRadius: 16, padding: 24, color: '#d4d4d4', background: 'rgba(16,16,16,.82)' }}>
                        No resources yet. Add a resource to start building your canvas.
                    </div>
                </div>
            ) : null}
        </div>
    );
}

export function mountResourceFlows() {
    document.querySelectorAll('[data-resource-flow-canvas]').forEach((container) => {
        const sourceId = container.getAttribute('data-flow-source');
        const source = sourceId ? document.getElementById(sourceId) : null;

        if (!source) {
            return;
        }

        let flow;
        try {
            flow = JSON.parse(source.textContent || '{}');
        } catch (error) {
            console.error('Unable to parse resource flow data', error);
            return;
        }

        if (!roots.has(container)) {
            roots.set(container, createRoot(container));
        }

        roots.get(container).render(<ResourceFlowCanvas flow={flow} />);
    });
}

window.mountResourceFlows = mountResourceFlows;
