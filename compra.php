<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Função para carregar os produtos do arquivo JSON
function carregarProdutos() {
    $arquivo = 'produtos.json';
    if (file_exists($arquivo)) {
        return json_decode(file_get_contents($arquivo), true);
    }
    return [];
}

// Carrega os produtos
$produtos = carregarProdutos();

// Verifica se um produto foi selecionado
if (isset($_GET['produto'])) {
    $indice_produto = $_GET['produto'];
    if (isset($produtos[$indice_produto])) {
        $produto = $produtos[$indice_produto];
    } else {
        header("Location: loja.php");
        exit();
    }
} else {
    header("Location: loja.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compra - <?php echo htmlspecialchars($produto['nome']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 400px;
            margin: 0 auto;
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            font-size: 1.5em;
            margin-bottom: 10px;
        }
        .produto-info {
            margin-bottom: 15px;
        }
        .produto-info h2 {
            font-size: 1.2em;
            margin-bottom: 5px;
        }
        .preco {
            font-weight: bold;
            color: #007bff;
        }
        .quantidade {
            color: #28a745;
        }
        .botao-comprar {
            background-color: purple;
            color: white;
            border: none;
            padding: 8px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin-top: 10px;
            cursor: pointer;
            border-radius: 5px;
        }
        .quantidade-controle {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .quantidade-controle button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 3px 8px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 3px;
        }
        .quantidade-controle input {
            width: 40px;
            text-align: center;
            margin: 0 8px;
        }
        .notificacao {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 14px;
            display: none;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Compra de Produto</h1>
        <div class="produto-info">
            <h2><?php echo htmlspecialchars($produto['nome']); ?></h2>
            <p><?php echo htmlspecialchars($produto['descricao']); ?></p>
            <p class="preco">Preço: R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></p>
            <p class="quantidade">Quantidade disponível: <?php echo $produto['quantidade']; ?></p>
        </div>
        <form id="formCompra" onsubmit="return finalizarCompra(event)">
            <input type="hidden" name="produto_id" value="<?php echo $indice_produto; ?>">
            <div class="quantidade-controle">
                <button type="button" onclick="diminuirQuantidade()">-</button>
                <input type="number" id="quantidade" name="quantidade" min="1" max="<?php echo $produto['quantidade']; ?>" value="1" required>
                <button type="button" onclick="aumentarQuantidade()">+</button>
            </div>
            <button type="submit" class="botao-comprar">Confirmar Compra</button>
        </form>
        <p><a href="loja.php">Voltar para a loja</a></p>
    </div>

    <div id="notificacao" class="notificacao"></div>

    <script>
        function diminuirQuantidade() {
            var input = document.getElementById('quantidade');
            if (input.value > 1) {
                input.value = parseInt(input.value) - 1;
            }
        }

        function aumentarQuantidade() {
            var input = document.getElementById('quantidade');
            var max = parseInt(input.getAttribute('max'));
            if (parseInt(input.value) < max) {
                input.value = parseInt(input.value) + 1;
            }
        }

        function finalizarCompra(event) {
            event.preventDefault();
            var notificacao = document.getElementById('notificacao');
            notificacao.textContent = 'Compra finalizada com sucesso!';
            notificacao.style.display = 'block';
            setTimeout(function() {
                notificacao.style.display = 'none';
            }, 3000);
            return false;
        }
    </script>
</body>
</html>
