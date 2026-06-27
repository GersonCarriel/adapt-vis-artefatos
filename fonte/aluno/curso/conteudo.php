<?php
// Exibir erros apenas para depuração (desative em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("../conexao.php");
//session_start();

$item_id     = $_GET['item_id'] ?? null;
$material_id = $_GET['material_id'] ?? null;

$usuario_id = $_SESSION['usuario_id'] ?? null;

if (!$item_id || !$usuario_id) {
    echo "<p>Item ou usuário não especificado.</p>";
    exit;
}

// Buscar item principal
$sqlItem = "SELECT id, dimensao_principal, conteudo_id, ordem, exibir_no_menu FROM itens_aula WHERE id = ?";

echo "<script>console.log('sqlItem==>' . $sqlItem  ' ');</script>";



$stmtItem = $conn->prepare($sqlItem);
$stmtItem->bind_param("i", $item_id);
$stmtItem->execute();
$resItem = $stmtItem->get_result();

if ($resItem->num_rows === 0) {
    echo "<p>Item não encontrado.</p>";
    exit;
}

$item = $resItem->fetch_assoc();

// ---------------------------
// Seleção do material
// ---------------------------
//$materialSelecionado = null;
//$materiais = [];

if (!empty($material_id)) {
    // Caminho 1: material escolhido explicitamente
    $sqlMaterial = "SELECT * FROM materiais_item WHERE id = ?";
    $stmtMaterial = $conn->prepare($sqlMaterial);
    $stmtMaterial->bind_param("i", $material_id);
    $stmtMaterial->execute();
    $resMaterial = $stmtMaterial->get_result();

    if ($resMaterial->num_rows > 0) {
        $materialSelecionado = $resMaterial->fetch_assoc();
    } else {
        // Marca que tentamos e não encontramos; segue com fallback
        $materialSelecionado = null;
    }
}

if (!$materialSelecionado) {
    // Caminho 2: sem material_id, carregar todos do item e aplicar fallback
    $sqlMatTodos = "SELECT * FROM materiais_item WHERE item_id = ?";
    $stmtMatTodos = $conn->prepare($sqlMatTodos);
    $stmtMatTodos->bind_param("i", $item_id);
    $stmtMatTodos->execute();
    $resMatTodos = $stmtMatTodos->get_result();

    while ($m = $resMatTodos->fetch_assoc()) {
        $materiais[] = $m;
        if (!$materialSelecionado && !empty($m['balanceado'])) {
            $materialSelecionado = $m;
        }
    }

    if (!$materialSelecionado && count($materiais) === 1) {
        $materialSelecionado = $materiais[0];
    }
    if (!$materialSelecionado && count($materiais) > 1) {
        $materialSelecionado = $materiais[0];
    }
}

// Aplicar dados do material ao item (corrigindo '??' quebrado)
if ($materialSelecionado) {
    $item['titulo']               = $materialSelecionado['titulo'] ?? 'Material sem título';
    $item['item_tipo']            = $materialSelecionado['tipo'] ?? 'texto';
    $item['url']                  = $materialSelecionado['url'] ?? '';
    $item['texto_html']           = $materialSelecionado['texto_html'] ?? '';
    $item['texto_pre_atividade']  = $materialSelecionado['texto_pre_atividade'] ?? '';
    $item['texto_pos_atividade']  = $materialSelecionado['texto_pos_atividade'] ?? '';
    $item['descricao_atividade']  = $materialSelecionado['descricao_atividade'] ?? '';
    $item['id_material']          = $materialSelecionado['id']; // importante para buscar filhos
} else {
    // Proteção para evitar erro no conteúdo
    $item['item_tipo']  = 'texto';
    $item['titulo']     = 'Material não encontrado';
    $item['texto_html'] = '<p><em>Este item ainda não possui material adaptado.</em></p>';
}

// ---------------------------
// Renderização
// ---------------------------

// Título
$tituloItem = $item['titulo'] ?? 'Material sem título';
echo "<h3>" . htmlspecialchars($tituloItem) . "</h3>";

// Texto pré-atividade
if (!empty($item['texto_pre_atividade'])) {
    echo '<div class="texto-pre">' . $item['texto_pre_atividade'] . '</div>';
}


// Quadro de conteúdo principal
echo '<div class="quadro-conteudo">';

    //echo "<p style='color: green;'>[LOG] Entrou no IF: id_material = {$item['id_material']}</p>";

// Verificação de erro com item_tipo
if (!isset($item['item_tipo'])) {
    echo '<div class="quadro-conteudo">';
    echo '<p><strong>Erro:</strong> Este item não possui tipo de material definido.</p>';
    echo '</div>';
    return;
}

