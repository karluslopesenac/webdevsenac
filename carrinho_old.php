<?php
session_start();
include 'config.php';

if (!isset($_SESSION['carrinho'])) $_SESSION['carrinho'] = [];

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'add':
            $id = $_POST['id'];
            $produto = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM produtos WHERE id=$id"));
            if (isset($_SESSION['carrinho'][$id])) {
                $_SESSION['carrinho'][$id]['qtd'] += 1;
            } else {
                $_SESSION['carrinho'][$id] = ['nome' => $produto['nome'], 'preco' => $produto['preco'], 'qtd' => 1, 'imagem' => $produto['imagem']];
            }
            break;
        case 'remove':
            unset($_SESSION['carrinho'][$_GET['id']]);
            break;
        case 'empty':
            $_SESSION['carrinho'] = [];
            break;
    }
    header('Location: carrinho.php');
    exit;
}

$total = 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <title>Carrinho</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header><a href="index.php">Produtos</a> | <a href="carrinho.php">Carrinho</a></header>
    <h2>Carrinho de Compras</h2>
    <?php if (empty($_SESSION['carrinho'])): ?>
        <p>Carrinho vazio!</p>
    <?php else: ?>
        <table>
            <tr><th>Imagem</th><th>Produto</th><th>Preço</th><th>Qtde</th><th>Total</th><th>Ações</th></tr>
            <?php foreach ($_SESSION['carrinho'] as $id => $item): 
                $subtotal = $item['preco'] * $item['qtd']; $total += $subtotal;
            ?>
                <tr>
                    <td><img src="img/<?php echo $item['imagem']; ?>" width="50"></td>
                    <td><?php echo $item['nome']; ?></td>
                    <td>R$ <?php echo $item['preco']; ?></td>
                    <td><?php echo $item['qtd']; ?></td>
                    <td>R$ <?php echo number_format($subtotal, 2); ?></td>
                    <td><button onclick="removerItem(<?php echo $id; ?>)">Remover</button></td>
                </tr>
            <?php endforeach; ?>
            <tr><td colspan="4"><strong>Total: R$ <?php echo number_format($total, 2); ?></strong></td><td colspan="2"><button onclick="esvaziarCarrinho()">Esvaziar</button></td></tr>
        </table>
        <?php if (!empty($_SESSION['carrinho']) && isset($_POST['checkout'])): 
    // Processa pedido
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $endereco = $_POST['endereco'];
    
    mysqli_query($conn, "INSERT INTO pedidos (nome_cliente, email, endereco, total) VALUES ('$nome', '$email', '$endereco', $total)");
    $pedido_id = mysqli_insert_id($conn);
    
    foreach ($_SESSION['carrinho'] as $id => $item) {
        mysqli_query($conn, "INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unit) VALUES ($pedido_id, $id, {$item['qtd']}, {$item['preco']})");
    }
    
    unset($_SESSION['carrinho']);
    $msg = "Pedido #$pedido_id confirmado! Total: R$ " . number_format($total, 2);
endif;
?>
<form method="POST">
    <h3>Checkout</h3>
    <input type="text" name="nome" placeholder="Nome completo" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <textarea name="endereco" placeholder="Endereço completo" required></textarea><br>
    <button type="submit" name="checkout">Finalizar Compra (Simulado)</button>
</form>
<?php if (isset($msg)) echo "<p style='color:green;'>$msg</p>"; ?>

    <?php endif; ?>
    <script src="script.js"></script>
</body>
</html>
