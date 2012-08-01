
(function ($) {
  Drupal.color = {
    callback: function(context, settings, form, farb, height, width) {
		
		// Background.
		$('#business', form).css('background-color', $('#palette input[name="palette[bg]"]', form).val());
    
    // Menu.
		$('#sitename h2 a:hover', form).css('color', $('#palette input[name="palette[menu]"]', form).val());
		$('#main-menu .menu a.active', form).css('background-color', $('#palette input[name="palette[menu]"]', form).val());
		$('#inner', form).css('border-top-color', $('#palette input[name="palette[menu]"]', form).val());
    
    
		$('#link a', form).css('color', $('#palette input[name="palette[link]"]', form).val());
		$('.input-button', form).css('background-color', $('#palette input[name="palette[button]"]', form).val());
		
    }
  };
})(jQuery);