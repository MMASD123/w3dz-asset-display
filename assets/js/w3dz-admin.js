/**
 * Admin JavaScript for WooCommerce 3D Asset Display Zyne
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Toggle visibility of fields based on model type selection
        $('#w3dz_model_type').on('change', function() {
            const selectedType = $(this).val();
            
            $('#w3dz_3d_model_fields').hide();
            $('#w3dz_360_images_fields').hide();
            
            if (selectedType === '3d_model') {
                $('#w3dz_3d_model_fields').show();
            } else if (selectedType === '360_images') {
                $('#w3dz_360_images_fields').show();
            }
        }).trigger('change');
        
        
        // Media uploader for single files (GLB, GLTF, USDZ, Poster)
        $('.w3dz-upload-file-btn').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const targetInput = $('#' + button.data('target'));
            
            // Create media frame
            const frame = wp.media({
                title: 'Select or Upload 3D Model File',
                button: {
                    text: 'Use this file'
                },
                multiple: false
            });
            
            // When file is selected
            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                targetInput.val(attachment.url);
            });
            
            frame.open();
        });
        
        
        // Media uploader for poster images
        $('.w3dz-upload-image-btn').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const targetInput = $('#' + button.data('target'));
            
            // Create media frame
            const frame = wp.media({
                title: 'Select Poster Image',
                button: {
                    text: 'Use this image'
                },
                library: {
                    type: 'image'
                },
                multiple: false
            });
            
            // When image is selected
            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                targetInput.val(attachment.url);
            });
            
            frame.open();
        });
        
        
        // Media uploader for 360 images (multiple selection)
        let w3dz360Frame;
        
        $('.w3dz-select-360-images').on('click', function(e) {
            e.preventDefault();
            
            // If frame already exists, reopen it
            if (w3dz360Frame) {
                w3dz360Frame.open();
                return;
            }
            
            // Create media frame
            w3dz360Frame = wp.media({
                title: 'Select 360° Images',
                button: {
                    text: 'Use these images'
                },
                library: {
                    type: 'image'
                },
                multiple: true
            });
            
            // Set pre-selected images if any
            w3dz360Frame.on('open', function() {
                const selection = w3dz360Frame.state().get('selection');
                const currentIds = $('#w3dz_360_images').val();
                
                if (currentIds) {
                    const ids = currentIds.split(',');
                    ids.forEach(function(id) {
                        const attachment = wp.media.attachment(id);
                        attachment.fetch();
                        selection.add(attachment ? [attachment] : []);
                    });
                }
            });
            
            // When images are selected
            w3dz360Frame.on('select', function() {
                const attachments = w3dz360Frame.state().get('selection').toJSON();
                const ids = [];
                const $preview = $('#w3dz_360_preview');
                
                $preview.empty();
                
                attachments.forEach(function(attachment) {
                    ids.push(attachment.id);
                    
                    // Add preview thumbnail
                    const thumb = `
                        <div class="w3dz-360-thumb" data-id="${attachment.id}">
                            <img src="${attachment.sizes.thumbnail.url}" />
                            <span class="w3dz-remove-image">×</span>
                        </div>
                    `;
                    $preview.append(thumb);
                });
                
                // Update hidden input with comma-separated IDs
                $('#w3dz_360_images').val(ids.join(','));
            });
            
            w3dz360Frame.open();
        });
        
        
        // Remove individual 360 image
        $(document).on('click', '.w3dz-remove-image', function() {
            const $thumb = $(this).closest('.w3dz-360-thumb');
            const imageId = $thumb.data('id');
            const currentIds = $('#w3dz_360_images').val().split(',');
            
            // Remove this ID from array
            const newIds = currentIds.filter(id => id != imageId);
            
            $('#w3dz_360_images').val(newIds.join(','));
            $thumb.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        
        // Make 360 images sortable
        $('#w3dz_360_preview').sortable({
            items: '.w3dz-360-thumb',
            cursor: 'move',
            placeholder: 'w3dz-360-thumb-placeholder',
            update: function() {
                const ids = [];
                $('#w3dz_360_preview .w3dz-360-thumb').each(function() {
                    ids.push($(this).data('id'));
                });
                $('#w3dz_360_images').val(ids.join(','));
            }
        });

        // Show/hide camera settings based on model type
        $('#w3dz_model_type').on('change', function() {
            const selectedType = $(this).val();
            if (selectedType === '3d_model') {
                $('.w3dz-camera-settings').show();
            } else {
                $('.w3dz-camera-settings').hide();
            }
        });
    });
    
})(jQuery);