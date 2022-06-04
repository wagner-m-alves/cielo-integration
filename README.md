## Cielo Integration

Esse projeto é uma implementação da API E-Commerce 3.0 da Cielo. Foi utilizado o framework Laravel 8 e o SDK para PHP dessa API.

## Detalhes

Foi utilizada a arquitetura de Serviços, visando uma maior organização e escalabilidade. Foi criado o arquivo de configuração app/config/payment.php para armazenar os dados da empresa que serão utilizados em cobranças via boleto bancário.

## Instruções de Instalação
1. Acesse o diretório do projeto, após clona-lo ou baixa-lo.

2. Instale as dependências do projeto, usando o comando abaixo:
```
composer install
```

3. Crie o arquivo .env, usando o comando abaixo:
```
cp .env.example .env
```

4. Gere a Chave do Sistema, usando o comando abaixo:
```
php artisan key:generate
```

5. Insira as variáveis CIELO_MERCHANT_ID e CIELO_MERCHANT_KEY no arquivo .env gerado no passo 3.

6. Crie uma conta de testes no [Ambiente Sandbox](https://cadastrosandbox.cieloecommerce.cielo.com.br/) da Cielo.

7. Salve as credencias geradas no passo 6, abra novamente o arquivo .env e insira os valores MERCHANT_ID e MERCHANT_KEY nas suas respectivas variáveis, criadas no passo 5.

## Instruções de Testes

1. Abra o arquivo de routes/web.php e altere a varável $method, linha 34, para mudar o método de pagamento. Use credit-card para gerar um pagamento via cartão de crédito ou billet para gerar uma cobrança via boleto bancário.

2. Acesse a rota /test em seu navegador. A resposta da requisição conterá links de consulta e url de visualização do boleto, caso esse seja o método escolhido.

## Considerações Finais

Há muitas funcionalidades contidas na SDK e na API que não foram implementadas, mas sinta-se a vontade para dar uma olhada na [Documentação Oficial da SDK](https://github.com/DeveloperCielo/API-3.0-PHP) e na [Documentação Oficial da API](https://developercielo.github.io/manual/cielo-ecommerce).
