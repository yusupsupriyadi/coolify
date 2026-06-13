import { initializeTerminalComponent } from './terminal.js';

let resourceFlowModule;

function initializeResourceFlowCanvases() {
    if (!document.querySelector('[data-resource-flow-canvas]')) {
        return;
    }

    resourceFlowModule ??= import('./resource-flow.jsx');
    resourceFlowModule.then(({ mountResourceFlows }) => mountResourceFlows());
}

document.addEventListener('DOMContentLoaded', initializeResourceFlowCanvases);
document.addEventListener('livewire:navigated', initializeResourceFlowCanvases);

// Livewire 3.5.19+ re-applies `x-cloak` to morphed elements during wire:navigate
// (via replaceHtmlAttributes). With `[x-cloak]{display:none}` on the app wrapper,
// this blanks the whole page on every navigation until Alpine re-processes it.
// Strip leftover x-cloak after each navigation; the initial-load FOUC guard stays.
document.addEventListener('livewire:navigated', () => {
    document.querySelectorAll('[x-cloak]').forEach((el) => el.removeAttribute('x-cloak'));
});

['livewire:navigated', 'alpine:init'].forEach((event) => {
    document.addEventListener(event, () => {
        // tree-shaking
        if (document.getElementById('terminal-container')) {
            initializeTerminalComponent()
        }
    });
});
