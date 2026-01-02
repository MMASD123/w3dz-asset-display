=== 3D Asset Display for WooCommerce ===
Contributors: littlerong
Tags: woocommerce, 3d, model-viewer, glb, augmented-reality
Requires at least: 6.3
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display interactive 3D models and 360° product images in WooCommerce. Supports AR (Augmented Reality) on mobile devices.

== Description ==

Transform your WooCommerce store with stunning 3D product displays. **3D Asset Display** lets you showcase GLB/GLTF models with smooth rotation, zoom, and native AR support—no coding required.

= Perfect For =

* **Digital Asset Sellers** - Sell game characters, 3D models, and printable files with confidence
* **eCommerce Stores** - Showcase furniture, jewelry, electronics, and fashion in 360°
* **Creative Professionals** - Display portfolios with interactive 3D presentations

= Key Features =

* **GLB/GLTF Model Support** - Upload 3D models directly to WooCommerce products
* **Visual Camera Editor** - Drag your 3D preview to set the perfect starting angle (no more guessing coordinates!)
* **Native AR (Augmented Reality)** - "View in Your Space" button for iOS Quick Look and Android Scene Viewer
* **360° Image Rotation** - Upload image sequences for pseudo-3D product spins
* **Auto-Rotation** - Models spin automatically to catch attention
* **Touch-Optimized** - Smooth pinch-to-zoom and drag rotation on mobile
* **Lazy Loading** - Show poster images first, load 3D models on demand for fast page speeds
* **Lightweight** - Built on Google's `<model-viewer>` web component (industry standard)
* **Developer Friendly** - Hooks and filters for custom integrations

= Technical Specs =

* **Supported Formats:** .glb (recommended), .gltf
* **AR Formats:** .usdz for iOS AR Quick Look
* **3D Engine:** Google `<model-viewer>` v3.3.0 (bundled, Apache 2.0 License)
* **AR Compatibility:** iOS 12+ (ARKit), Android 8+ (ARCore)
* **Browser Support:** Chrome, Safari, Firefox, Edge (latest versions)

= Developer Hooks =

* `w3dz_after_camera_settings` - Add custom fields after camera editor
* `w3dz_render_pro_features` - Insert custom sections in product editor
* `w3dz_save_pro_features` - Hook into product save process
* `w3dz_viewer_data` - Filter viewer data passed to JavaScript
* `w3dz_camera_orbit` - Modify camera orbit settings

== Installation ==

= Automatic Installation =

1. Go to **Plugins > Add New** in WordPress admin
2. Search for "3D Asset Display for WooCommerce"
3. Click **Install Now**, then **Activate**
4. Done! No configuration needed

= Manual Installation =

1. Download the plugin ZIP file
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Activate the plugin

= First Steps =

1. Edit any WooCommerce product
2. Go to the **"3D & 360 View"** tab in Product Data
3. Select **"True 3D Model"** as the model type
4. Upload a `.glb` file (your 3D model)
5. Use the **Visual Camera Editor** to drag and set the perfect starting angle
6. Click **"Save Current View"** to lock in the angle
7. Add a poster image (optional, but recommended for faster loading)
8. Click **Update** and view your product page
9. Test AR on mobile by tapping **"View in Your Space"**

== Frequently Asked Questions ==

= What file formats are supported? =

The plugin supports `.glb` and `.gltf` files for 3D models. We strongly recommend using `.glb` (binary format) for smaller file sizes and better compatibility.

For AR on iOS devices, you can also upload a `.usdz` file (optional) for native Quick Look support.

= Does this work with AR (Augmented Reality)? =

Yes! On compatible mobile devices, users will see a "View in Your Space" button that launches native AR:
* **iOS:** Quick Look (iPhone/iPad with iOS 12+, requires .usdz file)
* **Android:** Scene Viewer (devices with ARCore support, uses .glb file)

**Note:** AR requires HTTPS and compatible hardware.

= What is the Visual Camera Editor? =

The Visual Camera Editor is an interactive 3D preview in the WordPress admin. Instead of entering cryptic camera coordinates, you simply:

1. Drag the 3D model to find the perfect starting angle
2. Zoom in/out to set the ideal distance
3. Click "Save Current View" to lock it in

Your customers will see the model from this exact angle when they first load your product page.

= Can I use 360° images instead of 3D models? =

Absolutely! If you don't have a 3D model, upload a sequence of product images (e.g., 36 images taken from different angles) and the plugin will create a smooth pseudo-3D rotation effect.

= Will this slow down my website? =

No. The plugin uses lazy loading by default—only the poster image loads initially. The 3D model loads when users interact with it (click, hover, or scroll into view). Built on lightweight Google technology.

= Does this work with my theme? =

Yes! The plugin uses WooCommerce standard hooks and works with any properly coded theme. Tested with Storefront, Astra, OceanWP, Flatsome, and Divi.

= How do I optimize my 3D models? =

