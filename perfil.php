<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Função para atualizar a senha do usuário
function atualizarSenha($senhaAtual, $novaSenha) {
    $usuarios = json_decode(file_get_contents('usuarios.json'), true);
    $usuarioLogado = $_SESSION['usuario'];

    if (isset($usuarios[$usuarioLogado])) {
        if (password_verify($senhaAtual, $usuarios[$usuarioLogado]['senha'])) {
            $usuarios[$usuarioLogado]['senha'] = password_hash($novaSenha, PASSWORD_DEFAULT);
            file_put_contents('usuarios.json', json_encode($usuarios));
            return true;
        } else {
            return false;
        }
    }
    return false;
}

// Processa a troca de senha
$mensagem = '';
if (isset($_POST['trocar_senha'])) {
    $senhaAtual = $_POST['senha_atual'];
    $novaSenha = $_POST['nova_senha'];
    $confirmarSenha = $_POST['confirmar_senha'];

    if ($novaSenha === $confirmarSenha) {
        if (atualizarSenha($senhaAtual, $novaSenha)) {
            $mensagem = 'Senha alterada com sucesso!';
        } else {
            $mensagem = 'Senha atual incorreta. Tente novamente.';
        }
    } else {
        $mensagem = 'As senhas não coincidem. Tente novamente.';
    }
}

$usuarioLogado = $_SESSION['usuario'];
$usuarios = json_decode(file_get_contents('usuarios.json'), true);
$nomeUsuario = isset($usuarios[$usuarioLogado]['nome']) ? $usuarios[$usuarioLogado]['nome'] : 'Usuário';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Usuário</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .mensagem {
            color: green;
            margin-bottom: 20px;
        }
        .erro {
            color: red;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .botao {
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Perfil do Usuário</h1>
        <p><strong>Nome:</strong> <?php echo htmlspecialchars($nomeUsuario); ?></p>
        <p><strong>Senha:</strong> ********</p>

        <?php if ($mensagem): ?>
            <p class="mensagem"><?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="senha_atual">Senha Atual:</label>
                <input type="password" id="senha_atual" name="senha_atual" required>
            </div>
            <div class="form-group">
                <label for="nova_senha">Nova Senha:</label>
                <input type="password" id="nova_senha" name="nova_senha" required>
            </div>
            <div class="form-group">
                <label for="confirmar_senha">Confirmar Nova Senha:</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required>
            </div>
            <button type="submit" name="trocar_senha" class="botao">Trocar Senha</button>
        </form>
    </div>
</body>
</html>

