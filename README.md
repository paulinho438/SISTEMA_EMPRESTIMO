55439708000135

d8db4747-c909-4e8f-8b63-d12d0aad5d39


CONFIGURAR NODE WHATSZAPP

no projeto do whatzap tem o app.js

e criado um pm2 do app

cada modificacao e necessario executar pm2 stop app e pm2 start app

pm2 start app.js --name "node2"

pm2 list

para uma nova aplicacao e necessario configurar um link na hostgator

na host gator tem o menu 

Engintron for cPanel/WHM

depois va em 

Edit your custom_rules for Nginx(view default)

precisa adicionar uma linha

 if ($host ~ "node.agecontrole.com.br") {
     set $PROXY_SCHEME "http";
     set $PROXY_TO_PORT 3000;
 }

 pronto agora para configurar um dominio tem que seguir esses passos


 cria um apontamento do subdominio para o ip da vpn
 cria um subdominio apontando para o projeto
 cria o ssl do subdominio

 faz o apontamento no nginix 

 pronto ssl configurado


 screen para laravel queue

 screen -S sistema-agecontrole // criar
 screen -r sistema-agecontrole // entrar

 screen -ls // listar todas as screen

 cd /path/to/your/project
php artisan queue:work


control + a e d


possivel problema do zap
ls -l storage/app/public/comprovante.html

ls -ld storage/app/public