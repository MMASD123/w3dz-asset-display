<?php
/**
 * Frontend functionality for WooCommerce 3D Asset Display Zyne
 * Free Version - No Pro features included
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class W3DZ_Frontend {
    
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
        // Enqueue frontend scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Add 3D/360 viewer button to product page
        add_action('woocommerce_before_single_product_summary', array($this, 'add_viewer_button'), 25);
        
        // Add modal/lightbox container to footer
        add_action('wp_footer', array($this, 'add_viewer_modal'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only load on single product pages
        if (!is_product()) {
            return;
        }
        
        global $post;
        $product_id = $post->ID;
        $model_type = get_post_meta($product_id, '_w3dz_model_type', true);
        
        // Only enqueue if there's a 3D model or 360 images configured
        if (empty($model_type) || $model_type === 'none') {
            return;
        }
        
        // Enqueue frontend CSS
        wp_enqueue_style(
            'w3dz-frontend-style',
            W3DZ_PLUGIN_URL . 'assets/css/w3dz-style.css',
            array(),
            W3DZ_VERSION
        );
        
        // For 3D models, add Model Viewer script with proper dependency management
        if ($model_type === '3d_model') {
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
            
            // Add type="module" attribute to model-viewer script
            add_filter('script_loader_tag', array($this, 'add_module_to_model_viewer'), 10, 3);
        }
        
        // Enqueue frontend JS
        $dependencies = array('jquery');
        if ($model_type === '3d_model') {
            $dependencies[] = 'w3dz-model-viewer';
        }
        
        wp_enqueue_script(
            'w3dz-frontend-script',
            W3DZ_PLUGIN_URL . 'assets/js/w3dz-frontend.js',
            $dependencies,
            W3DZ_VERSION,
            true
        );
        
        // Prepare data for JavaScript
        $viewer_data = $this->prepare_viewer_data($product_id, $model_type);
        
        // Localize script with product data
        wp_localize_script(
            'w3dz-frontend-script',
            'w3dzData',
            $viewer_data
        );
        
        // Pass settings to JavaScript (for auto-rotation)
        wp_localize_script(
            'w3dz-frontend-script',
            'w3dzSettings',
            array(
                'enableAutoRotate' => W3DZ_Settings::get_option('enable_auto_rotate', true) ? true : false,
                'rotationSpeed'    => W3DZ_Settings::get_option('rotation_speed', '30deg')
            )
        );
        
        // Pass 360 degree settings to JavaScript
        wp_localize_script(
            'w3dz-frontend-script',
            'w3dz360Settings',
            array(
                'enable360Autoplay' => W3DZ_Settings::get_option('enable_360_autoplay', true) ? true : false,
                'playbackSpeed'     => intval(W3DZ_Settings::get_option('360_playback_speed', '100')),
                'loopInfinite'      => W3DZ_Settings::get_option('360_loop_infinite', false) ? true : false,
                'autoplayHover'     => W3DZ_Settings::get_option('360_autoplay_hover', false) ? true : false
            )
        );
        
        // Hook: Allow Pro plugin to enqueue its own assets
        do_action('w3dz_after_enqueue_frontend_assets', $product_id, $model_type);
    }
    
    /**
     * Add type="module" attribute to model-viewer script
     * FIXED: Using str_replace to modify existing tag instead of rebuilding it
     */
    public function add_module_to_model_viewer($tag, $handle, $src) {
        if ('w3dz-model-viewer' === $handle) {
            // Replace <script with <script type="module" to preserve all other attributes
            $tag = str_replace('<script ', '<script type="module" ', $tag);
        }
        return $tag;
    }
    
    /**
     * Prepare viewer data for JavaScript
     * Free Version - Only basic data, no Pro features
     */
    private function prepare_viewer_data($product_id, $model_type) {
        $data = array(
            'modelType' => $model_type,
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('w3dz_viewer_nonce'),
        );
        
        if ($model_type === '3d_model') {
            $data['glbUrl'] = get_post_meta($product_id, '_w3dz_glb_url', true);
            $data['usdzUrl'] = get_post_meta($product_id, '_w3dz_usdz_url', true);
            $data['posterUrl'] = get_post_meta($product_id, '_w3dz_poster_url', true);
            
            // Camera settings - Check for Pro version first
            wp_cache_delete($product_id, 'post_meta');
            $pro_camera_orbit = get_post_meta($product_id, '_w3dz_camera_orbit', true);
            
            if (!empty($pro_camera_orbit)) {
                // Pro version camera data exists
                $data['cameraOrbit'] = $pro_camera_orbit;
                $data['cameraSource'] = 'pro';
            } else {
                // Free version: Use theta/phi/radius
                $camera_settings = W3DZ_Camera::get_camera_settings($product_id);
                $data['cameraOrbit'] = W3DZ_Camera::format_camera_orbit(
                    $camera_settings['theta'],
                    $camera_settings['phi'],
                    $camera_settings['radius']
                );
                $data['cameraSource'] = $camera_settings['source'];
            }
            
        } elseif ($model_type === '360_images') {
            $image_ids = get_post_meta($product_id, '_w3dz_360_images', true);
            $images_array = array();
            
            if (!empty($image_ids)) {
                $ids = explode(',', $image_ids);
                foreach ($ids as $id) {
                    $image_url = wp_get_attachment_image_src(trim($id), 'full');
                    if ($image_url) {
                        $images_array[] = $image_url[0];
                    }
                }
            }
            
            $data['images360'] = $images_array;
        }
        
        // Hook: Allow Pro plugin to add its own data
        $data = apply_filters('w3dz_viewer_data', $data, $product_id, $model_type);
        
        return $data;
    }
    
    /**
     * Add viewer button to product page
     */
    public function add_viewer_button() {
        global $post;
        $product_id = $post->ID;
        $model_type = get_post_meta($product_id, '_w3dz_model_type', true);
        
        if (empty($model_type) || $model_type === 'none') {
            return;
        }
        
        ?>
        <div class="w3dz-viewer-buttons">
            <?php if ($model_type === '3d_model') : ?>
                <button type="button" class="w3dz-open-viewer-btn w3dz-3d-btn" data-type="3d">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                    <?php esc_html_e('View in 3D / AR', 'w3dz-asset-display'); ?>
                </button>
            <?php elseif ($model_type === '360_images') : ?>
                <button type="button" class="w3dz-open-viewer-btn w3dz-360-btn" data-type="360">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                    <?php esc_html_e('View 360°', 'w3dz-asset-display'); ?>
                </button>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Add viewer modal to footer
     * Free Version - Basic modal structure with hooks for Pro features
     */
    public function add_viewer_modal() {
        if (!is_product()) {
            return;
        }
        
        global $post;
        $product_id = $post->ID;
        $model_type = get_post_meta($product_id, '_w3dz_model_type', true);
        
        if (empty($model_type) || $model_type === 'none') {
            return;
        }
        
        ?>
        <div id="w3dz-viewer-modal" class="w3dz-modal" style="display: none;">
            <div class="w3dz-modal-overlay"></div>
            <div class="w3dz-modal-content">
                <button class="w3dz-modal-close" aria-label="<?php esc_attr_e('Close', 'w3dz-asset-display'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
                
                <?php
                /**
                 * Hook: w3dz_before_viewer_container
                 * 
                 * Allows Pro plugin to inject content above the viewer (e.g., Gallery switcher)
                 * 
                 * @param int    $product_id Product ID
                 * @param string $model_type Model type (3d_model or 360_images)
                 */
                do_action('w3dz_before_viewer_container', $product_id, $model_type);
                ?>
                
                <div id="w3dz-viewer-container" class="w3dz-viewer-container">
                    <?php if ($model_type === '3d_model') : ?>
                        <!-- 3D Model Viewer will be injected here by JavaScript -->
                        <div id="w3dz-3d-viewer"></div>
                    <?php elseif ($model_type === '360_images') : ?>
                        <!-- 360 Image Viewer -->
                        <div id="w3dz-360-viewer" class="w3dz-360-viewer">
                            <div class="w3dz-360-spinner" style="display: none;">
                                <div class="w3dz-spinner"></div>
                                <p><?php esc_html_e('Loading images...', 'w3dz-asset-display'); ?></p>
                            </div>
                            <img id="w3dz-360-image" src="" alt="360 view" />
                            <div class="w3dz-360-controls">
                                <p><?php esc_html_e('Drag to rotate', 'w3dz-asset-display'); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php
                /**
                 * Hook: w3dz_after_viewer_container
                 * 
                 * Allows Pro plugin to inject content below the viewer
                 * 
                 * @param int    $product_id Product ID
                 * @param string $model_type Model type (3d_model or 360_images)
                 */
                do_action('w3dz_after_viewer_container', $product_id, $model_type);
                ?>
                
            </div>
        </div>
        <?php
    }
}