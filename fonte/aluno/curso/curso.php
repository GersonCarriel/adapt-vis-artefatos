<?php
//escolha do  material no menu.
function grupoDoGrau($grau) {
    if (in_array($grau, [9, 11])) return 'A';
    if (in_array($grau, [7, 5])) return 'B';
    if (in_array($grau, [3, 1])) return 'C';
    return null;
}


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


//session_start();
include("../conexao.php");

$sub = $_SESSION['sub'] ?? null;

if (!$sub) {
  echo "Sessão inválida. Sub não encontrado.";
  exit;
}

// Buscar o usuário
$sqlUsuario = "SELECT id, nome FROM usuarios WHERE lti_sub = ?";

$stmtUsuario = $conn->prepare($sqlUsuario);
$stmtUsuario->bind_param("i", $sub);
$stmtUsuario->execute();
$resUsuario = $stmtUsuario->get_result();
// (Opcional) Logs sem consumir o result set incorretamente
error_log("🔍 SUB recebido: " . print_r($sub, true));
error_log("🔍 SQL executado: SELECT id, nome FROM usuarios WHERE lti_sub = ?");

if ($resUsuario->num_rows === 0) {
  echo "Usuário não encontrado.";
  exit;
}

$usuario = $resUsuario->fetch_assoc();

$usuario_id = $usuario['id'];
$nome = $usuario['nome'];

// Buscar estilo cognitivo do aluno
// Buscar todos os estilos cognitivos do aluno
$sqlEstilo = "SELECT desc_dimensao, polo_dominante FROM perfil_cognitivo_usuario WHERE usuario_id = ?";

$stmtEstilo = $conn->prepare($sqlEstilo);
$stmtEstilo->bind_param("i", $usuario_id);
$stmtEstilo->execute();
$resEstilo = $stmtEstilo->get_result();

$estilosAluno = [];
while ($row = $resEstilo->fetch_assoc()) {
  $estilosAluno[strtolower($row['desc_dimensao'])] = $row['polo_dominante'];
}

// Buscar grau do aluno por dimensão e registrar perfil completo
$grausAluno = [];
$sqlGrau = "SELECT desc_dimensao, intensidade FROM perfil_cognitivo_usuario WHERE usuario_id = ?";
$stmtGrau = $conn->prepare($sqlGrau);
$stmtGrau->bind_param("i", $usuario_id);
$stmtGrau->execute();
$resGrau = $stmtGrau->get_result();

while ($g = $resGrau->fetch_assoc()) {
    $dim = strtolower($g['desc_dimensao']);
    $grausAluno[$dim] = $g['intensidade'];

    // Logar polo e grau do aluno para cada dimensão
    $polo = $estilosAluno[$dim] ?? 'N/A';
//    error_log("🧠 Perfil aluno: dimensão=$dim | polo=$polo | grau={$g['intensidade']}");
}




$_SESSION['usuario_id'] = $usuario_id; // manter a sessão ativa

// Buscar curso do aluno
$sqlCurso = "SELECT c.id AS curso_id, c.titulo AS curso_titulo
             FROM alunos a
             JOIN cursos c ON c.id = a.curso_id
             WHERE a.usuario_id = ?";
$stmtCurso = $conn->prepare($sqlCurso);
$stmtCurso->bind_param("i", $usuario_id);
$stmtCurso->execute();
$resCurso = $stmtCurso->get_result();

if ($resCurso->num_rows === 0) {
  echo "Curso não encontrado para o aluno.";
  exit;
}

$curso = $resCurso->fetch_assoc();
$curso_id = $curso['curso_id'];
$curso_titulo = $curso['curso_titulo'];

// Buscar todas as aulas do curso
$sqlTodasAulas = "SELECT id, titulo, ordem FROM aulas WHERE curso_id = ? ORDER BY ordem";
$stmtTodasAulas = $conn->prepare($sqlTodasAulas);
$stmtTodasAulas->bind_param("i", $curso_id);
$stmtTodasAulas->execute();
$resTodasAulas = $stmtTodasAulas->get_result();

