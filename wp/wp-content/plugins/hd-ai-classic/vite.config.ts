/**
 * HD AI Classic Plugin Vite Configuration
 */

import { defineConfig } from 'vite';
import { getSharedConfig } from '../../../../tools/vite.config.shared';

const jsFiles = ['editor-ai', 'admin'];

export default defineConfig(
	getSharedConfig({
		basePath: __dirname,
		input: {
			js: jsFiles,
		},
	}),
);
