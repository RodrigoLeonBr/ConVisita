<?php
// Modelo de configuração. Copie para config.php e ajuste ao ambiente.
// config.php NÃO é versionado (ver .gitignore): guarda a conexão de cada máquina/servidor.
return [
    'db_host'    => '127.0.0.1',
    'db_porta'   => 3306,
    'db_nome'    => 'convisita',
    'db_usuario' => 'root',
    'db_senha'   => '',
    // Nome exibido no topo do sistema
    'titulo'     => 'COMSAÚDE Americana | Visitas às Unidades de Saúde',
    // Permite rodar instalar.php mesmo com o banco já criado (apaga e recria tudo)
    'permitir_reinstalar' => false,
];
