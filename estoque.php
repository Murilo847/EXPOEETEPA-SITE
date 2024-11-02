<?php
session_start();

// Verifica se o usuário está logado e é um super usuário
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'super') {
    header("Location: login.php");
    exit();
}

// Função para salvar os produtos
function salvarProdutos($produtos) {
    file_put_contents('produtos.json', json_encode($produtos));
    $tempoModificacaoAtual = filemtime('produtos.json');
    file_put_contents('controle_modificacao.txt', $tempoModificacaoAtual); // Atualiza o tempo de modificação
}

// Função para carregar os produtos
function carregarProdutos() {
    if (file_exists('produtos.json')) {
        return json_decode(file_get_contents('produtos.json'), true);
    }
    return [];
}

// Função para processar o upload da imagem
function processarUploadImagem($arquivo) {
    $diretorio_destino = 'imagens_produtos/';
    
    // Cria o diretório se ele não existir
    if (!file_exists($diretorio_destino)) {
        mkdir($diretorio_destino, 0777, true);
    }
    
    $nome_imagem = time() . '_' . $arquivo['name'];
    $destino = $diretorio_destino . $nome_imagem;
    
    if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
        return $destino;
    } else {
        return false;
    }
}

$produtos = carregarProdutos();
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        if ($_POST['acao'] === 'adicionar') {
            $novoProduto = [
                'nome' => $_POST['nome'],
                'descricao' => $_POST['descricao'],
                'preco' => floatval($_POST['preco']),
                'quantidade' => intval($_POST['quantidade'])
            ];
            
            // Processamento da imagem
            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
                $resultado_upload = processarUploadImagem($_FILES['imagem']);
                if ($resultado_upload) {
                    $novoProduto['imagem'] = $resultado_upload;
                } else {
                    $mensagem = "Erro ao fazer upload da imagem.";
                }
            }
            
            $produtos[] = $novoProduto;
            salvarProdutos($produtos);
            $mensagem = "Produto adicionado com sucesso!";
        } elseif ($_POST['acao'] === 'remover' && isset($_POST['indice'])) {
            $indice = intval($_POST['indice']);
            if (isset($produtos[$indice])) {
                // Remove a imagem do produto, se existir
                if (isset($produtos[$indice]['imagem']) && file_exists($produtos[$indice]['imagem'])) {
                    unlink($produtos[$indice]['imagem']);
                }
                unset($produtos[$indice]);
                $produtos = array_values($produtos);
                salvarProdutos($produtos);
                $mensagem = "Produto removido com sucesso!";
            }
        } elseif ($_POST['acao'] === 'editar' && isset($_POST['indice'])) {
            $indice = intval($_POST['indice']);
            if (isset($produtos[$indice])) {
                $produtos[$indice]['nome'] = $_POST['nome'];
                $produtos[$indice]['descricao'] = $_POST['descricao'];
                $produtos[$indice]['preco'] = floatval($_POST['preco']);
                $produtos[$indice]['quantidade'] = intval($_POST['quantidade']);
                
                // Processamento da nova imagem, se fornecida
                if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
                    $resultado_upload = processarUploadImagem($_FILES['imagem']);
                    if ($resultado_upload) {
                        // Remove a imagem antiga, se existir
                        if (isset($produtos[$indice]['imagem']) && file_exists($produtos[$indice]['imagem'])) {
                            unlink($produtos[$indice]['imagem']);
                        }
                        $produtos[$indice]['imagem'] = $resultado_upload;
                    } else {
                        $mensagem = "Erro ao fazer upload da nova imagem.";
                    }
                }
                
                salvarProdutos($produtos);
                $mensagem = "Produto atualizado com sucesso!";
            }
        }
    }
}

