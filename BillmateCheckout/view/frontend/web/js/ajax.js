define([
	'jquery'
], function ($, billmateajax) {
	return {
		call:function(ajaxUrl){
			$(document).on('click', '.inc', function() {
				var exists1 = document.getElementById("button-step1");
				var exists2 = document.getElementById("button-step2");
				var incexists = document.getElementById("bm-inc-btn");
				var subexists = document.getElementById("bm-sub-btn");
				var delexists = document.getElementById("bm-del-btn");
				if (exists1 != null){
					document.getElementById('button-step1').disabled = true;
				}
				if (exists2 != null){
					document.getElementById('button-step2').disabled = true;
				}
				if (incexists != null){
					document.getElementById("bm-inc-btn").disabled = true;
				}
				if (subexists != null){
					document.getElementById("bm-sub-btn").disabled = true;
				}
				if (delexists != null){
					document.getElementById("bm-del-btn").disabled = true;
				}
				var param = {
					field1 : "ajax", 
					field2 : "inc",
					field3 : this.id
				};
				var qty = 'qty_' + (this.id).split("_")[1];
				var sum = 'sum_' + (this.id).split("_")[1];
				var price = 'price_' + (this.id).split("_")[1];
				$.ajax({
					showLoader: true,
					url: ajaxUrl,
					data: param,
					type: "POST",
					dataType: 'json'
				}).done(function (data) {
                    if (data.error) {
                        location.href = data.redirect_url;
                        return;
                    }
					var old = parseInt(document.getElementById(qty).innerHTML);
					update = parseInt(old + 1);
					document.getElementById(qty).innerHTML = update;
					document.getElementById(sum).innerHTML = (parseFloat(document.getElementById(price).innerHTML)*update);
					document.getElementById('checkout').src = data.iframe;
					document.getElementById('billmate-cart').innerHTML = data.cart;
					var exists1 = document.getElementById("button-step1");
					var exists2 = document.getElementById("button-step2");
					var incexists = document.getElementById("bm-inc-btn");
					var subexists = document.getElementById("bm-sub-btn");
					var delexists = document.getElementById("bm-del-btn");
					if (exists1 != null){
						document.getElementById('button-step1').disabled = false;
					}
					if (exists2 != null){
						document.getElementById('button-step2').disabled = false;
					}
					if (incexists != null){
						document.getElementById("bm-inc-btn").disabled = false;
					}
					if (subexists != null){
						document.getElementById("bm-sub-btn").disabled = false;
					}
					if (delexists != null){
						document.getElementById("bm-del-btn").disabled = false;
					}
					require([
						'Magento_Customer/js/customer-data'
					], function (customerData) {
						var sections = ['cart'];
						customerData.invalidate(sections);
						customerData.reload(sections, true);
					});
				});
			});
			$(document).on('click', '.sub', function() {
				var exists1 = document.getElementById("button-step1");
				var exists2 = document.getElementById("button-step2");
				var incexists = document.getElementById("bm-inc-btn");
				var subexists = document.getElementById("bm-sub-btn");
				var delexists = document.getElementById("bm-del-btn");
				if (exists1 != null){
					document.getElementById('button-step1').disabled = true;
				}
				if (exists2 != null){
					document.getElementById('button-step2').disabled = true;
				}
				if (incexists != null){
					document.getElementById("bm-inc-btn").disabled = true;
				}
				if (subexists != null){
					document.getElementById("bm-sub-btn").disabled = true;
				}
				if (delexists != null){
					document.getElementById("bm-del-btn").disabled = true;
				}
				var param = {
					field1 : "ajax", 
					field2 : "sub",
					field3 : this.id
				};
				var qty = 'qty_' + (this.id).split("_")[1];
				var sum = 'sum_' + (this.id).split("_")[1];
				var price = 'price_' + (this.id).split("_")[1];
				$.ajax({
					showLoader: true,
					url: ajaxUrl,
					data: param,
					type: "POST",
					dataType: 'json'
				}).done(function (data) {
                    if (data.error) {
                        location.href = data.redirect_url;
                        return;
                    }
					var old = parseInt(document.getElementById(qty).innerHTML);
					if (old != 0){
						update = parseInt(old - 1);
					}
					document.getElementById(qty).innerHTML = update;
					document.getElementById(sum).innerHTML = (parseFloat(document.getElementById(price).innerHTML)*update);
					document.getElementById('checkout').src = data.iframe;
					document.getElementById('billmate-cart').innerHTML = data.cart;
					var exists1 = document.getElementById("button-step1");
					var exists2 = document.getElementById("button-step2");
					var incexists = document.getElementById("bm-inc-btn");
					var subexists = document.getElementById("bm-sub-btn");
					var delexists = document.getElementById("bm-del-btn");
					if (exists1 != null){
						document.getElementById('button-step1').disabled = false;
					}
					if (exists2 != null){
						document.getElementById('button-step2').disabled = false;
					}
					if (incexists != null){
						document.getElementById("bm-inc-btn").disabled = false;
					}
					if (subexists != null){
						document.getElementById("bm-sub-btn").disabled = false;
					}
					if (delexists != null){
						document.getElementById("bm-del-btn").disabled = false;
					}
					require([
						'Magento_Customer/js/customer-data'
					], function (customerData) {
						var sections = ['cart'];
						customerData.invalidate(sections);
						customerData.reload(sections, true);
					});
				});
			});
			$(document).on('click', '.del', function() {
				var exists1 = document.getElementById("button-step1");
				var exists2 = document.getElementById("button-step2");
				var incexists = document.getElementById("bm-inc-btn");
				var subexists = document.getElementById("bm-sub-btn");
				var delexists = document.getElementById("bm-del-btn");
				if (exists1 != null){
					document.getElementById('button-step1').disabled = true;
				}
				if (exists2 != null){
					document.getElementById('button-step2').disabled = true;
				}
				if (incexists != null){
					document.getElementById("bm-inc-btn").disabled = true;
				}
				if (subexists != null){
					document.getElementById("bm-sub-btn").disabled = true;
				}
				if (delexists != null){
					document.getElementById("bm-del-btn").disabled = true;
				}
				var param = {
					field1 : "ajax", 
					field2 : "del",
					field3 : this.id
				};
				$.ajax({
					showLoader: true,
					url: ajaxUrl,
					data: param,
					type: "POST",
					dataType: 'json'
				}).done(function (data) {
                    if (data.error) {
                        location.href = data.redirect_url;
                        return;
                    }
					document.getElementById('checkout').src = data.iframe;
					document.getElementById('billmate-cart').innerHTML = data.cart;
					var exists1 = document.getElementById("button-step1");
					var exists2 = document.getElementById("button-step2");
					var incexists = document.getElementById("bm-inc-btn");
					var subexists = document.getElementById("bm-sub-btn");
					var delexists = document.getElementById("bm-del-btn");
					if (exists1 != null){
						document.getElementById('button-step1').disabled = false;
					}
					if (exists2 != null){
						document.getElementById('button-step2').disabled = false;
					}
					if (incexists != null){
						document.getElementById("bm-inc-btn").disabled = false;
					}
					if (subexists != null){
						document.getElementById("bm-sub-btn").disabled = false;
					}
					if (delexists != null){
						document.getElementById("bm-del-btn").disabled = false;
					}
					require([
						'Magento_Customer/js/customer-data'
					], function (customerData) {
						var sections = ['cart'];
						customerData.invalidate(sections);
						customerData.reload(sections, true);
					});
				});
			});
			$(document).on('click', '.radio', function() {
				var exists1 = document.getElementById("button-step1");
				var exists2 = document.getElementById("button-step2");
				var incexists = document.getElementById("bm-inc-btn");
				var subexists = document.getElementById("bm-sub-btn");
				var delexists = document.getElementById("bm-del-btn");
				if (exists1 != null){
					document.getElementById('button-step1').disabled = true;
				}
				if (exists2 != null){
					document.getElementById('button-step2').disabled = true;
				}
				if (incexists != null){
					document.getElementById("bm-inc-btn").disabled = true;
				}
				if (subexists != null){
					document.getElementById("bm-sub-btn").disabled = true;
				}
				if (delexists != null){
					document.getElementById("bm-del-btn").disabled = true;
				}
				var param = {
					field1 : "ajax", 
					field2 : "radio",
					field3 : this.id
				};
				$.ajax({
					showLoader: true,
					url: ajaxUrl,
					data: param,
					type: "POST",
					dataType: 'json'
				}).done(function (data) {
                    if (data.error) {
                        location.href = data.redirect_url;
                        return;
                    }
					document.getElementById('checkout').src = data.iframe;
					document.getElementById('billmate-cart').innerHTML = data.cart;
				});
			});
			$(document).on('click', '.codeButton', function(){
				var param = {
					field1 : "ajax",
					field2 : "submit",
					field3 : document.getElementById("code").value
				};
				$.ajax({
					showLoader: true,
					url: ajaxUrl,
					data: param,
					type: "POST",
					dataType: 'json'
				}).done(function (data) {
                    if (data.error) {
                        location.href = data.redirect_url;
                        return;
                    }
					document.getElementById('checkout').src = data.iframe;
					document.getElementById('billmate-cart').innerHTML = data.cart;
					var exists1 = document.getElementById("button-step1");
					var exists2 = document.getElementById("button-step2");
					var incexists = document.getElementById("bm-inc-btn");
					var subexists = document.getElementById("bm-sub-btn");
					var delexists = document.getElementById("bm-del-btn");
					if (exists1 != null){
						document.getElementById('button-step1').disabled = false;
					}
					if (exists2 != null){
						document.getElementById('button-step2').disabled = false;
					}
					if (incexists != null){
						document.getElementById("bm-inc-btn").disabled = false;
					}
					if (subexists != null){
						document.getElementById("bm-sub-btn").disabled = false;
					}
					if (delexists != null){
						document.getElementById("bm-del-btn").disabled = false;
					}
				});
			});
		}
	}
});