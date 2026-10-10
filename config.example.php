<?php
// Modelo de configuração. Copie para config.php e ajuste ao ambiente.
// config.php NÃO é versionado (ver .gitignore): guarda a conexão de cada máquina/servidor.
//
// Use um usuário com privilégio mínimo (apenas DML), nunca root. Crie-o uma vez:
//   CREATE USER 'convisita_app'@'localhost' IDENTIFIED BY '<senha forte>';
//   GRANT SELECT, INSERT, UPDATE, DELETE ON convisita.* TO 'convisita_app'@'localhost';
// A instalação/upgrade (DDL) roda separadamente com credencial administrativa.
return [
    'db_host'    => '127.0.0.1',
    'db_porta'   => 3306,
    'db_nome'    => 'convisita',
    'db_usuario' => 'convisita_app',
    'db_senha'   => 'TROQUE_POR_SENHA_FORTE',
    // Nome exibido no topo do sistema
    'titulo'     => 'COMSAÚDE Americana | Visitas às Unidades de Saúde',
    // Permite rodar instalar.php mesmo com o banco já criado (apaga e recria tudo)
    'permitir_reinstalar' => false,
];
