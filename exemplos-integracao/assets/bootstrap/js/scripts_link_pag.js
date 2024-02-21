$(document).ready(function () {
	//Aplicando as mascaras nos inputs cpf, valor e vencimento.
	$("#btn_emitir_link").click(function () {
		if ($('#form')[0].checkValidity()) {

			$("#myModal").modal('show');
			$("#link").addClass("hide");
			var descricao = $("#descricao").val(); 
			var valor = $("#valor").val();
			var quantidade = $("#quantidade").val();
			var vencimento = $("#vencimento").val();
			var message = $("#message").val();
			var request = $("#request").val();
			var method = $("#method").val();

			if (parseInt(valor) == "NaN") {
				$("#myModal").modal('hide');

				alert("Dados inválidos.");

				return false;
			}


			$.ajax({
				url: "../link-de-pagamento/emitir_com_link.php",
				data: { descricao: descricao, valor: valor, quantidade: quantidade, message: message, request: request, method: method, vencimento: vencimento },
				type: 'post',
				dataType: 'json',
				success: function (resposta) {
					$("#myModal").modal('hide');
					
					if (resposta.code == 200) {
						$("#myModalResult").modal('show');
						$("#link").removeClass("hide");
						var html = "<th>" + resposta.data.charge_id + "</th>"
						html += "<th>" + resposta.data.payment_method + "</th>"
						html += "<th>" + resposta.data.status + "</th>"
						html += "<th>" + resposta.data.total + "</th>"
						html += "<th><a target='blank' href='" + resposta.data.payment_url + "'> Visualizar </a></a></th>"

						$("#result_table").html(html);


					} else {
						$("#myModal").modal('hide');
						if (resposta.error_description) {
							alert("Code: " + resposta.code + '\n' + 'Ocorreu um erro - Mensagem: ' + resposta.responseText)
						} else {
							alert("Code: " + resposta.code + '\n' + 'Property: ' + resposta.error + '\n' + 'Message: ' + resposta.error_description)
						}
					}
				},
				error: function (resposta) {
					console.log(resposta)
					$("#myModal").modal('hide');
					alert("Ocorreu um erro - Mensagem: " + resposta.responseText)
				}
			});
		} //endif
		else {
			alert("Você deverá preencher todos os dados do formulário.")
		}
	})


})