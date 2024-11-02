<?php
session_start();

// Função para salvar dados localmente (simulando um banco de dados)
function salvarDados($dados) {
    $arquivo = 'usuarios.json';
    $dadosAtuais = file_exists($arquivo) ? json_decode(file_get_contents($arquivo), true) : [];
    $dadosAtuais[$dados['usuario']] = $dados;
    file_put_contents($arquivo, json_encode($dadosAtuais));
}

// Função para verificar credenciais
function verificarCredenciais($usuario, $senha) {
    $arquivo = 'usuarios.json';
    if (file_exists($arquivo)) {
        $usuarios = json_decode(file_get_contents($arquivo), true);
        if (isset($usuarios[$usuario]) && $usuarios[$usuario]['senha'] === $senha) {
            // Verifica se o usuário está banido
            if (isset($usuarios[$usuario]['banido']) && $usuarios[$usuario]['banido']) {
                return 'banido';
            }
            return $usuarios[$usuario]['tipo'];
        }
    }
    return false;
}

// Função para verificar se um usuário já existe
function usuarioExiste($usuario) {
    if ($usuario === 'veyr') {
        return true; // 'veyr' é considerado como existente para impedir sua criação
    }
    $arquivo = 'usuarios.json';
    if (file_exists($arquivo)) {
        $usuarios = json_decode(file_get_contents($arquivo), true);
        return isset($usuarios[$usuario]);
    }
    return false;
}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        if ($_POST['acao'] === 'cadastrar') {
            $novoUsuario = $_POST['usuario'];
            if ($novoUsuario === 'veyr') {
                $mensagem = "<p class='mensagem erro'>Este nome de usuário já está em uso. Por favor, escolha outro.</p>";
            } elseif (usuarioExiste($novoUsuario)) {
                $mensagem = "<p class='mensagem erro'>Este nome de usuário já está em uso. Por favor, escolha outro.</p>";
            } else {
                $novoUsuario = [
                    'usuario' => $novoUsuario,
                    'senha' => $_POST['senha'],
                    'tipo' => 'comum',
                    'banido' => false
                ];
                salvarDados($novoUsuario);
                $mensagem = "<p class='mensagem sucesso'>Usuário cadastrado com sucesso! Faça login para continuar.</p>";
            }
        } elseif ($_POST['acao'] === 'login') {
            $usuario = $_POST['usuario'];
            $senha = $_POST['senha'];
            
            if ($usuario === 'veyr' && $senha === 'veyr') {
                $_SESSION['usuario'] = $usuario;
                $_SESSION['tipo'] = 'super';
                header("Location: loja.php");
                exit();
            } else {
                $tipoUsuario = verificarCredenciais($usuario, $senha);
                
                if ($tipoUsuario) {
                    if ($tipoUsuario === 'banido') {
                        $mensagem = "<p class='mensagem erro'>Esta conta foi suspensa por violar as regras do site.</p>";
                    } else {
                        $_SESSION['usuario'] = $usuario;
                        $_SESSION['tipo'] = $tipoUsuario;
                        
                        if ($tipoUsuario === 'comum' || $tipoUsuario === 'super') {
                            header("Location: loja.php");
                        }
                        exit();
                    }
                } else {
                    $mensagem = "<p class='mensagem erro'>Usuário ou senha inválidos.</p>";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login e Cadastro</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 80%;
            max-width: 500px;
        }
        h2 {
            color: #333;
            font-size: 36px;
            margin-bottom: 20px;
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        input {
            margin: 15px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 15px;
            font-size: 16px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            margin-top: 15px;
            font-size: 18px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #45a049;
        }
        .toggle-btn {
            background-color: #008CBA;
            margin-top: 30px;
        }
        .toggle-btn:hover {
            background-color: #007B9A;
        }
        .mensagem {
            text-align: center;
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 20px;
            font-size: 16px;
        }
        .sucesso {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .erro {
            background-color: #f2dede;
            color: #a94442;
        }
    </style>
</head>
<body>
    <div class="container">
        <div id="login-form">
            <h2>Entrar</h2>
            <form method="post">
                <input type="hidden" name="acao" value="login">
                <input type="text" name="usuario" placeholder="Usuário" required>
                <input type="password" name="senha" placeholder="Senha" required>
                <button type="submit">Entrar</button>
            </form>
            <button class="toggle-btn" onclick="toggleForm('cadastro-form')">Cadastrar-se</button>
        </div>

        <div id="cadastro-form" style="display: none;">
            <h2>Cadastrar-se</h2>
            <form method="post">
                <input type="hidden" name="acao" value="cadastrar">
                <input type="text" name="usuario" placeholder="Usuário" required>
                <input type="password" name="senha" placeholder="Senha" required>
                <button type="submit">Cadastrar-se</button>
            </form>
            <button class="toggle-btn" onclick="toggleForm('login-form')">Entrar</button>
        </div>
        
        <?php echo $mensagem; ?>
    </div>

    <script>
        function toggleForm(formId) {
            document.getElementById('login-form').style.display = formId === 'login-form' ? 'block' : 'none';
            document.getElementById('cadastro-form').style.display = formId === 'cadastro-form' ? 'block' : 'none';
        }
    </script>
</body>
</html>