Tips for best performance:
* Keep models under 10MB (ideally 2-5MB for web use)
* Use `.glb` format instead of `.gltf` (smaller file size)
* Compress textures to 1024x1024 or 2048x2048 pixels
* Reduce polygon count (use decimation in Blender)
* Tools: [Blender](https://www.blender.org/), [gltf-transform](https://gltf-transform.donmccurdy.com/), [gltf.report](https://gltf.report/)

= Can I customize the viewer appearance? =

The plugin provides clean default styling that matches most themes. For advanced customization, you can use custom CSS in your theme or child theme.

= Where can I get 3D models? =

* **Free Resources:** Sketchfab (many free models), Poly Haven, TurboSquid Free section
* **Premium Marketplaces:** CGTrader, TurboSquid, Sketchfab Store
* **Create Your Own:** Blender (free software), Autodesk Maya, 3ds Max
* **Hire a 3D Artist:** Fiverr, Upwork, or specialized 3D studios

= Does this support animations? =

Yes! The plugin supports GLB files with embedded animations. By default, animations are controlled by user interaction. For auto-play functionality, you can add custom JavaScript using the model-viewer API. The plugin provides hooks for developers to extend animation behavior.

= Can I sell this plugin or modify it? =

Yes! This plugin is licensed under GPLv2 or later, which means you can use, modify, and redistribute it freely. See the [GPL license](https://www.gnu.org/licenses/gpl-2.0.html) for details.

= What happens if I hover over products on shop pages? =

The plugin can display 3D models or 360° image previews when hovering over products on shop/category pages. This feature can be enabled or disabled in **WooCommerce > 3D & AR** settings.

= How do I configure auto-rotation speed? =

Go to **WooCommerce > 3D & AR** settings and choose from three rotation speeds:
* Slow (20deg/sec) - Elegant, professional
* Medium (30deg/sec) - Recommended default
* Fast (45deg/sec) - Dynamic, attention-grabbing

= Can I set different camera angles for each product? =

Yes! Each product can have its own custom camera angle using the Visual Camera Editor. If not set, the product will use the global default angle from **WooCommerce > 3D & AR** settings.

== Screenshots ==

1. **Front-End Display** - Interactive 3D model viewer on product page with auto-rotation and zoom
2. **Visual Camera Editor** - Drag the 3D preview in WordPress admin to set the perfect starting angle
3. **Mobile AR Experience** - "View in Your Space" button launches native AR on iOS/Android
4. **Product Editor UI** - Simple upload interface in the "3D & 360 View" product tab
5. **360° Image Mode** - Alternative display using image sequences for pseudo-3D rotation

== Changelog ==

= 1.0.0 - 2025-01-03 =
* 🎉 Initial release
* ✅ GLB/GLTF 3D model upload support
* ✅ Visual Camera Editor - drag to set perfect starting angles
* ✅ Native AR support (iOS Quick Look with .usdz, Android Scene Viewer)
* ✅ 360° image sequence rotation with auto-play
* ✅ Auto-rotation and touch controls
* ✅ Lazy loading for performance optimization
* ✅ Poster image support for faster initial load
* ✅ Product list page hover preview (3D models and 360° images)
* ✅ Comprehensive settings page with customization options
* ✅ Developer hooks for extensibility
* ✅ WooCommerce HPOS (High-Performance Order Storage) compatibility

== Upgrade Notice ==

= 1.0.0 =
First stable release. Welcome to interactive 3D product displays with visual camera controls!

== Privacy & External Services ==

This plugin includes Google's `<model-viewer>` web component library (v3.3.0) as a bundled local file (`assets/js/model-viewer.min.js`). The library is licensed under Apache 2.0. No external CDN connections are made during normal operation.

For AR functionality, the plugin may trigger native device features:
* **iOS Quick Look** - Governed by Apple's privacy policy
* **Android Scene Viewer** - Governed by Google's privacy policy

All 3D model rendering happens locally in the user's browser. No user data, product data, or model files are transmitted to external services.

== Support ==

* **Community Support:** [WordPress.org Forum](https://wordpress.org/support/plugin/w3dz-asset-display/)
* **Bug Reports:** Please use the WordPress.org support forum with detailed information
* **Documentation:** See the FAQ section and plugin description for usage guides

== Credits ==

Developed by **Zyne** (littlerong).

**Third-Party Libraries:**
* Google `<model-viewer>` v3.3.0 (Apache 2.0 License) - 3D rendering engine
* Bundled locally, no external API calls

**Special Thanks:**
* Google Model Viewer team for the excellent web component
* WordPress and WooCommerce communities for support and inspiration
* All early testers and contributors

== Additional Notes ==

= Performance Optimization =

The plugin is designed for optimal performance:
* Lazy loading of 3D models (loads only when needed)
* Efficient memory management
* Configurable concurrent loading limits for product list pages
* Poster images for instant visual feedback

= Browser Compatibility =

Tested and working on:
* Chrome 90+ (Desktop & Mobile)
* Safari 14+ (Desktop & Mobile)
* Firefox 88+ (Desktop & Mobile)
* Edge 90+ (Desktop & Mobile)

= Mobile Experience =

Fully optimized for mobile devices:
* Touch-friendly controls (pinch, drag, rotate)
* Responsive modal/lightbox viewer
* Native AR support for compatible devices
* Optimized asset loading for cellular connections

= Developer Resources =

Available hooks and filters for customization:
* `w3dz_after_camera_settings` - Add custom fields in admin
* `w3dz_render_pro_features` - Insert custom UI sections
* `w3dz_save_pro_features` - Hook into save process
* `w3dz_viewer_data` - Modify viewer JavaScript data
* `w3dz_camera_orbit` - Filter camera settings
* `w3dz_after_enqueue_frontend_assets` - Add custom scripts
* `w3dz_before_viewer_container` - Inject content above viewer
* `w3dz_after_viewer_container` - Inject content below viewer

Documentation for developers available in the plugin source code.