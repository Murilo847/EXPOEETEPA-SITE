<?php
session_start();

// Verifica se o usuário está logado e é um super usuário
if (!isset($_SESSION['usuario']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'super') {
    header("Location: login.php");
    exit();
}

// Função para carregar usuários do arquivo JSON
function carregarUsuarios() {
    $arquivo = 'usuarios.json';
    if (file_exists($arquivo)) {
        $usuarios = json_decode(file_get_contents($arquivo), true);
        // Garante que cada usuário tenha as chaves 'nome', 'senha' e 'banido'
        foreach ($usuarios as $usuario => &$dados) {
            if (!isset($dados['nome'])) {
                $dados['nome'] = $usuario;
            }
            if (!isset($dados['senha'])) {
                $dados['senha'] = '';
            }
            if (!isset($dados['banido'])) {
                $dados['banido'] = false;
            }
        }
        return $usuarios;
    }
    return [];
}

// Função para salvar usuários no arquivo JSON
function salvarUsuarios($usuarios) {
    $arquivo = 'usuarios.json';
    file_put_contents($arquivo, json_encode($usuarios, JSON_PRETTY_PRINT));
}

// Carrega os usuários
$usuarios = carregarUsuarios();

// Recebe o novo usuário da página login.php
if (isset($_SESSION['novo_usuario'])) {
    $novo_usuario = $_SESSION['novo_usuario'];
    if (!isset($usuarios[$novo_usuario])) {
        $usuarios[$novo_usuario] = ['nome' => $novo_usuario, 'senha' => '', 'banido' => false];
        salvarUsuarios($usuarios);
    }
    unset($_SESSION['novo_usuario']);
}

// Processa ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && isset($_POST['usuario'])) {
        $usuario = $_POST['usuario'];
        
        if ($_POST['acao'] === 'banir') {
            $usuarios[$usuario]['banido'] = true;
        } elseif ($_POST['acao'] === 'desbanir') {
            $usuarios[$usuario]['banido'] = false;
        } elseif ($_POST['acao'] === 'excluir') {
            unset($usuarios[$usuario]);
        } elseif ($_POST['acao'] === 'editar') {
            $usuarios[$usuario]['nome'] = $_POST['nome'];
            $usuarios[$usuario]['senha'] = $_POST['senha'];
        }
        
        salvarUsuarios($usuarios);
        header("Location: users.php");
        exit();
    }
}

// Sistema de busca
$termoPesquisa = isset($_GET['pesquisa']) ? $_GET['pesquisa'] : '';
$usuariosFiltrados = [];

if (!empty($termoPesquisa)) {
    foreach ($usuarios as $usuario => $dados) {
        if (stripos($dados['nome'], $termoPesquisa) !== false) {
            $usuariosFiltrados[$usuario] = $dados;
        }
    }
} else {
    $usuariosFiltrados = $usuarios;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Usuários</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .btn {
            display: inline-block;
            padding: 5px 10px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 3px;
            margin-right: 5px;
            border: none;
            cursor: pointer;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .search-form {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .search-form input[type="text"] {
            padding: 0.5rem;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
        }
        .search-form button {
            padding: 0.5rem 1rem;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        .menu-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .menu-content {
            display: none;
            position: fixed;
            top: 60px;
            right: 20px;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 4px;
        }
        .menu-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        .menu-content a:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gerenciamento de Usuários</h1>
        <form class="search-form" method="get">
            <input type="text" name="pesquisa" placeholder="Buscar usuários" value="<?php echo htmlspecialchars($termoPesquisa); ?>">
            <button type="submit">Buscar</button>
        </form>
        <table>
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Senha</th>
                    <th>Status</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuariosFiltrados as $usuario => $dados): ?>
                <tr>
                    <td><?php echo htmlspecialchars($dados['nome']); ?></td>
                    <td><?php echo htmlspecialchars($dados['senha']); ?></td>
                    <td><?php echo $dados['banido'] ? 'Banido' : 'Ativo'; ?></td>
                    <td>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="usuario" value="<?php echo htmlspecialchars($usuario); ?>">
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($dados['nome']); ?>" placeholder="Novo nome">
                            <input type="text" name="senha" value="<?php echo htmlspecialchars($dados['senha']); ?>" placeholder="Nova senha">
                            <button type="submit" name="acao" value="editar" class="btn">Editar</button>
                            <?php if (!$dados['banido']): ?>
                                <button type="submit" name="acao" value="banir" class="btn">Banir</button>
                            <?php else: ?>
                                <button type="submit" name="acao" value="desbanir" class="btn">Desbanir</button>
                            <?php endif; ?>
                            <button type="submit" name="acao" value="excluir" class="btn btn-danger">Excluir</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <button class="menu-button" onclick="toggleMenu()">Menu</button>
    <div id="menuContent" class="menu-content">
        <a href="loja.php">Ir para a loja</a>
        <a href="estoque.php">Gerencia Estoque</a>
        <a href="login.php">Sair</a>
    </div>

    <script>
        function toggleMenu() {
            var menu = document.getElementById("menuContent");
            if (menu.style.display === "block") {
                menu.style.display = "none";
            } else {
                menu.style.display = "block";
            }
        }
    </script>
</body>
</html>