$aulas = [];
while ($a = $resTodasAulas->fetch_assoc()) {
  $aulas[] = $a;
}

// Aula atual (via GET ou padrão para primeira)
$aula_id = $_GET['aula_id'] ?? $aulas[0]['id'];

// Garantir que o aluno tenha pelo menos a primeira aula liberada
$primeiraAulaId = $aulas[0]['id'];

$sqlVerificaInicial = "SELECT 1 FROM aulas_aluno WHERE usuario_id = ? AND aula_id = ?";
$stmtVerificaInicial = $conn->prepare($sqlVerificaInicial);
$stmtVerificaInicial->bind_param("ii", $usuario_id, $primeiraAulaId);
$stmtVerificaInicial->execute();
$resVerificaInicial = $stmtVerificaInicial->get_result();

if ($resVerificaInicial->num_rows === 0) {
  $sqlLiberaInicial = "INSERT INTO aulas_aluno (usuario_id, aula_id) VALUES (?, ?)";
  $stmtLiberaInicial = $conn->prepare($sqlLiberaInicial);
  $stmtLiberaInicial->bind_param("ii", $usuario_id, $primeiraAulaId);
  $stmtLiberaInicial->execute();
}
// Fim do Garantir que aluno tenha pelo menos a primeira aula liberada

$aula_titulo = '';
foreach ($aulas as $a) {
  if ($a['id'] == $aula_id) {
    $aula_titulo = $a['titulo'];
    break;
  }
}

// Buscar conteúdos e itens da aula atual
$sqlConteudos = "SELECT id, titulo FROM conteudos WHERE aula_id = ? ORDER BY ordem";
$stmtConteudos = $conn->prepare($sqlConteudos);
$stmtConteudos->bind_param("i", $aula_id);
$stmtConteudos->execute();
$resConteudos = $stmtConteudos->get_result();

