<?php
/**
 * Plugin Name: 3D Asset Display for WooCommerce
 * Plugin URI: https://github.com/MMASD123/w3dz-asset-display
 * Description: Display 3D models (.glb/.gltf) and 360° image rotations for WooCommerce products with AR support
 * Version: 1.0.0
 * Author: Zyne
 * Author URI: https://github.com/MMASD123
 * Text Domain: w3dz-asset-display
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 8.5
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('W3DZ_VERSION', '1.0.0');
define('W3DZ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('W3DZ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('W3DZ_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Pro version constants - Allow Pro plugin to override
if (!defined('W3DZ_IS_PRO')) {
    define('W3DZ_IS_PRO', false); // Default to false, Pro plugin can override
}

/**
 * Main Plugin Class - Singleton Pattern
 */
class WooCommerce_3D_Asset_Display_Zyne {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Declare HPOS compatibility
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
        // Check if WooCommerce is active
        add_action('plugins_loaded', array($this, 'init'));
        
        // Allow uploading 3D model files
        add_filter('upload_mimes', array($this, 'allow_3d_file_types'));
        add_filter('wp_check_filetype_and_ext', array($this, 'fix_mime_type_glb'), 10, 4);
		
		// Enqueue model-viewer for admin camera editor
		add_action('admin_enqueue_scripts', array($this, 'enqueue_model_viewer_admin'), 5);
    }
    
    /**
     * Declare HPOS compatibility
     */
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                __FILE__,
                true
            );
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Include required files
        $this->include_files();
        
        // Initialize settings page (always load in admin)
        if (is_admin()) {
            W3DZ_Settings::get_instance();
        }
        
        // Initialize admin functionality
        if (is_admin()) {
            W3DZ_Admin::get_instance();
        }
        
        // Initialize frontend functionality
        if (!is_admin()) {
            W3DZ_Frontend::get_instance();
            W3DZ_Archive::get_instance();
        }
    }
    
    /**
     * Check if Pro plugin is active
     * More reliable than checking constants
     */
    private function is_pro_active() {
        return class_exists('WooCommerce_3D_Asset_Display_Zyne_Pro') || 
               class_exists('W3DZ_Pro_Core') ||
               isset($GLOBALS['w3dz_pro_active']);
    }
    
    /**
     * Include required files
     */
    private function include_files() {
        require_once W3DZ_PLUGIN_DIR . 'includes/class-w3dz-camera.php';
        require_once W3DZ_PLUGIN_DIR . 'includes/class-w3dz-admin.php';
        require_once W3DZ_PLUGIN_DIR . 'includes/class-w3dz-frontend.php';
        require_once W3DZ_PLUGIN_DIR . 'includes/class-w3dz-settings.php';
        require_once W3DZ_PLUGIN_DIR . 'includes/class-w3dz-archive.php';
        
        // Pro Teaser class is NOT loaded in free version
        // Hook available for Pro plugin: 'w3dz_after_camera_settings'
    }
	
	/**
	 * Enqueue model-viewer library for admin camera editor
	 */
	public function enqueue_model_viewer_admin() {
		// Only load on product edit pages
		$screen = get_current_screen();
		if (!$screen || $screen->post_type !== 'product') {
			return;
		}
		
		// Enqueue model-viewer library as ES6 module
		wp_enqueue_script(
			'model-viewer',
			W3DZ_PLUGIN_URL . 'assets/js/model-viewer.min.js',
			array(),
			'3.0.0',
			true
		);
		
		// CRITICAL: Add module type attribute
		add_filter('script_loader_tag', array($this, 'add_module_type_to_model_viewer'), 10, 3);
	}

	/**
	 * Add type="module" to model-viewer script tag
	 */
	public function add_module_type_to_model_viewer($tag, $handle, $src) {
		if ('model-viewer' === $handle) {
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Modifying existing enqueued script tag
			$tag = '<script type="module" src="' . esc_url($src) . '" id="model-viewer-js"></script>';
		}
		return $tag;
	}
    
    /**
     * Allow uploading .glb, .gltf, and .usdz files
     */
    public function allow_3d_file_types($mimes) {
        $mimes['glb'] = 'model/gltf-binary';
        $mimes['gltf'] = 'model/gltf+json';
        $mimes['usdz'] = 'model/vnd.usdz+zip';
        return $mimes;
    }
    
    /**
     * Fix MIME type detection for GLB files
     */
    public function fix_mime_type_glb($data, $file, $filename, $mimes) {
        $ext = isset($data['ext']) ? $data['ext'] : '';
        
        if (empty($ext)) {
            $exploded = explode('.', $filename);
            $ext = strtolower(end($exploded));
        }
        
        if ($ext === 'glb') {
            $data['ext'] = 'glb';
            $data['type'] = 'model/gltf-binary';
        } elseif ($ext === 'gltf') {
            $data['ext'] = 'gltf';
            $data['type'] = 'model/gltf+json';
        } elseif ($ext === 'usdz') {
            $data['ext'] = 'usdz';
            $data['type'] = 'model/vnd.usdz+zip';
        }
        
        return $data;
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php esc_html_e('3D Asset Display for WooCommerce requires WooCommerce to be installed and active.', 'w3dz-asset-display'); ?></p>
        </div>
        <?php
    }
}

/**
 * Initialize the plugin
 */
function w3dz_asset_display_init() {
    return WooCommerce_3D_Asset_Display_Zyne::get_instance();
}

// Start the plugin
w3dz_asset_display_init();