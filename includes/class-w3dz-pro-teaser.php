<?php
/**
 * Pro Features Teaser for WooCommerce 3D Asset Display Zyne (Free Version)
 * Displays locked UI for premium features to encourage upgrades
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class W3DZ_Pro_Teaser {
    
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
     * Check if Pro plugin is active
     * CRITICAL: Check this before showing any teaser
     */
    private static function is_pro_active() {
        return class_exists('WooCommerce_3D_Asset_Display_Zyne_Pro') || 
               class_exists('W3DZ_Pro_Core') ||
               isset($GLOBALS['w3dz_pro_active']);
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Hook to add Pro teaser content to product edit page
        // Only add hook if Pro is NOT active
        if (!self::is_pro_active()) {
            add_action('w3dz_after_camera_settings', array($this, 'render_product_page_teasers'));
        }
    }
    
    /**
     * Render Pro teasers for PRODUCT EDIT PAGE only
     * (Visual Editor + 3D Gallery - product-specific features)
     */
    public function render_product_page_teasers() {
        // Double-check Pro is not active
        if (self::is_pro_active()) {
            return;
        }
        
        ?>
        <!-- PRO FEATURES PREVIEW SECTION -->
        <div class="w3dz-pro-features-section">
            <?php
            self::render_visual_editor_teaser();
            self::render_gallery_teaser();
            ?>
        </div>
        <?php
    }
    
    /**
     * Render Pro features overview for SETTINGS PAGE
     * (Comprehensive Pro features showcase)
     */
    public static function render_settings_page_promo() {
        // Check if Pro is active
        if (self::is_pro_active()) {
            return; // Don't show promo if Pro is active
        }
        
        $pro_url = W3DZ_PRO_URL;
        ?>
        <div class="w3dz-settings-pro-section">
            
            <!-- Hero Banner -->
            <div class="w3dz-pro-hero">
                <div class="w3dz-pro-hero-content">
                    <span class="w3dz-pro-hero-badge">PRO VERSION</span>
                    <h2><?php esc_html_e('Unlock Advanced 3D & AR Features', 'w3dz-asset-display'); ?></h2>
                    <p><?php esc_html_e('Take your product presentations to the next level with professional-grade tools and integrations.', 'w3dz-asset-display'); ?></p>
                    <a href="<?php echo esc_url($pro_url); ?>" class="button button-primary button-hero w3dz-upgrade-hero-btn" target="_blank">
                        <span class="dashicons dashicons-unlock"></span>
                        <?php esc_html_e('Upgrade to Pro', 'w3dz-asset-display'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Pro Features Grid -->
            <div class="w3dz-pro-features-grid">
                
                <!-- Feature 1: Elementor Integration -->
                <div class="w3dz-pro-feature-card">
                    <div class="w3dz-feature-icon w3dz-icon-elementor">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                            <rect x="7" y="7" width="4" height="10" fill="currentColor"/>
                            <rect x="13" y="7" width="4" height="4" fill="currentColor"/>
                            <rect x="13" y="13" width="4" height="4" fill="currentColor"/>
                        </svg>
                    </div>
                    <h3><?php esc_html_e('Elementor Page Builder', 'w3dz-asset-display'); ?></h3>
                    <p><?php esc_html_e('Drag & drop 3D model widgets anywhere on your pages. Perfect for landing pages, homepages, and custom product showcases.', 'w3dz-asset-display'); ?></p>
                    <ul class="w3dz-feature-bullets">
                        <li><?php esc_html_e('3D Model Viewer Widget', 'w3dz-asset-display'); ?></li>
                        <li><?php esc_html_e('360° Product Viewer Widget', 'w3dz-asset-display'); ?></li>
                        <li><?php esc_html_e('AR Quick View Button Widget', 'w3dz-asset-display'); ?></li>
                    </ul>
                </div>
                
                <!-- Feature 2: Visual Orbit Editor -->
                <div class="w3dz-pro-feature-card">
                    <div class="w3dz-feature-icon w3dz-icon-orbit">
                        <span class="dashicons dashicons-camera" style="font-size: 36px; height: 36px; width: 36px; display: flex; align-items: center; justify-content: center;"></span>
                    </div>
                    <h3><?php esc_html_e('Visual 3D Camera Editor', 'w3dz-asset-display'); ?></h3>
                    <p><?php esc_html_e('Set the perfect starting camera angle by dragging a 3D sphere preview. No more guessing with numbers!', 'w3dz-asset-display'); ?></p>
                    <ul class="w3dz-feature-bullets">
                        <li><?php esc_html_e('Interactive drag & drop interface', 'w3dz-asset-display'); ?></li>
                        <li><?php esc_html_e('Real-time preview', 'w3dz-asset-display'); ?></li>
                        <li><?php esc_html_e('Save per-product camera angles', 'w3dz-asset-display'); ?></li>
                    </ul>
                </div>
                
                <!-- Feature 3: 3D Gallery -->
                <div class="w3dz-pro-feature-card">
                    <div class="w3dz-feature-icon w3dz-icon-inspector">
                        <span class="dashicons dashicons-search" style="font-size: 36px; height: 36px; width: 36px; display: flex; align-items: center; justify-content: center;"></span>
                    </div>
                    <h3><?php esc_html_e('Pro Model Inspector', 'w3dz-asset-display'); ?></h3>
                    <p><?php esc_html_e('Give your customers professional tools to inspect digital assets. Auto-detects features inside your GLB file.', 'w3dz-asset-display'); ?></p>
                    <ul class="w3dz-feature-bullets">
                        <li><?php esc_html_e('Auto-detect Animations & Variants', 'w3dz-asset-display'); ?></li>
                        <li><?php esc_html_e('Wireframe Mode (Topology Check)', 'w3dz-asset-display'); ?></li>
                        <li><?php esc_html_e('Studio Lighting & Background Switcher', 'w3dz-asset-display'); ?></li>
                    </ul>
                </div>
                
            </div>
            
            <!-- CTA Footer -->
            <div class="w3dz-pro-cta-footer">
                <h3><?php esc_html_e('Ready to upgrade your 3D product experience?', 'w3dz-asset-display'); ?></h3>
                <a href="<?php echo esc_url($pro_url); ?>" class="button button-primary button-large" target="_blank">
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php esc_html_e('View All Pro Features & Pricing', 'w3dz-asset-display'); ?>
                </a>
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Render Visual Orbit Editor Teaser
     */
    public static function render_visual_editor_teaser() {
        // Check if Pro is active
        if (self::is_pro_active()) {
            return;
        }
        
        $pro_url = W3DZ_PRO_URL;
        ?>
        <div class="w3dz-pro-teaser w3dz-visual-editor-teaser">
            <div class="w3dz-teaser-header">
                <span class="dashicons dashicons-lock"></span>
                <h4><?php esc_html_e('Visual 3D Camera Editor', 'w3dz-asset-display'); ?></h4>
                <span class="w3dz-pro-badge"><?php esc_html_e('PRO', 'w3dz-asset-display'); ?></span>
            </div>
            
            <div class="w3dz-teaser-content">
                <!-- Mockup Preview Image -->
                <div class="w3dz-teaser-preview">
                    <div class="w3dz-orbit-mockup">
                        <!-- 3D Sphere visualization mockup -->
                        <div class="w3dz-orbit-sphere">
                            <div class="w3dz-orbit-ring"></div>
                            <div class="w3dz-orbit-dot"></div>
                            <svg width="200" height="200" viewBox="0 0 200 200">
                                <!-- Sphere outline -->
                                <circle cx="100" cy="100" r="60" fill="none" stroke="#ddd" stroke-width="2"/>
                                <ellipse cx="100" cy="100" rx="60" ry="20" fill="none" stroke="#ddd" stroke-width="1"/>
                                <ellipse cx="100" cy="100" rx="20" ry="60" fill="none" stroke="#ddd" stroke-width="1"/>
                                
                                <!-- Camera position indicator -->
                                <circle cx="140" cy="70" r="8" fill="#0073aa" stroke="#fff" stroke-width="2"/>
                                <path d="M 140 70 L 100 100" stroke="#0073aa" stroke-width="2" stroke-dasharray="4,4"/>
                                
                                <!-- Model icon in center -->
                                <path d="M 100 90 L 85 100 L 100 110 L 115 100 Z" fill="#666"/>
                                <path d="M 100 90 L 100 75 L 115 85 L 115 100 Z" fill="#999"/>
                            </svg>
                        </div>
                        
                        <!-- Lock Overlay -->
                        <div class="w3dz-lock-overlay">
                            <div class="w3dz-lock-content">
                                <span class="dashicons dashicons-lock" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><strong><?php esc_html_e('Drag & Drop Camera Control', 'w3dz-asset-display'); ?></strong></p>
                                <p><?php esc_html_e('Set the perfect starting angle visually by dragging the 3D preview', 'w3dz-asset-display'); ?></p>
                                <a href="<?php echo esc_url($pro_url); ?>" class="button button-primary w3dz-upgrade-btn" target="_blank">
                                    <span class="dashicons dashicons-unlock"></span>
                                    <?php esc_html_e('Unlock Visual Editor', 'w3dz-asset-display'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="w3dz-teaser-description">
                    <ul class="w3dz-feature-list">
                        <li>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Interactive 3D sphere for visual camera positioning', 'w3dz-asset-display'); ?>
                        </li>
                        <li>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Real-time preview of camera angles', 'w3dz-asset-display'); ?>
                        </li>
                        <li>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Intuitive drag & drop controls', 'w3dz-asset-display'); ?>
                        </li>
                        <li>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('No more guessing numbers!', 'w3dz-asset-display'); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render 3D Gallery Teaser (Multiple Models/Colors)
     */
    public static function render_gallery_teaser() {
        // Check if Pro is active
        if (self::is_pro_active()) {
            return;
        }
        
        $pro_url = W3DZ_PRO_URL;
        ?>
        <div class="w3dz-pro-teaser w3dz-gallery-teaser">
            <div class="w3dz-teaser-header">
                <span class="dashicons dashicons-lock"></span>
                <h4><?php esc_html_e('3D Gallery - Multiple Models & Colors', 'w3dz-asset-display'); ?></h4>
                <span class="w3dz-pro-badge"><?php esc_html_e('PRO', 'w3dz-asset-display'); ?></span>
            </div>
            
            <div class="w3dz-teaser-content">
                <!-- Mockup Repeater Interface -->
                <div class="w3dz-teaser-preview w3dz-gallery-mockup">
                    <div class="w3dz-repeater-mockup">
                        <div class="w3dz-repeater-header">
                            <button type="button" class="button button-disabled" disabled>
                                <span class="dashicons dashicons-plus-alt"></span>
                                <?php esc_html_e('Add Model/Color Variant', 'w3dz-asset-display'); ?>
                            </button>
                        </div>
                        
                        <div class="w3dz-repeater-items">
                            <!-- Mock Item 1 -->
                            <div class="w3dz-repeater-item w3dz-disabled">
                                <span class="w3dz-drag-handle dashicons dashicons-menu"></span>
                                <div class="w3dz-item-content">
                                    <input type="text" placeholder="<?php esc_attr_e('Model Name (e.g., Red)', 'w3dz-asset-display'); ?>" disabled />
                                    <input type="text" placeholder="<?php esc_attr_e('GLB File URL', 'w3dz-asset-display'); ?>" disabled />
                                    <button class="button button-small" disabled><?php esc_html_e('Upload', 'w3dz-asset-display'); ?></button>
                                </div>
                                <button class="w3dz-remove-item dashicons dashicons-trash" disabled></button>
                            </div>
                            
                            <!-- Mock Item 2 -->
                            <div class="w3dz-repeater-item w3dz-disabled">
                                <span class="w3dz-drag-handle dashicons dashicons-menu"></span>
                                <div class="w3dz-item-content">
                                    <input type="text" placeholder="<?php esc_attr_e('Model Name (e.g., Blue)', 'w3dz-asset-display'); ?>" disabled />
                                    <input type="text" placeholder="<?php esc_attr_e('GLB File URL', 'w3dz-asset-display'); ?>" disabled />
                                    <button class="button button-small" disabled><?php esc_html_e('Upload', 'w3dz-asset-display'); ?></button>
                                </div>
                                <button class="w3dz-remove-item dashicons dashicons-trash" disabled></button>
                            </div>
                            
                            <!-- Mock Item 3 -->
                            <div class="w3dz-repeater-item w3dz-disabled">
                                <span class="w3dz-drag-handle dashicons dashicons-menu"></span>
                                <div class="w3dz-item-content">
                                    <input type="text" placeholder="<?php esc_attr_e('Model Name (e.g., Black)', 'w3dz-asset-display'); ?>" disabled />
                                    <input type="text" placeholder="<?php esc_attr_e('GLB File URL', 'w3dz-asset-display'); ?>" disabled />
                                    <button class="button button-small" disabled><?php esc_html_e('Upload', 'w3dz-asset-display'); ?></button>
                                </div>
                                <button class="w3dz-remove-item dashicons dashicons-trash" disabled></button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lock Overlay -->
                    <div class="w3dz-lock-overlay">
                        <div class="w3dz-lock-content">
                            <span class="dashicons dashicons-images-alt2" style="font-size: 48px; opacity: 0.3;"></span>
                            <p><strong><?php esc_html_e('Multiple Models/Colors Gallery', 'w3dz-asset-display'); ?></strong></p>
                            <p><?php esc_html_e('Let customers switch between different colors and model variants', 'w3dz-asset-display'); ?></p>
                            <a href="<?php echo esc_url($pro_url); ?>" class="button button-primary w3dz-upgrade-btn" target="_blank">
                                <span class="dashicons dashicons-unlock"></span>
                                <?php esc_html_e('Unlock Gallery Feature', 'w3dz-asset-display'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="w3dz-teaser-description">
                    <ul class="w3dz-feature-list">
                        <li>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Upload unlimited model variants', 'w3dz-asset-display'); ?>
                        </li>
                        <li>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Perfect for products with multiple colors', 'w3dz-asset-display'); ?>
                        </li>
                        <li>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Drag to reorder gallery items', 'w3dz-asset-display'); ?>
                        </li>
                        <li>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Customers can switch models on frontend', 'w3dz-asset-display'); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get Pro URL
     */
    public static function get_pro_url() {
        return W3DZ_PRO_URL;
    }
    
    /**
     * Check if current version is Pro
     */
    public static function is_pro() {
        return self::is_pro_active();
    }
}