

import Alpine from 'alpinejs';
import { registerWorkspace } from './modules/workspace';
import { registerAssistant } from './modules/assistant';
import { registerResponsiveLists } from './modules/responsive-lists';

window.Alpine = Alpine;
registerWorkspace(Alpine);
registerAssistant(Alpine);

Alpine.start();
registerResponsiveLists();