switch ($item['item_tipo']) {
    case 'texto':

        echo '<div class="texto-principal">' . $item['texto_html'] . '</div>';

if (!empty($item['id_material'])) {
    // Verifica se já existe interação
    $stmtCheck = $conn->prepare("SELECT id FROM interacoes_aluno WHERE usuario_id = ? AND item_id = ?");
    $stmtCheck->bind_param("ii", $usuario_id, $item_id);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows === 0) {
        // Se não existe, insere com data_acesso
        $sqlInteracao = "INSERT INTO interacoes_aluno (usuario_id, item_id, material_id, data_acesso)
                         VALUES (?, ?, ?, NOW())";
        $stmtInteracao = $conn->prepare($sqlInteracao);
        $stmtInteracao->bind_param("iii", $usuario_id, $item_id, $item['id_material']);
        $stmtInteracao->execute();
    }
    // Se já existe, não atualiza data_acesso
}


        break;

    case 'imagem':

if (!empty($item['id_material'])) {
    // Verifica se já existe interação
    $stmtCheck = $conn->prepare("SELECT id FROM interacoes_aluno WHERE usuario_id = ? AND item_id = ?");
    $stmtCheck->bind_param("ii", $usuario_id, $item_id);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows === 0) {
        // Se não existe, insere com data_acesso
        $sqlInteracao = "INSERT INTO interacoes_aluno (usuario_id, item_id, material_id, data_acesso)
                         VALUES (?, ?, ?, NOW())";
        $stmtInteracao = $conn->prepare($sqlInteracao);
        $stmtInteracao->bind_param("iii", $usuario_id, $item_id, $item['id_material']);
        $stmtInteracao->execute();
    }
    // Se já existe, não atualiza data_acesso
}


        echo '<img src="' . htmlspecialchars($item['url']) . '" alt="Imagem" style="max-width:100%; border-radius:4px;">';
        break;

    case 'arquivo':

if (!empty($item['id_material'])) {
    // Verifica se já existe interação
    $stmtCheck = $conn->prepare("SELECT id FROM interacoes_aluno WHERE usuario_id = ? AND item_id = ?");
    $stmtCheck->bind_param("ii", $usuario_id, $item_id);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows === 0) {
        // Se não existe, insere com data_acesso
        $sqlInteracao = "INSERT INTO interacoes_aluno (usuario_id, item_id, material_id, data_acesso)
                         VALUES (?, ?, ?, NOW())";
        $stmtInteracao = $conn->prepare($sqlInteracao);
        $stmtInteracao->bind_param("iii", $usuario_id, $item_id, $item['id_material']);
        $stmtInteracao->execute();
    }
    // Se já existe, não atualiza data_acesso
}


        $arquivo = !empty($item['caminho_local']) ? $item['caminho_local'] : $item['url'];
        if (!empty($arquivo)) {
            $nomeArquivo = basename($arquivo);
            echo '<p><a href="download_atividade.php?arquivo=' . urlencode($nomeArquivo) . '" class="botao-acao">📁 Baixar material</a></p>';
        } else {
            echo '<p><em>Material não disponível.</em></p>';
        }
        break;

    case 'video':
if (!empty($item['id_material'])) {
    // Verifica se já existe interação
    $stmtCheck = $conn->prepare("SELECT id FROM interacoes_aluno WHERE usuario_id = ? AND item_id = ?");
    $stmtCheck->bind_param("ii", $usuario_id, $item_id);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows === 0) {
        // Se não existe, insere com data_acesso
        $sqlInteracao = "INSERT INTO interacoes_aluno (usuario_id, item_id, material_id, data_acesso)
                         VALUES (?, ?, ?, NOW())";
        $stmtInteracao = $conn->prepare($sqlInteracao);
        $stmtInteracao->bind_param("iii", $usuario_id, $item_id, $item['id_material']);
        $stmtInteracao->execute();
    }
    // Se já existe, não atualiza data_acesso
}


        $url = $item['url'] ?? '';
        if (strpos($url, 'watch?v=') !== false) {
            $url = str_replace('watch?v=', 'embed/', $url);
        }

        echo '<div style="width: 100%; display: flex; justify-content: center; margin: 20px 0;">
                <div style="max-width: 640px; width: 100%;">
                    <iframe width="100%" height="360" src="' . htmlspecialchars($url) . '" frameborder="0" allowfullscreen></iframe>
                </div>
              </div>';
        break;


    case 'checklist':
