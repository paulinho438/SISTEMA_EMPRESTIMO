<!DOCTYPE html>
<html>

<head>
<meta charset="UTF-8">
	<!-- <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.css"> -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet"
		integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
		integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm"
		crossorigin="anonymous"></script>
	<link rel="stylesheet" href="../assets/bootstrap/css/style.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script type="text/javascript" src="../assets/bootstrap/js/payment-token.js"></script>
    <script type="text/javascript" src="../assets/bootstrap/js/payment-token.js"></script>
    <script type="text/javascript" src="../assets/bootstrap/js/script-split.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <title>Exemplos Oficiais das APIs Efí</title>
    <link rel="shortcut icon" href="../assets/img/favicon.png" type="image/x-icon">
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid navbar-efi">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
                <span class="sr-only">Toggle navigation</span>
            </button>
            <!-- Logotipo à esquerda -->
            <a class="navbar-brand" href="/exemplos-integracao">
				<img src="../assets/img/logo-efi-pay.svg" alt="Efí" width="90px">
			</a>

            <!-- Botões à direita -->
           
            <div class="navbar-nav ml-auto">
                 <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Exemplos
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <li><a href="../boleto/">Boletos</a></li>
                        <li><a href="../cartao/">Cartão</a></li>
                        <li><a href="../pix/">Pix</a></li>
                        <li><a href="../assinatura/">Assinaturas</a></li>
                        <li><a href="../carne/">Carnê</a></li>
                        <li><a href="../link-de-pagamento/">Link de Pagamento</a></li>
                        <li><a href="../split-de-pagamento/">Split de Pagamento</a></li>
                    </ul>
                </li>
                <a target="blank" class="btn btn-efi-blue" href="https://sejaefi.com.br/central-de-ajuda/efi-bank/como-abrir-conta-na-efi-bank#conteudo">Abra sua conta grátis</a>
				<a target="blank" class="btn btn-efi " href="https://app.sejaefi.com.br/">Acessar minha conta</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid content">
		<div class="mb-5">
			<div class="row">
				<!-- Coluna da Imagem -->
				<div class="col-6 d-flex align-items-start justify-content-start">
					<img src="../assets/img/api_cobrancas.png" alt="Imagem" class="img-api" width="56" height="56">
					<h2 class="mt-3 ms-3">Split de Pagamento</h2>
				</div>
			</div>
		</div>

    	<div class="row">
            <div class="col-lg-9">
                <form id="form" method="POST" action="pagar-split.php" class="">
                    <div class="row">
						<div class="custom-col-5 mt-5">
							<h4><strong>Informações do produto</strong></h4>
                            <div class="form-group mt-3 mb-4">
                                <label for="exampleInputEmail1">Descrição do produto: (<em class="atributo">name</em>) <br> <strong class="ex">Ex: Monitor LCD</strong></label>
                                <input required type="text" class="form-control mt-1 custom-input" id="descricao" placeholder="Descrição do produto">
                            </div>
                            <div class="form-group mt-3 mb-4">
                                <label for="exampleInputPassword1">Valor do produto (<em class="atributo">value</em>) <br><strong class="ex">Ex: 5000 (equivale a R$ 50,00)</strong></label>
                                <input required type="text" class="form-control mt-1 custom-input" id="valor" placeholder="Valor do Produto">
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Quantidade de itens: (<em class="atributo">amount</em>) <br><strong class="ex">Ex: 1 (int)</strong></label>
                                <select required id="quantidade" class="form-select mt-1 custom-input">
                                    <?php for ($i = 1; $i < 20; $i++) : ?>
                                        <option><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                         </div>
                        <div class="custom-col-5 mt-5">
                            <h4><strong> Informações do cliente</strong></h4>

                            <div class="form-group mt-3 mb-4">
                                <label for="exampleInputEmail1">Nome do cliente: (<em class="atributo">name</em>) <br><strong class="ex">Ex: Gorbadoc Oldbuck</strong></label>
                                <input required type="text" class="form-control mt-1 custom-input" id="nome_cliente" placeholder="Nome do cliente">
                            </div>
                            <div class="form-group mt-3 mb-4">
                                <label for="exampleInputPassword1">CPF: (<em class="atributo">cpf</em>) <br><strong class="ex">Ex: 94271564656 (sem formatação)</strong></label>
                                <input required type="text" class="form-control mt-1 custom-input" id="cpf" placeholder="CPF">
                            </div>
                            <div class="form-group mt-3 mb-4">
                                <label for="exampleInputPassword1">Telefone: (<em class="atributo">phone_number</em>) <br><strong class="ex">Ex: 5144916523 (sem formatação)</strong></label>
                                <input required type="text" class="form-control mt-1 custom-input" id="telefone" placeholder="Telefone">
                            </div>
                            <div class="form-group mt-3 mb-4">
                                <label for="exampleInputPassword1">Email: (<em class="atributo">email</em>) <br><strong class="ex">Ex: email_cliente@servidor.com.br</strong> </label>
                                <input required type="text" class="form-control mt-1 custom-input" id="email" placeholder="Email">
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Data de nascimento: (<em class="atributo">birth</em>) <br><strong class="ex">Ex: 1980-08-31 (yyyy-mm-dd)</strong></label>
                                <input required type="text" class="form-control mt-1 custom-input" id="nascimento" placeholder="">
                            </div>

                        </div>
                        <div class="custom-col-5 mt-5">
                            <h4><strong>Informações do endereço</strong></h4>

                            <div class="form-group mt-3 mb-4">
                                <label for="exampleInputEmail1">Rua: (<em class="atributo">street</em>) <br><strong class="ex">Ex: Avenida Juscelino Kubitschek</strong></label>
                                <input required type="text" class="form-control mt-1 custom-input" id="rua" placeholder="Rua">
                            </div>
                            <div class="form-group mt-3 mb-4">
                                <label for="exampleInputPassword1">Número: (<em class="atributo">number</em>) <br><strong class="ex">Ex: 10 (int)</strong></label>
                                <input required type="text" class="form-control mt-1 custom-input" id="numero" placeholder="Número">
                            </div>
                            <div class="form-group mt-3 mb-4">
                                <label for="exampleInputPassword1">Bairro: (<em class="atributo">neighborhood</em>) <br><strong class="ex">Ex: Bauxita</strong></label>
                                <input required type="text" class="form-control mt-1 custom-input" id="bairro" placeholder="Bairro">
                            </div>
                            <div class="form-group mt-3 mb-4">
                                <label for="exampleInputPassword1">CEP: (<em class="atributo">zipcode</em>) <br><strong class="ex">Ex: 35400000 (string)</strong></label>
                                <input required type="text" class="form-control mt-1 custom-input" id="cep" placeholder="CEP">
                            </div>
                            <div class="form-group mt-3 mb-4">
                                <label for="exampleInputPassword1">Cidade: (<em class="atributo">city</em>) <br><strong class="ex">Ex: Ouro Preto</strong></label>
                                <input required type="text" class="form-control mt-1 custom-input" id="cidade" placeholder="Cidade">
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Estado: (<em class="atributo">state</em>) <br><strong class="ex">Ex: MG</strong></label>
                                <input required type="text" class="form-control mt-1 custom-input" id="estado" placeholder="Estado">
                            </div>

                        </div>

                        <div class="custom-col-5 mt-5">
                            <h4><strong>Informações dos repasses</strong></h4>
                            <h6>Cliente 1</h6>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Identificador da conta: (<em class="atributo">payee_code</em>) <br><strong class="ex">Ex: 51e0364ebc8f541ddc160c7b2229e5b1 (<a href="../assets/img/identificador.png" target="_blank">onde localizar?</a>)</strong></label>
                                <input required type="text" class="form-control mt-1 custom-input" id="codigo_usuario1" placeholder="Código Usuário 1">
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Porcentagem: (<em class="atributo">percentage</em>) <br><strong class="ex">Ex: 1500 (1500 = 15%)</strong></label>
                                <input required type="text" class="form-control mt-1 custom-input" id="porcentagem1" placeholder="1500">
                            </div>
                            <h6>Cliente 2</h6>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Identificador da conta: (<em class="atributo">payee_code</em>) <br><strong class="ex">Ex: 14e0364ebc8f471aad112c7b2229e5b2 (<a href="../assets/img/identificador.png" target="_blank">onde localizar?</a>)</strong></label>
                                <input required type="text" class="form-control mt-1 custom-input" id="codigo_usuario2" placeholder="Código Usuário 2">
                            </div>
                            <div class="form-group">
                                <label for="exampleInputPassword1">Porcentagem: (<em class="atributo">percentage</em>) <br><strong class="ex">Ex: 2000 (2000 = 20%)</strong></label>
                                <input required type="text" class="form-control mt-1 custom-input" id="porcentagem2" placeholder="2000">
                            </div>
                        </div>

                        <div class="custom-col-5 mt-5">

                            <h4><strong>Informações de pagamento</strong></h4>
                            <div class="form-group mt-3 mb-4">
                                <label for="exampleInputPassword1">Bandeira: (<em class="atributo">brand</em>) <br><strong class="ex">Ex: visa (string)</strong></label>
                                <select required id="bandeira" class="form-select mt-1 custom-input">
                                    <option value="" selected >Selecione um bandeira</option>
                                    <option value="visa">Visa</option>
                                    <option value="mastercard">MasterCard</option>
                                    <option value="amex">Amex</option>
                                    <option value="elo">Elo</option>
                                    <option value="hipercard">Hipercard</option>
                                </select>
                            </div>
                            <div class="form-group mt-3 mb-4">
                                <label for="exampleInputPassword1">Número do cartão: (<em class="atributo">number</em>)<br><strong class="ex">Ex: 4012001038443335</strong> </label>
                                <input required type="text" class="form-control mt-1 custom-input" id="numero_cartao" placeholder="Número do cartão">
                            </div>
                            <div class="form-group mt-3 mb-4">
                                <label for="exampleInputPassword1">Código de segurança: (<em class="atributo">cvv</em>)<br> <strong class="ex">Ex: 123 </strong> </label>
                                <input required type="text" class="form-control mt-1 custom-input" id="codigo_seguranca" placeholder="Código de segurança">
                            </div>

                            <div class="form-group mt-3 mb-4">
                                <label for="exampleInputPassword1">Mês de vencimento: (<em class="atributo">expiration_month</em>)</label>
                                <select required id="mes_vencimento" class="form-select mt-1 custom-input">
                                    <?php for ($i = 1; $i <= 12; $i++) : ?>
                                        <option><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group mt-3 mb-4">
                                <label for="exampleInputPassword1">Ano de vencimento: (<em class="atributo">expiration_year</em>)</label>
                                <select required id="ano_vencimento" class="form-select mt-1 custom-input">
                                    <?php for ($i = 2023; $i <= 2035; $i++) : ?>
                                        <option><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div id="div_installments" class="form-group alert alert-warning">
                            <label for="exampleInputPassword1">Número de parcelas: (<em class="atributo">installments</em>) <br><strong class="ex">Ex: 1</strong></label>
                                    <select required id="installments" class="form-select mt-1 custom-input">
                                    <option>Selecione uma opção</option>
                                </select>
                            </div>
                        </div>                    
                    </div>
                </form>
                <div class="col-lg-12 mt-5">
					<button id="ver_parcelas" type="button" class="btn btn-efi-blue icon-success">Definir número de parcelas <img src="../assets/img/next.svg"></button>
                    <button id="btn_pg_cartao" type="button" class="btn btn-efi-blue icon-success d-none">Confirmar pagamento <img src="../assets/img/ok-mark.png"></button>
                </div>
            </div>

            <div class="col-lg-3">
                <div style="margin-top: 45px;" class="col-lg-12 content-guidelines ms-2">
                        <div class="alert alert-warning" role="alert">
                            <img class="me-1 mb-1" src="../assets/img/exclamation-triangle-orange.svg"/> <b>Atenção!</b><br/>
                            <p>Para o funcionamento deste exemplo, você deverá informar o <b>Client_Id</b> e <b>Client_Secret</b> de sua aplicação (<a
                            href="../assets/img/credenciais.png" target="_blank"
                            title="Veja onde localizar o Client_Id e Client_Secret">?</a>) no arquivo "./credentials.json", além de alterar o parâmetro <b>sandbox</b>, de acordo com o ambiente utilizado ("sandbox => true" para desenvolvimento e "sandbox => false" para produção).</p>
                            <p>Será necessário também, informar seu <b>Identificador de conta</b> (<a target="_blank" href="../assets/img/identificador.png">?</a>) na <b>linha 1</b> do script contido no arquivo "./assets/bootstrap/js/payment-token.js".</p>
                        </div>
                    </div>

                    <div class="col-lg-12 mt-5 ms-2">
                        <a href="../download/exemplo-split-de-pagamento.zip" class="btn btn-efi"><svg class="icon-download"></svg> Baixar este exemplo </a>
                    </div>
                </div>
            </div>
    </div>

    <footer>
		<div class="container-fluid">
			<div class="row">
				<div class="col-md-6">
					<span class="contact-title-efi">Efí Bank</span>
					<br>
					<span class="info_endereco">Av Paulista, 1337, Edifício Paulista 1 - Bela Vista, São Paulo, SP -
						01311-200, Brasil</span>
				</div>
				<div class="col-md-6" id="msg-box">
					<div class="row">
						<div class="col-md-3" id="contact-box">
							<span class="contact-title">(11) 2394 2208</span>
						</div>
						<div class="col-md-3" id="contact-box">
							<span class="contact-title">0800 941 2343</span>
						</div>
						<div class="col-md-3" id="contact-box">
							<span class="contact-title">4000 1234</span>
						</div>
						<div class="col-md-3" id="contact-box">
							<span class="contact-title">0800 940 0361</span>
						</div>
					</div>
					<div class="row">
						<div class="col-md-3" id="contact-box">
							<p>São Paulo e região</p>
						</div>
						<div class="col-md-3" id="contact-box">
							<p>Ligações de telefone fixo</p>
						</div>
						<div class="col-md-3" id="contact-box">
							<p>Regiões metropolitanas</p>
						</div>
						<div class="col-md-3" id="contact-box">
							<p>Ouvidoria</p>
						</div>
					</div>
				</div>
			</div>
			<div class="row footer-redes">
				<div class="container">
					<div class="row">
						<div class="col-md-6">
							<div class="row">
								<div class="col-md-1">
									<a href="https://sejaefi.com.br/efi-bank" target="_blank"><svg
											xmlns="http://www.w3.org/2000/svg" width="65.161" height="51.056"
											viewBox="0 0 65.161 51.056">
											<g id="Group_27158" data-name="Group 27158"
												transform="translate(-96 -5165.238)">
												<path id="Path_21451" data-name="Path 21451"
													d="M56.808,11.283l8.353-3.077V0L55.05,7.913a2.1,2.1,0,0,0-1.026,1.709,1.906,1.906,0,0,0,2.784,1.661M54.024,39.761H59.4V13.872H54.024Zm-24.374,0h5.373V29.8H48.455V24.911H35.023c-.1-4.054,2.638-6.5,6.4-6.5a8.916,8.916,0,0,1,5.129,1.661l3.419-3.859a13.52,13.52,0,0,0-8.4-2.833c-7.083,0-11.918,4.689-11.918,11.528ZM5.471,24.081c.782-3.517,3.566-5.666,7.424-5.666,3.908,0,6.5,2.149,6.985,5.666Zm19.929,2.2c0-5.959-4.3-12.9-12.5-12.9C5.666,13.384,0,18.806,0,26.621S5.715,40.249,13.433,40.249a13.774,13.774,0,0,0,11.186-5.666l-3.761-3.077a9.066,9.066,0,0,1-7.571,3.712,7.448,7.448,0,0,1-7.815-6.252H22.748A2.356,2.356,0,0,0,25.4,26.279"
													transform="translate(96 5165.238)" fill="#586475" />
												<path id="Path_21452" data-name="Path 21452"
													d="M105.38,141.781h-.3l2.538,6.168h1.244l-2.716-6.345a.915.915,0,0,0-.888-.635.952.952,0,0,0-.914.635l-2.715,6.345h1.218Zm-10.533,6.168h1.142v-6.98H94.847Zm3.426-3.274a2.023,2.023,0,0,0,2.157-1.954c0-1.066-.838-1.752-2.107-1.752h-2.97v1.016h2.817a.944.944,0,0,1,1.066.939.961.961,0,0,1-.965,1.015Zm11.879,3.274h1.142l-.051-6.295-.609.229,4.036,5.584a1.01,1.01,0,0,0,.812.482.7.7,0,0,0,.66-.761v-6.219H115l.051,6.3.609-.229-4.036-5.584a.932.932,0,0,0-.787-.482.718.718,0,0,0-.685.762Zm-7.234-1.4h4.67v-1.015h-4.67Zm20.229,1.4h1.447l-3.223-3.5,3.2-3.477H123.1l-2.64,2.919a.763.763,0,0,0,0,1.142Zm-4.671,0h1.142v-6.98h-1.142Zm-23.122,0H98.3c1.548,0,2.411-.761,2.411-1.929a2.227,2.227,0,0,0-2.437-2.081H95.354v1.015h3.071c.634,0,1.066.33,1.066.914,0,.659-.533,1.066-1.294,1.066H95.354Z"
													transform="translate(30.809 5068.345)" fill="#586475" />
											</g>
										</svg></a>
								</div>
								<div class="col mt-3 info">
									© 2007-2023 • Efí - Instituição de Pagamento. Todos os direitos reservados.
									<br>Efí S.A. CNPJ: 09.089.356/0001-18
								</div>
							</div>
						</div>
						<div class="col-md-6 justify-content-end d-flex">
							<ul class="list-unstyled d-flex">
								<li class="ms-6"><a href="https://www.youtube.com/@sejaefi" target="_blank"><img
											src="../assets/img/rede1.svg" /></a></li>
								<li class="ms-6"><a href="https://www.instagram.com/sejaefi/" target="_blank"><img
											src="../assets/img/rede2.svg" /></a></li>
								<li class="ms-6"><a href="https://www.linkedin.com/company/sejaefi/"
										target="_blank"><img src="../assets/img/rede-3.svg" /></a></li>
								<li class="ms-6"><a href="https://www.facebook.com/sejaefi" target="_blank"><img
											src="../assets/img/rede-4.svg" /></a></li>
								<li class="ms-6"><a href="https://twitter.com/sejaefi" target="_blank"><img
											src="../assets/img/rede-5.svg" /></a></li>
								<li class="ms-6"><a href="https://www.tiktok.com/@sejaefi" target="_blank"><img
											src="../assets/img/rede-6.svg" /></a></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	</footer>

    <!-- Este componente é utilizando para exibir um alerta(modal) para o usuário aguardar as consultas via API.  -->
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
					<h4 class="modal-title" id="myModalLabel">Um momento</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    Estamos processando a requisição <img src="../assets/img/ajax-loader.gif">
                </div>
            </div>
        </div>
    </div>

    <!-- Este componente é utilizando para exibir um alerta(modal) para o usuário aguardar as consultas via API.  -->
    <div class="modal fade" id="myModalResult" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel">Retorno de um pagamento com cartão, utilizando Split de Pagamento</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">

                    <!--div responsável por exibir o resultado da emissão do boleto-->
                    <div id="boleto" class="hide">
                        <div class="panel panel-success">
                            <div id="" class="panel-body">
                                <table class="table">
                                    <caption></caption>
                                    <thead>
                                        <tr>
                                            <th>Id da transação</th>
                                            <th>N° de parcelas</th>
                                            <th>Valor da parcela</th>
                                            <th>Status</th>
                                            <th>Valor total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr id="result_table">
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

<script>
    window.addEventListener("load", function(event) {
        Swal.fire({
            title: 'Exemplo de integração Efí',
            icon: 'info',
            html: 'Esta página é somente para demonstração, mas você pode testar a vontade e baixar o código de exemplo.',
            showCloseButton: true,
            focusConfirm: true,
            confirmButtonText: '<img src="../assets/img/ok-mark.png">',
            confirmButtonColor: '#0BA1C2'
        });
    });
</script>

</html>