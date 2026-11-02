

import Alpine from 'alpinejs';
import { registerWorkspace } from './modules/workspace';
import { registerAssistant } from './modules/assistant';
import { registerResponsiveLists } from './modules/responsive-lists';
import { registerAuthScene } from './modules/auth-scene';
import { registerAppearance } from './modules/appearance';

window.Alpine = Alpine;
registerAppearance(Alpine);
registerWorkspace(Alpine);
registerAssistant(Alpine);
registerAuthScene(Alpine);

Alpine.start();
registerResponsiveLists();
