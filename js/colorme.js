$jq =jQuery.noConflict();

$jq(document).ready(function(){

	$jq("#colorme-table").each(function(){
		
		var $item_id = $jq("#colorme-item_id").val();
		if($item_id.match(/[0-9]+/) && $item_id !== ""){
			colorme_api()
		}
		
		$jq('#colorme-check').on('click',function(e){
			e.preventDefault()

			$item_id = $jq("#colorme-item_id").val();
			if($item_id.match(/[0-9]+/) && $item_id !== ""){
				colorme_api();
			}else{
				alert("商品IDを半角数字で入力してください。");
				
			}
			
		});

		function colorme_api(){

			$jq.ajax({
		        type: 'POST',
		        url: colorme_conf.ajaxURL,
		        data: {
		            'action' : "colorme_api",
		            'item_id' : $item_id,
		            'nonce' : colorme_conf.ajaxNonce
		        },
		        success: function( data ){
		        	if(data){
		            	
		            	var item = data["product"];
		            	var item_id = item["id"];
		            	var name = item["name"];
		            	var price = item["sales_price"];
		            	price *= 1.08;

		            	var image = item["image_url"];
		            	$jq("#colorme-box-title").html(name);
		            	$jq("#colorme-box-price").html(price.toLocaleString()+" (税込)");
		            	$jq("#colorme-box-image").html('<img src="'+image+'">');
		            	
		        	}else{
		        		console.log("なし");
		        	}
		        }

		    });

		}

	})

});