if (!empty($item['id_material'])) {
    // Verifica se já existe interação
    $stmtCheck = $conn->prepare("SELECT id FROM interacoes_aluno WHERE usuario_id = ? AND item_id = ?");
    $stmtCheck->bind_param("ii", $usuario_id, $item_id);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows === 0) {
        // Se não existe, insere com data_acesso
        $sqlInteracao = "INSERT INTO interacoes_aluno (usuario_id, item_id, material_id, data_acesso)
                         VALUES (?, ?, ?, NOW())";
        $stmtInteracao = $conn->prepare($sqlInteracao);
        $stmtInteracao->bind_param("iii", $usuario_id, $item_id, $item['id_material']);
        $stmtInteracao->execute();
    }
    // Se já existe, não atualiza data_acesso
}


        // Preparação para checklist
        if (($item['item_tipo'] ?? '') === 'checklist' && isset($item['id_material'])) {
            $material_id = $item['id_material'];
    
            $sqlChecklist = "SELECT id, texto, dica, foco FROM checklist_itens WHERE material_id = ? AND ativo = 1 ORDER BY ordem ASC";
            $stmtChecklist = $conn->prepare($sqlChecklist);
            $stmtChecklist->bind_param("i", $material_id);
            $stmtChecklist->execute();
            $resChecklist = $stmtChecklist->get_result();

            $item['checklist_itens'] = [];

            while ($linha = $resChecklist->fetch_assoc()) {
                // Verifica se o aluno já marcou esse item
                $sqlStatus = "SELECT checked FROM checklist_status_aluno WHERE material_id = ? AND checklist_item_id = ? AND usuario_id = ?";
                $stmtStatus = $conn->prepare($sqlStatus);
                $stmtStatus->bind_param("iii", $material_id, $linha['id'], $usuario_id);
                $stmtStatus->execute();
                $resStatus = $stmtStatus->get_result();
                $status = $resStatus->fetch_assoc();

                $item['checklist_itens'][] = [
                    'id'      => $linha['id'],
                    'texto'   => $linha['texto'],
                    'dica'    => $linha['dica'],
                    'foco'    => $linha['foco'],
                    'checked' => !empty($status['checked']) ? true : false
                ];
            }
        }

        echo '<div class="checklist-container">';
        // Barra de progresso
        echo '<div id="barraProgresso" class="progresso-container">';
        echo '<div id="progressoInterno" class="progresso-preenchido"></div>';
        echo '</div>';

        $idPrefix = 'item_' . $item_id . '_';
    
        foreach ($item['checklist_itens'] as $i => $linha) {
            $idDica  = $idPrefix . 'dica_' . $i;
            $idCheck = $idPrefix . 'check_' . $i;
            $isChecked = !empty($linha['checked']) ? ' checked' : '';

            echo '<div class="checklist-item">';
            echo '<input type="checkbox" class="checklist-checkbox" id="' . $idCheck . '"' . $isChecked .
                 ' data-item-id="' . $linha['id'] . '" data-material-id="' . $material_id . '">';
            echo '<label for="' . $idCheck . '">' . htmlspecialchars($linha['texto']) . '</label>';

            // Interrogação no final do texto, se houver dica
            if (!empty($linha['dica'])) {
                echo '<span class="hint-icon destaque-dica" onclick="toggleDica(event, \'' . $idDica . '\')">?</span>';
                echo '<div class="dica-tooltip" id="' . $idDica . '">' . htmlspecialchars($linha['dica']) . '</div>';
            }

            echo '</div>';
        }

        echo '</div>';

        // Chamada do JavaScript para ativar o monitoramento dos checkboxes
        echo '<script>monitorarChecklist();</script>';
        break;



case 'upload':

