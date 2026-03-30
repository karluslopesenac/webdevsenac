<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title> Newage Loja Gamer</title>
    <link rel="stylesheet" href="style.css">
    <?php 
session_start(); 
if (isset($_SESSION['user_id'])) {
    echo "<span>Olá, {$_SESSION['user_nome']}! </span>";
    echo "<a href='logout.php'>Sair</a>";
} else {
    echo "<a href='login.php'>Login/Cadastro</a>";
}
?>
</head>
<body>
    <header>
        <h1>Newage Loja Gamer Pro</h1>
        <nav><a href="carrinho.php">Carrinho</a> | <a href="login.php">Login</a></nav>
    </header>
    <section id="produtos">
        <?php
        include 'config.php';
        $result = mysqli_query($conn, "SELECT * FROM produtos");
        while($row = mysqli_fetch_assoc($result)) {
            echo "<div class='produto'>
                <img src='img/{$row['imagem']}' alt='{$row['nome']}'>
                <h3>{$row['nome']}</h3>
                <p>R$ {$row['preco']}</p>
                <button onclick='addCarrinho({$row['id']})'>Adicionar</button>
            </div>";
        }
        ?>
    </section>
    <script src="script.js"></script>
</body>
</html>
