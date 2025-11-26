(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Handle download month links
        $(document).on('click', '.mbd-download-month', function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var year = $link.data('year');
            var month = $link.data('month');
            var originalText = $link.text();
            
            // Disable link and show loading
            $link.text('Downloading...').addClass('disabled');
            
            // Start download
            $.ajax({
                url: mbd_media.ajax_url,
                type: 'POST',
                data: {
                    action: 'mbd_download_images',
                    nonce: mbd_media.nonce,
                    year: year,
                    month: month
                },
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(response, status, xhr) {
                    // Check if this is an error response
                    if (xhr.getResponseHeader('Content-Type').includes('application/json')) {
                        response.text().then(function(text) {
                            var jsonResponse = JSON.parse(text);
                            if (!jsonResponse.success) {
                                alert('Error: ' + (jsonResponse.data || 'Download failed'));
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
                        var monthName = new Date(year, month - 1).toLocaleString('default', { month: 'long' });
                        filename = 'media-' + year + '-' + monthName + '.zip';
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
                },
                error: function(xhr) {
                    var errorMsg = 'Download failed';
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMsg = xhr.responseJSON.data;
                    }
                    alert('Error: ' + errorMsg);
                },
                complete: function() {
                    // Restore link
                    $link.text(originalText).removeClass('disabled');
                }
            });
        });
    });
})(jQuery);