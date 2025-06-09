# ZaionPlus

ZaionPlus é um sistema de gestão escrito em **PHP** que utiliza **MySQL** para armazenamento dos dados. O projeto inclui módulos para controle de clientes, produtos, vendas, fornecedores, contas e gestão de funcionários, além de uma área dedicada para clientes.

## Principais recursos

- Tela de login e dashboard com gráficos e indicadores de vendas
- Cadastro e gestão de clientes e fornecedores
- Controle de produtos com importação em massa
- Registro de vendas, pagamentos e contas a receber/pagar
- Módulo de funcionários com controle de ponto
- Área do cliente para consulta de compras e pagamentos
- PWA (manifest e service worker) para uso offline básico
- API de estatísticas para atualizações em tempo real

## Estrutura de diretórios

```
area_cliente/       # páginas acessíveis aos clientes
assets/             # arquivos estáticos (CSS, JS e imagens)
clientes/           # CRUD de clientes e relatórios
config/             # arquivos de configuração e conexão com o banco
contas/             # contas a pagar/receber
fornecedores/       # gestão de fornecedores
funcionarios/       # cadastro de funcionários e extras
lista_compras/      # lista de compras e relatórios
pagamentos/         # listagem de pagamentos
ponto/              # registro e consulta de ponto
produtos/           # cadastro e importação de produtos
uploads/            # arquivos enviados (anexos, fotos etc.)
```

## Instalação

1. Instale **PHP 7+** e **MySQL** em seu ambiente.
2. Clone este repositório e copie o arquivo `db/dunkac76_uzumaki.sql` para seu servidor MySQL.
3. Crie um banco de dados e importe o script SQL acima.
4. Ajuste as credenciais em `config/database.php` conforme seu ambiente:

```php
$host = 'localhost';
$username = 'usuario';
$password = 'senha';
$database = 'nome_do_banco';
```

5. Acesse a aplicação pela página `index.php` em um servidor web (ex.: `php -S localhost:8000`).

## Uso básico

- Após autenticar-se, a tela **Início** apresenta atalhos para os módulos principais.
- Utilize o menu lateral para navegar entre clientes, produtos, fornecedores, contas e demais seções.
- O sistema grava logs e estatísticas no banco de dados para acompanhamento de vendas e pagamentos.
- Clientes podem acessar sua própria área em `area_cliente/` para ver compras e efetuar pagamentos.

## PWA

O arquivo [`manifest.json`](manifest.json) e o service worker [`sw.js`](sw.js) permitem instalar o sistema como aplicativo web progressivo, oferecendo cache de alguns recursos estáticos para uso offline.

## API de estatísticas

O endpoint [`api_estatisticas.php`](api_estatisticas.php) fornece dados de vendas, presença de funcionários e total de clientes em formato JSON, usado para atualizações dinâmicas no dashboard.

## Licença

Este repositório não possui um arquivo de licença específico. Consulte o autor para informações sobre permissões de uso.