$conteudos = [];
while ($row = $resConteudos->fetch_assoc()) {
  $conteudo_id = $row['id'];
  $row['itens'] = [];

$sqlItens = "SELECT i.id, i.dimensao_principal
             FROM itens_aula i
             WHERE i.conteudo_id = ? AND i.exibir_no_menu = TRUE
             ORDER BY i.ordem";


  $stmtItens = $conn->prepare($sqlItens);
  $stmtItens->bind_param("i", $conteudo_id);
  $stmtItens->execute();
  $resItens = $stmtItens->get_result();

  while ($item = $resItens->fetch_assoc()) {

    // Buscar material adaptado para o item
    $sqlMat = "SELECT * FROM materiais_item WHERE item_id = ? AND item_pai_id IS NULL";

    $stmtMat = $conn->prepare($sqlMat);
    $stmtMat->bind_param("i", $item['id']);
    $stmtMat->execute();
    $resMat = $stmtMat->get_result();

    $materialSelecionado = null;
    $materiais = [];

$dimensao = strtolower($item['dimensao_principal'] ?? '');
$poloPreferido = $estilosAluno[$dimensao] ?? null;
$grauIdeal = $grausAluno[$dimensao] ?? null;
$grupoIdeal = grupoDoGrau($grauIdeal);

while ($m = $resMat->fetch_assoc()) {
    $materiais[] = $m;
}

// Prioridade 1: mesmo grupo e polo
foreach ($materiais as $m) {
    if ($m['polo'] !== $poloPreferido) continue;

    $grupoMaterial = grupoDoGrau($m['grau']);
    if ($grupoMaterial === $grupoIdeal) {
        $materialSelecionado = $m;
        break;
    }
}






// Prioridade 2: mais próximo, se não achou no mesmo grupo
if (!$materialSelecionado) {
    foreach ($materiais as $m) {
        if ($m['item_pai_id'] !== null || $m['polo'] !== $poloPreferido) continue;
        $distancia = abs($m['grau'] - $grauIdeal);
        if (!$materialSelecionado || $distancia < abs($materialSelecionado['grau'] - $grauIdeal)) {
            $materialSelecionado = $m;
        }
    }
}
    



    if (!$materialSelecionado) {
        foreach ($materiais as $m) {
            if (!empty($m['balanceado'])) {
                $materialSelecionado = $m;
                break;
            }
        }
    }

    if (!$materialSelecionado && count($materiais) === 1) {
        $materialSelecionado = $materiais[0];
    }

    // Substituir campos do item pelo material adaptado
    if ($materialSelecionado) {
        if ($materialSelecionado['tipo'] === 'quiz') {
            continue; // Ignora quiz por enquanto
        }
        $item['titulo'] = $materialSelecionado['titulo'];
        $item['item_tipo'] = $materialSelecionado['tipo'];
        $item['url'] = $materialSelecionado['url'];
        $item['id_material'] = $materialSelecionado['id'];
//echo "<script>console.log('Item {$item['id']} recebeu material ID: {$materialSelecionado['id']}');</script>";
    }

    // Adicionar o item (já adaptado) ao menu
    $row['itens'][] = $item;

    // Buscar e exibir itens extras vinculados a este item
    $sqlExtras = "SELECT * FROM materiais_item WHERE item_pai_id = ?";
    $stmtExtras = $conn->prepare($sqlExtras);
    $stmtExtras->bind_param("i", $item['id']);
    $stmtExtras->execute();
    $resExtras = $stmtExtras->get_result();

    while ($extra = $resExtras->fetch_assoc()) {
      $sqlMatExtra = "SELECT * FROM materiais_item WHERE item_id = ?";
      $stmtMatExtra = $conn->prepare($sqlMatExtra);
      $stmtMatExtra->bind_param("i", $extra['id']);
      $stmtMatExtra->execute();
      $resMatExtra = $stmtMatExtra->get_result();

      $materialExtra = null;
      $materiaisExtra = [];

      while ($m = $resMatExtra->fetch_assoc()) {
        $materiaisExtra[] = $m;

$dimensaoExtra = strtolower($extra['dimensao_principal'] ?? '');
$poloExtraPreferido = $estilosAluno[$dimensaoExtra] ?? null;

if ($m['polo'] === $poloExtraPreferido) {
    $materialExtra = $m;
}


      }

      if (!$materialExtra) {
        foreach ($materiaisExtra as $m) {
          if (!empty($m['balanceado'])) {
            $materialExtra = $m;
            break;
          }
        }
      }

      if (!$materialExtra && count($materiaisExtra) === 1) {
        $materialExtra = $materiaisExtra[0];
      }

      if ($materialExtra) {
        $extra['titulo'] = $materialExtra['titulo'];
        $extra['item_tipo'] = $materialExtra['tipo'];
        $extra['url'] = $materialExtra['url'];
      }

      // Aqui você pode exibir/acumular os extras do item (ex.: $row['extras'][] = $extra;)
    }
  }

  $conteudos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sistema de Aprendizagem</title>
  <link rel="stylesheet" href="../css/styles.css" />
  <link rel="stylesheet" href="../css/curso-visualizacao.css" />
</head>
<body>
  <div class="container">
    <!-- Header -->
    <header class="header">
      <div class="user-info">Usuário: <?php echo htmlspecialchars($nome); ?></div>
      <div class="course-title">Curso: <?php echo htmlspecialchars($curso_titulo); ?></div>
      <div class="professor-badge">Aluno</div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
      <!-- Left Panel -->
      <section class="course-panel">
        <div class="lesson-section">

          <!-- Navegação por abas -->
          <div class="aula-navegacao-wrapper">
            <?php
            // Buscar aulas liberadas para o aluno
            $sqlLiberadas = "SELECT aula_id FROM aulas_aluno WHERE usuario_id = ?";
            $stmtLiberadas = $conn->prepare($sqlLiberadas);
            $stmtLiberadas->bind_param("i", $usuario_id);
            $stmtLiberadas->execute();
            $resLiberadas = $stmtLiberadas->get_result();

            $aulas_liberadas = [];
            while ($row = $resLiberadas->fetch_assoc()) {
              $aulas_liberadas[] = $row['aula_id'];
            }
            ?>

            <div class="aula-tabs" id="aulaTabs">
              <?php foreach ($aulas as $a): ?>
                <?php
                  $liberada = in_array($a['id'], $aulas_liberadas);
                  $isAtiva = ($a['id'] == $aula_id);
                  $classe = $liberada ? 'tab' : 'tab bloqueada';
                  if ($isAtiva) {
                    $classe .= ' ativa'; // adiciona classe 'ativa' à aba atual
                  }
                  $disabled = $liberada ? '' : 'disabled';
                  $id = $isAtiva ? 'id="abaAtiva"' : '';
                ?>
                <form method="get" style="display:inline;">
                  <input type="hidden" name="aula_id" value="<?php echo $a['id']; ?>">
                  <button type="submit"
                          class="<?php echo $classe; ?>"
                          <?php echo $id; ?>
                          <?php echo $disabled; ?>>
                    <?php echo htmlspecialchars($a['titulo']); ?>
                  </button>
                </form>
              <?php endforeach; ?>
            </div>

          </div>
          <!-- fim Navegação por abas -->

          <!-- Inicio controle de itens na tela -->
          <div class="lesson-header"><?php echo htmlspecialchars($aula_titulo); ?></div>
          
          <?php foreach ($conteudos as $conteudo): ?>
            <div class="content-group">
              <h3 class="content-title"><?php echo htmlspecialchars($conteudo['titulo']); ?></h3>
          
              <?php foreach ($conteudo['itens'] as $item): ?>
                <?php
                  // Verificar se o item foi concluído
                  $sqlCheck = "SELECT concluido, data_conclusao FROM interacoes_aluno WHERE usuario_id = ? AND item_id = ?";
                  $stmtCheck = $conn->prepare($sqlCheck);
                  $stmtCheck->bind_param("ii", $usuario_id, $item['id']);
                  $stmtCheck->execute();
                  $resCheck = $stmtCheck->get_result();
          
                  $concluido = false;
                  $dataConclusao = null;
                  if ($resCheck->num_rows > 0) {
                    $rowCheck = $resCheck->fetch_assoc();
                    $concluido = $rowCheck['concluido'];
                    $dataConclusao = $rowCheck['data_conclusao'];
                  }
          
                  $classeItem = $concluido ? 'completed' : '';
                  $iconeCheck = $concluido
                       ? '<span style="font-size:18px;">☑</span>'  // concluído
                        : '<span style="font-size:18px;">☐</span>'; // não concluído
                  //$iconeCheck = $concluido ? '✓' : 'O';
                  $tooltip = $dataConclusao
                    ? 'title="Concluído em ' . date('d/m/Y H:i', strtotime($dataConclusao)) . '"'
                    : '';

                ?>
                
<script>
  console.log("Item no menu:", {
    id: <?php echo json_encode($item['id']); ?>,
    titulo: <?php echo json_encode($item['titulo'] ?? ''); ?>,
    tipo: <?php echo json_encode($item['item_tipo'] ?? 'NÃO DEFINIDO'); ?>,
    material_id: <?php echo json_encode($item['id_material'] ?? null); ?>
  });
</script>






                <div class="content-item<?php echo $classeItem ? ' ' . $classeItem : ''; ?>"
                      onclick="if (!event.target.closest('.check-icon')) { abrirConteudo(<?php echo $item['id']; ?>, <?php echo isset($item['id_material']) ? $item['id_material'] : 'null'; ?>); }"


                   <?php echo $tooltip; ?>>
                 <?php //echo  $item['item_tipo'] ?>
                  <?php if ($item['item_tipo'] === 'video'): ?>
                    <span class="play-icon">▶</span> 
                  <?php endif; ?>

                  <span class="item-text"><?php echo htmlspecialchars($item['titulo']); ?></span>

<span class="check-icon2 <?php echo $classeItem; ?>"
  onclick="event.stopPropagation(); alternarConclusao(<?php echo $item['id']; ?>, this)">
  <?php echo $iconeCheck; ?>
</span>


                </div>

              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
          <!-- Fim - controle de itens na tela -->

<!-- Começo de montagem do Quiz, que segue lógica própria fora do foreach dos itens dos conteudos -->
<?php
// Buscar item e material do tipo quiz da aula
$sqlQuiz = "
  SELECT i.id AS item_id, m.id AS material_id, m.titulo
  FROM materiais_item m
  JOIN itens_aula i ON m.item_id = i.id
  JOIN conteudos c ON i.conteudo_id = c.id
  WHERE c.aula_id = ? AND m.tipo = 'quiz' AND m.exibir_no_menu = 0
  LIMIT 1
";

$stmtQuiz = $conn->prepare($sqlQuiz);
$stmtQuiz->bind_param("i", $aula_id);
$stmtQuiz->execute();
$resQuiz = $stmtQuiz->get_result();

if ($resQuiz->num_rows > 0):
  $quiz = $resQuiz->fetch_assoc(); ?>
  <div class="content-group">
    <div class="content-item quiz-final"
         onclick="abrirConteudo(<?php echo $quiz['item_id']; ?>, <?php echo $quiz['material_id']; ?>)">
      <span class="quiz-icon">❓</span>
      <span class="item-text"><?php echo htmlspecialchars($quiz['titulo'] ?? 'Quiz Aula ' . $aula_id); ?></span>
    </div>
  </div>
<?php endif; ?>
<!-- Fim de montagem da opção do menu para quiz -->



         <?php
         // Verificar se todos os itens da aula atual foram concluídos
         $sqlItensAula = "SELECT id FROM itens_aula WHERE conteudo_id IN (
                            SELECT id FROM conteudos WHERE aula_id = ?
                                 ) AND exibir_no_menu = TRUE";
         $stmtItensAula = $conn->prepare($sqlItensAula);
         $stmtItensAula->bind_param("i", $aula_id);
         $stmtItensAula->execute();
         $resItensAula = $stmtItensAula->get_result();

         $totalItens = 0;
         $itensConcluidos = 0;

         while ($item = $resItensAula->fetch_assoc()) {
           $totalItens++;

           $sqlCheck = "SELECT concluido FROM interacoes_aluno WHERE usuario_id = ? AND item_id = ?";
           $stmtCheck = $conn->prepare($sqlCheck);
           $stmtCheck->bind_param("ii", $usuario_id, $item['id']);
           $stmtCheck->execute();
           $resCheck = $stmtCheck->get_result();

           if ($resCheck->num_rows > 0) {
             $rowCheck = $resCheck->fetch_assoc();
             if ($rowCheck['concluido']) {
               $itensConcluidos++;
             }
           }
         }

         // Se todos os itens foram concluídos, liberar a próxima aula
         if ($totalItens > 0 && $itensConcluidos === $totalItens) {
           // Buscar próxima aula
           $proximaAula = null;
           foreach ($aulas as $index => $a) {
             if ($a['id'] == $aula_id && isset($aulas[$index + 1])) {
               $proximaAula = $aulas[$index + 1]['id'];
               break;
             }
           }

           if ($proximaAula) {
             // Verificar se já está liberada
             $sqlVerifica = "SELECT 1 FROM aulas_aluno WHERE usuario_id = ? AND aula_id = ?";
             $stmtVerifica = $conn->prepare($sqlVerifica);
             $stmtVerifica->bind_param("ii", $usuario_id, $proximaAula);
             $stmtVerifica->execute();
             $resVerifica = $stmtVerifica->get_result();

             if ($resVerifica->num_rows === 0) {
               // Liberar próxima aula
               $sqlLibera = "INSERT INTO aulas_aluno (usuario_id, aula_id) VALUES (?, ?)";
               $stmtLibera = $conn->prepare($sqlLibera);
               $stmtLibera->bind_param("ii", $usuario_id, $proximaAula);
               $stmtLibera->execute();
             }
           }
         }
         ?>

      </section>

      <!-- Right Panel -->
      <section class="right-panel">
        <div class="fixed-view-area">
          <!-- Botão de visualização -->
          <div class="view-header" id="backButtonArea" style="display: none;">
            <button class="back-button" onclick="mostrarVisualizacao()">← Visualização</button>
          </div>

          <!-- Área principal de visualização -->

          <!-- Área principal de visualização -->
