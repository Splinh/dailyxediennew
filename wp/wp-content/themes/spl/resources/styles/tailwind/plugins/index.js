/* tailwind/plugins/index.js - Plugin entry point */

import { composeHandlers } from './_compose.js';
import presets from './clamp-presets.js';
import fluidTypeFactory from './clamp.js';

// Fluid typography: p-clamp-[min,max] or p-clamp-h1
const fluidType = fluidTypeFactory({
	root: 16,
	defaults: { minw: 380, maxw: 1920, base: 0 },
	presets,
});

export default composeHandlers(fluidType);
