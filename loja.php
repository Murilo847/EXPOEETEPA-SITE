<?php
session_start();

// Função para verificar o status do usuário
function verificarStatusUsuario($usuario) {
    $arquivo = 'usuarios.json';
    if (file_exists($arquivo)) {
        $usuarios = json_decode(file_get_contents($arquivo), true);
        if (isset($usuarios[$usuario])) {
            return $usuarios[$usuario]['banido'] ?? false;
        }
    }
    return false;
}

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Verifica o status do usuário
if (verificarStatusUsuario($_SESSION['usuario'])) {
    header("Location: banned.php");
    exit();
}

// Função para carregar produtos do estoque
function carregarProdutos() {
    $arquivo = 'produtos.json';
    if (file_exists($arquivo)) {
        return json_decode(file_get_contents($arquivo), true);
    }
    return [];
}

// Carrega os produtos
$produtos = carregarProdutos();

// Filtra os produtos com base na pesquisa, se houver
$termoPesquisa = isset($_GET['pesquisa']) ? $_GET['pesquisa'] : '';
$produtosFiltrados = array_filter($produtos, function($produto) use ($termoPesquisa) {
    return empty($termoPesquisa) || stripos($produto['nome'], $termoPesquisa) !== false;
});

// Verifica se o usuário é um super usuário
$isSuperUsuario = isset($_SESSION['tipo']) && ($_SESSION['tipo'] === 'super' || $_SESSION['tipo'] === 'super_usuario');

// Inicializa o carrinho se não existir
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Função para obter a quantidade de itens no carrinho
function getQuantidadeCarrinho() {
    return array_sum($_SESSION['carrinho']);
}

// Função para verificar e adicionar produto ao carrinho
function adicionarAoCarrinho($indice_produto, $produtos) {
    if (!isset($_SESSION['carrinho'][$indice_produto])) {
        $_SESSION['carrinho'][$indice_produto] = 0;
    }
    
    if ($_SESSION['carrinho'][$indice_produto] < $produtos[$indice_produto]['quantidade']) {
        $_SESSION['carrinho'][$indice_produto]++;
        return true;
    }
    
    return false;
}

