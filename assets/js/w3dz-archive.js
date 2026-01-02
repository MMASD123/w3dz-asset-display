/**
 * Archive/List page JavaScript for WooCommerce 3D Asset Display Zyne
 * Handles hover preview interactions on product listing pages
 * ADDED: 360 degree image auto-play on hover
 * FIXED: Storefront theme compatibility
 */

(function($) {
    'use strict';
    
    // Check if settings are available
    if (typeof w3dzArchiveSettings === 'undefined') {
        console.error('W3DZ Archive: Settings not loaded');
        return;
    }
    
    const enableAutoRotate = (w3dzArchiveSettings.enableAutoRotate == true);
    const rotationSpeed = w3dzArchiveSettings.rotationSpeed || '30deg';
    
    // Read 360 auto-play settings
    const enable360AutoplayHover = (w3dzArchiveSettings.enable360AutoplayHover == true);
    const playbackSpeed = w3dzArchiveSettings.playbackSpeed || 100;
    const loopInfinite = (w3dzArchiveSettings.loopInfinite == true);
    
    console.log('W3DZ Archive: 360 auto-play hover enabled:', enable360AutoplayHover);
    
    const W3DZArchive = {
        
        // Configuration
        maxConcurrent: 1,
        showProgress: true,
        progressColor: '#0073aa',
        
        // State management
        activeViewers: 0,
        loadingQueue: [],
        loadedProducts: new Set(),
        intersectionObserver: null,
        
        // 360 degree viewer state
        active360Viewers: {},
        
        /**
         * Initialize the archive viewer
         */
        init: function() {
            // Load settings
            this.maxConcurrent = w3dzArchiveSettings.maxConcurrent || 1;
            this.showProgress = w3dzArchiveSettings.showProgress !== false;
            this.progressColor = w3dzArchiveSettings.progressColor || '#0073aa';
            
            // Wait for model-viewer to be ready
            this.waitForModelViewer();
        },
        
        /**
         * Wait for model-viewer custom element to be defined
         */
        waitForModelViewer: function() {
            const self = this;
            let attempts = 0;
            const maxAttempts = 100;
            
            const checkReady = function() {
                attempts++;
                
                if (attempts > maxAttempts) {
                    console.error('Model-viewer failed to load after ' + maxAttempts + ' attempts');
                    return;
                }
                
                if (typeof customElements === 'undefined' || !customElements.get('model-viewer')) {
                    setTimeout(checkReady, 50);
                    return;
                }
                
                self.setupIntersectionObserver();
                self.bindEvents();
            };
            
            checkReady();
        },
        
        /**
         * Setup Intersection Observer for performance optimization
         * Only enable hover for products in viewport
         */
        setupIntersectionObserver: function() {
            const self = this;
            
            // Check if IntersectionObserver is supported
            if (!('IntersectionObserver' in window)) {
                $('.w3dz-product-image-wrapper').data('visible', true);
                return;
            }
            
            // Create observer
            this.intersectionObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    const $wrapper = $(entry.target);
                    
                    if (entry.isIntersecting) {
                        $wrapper.data('visible', true);
                    } else {
                        $wrapper.data('visible', false);
                        
                        // Clean up 3D viewer
                        if ($wrapper.data('loaded')) {
                            self.cleanup($wrapper);
                        }
                        
                        // Stop 360 auto-play
                        const wrapperId = $wrapper.attr('id') || $wrapper.data('product-id');
                        if (wrapperId && self.active360Viewers[wrapperId]) {
                            self.stop360AutoPlay(wrapperId);
                        }
                    }
                });
            }, {
                rootMargin: '100px',
                threshold: 0.1
            });
            
            // Observe all product wrappers
            $('.w3dz-product-image-wrapper').each(function() {
                self.intersectionObserver.observe(this);
            });
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;
            
            $(document).on('mouseenter', '.w3dz-product-image-wrapper', function() {
                self.onProductHover($(this));
            });
            
            $(document).on('mouseleave', '.w3dz-product-image-wrapper', function() {
                self.onProductLeave($(this));
            });
            
            $(document).on('dragstart', '.w3dz-product-image-wrapper, .w3dz-product-image-wrapper *', function(e) {
                const $wrapper = $(this).closest('.w3dz-product-image-wrapper');
                if ($wrapper.data('loaded') || $wrapper.data('loading')) {
                    e.preventDefault();
                    return false;
                }
            });
            
            $(document).on('click', '.w3dz-product-image-wrapper a', function(e) {
                const $wrapper = $(this).closest('.w3dz-product-image-wrapper');
                if ($wrapper.data('loaded')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });
        },
        
        /**
         * Handle mouse hover over product
         */
        onProductHover: function($wrapper) {
            if ($wrapper.data('visible') === false) {
                return;
            }
            
            const modelType = $wrapper.data('model-type');
            
            // Handle 3D model hover
            if (modelType === '3d_model' || !modelType) {
                this.handle3DHover($wrapper);
            } 
            // Handle 360 images hover
            else if (modelType === '360_images') {
                this.handle360Hover($wrapper);
            }
        },
        
        /**
         * Handle 3D model hover (existing functionality)
         */
        handle3DHover: function($wrapper) {
            if ($wrapper.data('loading') || $wrapper.data('loaded')) {
                if ($wrapper.data('loaded')) {
                    $wrapper.addClass('w3dz-showing-3d');
                    $wrapper.find('.w3dz-viewer-overlay').addClass('w3dz-visible').fadeIn(300);
                }
                return;
            }
            
            const glbUrl = $wrapper.data('glb-url');
            if (!glbUrl) {
                return;
            }
            
            if (this.activeViewers >= this.maxConcurrent) {
                if (!this.loadingQueue.includes($wrapper[0])) {
                    this.loadingQueue.push($wrapper[0]);
                }
                return;
            }
            
            this.startLoading($wrapper);
        },
        
        /**
         * Handle 360 images hover
         */
        handle360Hover: function($wrapper) {
            const self = this;
            const wrapperId = $wrapper.attr('id') || $wrapper.data('product-id') || 'product-' + Math.random().toString(36).substr(2, 9);
            
            // Set ID if not exists
            if (!$wrapper.attr('id')) {
                $wrapper.attr('id', wrapperId);
            }
            
            // Check if already initialized
            if (self.active360Viewers[wrapperId]) {
                // Show overlay and resume auto-play if enabled
                $wrapper.addClass('w3dz-showing-360');
                $wrapper.find('.w3dz-360-overlay').addClass('w3dz-visible').fadeIn(300);
                
                if (enable360AutoplayHover) {
                    self.start360AutoPlay(wrapperId);
                }
                return;
            }
            
            // Get 360 images data
            const images360Data = $wrapper.data('images-360');
            if (!images360Data) {
                console.log('W3DZ Archive: No 360 images data found for product');
                return;
            }
            
            // Parse images array
            let images360 = [];
            try {
                if (typeof images360Data === 'string') {
                    images360 = JSON.parse(images360Data);
                } else if (Array.isArray(images360Data)) {
                    images360 = images360Data;
                }
            } catch (e) {
                console.error('W3DZ Archive: Failed to parse 360 images data:', e);
                return;
            }
            
            if (!images360 || images360.length === 0) {
                console.log('W3DZ Archive: No 360 images found');
                return;
            }
            
            console.log('W3DZ Archive: Initializing 360 viewer for product with', images360.length, 'images');
            
            // Initialize 360 viewer state
            self.active360Viewers[wrapperId] = {
                images: images360,
                currentIndex: 0,
                isPlaying: false,
                interval: null,
                imagesLoaded: false
            };
            
            // Show overlay
            $wrapper.addClass('w3dz-showing-360');
            const $overlay = $wrapper.find('.w3dz-360-overlay');
            
            if ($overlay.length === 0) {
                // Create overlay if doesn't exist
                $wrapper.append('<div class="w3dz-360-overlay"><img class="w3dz-360-image" src="" alt="360 view" /></div>');
            }
            
            $overlay.addClass('w3dz-visible').fadeIn(300);
            
            // Preload images and start playing
            this.preload360ImagesArchive(wrapperId, function(success) {
                if (success && $wrapper.is(':hover')) {
                    self.display360ImageArchive(wrapperId, 0);
                    
                    if (enable360AutoplayHover) {
                        self.start360AutoPlay(wrapperId);
                    }
                }
            });
        },
        
        /**
         * Handle mouse leave from product
         */
        onProductLeave: function($wrapper) {
            const self = this;
            const modelType = $wrapper.data('model-type');
            
            // Handle 3D model leave
            if (modelType === '3d_model' || !modelType) {
                if (!$wrapper.data('loaded')) {
                    return;
                }
                
                setTimeout(function() {
                    if (!$wrapper.is(':hover')) {
                        $wrapper.find('.w3dz-viewer-overlay').removeClass('w3dz-visible').fadeOut(300);
                        $wrapper.removeClass('w3dz-showing-3d');
                    }
                }, 300);
            }
            // Handle 360 images leave
            else if (modelType === '360_images') {
                const wrapperId = $wrapper.attr('id') || $wrapper.data('product-id');
                
                setTimeout(function() {
                    if (!$wrapper.is(':hover')) {
                        // Stop auto-play
                        self.stop360AutoPlay(wrapperId);
                        
                        // Hide overlay
                        $wrapper.find('.w3dz-360-overlay').removeClass('w3dz-visible').fadeOut(300);
                        $wrapper.removeClass('w3dz-showing-360');
                        
                        // Reset to first frame
                        if (self.active360Viewers[wrapperId]) {
                            self.display360ImageArchive(wrapperId, 0);
                        }
                    }
                }, 300);
            }
        },
        
        /**
         * Preload 360 images for archive item
         */
        preload360ImagesArchive: function(wrapperId, callback) {
            const self = this;
            const viewer = this.active360Viewers[wrapperId];
            
            if (!viewer) {
                callback(false);
                return;
            }
            
            // Skip if already loaded
            if (viewer.imagesLoaded) {
                callback(true);
                return;
            }
            
            let loadedCount = 0;
            const totalImages = viewer.images.length;
            
            console.log('W3DZ Archive: Preloading', totalImages, 'images for', wrapperId);
            
            viewer.images.forEach(function(src, index) {
                const img = new Image();
                
                img.onload = function() {
                    loadedCount++;
                    
                    if (loadedCount === totalImages) {
                        viewer.imagesLoaded = true;
                        console.log('W3DZ Archive: All images preloaded for', wrapperId);
                        callback(true);
                    }
                };
                
                img.onerror = function() {
                    console.error('W3DZ Archive: Failed to load image', index, 'for', wrapperId);
                    loadedCount++;
                    
                    if (loadedCount === totalImages) {
                        viewer.imagesLoaded = true;
                        callback(true);
                    }
                };
                
                img.src = src;
            });
        },
        
        /**
         * Display 360 image by index for archive item
         */
        display360ImageArchive: function(wrapperId, index) {
            const viewer = this.active360Viewers[wrapperId];
            
            if (!viewer || !viewer.images || index < 0 || index >= viewer.images.length) {
                return;
            }
            
            const $wrapper = $('#' + wrapperId);
            const $image = $wrapper.find('.w3dz-360-image');
            
            if ($image.length === 0) {
                return;
            }
            
            $image.attr('src', viewer.images[index]);
            viewer.currentIndex = index;
        },
        
        /**
         * Start 360 auto-play for archive item
         */
        start360AutoPlay: function(wrapperId) {
            const self = this;
            const viewer = this.active360Viewers[wrapperId];
            
            if (!viewer || viewer.isPlaying) {
                return;
            }
            
            console.log('W3DZ Archive: Starting auto-play for', wrapperId);
            
            viewer.isPlaying = true;
            
            viewer.interval = setInterval(function() {
                let nextIndex = viewer.currentIndex + 1;
                
                // Handle looping
                if (nextIndex >= viewer.images.length) {
                    if (loopInfinite) {
                        nextIndex = 0;
                    } else {
                        // Stop at end
                        self.stop360AutoPlay(wrapperId);
                        return;
                    }
                }
                
                self.display360ImageArchive(wrapperId, nextIndex);
            }, playbackSpeed);
        },
        
        /**
         * Stop 360 auto-play for archive item
         */
        stop360AutoPlay: function(wrapperId) {
            const viewer = this.active360Viewers[wrapperId];
            
            if (!viewer) {
                return;
            }
            
            if (viewer.interval) {
                clearInterval(viewer.interval);
                viewer.interval = null;
                viewer.isPlaying = false;
                console.log('W3DZ Archive: Stopped auto-play for', wrapperId);
            }
        },
        
        /**
         * Start loading 3D model
         */
        startLoading: function($wrapper) {
            const self = this;
            const glbUrl = $wrapper.data('glb-url');
            
            $wrapper.data('loading', true);
            $wrapper.attr('data-loading', 'true');
            this.activeViewers++;
            
            if (this.showProgress) {
                $wrapper.find('.w3dz-hover-progress').fadeIn(200);
            }
            
            $wrapper.addClass('w3dz-showing-3d');
            
            this.createModelViewer($wrapper, glbUrl);
        },
        
        /**
         * Create model-viewer element
         * FIXED: Storefront theme compatibility
         */
        createModelViewer: function($wrapper, glbUrl) {
            const self = this;
            const $overlay = $wrapper.find('.w3dz-viewer-overlay');
            
            $overlay.empty();
            
            const modelViewer = document.createElement('model-viewer');
            modelViewer.setAttribute('src', glbUrl);
            modelViewer.setAttribute('alt', '3D Product Model');
            
            if (enableAutoRotate) {
                modelViewer.setAttribute('auto-rotate', '');
                modelViewer.setAttribute('auto-rotate-delay', '0');
                modelViewer.setAttribute('rotation-per-second', rotationSpeed);
            }
            
            modelViewer.setAttribute('shadow-intensity', '1');
            modelViewer.setAttribute('interaction-prompt', 'none');
            modelViewer.style.width = '100%';
            modelViewer.style.height = '100%';
            
            // Get product-specific camera settings
            let cameraOrbit = $wrapper.attr('data-camera-orbit');
            
            if (!cameraOrbit && typeof w3dzArchiveSettings !== 'undefined' && w3dzArchiveSettings.defaultCameraOrbit) {
                cameraOrbit = w3dzArchiveSettings.defaultCameraOrbit;
            }
            
            if (!cameraOrbit) {
                cameraOrbit = '0deg 75deg 105%';
            }
            
            modelViewer.setAttribute('camera-orbit', cameraOrbit);
            modelViewer.setAttribute('camera-target', 'auto auto auto');
            modelViewer.setAttribute('field-of-view', '30deg');
            modelViewer.setAttribute('min-field-of-view', '10deg');
            modelViewer.setAttribute('max-field-of-view', '90deg');
            
            // Calculate zoom limits based on camera distance
            const radiusMatch = cameraOrbit.match(/(\d+(?:\.\d+)?)m/);
            const baseRadius = radiusMatch ? parseFloat(radiusMatch[1]) : 3;
            
            modelViewer.setAttribute('min-camera-orbit', 'auto auto ' + Math.max(0.5, baseRadius * 0.3) + 'm');
            modelViewer.setAttribute('max-camera-orbit', 'auto auto ' + Math.min(50, baseRadius * 3) + 'm');
            
            // Storefront compatibility fix: Use unified load completion function
            let loadCompleted = false;
            let loadTimeout = null;
            
            // Unified load completion function
            function completeLoading() {
                if (loadCompleted) return; // Prevent duplicate execution
                loadCompleted = true;
                
                console.log('Model loading completed');
                
                if (loadTimeout) {
                    clearTimeout(loadTimeout);
                }
                
                $wrapper.find('.w3dz-hover-progress').fadeOut(300);
                
                $wrapper.data('loading', false);
                $wrapper.data('loaded', true);
                $wrapper.attr('data-loading', 'false');
                $wrapper.attr('data-loaded', 'true');
                
                // Remove waiting cursor
                $wrapper.css('cursor', '');
                
                $overlay.addClass('w3dz-visible').fadeIn(300);
                
                self.activeViewers--;
                self.processQueue();
            }
            
            // Progress event
            modelViewer.addEventListener('progress', function(event) {
                const percent = event.detail.totalProgress * 100;
                $wrapper.find('.w3dz-progress-bar').css('width', percent + '%');
                
                // Storefront fix: When progress reaches 100% and load hasn't fired, force completion with delay
                if (percent >= 100 && !loadCompleted) {
                    // Clear previous timeout
                    if (loadTimeout) {
                        clearTimeout(loadTimeout);
                    }
                    
                    // Wait 800ms for load event, force completion if not fired
                    loadTimeout = setTimeout(function() {
                        if (!loadCompleted) {
                            console.log('Load event not fired, forcing completion (Storefront compatibility)');
                            completeLoading();
                        }
                    }, 800);
                }
            });
            
            // Load complete
            modelViewer.addEventListener('load', function() {
                console.log('Model load event fired');
                completeLoading();
            });
            
            // Error event
            modelViewer.addEventListener('error', function(event) {
                console.error('Failed to load 3D model:', event);
                
                loadCompleted = true; // Prevent timeout from firing again
                if (loadTimeout) {
                    clearTimeout(loadTimeout);
                }
                
                $wrapper.find('.w3dz-hover-progress').fadeOut(300);
                $wrapper.removeClass('w3dz-showing-3d');
                
                $wrapper.data('loading', false);
                $wrapper.data('loaded', false);
                $wrapper.attr('data-loading', 'false');
                $wrapper.attr('data-loaded', 'false');
                
                // Remove waiting cursor
                $wrapper.css('cursor', '');
                
                self.activeViewers--;
                self.processQueue();
            });
            
            $overlay.append(modelViewer);
        },
        
        /**
         * Cleanup - hide 3D viewer but keep it loaded
         */
        cleanup: function($wrapper) {
            const $overlay = $wrapper.find('.w3dz-viewer-overlay');
            
            $overlay.removeClass('w3dz-visible').fadeOut(300);
            $wrapper.removeClass('w3dz-showing-3d');
            $wrapper.find('.w3dz-hover-progress').hide();
        },
        
        /**
         * Full cleanup - completely remove model viewer
         */
        fullCleanup: function($wrapper) {
            const $overlay = $wrapper.find('.w3dz-viewer-overlay');
            
            $overlay.removeClass('w3dz-visible').fadeOut(300, function() {
                $(this).empty();
            });
            
            $wrapper.removeClass('w3dz-showing-3d');
            $wrapper.find('.w3dz-hover-progress').hide();
            
            $wrapper.data('loading', false);
            $wrapper.data('loaded', false);
            $wrapper.attr('data-loading', 'false');
            $wrapper.attr('data-loaded', 'false');
            
            $wrapper.css('cursor', '');
        },
        
        /**
         * Process loading queue
         */
        processQueue: function() {
            if (this.loadingQueue.length > 0 && this.activeViewers < this.maxConcurrent) {
                const nextElement = this.loadingQueue.shift();
                const $next = $(nextElement);
                
                if ($next.is(':hover')) {
                    this.startLoading($next);
                } else {
                    this.processQueue();
                }
            }
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        W3DZArchive.init();
    });
    
    // Re-initialize on AJAX product load
    $(document).on('updated_wc_div', function() {
        W3DZArchive.setupIntersectionObserver();
    });
    
})(jQuery);