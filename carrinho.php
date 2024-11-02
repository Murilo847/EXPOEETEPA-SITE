<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Inicializa o carrinho se não existir
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Função para carregar os produtos do arquivo JSON
function carregarProdutos() {
    $arquivo = 'produtos.json';
    if (file_exists($arquivo)) {
        return json_decode(file_get_contents($arquivo), true);
    }
    return [];
}

$produtos = carregarProdutos();

// Adicionar produto ao carrinho
if (isset($_POST['adicionar_ao_carrinho']) && isset($_POST['produto_id'])) {
    $produto_id = $_POST['produto_id'];
    if (isset($produtos[$produto_id])) {
        if (!isset($_SESSION['carrinho'][$produto_id])) {
            $_SESSION['carrinho'][$produto_id] = 1;
        } else {
            $_SESSION['carrinho'][$produto_id]++;
        }
    }
}

// Remover produto do carrinho
if (isset($_POST['remover_do_carrinho']) && isset($_POST['produto_id'])) {
    $produto_id = $_POST['produto_id'];
    if (isset($_SESSION['carrinho'][$produto_id])) {
        $_SESSION['carrinho'][$produto_id]--;
        if ($_SESSION['carrinho'][$produto_id] <= 0) {
            unset($_SESSION['carrinho'][$produto_id]);
        }
    }
}

// Calcular total do carrinho
$total_carrinho = 0;
foreach ($_SESSION['carrinho'] as $produto_id => $quantidade) {
    if (isset($produtos[$produto_id])) {
        $total_carrinho += $produtos[$produto_id]['preco'] * $quantidade;
    }
}

// Finalizar compra e zerar carrinho
if (isset($_POST['finalizar_compra'])) {
    $_SESSION['carrinho'] = [];
    $mensagem_sucesso = "Compra realizada com sucesso!";
}

// Filtra os produtos do carrinho com base na pesquisa, se houver
$termoPesquisa = isset($_GET['pesquisa']) ? $_GET['pesquisa'] : '';
$produtosCarrinhoFiltrados = array_filter($_SESSION['carrinho'], function($quantidade, $produto_id) use ($termoPesquisa, $produtos) {
    return empty($termoPesquisa) || stripos($produtos[$produto_id]['nome'], $termoPesquisa) !== false;
}, ARRAY_FILTER_USE_BOTH);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho de Compras</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            text-align: center; /* Centraliza o texto */
        }
        header {
            
            background-color: #333;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between; /* Mantém o header não centralizado */
            align-items: center;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        .user-menu {
            position: relative;
            display: flex;
            align-items: center;
        }
        .user-name {
            margin-right: 10px;
        }
        .user-menu-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 10px;
            overflow: hidden;
        }

        p {
            text-align: center;
        }
        .user-menu-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        .user-menu-content a:hover {
            background-color: #f1f1f1;
        }
        h1 {
            color: #333;
            padding: 20px;
            text-align: center;
        }
        .table-container {
            display: flex;
            justify-content: center;
        }
        table {
            width: 100%;
            max-width: 800px;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background-color: #f2f2f2;
        }
        .total {
            font-weight: bold;
        }
        .botao {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 10px 10px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 10px;
            width: 200px;
        }

        .botaoX {
            margin: 0 auto;
            display: block;
            background-color: #f44336;
            border: none;
            border-radius: 100%;
            color: white;
            padding: 8px 12px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        
        .mensagem-sucesso {
            display: <?php echo isset($mensagem_sucesso) ? 'block' : 'none'; ?>;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            font-size: 18px;
            z-index: 1000;
        }
        .botao-menu {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 10px;
        }
        .search-form {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .search-form input[type="text"] {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 10px 0 0 10px;
            width: 250px;
        }
        .search-form button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 0 10px 10px 0;
            cursor: pointer;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">Scat</div>
        <div class="user-menu">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
            <button class="botao-menu" onclick="toggleMenu()">Menu</button>
            <div id="userMenuContent" class="user-menu-content">
                <a href="loja.php">Voltar à Loja</a>
            </div>
        </div>
    </header>

    <h1>Seu Carrinho de Compras</h1>
    
    <form class="search-form" method="get">
        <input type="text" name="pesquisa" placeholder="Buscar produtos no carrinho" value="<?php echo htmlspecialchars($termoPesquisa); ?>">
        <button type="submit">Buscar</button>
    </form>

    <?php if (empty($produtosCarrinhoFiltrados)): ?>
        <p>Nenhum produto encontrado no carrinho.</p>
    <?php else: ?>
        <div class="table-container">
            <table>
                <tr>
                    <th>Produto</th>
                    <th>Preço</th>
                    <th>Quantidade</th>
                    <th>Subtotal</th>
                    <th>Ações</th>
                </tr>
                <?php foreach ($produtosCarrinhoFiltrados as $produto_id => $quantidade): ?>
                    <?php if (isset($produtos[$produto_id])): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($produtos[$produto_id]['nome']); ?></td>
                            <td>R$ <?php echo number_format($produtos[$produto_id]['preco'], 2, ',', '.'); ?></td>
                            <td><?php echo $quantidade; ?></td>
                            <td>R$ <?php echo number_format($produtos[$produto_id]['preco'] * $quantidade, 2, ',', '.'); ?></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="produto_id" value="<?php echo $produto_id; ?>">
                                    <button type="submit" name="remover_do_carrinho" class="botaoX" style="font-weight: bold;">X</button>
                                </form>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                <tr class="total">
                    <td colspan="3">Total</td>
                    <td>R$ <?php echo number_format($total_carrinho, 2, ',', '.'); ?></td>
                    <td></td>
                </tr>
            </table>
        </div>
        
        <button onclick="window.location.href='loja.php'" class="botao" style="width: 200px;">Continuar Comprando</button>
        <form method="post" style="display: inline;">
            <button type="submit" name="finalizar_compra" class="botao" style="width: 200px;">Finalizar Compra</button>
        </form>
    <?php endif; ?>

    <div id="mensagem-sucesso" class="mensagem-sucesso">
        <?php echo isset($mensagem_sucesso) ? $mensagem_sucesso : ''; ?>
    </div>

    <script>
        function toggleMenu() {
            var menu = document.getElementById('userMenuContent');
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }

        <?php if (isset($mensagem_sucesso)): ?>
        setTimeout(function() {
            document.getElementById('mensagem-sucesso').style.display = 'none';
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>
