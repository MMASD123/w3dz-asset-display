/**
 * Visual Camera Editor JavaScript
 * Handles 3D interactive camera angle editor with model-viewer
 * Adapted from Pro version for Free plugin
 */

(function($) {
    'use strict';
    
    const W3DZ_Camera_Visual = {
        
        modelViewer: null,
        glbUrl: null,
        currentOrbit: null,
        isInitialized: false,
        updateInterval: null,
        
        /**
		 * Initialize Camera Editor
		 */
		init: function() {
			console.log('🎥 W3DZ Visual Camera Editor initializing...');
			
			// Check if visual camera editor exists
			const $editor = $('.w3dz-visual-camera-editor');
			console.log('📍 Editor found:', $editor.length);
			
			if ($editor.length === 0) {
				console.log('ℹ️ Visual camera editor not found on this page');
				return;
			}
			
			// Get GLB URL
			this.glbUrl = $('#w3dz_camera_glb_url').val();
			console.log('📍 GLB URL:', this.glbUrl);
			
			if (!this.glbUrl) {
				console.log('⚠️ No GLB model uploaded yet');
				return;
			}
			
			// Load current orbit value
			this.currentOrbit = $('#w3dz_camera_orbit').val() || '0deg 75deg 3m';
			console.log('📍 Current Orbit:', this.currentOrbit);
			
			// Wait for model-viewer to be defined (it's a custom element)
			this.waitForModelViewer();
		},

		/**
		 * Wait for model-viewer custom element to be defined
		 */
		waitForModelViewer: function() {
			const self = this;
			
			if (typeof customElements !== 'undefined' && customElements.get('model-viewer')) {
				console.log('✅ model-viewer is defined, creating viewer...');
				self.createModelViewer();
				self.bindEvents();
			} else {
				console.log('⏳ Waiting for model-viewer to be defined...');
				setTimeout(function() {
					self.waitForModelViewer();
				}, 100);
			}
		},
        
        /**
         * Create model-viewer element dynamically
         */
        createModelViewer: function() {
            console.log('🔨 Creating model-viewer...');
            
            const $preview = $('#w3dz-camera-preview');
            
            // Create model-viewer HTML
            const modelViewerHTML = `
                <model-viewer 
                    id="w3dz-camera-model-viewer"
                    src="${this.glbUrl}"
                    camera-orbit="${this.currentOrbit}"
                    camera-controls
                    touch-action="none"
                    min-camera-orbit="auto auto 0.5m"
                    max-camera-orbit="auto auto 10m"
                    min-field-of-view="20deg"
                    max-field-of-view="90deg"
                    field-of-view="45deg"
                    interpolation-decay="200"
                    style="width: 100%; height: 100%;"
                    loading="eager">
                    
                    <!-- Loading indicator -->
                    <div slot="poster" class="w3dz-camera-loading">
                        <div class="w3dz-spinner"></div>
                        <p>Loading 3D preview...</p>
                    </div>
                    
                    <!-- Error message -->
                    <div slot="error" style="padding: 20px; text-align: center; color: #d63638;">
                        <span class="dashicons dashicons-warning" style="font-size: 48px;"></span>
                        <p>Failed to load 3D model. Please check the GLB file.</p>
                    </div>
                </model-viewer>
            `;
            
            // Remove loading placeholder
            $preview.find('.w3dz-camera-loading').remove();
            
            // Insert model-viewer
            $preview.html(modelViewerHTML);
            
            // Get reference to model-viewer element
            this.modelViewer = document.getElementById('w3dz-camera-model-viewer');
            
            // Listen for model load
            this.modelViewer.addEventListener('load', () => {
                console.log('✅ Model loaded successfully');
                this.onModelLoaded();
            });
            
            // Listen for errors
            this.modelViewer.addEventListener('error', (e) => {
                console.error('❌ Failed to load model:', e);
            });
            
            // Listen for camera changes
            this.modelViewer.addEventListener('camera-change', () => {
                this.onCameraChange();
            });
        },
        
        /**
         * Handle model loaded event
         */
        onModelLoaded: function() {
            this.isInitialized = true;
            
            // Start monitoring camera orbit
            this.startOrbitMonitoring();
            
            // Update display
            this.updateOrbitDisplay();
        },
        
        /**
         * Start monitoring camera orbit changes
         */
        startOrbitMonitoring: function() {
            const self = this;
            
            // Update orbit every 100ms while user is interacting
            this.updateInterval = setInterval(function() {
                if (self.modelViewer && self.isInitialized) {
                    const orbit = self.modelViewer.getCameraOrbit();
                    const orbitString = self.formatOrbit(orbit);
                    
                    if (orbitString !== self.currentOrbit) {
                        self.currentOrbit = orbitString;
                        self.updateOrbitDisplay();
                    }
                }
            }, 100);
        },
        
        /**
         * Handle camera change event
         */
        onCameraChange: function() {
            // Update orbit immediately on camera change
            if (!this.modelViewer || !this.isInitialized) return;
            
            const orbit = this.modelViewer.getCameraOrbit();
            const orbitString = this.formatOrbit(orbit);
            
            if (orbitString !== this.currentOrbit) {
                this.currentOrbit = orbitString;
                this.updateOrbitDisplay();
            }
        },
        
        /**
         * Format orbit object to string
         * 
         * @param {object} orbit Orbit object from getCameraOrbit()
         * @return {string} Formatted orbit string
         */
        formatOrbit: function(orbit) {
            // getCameraOrbit() returns {theta, phi, radius}
            // theta and phi are in radians, need to convert to degrees
            const thetaDeg = this.radToDeg(orbit.theta);
            const phiDeg = this.radToDeg(orbit.phi);
            const radius = orbit.radius;
            
            // Format: "45deg 75deg 3m"
            return `${Math.round(thetaDeg)}deg ${Math.round(phiDeg)}deg ${radius.toFixed(1)}m`;
        },
        
        /**
         * Convert radians to degrees
         */
        radToDeg: function(rad) {
            return rad * (180 / Math.PI);
        },
        
        /**
         * Convert degrees to radians
         */
        degToRad: function(deg) {
            return deg * (Math.PI / 180);
        },
        
        /**
         * Update orbit display
         */
        updateOrbitDisplay: function() {
            $('#w3dz-camera-orbit-display').text(this.currentOrbit);
        },
        
        /**
         * Bind all event handlers
         */
        bindEvents: function() {
            const self = this;
            
            // Save current view button
            $(document).on('click', '.w3dz-save-camera-view', function(e) {
                e.preventDefault();
                self.saveCurrentView();
            });
            
            // Reset view button
            $(document).on('click', '.w3dz-reset-camera-view', function(e) {
                e.preventDefault();
                self.resetToFrontView();
            });
            
            // Preset buttons
            $(document).on('click', '.w3dz-preset-btn-visual', function(e) {
                e.preventDefault();
                const orbit = $(this).data('orbit');
                self.setOrbit(orbit);
            });
            
            // Update hidden input before form submission
            $('form#post').on('submit', function() {
                // Make sure current orbit is saved to hidden input
                $('#w3dz_camera_orbit').val(self.currentOrbit);
            });
        },
        
        /**
         * Save current camera view
         */
        saveCurrentView: function() {
            console.log('💾 Saving camera view:', this.currentOrbit);
            
            // Update hidden input
            $('#w3dz_camera_orbit').val(this.currentOrbit);
            
            // Visual feedback
            const $btn = $('.w3dz-save-camera-view');
            const originalHTML = $btn.html();
            
            $btn.prop('disabled', true)
                .html('<span class="dashicons dashicons-yes" style="margin-top: 4px;"></span> Saved!')
                .css('background-color', '#46b450');
            
            setTimeout(function() {
                $btn.prop('disabled', false)
                    .html(originalHTML)
                    .css('background-color', '');
            }, 2000);
            
            // Show success notice
            const message = w3dzCameraData && w3dzCameraData.strings && w3dzCameraData.strings.saveSuccess 
                ? w3dzCameraData.strings.saveSuccess 
                : 'Camera angle saved! Remember to click "Update" to save the product.';
            
            this.showNotice('success', message);
        },
        
        /**
         * Reset to front view
         */
        resetToFrontView: function() {
            console.log('🔄 Resetting to front view...');
            
            const defaultOrbit = '0deg 75deg 3m';
            this.setOrbit(defaultOrbit);
        },
        
        /**
         * Set camera orbit programmatically
         * 
         * @param {string} orbit Orbit string (e.g., "45deg 75deg 3m")
         */
        setOrbit: function(orbit) {
            if (!this.modelViewer || !this.isInitialized) {
                console.warn('⚠️ Model viewer not ready');
                return;
            }
            
            console.log('🎯 Setting orbit to:', orbit);
            
            // Set camera orbit
            this.modelViewer.setAttribute('camera-orbit', orbit);
            
            // Update current orbit
            this.currentOrbit = orbit;
            
            // Update display
            this.updateOrbitDisplay();
            
            // Visual feedback on preset buttons
            $('.w3dz-preset-btn-visual').each(function() {
                if ($(this).data('orbit') === orbit) {
                    $(this).css('transform', 'scale(0.95)');
                    setTimeout(() => {
                        $(this).css('transform', '');
                    }, 150);
                }
            });
        },
        
        /**
         * Show admin notice
         * 
         * @param {string} type 'success' | 'error' | 'warning' | 'info'
         * @param {string} message Notice message
         */
        showNotice: function(type, message) {
            // Create notice element
            const $notice = $('<div>')
                .addClass('notice notice-' + type + ' is-dismissible')
                .html('<p>' + message + '</p>')
                .css({
                    'position': 'fixed',
                    'top': '32px',
                    'right': '20px',
                    'z-index': '999999',
                    'max-width': '400px',
                    'animation': 'slideInRight 0.3s ease-out'
                });
            
            // Add to page
            $('body').append($notice);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Dismiss button
            $notice.on('click', '.notice-dismiss', function() {
                $notice.remove();
            });
        },
        
        /**
         * Get current camera info for debugging
         * 
         * @return {object}
         */
        getCurrentCameraInfo: function() {
            if (!this.modelViewer || !this.isInitialized) {
                return null;
            }
            
            const orbit = this.modelViewer.getCameraOrbit();
            const target = this.modelViewer.getCameraTarget();
            const fov = this.modelViewer.getFieldOfView();
            
            return {
                orbit: this.formatOrbit(orbit),
                orbitRaw: orbit,
                target: target,
                fov: fov,
                isLoaded: this.modelViewer.loaded,
                modelUrl: this.glbUrl
            };
        },
        
        /**
         * Cleanup on page unload
         */
        cleanup: function() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
            }
        }
    };
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        W3DZ_Camera_Visual.init();
    });
    
    /**
     * Cleanup on page unload
     */
    $(window).on('beforeunload', function() {
        W3DZ_Camera_Visual.cleanup();
    });
    
    // Make it globally accessible for debugging
    window.W3DZ_Camera_Visual = W3DZ_Camera_Visual;
    
    // Add CSS animation for notice
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
    
})(jQuery);