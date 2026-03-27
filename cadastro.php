<?php
session_start();
include 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$erro = $sucesso = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = mysqli_real_escape_string($conn, $_POST['nome']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    
    // Verifica email duplicado
    $check = mysqli_query($conn, "SELECT id FROM usuarios WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $erro = 'Email já cadastrado!';
    } else {
        $query = "INSERT INTO usuarios (nome, email, senha) VALUES ('$nome', '$email', '$senha')";
        if (mysqli_query($conn, $query)) {
            $sucesso = 'Cadastro realizado! Faça login.';
        } else {
            $erro = 'Erro ao cadastrar. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <title>Cadastro - Loja Gamer</title>
    <link rel="stylesheet" href="style.css">
    <style>.form-login h2 { color: #ff4500; } .sucesso { color: #44ff44; text-align: center; }</style>
</head>
<body>
    <div class="form-login">
        <h2>👤 Criar Conta</h2>
        <?php if ($erro): ?><div class="erro"><?php echo $erro; ?></div><?php endif; ?>
        <?php if ($sucesso): ?><div class="sucesso"><?php echo $sucesso; ?></div><?php endif; ?>
        <form method="POST">
            <input type="text" name="nome" placeholder="Nome Completo" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="senha" placeholder="Senha (mín. 6 chars)" minlength="6" required>
            <button type="submit" class="btn">Cadastrar</button>
        </form>
        <div class="cadastro-link">
            <a href="login.php">Já tem conta? Faça login!</a>
        </div>
        <hr>
        <a href="index.php">← Voltar à Loja</a>
    </div>
</body>
</html>