// Adiciona produto ao carrinho
if (isset($_POST['adicionar_ao_carrinho'])) {
    $indice_produto = $_POST['indice_produto'];
    if (adicionarAoCarrinho($indice_produto, $produtos)) {
        $_SESSION['mensagem'] = "Produto adicionado ao carrinho com sucesso.";
    } else {
        $_SESSION['mensagem'] = "Não foi possível adicionar mais deste produto ao carrinho. Quantidade máxima atingida.";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Inicializa o modo de tema
if (!isset($_SESSION['tema'])) {
    $_SESSION['tema'] = 'claro';
}

// Alterna o tema
if (isset($_POST['alternar_tema'])) {
    $_SESSION['tema'] = $_SESSION['tema'] === 'claro' ? 'escuro' : 'claro';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$tema = $_SESSION['tema'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scat - Loja Virtual</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: <?php echo $tema === 'claro' ? '#f4f4f4' : '#333'; ?>;
            color: <?php echo $tema === 'claro' ? '#333' : '#f4f4f4'; ?>;
            padding-top: 60px;
        }
        .header {
            background-color: <?php echo $tema === 'claro' ? 'rgba(51, 51, 51, 0.8)' : 'rgba(34, 34, 34, 0.8)'; ?>;
            color: <?php echo $tema === 'claro' ? 'white' : '#f4f4f4'; ?>;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .search-form {
            display: flex;
            align-items: center;
        }
        .search-form input[type="text"] {
            padding: 0.5rem;
            width: 100px;
            border: none;
            border-radius: 10px 0 0 10px;
            outline: none;
        }
        .search-form button {
            padding: 0.5rem 1rem;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 0 10px 10px 0;
            cursor: pointer;
        }
        .user-menu {
            display: flex;
            align-items: center;
            position: relative;
        }
        .user-name {
            margin-right: 1rem;
        }
        .menu-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            cursor: pointer;
            margin-left: 10px;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: <?php echo $tema === 'claro' ? '#fff' : '#444'; ?>;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .produtos-lista {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .produto {
            border: none;
            padding: 10px;
            background-color: <?php echo $tema === 'claro' ? '#fff' : '#555'; ?>;
            border-radius: 15px;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .produto-info {
            flex: 1;
            margin-right: 20px;
        }
        .produto-imagem {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 15px;
        }
        .produto.focused {
            transform: scale(1.05);
            box-shadow: 0 0 1px 3px black;
        }
        .produto h2 {
            margin-top: 0;
        }
        .preco {
            font-weight: bold;
            color: #007bff;
        }
        .menu-content {
            display: none;
            position: fixed;
            right: 20px;
            top: 60px;
            background-color: <?php echo $tema === 'claro' ? 'rgba(249, 249, 249, 0.8)' : 'rgba(51, 51, 51, 0.8)'; ?>;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1000;
            border-radius: 15px;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .menu-content.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        .menu-content a, .menu-content button {
            color: <?php echo $tema === 'claro' ? 'black' : 'white'; ?>;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            font-size: 1em;
            cursor: pointer;
        }
        .btn {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            margin-right: 5px;
            text-decoration: none;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .btn-comprar {
            padding: 10px;
            background-color: #28a745;
            border-radius: 10px;
            width: 100px;
        }
        .btn-carrinho {
            padding: 10px;
            background-color: ;
            border: none;
            border-radius: 10px;
        }
        .botoes {
            display: flex;
            justify-content: left;
            align-items: center;
            text-align: center;
            
        }
        .carrinho-quantidade {
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.8rem;
            margin-left: 5px;
        }
        .mensagem {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
    <script>
        // Função para verificar atualizações no estoque
        function verificarAtualizacaoEstoque() {
            fetch('verificar_atualizacao_estoque.php')
                .then(response => response.json())
                .then(data => {
                    if (data.atualizado) {
                        location.reload();
                    }
                })
                .catch(error => console.error('Erro ao verificar atualização:', error));
        }

        // Verifica atualizações a cada 5 segundos
        setInterval(verificarAtualizacaoEstoque, 5000);

        // Função para verificar se um elemento está no centro da tela
        function isElementInViewport(el) {
            var rect = el.getBoundingClientRect();
            var windowHeight = window.innerHeight || document.documentElement.clientHeight;
            var windowWidth = window.innerWidth || document.documentElement.clientWidth;

            var vertInView = (rect.top <= windowHeight / 2) && ((rect.top + rect.height) >= windowHeight / 2);
            var horInView = (rect.left <= windowWidth / 2) && ((rect.left + rect.width) >= windowWidth / 2);

            return (vertInView && horInView);
        }

        // Função para atualizar o foco dos produtos
        function updateFocus() {
            var produtos = document.querySelectorAll('.produto');
            produtos.forEach(function(produto) {
                if (isElementInViewport(produto)) {
                    produto.classList.add('focused');
                } else {
                    produto.classList.remove('focused');
                }
            });
        }

        // Função para carregar mais produtos
        function carregarMaisProdutos() {
            var produtos = <?php echo json_encode($produtosFiltrados); ?>;
            var container = document.querySelector('.produtos-lista');
            for (var i = 0; i < 5; i++) {
                var indice = Math.floor(Math.random() * produtos.length);
                var produto = produtos[indice];
                var produtoDiv = document.createElement('div');
                produtoDiv.className = 'produto';
                produtoDiv.innerHTML = `
                    <div class="produto-info">
                        <h2>${produto.nome}</h2>
                        <p>${produto.descricao}</p>
                        <p class="preco">Preço: R$ ${produto.preco.toFixed(2).replace('.', ',')}</p>
                        <p>Estoque: ${produto.quantidade}</p>
                        <div class="botoes">
                            <a href="compra.php?produto=${indice}" class="btn btn-comprar">Comprar</a>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="indice_produto" value="${indice}">
                                <button type="submit" name="adicionar_ao_carrinho" class="btn btn-carrinho"><img src="carrinho-carrinho.png" alt="Adicionar ao Carrinho" style="width: 25px; height: 20px;"></button>
                            </form>
                        </div>
                    </div>
                    <img src="${produto.imagem ? produto.imagem : 'imagem_padrao.jpg'}" alt="${produto.nome}" class="produto-imagem">
                `;
                container.appendChild(produtoDiv);
            }
            updateFocus();
        }

        // Função para detectar o scroll e carregar mais produtos
        function handleScroll() {
            if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
                carregarMaisProdutos();
            }
        }

        // Adiciona os event listeners
        window.addEventListener('scroll', handleScroll);
        window.addEventListener('scroll', updateFocus);
        window.addEventListener('resize', updateFocus);

        // Chama a função inicialmente
        updateFocus();

        // Função para alternar o menu
        function toggleMenu() {
            var menuContent = document.getElementById("menuContent");
            if (menuContent.classList.contains("show")) {
                menuContent.classList.remove("show");
                setTimeout(function() {
                    menuContent.style.display = "none";
                }, 300);
            } else {
                menuContent.style.display = "block";
                setTimeout(function() {
                    menuContent.classList.add("show");
                }, 10);
            }
        }

        // Função para verificar o status do usuário
        function verificarStatusUsuario() {
            fetch('verificar_status_usuario.php')
                .then(response => response.json())
                .then(data => {
                    if (data.banido) {
                        window.location.href = 'banned.php';
                    }
                })
                .catch(error => console.error('Erro ao verificar status do usuário:', error));
        }

        // Verifica o status do usuário a cada 30 segundos
        setInterval(verificarStatusUsuario, 30000);

        // Verifica o status do usuário quando a página é carregada
        document.addEventListener('DOMContentLoaded', verificarStatusUsuario);
    </script>
</head>
<body>
    <header class="header">
        <div class="logo">Scat</div>
        <form class="search-form" method="get">
            <input type="text" name="pesquisa" placeholder="Buscar produtos" value="<?php echo htmlspecialchars($termoPesquisa); ?>">
            <button type="submit">Buscar</button>
        </form>
        <div class="user-menu">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
            <button class="menu-button" onclick="toggleMenu()">Menu</button>
        </div>
    </header>

    <div id="menuContent" class="menu-content">
        <a href="carrinho.php">Gerenciar Carrinho <span class="carrinho-quantidade"><?php echo getQuantidadeCarrinho(); ?></span></a>
        <?php if ($isSuperUsuario): ?>
            <a href="estoque.php">Gerenciar Estoque</a>
            <a href="users.php">Gerenciar Usuários</a>
        <?php endif; ?>
        <form method="post" style="display: inline;">
            <button type="submit" name="alternar_tema"><?php echo $tema === 'claro' ? 'Modo Escuro' : 'Modo Claro'; ?></button>
        </form>
        <a href="login.php">Sair</a>
    </div>

    <div class="container">
        <?php
        if (isset($_SESSION['mensagem'])) {
            echo '<div class="mensagem">' . $_SESSION['mensagem'] . '</div>';
            unset($_SESSION['mensagem']);
        }
        ?>
        <?php if (empty($produtosFiltrados)): ?>
            <p>Nenhum produto encontrado.</p>
        <?php else: ?>
            <div class="produtos-lista">
                <?php foreach ($produtosFiltrados as $indice => $produto): ?>
                    <div class="produto" id="produto-<?php echo $indice; ?>">
                        <div class="produto-info">
                            <h2><?php echo htmlspecialchars($produto['nome']); ?></h2>
                            <p><?php echo htmlspecialchars($produto['descricao']); ?></p>
                            <p class="preco">Preço: R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></p>
                            <p>Estoque: <?php echo $produto['quantidade']; ?></p>
                            <div class="botoes">
                                <a href="compra.php?produto=<?php echo $indice; ?>" class="btn btn-comprar">Comprar</a>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="indice_produto" value="<?php echo $indice; ?>">
                                    <button type="submit" name="adicionar_ao_carrinho" class="btn btn-carrinho"><img src="carrinho-carrinho.png" alt="Adicionar ao Carrinho" style="width: 25px; height: 20px;"></button>
                                </form>
                            </div>
                        </div>
                        <?php if (isset($produto['imagem'])): ?>
                            <img src="<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>" class="produto-imagem">
                        <?php else: ?>
                            <img src="imagem_padrao.jpg" alt="Imagem não disponível" class="produto-imagem">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleMenu() {
            var menuContent = document.getElementById("menuContent");
            if (menuContent.classList.contains("show")) {
                menuContent.classList.remove("show");
                setTimeout(function() {
                    menuContent.style.display = "none";
                }, 300);
            } else {
                menuContent.style.display = "block";
                setTimeout(function() {
                    menuContent.classList.add("show");
                }, 10);
            }
        }
    </script>
</body>
</html>
