CREATE DATABASE newage;
USE newage;
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100),
    preco DECIMAL(10,2),
    imagem VARCHAR(200),
    descricao TEXT
);
INSERT INTO produtos (nome, preco, imagem, descricao) VALUES ('Mouse Gamer RGB', 150.00, 'mouse.jpg', 'Mouse com 16000 DPI'),('Teclado Gamer RGB', 450.00, 'teclado.webp', 'Teclado sem fio ABNT2'),('Microfone Gamer', 250.00, 'microfone.jpg', 'Microfone USB com redução de ruído'),('Placa de Vídeo NVIDIA RTX4090', 8.000,'placa_de_video.jpg','Aceleradora gráfica NVIDIA 24GB com Raytracing'), ('Headset Gamer RGB', 400.00, 'headset.jpg', 'Headset gamer com cabo para som 7.2');