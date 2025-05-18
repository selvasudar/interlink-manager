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
                    positions.forEach(function(pos) {
                        html += '<div class="form-check mb-2">';
                        html += '<input class="form-check-input keyword-checkbox" type="checkbox" data-offset="' + pos.offset + '" checked>';
                        html += '<label class="form-check-label">' + 
                                pos.context.replace(response.data.keyword, '<strong>' + response.data.keyword + '</strong>') + 
                                '</label>';
                        html += '</div>';
                    });
                    $('#keyword-options').html(html);

                    // Show the Bootstrap modal
                    var keywordModal = new bootstrap.Modal(document.getElementById('keywordModal'));
                    keywordModal.show();

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
                                    keywordModal.hide();
                                }
                            },
                            error: function() {
                                alert('An error occurred while processing your request.');
                                keywordModal.hide();
                            }
                        });
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
