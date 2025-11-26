(function($) {
    'use strict';
    
    $(document).ready(function() {
        var $yearSelect = $('#mbd-year');
        var $monthSelect = $('#mbd-month');
        var $downloadBtn = $('#mbd-download-btn');
        var $spinner = $('.spinner');
        var $message = $('#mbd-message');
        var $form = $('#mbd-download-form');
        var $imageCount = $('#mbd-image-count');
        
        // Handle year change
        $yearSelect.on('change', function() {
            var year = $(this).val();
            
            // Reset month select and image count
            $monthSelect.prop('disabled', true).html('<option value="">All Months</option>');
            $imageCount.hide();
            
            if (year) {
                // Load months for selected year
                $.ajax({
                    url: mbd_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mbd_get_months',
                        nonce: mbd_ajax.nonce,
                        year: year
                    },
                    beforeSend: function() {
                        $spinner.addClass('is-active');
                    },
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            $monthSelect.prop('disabled', false);
                            var options = '<option value="">All Months</option>';
                            
                            $.each(response.data, function(index, month) {
                                options += '<option value="' + month.value + '">' + month.label + '</option>';
                            });
                            
                            $monthSelect.html(options);
                        }
                        
                        // Get image count for the year (all months)
                        getImageCount(year, '');
                    },
                    complete: function() {
                        $spinner.removeClass('is-active');
                    }
                });
            }
        });
        
        // Function to get and display image count
        function getImageCount(year, month) {
            if (!year) return;
            
            $.ajax({
                url: mbd_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mbd_get_image_count',
                    nonce: mbd_ajax.nonce,
                    year: year,
                    month: month
                },
                success: function(response) {
                    if (response.success) {
                        var count = response.data;
                        var text = '';
                        
                        if (month) {
                            text = count + ' image' + (count !== 1 ? 's' : '') + ' found for ' + 
                                   $monthSelect.find('option:selected').text() + ' ' + year;
                        } else {
                            text = count + ' image' + (count !== 1 ? 's' : '') + ' found for all months in ' + year;
                        }
                        
                        $imageCount.html('<strong>' + text + '</strong>').show();
                    }
                }
            });
        }
        
        // Handle month change to show image count
        $monthSelect.on('change', function() {
            var year = $yearSelect.val();
            var month = $(this).val();
            
            if (year) {
                getImageCount(year, month);
            }
        });
        
        // Handle form submission
        $form.on('submit', function(e) {
            e.preventDefault();
            
            var year = $yearSelect.val();
            var month = $monthSelect.val();
            
            if (!year && !month) {
                showMessage('error', 'Please select at least a year to download images.');
                return;
            }
            
            // Start download process
            $.ajax({
                url: mbd_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mbd_download_images',
                    nonce: mbd_ajax.nonce,
                    year: year,
                    month: month
                },
                xhrFields: {
                    responseType: 'blob'
                },
                beforeSend: function() {
                    $downloadBtn.prop('disabled', true);
                    $spinner.addClass('is-active');
                    showMessage('info', mbd_ajax.downloading_text);
                },
                success: function(response, status, xhr) {
                    // Check if this is an error response
                    if (xhr.getResponseHeader('Content-Type').includes('application/json')) {
                        response.text().then(function(text) {
                            var jsonResponse = JSON.parse(text);
                            if (!jsonResponse.success) {
                                showMessage('error', jsonResponse.data || mbd_ajax.download_error);
                            }
                        });
                        return;
                    }
                    
                    // Get filename from header or use default
                    var filename = '';
                    var disposition = xhr.getResponseHeader('Content-Disposition');
                    if (disposition && disposition.indexOf('attachment') !== -1) {
                        var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                        var matches = filenameRegex.exec(disposition);
                        if (matches != null && matches[1]) { 
                            filename = matches[1].replace(/['"]/g, '');
                        }
                    }
                    
                    if (!filename) {
                        filename = 'media-download-' + new Date().getTime() + '.zip';
                    }
                    
                    // Create download link
                    var url = window.URL.createObjectURL(response);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    showMessage('success', mbd_ajax.download_complete);
                },
                error: function(xhr) {
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        showMessage('error', xhr.responseJSON.data);
                    } else {
                        showMessage('error', mbd_ajax.download_error);
                    }
                },
                complete: function() {
                    $downloadBtn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        // Show message function
        function showMessage(type, message) {
            var noticeClass = 'notice-' + type;
            $message.removeClass('notice-success notice-error notice-info notice-warning')
                    .addClass(noticeClass)
                    .html('<p>' + message + '</p>')
                    .fadeIn();
            
            // Auto hide success messages
            if (type === 'success') {
                setTimeout(function() {
                    $message.fadeOut();
                }, 5000);
            }
        }
    });
})(jQuery);