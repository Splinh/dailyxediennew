/**
 * SPL Theme Vite Configuration
 */

import { defineConfig } from 'vite';
import { getSharedConfig } from '../../../../tools/vite.config.shared';

// Entry points
const jsFiles = ['preflight', 'index', 'woocommerce'];
const scssFiles = ['editor-style', 'page', 'share', 'woocommerce'];

// Chunk directories to scan (relative to scripts/core/)
const chunkDirs = ['fx', 'modules'];

export default defineConfig(
	getSharedConfig({
		basePath: __dirname,
		input: {
			js: jsFiles,
			scss: scssFiles,
		},
		chunkDirs,
	}),
);