<div class="content-display" id="contentDisplay">

  <div id="graph-wrapper" style="display:block; height:auto;">

    <h3 style="margin:0 0 6px 0;font-size:16px;color:#333;">
      Mapa de Conhecimentos da Aula 1
    </h3>
<!-- Barra de modos de visualização -->
<div id="graph-modes"
     style="margin-bottom:4px; display:flex; gap:6px; font-size:13px;">
  <button id="btn-modo-grafo"
          style="padding:4px 8px; border:1px solid #ccc; border-radius:4px; background:#e0f0ff; cursor:pointer;">
    🔁 Grafo
  </button>

  <button id="btn-modo-trilha"
          style="padding:4px 8px; border:1px solid #ccc; border-radius:4px; background:#f5f5f5; cursor:pointer;">
    🪜 Trilha
  </button>

  <button id="btn-modo-dashboard"
          style="padding:4px 8px; border:1px solid #ccc; border-radius:4px; background:#f5f5f5; cursor:pointer;">
    📊 Dashboard
  </button>
</div>

<!-- Legenda (separada, alinhada à direita e mais baixa) -->
<div id="vis-legend"
     style="
       text-align:right;
       margin-bottom:10px;
       margin-top:-6px;
       font-size:13px;
       color:#444;
     ">
  <b>Legenda:</b>
  <span style="margin-left:12px;">
    <span style="color:#2ecc71; font-weight:bold;">●</span> Compreendido
  </span>
  <span style="margin-left:12px;">
    <span style="color:#e67e22; font-weight:bold;">●</span> Ponto de Atenção
  </span>
  <span style="margin-left:12px;">
    <span style="color:#95a5a6; font-weight:bold;">●</span> Não Avaliado
  </span>
