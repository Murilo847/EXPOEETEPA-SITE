<?php
$arquivo_produtos = 'produtos.json';
$arquivo_controle = 'controle_modificacao.txt';

$tempoModificacaoAtual = filemtime($arquivo_produtos);
$tempoModificacaoAnterior = file_exists($arquivo_controle) ? file_get_contents($arquivo_controle) : 0;

if ($tempoModificacaoAtual > $tempoModificacaoAnterior) {
    file_put_contents($arquivo_controle, $tempoModificacaoAtual);
    echo json_encode(['atualizado' => true]);
} else {
    echo json_encode(['atualizado' => false]);
}
?>