<?php
session_start();
include 'config.php';

if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    session_unset();
    session_destroy();
    // Opcional: remover cookies de login aqui
    header("Location: admin.php"); // Redireciona para o login
    exit;
}

// Login Admin Simples
if (!isset($_SESSION['admin']) && !isset($_POST['senha'])) {
    ?>
    <!DOCTYPE html><html><body>
    <form method="POST">
        <h2>Admin Login</h2>
        <input type="password" name="senha" placeholder="Senha Admin" required>
        <button type="submit">Entrar</button>
    </form></body></html>
    <?php exit;
}

if (isset($_POST['senha']) && $_POST['senha'] === 'admin123') {
    $_SESSION['admin'] = true;
} elseif (!isset($_SESSION['admin'])) {
    die('Acesso negado!');
}
// GERENCIAMENTO USUÁRIOS
if (isset($_POST['acao_user']) && isset($_SESSION['admin_id'])) {
    $user_id = (int)$_POST['user_id'];
    $admin_id = $_SESSION['admin_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    
    switch ($_POST['acao_user']) {
        case 'ativar':
            mysqli_query($conn, "UPDATE usuarios SET ativo = 1 WHERE id = $user_id");
            mysqli_query($conn, "INSERT INTO usuario_logs (user_id, admin_id, acao, detalhes, ip) VALUES ($user_id, $admin_id, 'ativado', 'Admin reativou conta', '$ip')");
            break;
            
        case 'inativar':
            mysqli_query($conn, "UPDATE usuarios SET ativo = 0 WHERE id = $user_id");
            mysqli_query($conn, "INSERT INTO usuario_logs (user_id, admin_id, acao, detalhes, ip) VALUES ($user_id, $admin_id, 'banido', 'Admin baniu conta', '$ip')");
            break;
            
        case 'reset_senha':
            $nova_senha = password_hash('123456', PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE usuarios SET senha = '$nova_senha' WHERE id = $user_id");
            mysqli_query($conn, "INSERT INTO usuario_logs (user_id, admin_id, acao, detalhes, ip) VALUES ($user_id, $admin_id, 'reset_senha', 'Senha resetada para 123456', '$ip')");
            break;
    }
    header('Location: admin.php#usuarios');
    exit;
}
// EXCLUSÃO DE USUÁRIOS
if (isset($_POST['deletar_usuario']) && isset($_SESSION['admin_id'])) {
    $user_id = (int)$_POST['user_id'];
    $admin_id = $_SESSION['admin_id'];
    $confirmado = $_POST['confirmar'] ?? 0;
    
    if (!$confirmado) {
        // Mostra confirmação
        $_SESSION['confirm_del_user'] = $user_id;
        $_SESSION['confirm_del_msg'] = "Confirmar exclusão do usuário ID $user_id? Todos pedidos serão excluídos.";
        header('Location: admin.php#confirm_del_user');
        exit;
    }
    
    // Backup dados antes de deletar
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM usuarios WHERE id = $user_id"));
    $pedidos_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pedidos WHERE user_id = $user_id"))['total'];
    
    // Log da exclusão
    $ip = $_SERVER['REMOTE_ADDR'];
    mysqli_query($conn, "INSERT INTO usuario_logs (user_id, admin_id, acao, detalhes, ip) VALUES ($user_id, $admin_id, 'excluido', 'Excluído por admin. Pedidos: $pedidos_count', '$ip')");
    
    // Deleta CASCAATA: pedidos → itens → usuário
    mysqli_query($conn, "DELETE ip FROM itens_pedido ip JOIN pedidos p ON ip.pedido_id = p.id WHERE p.user_id = $user_id");
    mysqli_query($conn, "DELETE FROM pedidos WHERE user_id = $user_id");
    mysqli_query($conn, "DELETE FROM usuarios WHERE id = $user_id");
    
    // Limpa confirmação
    unset($_SESSION['confirm_del_user'], $_SESSION['confirm_del_msg']);
    
    // Mensagem sucesso
    $_SESSION['msg_sucesso'] = "Usuário ID $user_id excluído com $pedidos_count pedidos relacionados!";
    header('Location: admin.php#usuarios');
    exit;
}

// CRUD Produtos
if (isset($_POST['acao'])) {
    switch ($_POST['acao']) {
        case 'add':
            $nome = mysqli_real_escape_string($conn, $_POST['nome']);
            $preco = $_POST['preco'];
            $imagem = $_FILES['imagem']['name'];
            $descricao = mysqli_real_escape_string($conn, $_POST['descricao']);
            move_uploaded_file($_FILES['imagem']['tmp_name'], "img/" . $imagem);
            mysqli_query($conn, "INSERT INTO produtos (nome, preco, imagem, descricao) VALUES ('$nome', $preco, '$imagem', '$descricao')");
            break;
        case 'edit':
            $id = $_POST['id'];
            $nome = mysqli_real_escape_string($conn, $_POST['nome']);
            $preco = $_POST['preco'];
            $descricao = mysqli_real_escape_string($conn, $_POST['descricao']);
            mysqli_query($conn, "UPDATE produtos SET nome='$nome', preco=$preco, descricao='$descricao' WHERE id=$id");
            break;
        case 'delete':
            $id = $_POST['id'];
            $produto = mysqli_fetch_assoc(mysqli_query($conn, "SELECT imagem FROM produtos WHERE id=$id"));
            unlink("img/" . $produto['imagem']);
            mysqli_query($conn, "DELETE FROM produtos WHERE id=$id");
            break;
    }
}

// Listar Produtos
$produtos = mysqli_query($conn, "SELECT * FROM produtos ORDER BY id DESC");
$pedidos = mysqli_query($conn, "SELECT * FROM pedidos ORDER BY data DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Admin - Loja Gamer</title>
    <style>
        body { background: #222; color: #fff; font-family: Arial; }
        .section { margin: 20px; padding: 20px; background: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #666; padding: 8px; text-align: left; }
        form { display: flex; flex-wrap: wrap; gap: 10px; align-items: end; }
        input, textarea, button { padding: 8px; background: #444; color: #fff; border: 1px solid #666; }
        button { background: #ff4500; cursor: pointer; }
        .produtos td:last-child button { background: #dc3545; margin-right: 5px; }
        .edit-form { background: #444; padding: 15px; margin: 10px 0; }
    </style>
</head>
<body>
<header style="background: #ff4500; padding: 15px;">
        <h1>🔧 Painel Admin</h1>
        <a href="index.php" style="color:#fff;">← Voltar Loja</a> | 
        <a href="admin.php?logout=1" style="color:#fff;">Sair</a>
    </header>

    <!-- Adicionar Produto -->
    <div class="section">
        <h2>➕ Novo Produto</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="acao" value="add">
            <input type="text" name="nome" placeholder="Nome do Produto" required>
            <input type="number" name="preco" step="0.01" placeholder="Preço" required>
            <input type="file" name="imagem" accept="image/*" required>
            <textarea name="descricao" placeholder="Descrição"></textarea>
            <button type="submit">Adicionar</button>
        </form>
    </div>

    <!-- Listar/Editar Produtos -->
    <div class="section">
        <h2>📦 Produtos (<?=mysqli_num_rows($produtos)?>)</h2>
        <table>
            <tr><th>ID</th><th>Imagem</th><th>Nome</th><th>Preço</th><th>Descrição</th><th>Ações</th></tr>
            <?php while($p = mysqli_fetch_assoc($produtos)): ?>
            <tr>
                <td><?=$p['id']?></td>
                <td><img src="img/<?=$p['imagem']?>" width="50" onerror="this.src='img/default.jpg'"></td>
                <td><?=$p['nome']?></td>
                <td>R$ <?=number_format($p['preco'],2)?></td>
                <td><?=substr($p['descricao'],0,50)?>...</td>
                <td>
                    <form method="POST" class="edit-form" style="display:inline;">
                        <input type="hidden" name="acao" value="edit">
                        <input type="hidden" name="id" value="<?=$p['id']?>">
                        <input type="text" name="nome" value="<?=$p['nome']?>" size="15">
                        <input type="number" name="preco" value="<?=$p['preco']?>" step="0.01" size="8">
                        <textarea name="descricao" rows="2"><?=$p['descricao']?></textarea>
                        <button>Salvar</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="acao" value="delete">
                        <input type="hidden" name="id" value="<?=$p['id']?>">
                        <button onclick="return confirm('Deletar?')">🗑️</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <!-- Últimos Pedidos -->
    <div class="section">
        <h2>📋 Últimos Pedidos</h2>
        <table>
            <tr><th>ID</th><th>Cliente</th><th>Total</th><th>Data</th><th>Status</th></tr>
            <?php while($ped = mysqli_fetch_assoc($pedidos)): ?>
            <tr>
                <td><?=$ped['id']?></td>
                <td><?=$ped['nome_cliente']?> (<?=$ped['email']?>)</td>
                <td>R$ <?=number_format($ped['total'],2)?></td>
                <td><?=date('d/m H:i', strtotime($ped['data']))?></td>
                <td><?=$ped['status']?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <a href="relatorio_pedidos.php">Ver todos ➔</a>
    </div>
</body>
</html>

<!-- GERENCIAMENTO DE USUÁRIOS -->
<div class="section">
    <h2>👥 Usuários (<?=mysqli_num_rows(mysqli_query($conn, "SELECT id FROM usuarios"))?>)</h2>
    
    <!-- Filtro -->
    <?php 
    $filtro = $_GET['filtro'] ?? 'todos';
    $where = '';
    if ($filtro == 'ativos') $where = 'WHERE ativo = 1';
    elseif ($filtro == 'inativos') $where = 'WHERE ativo = 0';
    $usuarios = mysqli_query($conn, "SELECT u.*, COUNT(p.id) as pedidos FROM usuarios u LEFT JOIN pedidos p ON u.id = p.id $where GROUP BY u.id ORDER BY u.criado_em DESC");
    ?>
    
    <div style="margin-bottom: 15px;">
        <a href="?filtro=todos" style="margin-right:10px; padding:5px 10px; background:#444; color:#fff; text-decoration:none;">Todos</a>
        <a href="?filtro=ativos" style="margin-right:10px; padding:5px 10px; background:#44ff44; color:#000;">Ativos</a>
        <a href="?filtro=inativos" style="margin-right:10px; padding:5px 10px; background:#ff4444; color:#fff;">Inativos</a>
    </div>
    
    <table>
        <tr>
            <th>ID</th><th>Foto</th><th>Nome</th><th>Email</th><th>Pedidos</th><th>Cadastrado</th><th>Status</th><th>Ações</th>
        </tr>
        <?php while($user = mysqli_fetch_assoc($usuarios)): ?>
        <tr style="background: <?=($user['ativo'] ? '#444' : '#666')?>;">
            <td><?=$user['id']?></td>
            <td><div style="width:40px;height:40px;background:#555;border-radius:50%;display:flex;align-items:center;justify-content:center;">👤</div></td>
            <td><strong><?=htmlspecialchars($user['nome'])?></strong></td>
            <td><?=htmlspecialchars($user['email'])?></td>
            <td><?=$user['pedidos']?></td>
            <td><?=date('d/m/y', strtotime($user['criado_em']))?></td>
            <td>
                <?php if($user['ativo']): ?>
                    <span style="color:#44ff44;">✅ Ativo</span>
                <?php else: ?>
                    <span style="color:#ff4444;">❌ Inativo</span>
                <?php endif; ?>
            </td>
            <td>
    <!-- Ações existentes (Ativar/Reset) -->
    <form method="POST" style="display:inline;">
        <input type="hidden" name="acao_user" value="<?=$user['ativo'] ? 'inativar' : 'ativar'?>">
        <input type="hidden" name="user_id" value="<?=$user['id']?>">
        <button style="background:<?=($user['ativo'] ? '#ff4444' : '#44ff44')?>; padding:3px 8px; font-size:12px;">
            <?=($user['ativo'] ? '❌ Banir' : '✅ Ativar')?>
        </button>
    </form>
    
    <!-- Reset Senha -->
    <form method="POST" style="display:inline; margin-left:5px;">
        <input type="hidden" name="acao_user" value="reset_senha">
        <input type="hidden" name="user_id" value="<?=$user['id']?>">
        <button style="background:#ffaa00; padding:3px 8px; font-size:12px;" onclick="return confirm('Resetar senha para 123456?')">🔑 Reset</button>
    </form>
    
    <!-- EXCLUIR -->
    <form method="POST" style="display:inline; margin-left:5px;">
        <input type="hidden" name="deletar_usuario" value="<?=$user['id']?>">
        <input type="hidden" name="confirmar" value="0">
        <button type="submit" style="background:#dc3545; color:#fff; padding:3px 8px; font-size:12px; border:none;" 
                onclick="return confirm('ATENÇÃO: Excluir usuário <?=$user['nome']?> (ID <?=$user['id']?>)?\nPedidos (<?=$user['pedidos']?>) serão PERDIDOS permanentemente!')">💀 EXCLUIR</button>
    </form>
    
    <a href="user_detalhes.php?id=<?=$user['id']?>" style="background:#007bff; color:#fff; padding:3px 8px; text-decoration:none; font-size:12px;">👁️</a>
</td>            
        </tr>
        <?php endwhile; ?>
    </table>
</div>
<script>
document.querySelectorAll('button[onclick*="EXCLUIR"]').forEach(btn => {
    btn.style.fontWeight = 'bold';
    btn.style.border = '2px solid #ff0000';
});

// Auto-hide mensagem sucesso após 5s
setTimeout(() => {
    const msg = document.querySelector('.msg-sucesso');
    if (msg) msg.style.display = 'none';
}, 5000);
</script>
<!-- GERENCIAMENTO DE PEDIDOS -->
<div class="section">
    <h2>📦 Pedidos (<?=mysqli_num_rows(mysqli_query($conn, "SELECT id FROM pedidos"))?>)</h2>
    
    <!-- Filtros e Busca -->
    <div style="margin-bottom: 20px;">
        <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" name="busca" placeholder="Buscar por ID/Cliente" value="<?=$_GET['busca']??''?>" style="padding:8px;">
            <select name="status">
                <option value="">Todos Status</option>
                <option value="Pendente" <?=($_GET['status']??'')=='Pendente'?'selected':''?>>Pendente</option>
                <option value="Enviado" <?=($_GET['status']??'')=='Enviado'?'selected':''?>>Enviado</option>
                <option value="Entregue" <?=($_GET['status']??'')=='Entregue'?'selected':''?>>Entregue</option>
                <option value="Cancelado" <?=($_GET['status']??'')=='Cancelado'?'selected':''?>>Cancelado</option>
            </select>
            <button type="submit" style="background:#ff4500;padding:8px 15px;color:#fff;border:none;">🔍 Buscar</button>
            <a href="export_pedidos.php" style="background:#28a745;padding:8px 15px;color:#fff;text-decoration:none;">📊 Export CSV</a>
            <a href="?reset=1" style="background:#6c757d;padding:8px 15px;color:#fff;text-decoration:none;" onclick="return confirm('Limpar filtros?')">🔄 Limpar</a>
        </form>
    </div>
    
    <!-- Seleção em Massa -->
    <form method="POST" id="form-pedidos">
        <div style="margin-bottom: 10px;">
            <label><input type="checkbox" id="selecionar-todos"> Selecionar Todos</label> |
            <select name="acao_massa">
                <option value="">Ação em Massa</option>
                <option value="enviar">Marcar como Enviado</option>
                <option value="entregue">Marcar como Entregue</option>
                <option value="cancelar">Cancelar Pedidos</option>
                <option value="deletar">🗑️ Deletar Pedidos</option>
            </select>
            <button type="submit" style="background:#dc3545;color:#fff;border:none;padding:5px 15px;" onclick="return confirm('Confirmar ação?')">Aplicar</button>
        </div>
    
        <?php
        // Filtros
        $where = [];
        if ($_GET['busca'] ?? '') {
            $busca = mysqli_real_escape_string($conn, $_GET['busca']);
            $where[] = "(p.id LIKE '%$busca%' OR u.nome LIKE '%$busca%' OR u.email LIKE '%$busca%')";
        }
        if ($_GET['status'] ?? '') $where[] = "p.status = '" . mysqli_real_escape_string($conn, $_GET['status']) . "'";
        
        $sql = "SELECT p.*, u.nome as cliente_nome, u.email FROM pedidos p 
                LEFT JOIN usuarios u ON p.id = u.id " . 
                (count($where) ? 'WHERE ' . implode(' AND ', $where) : '') . 
                " ORDER BY p.data DESC LIMIT 50";
        $pedidos = mysqli_query($conn, $sql);
        ?>
        
        <table>
            <tr style="background:#ff4500;">
                <th><input type="checkbox" id="selecionar-cabecalho"></th>
                <th>ID</th><th>Cliente</th><th>Total</th><th>Itens</th><th>Data</th><th>Status</th><th>Ações</th>
            </tr>
            <?php while($pedido = mysqli_fetch_assoc($pedidos)): 
                $itens = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as qtd FROM itens_pedido WHERE pedido_id = {$pedido['id']}"));
            ?>
            <tr>
                <td><input type="checkbox" name="pedidos[]" value="<?=$pedido['id']?>"></td>
                <td><strong>#<?=$pedido['id']?></strong></td>
                <td><?=htmlspecialchars($pedido['cliente_nome'] ?? 'Convidado')?> <br><small><?=htmlspecialchars($pedido['email'] ?? '')?></small></td>
                <td><strong>R$ <?=number_format($pedido['total'], 2)?></strong></td>
                <td><?=$itens['qtd']?></td>
                <td><?=date('d/m H:i', strtotime($pedido['data']))?></td>
                <td>
                    <span class="status status-<?=$pedido['status']?>">
                        <?=match($pedido['status']) {
                            'Pendente' => '⏳ Pendente',
                            'Enviado' => '📤 Enviado', 
                            'Entregue' => '✅ Entregue',
                            'Cancelado' => '❌ Cancelado',
                            default => $pedido['status']
                        }?>
                    </span>
                </td>
                <td>
                    <!-- Muda Status Individual -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="acao_pedido" value="<?=$pedido['id']?>">
                        <select name="novo_status" onchange="this.form.submit()" style="padding:2px 5px;">
                            <option value="Pendente" <?=($pedido['status']=='Pendente'?'selected':'')?>>⏳ Pendente</option>
                            <option value="Enviado" <?=($pedido['status']=='Enviado'?'selected':'')?>>📤 Enviado</option>
                            <option value="Entregue" <?=($pedido['status']=='Entregue'?'selected':'')?>>✅ Entregue</option>
                            <option value="Cancelado" <?=($pedido['status']=='Cancelado'?'selected':'')?>>❌ Cancelado</option>
                        </select>
                    </form>
                    |
                    <!-- Deletar -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="deletar_pedido" value="<?=$pedido['id']?>">
                        <button type="submit" style="background:#dc3545;color:#fff;border:none;padding:2px 8px;font-size:12px;" onclick="return confirm('Deletar pedido #<?=$pedido['id']?>?')">🗑️</button>
                    </form>
                    |
                    <a href="pedido_detalhes.php?id=<?=$pedido['id']?>" style="color:#007bff;">👁️</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </form>
</div>

<style>
.status-Pendente { color: #ffc107; }
.status-Enviado { color: #17a2b8; }
.status-Entregue { color: #28a745; }
.status-Cancelado { color: #dc3545; }
</style>

<script>
document.getElementById('selecionar-cabecalho').onclick = function() {
    document.querySelectorAll('input[name="pedidos[]"]').forEach(cb => cb.checked = this.checked);
}
document.getElementById('selecionar-todos').onclick = function() {
    document.getElementById('selecionar-cabecalho').click();
}
</script>