</div>




    <!-- (1) Área do grafo / trilha / dashboard -->
    <div id="graph-area"
         style="height:300px;border:1px solid #ddd;border-radius:8px;
                padding:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
      <!-- O conteúdo (SVG ou HTML) será inserido via JavaScript -->
    </div>

    <!-- (2) Texto explicativo do conhecimento -->
    <div id="graph-info"
         style="margin-top:8px;border:1px solid #eee;border-radius:6px;
                padding:8px 10px;font-size:14px;color:#333;
                min-height:80px;">
      Passe o mouse sobre um conhecimento para ver uma explicação.
    </div>

    <!-- (3) Sugestões de estudo -->
    <div id="graph-suggestions"
         style="margin-top:8px;border:1px solid #eee;border-radius:6px;
                padding:8px 10px;font-size:14px;color:#333;max-height:180px;overflow:auto;">
      Clique em um conhecimento para ver sugestões de estudo relacionadas.
    </div>

  </div>

</div>



          <!-- Sugestões
          <div class="suggestions-scroll" id="suggestionsScroll">
            <div class="suggestions-header">Sugestões de Aprendizagem</div>
            <div class="suggestion-item" onclick="abrirConteudo('sugestao-video')">
              <span class="play-icon">▶</span>
              <span class="suggestion-text">Vídeo alternativo sobre Fluxo de ações</span>
              <span class="check-icon">○</span>
            </div>
            <div class="suggestion-item" onclick="abrirConteudo('sugestao-texto')">
              <span class="info-icon">i</span>
              <span class="suggestion-text">Texto complementar sobre Quiz</span>
              <span class="check-icon">○</span>
            </div>
          </div>
          -->

        </div>
      </section>

    </main>
  </div>

  <script src="../js/script.js"></script>
  <script src="../js/curso-visualizacao.js" defer></script>
  <script src="../js/curso-adaptativo.js?v=<?php echo time(); ?>" defer></script>
  <!-- arquivo js criado para separar o processo de refatoração da fase 1 para fase adaptativa -->
</body>
</html>
