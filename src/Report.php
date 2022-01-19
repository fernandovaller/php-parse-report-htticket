<?php

namespace FVCode\ParseReport;

use Nahid\JsonQ\Jsonq;

class Report
{
    /**
     * Nome do arquivo
     * @var string
     */
    private $file;

    /**
     * Dados em json
     * @var string|JSON
     */
    private $json;

    /**
     * Dados estruturados relatório
     * @var array
     */
    private $data;

    /**
     * Ativar debug
     * @var booleanF
     */
    private $debug;

    /**
     * @param string $file Filename with path
     * @param boolean $debug
     * @return void
     */
    public function __construct($file, $debug = false)
    {
        $this->file = file_get_contents($file);

        $this->json = $this->parseHtmlToJson();

        $this->debug = $debug;
    }

    /**
     * Converte os html para json
     *
     * @return string|JSON
     */
    private function parseHtmlToJson()
    {
        if (empty($this->file)) {
            throw new \Exception("No HTML data to be converted", 1);
        }

        $dom = new \DOMDocument();

        @$dom->loadHTML($this->file);

        $dom->preserveWhiteSpace = true;

        // Pegar a tag <table></table>
        $tables = $dom->getElementsByTagName('table');

        // Pegar as linhas da primeira tabela
        $rows = $tables->item(0)->getElementsByTagName('tr');

        $data = [];

        foreach ($rows as $row) {

            // Pegar as td
            $a = $row->getElementsByTagName('td');

            $data[] = [
                'column0' => isset($a[0]->nodeValue) ? trim($a[0]->nodeValue) : '',
                'column1' => isset($a[1]->nodeValue) ? trim($a[1]->nodeValue) : '',
                'column2' => isset($a[2]->nodeValue) ? trim($a[2]->nodeValue) : '',
                'column3' => isset($a[3]->nodeValue) ? trim($a[3]->nodeValue) : '',
                'column4' => isset($a[4]->nodeValue) ? trim($a[4]->nodeValue) : '',
                'column5' => isset($a[5]->nodeValue) ? trim($a[5]->nodeValue) : '',
                'column6' => isset($a[6]->nodeValue) ? trim($a[6]->nodeValue) : '',
            ];
        }

        return json_encode($data);
    }

    /**
     * Monta os dados estruturados
     *
     * @return object
     */
    public function getData()
    {
        $data = [
            'data'           => $this->getDate($this->getValue('column0', 'Cine Cocais', 'column1')),

            'valor_inicial'  => $this->getTotal('column0', 'Valor Inicial:', 'column1'),
            'suprimento'     => $this->getTotal('column0', 'Suprimento:', 'column1'),
            'retirada'       => $this->getTotal('column0', 'Retirada:', 'column1'),
            'total_dinheiro' => $this->getTotal('column0', 'Total Dinheiro:', 'column1'),
            'total_pos'      => $this->getTotal('column0', 'Total Maq. Móvel:', 'column1'),
            'total_debito'   => $this->getTotal('column0', 'Total Cartão Débito:', 'column1'),
            'total_credito'  => $this->getTotal('column0', 'Total Cartão Crédito:', 'column1'),
            'antecipada'     => $this->getTotal('column0', 'Vendas Antecipadas:', 'column1'),

            'vendas_produto' => [
                'ingresso'   => $this->getTotal('column2', 'Ingresso:', 'column3'),
                'bomboniere' => $this->getTotal('column2', 'BOMBONIERE:', 'column3'),
                'combo'      => $this->getTotal('column2', 'COMBO:', 'column3'),
                'diversos'   => $this->getTotal('column2', 'DIVERSOS:', 'column3'),
            ],

            'saldo_dia'      => $this->getTotal('column0', 'Saldo do Dia:', 'column1'),
            'liquido'        => $this->getTotal('column2', 'Total Líquido:', 'column3'),
        ];

        return json_encode($data);
    }

    private function getValue($column, $string, $column2 = '')
    {
        $jsonq = new Jsonq($this->json);

        $resp = $jsonq->from('0')->where("{$column}", '=', "{$string}")->first();

        if ($this->debug) {
            dump($resp);
        }

        $data = json_decode($resp, true);

        return $data[$column2];
    }

    private function getTotal($column, $string, $column2 = '')
    {
        $jsonq = new Jsonq($this->json);

        $resp = $jsonq->from('0')->where("{$column}", '=', "{$string}")->get();

        if ($this->debug) {
            dump($resp);
        }

        $data = json_decode($resp, true);

        $total = 0;

        foreach ($data as $key => $row) {
            $total += $this->moneyBrToUs($row["{$column2}"]);
        }

        return $total;
    }

    /**
     * Formata um valor no padrão BR para US
     *
     * @param string $valor Valor no padrão BR
     * @param integer $decimal Número de casas decimais no retorno
     * @return string Valor formatado no padrão US
     */
    private function moneyBrToUs($valor, $decimal = 2)
    {
        if (empty($valor)) {
            return '0';
        }

        $valor = str_replace(',', '.', str_replace('.', '', $valor));

        return number_format($valor, $decimal, '.', '');
    }

    /**
     * Formata um valor no padrão US para BR
     *
     * @param float $valor Valor no formato US
     * @param integer $decimal Número de casas decimais no retorno
     * @param string $zero Definir um valor de retorno em valores zerados
     * @return string Valor formato no padrão BR
     */
    private function moneyUsToBt($valor, $decimal = 2, $zero = '0')
    {
        if ($valor == '' && $zero != '0') {
            return $zero;
        }

        if ($valor == '' || $valor == 0 || is_null($valor)) {
            $valor = 0;
        }

        return number_format($valor, $decimal, ',', '.');
    }

    private function getDate($string)
    {
        $string = trim(str_replace('Dia:', '', $string));

        return $this->dateBrToUs($string);
    }


    /**
     * Converte uma data do padrão BR para o US
     * @param string $data Data no formato DD/MM/YYYY
     * @return string Data no formato YYYY-MM-DD
     */
    private function dateBrToUs($data)
    {
        if (empty($data)) {
            return '';
        }

        return implode('-', array_reverse(explode('/', $data)));
    }
}
