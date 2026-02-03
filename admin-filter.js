(function($, Drupal) {
  'use strict';

  // AJAX for event name filter
  $('#event-date-filter').on('change', function() {
    var date = $(this).val();
    if (date) {
      $.ajax({
        url: Drupal.url('admin/event-registration/ajax/' + date + '/events'),
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          var select = $('#event-name-filter');
          select.empty();
          select.append('<option value="">- Select Event -</option>');
          $.each(data, function(index, item) {
            select.append('<option value="' + item.value + '">' + item.label + '</option>');
          });
          $('#total-count').html('');
          $('#registrations-table tbody').empty();
          $('#export-link').html('');
        }
      });
    }
  });

  // AJAX for registrations table
  $(document).on('change', '#event-name-filter', function() {
    var date = $('#event-date-filter').val();
    var eventName = $(this).val();
    
    if (date && eventName) {
      $.ajax({
        url: Drupal.url('admin/event-registration/ajax/' + date + '/' + eventName),
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          var table = $('#registrations-table tbody');
          table.empty();
          
          $.each(data.rows, function(index, row) {
            var tr = $('<tr>');
            $.each(row, function(i, cell) {
              tr.append($('<td>').html(cell));
            });
            table.append(tr);
          });
          
          $('#total-count').html('<h3>Total Participants: ' + data.total + '</h3>');
          
          var exportLink = '<a href="' + Drupal.url('admin/event-registration/export/' + date + '/' + eventName) + 
                         '" class="button button--primary">Export as CSV</a>';
          $('#export-link').html(exportLink);
        }
      });
    }
  });

})(jQuery, Drupal);