if (!empty($item['id_material'])) {
    // Verifica se já existe interação
    $stmtCheck = $conn->prepare("SELECT id FROM interacoes_aluno WHERE usuario_id = ? AND item_id = ?");
    $stmtCheck->bind_param("ii", $usuario_id, $item_id);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows === 0) {
        // Se não existe, insere com data_acesso
        $sqlInteracao = "INSERT INTO interacoes_aluno (usuario_id, item_id, material_id, data_acesso)
                         VALUES (?, ?, ?, NOW())";
        $stmtInteracao = $conn->prepare($sqlInteracao);
        $stmtInteracao->bind_param("iii", $usuario_id, $item_id, $item['id_material']);
        $stmtInteracao->execute();
    }
    // Se já existe, não atualiza data_acesso
}


    // Cria/garante o registro "fazendo" para (material_id, usuario_id)
    $sqlSeed = "INSERT INTO atividades_enviadas (material_id, usuario_id, status, data_inicio)
                VALUES (?, ?, 'fazendo', NOW())
                ON DUPLICATE KEY UPDATE status = status";
    $stmtSeed = $conn->prepare($sqlSeed);
    $stmtSeed->bind_param("ii", $material_id, $usuario_id);
    $stmtSeed->execute();

    // Busca estado atual (campos relevantes ao upload)
    $sqlCheck = "SELECT id, status, data_inicio, data_envio, tempo_execucao, nome_arquivo
                 FROM atividades_enviadas
                 WHERE material_id = ? AND usuario_id = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("ii", $material_id, $usuario_id);
    $stmtCheck->execute();
    $atividade = $stmtCheck->get_result()->fetch_assoc();

    echo '<p>Status da atividade: <strong>' . htmlspecialchars($atividade['status'] ?? 'fazendo') . '</strong></p>';
    if (!empty($atividade['nome_arquivo'])) {
        echo '<p><strong>Último envio:</strong> ' . htmlspecialchars($atividade['nome_arquivo']) . '</p>';
    }
    if (!empty($atividade['data_envio'])) {
        echo '<p>Enviado em: ' . date('d/m/Y H:i', strtotime($atividade['data_envio'])) . '</p>';
        echo '<p>Tempo de execução: ' . intval($atividade['tempo_execucao']) . ' minutos</p>';
    }

    // IDs únicos por material
    $fid = 'formUploadArquivo-' . intval($material_id);
    $mid = 'mensagemUploadArquivo-' . intval($material_id);

    // Formulário exclusivo para upload de arquivo (envio tradicional)
    echo '<form id="'.$fid.'" action="upload_atividade.php" method="post" enctype="multipart/form-data" style="margin-top:20px">';
    echo '<input type="hidden" name="return_to" value="'.htmlspecialchars($_SERVER['REQUEST_URI']).'">';
    echo '<input type="hidden" name="material_id" value="' . intval($material_id) . '">';
    echo '<label>Envie seu trabalho (arquivo):</label><br>';
    echo '<input type="file" name="arquivo" required><br><br>';
    if (!empty($atividade['nome_arquivo'])) {
        echo '<p style="color:#b00;font-weight:bold">⚠️ O novo envio substituirá o anterior.</p>';
    }
    echo '<button type="submit" class="botao-acao">Enviar arquivo</button>';
    echo '</form><div id="'.$mid.'" style="margin-top:15px"></div>';

    break;

case 'questao_aberta':
if (!empty($item['id_material'])) {
    // Verifica se já existe interação
    $stmtCheck = $conn->prepare("SELECT id FROM interacoes_aluno WHERE usuario_id = ? AND item_id = ?");
    $stmtCheck->bind_param("ii", $usuario_id, $item_id);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows === 0) {
        // Se não existe, insere com data_acesso
        $sqlInteracao = "INSERT INTO interacoes_aluno (usuario_id, item_id, material_id, data_acesso)
                         VALUES (?, ?, ?, NOW())";
        $stmtInteracao = $conn->prepare($sqlInteracao);
        $stmtInteracao->bind_param("iii", $usuario_id, $item_id, $item['id_material']);
        $stmtInteracao->execute();
    }
    // Se já existe, não atualiza data_acesso
}


    // Cria/garante o registro "fazendo" para (material_id, usuario_id)
    $sqlSeed = "INSERT INTO atividades_enviadas (material_id, usuario_id, status, data_inicio)
                VALUES (?, ?, 'fazendo', NOW())
                ON DUPLICATE KEY UPDATE status = status";
    $stmtSeed = $conn->prepare($sqlSeed);
    $stmtSeed->bind_param("ii", $material_id, $usuario_id);
    $stmtSeed->execute();

    // Busca estado atual (campos relevantes ao texto)
    $sqlCheck = "SELECT id, status, data_inicio, data_envio, tempo_execucao, texto_resposta
                 FROM atividades_enviadas
                 WHERE material_id = ? AND usuario_id = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("ii", $material_id, $usuario_id);
    $stmtCheck->execute();
    $atividade = $stmtCheck->get_result()->fetch_assoc();

    echo '<p>Status da atividade: <strong>' . htmlspecialchars($atividade['status'] ?? 'fazendo') . '</strong></p>';
    if (!empty($atividade['data_envio'])) {
        echo '<p>Enviado em: ' . date('d/m/Y H:i', strtotime($atividade['data_envio'])) . '</p>';
        echo '<p>Tempo de execução: ' . intval($atividade['tempo_execucao']) . ' minutos</p>';
    }
    if (!empty($atividade['texto_resposta'])) {
        echo '<p><strong>Resposta enviada anteriormente:</strong><br><i>' . nl2br(htmlspecialchars($atividade['texto_resposta'])) . '<i></p>';
    }

    // Enunciado
    //echo '<p><strong>Questão aberta:</strong></p>';
    echo '<br><p><h4>' . nl2br(htmlspecialchars($item["descricao_atividade"] ?? "")) . '<h4></p>';

    // IDs únicos por material
    $fid = 'formRespostaTexto-' . intval($material_id);
    $mid = 'mensagemRespostaTexto-' . intval($material_id);

    // Formulário exclusivo para texto (sem AJAX, envio tradicional)
    echo '<form id="'.$fid.'" action="upload_atividade.php" method="post" style="margin-top:20px">';
    echo '<input type="hidden" name="return_to" value="'.htmlspecialchars($_SERVER['REQUEST_URI']).'">';
    echo '<input type="hidden" name="material_id" value="' . intval($material_id) . '">';
