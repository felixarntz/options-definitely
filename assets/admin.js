+function ($) {
  'use strict';

  $(document).ready(function($) {

    // viewer handling for fields without visible output
    $('.form-table input[type="range"], .form-table input[type="color"]').on('change', function() {
      $('#' + $(this).attr('id') + '-' + $(this).attr('type') + '-viewer').html($(this).val());
    });

    // radio handling
    $('.radio-group .radio div').on('click', function() {
      var input_id = $(this).attr('id').replace('-asset', '');
      $(this).parent().parent().find('.radio div').removeClass('checked');
      $(this).addClass('checked');
      $('#' + input_id).prop('checked', true);
    });
    $('.radio-group .radio input').on('change', function() {
      $(this).parent().parent().find('.radio div').removeClass('checked');
    });

    // multibox handling
    $('.checkbox-group .checkbox div').on('click', function() {
      var input_id = $(this).attr('id').replace('-asset', '');
      if($(this).hasClass('checked')) {
        $(this).removeClass('checked');
        $('#' + input_id).prop('checked', false);
      }
      else {
        $(this).addClass('checked');
        $('#' + input_id).prop('checked', true);
      }
    });

    // select2 setup
    function formatSelect2(option) {
      var $option = $(option.element);

      if($option.data().hasOwnProperty('image')) {
        return '<div class="option-box" style="background-image:url(' + $option.data('image') + ');"></div>' + option.text;
      }
      else if($option.data().hasOwnProperty('color')) {
        return '<div class="option-box" style="background-color:#' + $option.data('color') + ';"></div>' + option.text;
      }
      else {
        return option.text;
      }
    }
    $('select').select2({
      containerCss : {
        'width': '100%',
        'max-width': '300px'
      },
      closeOnSelect: false,
      formatResult: formatSelect2,
      formatSelection: formatSelect2,
      escapeMarkup: function(m) { return m; },
      minimumResultsForSearch: 8
    });

    // media uploader
    if(wp.media != null)
    {
      var _custom_media = true;
      var _orig_send_attachment = wp.media.editor.send.attachment;
      
      $('.form-table .media-button').click(function(e) {
        var send_attachment = wp.media.editor.send.attachment;
        var $button = $(this);
        var search_id = $button.attr('id').replace('-media-button', '');
        _custom_media = true;
        wp.media.editor.send.attachment = function(props,attachment) {
          if(_custom_media)
          {
            $('#' + search_id).val(attachment.url);
            var extension = attachment.url.split('.').pop();
            if($.inArray(extension, ['bmp', 'jpg', 'jpeg', 'png', 'gif']) > -1) {
              if($('#' + search_id + '-media-image').length > 0) {
                $('#' + search_id + '-media-image').attr('src', attachment.url);
              }
              else {
                $('#' + search_id + '-media-button').after('<br/><img id="' + search_id + '-media-image" class="media-image" src="' + attachment.url + '" />');
              }
            }
            else {
              if($('#' + search_id + '-media-link').length > 0) {
                $('#' + search_id + '-media-link').attr('href', attachment.url);
              }
              else {
                $('#' + search_id + '-media-button').after('<br/><a id="' + search_id + '-media-link" class="media-link" href="' + attachment.url + '" target="_blank">Open file</a>');
              }
            }
          }
          else
          {
            return _orig_send_attachment.apply(this, [props, attachment]);
          }
        }
        
        wp.media.editor.open($button);
        return false;
      });
      
      $('.add_media').on('click', function() {
        _custom_media = false;
      });
    }

  });

}(jQuery);
