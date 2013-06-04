;(function($) {
  
	var GoogleTypography = function(container, collection, values){

		initialize = function(container, collection) {
			
			var container = $(container);
			var collection = $(collection);
			var preview = $(".font_preview input", collection);
			
			// Dropdown styles
			collection.find("select").chosen();
      
			// Colorpicker
			collection.find(".font_color").wpColorPicker({
				change: function(event, ui) {
					preview.css( 'color', ui.color.toString());
				}
			});
      
			// Font attributes
			collection.find(".font_family").on("change", function(e, variant) { previewFontFamily($(this), collection, preview, variant); });
			collection.find(".font_variant").on("change", function() { previewFontVariant($(this), preview); });
			collection.find(".font_size").change(function() { previewFontSize($(this), preview); });
			collection.find(".preview_color li a").on("click", function() { previewBackgroundColor($(this), collection); });
      
			// Save and delete
			collection.find(".save_collection").on("click", function() { saveCollections(collection, container); });
			collection.find(".delete_collection").on("click", function() { 
				if(confirm("Are you sure you want to delete this font?")) {
					collection.remove(); 
					saveCollections(collection, container, false);
					if(container.find(".collections .collection").length == 0) {
						container.find(".welcome").fadeIn();
					}
				}
			});
			
			collection.on("focus", "input, select, textarea", function(){ setCurrentCollection(container, collection); });
			
			collection.find(".wp-color-result").on("click", function(){ setCurrentCollection(container, collection); });
			
			if(values) {
				loadCollection(values, collection);
			}
      
		};
		
		setCurrentCollection = function(container, collection) {
			
			container.find(".collection").removeClass("current");
			
			collection.addClass("current");
			
		};
    
		previewFontFamily = function(elem, collection, preview, variant) {

			var font = $(elem).val();

			getFontVariants(font, collection, variant, preview);
			
		};
    
		previewFontVariant = function(elem, preview) {

			preview.css('font-weight', $(elem).val());
      
		};    
    
		previewFontSize = function(elem, preview) {

			$(preview).css('font-size', $(elem).val());
      
		};
    
		previewBackgroundColor = function(elem, collection) {
      
			collection.find(".font_preview .preview_color li").removeClass("current");
			collection.find(".font_preview")
				.removeClass("dark light")
				.addClass($(elem).attr("class"));
				$(elem).parent().addClass("current");
      
		};
    
		getFontVariants = function(font, collection, selected, preview) {

			var variants = collection.find(".font_variant");
      
			var variant_array = [];

			jQuery.ajax({
				url: ajaxurl,
				data: {
					'action' : 'get_google_font_variants',
					'font_family' : font
				},
				success: function(data) {
					variants.find("option").remove();
    			for(i = 0; i < data.length; ++i) {
						if(selected == data[i]) { 
							var is_selected = "selected"; 
						} else { 
							var is_selected = ""; 
						}
						variants.append('<option value="'+data[i]+'" '+is_selected+'>'+data[i]+'</option>');
						variant_array.push(data[i]);
    			}

					WebFont.load({
						google: {
							families: [font+':'+variant_array.join()]
						},
						loading: function() {
							preview.css("opacity", 0);
						},
						fontactive: function(family, desc) {
							preview.css('font-family', '"'+font+'"').css("opacity", 1);
						}
					});

					variants.trigger("change").trigger("liszt:updated");
    		}
    	});
      
		};
    
		saveCollections = function(collection, container, showLoading) {
      
			var collectionData = new Array();
			i=0;
      
			container.find(".collections .collection").each(function() {
        
				if(showLoading != false) {
					collection.find(".save_collection").addClass("saving").html("Saving...");
				}

				previewText		= $(this).find(".preview_text").val();
				previewColor	= $(this).find(".preview_color li.current a").attr("class");
				fontFamily		= $(this).find(".font_family").val();
				fontVariant		= $(this).find(".font_variant").val();
				fontSize			= $(this).find(".font_size").val();
				fontColor			= $(this).find(".font_color").val();
				cssSelectors	= $(this).find(".css_selectors").val();
				isDefault			= $(this).attr("data-default");
        
				collectionData[i] = {
					uid: i+1,
					preview_text: previewText,
					preview_color: previewColor,
					font_family: fontFamily,
					font_variant: fontVariant, 
					font_size: fontSize,
					font_color: fontColor,
					css_selectors: cssSelectors,
					default: isDefault
				};
  
				i++;
        
			});
			
			$.ajax({
				url: ajaxurl, 
				method: 'post',
				data: {  'action' : 'save_user_fonts',  'collections' : collectionData },
				success: function(data) {
					
					if(showLoading != false) {
						collection.find(".save_collection").removeClass("saving").html("Save");
					}
					
				}
			});
		};
		
		loadCollection = function(values, collection) {

			collection.find(".preview_text").val(values.preview_text.replace("\\", ""));
			collection.find(".preview_color li a[class="+values.preview_color+"]").trigger("click");
			
			if(values.font_family) {
				collection.find(".font_family option[value='"+values.font_family+"']")
					.attr("selected", "selected")
					.trigger("change", [values.font_variant])
					.trigger("liszt:updated");
			}
				
			// fontVariant		= $(this).find(".font_variant").val();
			collection.find(".font_size option[value="+values.font_size+"]")
				.attr("selected", "selected")
				.trigger("change")
				.trigger("liszt:updated");
			collection.find(".font_color")
				.val(values.font_color)
				.wpColorPicker('color', values.font_color);
			collection.find(".css_selectors").val(values.css_selectors);
			
			collection.attr("data-default", values.default);
			
		};
    
		initialize(container, collection);
    
	}
	
	// jQuery ready
	$(document).ready(function() {
    
		var container = $("#google_typography");
		var template = container.find(".template").html();
		
		// Retrieve collections
		$.ajax({
			url: ajaxurl, 
			data: {  'action' : 'get_user_fonts' },
			beforeSend: function() {
				container.find(".loading").show();
				container.find(".collections").hide();
			},
			success: function(data) {
				if(data.collections.length == 0 || data.collections == false) {
					container.find(".loading").fadeOut("normal", function() {
						container.find(".welcome").fadeIn();
					});
				} else {
					for (var i=0;i<data.collections.length;i++) {
						new GoogleTypography(container, $(template).appendTo(".collections"), data.collections[i])
					}
					container.find(".loading").fadeOut("normal", function() {
						container.find(".collections").fadeIn();
					});
				}
				$(".collections").sortable({
					items: '.collection',
					containment: ".wrap"
				});
			}
		});
		
		// Add a new collection
		container.find(".new_collection").on("click", function() { 
			new GoogleTypography(container, $(template).prependTo(".collections"));
			container.find(".collections").show();
			container.find(".collections .collection:first .preview_text").focus();
			container.find(".welcome").hide();
		});
		
		// Reset collections
		container.find(".reset_collections").on("click", function() {
			if(confirm("Are you sure you want to reset back to the default collections? Note: You will lose any custom collections you've created.")) {
				$.ajax({
					url: ajaxurl, 
					method: 'post',
					data: {  'action' : 'reset_user_fonts' },
					success: function(data) {
						if(data.success == true) {
							location.reload();
						}
					}
				});
			}
		});
	});
  
})(jQuery);