// The only Alpine bootstrap. Register new modules in ui/register.js; never call
// Alpine.start() anywhere else. The guest-facing stage scripts below are plain
// ES modules that attach themselves to their own markup.
import Alpine from 'alpinejs';

import { registerUi } from './ui/register.js';

import './sound';
import './barista';
import './reservation';
import './stage';
import './narrator';

registerUi(Alpine);

window.Alpine = Alpine;

Alpine.start();
