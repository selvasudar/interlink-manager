jQuery(document).ready(function($) {
    $('.approve-link').on('click', function(e) {
        console.log('Approve link clicked');
        e.preventDefault();
        var rowId = $(this).data('id');
        $.ajax({
            url: interlinkAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'approve_interlink',
                nonce: interlinkAjax.nonce,
                id: rowId
            },
            success: function(response) {
                if (response.success) {
                    var positions = response.data.positions;
                    var html = '';
                    positions.forEach(function(pos, index) {
                        html += '<div class="keyword-option">';
                        html += '<input type="checkbox" class="keyword-checkbox" data-offset="' + pos.offset + '" checked>';
                        html += '<span>' + pos.context.replace(response.data.keyword, '<strong>' + response.data.keyword + '</strong>') + '</span>';
                        html += '</div>';
                    });
                    $('#keyword-options').html(html);
                    $('#keyword-popup').show().addClass('active');
                    $('body').append('<div class="overlay"></div>');

                    $('#confirm-links').off('click').on('click', function() {
                        var selectedPositions = [];
                        $('.keyword-checkbox:checked').each(function() {
                            selectedPositions.push({
                                offset: $(this).data('offset')
                            });
                        });
                        $.ajax({
                            url: interlinkAjax.ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'confirm_interlink',
                                nonce: interlinkAjax.nonce,
                                post_id: response.data.post_id,
                                row_id: response.data.row_id,
                                positions: JSON.stringify(selectedPositions),
                                destination_url: response.data.destination_url,
                                keyword: response.data.keyword
                            },
                            success: function(res) {
                                if (res.success) {
                                    alert('Links added successfully!');
                                    location.reload();
                                } else {
                                    alert('Error: ' + res.data);
                                    $('.overlay').remove();
                                    $('#keyword-popup').hide().removeClass('active');
                                }
                            },
                            error: function() {
                                alert('An error occurred while processing your request.');
                                $('.overlay').remove();
                                $('#keyword-popup').hide().removeClass('active');
                            }
                        });
                    });

                    $('#cancel-popup').on('click', function() {
                        $('#keyword-popup').hide().removeClass('active');
                        $('.overlay').remove();
                    });

                    // Close popup when clicking on overlay
                    $(document).on('click', '.overlay', function() {
                        $('#keyword-popup').hide().removeClass('active');
                        $('.overlay').remove();
                    });
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while fetching keyword positions.');
            }
        });
    });
});