//    echo '<label for="texto_resposta">Resposta escrita:</label><br>';
    echo '<textarea name="texto_resposta" id="texto_resposta" rows="6" cols="60" required></textarea><br><br>';
    if (!empty($atividade['texto_resposta'])) {
        echo '<p style="color:#b00;font-weight:bold">⚠️ O novo envio substituirá o anterior.</p>';
    }
    echo '<button type="submit" class="botao-acao">Enviar resposta</button>';
    echo '</form><div id="'.$mid.'" style="margin-top:15px"></div>';

    break;



    case 'quiz':
if (!empty($item['id_material'])) {
    // Verifica se já existe interação
    $stmtCheck = $conn->prepare("SELECT id FROM interacoes_aluno WHERE usuario_id = ? AND item_id = ?");
    $stmtCheck->bind_param("ii", $usuario_id, $item_id);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows === 0) {
        // Se não existe, insere com data_acesso
        $sqlInteracao = "INSERT INTO interacoes_aluno (usuario_id, item_id, material_id, data_acesso)
                         VALUES (?, ?, ?, NOW())";
        $stmtInteracao = $conn->prepare($sqlInteracao);
        $stmtInteracao->bind_param("iii", $usuario_id, $item_id, $item['id_material']);
        $stmtInteracao->execute();
    }
    // Se já existe, não atualiza data_acesso
}



        include "item_quiz.php";
        break;

    default:
        echo htmlspecialchars($item['item_tipo'] ?? 'tipo indefinido');
        echo "Erro ao carregar conteúdo...";
}

echo '</div>';

// Texto pós-atividade
if (!empty($item['texto_pos_atividade'])) {
    echo '<div class="texto-pos">' . $item['texto_pos_atividade'] . '</div>';
}

// Material complementar (filhos do material selecionado)
// Verificar e buscar materiais complementares
if (!empty($item['id_material'])) {
//    echo "<p style='color: green;'>[LOG] Entrou no IF: id_material = {$item['id_material']}</p>";

    $sqlExtras = "SELECT * FROM materiais_item WHERE item_pai_id = ?";
    $stmtExtras = $conn->prepare($sqlExtras);
    $stmtExtras->bind_param("i", $item['id_material']);
    $stmtExtras->execute();
    $resExtras = $stmtExtras->get_result();

    if ($resExtras->num_rows > 0) {
//        echo "<p style='color: green;'>[LOG] Materiais complementares encontrados: {$resExtras->num_rows}</p>";
        echo '<div class="extras-area">';
        echo '<h4>Material complementar</h4>';
        echo '<ul class="extras-list">';
        while ($extra = $resExtras->fetch_assoc()) {
            echo '<li>';
            if (!empty($extra['url'])) {
                echo '<a href="' . htmlspecialchars($extra['url']) . '" target="_blank">' . htmlspecialchars($extra['titulo']) . '</a>';
            } elseif (!empty($extra['texto_html'])) {
                echo '<div class="extra-texto"><strong>' . htmlspecialchars($extra['titulo']) . '</strong><br>' . $extra['texto_html'] . '</div>';
            } else {
                echo htmlspecialchars($extra['titulo']);
            }
            echo '</li>';
        }
        echo '</ul></div>';
    } else {
//        echo "<p style='color: orange;'>[LOG] Nenhum material complementar encontrado para id_material = {$item['id_material']}</p>";
    }
} else {
    echo "<p style='color: red;'>[LOG] NÃO entrou no IF: id_material está vazio ou indefinido</p>";
}
?>
