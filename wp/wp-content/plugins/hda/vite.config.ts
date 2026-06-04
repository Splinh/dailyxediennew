/**
 * HD Toolkit Plugin Vite Configuration (Simplified with wpVite plugin)
 */

import { defineConfig } from 'vite';
import { getSharedConfig } from '../../../../tools/vite.config.shared';

// Entry points — SCSS imported from JS (Vite auto-extracts CSS)
const jsFiles = ['settings', 'admin-core', 'login', 'sorting', 'profile', 'converter'];

export default defineConfig(
	getSharedConfig({
		basePath: __dirname,
		input: {
			js: jsFiles,
		},
	}),
);
