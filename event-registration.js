(function($, Drupal) {
  'use strict';

  Drupal.behaviors.eventRegistration = {
    attach: function(context, settings) {
      // Validate special characters on form submission
      $('#event-registration-form', context).once('event-registration').submit(function(e) {
        var name = $('#edit-full-name').val();
        var college = $('#edit-college-name').val();
        var dept = $('#edit-department').val();
        
        var regex = /[<>"']/;
        
        if (regex.test(name)) {
          alert(Drupal.t('Special characters are not allowed in Full Name.'));
          e.preventDefault();
          return false;
        }
        
        if (regex.test(college)) {
          alert(Drupal.t('Special characters are not allowed in College Name.'));
          e.preventDefault();
          return false;
        }
        
        if (regex.test(dept)) {
          alert(Drupal.t('Special characters are not allowed in Department.'));
          e.preventDefault();
          return false;
        }
      });
    }
  };

  // AJAX for date selection
  $('#edit-category').on('change', function() {
    var category = $(this).val();
    if (category) {
      $.ajax({
        url: '/event-registration/ajax/dates/' + category,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          var select = $('#edit-event-date');
          select.empty();
          select.append('<option value="">- Select -</option>');
          $.each(data, function(index, item) {
            select.append('<option value="' + item.value + '">' + item.label + '</option>');
          });
        }
      });
    }
  });

  // AJAX for event selection
  $(document).on('change', '#edit-event-date', function() {
    var category = $('#edit-category').val();
    var date = $(this).val();
    if (category && date) {
      $.ajax({
        url: '/event-registration/ajax/events/' + category + '/' + date,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          var select = $('#edit-event-name');
          select.empty();
          select.append('<option value="">- Select -</option>');
          $.each(data, function(index, item) {
            select.append('<option value="' + item.value + '">' + item.label + '</option>');
          });
        }
      });
    }
  });

})(jQuery, Drupal);