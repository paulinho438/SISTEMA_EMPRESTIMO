$(document).ready(function () {
    //Aplicando as mascaras nos inputs cpf, valor e vencimento.
    $("#btn_emitir_boleto").click(function () {
        if ($('#form')[0].checkValidity()) {

            $("#myModal").modal('show');
            $("#boleto").addClass("hide");
           
            var descricao = $("#descricao").val();
            var valor = $("#valor").val();
            var quantidade = $("#quantidade").val();
            var nome_cliente = $("#nome_cliente").val();
            var cpf = $("#cpf").val();
            var telefone = $("#telefone").val();
            var vencimento = $("#vencimento").val();

            if (parseInt(nome_cliente) == "NaN" || parseInt(valor) == "NaN") {
                $("#myModal").modal('hide');
                alert("Dados inválidos.");

                return false;
            }

            $.ajax({
                url: "../boleto/emitir_boleto.php",
                data: { descricao: descricao, valor: valor, quantidade: quantidade, nome_cliente: nome_cliente, cpf: cpf, telefone: telefone, vencimento: vencimento },
                type: 'post',
                dataType: 'json', 
                success: function (resposta) {
                    $("#myModal").modal('hide'); 
                    if (resposta.code == 200) {
                        $("#boleto").removeClass("hide");
                        //"code":200,"data":{"barcode":"03399.32766 55400.000000 60348.101027 6 69020000009000","link":"https:\/\/visualizacaosandbox.gerencianet.com.br\/emissao\/59808_79_FORAA2\/A4XB-59808-60348-HIMA4","expire_at":"2016-08-30","charge_id":76777,"status":"waiting","total":9000,"payment":"banking_billet"-->
                        var html = "<th>" + resposta.data.charge_id + "</th>"
                        html += "<th>" + resposta.data.status + "</th>"
                        html += "<th>" + resposta.data.barcode + "</th>"
                        html += "<th>" + resposta.data.expire_at + "</th>"
                        html += "<th>" + resposta.data.total + "</th>"
                        html += "<th><a target='blank' href='" + resposta.data.link + "'> Visualizar </a></th>"
                        
                        $("#result_table").html(html);
                        $("#myModalResult").modal('show');
                    } else {
                        $("#myModal").modal('hide');
                        alert("Code: " + resposta.code + '\n' + 'Ocorreu um erro - Mensagem: ' + resposta.responseText)
                    }
                },
                error: function (resposta) {
                    console.log(resposta)
                    $("#myModal").modal('hide');
                    alert("Ocorreu um erro - Mensagem: " + resposta.responseText)
                }
            });
        }
        else {
            alert("Você deverá preencher todos os dados do formulário.")
        }
    })


})