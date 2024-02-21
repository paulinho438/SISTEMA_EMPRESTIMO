$(document).ready(function () {
    //Aplicando as mascaras nos inputs cpf, valor e vencimento.
    $("#div_installments").addClass("d-none")

    $gn.ready(function (checkout) {
 
        $("#ver_parcelas").click(function () {
            $("#div_installments").removeClass("d-none")
            $("#ver_parcelas").addClass("d-none") 
            $("#btn_pg_cartao").removeClass("d-none")
            if ($('#form')[0].checkValidity()) {
                $("#myModal").modal('show');
                checkout.getInstallments(parseInt($("#valor").val()), $("#bandeira").val(), function (error, response) {
                    if (error) {
                        // Trata o erro ocorrido
                        console.log(error);

                        $("#myModal").modal('hide');
                        alert("Ocorreu um erro - Mensagem: Veja o erro no log do seu navegador")
                    } else {
                        $('#valor').attr('disabled', 'true');

                        if (response.code == 200) {
                            var options = '';

                            for (i = 0; i < response.data.installments.length; i++) {
                                options += '<option value="' + response.data.installments[i].installment + '">' + response.data.installments[i].installment + 'x de R$' + response.data.installments[i].currency + '</option>';


                            }
                            $('#installments').html(options);
                            $('#btn_pg_cartao').removeClass('hide');
                            $('#ver_parcelas').addClass('hide');
                            $("#div_installments").removeClass("hide")
                            $("#myModal").modal('hide');
                        }
                    }
                });
            } else {
                alert("Você deverá preencher todos os dados do formulário.")
            }
        });

        $("#btn_pg_cartao").click(function () {

            $("#myModal").modal('show');

            var descricao = $("#descricao").val();
            var valor = $("#valor").val();
            var quantidade = $("#quantidade").val();
            var nome_cliente = $("#nome_cliente").val();
            var cpf = $("#cpf").val();
            var telefone = $("#telefone").val();
            var vencimento = $("#vencimento").val();
            var email = $("#email").val();
            var nascimento = $("#nascimento").val();

            var rua = $("#rua").val();
            var numero = $("#numero").val();
            var bairro = $("#bairro").val();
            var cep = $("#cep").val();
            var cidade = $("#cidade").val();
            var estado = $("#estado").val();

            var numero_cartao = $("#numero_cartao").val();
            var codigo_seguranca = $("#codigo_seguranca").val();
            var bandeira = $("#bandeira").val();
            var ano_vencimento = $("#ano_vencimento").val();
            var mes_vencimento = $("#mes_vencimento").val();
            var installments = $("#installments").val();

            var callback = function (error, response) {

                if (error) {
                    $("#myModal").modal('hide');
                    alert("Ocorreu um erro - Mensagem: " + error)
                } else {
                    $.ajax({
                        url: "../cartao/pagar-cartao.php",
                        data: {
                            descricao: descricao,
                            valor: valor,
                            quantidade: quantidade,
                            nome_cliente: nome_cliente,
                            cpf: cpf,
                            telefone: telefone,
                            vencimento: vencimento,
                            rua: rua,
                            numero: numero,
                            bairro: bairro,
                            cep: cep,
                            cidade: cidade,
                            estado: estado,
                            payament_token: response.data.payment_token,
                            installments: installments,
                            email: email,
                            nascimento: nascimento
                        },
                        type: 'post',
                        dataType: 'json',
                        success: function (resposta) {
                            $("#myModal").modal('hide');
                            if (resposta.code == 200) {
                                $("#boleto").removeClass("hide");
                                var html = "";
                                html += "<th>" + resposta.data.charge_id + "</th>"
                                html += "<th>" + resposta.data.installments + "</th>"
                                html += "<th>" + resposta.data.installment_value + "</th>"
                                html += "<th>" + resposta.data.status + "</th>"
                                html += "<th>" + resposta.data.total + "</th>";

                                $("#result_table").html(html);
                                $("#myModalResult").modal('show');
                            } else if (resposta.code == undefined) {
                                alert("O Identificador de conta informado não corresponde à sua conta Efí")
                            }
                            else {
                                $("#myModal").modal('hide');
                                alert("Code: " + resposta.code + '\n' + 'Ocorreu um erro - Mensagem: ' + resposta.responseText)
                            }
                        },
                        error: function (resposta) {
                            $("#myModal").modal('hide');
                            alert("Ocorreu um erro - Mensagem: " + resposta.responseText)
                        }
                    });
                }
            }
            checkout.getPaymentToken({
                brand: bandeira,
                number: numero_cartao,
                cvv: codigo_seguranca,
                expiration_month: mes_vencimento,
                expiration_year: ano_vencimento
            }, callback);
        })

    })


})