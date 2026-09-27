/**
 * Aurelia 3D viewer (bundled with three.js by tools/build-js.mjs).
 * Ported from the reference JewelViewer: PBR metal band, refractive
 * brilliant-cut stone, drag to rotate, gold dust for the hero.
 */
import {
	WebGLRenderer, Scene, PMREMGenerator, PerspectiveCamera, DirectionalLight, AmbientLight, Group,
	MeshPhysicalMaterial, Color, Mesh, TorusGeometry, CylinderGeometry, LatheGeometry, SphereGeometry,
	TorusKnotGeometry, Vector2, BufferGeometry, BufferAttribute, PointsMaterial, Points, AdditiveBlending,
	ACESFilmicToneMapping, SRGBColorSpace, Clock, Box3, Vector3,
} from 'three';
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment.js';

function buildRing( jewel, metal, gem, disposables ) {
	const metalMat = new MeshPhysicalMaterial( {
		color: new Color( metal ), metalness: 1, roughness: 0.16, clearcoat: 1, clearcoatRoughness: 0.08, envMapIntensity: 1.6,
	} );
	const band = new Mesh( new TorusGeometry( 1, 0.11, 64, 220 ), metalMat );
	band.scale.set( 1, 1, 1.6 );
	jewel.add( band );
	disposables.push( band.geometry, metalMat );

	if ( gem ) {
		const prongGeo = new CylinderGeometry( 0.025, 0.035, 0.42, 12 );
		disposables.push( prongGeo );
		for ( let i = 0; i < 4; i++ ) {
			const a = ( i / 4 ) * Math.PI * 2 + Math.PI / 4;
			const prong = new Mesh( prongGeo, metalMat );
			prong.position.set( Math.cos( a ) * 0.2, 1.24, Math.sin( a ) * 0.2 );
			prong.rotation.z = -Math.cos( a ) * 0.35;
			prong.rotation.x = Math.sin( a ) * 0.35;
			jewel.add( prong );
		}
		const basket = new Mesh( new TorusGeometry( 0.2, 0.025, 12, 48 ), metalMat );
		basket.rotation.x = Math.PI / 2;
		basket.position.y = 1.12;
		jewel.add( basket );
		disposables.push( basket.geometry );

		const profile = [ new Vector2( 0, -0.36 ), new Vector2( 0.4, 0 ), new Vector2( 0.4, 0.035 ), new Vector2( 0.24, 0.2 ), new Vector2( 0, 0.2 ) ];
		const gemGeo = new LatheGeometry( profile, 16 ).toNonIndexed();
		gemGeo.computeVertexNormals();
		const lower = String( gem ).toLowerCase();
		const opaque = lower === '#1b1b1b' || lower === '#f7efe6';
		const gemMat = new MeshPhysicalMaterial( {
			color: new Color( gem ), metalness: 0, roughness: opaque ? 0.25 : 0, transmission: opaque ? 0 : 1, thickness: 0.8,
			ior: 2.2, dispersion: 4, iridescence: opaque ? 0.4 : 0.15, envMapIntensity: 3.5, flatShading: ! opaque, clearcoat: 1,
		} );
		const stone = new Mesh( opaque ? new SphereGeometry( 0.3, 48, 48 ) : gemGeo, gemMat );
		stone.position.y = 1.42;
		jewel.add( stone );
		disposables.push( gemGeo, gemMat, stone.geometry );
	} else {
		const band2 = new Mesh( new TorusKnotGeometry( 0.95, 0.045, 400, 12, 1, 24 ), metalMat );
		band2.scale.set( 1, 1, 1.4 );
		band2.position.y = -0.02;
		jewel.add( band2 );
		disposables.push( band2.geometry );
	}
}

