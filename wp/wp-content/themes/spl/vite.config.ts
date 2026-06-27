/**
 * SPL Theme Vite Configuration
 */

import { defineConfig } from 'vite';
import { getSharedConfig } from '../../../../tools/vite.config.shared';

// Entry points
const jsFiles = ['preflight', 'index', 'woocommerce', 'dxd', 'home'];
const scssFiles = ['editor-style', 'page', 'share', 'woocommerce', 'commerce'];

// Chunk directories to scan (relative to scripts/core/)
const chunkDirs = ['fx', 'modules'];

export default defineConfig({
	...getSharedConfig({
		basePath: __dirname,
		input: {
			js: jsFiles,
			scss: scssFiles,
		},
		chunkDirs,
	}),
	base: '/wp-content/themes/spl/assets/',

	// Static assets (fonts) — copied to outDir on build, survives emptyOutDir.
	publicDir: 'static',
});