// Filtrar produtos com base na pesquisa
$termoPesquisa = isset($_GET['pesquisa']) ? $_GET['pesquisa'] : '';
$produtosFiltrados = array_filter($produtos, function($produto) use ($termoPesquisa) {
    return empty($termoPesquisa) || stripos($produto['nome'], $termoPesquisa) !== false;
});
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Estoque</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        h1, h2 {
            color: #333;
            text-align: center;
        }
        form {
            margin-bottom: 20px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
               }
        input, textarea {
            display: block;
            margin-bottom: 10px;
            width: 400px;
            padding: 10px;
            border-radius: 5px;
            border: none;
            box-shadow: 0 0 1px 1px black;
            text-align: center;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            cursor: pointer;
            margin-right: 10px;
            border-radius: 5px;
            white-space: nowrap;
        }
        button:hover {
            background-color: #45a049;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        .action-buttons {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .pesquisa-container {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }
        .pesquisa-container input[type="text"] {
            width: 300px;
            padding: 10px;
            margin-bottom: 10px;
            text-align: center;
        }
        .pesquisa-container .button-container {
            display: flex;
            justify-content: center;
            width: 400px;
        }
        .pesquisa-container button {
            flex-grow: 1;
            margin-right: 10px;
        }
        .pesquisa-container button:last-child {
            margin-right: 0;
        }
        .editable {
            background-color: #f0f0f0;
        }
        .produto-imagem {
            max-width: 100px;
            max-height: 100px;
        }
        .editable input, .editable textarea {
            width: 100px; /* Define um tamanho fixo para os inputs e textareas */
            padding: 5px;
            box-sizing: border-box;
        }
        .editable textarea {
            height: 50px; /* Define uma altura fixa para os textareas */
        }
    </style>
</head>
<body>
    <h1>Gerenciamento de Estoque</h1>
    
    <?php if ($mensagem): ?>
        <p><?php echo $mensagem; ?></p>
    <?php endif; ?>

    <h2>Adicionar Produto</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="acao" value="adicionar">
        <input type="text" name="nome" placeholder="Nome do Produto" required>
        <textarea name="descricao" placeholder="Descrição do Produto" required></textarea>
        <input type="number" name="preco" placeholder="Preço" step="0.01" required>
        <input type="number" name="quantidade" placeholder="Quantidade" required>
        <input type="file" name="imagem" accept="image/*">
        <div class="action-buttons">
            <button type="submit">Adicionar Produto</button>
            <button type="button" onclick="window.location.href='loja.php'">Ir para a Loja</button>
            <button type="button" onclick="window.location.href='users.php'">Gerenciar Usuários</button>
        </div>
    </form>

    <h2>Produtos em Estoque</h2>
    <div class="pesquisa-container">
        <form method="get" action="" style="width: 100%;">
            <input type="text" name="pesquisa" placeholder="Pesquisar produto" value="<?php echo htmlspecialchars($termoPesquisa); ?>">
            <div class="button-container">
                <button type="submit">Buscar</button>
                <button type="button" onclick="toggleEdit()">Editar produtos</button>
            </div>
        </form>
    </div>
    <table id="produtosTable">
        <tr>
            <th>Imagem</th>
            <th>Nome</th>
            <th>Descrição</th>
            <th>Preço</th>
            <th>Quantidade</th>
            <th>Ação</th>
        </tr>
        <?php foreach ($produtosFiltrados as $indice => $produto): ?>
            <tr>
                <td>
                    <?php if (isset($produto['imagem']) && file_exists($produto['imagem'])): ?>
                        <img src="<?php echo $produto['imagem']; ?>" alt="Imagem do produto" class="produto-imagem">
                    <?php else: ?>
                        <span>Sem imagem</span>
                    <?php endif; ?>
                    <input type="file" name="imagem" accept="image/*" style="display: none;">
                </td>
                <td><span><?php echo $produto['nome']; ?></span><input type="text" name="nome" value="<?php echo $produto['nome']; ?>" style="display: none;"></td>
                <td><span><?php echo $produto['descricao']; ?></span><textarea name="descricao" style="display: none;"><?php echo $produto['descricao']; ?></textarea></td>
                <td><span>R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></span><input type="number" name="preco" value="<?php echo $produto['preco']; ?>" step="0.01" style="display: none;"></td>
                <td><span><?php echo $produto['quantidade']; ?></span><input type="number" name="quantidade" value="<?php echo $produto['quantidade']; ?>" style="display: none;"></td>
                <td>
                    <form method="post" style="margin: 0;" enctype="multipart/form-data">
                        <input type="hidden" name="acao" value="remover">
                        <input type="hidden" name="indice" value="<?php echo $indice; ?>">
                        <button type="submit" class="remover">Remover</button>
                        <button type="button" class="salvar" style="display: none;" onclick="salvarEdicao(this, <?php echo $indice; ?>)">Salvar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <script>
        function toggleEdit() {
            const table = document.getElementById('produtosTable');
            const rows = table.getElementsByTagName('tr');
            const isEditing = table.classList.toggle('editing');

            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                for (let j = 0; j < cells.length - 1; j++) {
                    const span = cells[j].getElementsByTagName('span')[0];
                    const input = cells[j].getElementsByTagName('input')[0] || cells[j].getElementsByTagName('textarea')[0];
                    
                    if (isEditing) {
                        if (span) span.style.display = 'none';
                        if (input) input.style.display = 'block';
                        cells[j].classList.add('editable');
                    } else {
                        if (span) span.style.display = 'block';
                        if (input) input.style.display = 'none';
                        cells[j].classList.remove('editable');
                    }
                }
                
                const removerBtn = cells[5].querySelector('.remover');
                const salvarBtn = cells[5].querySelector('.salvar');
                
                if (isEditing) {
                    removerBtn.style.display = 'none';
                    salvarBtn.style.display = 'inline-block';
                } else {
                    removerBtn.style.display = 'inline-block';
                    salvarBtn.style.display = 'none';
                }
            }
        }

        function salvarEdicao(button, indice) {
            const row = button.closest('tr');
            const formData = new FormData();
            formData.append('acao', 'editar');
            formData.append('indice', indice);
            formData.append('nome', row.querySelector('input[name="nome"]').value);
            formData.append('descricao', row.querySelector('textarea[name="descricao"]').value);
            formData.append('preco', row.querySelector('input[name="preco"]').value);
            formData.append('quantidade', row.querySelector('input[name="quantidade"]').value);
            
            const imagemInput = row.querySelector('input[name="imagem"]');
            if (imagemInput.files.length > 0) {
                formData.append('imagem', imagemInput.files[0]);
            }

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(response => response.text())
              .then(html => {
                  document.open();
                  document.write(html);
                  document.close();
              });
        }

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
    </script>
</body>
</html>