async function buildModel( jewel, url, disposables ) {
	const { GLTFLoader } = await import( 'three/examples/jsm/loaders/GLTFLoader.js' );
	const gltf = await new GLTFLoader().loadAsync( url );
	const root = gltf.scene;
	const box = new Box3().setFromObject( root );
	const size = box.getSize( new Vector3() );
	const center = box.getCenter( new Vector3() );
	const scale = 2.4 / Math.max( size.x, size.y, size.z, 0.0001 );
	root.position.sub( center ).multiplyScalar( scale );
	root.scale.setScalar( scale );
	jewel.add( root );
	root.traverse( ( o ) => {
		if ( o.isMesh ) {
			disposables.push( o.geometry );
			( Array.isArray( o.material ) ? o.material : [ o.material ] ).forEach( ( m ) => disposables.push( m ) );
		}
	} );
}

/**
 * Mount a viewer into an element.
 *
 * @param {HTMLElement} el   Container (sized by CSS).
 * @param {Object}      opts { mode: 'ring'|'model', metal, gem, model, variant: 'hero'|'product', label }
 * @return {Promise<Function>} Unmount function.
 */
export async function mount( el, opts = {} ) {
	const variant = opts.variant || 'product';
	const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	const renderer = new WebGLRenderer( { antialias: true, alpha: true, powerPreference: 'high-performance' } );
	renderer.setPixelRatio( Math.min( window.devicePixelRatio, 2 ) );
	renderer.toneMapping = ACESFilmicToneMapping;
	renderer.toneMappingExposure = 1.15;
	renderer.outputColorSpace = SRGBColorSpace;
	renderer.domElement.setAttribute( 'role', 'img' );
	renderer.domElement.setAttribute( 'aria-label', opts.label || '3D model' );
	renderer.domElement.style.touchAction = 'pan-y';
	renderer.domElement.style.cursor = 'grab';
	el.appendChild( renderer.domElement );

	const scene = new Scene();
	const pmrem = new PMREMGenerator( renderer );
	const envTex = pmrem.fromScene( new RoomEnvironment(), 0.04 ).texture;
	scene.environment = envTex;

	const camera = new PerspectiveCamera( 35, 1, 0.1, 100 );
	camera.position.set( 0, 0.6, variant === 'hero' ? 6.6 : 5.4 );
	camera.lookAt( 0, 0.15, 0 );
	const key = new DirectionalLight( 0xffffff, 2.2 );
	key.position.set( 3, 4, 5 );
	const rim = new DirectionalLight( 0xffe2b0, 1.4 );
	rim.position.set( -4, 2, -3 );
	scene.add( key, rim, new AmbientLight( 0xffffff, 0.25 ) );

	const jewel = new Group();
	scene.add( jewel );
	const disposables = [ envTex, pmrem ];
	const isModel = opts.mode === 'model' && opts.model;
	if ( isModel ) {
		await buildModel( jewel, opts.model, disposables );
	} else {
		buildRing( jewel, opts.metal || '#e6c068', opts.gem === null ? null : opts.gem || '#dff3ff', disposables );
		jewel.rotation.x = -0.25;
	}
	const baseY = isModel ? 0 : opts.gem === null ? 0.1 : -0.35;
	jewel.position.y = baseY;

	let dust = null;
	if ( variant === 'hero' ) {
		const n = 380;
		const pos = new Float32Array( n * 3 );
		for ( let i = 0; i < n; i++ ) {
			pos[ i * 3 ] = ( Math.random() - 0.5 ) * 9;
			pos[ i * 3 + 1 ] = ( Math.random() - 0.5 ) * 6;
			pos[ i * 3 + 2 ] = ( Math.random() - 0.5 ) * 5 - 1;
		}
		const g = new BufferGeometry();
		g.setAttribute( 'position', new BufferAttribute( pos, 3 ) );
		const m = new PointsMaterial( { color: 0xf3d58a, size: 0.025, transparent: true, opacity: 0.85, depthWrite: false, blending: AdditiveBlending } );
		dust = new Points( g, m );
		scene.add( dust );
		disposables.push( g, m );
	}

	let dragging = false;
	let lastX = 0;
	let lastY = 0;
	let velY = 0;
	let tiltX = 0;
	const target = { x: 0, y: 0 };
	const canvas = renderer.domElement;
	const onDown = ( e ) => {
		dragging = true;
		lastX = e.clientX;
		lastY = e.clientY;
		canvas.setPointerCapture( e.pointerId );
		canvas.style.cursor = 'grabbing';
	};
	const onMove = ( e ) => {
		const r = canvas.getBoundingClientRect();
		target.x = ( ( e.clientX - r.left ) / r.width - 0.5 ) * 2;
		target.y = ( ( e.clientY - r.top ) / r.height - 0.5 ) * 2;
		if ( ! dragging ) {
			return;
		}
		velY = ( e.clientX - lastX ) * 0.01;
		tiltX = Math.max( -0.8, Math.min( 0.8, tiltX + ( e.clientY - lastY ) * 0.005 ) );
		jewel.rotation.y += velY;
		lastX = e.clientX;
		lastY = e.clientY;
	};
	const onUp = () => {
		dragging = false;
		canvas.style.cursor = 'grab';
	};
	const onKey = ( e ) => {
		if ( e.key === 'ArrowLeft' ) {
			jewel.rotation.y -= 0.2;
		} else if ( e.key === 'ArrowRight' ) {
			jewel.rotation.y += 0.2;
		}
	};
	canvas.tabIndex = 0;
	canvas.addEventListener( 'pointerdown', onDown );
	canvas.addEventListener( 'pointermove', onMove );
	canvas.addEventListener( 'pointerup', onUp );
	canvas.addEventListener( 'pointerleave', onUp );
	canvas.addEventListener( 'keydown', onKey );

	const resize = () => {
		const { width, height } = el.getBoundingClientRect();
		if ( ! width || ! height ) {
			return;
		}
		renderer.setSize( width, height, false );
		camera.aspect = width / height;
		camera.updateProjectionMatrix();
	};
	const ro = new ResizeObserver( resize );
	ro.observe( el );
	resize();

	let visible = true;
	const io = new IntersectionObserver( ( [ entry ] ) => ( visible = entry.isIntersecting ) );
	io.observe( el );

	const clock = new Clock();
	let raf = 0;
	const loop = () => {
		raf = requestAnimationFrame( loop );
		if ( ! visible || document.hidden ) {
			return;
		}
		const t = clock.getElapsedTime();
		if ( ! dragging ) {
			velY *= 0.94;
			jewel.rotation.y += reduced ? 0 : 0.006 + velY;
		}
		if ( ! isModel ) {
			jewel.rotation.x += ( -0.25 + tiltX + target.y * 0.12 - jewel.rotation.x ) * 0.05;
		} else {
			jewel.rotation.x += ( tiltX + target.y * 0.12 - jewel.rotation.x ) * 0.05;
		}
		jewel.rotation.z += ( target.x * -0.08 - jewel.rotation.z ) * 0.05;
		if ( ! reduced ) {
			jewel.position.y = baseY + Math.sin( t * 1.2 ) * 0.06;
		}
		if ( dust && ! reduced ) {
			dust.rotation.y = t * 0.02;
			dust.position.y = Math.sin( t * 0.3 ) * 0.1;
		}
		renderer.render( scene, camera );
	};
	loop();

	return () => {
		cancelAnimationFrame( raf );
		ro.disconnect();
		io.disconnect();
		canvas.removeEventListener( 'pointerdown', onDown );
		canvas.removeEventListener( 'pointermove', onMove );
		canvas.removeEventListener( 'pointerup', onUp );
		canvas.removeEventListener( 'pointerleave', onUp );
		canvas.removeEventListener( 'keydown', onKey );
		disposables.forEach( ( d ) => d && d.dispose && d.dispose() );
		renderer.dispose();
		canvas.remove();
	};
}
