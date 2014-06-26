
// Image
(function($) {

  detach_buttons = jQuery('.killerapps-settings-image-detach');
  detach_buttons.live('click', function(event) {
    var detach_button = jQuery(event.currentTarget);
    var input = jQuery(detach_button).parent().siblings('input[type="hidden"]');
    var image_container = jQuery(detach_button).parent('.killerapps-settings-image-container');

    var input_val = $.parseJSON(input.val());
    input_val.remove()

    input.val($toJSON(input_val));
    image_container.html('');
    detach_button.addClass("hidden");
  });

  function initGallery() {

    if (typeof wp === 'undefined') {

      setTimeout(initGallery, 1000);

    } else {

      // Uploading files
      var file_frame;
      var wp_media_post_id = wp.media.model.settings.post.id;
      // Store the old id
      var set_to_post_id = 10;
      // Set this
      var select_buttons = jQuery('.killerapps-settings-image-select');

      select_buttons.live('click', function(event) {

        var select_button = event.currentTarget;
        var input = jQuery(select_button).prev('input');
        var image_container = jQuery(select_button).next('.killerapps-settings-image-container');
        var detach_button = jQuery(select_button).siblings('.killerapps-settings-image-detach');

        event.preventDefault();

        // If the media frame already exists, reopen it.
        if (file_frame) {
          // Set the post ID to what we want
          file_frame.uploader.uploader.param('post_id', set_to_post_id);
          // Open frame
          file_frame.open();
          return;
        } else {
          // Set the wp.media post id so the uploader grabs the ID we want when initialised
          wp.media.model.settings.post.id = set_to_post_id;
        }

        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
          title: jQuery(this).data('uploader_title'),
          button: {
            text: jQuery(this).data('uploader_button_text'),
          },
          multiple: false // Set to true to allow multiple files to be selected
        });

        // When an image is selected, run a callback.
        file_frame.on('select', function() {
          // We set multiple to false so only get one image from the uploader
          attachment = file_frame.state().get('selection').first().toJSON();

          input.val(attachment.id);
          image_container.html("<img src='" + attachment.url + "'/>");
          detach_button.removeClass("hidden");

          // Do something with attachment.id and/or attachment.url here

          // Restore the main post ID
          wp.media.model.killerapps_settings.post.id = wp_media_post_id;
        });

        // Finally, open the modal
        file_frame.open();
      });

      // Restore the main ID when the add media button is pressed
      jQuery('a.add_media').on('click', function() {
        wp.media.model.killerapps_settings.post.id = wp_media_post_id;
      });

    }
  }

  initGallery();

})(jQuery);


// Range
(function($) {
  $(document).ready(function() {
    $('.killerapps-settings-range').on('change', function(e) {
      $(e.target).siblings('.killerapps-settings-range-value').html(e.target.value);
    })
  });
})(jQuery);

