// modules/index.js
// Core Modules assets loader
// Each module group maintains its own config.js — add new groups by importing + spreading.

import { createLoader } from '../createLoader.js';

import wcConfig from './woocommerce/config.js';
import formConfig from './form/config.js';

const config = {
	...wcConfig,
	...formConfig,
};

export default createLoader(config, 'Modules');
