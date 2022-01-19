# PHP Parse Report - HTTicket

Extrair dados de vendas de ingressos e bomboniere de relatório do sistema HTTicket. Consegue converter os dados do relatório HTML para dados em JSON.

## Basic Usage

```php
<?php

use FVCode\ParseReport\Report;

require __DIR__ . '/vendor/autoload.php';

$file = '17.01.htm';

$parse = new Report($file);

// Retorna os dados em JSON
echo $parse->getData();
```

A saída será algo como:

```json
{
  "data": "2022-01-18",
  "valor_inicial": 0,
  "suprimento": 400,
  "retirada": 0,
  "total_dinheiro": 978,
  "total_pos": 664,
  "total_debito": 0,
  "total_credito": 49,
  "antecipada": 0,
  "vendas_produto": {
    "ingresso": 1538,
    "bomboniere": 113,
    "combo": 40,
    "diversos": 0
  },
  "saldo_dia": 2091,
  "liquido": 1691
}
```
