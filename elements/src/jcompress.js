/*======================================================================*\
|| #################################################################### ||
|| # Package - Joomla Template based on YJSimpleGrid Framework          ||
|| # Copyright (C) 2010  Youjoomla.com. All Rights Reserved.            ||
|| # Authors - Dragan Todorovic and Constantin Boiangiu                 ||
|| # license - PHP files are licensed under  GNU/GPL V2                 ||
|| # license - CSS  - JS - IMAGE files  are Copyrighted material        ||
|| # bound by Proprietary License of Youjoomla.com                      ||
|| # for more information visit http://www.youjoomla.com/license.html   ||
|| # Redistribution and  modification of this software                  ||
|| # is bounded by its licenses                                         ||
|| # websites - http://www.youjoomla.com | http://www.yjsimplegrid.com  ||
|| #################################################################### ||
\*======================================================================*/
(function($) {

	var JCompress = {

		settings: {
			adminform: "#style-form"
		},

		initialize: function(options) {

			this.options = $.extend({}, this.settings, options);

			JCompress.start();

		},
		start: function() {

			var self = this;

			this.path = $('#compress_ajax_path').val();
			this.ajax_path = this.path + '/yjsgupdate.php';
			this.adminForm = $(this.settings.adminform);

			JCompress.cacheActions();

		},

		cacheActions: function() {

			var self = this;

			$("#clearall").click(function(event) {
				event.preventDefault();
				self.cacheUpdate('clearCache');
			});

			$("#clearcss").click(function(event) {
				event.preventDefault();
				self.cacheUpdate('clearCacheCss');
			});

			$("#clearjs").click(function(event) {
				event.preventDefault();
				self.cacheUpdate('clearCacheJs');
			});

			$("#scanpages").click(function(event) {
				event.preventDefault();
				self.scanPages();
			});

		},

		scanPages: function() {

			var self = this;

			$.ajax({
				type: 'post',
				url: self.ajax_path,
				data: {
					task: 'buildMenu'
				},
				dataType: 'json',
				success: function(data) {
					
					if(!data) return;
					
					var menuLinks = jQuery.parseJSON(data.menuarray);
					var lastEl = $(menuLinks).last()[0].full_link;

					$(menuLinks).each(function() {

						var page = this['full_link'];

						$.ajax({
							url: page,
							beforeSend: function(xhr) {
								$('.ajaxresponse').addClass('scanning').text($('.ajaxresponse').data('scannning'));
							}
						}).done(function(data, textStatus, jqXHR) {
			
							if (page == lastEl) {
								self.cacheUpdate('resetCounters');
								$('.ajaxresponse').addClass('scanning').text($('.ajaxresponse').data('resetting'));
							}
							
						});

					});
				},
				error: function(request, textStatus, errorThrown) {

					error_response = self.errorResponse;
					$('.ajaxresponse').text(error_response);

				}
			});

		},

		cacheUpdate: function(setTask) {

			var self = this;

			$.ajax({
				url: self.ajax_path,
				type: "post",
				data: {
					task: setTask
				},
				dataType: 'json',
				success: function(data, textStatus, jqXHR) {

					$('.filescount').text(data.filesount);
					$('.filesount_css').text(data.filesount_css);
					$('.filesount_js').text(data.filesount_js);
					$('.ajaxresponse').text(data.message);

					if (data.finished) {
						$('.ajaxresponse').removeClass('scanning');
					}

				},
				error: function(request, textStatus, errorThrown) {

					error_response = self.errorResponse;
					$('.ajaxresponse').text(error_response);

				}
			});

		},

		errorResponse: function(request) {

			if (request.status === 0) {
				error_response = 'Not connected.\n Verify Network.';

			}
			else if (request.status == 404) {
				error_response = 'Requested page not found. [404]';

			}
			else if (request.status == 500) {
				error_response = 'Requested page not found. [500]';
			}
			else if (request.status === 'parsererror') {
				error_response = 'Requested JSON parse failed.';
			}
			else if (request.status === 'timeout') {
				error_response = 'Time out error.';
			}
			else if (request.status === 'abort') {
				error_response = 'Ajax request aborted.';
			}
			else {
				error_response = request.responseText;
			}

			return error_response;

		}

	}

	$(document).on('ready', JCompress.initialize);

})(jQuery);