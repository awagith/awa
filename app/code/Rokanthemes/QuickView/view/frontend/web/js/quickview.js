/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
	'mage/template',
    'mage/translate',
	'quickview/cloudzoom',
	'rokanthemes/owl'
], function($, mageTemplate) {
    "use strict";

    $.widget('mage.productQuickview', {
		loaderStarted: 0,
        options: {
            icon: '',
            texts: {
                loaderText: $.mage.__('Please wait...'),
                imgAlt: $.mage.__('Loading...')
            },
            template:'<div class="loading-mask" data-role="loader">' +
                    '<div class="loader">' +
                        '<img alt="<%- data.texts.imgAlt %>" src="<%- data.icon %>">' +
                        '<p><%- data.texts.loaderText %></p>' +
                    '</div>' + '</div>'

        },

        _create: function() {
			this._bindClick();
        },

        _bindClick: function() {
            var self = this;
			self.createWindow();
            this.element.on('click', function(e) {
                e.preventDefault();
				self.element.removeClass('active');
				$(this).addClass('active');
				self.show();
                self.ajaxLoad($(this));
            });
        },
		_render: function () {
            var html;

            if (!this.spinnerTemplate) {
                this.spinnerTemplate = mageTemplate(this.options.template);

                html = $(this.spinnerTemplate({
                    data: this.options
                }));

                html.prependTo($('body'));

                this.spinner = html;
            }
        },
		show: function (e, ctx) {
            $('.quickview-link').addClass('loading');
            return false;
        },
		hide: function () {
            $('.quickview-link').removeClass('loading');
            return false;
        },

        ajaxLoad: function(link) {
            var self = this;
			if($('#quickview-content-' + link.attr('data-id')).length > 0)
			{
				return self.showWindow($('#quickview-content-' + link.attr('data-id')));
			}
			var urlLink = link.attr('data-href');
			if(!urlLink)
				urlLink = link.attr('href');
            $.ajax({
                url: urlLink,
                data: {},
                success: function(res) {
					var itemShow = $('#quickview-content');
					if(link.attr('data-id'))
					{
						if($('#quickview-content-' + link.attr('data-id')).length < 1)
						{
							var wrapper = document.createElement('div');
							$(wrapper).attr('id', 'quickview-content-' + link.attr('data-id'));
							$(wrapper).addClass('wrapper_quickview_item');
							$(wrapper).html(res);
							$('#quickview-content').append(wrapper);
						}
						itemShow = $('#quickview-content-' + link.attr('data-id'));
						$('#quickview-content-' + link.attr('data-id') + ' .owl .small_image').on('click', function(event){
							$('#quickview-content-' + link.attr('data-id') + ' .owl .small_image').removeClass('active');
							$(this).addClass('active');
							var currentImg = $(this).children('img');
							jQuery('#gallery_' + link.attr('data-id') + ' a.cloud-zoom').attr('href', currentImg.attr('data-href'));
							jQuery('#gallery_' + link.attr('data-id') + ' a.cloud-zoom img').attr('src', currentImg.attr('data-thumb-image'));
							$('.cloud-zoom, .cloud-zoom-gallery').CloudZoom();
						});
						$('#quickview-content-' + link.attr('data-id') + ' .owl').owlCarousel({
							lazyLoad:true,
							autoPlay : false,
							items : 4,
							itemsDesktop : [1199,4],
							itemsDesktopSmall : [980,3],
							itemsTablet: [767,4],
							itemsMobile : [480,3],
							slideSpeed : 500,
							paginationSpeed : 500,
							rewindSpeed : 500,
							navigation : true,
							stopOnHover : true,
							pagination :false,
							scrollPerPage:true,
						});
					}else
					{
						$('#quickview-content').html(res);
					}
					$('.cloud-zoom, .cloud-zoom-gallery').CloudZoom();
					$('#quickview-content').trigger('contentUpdated');
					self.showWindow(itemShow);
                }
            });
        },
		showWindow: function(itemShow)
		{	
			this.hide();
			this.lastActiveElement = document.activeElement;
			$('#quick-window .wrapper_quickview_item').hide();
			$('#quick-window').css({
						display: 'block'
			});
			if(itemShow)
				itemShow.show();
			$('#quick-window').attr('aria-hidden', 'false');
			$('#quick-background').removeClass('hidden');
			$('body').addClass('quickview-open');

			// Focus management
			var $focusTarget = $('#quick-window').find('#quickview-close').first();
			if ($focusTarget.length) {
				$focusTarget.trigger('focus');
			} else {
				$('#quick-window').trigger('focus');
			}
		},
		hideWindow: function()
		{
			$('#quick-window').hide();
			$('#quick-window .wrapper_quickview_item').hide();
			$('#quick-window').attr('aria-hidden', 'true');
			$('#quick-background').addClass('hidden');
			$('#quickview-content').html('');
			$('body').removeClass('quickview-open');

			// Restore focus
			if (this.lastActiveElement && this.lastActiveElement.focus) {
				try {
					this.lastActiveElement.focus();
				} catch (e) {}
			}
		},
		createWindow: function()
		{
			if($('#quick-background').length > 0)
				return;
			var qBackground = document.createElement('div');
			$(qBackground).attr('id', 'quick-background');
			$(qBackground).addClass('hidden');
			$('body').append(qBackground);
			
			var qWindow = document.createElement('div');
			$(qWindow).attr('id', 'quick-window');
			$(qWindow)
				.attr('role', 'dialog')
				.attr('aria-modal', 'true')
				.attr('aria-hidden', 'true')
				.attr('tabindex', '-1');
			$(qWindow).html('<div id="quickview-header"><a href="#" id="quickview-close" role="button" aria-label="' + $.mage.__('Close') + '">close</a></div><div class="quick-view-content" id="quickview-content"></div>');
			$('body').append(qWindow);

			// Close interactions
			$('#quickview-close').on('click', function(e) {
				e.preventDefault();
				this.hideWindow();
			}.bind(this));
			$('#quick-background').on('click', this.hideWindow.bind(this));

			// ESC to close + focus trap
			$(document).on('keydown.quickview', function(e) {
				if ($('#quick-window').is(':visible') !== true) {
					return;
				}
				if (e.key === 'Escape' || e.keyCode === 27) {
					e.preventDefault();
					this.hideWindow();
					return;
				}
				if (e.key !== 'Tab' && e.keyCode !== 9) {
					return;
				}
				var $modal = $('#quick-window');
				var $focusables = $modal
					.find('a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, object, embed, [tabindex]:not([tabindex="-1"]), [contenteditable]')
					.filter(':visible');
				if ($focusables.length < 1) {
					$modal.trigger('focus');
					e.preventDefault();
					return;
				}
				var first = $focusables.get(0);
				var last = $focusables.get($focusables.length - 1);
				if (e.shiftKey && document.activeElement === first) {
					$(last).trigger('focus');
					e.preventDefault();
				} else if (!e.shiftKey && document.activeElement === last) {
					$(first).trigger('focus');
					e.preventDefault();
				}
			}.bind(this));
		}
    });

    return $.mage.productQuickview;
});