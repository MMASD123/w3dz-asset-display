/**
 * Frontend JavaScript for WooCommerce 3D Asset Display Zyne
 * FIXED: 360 degree image viewer now works correctly with all frames
 * ADDED: 360 degree auto-play functionality
 */

(function($) {
    'use strict';
    
    // Read auto-rotation settings from PHP
    const enableAutoRotate = (typeof w3dzSettings !== 'undefined' && w3dzSettings.enableAutoRotate == true);
    const rotationSpeed = (typeof w3dzSettings !== 'undefined' && w3dzSettings.rotationSpeed) || '30deg';
    
    console.log('W3DZ: Auto-rotate settings:', { enableAutoRotate, rotationSpeed });
    
    // Read 360 degree auto-play settings from PHP
    const enable360Autoplay = (typeof w3dz360Settings !== 'undefined' && w3dz360Settings.enable360Autoplay == true);
    const playbackSpeed = (typeof w3dz360Settings !== 'undefined' && w3dz360Settings.playbackSpeed) || 100;
    const loopInfinite = (typeof w3dz360Settings !== 'undefined' && w3dz360Settings.loopInfinite == true);
    
    console.log('W3DZ: 360 auto-play settings:', { enable360Autoplay, playbackSpeed, loopInfinite });
    
    // CRITICAL: Check if w3dzData is available
    if (typeof w3dzData === 'undefined') {
        console.error('W3DZ: CRITICAL ERROR - w3dzData is not defined!');
        console.error('W3DZ: Plugin may not be loaded correctly or PHP localization failed.');
        return;
    }
    
    // Log what data we received from PHP
    console.log('W3DZ: Received data from PHP:', w3dzData);
    
    const W3DZViewer = {
        
        modal: null,
        container: null,
        modelViewer: null,
        images360: [],
        currentImageIndex: 0,
        isDragging: false,
        startX: 0,
        imagesLoaded: false,
        initAttempts: 0,
        maxAttempts: 100,
        
        // 360 Auto-play properties
        isAutoPlaying: false,
        autoPlayInterval: null,
        autoPlayDirection: 1, // 1 for forward, -1 for backward
        hasPlayedOnce: false,
        
        /**
         * Initialize the viewer
         */
        init: function() {
            console.log('W3DZ: Initializing viewer...');
            
            this.modal = $('#w3dz-viewer-modal');
            this.container = $('#w3dz-viewer-container');
            
            if (this.modal.length === 0) {
                console.error('W3DZ: Modal element not found!');
                return;
            }
            
            // Bind events
            this.bindEvents();
            
            // CRITICAL: Validate and load 360 images
            if (w3dzData.modelType === '360_images') {
                console.log('W3DZ: Model type is 360_images');
                
                // Check if images360 exists and is an array
                if (!w3dzData.images360) {
                    console.error('W3DZ: images360 is undefined in w3dzData!');
                    console.error('W3DZ: This means PHP did not pass image data correctly.');
                } else if (!Array.isArray(w3dzData.images360)) {
                    console.error('W3DZ: images360 is not an array:', typeof w3dzData.images360);
                } else if (w3dzData.images360.length === 0) {
                    console.error('W3DZ: images360 array is empty! No images uploaded?');
                } else {
                    this.images360 = w3dzData.images360;
                    console.log('W3DZ: Successfully loaded', this.images360.length, '360 images');
                    console.log('W3DZ: First image URL:', this.images360[0]);
                    console.log('W3DZ: Last image URL:', this.images360[this.images360.length - 1]);
                }
            }
            
            // Check if Gallery exists (for info only)
            if (w3dzData.hasGallery) {
                console.log('W3DZ: Gallery detected with', w3dzData.galleryItems.length, 'variants');
            }
            
            console.log('W3DZ: Initialization complete');
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;
            
            // Open viewer button
            $('.w3dz-open-viewer-btn').on('click', function() {
                const $btn = $(this);
                console.log('W3DZ: Button clicked');
                console.log('W3DZ: Button element:', $btn[0]);
                console.log('W3DZ: Button classes:', $btn.attr('class'));
                console.log('W3DZ: Button data-type attribute:', $btn.attr('data-type'));
                console.log('W3DZ: Button .data("type"):', $btn.data('type'));
                
                const type = $btn.attr('data-type') || $btn.data('type');
                console.log('W3DZ: Final type value:', type);
                
                if (!type) {
                    console.error('W3DZ: ERROR - No type found on button!');
                    alert('Error: Button is missing data-type attribute. Check HTML.');
                    return;
                }
                
                self.openViewer(type);
            });
            
            // Close modal
            $('.w3dz-modal-close, .w3dz-modal-overlay').on('click', function() {
                console.log('W3DZ: Closing viewer');
                self.closeViewer();
            });
            
            // Prevent closing when clicking inside content
            $('.w3dz-modal-content').on('click', function(e) {
                e.stopPropagation();
            });
            
            // ESC key to close
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.modal.is(':visible')) {
                    console.log('W3DZ: ESC key pressed, closing viewer');
                    self.closeViewer();
                }
            });
        },
        
        /**
         * Open viewer modal
         */
        openViewer: function(type) {
            console.log('W3DZ: openViewer called with type:', type);
            
            // Handle undefined type
            if (!type) {
                console.error('W3DZ: Type is undefined or empty!');
                alert('Error: Viewer type not specified');
                return;
            }
            
            // Trim whitespace
            type = String(type).trim();
            console.log('W3DZ: Type after trim:', type);
            
            this.modal.fadeIn(300);
            $('body').addClass('w3dz-modal-open');
            
            if (type === '3d' || type === '3d_model') {
                console.log('W3DZ: Initializing 3D viewer');
                this.init3DViewer();
            } else if (type === '360' || type === '360_images') {
                console.log('W3DZ: Initializing 360 viewer');
                this.init360Viewer();
            } else {
                console.error('W3DZ: Unknown viewer type:', type);
                alert('Error: Unknown viewer type: ' + type);
            }
        },
        
        /**
         * Close viewer modal
         */
        closeViewer: function() {
            this.modal.fadeOut(300);
            $('body').removeClass('w3dz-modal-open');
            
            // Clean up 3D viewer
            if (this.modelViewer) {
                this.modelViewer.remove();
                this.modelViewer = null;
                console.log('W3DZ: 3D viewer cleaned up');
            }
            
            // Stop 360 auto-play
            this.stop360AutoPlay();
            
            // Reset 360 viewer state
            this.isDragging = false;
            this.currentImageIndex = 0;
            this.hasPlayedOnce = false;
            
            // Reset states
            this.initAttempts = 0;
            
            console.log('W3DZ: Viewer closed');
        },
        
        /**
         * Initialize 3D Model Viewer
         */
        init3DViewer: function() {
            const self = this;
            const $viewer = $('#w3dz-3d-viewer');
            $viewer.empty();
            
            if (!w3dzData.glbUrl) {
                console.error('W3DZ: No GLB URL provided');
                $viewer.html('<p style="text-align: center; padding: 50px; color: white;">No 3D model available</p>');
                return;
            }
            
            // Reset attempt counter
            this.initAttempts = 0;
            
            // Wait for model-viewer to be defined
            const initModelViewer = function() {
                self.initAttempts++;
                
                if (self.initAttempts > self.maxAttempts) {
                    console.error('W3DZ: Failed to load model-viewer after ' + self.maxAttempts + ' attempts');
                    $viewer.html('<p style="text-align: center; padding: 50px; color: white;">Failed to load 3D viewer. Please refresh the page.</p>');
                    return;
                }
                
                if (typeof customElements === 'undefined') {
                    console.log('W3DZ: Waiting for customElements API... (attempt ' + self.initAttempts + ')');
                    setTimeout(initModelViewer, 50);
                    return;
                }
                
                if (!customElements.get('model-viewer')) {
                    console.log('W3DZ: Waiting for model-viewer to register... (attempt ' + self.initAttempts + ')');
                    setTimeout(initModelViewer, 50);
                    return;
                }
                
                console.log('W3DZ: Model Viewer is ready! Starting initialization...');
                self.createModelViewer($viewer);
            };
            
            initModelViewer();
        },
        
        /**
         * Create Model Viewer element
         */
        createModelViewer: function($viewer) {
            const self = this;
            
            console.log('W3DZ: Creating model-viewer element...');
            console.log('W3DZ: GLB URL:', w3dzData.glbUrl);
            console.log('W3DZ: Camera Orbit:', w3dzData.cameraOrbit);
            
            const modelViewer = document.createElement('model-viewer');
            modelViewer.setAttribute('src', w3dzData.glbUrl);
            modelViewer.setAttribute('alt', 'Product 3D Model');
            modelViewer.setAttribute('ar', '');
            modelViewer.setAttribute('ar-modes', 'webxr scene-viewer quick-look');
            modelViewer.setAttribute('camera-controls', '');
            modelViewer.setAttribute('shadow-intensity', '1');
            modelViewer.setAttribute('environment-image', 'neutral');
            modelViewer.setAttribute('exposure', '1');
            
            if (enableAutoRotate) {
                modelViewer.setAttribute('auto-rotate', '');
                if (rotationSpeed) {
                    modelViewer.setAttribute('rotation-per-second', rotationSpeed);
                }
            }
            
            if (w3dzData.usdzUrl) {
                modelViewer.setAttribute('ios-src', w3dzData.usdzUrl);
            }
            
            if (w3dzData.posterUrl) {
                modelViewer.setAttribute('poster', w3dzData.posterUrl);
            }
            
            if (w3dzData.cameraOrbit) {
                modelViewer.setAttribute('camera-orbit', w3dzData.cameraOrbit);
            }
            
            $viewer.append(modelViewer);
            this.modelViewer = modelViewer;
            
            modelViewer.addEventListener('load', function() {
                console.log('W3DZ: 3D model loaded successfully');
            });
            
            modelViewer.addEventListener('error', function(event) {
                console.error('W3DZ: Error loading 3D model:', event);
            });
            
            $(document).trigger('w3dz:modelViewerReady', [modelViewer]);
            console.log('W3DZ: Model viewer created and ready');
        },
        
        /**
         * Initialize 360 Image Viewer - FULLY FIXED
         */
        init360Viewer: function() {
            const self = this;
            
            console.log('W3DZ: init360Viewer called');
            console.log('W3DZ: images360 array:', this.images360);
            console.log('W3DZ: images360 length:', this.images360.length);
            
            // CRITICAL: Check if images exist
            if (!this.images360 || this.images360.length === 0) {
                console.error('W3DZ: CRITICAL - No 360 images available!');
                console.error('W3DZ: images360:', this.images360);
                
                const errorMsg = this.images360 === undefined 
                    ? 'No 360 images data received from server. Check PHP configuration.'
                    : 'No 360 images uploaded for this product. Please add images in admin.';
                
                $('#w3dz-360-viewer').html(
                    '<div style="text-align: center; padding: 50px; color: white;">' +
                    '<p style="font-size: 18px; margin-bottom: 10px;">No 360° Images</p>' +
                    '<p style="font-size: 14px; opacity: 0.8;">' + errorMsg + '</p>' +
                    '</div>'
                );
                return;
            }
            
            console.log('W3DZ: 360 images validated, count:', this.images360.length);
            console.log('W3DZ: Sample URLs:');
            console.log('  - First:', this.images360[0]);
            console.log('  - Last:', this.images360[this.images360.length - 1]);
            
            const $image = $('#w3dz-360-image');
            const $spinner = $('.w3dz-360-spinner');
            const $controls = $('.w3dz-360-controls');
            
            // CRITICAL: Ensure elements exist
            if ($image.length === 0) {
                console.error('W3DZ: CRITICAL - #w3dz-360-image element not found!');
                return;
            }
            
            console.log('W3DZ: Elements found - image:', $image.length, 'spinner:', $spinner.length);
            
            // Clear current state
            $image.removeClass('w3dz-loading w3dz-loaded').attr('src', '');
            this.currentImageIndex = 0;
            
            // Show loading spinner
            if (!this.imagesLoaded) {
                console.log('W3DZ: Showing spinner, starting preload...');
                $spinner.show();
                $controls.hide();
                
                this.preload360Images(function(successCount, errorCount) {
                    console.log('W3DZ: Preload callback - success:', successCount, 'errors:', errorCount);
                    $spinner.hide();
                    $controls.show();
                    
                    if (successCount > 0) {
                        console.log('W3DZ: Displaying first image...');
                        self.display360Image(0);
                        self.setup360Interaction();
                        
                        // Start auto-play if enabled
                        if (enable360Autoplay) {
                            console.log('W3DZ: Starting 360 auto-play');
                            self.start360AutoPlay();
                        }
                    } else {
                        console.error('W3DZ: All images failed to load!');
                        $('#w3dz-360-viewer').html(
                            '<p style="text-align: center; padding: 50px; color: white;">' +
                            'Failed to load 360° images. Please check image URLs.</p>'
                        );
                    }
                });
            } else {
                console.log('W3DZ: Images already preloaded, displaying immediately');
                $spinner.hide();
                $controls.show();
                this.display360Image(0);
                this.setup360Interaction();
                
                // Start auto-play if enabled
                if (enable360Autoplay) {
                    console.log('W3DZ: Starting 360 auto-play');
                    this.start360AutoPlay();
                }
            }
        },
        
        /**
         * Start 360 degree auto-play
         */
        start360AutoPlay: function() {
            const self = this;
            
            // Don't start if already playing
            if (this.isAutoPlaying) {
                return;
            }
            
            // Don't start if no images
            if (!this.images360 || this.images360.length === 0) {
                console.error('W3DZ: Cannot start auto-play, no images loaded');
                return;
            }
            
            console.log('W3DZ: Starting auto-play with speed:', playbackSpeed, 'ms/frame');
            
            this.isAutoPlaying = true;
            this.autoPlayDirection = 1; // Always start forward
            
            this.autoPlayInterval = setInterval(function() {
                let nextIndex = self.currentImageIndex + self.autoPlayDirection;
                
                // Handle looping
                if (nextIndex >= self.images360.length) {
                    if (loopInfinite) {
                        // Loop back to start
                        nextIndex = 0;
                        console.log('W3DZ: Auto-play looping back to start');
                    } else {
                        // Stop at end if not infinite loop
                        if (!self.hasPlayedOnce) {
                            self.hasPlayedOnce = true;
                            console.log('W3DZ: Auto-play completed one full rotation, stopping');
                            self.stop360AutoPlay();
                            return;
                        }
                    }
                } else if (nextIndex < 0) {
                    if (loopInfinite) {
                        nextIndex = self.images360.length - 1;
                    } else {
                        nextIndex = 0;
                        if (!self.hasPlayedOnce) {
                            self.hasPlayedOnce = true;
                            console.log('W3DZ: Auto-play completed, stopping');
                            self.stop360AutoPlay();
                            return;
                        }
                    }
                }
                
                self.display360Image(nextIndex);
            }, playbackSpeed);
            
            console.log('W3DZ: Auto-play started');
        },
        
        /**
         * Stop 360 degree auto-play
         */
        stop360AutoPlay: function() {
            if (this.autoPlayInterval) {
                clearInterval(this.autoPlayInterval);
                this.autoPlayInterval = null;
                this.isAutoPlaying = false;
                console.log('W3DZ: Auto-play stopped');
            }
        },
        
        /**
         * Pause 360 degree auto-play (can be resumed)
         */
        pause360AutoPlay: function() {
            if (this.isAutoPlaying) {
                this.stop360AutoPlay();
                console.log('W3DZ: Auto-play paused');
            }
        },
        
        /**
         * Resume 360 degree auto-play
         */
        resume360AutoPlay: function() {
            if (!this.isAutoPlaying && enable360Autoplay) {
                this.start360AutoPlay();
                console.log('W3DZ: Auto-play resumed');
            }
        },
        
        /**
         * Display a 360 image by index
         */
        display360Image: function(index) {
            const self = this;
            const $image = $('#w3dz-360-image');
            
            // Validate index
            if (index < 0 || index >= this.images360.length) {
                console.error('W3DZ: Invalid image index:', index, 'valid range: 0-' + (this.images360.length - 1));
                return;
            }
            
            const imageSrc = this.images360[index];
            
            // Validate URL
            if (!imageSrc || imageSrc === '') {
                console.error('W3DZ: Empty image URL at index:', index);
                return;
            }
            
            // Show loading state
            $image.addClass('w3dz-loading').removeClass('w3dz-loaded');
            
            // Update image source
            $image.attr('src', imageSrc);
            
            // Handle load events
            $image.off('load.w3dz error.w3dz')
                .on('load.w3dz', function() {
                    $image.removeClass('w3dz-loading').addClass('w3dz-loaded');
                    self.currentImageIndex = index;
                })
                .on('error.w3dz', function() {
                    console.error('W3DZ: Failed to load image', index + 1, ':', imageSrc);
                    $image.removeClass('w3dz-loading');
                    
                    // Try next image
                    if (index < self.images360.length - 1) {
                        setTimeout(function() {
                            self.display360Image(index + 1);
                        }, 100);
                    }
                });
        },
        
        /**
         * Preload all 360 images
         */
        preload360Images: function(callback) {
            let loadedCount = 0;
            let errorCount = 0;
            const totalImages = this.images360.length;
            
            console.log('W3DZ: Preloading', totalImages, 'images...');
            
            if (totalImages === 0) {
                console.error('W3DZ: No images to preload!');
                callback(0, 0);
                return;
            }
            
            this.images360.forEach(function(src, index) {
                const img = new Image();
                
                img.onload = function() {
                    loadedCount++;
                    const progress = Math.round((loadedCount / totalImages) * 100);
                    console.log('W3DZ: Loaded', loadedCount + '/' + totalImages, '(' + progress + '%)');
                    
                    if (loadedCount + errorCount === totalImages) {
                        console.log('W3DZ: Preload complete -', loadedCount, 'loaded,', errorCount, 'failed');
                        callback(loadedCount, errorCount);
                    }
                };
                
                img.onerror = function() {
                    errorCount++;
                    console.error('W3DZ: Failed to preload image', (index + 1) + ':', src);
                    
                    if (loadedCount + errorCount === totalImages) {
                        console.log('W3DZ: Preload complete -', loadedCount, 'loaded,', errorCount, 'failed');
                        callback(loadedCount, errorCount);
                    }
                };
                
                img.src = src;
            });
            
            this.imagesLoaded = true;
        },
        
        /**
         * Setup 360 degree interaction (drag to rotate)
         */
        setup360Interaction: function() {
            const self = this;
            const $image = $('#w3dz-360-image');
            const $viewer = $('#w3dz-360-viewer');
            const totalImages = this.images360.length;
            
            console.log('W3DZ: Setting up 360 interaction for', totalImages, 'frames');
            
            // Remove old event handlers
            $viewer.off('mousedown.w3dz touchstart.w3dz mousemove.w3dz touchmove.w3dz mouseup.w3dz touchend.w3dz');
            $(document).off('mousemove.w3dz mouseup.w3dz');
            
            // Mouse/Touch start
            $viewer.on('mousedown.w3dz touchstart.w3dz', function(e) {
                e.preventDefault();
                self.isDragging = true;
                self.startX = e.type === 'touchstart' ? e.touches[0].pageX : e.pageX;
                $image.css('cursor', 'grabbing');
                
                // Pause auto-play when user starts dragging
                self.pause360AutoPlay();
                
                console.log('W3DZ: Drag started at frame', self.currentImageIndex + 1);
            });
            
            // Mouse/Touch move
            $(document).on('mousemove.w3dz', function(e) {
                if (!self.isDragging) return;
                
                const currentX = e.pageX;
                const deltaX = currentX - self.startX;
                const sensitivity = 5;
                
                if (Math.abs(deltaX) > sensitivity) {
                    const steps = Math.floor(Math.abs(deltaX) / sensitivity);
                    const direction = deltaX > 0 ? 1 : -1;
                    
                    let newIndex = self.currentImageIndex + (direction * steps);
                    
                    // Wrap around
                    while (newIndex < 0) newIndex += totalImages;
                    while (newIndex >= totalImages) newIndex -= totalImages;
                    
                    if (newIndex !== self.currentImageIndex) {
                        self.display360Image(newIndex);
                        self.startX = currentX;
                    }
                }
            });
            
            $viewer.on('touchmove.w3dz', function(e) {
                if (!self.isDragging) return;
                e.preventDefault();
                
                const currentX = e.touches[0].pageX;
                const deltaX = currentX - self.startX;
                const sensitivity = 5;
                
                if (Math.abs(deltaX) > sensitivity) {
                    const steps = Math.floor(Math.abs(deltaX) / sensitivity);
                    const direction = deltaX > 0 ? 1 : -1;
                    
                    let newIndex = self.currentImageIndex + (direction * steps);
                    
                    // Wrap around
                    while (newIndex < 0) newIndex += totalImages;
                    while (newIndex >= totalImages) newIndex -= totalImages;
                    
                    if (newIndex !== self.currentImageIndex) {
                        self.display360Image(newIndex);
                        self.startX = currentX;
                    }
                }
            });
            
            // Mouse/Touch end
            $(document).on('mouseup.w3dz', function() {
                if (self.isDragging) {
                    self.isDragging = false;
                    $image.css('cursor', 'grab');
                    
                    // Resume auto-play after user stops dragging
                    setTimeout(function() {
                        if (!self.isDragging) {
                            self.resume360AutoPlay();
                        }
                    }, 500); // Wait 500ms before resuming to avoid accidental resume
                    
                    console.log('W3DZ: Drag ended at frame', self.currentImageIndex + 1);
                }
            });
            
            $viewer.on('touchend.w3dz', function() {
                if (self.isDragging) {
                    self.isDragging = false;
                    $image.css('cursor', 'grab');
                    
                    // Resume auto-play after user stops dragging
                    setTimeout(function() {
                        if (!self.isDragging) {
                            self.resume360AutoPlay();
                        }
                    }, 500);
                }
            });
            
            // Set initial cursor
            $image.css('cursor', 'grab');
            
            console.log('W3DZ: 360 interaction ready - drag to rotate through', totalImages, 'frames');
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        console.log('W3DZ: Document ready, initializing viewer...');
        console.log('W3DZ: Model Type:', w3dzData.modelType);
        W3DZViewer.init();
    });
    
})(jQuery);