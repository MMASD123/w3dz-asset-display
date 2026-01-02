<?php
/**
 * Archive/List page functionality for WooCommerce 3D Asset Display Zyne
 * Handles 3D model and 360 image hover preview on product listing pages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class W3DZ_Archive {
    
    /**
     * Single instance
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
        // Check if archive hover is enabled in settings
        if (!W3DZ_Settings::get_option('archive_enable_hover', true)) {
            return; // Feature disabled, do nothing
        }
        
        // Hook into WordPress to check if we're on a product listing page
        add_action('wp', array($this, 'maybe_init'));
    }
    
    /**
     * Check if we're on a product listing page and initialize if needed
     */
    public function maybe_init() {
        // Only initialize on product listing pages
        if ($this->is_product_listing_page()) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
            add_filter('woocommerce_product_get_image', array($this, 'add_hover_container'), 10, 5);
        }
    }
    
    /**
     * Check if current page is a product listing page
     */
    private function is_product_listing_page() {
        return is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy();
    }
    
    /**
     * Enqueue archive page assets
     */
    public function enqueue_assets() {
        // Enqueue model-viewer (for 3D models)
        wp_enqueue_script(
            'w3dz-model-viewer',
            W3DZ_PLUGIN_URL . 'assets/js/model-viewer.min.js',
            array(),
            '3.3.0',
            array(
                'strategy'  => 'defer',
                'in_footer' => true
            )
        );
        
        // Add type="module" attribute
        add_filter('script_loader_tag', array($this, 'add_module_to_model_viewer'), 10, 3);
        
        // Enqueue archive CSS
        wp_enqueue_style(
            'w3dz-archive-style',
            W3DZ_PLUGIN_URL . 'assets/css/w3dz-archive.css',
            array(),
            W3DZ_VERSION
        );
        
        // Enqueue archive JS
        wp_enqueue_script(
            'w3dz-archive-script',
            W3DZ_PLUGIN_URL . 'assets/js/w3dz-archive.js',
            array('jquery', 'w3dz-model-viewer'),
            W3DZ_VERSION,
            true
        );
        
        // Pass settings to JavaScript
        wp_localize_script(
            'w3dz-archive-script',
            'w3dzArchiveSettings',
            array(
                // Progress bar settings
                'showProgress'      => W3DZ_Settings::get_option('archive_show_progress', true),
                'progressColor'     => W3DZ_Settings::get_option('archive_progress_color', '#0073aa'),
                'maxConcurrent'     => W3DZ_Settings::get_option('max_concurrent_loads', 1),
                
                // 3D model auto-rotation settings
                'enableAutoRotate'  => W3DZ_Settings::get_option('enable_auto_rotate', true) ? true : false,
                'rotationSpeed'     => W3DZ_Settings::get_option('rotation_speed', '30deg'),
                
                // 360 degree auto-play settings
                'enable360AutoplayHover' => W3DZ_Settings::get_option('360_autoplay_hover', false) ? true : false,
                'playbackSpeed'          => intval(W3DZ_Settings::get_option('360_playback_speed', '100')),
                'loopInfinite'           => W3DZ_Settings::get_option('360_loop_infinite', false) ? true : false,
                
                // Global default camera settings
                'defaultCameraOrbit' => W3DZ_Camera::format_camera_orbit(
                    W3DZ_Settings::get_option('default_camera_theta', 0),
                    W3DZ_Settings::get_option('default_camera_phi', 75),
                    W3DZ_Settings::get_option('default_camera_radius', 3)
                ),
                
                // Ajax URL
                'ajaxUrl'           => admin_url('admin-ajax.php')
            )
        );
    }
    
    /**
     * Add type="module" attribute to model-viewer script
     */
    public function add_module_to_model_viewer($tag, $handle, $src) {
        if ('w3dz-model-viewer' === $handle) {
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Modifying existing enqueued script tag
            $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
        }
        return $tag;
    }
    
    /**
     * Wrap product image with hover container
     * This filter is called for every product image in the loop
     * Supports both 3D models and 360 images
     */
    public function add_hover_container($image, $product, $size, $attr, $placeholder) {
        // Get product ID
        $product_id = $product->get_id();
        
        // Check model type
        $model_type = get_post_meta($product_id, '_w3dz_model_type', true);
        
        // If no 3D content, return original image unchanged
        if ($model_type !== '3d_model' && $model_type !== '360_images') {
            return $image;
        }
        
        // Get progress bar color from settings
        $progress_color = W3DZ_Settings::get_option('archive_progress_color', '#0073aa');
        
        // ============================================
        // Handle 3D Model Type
        // ============================================
        if ($model_type === '3d_model') {
            // Get GLB URL
            $glb_url = get_post_meta($product_id, '_w3dz_glb_url', true);
            
            // If no GLB file, return original image
            if (empty($glb_url)) {
                return $image;
            }
            
            // Get camera settings for this product
            $camera_settings = W3DZ_Camera::get_camera_settings($product_id);
            $camera_orbit = W3DZ_Camera::format_camera_orbit(
                $camera_settings['theta'],
                $camera_settings['phi'],
                $camera_settings['radius']
            );
            
            // Build wrapper HTML for 3D model
            $wrapper = '<div class="w3dz-product-image-wrapper" data-product-id="' . esc_attr($product_id) . '" data-model-type="3d_model" data-glb-url="' . esc_url($glb_url) . '" data-camera-orbit="' . esc_attr($camera_orbit) . '">';
            
            // Add original image
            $wrapper .= $image;
            
            // Add progress bar (hidden by default)
            if (W3DZ_Settings::get_option('archive_show_progress', true)) {
                $wrapper .= '<div class="w3dz-hover-progress" style="display:none;">';
                $wrapper .= '<div class="w3dz-progress-bar" style="background-color:' . esc_attr($progress_color) . ';"></div>';
                $wrapper .= '</div>';
            }
            
            // Add 3D viewer overlay (hidden by default)
            $wrapper .= '<div class="w3dz-viewer-overlay" style="display:none;"></div>';
            
            // Add hover hint badge
            $wrapper .= '<span class="w3dz-hover-hint">3D</span>';
            
            $wrapper .= '</div>';
            
            return $wrapper;
        }
        
        // ============================================
        // Handle 360 Images Type
        // ============================================
        if ($model_type === '360_images') {
            // Get 360 image IDs
            $image_ids_string = get_post_meta($product_id, '_w3dz_360_images', true);
            
            // If no images, return original image
            if (empty($image_ids_string)) {
                return $image;
            }
            
            // Convert image IDs to URLs
            $image_ids = explode(',', $image_ids_string);
            $images_360_urls = array();
            
            foreach ($image_ids as $id) {
                $id = trim($id);
                if (empty($id)) {
                    continue;
                }
                
                $image_url = wp_get_attachment_image_src($id, 'full');
                if ($image_url) {
                    $images_360_urls[] = $image_url[0];
                }
            }
            
            // If no valid images, return original image
            if (empty($images_360_urls)) {
                return $image;
            }
            
            // Encode images array as JSON for JavaScript
            $images_json = wp_json_encode($images_360_urls);
            
            // Build wrapper HTML for 360 images
            $wrapper = '<div class="w3dz-product-image-wrapper" data-product-id="' . esc_attr($product_id) . '" data-model-type="360_images" data-images-360="' . esc_attr($images_json) . '">';
            
            // Add original image
            $wrapper .= $image;
            
            // Add 360 overlay (hidden by default)
            $wrapper .= '<div class="w3dz-360-overlay" style="display:none;">';
            $wrapper .= '<img class="w3dz-360-image" src="" alt="360 view" />';
            $wrapper .= '</div>';
            
            // Add hover hint badge
            $wrapper .= '<span class="w3dz-hover-hint">360°</span>';
            
            $wrapper .= '</div>';
            
            return $wrapper;
        }
        
        // Fallback: return original image
        return $image;
    }
}