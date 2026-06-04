/**
 * HD Theme Vite Configuration (Simplified with wpVite plugin)
 */

import { defineConfig } from 'vite';
import { getSharedConfig } from '../../../../tools/vite.config.shared';

// Entry points
const jsFiles = ['preflight', 'admin', 'index', 'starter' /* STARTER */, 'woocommerce'];
const scssFiles = ['editor-style', 'page', 'share', 'starter' /* STARTER */, 'woocommerce'];

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
