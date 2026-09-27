// Bundles the 3D viewer (three.js, code-split) and the QR library.
// Usage: npm run build:js
import { build } from 'esbuild';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { rmSync } from 'node:fs';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const assets = join( root, 'plugin/assets' );

rmSync( join( assets, 'js/viewer' ), { recursive: true, force: true } );
await build( {
	entryPoints: [ join( assets, 'src/viewer.js' ) ],
	bundle: true,
	splitting: true,
	format: 'esm',
	minify: true,
	target: 'es2020',
	outdir: join( assets, 'js/viewer' ),
	entryNames: '[name]',
	chunkNames: 'chunk-[hash]',
	legalComments: 'eof',
	logLevel: 'info',
} );

await build( {
	stdin: { contents: "import qrcode from 'qrcode-generator'; window.qrcode = qrcode;", resolveDir: root },
	bundle: true,
	format: 'iife',
	minify: true,
	target: 'es2018',
	outfile: join( assets, 'js/vendor/qrcode.min.js' ),
	legalComments: 'inline',
	banner: { js: '/*! qrcode-generator (c) Kazuhiko Arase, MIT License */' },
	logLevel: 'info',
} );
