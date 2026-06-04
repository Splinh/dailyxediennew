/**
 * HDAT Admin Vite Configuration.
 */

import { defineConfig } from 'vite';
import { getSharedConfig } from '../../../../tools/vite.config.shared';

const jsFiles = ['admin'];

export default defineConfig(
	getSharedConfig({
		basePath: __dirname,
		input: {
			js: jsFiles,
		},
	}),
);
