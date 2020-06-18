if(document.getElementById("LayerPayNow")){

    if(layer_params.retry === "0"){

        window.onload = function(){
            trigger_layer()
        }
    } else {

        document.getElementById("LayerPayNow").focus()
    }

    document.getElementById("LayerPayNow").onclick = function () {

        trigger_layer()
    }


}

function trigger_layer() {

    Layer.checkout(
        {
            token: layer_params.payment_token_id,
            accesskey: layer_params.accesskey
        },
        function (response) {
            console.log(response)
            if(response !== null || response.length > 0 ){

                if(response.payment_id !== undefined){

                    document.getElementById('layer_payment_id').value = response.payment_id;

                }

            }

            document.layer_payment_int_form.submit();
        },
        function (err) {
            alert(err.message);
        }
    );
}
