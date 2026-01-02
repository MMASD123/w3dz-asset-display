<?php
/**
 * Admin functionality for WooCommerce 3D Asset Display Zyne
 * Fixed Pro plugin integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class W3DZ_Admin {
    
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
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add product data tab
        add_filter('woocommerce_product_data_tabs', array($this, 'add_product_data_tab'));
        
        // Add product data panel
        add_action('woocommerce_product_data_panels', array($this, 'add_product_data_panel'));
        
        // Save product meta
        add_action('woocommerce_process_product_meta', array($this, 'save_product_meta'));
    }
    
    /**
     * New: Check if Pro plugin is active
     * Use runtime detection instead of constants
     */
    private function is_pro_active() {
        return class_exists('WooCommerce_3D_Asset_Display_Zyne_Pro') || 
               class_exists('W3DZ_Pro_Core') ||
               isset($GLOBALS['w3dz_pro_active']);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on product edit pages
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        
        global $post;
        if (!$post || 'product' !== $post->post_type) {
            return;
        }
        
        // Enqueue WordPress media uploader
        wp_enqueue_media();
        
        // Enqueue admin CSS
        wp_enqueue_style(
            'w3dz-admin-style',
            W3DZ_PLUGIN_URL . 'assets/css/w3dz-admin.css',
            array(),
            W3DZ_VERSION
        );
        
        // Enqueue admin JS (basic functionality)
        wp_enqueue_script(
            'w3dz-admin-script',
            W3DZ_PLUGIN_URL . 'assets/js/w3dz-admin.js',
            array('jquery', 'jquery-ui-sortable'),
            W3DZ_VERSION,
            true
        );
        
        // NEW: Check if there's a 3D model field before loading camera editor
        $model_type = get_post_meta($post->ID, '_w3dz_model_type', true);
        
        if ($model_type === '3d_model' || empty($model_type)) {
            // Enqueue Visual Camera Editor JS
            wp_enqueue_script(
                'w3dz-camera-visual',
                W3DZ_PLUGIN_URL . 'assets/js/w3dz-camera-visual.js',
                array('jquery', 'w3dz-admin-script'),
                W3DZ_VERSION,
                true
            );
            
            // Localize script with translations
            wp_localize_script('w3dz-camera-visual', 'w3dzCameraData', array(
                'strings' => array(
                    'saveSuccess' => __('Camera angle saved! Remember to click "Update" to save the product.', 'w3dz-asset-display')
                )
            ));
        }
    }
    
    /**
     * Add custom tab to product data metabox
     */
    public function add_product_data_tab($tabs) {
        $tabs['w3dz_3d_360'] = array(
            'label'    => __('3D & 360 View', 'w3dz-asset-display'),
            'target'   => 'w3dz_3d_360_panel',
            'class'    => array('show_if_simple', 'show_if_variable'),
            'priority' => 65,
        );
        return $tabs;
    }
    
    /**
     * Add custom panel content
     */
    public function add_product_data_panel() {
        global $post;
        $product_id = $post->ID;
        
        // Get saved values
        $model_type = get_post_meta($product_id, '_w3dz_model_type', true);
        $glb_url = get_post_meta($product_id, '_w3dz_glb_url', true);
        $usdz_url = get_post_meta($product_id, '_w3dz_usdz_url', true);
        $poster_url = get_post_meta($product_id, '_w3dz_poster_url', true);
        $images_360 = get_post_meta($product_id, '_w3dz_360_images', true);
        ?>
        
        <div id="w3dz_3d_360_panel" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                
                <p class="form-field">
                    <label for="w3dz_model_type"><?php esc_html_e('3D Model Type', 'w3dz-asset-display'); ?></label>
                    <select id="w3dz_model_type" name="w3dz_model_type" class="select short">
                        <option value="none" <?php selected($model_type, 'none'); ?>><?php esc_html_e('None', 'w3dz-asset-display'); ?></option>
                        <option value="3d_model" <?php selected($model_type, '3d_model'); ?>><?php esc_html_e('True 3D Model', 'w3dz-asset-display'); ?></option>
                        <option value="360_images" <?php selected($model_type, '360_images'); ?>><?php esc_html_e('360° Image Sequence', 'w3dz-asset-display'); ?></option>
                    </select>
                </p>
                
                <!-- 3D Model Fields -->
                <div id="w3dz_3d_model_fields" style="display: none;">
                    
                    <p class="form-field">
                        <label for="w3dz_glb_url"><?php esc_html_e('GLB/GLTF File URL', 'w3dz-asset-display'); ?></label>
                        <input type="text" id="w3dz_glb_url" name="w3dz_glb_url" value="<?php echo esc_attr($glb_url); ?>" style="width: 60%;" />
                        <button type="button" class="button w3dz-upload-file-btn" data-target="w3dz_glb_url"><?php esc_html_e('Upload GLB', 'w3dz-asset-display'); ?></button>
                        <span class="description"><?php esc_html_e('Upload a .glb or .gltf file for 3D viewing', 'w3dz-asset-display'); ?></span>
                    </p>
                    
                    <p class="form-field">
                        <label for="w3dz_usdz_url"><?php esc_html_e('USDZ File URL (iOS AR)', 'w3dz-asset-display'); ?></label>
                        <input type="text" id="w3dz_usdz_url" name="w3dz_usdz_url" value="<?php echo esc_attr($usdz_url); ?>" style="width: 60%;" />
                        <button type="button" class="button w3dz-upload-file-btn" data-target="w3dz_usdz_url"><?php esc_html_e('Upload USDZ', 'w3dz-asset-display'); ?></button>
                        <span class="description"><?php esc_html_e('Optional: Upload a .usdz file for iOS AR Quick Look', 'w3dz-asset-display'); ?></span>
                    </p>
                    
                    <p class="form-field">
                        <label for="w3dz_poster_url"><?php esc_html_e('Poster Image', 'w3dz-asset-display'); ?></label>
                        <input type="text" id="w3dz_poster_url" name="w3dz_poster_url" value="<?php echo esc_attr($poster_url); ?>" style="width: 60%;" />
                        <button type="button" class="button w3dz-upload-image-btn" data-target="w3dz_poster_url"><?php esc_html_e('Upload Image', 'w3dz-asset-display'); ?></button>
                        <span class="description"><?php esc_html_e('Optional: Static cover image shown before 3D model loads', 'w3dz-asset-display'); ?></span>
                    </p>
                    
                    <!-- Camera Angle Settings Section -->
                    <div class="w3dz-camera-settings" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                        <h4 style="margin-bottom: 15px;">
                            <?php esc_html_e('Camera Angle Settings', 'w3dz-asset-display'); ?>
                        </h4>
                        
                        <?php
                        // Check if GLB model is uploaded
                        if (empty($glb_url)) {
                            // No model - show upload prompt
                            ?>
                            <div class="w3dz-camera-editor-placeholder" style="background: #fff3cd; border: 2px dashed #ffc107; border-radius: 8px; padding: 30px; text-align: center;">
                                <span class="dashicons dashicons-warning" style="font-size: 48px; color: #ffc107; margin-bottom: 15px;"></span>
                                <p style="margin: 0; color: #856404; font-weight: 600;">
                                    <?php esc_html_e('Please upload a GLB model first to use the Visual Camera Editor.', 'w3dz-asset-display'); ?>
                                </p>
                            </div>
                            <?php
                        } else {
                            // Model exists - show Visual Camera Editor
                            $camera_orbit = get_post_meta($product_id, '_w3dz_camera_orbit', true);
                            if (empty($camera_orbit)) {
                                $defaults = W3DZ_Camera::get_default_camera();
                                $camera_orbit = W3DZ_Camera::format_camera_orbit($defaults['theta'], $defaults['phi'], $defaults['radius']);
                            }
                            ?>
                            
                            <!-- Visual Camera Editor -->
                            <div class="w3dz-visual-camera-editor">
                                
                                <p class="description" style="margin: 0 0 15px 0;">
                                    <?php esc_html_e('Drag the 3D preview to set the perfect starting camera angle.', 'w3dz-asset-display'); ?>
                                </p>
                                
                                <div class="w3dz-camera-editor-container">
                                    
                                    <!-- 3D Preview -->
                                    <div class="w3dz-camera-preview" id="w3dz-camera-preview">
                                        <div class="w3dz-camera-loading">
                                            <div class="w3dz-spinner"></div>
                                            <p><?php esc_html_e('Loading 3D preview...', 'w3dz-asset-display'); ?></p>
                                        </div>
                                    </div>
                                    
                                    <!-- Controls -->
                                    <div class="w3dz-camera-controls">
                                        
                                        <!-- Current Values Display -->
                                        <div class="w3dz-camera-info">
                                            <div class="w3dz-info-item">
                                                <label><?php esc_html_e('Camera Orbit:', 'w3dz-asset-display'); ?></label>
                                                <code id="w3dz-camera-orbit-display"><?php echo esc_html($camera_orbit); ?></code>
                                            </div>
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="w3dz-camera-actions">
                                            <button type="button" class="button button-primary button-large w3dz-save-camera-view">
                                                <span class="dashicons dashicons-saved" style="margin-top: 4px;"></span>
                                                <?php esc_html_e('Save Current View', 'w3dz-asset-display'); ?>
                                            </button>
                                            
                                            <button type="button" class="button button-secondary w3dz-reset-camera-view">
                                                <span class="dashicons dashicons-image-rotate" style="margin-top: 4px;"></span>
                                                <?php esc_html_e('Reset to Front View', 'w3dz-asset-display'); ?>
                                            </button>
                                        </div>
                                        
                                        <!-- Quick Presets -->
                                        <div class="w3dz-camera-presets-visual">
                                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                                                <?php esc_html_e('Quick Presets:', 'w3dz-asset-display'); ?>
                                            </label>
                                            <div class="w3dz-preset-buttons">
                                                <button type="button" class="button w3dz-preset-btn-visual" data-orbit="0deg 75deg 3m" title="<?php esc_attr_e('Front View', 'w3dz-asset-display'); ?>">
                                                    👁️ <?php esc_html_e('Front', 'w3dz-asset-display'); ?>
                                                </button>
                                                <button type="button" class="button w3dz-preset-btn-visual" data-orbit="90deg 75deg 3m" title="<?php esc_attr_e('Side View', 'w3dz-asset-display'); ?>">
                                                    ↔️ <?php esc_html_e('Side', 'w3dz-asset-display'); ?>
                                                </button>
                                                <button type="button" class="button w3dz-preset-btn-visual" data-orbit="0deg 0deg 5m" title="<?php esc_attr_e('Top View', 'w3dz-asset-display'); ?>">
                                                    ⬇️ <?php esc_html_e('Top', 'w3dz-asset-display'); ?>
                                                </button>
                                                <button type="button" class="button w3dz-preset-btn-visual" data-orbit="45deg 65deg 3m" title="<?php esc_attr_e('3/4 View', 'w3dz-asset-display'); ?>">
                                                    📷 <?php esc_html_e('3/4 View', 'w3dz-asset-display'); ?>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Instructions -->
                                        <div class="w3dz-camera-instructions" style="margin-top: 15px; padding: 12px; background: #f0f6fc; border-left: 4px solid #0073aa; border-radius: 4px;">
                                            <p style="margin: 0; font-size: 13px; line-height: 1.6;">
                                                <strong>💡 <?php esc_html_e('How to use:', 'w3dz-asset-display'); ?></strong><br>
                                                • <?php esc_html_e('Drag to rotate the model and find the perfect angle', 'w3dz-asset-display'); ?><br>
                                                • <?php esc_html_e('Scroll/pinch to zoom in/out', 'w3dz-asset-display'); ?><br>
                                                • <?php esc_html_e('Click "Save Current View" to set this as the starting angle', 'w3dz-asset-display'); ?><br>
                                                • <?php esc_html_e('Use presets for common angles, then fine-tune by dragging', 'w3dz-asset-display'); ?>
                                            </p>
                                        </div>
                                        
                                    </div>
                                    
                                </div>
                                
                                <!-- Hidden Inputs -->
                                <input type="hidden" id="w3dz_camera_orbit" name="w3dz_camera_orbit" value="<?php echo esc_attr($camera_orbit); ?>" />
                                <input type="hidden" id="w3dz_camera_glb_url" value="<?php echo esc_attr($glb_url); ?>" />
                                
                            </div>
                            <?php
                        }
                        ?>
                        
                    </div>
                    <!-- END Camera Settings Section -->
                    
                    <?php
                    /**
                     * Hook: w3dz_after_camera_settings
                     * 
                     * Allows Pro plugin to add additional features after camera settings.
                     * For example: Visual Orbit Editor, Model Inspector, etc.
                     * 
                     * @since 1.0.0
                     * @param int $product_id Current product ID
                     */
                    do_action('w3dz_after_camera_settings', $product_id);
                    
                    /**
                     * Hook: w3dz_render_pro_features
                     * 
                     * Allows Pro plugin to render additional features.
                     * For example: 3D Gallery (Multiple Models/Colors), Advanced AR settings, etc.
                     * 
                     * @since 1.0.0
                     * @param int $product_id Current product ID
                     */
                    do_action('w3dz_render_pro_features', $product_id);
                    ?>
                    
                </div>
                <!-- END 3D Model Fields -->
                
                <!-- 360 Images Fields -->
                <div id="w3dz_360_images_fields" style="display: none;">
                    
                    <p class="form-field">
                        <label for="w3dz_360_images"><?php esc_html_e('360° Images', 'w3dz-asset-display'); ?></label>
                        <input type="hidden" id="w3dz_360_images" name="w3dz_360_images" value="<?php echo esc_attr($images_360); ?>" />
                        <button type="button" class="button w3dz-select-360-images"><?php esc_html_e('Select Images', 'w3dz-asset-display'); ?></button>
                        <span class="description"><?php esc_html_e('Select multiple images in sequence (minimum 8 recommended)', 'w3dz-asset-display'); ?></span>
                    </p>
                    
                    <div id="w3dz_360_preview" class="w3dz-360-preview">
                        <?php
                        if (!empty($images_360)) {
                            $image_ids = explode(',', $images_360);
                            foreach ($image_ids as $image_id) {
                                $image_url = wp_get_attachment_image_src($image_id, 'thumbnail');
                                if ($image_url) {
                                    echo '<div class="w3dz-360-thumb" data-id="' . esc_attr($image_id) . '">';
                                    echo '<img src="' . esc_url($image_url[0]) . '" />';
                                    echo '<span class="w3dz-remove-image">×</span>';
                                    echo '</div>';
                                }
                            }
                        }
                        ?>
                    </div>
                    
                </div>
                <!-- END 360 Images Fields -->
                
            </div>
        </div>
        
        <?php
    }
    
    /**
     * Save product meta data
     * 
     * SECURITY FIX: Properly verify WooCommerce nonce before processing form data
     */
    public function save_product_meta($post_id) {
        // SECURITY FIX: Verify nonce properly
        // WooCommerce adds 'woocommerce_meta_nonce' to product edit forms
        if (!isset($_POST['woocommerce_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['woocommerce_meta_nonce'])), 'woocommerce_save_data')) {
            return;
        }
        
        // Check if user has permission to edit this post
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if not autosaving
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Save model type
        if (isset($_POST['w3dz_model_type'])) {
            $model_type = sanitize_text_field(wp_unslash($_POST['w3dz_model_type']));
            update_post_meta($post_id, '_w3dz_model_type', $model_type);
        }
        
        // Save GLB URL
        if (isset($_POST['w3dz_glb_url'])) {
            $glb_url = esc_url_raw(wp_unslash($_POST['w3dz_glb_url']));
            update_post_meta($post_id, '_w3dz_glb_url', $glb_url);
        }
        
        // Save USDZ URL
        if (isset($_POST['w3dz_usdz_url'])) {
            $usdz_url = esc_url_raw(wp_unslash($_POST['w3dz_usdz_url']));
            update_post_meta($post_id, '_w3dz_usdz_url', $usdz_url);
        }
        
        // Save Poster URL
        if (isset($_POST['w3dz_poster_url'])) {
            $poster_url = esc_url_raw(wp_unslash($_POST['w3dz_poster_url']));
            update_post_meta($post_id, '_w3dz_poster_url', $poster_url);
        }
        
        // Save 360 images
        if (isset($_POST['w3dz_360_images'])) {
            $image_ids = sanitize_text_field(wp_unslash($_POST['w3dz_360_images']));
            update_post_meta($post_id, '_w3dz_360_images', $image_ids);
        }
        
        // Save camera orbit (Visual Editor format)
        if (isset($_POST['w3dz_camera_orbit'])) {
            $camera_orbit = sanitize_text_field(wp_unslash($_POST['w3dz_camera_orbit']));
            
            if (!empty($camera_orbit)) {
                // Validate format: "45deg 75deg 3m"
                if (preg_match('/^\d+(\.\d+)?deg\s+\d+(\.\d+)?deg\s+\d+(\.\d+)?m$/', $camera_orbit)) {
                    update_post_meta($post_id, '_w3dz_camera_orbit', $camera_orbit);
                    
                    // Also save in old format for backward compatibility
                    $parsed = W3DZ_Camera::parse_camera_orbit($camera_orbit);
                    if ($parsed) {
                        update_post_meta($post_id, '_w3dz_camera_theta', $parsed['theta']);
                        update_post_meta($post_id, '_w3dz_camera_phi', $parsed['phi']);
                        update_post_meta($post_id, '_w3dz_camera_radius', $parsed['radius']);
                    }
                }
            } else {
                // Empty - delete all camera settings
                delete_post_meta($post_id, '_w3dz_camera_orbit');
                delete_post_meta($post_id, '_w3dz_camera_theta');
                delete_post_meta($post_id, '_w3dz_camera_phi');
                delete_post_meta($post_id, '_w3dz_camera_radius');
            }
        }
        
        /**
         * Hook: w3dz_save_pro_features
         * 
         * Allows Pro plugin to save additional product meta data.
         * 
         * @since 1.0.0
         * @param int $post_id Current product ID
         */
        do_action('w3dz_save_pro_features', $post_id);
    }
}