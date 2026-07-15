import './widget.css';
import { initCountLive } from './count-live';
import { initWidgets } from './widget';

function autoInit(): void {
  initCountLive(document);
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
