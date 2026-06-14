import React, { useCallback, useMemo } from 'react';
import { createRoot } from 'react-dom/client';
import {
    Background,
    BackgroundVariant,
    Controls,
    Handle,
    MarkerType,
    Panel,
    Position,
    ReactFlow,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';

const roots = new WeakMap();

let stylesInjected = false;

function injectStyles() {
    if (stylesInjected) {
        return;
    }
    stylesInjected = true;

    const style = document.createElement('style');
    style.setAttribute('data-resource-flow-styles', 'true');
    style.textContent = `
        .rf-canvas { background: #0c0c0e; }
        .rf-canvas .react-flow__node-group { background: transparent; border: none; padding: 0; border-radius: 0; }
        .rf-group {
            width: 100%; height: 100%;
            border: 1px solid #232327; border-radius: 18px;
            background: rgba(22, 22, 25, .5);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.02);
        }
        .rf-group__header { display: flex; align-items: center; gap: 8px; padding: 16px 18px; }
        .rf-group__title { font-size: 13px; font-weight: 700; color: #e7e7e9; letter-spacing: .01em; }
        .rf-group__count { margin-left: auto; font-size: 12px; color: #6b6b70; }
        .rf-card {
            width: 280px;
            border: 1px solid #232327; border-radius: 14px;
            background: #161619; color: #e7e7e9;
            padding: 14px 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,.25);
            transition: border-color .12s ease, box-shadow .12s ease, transform .12s ease;
            cursor: pointer;
        }
        .rf-card:hover { border-color: #3a3a40; box-shadow: 0 14px 38px rgba(0,0,0,.4); }
        .rf-handle { width: 7px; height: 7px; min-width: 0; min-height: 0; background: #52525b; border: none; opacity: 0; transition: opacity .12s ease; }
        .rf-card:hover .rf-handle { opacity: .6; }
        .react-flow__node.selected .rf-card { border-color: #6b16ed; box-shadow: 0 0 0 1px #6b16ed, 0 14px 38px rgba(0,0,0,.45); }
        .rf-card__head { display: flex; align-items: center; gap: 10px; }
        .rf-card__icon { width: 22px; height: 22px; border-radius: 6px; flex: 0 0 auto; object-fit: contain; background: #0e0e10; padding: 2px; }
        .rf-card__name { font-size: 15px; font-weight: 700; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .rf-card__menu { margin-left: auto; color: #5a5a60; font-size: 18px; line-height: 1; }
        .rf-card__sub { margin-top: 4px; font-size: 12px; color: #8a8a90; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .rf-card__status { margin-top: 12px; display: flex; align-items: center; gap: 7px; font-size: 12px; }
        .rf-card__dot { width: 8px; height: 8px; border-radius: 999px; flex: 0 0 auto; }
        .rf-card__volume { margin-top: 12px; padding-top: 10px; border-top: 1px solid #232327; display: flex; align-items: center; gap: 7px; font-size: 12px; color: #6b6b70; }
        .rf-toolbar { display: flex; gap: 8px; }
        .rf-btn {
            display: inline-flex; align-items: center; gap: 6px;
            height: 32px; padding: 0 12px;
            font-size: 13px; font-weight: 600; color: #e7e7e9;
            background: #1b1b1f; border: 1px solid #2c2c31; border-radius: 8px;
            cursor: pointer; text-decoration: none;
            transition: background .12s ease, border-color .12s ease;
        }
        .rf-btn:hover { background: #232328; border-color: #3a3a40; }
        .rf-btn--accent { background: #6b16ed; border-color: #7c2bf5; color: #fff; }
        .rf-btn--accent:hover { background: #7c2bf5; }
        .rf-canvas .react-flow__controls { box-shadow: none; }
        .rf-canvas .react-flow__controls-button { background: #1b1b1f; border-color: #2c2c31; color: #e7e7e9; }
        .rf-canvas .react-flow__controls-button:hover { background: #232328; }
        .rf-canvas .react-flow__controls-button svg { fill: #e7e7e9; }
    `;
    document.head.appendChild(style);
}

function ServerIcon() {
    return (
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8a8a90" strokeWidth="2"
            strokeLinecap="round" strokeLinejoin="round">
            <rect x="2" y="3" width="20" height="6" rx="1.5" />
            <rect x="2" y="15" width="20" height="6" rx="1.5" />
            <path d="M6 6h.01M6 18h.01" />
        </svg>
    );
}

function VolumeIcon() {
    return (
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"
            strokeLinecap="round" strokeLinejoin="round">
            <ellipse cx="12" cy="5" rx="9" ry="3" />
            <path d="M3 5v14a9 3 0 0 0 18 0V5" />
        </svg>
    );
}

function GroupNode({ data }) {
    return (
        <div className="rf-group">
            <div className="rf-group__header">
                <ServerIcon />
                <span className="rf-group__title">{data.label}</span>
                <span className="rf-group__count">{data.resourceCount}</span>
            </div>
        </div>
    );
}

function ResourceNode({ data }) {
    return (
        <div className="rf-card">
            <Handle type="target" position={Position.Left} id="in" className="rf-handle" />
            <Handle type="source" position={Position.Right} id="out" className="rf-handle" />
            <div className="rf-card__head">
                {data.icon ? (
                    <img
                        className="rf-card__icon"
                        src={data.icon}
                        alt=""
                        onError={(event) => {
                            event.currentTarget.style.display = 'none';
                        }}
                    />
                ) : null}
                <span className="rf-card__name" title={data.label}>{data.label}</span>
                <span className="rf-card__menu">⋮</span>
            </div>
            {data.subtitle ? <div className="rf-card__sub" title={data.subtitle}>{data.subtitle}</div> : null}
            <div className="rf-card__status" style={{ color: data.statusColor }}>
                <span className="rf-card__dot" style={{ background: data.statusColor }} />
                <span>{data.statusLabel}</span>
            </div>
            {data.volumes && data.volumes.length ? (
                <div className="rf-card__volume">
                    <VolumeIcon />
                    <span>{data.volumes[0]}{data.volumes.length > 1 ? ` +${data.volumes.length - 1}` : ''}</span>
                </div>
            ) : null}
        </div>
    );
}

const nodeTypes = {
    group: GroupNode,
    resource: ResourceNode,
};

function navigateTo(href) {
    if (!href) {
        return;
    }
    if (window.Livewire && typeof window.Livewire.navigate === 'function') {
        window.Livewire.navigate(href);
        return;
    }
    window.location.assign(href);
}

function ResourceFlowCanvas({ flow, meta }) {
    const nodes = useMemo(() => flow.nodes || [], [flow]);
    const edges = useMemo(() => (flow.edges || []).map((edge) => ({
        type: 'smoothstep',
        animated: false,
        ...edge,
        markerEnd: { type: MarkerType.ArrowClosed, color: '#52525b', width: 18, height: 18 },
        style: { stroke: '#52525b', strokeWidth: 1.5 },
    })), [flow]);
    const total = flow.summary?.total || 0;

    const onNodeClick = useCallback((_event, node) => {
        if (node?.type === 'resource' && node.data) {
            window.dispatchEvent(new CustomEvent('resource-flow:open', { detail: node.data }));
        }
    }, []);

    const onSync = useCallback(() => {
        window.dispatchEvent(new CustomEvent('resource-flow:sync'));
    }, []);

    return (
        <ReactFlow
            className="rf-canvas"
            nodes={nodes}
            edges={edges}
            nodeTypes={nodeTypes}
            onNodeClick={onNodeClick}
            fitView
            fitViewOptions={{ padding: 0.25, maxZoom: 1 }}
            minZoom={0.2}
            maxZoom={1.6}
            proOptions={{ hideAttribution: true }}
            nodesDraggable
            nodesConnectable={false}
            elementsSelectable
            deleteKeyCode={null}
        >
            <Background variant={BackgroundVariant.Dots} gap={22} size={1.4} color="#26262b" />
            <Controls position="bottom-left" showInteractive={false} />
            <Panel position="top-right">
                <div className="rf-toolbar">
                    <button type="button" className="rf-btn" onClick={onSync} title="Refresh statuses">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M21 12a9 9 0 1 1-2.64-6.36" />
                            <path d="M21 3v6h-6" />
                        </svg>
                        Sync
                    </button>
                    {meta?.addUrl ? (
                        <a className="rf-btn rf-btn--accent" href={meta.addUrl} onClick={(event) => {
                            event.preventDefault();
                            navigateTo(meta.addUrl);
                        }}>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                            Add
                        </a>
                    ) : null}
                </div>
            </Panel>
            {total === 0 ? (
                <Panel position="top-center">
                    <div style={{
                        marginTop: 120,
                        border: '1px dashed #2c2c31',
                        borderRadius: 16,
                        padding: '20px 26px',
                        color: '#8a8a90',
                        background: 'rgba(16,16,18,.7)',
                        textAlign: 'center',
                    }}>
                        No resources yet. Click <strong style={{ color: '#cfcfd4' }}>Add</strong> to start building your canvas.
                    </div>
                </Panel>
            ) : null}
        </ReactFlow>
    );
}

export function mountResourceFlows() {
    injectStyles();

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

        const meta = {
            addUrl: container.getAttribute('data-add-url') || '',
            project: container.getAttribute('data-project') || '',
            environment: container.getAttribute('data-environment') || '',
        };

        if (!roots.has(container)) {
            roots.set(container, createRoot(container));
        }

        roots.get(container).render(<ResourceFlowCanvas flow={flow} meta={meta} />);
    });
}

window.mountResourceFlows = mountResourceFlows;
