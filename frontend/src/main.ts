import './widget.css';
import { initWidgets } from './widget';

declare global {
  interface Window {
    Reactions?: {
      init: typeof initWidgets;
    };
  }
}

function autoInit(): void {
  initWidgets(document);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', autoInit);
} else {
  autoInit();
}

window.Reactions = { init: initWidgets };

export { initWidgets, mountWidget } from './widget';
export type { WidgetConfig, CountsData, ReactionData } from './types';
