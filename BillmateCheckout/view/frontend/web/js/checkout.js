/**
 * Created by Boxedsolutions on 2016-12-07.
 */
define([
	'jquery'
], function ($, billmateajax) {
	window.method = null;
	window.address_selected = null;
	window.latestScroll = null;
	var BillmateIframe = new function(){
		var self = this;
		var childWindow = null;
	    this.updateAddress = function (data) {
			$.ajax({
				url : UPDATE_ADDRESS_URL,
				data: data,
				type: 'POST',
				success: function(response){
					document.getElementById('billmate-cart').innerHTML = response.cart;
				}
			});

		};
		this.update = function(){
			
		}
		this.createOrder = function(data){
			if (data.status == "Step2Loaded"){
				$.ajax({
					url : CREATE_ORDER_URL,
					data: data,
					type: 'POST',
					success: function(response){
						location.href=response;
					}
				});
			}
		};
		this.paymentMethod = function(data){
			$.ajax({
				url : SET_PAYMENTMETHOD_URL,
				data: data,
				type: 'POST'
			});
		};
		this.initListeners = function () {
			window.addEventListener("message",self.handleEvent);
		};
		this.handleEvent = function(event){
				try {
					var json = JSON.parse(event.data);
				} catch (e) {
					return;
				}
				self.childWindow = json.source;
				switch (json.event) {
				    case 'address_selected':
						self.updateAddress(json.data);
						if(window.method == null || window.method == json.data.method) {
							$('#checkoutdiv').removeClass('loading');
						}
						break;
					case 'payment_method_selected':
						self.paymentMethod(json.data);
						break;
					case 'checkout_success':
							self.createOrder(json.data);
						break;
					case 'content_height':
						document.getElementById("checkout").height = json.data;
						break;
					case 'content_scroll_position':
						window.latestScroll = $(document).find( "#checkout" ).offset().top + json.data;
						break;
					case 'checkout_loaded':
						$('#checkoutdiv').removeClass('loading');
						break;
					default:
						break;

				}
		};
	};
	var b_iframe = BillmateIframe;
	b_iframe.initListeners();
	b_iframe.update();
});