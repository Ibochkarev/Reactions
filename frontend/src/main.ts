import './widget.css';
import { initWidgets } from './widget';

function autoInit(): void {
  initWidgets(document);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', autoInit);
} else {
  autoInit();
}

window.Reactions = {
  init: initWidgets,
  config: window.Reactions?.config,
};

export { initWidgets, mountWidget } from './widget';
export type { WidgetConfig, CountsData, ReactionData, ReactionsGlobalConfig } from './types';
